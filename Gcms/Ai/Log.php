<?php
/**
 * @filesource Gcms/Ai/Log.php
 *
 * @copyright 2026 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Gcms\Ai;

/**
 * AI conversation log
 *
 * Records every provider exchange — the prompt that was sent, the answer that
 * came back, token usage, latency and any error — so a failed or surprising
 * answer can be inspected after the fact instead of being lost with the
 * request. Every driver funnels through Driver::post(), postBatch() and
 * streamSse(), so no provider needs its own logging code.
 *
 * Enabled by the AI_LOG constant. Entries go to the `logs` table by default,
 * where index/usage lists them under module "ai"; AI_LOG_DESTINATION switches
 * to (or adds) the AI_LOG_FILE text log. Retention and the per-field length cap
 * come from AI_LOG_RETENTION_DAYS and AI_LOG_MAX_LENGTH, and AI_LOG_RAW adds
 * the untouched provider payloads.
 *
 * Table row: module "ai", action the exchange kind ("chat", "stream", "batch",
 * "get", "image") or "error" when the call failed, topic the prompt, reason the
 * answer or the error, datas the full record as JSON.
 *
 * File line, in the standard Kotchasan log format:
 *   [2026-08-27 10:36:12] [INFO] AI chat {"provider":"openrouter", ...}
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Log
{
    /**
     * Logger instance writing to AI_LOG_FILE.
     *
     * @var \Kotchasan\Logger\RetentionFileLogger|null
     */
    private static $logger = null;

    /**
     * Caller label set by application code, overriding backtrace detection.
     *
     * @var string
     */
    private static $context = '';

    /**
     * Whether the `logs` table has already been trimmed in this request.
     *
     * @var bool
     */
    private static $cleaned = false;

    /**
     * Whether AI logging is enabled.
     *
     * @return bool
     */
    public static function enabled()
    {
        // ROOT_PATH is missing outside the application bootstrap (unit tests),
        // where there is nowhere to log to.
        return defined('ROOT_PATH') && (!defined('AI_LOG') || AI_LOG);
    }

    /**
     * Where entries are written: LOG_DB (default), LOG_FILE or LOG_BOTH.
     *
     * @return string
     */
    private static function destination()
    {
        $destination = defined('AI_LOG_DESTINATION') ? strtoupper((string) AI_LOG_DESTINATION) : 'LOG_DB';

        return in_array($destination, ['LOG_DB', 'LOG_FILE', 'LOG_BOTH'], true) ? $destination : 'LOG_DB';
    }

    /**
     * Label the feature that is about to talk to the AI, e.g. 'chat.fallback'.
     * Without it the caller is derived from the call stack.
     *
     * @param string $context Label, or '' to clear
     *
     * @return void
     */
    public static function context($context)
    {
        self::$context = trim((string) $context);
    }

    /**
     * Record one provider exchange.
     *
     * Supported keys in $entry:
     *   kind     string  chat | batch | stream | image | get
     *   provider string  Provider name
     *   url      string  Endpoint that was called
     *   payload  array   Request body that was sent
     *   response array   Decoded provider response, if any
     *   status   int     HTTP status (0 = no response / transport error)
     *   started  float   microtime(true) taken before the request
     *   error    string  Transport-level error, when the request never landed
     *   note     string  Extra detail for the entry
     *
     * @param array $entry
     *
     * @return void
     */
    public static function exchange(array $entry)
    {
        if (!self::enabled()) {
            return;
        }

        $payload = isset($entry['payload']) && is_array($entry['payload']) ? $entry['payload'] : [];
        $response = isset($entry['response']) && is_array($entry['response']) ? $entry['response'] : [];
        $status = isset($entry['status']) ? (int) $entry['status'] : 0;

        $request = self::summarizeRequest($payload);
        $answer = self::summarizeResponse($response);
        $error = trim((string) ($entry['error'] ?? ''));
        if ($error === '') {
            $error = $answer['error'];
        }
        $ok = $error === '' && ($status === 0 || ($status >= 200 && $status < 300));

        $record = [
            'kind' => (string) ($entry['kind'] ?? 'chat'),
            'context' => self::resolveContext(),
            'provider' => (string) ($entry['provider'] ?? ''),
            'model' => $answer['model'] !== '' ? $answer['model'] : (string) ($payload['model'] ?? ''),
            'url' => self::maskUrl((string) ($entry['url'] ?? '')),
            'status' => $status,
            'ok' => $ok
        ];
        if (!empty($entry['started'])) {
            $record['duration_ms'] = (int) round((microtime(true) - (float) $entry['started']) * 1000);
        }
        if ($request['system'] !== '') {
            $record['system'] = $request['system'];
        }
        if ($request['messages'] > 0) {
            $record['messages'] = $request['messages'];
        }
        if ($request['prompt'] !== '') {
            $record['prompt'] = $request['prompt'];
        }
        $content = $answer['content'] !== '' ? $answer['content'] : self::truncate((string) ($entry['answer'] ?? ''));
        if ($content !== '') {
            $record['answer'] = $content;
        }
        if ($answer['reasoning'] !== '') {
            $record['reasoning'] = $answer['reasoning'];
        }
        if ($answer['finish'] !== '') {
            $record['finish'] = $answer['finish'];
        }
        if ($answer['inputTokens'] || $answer['outputTokens']) {
            $record['tokens'] = $answer['inputTokens'].'/'.$answer['outputTokens'];
        }
        if ($error !== '') {
            $record['error'] = self::truncate($error);
        }
        if (!empty($entry['note'])) {
            $record['note'] = self::truncate((string) $entry['note']);
        }
        if (defined('AI_LOG_RAW') && AI_LOG_RAW) {
            $record['raw_request'] = self::redact($payload);
            $record['raw_response'] = $response;
        }

        $destination = self::destination();
        $written = false;
        if ($destination === 'LOG_DB' || $destination === 'LOG_BOTH') {
            $written = self::writeDatabase($record);
        }
        // Never lose an entry: fall back to the file when the table is
        // unreachable, which is exactly when something is going wrong.
        if ($destination !== 'LOG_DB' || !$written) {
            self::writeFile($record, $ok);
        }
    }

    /**
     * Insert one entry into the `logs` table, so it shows up in index/usage
     * alongside the rest of the activity log.
     *
     * @param array $record
     *
     * @return bool False when the row could not be written
     */
    private static function writeDatabase(array $record)
    {
        $prompt = (string) ($record['prompt'] ?? '');
        if ($prompt === '') {
            $prompt = 'AI '.$record['kind'].' — '.($record['model'] !== '' ? $record['model'] : $record['provider']);
        }
        $reason = (string) ($record['error'] ?? ($record['answer'] ?? ''));
        $login = class_exists('\Kotchasan\Login') ? \Kotchasan\Login::isMember() : null;

        try {
            \Kotchasan\DB::create()->insert('logs', [
                'src_id' => 0,
                'module' => 'ai',
                'action' => empty($record['ok']) ? 'error' : $record['kind'],
                'created_at' => date('Y-m-d H:i:s'),
                'member_id' => empty($login->id) ? 0 : (int) $login->id,
                'topic' => $prompt,
                'reason' => $reason,
                'datas' => self::encode($record)
            ]);
            self::cleanupDatabase();
        } catch (\Exception $e) {
            error_log('AI log insert failed: '.$e->getMessage());
            return false;
        }

        return true;
    }

    /**
     * Drop AI rows older than AI_LOG_RETENTION_DAYS, at most once a day.
     * Other modules' log rows are left alone.
     *
     * @return void
     */
    private static function cleanupDatabase()
    {
        $days = defined('AI_LOG_RETENTION_DAYS') ? (int) AI_LOG_RETENTION_DAYS : 30;
        if ($days <= 0 || self::$cleaned) {
            return;
        }
        self::$cleaned = true;

        // A marker file keeps the daily sweep from running on every request;
        // without a writable marker it runs once per request at most.
        $marker = ROOT_PATH.dirname(self::logFile()).'/.ai_log_db.cleanup';
        if (is_file($marker) && (time() - (int) filemtime($marker)) < 86400) {
            return;
        }

        \Kotchasan\DB::create()->delete('logs', [
            ['module', 'ai'],
            ['created_at', '<', date('Y-m-d H:i:s', time() - ($days * 86400))]
        ], 0);
        @touch($marker);
    }

    /**
     * Append one entry to the AI_LOG_FILE text log.
     *
     * @param array $record
     * @param bool  $ok
     *
     * @return void
     */
    private static function writeFile(array $record, $ok)
    {
        $message = 'AI '.$record['kind'].' '.self::encode($record);
        if ($ok) {
            self::logger()->info($message);
        } else {
            self::logger()->error($message);
        }
    }

    /**
     * Record a failure that never reached the provider, such as an unusable
     * endpoint URL or a driver-level refusal.
     *
     * @param string $provider
     * @param string $kind
     * @param string $error
     * @param array  $entry Extra keys merged into the record
     *
     * @return void
     */
    public static function failure($provider, $kind, $error, array $entry = [])
    {
        self::exchange(array_merge($entry, [
            'kind' => $kind,
            'provider' => $provider,
            'error' => $error
        ]));
    }

    /**
     * Pull the system prompt, the last user turn and the message count out of
     * a provider request body. Handles the OpenAI/Claude ('messages') and
     * Gemini ('contents') shapes plus the image-generation ('prompt') shape.
     *
     * @param array $payload
     *
     * @return array{system:string,prompt:string,messages:int}
     */
    private static function summarizeRequest(array $payload)
    {
        $system = '';
        $prompt = '';
        $count = 0;

        if (isset($payload['system'])) {
            $system = self::flatten($payload['system']);
        } elseif (isset($payload['system_instruction'])) {
            $system = self::flatten($payload['system_instruction']);
        } elseif (isset($payload['systemInstruction'])) {
            $system = self::flatten($payload['systemInstruction']);
        }

        if (!empty($payload['messages']) && is_array($payload['messages'])) {
            $count = count($payload['messages']);
            foreach ($payload['messages'] as $message) {
                $role = is_array($message) ? ($message['role'] ?? '') : '';
                $text = self::flatten(is_array($message) ? ($message['content'] ?? '') : $message);
                if ($role === 'system' && $system === '') {
                    $system = $text;
                } elseif ($text !== '') {
                    $prompt = $text;
                }
            }
        } elseif (!empty($payload['contents']) && is_array($payload['contents'])) {
            $count = count($payload['contents']);
            foreach ($payload['contents'] as $content) {
                $text = self::flatten(is_array($content) ? ($content['parts'] ?? $content) : $content);
                if ($text !== '') {
                    $prompt = $text;
                }
            }
        } elseif (isset($payload['prompt'])) {
            $prompt = self::flatten($payload['prompt']);
            $count = 1;
        }

        return [
            'system' => self::truncate($system),
            'prompt' => self::truncate($prompt),
            'messages' => $count
        ];
    }

    /**
     * Pull the answer text, model, token usage and error out of a decoded
     * provider response. Handles the OpenAI, Claude and Gemini shapes as well
     * as the ['error' => '...'] shape produced by Driver on transport failure.
     *
     * @param array $raw
     *
     * @return array{content:string,reasoning:string,model:string,finish:string,inputTokens:int,outputTokens:int,error:string}
     */
    private static function summarizeResponse(array $raw)
    {
        $result = [
            'content' => '',
            'reasoning' => '',
            'model' => isset($raw['model']) && is_string($raw['model']) ? $raw['model'] : '',
            'finish' => '',
            'inputTokens' => 0,
            'outputTokens' => 0,
            'error' => ''
        ];
        if (empty($raw)) {
            return $result;
        }

        if (isset($raw['error'])) {
            $result['error'] = is_array($raw['error'])
                ? self::flatten($raw['error']['message'] ?? $raw['error'])
                : self::flatten($raw['error']);
        }

        if (!empty($raw['choices'][0]) && is_array($raw['choices'][0])) {
            // OpenAI-compatible
            $choice = $raw['choices'][0];
            $result['content'] = self::flatten($choice['message']['content'] ?? ($choice['text'] ?? ''));
            $result['reasoning'] = self::flatten($choice['message']['reasoning_content'] ?? '');
            $result['finish'] = (string) ($choice['finish_reason'] ?? '');
            $result['inputTokens'] = (int) ($raw['usage']['prompt_tokens'] ?? 0);
            $result['outputTokens'] = (int) ($raw['usage']['completion_tokens'] ?? 0);
        } elseif (!empty($raw['content']) && is_array($raw['content'])) {
            // Anthropic Claude
            $result['content'] = self::flatten($raw['content']);
            $result['finish'] = (string) ($raw['stop_reason'] ?? '');
            $result['inputTokens'] = (int) ($raw['usage']['input_tokens'] ?? 0);
            $result['outputTokens'] = (int) ($raw['usage']['output_tokens'] ?? 0);
        } elseif (!empty($raw['candidates'][0]) && is_array($raw['candidates'][0])) {
            // Google Gemini
            $candidate = $raw['candidates'][0];
            $result['content'] = self::flatten($candidate['content']['parts'] ?? $candidate['content'] ?? '');
            $result['finish'] = (string) ($candidate['finishReason'] ?? '');
            $result['model'] = $result['model'] !== '' ? $result['model'] : (string) ($raw['modelVersion'] ?? '');
            $result['inputTokens'] = (int) ($raw['usageMetadata']['promptTokenCount'] ?? 0);
            $result['outputTokens'] = (int) ($raw['usageMetadata']['candidatesTokenCount'] ?? 0);
        } elseif (!empty($raw['data']) && is_array($raw['data'])) {
            // Image generation — never log the base64 payload, only its shape
            $result['content'] = count($raw['data']).' image(s)';
        }

        $result['content'] = self::truncate($result['content']);
        $result['reasoning'] = self::truncate($result['reasoning']);

        if ($result['content'] === '' && $result['error'] === '' && !empty($raw)) {
            $result['error'] = self::truncate('Empty answer: '.self::encode($raw));
        }

        return $result;
    }

    /**
     * Reduce a provider content value — string, parts array or message array —
     * to plain text.
     *
     * @param mixed $value
     *
     * @return string
     */
    private static function flatten($value)
    {
        if (is_string($value)) {
            return trim($value);
        }
        if (is_scalar($value)) {
            return trim((string) $value);
        }
        if (!is_array($value)) {
            return '';
        }

        $parts = [];
        foreach ($value as $key => $item) {
            if (is_string($item)) {
                // Skip type discriminators such as ['type' => 'text']
                if ($key === 'type' || $key === 'role' || $key === 'mime_type') {
                    continue;
                }
                $parts[] = trim($item);
            } elseif (is_array($item)) {
                $text = self::flatten($item);
                if ($text !== '') {
                    $parts[] = $text;
                }
            }
        }

        return trim(implode(' ', array_filter($parts, static function ($part) {
            return $part !== '';
        })));
    }

    /**
     * Shorten a value to AI_LOG_MAX_LENGTH characters, marking what was cut.
     *
     * @param string $text
     *
     * @return string
     */
    private static function truncate($text)
    {
        $text = (string) $text;
        $max = defined('AI_LOG_MAX_LENGTH') ? (int) AI_LOG_MAX_LENGTH : 2000;
        if ($max <= 0 || mb_strlen($text, 'UTF-8') <= $max) {
            return $text;
        }

        return mb_substr($text, 0, $max, 'UTF-8').'… (+'.(mb_strlen($text, 'UTF-8') - $max).' chars)';
    }

    /**
     * Hide the API key some providers carry in the query string.
     *
     * @param string $url
     *
     * @return string
     */
    private static function maskUrl($url)
    {
        return preg_replace('/([?&](?:key|api_key|access_token)=)[^&]+/i', '$1***', (string) $url);
    }

    /**
     * Strip credential-looking keys from a payload before it is written.
     *
     * @param array $payload
     *
     * @return array
     */
    private static function redact(array $payload)
    {
        foreach ($payload as $key => $value) {
            if (is_array($value)) {
                $payload[$key] = self::redact($value);
            } elseif (preg_match('/(api_?key|token|secret|password)/i', (string) $key)) {
                $payload[$key] = '***';
            }
        }

        return $payload;
    }

    /**
     * JSON-encode a record on one line, keeping Thai text readable.
     *
     * @param array $record
     *
     * @return string
     */
    private static function encode(array $record)
    {
        $json = json_encode($record, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE);

        return $json === false ? '{"error":"log encode failed"}' : $json;
    }

    /**
     * Name the application code that triggered the request: the explicit
     * context() label, or the first stack frame outside the AI driver layer.
     *
     * @return string
     */
    private static function resolveContext()
    {
        if (self::$context !== '') {
            return self::$context;
        }

        foreach (debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 12) as $frame) {
            $class = $frame['class'] ?? '';
            if ($class === '' || strpos($class, 'Gcms\\Ai') === 0) {
                continue;
            }

            return $class.'::'.($frame['function'] ?? '');
        }

        return 'unknown';
    }

    /**
     * Shared logger instance.
     *
     * @return \Kotchasan\Logger\RetentionFileLogger
     */
    private static function logger()
    {
        if (self::$logger === null) {
            $days = defined('AI_LOG_RETENTION_DAYS') ? (int) AI_LOG_RETENTION_DAYS : 30;
            self::$logger = new \Kotchasan\Logger\RetentionFileLogger(self::logFile(), $days);
        }

        return self::$logger;
    }

    /**
     * Text log path, relative to ROOT_PATH.
     *
     * @return string
     */
    private static function logFile()
    {
        return defined('AI_LOG_FILE') ? AI_LOG_FILE : 'datas/logs/ai_log.php';
    }
}

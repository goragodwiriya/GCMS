<?php
/**
 * @filesource modules/index/controllers/aiwriter.php
 *
 * AI Writer Controller for RichTextEditor
 *
 * Endpoints:
 *   POST /api/index/aiwriter/generate  - create original HTML content from a prompt
 *   POST /api/index/aiwriter/rewrite   - rewrite existing HTML content into a clean HTML fragment
 *   POST /api/index/aiwriter/metadata  - generate SEO metadata (title, keywords, description, slug, image prompt)
 *   POST /api/index/aiwriter/image     - generate an image from a prompt
 *
 * @copyright 2026 Goragod.com
 * @license https://www.kotchasan.com/license/
 */

namespace Index\Aiwriter;

use Gcms\Ai;
use Gcms\Api as ApiController;
use Kotchasan\Curl;
use Kotchasan\Http\Request;

class Controller extends ApiController
{
    /**
     * System prompt for content generation.
     */
    private const GENERATE_SYSTEM_PROMPT = <<<'PROMPT'
You write publication-ready HTML fragments for the GCMS RichTextEditor.
Return ONLY an HTML fragment. Do not return markdown fences, explanations, or JSON.

Rules:
- Do not output <html>, <head>, <body>, <script>, <style>, <form>, or inline event handlers.
- Do not use inline styles, ids, or arbitrary CSS classes.
- Do not use <hr>.
- Use clean semantic HTML such as p, h2, h3, h4, ul, ol, li, blockquote, table, thead, tbody, tr, th, td, strong, em, and a when needed.
- Tables must be clean semantic tables without presentation attributes.
- If classes are explicitly allowed by the user message, use only those classes and no others.
PROMPT;

    /**
     * System prompt for SEO metadata generation.
     */
    private const METADATA_SYSTEM_PROMPT = <<<'PROMPT'
You produce SEO-focused metadata for CMS article editing.
Return ONLY valid JSON. No markdown fences. No explanation.

Output schema:
{
    "topic": "string",
    "keywords": ["string", "string"],
    "description": "string",
    "slug": "string",
    "image_prompt": "string"
}

Rules:
- Keep topic concise, compelling, and search-friendly.
- description should be concise meta-description text suitable for search snippets.
- keywords should be short, specific, and relevant to the content.
- slug must be URL-friendly and concise.
- If a field cannot be improved, keep it close to source meaning.
- image_prompt should describe a featured image concept relevant to the article.
PROMPT;

    /**
     * System prompt for content rewriting.
     */
    private const REWRITE_SYSTEM_PROMPT = <<<'PROMPT'
You rewrite user-provided HTML into a fresh, clean HTML fragment for the GCMS RichTextEditor.
Return ONLY an HTML fragment. Do not return markdown fences, explanations, or JSON.

Rules:
- Preserve the important facts, structure, and meaning from the source.
- Rewrite in new wording instead of copying phrases mechanically.
- Do not output <html>, <head>, <body>, <script>, <style>, <form>, or inline event handlers.
- Do not use inline styles, ids, or arbitrary CSS classes.
- Do not use <hr>.
- Keep semantic structures such as headings, lists, quotes, links, and tables when relevant.
- Tables must be clean semantic tables without presentation attributes.
- If classes are explicitly allowed by the user message, use only those classes and no others.
PROMPT;

    /**
     * Create original content from a prompt.
     *
     * @param Request $request
     *
     * @return mixed
     */
    public function generate(Request $request)
    {
        ApiController::validateMethod($request, 'POST');

        $login = $this->authenticateRequest($request);
        if (!$login) {
            return $this->errorResponse('Unauthorized', 401);
        }
        if (!$this->canUseAiWriter($login)) {
            return $this->errorResponse('Forbidden', 403);
        }
        if (empty(self::$cfg->ai_enabled)) {
            return $this->errorResponse('AI connector is disabled. Enable it in Settings -> AI.', 503);
        }

        $body = $this->jsonBody($request);
        $prompt = trim((string) ($body['prompt'] ?? ''));
        if ($prompt === '') {
            return $this->errorResponse('prompt is required', 400);
        }

        $contextHtml = trim((string) ($body['context_html'] ?? ''));
        $allowedClasses = $this->normalizeAllowedClasses($body['allowed_classes'] ?? []);

        $userMessage = "Create original HTML content from this brief:\n{$prompt}\n";
        if ($contextHtml !== '') {
            $userMessage .= "\nCurrent document context (use only when it helps continuity):\n{$contextHtml}\n";
        }
        $userMessage .= $this->buildAllowedClassesInstruction($allowedClasses);

        try {
            $response = Ai::driver()->chat(
                [['role' => 'user', 'content' => $userMessage]],
                [
                    'system' => self::GENERATE_SYSTEM_PROMPT,
                    'max_tokens' => 2048,
                    'temperature' => 0.7
                ]
            );
        } catch (\InvalidArgumentException $e) {
            return $this->errorResponse($e->getMessage(), 400);
        } catch (\Kotchasan\ApiException $e) {
            return $this->errorResponse($e->getMessage(), $e->getCode() ?: 400, $e);
        } catch (\Exception $e) {
            return $this->errorResponse('AI generation failed: '.$e->getMessage(), 500);
        }

        if (!$response->success) {
            return $this->errorResponse('AI generation failed: '.$response->error, 502);
        }

        $html = $this->extractHtmlFragment($response->content);
        if ($html === '') {
            return $this->errorResponse('AI returned an empty response. Try again with a more specific prompt.', 422);
        }

        return $this->successResponse([
            'html' => $html,
            'model' => $response->model,
            'tokens' => (int) $response->inputTokens + (int) $response->outputTokens
        ], 'Content generated');
    }

    /**
     * Rewrite existing content into a clean HTML fragment.
     *
     * @param Request $request
     *
     * @return mixed
     */
    public function rewrite(Request $request)
    {
        ApiController::validateMethod($request, 'POST');

        $login = $this->authenticateRequest($request);
        if (!$login) {
            return $this->errorResponse('Unauthorized', 401);
        }
        if (!$this->canUseAiWriter($login)) {
            return $this->errorResponse('Forbidden', 403);
        }
        if (empty(self::$cfg->ai_enabled)) {
            return $this->errorResponse('AI connector is disabled. Enable it in Settings -> AI.', 503);
        }

        $body = $this->jsonBody($request);
        $instruction = trim((string) ($body['prompt'] ?? 'Rewrite this content in new words while preserving the important facts.'));
        if ($instruction === '') {
            return $this->errorResponse('prompt is required', 400);
        }

        $contentHtml = trim((string) ($body['content_html'] ?? ''));
        $contextHtml = trim((string) ($body['context_html'] ?? ''));
        $allowedClasses = $this->normalizeAllowedClasses($body['allowed_classes'] ?? []);
        $isGenerateFromPrompt = $contentHtml === '';

        if ($isGenerateFromPrompt) {
            $userMessage = "Create original HTML content from this brief:\n{$instruction}\n";
            if ($contextHtml !== '') {
                $userMessage .= "\nCurrent document context (use only when it helps continuity):\n{$contextHtml}\n";
            }
        } else {
            $userMessage = "Rewrite the following HTML fragment.\n";
            $userMessage .= "Instruction: {$instruction}\n\n";
            $userMessage .= "Source HTML:\n{$contentHtml}\n";
        }
        $userMessage .= $this->buildAllowedClassesInstruction($allowedClasses);

        try {
            $response = Ai::driver()->chat(
                [['role' => 'user', 'content' => $userMessage]],
                [
                    'system' => $isGenerateFromPrompt ? self::GENERATE_SYSTEM_PROMPT : self::REWRITE_SYSTEM_PROMPT,
                    'max_tokens' => 2048,
                    'temperature' => $isGenerateFromPrompt ? 0.7 : 0.55
                ]
            );
        } catch (\InvalidArgumentException $e) {
            return $this->errorResponse($e->getMessage(), 400);
        } catch (\Kotchasan\ApiException $e) {
            return $this->errorResponse($e->getMessage(), $e->getCode() ?: 400, $e);
        } catch (\Exception $e) {
            return $this->errorResponse('AI rewrite failed: '.$e->getMessage(), 500);
        }

        if (!$response->success) {
            return $this->errorResponse('AI rewrite failed: '.$response->error, 502);
        }

        $html = $this->extractHtmlFragment($response->content);
        if ($html === '') {
            return $this->errorResponse('AI returned an empty response. Try again with a more specific instruction.', 422);
        }

        return $this->successResponse([
            'html' => $html,
            'model' => $response->model,
            'tokens' => (int) $response->inputTokens + (int) $response->outputTokens
        ], 'Content rewritten');
    }

    /**
     * Generate SEO metadata suggestions from article inputs.
     *
     * @param Request $request
     *
     * @return mixed
     */
    public function metadata(Request $request)
    {
        ApiController::validateMethod($request, 'POST');

        $login = $this->authenticateRequest($request);
        if (!$login) {
            return $this->errorResponse('Unauthorized', 401);
        }
        if (!$this->canUseAiWriter($login)) {
            return $this->errorResponse('Forbidden', 403);
        }
        if (empty(self::$cfg->ai_enabled)) {
            return $this->errorResponse('AI connector is disabled. Enable it in Settings -> AI.', 503);
        }

        $body = $this->jsonBody($request);
        $topic = trim((string) ($body['topic'] ?? ''));
        $keywords = trim((string) ($body['keywords'] ?? ''));
        $description = trim((string) ($body['description'] ?? ''));
        $detailHtml = trim((string) ($body['detail_html'] ?? ''));

        if ($topic === '' && $keywords === '' && $description === '' && $detailHtml === '') {
            return $this->errorResponse('At least one of topic, keywords, description, or detail_html is required.', 400);
        }

        $mode = trim((string) ($body['mode'] ?? 'seo'));
        $targetLanguage = trim((string) ($body['target_language'] ?? ''));
        $tone = trim((string) ($body['tone'] ?? ''));
        $mood = trim((string) ($body['mood'] ?? ''));
        $audience = trim((string) ($body['audience'] ?? ''));
        $seoFocus = !empty($body['seo_focus']);
        $includeImagePrompt = !empty($body['include_image_prompt']);

        $userMessage = $this->buildMetadataUserPrompt([
            'topic' => $topic,
            'keywords' => $keywords,
            'description' => $description,
            'detail_html' => $detailHtml,
            'mode' => $mode,
            'target_language' => $targetLanguage,
            'tone' => $tone,
            'mood' => $mood,
            'audience' => $audience,
            'seo_focus' => $seoFocus,
            'include_image_prompt' => $includeImagePrompt
        ]);

        try {
            $response = Ai::driver()->chat(
                [['role' => 'user', 'content' => $userMessage]],
                [
                    'system' => self::METADATA_SYSTEM_PROMPT,
                    'max_tokens' => 1200,
                    'temperature' => $seoFocus ? 0.45 : 0.6
                ]
            );
        } catch (\InvalidArgumentException $e) {
            return $this->errorResponse($e->getMessage(), 400);
        } catch (\Kotchasan\ApiException $e) {
            return $this->errorResponse($e->getMessage(), $e->getCode() ?: 400, $e);
        } catch (\Exception $e) {
            return $this->errorResponse('AI metadata generation failed: '.$e->getMessage(), 500);
        }

        if (!$response->success) {
            return $this->errorResponse('AI metadata generation failed: '.$response->error, 502);
        }

        $metadata = $this->parseMetadataResponse($response->content);

        $resultTopic = trim((string) ($metadata['topic'] ?? $topic));
        $resultDescription = trim((string) ($metadata['description'] ?? $description));

        $keywordList = $this->normalizeKeywordList($metadata['keywords'] ?? $keywords);
        if (empty($keywordList) && $keywords !== '') {
            $keywordList = $this->normalizeKeywordList($keywords);
        }

        $slugSource = trim((string) ($metadata['slug'] ?? ''));
        if ($slugSource === '') {
            $slugSource = $resultTopic !== '' ? $resultTopic : $topic;
        }
        $slug = \Web\Gcms::aliasName($slugSource);

        $imagePrompt = trim((string) ($metadata['image_prompt'] ?? ''));
        if (!$includeImagePrompt) {
            $imagePrompt = '';
        }

        return $this->successResponse([
            'topic' => $resultTopic,
            'keywords' => implode(', ', $keywordList),
            'keywords_list' => $keywordList,
            'description' => $resultDescription,
            'slug' => $slug,
            'image_prompt' => $imagePrompt,
            'model' => $response->model,
            'tokens' => (int) $response->inputTokens + (int) $response->outputTokens
        ], 'Metadata generated');
    }

    /**
     * Generate an AI image from a prompt.
     *
     * @param Request $request
     *
     * @return mixed
     */
    public function image(Request $request)
    {
        ApiController::validateMethod($request, 'POST');

        $login = $this->authenticateRequest($request);
        if (!$login) {
            return $this->errorResponse('Unauthorized', 401);
        }
        if (!$this->canUseAiWriter($login)) {
            return $this->errorResponse('Forbidden', 403);
        }
        if (empty(self::$cfg->ai_enabled)) {
            return $this->errorResponse('AI connector is disabled. Enable it in Settings -> AI.', 503);
        }

        $body = $this->jsonBody($request);
        $prompt = trim((string) ($body['prompt'] ?? ''));
        if ($prompt === '') {
            return $this->errorResponse('prompt is required', 400);
        }

        $size = $this->normalizeImageSize($body['size'] ?? '1024x1024');

        try {
            /** @var \Gcms\Ai\Driver $driver */
            $driver = Ai::driver();

            $response = $driver->generateImage($prompt, [
                'size' => $size,
                'count' => 1
            ]);
        } catch (\InvalidArgumentException $e) {
            return $this->errorResponse($e->getMessage(), 400);
        } catch (\Kotchasan\ApiException $e) {
            return $this->errorResponse($e->getMessage(), $e->getCode() ?: 400, $e);
        } catch (\Exception $e) {
            return $this->errorResponse('AI image generation failed: '.$e->getMessage(), 500);
        }

        if (!$response->success) {
            $status = stripos($response->error, 'not supported') !== false || stripos($response->error, 'supported only') !== false
                ? 501
                : 502;

            return $this->errorResponse('AI image generation failed: '.$response->error, $status);
        }

        if (empty($response->images)) {
            return $this->errorResponse('AI image generation returned no images.', 422);
        }

        try {
            $images = $this->normalizeGeneratedImagesForResponse($response->images);
        } catch (\RuntimeException $e) {
            return $this->errorResponse('AI image processing failed: '.$e->getMessage(), 500);
        }

        if (empty($images)) {
            return $this->errorResponse('AI image generation returned no usable images.', 422);
        }

        return $this->successResponse([
            'images' => $images,
            'model' => $response->model,
            'tokens' => (int) $response->inputTokens + (int) $response->outputTokens
        ], 'Image generated');
    }

    /**
     * Decode request body from form POST or raw JSON.
     */
    private function jsonBody(Request $request): array
    {
        $raw = $request->getParsedBody();
        if (is_array($raw) && !empty($raw)) {
            return $raw;
        }

        $content = (string) $request->getBody();
        if ($content !== '') {
            $decoded = json_decode($content, true);
            if (is_array($decoded)) {
                return $decoded;
            }
        }

        return [];
    }

    /**
     * Restrict AI writer to admins or config-capable users.
     *
     * @param object|null $login
     *
     * @return bool
     */
    private function canUseAiWriter($login): bool
    {
        return ApiController::isAdmin($login)
        || ApiController::hasPermission($login, ['can_config', 'can_write', 'can_approve']);
    }

    /**
     * Build SEO metadata prompt from article values and options.
     *
     * @param array $data
     *
     * @return string
     */
    private function buildMetadataUserPrompt(array $data): string
    {
        $message = "Source article values:\n";
        if (!empty($data['topic'])) {
            $message .= "- topic: {$data['topic']}\n";
        }
        if (!empty($data['keywords'])) {
            $message .= "- keywords: {$data['keywords']}\n";
        }
        if (!empty($data['description'])) {
            $message .= "- description: {$data['description']}\n";
        }
        if (!empty($data['detail_html'])) {
            $message .= "- detail_html:\n{$data['detail_html']}\n";
        }

        $message .= "\nTask options:\n";
        $message .= "- mode: ".($data['mode'] !== '' ? $data['mode'] : 'seo')."\n";
        if (!empty($data['target_language'])) {
            $message .= "- target_language: {$data['target_language']}\n";
        }
        if (!empty($data['tone'])) {
            $message .= "- tone: {$data['tone']}\n";
        }
        if (!empty($data['mood'])) {
            $message .= "- mood: {$data['mood']}\n";
        }
        if (!empty($data['audience'])) {
            $message .= "- audience: {$data['audience']}\n";
        }
        $message .= "- seo_focus: ".(!empty($data['seo_focus']) ? 'true' : 'false')."\n";
        $message .= "- include_image_prompt: ".(!empty($data['include_image_prompt']) ? 'true' : 'false')."\n";

        return $message;
    }

    /**
     * Parse metadata JSON response.
     *
     * @param string $content
     *
     * @return array
     */
    private function parseMetadataResponse(string $content): array
    {
        $text = trim($content);
        if ($text === '') {
            return [];
        }

        if (preg_match('/```(?:json)?\s*(.*?)\s*```/is', $text, $match)) {
            $text = trim($match[1]);
        }

        $decoded = json_decode($text, true);
        if (is_array($decoded)) {
            return $decoded;
        }

        if (preg_match('/\{[\s\S]*\}/', $text, $match)) {
            $decoded = json_decode($match[0], true);
            if (is_array($decoded)) {
                return $decoded;
            }
        }

        return [];
    }

    /**
     * Normalize keywords from array or string into a clean list.
     *
     * @param mixed $keywords
     *
     * @return array
     */
    private function normalizeKeywordList($keywords): array
    {
        if (is_array($keywords)) {
            $items = $keywords;
        } else {
            $items = preg_split('/[,;\n]+/', (string) $keywords);
        }

        $items = array_map(static function ($item) {
            return trim((string) $item);
        }, $items);
        $items = array_filter($items, static function ($item) {
            return $item !== '';
        });
        $items = array_values(array_unique($items));

        if (count($items) > 12) {
            $items = array_slice($items, 0, 12);
        }

        return $items;
    }

    /**
     * Normalize allowed classes from request input.
     *
     * @param mixed $input
     *
     * @return array
     */
    private function normalizeAllowedClasses($input): array
    {
        $classes = is_array($input) ? $input : explode(',', (string) $input);
        $classes = array_map(static function ($item) {
            return strtolower(trim((string) $item));
        }, $classes);
        $classes = array_filter($classes, static function ($item) {
            return $item !== '' && preg_match('/^[a-z0-9_-]+$/', $item);
        });

        return array_values(array_unique($classes));
    }

    /**
     * Build a small instruction about allowed classes.
     *
     * @param array $allowedClasses
     *
     * @return string
     */
    private function buildAllowedClassesInstruction(array $allowedClasses): string
    {
        if (empty($allowedClasses)) {
            return "\nDo not use CSS classes or ids in the result unless they are essential HTML semantics.\n";
        }

        return "\nAllowed CSS classes (only when genuinely useful): ".implode(', ', $allowedClasses)."\n";
    }

    /**
     * Normalize supported image sizes.
     *
     * @param mixed $size
     *
     * @return string
     */
    private function normalizeImageSize($size): string
    {
        $size = strtolower(trim((string) $size));
        $allowed = ['1024x1024', '1536x1024', '1024x1536'];

        return in_array($size, $allowed, true) ? $size : '1024x1024';
    }

    /**
     * Normalize provider image payloads into API-safe response items without persisting files.
     *
     * @param array $images
     *
     * @return array
     */
    private function normalizeGeneratedImagesForResponse(array $images): array
    {
        $normalized = [];
        foreach ($images as $image) {
            if (!is_array($image)) {
                continue;
            }
            $item = $this->normalizeGeneratedImageForResponse($image);
            if ($item !== null) {
                $normalized[] = $item;
            }
        }

        return $normalized;
    }

    /**
     * Normalize one provider image payload into an inline response item.
     *
     * @param array $image
     *
     * @return array|null
     */
    private function normalizeGeneratedImageForResponse(array $image): ?array
    {
        list($binary, $mimeType) = $this->resolveGeneratedImageBinary($image);
        if ($binary === '') {
            return null;
        }

        $extension = $this->imageExtension($mimeType, isset($image['url']) ? (string) $image['url'] : '');
        $normalized = [
            'b64_json' => base64_encode($binary),
            'mime_type' => $mimeType,
            'name' => $this->generatedImageFilename($extension)
        ];
        if (!empty($image['revised_prompt'])) {
            $normalized['revised_prompt'] = (string) $image['revised_prompt'];
        }

        return $normalized;
    }

    /**
     * Resolve generated image payloads to binary data and a verified image MIME type.
     *
     * @param array $image
     *
     * @return array
     */
    private function resolveGeneratedImageBinary(array $image): array
    {
        $binary = '';
        $mimeType = trim((string) ($image['mime_type'] ?? ''));

        if (!empty($image['b64_json'])) {
            $decoded = base64_decode((string) $image['b64_json'], true);
            if ($decoded === false) {
                throw new \RuntimeException('The AI provider returned invalid base64 image data.');
            }
            $binary = $decoded;
        } elseif (!empty($image['url'])) {
            $binary = $this->downloadGeneratedImage((string) $image['url']);
        }

        if ($binary === '') {
            return ['', ''];
        }

        $mimeType = $this->detectImageMimeType($binary, $mimeType);
        if (strpos($mimeType, 'image/') !== 0) {
            throw new \RuntimeException('The AI provider returned an unsupported image payload.');
        }

        return [$binary, $mimeType];
    }

    /**
     * Download a provider-hosted generated image before saving it locally.
     *
     * @param string $url
     *
     * @return string
     */
    private function downloadGeneratedImage(string $url): string
    {
        $url = trim($url);
        if ($url === '') {
            return '';
        }

        $scheme = strtolower((string) parse_url($url, PHP_URL_SCHEME));
        if ($scheme !== 'http' && $scheme !== 'https') {
            throw new \RuntimeException('Unsupported generated image URL returned by the AI provider.');
        }

        try {
            $curl = new Curl();
        } catch (\Exception $e) {
            throw new \RuntimeException('cURL is not available for downloading provider-hosted images.');
        }
        $curl->setHeaders(['Accept' => 'image/*,*/*;q=0.8']);
        $curl->setOptions([CURLOPT_TIMEOUT => 60]);
        $binary = $curl->get($url);
        if ($curl->error() !== 0) {
            throw new \RuntimeException('Unable to download the generated image: '.$curl->errorMessage());
        }
        if (!is_string($binary) || $binary === '') {
            throw new \RuntimeException('The generated image download returned empty data.');
        }

        return $binary;
    }

    /**
     * Detect the final image MIME type from binary data.
     *
     * @param string $binary
     * @param string $fallbackMimeType
     *
     * @return string
     */
    private function detectImageMimeType(string $binary, string $fallbackMimeType): string
    {
        if (function_exists('finfo_open')) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            if ($finfo !== false) {
                $detected = finfo_buffer($finfo, $binary);
                finfo_close($finfo);
                if (is_string($detected) && strpos($detected, 'image/') === 0) {
                    return $detected;
                }
            }
        }

        $fallbackMimeType = strtolower(trim($fallbackMimeType));
        if (strpos($fallbackMimeType, 'image/') === 0) {
            return $fallbackMimeType;
        }

        return 'image/png';
    }

    /**
     * Map MIME types to filename extensions.
     *
     * @param string $mimeType
     * @param string $sourceUrl
     *
     * @return string
     */
    private function imageExtension(string $mimeType, string $sourceUrl): string
    {
        $extensions = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/gif' => 'gif',
            'image/webp' => 'webp'
        ];
        if (isset($extensions[$mimeType])) {
            return $extensions[$mimeType];
        }

        $path = (string) parse_url($sourceUrl, PHP_URL_PATH);
        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'], true)) {
            return $ext === 'jpeg' ? 'jpg' : $ext;
        }

        return 'png';
    }

    /**
     * Generate a unique local filename for an AI image.
     *
     * @param string $extension
     *
     * @return string
     */
    private function generatedImageFilename(string $extension): string
    {
        try {
            $suffix = bin2hex(random_bytes(6));
        } catch (\Exception $e) {
            $suffix = preg_replace('/[^a-z0-9]/i', '', uniqid('', true));
        }

        return 'ai-'.date('Ymd-His').'-'.$suffix.'.'.$extension;
    }

    /**
     * Remove markdown fences and document wrappers from the AI response.
     *
     * @param string $content
     *
     * @return string
     */
    private function extractHtmlFragment(string $content): string
    {
        $text = trim($content);

        if (preg_match('/```(?:html)?\s*(.*?)\s*```/is', $text, $match)) {
            $text = trim($match[1]);
        }

        if (preg_match('/<body\b[^>]*>(.*)<\/body>/is', $text, $match)) {
            $text = trim($match[1]);
        }

        $text = preg_replace('/^\s*<\/?(?:html|head|body)[^>]*>\s*/i', '', $text);
        $text = preg_replace('/\s*<\/?(?:html|head|body)[^>]*>\s*$/i', '', $text);

        return trim($text);
    }
}
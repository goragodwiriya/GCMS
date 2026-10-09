<?php
/**
 * @filesource modules/index/controllers/linewebhook.php
 *
 * LINE webhook bridge to the shared AI chat core.
 *
 * @copyright 2026 Goragod.com
 * @license https://www.kotchasan.com/license/
 */

namespace Index\Linewebhook;

use Gcms\Api as ApiController;
use Gcms\Chat\Dispatcher;
use Gcms\Chat\Processor;
use Kotchasan\Http\Request;

/**
 * @since 1.0
 */
class Controller extends ApiController
{
    /**
     * Handle LINE Messaging API webhook events.
     *
     * @param Request $request
     *
     * @return mixed
     */
    public function index(Request $request)
    {
        // LINE sends a GET request when verifying the webhook URL in the developer console.
        // Respond with 200 OK so the verification succeeds without attempting to process a payload.
        if ($request->getMethod() === 'GET') {
            return $this->successResponse([], 'LINE webhook endpoint is active');
        }

        // All other non-POST methods are rejected cleanly.
        if ($request->getMethod() !== 'POST') {
            return $this->errorResponse('Method not allowed', 405);
        }

        $rawBody = (string) $request->getBody();
        if (!$this->isValidSignature($request, $rawBody)) {
            return $this->errorResponse('Unauthorized', 401);
        }

        $payload = $this->decodePayload($rawBody);
        if (empty($payload)) {
            return $this->errorResponse('Invalid webhook payload', 400);
        }

        $processor = new Processor();
        $dispatcher = new Dispatcher();
        $handled = 0;
        $ignored = 0;

        foreach ($this->events($payload) as $event) {
            $message = $processor->normalize('line', $event);
            if ($message->text === '') {
                ++$ignored;
                continue;
            }

            $result = $processor->handleMessage($message);
            $error = $dispatcher->dispatch($message, $result['response'], $result['payload']);
            if ($error !== '') {
                return $this->errorResponse('Failed to reply to LINE: '.$error, 502);
            }
            ++$handled;
        }

        return $this->successResponse([
            'handled' => $handled,
            'ignored' => $ignored
        ], 'LINE webhook processed');
    }

    /**
     * @param Request $request
     * @param string  $rawBody
     *
     * @return bool
     */
    private function isValidSignature(Request $request, $rawBody)
    {
        // Single source of truth — \Gcms\Line::verifyWebhookSignature() does the
        // HMAC-SHA256 + base64 + hash_equals and fails closed on empty secret.
        return \Gcms\Line::verifyWebhookSignature(
            $rawBody,
            trim((string) $request->getHeaderLine('x-line-signature'))
        );
    }

    /**
     * @param string $rawBody
     *
     * @return array
     */
    private function decodePayload($rawBody)
    {
        if ($rawBody === '') {
            return [];
        }

        $decoded = json_decode($rawBody, true);

        return is_array($decoded) ? $decoded : [];
    }

    /**
     * @param array $payload
     *
     * @return array
     */
    private function events(array $payload)
    {
        return !empty($payload['events']) && is_array($payload['events']) ? $payload['events'] : [];
    }
}
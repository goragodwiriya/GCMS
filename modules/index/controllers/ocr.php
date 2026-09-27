<?php
/**
 * @filesource modules/index/controllers/ocr.php
 *
 * OCR API endpoints for uploaded images (slip/receipt).
 *
 * @copyright 2026 Goragod.com
 * @license https://www.kotchasan.com/license/
 */

namespace Index\Ocr;

use Gcms\Api as ApiController;
use Gcms\Ocr\OcrService;
use Kotchasan\Http\Request;

/**
 * @since 1.0
 */
class Controller extends ApiController
{
    /**
     * Parse one uploaded image with AI OCR.
     *
     * POST /api/index/ocr/parse
     *
     * Form fields:
     * - file (required): uploaded image
     * - document_type (optional): auto|slip|receipt
     * - vision_model (optional): provider-specific override model
     *
     * @param Request $request
     *
     * @return mixed
     */
    public function parse(Request $request)
    {
        ApiController::validateMethod($request, 'POST');

        $login = $this->authenticateRequest($request);
        if (!ApiController::hasPermission($login, ['can_use_ai_chat'])) {
            return $this->errorResponse('Access denied', 403);
        }

        $uploadedFiles = $request->getUploadedFiles();
        $file = $uploadedFiles['file'] ?? null;
        if ($file === null && !empty($uploadedFiles)) {
            $file = reset($uploadedFiles);
        }

        if (!is_object($file) || !method_exists($file, 'hasUploadFile') || !$file->hasUploadFile()) {
            return $this->errorResponse('Uploaded file is required', 422);
        }

        $options = [
            'document_type' => $request->post('document_type')->filter('a-z'),
            'vision_model' => trim((string) $request->post('vision_model')->topic())
        ];

        try {
            $result = (new OcrService())->parseUploadedFile($file, $options);

            return $this->successResponse($result, 'OCR parsed successfully');
        } catch (\InvalidArgumentException $e) {
            return $this->errorResponse($e->getMessage(), 422);
        } catch (\RuntimeException $e) {
            return $this->errorResponse($e->getMessage(), 400);
        } catch (\Exception $e) {
            return $this->errorResponse('OCR processing failed: '.$e->getMessage(), 500);
        }
    }
}

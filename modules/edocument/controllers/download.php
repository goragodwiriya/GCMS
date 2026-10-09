<?php
/**
 * @filesource modules/edocument/controllers/download.php
 *
 * @copyright 2026 Goragod.com
 * @license https://www.kotchasan.com/license/
 */

namespace Edocument\Download;

use Gcms\Api as ApiController;
use Kotchasan\Http\Request;
use Kotchasan\Language;

/**
 * API E-Document download action controller
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Controller extends ApiController
{
    /**
     * POST /api/edocument/download/action
     *
     * action=download    check the file and the member's right, ask to confirm
     * action=downloading count the download and return a one-time file URL
     *
     * @param Request $request
     *
     * @return \Kotchasan\Http\Response
     */
    public function action(Request $request)
    {
        try {
            ApiController::validateMethod($request, 'POST');
            $request->initSession();

            $action = $request->post('action')->filter('a-z');
            if (!in_array($action, ['download', 'downloading'], true)) {
                return $this->errorResponse('Invalid action', 400);
            }

            if (!preg_match('/([0-9]+)$/', $request->post('id')->toString(), $match)) {
                return $this->errorResponse('Invalid ID', 400);
            }

            // Token (auth_token cookie / Bearer) first, then the PHP session login
            $login = $this->authenticateRequest($request) ?: \Web\Login::isMember();
            $member_id = $login ? (int) $login->id : 0;
            $status = $login ? (int) $login->status : -1;

            $document = \Edocument\Download\Model::get((int) $match[1], $member_id);
            if (!$document) {
                return $this->errorResponse(Language::get('Sorry, Item not found It\'s may be deleted'), 404);
            }

            $filePath = \Edocument\Setup\Model::toFilePath($document->file);
            if (!$filePath || !is_file($filePath)) {
                return $this->errorResponse(Language::get('Sorry, Item not found It\'s may be deleted'), 404);
            }

            // Admin can always download; others must be in a recipient group
            if ($status !== 1 && !in_array($status, $document->reciever, true)) {
                return $this->errorResponse(Language::get('Can not be performed this request. Because they do not find the information you need or you are not allowed'), 403);
            }

            if ($action === 'download') {
                return $this->successResponse([
                    'action' => $action,
                    'id' => (int) $document->id,
                    'confirm' => Language::get('Do you want to download the file ?')
                ]);
            }

            $downloads = \Edocument\Download\Model::record($document, $member_id);

            // download_action 1 = open in the browser, only for types a browser
            // shows without running them (never html/svg from the site origin)
            $mime = (string) \Kotchasan\Mime::get(strtolower((string) $document->ext));
            $inline = (int) $document->config->download_action === 1
                && preg_match('#^(application/pdf|text/plain|image/(jpeg|png|gif|webp))$#', $mime);
            $this->cleanupSessionTokens();
            $token = bin2hex(random_bytes(16));
            $_SESSION['edocument_files'][$token] = [
                'file' => $filePath,
                'size' => (int) filesize($filePath),
                'name' => $this->downloadFilename($document),
                'mime' => $inline ? $mime : 'application/octet-stream',
                'inline' => $inline,
                'time' => time()
            ];

            return $this->successResponse([
                'action' => $action,
                'id' => (int) $document->id,
                'downloads' => number_format($downloads),
                'target' => $inline ? '_blank' : 'downloading',
                'href' => WEB_URL.'modules/edocument/filedownload.php?id='.$token
            ]);
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), $e->getCode() ?: 500, $e);
        }
    }

    /**
     * Build safe filename for Content-Disposition.
     *
     * @param object $document
     *
     * @return string
     */
    private function downloadFilename($document)
    {
        $name = trim((string) $document->topic);
        $ext = strtolower(trim((string) $document->ext));

        if ($name === '') {
            $name = 'edocument-'.(int) $document->id;
        }

        $name = preg_replace('/[\x00-\x1F\\\\\/:"*?<>|]+/u', '_', $name);
        if ($ext !== '' && !preg_match('/\.'.preg_quote($ext, '/').'$/i', $name)) {
            $name .= '.'.$ext;
        }

        return $name;
    }

    /**
     * Remove expired download tokens from session.
     *
     * @return void
     */
    private function cleanupSessionTokens()
    {
        if (!isset($_SESSION['edocument_files']) || !is_array($_SESSION['edocument_files'])) {
            $_SESSION['edocument_files'] = [];
            return;
        }

        $expiredAt = time() - 3600;
        foreach ($_SESSION['edocument_files'] as $key => $item) {
            if (empty($item['time']) || (int) $item['time'] < $expiredAt) {
                unset($_SESSION['edocument_files'][$key]);
            }
        }
    }
}

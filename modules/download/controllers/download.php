<?php
/**
 * @filesource modules/download/controllers/download.php
 *
 * @copyright 2026 Goragod.com
 * @license https://www.kotchasan.com/license/
 */

namespace Download\Download;

use Gcms\Api as ApiController;
use Kotchasan\Http\Request;

/**
 * API Download action controller
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Controller extends ApiController
{
    /**
     * POST /api/download/download/action
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

            if (!preg_match('/([0-9]+)/', $request->post('id')->toString(), $match)) {
                return $this->errorResponse('Invalid ID', 400);
            }

            $download = \Download\Download\Model::get((int) $match[1]);
            if (!$download) {
                return $this->errorResponse('Sorry, Item not found It\'s may be deleted', 404);
            }

            $filePath = \Download\Setup\Model::toFilePath($download->file);
            if (!$filePath || !is_file($filePath)) {
                return $this->errorResponse('Sorry, Item not found It\'s may be deleted', 404);
            }

            // an API request carries the member's token (Web\Login::create() never ran)
            $login = $this->authenticateRequest($request) ?: \Web\Login::isMember();
            $status = $login ? (int) $login->status : -1;
            if (!in_array($status, $download->reciever, true)) {
                return $this->errorResponse('Can not be performed this request. Because they do not find the information you need or you are not allowed', 403);
            }

            if ($action === 'download') {
                return $this->successResponse([
                    'action' => $action,
                    'id' => (int) $download->id,
                    'confirm' => 'Do you want to download the file ?'
                ]);
            }

            $downloads = (int) $download->downloads + 1;
            \Kotchasan\DB::create()->update('download', ['id', (int) $download->id], ['downloads' => $downloads]);

            $this->cleanupSessionTokens();
            $token = bin2hex(random_bytes(16));
            if (!isset($_SESSION['download_files']) || !is_array($_SESSION['download_files'])) {
                $_SESSION['download_files'] = [];
            }
            $_SESSION['download_files'][$token] = [
                'id' => (int) $download->id,
                'file' => $filePath,
                'size' => is_file($filePath) ? (int) filesize($filePath) : (int) $download->size,
                'name' => $this->downloadFilename($download),
                'time' => time()
            ];

            return $this->successResponse([
                'action' => $action,
                'id' => (int) $download->id,
                'downloads' => number_format($downloads),
                'href' => WEB_URL.'modules/download/filedownload.php?id='.$token
            ]);
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), $e->getCode() ?: 500, $e);
        }
    }

    /**
     * Build safe filename for Content-Disposition.
     *
     * @param object $download
     *
     * @return string
     */
    private function downloadFilename($download)
    {
        $name = trim((string) $download->name);
        $ext = strtolower(trim((string) $download->ext));

        if ($name === '') {
            $name = 'download-'.(int) $download->id;
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
        if (!isset($_SESSION['download_files']) || !is_array($_SESSION['download_files'])) {
            return;
        }

        $expiredAt = time() - 3600;
        foreach ($_SESSION['download_files'] as $key => $item) {
            if (empty($item['time']) || (int) $item['time'] < $expiredAt) {
                unset($_SESSION['download_files'][$key]);
            }
        }
    }
}

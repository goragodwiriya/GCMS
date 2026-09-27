<?php
/**
 * @filesource widgets/textlinks/controllers/settings.php
 *
 * @copyright 2026 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Widgets\Textlinks\Controllers;

use Gcms\Api as ApiController;
use Kotchasan\File;
use Kotchasan\Http\Request;
use Kotchasan\Language;

/**
 * Textlinks Widget — Settings Controller
 *
 * GET  ../api/index/widgets/get?widget=textlinks  → load current settings
 * POST ../api/index/widgets/save?widget=textlinks → validate and persist settings
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Settings extends \Kotchasan\ApiController
{
    /**
     * GET  ../api/index/widgets/get?widget=textlinks
     * Return current Textlinks widget settings as JSON.
     *
     * @param Request $request
     *
     * @return \Kotchasan\Http\Response
     */
    public function get(Request $request)
    {
        try {
            $login = $this->authenticateRequest($request);
            if (!$login) {
                return $this->errorResponse('Unauthorized', 401);
            }

            if (!ApiController::hasPermission($login, ['can_config'])) {
                return $this->errorResponse('Permission required', 403);
            }

            $id = $request->get('id')->toInt();

            $textlink = \Widgets\Textlinks\Models\Settings::get($id);
            if (!$textlink) {
                return $this->redirectResponse('/404', 'No data available', 404);
            }

            $styles = include ROOT_PATH.'widgets/textlinks/styles.php';
            $textlink->styles = json_encode($styles, JSON_UNESCAPED_UNICODE);
            $textlink->dateless = $textlink->publish_end === null ? 1 : 0;
            // logo
            if (file_exists(ROOT_PATH.DATA_FOLDER.'textlink/'.$textlink->logo)) {
                $textlink->logo = [
                    [
                        'url' => WEB_URL.DATA_FOLDER.'textlink/'.$textlink->logo,
                        'name' => $textlink->logo
                    ]
                ];
            }

            return $this->successResponse($textlink, 'Textlink retrieved successfully');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), $e->getCode() ?: 500, $e);
        }
    }

    /**
     * POST ../api/index/widgets/save?widget=textlinks
     * Validate, upload image, and persist a textlink record (insert or update).
     *
     * Expected POST fields:
     *   id            int    0 = new record, > 0 = update existing
     *   name          string Textlink group name (a–z, 0–9, _ only)
     *   description   string Short description / notes
     *   type          string One of: custom | text | menu | image | banner | slideshow
     *   template      string HTML template (required when type = custom)
     *   text          string Link label / alt text
     *   url           string Link href
     *   target        string '' | '_blank'
     *   dateless      int    1 = no expiry dates, 0 = use publish dates
     *   publish_start date   Start date (Y-m-d)
     *   publish_end   date   End date   (Y-m-d), ignored when dateless = 1
     *   logo          file   Optional image upload (jpg/jpeg/png/webp)
     *
     * @param Request $request
     *
     * @return \Kotchasan\Http\Response
     */
    public function save(Request $request)
    {
        try {
            $login = $this->authenticateRequest($request);
            if (!$login) {
                return $this->errorResponse('Unauthorized', 401);
            }

            if (!ApiController::canModify($login, ['can_config'])) {
                return $this->errorResponse('Permission required', 403);
            }

            // ── Validate & sanitise inputs ────────────────────────────────
            $errors = [];

            $name = $request->post('name')->filter('a-z0-9_');
            $type = $request->post('type')->filter('a-z');

            $styles = include ROOT_PATH.'widgets/textlinks/styles.php';
            if (!isset($styles[$type])) {
                $errors['type'] = 'Invalid type';
            }

            if (!empty($errors)) {
                return $this->formErrorResponse($errors, 422);
            }

            $dateless = $request->post('dateless')->toBoolean();

            $save = [
                'name' => $name,
                'description' => $request->post('description')->topic(),
                'type' => $type,
                'template' => $type === 'custom' ? $request->post('template')->toString() : null,
                'text' => $request->post('text')->topic(),
                'url' => $request->post('url')->url(),
                'target' => $request->post('target')->filter('a-z_'),
                'publish_start' => $request->post('publish_start')->date(),
                'publish_end' => $dateless ? null : $request->post('publish_end')->date()
            ];

            // ── Determine insert vs update ────────────────────────────────
            $db = \Kotchasan\DB::create();
            $postId = $request->post('id')->toInt();

            if ($postId > 0) {
                $save['id'] = $postId;
                $isNew = false;
            } else {
                $id = $db->nextId('textlink');
                $save['id'] = $id;
                $save['link_order'] = $id;
                $save['published'] = 1;
                $save['created_at'] = date('Y-m-d H:i:s');
                $isNew = true;
            }

            // ── Handle logo upload ────────────────────────────────────────
            $dir = ROOT_PATH.DATA_FOLDER.'textlink/';
            if (!File::makeDirectory($dir)) {
                $errors['logo'] = Language::replace('Directory %s cannot be created or is read-only.', DATA_FOLDER.'textlink/');
            } else {
                foreach ($request->getUploadedFiles() as $item => $file) {
                    if ($file->hasUploadFile()) {
                        if ($item === 'logo') {
                            try {
                                $images = getimagesize($file->getTempFileName());
                                if ($images === false) {
                                    $errors['logo'] = 'Uploaded file is not a valid image';
                                } else {
                                    $save['logo'] = 'textlink-'.$save['id'].'.'.$file->getClientFileExt();
                                    $save['width'] = $images[0];
                                    $save['height'] = $images[1];

                                    $file->moveTo($dir.$save['logo']);
                                }
                            } catch (\Exception $exc) {
                                $errors[$item] = $exc->getMessage();
                            }
                        }
                    } elseif ($file->hasError()) {
                        $errors[$item] = $file->getErrorMessage();
                    }
                }
            }

            if (!empty($errors)) {
                return $this->formErrorResponse($errors, 422);
            }

            // ── Persist ───────────────────────────────────────────────────
            \Widgets\Textlinks\Models\Settings::save($db, $save, $isNew);

            // ── Log ───────────────────────────────────────────────────────
            $action = $isNew ? 'Created' : 'Updated';
            \Index\Log\Model::add($save['id'], 'widgets', 'Textlinks', $action.' Textlink: '.$name, $login->id);

            return $this->redirectResponse('back', 'Saved successfully', 200, 1000);
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), $e->getCode() ?: 500, $e);
        }
    }

    /**
     * POST ../api/index/widgets/remove?widget=textlinks&type=logo
     * Remove the logo file for a given textlink ID and update the database record to clear logo reference.
     *
     * @param Request $request
     *
     * @return mixed
     */
    public function removeLogo(Request $request)
    {
        try {
            ApiController::validateMethod($request, 'POST');
            $this->validateCsrfToken($request);

            // Authentication check (required)
            $login = $this->authenticateRequest($request);
            if (!$login) {
                return $this->redirectResponse('/login', 'Unauthorized', 401);
            }

            if (!ApiController::canModify($login, ['can_config'])) {
                return $this->errorResponse('Permission required', 403);
            }

            // Validate URL and extract textlink ID
            $url = $request->post('url')->url();
            if (!preg_match('/\/textlink\/textlink-(\d+)\.(jpg|jpeg|png|gif|webp)$/', $url, $matches)) {
                return $this->errorResponse('Invalid file URL format', 400);
            }

            $id = (int) $matches[1];

            // Remove logo file
            $textlink = \Widgets\Textlinks\Models\Settings::get($id);
            if ($textlink && !empty($textlink->logo)) {
                $filePath = ROOT_PATH.DATA_FOLDER.'textlink/'.$textlink->logo;
                if (file_exists($filePath) && is_file($filePath)) {
                    unlink($filePath);
                }

                // Update database record to remove logo reference
                \Kotchasan\DB::create()->update('textlink', ['id', $id], ['logo' => null, 'width' => 0, 'height' => 0]);

                // Log
                \Index\Log\Model::add($id, 'index', 'Index', 'Remove textlink logo: '.$id, $login->id);

                return $this->successResponse('Removed successfully');
            }

            return $this->errorResponse('File not found', 404);
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), $e->getCode() ?: 500, $e);
        }
    }
}

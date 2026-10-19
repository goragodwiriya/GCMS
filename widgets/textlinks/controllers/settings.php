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
use Kotchasan\Text;
use Widgets\Textlinks\Models\Index as TextlinkModel;

/**
 * Textlinks Widget — Settings Controller
 *
 * GET  ../api/index/widgets/get?widget=textlinks[&id=N]  → ข้อมูลลิงค์ 1 รายการ
 *      พร้อมรายชื่อกลุ่มทั้งหมด (modules) สำหรับ datalist เลือกกลุ่มในฟอร์ม
 * POST ../api/index/widgets/save?widget=textlinks        → บันทึกลิงค์ 1 รายการ
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Settings extends \Kotchasan\ApiController
{
    /**
     * GET  ../api/index/widgets/get?widget=textlinks[&id=N]
     * Return one textlink record (or blank defaults) plus the list of groups.
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

            if ($id === 0) {
                // เพิ่มรายการใหม่จากหน้าที่กรองกลุ่มไว้ (ปุ่มเพิ่ม data-params="name")
                $textlink->name = $request->get('name')->filter('a-z0-9_');
            }

            // เหลือ 2 ชนิด ข้อมูลเดิม (menu, banner, hero, slideshow, custom) จับคู่ให้อัตโนมัติ
            $textlink->type = TextlinkModel::normalizeType($textlink->type);
            $textlink->dateless = empty($textlink->publish_start) && empty($textlink->publish_end) ? 1 : 0;
            // รายชื่อกลุ่ม สำหรับ <datalist id="textlink_groups"> ของ textlink.html (widgets/textlinks/admin.js)
            $textlink->modules = TextlinkModel::getGroups();
            // logo
            if (!empty($textlink->logo) && file_exists(ROOT_PATH.DATA_FOLDER.'image/'.$textlink->logo)) {
                $textlink->logo = [
                    [
                        'url' => WEB_URL.DATA_FOLDER.'image/'.$textlink->logo,
                        'name' => $textlink->logo
                    ]
                ];
            }
            $textlink->url = str_replace('{WEBURL}', WEB_URL, (string) $textlink->url);

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
     *   name          string ชื่อกลุ่ม (a–z, 0–9, _)
     *   description   string หมายเหตุสั้นๆ
     *   type          string text | image
     *   text          string ข้อความบนลิงค์ / alt ของรูป
     *   url           string ลิงค์ปลายทาง
     *   target        string '' | '_blank'
     *   dateless      int    1 = แสดงตลอด, 0 = กำหนดช่วงเวลา
     *   publish_start date   วันเริ่มแสดง (Y-m-d)
     *   publish_end   date   วันสิ้นสุด (Y-m-d) ไม่ใช้เมื่อ dateless = 1
     *   logo          file   รูปภาพ (jpg/jpeg/png/webp) จำเป็นสำหรับ type = image
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
            $text = $request->post('text')->topic();

            if ($name === '') {
                $errors['name'] = Language::get('Please fill in');
            }
            if (!in_array($type, TextlinkModel::TYPES, true)) {
                $errors['type'] = 'Invalid type';
            }
            if ($type === 'text' && $text === '') {
                $errors['text'] = Language::get('Please fill in');
            }

            if (!empty($errors)) {
                return $this->formErrorResponse($errors, 422);
            }

            $dateless = $request->post('dateless')->toBoolean();

            $save = [
                'name' => $name,
                'description' => $request->post('description')->topic(),
                'type' => $type,
                'text' => $text,
                'url' => str_replace(WEB_URL, '{WEBURL}', $request->post('url')->url()),
                'target' => $request->post('target')->filter('a-z_'),
                'publish_start' => $dateless ? 0 : strtotime($request->post('publish_start')->date()),
                'publish_end' => $dateless ? 0 : strtotime($request->post('publish_end')->date())
            ];

            // ── Determine insert vs update ────────────────────────────────
            $db = \Kotchasan\DB::create();
            $postId = $request->post('id')->toInt();

            if ($postId > 0) {
                $save['id'] = $postId;
                $isNew = false;
                $current = \Widgets\Textlinks\Models\Settings::get($postId);
            } else {
                $id = $db->nextId('textlink');
                $save['id'] = $id;
                $save['link_order'] = $id;
                $save['published'] = 1;
                $save['created_at'] = date('Y-m-d H:i:s');
                $isNew = true;
                $current = null;
            }

            // ── Handle logo upload ────────────────────────────────────────
            $dir = ROOT_PATH.DATA_FOLDER.'image/';
            if (!File::makeDirectory($dir)) {
                $errors['logo'] = Language::replace('Directory %s cannot be created or is read-only.', DATA_FOLDER.'image/');
            } else {
                foreach ($request->getUploadedFiles() as $item => $file) {
                    if ($file->hasUploadFile()) {
                        if ($item === 'logo') {
                            try {
                                $images = getimagesize($file->getTempFileName());
                                if ($images === false) {
                                    $errors['logo'] = 'Uploaded file is not a valid image';
                                } else {
                                    $save['logo'] = uniqid().'.'.$file->getClientFileExt();
                                    $save['width'] = $images[0];
                                    $save['height'] = $images[1];

                                    $file->moveTo($dir.$save['logo']);

                                    if (!empty($current->logo) && $save['logo'] !== $current->logo) {
                                        $path = $dir.$current->logo;
                                        if (file_exists($path)) {
                                            unlink($path);
                                        }
                                    }
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

            // เมนูรูปภาพต้องมีรูป ไม่ว่าจะอัปโหลดใหม่หรือมีของเดิมอยู่แล้ว
            if ($type === 'image' && !isset($save['logo']) && empty($current->logo)) {
                $errors['logo'] = Language::get('Please fill in');
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
            if (!preg_match('/\/image\/([a-z0-9]+\.(jpg|jpeg|png|gif|webp))$/', $url, $matches)) {
                return $this->errorResponse('Invalid file URL format', 400);
            }

            $logo = $matches[1];

            // Remove logo file
            $textlink = \Widgets\Textlinks\Models\Settings::formLogo($logo);
            if ($logo && !empty($textlink->logo)) {
                $filePath = ROOT_PATH.DATA_FOLDER.'image/'.$textlink->logo;
                if (file_exists($filePath) && is_file($filePath)) {
                    unlink($filePath);
                }

                // Update database record to remove logo reference
                \Kotchasan\DB::create()->update('textlink', ['id', $textlink->id], ['logo' => null, 'width' => 0, 'height' => 0]);

                // Log
                \Index\Log\Model::add($textlink->id, 'index', 'Index', 'Remove textlink logo: '.$textlink->id, $login->id);

                return $this->successResponse('Removed successfully');
            }

            return $this->errorResponse('File not found', 404);
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), $e->getCode() ?: 500, $e);
        }
    }
}

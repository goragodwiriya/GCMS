<?php
/**
 * @filesource modules/index/models/skin.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Index\Skin;

use Gcms\Config;
use Gcms\Login;
use Kotchasan\File;
use Kotchasan\Http\Request;
use Kotchasan\Language;

/**
 * บันทึกการตั้งค่า template
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Model extends \Kotchasan\KBase
{
    /**
     * รับค่าจากฟอร์ม (skin.php)
     *
     * @param Request $request
     */
    public function submit(Request $request)
    {
        $ret = array();
        // session, token, member, can_config, ไม่ใช่สมาชิกตัวอย่าง
        if ($request->initSession() && $request->isSafe() && $login = Login::adminAccess()) {
            if (Login::checkPermission($login, 'can_config') && Login::notDemoMode($login)) {
                // โหลด config
                $config = Config::load(CONFIG);
                // อัปโหลดไฟล์
                foreach ($request->getUploadedFiles() as $item => $file) {
                    if (in_array($item, array('logo', 'bg_image'))) {
                        /* @var $file \Kotchasan\Http\UploadedFile */
                        if ($request->post('delete_'.$item)->toBoolean() == 1) {
                            // ลบรูปภาพ
                            if (isset($config->$item) && is_file(ROOT_PATH.DATA_FOLDER.'image/'.$config->$item)) {
                                @unlink(ROOT_PATH.DATA_FOLDER.'image/'.$config->$item);
                                unset($config->$item);
                            }
                        } elseif ($file->hasUploadFile()) {
                            // ชนิดของไฟล์ที่ยอมรับ
                            $typies = $item == 'logo' ? array('jpg', 'jpeg', 'gif', 'png', 'swf') : array('jpg', 'jpeg', 'gif', 'png');
                            if (!$file->validFileExt($typies)) {
                                // ชนิดของไฟล์ไม่รองรับ
                                $ret['ret_'.$item] = Language::get('The type of file is invalid');
                            } elseif (!File::makeDirectory(ROOT_PATH.DATA_FOLDER.'image/')) {
                                // ไดเรคทอรี่ไม่สามารถสร้างได้
                                $ret['ret_'.$item] = sprintf(Language::get('Directory %s cannot be created or is read-only.'), DATA_FOLDER.'image/');
                            } else {
                                try {
                                    $ext = $file->getClientFileExt();
                                    $file->moveTo(ROOT_PATH.DATA_FOLDER.'image/'.$item.'.'.$ext);
                                    $config->$item = $item.'.'.$ext;
                                } catch (\Exception $exc) {
                                    // ไม่สามารถอัปโหลดได้
                                    $ret['ret_'.$item] = Language::get($exc->getMessage());
                                }
                            }
                        } elseif ($file->hasError()) {
                            // ข้อผิดพลาดการอัปโหลด
                            $ret['ret_'.$item] = Language::get($file->getErrorMessage());
                        }
                    }
                }
                if (empty($ret)) {
                    // bg_color
                    $bg_color = $request->post('bg_color')->color();
                    if (empty($bg_color)) {
                        unset($config->bg_color);
                    } else {
                        $config->bg_color = strtoupper($bg_color);
                    }
                    // save config
                    if (Config::save($config, CONFIG)) {
                        $ret['alert'] = Language::get('Saved successfully');
                        $ret['location'] = 'reload';
                        // เคลียร์
                        $request->removeToken();
                    } else {
                        // ไม่สามารถบันทึก config ได้
                        $ret['alert'] = Language::replace('File %s cannot be created or is read-only.', 'settings/config.php');
                    }
                }
            }
        }
        if (empty($ret)) {
            $ret['alert'] = Language::get('Unable to complete the transaction');
        }
        // คืนค่าเป็น JSON
        echo json_encode($ret);
    }
}

<?php
/**
 * @filesource modules/index/models/system.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Index\System;

use Gcms\Config;
use Gcms\Login;
use Kotchasan\Cache\FileCache as Cache;
use Kotchasan\File;
use Kotchasan\Http\Request;
use Kotchasan\Language;

/**
 * module=system
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Model extends \Kotchasan\KBase
{
    /**
     * เคลียร์แคช
     *
     * @param Request $request
     */
    public function clearCache(Request $request)
    {
        if ($request->initSession() && $request->isReferer() && Login::isAdmin()) {
            $cahce = new Cache();
            if ($cahce->clear()) {
                $ret = array('alert' => Language::get('Cache cleared successfully'));
            } else {
                $ret = array('alert' => Language::get('Some files cannot be deleted'));
            }
            // คืนค่าเป็น JSON
            echo json_encode($ret);
        }
    }

    /**
     * บันทึกการตั้งค่าเว็บไซต์ (system.php)
     *
     * @param Request $request
     */
    public function submit(Request $request)
    {
        $ret = array();
        // session, token, แอดมิน, can_config, ไม่ใช่สมาชิกตัวอย่าง
        if ($request->initSession() && $request->isSafe() && $login = Login::adminAccess()) {
            if (Login::checkPermission($login, 'can_config') && Login::notDemoMode($login)) {
                // โหลด config
                $config = Config::load(CONFIG);
                foreach (array('web_title', 'web_description') as $key) {
                    $value = $request->post($key)->quote();
                    if (empty($value)) {
                        $ret['ret_'.$key] = 'Please fill in';
                    } else {
                        $config->$key = $value;
                    }
                }
                foreach (array('user_icon_typies', 'login_fields') as $key) {
                    $value = $request->post($key, array())->filter('a-z0-9_');
                    if (empty($value)) {
                        $ret['ret_'.$key] = Language::get('Please select at least one item');
                    } else {
                        $config->$key = $value;
                    }
                }
                if (empty($ret)) {
                    // อัปโหลดไฟล์
                    foreach ($request->getUploadedFiles() as $item => $file) {
                        if ($item == 'favicon') {
                            /* @var $file \Kotchasan\Http\UploadedFile */
                            if ($request->post('delete_'.$item)->toBoolean() == 1) {
                                // ลบรูปภาพ
                                if (is_file(ROOT_PATH.DATA_FOLDER.'image/'.$item.'.ico')) {
                                    @unlink(ROOT_PATH.DATA_FOLDER.'image/'.$item.'.ico');
                                }
                            } elseif (!File::makeDirectory(ROOT_PATH.DATA_FOLDER.'image/')) {
                                // ไดเรคทอรี่ไม่สามารถสร้างได้
                                $ret['ret_'.$item] = Language::replace('Directory %s cannot be created or is read-only.', DATA_FOLDER.'image/');
                            } elseif ($file->hasUploadFile()) {
                                // ตรวจสอบไฟล์อัปโหลด
                                if (!$file->validFileExt(array('ico'))) {
                                    $ret['ret_'.$item] = Language::get('The type of file is invalid');
                                } else {
                                    try {
                                        $file->moveTo(ROOT_PATH.DATA_FOLDER.'image/'.$item.'.ico');
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
                }
                if (empty($ret)) {
                    $config->user_icon_h = max(16, $request->post('user_icon_h')->toInt());
                    $config->user_icon_w = max(16, $request->post('user_icon_w')->toInt());
                    $config->cache_expire = max(0, $request->post('cache_expire')->toInt());
                    $config->module_url = $request->post('module_url')->toInt();
                    $config->timezone = $request->post('timezone')->text();
                    $config->demo_mode = $request->post('demo_mode')->toBoolean();
                    $config->user_activate = $request->post('user_activate')->toBoolean();
                    $config->member_phone = $request->post('member_phone')->toInt();
                    $config->member_idcard = $request->post('member_idcard')->toInt();
                    $config->use_ajax = $request->post('use_ajax')->toBoolean();
                    $config->new_register_status = $request->post('new_register_status')->toInt();
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

<?php
/**
 * @filesource modules/index/models/meta.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Index\Meta;

use Gcms\Config;
use Gcms\Login;
use Kotchasan\File;
use Kotchasan\Http\Request;
use Kotchasan\Language;

/**
 * module=meta
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Model extends \Kotchasan\KBase
{
    /**
     * รับค่าจากฟอร์ม (meta.php)
     *
     * @param Request $request
     */
    public function submit(Request $request)
    {
        $ret = array();
        // session, token, member, can_config, ไม่ใช่สมาชิกตัวอย่าง
        if ($request->initSession() && $request->isSafe() && $login = Login::adminAccess()) {
            if (Login::checkPermission($login, 'can_config') && Login::notDemoMode($login)) {
                try {
                    // โหลด config
                    $config = Config::load(CONFIG);
                    // อัปโหลดไฟล์
                    foreach ($request->getUploadedFiles() as $item => $file) {
                        /* @var $file \Kotchasan\Http\UploadedFile */
                        if ($request->post('delete_'.$item)->toBoolean() == 1) {
                            // ลบรูปภาพ
                            if (is_file(ROOT_PATH.DATA_FOLDER.'image/'.$item.'.jpg')) {
                                @unlink(ROOT_PATH.DATA_FOLDER.'image/'.$item.'.jpg');
                            }
                        } elseif (!File::makeDirectory(ROOT_PATH.DATA_FOLDER.'image/')) {
                            // ไดเรคทอรี่ไม่สามารถสร้างได้
                            $ret['ret_'.$item] = sprintf(Language::get('Directory %s cannot be created or is read-only.'), DATA_FOLDER.'image/');
                        } elseif ($file->hasUploadFile()) {
                            // ตรวจสอบไฟล์อัปโหลด
                            if (!$file->validFileExt(array('jpg', 'jpeg'))) {
                                $ret['ret_'.$item] = Language::get('The type of file is invalid');
                            } else {
                                try {
                                    $file->moveTo(ROOT_PATH.DATA_FOLDER.'image/'.$item.'.jpg');
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
                    // other
                    foreach (array('google_site_verification', 'msvalidate', 'facebook_appId', 'google_client_id', 'theme_color', 'google_tag', 'google_ads_code') as $item) {
                        $value = $request->post($item)->text();
                        if (empty($value)) {
                            unset($config->$item);
                        } else {
                            $config->$item = $value;
                        }
                    }
                    if (!empty($config->google_client_id)) {
                        $config->google_client_id = explode('.', $config->google_client_id)[0];
                    }
                    $config->amp = $request->post('amp')->toBoolean();
                    if (empty($ret)) {
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
                } catch (\Kotchasan\InputItemException $e) {
                    $ret['alert'] = $e->getMessage();
                }
            }
        }
        if (empty($ret)) {
            $ret['alert'] = Language::get('Unable to complete the transaction');
        }
        // คืนค่าเป็น JSON
        echo json_encode($ret);
    }

    /**
     * ทดสอบส่งไลน์
     *
     * @param Request $request
     */
    public function linetest(Request $request)
    {
        \Gcms\Line::send('Test');
    }
}

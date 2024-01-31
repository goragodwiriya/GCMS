<?php
/**
 * @filesource modules/board/models/admin/settings.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Board\Admin\Settings;

use Gcms\Gcms;
use Gcms\Login;
use Kotchasan\File;
use Kotchasan\Http\Request;
use Kotchasan\Language;

/**
 *  บันทึกการตั้งค่า
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Model extends \Kotchasan\Model
{
    /**
     * ค่าติดตั้งเริ่มต้น
     *
     * @return array
     */
    public static function defaultSettings()
    {
        $members = array_keys(self::$cfg->member_status);
        return array(
            'icon_width' => 696,
            'icon_height' => 464,
            'img_typies' => array('jpg', 'jpeg'),
            'default_icon' => 'modules/board/img/default_icon.png',
            'list_per_page' => 20,
            'new_date' => 604800,
            'viewing' => 0,
            'category_display' => 1,
            'category_cols' => 1,
            'news_count' => 10,
            'img_upload_type' => array('jpg', 'jpeg'),
            'img_upload_size' => 1024,
            'img_law' => 0,
            'line_notifications' => array(),
            'can_post' => $members,
            'can_reply' => $members,
            'can_view' => array_merge(array(-1), $members),
            'moderator' => array(1),
            'can_config' => array(1)
        );
    }

    /**
     * บันทึกข้อมูล config ของโมดูล
     *
     * @param Request $request
     */
    public function submit(Request $request)
    {
        $ret = array();
        // referer, token, can_config, ไม่ใช่สมาชิกตัวอย่าง
        if ($request->initSession() && $request->isSafe() && $login = Login::adminAccess()) {
            if (Login::notDemoMode($login)) {
                // รับค่าจากการ POST
                $save = array(
                    'icon_width' => max(75, $request->post('icon_width')->toInt()),
                    'icon_height' => max(75, $request->post('icon_height')->toInt()),
                    'img_typies' => $request->post('img_typies', array())->toString(),
                    'list_per_page' => $request->post('list_per_page')->toInt(),
                    'new_date' => $request->post('new_date')->toInt(),
                    'viewing' => $request->post('viewing')->toInt(),
                    'category_display' => $request->post('category_display')->toBoolean(),
                    'category_cols' => max(1, $request->post('category_cols')->toInt()),
                    'news_count' => $request->post('news_count')->toInt(),
                    'img_upload_type' => $request->post('img_upload_type', array())->toString(),
                    'img_upload_size' => $request->post('img_upload_size', array())->toInt(),
                    'img_law' => $request->post('img_law')->toBoolean(),
                    'line_notifications' => $request->post('line_notifications', array())->toInt(),
                    'can_post' => $request->post('can_post', array())->toInt(),
                    'can_reply' => $request->post('can_reply', array())->toInt(),
                    'can_view' => $request->post('can_view', array())->toInt(),
                    'moderator' => $request->post('moderator', array())->toInt(),
                    'can_config' => $request->post('can_config', array())->toInt()
                );
                // อ่านข้อมูลโมดูล และ config
                $index = \Index\Adminmodule\Model::getModuleWithConfig('board', $request->post('id')->toInt());
                // สามารถตั้งค่าได้
                if ($index && Gcms::canConfig($login, $index, 'can_config')) {
                    if (empty($save['img_typies'])) {
                        // คืนค่า input ที่ error
                        $ret['ret_img_typies'] = Language::get('Please select at least one item');
                    } else {
                        $save['default_icon'] = $index->default_icon;
                        // อัปโหลดไฟล์
                        foreach ($request->getUploadedFiles() as $item => $file) {
                            /* @var $file \Kotchasan\Http\UploadedFile */
                            if ($file->hasUploadFile()) {
                                if (!File::makeDirectory(ROOT_PATH.DATA_FOLDER.'board/')) {
                                    // ไดเรคทอรี่ไม่สามารถสร้างได้
                                    $ret['ret_'.$item] = sprintf(Language::get('Directory %s cannot be created or is read-only.'), DATA_FOLDER.'board/');
                                } elseif (!$file->validFileExt($save['img_typies'])) {
                                    // รูปภาพเท่านั้น
                                    $ret['ret_'.$item] = Language::get('The type of file is invalid');
                                } else {
                                    // อัปโหลด
                                    $save['default_icon'] = DATA_FOLDER.'board/default-'.$index->module_id.'.'.$file->getClientFileExt();
                                    try {
                                        $file->moveTo(ROOT_PATH.$save['default_icon']);
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
                        if (empty($ret)) {
                            // บันทึก
                            $save['new_date'] = $save['new_date'] * 86400;
                            $save['can_post'][] = 1;
                            $save['can_reply'][] = 1;
                            $save['can_view'][] = 1;
                            $save['moderator'][] = 1;
                            $save['can_config'][] = 1;
                            $this->db()->update($this->getTableName('modules'), $index->module_id, array('config' => serialize($save)));
                            // คืนค่า
                            $ret['alert'] = Language::get('Saved successfully');
                            $ret['location'] = 'reload';
                            // เคลียร์
                            $request->removeToken();
                        }
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

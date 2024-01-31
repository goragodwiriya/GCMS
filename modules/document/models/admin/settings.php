<?php
/**
 * @filesource modules/document/models/admin/settings.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Document\Admin\Settings;

use Gcms\Config;
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
            'default_icon' => 'modules/document/img/default_icon.png',
            'published' => 1,
            'rows' => 20,
            'cols' => 1,
            'sort' => 1,
            'style' => 'iconview',
            'new_date' => 604800,
            'viewing' => 0,
            'category_display' => 1,
            'category_cols' => 1,
            'news_count' => 10,
            'news_sort' => 1,
            'line_notifications' => array(),
            'can_reply' => $members,
            'can_view' => array_merge(array(-1), $members),
            'can_write' => array(1),
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
                    'published' => $request->post('published')->toBoolean(),
                    'rows' => max(1, $request->post('rows')->toInt()),
                    'cols' => max(1, $request->post('cols')->toInt()),
                    'sort' => $request->post('sort')->toInt(),
                    'style' => $request->post('style', 'iconview')->filter('a-z'),
                    'new_date' => $request->post('new_date')->toInt(),
                    'viewing' => $request->post('viewing')->toInt(),
                    'category_display' => $request->post('category_display')->toBoolean(),
                    'category_cols' => max(1, $request->post('category_cols')->toInt()),
                    'news_count' => $request->post('news_count')->toInt(),
                    'news_sort' => $request->post('news_sort')->toInt(),
                    'line_notifications' => $request->post('line_notifications', array())->toInt(),
                    'can_reply' => $request->post('can_reply', array())->toInt(),
                    'can_view' => $request->post('can_view', array())->toInt(),
                    'can_write' => $request->post('can_write', array())->toInt(),
                    'moderator' => $request->post('moderator', array())->toInt(),
                    'can_config' => $request->post('can_config', array())->toInt()
                );
                // อ่านข้อมูลโมดูล และ config
                $index = \Index\Adminmodule\Model::getModuleWithConfig('document', $request->post('id')->toInt());
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
                                if (!File::makeDirectory(ROOT_PATH.DATA_FOLDER.'document/')) {
                                    // ไดเรคทอรี่ไม่สามารถสร้างได้
                                    $ret['ret_'.$item] = sprintf(Language::get('Directory %s cannot be created or is read-only.'), DATA_FOLDER.'document/');
                                } elseif (!$file->validFileExt($save['img_typies'])) {
                                    // รูปภาพเท่านั้น
                                    $ret['ret_'.$item] = Language::get('The type of file is invalid');
                                } else {
                                    // อัปโหลด
                                    if ($item == 'document_icon') {
                                        $icon = DATA_FOLDER.'document/default_icon.png';
                                    } else {
                                        $save['default_icon'] = DATA_FOLDER.'document/default-'.$index->module_id.'.'.$file->getClientFileExt();
                                        $icon = $save['default_icon'];
                                    }
                                    try {
                                        $file->moveTo(ROOT_PATH.$icon);
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
                            // โหลด config
                            $config = Config::load(CONFIG);
                            $config->document_style = $request->post('document_style', 'iconview')->filter('a-z');
                            $config->document_rows = max(1, $request->post('document_rows')->toInt());
                            $config->document_cols = max(1, $request->post('document_cols')->toInt());
                            // บันทึก
                            if (Config::save($config, CONFIG)) {
                                $save['new_date'] = $save['new_date'] * 86400;
                                $save['can_view'][] = 1;
                                $save['can_write'][] = 1;
                                $save['moderator'][] = 1;
                                $save['can_config'][] = 1;
                                $this->db()->update($this->getTableName('modules'), $index->module_id, array('config' => serialize($save)));
                                // คืนค่า
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
            }
        }
        if (empty($ret)) {
            $ret['alert'] = Language::get('Unable to complete the transaction');
        }
        // คืนค่าเป็น JSON
        echo json_encode($ret);
    }
}

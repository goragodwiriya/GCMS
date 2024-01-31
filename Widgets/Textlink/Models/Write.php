<?php
/**
 * @filesource Widgets/Textlink/Models/Write.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Widgets\Textlink\Models;

use Gcms\Login;
use Kotchasan\Database\Sql;
use Kotchasan\Date;
use Kotchasan\File;
use Kotchasan\Http\Request;
use Kotchasan\Image;
use Kotchasan\Language;

/**
 * Controller สำหรับจัดการการตั้งค่าเริ่มต้น
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Write extends \Kotchasan\Model
{
    /**
     * รับค่าจากฟอร์ม (Write.php)
     *
     * @param Request $request
     */
    public function submit(Request $request)
    {
        $ret = array();
        // session, token, member, can_config, ไม่ใช่สมาชิกตัวอย่าง
        if ($request->initSession() && $request->isSafe() && $login = Login::adminAccess()) {
            if (Login::checkPermission($login, 'can_config') && Login::notDemoMode($login)) {
                // ค่าที่ส่งมา
                $save = array(
                    'name' => $request->post('name')->topic(),
                    'description' => $request->post('description')->topic(),
                    'type' => $request->post('type')->topic(),
                    'text' => $request->post('text')->topic(),
                    'url' => str_replace(array('&#x007B;', '&#x007D;', WEB_URL), array('{', '}', '{WEBURL}'), $request->post('url')->url()),
                    'target' => $request->post('target')->topic(),
                    'publish_start' => strtotime($request->post('publish_start')->date()),
                    'publish_end' => strtotime($request->post('publish_end')->date().' 23:59:59')
                );
                if ($request->post('dateless')->toInt() == 1) {
                    $save['publish_end'] = 0;
                }
                $template = $request->post('template')->toString();
                if ($template && $save['type'] == 'custom') {
                    $save['template'] = preg_replace('/<\?(.*?)\?>/', '', trim($template));
                }
                $id = $request->post('id')->toInt();
                // ตาราง textlink
                $table_name = $this->getTableName('textlink');
                // ตรวจสอบรายการที่เลือก
                $query = $this->db()->createQuery()->from('textlink');
                if ($id > 0) {
                    // แก้ไข
                    $textlink = $query->where($id)->first('id', 'logo');
                } else {
                    // ใหม่
                    $textlink = $query->first(array(
                        Sql::NEXT('link_order', $table_name, null, 'link_order'),
                        Sql::NEXT('id', $table_name, null, 'id')
                    ));
                    if (!$textlink) {
                        $textlink = (object) array(
                            'link_order' => 1,
                            'id' => 1
                        );
                    }
                }
                // ตรวจสอบค่าที่ส่งมา
                $ret = array();
                if (!$textlink) {
                    $ret['alert'] = Language::get('Sorry, Item not found It&#39;s may be deleted');
                } elseif (!preg_match('/^[a-z0-9]{1,}$/u', $save['name'])) {
                    // ภาษาอังกฤษและตัวเลข
                    $ret['ret_name'] = 'this';
                } else {
                    // อัปโหลดไฟล์
                    foreach ($request->getUploadedFiles() as $item => $file) {
                        /* @var $file \Kotchasan\Http\UploadedFile */
                        if ($file->hasUploadFile()) {
                            if (!File::makeDirectory(ROOT_PATH.DATA_FOLDER.'image/')) {
                                // ไดเรคทอรี่ไม่สามารถสร้างได้
                                $ret['ret_'.$item] = sprintf(Language::get('Directory %s cannot be created or is read-only.'), DATA_FOLDER.'image/');
                            } elseif (!$file->validFileExt(array('jpg', 'jpeg', 'gif', 'png'))) {
                                $ret['ret_'.$item] = Language::get('The type of file is invalid');
                            } else {
                                // อัปโหลด
                                $save[$item] = 'textlink-'.$textlink->id.'.'.$file->getClientFileExt();
                                try {
                                    $file->moveTo(ROOT_PATH.DATA_FOLDER.'image/'.$save[$item]);
                                    $info = Image::info(ROOT_PATH.DATA_FOLDER.'image/'.$save[$item]);
                                    if ($info === false) {
                                        @unlink(ROOT_PATH.DATA_FOLDER.'image/'.$save[$item]);
                                        $ret['ret_'.$item] = Language::get('The type of file is invalid');
                                    } else {
                                        $save['width'] = $info['width'];
                                        $save['height'] = $info['height'];
                                        if ($id > 0 && $textlink->$item != $save[$item]) {
                                            // ลบรูปภาพเก่า
                                            @unlink(ROOT_PATH.DATA_FOLDER.'image/'.$textlink->$item);
                                        }
                                    }
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
                        // save
                        $save['text'] = preg_replace('/(&lt;br[\s\/]+&gt;)/iu', '<br>', $save['text']);
                        if ($id == 0) {
                            // ใหม่
                            $save['link_order'] = $textlink->link_order;
                            $save['published'] = 1;
                            $id = $this->db()->insert($table_name, $save);
                        } else {
                            // แก้ไข
                            $this->db()->update($table_name, $textlink->id, $save);
                        }
                        // ส่งค่ากลับ
                        $ret['alert'] = Language::get('Saved successfully');
                        $ret['location'] = $request->getUri()->postBack('index.php', array('name' => $save['name'], 'module' => 'Textlink-settings'));
                        // เคลียร์
                        $request->removeToken();
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

<?php
/**
 * @filesource modules/document/models/write.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Document\Write;

use Gcms\Gcms;
use Gcms\Login;
use Kotchasan\Date;
use Kotchasan\File;
use Kotchasan\Http\Request;
use Kotchasan\Language;

/**
 * บันทึก
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Model extends \Kotchasan\Model
{
    /**
     * บันทึก
     *
     * @param Request $request
     */
    public function submit(Request $request)
    {
        $ret = array();
        // referer, token, can_config, ไม่ใช่สมาชิกตัวอย่าง
        if ($request->initSession() && $request->isSafe() && $login = Login::isMember()) {
            if (Login::notDemoMode($login)) {
                try {
                    // details
                    $details = array();
                    $tags = array();
                    $alias_topic = '';
                    $languages = Gcms::installedLanguage();
                    foreach ($languages as $lng) {
                        $topic = $request->post('topic_'.$lng)->topic();
                        $alias = Gcms::aliasName($request->post('topic_'.$lng)->toString());
                        $relate = $request->post('relate_'.$lng)->quote();
                        $k = $request->post('keywords_'.$lng, array())->topic();
                        $tags = array_merge($tags, $k);
                        $keywords = implode(',', $k);
                        $description = $request->post('description_'.$lng)->description();
                        if (!empty($topic)) {
                            $save = array();
                            $save['topic'] = $topic;
                            $save['keywords'] = empty($keywords) ? $request->post('topic_'.$lng)->keywords(255) : $keywords;
                            $save['description'] = empty($description) ? $request->post('details_'.$lng)->description(255) : $description;
                            $save['detail'] = str_replace(WEB_URL, '{WEBURL}', $request->post('details_'.$lng)->detail());
                            $save['language'] = $lng;
                            $save['relate'] = empty($relate) ? $save['keywords'] : $relate;
                            $details[$lng] = $save;
                            $alias_topic = empty($alias_topic) ? $alias : $alias_topic;
                        }
                    }
                    $save = array(
                        'alias' => Gcms::aliasName($request->post('alias')->toString()),
                        'category_id' => $request->post('category_id')->toInt(),
                        'create_date' => strtotime($request->post('create_date')->date().' '.$request->post('create_time')->date())
                    );
                    // id ที่แก้ไข
                    $id = $request->post('id')->toInt();
                    // ตรวจสอบรายการที่เลือก
                    $index = \Document\Admin\Write\Model::get($request->post('module_id')->toInt(), $id, true);
                    if (empty($index)) {
                        $ret['alert'] = Language::get('Can not be performed this request. Because they do not find the information you need or you are not allowed');
                    } else {
                        // ตาราง index
                        $table = $this->getTableName('index');
                        if (empty($id)) {
                            // เขียนใหม่ตรวจสอบกับ can_write
                            $canWrite = in_array($login['status'], $index->can_write);
                        } else {
                            // แก้ไข ตรวจสอบเจ้าของหรือ ผู้ดูแล
                            $canWrite = ($index->member_id == $login['id'] || in_array($login['status'], $index->moderator));
                        }
                        if ($canWrite) {
                            // ตรวจสอบข้อมูลที่กรอก
                            if (empty($details)) {
                                $lng = reset($languages);
                                $ret['ret_topic_'.$lng] = 'this';
                            } else {
                                foreach ($details as $lng => $values) {
                                    if (mb_strlen($values['topic']) < 3) {
                                        $ret['ret_topic_'.$lng] = 'this';
                                    }
                                }
                            }
                            // มีข้อมูลมาภาษาเดียวให้แสดงในทุกภาษา
                            if (count($details) == 1) {
                                foreach ($details as $i => $item) {
                                    $details[$i]['language'] = '';
                                }
                            }
                            // alias
                            if ($save['alias'] == '') {
                                $save['alias'] = $alias_topic;
                            }
                            if (in_array($save['alias'], Gcms::$MODULE_RESERVE) || is_dir(ROOT_PATH."modules/$save[alias]") || is_dir(ROOT_PATH."widgets/$save[alias]")) {
                                // ชื่อสงวน หรือ ชื่อโฟลเดอร์
                                $ret['ret_alias'] = 'this';
                            } else {
                                // ค้นหาชื่อเรื่องซ้ำ
                                $search = $this->db()->first($table, array(
                                    array('alias', $save['alias']),
                                    array('language', array('', Language::name())),
                                    array('index', '0')
                                ));
                                if ($search && ($id == 0 || $id != $search->id)) {
                                    $ret['ret_alias'] = Language::replace('This :name already exist', array(':name' => Language::get('Alias')));
                                }
                            }
                            if (empty($ret)) {
                                // อัปโหลดไฟล์
                                foreach ($request->getUploadedFiles() as $item => $file) {
                                    /* @var $file \Kotchasan\Http\UploadedFile */
                                    if ($file->hasUploadFile()) {
                                        if (!File::makeDirectory(ROOT_PATH.DATA_FOLDER.'document/')) {
                                            // ไดเรคทอรี่ไม่สามารถสร้างได้
                                            $ret['ret_'.$item] = sprintf(Language::get('Directory %s cannot be created or is read-only.'), DATA_FOLDER.'document/');
                                        } else {
                                            // อัปโหลด
                                            $save[$item] = $item.'-'.$index->module_id.'-'.$index->id.'.'.$file->getClientFileExt();
                                            try {
                                                $file->cropImage($index->img_typies, ROOT_PATH.DATA_FOLDER.'document/'.$save[$item], $index->icon_width, $index->icon_height);
                                                if (!empty($index->$item) && $index->$item != $save[$item]) {
                                                    // ลบรูปภาพเก่า
                                                    @unlink(ROOT_PATH.DATA_FOLDER.'document/'.$index->$item);
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
                            }
                            if ($save['category_id'] > 0 && ($id == 0 || $save['category_id'] != $index->category_id)) {
                                // ใหม่ หรือมีการเปลี่ยนหมวดหมู่ ใช้ค่ากำหนดจากหมวด
                                $category = \Index\Category\Model::get($save['category_id'], $index->module_id);
                                if ($category) {
                                    $save['published'] = $category->published;
                                    $save['can_reply'] = $category->can_reply;
                                }
                            } elseif ($id == 0) {
                                // ใหม่ ไม่มีหมวด ใช้ค่ากำหนดจากโมดูล
                                $save['published'] = $index->published;
                                $save['can_reply'] = empty($index->can_reply) ? 0 : 1;
                            }
                            if (empty($ret)) {
                                $save['last_update'] = time();
                                $save['index'] = 0;
                                $save['ip'] = $request->getClientIp();
                                if (empty($id)) {
                                    // ใหม่
                                    $save['show_news'] = '';
                                    $save['published_date'] = date('Y-m-d');
                                    $save['module_id'] = $index->module_id;
                                    $save['member_id'] = $login['id'];
                                    $index->id = $this->db()->insert($table, $save);
                                } else {
                                    // แก้ไข
                                    $this->db()->update($table, $index->id, $save);
                                }
                                // details
                                $table = $this->getTableName('index_detail');
                                $this->db()->delete($table, array(array('id', $index->id), array('module_id', $index->module_id)), 0);
                                foreach ($details as $save1) {
                                    $save1['module_id'] = $index->module_id;
                                    $save1['id'] = $index->id;
                                    $this->db()->insert($table, $save1);
                                }
                                // อัปเดตหมวดหมู่
                                if ($save['category_id'] > 0) {
                                    \Document\Admin\Write\Model::updateCategories((int) $index->module_id);
                                }
                                // update tags
                                \Index\Tag\Model::update($tags);
                                // ส่งข้อความแจ้งเตือนไปยังไลน์เมื่อมีการเขียนหรือแก้ไขบทความ
                                if (!empty($index->line_notifications)) {
                                    $msg = Language::get('DOCUMENT_NOTIFICATIONS');
                                    if (empty($id) && in_array(1, $index->line_notifications)) {
                                        // เขียน
                                        $line = array(
                                            $login['name'].' '.$msg[1].':',
                                            $save['topic'],
                                            WEB_URL.'index.php?openExternalBrowser=1&module='.$index->module.'&id='.$index->id
                                        );
                                    } elseif (!empty($id) && in_array(2, $index->line_notifications)) {
                                        //  แก้ไข
                                        $line = array(
                                            $login['name'].' '.$msg[2].':',
                                            $save['topic'],
                                            WEB_URL.'index.php?openExternalBrowser=1&module='.$index->module.'&id='.$index->id
                                        );
                                    }
                                    if (isset($line)) {
                                        \Gcms\Line::send(implode("\n", $line));
                                    }
                                }
                                // ส่งค่ากลับ
                                $ret['alert'] = Language::get('Saved successfully');
                                $ret['location'] = 'back';
                                // เคลียร์
                                $request->removeToken();
                            }
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
}

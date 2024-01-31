<?php
/**
 * @filesource modules/document/models/admin/write.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Document\Admin\Write;

use Gcms\Gcms;
use Gcms\Login;
use Kotchasan\ArrayTool;
use Kotchasan\Database\Sql;
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
     * อ่านข้อมูลรายการที่เลือก
     *
     * @param int  $module_id ของโมดูล
     * @param int  $id        ID
     * @param bool $new       false (default) คืนค่า ID สำหรับรายการใหม่, true คืนค่า ID ถัดไปสำหรับรายการใหม่
     *
     * @return object|null คืนค่าข้อมูล object ไม่พบคืนค่า null
     */
    public static function get($module_id, $id, $new = false)
    {
        if (is_int($module_id)) {
            // model
            $model = new static;
            if (empty($id)) {
                // ใหม่ ตรวจสอบโมดูล
                $select = array(
                    'M.id module_id',
                    'M.module',
                    'M.config mconfig'
                );
                if ($new) {
                    $select[] = Sql::NEXT('id', $model->getTableName('index'), null, 'id');
                } else {
                    $select[] = '0 id';
                }
                $index = $model->db()->createQuery()
                    ->from('modules M')
                    ->where(array(
                        array('M.id', $module_id),
                        array('M.owner', 'document')
                    ))
                    ->toArray()
                    ->first($select);
            } else {
                // แก้ไข ตรวจสอบรายการที่เลือก
                $where = array(
                    array('I.id', $id),
                    array('I.index', 0)
                );
                if (!empty($module_id)) {
                    $where[] = array('I.module_id', $module_id);
                }
                $index = $model->db()->createQuery()
                    ->from('index I')
                    ->join('modules M', 'INNER', array(array('M.id', 'I.module_id'), array('M.owner', 'document')))
                    ->where($where)
                    ->toArray()
                    ->first('I.*', 'M.module', 'M.config mconfig');
            }
            if ($index) {
                // ใหม่ ใช้ config จาก mconfig, แก้ไข ใช้ config ของบทความ
                $result = ArrayTool::unserialize($index['mconfig'], $index, empty($id));
                unset($result['mconfig']);
                if (empty($id)) {
                    $result['create_date'] = time();
                    $result['show_news'] = '';
                    $result['alias'] = '';
                    $result['published_date'] = date('Y-m-d');
                }
                return (object) $result;
            }
        }
        return null;
    }

    /**
     * บันทึก
     *
     * @param Request $request
     */
    public function submit(Request $request)
    {
        $ret = array();
        // referer, token, can_config, ไม่ใช่สมาชิกตัวอย่าง
        if ($request->initSession() && $request->isSafe() && $login = Login::adminAccess()) {
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
                        'can_reply' => $request->post('can_reply')->toBoolean(),
                        'show_news' => $request->post('show_news', array())->text(),
                        'published' => $request->post('published')->toBoolean()
                    );
                    // id ที่แก้ไข
                    $id = $request->post('id')->toInt();
                    // ตรวจสอบรายการที่เลือก
                    $index = self::get($request->post('module_id')->toInt(), $id, $save['category_id'], true);
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
                            $tab = false;
                            if (empty($details)) {
                                $lng = reset($languages);
                                $ret['ret_topic_'.$lng] = 'this';
                                $tab = !$tab ? 'detail_'.$lng : $tab;
                            } else {
                                foreach ($details as $lng => $values) {
                                    if (mb_strlen($values['topic']) < 3) {
                                        $ret['ret_topic_'.$lng] = 'this';
                                        $tab = !$tab ? 'detail_'.$lng : $tab;
                                        break;
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
                                $tab = !$tab ? 'options' : $tab;
                            } elseif (preg_match('/^[A-Z]{1,1}[0-9]{0,1}\.[a-zA-Z0-9_]+$/', $save['alias'])) {
                                $ret['ret_alias'] = Language::replace('Cannot use :name', array(':name' => Language::get('Alias')));
                                $tab = !$tab ? 'options' : $tab;
                            } else {
                                // ค้นหาชื่อเรื่องซ้ำ
                                $search = $this->db()->first($table, array(
                                    array('alias', $save['alias']),
                                    array('language', array('', Language::name())),
                                    array('index', '0')
                                ));
                                if ($search && ($id == 0 || $id != $search->id)) {
                                    $ret['ret_alias'] = Language::replace('This :name already exist', array(':name' => Language::get('Alias')));
                                    $tab = !$tab ? 'options' : $tab;
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
                                            $tab = !$tab ? 'options' : $tab;
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
                                                $tab = !$tab ? 'options' : $tab;
                                            }
                                        }
                                    } elseif ($file->hasError()) {
                                        // ข้อผิดพลาดการอัปโหลด
                                        $ret['ret_'.$item] = Language::get($file->getErrorMessage());
                                        $tab = !$tab ? 'options' : $tab;
                                    }
                                }
                            }
                            if (empty($ret)) {
                                $save['create_date'] = strtotime($request->post('create_date')->date().' '.$request->post('create_time')->date().':00');
                                $save['published_date'] = $request->post('published_date')->date();
                                $save['last_update'] = time();
                                $save['ip'] = $request->getClientIp();
                                $show_news = array();
                                foreach ($save['show_news'] as $item) {
                                    $show_news[] = "$item=1";
                                }
                                $save['show_news'] = implode("\n", $show_news);
                                if (empty($id)) {
                                    // ใหม่
                                    $save['index'] = 0;
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
                                    self::updateCategories((int) $index->module_id);
                                }
                                // update tags
                                \Index\Tag\Model::update($tags);
                                // ส่งข้อความแจ้งเตือนไปยังไลน์เมื่อมีการเขียนหรือแก้ไขบทความ
                                if (!empty($index->line_notifications)) {
                                    if (empty($id) && in_array(1, $index->line_notifications)) {
                                        // เขียน
                                        $msg = Language::get('DOCUMENT_NOTIFICATIONS');
                                        \Gcms\Line::send($msg[1].' '.WEB_URL.'index.php?module='.$index->module.'&id='.$index->id);
                                    } elseif (!empty($id) && in_array(2, $index->line_notifications)) {
                                        //  แก้ไข
                                        $msg = Language::get('DOCUMENT_NOTIFICATIONS');
                                        \Gcms\Line::send($msg[2].' '.WEB_URL.'index.php?module='.$index->module.'&id='.$index->id);
                                    }
                                }
                                // ส่งค่ากลับ
                                $ret['alert'] = Language::get('Saved successfully');
                                $ret['location'] = $request->getUri()->postBack('index.php', array('mid' => $index->module_id, 'module' => 'document-setup'));
                                // เคลียร์
                                $request->removeToken();
                            } elseif ($tab) {
                                $ret['tab'] = $tab;
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

    /**
     * อ่านรายละเอียด (detail) ของบทความตามภาษา
     *
     * @param int    $module_id
     * @param int    $id
     * @param string $lng
     *
     * @return array
     */
    public static function details($module_id, $id, $lng)
    {
        $result = array();
        if (is_int($module_id) && $module_id > 0) {
            // model
            $model = new static;
            $query = $model->db()
                ->createQuery()
                ->select('language', 'topic', 'keywords', 'relate', 'description', 'detail')
                ->from('index_detail')
                ->where(array(
                    array('id', $id),
                    array('module_id', $module_id)
                ))
                ->toArray();
            foreach ($query->execute() as $i => $item) {
                $item['language'] = ($i == 0 && $item['language'] == '') ? $lng : $item['language'];
                $result[$item['language']] = (object) $item;
            }
        }
        return $result;
    }

    /**
     * อัปเดตจำนวนบทความและความคิดเห็นในหมวดหมู่
     *
     * @param int $module_id
     */
    public static function updateCategories($module_id)
    {
        if (is_int($module_id) && $module_id > 0) {
            $model = new static;
            $sql1 = $model->db()->createQuery()->selectCount()->from('index')->where(array(
                array('category_id', 'C.category_id'),
                array('module_id', 'C.module_id'),
                array('index', '0')
            ));
            $sql2 = $model->db()->createQuery()->select('id')->from('index')->where(array(
                array('category_id', 'C.category_id'),
                array('module_id', 'C.module_id'),
                array('index', '0')
            ));
            $sql3 = $model->db()->createQuery()->selectCount()->from('comment')->where(array(
                array('index_id', 'IN', $sql2),
                array('module_id', 'C.module_id')
            ));
            $model->db()->createQuery()->update('category C')->set(array(
                'C.c1' => $sql1,
                'C.c2' => $sql3
            ))->where(array('C.module_id', $module_id))->execute();
        }
    }
}

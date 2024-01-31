<?php
/**
 * @filesource modules/documentation/models/admin/write.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Documentation\Admin\Write;

use Gcms\Gcms;
use Gcms\Login;
use Kotchasan\ArrayTool;
use Kotchasan\Database\Sql;
use Kotchasan\Http\Request;
use Kotchasan\Language;

/**
 * อ่านข้อมูลโมดูล
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
     * @param int $module_id   ของโมดูล
     * @param int $id          ID ของบทความ
     * @param int $category_id หมวดหมู่
     *
     * @return object|null คืนค่าข้อมูล object ไม่พบคืนค่า null
     */
    public static function get($module_id, $id, $category_id)
    {
        if (is_int($module_id)) {
            // model
            $model = new static;
            $query = $model->db()->createQuery();
            if (empty($id)) {
                // ใหม่ ตรวจสอบโมดูล
                $query->select('M.id module_id', 'M.owner', 'M.module', 'M.config')
                    ->from('modules M')
                    ->join('index D', 'INNER', array(array('D.module_id', 'M.id'), array('D.index', 1), array('D.language', array('', Language::name()))))
                    ->where(array(
                        array('M.id', $module_id),
                        array('M.owner', 'documentation')
                    ));
            } else {
                // แก้ไข ตรวจสอบรายการที่เลือก
                $query->select('I.*', 'M.owner', 'M.module', 'M.config')
                    ->from('index I')
                    ->join('modules M', 'INNER', array(array('M.id', 'I.module_id'), array('M.owner', 'documentation')))
                    ->join('index D', 'INNER', array(array('D.module_id', 'I.module_id'), array('D.index', 1), array('D.language', array('', Language::name()))))
                    ->where(array(
                        array('I.id', $id),
                        array('I.index', 0)
                    ));
            }
            $result = $query->limit(1)->toArray()->execute();
            if (count($result) == 1) {
                $result = ArrayTool::unserialize($result[0]['config'], $result[0], empty($id));
                unset($result['config']);
                if (empty($id)) {
                    $result['id'] = 0;
                    $result['create_date'] = time();
                    $result['alias'] = '';
                    $result['published'] = 1;
                    $result['category_id'] = $category_id;
                }
                return (object) $result;
            }
        }
        return null;
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
     * อัปเดตจำนวนบทความ ในหมวดหมู่
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
            $model->db()->createQuery()->update('category C')->set(array(
                'C.c1' => $sql1
            ))->where(array('C.module_id', $module_id))->execute();
        }
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
                // ค่าที่ส่งมา
                $tab = false;
                // details
                $details = array();
                $alias_topic = '';
                $languages = Language::installedLanguage();
                foreach ($languages as $lng) {
                    $topic = $request->post('topic_'.$lng)->topic();
                    $alias = Gcms::aliasName($request->post('topic_'.$lng)->toString());
                    $relate = $request->post('relate_'.$lng)->quote();
                    $keywords = $request->post('keywords_'.$lng)->keywords();
                    $description = $request->post('description_'.$lng)->description();
                    if (!empty($topic)) {
                        $save = array();
                        $save['topic'] = $topic;
                        $save['keywords'] = empty($keywords) ? $request->post('topic_'.$lng)->keywords(255) : $keywords;
                        $save['description'] = empty($description) ? $request->post('details_'.$lng)->description(255) : $description;
                        $save['detail'] = $request->post('details_'.$lng)->detail();
                        $save['language'] = $lng;
                        $save['relate'] = empty($relate) ? $save['keywords'] : $relate;
                        $details[$lng] = $save;
                        $alias_topic = empty($alias_topic) ? $alias : $alias_topic;
                    }
                }
                $save = array(
                    'alias' => Gcms::aliasName($request->post('alias')->toString()),
                    'category_id' => $request->post('category_id')->toInt(),
                    'published' => $request->post('published')->toBoolean()
                );
                // id ที่แก้ไข
                $id = $request->post('id')->toInt();
                $module_id = $request->post('module_id')->toInt();
                // ตาราง index
                $table_index = $this->getTableName('index');
                // query builder
                $query = $this->db()->createQuery();
                if (empty($id)) {
                    // ตรวจสอบโมดูล (ใหม่)
                    $query->select('M.id module_id', 'M.module', 'M.config', Sql::NEXT('id', $table_index, array('module_id', 'M.id'), 'id'))
                        ->from('modules M')
                        ->where(array(
                            array('M.id', $module_id),
                            array('M.owner', 'documentation')
                        ))
                        ->limit(1);
                } else {
                    // ตรวจสอบโมดูล หรือ เรื่องที่เลือก (แก้ไข)
                    $query->select('I.id', 'I.module_id', 'M.module', 'M.config', 'I.picture', 'I.member_id')
                        ->from('modules M')
                        ->join('index I', 'INNER', array(array('I.module_id', 'M.id'), array('I.id', $id), array('I.index', '0')))
                        ->where(array(
                            array('M.id', $module_id),
                            array('M.owner', 'documentation')
                        ))
                        ->limit(1);
                }
                $index = $query->toArray()->execute();
                if (empty($index)) {
                    $ret['alert'] = Language::get('Can not be performed this request. Because they do not find the information you need or you are not allowed');
                } else {
                    $index = ArrayTool::unserialize($index[0]['config'], $index[0]);
                    unset($index['config']);
                    if (empty($id)) {
                        // เขียนใหม่ตรวจสอบกับ can_write
                        $canWrite = in_array($login['status'], $index['can_write']);
                    } else {
                        // แก้ไข เจ้าของหรือแอดมิน
                        $canWrite = $index['member_id'] == $login['id'] || Login::isAdmin();
                    }
                    if ($canWrite) {
                        // ตรวจสอบข้อมูลที่กรอก
                        if (empty($details)) {
                            $lng = reset($languages);
                            $ret['ret_topic_'.$lng] = 'this';
                            $tab = !$tab ? 'detail_'.$lng : $tab;
                        } else {
                            foreach ($details as $lng => $values) {
                                if (mb_strlen($values['topic']) < 3) {
                                    $ret['ret_topic_'.$lng] = 'this';
                                    $tab = !$tab ? 'detail_'.$lng : $tab;
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
                        } else {
                            // ค้นหาชื่อเรื่องซ้ำ
                            $search = $this->db()->first($table_index, array(
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
                            $save['last_update'] = time();
                            $save['index'] = 0;
                            $save['ip'] = $request->getClientIp();
                            if (empty($id)) {
                                // ใหม่
                                $save['module_id'] = $index['module_id'];
                                $save['member_id'] = $login['id'];
                                $save['create_date'] = $save['last_update'];
                                $index['id'] = $this->db()->insert($table_index, $save);
                            } else {
                                // แก้ไข
                                $this->db()->update($table_index, $index['id'], $save);
                            }
                            // details
                            $index_detail = $this->getTableName('index_detail');
                            $this->db()->delete($index_detail, array(array('id', $index['id']), array('module_id', $index['module_id'])), 0);
                            foreach ($details as $save1) {
                                $save1['module_id'] = $index['module_id'];
                                $save1['id'] = $index['id'];
                                $this->db()->insert($index_detail, $save1);
                            }
                            // อัปเดตหมวดหมู่
                            if ($save['category_id'] > 0) {
                                self::updateCategories((int) $index['module_id']);
                            }
                            // ส่งค่ากลับ
                            $ret['alert'] = Language::get('Saved successfully');
                            $ret['location'] = $request->getUri()->postBack('index.php', array('mid' => $index['module_id'], 'module' => 'documentation-setup'));
                            // เคลียร์
                            $request->removeToken();
                        } elseif ($tab) {
                            $ret['tab'] = $tab;
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

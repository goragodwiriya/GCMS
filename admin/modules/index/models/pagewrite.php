<?php
/**
 * @filesource modules/index/models/pagewrite.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Index\Pagewrite;

use Gcms\Login;
use Kotchasan\Date;
use Kotchasan\Http\Request;
use Kotchasan\Language;

/**
 * อ่าน/บันทึก ข้อมูลหน้าเพจ
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Model extends \Kotchasan\Model
{
    /**
     * อ่านหน้าเพจ
     * id = 0 สร้างหน้าใหม่
     *
     * @param int    $id
     * @param string $owner
     *
     * @return object|null คืนค่า object ของข้อมูล ไม่พบคืนค่า null
     */
    public static function getIndex($id, $owner)
    {
        if (is_int($id)) {
            if (empty($id)) {
                // ใหม่
                return (object) array(
                    'owner' => $owner,
                    'id' => 0,
                    'published' => 1,
                    'module' => '',
                    'topic' => '',
                    'keywords' => '',
                    'description' => '',
                    'detail' => '',
                    'last_update' => 0,
                    'published_date' => date('Y-m-d'),
                    'language' => ''
                );
            } else {
                // แก้ไข
                $select = array(
                    'I.id',
                    'I.language',
                    'D.topic',
                    'D.keywords',
                    'D.description',
                    'D.detail',
                    'I.last_update',
                    'I.published',
                    'I.published_date',
                    'M.module',
                    'M.owner'
                );
                return static::createQuery()
                    ->from('index I')
                    ->join('modules M', 'INNER', array(array('M.id', 'I.module_id')))
                    ->join('index_detail D', 'INNER', array(array('D.id', 'I.id'), array('D.module_id', 'I.module_id'), array('D.language', 'I.language')))
                    ->where(array(
                        array('I.id', $id),
                        array('I.index', 1)
                    ))
                    ->first($select);
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
        // session, token, member, can_config, ไม่ใช่สมาชิกตัวอย่าง
        if ($request->initSession() && $request->isSafe() && $login = Login::adminAccess()) {
            if (Login::checkPermission($login, 'can_config') && Login::notDemoMode($login)) {
                try {
                    // รับค่าจากการ POST
                    $module_id = 0;
                    // index
                    $index_save = array(
                        'language' => strtolower($request->post('language')->text()),
                        'published' => $request->post('published')->toBoolean(),
                        'published_date' => $request->post('published_date')->date()
                    );
                    // modules
                    $module_save = array(
                        'owner' => $request->post('owner')->filter('a-z'),
                        'module' => $request->post('module')->filter('a-z0-9')
                    );
                    // index_detail
                    $detail_save = array(
                        'language' => $index_save['language'],
                        'topic' => $request->post('topic')->topic(),
                        'keywords' => $request->post('keywords')->keywords(),
                        'detail' => str_replace(WEB_URL, '{WEBURL}', $request->post('detail')->detail()),
                        'description' => $request->post('description')->description()
                    );
                    $index_id = $request->post('id')->toInt();
                    $detail_save['keywords'] = empty($detail_save['keywords']) ? $request->post('topic')->keywords(149) : $detail_save['keywords'];
                    $detail_save['description'] = empty($detail_save['description']) ? $request->post('detail')->keywords(149) : $detail_save['description'];
                    // model
                    $model = new static;
                    // ชื่อตาราง
                    $table_index = $model->getTableName('index');
                    $table_index_detail = $model->getTableName('index_detail');
                    $table_modules = $model->getTableName('modules');
                    if (!empty($index_id)) {
                        // หน้าที่แก้ไข
                        $index = $model->db()->createQuery()
                            ->from('index I')
                            ->join('index_detail D', 'INNER', array(array('D.id', 'I.id'), array('D.module_id', 'I.module_id'), array('D.language', 'I.language')))
                            ->where(array('I.id', $index_id))
                            ->limit(1)
                            ->toArray()
                            ->first('D.id', 'D.language', 'D.module_id');
                    }
                    if ((!empty($index_id) && !$index) || !preg_match('/[a-z]{3,}/', $module_save['owner']) || !is_dir(ROOT_PATH.'modules/'.$module_save['owner'])) {
                        // owner ไม่ถูกต้อง
                        $ret['alert'] = Language::get('Unable to complete the transaction');
                    } elseif (!preg_match('/^[a-z0-9]{2,}$/', $module_save['module'])) {
                        // module ไม่ถูกต้อง
                        $ret['ret_module'] = 'this';
                    } elseif ($module_save['owner'] === 'index' && $module_save['module'] != 'home' && is_dir(ROOT_PATH.'modules/'.$module_save['module'])) {
                        // index ไม่สามารถใช้ชื่อโมดูลหรือวิดเจ็ตได้
                        $ret['ret_module'] = Language::get('Invalid name');
                    } else {
                        // ค้นหาชื่อโมดูลซ้ำ
                        $where = array(array('M.module', $module_save['module']));
                        if ($index_id > 0) {
                            $where[] = array('I.id', '!=', $index_id);
                        }
                        $query = $model->db()->createQuery()
                            ->select('I.language', 'I.module_id')
                            ->from('modules M')
                            ->join('index I', 'INNER', array(array('I.module_id', 'M.id'), array('I.index', 1)))
                            ->where($where)
                            ->toArray();
                        foreach ($query->execute() as $item) {
                            if (empty($detail_save['language']) ||
                                empty($item['language']) ||
                                $item['language'] == $detail_save['language']
                            ) {
                                $ret['ret_module'] = Language::replace('This :name already exist', array(':name' => Language::get('Module')));
                            }
                        }
                        // topic
                        if (mb_strlen($detail_save['topic']) < 3) {
                            $ret['ret_topic'] = 'this';
                        } elseif (empty($ret)) {
                            // ค้นหาชื่อไตเติลซ้ำ
                            $search = $model->db()->first($table_index_detail, array(
                                array('topic', $detail_save['topic']),
                                array('language', array('', $detail_save['language']))
                            ));
                            if ($search && (empty($index_id) || $index_id != $search->id)) {
                                $ret['ret_topic'] = Language::replace('This :name already exist', array(':name' => Language::get('Topic')));
                            }
                        }
                        if (empty($ret)) {
                            $index_save['ip'] = $request->getClientIp();
                            $index_save['last_update'] = time();
                            if (empty($index_id)) {
                                // ใหม่
                                if (empty($module_id)) {
                                    // โมดูลใหม่
                                    $class = ucfirst($module_save['owner']).'\Admin\Settings\Model';
                                    if (class_exists($class)) {
                                        // ค่าติดตั้งเริ่มต้น
                                        if (method_exists($class, 'defaultSettings')) {
                                            $module_save['config'] = serialize($class::defaultSettings());
                                        }
                                        // มี method install
                                        if (method_exists($class, 'install')) {
                                            $class::install($module_save);
                                        }
                                    }
                                    $module_id = $model->db()->insert($table_modules, $module_save);
                                }
                                $index_save['member_id'] = $login['id'];
                                $index_save['create_date'] = $index_save['last_update'];
                                $index_save['index'] = 1;
                                $index_save['module_id'] = $module_id;
                                $index_id = $model->db()->insert($table_index, $index_save);
                                $detail_save['id'] = $index_id;
                                $detail_save['module_id'] = $module_id;
                                $model->db()->insert($table_index_detail, $detail_save);
                            } else {
                                // แก้ไข
                                $model->db()->update($table_index, (int) $index['id'], $index_save);
                                $model->db()->update($table_modules, (int) $index['module_id'], $module_save);
                                $model->db()->update($table_index_detail, array(
                                    array('id', $index['id']),
                                    array('module_id', $index['module_id']),
                                    array('language', $index['language'])
                                ), $detail_save);
                            }
                            // ส่งค่ากลับ
                            $ret['alert'] = Language::get('Saved successfully');
                            // กลับไปหน้าก่อนหน้า
                            $ret['location'] = $request->getUri()->postBack('index.php');
                            // เคลียร์
                            $request->removeToken();
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
     * สำเนาหน้าเพจหรือโมดูลไปยังภาษาอื่น
     *
     * @param Request $request
     */
    public static function copy(Request $request)
    {
        $ret = array();
        // session, referer, member, can_config, ไม่ใช่สมาชิกตัวอย่าง
        if ($request->initSession() && $request->isReferer() && $login = Login::adminAccess()) {
            if (Login::checkPermission($login, 'can_config') && Login::notDemoMode($login)) {
                $id = $request->post('id')->toInt();
                $lng = $request->post('lng')->toString();
                // model
                $model = new static;
                // ชื่อตาราง
                $table_index = $model->getTableName('index');
                $table_index_detail = $model->getTableName('index_detail');
                // ตรวจสอบรายการที่เลือก
                $index = $model->db()->first($table_index, array(
                    array('id', $id),
                    array('index', 1)
                ));
                if ($index === false) {
                    $ret['alert'] = Language::get('Sorry, Item not found It&#39;s may be deleted');
                } else {
                    if ($index->language == '') {
                        $ret['alert'] = Language::get('This entry is displayed in all languages');
                    } else {
                        // ตรวจสอบโมดูลซ้ำ
                        $search = $model->db()->first($table_index, array(
                            array('language', $lng),
                            array('module_id', (int) $index->module_id)
                        ));
                        if ($search !== false) {
                            $ret['alert'] = Language::get('This entry is in selected language');
                        } else {
                            $old_lng = $index->language;
                            // อ่าน detail
                            $detail = $model->db()->first($table_index_detail, array(
                                array('id', (int) $index->id),
                                array('module_id', (int) $index->module_id),
                                array('language', $index->language)
                            ));
                            // เปลี่ยนรายการปัจจุบันเป็นรายการในภาษาใหม่
                            $model->db()->update($table_index, $index->id, array('language' => $lng));
                            $model->db()->update($table_index_detail, array(array('id', (int) $index->id), array('module_id', (int) $index->module_id), array('language', $old_lng)), array('language' => $lng));
                            unset($index->id);
                            // บันรายการเดิมเป็น ID ใหม่
                            $detail->id = $model->db()->insert($table_index, $index);
                            $model->db()->insert($table_index_detail, $detail);
                            // คืนค่า
                            $ret['alert'] = Language::get('Copy successfully, you can edit this entry');
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

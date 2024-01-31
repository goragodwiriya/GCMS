<?php
/**
 * @filesource modules/index/models/modulepage.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Index\Modulepage;

use Gcms\Login;
use Kotchasan\Date;
use Kotchasan\Http\Request;
use Kotchasan\Language;

/**
 * อ่าน/บันทึก topic, descript ของหน้าเว็บไซต์ย่อยของโมดูล
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
     * @param int $id
     *
     * @return object|null คืนค่า object ของข้อมูล ไม่พบคืนค่า null
     */
    public static function get($id)
    {
        if (empty($id)) {
            // ใหม่
            return (object) array(
                'id' => 0,
                'language' => '',
                'topic' => '',
                'keywords' => '',
                'description' => '',
                'module' => '',
                'page' => ''
            );
        } else {
            // แก้ไข
            $select = array(
                'I.id',
                'I.language',
                'D.topic',
                'D.keywords',
                'D.description',
                'M.module',
                'I.page'
            );
            return static::createQuery()
                ->from('index I')
                ->join('modules M', 'INNER', array(array('M.id', 'I.module_id')))
                ->join('index_detail D', 'INNER', array(array('D.id', 'I.id'), array('D.module_id', 'I.module_id'), array('D.language', 'I.language')))
                ->where(array(
                    array('I.id', $id),
                    array('I.index', 2)
                ))
                ->first($select);
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
                    $index_save = array(
                        'page' => $request->post('page')->filter('a-z0-9'),
                        'language' => $request->post('language')->filter('a-z'),
                        'published' => 1,
                        'published_date' => date('Y-m-d')
                    );
                    $detail_save = array(
                        'language' => $index_save['language'],
                        'topic' => $request->post('topic')->topic(),
                        'keywords' => $request->post('keywords')->keywords(),
                        'description' => $request->post('description')->description()
                    );
                    $id = $request->post('id')->toInt();
                    // ตรวจสอบค่าที่ส่งมา
                    if ($index_save['page'] == '') {
                        $ret['ret_page'] = 'Please fill in';
                    } elseif ($detail_save['topic'] == '') {
                        $ret['ret_topic'] = 'Please fill in';
                    } elseif ($detail_save['keywords'] == '') {
                        $ret['ret_keywords'] = 'Please fill in';
                    } elseif ($detail_save['description'] == '') {
                        $ret['ret_description'] = 'Please fill in';
                    } else {
                        if ($id > 0) {
                            // แก้ไข
                            $index = $this->db()->createQuery()
                                ->from('index I')
                                ->where(array('I.id', $id))
                                ->first('I.id', 'I.language', 'I.module_id');
                        } else {
                            // ใหม่
                            $index = $this->db()->createQuery()
                                ->from('modules')
                                ->where(array('module', $request->post('module')->filter('a-z0-9')))
                                ->first('0 id', 'id module_id');
                        }
                        if (empty($index)) {
                            // ข้อมูลไม่ถูกต้อง
                            $ret['alert'] = Language::get('Unable to complete the transaction');
                        } else {
                            // ค้นหาชื่อ page ซ้ำ
                            $where = array(
                                array('I.module_id', $index->module_id),
                                array('I.page', $index_save['page'])
                            );
                            if ($id > 0) {
                                $where[] = array('I.id', '!=', $id);
                            }
                            $query = $this->db()->createQuery()
                                ->select('I.id', 'I.language', 'I.module_id')
                                ->from('index I')
                                ->where($where);
                            foreach ($query->execute() as $item) {
                                if (empty($detail_save['language']) ||
                                    empty($item['language']) ||
                                    $item['language'] == $detail_save['language']
                                ) {
                                    $ret['ret_page'] = Language::replace('This :name already exist', array(':name' => Language::get('Webpage')));
                                }
                            }
                            if (empty($ret)) {
                                $index_save['ip'] = $request->getClientIp();
                                $index_save['last_update'] = time();
                                if ($index->id == 0) {
                                    // ใหม่
                                    $index_save['member_id'] = $login['id'];
                                    $index_save['create_date'] = $index_save['last_update'];
                                    $index_save['index'] = 2;
                                    $index_save['module_id'] = $index->module_id;
                                    $detail_save['id'] = $this->db()->insert($this->getTableName('index'), $index_save);
                                    $detail_save['module_id'] = $index->module_id;
                                    $this->db()->insert($this->getTableName('index_detail'), $detail_save);
                                } else {
                                    // แก้ไข
                                    $this->db()->update($this->getTableName('index'), $index->id, $index_save);
                                    $this->db()->update($this->getTableName('index_detail'), array(
                                        array('id', $index->id),
                                        array('module_id', $index->module_id),
                                        array('language', $index->language)
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

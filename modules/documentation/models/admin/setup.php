<?php
/**
 * @filesource modules/documentation/models/admin/setup.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Documentation\Admin\Setup;

use Gcms\Gcms;
use Gcms\Login;
use Kotchasan\Database\Sql;
use Kotchasan\Http\Request;
use Kotchasan\Language;

/**
 * โมเดลสำหรับแสดงรายการบทความ (setup.php)
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Model extends \Kotchasan\Orm\Field
{
    /**
     * ชื่อตาราง
     *
     * @var string
     */
    protected $table = 'index P';

    /**
     * query หน้าเพจ เรียงลำดับตาม module,language
     *
     * @return array
     */
    public function getConfig()
    {
        return array(
            'select' => array(
                'P.id',
                'D.topic',
                'P.published',
                'P.category_id',
                Sql::create('(CASE WHEN ISNULL(U.`id`) THEN P.`email` WHEN U.`displayname`=\'\' THEN U.`email` ELSE U.`displayname` END) AS `writer`'),
                'P.last_update',
                'P.member_id',
                'P.visited',
                'U.status',
                'P.module_id',
                'P.index',
                'P.language'
            ),
            'join' => array(
                array(
                    'INNER',
                    'Index\Detail\Model',
                    array(
                        array('D.id', 'P.id'),
                        array('D.module_id', 'P.module_id')
                    )
                ),
                array(
                    'LEFT',
                    'Index\User\Model',
                    array(
                        array('U.id', 'P.member_id')
                    )
                )
            ),
            'order' => array(
                'P.create_date DESC'
            )
        );
    }

    /**
     * รับค่าจาก action ของ table (setup.php)
     *
     * @param Request $request
     */
    public static function action(Request $request)
    {
        $ret = array();
        // session, referer, member, ไม่ใช่สมาชิกตัวอย่าง
        if ($request->initSession() && $request->isReferer() && $login = Login::adminAccess()) {
            if (Login::notDemoMode($login)) {
                // รับค่าจากการ POST
                $id = $request->post('id')->toString();
                $action = $request->post('action')->toString();
                // อ่านข้อมูลโมดูล และ config
                $index = \Index\Adminmodule\Model::getModuleWithConfig('documentation', $request->post('mid')->toInt());
                if ($index && Gcms::canConfig($login, $index, 'can_write')) {
                    // Model
                    $model = new \Kotchasan\Model();
                    if ($action === 'published') {
                        // สถานะการเผยแพร่
                        $table = $model->getTableName('index');
                        $search = $model->db()->first($table, array(array('id', (int) $id), array('module_id', (int) $index->module_id)));
                        if ($search) {
                            $published = $search->published == 1 ? 0 : 1;
                            $model->db()->update($table, $search->id, array('published' => $published));
                            // คืนค่า
                            $ret['elem'] = 'published_'.$search->id;
                            $lng = Language::get('PUBLISHEDS');
                            $ret['title'] = $lng[$published];
                            $ret['class'] = 'icon-published'.$published;
                        }
                    } elseif ($action === 'delete' && preg_match('/^[0-9,]+$/', $id)) {
                        // ลบรายการที่เลือก
                        $model->db()->createQuery()->delete('index', array(array('id', explode(',', $id)), array('module_id', (int) $index->id)))->execute();
                        $model->db()->createQuery()->delete('index_detail', array(array('id', explode(',', $id)), array('module_id', (int) $index->id)))->execute();
                        // reload
                        $ret['location'] = 'reload';
                    } elseif ($action === 'move') {
                        $data = $request->post('data')->toString();
                        if (preg_match('/[0-9,]+/', $data)) {
                            $data = explode(',', $data);
                            $top = 0;
                            $ids = array();
                            $table_index = $model->getTableName('index');
                            foreach ($model->db()->find($table_index, array(array('id', $data), array('module_id', (int) $index->module_id))) as $item) {
                                $top = max($top, $item->create_date);
                                $ids[$item->id] = $item->create_date;
                            }
                            foreach ($data as $id) {
                                if (isset($ids[$id])) {
                                    $model->db()->update($table_index, $id, array('create_date' => $top));
                                    --$top;
                                }
                            }
                            $ret['save'] = true;
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

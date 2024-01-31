<?php
/**
 * @filesource modules/index/models/mods.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Index\Mods;

use Gcms\Login;
use Kotchasan\Http\Request;
use Kotchasan\Language;

/**
 * โมเดลสำหรับแสดงรายการหน้าเพจ (modules.php)
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
    protected $table = 'index I';

    /**
     * query เมนู เรียงลำดับตาม owner,module,language
     *
     * @return array
     */
    public function getConfig()
    {
        return array(
            'select' => array(
                'I.id',
                'M.id AS module_id',
                'D.topic',
                'I.published',
                'I.language',
                'M.module',
                'M.owner',
                'I.last_update',
                'I.visited',
                'I.index'
            ),
            'join' => array(
                array(
                    'INNER',
                    'Index\Pages\Model',
                    array(
                        array('M.id', 'I.module_id'),
                        array('M.owner', '!=', 'index')
                    )
                ),
                array(
                    'INNER',
                    'Index\Detail\Model',
                    array(
                        array('D.id', 'I.id'),
                        array('D.module_id', 'I.module_id')
                    )
                )
            )
        );
    }

    /**
     * รับค่าจาก action ของ table
     *
     * @param Request $request
     */
    public function action(Request $request)
    {
        $ret = array();
        // referer, session, member, ไม่ใช่สมาชิกตัวอย่าง
        if ($request->initSession() && $request->isReferer() && $login = Login::adminAccess()) {
            if (Login::notDemoMode($login) && Login::checkPermission($login, 'can_config')) {
                // ค่าที่ส่งมา
                $action = $request->post('action')->toString();
                $id = $request->post('id')->toInt();
                // Model
                $model = new \Kotchasan\Model();
                if ($action === 'published') {
                    // เผยแพร่
                    $index = $model->db()->first($model->getTableName('index'), $id);
                    if ($index) {
                        $published = $index->published == 1 ? 0 : 1;
                        $model->db()->update($model->getTableName('index'), $index->id, array('published' => $published));
                        // คืนค่า
                        $ret['elem'] = 'published_'.$index->id;
                        $lng = Language::get('PUBLISHEDS');
                        $ret['title'] = $lng[$published];
                        $ret['class'] = 'icon-published'.$published;
                    }
                } elseif ($action === 'delete') {
                    // ลบโมดูลและหน้าเพจ ไม่ลบข้อมูลของโมดูล
                    $query = $model->db()->createQuery()
                        ->select('id', 'module_id')
                        ->from('index')
                        ->where(array(
                            array('index', 1),
                            array('module_id', $model->db()->createQuery()->select('module_id')->from('index')->where(array('id', $id)))
                        ));
                    $count = 0;
                    foreach ($query->execute() as $field) {
                        ++$count;
                        if ($field->id == $id) {
                            $model->db()->delete($model->getTableName('index'), $id);
                            $model->db()->delete($model->getTableName('index_detail'), $id);
                        }
                    }
                    // ลบโมดูล ถ้าไม่มีรายการในภาษาอื่น
                    if ($count < 2) {
                        $model->db()->delete($model->getTableName('modules'), $field->module_id);
                    }
                    // คืนค่า
                    $ret['delete_id'] = $request->post('src')->toString().'_'.$id;
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

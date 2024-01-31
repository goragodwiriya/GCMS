<?php
/**
 * @filesource event/models/admin/setup.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Event\Admin\Setup;

use Gcms\Gcms;
use Gcms\Login;
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
    protected $table = 'event A';

    /**
     * query หน้าเพจ เรียงลำดับตาม module,language
     *
     * @return array
     */
    public function getConfig()
    {
        return array(
            'select' => array(
                'A.id',
                'A.topic',
                'A.color',
                'A.begin_date',
                'A.end_date',
                'A.last_update',
                'A.published',
                array($this->db()->createQuery()->select('email')->from('user U')->where(array('U.id', 'A.member_id')), 'writer'),
                'A.module_id'
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
                $index = \Index\Adminmodule\Model::getModuleWithConfig('event', $request->post('mid')->toInt());
                if ($index && Gcms::canConfig($login, $index, 'can_write') && preg_match('/^[0-9,]+$/', $id)) {
                    $module_id = (int) $index->module_id;
                    // Model
                    $model = new \Kotchasan\Model();
                    if ($action === 'delete') {
                        // ลบ
                        $id = explode(',', $id);
                        // ลบข้อมูล
                        $model->db()->createQuery()->delete('event', array(array('id', $id), array('module_id', $module_id)))->execute();
                        // reload
                        $ret['location'] = 'reload';
                    } elseif ($action === 'published') {
                        // สถานะการเผยแพร่
                        $id = (int) $id;
                        $table_event = $model->getTableName('event');
                        $search = $model->db()->first($table_event, array(array('id', $id), array('module_id', $module_id)));
                        if ($search) {
                            $published = $search->published == 1 ? 0 : 1;
                            $model->db()->update($table_event, $search->id, array('published' => $published));
                            // คืนค่า
                            $ret['elem'] = 'published_'.$search->id;
                            $lng = Language::get('PUBLISHEDS');
                            $ret['title'] = $lng[$published];
                            $ret['class'] = 'icon-published'.$published;
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

<?php
/**
 * @filesource modules/portfolio/models/admin/setup.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Portfolio\Admin\Setup;

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
    protected $table = 'portfolio P';

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
                $index = \Index\Adminmodule\Model::getModuleWithConfig('portfolio', $request->post('mid')->toInt());
                if ($index && Gcms::canConfig($login, $index, 'can_write')) {
                    // Model
                    $model = new \Kotchasan\Model();
                    if ($action === 'published') {
                        // สถานะการเผยแพร่
                        $table_index = $model->getTableName('portfolio');
                        $search = $model->db()->first($table_index, array(array('id', (int) $id), array('module_id', (int) $index->module_id)));
                        if ($search) {
                            $published = $search->published == 1 ? 0 : 1;
                            $model->db()->update($table_index, $search->id, array('published' => $published));
                            // คืนค่า
                            $ret['elem'] = 'published_'.$search->id;
                            $lng = Language::get('PUBLISHEDS');
                            $ret['title'] = $lng[$published];
                            $ret['class'] = 'icon-published'.$published;
                        }
                    } elseif ($action === 'delete' && preg_match('/^[0-9,]+$/', $id)) {
                        // ลบรายการที่เลือก
                        $query = $model->db()->createQuery()->select('id', 'picture')->from('portfolio')->where(array(array('id', explode(',', $id)), array('module_id', (int) $index->module_id)))->toArray();
                        $id = array();
                        foreach ($query->execute() as $item) {
                            // ลบรูปภาพ
                            if (!empty($item['picture'])) {
                                @unlink(ROOT_PATH.DATA_FOLDER.'portfolio/'.$item['picture']);
                            }
                            $id[] = $item['id'];
                        }
                        if (!empty($id)) {
                            // ลบฐานข้อมูล
                            $model->db()->createQuery()->delete('portfolio', array(array('id', $id), array('module_id', (int) $index->module_id)))->execute();
                            // คืนค่า
                            $ret['location'] = 'reload';
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

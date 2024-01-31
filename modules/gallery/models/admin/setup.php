<?php
/**
 * @filesource modules/gallery/models/admin/setup.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Gallery\Admin\Setup;

use Gcms\Gcms;
use Gcms\Login;
use Kotchasan\File;
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
    protected $table = 'gallery_album A';

    /**
     * query หน้าเพจ เรียงลำดับตาม module,language
     *
     * @return array
     */
    public function getConfig()
    {
        $query = $this->db()->createQuery()
            ->select('G.image')
            ->from('gallery G')
            ->where(array(array('G.album_id', 'A.id'), array('G.module_id', 'A.module_id')))
            ->order('count')
            ->limit(1);
        return array(
            'select' => array(
                'A.id',
                'A.topic',
                array($query, 'image'),
                'A.count',
                'A.visited',
                'A.last_update',
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
                $index = \Index\Adminmodule\Model::getModuleWithConfig('gallery', $request->post('mid')->toInt());
                if ($index && Gcms::canConfig($login, $index, 'can_write') && preg_match('/^[0-9,]+$/', $id)) {
                    $module_id = (int) $index->module_id;
                    // Model
                    $model = new \Kotchasan\Model();
                    // ชื่อตาราง
                    $table_name = $model->getTableName('gallery');
                    if ($action === 'delete') {
                        $ids = explode(',', $id);
                        // ลบอัลบัม
                        $query = $model->db()->createQuery()
                            ->select('album_id', 'image')
                            ->from('gallery')
                            ->where(array(
                                array('album_id', $ids),
                                array('module_id', $module_id)
                            ))
                            ->toArray();
                        foreach ($query->execute() as $item) {
                            // ลบไดเรคทอรี่ของอัลบัม
                            File::removeDirectory(ROOT_PATH.DATA_FOLDER.'gallery/'.$item['album_id'].'/');
                        }
                        // ลบฐานข้อมูล
                        $model->db()->createQuery()->delete('gallery', array(array('album_id', $ids), array('module_id', $module_id)))->execute();
                        $model->db()->createQuery()->delete('gallery_album', array(array('id', $ids), array('module_id', $module_id)))->execute();
                        // คืนค่า
                        $ret['location'] = 'reload';
                    } elseif ($action === 'deletep') {
                        // ลบรูปภาพ
                        $query = $model->db()->createQuery()
                            ->select('id', 'album_id', 'image')
                            ->from('gallery')
                            ->where(array(
                                array('id', explode(',', $id)),
                                array('album_id', $request->post('aid')->toInt()),
                                array('module_id', $module_id)
                            ))
                            ->toArray();
                        $id = array();
                        foreach ($query->execute() as $item) {
                            $id[] = $item['id'];
                            // ลบรูปภาพ
                            @unlink(ROOT_PATH.DATA_FOLDER.'gallery/'.$item['album_id'].'/'.$item['image']);
                            @unlink(ROOT_PATH.DATA_FOLDER.'gallery/'.$item['album_id'].'/thumb_'.$item['image']);
                        }
                        // ลบข้อมูล
                        if (!empty($id)) {
                            $model->db()->delete($table_name, array(
                                array('id', $id),
                                array('module_id', $module_id)
                            ), 0);
                        }
                        // คืนค่า
                        $ret['location'] = 'reload';
                    } elseif ($action === 'sort') {
                        // เรียงลำดับรูป
                        $album_id = $request->post('aid')->toInt();
                        $count = 1;
                        foreach (explode(',', $id) as $id) {
                            // save
                            $model->db()->update($table_name, array(
                                array('album_id', $album_id),
                                array('id', $id)
                            ), array('count' => $count));
                            $count++;
                        }
                        // save
                        $ret['save'] = true;
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

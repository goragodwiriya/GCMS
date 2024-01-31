<?php
/**
 * @filesource modules/download/models/admin/category.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Download\Admin\Category;

use Gcms\Gcms;
use Gcms\Login;
use Kotchasan\ArrayTool;
use Kotchasan\Http\Request;
use Kotchasan\Language;

/**
 * อ่านข้อมูลหมวดหมู่ (Backend)
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Model extends \Kotchasan\Model
{
    /**
     * อ่านข้อมูลหมวดหมู่
     * สำหรับหน้าแสดงรายการหมวดหมู่
     *
     * @param int $module_id
     *
     * @return array คืนค่าแอเรย์ของ Object ไม่มีคืนค่าแอเรย์ว่าง
     */
    public static function all($module_id)
    {
        foreach (Language::installedLanguage() as $lng) {
            $default[$lng] = '';
        }
        $result = array();
        if (is_int($module_id) && $module_id > 0) {
            $model = new static;
            $query = $model->db()->createQuery()
                ->select('id', 'category_id', 'topic')
                ->from('category')
                ->where(array('module_id', $module_id))
                ->cacheOn()
                ->order('category_id');
            foreach ($query->toArray()->execute() as $item) {
                $tmp = ArrayTool::replace($default, @unserialize($item['topic']));
                unset($item['topic']);
                $result[] = ArrayTool::replace($item, $tmp);
            }
        }
        if (empty($result)) {
            $result[] = ArrayTool::replace(array('id' => 0, 'category_id' => 1), $default);
        }
        return $result;
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
                // อ่านข้อมูลโมดูล และ config
                $index = \Index\Adminmodule\Model::getModuleWithConfig('download', $request->post('module_id')->toInt());
                // สามารถตั้งค่าได้
                if (Gcms::canConfig($login, $index, 'can_config')) {
                    // ค่าที่ส่งมา
                    $save = array();
                    $category_exists = array();
                    foreach ($request->post('category_id')->toInt() as $key => $value) {
                        if (isset($category_exists[$value])) {
                            $ret['ret_category_id_'.$key] = Language::replace('This :name already exist', array(':name' => 'ID'));
                        } else {
                            $category_exists[$value] = $value;
                            $save[$key]['category_id'] = $value;
                            $save[$key]['module_id'] = $index->module_id;
                            $save[$key]['published'] = '1';
                        }
                    }
                    foreach (Language::installedLanguage() as $lng) {
                        foreach ($request->post($lng)->topic() as $key => $value) {
                            if ($value != '') {
                                $save[$key]['topic'][$lng] = $value;
                            }
                        }
                    }
                    if (empty($ret)) {
                        // ชื่อตาราง
                        $table_name = $this->getTableName('category');
                        // db
                        $db = $this->db();
                        // ลบข้อมูลเดิม
                        $db->delete($table_name, array('module_id', $index->module_id), 0);
                        // เพิ่มข้อมูลใหม่
                        foreach ($save as $item) {
                            if (isset($item['topic'])) {
                                $item['topic'] = serialize($item['topic']);
                                $db->insert($table_name, $item);
                            }
                        }
                        // คืนค่า
                        $ret['alert'] = Language::get('Saved successfully');
                        $ret['location'] = 'reload';
                        // เคลียร์
                        $request->removeToken();
                    }
                }
            }
        }
        if (empty($ret)) {
            $ret['alert'] = Language::get('Unable to complete the transaction');
        }
        // คืนค่า JSON
        echo json_encode($ret);
    }
}

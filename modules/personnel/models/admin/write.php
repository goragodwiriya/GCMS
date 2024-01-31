<?php
/**
 * @filesource modules/personnel/models/admin/write.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Personnel\Admin\Write;

use Gcms\Gcms;
use Gcms\Login;
use Kotchasan\ArrayTool;
use Kotchasan\Database\Sql;
use Kotchasan\File;
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
     * @param int  $module_id ของโมดูล
     * @param int  $id        ID
     * @param bool $new       true คืนค่า ID ถัดไป, false (default) คืนค่า $id ที่ส่งเข้ามา
     *
     * @return object|null คืนค่าข้อมูล object ไม่พบคืนค่า null
     */
    public static function get($module_id, $id, $new = false)
    {
        // model
        $model = new static;
        $query = $model->db()->createQuery();
        if (empty($id)) {
            // ใหม่ ตรวจสอบโมดูล
            if ($new) {
                $query->select(Sql::NEXT('id', $model->getTableName('personnel'), null, 'id'), 'M.id module_id', 'M.owner', 'M.module', 'M.config');
            } else {
                $query->select('0 id', 'M.id module_id', 'M.owner', 'M.module', 'M.config');
            }
            $query->from('modules M')
                ->where(array(
                    array('M.id', $module_id),
                    array('M.owner', 'personnel')
                ));
        } else {
            // แก้ไข ตรวจสอบรายการที่เลือก
            $query->select('A.*', 'M.owner', 'M.module', 'M.config')
                ->from('personnel A')
                ->join('modules M', 'INNER', array(array('M.id', 'A.module_id'), array('M.owner', 'personnel')))
                ->where(array('A.id', $id));
        }
        $result = $query->limit(1)->toArray()->execute();
        if (count($result) == 1) {
            $result = ArrayTool::unserialize($result[0]['config'], $result[0], empty($id));
            unset($result['config']);
            return (object) $result;
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
                // ค่าที่ส่งมา
                $save = array(
                    'name' => $request->post('name')->topic(),
                    'category_id' => $request->post('category_id')->toInt(),
                    'order' => $request->post('order')->toInt(),
                    'position' => $request->post('position')->topic(),
                    'detail' => $request->post('detail')->topic(),
                    'address' => $request->post('address')->topic(),
                    'phone' => $request->post('phone')->topic(),
                    'email' => $request->post('email')->url()
                );
                $id = $request->post('id')->toInt();
                // ตรวจสอบรายการที่เลือก
                $index = self::get($request->post('module_id')->toInt(), $id, true);
                if ($index && Gcms::canConfig($login, $index, 'can_write')) {
                    // name
                    if ($save['name'] == '') {
                        $ret['ret_name'] = 'Please fill in';
                    } else {
                        // อัปโหลดไฟล์
                        foreach ($request->getUploadedFiles() as $item => $file) {
                            /* @var $file \Kotchasan\Http\UploadedFile */
                            if ($file->hasUploadFile()) {
                                if (!File::makeDirectory(ROOT_PATH.DATA_FOLDER.'personnel/')) {
                                    // ไดเรคทอรี่ไม่สามารถสร้างได้
                                    $ret['ret_'.$item] = sprintf(Language::get('Directory %s cannot be created or is read-only.'), DATA_FOLDER.'personnel/');
                                } elseif (!$file->validFileExt(array('jpg', 'jpeg', 'png', 'gif'))) {
                                    // ชนิดของไฟล์ไม่ถูกต้อง
                                    $ret['ret_'.$item] = Language::get('The type of file is invalid');
                                } else {
                                    $save[$item] = $index->id.'.'.$file->getClientFileExt();
                                    try {
                                        $file->cropImage(array('jpg', 'jpeg', 'png', 'gif'), ROOT_PATH.DATA_FOLDER.'personnel/'.$save[$item], $index->image_width, $index->image_height);
                                    } catch (\Exception $exc) {
                                        // ไม่สามารถอัปโหลดได้
                                        $ret['ret_'.$item] = Language::get($exc->getMessage());
                                    }
                                }
                            } elseif ($file->hasError()) {
                                // ข้อผิดพลาดการอัปโหลด
                                $ret['ret_'.$item] = Language::get($file->getErrorMessage());
                            } elseif ($id == 0) {
                                // ใหม่ ต้องมีไฟล์
                                $ret['ret_'.$item] = Language::get('Please select file');
                            }
                        }
                        if (empty($ret)) {
                            if ($id == 0) {
                                // ใหม่
                                $save['module_id'] = $index->module_id;
                                $this->db()->insert($this->getTableName('personnel'), $save);
                            } else {
                                // แก้ไข
                                $this->db()->update($this->getTableName('personnel'), $id, $save);
                            }
                            // ส่งค่ากลับ
                            $ret['alert'] = Language::get('Saved successfully');
                            $ret['location'] = $request->getUri()->postBack('index.php', array('mid' => $index->module_id, 'module' => 'personnel-setup'));
                            // เคลียร์
                            $request->removeToken();
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

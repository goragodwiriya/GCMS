<?php
/**
 * @filesource modules/download/models/admin/write.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Download\Admin\Write;

use Gcms\Gcms;
use Gcms\Login;
use Kotchasan\ArrayTool;
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
     * @param int $module_id ของโมดูล
     * @param int $id        ID
     *
     * @return object|null คืนค่าข้อมูล object ไม่พบคืนค่า null
     */
    public static function get($module_id, $id)
    {
        // model
        $model = new static;
        $query = $model->db()->createQuery();
        if (empty($id)) {
            // ใหม่ ตรวจสอบโมดูล
            $query->select('0 id', 'M.id module_id', 'M.owner', 'M.module', 'M.config')
                ->from('modules M')
                ->where(array(
                    array('M.id', $module_id),
                    array('M.owner', 'download')
                ));
        } else {
            // แก้ไข ตรวจสอบรายการที่เลือก
            $query->select('A.*', 'M.owner', 'M.module', 'M.config')
                ->from('download A')
                ->join('modules M', 'INNER', array(array('M.id', 'A.module_id'), array('M.owner', 'download')))
                ->where(array('A.id', $id));
        }
        $result = $query->limit(1)->toArray()->execute();
        if (count($result) == 1) {
            $result = ArrayTool::unserialize($result[0]['config'], $result[0], empty($id));
            unset($result['config']);
            if (empty($id)) {
                $result['reciever'] = $result['can_download'];
            } else {
                $reciever = @unserialize($result['reciever']);
                $result['reciever'] = is_array($reciever) ? $reciever : array();
            }
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
                    'reciever' => $request->post('reciever', array())->toInt(),
                    'file' => $request->post('file')->topic(),
                    'detail' => $request->post('detail')->topic()
                );
                $id = $request->post('id')->toInt();
                // ตรวจสอบรายการที่เลือก
                $index = self::get($request->post('module_id')->toInt(), $id);
                if (!$index || !Gcms::canConfig($login, $index, 'can_upload')) {
                    // ไม่พบ หรือไม่สามารถอัปโหลดได้
                    $ret['alert'] = Language::get('Can not be performed this request. Because they do not find the information you need or you are not allowed');
                } elseif ($id > 0 && !($login['id'] == $index->member_id || Gcms::canConfig($login, $index, 'moderator'))) {
                    // แก้ไข ไม่ใช่เจ้าของหรือ moderator
                    $ret['alert'] = Language::get('Can not be performed this request. Because they do not find the information you need or you are not allowed');
                } else {
                    if (empty($save['reciever'])) {
                        // reciever
                        $ret['ret_reciever'] = Language::replace('Please select :name at least one item', array(':name' => Language::get('Recipient')));
                    } elseif ($save['detail'] == '') {
                        // detail
                        $ret['ret_detail'] = 'Please fill in';
                    } else {
                        // อัปโหลดไฟล์
                        foreach ($request->getUploadedFiles() as $item => $file) {
                            /* @var $file \Kotchasan\Http\UploadedFile */
                            if ($file->hasUploadFile()) {
                                if (!File::makeDirectory(ROOT_PATH.DATA_FOLDER.'download/')) {
                                    // ไดเรคทอรี่ไม่สามารถสร้างได้
                                    $ret['ret_'.$item] = sprintf(Language::get('Directory %s cannot be created or is read-only.'), DATA_FOLDER.'download/');
                                } elseif (!$file->validFileExt($index->file_typies)) {
                                    // ชนิดของไฟล์ไม่ถูกต้อง
                                    $ret['ret_'.$item] = Language::get('The type of file is invalid');
                                } elseif ($file->getSize() > $index->upload_size) {
                                    // ขนาดของไฟล์ใหญ่เกินไป
                                    $ret['ret_'.$item] = Language::get('The file size larger than the limit');
                                } else {
                                    $save['ext'] = $file->getClientFileExt();
                                    $file_name = str_replace('.'.$save['ext'], '', $file->getClientFilename());
                                    if ($file_name == '' && $save['name'] == '') {
                                        $ret['ret_name'] = 'this';
                                    } else {
                                        // อัปโหลด
                                        $save['file'] = DATA_FOLDER.'download/'.uniqid().'.'.$save['ext'];
                                        while (file_exists(ROOT_PATH.$save['file'])) {
                                            $save['file'] = DATA_FOLDER.'download/'.uniqid().'.'.$save['ext'];
                                        }
                                        try {
                                            $file->moveTo(ROOT_PATH.$save['file']);
                                            $save['size'] = $file->getSize();
                                            if ($save['name'] == '') {
                                                $save['name'] = $file_name;
                                            }
                                            if (!empty($index->file) && $save['file'] != $index->file) {
                                                @unlink(ROOT_PATH.$index->file);
                                            }
                                        } catch (\Exception $exc) {
                                            // ไม่สามารถอัปโหลดได้
                                            $ret['ret_'.$item] = Language::get($exc->getMessage());
                                        }
                                    }
                                }
                            } elseif ($file->hasError()) {
                                // ข้อผิดพลาดการอัปโหลด
                                $ret['ret_'.$item] = Language::get($file->getErrorMessage());
                            } elseif ($id == 0 && (empty($save['file']) || !is_file(ROOT_PATH.$save['file']))) {
                                // ใหม่ ต้องมีไฟล์
                                $ret['ret_'.$item] = Language::get('Please select file');
                            }
                        }
                    }
                    if (is_file(ROOT_PATH.$save['file'])) {
                        // อัปเดตขนาดของไฟล์
                        $save['size'] = filesize(ROOT_PATH.$save['file']);
                    }
                    if (empty($ret)) {
                        $save['last_update'] = time();
                        $save['reciever'] = serialize($save['reciever']);
                        if ($id == 0) {
                            // ใหม่
                            $save['module_id'] = $index->module_id;
                            $save['downloads'] = 0;
                            $save['member_id'] = $login['id'];
                            $this->db()->insert($this->getTableName('download'), $save);
                        } else {
                            // แก้ไข
                            $this->db()->update($this->getTableName('download'), $id, $save);
                        }
                        // ส่งค่ากลับ
                        $ret['alert'] = Language::get('Saved successfully');
                        $ret['location'] = $request->getUri()->postBack('index.php', array('module' => 'download-setup', 'mid' => $index->module_id));
                        // เคลียร์
                        $request->removeToken();
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

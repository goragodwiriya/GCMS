<?php
/**
 * @filesource modules/edocument/models/admin/write.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Edocument\Admin\Write;

use Gcms\Email;
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
                $query->select(Sql::NEXT('id', $model->getTableName('edocument'), null, 'id'), 'M.id module_id', 'M.owner', 'M.module', 'M.config');
            } else {
                $query->select('0 id', 'M.id module_id', 'M.owner', 'M.module', 'M.config');
            }
            $query->from('modules M')
                ->where(array(
                    array('M.id', $module_id),
                    array('M.owner', 'edocument')
                ));
        } else {
            // แก้ไข ตรวจสอบรายการที่เลือก
            $query->select('A.*', 'M.owner', 'M.module', 'M.config')
                ->from('edocument A')
                ->join('modules M', 'INNER', array(array('M.id', 'A.module_id'), array('M.owner', 'edocument')))
                ->where(array('A.id', $id));
        }
        $result = $query->limit(1)->toArray()->execute();
        if (count($result) == 1) {
            $result = ArrayTool::unserialize($result[0]['config'], $result[0], empty($id));
            unset($result['config']);
            if (empty($id) && $new) {
                $result['document_no'] = sprintf($result['format_no'], $result['id']);
                $result['id'] = 0;
            }
            if (empty($id)) {
                $result['reciever'] = array();
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
                    'document_no' => $request->post('document_no')->topic(),
                    'reciever' => $request->post('reciever', array())->toInt(),
                    'topic' => $request->post('topic')->topic(),
                    'detail' => $request->post('detail')->textarea()
                );
                $id = $request->post('id')->toInt();
                // ตรวจสอบรายการที่เลือก
                $index = self::get($request->post('module_id')->toInt(), $id);
                if (!$index || !Gcms::canConfig($login, $index, 'can_upload')) {
                    // ไม่พบ หรือไม่สามารถอัปโหลดได้
                    $ret['alert'] = Language::get('Can not be performed this request. Because they do not find the information you need or you are not allowed');
                } elseif ($id > 0 && !($login['id'] == $index->sender_id || Gcms::canConfig($login, $index, 'moderator'))) {
                    // แก้ไข ไม่ใช่เจ้าของหรือ moderator
                    $ret['alert'] = Language::get('Can not be performed this request. Because they do not find the information you need or you are not allowed');
                } else {
                    if ($save['document_no'] == '') {
                        // ไม่ได้กรอกเลขที่เอกสาร
                        $ret['ret_document_no'] = 'this';
                    } else {
                        // ค้นหาเลขที่เอกสารซ้ำ
                        $search = $this->db()->first($this->getTableName('edocument'), array('document_no', $save['document_no']));
                        if ($search && ($id == 0 || $id != $search->id)) {
                            $ret['ret_document_no'] = Language::replace('This :name already exist', array(':name' => Language::get('Document number')));
                        }
                    }
                    if (empty($ret)) {
                        if (empty($save['reciever'])) {
                            // reciever
                            $ret['ret_reciever'] = Language::replace('Please select :name at least one item', array(':name' => Language::get('Recipient')));
                        } elseif ($save['detail'] == '') {
                            // detail
                            $ret['ret_detail'] = 'this';
                        } else {
                            // อัปโหลดไฟล์
                            foreach ($request->getUploadedFiles() as $item => $file) {
                                /* @var $file \Kotchasan\Http\UploadedFile */
                                if ($file->hasUploadFile()) {
                                    $dir = ROOT_PATH.DATA_FOLDER.'edocument/';
                                    if (!File::makeDirectory($dir)) {
                                        // ไดเรคทอรี่ไม่สามารถสร้างได้
                                        $ret['ret_'.$item] = sprintf(Language::get('Directory %s cannot be created or is read-only.'), DATA_FOLDER.'edocument/');
                                    } elseif (!$file->validFileExt($index->file_typies)) {
                                        // ชนิดของไฟล์ไม่ถูกต้อง
                                        $ret['ret_'.$item] = Language::get('The type of file is invalid');
                                    } elseif ($file->getSize() > $index->upload_size) {
                                        // ขนาดของไฟล์ใหญ่เกินไป
                                        $ret['ret_'.$item] = Language::get('The file size larger than the limit');
                                    } else {
                                        $save['ext'] = $file->getClientFileExt();
                                        $file_name = str_replace('.'.$save['ext'], '', $file->getClientFilename());
                                        if ($file_name == '' && $save['topic'] == '') {
                                            $ret['ret_topic'] = 'this';
                                        } else {
                                            // อัปโหลด
                                            $save['file'] = substr(uniqid(), 0, 10).'.'.$save['ext'];
                                            while (file_exists($dir.$save['file'])) {
                                                $save['file'] = substr(uniqid(), 0, 10).'.'.$save['ext'];
                                            }
                                            try {
                                                $file->moveTo($dir.$save['file']);
                                                $save['size'] = $file->getSize();
                                                if ($save['topic'] == '') {
                                                    $save['topic'] = $file_name;
                                                }
                                                if (!empty($index->file) && $save['file'] != $index->file) {
                                                    @unlink($dir.$index->file);
                                                }
                                            } catch (\Exception $exc) {
                                                // ไม่สามารถอัปโหลดได้
                                                $ret['ret_'.$item] = Language::get($exc->getMessage());
                                            }
                                        }
                                    }
                                } elseif ($id == 0) {
                                    // ใหม่ ต้องมีไฟล์
                                    $ret['ret_'.$item] = Language::get('Please select file');
                                }
                            }
                        }
                        if (empty($ret)) {
                            $save['last_update'] = time();
                            $reciever = $save['reciever'];
                            $save['reciever'] = serialize($reciever);
                            if ($id == 0) {
                                // ใหม่
                                $save['module_id'] = $index->module_id;
                                $save['downloads'] = 0;
                                $save['sender_id'] = $login['id'];
                                $this->db()->insert($this->getTableName('edocument'), $save);
                            } else {
                                // แก้ไข
                                $this->db()->update($this->getTableName('edocument'), $id, $save);
                            }
                            if ($request->post('send_mail')->toInt() == 1) {
                                $query = $this->db()->createQuery()->select('name', 'email')->from('user')->where(array('status', $reciever));
                                foreach ($query->toArray()->execute() as $item) {
                                    // ส่งอีเมล
                                    $replace = array(
                                        '/%NAME%/' => $item['name'],
                                        '/%URL%/' => WEB_URL.'index.php?module='.$index->module
                                    );
                                    Email::send(1, 'edocument', $replace, $item['email']);
                                }
                                $ret['alert'] = Language::get('Save and email completed');
                            } else {
                                $ret['alert'] = Language::get('Saved successfully');
                            }
                            $ret['location'] = $request->getUri()->postBack('index.php', array('mid' => $index->module_id, 'module' => 'edocument-setup'));
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

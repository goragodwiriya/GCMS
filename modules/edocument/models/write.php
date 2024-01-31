<?php
/**
 * @filesource modules/edocument/models/write.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Edocument\Write;

use Gcms\Email;
use Gcms\Gcms;
use Gcms\Login;
use Kotchasan\ArrayTool;
use Kotchasan\Database\Sql;
use Kotchasan\File;
use Kotchasan\Http\Request;
use Kotchasan\Language;

/**
 * module=edocument-write
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Model extends \Kotchasan\Model
{
    /**
     * query ข้อมูลสำหรับการบันทึก
     *
     * @param int $module_id
     * @param int $id        สำหรับรายการใหม่, > สำหรับการแก้ไข
     *
     * @return JSON
     */
    public static function getForSave($module_id, $id)
    {
        // model
        $model = new static;
        $query = $model->db()->createQuery();
        if (empty($id)) {
            // ใหม่ ตรวจสอบโมดูล
            $query->select('M.id module_id', 'M.module', 'M.config', Sql::NEXT('id', $model->getTableName('edocument'), null, 'id'))
                ->from('modules M')
                ->where(array(array('M.id', $module_id), array('M.owner', 'edocument')));
        } else {
            // แก้ไข ตรวจสอบรายการที่เลือก
            $query->select('A.id', 'A.file', 'A.sender_id', 'M.id module_id', 'M.module', 'M.config')
                ->from('edocument A')
                ->join('modules M', 'INNER', array(array('M.id', $module_id), array('M.owner', 'edocument')))
                ->where(array('A.id', $id));
        }
        $result = $query->limit(1)->toArray()->execute();
        if (count($result) == 1) {
            $result = ArrayTool::unserialize($result[0]['config'], $result[0]);
            unset($result['config']);
            return (object) $result;
        }
        return null;
    }

    /**
     * อ่านข้อมูลรายการที่เลือก
     *
     * @param int    $id    ID ใหม่, > แก้ไข
     * @param object $index ข้อมูลที่ส่งมา
     *
     * @return object|null คืนค่าข้อมูล object ไม่พบคืนค่า null
     */
    public static function get($id, $index)
    {
        // login
        $login = Login::isMember();
        // model
        $model = new static;
        $query = $model->db()->createQuery();
        if ($id > 0) {
            // แก้ไข
            $search = $query->from('edocument')->where($id)->toArray()->first();
        } else {
            // ใหม่
            $search = $query->toArray()->first(Sql::NEXT('id', $model->getTableName('edocument'), null, 'id'));
        }
        if ($search) {
            $search['modules'] = array();
            foreach (Gcms::$module->findByOwner('edocument') as $item) {
                $search['modules'][$item->module_id] = $item->topic;
                if (empty($search['module_id']) || $search['module_id'] == $item->module_id) {
                    $search['module'] = $item;
                }
            }
            if (isset($search['module'])) {
                if ($id > 0) {
                    // แก้ไข
                    $reciever = @unserialize($search['reciever']);
                    $search['reciever'] = is_array($reciever) ? $reciever : array();
                } else {
                    // ใหม่
                    $search['module_id'] = $search['module']->module_id;
                    $search['reciever'] = array();
                    $search['document_no'] = sprintf($search['module']->format_no, $search['id']);
                    $search['id'] = 0;
                }
                $search['description'] = $index->description;
                $search['tab'] = $index->tab;
                return (object) $search;
            }
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
        // session, token, member
        if ($request->initSession() && $request->isSafe() && $login = Login::isMember()) {
            if ($login['email'] == 'demo' || !empty($login['social'])) {
                $ret['alert'] = Language::get('Unable to complete the transaction');
            } else {
                try {
                    // ค่าที่ส่งมา
                    $save = array(
                        'document_no' => $request->post('document_no')->topic(),
                        'reciever' => $request->post('reciever', array())->toInt(),
                        'topic' => $request->post('topic')->topic(),
                        'detail' => $request->post('detail')->textarea()
                    );
                    $id = $request->post('id')->toInt();
                    // ตรวจสอบรายการที่เลือก
                    $index = self::getForSave($request->post('module_id')->toInt(), $id);
                    if (!$index || !Gcms::canConfig($login, $index, 'can_upload')) {
                        // ไม่พบ หรือไม่สามารถอัปโหลดได้
                        $ret['alert'] = Language::get('Can not be performed this request. Because they do not find the information you need or you are not allowed');
                    } elseif ($id > 0 && !($login['id'] == $index->sender_id || Gcms::canConfig($login, $index, 'moderator'))) {
                        // แก้ไข ไม่ใช่เจ้าของหรือ moderator
                        $ret['alert'] = Language::get('Can not be performed this request. Because they do not find the information you need or you are not allowed');
                    } else {
                        // document_no
                        if ($save['document_no'] == '') {
                            $ret['ret_document_no'] = 'this';
                        } else {
                            // ค้นหาเลขที่เอกสารซ้ำ
                            $search = $this->db()->first($this->getTableName('edocument'), array('document_no', $save['document_no']));
                            if ($search && ($id == 0 || $id != $search->id)) {
                                $ret['ret_document_no'] = Language::replace('This :name already exist', array(':name' => Language::get('Document number')));
                            }
                        }
                        // reciever
                        if (empty($save['reciever'])) {
                            $ret['ret_reciever'] = Language::replace('Please select :name at least one item', array(':name' => Language::get('Reciever')));
                        }
                        // detail
                        if ($save['detail'] == '') {
                            $ret['ret_detail'] = 'this';
                        }
                        if (empty($ret)) {
                            // อัปโหลดไฟล์
                            foreach ($request->getUploadedFiles() as $item => $file) {
                                /* @var $file UploadedFile */
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
                            $save['module_id'] = $index->module_id;
                            if ($id == 0) {
                                // ใหม่
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
                            $ret['location'] = WEB_URL.'index.php?module=editprofile&tab=edocument';
                            // เคลียร์
                            $request->removeToken();
                        }
                    }
                } catch (\Kotchasan\InputItemException $e) {
                    $ret['alert'] = $e->getMessage();
                }
            }
        } else {
            $ret['alert'] = Language::get('Can not be performed this request. Because they do not find the information you need or you are not allowed');
        }
        // คืนค่าเป็น JSON
        echo json_encode($ret);
    }
}

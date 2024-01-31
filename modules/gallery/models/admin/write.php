<?php
/**
 * @filesource modules/gallery/models/admin/write.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Gallery\Admin\Write;

use Gcms\Gcms;
use Gcms\Login;
use Kotchasan\ArrayTool;
use Kotchasan\Database\Sql;
use Kotchasan\Date;
use Kotchasan\File;
use Kotchasan\Http\Request;
use Kotchasan\Language;

/**
 * บันทึก
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
     * @param bool $new       false (default) คืนค่า ID สำหรับรายการใหม่, true คืนค่า ID ถัดไปสำหรับรายการใหม่
     *
     * @return object|null คืนค่าข้อมูล object ไม่พบคืนค่า null
     */
    public static function get($module_id, $id, $new = false)
    {
        // model
        $model = new static;
        $query = $model->db()->createQuery();
        if (empty($id) && !empty($module_id)) {
            // ใหม่ ตรวจสอบโมดูล
            if ($new) {
                $query->select(Sql::NEXT('id', $model->getTableName('gallery_album'), null, 'id'), 'M.id module_id', 'M.owner', 'M.module', 'M.config');
            } else {
                $query->select('0 id', 'M.id module_id', 'M.owner', 'M.module', 'M.config');
            }
            $query->from('modules M')
                ->where(array(
                    array('M.id', $module_id),
                    array('M.owner', 'gallery')
                ));
        } else {
            // แก้ไข ตรวจสอบรายการที่เลือก
            $q1 = $model->db()->createQuery()
                ->select('G.image')
                ->from('gallery G')
                ->where(array(array('G.album_id', 'A.id'), array('G.module_id', 'A.module_id')))
                ->order('count')
                ->limit(1);
            $query->select('A.*', array($q1, 'image'), 'M.owner', 'M.module', 'M.config')
                ->from('gallery_album A')
                ->join('modules M', 'INNER', array(array('M.id', 'A.module_id'), array('M.owner', 'gallery')))
                ->where(array('A.id', $id));
        }
        $result = $query->limit(1)->toArray()->execute();
        if (count($result) == 1) {
            $result = ArrayTool::unserialize($result[0]['config'], $result[0], empty($id));
            unset($result['config']);
            if (empty($id)) {
                $result['topic'] = '';
                $result['detail'] = '';
                $result['last_update'] = time();
            }
            return (object) $result;
        }
        return null;
    }

    /**
     * query รูปภาพทั้งหมดของอัลบัม
     *
     * @param object $index
     *
     * @return array
     */
    public static function pictures($index)
    {
        // model
        $model = new static;
        return $model->db()->createQuery()
            ->select('id', 'image', 'count')
            ->from('gallery')
            ->where(array(
                array('album_id', (int) $index->id),
                array('module_id', (int) $index->module_id)
            ))
            ->order('count')
            ->execute();
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
                    'topic' => $request->post('topic')->topic(),
                    'detail' => $request->post('detail')->textarea(),
                    'last_update' => strtotime($request->post('last_update_date')->date().' '.$request->post('last_update_time')->date().':00')
                );
                $id = $request->post('id')->toInt();
                // ตรวจสอบรายการที่เลือก
                $index = self::get($request->post('module_id')->toInt(), $id, true);
                if ($index && Gcms::canConfig($login, $index, 'can_write')) {
                    // ตรวจสอบค่าที่ส่งมา
                    if ($save['topic'] == '') {
                        $ret['ret_topic'] = 'this';
                    } elseif ($save['detail'] == '') {
                        $ret['ret_detail'] = 'this';
                    } else {
                        // อัปโหลดไฟล์
                        foreach ($request->getUploadedFiles() as $item => $file) {
                            /* @var $file \Kotchasan\Http\UploadedFile */
                            if ($file->hasUploadFile()) {
                                $dir = ROOT_PATH.DATA_FOLDER.'gallery/'.$index->id.'/';
                                if (!File::makeDirectory(ROOT_PATH.DATA_FOLDER.'gallery/') || !File::makeDirectory($dir)) {
                                    // ไดเรคทอรี่ไม่สามารถสร้างได้
                                    $ret['ret_'.$item] = sprintf(Language::get('Directory %s cannot be created or is read-only.'), DATA_FOLDER.'gallery/');
                                } else {
                                    // อัปโหลด
                                    $image = '0.'.$file->getClientFileExt();
                                    try {
                                        // image
                                        $image = $file->resizeImage($index->img_typies, $dir, $image, $index->image_width);
                                        // thumb
                                        $file->cropImage($index->img_typies, $dir.'thumb_'.$image['name'], $index->icon_width, $index->icon_height);
                                    } catch (\Exception $exc) {
                                        // ไม่สามารถอัปโหลดได้
                                        $ret['ret_'.$item] = Language::get($exc->getMessage());
                                    }
                                }
                            } elseif ($file->hasError()) {
                                // ข้อผิดพลาดการอัปโหลด
                                $ret['ret_'.$item] = Language::get($file->getErrorMessage());
                            } elseif ($id == 0) {
                                // ใหม่ ต้องมีรูปภาพ
                                $ret['ret_'.$item] = Language::get('Please select a cover photo');
                            }
                        }
                    }
                    if (empty($ret)) {
                        if ($id == 0) {
                            // ใหม่
                            $save['id'] = $index->id;
                            $save['module_id'] = $index->module_id;
                            $save['count'] = 1;
                            $save['visited'] = 0;
                            $this->db()->insert($this->getTableName('gallery_album'), $save);
                            // ไปหน้าอัพโหลดรูปภาพ
                            $ret['location'] = WEB_URL.'index.php?module=gallery-upload&id='.$index->id;
                        } else {
                            // แก้ไข
                            $this->db()->update($this->getTableName('gallery_album'), $index->id, $save);
                            // กลับไปหน้ารายการอัลบัม
                            $ret['location'] = $request->getUri()->postBack('index.php', array('module' => 'gallery-setup', 'mid' => $index->module_id));
                        }
                        if (isset($image)) {
                            $table = $this->getTableName('gallery');
                            $this->db()->delete($table, array(
                                array('album_id', $index->id),
                                array('module_id', $index->module_id),
                                array('count', 0)
                            ), 0);
                            $this->db()->insert($table, array(
                                'album_id' => $index->id,
                                'module_id' => $index->module_id,
                                'image' => $image['name'],
                                'last_update' => time(),
                                'count' => 0
                            ));
                        }
                        // ส่งค่ากลับ
                        $ret['alert'] = Language::get('Saved successfully');
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

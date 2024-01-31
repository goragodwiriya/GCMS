<?php
/**
 * @filesource modules/gallery/models/admin/upload.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Gallery\Admin\Upload;

use Gcms\Gcms;
use Gcms\Login;
use Kotchasan\ArrayTool;
use Kotchasan\Database\Sql;
use Kotchasan\Http\Request;
use Kotchasan\Language;

/**
 * อัปโหลดไฟล์
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Model extends \Kotchasan\Model
{
    /**
     * อ่านอัลบัม
     *
     * @param int $id ID ของอัลบัม
     *
     * @return object|null คืนค่าข้อมูล object ไม่พบคืนค่า null
     */
    public static function get($id)
    {
        // model
        $model = new static;
        // ตรวจสอบรายการที่เลือก
        $result = $model->db()->createQuery()
            ->from('gallery_album A')
            ->join('modules M', 'INNER', array(array('M.id', 'A.module_id'), array('M.owner', 'gallery')))
            ->where(array('A.id', $id))
            ->toArray()
            ->first('A.id', Sql::NEXT('count', $model->getTableName('gallery'), array(array('module_id', 'A.module_id'), array('album_id', 'A.id')), 'count'), 'M.id module_id', 'M.owner', 'M.module', 'M.config');
        if ($result) {
            $result = ArrayTool::unserialize($result['config'], $result);
            unset($result['config']);
            return (object) $result;
        }
        return null;
    }

    /**
     * อัปโหลดรูปภาพ
     *
     * @param Request $request
     */
    public function submit(Request $request)
    {
        $ret = array();
        // referer, referer, can_config, ไม่ใช่สมาชิกตัวอย่าง
        if ($request->initSession() && $request->isReferer() && $login = Login::adminAccess()) {
            if (Login::notDemoMode($login)) {
                // ตรวจสอบรายการที่เลือก
                $index = self::get($request->post('albumId')->toInt());
                if ($index && Gcms::canConfig($login, $index, 'can_write')) {
                    // อัปโหลดไฟล์
                    foreach ($request->getUploadedFiles() as $item => $file) {
                        /* @var $file \Kotchasan\Http\UploadedFile */
                        if ($file->hasUploadFile()) {
                            if (!$file->validFileExt($index->img_typies)) {
                                // ชนิดของไฟล์ไม่ถูกต้อง
                                $ret['alert'] = Language::get('The type of file is invalid');
                            } else {
                                // อัปโหลด
                                $image = $index->count.'.'.$file->getClientFileExt();
                                try {
                                    $dir = ROOT_PATH.DATA_FOLDER.'gallery/'.$index->id.'/';
                                    $image = $file->resizeImage($index->img_typies, $dir, $image, $index->image_width);
                                    $file->cropImage($index->img_typies, $dir.'thumb_'.$image['name'], $index->icon_width, $index->icon_height);
                                    // save
                                    $save = array(
                                        'album_id' => $index->id,
                                        'module_id' => $index->module_id,
                                        'image' => $image['name'],
                                        'last_update' => time(),
                                        'count' => $index->count
                                    );
                                    $this->db()->insert($this->getTableName('gallery'), $save);
                                } catch (\Exception $exc) {
                                    // ไม่สามารถอัปโหลดได้
                                    $ret['alert'] = Language::get($exc->getMessage());
                                }
                            }
                        } elseif ($file->hasError()) {
                            // ข้อผิดพลาดการอัปโหลด
                            $ret['alert'] = Language::get($file->getErrorMessage());
                        }
                    }
                    $q1 = $this->db()->createQuery()->selectCount()->from('gallery G')->where(array(
                        array('G.album_id', 'A.id'),
                        array('G.module_id', 'A.module_id')
                    ));
                    $this->db()->createQuery()
                        ->update('gallery_album A')
                        ->set(array(
                            'last_update' => time(),
                            'count' => $q1
                        ))
                        ->where(array(
                            array('A.id', (int) $index->id),
                            array('A.module_id', (int) $index->module_id)
                        ))
                        ->execute();
                }
            }
        }
        // คืนค่าเป็น JSON
        if (!empty($ret)) {
            echo json_encode($ret);
        }
    }
}

<?php
/**
 * @filesource modules/board/models/admin/categorywrite.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Board\Admin\Categorywrite;

use Gcms\Gcms;
use Gcms\Login;
use Kotchasan\ArrayTool;
use Kotchasan\Database\Sql;
use Kotchasan\File;
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
     *
     * @param int $module_id
     * @param int $id
     *
     * @return object ถ้าไม่พบคืนค่า null
     */
    public static function get($module_id, $id)
    {
        if (is_int($module_id) && $module_id > 0) {
            $model = new static;
            if ($id == 0) {
                // ใหม่, ตรวจสอบโมดูลที่เรียก
                $select = [
                    '0 id',
                    'M.id module_id',
                    'M.module',
                    'M.config mconfig',
                    "'' topic",
                    "'' detail",
                    "'' icon",
                    '1 published',
                    Sql::NEXT('category_id', $model->getTableName('category'), ['module_id', 'M.id'], 'category_id')
                ];
                $index = $model->db()->createQuery()
                    ->from('modules M')
                    ->where([
                        ['M.id', $module_id],
                        ['M.owner', 'board']
                    ])
                    ->toArray()
                    ->first($select);
            } else {
                // แก้ไข ตรวจสอบโมดูลและหมวดที่เลือก
                $index = $model->db()->createQuery()
                    ->from('category C')
                    ->join('modules M', 'INNER', [
                        ['M.id', 'C.module_id'],
                        ['M.owner', 'board']
                    ])
                    ->where([
                        ['C.id', $id],
                        ['C.module_id', $module_id]
                    ])
                    ->toArray()
                    ->first('C.*', 'M.module', 'M.config mconfig');
            }
            if ($index) {
                // การเผยแพร่จากหมวด
                $published = $index['published'];
                // config จาก module
                $index = ArrayTool::unserialize($index['mconfig'], $index);
                unset($index['mconfig']);
                // config จากหมวด
                if (isset($index['config'])) {
                    $index = ArrayTool::unserialize($index['config'], $index);
                    unset($index['config']);
                }
                $index['published'] = $published;
                return (object) $index;
            }
        }
        return null;
    }

    /**
     * บันทึกหมวดหมู่
     *
     * @param Request $request
     */
    public function submit(Request $request)
    {
        $ret = [];
        // referer, token, can_config, ไม่ใช่สมาชิกตัวอย่าง
        if ($request->initSession() && $request->isSafe() && $login = Login::adminAccess()) {
            if (Login::notDemoMode($login)) {
                try {
                    // รับค่าจากการ POST
                    $save = [
                        'published' => 1,
                        'config' => [
                            'img_upload_type' => $request->post('img_upload_type', [])->toString(),
                            'img_upload_size' => $request->post('img_upload_size', [])->toInt(),
                            'img_law' => $request->post('img_law')->toBoolean(),
                            'can_post' => $request->post('can_post', [])->toInt(),
                            'can_reply' => $request->post('can_reply', [])->toInt(),
                            'can_view' => $request->post('can_view', [])->toInt(),
                            'moderator' => $request->post('moderator', [])->toInt(),
                        ],
                    ];
                    $id = $request->post('id')->toInt();
                    $module_id = $request->post('module_id')->toInt();
                    $category_id = $request->post('category_id')->toInt();
                    // ตาราง category
                    $table_category = $this->getTableName('category');
                    $q1 = $this->db()->createQuery()
                        ->select('id')
                        ->where([
                            ['category_id', $category_id],
                            ['module_id', 'M.id']
                        ])
                        ->from('category');
                    if ($id > 0) {
                        $select = [
                            'C.id',
                            'C.module_id',
                            'C.icon',
                            'C.config',
                            'M.config mconfig',
                            [$q1, 'cid']
                        ];
                        $index = $this->db()->createQuery()
                            ->from('category C')
                            ->join('modules M', 'INNER', ['M.id', 'C.module_id'])
                            ->where([
                                ['C.id', $id],
                                ['C.module_id', $module_id],
                                ['M.owner', 'board']
                            ])
                            ->toArray()
                            ->first($select);
                    } else {
                        // ใหม่, ตรวจสอบโมดูลที่เรียก
                        $select = [
                            'M.id module_id',
                            '"" icon',
                            'M.config mconfig',
                            Sql::NEXT('id', $table_category, null, 'id'),
                            [$q1, 'cid']
                        ];
                        $index = $this->db()->createQuery()
                            ->from('modules M')
                            ->where([
                                ['M.id', $module_id],
                                ['M.owner', 'board']
                            ])
                            ->toArray()
                            ->first($select);
                    }
                    if ($index === false) {
                        $ret['alert'] = Language::get('Sorry, Item not found It&#39;s may be deleted');
                    } else {
                        // config จาก module
                        $index = ArrayTool::unserialize($index['mconfig'], $index);
                        if (Gcms::canConfig($login, $index, 'can_config')) {
                            unset($index['mconfig']);
                            $topic = [];
                            foreach ($request->post('topic')->topic() as $key => $value) {
                                if ($value != '') {
                                    $topic[$key] = $value;
                                }
                            }
                            $detail = [];
                            foreach ($request->post('detail')->topic() as $key => $value) {
                                if ($value != '') {
                                    $detail[$key] = $value;
                                }
                            }
                            // ตรวจสอบค่าที่ส่งมา
                            if ($category_id == 0) {
                                $ret['ret_category_id'] = 'this';
                            } elseif ($index['cid'] > 0 && $index['cid'] != $index['id']) {
                                $ret['ret_category_id'] = Language::replace('This :name already exist', [':name' => Language::get('ID')]);
                            } elseif (empty($topic)) {
                                $ret['ret_topic_'.Language::name()] = 'Please fill in';
                            } elseif (empty($detail)) {
                                $ret['ret_detail_'.Language::name()] = 'Please fill in';
                            } else {
                                // อัปโหลดไฟล์
                                $icon = ArrayTool::unserialize($index['icon']);
                                foreach ($request->getUploadedFiles() as $item => $file) {
                                    /* @var $file \Kotchasan\Http\UploadedFile */
                                    if ($file->hasUploadFile()) {
                                        if (!File::makeDirectory(ROOT_PATH.DATA_FOLDER.'board/')) {
                                            // ไดเรคทอรี่ไม่สามารถสร้างได้
                                            $ret['ret_'.$item] = sprintf(Language::get('Directory %s cannot be created or is read-only.'), DATA_FOLDER.'board/');
                                        } elseif (!$file->validFileExt(['jpg', 'jpeg', 'gif', 'png'])) {
                                            $ret['ret_'.$item] = Language::get('The type of file is invalid');
                                        } else {
                                            $old_icon = empty($icon[$item]) ? '' : $icon[$item];
                                            $icon[$item] = "cat-$item-$index[id].".$file->getClientFileExt();
                                            try {
                                                $file->moveTo(ROOT_PATH.DATA_FOLDER.'board/'.$icon[$item]);
                                                if ($old_icon != $icon[$item]) {
                                                    @unlink(ROOT_PATH.DATA_FOLDER.'board/'.$old_icon);
                                                }
                                            } catch (\Exception $exc) {
                                                // ไม่สามารถอัปโหลดได้
                                                $ret['ret_'.$item] = Language::get($exc->getMessage());
                                            }
                                        }
                                    } elseif ($file->hasError()) {
                                        // ข้อผิดพลาดการอัปโหลด
                                        $ret['ret_'.$item] = Language::get($file->getErrorMessage());
                                    }
                                }
                                if (!empty($icon)) {
                                    $save['icon'] = Gcms::array2Ser($icon);
                                }
                            }
                            if (empty($ret)) {
                                $save['category_id'] = $category_id;
                                $save['topic'] = Gcms::array2Ser($topic);
                                $save['detail'] = Gcms::array2Ser($detail);
                                $save['config']['can_post'][] = 1;
                                $save['config']['can_reply'][] = 1;
                                $save['config']['can_view'][] = 1;
                                $save['config']['moderator'][] = 1;
                                $save['config'] = serialize($save['config']);
                                if ($id == 0) {
                                    // ใหม่
                                    $save['module_id'] = $index['module_id'];
                                    $this->db()->insert($table_category, $save);
                                } else {
                                    // แก้ไข
                                    $this->db()->update($table_category, $id, $save);
                                }
                                // อัปเดตจำนวนเรื่อง และ ความคิดเห็น ในหมวด
                                \Board\Admin\Write\Model::updateCategories((int) $index['module_id']);
                                // ส่งค่ากลับ
                                $ret['alert'] = Language::get('Saved successfully');
                                $ret['location'] = $request->getUri()->postBack('index.php', ['id' => $index['module_id'], 'module' => 'board-category']);
                                // เคลียร์
                                $request->removeToken();
                            }
                        }
                    }
                } catch (\Kotchasan\InputItemException $e) {
                    $ret['alert'] = $e->getMessage();
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

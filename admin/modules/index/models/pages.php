<?php
/**
 * @filesource modules/index/models/pages.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Index\Pages;

use Gcms\Login;
use Kotchasan\Http\Request;
use Kotchasan\Language;

/**
 * โมเดลสำหรับแสดงรายการหน้าเว็บไซต์ที่สร้างแล้ว (pages.php)
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
    protected $table = 'modules M';

    /**
     * query หน้าเพจ เรียงลำดับตาม module,language
     *
     * @return array
     */
    public function getConfig()
    {
        return [
            'select' => [
                'I.id',
                'M.id AS module_id',
                'D.topic',
                'I.published',
                'I.language',
                'M.module',
                'M.owner',
                'I.last_update',
                'I.visited'
            ],
            'join' => [
                [
                    'INNER',
                    'Index\Detail\Model',
                    [
                        ['D.module_id', 'M.id']
                    ]
                ],
                [
                    'INNER',
                    'Index\Index\Model',
                    [
                        ['I.id', 'D.id'],
                        ['I.module_id', 'M.id'],
                        ['I.language', 'D.language']
                    ]
                ]
            ]
        ];
    }

    /**
     * คืนค่ารายการหน้าเว็บเพจที่สร้างจากโมดูล Index
     *
     * @return array
     */
    public static function toSelect()
    {
        $query = \Kotchasan\Model::createQuery()
            ->select('M.module', 'D.topic')
            ->from('modules M')
            ->join('index_detail D', 'INNER', ['D.module_id', 'M.id'])
            ->join('index I', 'INNER', [['I.id', 'D.id'], ['I.module_id', 'M.id'], ['I.language', 'D.language']])
            ->where(['M.owner', 'index'])
            ->cacheOn();
        $result = [];
        foreach ($query->execute() as $item) {
            $result[$item->module] = $item->topic;
        }
        return $result;
    }

    /**
     * รับค่าจาก action ของ table
     *
     * @param Request $request
     */
    public function action(Request $request)
    {
        $ret = [];
        // session, referer, member, ไม่ใช่สมาชิกตัวอย่าง
        if ($request->initSession() && $request->isReferer() && $login = Login::adminAccess()) {
            if (Login::notDemoMode($login) && Login::checkPermission($login, 'can_config')) {
                // ค่าที่ส่งมา
                $action = $request->post('action')->toString();
                $id = $request->post('id')->toInt();
                // Model
                $model = new \Kotchasan\Model();
                if ($action === 'published') {
                    // เผยแพร่
                    $index = $model->db()->first($model->getTableName('index'), $id);
                    if ($index) {
                        $published = $index->published == 1 ? 0 : 1;
                        $model->db()->update($model->getTableName('index'), $index->id, ['published' => $published]);
                        // คืนค่า
                        $ret['elem'] = 'published_'.$index->id;
                        $lng = Language::get('PUBLISHEDS');
                        $ret['title'] = $lng[$published];
                        $ret['class'] = 'icon-published'.$published;
                    }
                } elseif ($action === 'delete') {
                    // ลบโมดูลและหน้าเพจ ไม่ลบข้อมูลของโมดูล
                    $query = $model->db()->createQuery()
                        ->select('id', 'module_id')
                        ->from('index')
                        ->where([
                            ['index', 1],
                            ['module_id', $model->db()->createQuery()->select('module_id')->from('index')->where(['id', $id])]
                        ]);
                    $count = 0;
                    foreach ($query->execute() as $field) {
                        ++$count;
                        if ($field->id == $id) {
                            $model->db()->delete($model->getTableName('index'), $id);
                            $model->db()->delete($model->getTableName('index_detail'), $id);
                        }
                    }
                    // ลบโมดูล ถ้าไม่มีรายการในภาษาอื่น
                    if ($count < 2) {
                        $model->db()->delete($model->getTableName('modules'), $field->module_id);
                    }
                    // คืนค่า
                    $ret['delete_id'] = $request->post('src')->toString().'_'.$id;
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

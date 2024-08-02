<?php
/**
 * @filesource modules/product/models/admin/setup.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Product\Admin\Setup;

use Gcms\Gcms;
use Gcms\Login;
use Kotchasan\Http\Request;
use Kotchasan\Language;

/**
 * ตาราง product
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
    protected $table = 'product P';

    /**
     * query หน้าเพจ เรียงลำดับตาม module,language
     *
     * @return array
     */
    public function getConfig()
    {
        return [
            'select' => [
                'P.id',
                'P.product_no',
                'D.topic',
                'P.published',
                'P.last_update',
                'P.visited',
                'P.module_id'
            ],
            'join' => [
                [
                    'LEFT',
                    'Product\Admin\Detail\Model',
                    [
                        ['D.id', 'P.id'],
                        ['D.language', [Language::name(), '']]
                    ]
                ]
            ]
        ];
    }

    /**
     * รับค่าจาก action ของ table (setup.php)
     *
     * @param Request $request
     */
    public static function action(Request $request)
    {
        $ret = [];
        // session, referer, member, ไม่ใช่สมาชิกตัวอย่าง
        if ($request->initSession() && $request->isReferer() && $login = Login::adminAccess()) {
            if (Login::notDemoMode($login)) {
                // รับค่าจากการ POST
                $id = $request->post('id')->toString();
                $action = $request->post('action')->toString();
                // อ่านข้อมูลโมดูล และ config
                $index = \Index\Adminmodule\Model::getModuleWithConfig('product', $request->post('mid')->toInt());
                if ($index && Gcms::canConfig($login, $index, 'can_write')) {
                    // Model
                    $model = new \Kotchasan\Model();
                    if ($action === 'published') {
                        // สถานะการเผยแพร่
                        $table = $model->getTableName('product');
                        $search = $model->db()->first($table, [['id', (int) $id], ['module_id', (int) $index->module_id]]);
                        if ($search) {
                            $published = $search->published == 1 ? 0 : 1;
                            $model->db()->update($table, $search->id, ['published' => $published]);
                            // คืนค่า
                            $ret['elem'] = 'published_'.$search->id;
                            $lng = Language::get('PUBLISHEDS');
                            $ret['title'] = $lng[$published];
                            $ret['class'] = 'icon-published'.$published;
                        }
                    } elseif ($action === 'delete' && preg_match('/^[0-9,]+$/', $id)) {
                        $id = explode(',', $id);
                        // ตรวจสอบรายการ เพื่อลบรูปภาพ
                        $query = $model->db()->createQuery()->select('id', 'picture')->from('product')->where([['id', $id], ['module_id', (int) $index->module_id]])->toArray();
                        foreach ($query->execute() as $item) {
                            // ลบรูปภาพ
                            @unlink(ROOT_PATH.DATA_FOLDER.'product/thumb_'.$item['picture']);
                            @unlink(ROOT_PATH.DATA_FOLDER.'product/'.$item['picture']);
                        }
                        // ลบฐานข้อมูล
                        $model->db()->createQuery()->delete('product', [['id', $id], ['module_id', (int) $index->module_id]])->execute();
                        $model->db()->createQuery()->delete('product_detail', ['id', $id])->execute();
                        $model->db()->createQuery()->delete('product_price', ['id', $id])->execute();
                        // คืนค่า
                        $ret['alert'] = Language::get('Deleted successfully');
                        $ret['location'] = 'reload';
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

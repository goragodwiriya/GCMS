<?php
/**
 * @filesource modules/document/models/admin/setup.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Document\Admin\Setup;

use Gcms\Gcms;
use Gcms\Login;
use Kotchasan\Database\Sql;
use Kotchasan\Http\Request;
use Kotchasan\Language;

/**
 * โมเดลสำหรับแสดงรายการบทความ (setup.php)
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
    protected $table = 'index P';

    /**
     * query หน้าเพจ เรียงลำดับตาม module,language
     *
     * @return array
     */
    public function getConfig()
    {
        return array(
            'select' => array(
                'P.id',
                'D.topic',
                'P.picture',
                'P.can_reply',
                'P.published',
                'P.show_news',
                'P.category_id',
                Sql::create('(CASE WHEN ISNULL(U.`id`) THEN P.`email` WHEN U.`displayname`=\'\' THEN U.`email` ELSE U.`displayname` END) AS `writer`'),
                'P.create_date',
                'P.last_update',
                'P.member_id',
                'P.visited',
                'U.status',
                'P.module_id',
                'P.index',
                'D.language',
                'D.detail'
            ),
            'join' => array(
                array(
                    'INNER',
                    'Index\Detail\Model',
                    array(
                        array('D.id', 'P.id'),
                        array('D.module_id', 'P.module_id')
                    )
                ),
                array(
                    'LEFT',
                    'Index\User\Model',
                    array(
                        array('U.id', 'P.member_id')
                    )
                )
            )
        );
    }

    /**
     * รับค่าจาก action ของ table (setup.php)
     *
     * @param Request $request
     */
    public static function action(Request $request)
    {
        $ret = array();
        // session, referer, member, ไม่ใช่สมาชิกตัวอย่าง
        if ($request->initSession() && $request->isReferer() && $login = Login::adminAccess()) {
            if (Login::notDemoMode($login)) {
                // รับค่าจากการ POST
                $id = $request->post('id')->toString();
                $action = $request->post('action')->toString();
                // อ่านข้อมูลโมดูล และ config
                $index = \Index\Adminmodule\Model::getModuleWithConfig('document', $request->post('mid')->toInt());
                if ($index && Gcms::canConfig($login, $index, 'can_write')) {
                    // Model
                    $model = new \Kotchasan\Model();
                    if ($action === 'published') {
                        // สถานะการเผยแพร่
                        $table = $model->getTableName('index');
                        $search = $model->db()->first($table, array(array('id', (int) $id), array('module_id', (int) $index->module_id)));
                        if ($search) {
                            $published = $search->published == 1 ? 0 : 1;
                            $model->db()->update($table, $search->id, array('published' => $published));
                            // คืนค่า
                            $ret['elem'] = 'published_'.$search->id;
                            $lng = Language::get('PUBLISHEDS');
                            $ret['title'] = $lng[$published];
                            $ret['class'] = 'icon-published'.$published;
                        }
                    } elseif ($action === 'delete' && preg_match('/^[0-9,]+$/', $id)) {
                        // ลบรายการที่เลือก
                        $query = $model->db()->createQuery()->select('id', 'picture')->from('index')->where(array(array('id', explode(',', $id)), array('module_id', (int) $index->module_id)))->toArray();
                        $id = array();
                        foreach ($query->execute() as $item) {
                            // ลบรูปภาพ
                            if (!empty($item['picture'])) {
                                @unlink(ROOT_PATH.DATA_FOLDER.'document/'.$item['picture']);
                            }
                            $id[] = $item['id'];
                        }
                        if (!empty($id)) {
                            // ลบฐานข้อมูล
                            $model->db()->createQuery()->delete('index', array(array('id', $id), array('module_id', (int) $index->module_id)))->execute();
                            $model->db()->createQuery()->delete('index_detail', array(array('id', $id), array('module_id', (int) $index->module_id)))->execute();
                            $model->db()->createQuery()->delete('comment', array(array('index_id', $id), array('module_id', (int) $index->module_id)))->execute();
                            // อัปเดตจำนวนเรื่อง และ ความคิดเห็น ในหมวด
                            \Document\Admin\Write\Model::updateCategories((int) $index->module_id);
                            // คืนค่า
                            $ret['location'] = 'reload';
                        }
                    } elseif ($action === 'can_reply') {
                        // การแสดงความคิดเห็น
                        $table = $model->getTableName('index');
                        $search = $model->db()->first($table, array(array('id', (int) $id), array('module_id', (int) $index->module_id)));
                        if ($search) {
                            $can_reply = $search->can_reply == 1 ? 0 : 1;
                            $model->db()->update($table, $search->id, array('can_reply' => $can_reply));
                            // คืนค่า
                            $ret['elem'] = 'can_reply_'.$search->id;
                            $lng = Language::get('REPLIES');
                            $ret['title'] = $lng[$can_reply];
                            $ret['class'] = 'icon-reply reply'.$can_reply;
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

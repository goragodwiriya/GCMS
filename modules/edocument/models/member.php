<?php
/**
 * @filesource modules/edocument/models/member.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Edocument\Member;

use Gcms\Login;
use Kotchasan\Http\Request;
use Kotchasan\Language;

/**
 * module=member&tab=edocument
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
    protected $table = 'edocument A';

    public function getConfig()
    {
        return array(
            'join' => array(
                array(
                    'INNER',
                    'Index\Modules\Model',
                    array(
                        array('M.id', 'A.module_id')
                    )
                )
            )
        );
    }

    /**
     * รับค่าจาก action ของ table
     *
     * @param Request $request
     */
    public static function action(Request $request)
    {
        $ret = array();
        // referer, session, member
        if ($request->initSession() && $request->isReferer() && $login = Login::isMember()) {
            if ($login['email'] == 'demo' || !empty($login['social'])) {
                $ret['alert'] = Language::get('Unable to complete the transaction');
            } else {
                if ($request->post('action')->toString() == 'delete') {
                    // Model
                    $model = new \Kotchasan\Model();
                    $search = $model->db()->createQuery()
                        ->from('edocument')
                        ->where(array(
                            array('id', $request->post('id')->toInt()),
                            array('sender_id', $login['id'])
                        ))
                        ->toArray()
                        ->first('id', 'file');
                    if ($search) {
                        // ลบไฟล์
                        @unlink(ROOT_PATH.$search['file']);
                        // ลบข้อมูล
                        $model->db()->delete($model->getTableName('edocument'), $search['id']);
                        $model->db()->delete($model->getTableName('edocument_download'), array('document_id', $search['id']), 0);
                        // ลบแถวตาราง
                        $ret['remove'] = 'datatable_'.$search['id'];
                    }
                }
            }
        } else {
            $ret['alert'] = Language::get('Unable to complete the transaction');
        }
        if (!empty($ret)) {
            // คืนค่าเป็น JSON
            echo json_encode($ret);
        }
    }
}

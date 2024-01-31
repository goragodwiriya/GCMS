<?php
/**
 * @filesource modules/index/models/user.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Index\User;

/**
 * ตาราง user
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
    protected $table = 'user U';

    /**
     * อ่านข้อมูลสมาชิกจาก ID
     *
     * @param int $id
     *
     * @return object|bool ข้อมูลสมาชิก ไม่พบคืนค่า false
     */
    public static function getUserById($id)
    {
        $model = new \Kotchasan\Model();
        return $model->db()->createQuery()->from('user')->where($id)->first();
    }

    /**
     * อ่านข้อมูลสมาชิกจาก activatecode
     *
     * @param string $id
     *
     * @return object|bool ข้อมูลสมาชิก ไม่พบคืนค่า false
     */
    public static function getUserByActivateCode($id)
    {
        $model = new \Kotchasan\Model();
        return $model->db()->createQuery()->from('user')->where(array('activatecode', $id))->first();
    }

    /**
     * Activate สมาชิก
     *
     * @param array $user ข้อมูลสมาชิก
     */
    public static function activateUser($user)
    {
        $model = new \Kotchasan\Model();
        $model->db()->update($model->getTableName('user'), $user->id, array('activatecode' => ''));
    }
}

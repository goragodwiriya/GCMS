<?php
/**
 * @filesource modules/index/models/autocomplete.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Index\Autocomplete;

use Gcms\Login;
use Kotchasan\Http\Request;

/**
 * ค้นหาสมาชิก สำหรับ autocomplete
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Model extends \Kotchasan\Model
{
    /**
     * ค้นหาสมาชิก สำหรับ autocomplete
     * คืนค่าเป็น JSON
     *
     * @param Request $request
     */
    public function findUser(Request $request)
    {
        if ($request->initSession() && $request->isReferer() && Login::isMember()) {
            $search = $request->post('name')->topic();
            if ($search != '') {
                $where = array();
                $select = array('id', 'name');
                $order = array();
                foreach (explode(',', $request->post('from', 'name')->filter('a-z,')) as $item) {
                    if ($item == 'name') {
                        $where[] = array('name', 'LIKE', "%$search%");
                        $order[] = 'name';
                    }
                    if ($item == 'email') {
                        $where[] = array('email', 'LIKE', "%$search%");
                        $order[] = 'email';
                    }
                    if ($item == 'phone') {
                        $where[] = array('phone1', 'LIKE', "$search%");
                        $select[] = 'phone1';
                        $order[] = 'phone1';
                    }
                    if ($item == 'idcard') {
                        $where[] = array('idcard', 'LIKE', "%$search%");
                        $order[] = 'idcard';
                    }
                }
                $result = array();
                if (!empty($where)) {
                    $query = $this->db()->createQuery()
                        ->select($select)
                        ->from('user')
                        ->where($where, 'OR')
                        ->order($order)
                        ->limit($request->post('count', 10)->toInt())
                        ->toArray();
                    foreach ($query->execute() as $item) {
                        $datas = array();
                        foreach ($item as $key => $value) {
                            if ($key != 'id') {
                                $value = trim($value);
                                if ($value != '') {
                                    $datas[] = $value;
                                }
                            }
                        }
                        if (!empty($datas)) {
                            $result[$item['id']] = array(
                                'id' => $item['id'],
                                'name' => implode(', ', $datas)
                            );
                        }
                    }
                }
                // คืนค่า JSON
                echo json_encode($result);
            }
        }
    }
}

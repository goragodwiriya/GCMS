<?php
/**
 * @filesource modules/index/models/counter.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Index\Counter;

use Gcms\Login;
use Kotchasan\Database\Sql;
use Kotchasan\Http\Request;

/**
 * ข้อมูล Counter และ Useronline
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Model extends \Kotchasan\Model
{
    /**
     * Initial Counter & Useronline
     *
     * @param Request $request
     *
     * @return type
     */
    public static function init(Request $request)
    {
        if (defined('MAIN_INIT')) {
            // Model
            $model = new static;
            // Database
            $db = $model->db();
            // ตาราง
            $useronline = $model->getTableName('useronline');
            $logs = $model->getTableName('logs');
            // วันนี้
            $y = (int) date('Y');
            $m = (int) date('m');
            $d = (int) date('d');
            // ไฟล์สำหรับ counter
            $counter_file = ROOT_PATH.DATA_FOLDER.'counter.log';
            // ตรวจสอบวันใหม่
            if (is_file($counter_file)) {
                $c = (int) file_get_contents($counter_file);
            } else {
                $c = 0;
            }
            if ($d != $c) {
                $f = @fopen($counter_file, 'wb');
                if ($f) {
                    fwrite($f, date('d-m-Y H:i:s'));
                    fclose($f);
                }
                if ($d < $c) {
                    // วันที่ 1 หรือวันแรกของเดือน ลบข้อมูลของปีก่อนๆ
                    $db->delete($logs, array(array(Sql::MONTH('time'), $m), array(Sql::YEAR('time'), '!=', $y)), 0);
                }
                // clear useronline
                $db->emptyTable($useronline);
                // clear visited_today
                $db->updateAll($model->getTableName('index'), array('visited_today' => 0));
            }
            // ip ปัจจุบัน
            $counter_ip = $request->getClientIp();
            // session ปัจจุบัน
            $session_id = session_id();
            // access log
            $db->insert($logs, array(
                'time' => date('Y-m-d H:i:s'),
                'ip' => $counter_ip,
                'session_id' => $session_id,
                'referer' => rawurldecode($request->server('HTTP_REFERER', '')),
                'user_agent' => $request->server('HTTP_USER_AGENT'),
                'url' => rawurldecode($request->server('REQUEST_URI', ''))
            ));
            // วันนี้
            $counter_day = date('Y-m-d');
            // อ่าน useronline
            $q2 = $db->createQuery()
                ->selectCount()
                ->from('useronline');
            // อ่าน counter รายการล่าสุด
            $my_counter = $db->createQuery()
                ->from('counter C')
                ->order('C.id DESC')
                ->toArray()
                ->first('C.*', array($q2, 'useronline'));
            if (empty($my_counter)) {
                $my_counter = array(
                    'date' => $counter_day,
                    'counter' => 0,
                    'visited' => 0,
                    'pages_view' => 0
                );
                // ข้อมูลใหม่
                $new = true;
                $user_online = 1;
            } elseif ($my_counter['date'] != $counter_day) {
                // ข้อมูลใหม่ ถ้าวันที่ไม่ตรงกัน
                $new = true;
                $user_online = $my_counter['useronline'];
                $my_counter['pages_view'] = 0;
                $my_counter['visited'] = 0;
            } else {
                $new = false;
                $user_online = $my_counter['useronline'];
            }
            ++$my_counter['pages_view'];
            $my_counter['time'] = time();
            $my_counter['date'] = $counter_day;
            unset($my_counter['useronline']);
            // ตรวจสอบ ว่าเคยเยี่ยมชมหรือไม่
            if ($new || $request->cookie('counter_date')->toInt() != $d) {
                // เข้ามาครั้งแรกในวันนี้, บันทึก counter 1 วัน
                setcookie('counter_date', $d, time() + 2592000, '/', HOST, HTTPS, true);
                // ยังไม่เคยเยี่ยมชมในวันนี้
                ++$my_counter['visited'];
                ++$my_counter['counter'];
            }
            // counter
            if ($new) {
                unset($my_counter['id']);
                $db->insert($model->getTableName('counter'), $my_counter);
            } else {
                $db->update($model->getTableName('counter'), $my_counter['id'], $my_counter);
            }
            // เวลาหมดอายุ useronline (2 นาที)
            $validtime = $my_counter['time'] - 120;
            // ลบคนที่หมดเวลาและตัวเอง
            $db->delete($useronline, array(array('time', '<', $validtime), array('session', $session_id)), 0, 'OR');
            // ตัวเอง
            $login = Login::isMember();
            // save useronline
            $db->insert($useronline, array(
                'time' => $my_counter['time'],
                'session' => $session_id,
                'ip' => $counter_ip,
                'member_id' => $login ? $login['id'] : 0
            ));
            $fmt = '%0'.self::$cfg->counter_digit.'d';
            return (object) array(
                'new_day' => $new,
                'counter' => sprintf($fmt, $my_counter['counter']),
                'counter_today' => sprintf($fmt, $my_counter['visited']),
                'pages_view' => sprintf($fmt, $my_counter['pages_view']),
                'useronline' => sprintf($fmt, $user_online)
            );
        }
    }
}

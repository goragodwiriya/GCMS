<?php
/**
 * @filesource modules/index/models/install.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Index\Install;

use Gcms\Gcms;
use Kotchasan\Text;

/**
 * ติดตั้งโมดูลและเมนู
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Model extends \Kotchasan\Model
{
    /**
     * ฟังก์ชั่น ติดตั้ง โมดูลและ เมนู
     *
     * @param object $db
     * @param string $owner
     * @param string $module
     * @param string $title
     * @param string $detail
     * @param bool   $published
     * @param string $menupos
     * @param string $menu
     *
     * @return int คืนค่า ID ของโมดูลที่ติดตั้ง
     */
    public static function installing($db, $owner, $module, $title, $detail, $published, $menupos = '', $menu = '')
    {
        if (preg_match('/^[a-z]+$/', $owner) && preg_match('/^[a-z0-9]+$/', $module)) {
            if (is_file(ROOT_PATH.'modules/'.$owner.'/models/admin/settings.php')) {
                include_once ROOT_PATH.'modules/'.$owner.'/models/admin/settings.php';
                $className = ucfirst($owner).'\Admin\Settings\Model';
                if (method_exists($className, 'defaultSettings')) {
                    $config = $className::defaultSettings();
                }
            }
            $module_id = $db->insert($_SESSION['prefix'].'_modules', array(
                'owner' => $owner,
                'module' => $module,
                'config' => empty($config) ? '' : serialize($config)
            ));
            // เพิ่มหน้าเว็บสำหรับโมดูล
            $index_id = self::insertIndex($db, $module_id, $published, $title, $detail, time());
            if ($menupos != '' && $menu != '') {
                self::installMenu($db, $menupos, $menu, '', '', $published, $index_id);
            }
            return $module_id;
        }
    }

    /**
     * เพิ่มหน้าเพจ
     *
     * @param object $db
     * @param int    $module_id   ID ของโมดูล
     * @param int    $published   เผยแพร่
     * @param string $title
     * @param string $detail
     * @param int    $create_date
     * @param int    $visited
     * @param int    $index
     * @param string $keywords
     * @param string $description
     * @param int    $member_id   ID ของสมาชิก
     * @param int    $can_reply   1 แสดงความคิดเห็นได้
     * @param int    $category_id
     *
     * @return int คืนค่า ID ของ index
     */
    public static function insertIndex($db, $module_id, $published, $title, $detail, $create_date, $visited = 0, $index = 1, $keywords = '', $description = '', $member_id = 0, $can_reply = 0, $category_id = 0, $picture = '')
    {
        $title = htmlspecialchars(strip_tags(stripslashes(html_entity_decode($title))));
        $detail = stripslashes($detail);
        $index_id = $db->insert($_SESSION['prefix'].'_index', array(
            'module_id' => $module_id,
            'index' => $index,
            'category_id' => $category_id,
            'published' => $published,
            'language' => '',
            'member_id' => $member_id,
            'create_date' => $create_date,
            'last_update' => $create_date,
            'visited' => $visited,
            'published_date' => '2017-01-01',
            'alias' => $index == 0 ? Gcms::aliasName($title) : '',
            'can_reply' => $can_reply
        ));
        if ($picture != '' && self::copy($picture, DATA_FOLDER.'document/picture-'.$module_id.'-'.$index_id.'.jpg')) {
            if (@getimagesize(ROOT_PATH.DATA_FOLDER.'document/picture-'.$module_id.'-'.$index_id.'.jpg') === false) {
                unlink(ROOT_PATH.DATA_FOLDER.'document/picture-'.$module_id.'-'.$index_id.'.jpg');
            } else {
                $db->update($_SESSION['prefix'].'_index', $index_id, array(
                    'picture' => 'picture-'.$module_id.'-'.$index_id.'.jpg'
                ));
            }
        }
        $db->insert($_SESSION['prefix'].'_index_detail', array(
            'id' => $index_id,
            'module_id' => $module_id,
            'language' => '',
            'topic' => $title,
            'keywords' => $keywords == '' ? Text::oneLine($title) : $keywords,
            'detail' => $detail,
            'description' => $description == '' ? Text::oneLine(strip_tags($detail), 255) : $description,
            'relate' => $title
        ));
        return $index_id;
    }

    /**
     * ฟังก์ชั่น ติดตั้งเมนูเปล่าๆ หรือลิงค์ไปยัง URL
     *
     * @param object $db
     * @param string $menupos     MAINMENU SIDEMENU BOTTOMMENU
     * @param string $menu        ชื่อเมนู
     * @param string $menu_url    URL ของเมนู
     * @param string $menu_target _blank หรือค่าว่าง
     * @param bool   $published   เผยแพร่
     * @param int    $index_id    ID ของโมดูลที่ต้องการ (index)
     */
    public static function installMenu($db, $menupos, $menu, $menu_url, $menu_target, $published, $index_id)
    {
        $menupos = strtoupper($menupos);
        if ($menupos != 'MAINMENU' && $menupos != 'SIDEMENU') {
            $menupos = 'BOTTOMMENU';
        }
        $db->insert($_SESSION['prefix'].'_menus', array(
            'index_id' => $index_id,
            'parent' => $menupos,
            'level' => 0,
            'menu_text' => $menu,
            'menu_tooltip' => $menu,
            'menu_url' => $menu_url,
            'menu_target' => $menu_target == '' ? '' : '_blank',
            'published' => $published
        ));
    }

    /**
     * อัปเดตจำนวนกระทู้และความคิดเห็นในหมวดหมู่
     *
     * @param object $db
     * @param int    $module_id ID ของโมดูล
     */
    public static function updateBoardCategories($db, $module_id)
    {
        $sql1 = 'SELECT COUNT(*) FROM `'.$_SESSION['prefix'].'_board_q` WHERE `category_id`=C.`category_id` AND `module_id`=C.`module_id`';
        $sql2 = 'SELECT `id` FROM `'.$_SESSION['prefix'].'_board_q` WHERE `category_id`=C.`category_id` AND `module_id`=C.`module_id`';
        $sql2 = 'SELECT COUNT(*) FROM `'.$_SESSION['prefix']."_board_r` WHERE `index_id` IN ($sql2) AND `module_id`=C.`module_id`";
        $db->query('UPDATE `'.$_SESSION['prefix']."_category` AS C SET C.`c1`=($sql1),C.`c2`=($sql2) WHERE C.`module_id`=".(int) $module_id);
    }

    /**
     * อัปเดตจำนวนบทความและความคิดเห็นในหมวดหมู่
     *
     * @param object $db
     * @param int    $module_id ID ของโมดูล
     */
    public static function updateCategories($db, $module_id)
    {
        $sql1 = 'SELECT COUNT(*) FROM `'.$_SESSION['prefix']."_index` WHERE `category_id`=C.`category_id` AND `module_id`=C.`module_id` AND `index`='0'";
        $sql2 = 'SELECT `id` FROM `'.$_SESSION['prefix']."_index` WHERE `category_id`=C.`category_id` AND `module_id`=C.`module_id` AND `index`='0'";
        $sql3 = 'SELECT COUNT(*) FROM `'.$_SESSION['prefix']."_comment` WHERE `index_id` IN ($sql2) AND `module_id`=C.`module_id`";
        $db->query('UPDATE `'.$_SESSION['prefix']."_category` AS C SET C.`c1`=($sql1),C.`c2`=($sql3) WHERE C.`module_id`=".(int) $module_id);
    }

    /**
     * อัปเดตจำนวนความคิดเห็น
     *
     * @param object $db
     * @param string $q  ตารางคำถาม
     * @param string $r  ตารางความคิดเห็น
     */
    public static function updateComments($db, $q, $r)
    {
        $db->query("UPDATE `$q` AS C SET C.`comments` = (SELECT COUNT(*) FROM `$r` WHERE `index_id` = C.`id` GROUP BY `index_id`)");
    }

    /**
     * สำเนาไฟล์แบบ remote
     *
     * @param string $src_url  URL ไฟล์ต้นฉบับ
     * @param string $dst_file ไฟล์ปลายทางนับแต่ root
     *
     * @return bool
     */
    public static function copy($src_url, $dst_file)
    {
        $content = @file_get_contents($src_url);
        if (!empty($content)) {
            $f = @fopen(ROOT_PATH.$dst_file, 'wb');
            if ($f) {
                fwrite($f, $content);
                fclose($f);
                return true;
            }
        }
        return false;
    }
}

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
     * ฟังก์ชั่น คิดตั้ง โมดูลและ เมนู
     * ถ้ามีโมดูลติดตั้งแล้ว คืนค่า ID ของโมดูล
     *
     * @param string $owner   โฟลเดอร์ของโมดูล
     * @param string $module  ชื่อโมดูล
     * @param string $title   (optional) ข้อความไตเติลบาร์ของโมดูล
     * @param string $menupos (optional) ตำแหน่งของเมนู (MAINMENU,SIDEMENU,BOTTOMMENU)
     * @param string $menu    (optional) ข้อความเมนู
     *
     * @return int คืนค่า ID ของโมดูลที่ติดตั้ง, -1 ติดตั้งแล้ว, มีข้อผิดพลาด
     */
    public static function installing($owner, $module, $title, $menupos = '', $menu = '')
    {
        if (preg_match('/^[a-z]+$/', $owner) && preg_match('/^[a-z]+$/', $module)) {
            // model
            $model = new static;
            $db = $model->db();
            // ตรวจสอบโมดูลที่ติดตั้งแล้ว
            $search = $db->createQuery()->from('modules')->where(array('module', $module))->first('id');
            if (!$search) {
                $className = ucfirst($owner).'\Admin\Settings\Model';
                if (class_exists($className) && method_exists($className, 'defaultSettings')) {
                    $config = $className::defaultSettings();
                }
                $id = $db->insert($model->getTableName('modules'), array(
                    'owner' => $owner,
                    'module' => $module,
                    'config' => empty($config) ? '' : serialize($config)
                ));
                $mktime = time();
                $index = $db->insert($model->getTableName('index'), array(
                    'module_id' => $id,
                    'index' => 1,
                    'published' => 1,
                    'language' => '',
                    'member_id' => 0,
                    'create_date' => $mktime,
                    'last_update' => $mktime,
                    'visited' => 0
                ));
                $db->insert($model->getTableName('index_detail'), array(
                    'module_id' => $id,
                    'id' => $index,
                    'topic' => $title,
                    'language' => ''
                ));
                if ($menupos != '' && $menu != '') {
                    $db->insert($model->getTableName('menus'), array(
                        'index_id' => $index,
                        'parent' => $menupos,
                        'level' => 0,
                        'menu_text' => $menu,
                        'menu_tooltip' => $title
                    ));
                }
                return $id;
            } else {
                return -1;
            }
        }
        return 0;
    }

    /**
     * บันทึกไฟล์ settings/database.php
     *
     * @param array $tables รายการตารางที่ต้องการอัปเดต (แทนที่ข้อมูลเดิม)
     *
     * @return bool คืนค่า true ถ้าสำเร็จ
     */
    public static function updateTables($tables)
    {
        // โหลด database
        $database = \Kotchasan\Config::load(ROOT_PATH.'settings/database.php');
        // อัปเดต tables
        foreach ($tables as $key => $value) {
            $database->tables[$key] = $value;
        }
        // save database

        return \Kotchasan\Config::save($database, ROOT_PATH.'settings/database.php');
    }

    /**
     * execute ไฟล์ sql.php
     *
     * @param string $sql_file ชื่อไฟล์รวม path
     *
     * @return string
     */
    public static function execute($sql_file)
    {
        // model
        $model = new static;
        $db = $model->db();
        // ผลลัพท์
        $content = array();
        // query จากไฟล์
        foreach (file($sql_file) as $value) {
            $sql = str_replace(array('{prefix}', '{WEBMASTER}', '{WEBURL}', '\r', '\n'), array($model->getSetting('prefix'), self::$cfg->noreply_email, WEB_URL, "\r", "\n"), trim($value));
            if ($sql != '') {
                if (preg_match('/^<\?.*\?>$/', $sql)) {
                    // php code
                } elseif (preg_match('/^define\([\'"]([A-Z_]+)[\'"](.*)\);$/', $sql, $match)) {
                    // define
                } elseif (preg_match('/DROP[\s]+TABLE[\s]+(IF[\s]+EXISTS[\s]+)?`?([a-z0-9_]+)`?/iu', $sql, $match)) {
                    $ret = $db->query($sql);
                    $content[] = '<li class="'.($ret === false ? 'incorrect' : 'correct')."\">DROP TABLE <b>$match[2]</b> ...</li>";
                } elseif (preg_match('/CREATE[\s]+TABLE[\s]+(IF[\s]+NOT[\s]+EXISTS[\s]+)?`?([a-z0-9_]+)`?/iu', $sql, $match)) {
                    $ret = $db->query($sql);
                    $content[] = '<li class="'.($ret === false ? 'incorrect' : 'correct')."\">CREATE TABLE <b>$match[2]</b> ...</li>";
                } elseif (preg_match('/ALTER[\s]+TABLE[\s]+`?([a-z0-9_]+)`?[\s]+ADD[\s]+`?([a-z0-9_]+)`?(.*)/iu', $sql, $match)) {
                    // add column
                    $sql = "SELECT * FROM `information_schema`.`columns` WHERE `table_schema`='".$model->getSetting('dbname')."' AND `table_name`='$match[1]' AND `column_name`='$match[2]'";
                    $search = $db->customQuery($sql);
                    if (count($search) == 1) {
                        $sql = "ALTER TABLE `$match[1]` DROP COLUMN `$match[2]`";
                        $db->query($sql);
                    }
                    $ret = $db->query($match[0]);
                    if (count($search) == 1) {
                        $content[] = '<li class="'.($ret === false ? 'incorrect' : 'correct')."\">REPLACE COLUMN <b>$match[2]</b> to TABLE <b>$match[1]</b></li>";
                    } else {
                        $content[] = '<li class="'.($ret === false ? 'incorrect' : 'correct')."\">ADD COLUMN <b>$match[2]</b> to TABLE <b>$match[1]</b></li>";
                    }
                } elseif (preg_match('/INSERT[\s]+INTO[\s]+`?([a-z0-9_]+)`?(.*)/iu', $sql, $match)) {
                    $ret = $db->query($sql);
                    if (isset($q) && $q != $match[1]) {
                        $q = $match[1];
                        $content[] = '<li class="'.($ret === false ? 'incorrect' : 'correct')."\">INSERT INTO <b>$match[1]</b> ...</li>";
                    }
                } else {
                    $db->query($sql);
                }
            }
        }
        return implode('', $content);
    }
}

<?php
/**
 * @filesource modules/index/models/upgrade.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Index\Upgrade;

/**
 * อัปเกรด
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Model extends \Kotchasan\KBase
{
    /**
     * ตรวจสอบว่ามีตารางหรือไม่
     *
     * @param object $db
     * @param string   $table_name
     *
     * @return bool
     */
    public static function tableExists($db, $table_name)
    {
        try {
            $db->connection()->query("SELECT 1 FROM `$table_name` LIMIT 1");
        } catch (\PDOException $e) {
            return false;
        }
        return true;
    }

    /**
     * ตรวจสอบฟิลด์
     *
     * @param object $db
     * @param string   $table_name
     * @param string   $field
     *
     * @return bool
     */
    public static function fieldExists($db, $table_name, $field)
    {
        $result = $db->customQuery("SHOW COLUMNS FROM `$table_name` LIKE '$field'");
        return empty($result) ? false : true;
    }

    /**
     * ตรวจสอบว่ามี $index ในตารางหรือไม่
     *
     * @param string $table_name
     * @param string $index
     *
     * @return bool คืนค่า true ถ้ามี คืนค่า false ถ้าไม่มี
     */
    public static function indexExists($db, $table_name, $index)
    {
        $result = $db->customQuery("SELECT * FROM information_schema.statistics WHERE table_schema='".$_SESSION['cfg']['db_name']."' AND table_name = '$table_name' AND column_name = '$index'");
        return empty($result) ? false : true;
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
}

<?php
/**
 * @filesource modules/index/models/upgrade1350.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Index\Upgrade1350;

/**
 * อัปเกรด
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Model extends \Index\Upgrade\Model
{
    /**
     * อัปเกรดเป็นเวอร์ชั่น 13.5.0
     *
     * @return object
     */
    public static function upgrade($db)
    {
        $content = array();
        // logs table
        $table = $_SESSION['prefix'].'_logs';
        if (!self::tableExists($db, $table)) {
            $db->query("DROP TABLE IF EXISTS `$table`");
            $db->query("CREATE TABLE `$table` ( `time` datetime NOT NULL, `ip` varchar(32) COLLATE utf8_unicode_ci NOT NULL, `session_id` varchar(32) COLLATE utf8_unicode_ci NOT NULL, `referer` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL, `user_agent` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL, `url` TEXT COLLATE utf8_unicode_ci DEFAULT NULL ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci");
            $content[] = '<li class="correct">Created table <b>'.$table.'</b> complete...</li>';
        } elseif (!\Index\Upgrade\Model::fieldExists($db, $table, 'url')) {
            $db->query("ALTER TABLE `$table` ADD `url` TEXT COLLATE utf8_unicode_ci DEFAULT NULL");
            $content[] = '<li class="correct">Updated database <b>'.$table.'</b> complete...</li>';
        }
        // update database user
        $table = $_SESSION['prefix'].'_user';
        if (!\Index\Upgrade\Model::fieldExists($db, $table, 'social')) {
            $db->query("ALTER TABLE `$table` ADD `social` TINYINT NOT NULL  AFTER `fb`");
            $db->query("UPDATE `$table` SET `social`=(CASE WHEN `fb`='0' THEN 0 ELSE 1 END)");
            $db->query("ALTER TABLE `$table` DROP `fb`");
        }
        $db->query("ALTER TABLE `$table` CHANGE `password` `password` VARCHAR(50) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL");
        if (!\Index\Upgrade\Model::fieldExists($db, $table, 'token')) {
            $db->query("ALTER TABLE `$table` ADD `token` VARCHAR(50) NULL  AFTER `password`");
        }
        $content[] = '<li class="correct">Updated database <b>'.$table.'</b> complete...</li>';
        // index table
        $table = $_SESSION['prefix'].'_index';
        if (!self::fieldExists($db, $table, 'page')) {
            $db->query("ALTER TABLE `$table` ADD `page` VARCHAR(20) NOT NULL DEFAULT ''");
            $content[] = '<li class="correct">Updated database <b>'.$table.'</b> complete...</li>';
        }
        $content[] = '<li class="correct">Upgrade to Version <b>13.5.0</b> complete.</li>';
        return (object) array(
            'content' => implode('', $content),
            'version' => '13.5.0',
        );
    }
}

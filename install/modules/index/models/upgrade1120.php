<?php
/**
 * @filesource modules/index/views/upgrade1120.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Index\Upgrade1120;

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
     * อัปเกรดจากเวอร์ชั่น 11.0.0
     *
     * @return object
     */
    public static function upgrade($db)
    {
        $content = array();
        // install database language
        $db->query("DROP TABLE IF EXISTS `$_SESSION[prefix]_language`;");
        $db->query("CREATE TABLE `$_SESSION[prefix]_language` (`id` int(11) NOT NULL auto_increment,`key` text collate utf8_unicode_ci NOT NULL,`ja` text collate utf8_unicode_ci,`th` text collate utf8_unicode_ci,`en` text collate utf8_unicode_ci,`owner` varchar(20) collate utf8_unicode_ci NOT NULL,`type` varchar(5) collate utf8_unicode_ci NOT NULL,`js` tinyint(1) NOT NULL,PRIMARY KEY (`id`)) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;");
        // class Index\Languages\Model
        include '../admin/modules/index/models/languages.php';
        // import language
        $dir = ROOT_PATH.'language/';
        if (is_dir($dir)) {
            // ตาราง language
            $language_table = $_SESSION['prefix'].'_language';
            $f = opendir($dir);
            while (false !== ($text = readdir($f))) {
                if (preg_match('/([a-z]{2,2})\.(php|js)/', $text, $match)) {
                    if ($match[2] == 'php') {
                        \Index\Languages\Model::importPHP($db, $language_table, $match[1], $dir.$text);
                    } else {
                        \Index\Languages\Model::importJS($db, $language_table, $match[1], $dir.$text);
                    }
                }
            }
            closedir($f);
        }
        $content[] = '<li class="correct">Created and Imported database <b>'.$_SESSION['prefix'].'_language</b> complete...</li>';
        // อัปเกรด useronline
        $table = $_SESSION['prefix'].'_useronline';
        if (self::fieldExists($db, $table, 'id')) {
            $f = $db->query("ALTER TABLE `$table` DROP `id`");
        }
        if (self::fieldExists($db, $table, 'icon')) {
            $f = $db->query("ALTER TABLE `$table` DROP `icon`");
        }
        $content[] = '<li class="correct">Update database <b>'.$table.'</b> complete...</li>';
        // update database index
        $table = $_SESSION['prefix'].'_index';
        $db->query("ALTER TABLE `$table` CHANGE `language` `language` VARCHAR( 2 ) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL DEFAULT '';");
        $db->query("ALTER TABLE `$table` CHANGE `visited` `visited` INT( 11 ) NOT NULL DEFAULT 0;");
        $db->query("ALTER TABLE `$table` CHANGE `visited_today` `visited_today` INT( 11 ) NOT NULL DEFAULT 0;");
        $db->query("ALTER TABLE `$table` CHANGE `comments` `comments` SMALLINT( 3 ) NOT NULL DEFAULT 0;");
        $db->query("ALTER TABLE `$table` CHANGE `comment_id` `comment_id` INT( 11 ) NOT NULL DEFAULT 0;");
        $db->query("ALTER TABLE `$table` CHANGE `commentator_id` `commentator_id` INT( 11 ) NOT NULL DEFAULT 0;");
        $content[] = '<li class="correct">Updated database <b>'.$table.'</b> complete...</li>';
        // add FULLTEXT
        $db->query('ALTER TABLE `'.$_SESSION['prefix'].'_index_detail` ADD FULLTEXT (`topic`);');
        $db->query('ALTER TABLE `'.$_SESSION['prefix'].'_index_detail` ADD FULLTEXT (`detail`);');
        $db->query('ALTER TABLE `'.$_SESSION['prefix'].'_comment` ADD FULLTEXT (`detail`);');
        $db->query('ALTER TABLE `'.$_SESSION['prefix'].'_board_q` ADD FULLTEXT (`topic`);');
        $db->query('ALTER TABLE `'.$_SESSION['prefix'].'_board_q` ADD FULLTEXT (`detail`);');
        $db->query('ALTER TABLE `'.$_SESSION['prefix'].'_board_r` ADD FULLTEXT (`detail`);');
        $content[] = '<li class="correct">Updated database for <b>FULLTEXT SEARCH</b> complete...</li>';
        if (\Index\Upgrade\Model::tableExists($db, $_SESSION['prefix'].'_eventcalendar')) {
            $db->query("ALTER TABLE `$_SESSION[prefix]_eventcalendar` CHANGE `color` `color` VARCHAR( 11 ) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL;");
            $content[] = '<li class="correct">Updated database <b>'.$_SESSION['prefix'].'_eventcalendar</b> complete...</li>';
        }
        // update database download
        $table = $_SESSION['prefix'].'_download';
        if (\Index\Upgrade\Model::tableExists($db, $table) && !self::fieldExists($db, $table, 'reciever')) {
            $db->query("ALTER TABLE `$table` ADD `reciever` TEXT CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL;");
            $content[] = '<li class="correct">Updated database <b>'.$table.'</b> complete...</li>';
        }
        // update database.php
        $f = \Index\Upgrade\Model::updateTables(array('language' => 'language'));
        $content[] = '<li class="'.($f ? 'correct' : 'incorrect').'">Update file <b>database.php</b> ...</li>';
        $content[] = '<li class="correct">Upgrade to Version <b>11.2.0</b> complete.</li>';
        return (object) array(
            'content' => implode('', $content),
            'version' => '11.2.0'
        );
    }
}

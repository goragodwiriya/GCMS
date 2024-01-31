<?php
/**
 * @filesource modules/index/models/upgrade910.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Index\Upgrade910;

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
     * อัปเกรดจากเวอร์ชั่น 9.1.0
     *
     * @return object
     */
    public static function upgrade($db)
    {
        $content = array();
        // update database user
        $table = $_SESSION['prefix'].'_'.$_SESSION['tables']['user'];
        if (!self::fieldExists($db, $table, 'ban')) {
            $db->query("ALTER TABLE `$table` ADD `ban` INT( 11 ) NOT NULL;");
        }
        if (!self::fieldExists($db, $table, 'active')) {
            $db->query("ALTER TABLE `$table` ADD `active` TINYINT(1) NOT NULL DEFAULT '0';");
        }
        if (!self::fieldExists($db, $table, 'permission')) {
            $db->query("ALTER TABLE `$table` ADD `permission` TEXT COLLATE utf8_unicode_ci NULL;");
            $db->query("UPDATE `$table` SET `permission`='can_config',`active`=1 WHERE `status`=1;");
        }
        if (self::fieldExists($db, $table, 'admin_access')) {
            $db->query("UPDATE `$table` SET `active`=IF(`admin_access`='1',1,0)");
            $db->query("ALTER TABLE `$table` DROP `admin_access`;");
        }
        if (self::fieldExists($db, $table, 'subscrib')) {
            $db->query("ALTER TABLE `$table` DROP `subscrib`;");
        }
        if (self::fieldExists($db, $table, 'invite_id')) {
            $db->query("ALTER TABLE `$table` DROP `invite_id`;");
        }
        if (!self::fieldExists($db, $table, 'name')) {
            $db->query("ALTER TABLE `$table` ADD `name` VARCHAR(150) NOT NULL AFTER `password`");
            $db->query("UPDATE `$table` SET `name`=TRIM(CONCAT_WS(' ', `pname`, `fname`, `lname`))");
        }
        if (!self::fieldExists($db, $table, 'salt')) {
            $db->query("ALTER TABLE `$table` ADD `salt` VARCHAR(150) NOT NULL AFTER `email`");
            $db->query("UPDATE `$table` SET `salt`=`email`");
        }
        $content[] = '<li class="correct">Update database <b>'.$table.'</b> complete...</li>';
        if (isset($_SESSION['tables']['eventcalendar'])) {
            // อัปเกรด eventcalendar
            $table = $_SESSION['prefix'].'_'.$_SESSION['tables']['eventcalendar'];
            if (self::tableExists($db, $table) && !self::fieldExists($db, $table, 'end_date')) {
                $f = $db->query($sql = "ALTER TABLE `$table` ADD `end_date` DATETIME NOT NULL AFTER `begin_date`;");
                $f = $db->query($sql = "ALTER TABLE `$table` CHANGE `create_date` `create_date` DATETIME NOT NULL;");
                $content[] = '<li class="'.($f ? 'correct' : 'incorrect').'">Update database <b>'.$table.'</b> complete...</li>';
            }
        }
        // download
        $table = $_SESSION['prefix'].'_'.$_SESSION['tables']['download'];
        if (!self::fieldExists($db, $table, 'reciever')) {
            $db->query("ALTER TABLE `$table` ADD `reciever` TEXT NULL AFTER `downloads`;");
            $db->query("UPDATE `$table` SET `reciever`='".serialize(array_keys(self::$cfg->member_status))."';");
            $content[] = '<li class="correct">Update database <b>'.$table.'</b> complete...</li>';
        }
        // index
        $table = $_SESSION['prefix'].'_'.$_SESSION['tables']['index'];
        if (!self::fieldExists($db, $table, 'visited_today')) {
            $db->query("ALTER TABLE `$table` ADD `visited_today` INT(11) NOT NULL AFTER `visited`;");
        }
        if (!self::fieldExists($db, $table, 'show_news')) {
            $db->query("ALTER TABLE `$table` ADD `show_news` TEXT NULL AFTER `can_reply`;");
        }
        $db->query("ALTER TABLE `$table` CHANGE `id` `id` INT(11) NOT NULL;");
        $db->query("ALTER TABLE `$table` DROP PRIMARY KEY;");
        $db->query("ALTER TABLE `$table` ADD PRIMARY KEY (`id`, `module_id`);");
        $db->query("ALTER TABLE `$table` CHANGE `id` `id` INT(11) NOT NULL AUTO_INCREMENT;");
        $content[] = '<li class="correct">Update database <b>'.$table.'</b> complete...</li>';
        // index_detail
        $table = $_SESSION['prefix'].'_'.$_SESSION['tables']['index_detail'];
        $db->query("ALTER TABLE `$table` CHANGE `topic` `topic` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL");
        $db->query("ALTER TABLE `$table` CHANGE `description` `description` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL");
        $db->query("ALTER TABLE `$table` CHANGE `keywords` `keywords` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL");
        $db->query("ALTER TABLE `$table` CHANGE `relate` `relate` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL");
        $db->query("ALTER TABLE `$table` DROP PRIMARY KEY;");
        $db->query("ALTER TABLE `$table` ADD PRIMARY KEY (`id`, `module_id`, `language`);");
        if (self::indexExists($db, $table, 'topic')) {
            $db->query("ALTER TABLE `$table` DROP INDEX `topic`;");
        }
        $db->query("ALTER TABLE `$table` ADD FULLTEXT KEY `topic` (`topic`);");
        if (self::indexExists($db, $table, 'detail')) {
            $db->query("ALTER TABLE `$table` DROP INDEX `detail`;");
        }
        $db->query("ALTER TABLE `$table` ADD FULLTEXT KEY `detail` (`detail`);");
        $content[] = '<li class="correct">Update database <b>'.$table.'</b> complete...</li>';
        // menus
        $table = $_SESSION['prefix'].'_'.$_SESSION['tables']['menus'];
        if (!self::fieldExists($db, $table, 'published')) {
            $db->query("ALTER TABLE `$table` CHANGE `published` `published` ENUM('0','1','2','3') CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL DEFAULT '1';");
            $content[] = '<li class="correct">Update database <b>'.$table.'</b> complete...</li>';
        }
        // modules
        $table = $_SESSION['prefix'].'_'.$_SESSION['tables']['modules'];
        foreach ($db->customQuery('SELECT `id`,`config`,`owner` FROM `'.$table.'` WHERE `owner`!="index"') as $item) {
            $className = ucfirst($item->owner).'\Admin\Settings\Model';
            if (class_exists($className) && method_exists($className, 'defaultSettings')) {
                $config = $className::defaultSettings();
                if ($item->owner == 'document' || $item->owner == 'board') {
                    // document, board
                    $_config = self::r2config($item->config);
                    foreach ($config as $key => $value) {
                        if (isset($_config[$key])) {
                            if (is_array($value) && !is_array($_config[$key])) {
                                $config[$key] = explode(',', $_config[$key]);
                            } else {
                                $config[$key] = $_config[$key];
                            }
                        }
                    }
                } else {
                    foreach ($config as $key => $value) {
                        if (isset($_SESSION['cfg'][$item->owner.'_'.$key])) {
                            if (is_array($value) && !is_array($_SESSION['cfg'][$item->owner.'_'.$key])) {
                                $config[$key] = explode(',', $_SESSION['cfg'][$item->owner.'_'.$key]);
                            } else {
                                $config[$key] = $_SESSION['cfg'][$item->owner.'_'.$key];
                            }
                        }
                    }
                }
                $db->update($table, $item->id, array('config' => serialize($config)));
            }
        }
        $content[] = '<li class="correct">Update database <b>'.$table.'</b> complete...</li>';
        // board_q
        $table = $_SESSION['prefix'].'_'.$_SESSION['tables']['board_q'];
        if (self::indexExists($db, $table, 'topic')) {
            $db->query("ALTER TABLE `$table` DROP INDEX `topic`;");
        }
        $db->query("ALTER TABLE `$table` ADD FULLTEXT KEY `topic` (`topic`);");
        if (self::indexExists($db, $table, 'detail')) {
            $db->query("ALTER TABLE `$table` DROP INDEX `detail`;");
        }
        $db->query("ALTER TABLE `$table` ADD FULLTEXT KEY `detail` (`detail`);");
        $content[] = '<li class="correct">Update database <b>'.$table.'</b> complete...</li>';
        // board_r
        $table = $_SESSION['prefix'].'_'.$_SESSION['tables']['board_r'];
        if (self::indexExists($db, $table, 'detail')) {
            $db->query("ALTER TABLE `$table` DROP INDEX `detail`;");
        }
        $db->query("ALTER TABLE `$table` ADD FULLTEXT KEY `detail` (`detail`);");
        $content[] = '<li class="correct">Update database <b>'.$table.'</b> complete...</li>';
        // comment
        $table = $_SESSION['prefix'].'_'.$_SESSION['tables']['comment'];
        if (self::indexExists($db, $table, 'detail')) {
            $db->query("ALTER TABLE `$table` DROP INDEX `detail`;");
        }
        $db->query("ALTER TABLE `$table` ADD FULLTEXT KEY `detail` (`detail`);");
        $content[] = '<li class="correct">Update database <b>'.$table.'</b> complete...</li>';
        // useronline
        $table = $_SESSION['prefix'].'_'.$_SESSION['tables']['useronline'];
        $db->query("ALTER TABLE `$table` CHANGE `displayname` `displayname` VARCHAR(50) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL;");
        $db->query("ALTER TABLE `$table` CHANGE `icon` `icon` VARCHAR(24) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL;");
        $content[] = '<li class="correct">Update database <b>'.$table.'</b> complete...</li>';
        // textlink
        $table = $_SESSION['prefix'].'_textlink';
        if (!self::tableExists($db, $table)) {
            $db->query("CREATE TABLE `$table` (
        `id` int(11) NOT NULL,
        `text` text COLLATE utf8_unicode_ci NOT NULL,
        `url` text COLLATE utf8_unicode_ci NOT NULL,
        `publish_start` int(11) NOT NULL,
        `publish_end` int(11) NOT NULL,
        `logo` text COLLATE utf8_unicode_ci NOT NULL,
        `width` int(11) NOT NULL,
        `height` int(11) NOT NULL,
        `type` varchar(11) COLLATE utf8_unicode_ci NOT NULL,
        `name` varchar(11) COLLATE utf8_unicode_ci NOT NULL,
        `published` smallint(1) NOT NULL DEFAULT '1',
        `link_order` smallint(2) NOT NULL,
        `last_preview` int(11) DEFAULT NULL,
        `description` varchar(49) COLLATE utf8_unicode_ci NOT NULL,
        `template` text COLLATE utf8_unicode_ci,
        `target` varchar(6) COLLATE utf8_unicode_ci NOT NULL
        ) ENGINE = MyISAM DEFAULT CHARSET = utf8 COLLATE = utf8_unicode_ci;");
            $content[] = '<li class="correct">Update database <b>'.$table.'</b> complete...</li>';
        }
        // tags
        $table = $_SESSION['prefix'].'_tags';
        if (!self::tableExists($db, $table)) {
            $db->query("CREATE TABLE `$table` (
        `id` int(11) NOT NULL,
        `tag` text COLLATE utf8_unicode_ci NOT NULL,
        `count` int(11) NOT NULL
        ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;");
            $db->query("INSERT INTO `$table` (`id`, `tag`, `count`) VALUES (1, 'GCMS', 13);");
            $content[] = '<li class="correct">Update database <b>'.$table.'</b> complete...</li>';
        }
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
        // category
        $table = $_SESSION['prefix'].'_'.$_SESSION['tables']['category'];
        if (!self::fieldExists($db, $table, 'published')) {
            $db->query("ALTER TABLE `$table` ADD `published` ENUM('1','0') NOT NULL DEFAULT '1'  AFTER `icon`;");
        }
        foreach ($db->customQuery('SELECT `id`,`config` FROM `'.$table.'` WHERE `config`!=""') as $item) {
            $config = self::r2config($item->config);
            $db->update($table, $item->id, array('config' => serialize($config)));
        }
        $content[] = '<li class="correct">Update database <b>'.$table.'</b> complete...</li>';
        // settings/config.php
        foreach (self::$cfg as $key => $value) {
            if (isset($_SESSION['cfg'][$key])) {
                self::$cfg->$key = $_SESSION['cfg'][$key];
            }
        }
        self::$cfg->version = self::$cfg->new_version;
        unset(self::$cfg->new_version);
        $f = \Gcms\Config::save(self::$cfg, CONFIG);
        $content[] = '<li class="'.($f ? 'correct' : 'incorrect').'">Update file <b>config.php</b> ...</li>';
        // settings/database.php
        $database_cfg = include ROOT_PATH.'install/settings/database.php';
        $database_cfg['mysql']['username'] = $_SESSION['cfg']['db_username'];
        $database_cfg['mysql']['password'] = $_SESSION['cfg']['db_password'];
        $database_cfg['mysql']['dbname'] = $_SESSION['cfg']['db_name'];
        $database_cfg['mysql']['hostname'] = $_SESSION['cfg']['db_server'];
        $database_cfg['mysql']['prefix'] = $_SESSION['prefix'];
        foreach ($_SESSION['tables'] as $key => $value) {
            $database_cfg['tables'][$key] = $value;
        }
        $database_cfg['tables']['language'] = 'language';
        $database_cfg['tables']['textlink'] = 'textlink';
        $database_cfg['tables']['tags'] = 'tags';
        $f = \Gcms\Config::save($database_cfg, ROOT_PATH.'settings/database.php');
        $content[] = '<li class="'.($f ? 'correct' : 'incorrect').'">Update file <b>database.php</b> ...</li>';
        $content[] = '<li class="correct">Upgrade to Version <b>13.0.0</b> complete.</li>';
        return (object) array(
            'content' => implode('', $content),
            'version' => '13.0.0'
        );
    }

    /**
     * @param  $data
     *
     * @return mixed
     */
    public static function r2config($data)
    {
        $config = @unserialize($data);
        if (!is_array($config)) {
            $config = array();
            foreach (explode("\n", $data) as $item) {
                if ($item != '') {
                    if (preg_match('/^(.*)=(.*)$/U', $item, $match)) {
                        $config[$match[1]] = trim($match[2]);
                    }
                }
            }
        }
        return $config;
    }
}

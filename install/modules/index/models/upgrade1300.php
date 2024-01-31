<?php
/**
 * @filesource modules/index/models/upgrade1300.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Index\Upgrade1300;

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
     * อัปเกรดเป็นเวอร์ชั่น 13.0.0
     *
     * @return object
     */
    public static function upgrade($db)
    {
        // update database user
        $table = $_SESSION['prefix'].'_user';
        if (!\Index\Upgrade\Model::fieldExists($db, $table, 'active')) {
            $db->query("ALTER TABLE `$table` ADD `active` TINYINT(1) NOT NULL DEFAULT '0';");
        }
        if (!\Index\Upgrade\Model::fieldExists($db, $table, 'permission')) {
            $db->query("ALTER TABLE `$table` ADD `permission` TEXT NULL;");
            $db->query("UPDATE `$table` SET `permission`='can_config' WHERE `status`=1;");
        }
        if (\Index\Upgrade\Model::fieldExists($db, $table, 'admin_access')) {
            $db->query("UPDATE `$table` SET `active`=IF(`admin_access`='1',1,0)");
            $db->query("ALTER TABLE `$table` DROP `admin_access`;");
        }
        if (\Index\Upgrade\Model::fieldExists($db, $table, 'subscrib')) {
            $db->query("ALTER TABLE `$table` DROP `subscrib`;");
        }
        if (\Index\Upgrade\Model::fieldExists($db, $table, 'invite_id')) {
            $db->query("ALTER TABLE `$table` DROP `invite_id`;");
        }
        if (!\Index\Upgrade\Model::fieldExists($db, $table, 'name')) {
            $db->query("ALTER TABLE `$table` ADD `name` VARCHAR(150) NOT NULL AFTER `password`");
            $db->query("UPDATE `$table` SET `name`=TRIM(CONCAT_WS(' ', `pname`, `fname`, `lname`))");
        }
        if (!\Index\Upgrade\Model::fieldExists($db, $table, 'salt')) {
            $db->query("ALTER TABLE `$table` ADD `salt` VARCHAR(150) NOT NULL AFTER `email`");
            $db->query("UPDATE `$table` SET `salt`=`email`");
        }
        $content[] = '<li class="correct">Updated database <b>'.$table.'</b> complete...</li>';
        // update database useronline
        $table = $_SESSION['prefix'].'_useronline';
        $db->query("ALTER TABLE `$table` CHANGE `displayname` `displayname` VARCHAR(50) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL;");
        $content[] = '<li class="correct">Updated database <b>'.$table.'</b> complete...</li>';
        // update database language
        $table = $_SESSION['prefix'].'_language';
        $ja = \Index\Upgrade\Model::fieldExists($db, $table, 'ja');
        if (!$ja) {
            $db->query("ALTER TABLE `$table` ADD `ja` VARCHAR(2) NULL;");
        }
        $db->query("INSERT INTO `$table` (`key`, `type`, `owner`, `js`, `th`, `en`, `ja`) VALUES ('Other templates','text','index','0','แม่แบบอื่นๆ','','その他のテンプレート');");
        $db->query("INSERT INTO `$table` (`key`, `type`, `owner`, `js`, `th`, `en`, `ja`) VALUES ('Permission','text','index','0','สิทธิ์การใช้งาน','','許可');");
        $db->query("INSERT INTO `$table` (`key`, `type`, `owner`, `js`, `th`, `en`, `ja`) VALUES ('PERMISSIONS','array','index','0','a:1:{s:10:\"can_config\";s:60:\"สามารถตั้งค่าระบบได้\";}','a:1:{s:10:\"can_config\";s:24:\"Can configure the system\";}','a:1:{s:10:\"can_config\";s:48:\"システムを設定することができます\";}');");
        $db->query("INSERT INTO `$table` (`key`, `type`, `owner`, `js`, `th`, `en`, `ja`) VALUES ('Template is in use','text','index','0','แม่แบบกำลังใช้งาน','','テンプレートが使用中です');");
        $db->query("INSERT INTO `$table` (`key`, `type`, `owner`, `js`, `th`, `en`, `ja`) VALUES ('Unable to login','text','index','0','ไม่สามารถเข้าระบบผู้ดูแลได้','','管理者としてログインできません');");
        if (!$ja) {
            $db->query("ALTER TABLE `$table` DROP `ja`;");
        }
        // อัปเดตไฟล์ ภาษา
        $error = \Index\Language\Model::updateLanguageFile($db);
        if ($error != '') {
            $content[] = '<li class="incorrect">Updated database <b>'.$table.'</b> error!.</li>';
        } else {
            $content[] = '<li class="correct">Updated database <b>'.$table.'</b> complete...</li>';
        }
        $content[] = '<li class="correct">Upgrade to Version <b>13.0.0</b> complete.</li>';
        return (object) array(
            'content' => implode('', $content),
            'version' => '13.0.0',
        );
    }
}

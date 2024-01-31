<?php
/**
 * @filesource modules/index/views/install.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Index\Install4;

use Kotchasan\File;
use Kotchasan\Http\Request;

/**
 * ติดตั้ง
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class View extends \Gcms\View
{
    /**
     * step 4
     *
     * @return string
     */
    public function render(Request $request)
    {
        $content = array();
        if (defined('INSTALL')) {
            $_SESSION['db_username'] = $request->post('db_username')->username();
            $_SESSION['db_password'] = $request->post('db_password')->topic();
            $_SESSION['db_server'] = $request->post('db_server')->url();
            $_SESSION['db_name'] = $request->post('db_name')->filter('a-z0-9_');
            $_SESSION['prefix'] = $request->post('prefix')->filter('a-z0-9');
            $_SESSION['typ'] = $request->post('typ')->filter('a-z');
            $_SESSION['newdb'] = $request->post('newdb')->toInt();
            if ($_SESSION['newdb'] == 1) {
                $db = \Kotchasan\Database::create(array(
                    'username' => $_SESSION['db_username'],
                    'password' => $_SESSION['db_password'],
                    'hostname' => $_SESSION['db_server'],
                    'prefix' => $_SESSION['prefix']
                ));
                if ($db->connection()) {
                    $sql = 'CREATE DATABASE IF NOT EXISTS `'.$_SESSION['db_name'].'` DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci';
                    $db->query($sql);
                    $db->close();
                }
            }
            $db = \Kotchasan\Database::create(array(
                'username' => $_SESSION['db_username'],
                'password' => $_SESSION['db_password'],
                'dbname' => $_SESSION['db_name'],
                'hostname' => $_SESSION['db_server'],
                'prefix' => $_SESSION['prefix']
            ));
            if (!$db->connection()) {
                return \Index\Dberror\View::create()->render($request);
            }
            $db->query('SET SQL_MODE=""');
            // database default
            $database_cfg = include ROOT_PATH.'install/settings/database.php';
            $content[] = '<h2>{TITLE}</h2>';
            $content[] = '<p>การติดตั้งได้ดำเนินการเสร็จเรียบร้อยแล้ว หากคุณต้องการความช่วยเหลือ คุณสามารถ ติดต่อสอบถามได้ที่ <a href="https://goragod.com" target="_blank">https://goragod.com</a> หรือ <a href="http://gcms.in.th" target="_blank">http://gcms.in.th</a></p>';
            $content[] = '<ul>';
            // install database
            $sqlfiles = array();
            $sqlfiles[] = ROOT_PATH.'install/sql.php';
            if (!empty($_SESSION['typ']) && is_file(ROOT_PATH.'install/demo/'.$_SESSION['typ'].'/datas.php')) {
                $sqlfiles[] = ROOT_PATH.'install/demo/'.$_SESSION['typ'].'/datas.php';
            }
            foreach ($sqlfiles as $folder) {
                $fr = file($folder);
                foreach ($fr as $value) {
                    $sql = str_replace(array('{prefix}', '{WEBMASTER}', '{WEBURL}', '\r', '\n'), array($_SESSION['prefix'], $_SESSION['email'], WEB_URL, "\r", "\n"), trim($value));
                    if ($sql != '') {
                        if (preg_match('/^<\?.*\?>$/', $sql)) {
                            // php code
                        } elseif (preg_match('/^define\([\'"]([A-Z_]+)[\'"](.*)\);$/', $sql, $match)) {
                            $defines[$match[1]] = $match[0];
                        } elseif (preg_match('/DROP[\s]+TABLE[\s]+(IF[\s]+EXISTS[\s]+)?`?([a-z0-9_]+)`?/iu', $sql, $match)) {
                            $ret = $db->query($sql);
                            $content[] = '<li class="'.($ret === false ? 'incorrect' : 'correct')."\">DROP TABLE <b>$match[2]</b> ...</li>";
                        } elseif (preg_match('/CREATE[\s]+TABLE[\s]+(IF[\s]+NOT[\s]+EXISTS[\s]+)?`?([a-z0-9_]+)`?/iu', $sql, $match)) {
                            $ret = $db->query($sql);
                            $content[] = '<li class="'.($ret === false ? 'incorrect' : 'correct')."\">CREATE TABLE <b>$match[2]</b> ...</li>";
                        } elseif (preg_match('/ALTER[\s]+TABLE[\s]+`?([a-z0-9_]+)`?[\s]+ADD[\s]+`?([a-z0-9_]+)`?(.*)/iu', $sql, $match)) {
                            // add column
                            $sql = "SELECT * FROM `information_schema`.`columns` WHERE `table_schema`='$config[db_name]' AND `table_name`='$match[1]' AND `column_name`='$match[2]'";
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
            }
            // config
            self::$cfg->password_key = uniqid();
            self::$cfg->version = self::$cfg->new_version;
            unset(self::$cfg->new_version);
            self::$cfg->skin = 'rooser';
            // install Admin
            $salt = uniqid();
            $sql = "INSERT INTO `$_SESSION[prefix]_user` (`id`, `password`, `email`, `salt`, `displayname`,`country`, `status`, `social`, `active`, `create_date`, `permission`) VALUES (1,'".sha1(self::$cfg->password_key.$_SESSION['password'].$salt)."','$_SESSION[email]', '$salt', 'Admin','TH',1,'0','1',".time().",'can_config');";
            $db->query($sql);
            if (!empty($_SESSION['typ'])) {
                if (is_dir(ROOT_PATH.'install/demo/'.$_SESSION['typ'].'/datas/')) {
                    File::copyDirectory(ROOT_PATH.'install/demo/'.$_SESSION['typ'].'/datas/', ROOT_PATH.DATA_FOLDER);
                }
            }
            if (!empty($_SESSION['typ']) && file_exists(ROOT_PATH.'install/demo/'.$_SESSION['typ'].'/installer.php')) {
                include ROOT_PATH.'install/demo/'.$_SESSION['typ'].'/installer.php';
                $className = ucfirst($_SESSION['typ']).'\\Installer\\Model';
                if (method_exists($className, 'import')) {
                    // ฟังก์ชั่นนำเข้าข้อมูล
                    $content[] = $className::import($db);
                }
            }
            $f = \Gcms\Config::save(self::$cfg, CONFIG);
            $content[] = '<li class="'.($f ? 'correct' : 'incorrect').'">Update file <b>config.php</b> ...</li>';
            // บันทึก settings/database.php
            $database_cfg['mysql']['username'] = $_SESSION['db_username'];
            $database_cfg['mysql']['password'] = $_SESSION['db_password'];
            $database_cfg['mysql']['dbname'] = $_SESSION['db_name'];
            $database_cfg['mysql']['hostname'] = $_SESSION['db_server'];
            $database_cfg['mysql']['prefix'] = $_SESSION['prefix'];
            $f = \Gcms\Config::save($database_cfg, ROOT_PATH.'settings/database.php');
            $content[] = '<li class="'.($f ? 'correct' : 'incorrect').'">Update file <b>database.php</b> ...</li>';
            $content[] = '<li class=correct>'.\Index\Language\Model::importLanguage($db, $database_cfg['mysql']['prefix']).'</li>';
            // sitemap
            $datas = array();
            $datas[] = 'sitemap: '.WEB_URL.'sitemap.xml';
            $datas[] = 'User-agent: *';
            $datas[] = 'Disallow: /Gcms/';
            $datas[] = 'Disallow: /Kotchasan/';
            $datas[] = 'Disallow: /ckeditor/';
            $datas[] = 'Disallow: /admin/';
            $datas[] = 'Disallow: /skin/fonts/';
            $f = @fopen(ROOT_PATH.'robots.txt', 'wb');
            fwrite($f, implode("\n", $datas));
            fclose($f);
            $content[] = '<li class="'.($f ? 'correct' : 'incorrect').'">Update file <b>robots.txt</b> ...</li>';
            // .htaccess
            $base_path = str_replace('install/', '', BASE_PATH);
            $datas = array();
            $datas[] = '<IfModule mod_rewrite.c>';
            $datas[] = 'RewriteEngine On';
            $datas[] = 'RewriteBase /';
            $datas[] = '';
            $datas[] = '# redirect to non-www';
            $datas[] = '#RewriteCond %{HTTP_HOST} ^www\.(.*)$ [NC]';
            $datas[] = '#RewriteRule ^(.*)$ http://%1/$1 [R=301,L]';
            $datas[] = '';
            $datas[] = '# redirect to www';
            $datas[] = '#RewriteCond %{HTTP_HOST} !^www\.';
            $datas[] = '#RewriteRule ^(.*)$ http://www.%{HTTP_HOST}/$1 [R=301,L]';
            $datas[] = '';
            $datas[] = '# redirect to https';
            $datas[] = '#RewriteCond %{HTTPS} off';
            $datas[] = '#RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]';
            $datas[] = '';
            $datas[] = 'RewriteRule ^(feed|menu|sitemap|BingSiteAuth)\.(xml|rss)$ '.$base_path.'$1.php [L,QSA]';
            $datas[] = 'RewriteRule ^(.*).rss$ '.$base_path.'feed.php?module=$1 [L,QSA]';
            $datas[] = 'RewriteCond %{REQUEST_FILENAME} !-f';
            $datas[] = 'RewriteCond %{REQUEST_FILENAME} !-d';
            $datas[] = 'RewriteRule . '.$base_path.'index.php [L,QSA]';
            $datas[] = '</IfModule>';
            $datas[] = '# cache 1 week';
            $datas[] = '<filesMatch ".(jpg|jpeg|png|gif|ico)$">';
            $datas[] = '#Header set Cache-Control "max-age=604800, public"';
            $datas[] = '</filesMatch>';
            $datas[] = '<filesMatch ".(css|js|ttf|woff|svg|eot)$">';
            $datas[] = '#Header set Cache-Control "max-age=604800, public"';
            $datas[] = '</filesMatch>';
            $datas[] = '<IfModule mod_headers.c>';
            $datas[] = '#Header set X-XSS-Protection "1; mode=block"';
            $datas[] = '</IfModule>';
            $f = @fopen(ROOT_PATH.'.htaccess', 'wb');
            if ($f) {
                fwrite($f, implode("\n", $datas));
                fclose($f);
            }
            $content[] = '<li class='.($f ? 'correct' : 'incorrect').'>Update file <b>.htaccess</b> ...</li>';
            $content[] = '</ul>';
            $content[] = '<p class=warning>กรุณาลบโฟลเดอร์ <em>install/</em> ออกจาก Server ของคุณ</p>';
            $content[] = '<p>คุณควรปรับ chmod ให้โฟลเดอร์ <em>'.DATA_FOLDER.'</em> เป็น 755 ก่อนดำเนินการต่อ (ถ้าคุณได้ทำการปรับ chmod ด้วยตัวเอง)</p>';
            $content[] = '<p>เมื่อเรียบร้อยแล้ว กรุณา<b>เข้าระบบผู้ดูแล</b>เพื่อตั้งค่าที่จำเป็นอื่นๆโดยใช้ขื่ออีเมล <em>'.$_SESSION['email'].'</em> และ รหัสผ่าน <em>'.$_SESSION['password'].'</em></p>';
            $content[] = '<p><a href="'.WEB_URL.'admin/index.php?module=system" class="button large admin">เข้าระบบผู้ดูแล</a></p>';
        }
        return (object) array(
            'title' => 'ติดตั้ง GCMS เวอร์ชั่น '.self::$cfg->version.' เรียบร้อย',
            'content' => implode('', $content)
        );
    }
}

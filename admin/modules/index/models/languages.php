<?php
/**
 * @filesource modules/index/models/languages.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Index\Languages;

use Gcms\Config;
use Gcms\Login;
use Kotchasan\Http\Request;
use Kotchasan\Language;

/**
 * module=languages
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Model extends \Kotchasan\KBase
{
    /**
     * บันทึกจาก ajax
     *
     * @param Request $request
     */
    public function action(Request $request)
    {
        $ret = array();
        // session, referer, เข้าระบบแอดมินได้, can_config, ไม่ใช่สมาชิกตัวอย่าง
        if ($request->initSession() && $request->isReferer() && $login = Login::adminAccess()) {
            if (Login::checkPermission($login, 'can_config') && Login::notDemoMode($login)) {
                // โหลด config
                $config = Config::load(CONFIG);
                // รับค่าจากการ POST
                $post = $request->getParsedBody();
                if ($post['action'] === 'import') {
                    self::import();
                } elseif ($post['action'] === 'changed' || $post['action'] === 'move') {
                    if ($post['action'] === 'changed') {
                        // เปลี่ยนแปลงสถานะการเผยแพร่ภาษา
                        $config->languages = explode(',', str_replace('check_', '', $post['data']));
                    } else {
                        // จัดลำดับภาษา
                        $languages = empty($config->languages) ? Language::installedLanguage() : $config->languages;
                        $config->languages = array();
                        foreach (explode(',', str_replace('L_', '', $post['data'])) as $lng) {
                            if (in_array($lng, $languages)) {
                                $config->languages[] = $lng;
                            }
                        }
                    }
                    $ret['save'] = true;
                } elseif ($post['action'] === 'droplang' && preg_match('/^([a-z]{2,2})$/', $post['data'], $match)) {
                    // ลบภาษา
                    $model = new \Kotchasan\Model();
                    $language_table = $model->getTableName('language');
                    if ($model->db()->fieldExists($language_table, $match[1])) {
                        $model->db()->query("ALTER TABLE `$language_table` DROP `$match[1]`");
                    }
                    // ลบไฟล์
                    @unlink(ROOT_PATH.'language/'.$match[1].'.php');
                    @unlink(ROOT_PATH.'language/'.$match[1].'.js');
                    @unlink(ROOT_PATH.'language/'.$match[1].'.gif');
                    $languages = array();
                    foreach ($config->languages as $item) {
                        if ($match[1] !== $item) {
                            $languages[] = $item;
                        }
                    }
                    $config->languages = $languages;
                    $ret['save'] = 'reload';
                }
                if (!empty($ret['save'])) {
                    // save config
                    if (Config::save($config, CONFIG)) {
                        if ($ret['save'] == 'reload') {
                            $ret['location'] = 'reload';
                        }
                    } else {
                        $ret['alert'] = Language::replace('File %s cannot be created or is read-only.', 'settings/config.php');
                    }
                }
            }
        }
        if (empty($ret)) {
            $ret['alert'] = Language::get('Unable to complete the transaction');
        }
        // คืนค่าเป็น JSON
        echo json_encode($ret);
    }

    /**
     * นำเข้าข้อมูลไฟล์ภาษา
     */
    public static function import()
    {
        $dir = ROOT_PATH.'language/';
        if (is_dir($dir)) {
            // Model
            $model = new \Kotchasan\Model();
            // ตาราง language
            $language_table = $model->getTableName('language');
            $f = opendir($dir);
            while (false !== ($text = readdir($f))) {
                if (preg_match('/([a-z]{2,2})\.(php|js)/', $text, $match)) {
                    if ($model->db()->fieldExists($language_table, $match[1]) == false) {
                        // เพิ่อมคอลัมน์ภาษา ถ้ายังไม่มีภาษาที่ต้องการ
                        $model->db()->query("ALTER TABLE `$language_table` ADD `$match[1]` TEXT CHARACTER SET utf8 COLLATE utf8_unicode_ci AFTER `key`");
                    }
                    if ($match[2] == 'php') {
                        self::importPHP($model->db(), $language_table, $match[1], $dir.$text);
                    } else {
                        self::importJS($model->db(), $language_table, $match[1], $dir.$text);
                    }
                }
            }
            closedir($f);
        }
    }

    /**
     * นำเข้าข้อมูลไฟล์ภาษา PHP
     *
     * @param Database $db             Database Object
     * @param string   $language_table ชื่อตาราง language
     * @param string   $lang           ชื่อภาษา
     * @param string   $file_name      ไฟล์ภาษา
     */
    public static function importPHP($db, $language_table, $lang, $file_name)
    {
        foreach (include ($file_name) as $key => $value) {
            if (is_array($value)) {
                $type = 'array';
            } elseif (is_int($value)) {
                $type = 'int';
            } else {
                $type = 'text';
            }
            $search = $db->first($language_table, array(
                array('key', $key),
                array('js', 0),
                array('type', $type)
            ));
            if ($type == 'array') {
                $value = serialize($value);
            }
            if ($search) {
                $db->update($language_table, $search->id, array(
                    $lang => $value
                ));
            } else {
                $db->insert($language_table, array(
                    'key' => $key,
                    'js' => 0,
                    'type' => $type,
                    'owner' => 'index',
                    $lang => $value
                ));
            }
        }
    }

    /**
     * นำเข้าข้อมูลไฟล์ภาษา Javascript
     *
     * @param Database $db             Database Object
     * @param string   $language_table ชื่อตาราง language
     * @param string   $lang           ชื่อภาษา
     * @param string   $file_name      ไฟล์ภาษา
     */
    public static function importJS($db, $language_table, $lang, $file_name)
    {
        $patt = '/^var[\s]+([A-Z0-9_]+)[\s]{0,}=[\s]{0,}[\'"](.*)[\'"];$/';
        foreach (file($file_name) as $item) {
            $item = trim($item);
            if ($item != '') {
                if (preg_match($patt, $item, $match)) {
                    $search = $db->first($language_table, array(
                        array('key', $match[1]),
                        array('js', 1)
                    ));
                    if ($search) {
                        $db->update($language_table, $search->id, array(
                            $lang => $match[2]
                        ));
                    } else {
                        $db->insert($language_table, array(
                            'key' => $match[1],
                            'js' => 1,
                            'type' => 'text',
                            'owner' => 'index',
                            $lang => $match[2]
                        ));
                    }
                }
            }
        }
    }
}

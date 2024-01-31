<?php
/**
 * @filesource modules/index/models/language.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Index\Language;

use Kotchasan\Language;

/**
 * โมเดลสำหรับภาษา (language.php)
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Model extends \Kotchasan\Model
{
    /**
     * อัปเดตไฟล์ ภาษา
     *
     * @param Database $db
     *
     * @return string
     */
    public static function updateLanguageFile($db)
    {
        // ภาษาที่ติดตั้ง
        $languages = Language::installedLanguage();
        // query ข้อมูลภาษา
        $query = $db->createQuery()->select()->from('language')->order('key');
        // เตรียมข้อมูล
        $datas = array();
        foreach ($query->toArray()->execute() as $item) {
            $save = array('key' => $item['key']);
            foreach ($languages as $lng) {
                if (isset($item[$lng]) && $item[$lng] != '') {
                    if ($item['type'] == 'array') {
                        $data = @unserialize($item[$lng]);
                        if (is_array($data)) {
                            $save[$lng] = $data;
                        }
                    } elseif ($item['type'] == 'int') {
                        $save[$lng] = (int) $item[$lng];
                    } else {
                        $save[$lng] = $item[$lng];
                    }
                }
            }
            $datas[$item['js'] == 1 ? 'js' : 'php'][] = $save;
        }
        // บันทึกไฟล์ภาษา
        $error = '';
        foreach ($datas as $type => $items) {
            $error .= Language::save($items, $type);
        }
        return $error;
    }

    /**
     * @param mixed $db
     * @param string $prefix
     */
    public static function importLanguage($db, $prefix)
    {
        // นำเข้าภาษา
        $dir = ROOT_PATH.'language/';
        if (is_dir(ROOT_PATH.'language/')) {
            // ตาราง language
            $table = $prefix.'_language';
            // อ่านไฟล์ภาษาที่ติดตั้ง
            $f = opendir($dir);
            if ($f) {
                while (false !== ($text = readdir($f))) {
                    if (preg_match('/^([a-z]{2,2})\.(php|js)$/', $text, $match)) {
                        if ($db->fieldExists($table, $match[1]) == false) {
                            // เพิ่มคอลัมน์ภาษา ถ้ายังไม่มีภาษาที่ต้องการ
                            $db->query("ALTER TABLE `$table` ADD `$match[1]` TEXT CHARACTER SET utf8 COLLATE utf8_unicode_ci AFTER `en`");
                        }
                        if ($match[2] == 'php') {
                            self::importPHP($db, $table, $match[1], $dir.$text);
                        } else {
                            self::importJS($db, $table, $match[1], $dir.$text);
                        }
                    }
                }
                closedir($f);
            }
            return 'นำเข้า `'.$table.'` สำเร็จ';
        }
    }

    /**
     * นำเข้าข้อมูลไฟล์ภาษา PHP
     *
     * @param Db $db             Database Class
     * @param string   $table ชื่อตาราง language
     * @param string   $lang           ชื่อภาษา
     * @param string   $file_name      ไฟล์ภาษา
     */
    public static function importPHP($db, $table, $lang, $file_name)
    {
        foreach (include ($file_name) as $key => $value) {
            if (is_array($value)) {
                $type = 'array';
            } elseif (is_int($value)) {
                $type = 'int';
            } else {
                $type = 'text';
            }
            $search = $db->first($table, array(
                array('key', $key),
                array('js', 0)
            ));
            if ($type == 'array') {
                $value = serialize($value);
            }
            if ($search) {
                $db->update($table, array('id', $search->id), array($lang => $value));
            } else {
                $db->insert($table, array(
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
     * @param string   $table ชื่อตาราง language
     * @param string   $lang           ชื่อภาษา
     * @param string   $file_name      ไฟล์ภาษา
     */
    public static function importJS($db, $table, $lang, $file_name)
    {
        $patt = '/^var[\s]+([A-Z0-9_]+)[\s]{0,}=[\s]{0,}[\'"](.*)[\'"];$/';
        foreach (file($file_name) as $item) {
            $item = trim($item);
            if ($item != '') {
                if (preg_match($patt, $item, $match)) {
                    $search = $db->first($table, array(
                        array('key', $match[1]),
                        array('js', 1)
                    ));
                    if ($search) {
                        $db->update($table, array('id', $search->id), array($lang => $match[2]));
                    } else {
                        $db->insert($table, array(
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

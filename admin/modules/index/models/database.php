<?php
/**
 * @filesource modules/index/models/database.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Index\Database;

use Gcms\Login;
use Kotchasan\Http\Request;
use Kotchasan\Http\Response;
use Kotchasan\Language;

/**
 * ตรวจสอบข้อมูลสมาชิกด้วย Ajax
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Model extends \Kotchasan\Model
{
    /**
     * export database to file
     *
     * @param Request $request
     */
    public function export(Request $request)
    {
        // referer, session, admin
        if ($request->initSession() && $request->isReferer() && $login = Login::isAdmin()) {
            if (Login::notDemoMode($login)) {
                $sqls = array();
                $rows = array();
                $database = array();
                $datas = array();
                foreach ($request->getParsedBody() as $table => $values) {
                    foreach ($values as $k => $v) {
                        if (isset($datas[$table][$v])) {
                            ++$datas[$table][$v];
                        } else {
                            $datas[$table][$v] = 1;
                        }
                    }
                }
                $web_url = str_replace(array('http://', 'https://', 'www.'), '', WEB_URL);
                $web_url = '/http(s)?:\/\/(www\.)?'.preg_quote($web_url, '/').'/';
                // ชื่อฐานข้อมูล
                $db_name = $this->getSetting('dbname').'.sql';
                // memory limit
                ini_set('memory_limit', '1024M');
                // prefix
                $prefix = $this->getSetting('prefix');
                // ตารางทั้งหมด
                $tables = $this->db()->customQuery('SHOW TABLE STATUS', true);
                // ตารางทั้งหมด
                foreach ($tables as $table) {
                    if (preg_match('/^'.$prefix.'_(.*?)$/', $table['Name'], $match) && isset($datas[$table['Name']])) {
                        if (isset($datas[$table['Name']]['sturcture'])) {
                            $fields = $this->db()->customQuery('SHOW CREATE TABLE '.$table['Name'], true);
                            $sqls[] = 'DROP TABLE IF EXISTS `{prefix}_'.$match[1].'`;';
                            $sqls[] = preg_replace(array('/AUTO_INCREMENT=[0-9]+/', '/[\r\n\t\s]{1,}/s', '/CREATE TABLE `'.$prefix.'_([^`]+)`/'), array(' ', ' ', 'CREATE TABLE `{prefix}_\\1`'), $fields[0]['Create Table']);
                        }
                        $fields = $this->db()->customQuery('SHOW FULL FIELDS FROM '.$table['Name'], true);
                        foreach ($fields as $field) {
                            $database[$table['Name']]['Field'][] = $field['Field'];
                        }
                    }
                }
                // ข้อมูลในตาราง
                foreach ($tables as $table) {
                    if (preg_match('/^'.$prefix.'(.*?)$/', $table['Name'], $match)) {
                        if ($match[1] == '_language') {
                            if (isset($_POST['language_lang']) && isset($_POST['language_owner'])) {
                                $l = array_merge(array('key', 'type', 'owner', 'js'), $_POST['language_lang']);
                                foreach ($_POST['language_owner'] as $lang) {
                                    $languages[] = "'$lang'";
                                    if ($lang == 'index') {
                                        $languages[] = "''";
                                    }
                                }
                                $table_name = $prefix == '' ? $table['Name'] : preg_replace('/^'.$prefix.'/', '{prefix}', $table['Name']);
                                $data = "INSERT INTO `$table_name` (`".implode('`, `', $l)."`) VALUES ('%s');";
                                $sql = 'SELECT `'.implode('`,`', $l).'` FROM `'.$table['Name'].'` WHERE `owner` IN ('.implode(',', $languages).') ORDER BY `owner`,`key`,`js`';
                                foreach ($this->db()->customQuery($sql, true) as $record) {
                                    foreach ($record as $field => $value) {
                                        $record[$field] = ($field == 'owner' && $value == '') ? 'index' : addslashes($value);
                                    }
                                    $sqls[] = preg_replace(array('/[\r]/u', '/[\n]/u'), array('\r', '\n'), sprintf($data, implode("','", $record)));
                                }
                            }
                        } elseif ($match[1] == '_emailtemplate') {
                            if (isset($datas[$table['Name']]['datas'])) {
                                if (($key = array_search('id', $database[$table['Name']]['Field'])) !== false) {
                                    unset($database[$table['Name']]['Field'][$key]);
                                }
                                $table_name = $prefix == '' ? $table['Name'] : preg_replace('/^'.$prefix.'/', '{prefix}', $table['Name']);
                                $data = "INSERT INTO `$table_name` (`".implode('`, `', $database[$table['Name']]['Field'])."`) VALUES ('%s');";
                                $records = $this->db()->customQuery('SELECT * FROM '.$table['Name'], true);
                                foreach ($records as $record) {
                                    foreach ($record as $field => $value) {
                                        if ($field === 'copy_to' || $field === 'from_email') {
                                            $record[$field] = $value == $login['email'] ? '{WEBMASTER}' : '';
                                        } elseif ($field == 'id') {
                                            unset($record['id']);
                                        } else {
                                            $record[$field] = addslashes(preg_replace($web_url, '{WEBURL}', $value));
                                        }
                                    }
                                    $sqls[] = preg_replace(array('/[\r]/u', '/[\n]/u'), array('\r', '\n'), sprintf($data, implode("','", $record)));
                                }
                            }
                        } elseif (isset($datas[$table['Name']]['datas'])) {
                            $table_name = $prefix == '' ? $table['Name'] : preg_replace('/^'.$prefix.'/', '{prefix}', $table['Name']);
                            $data = "INSERT INTO `$table_name` (`".implode('`, `', $database[$table['Name']]['Field'])."`) VALUES ('%s');";
                            $records = $this->db()->customQuery('SELECT * FROM '.$table['Name'], true);
                            foreach ($records as $record) {
                                foreach ($record as $field => $value) {
                                    $record[$field] = addslashes(preg_replace($web_url, '{WEBURL}', $value));
                                }
                                $sqls[] = preg_replace(array('/[\r]/u', '/[\n]/u'), array('\r', '\n'), sprintf($data, implode("','", $record)));
                            }
                        }
                    }
                }
                // send file
                $response = new Response();
                $response->withHeaders(array(
                    'Content-Type' => 'application/force-download',
                    'Content-Disposition' => 'attachment; filename='.$db_name
                ))->withContent(preg_replace(array('/[\\\\]+/', '/\\\"/'), array('\\', '"'), implode("\r\n", $sqls)))->send();
                exit;
            }
        }
        // ไม่สามารถดาวน์โหลดได้
        $response = new Response(404);
        $response->withContent('File Not Found!')->send();
    }

    /**
     * import database
     *
     * @param Request $request
     */
    public function import(Request $request)
    {
        $ret = array();
        // referer, token, admin, ไม่ใช่ตัวอย่าง
        if ($request->initSession() && $request->isSafe() && $login = Login::isAdmin()) {
            if (Login::notDemoMode($login)) {
                // prefix
                $prefix = $this->getSetting('prefix');
                // อัปโหลดไฟล์
                foreach ($request->getUploadedFiles() as $item => $file) {
                    /* @var $file \Kotchasan\Http\UploadedFile */
                    if ($file->hasUploadFile()) {
                        if (!$file->validFileExt(array('sql'))) {
                            // ชนิดของไฟล์ไม่ถูกต้อง
                            $ret['ret_'.$item] = Language::get('The type of file is invalid');
                        } else {
                            // long time
                            set_time_limit(0);
                            // อ่านไฟล์อัปโหลดมา query ทีละบรรทัด
                            foreach (file($file->getTempFileName()) as $value) {
                                $sql = str_replace(array('\r', '\n', '{prefix}', '/{WEBMASTER}/', '/{WEBURL}/'), array("\r", "\n", $prefix, $login['email'], WEB_URL), trim($value));
                                if ($sql != '') {
                                    $this->db()->query($sql);
                                }
                            }
                            // คืนค่า
                            $ret['alert'] = Language::get('Data import completed Please reload the page to see the changes');
                            // เคลียร์
                            $request->removeToken();
                        }
                    } elseif ($file->hasError()) {
                        // ข้อผิดพลาดการอัปโหลด
                        $ret['ret_'.$item] = Language::get($file->getErrorMessage());
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
}

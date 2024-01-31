<?php
/**
 * @filesource modules/index/models/languageedit.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Index\Languageedit;

use Gcms\Login;
use Kotchasan\Http\Request;
use Kotchasan\Language;

/**
 * module=languageedit
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Model extends \Kotchasan\Model
{
    /**
     * อ่านรายการ owner จากฐานข้อมูลภาษา
     *
     * @return array
     */
    public static function getOwners()
    {
        $query = static::createQuery()
            ->select('owner')
            ->from('language')
            ->groupBy('owner')
            ->toArray();
        $result = array();
        foreach ($query->execute() as $item) {
            $result[$item['owner']] = $item['owner'];
        }
        return $result;
    }

    /**
     * ยอมรับ tag บางตัว ในภาษา a em b strong ul ol li dd dt dl
     *
     * @param string $string
     *
     * @return string
     */
    public function allowTags($string)
    {
        return preg_replace_callback('/(&lt;(\/?(a|em|b|strong|ul|ol|li|dd|dt|dl|small)).*?&gt;)/is', function ($matches) {
            return html_entity_decode($matches[1]);
        }, $string);
    }

    /**
     * form submit (languageedit.php)
     *
     * @param Request $request
     */
    public function submit(Request $request)
    {
        $ret = array();
        // session, token, แอดมิน, can_config, ไม่ใช่สมาชิกตัวอย่าง
        if ($request->initSession() && $request->isSafe() && $login = Login::adminAccess()) {
            if (Login::checkPermission($login, 'can_config') && Login::notDemoMode($login)) {
                try {
                    // ค่าที่ส่งมา
                    $save = array(
                        'js' => $request->post('write_js')->toBoolean(),
                        'type' => $request->post('write_type')->topic(),
                        'owner' => $request->post('write_owner')->topic(),
                        'key' => $this->allowTags($request->post('write_key')->topic())
                    );
                    // ภาษาที่ติดตั้ง
                    $languages = Language::installedLanguage();
                    $values = array();
                    foreach ($request->post('datas')->topic() as $items) {
                        foreach ($languages as $lng) {
                            if ($items[$lng] != '') {
                                $values[$lng][$items['key']] = $items[$lng];
                                if (count($values[$lng]) > 1) {
                                    $save['type'] = 'array';
                                }
                            }
                        }
                    }
                    if (empty($values)) {
                        // ไม่ได้กรอกภาษา
                        $ret['alert'] = Language::trans('{LNG_Please fill in} {LNG_Language} th');
                    } else {
                        foreach ($languages as $lng) {
                            if (isset($values[$lng])) {
                                if ($save['type'] == 'array') {
                                    $save[$lng] = $this->allowTags(serialize($values[$lng]));
                                } else {
                                    $save[$lng] = $this->allowTags(reset($values[$lng]));
                                }
                            } else {
                                $save[$lng] = '';
                            }
                        }
                        if ($save['js']) {
                            if (isset($save['en']) && $save['en'] == '') {
                                $save['en'] = $save['key'];
                            }
                            $save['key'] = strtoupper(preg_replace(array('/[\s]+/', '/([^A-Z_]+)/i'), array('_', ''), $save['key']));
                        }
                        $id = $request->post('write_id')->toInt();
                        // ตาราง
                        $table_language = $this->getTableName('language');
                        // ตรวจสอบรายการที่แก้ไข
                        if ($id > 0) {
                            $language = $this->db()->first($table_language, $id);
                        }
                        if ($id > 0 && !$language) {
                            $ret['alert'] = Language::get('Sorry, Item not found It&#39;s may be deleted');
                        } elseif ($save['key'] == '') {
                            $ret['ret_write_key'] = Language::get('Please fill in');
                        } else {
                            // ตรวจสอบข้อมูลซ้ำ
                            $search = $this->db()->first($table_language, array(
                                array('key', $save['key']),
                                array('js', $save['js'])
                            ));
                            if ($search && ($id == 0 || $id != $search->id)) {
                                $ret['ret_write_key'] = Language::replace('This :name already exist', array(':name' => Language::get('Key')));
                            } else {
                                // บันทึก
                                if ($id == 0) {
                                    // ใหม่
                                    $id = $this->db()->insert($table_language, $save);
                                    // redirect
                                    $ret['location'] = $request->getUri()->postBack('index.php', array('module' => 'language', 'js' => $save['js'], 'sort' => 'id DESC')).'#datatable_'.$id;
                                } else {
                                    // แก้ไข
                                    $this->db()->update($table_language, $id, $save);
                                    // redirect
                                    $ret['location'] = $request->getUri()->postBack('index.php', array('module' => 'language', 'js' => $save['js'])).'#datatable_'.$id;
                                }
                                // อัปเดตไฟล์ ภาษา
                                $error = \Index\Language\Model::updateLanguageFile();
                                if (empty($error)) {
                                    $ret['alert'] = Language::get('Saved successfully');
                                } else {
                                    unset($ret['location']);
                                    $ret['alert'] = $error;
                                }
                                // clear
                                $request->removeToken();
                            }
                        }
                    }
                } catch (\Kotchasan\InputItemException $e) {
                    $ret['alert'] = $e->getMessage();
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

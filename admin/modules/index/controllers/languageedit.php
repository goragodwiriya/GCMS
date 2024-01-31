<?php
/**
 * @filesource modules/index/controllers/languageedit.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Index\Languageedit;

use Gcms\Login;
use Kotchasan\Html;
use Kotchasan\Http\Request;
use Kotchasan\Language;

/**
 * module=languageedit
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Controller extends \Gcms\Controller
{
    /**
     * ฟอร์มเขียน/แก้ไข ภาษา
     *
     * @param Request $request
     *
     * @return string
     */
    public function render(Request $request)
    {
        // ข้อความ title bar
        $this->title = Language::get('Add and manage the display language of the site');
        // เลือกเมนู
        $this->menu = 'tools';
        // สามารถตั้งค่าระบบได้
        if (Login::checkPermission(Login::adminAccess(), 'can_config')) {
            // ภาษาที่ติดตั้ง
            $languages = \Gcms\Gcms::installedLanguage();
            // รายการที่แก้ไข (id)
            $id = $request->request('id')->toInt();
            $key = $request->request('key')->topic();
            // แก้ไข อ่านรายการที่เลือก
            $model = new \Kotchasan\Model();
            if ($id > 0) {
                $language = $model->db()->first($model->getTableName('language'), $id);
            } elseif ($key != '') {
                $language = $model->db()->first($model->getTableName('language'), array('key', $key));
            } else {
                $language = false;
            }
            if ($language) {
                $title = '{LNG_Edit}';
                if ($language->type == 'array') {
                    foreach ($languages as $lng) {
                        if ($language->$lng != '') {
                            $ds = @unserialize($language->$lng);
                            if (is_array($ds)) {
                                foreach ($ds as $key => $value) {
                                    $language->datas[$key]['key'] = $key;
                                    $language->datas[$key][$lng] = $value;
                                }
                            } else {
                                $language->datas[0]['key'] = '';
                                $language->datas[0][$lng] = $language->$lng;
                            }
                        }
                        unset($language->$lng);
                    }
                    // ตรวจสอบข้อมูลให้มีทุกภาษา
                    foreach ($language->datas as $key => $values) {
                        foreach ($languages as $lng) {
                            if (!isset($language->datas[$key][$lng])) {
                                $language->datas[$key][$lng] = '';
                            }
                        }
                    }
                } else {
                    $language->datas[0]['key'] = '';
                    foreach ($languages as $lng) {
                        $language->datas[0][$lng] = $language->$lng;
                        unset($language->$lng);
                    }
                }
            } else {
                $title = '{LNG_Add New}';
                // ใหม่
                $language = array(
                    'id' => 0,
                    'key' => '',
                    'js' => $request->request('type')->toBoolean(),
                    'owner' => 'index',
                    'type' => 'text'
                );
                $language['datas'][0]['key'] = '';
                foreach ($languages as $lng) {
                    $language['datas'][0][$lng] = '';
                }
                $language = (object) $language;
            }
            // แสดงผล
            $section = Html::create('section');
            // breadcrumbs
            $breadcrumbs = $section->add('div', array(
                'class' => 'breadcrumbs'
            ));
            $ul = $breadcrumbs->add('ul');
            $ul->appendChild('<li><span class="icon-tools">{LNG_Tools}</span></li>');
            $ul->appendChild('<li><a href="{BACKURL?module=language&id=0}">{LNG_Language}</a></li>');
            $ul->appendChild('<li><span>'.$title.'</span></li>');
            $section->add('header', array(
                'innerHTML' => '<h2 class="icon-language">'.$this->title.'</h2>'
            ));
            // แสดงฟอร์ม
            $section->appendChild(\Index\Languageedit\View::create()->render($request, $language));
            // คืนค่า HTML
            return $section->render();
        }
        // 404.html
        return \Index\Error\Controller::page404();
    }
}

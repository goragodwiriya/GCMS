<?php
/**
 * @filesource modules/index/models/menuwrite.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Index\Menuwrite;

use Gcms\Gcms;
use Gcms\Login;
use Kotchasan\Http\Request;
use Kotchasan\Language;

/**
 * module=menuwrite
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Model extends \Kotchasan\Model
{
    /**
     * อ่านเมนูตาม id
     * 0 หมายถึง สร้างเมนูใหม่
     *
     * @param string $parent
     * @param int    $id
     *
     * @return array|bool คืนค่าแอเรย์ของข้อมูล ไม่พบคืนค่า false
     */
    public static function getMenu($parent, $id)
    {
        if (is_int($id)) {
            if (empty($id)) {
                // ใหม่
                $index = (object) array(
                    'id' => 0,
                    'index_id' => 0,
                    'parent' => $parent,
                    'level' => 0,
                    'language' => Language::name(),
                    'menu_text' => '',
                    'menu_tooltip' => '',
                    'accesskey' => '',
                    'menu_order' => 0,
                    'menu_url' => '',
                    'menu_target' => '',
                    'alias' => '',
                    'owner' => '',
                    'module' => '',
                    'published' => 1
                );
            } else {
                // อ่านข้อมูลจาก db
                $model = new static;
                $q = $model->db()->createQuery()->select('I.module_id')->from('index I')->where(array('I.id', 'U.index_id'));
                $q1 = $model->db()->createQuery()->select('M.owner')->from('modules M')->where(array('M.id', $q));
                $q2 = $model->db()->createQuery()->select('M.module')->from('modules M')->where(array('M.id', $q));
                $index = $model->db()->createQuery()->from('menus U')->where($id)->first('U.*', array($q1, 'owner'), array($q2, 'module'));
            }
            return $index;
        }
        return false;
    }

    /**
     * รายการ หน้าเว็บทั้งหมด และ โมดูลที่ติดตั้ง
     *
     * @return array
     */
    public static function getModules()
    {
        $result = array();
        if (defined('MAIN_INIT')) {
            $query = static::createQuery()
                ->select(array('I.id', 'M.owner', 'M.module', 'D.topic', 'I.language'))
                ->from('index I')
                ->join('index_detail D', 'INNER', array(array('D.id', 'I.id'), array('D.module_id', 'I.module_id'), array('D.language', 'I.language')))
                ->join('modules M', 'INNER', array('M.id', 'I.module_id'))
                ->where(array('I.index', 1))
                ->order(array('M.owner', 'M.module', 'I.module_id', 'I.language'));
            foreach ($query->toArray()->execute() as $item) {
                $result[$item['owner']][$item['owner'].'_'.$item['module'].'_'.$item['id']] = $item['module'].(empty($item['language']) ? '' : " [$item[language]]").', '.$item['topic'];
            }
            foreach (Gcms::$module_menus as $key => $values) {
                foreach ($values as $menu => $details) {
                    $result[$key][$key.'_'.$menu] = $details[0];
                }
            }
            // inint module
            foreach ($result as $owner => $values) {
                $class = ucfirst($owner).'\Admin\Init\Model';
                if (class_exists($class) && method_exists($class, 'initMenuwrite')) {
                    $result[$owner] = $class::initMenuwrite($values);
                }
            }
        }
        return $result;
    }

    /**
     * action (menuwrite.php)
     *
     * @param Request $request
     */
    public function action(Request $request)
    {
        // session, referer, member, can_config, ไม่ใช่สมาชิกตัวอย่าง
        if ($request->initSession() && $request->isReferer() && $login = Login::adminAccess()) {
            if (Login::checkPermission($login, 'can_config') && Login::notDemoMode($login)) {
                $ret = array();
                // ค่าที่ส่งมา
                $action = $request->post('action')->toString();
                $parent = $request->post('parent')->toString();
                $id = $request->post('id')->toInt();
                // Database
                $db = $this->db();
                if ($action === 'get' && !empty($parent)) {
                    // query menu
                    $query = $db->createQuery()
                        ->select('id', 'level', 'menu_text', 'menu_tooltip')
                        ->from('menus')
                        ->where(array('parent', $parent))
                        ->order('menu_order');
                    foreach ($query->execute() as $item) {
                        $text = '';
                        for ($i = 0; $i < $item->level; ++$i) {
                            $text .= '&nbsp;&nbsp;';
                        }
                        $ret['O_'.$item->id] = (empty($text) ? '' : $text.'↳&nbsp;').(empty($item->menu_text) ? (empty($item->menu_tooltip) ? '---' : $item->menu_tooltip) : $item->menu_text).(empty($item->language) ? '' : ' ['.$item->language.']');
                    }
                } elseif ($action === 'copy' && !empty($id)) {
                    // สำเนาเมนู
                    $table_menus = $this->getTableName('menus');
                    $menu = $db->first($table_menus, $id);
                    if ($menu->language == '') {
                        $ret['alert'] = Language::get('This entry is displayed in all languages');
                    } else {
                        $lng = strtolower($request->post('lng')->toString());
                        // ตรวจสอบเมนูซ้ำ
                        $search = $db->first($table_menus, array(
                            array('index_id', $menu->index_id),
                            array('parent', $menu->parent),
                            array('level', $menu->level),
                            array('language', $lng)
                        ));
                        if ($search === false) {
                            // ข้อมูลเดิม
                            $old_lng = $menu->language;
                            // แก้ไขรายการเดิมเป็นภาษาใหม่
                            $menu->language = $lng;
                            $db->update($table_menus, $menu->id, $menu);
                            unset($menu->id);
                            // เพิ่มรายการใหม่จากรายการเดิม
                            $menu->language = $old_lng;
                            $db->insert($table_menus, $menu);
                            $ret['alert'] = Language::get('Copy successfully, you can edit this entry');
                        } else {
                            $ret['alert'] = Language::get('This entry is in selected language');
                        }
                    }
                }
                if (!empty($ret)) {
                    // คืนค่าเป็น JSON
                    echo json_encode($ret);
                }
            }
        }
    }

    /**
     * บันทึก
     *
     * @param Request $request
     */
    public function submit(Request $request)
    {
        $ret = array();
        // session, token, member, can_config, ไม่ใช่สมาชิกตัวอย่าง
        if ($request->initSession() && $request->isSafe() && $login = Login::adminAccess()) {
            if (Login::checkPermission($login, 'can_config') && Login::notDemoMode($login)) {
                try {
                    // รับค่าจากการ POST
                    $save = array(
                        'language' => strtolower($request->post('language')->topic()),
                        'menu_text' => $request->post('menu_text')->topic(),
                        'menu_tooltip' => $request->post('menu_tooltip')->topic(),
                        'accesskey' => strtolower($request->post('accesskey')->topic()),
                        'alias' => $request->post('alias')->topic(),
                        'parent' => strtoupper($request->post('parent')->topic()),
                        'published' => $request->post('published')->toInt(),
                        'menu_url' => str_replace(array('&#x007B;', '&#x007D;', WEB_URL), array('{', '}', '{WEBURL}'), $request->post('menu_url')->url()),
                        'menu_target' => $request->post('menu_target')->topic(),
                        'action' => $request->post('action')->toInt()
                    );
                    $id = $request->post('id')->toInt();
                    $type = $request->post('type')->toInt();
                    $toplvl = $request->post('menu_order')->toInt();
                    // owner_action_module_moduleid
                    if ($save['action'] == 1 && preg_match('/^([a-z]+)_([a-z0-9]+)(_([0-9]+))?(_([a-z0-9]+))?$/', $request->post('index_id')->toString(), $match)) {
                        // module Initial
                        $class = ucfirst($match[1]).'\Admin\Init\Model';
                        if (class_exists($class) && method_exists($class, 'parseMenuwrite')) {
                            $save = $class::parseMenuwrite($match, $save);
                        }
                        if (empty($match[4])) {
                            if (isset(Gcms::$module_menus[$match[1]])) {
                                $save['action'] = 2;
                                $save['menu_url'] = Gcms::$module_menus[$match[1]][$match[2]][1];
                                $save['alias'] = $save['alias'] == '' ? Gcms::$module_menus[$match[1]][$match[2]][2] : $save['alias'];
                            }
                        } else {
                            $save['index_id'] = $match[4];
                        }
                    }
                    // Database
                    $db = $this->db();
                    // table
                    $table_menu = $this->getTableName('menus');
                    if (!empty($id)) {
                        $menu = $db->first($table_menu, array('id', $id));
                    } else {
                        $menu = (object) array('id' => 0);
                    }
                    if ($id > 0 && !$menu) {
                        $ret['alert'] = Language::get('Unable to complete the transaction');
                    } else {
                        // accesskey
                        if ($save['accesskey'] != '') {
                            if (!preg_match('/[a-z0-9]{1,1}/', $save['accesskey'])) {
                                $ret['ret_accesskey'] = 'this';
                            }
                        }
                        // menu order (top level)
                        if ($type != 0 && $toplvl == 0) {
                            $ret['ret_menu_order'] = 'this';
                        } elseif ($save['action'] == 1 && $save['index_id'] == 0) {
                            $ret['ret_menu_order'] = 'this';
                        }
                        // menu_url
                        if ($save['action'] == 2 && $save['menu_url'] == '') {
                            $ret['ret_menu_url'] = 'this';
                        }
                        if ($save['action'] != 2) {
                            unset($ret['ret_menu_url']);
                        }
                        if ($save['action'] != 1) {
                            $save['index_id'] = 0;
                        }
                    }
                    unset($save['action']);
                    if (empty($ret)) {
                        if ($type == 0) {
                            // เป็นเมนูลำดับแรกสุด
                            $save['menu_order'] = 1;
                            $save['level'] = 0;
                            $menu_order = 1;
                            $toplvl = 0;
                        } else {
                            $save['level'] = $type - 1;
                            $menu_order = 0;
                        }
                        $top_level = 0;
                        // query menu ทั้งหมด, เรียงลำดับเมนูตามที่กำหนด
                        $query = $db->createQuery()
                            ->select('id', 'level', 'menu_order')
                            ->from('menus')
                            ->where(array('parent', $save['parent']))
                            ->order('menu_order');
                        foreach ($query->toArray()->execute() as $item) {
                            if ($item['id'] != $menu->id) {
                                $changed = false;
                                ++$menu_order;
                                $top_level = $menu_order == 1 ? 0 : min($top_level + 1, $item['level']);
                                if ($menu_order != $item['menu_order']) {
                                    // อัปเดต menu_order
                                    $item['menu_order'] = $menu_order;
                                    $changed = true;
                                }
                                if ($top_level != $item['level']) {
                                    // อัปเดต level
                                    $item['level'] = $top_level;
                                    $changed = true;
                                }
                                if ($changed) {
                                    $db->update($table_menu, $item['id'], $item);
                                }
                                if ($toplvl == $item['id']) {
                                    ++$menu_order;
                                    $save['menu_order'] = $menu_order;
                                    $save['level'] = min($item['level'] + 1, $save['level']);
                                }
                            }
                        }
                        // บันทึก
                        if (empty($id)) {
                            // ใหม่
                            $id = $db->insert($table_menu, $save);
                        } else {
                            // แก้ไข
                            $db->update($table_menu, $id, $save);
                        }
                        // ส่งค่ากลับ
                        $ret['alert'] = Language::get('Saved successfully');
                        $ret['location'] = $request->getUri()->postBack('index.php', array('module' => 'menus', 'id' => null, 'parent' => $save['parent']));
                        // เคลียร์
                        $request->removeToken();
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

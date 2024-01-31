<?php
/**
 * @filesource modules/index/views/editprofile.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Index\Editprofile;

use Gcms\Gcms;
use Gcms\Login;
use Kotchasan\ArrayTool;
use Kotchasan\Http\Request;
use Kotchasan\Language;
use Kotchasan\Template;

/**
 * module=editprofile
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class View extends \Gcms\View
{
    /**
     * หน้าแก้ไขข้อมูลส่วนตัว
     *
     * @param Request $request
     *
     * @return object
     */
    public function render(Request $request)
    {
        if ($login = Login::isMember()) {
            // tab ที่เลือก
            $tab = $request->request('tab')->toString();
            $tab = empty($tab) ? ArrayTool::getFirstKey(Gcms::$member_tabs) : $tab;
            $index = (object) array('description' => self::$cfg->web_description, 'tab' => $tab);
            if (!empty($login['social'])) {
                unset(Gcms::$member_tabs['password']);
            }
            if (isset(Gcms::$member_tabs[$tab])) {
                // topic
                $index->topic = Language::get(Gcms::$member_tabs[$tab][0]);
                // load class
                $index = createClass(Gcms::$member_tabs[$tab][1])->render($request, $index);
                if ($index) {
                    // /member/main.html
                    $template = Template::create('member', 'member', 'main');
                    // รายการ tabs
                    $tabs = array();
                    foreach (Gcms::$member_tabs as $key => $values) {
                        if (!empty($values[0])) {
                            $title = Language::get($values[0]);
                            $class = 'tab '.$key.($key == $index->tab ? ' select' : '');
                            $tabs[] = '<li class="notext '.$class.'"><a'.(isset($values[2]) ? ' class='.$values[2] : '').' title="'.$title.'" href="{WEBURL}index.php?module=editprofile&amp;tab='.$key.'"></a></li>';
                        }
                    }
                    $template->add(array(
                        '/{TAB}/' => implode('', $tabs),
                        '/{DETAIL}/' => $index->detail,
                        '/{TOKEN}/' => $request->createToken()
                    ));
                    $index->detail = $template->render();
                    $index->keywords = $index->topic;
                    // menu
                    $index->menu = 'member';
                    return $index;
                }
            }
        }
        // ไม่ได้ login
        return createClass('Index\Error\Controller')->init('index');
    }
}

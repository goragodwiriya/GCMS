<?php
/**
 * @filesource modules/document/views/replyedit.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Document\Replyedit;

use Gcms\Gcms;
use Kotchasan\Http\Request;
use Kotchasan\Language;
use Kotchasan\Template;

/**
 * แก้ไขความคิดเห็น
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class View extends \Gcms\View
{
    /**
     * แก้ไขความคิดเห็น
     *
     * @param Request $request
     * @param object  $index   ข้อมูลโมดูล
     *
     * @return object
     */
    public function index(Request $request, $index)
    {
        // login
        $login = $request->session('login', array('id' => 0, 'status' => -1, 'email' => '', 'password' => ''))->all();
        // สมาชิก true
        $isMember = $login['status'] > -1;
        // /document/replyedit.html
        $template = Template::create('document', $index->module->module, 'replyedit');
        $template->add(array(
            '/{TOPIC}/' => $index->topic,
            '/{DETAIL}/' => $index->detail,
            '/{MODULEID}/' => $index->module_id,
            '/{TOKEN}/' => $request->createToken(),
            '/{QID}/' => $index->index_id,
            '/{RID}/' => $index->id
        ));
        // breadcrumb ของโมดูล
        if (!Gcms::$menu->isHome($index->module->index_id)) {
            $menu = Gcms::$menu->findTopLevelMenu($index->module->index_id);
            if ($menu) {
                Gcms::$view->addBreadcrumb(Gcms::createUrl($index->module->module), $menu->menu_text, $menu->menu_tooltip);
            } else {
                Gcms::$view->addBreadcrumb(Gcms::createUrl($index->module->module), $index->module->topic, $index->module->description);
            }
        }
        // breadcrumb ของหมวดหมู่
        if (!empty($index->category_id)) {
            $category = Gcms::ser2Str($index->category);
            Gcms::$view->addBreadcrumb(Gcms::createUrl($index->module->module, '', $index->category_id), $category);
        }
        // breadcrumb ของกระทู้
        Gcms::$view->addBreadcrumb(Gcms::createUrl($index->module->module, '', 0, 0, 'id='.$index->index_id), $index->topic);
        // breadcrumb ของหน้า
        $canonical = WEB_URL.'index.php?module='.$index->module->module.'-edit&amp;rid='.$index->id;
        $topic = Language::get('Edit').' '.Language::get('Comment');
        Gcms::$view->addBreadcrumb($canonical, $topic);
        // คืนค่า
        return (object) array(
            'module' => $index->module->module,
            'canonical' => $canonical,
            'topic' => $topic.' - '.$index->topic,
            'detail' => $template->render(),
            'keywords' => $index->topic,
            'description' => $index->topic
        );
    }
}

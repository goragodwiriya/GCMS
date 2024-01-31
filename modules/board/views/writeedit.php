<?php
/**
 * @filesource modules/board/views/writeedit.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Board\Writeedit;

use Gcms\Gcms;
use Gcms\Login;
use Kotchasan\Http\Request;
use Kotchasan\Language;
use Kotchasan\Template;

/**
 * แก้ไขกระทู้
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
        // หมวดหมู่
        $category_options = array();
        foreach (\Index\Category\Model::all($index->module_id) as $item) {
            $categories[$item->category_id] = $item->topic;
            if (Login::isAdmin() || $index->category_id == $item->category_id) {
                $sel = $index->category_id == $item->category_id ? ' selected' : '';
                $category_options[] = '<option value='.$item->category_id.$sel.'>'.$item->topic.'</option>';
            }
        }
        if (empty($category_options)) {
            $category_options[] = '<option value=0>{LNG_Uncategorized}</option>';
        }
        // /board/writeedit.html
        $template = Template::create('board', $index->module->module, 'writeedit');
        $template->add(array(
            '/{TOPIC}/' => $index->topic,
            '/{DETAIL}/' => $index->detail,
            '/{CATEGORIES}/' => implode('', $category_options),
            '/<MODERATOR>(.*)<\/MODERATOR>/s' => Gcms::canConfig($login, $index, 'moderator') ? '$1' : '',
            '/<UPLOAD>(.*)<\/UPLOAD>/s' => empty($index->module->img_upload_type) ? '' : '$1',
            '/{DATE}/' => date('Y-m-d', $index->create_date),
            '/{TIME}/' => date('H:i', $index->create_date),
            '/{MODULEID}/' => $index->module_id,
            '/{TOKEN}/' => $request->createToken(),
            '/{QID}/' => $index->id
        ));
        Gcms::$view->setContentsAfter(array(
            '/:size/' => $index->module->img_upload_size,
            '/:type/' => implode(', ', $index->module->img_upload_type)
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
        if (!empty($index->category)) {
            Gcms::$view->addBreadcrumb(Gcms::createUrl($index->module->module, '', $index->category_id), $index->category);
        }
        // breadcrumb ของกระทู้
        Gcms::$view->addBreadcrumb(Gcms::createUrl($index->module->module, '', 0, 0, 'wbid='.$index->id), $index->topic);
        // breadcrumb ของหน้า
        $canonical = WEB_URL.'index.php?module='.$index->module->module.'-edit&amp;qid='.$index->id;
        $topic = Language::get('Edit').' '.Language::get('Post');
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

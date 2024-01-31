<?php
/**
 * @filesource modules/board/views/view.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Board\Write;

use Gcms\Gcms;
use Kotchasan\Http\Request;
use Kotchasan\Language;
use Kotchasan\Template;

/**
 * ตั้งกระทู้
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
            if (empty($index->category_id) || $index->category_id == $item->category_id) {
                $category_options[] = '<option value='.$item->category_id.'>'.$item->topic.'</option>';
            }
        }
        if (empty($category_options)) {
            $category_options[] = '<option value=0>{LNG_Uncategorized}</option>';
        }
        // เปิดใช้งานการส่งข้อความ LINE
        $line = !empty(self::$cfg->line_official_account) && !empty(self::$cfg->line_channel_access_token);
        // /board/write.html
        $template = Template::create('board', $index->module, 'write');
        $template->add(array(
            '/{TOPIC}/' => $index->topic,
            '/{CATEGORIES}/' => implode('', $category_options),
            '/<MEMBER>(.*)<\/MEMBER>/s' => $isMember ? '' : '\\1',
            '/<UPLOAD>(.*)<\/UPLOAD>/s' => empty($index->img_upload_type) ? '' : '\\1',
            '/<LINE>(.*)<\/LINE>/s' => $line ? '\\1' : '',
            '/{MODULEID}/' => $index->module_id,
            '/{TOKEN}/' => $request->createToken(),
            '/{LOGIN_EMAIL}/' => $login['email'],
            '/{ICON}/' => Gcms::usernameIcon(),
            '/{PLACEHOLDER}/' => Gcms::getLoginPlaceholder()
        ));
        Gcms::$view->setContentsAfter(array(
            '/:size/' => $index->img_upload_size,
            '/:type/' => implode(', ', $index->img_upload_type)
        ));
        // breadcrumb ของโมดูล
        if (!Gcms::$menu->isHome($index->index_id)) {
            $menu = Gcms::$menu->findTopLevelMenu($index->index_id);
            if ($menu) {
                Gcms::$view->addBreadcrumb(Gcms::createUrl($index->module), $menu->menu_text, $menu->menu_tooltip);
            } else {
                Gcms::$view->addBreadcrumb(Gcms::createUrl($index->module), $index->topic, $index->description);
            }
        }
        // breadcrumb ของหมวดหมู่
        if (!empty($index->category)) {
            Gcms::$view->addBreadcrumb(Gcms::createUrl($index->module, '', $index->category_id), $index->category);
        }
        $canonical = WEB_URL.'index.php?module='.$index->module.'-write';
        $topic = Language::get('Create topic');
        Gcms::$view->addBreadcrumb($canonical, $topic);
        // คืนค่า
        return (object) array(
            'module' => $index->module,
            'canonical' => $canonical,
            'topic' => $topic.' - '.$index->topic,
            'detail' => $template->render(),
            'keywords' => $index->topic,
            'description' => $index->topic
        );
    }
}

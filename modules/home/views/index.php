<?php
/**
 * @filesource modules/home/views/index.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Home\Index;

use Kotchasan\Template;
use Kotchasan\Text;
use Web\Gcms;

/**
 * หน้าเพจจากโมดูล index
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class View extends \Web\View
{
    /**
     * แสดงผล
     *
     * @param object $index ข้อมูลโมดูล
     */
    public function render($index)
    {
        // get detail
        $detail = \Index\Index\Model::getDetail($index->index_id, $index->module_id);
        // home.html
        $template = Template::create('home', $index->module, 'home');
        // canonical
        if (Gcms::$menu->isHomeMenu($index->index_id)) {
            $index->canonical = WEB_URL.'index.php';
        } else {
            $index->canonical = Gcms::createUrl($index->module);
            // breadcrumb ของหน้า
            Gcms::$view->addBreadcrumb($index->canonical, $index->topic, $index->description);
        }
        // add template
        $template->add([
            // content
            '/{CONTENT}/' => Gcms::$widget->get('content'),
            '/{DETAIL}/' => Gcms::highlighter($detail),
            // topic, description
            '/{TOPIC}/' => Text::htmlspecialchars($index->topic),
            '/{DESCRIPTION}/' => Text::htmlspecialchars($index->description),
            // Module name
            '/{MODULE}/' => $index->module
        ]);
        // detail
        $index->detail = $template->render();
        // JSON-LD (Index)
        Gcms::$view->setJsonLd(\Index\Jsonld\View::webpage($index));
        // คืนค่า
        return $index;
    }
}

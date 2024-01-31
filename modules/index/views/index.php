<?php
/**
 * @filesource modules/index/views/index.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Index\Index;

use Gcms\Gcms;
use Kotchasan\Template;

/**
 * หน้าเพจจากโมดูล index
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class View extends \Gcms\View
{
    /**
     * แสดงผล
     *
     * @param object $index ข้อมูลโมดูล
     */
    public function render($index)
    {
        // template main.html, home/main.html
        $template = Template::create('', $index->module, 'main');
        // canonical
        if (Gcms::$menu->isHome($index->index_id)) {
            $index->canonical = WEB_URL.'index.php';
        } else {
            $index->canonical = Gcms::createUrl($index->module);
            // breadcrumb ของหน้า
            Gcms::$view->addBreadcrumb($index->canonical, $index->topic, $index->description);
        }
        // add template
        $template->add(array(
            // content
            '/{DETAIL}/' => Gcms::showDetail(str_replace(array('&#x007B;', '&#x007D;'), array('{', '}'), $index->detail), true, false),
            // topic
            '/{TOPIC}/' => $index->topic,
            // Module name
            '/{MODULE}/' => $index->module,
            // ผู้ควบคุมข้อมูล
            '/%DATA_CONTROLLER%/' => isset(self::$cfg->data_controller) ? self::$cfg->data_controller : ''
        ));
        // detail
        $index->detail = $template->render();
        // JSON-LD (Index)
        Gcms::$view->setJsonLd(\Index\Jsonld\View::webpage($index));
        // คืนค่า
        return $index;
    }
}

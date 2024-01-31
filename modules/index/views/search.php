<?php
/**
 * @filesource modules/index/views/search.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Index\Search;

use Gcms\Gcms;
use Kotchasan\Grid;
use Kotchasan\Language;
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
        // /search/searchitem.html
        $listitem = Grid::create('search', 'search', 'searchitem');
        // รายการ
        foreach ($index->items as $item) {
            if ($item->index == 1) {
                // หน้าหลักโมดูล
                $item->url = WEB_URL.'index.php?module='.$item->module;
            } else {
                // รายการ
                $item->url = WEB_URL.'index.php?module='.$item->module.'&amp;id='.$item->id;
            }
            $listitem->add(array(
                '/{URL}/' => $item->url,
                '/{TOPIC}/' => $item->topic,
                '/{LINK}/' => $item->url,
                '/{DETAIL}/' => Gcms::html2txt($item->description, 149)
            ));
        }
        // /search/search.html
        $template = Template::create('search', 'search', 'search');
        // canonical
        $index->canonical = Gcms::createUrl($index->module, '', 0, 0, 'q='.rawurlencode($index->q));
        // current URL
        $uri = \Kotchasan\Http\Uri::createFromUri($index->canonical);
        if ($index->total > 0) {
            $list = Gcms::highlightSearch($listitem->render(), $index->q);
        } else {
            $list = $index->q == '' ? '' : '<div>'.Language::get('No results were found for').' <strong>'.$index->q.'</strong></div>';
            $list .= '<div><strong>'.Language::get('Search tips').' :</strong>'.Language::get('EMPTY_SEARCH_MESSAGE').'</div>';
        }
        // add template
        $template->add(array(
            '/{LIST}/' => $list,
            '/{SPLITPAGE}/' => $uri->pagination($index->totalpage, $index->page),
            '/{SEARCH}/' => $index->q,
            '/{MODULE}/' => 'search',
            '/{RESULT}/' => $index->total == 0 ? '' : sprintf(Language::get('Search results <strong>%d - %d</strong> of about <strong>%d</strong> for <strong>%s</strong> (%s sec)'), $index->start + 1, $index->end, $index->total, $index->q, number_format(microtime(true) - REQUEST_TIME, 4))
        ));
        $search = Language::get('Search');
        $index->detail = $template->render();
        $index->topic = ($index->q == '' ? '' : $index->q.' - ').$search;
        $index->description = $index->topic;
        $index->keywords = $index->topic;
        // JSON-LD สำหรับหน้าค้นหา
        Gcms::$view->setJsonLd(\Index\Jsonld\View::search($index));
        // breadcrumb ของหน้า
        Gcms::$view->addBreadcrumb($index->canonical, $search, $search);
        return $index;
    }
}

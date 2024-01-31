<?php
/**
 * @filesource modules/product/views/stories.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Product\Stories;

use Gcms\Gcms;
use Kotchasan\Currency;
use Kotchasan\Grid;
use Kotchasan\Http\Request;
use Kotchasan\Language;
use Kotchasan\Template;
use Product\Index\Controller;

/**
 * แสดงรายการบทความ
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class View extends \Gcms\View
{
    /**
     * แสดงรายการบทความ
     *
     * @param Request $request
     * @param object  $index   ข้อมูลโมดูล
     *
     * @return object
     */
    public function index(Request $request, $index)
    {
        // listitem.html
        $listitem = Grid::create('product', $index->module, 'listitem');
        // ลิสต์รายการ
        foreach ($index->items as $item) {
            if (!empty($item->picture) && is_file(ROOT_PATH.DATA_FOLDER.'product/'.$item->picture)) {
                $thumb = WEB_URL.DATA_FOLDER.'product/thumb_'.$item->picture;
                $picture = WEB_URL.DATA_FOLDER.'product/'.$item->picture;
            } else {
                $thumb = WEB_URL.'skin/'.self::$cfg->skin.'/product/img/nopicture.png';
                $picture = WEB_URL.'skin/'.self::$cfg->skin.'/product/img/nopicture.png';
            }
            $listitem->add(array(
                '/{ID}/' => $item->id,
                '/{THUMBNAIL}/' => $thumb,
                '/{PICTURE}/' => $picture,
                '/{URL}/' => Controller::url($index->module, $item->alias, $item->id),
                '/{TOPIC}/' => $item->topic,
                '/{SHOWPRICE}/' => empty($item->price[$index->currency_unit]) ? 'hidden' : 'price',
                '/{PRICE}/' => empty($item->price[$index->currency_unit]) ? '' : Currency::format($item->price[$index->currency_unit]),
                '/{NET}/' => empty($item->net[$index->currency_unit]) ? '{LNG_Contact information}' : Currency::format($item->net[$index->currency_unit]),
                '/{VISITED}/' => number_format($item->visited),
                '/{DESCRIPTION}/' => $item->description
            ));
        }
        // breadcrumb ของโมดูล
        if (Gcms::$menu->isHome($index->index_id)) {
            $index->canonical = WEB_URL.'index.php';
        } else {
            $index->canonical = Gcms::createUrl($index->module);
            $menu = Gcms::$menu->findTopLevelMenu($index->index_id);
            if ($menu) {
                Gcms::$view->addBreadcrumb($index->canonical, $menu->menu_text, $menu->menu_tooltip);
            }
        }
        // current URL
        $uri = \Kotchasan\Http\Uri::createFromUri($index->canonical);
        // list.html หรือ empty.html ถ้าไม่มีข้อมูล
        $template = Template::create('product', $index->module, $listitem->hasItem() ? 'list' : 'empty');
        // สกุลเงิน
        $currency_units = Language::get('CURRENCY_UNITS');
        $template->add(array(
            '/{TOPIC}/' => $index->topic,
            '/{DETAIL}/' => $index->detail,
            '/{LIST}/' => $listitem->render(),
            '/{COLS}/' => $index->cols,
            '/{CURRENCYUNIT}/' => $currency_units[$index->currency_unit],
            '/{SPLITPAGE}/' => $uri->pagination($index->totalpage, $index->page),
            '/{MODULE}/' => $index->module
        ));
        // คืนค่า
        return (object) array(
            'canonical' => $index->canonical,
            'module' => $index->module,
            'topic' => $index->topic,
            'description' => $index->description,
            'keywords' => $index->keywords,
            'detail' => $template->render()
        );
    }
}

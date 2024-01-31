<?php
/**
 * @filesource modules/product/views/amp.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Product\Amp;

use Gcms\Gcms;
use Kotchasan\Currency;
use Kotchasan\Date;
use Kotchasan\Http\Request;
use Kotchasan\Language;
use Kotchasan\Template;

/**
 * แสดงหน้าสำหรับ Amp
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class View extends \Gcms\View
{
    /**
     * แสดงหน้าสำหรับ Amp
     *
     * @param Request $request
     * @param object  $index   ข้อมูลโมดูล
     *
     * @return object
     */
    public function index(Request $request, $index)
    {
        // ค่าที่ส่งมา
        $index->id = $request->get('id')->toInt();
        $index->alias = $request->get('alias')->text();
        // อ่านรายการที่เลือก
        $index = \Product\View\Model::get($index);
        if ($index && $index->published) {
            // รูปภาพ Thumbnail
            $dir = DATA_FOLDER.'product/';
            $imagedir = ROOT_PATH.$dir;
            if (!empty($index->picture) && is_file($imagedir.$index->picture)) {
                $index->image = WEB_URL.$dir.$index->picture;
            }
            // URL ของหน้า
            $index->canonical = \Product\Index\Controller::url($index->module, $index->alias, $index->id, false);
            // เนื้อหา
            $index->detail = Gcms::showDetail(str_replace(array('&#x007B;', '&#x007D;'), array('{', '}'), $index->detail), false, true);
            // JSON-LD
            Gcms::$view->setJsonLd(\Product\Jsonld\View::generate($index));
            // สกุลเงิน
            $currency_units = Language::get('CURRENCY_UNITS');
            // คืนค่า
            return (object) array(
                // /product/amp.html
                'content' => Template::create('product', $index->module, 'amp')->render(),
                'canonical' => $index->canonical,
                'topic' => $index->topic,
                'detail' => $index->detail,
                'date' => Date::format($index->last_update),
                'visited' => number_format($index->visited),
                'picture' => isset($index->image) ? $index->image : '',
                'showprice' => empty($index->price[$index->currency_unit]) ? 'hidden' : 'price',
                'price' => empty($index->price[$index->currency_unit]) ? '' : Currency::format($index->price[$index->currency_unit]),
                'net' => empty($index->net[$index->currency_unit]) ? '{LNG_Contact information}' : Currency::format($index->net[$index->currency_unit]),
                'currencyunit' => $currency_units[$index->currency_unit]
            );
        }
        // 404
        return createClass('Index\Error\Controller')->init('product');
    }
}

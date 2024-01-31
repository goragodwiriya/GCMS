<?php
/**
 * @filesource modules/documentation/views/amp.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Documentation\Amp;

use Gcms\Gcms;
use Kotchasan\Http\Request;
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
        // อ่านรายการที่เลือก
        $index = \Documentation\View\Model::get($index);
        if ($index && $index->published) {
            // URL ของหน้า
            $index->canonical = \Documentation\Index\Controller::url($index->module, $index->alias, $index->id);
            // เนื้อหา
            $index->detail = Gcms::showDetail(str_replace(array('&#x007B;', '&#x007D;'), array('{', '}'), $index->detail), true, true);
            // JSON-LD
            Gcms::$view->setJsonLd(\Documentation\Jsonld\View::generate($index));
            // คืนค่า
            return (object) array(
                // /documentation/amp.html
                'content' => Template::create('documentation', $index->module, 'amp')->render(),
                'canonical' => $index->canonical,
                'topic' => $index->topic,
                'detail' => $index->detail
            );
        }
        // 404
        return createClass('Index\Error\Controller')->init('document');
    }
}

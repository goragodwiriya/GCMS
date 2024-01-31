<?php
/**
 * @filesource modules/product/controllers/feed.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Product\Feed;

use Kotchasan\Http\Request;
use Kotchasan\Text;
use Product\Index\Controller as Module;

/**
 * RSS Feed
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Controller extends \Kotchasan\Controller
{
    /**
     * RSS Feed
     *
     * @param Request $request
     * @param object  $index   ข้อมูลโมดูล
     * @param int     $count   จำนวนที่ต้องการ
     * @param string  $today   วันที่วันนี้ รูปแบบ Y-m-d
     *
     * @return string
     */
    public function init(Request $request, $index, $count, $today)
    {
        $xml = '';
        foreach (\Product\Feed\Model::getStories($request, $index, $count, $today) as $item) {
            $link = Module::url($index->module, $item->alias, $item->id);
            $xml .= '<item>';
            $xml .= '<title>'.$item->topic.'</title>';
            $xml .= '<link>'.$link.'</link>';
            $xml .= '<description><![CDATA['.Text::cut($item->description, 50).']]></description>';
            if ($item->picture != '' && is_file(ROOT_PATH.DATA_FOLDER.'product/thumb_'.$item->picture)) {
                $xml .= '<enclosure url="'.WEB_URL.DATA_FOLDER.'product/thumb_'.$item->picture.'" type="image/jpeg"></enclosure>';
            }
            $xml .= '<guid isPermaLink="true">'.$link.'</guid>';
            $xml .= '<pubDate>'.date('D, d M Y H:i:s +0700', $item->last_update).'</pubDate>';
            $xml .= '</item>';
        }
        // คืนค่า XML
        return $xml;
    }
}

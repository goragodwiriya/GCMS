<?php
/**
 * @filesource modules/board/controllers/feed.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Board\Feed;

use Board\Index\Controller as Module;
use Gcms\Gcms;
use Kotchasan\Http\Request;

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
     *
     * @return string
     */
    public function init(Request $request, $index, $count)
    {
        $xml = '';
        foreach (\Board\Feed\Model::getStories($request, $index, $count) as $item) {
            $link = Module::url($index->module, 0, $item->id);
            $xml .= '<item>';
            $xml .= '<title>'.$item->topic.'</title>';
            $xml .= '<link>'.$link.'</link>';
            $xml .= '<description><![CDATA['.Gcms::html2txt($item->detail, 50).']]></description>';
            if ($item->picture != '' && is_file(ROOT_PATH.DATA_FOLDER.'board/thumb-'.$item->picture)) {
                $xml .= '<enclosure url="'.WEB_URL.DATA_FOLDER.'board/thumb-'.$item->picture.'" type="image/jpeg"></enclosure>';
            }
            $xml .= '<guid isPermaLink="true">'.$link.'</guid>';
            $xml .= '<pubDate>'.date('D, d M Y H:i:s +0700', $item->last_update).'</pubDate>';
            $xml .= '</item>';
        }
        return $xml;
    }
}

<?php
/**
 * @filesource modules/video/controllers/view.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Video\View;

use Kotchasan\Http\Request;

/**
 * Controller หลัก สำหรับแสดง frontend ของ GCMS
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Controller extends \Kotchasan\Controller
{
    /**
     * Controller หลักของโมดูล ใช้เพื่อตรวจสอบว่าจะเรียกหน้าไหนมาแสดงผล
     *
     * @param Request $request
     *
     * @return object
     */
    public function modal(Request $request)
    {
        if (defined('MAIN_INIT') && preg_match('/^youtube_([0-9]+)_([a-zA-Z0-9\-_]{11,11})$/', $request->post('id')->toString(), $match)) {
            // ตรวจสอบวีดีโอที่เลือก
            $mv = \Video\View\Model::get($match[1]);
            if ($mv) {
                // get video info
                $url = 'https://www.googleapis.com/youtube/v3/videos?part=statistics&id='.$mv->youtube.(empty($mv->google_api_key) ? '' : '&key='.$mv->google_api_key);
                if (function_exists('curl_init') && $ch = @curl_init()) {
                    curl_setopt($ch, CURLOPT_URL, $url);
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                    $feed = curl_exec($ch);
                    curl_close($ch);
                } else {
                    $feed = file_get_contents($url);
                }
                if ($feed != '') {
                    $datas = json_decode($feed);
                    if (isset($datas->{'items'})) {
                        $items = $datas->{'items'};
                        if (count($items) == 1) {
                            $viewCount = (int) $items[0]->{'statistics'}->{'viewCount'};
                            if ($viewCount != $mv->views) {
                                \Video\View\Model::updateView($mv->id, $viewCount);
                            }
                        }
                    }
                }
                echo '<figure class=mv>';
                echo '<div class=youtube><iframe width=560 height=315 src="//www.youtube.com/embed/'.$mv->youtube.'?wmode=transparent&amp;autoplay=true" frameborder=0></iframe></div>';
                echo '<figcaption>'.$mv->topic.'</figcaption>';
                echo '</figure>';
            }
        }
    }
}

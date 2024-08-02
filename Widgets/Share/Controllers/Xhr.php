<?php
/**
 * @filesource Widgets/Share/Controllers/Xhr.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Widgets\Share\Controllers;

use Kotchasan\Http\Request;

/**
 * get facebook share count (Ajax called)
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Xhr extends \Kotchasan\Controller
{
    /**
     * get facebook share count
     *
     * @param Request $request
     */
    public function get(Request $request)
    {
        if (defined('MAIN_INIT')) {
            $url = 'https://graph.facebook.com/'.rawurlencode($request->get('url')->url());
            if (function_exists('curl_init') && $curl = @curl_init()) {
                curl_setopt($curl, CURLOPT_URL, $url);
                curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 5);
                curl_setopt($curl, CURLOPT_TIMEOUT, 5);
                $result = curl_exec($curl);
                curl_close($curl);
            } else {
                $result = @file_get_contents($url);
            }
            $result = (array) json_decode($result);
            echo isset($result['share']) && isset($result['share']->share_count) ? number_format($result['share']->share_count) : 0;
        }
    }
}

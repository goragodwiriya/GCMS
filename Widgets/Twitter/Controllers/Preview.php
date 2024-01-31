<?php
/**
 * @filesource Widgets/Twitter/Controllers/Index.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Widgets\Twitter\Controllers;

use Kotchasan\Http\Request;

/**
 * Controller หลัก สำหรับแสดงผล Widget
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Preview extends \Kotchasan\Controller
{
    /**
     * หน้าเว็บ Twitter
     *
     * @param Request $request
     */
    public function index(Request $request)
    {
        if (empty(self::$cfg->twitter)) {
            self::$cfg->twitter = \Widgets\Twitter\Models\Settings::defaultSettings();
        }
        $query_string = array();
        foreach (self::$cfg->twitter as $key => $value) {
            $query_string[$key] = $request->get($key, $value)->toString();
        }
        if (!empty($query_string)) {
            // หน้าเว็บ Facebook
            echo \Widgets\Twitter\Views\Preview::render($query_string);
        }
    }
}

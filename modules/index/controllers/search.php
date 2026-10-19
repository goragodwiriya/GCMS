<?php
/**
 * @filesource modules/index/controllers/search.php
 *
 * @copyright 2026 Goragod.com
 * @license https://www.kotchasan.com/license/
 */

namespace Index\Search;

use Kotchasan\Http\Request;

/**
 * Site-wide search (merged modular providers, Google-style results page).
 *
 * Routed from {@see \Index\Module\Controller::checkModuleCalled()} when module=search.
 *
 * @since 1.0
 */
class Controller extends \Web\Controller
{
    /**
     * @param Request $request
     * @param object  $index Stub module object from router
     *
     * @return object
     */
    public function init(Request $request, $index)
    {
        return View::create()->render($request);
    }
}

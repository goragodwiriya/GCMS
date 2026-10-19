<?php
/**
 * @filesource modules/portfolio/controllers/index.php
 *
 * @copyright 2026 Goragod.com
 * @license https://www.kotchasan.com/license/
 */

namespace Portfolio\Index;

use Kotchasan\Http\Request;

/**
 * Public portfolio front controller — branches between the single-item
 * view and the listing, same shape as Document\Index\Controller::init().
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Controller extends \Web\Controller
{
    /**
     * @param Request $request
     * @param object  $index
     *
     * @return object
     */
    public function init(Request $request, $index)
    {
        if (MAIN_INIT === 'indexhtml') {
            if ($request->get('id')->exists()) {
                $data = \Portfolio\View\Model::get($request, $index);
                if ($data !== null) {
                    return \Portfolio\View\View::create()->render($data);
                }
            } else {
                $data = \Portfolio\Lists\Model::get($request, $index);
                return \Portfolio\Lists\View::create()->render($request, $data);
            }
        }

        return \Index\Error\Controller::create()->init('portfolio');
    }

    /**
     * URL to a single portfolio item.
     *
     * @param string $module
     * @param int    $id
     * @param string $tag
     *
     * @return string
     */
    public static function url($module, $id, $tag = '')
    {
        $params = [];
        if ($id > 0) {
            $params['id'] = $id;
        }
        if ($tag !== '') {
            $params['tag'] = $tag;
        }
        return \Web\Gcms::createUrl($module, '', 0, 0, $params);
    }
}

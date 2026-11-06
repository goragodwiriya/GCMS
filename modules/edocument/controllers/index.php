<?php
/**
 * @filesource modules/edocument/controllers/index.php
 *
 * @copyright 2026 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Edocument\Index;

use Kotchasan\ArrayTool;
use Kotchasan\Http\Request;

/**
 * Frontend Controller for E-Document module
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Controller extends \Kotchasan\Controller
{
    /**
     * Main controller for the module
     *
     * @param Request $request
     * @param object  $index
     *
     * @return object
     */
    public function init(Request $request, $index)
    {
        if (MAIN_INIT === 'indexhtml') {
            $index = \Index\Module\Model::getModuleDetails($index);
            if ($index) {
                $index->config = \Edocument\Settings\Model::normalizeConfig($index->config);

                $page = max(1, $request->get('page')->toInt());
                $pagination = \Edocument\Index\Model::paginate($index->module_id, $page, (int) $index->config->list_per_page);
                $index = ArrayTool::replace($index, $pagination);

                return \Edocument\Index\View::create()->render($request, $index);
            }
        }

        return \Index\Error\Controller::create()->init('edocument');
    }
}

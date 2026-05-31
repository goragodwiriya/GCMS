<?php
/**
 * @filesource modules/board/controllers/index.php
 *
 * @copyright 2026 Goragod.com
 * @license https://www.kotchasan.com/license/
 */

namespace Board\Index;

use Kotchasan\ArrayTool;
use Kotchasan\Http\Request;
use Web\Gcms;

/**
 * Frontend Controller for Board module
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
     * @param object  $index   Module data
     *
     * @return object
     */
    public function init(Request $request, $index)
    {
        $index->id = $request->get('wbid')->toInt();
        $index->category_id = $request->get('cat')->filter('0-9,');
        if ($index->id > 0) {
            // Single topic view
            $data = \Board\View\Model::get($index);
            if ($data !== null) {
                return \Board\View\View::create()->render($data);
            }
        } elseif (MAIN_INIT === 'indexhtml') {
            // normalize category_id to array of ints and remove zeros
            $index->category_id = array_values(array_filter(array_map('intval', explode(',', $index->category_id))));
            // Get categories
            $index->categories = \Web\Category::create($index->module_id);

            if (!empty($index->category_id) || empty($index->categories) || empty($index->config->category_display)) {
                // Select category or no category? or turn off category display Show a list of articles
                $page = max(1, $request->get('page')->toInt());
                $listModel = \Board\Stories\Model::create($index);
                $pagination = $listModel->paginate($page, $index->config->list_per_page);
                $index = ArrayTool::replace($index, $pagination);
                // Article listing view
                return \Board\Stories\View::create()->render($index);
            } else {
                // Category listing view
                return \Board\Categories\View::create()->render($request, $index);
            }
        }
        // Not found
        return \Index\Error\Controller::create()->init('document');
    }

    /**
     * URL generation function
     *
     * @param string $module Module name
     * @param string|array $category Category ID
     * @param int    $id     ID of the topic
     *
     * @return string
     */
    public static function url($module, $category = '', $id = 0)
    {
        $params = [];
        if (!empty($category)) {
            if (is_array($category)) {
                $category = implode(',', $category);
            }
            $params['cat'] = $category;
        }
        if (!empty($id)) {
            $params['wbid'] = $id;
        }
        return Gcms::createUrl($module, '', 0, 0, http_build_query($params));
    }
}

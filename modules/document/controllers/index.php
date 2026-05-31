<?php
/**
 * @filesource modules/document/controllers/index.php
 *
 * @copyright 2026 Goragod.com
 * @license https://www.kotchasan.com/license/
 */

namespace Document\Index;

use Kotchasan\ArrayTool;
use Kotchasan\Http\Request;
use Web\Gcms;

/**
 * Frontend Controller for Document module
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
        $index->alias = $request->get('alias')->topic();
        $index->id = $request->get('id')->toInt();
        $index->category_id = $request->get('cat')->filter('0-9,');
        if ($index->alias !== '') {
            if (preg_match('/^[0-9,]+$/', $index->alias)) {
                // normalize to array of ints and remove zeros
                $index->category_id = $index->alias;
                $index->alias = '';
            }
        }

        if ($index->alias !== '' || $index->id > 0) {
            // Single article view
            $data = \Document\View\Model::get($index);
            if ($data !== null) {
                return \Document\View\View::create()->render($data);
            }
        } elseif (MAIN_INIT === 'indexhtml') {
            // normalize category_id to array of ints and remove zeros
            $index->category_id = array_values(array_filter(array_map('intval', explode(',', $index->category_id))));
            // Get categories
            $index->categories = \Web\Category::create($index->module_id);

            if (!empty($index->category_id) || empty($index->categories) || empty($index->config->category_display)) {
                // Select category or no category? or turn off category display Show a list of articles
                $page = max(1, $request->get('page')->toInt());
                $limit = $index->config->cols * $index->config->rows;
                $listModel = \Document\Stories\Model::create($index);
                $pagination = $listModel->paginate($page, $limit);
                $index = ArrayTool::replace($index, $pagination);
                // Article listing view
                return \Document\Stories\View::create()->render($index);
            } else {
                // Category listing view
                return \Document\Categories\View::create()->render($request, $index);
            }
        }

        // Not found
        return \Index\Error\Controller::create()->init('document');
    }

    /**
     * URL generation for document module
     *
     * @param string $module Module name
     * @param string|array $aliasOrCategory  alias of the article or id of the category (Categories are numbers separated by commas.)
     * @param int    $id     ID of the article if there is no alias
     * @param bool   $encode (option) true=encode with rawurlencode (default true)
     * @param string $query
     *
     * @return string
     */
    public static function url($module, $aliasOrCategory = '', $id = 0, $encode = true, $query = '')
    {
        if (is_array($aliasOrCategory)) {
            $aliasOrCategory = implode(',', $aliasOrCategory);
        }
        if ($aliasOrCategory !== '') {
            return Gcms::createUrl($module, $aliasOrCategory, 0, 0, $query, $encode);
        } else {
            return Gcms::createUrl($module, '', 0, $id, $query, $encode);
        }
    }
}

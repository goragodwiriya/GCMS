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
                // A lone number that is no category of this module is an
                // article id. Articles saved before an alias became mandatory
                // still have none, and Gcms::createUrl then has nothing to put
                // in {document} and builds {module}/{id} for them — which the
                // branch above reads back as a category, so the article
                // rendered as an empty listing and could not be reached at all
                // under the pretty-URL scheme. Categories keep priority, so a
                // number that IS a category behaves exactly as before.
                if ($index->id === 0
                    && preg_match('/^[0-9]+$/', $index->category_id)
                    && \Web\Category::create($index->module_id)->get('category', (int) $index->category_id) === null
                ) {
                    $index->id = (int) $index->category_id;
                    $index->category_id = '';
                }
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

            if (!empty($index->category_id) || $index->categories->isEmpty() || empty($index->category_display)) {
                // Select category or no category? or turn off category display Show a list of articles
                $page = max(1, $request->get('page')->toInt());
                $limit = $index->cols * $index->rows;
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
     * URL generation function
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

        if (self::$cfg->module_url == 1) {
            return Gcms::createUrl($module, $aliasOrCategory, 0, 0, $query, $encode);
        } else {
            return Gcms::createUrl($module, '', 0, $id, $query, $encode);
        }

        if ($aliasOrCategory === '' || $aliasOrCategory === null) {
            return Gcms::createUrl($module, '', 0, $id, $query, $encode);
        } elseif (preg_match('/^[0-9,]+$/', $aliasOrCategory)) {
            return Gcms::createUrl($module, '', $aliasOrCategory, 0, $query, $encode);
        } else {
            return Gcms::createUrl($module, $aliasOrCategory, 0, 0, $query, $encode);
        }
    }
}

<?php
/**
 * @filesource modules/download/controllers/index.php
 *
 * @copyright 2026 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Download\Index;

use Kotchasan\ArrayTool;
use Kotchasan\Http\Request;
use Web\Gcms;

/**
 * Frontend Controller for Download module
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
                $index->config = \Download\Settings\Model::normalizeConfig($index->config);

                $index->category_id = $request->get('cat')->filter('0-9,');
                if ($index->category_id === '') {
                    $index->category_id = [];
                } else {
                    $index->category_id = array_values(array_filter(array_map('intval', explode(',', $index->category_id))));
                }

                $index->categories = \Web\Category::create($index->module_id);

                $page = max(1, $request->get('page')->toInt());
                $limit = (int) ($index->list_per_page ?? 20);
                $limit = max(1, min(100, $limit));

                $listModel = \Download\Index\Model::create($index);
                $pagination = $listModel->paginate($page, $limit);
                $index = ArrayTool::replace($index, $pagination);

                return \Download\Index\View::create()->render($request, $index);
            }
        }

        return \Index\Error\Controller::create()->init('download');
    }

    /**
     * URL generation for download module
     *
     * @param string $module
     * @param string|array $category
     * @param int $id
     * @param string $query
     * @param bool $encode
     *
     * @return string
     */
    public static function url($module, $category = '', $id = 0, $query = '', $encode = true)
    {
        $params = [];
        if (!empty($category)) {
            if (is_array($category)) {
                $category = implode(',', $category);
            }
            $params['cat'] = $category;
        }
        if ($id > 0) {
            $params['id'] = $id;
        }

        if (!empty($query)) {
            parse_str($query, $extra);
            $params = array_merge($params, is_array($extra) ? $extra : []);
        }

        $queryString = http_build_query($params);
        return Gcms::createUrl($module, '', 0, 0, $queryString, $encode);
    }
}

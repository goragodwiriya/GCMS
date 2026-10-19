<?php
/**
 * @filesource modules/product/controllers/category.php
 *
 * @copyright 2026 Goragod.com
 * @license https://www.kotchasan.com/license/
 */

namespace Product\Category;

use Gcms\Api as ApiController;
use Kotchasan\Http\Request;
use Kotchasan\Text;

/**
 * API Product Category Controller
 *
 * Uses the framework editable-rows grid contract (matches modules/personnel/category).
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Controller extends ApiController
{
    /**
     * GET /api/product/category/get
     *
     * @param Request $request
     *
     * @return mixed
     */
    public function get(Request $request)
    {
        try {
            ApiController::validateMethod($request, 'GET');

            $login = $this->authenticateRequest($request);
            if (!$login || !ApiController::isAdmin($login)) {
                return $this->errorResponse('Unauthorized', 401);
            }

            $module_id = $request->get('module_id')->toInt();
            $module = \Index\Module\Model::getModuleWithConfig('product', $module_id);
            if (!$module) {
                return $this->redirectResponse('/404', 'No data available', 404);
            }

            return $this->successResponse([
                'data' => [
                    'type' => \Product\Category\Model::$type,
                    'module_id' => $module_id,
                    'options' => [
                        'columns' => \Product\Category\Model::getColumns(),
                        'data' => \Product\Category\Model::get($module_id)
                    ]
                ]
            ], 'Product categories retrieved');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), $e->getCode() ?: 500, $e);
        }
    }

    /**
     * POST /api/product/category/save
     *
     * Receives parallel arrays id[], th[], en[] from the editable grid.
     *
     * @param Request $request
     *
     * @return mixed
     */
    public function save(Request $request)
    {
        try {
            ApiController::validateMethod($request, 'POST');
            $this->validateCsrfToken($request);

            $login = $this->authenticateRequest($request);
            if (!$login) {
                return $this->redirectResponse('/login', 'Unauthorized', 401);
            }
            if (!ApiController::canModify($login)) {
                return $this->errorResponse('Permission required', 403);
            }

            $module_id = $request->post('module_id')->toInt();
            $module = \Index\Module\Model::getModuleWithConfig('product', $module_id);
            if (!$module) {
                return $this->errorResponse('No data available', 404);
            }

            $ids = $request->post('id', [])->topic();
            $langValues = [];
            foreach (\Product\Category\Model::$languages as $lng) {
                $langValues[$lng] = $request->post($lng, [])->topic();
            }

            $error = [];
            $save = [];
            foreach ($ids as $key => $id) {
                $category_id = Text::topic($id);
                if ($category_id === '') {
                    $error['category_id_'.$key] = 'Category ID is required';
                    continue;
                }
                if (isset($save[$category_id])) {
                    $error['category_id_'.$key] = 'Category ID '.$category_id.' already exists';
                    continue;
                }
                $topics = [];
                foreach (\Product\Category\Model::$languages as $lng) {
                    $topic = Text::topic($langValues[$lng][$key] ?? '');
                    if ($topic !== '') {
                        $topics[$lng] = $topic;
                    }
                }
                if (empty($topics)) {
                    $error['category_'.\Product\Category\Model::$languages[0].'_'.$key] = 'Please fill in';
                } else {
                    $save[$category_id] = json_encode($topics, JSON_UNESCAPED_UNICODE);
                }
            }

            if (!empty($error)) {
                return $this->formErrorResponse($error);
            }

            \Product\Category\Model::save($module_id, $save);
            \Index\Log\Model::add(0, 'product', 'Category', 'Save Product Categories', $login->id);

            return $this->redirectResponse('reload', 'Saved successfully');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), $e->getCode() ?: 500, $e);
        }
    }
}

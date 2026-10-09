<?php
/**
 * @filesource modules/download/controllers/category.php
 *
 * @copyright 2026 Goragod.com
 * @license https://www.kotchasan.com/license/
 */

namespace Download\Category;

use Gcms\Api as ApiController;
use Kotchasan\Http\Request;
use Kotchasan\Text;

/**
 * API Download Category Controller
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Controller extends ApiController
{
    /**
     * GET /api/download/category/get
     *
     * @param Request $request
     *
     * @return \Kotchasan\Http\Response
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
            $module = \Index\Module\Model::getModuleWithConfig('download', $module_id);
            if (!$module) {
                return $this->redirectResponse('/404', 'No data available', 404);
            }

            return $this->successResponse([
                'data' => [
                    'type' => \Download\Category\Model::$type,
                    'module_id' => $module_id,
                    'options' => [
                        'columns' => \Download\Category\Model::getColumns(),
                        'data' => \Download\Category\Model::get($module_id)
                    ]
                ]
            ], 'Download categories retrieved');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), $e->getCode() ?: 500, $e);
        }
    }

    /**
     * POST /api/download/category/save
     *
     * @param Request $request
     *
     * @return \Kotchasan\Http\Response
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
            $module = \Index\Module\Model::getModuleWithConfig('download', $module_id);
            if (!$module) {
                return $this->errorResponse('No data available', 404);
            }

            $ids = $request->post('id', [])->topic();
            $type = $request->post('type')->filter('a-z_') ?: \Download\Category\Model::$type;
            $languages = \Download\Category\Model::getLanguages();

            $langValues = [];
            foreach ($languages as $lng) {
                $langValues[$lng] = $request->post($lng, [])->topic();
            }

            $errors = [];
            $save = [];
            foreach ($ids as $key => $id) {
                $category_id = Text::topic($id);
                if ($category_id === '') {
                    $errors['category_id_'.$key] = 'Category ID is required';
                    continue;
                }

                if (isset($save[$category_id])) {
                    $errors['category_id_'.$key] = 'Category ID '.$category_id.' already exists';
                    continue;
                }

                $topics = [];
                foreach ($languages as $lng) {
                    $topic = Text::topic($langValues[$lng][$key] ?? '');
                    if ($topic !== '') {
                        $topics[$lng] = $topic;
                    }
                }

                if (empty($topics)) {
                    $errors['category_'.$languages[0].'_'.$key] = 'Please fill in';
                } else {
                    $save[$category_id] = json_encode($topics, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                }
            }

            if (!empty($errors)) {
                return $this->formErrorResponse($errors);
            }

            \Download\Category\Model::save($module_id, $type, $save);
            \Index\Log\Model::add(0, 'download', 'Category', 'Save Download Categories', $login->id);

            return $this->redirectResponse('reload', 'Saved successfully');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), $e->getCode() ?: 500, $e);
        }
    }
}

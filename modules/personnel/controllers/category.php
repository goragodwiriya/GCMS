<?php
/**
 * @filesource modules/personnel/controllers/category.php
 *
 * @copyright 2026 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Personnel\Category;

use Gcms\Api as ApiController;
use Kotchasan\Http\Request;
use Kotchasan\Http\Response;
use Kotchasan\Text;

/**
 * API Category Controller
 *
 * Handles category translation endpoints
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Controller extends ApiController
{
    /**
     * @var array
     */
    protected $categories = [];

    /**
     * GET /api/index/category/get
     * Get Category details by ID
     *
     * @param Request $request
     *
     * @return Response
     */
    public function get(Request $request)
    {
        try {
            // Validate request method (GET request doesn't need CSRF token)
            ApiController::validateMethod($request, 'GET');

            // Read user from token (Bearer /X-Access-Token param)
            $login = $this->authenticateRequest($request);

            // Check authentication first (token missing or expired
            if (!$login || !ApiController::isAdmin($login)) {
                return $this->errorResponse('Unauthorized', 401);
            }

            $module_id = $request->get('module_id')->toInt();
            $type = $request->get('type', 'department')->filter('a-z_\-');
            if (!in_array($type, ['department'])) {
                return $this->redirectResponse('/404', 'No data available', 404);
            }

            $category = \Personnel\Category\Model::get($module_id, $type);
            if (!$category) {
                return $this->redirectResponse('/404', 'No data available', 404);
            }

            return $this->successResponse([
                'data' => [
                    'type' => $type,
                    'module_id' => $module_id,
                    'options' => [
                        'columns' => \Personnel\Category\Model::getColumns(),
                        'data' => $category
                    ]
                ]
            ], 'Category details retrieved');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), $e->getCode() ?: 500, $e);
        }
    }

    /**
     * POST /api/index/category/save
     * Save category (create or update)
     *
     * @param Request $request
     * @return Response
     */
    public function save(Request $request)
    {
        try {
            ApiController::validateMethod($request, 'POST');
            $this->validateCsrfToken($request);

            // Authentication check (required)
            $login = $this->authenticateRequest($request);
            if (!$login) {
                return $this->redirectResponse('/login', 'Unauthorized', 401);
            }

            // Authorization for saving
            if (!ApiController::canModify($login)) {
                return $this->errorResponse('Permission required', 403);
            }

            $module_id = $request->post('module_id')->toInt();
            $ids = $request->post('id', [])->topic();
            $type = $request->post('type')->filter('a-z_');
            // ต้องเป็นโมดูลบุคลากรจริงและ type ที่รองรับเท่านั้น — module_id = 0 คือหมวดระดับเว็บไซต์
            // (แผนกของสมาชิก) ถ้าปล่อยผ่าน การบันทึกจะลบหมวดเหล่านั้นทิ้งทั้งหมด
            if ($module_id <= 0 || !in_array($type, ['department'], true)
                || !\Index\Module\Model::getModuleWithConfig('personnel', $module_id)) {
                return $this->errorResponse('No data available', 404);
            }

            // Installed language
            $languages = \Index\Language\Model::getLanguages();

            $langValues = [];
            foreach ($languages as $lng) {
                $langValues[$lng] = $request->post($lng, [])->topic();
            }

            $error = [];
            $save = [];
            foreach ($ids as $key => $id) {
                $category_id = Text::topic($id);
                if ($category_id === '') {
                    $error['category_id_'.$key] = 'Category ID is required';
                } else {
                    $topics = [];
                    foreach ($languages as $lng) {
                        if (isset($save[$category_id])) {
                            $error['category_id_'.$key] = 'Category ID '.$category_id.' already exists';
                        } else {
                            $topic = Text::topic($langValues[$lng][$key]);
                            if ($topic !== '') {
                                $topics[$lng] = $topic;
                            }
                        }
                    }
                    if (empty($topics)) {
                        $error['category_'.$languages[0].'_'.$key] = 'Please fill in';
                    } else {
                        $save[$category_id] = json_encode($topics, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                    }
                }
            }

            if (!empty($error)) {
                return $this->formErrorResponse($error);
            }

            // Save
            \Personnel\Category\Model::save($module_id, $type, $save);

            // Log
            \Index\Log\Model::add(0, 'personnel', 'Category', 'Saved '.ucfirst($type).': '.implode(', ', array_keys($save)), $login->id);

            // Redirect to reload
            return $this->redirectResponse('reload', 'Saved successfully');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), $e->getCode() ?: 500, $e);
        }
    }
}

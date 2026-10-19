<?php
/**
 * @filesource modules/personnel/controllers/lists.php
 *
 * Personnel List Controller - for frontend/widget API
 *
 * @copyright 2026 Goragod.com
 * @license https://www.kotchasan.com/license/
 */

namespace Personnel\Lists;

use Gcms\Api as ApiController;
use Kotchasan\Http\Request;

class Controller extends ApiController
{
    /**
     * GET /api/personnel/lists
     * Get personnel list (public, for frontend)
     *
     * @param Request $request
     *
     * @return mixed
     */
    public function index(Request $request)
    {
        try {
            ApiController::validateMethod($request, 'GET');

            $module_id = $request->get('module_id')->toInt();
            $department = $request->get('department')->topic();
            $limit = $request->get('limit')->toInt();
            $limit = $limit > 0 ? $limit : 0; // 0 = all

            $persons = \Personnel\Lists\Model::getPersonnel($module_id, $department, $limit);

            foreach ($persons as $person) {
                if (file_exists(ROOT_PATH.DATA_FOLDER.'personnel/'.$person->picture)) {
                    $person->image_url = WEB_URL.DATA_FOLDER.'personnel/'.$person->picture;
                } else {
                    $person->image_url = WEB_URL.'images/no-image.webp';
                }
                $person->url = WEB_URL.'personnel/id/'.$person->id;
            }

            $departments = \Personnel\Lists\Model::getDepartments($module_id);

            return $this->successResponse([
                'items' => $persons,
                'departments' => $departments
            ], 'Personnel retrieved');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), $e->getCode() ?: 500, $e);
        }
    }

    /**
     * GET /api/personnel/lists/detail
     * Get single person detail (public)
     *
     * @param Request $request
     *
     * @return mixed
     */
    public function detail(Request $request)
    {
        try {
            ApiController::validateMethod($request, 'GET');

            $id = $request->get('id')->toInt();
            $person = \Personnel\Lists\Model::getPerson($id);
            if (!$person) {
                return $this->errorResponse('Not found', 404);
            }

            if (file_exists(ROOT_PATH.DATA_FOLDER.'personnel/'.$person->picture)) {
                $person->image_url = WEB_URL.DATA_FOLDER.'personnel/'.$person->picture;
            } else {
                $person->image_url = WEB_URL.'images/no-image.webp';
            }

            return $this->successResponse($person, 'Person detail retrieved');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), $e->getCode() ?: 500, $e);
        }
    }
}

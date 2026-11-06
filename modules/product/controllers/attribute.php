<?php
/**
 * @filesource modules/product/controllers/attribute.php
 *
 * @copyright 2026 Goragod.com
 * @license https://www.kotchasan.com/license/
 */

namespace Product\Attribute;

use Gcms\Api as ApiController;
use Kotchasan\Http\Request;

/**
 * API Product Attribute Controller
 *
 * Manage attribute definitions (e.g. Size, Color, Grind) and their values.
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Controller extends ApiController
{
    /**
     * GET /api/product/attribute/get
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
                return $this->redirectResponse('/login', 'Unauthorized', 401);
            }

            $module = \Index\Module\Model::getModuleWithConfig('product', $request->get('module_id')->toInt());
            if (!$module) {
                return $this->redirectResponse('/404', 'No data available', 404);
            }

            return $this->successResponse([
                'module_id' => $module->id,
                'languages' => \Product\Attribute\Model::$languages,
                'datas' => \Product\Attribute\Model::getAll($module->id)
            ], 'Product attributes loaded');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), $e->getCode() ?: 500, $e);
        }
    }

    /**
     * POST /api/product/attribute/save
     *
     * @param Request $request
     *
     * @return mixed
     */
    public function save(Request $request)
    {
        try {
            ApiController::validateMethod($request, 'POST');
            ApiController::validateCsrfToken($request);

            $login = $this->authenticateRequest($request);
            if (!$login) {
                return $this->redirectResponse('/login', 'Unauthorized', 401);
            }
            if (!ApiController::canModify($login)) {
                return $this->errorResponse('Permission required', 403);
            }

            $module = \Index\Module\Model::getModuleWithConfig('product', $request->post('module_id')->toInt());
            if (!$module) {
                return $this->errorResponse('No data available', 404);
            }

            $datas = $request->post('datas')->json([]);
            if (!is_array($datas)) {
                $datas = [];
            }

            \Product\Attribute\Model::saveAll($module->id, $datas);
            \Index\Log\Model::add(0, 'product', 'Product', 'Save Product Attributes', $login->id);

            return $this->redirectResponse('reload', 'Saved successfully', 200, 1000);
        } catch (\Kotchasan\ApiException $e) {
            return $this->errorResponse($e->getMessage(), $e->getCode() ?: 400, $e);
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), $e->getCode() ?: 500, $e);
        }
    }
}

<?php
/**
 * @filesource modules/product/controllers/lists.php
 *
 * @copyright 2026 Goragod.com
 * @license https://www.kotchasan.com/license/
 */

namespace Product\Lists;

use Gcms\Api as ApiController;
use Kotchasan\Http\Request;

/**
 * Public storefront list API.
 *
 * GET /api/product/lists?limit=&category_id=&search=&page=
 * Returns { data: [ {id,url,thumb,topic,page,price,stock}, ... ] }.
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Controller extends ApiController
{
    /**
     * Resolve the storefront product module instance (param or first installed).
     *
     * @param Request $request
     *
     * @return object|null { module_id, module }
     */
    public static function resolveModule(Request $request)
    {
        $module_id = $request->get('module_id')->toInt();
        if ($module_id > 0) {
            $module = \Index\Module\Model::getModuleWithConfig('product', $module_id);
            if ($module) {
                return (object) ['module_id' => $module->id, 'module' => $module->module];
            }
        }
        $installed = \Index\Modules\Model::getInstalledModules('product');
        foreach ($installed as $item) {
            return (object) ['module_id' => (int) $item->module_id, 'module' => $item->module];
        }
        return null;
    }

    /**
     * GET /api/product/lists
     *
     * @param Request $request
     *
     * @return mixed
     */
    public function index(Request $request)
    {
        try {
            ApiController::validateMethod($request, 'GET');
            $this->initLanguage($request);

            $ctx = self::resolveModule($request);
            if (!$ctx) {
                return $this->successResponse(['data' => []], 'No product module');
            }

            $limit = $request->get('limit')->toInt() ?: 10;
            $limit = min(60, max(1, $limit));
            $catParam = $request->get('category_id')->filter('0-9,');
            $categoryIds = array_values(array_filter(array_map('intval', explode(',', $catParam))));
            $search = $request->get('search')->topic();

            $exclude = $request->get('exclude')->toInt();

            $data = \Product\Lists\Model::getList($ctx->module_id, $ctx->module, $categoryIds, $search, $limit, 0, $exclude);

            return $this->successResponse(['data' => $data], 'Products retrieved');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), $e->getCode() ?: 500, $e);
        }
    }
}

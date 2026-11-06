<?php
/**
 * @filesource modules/product/controllers/filter.php
 *
 * @copyright 2026 Goragod.com
 * @license https://www.kotchasan.com/license/
 */

namespace Product\Filter;

use Gcms\Api as ApiController;
use Kotchasan\Http\Request;

/**
 * Public storefront filter API (sidebar).
 *
 * GET /api/product/filter -> { categories: [ {id,topic} ], search }
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Controller extends ApiController
{
    /**
     * GET /api/product/filter
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

            $ctx = \Product\Lists\Controller::resolveModule($request);
            if (!$ctx) {
                return $this->successResponse(['categories' => [], 'search' => '', 'selected' => [], 'url' => ''], 'No product module');
            }

            // The listing page renders its own module_id/cat into this
            // endpoint (Product\Index\View) — the page URL alone does not carry
            // them ({module}/{category}.html)
            $selected = array_values(array_filter(array_map('intval', explode(',', $request->get('cat')->filter('0-9,')))));

            return $this->successResponse([
                'categories' => \Product\Lists\Model::getFilterCategories($ctx->module_id),
                'selected' => $selected,
                'search' => $request->get('search')->topic(),
                'url' => \Web\Gcms::createUrl($ctx->module)
            ], 'Filters retrieved');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), $e->getCode() ?: 500, $e);
        }
    }
}

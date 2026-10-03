<?php
/**
 * @filesource modules/index/controllers/chart.php
 *
 * @copyright 2026 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Index\Chart;

use Index\Chart\Model as ChartModel;
use Kotchasan\ApiController;
use Kotchasan\Http\Request;
use Kotchasan\Http\Response;

/**
 * API Chart Controller
 *
 * Provides chart data endpoints for the admin dashboard.
 *
 * Routes:
 *   GET /api/index/chart/visitors  — 30-day visitor trend
 *   GET /api/index/chart/content   — content distribution by module
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Controller extends \Kotchasan\ApiController
{
    /**
     * GET /api/index/chart/visitors
     * 30-day daily visitor trend (unique visitors + page views)
     *
     * @param Request $request
     *
     * @return Response
     */
    public function visitors(Request $request)
    {
        try {
            ApiController::validateMethod($request, 'GET');
            $data = ChartModel::visitors();
            return $this->successResponse($data, 'Visitor chart data retrieved');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), $e->getCode() ?: 500, $e);
        }
    }

    /**
     * GET /api/index/chart/content
     * Content distribution across installed modules (pie chart)
     *
     * @param Request $request
     *
     * @return Response
     */
    public function content(Request $request)
    {
        try {
            ApiController::validateMethod($request, 'GET');
            $data = ChartModel::content();
            return $this->successResponse($data, 'Content chart data retrieved');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), $e->getCode() ?: 500, $e);
        }
    }
}

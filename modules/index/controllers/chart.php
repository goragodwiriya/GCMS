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

use Gcms\Api as ApiController;
use Index\Chart\Model as ChartModel;
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
 * ต้องเข้าระบบ · ผู้ที่ไม่มีสิทธิ์ดูภาพรวม (Index\Dashboard\Controller::canViewOverview) ได้กราฟว่าง
 * ไม่ใช่ 403 — ดูเหตุผลที่ Index\Dashboard\Controller
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Controller extends ApiController
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
            $login = $this->authenticateRequest($request);
            if (!$login) {
                return $this->errorResponse('Unauthorized', 401);
            }
            $data = \Index\Dashboard\Controller::canViewOverview($login) ? ChartModel::visitors() : [
                ['name' => '{LNG_Visitors}', 'data' => []],
                ['name' => '{LNG_Page Views}', 'data' => []]
            ];
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
            $login = $this->authenticateRequest($request);
            if (!$login) {
                return $this->errorResponse('Unauthorized', 401);
            }
            $data = \Index\Dashboard\Controller::canViewOverview($login) ? ChartModel::content() : [
                ['name' => '{LNG_Content Distribution}', 'data' => []]
            ];
            return $this->successResponse($data, 'Content chart data retrieved');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), $e->getCode() ?: 500, $e);
        }
    }
}

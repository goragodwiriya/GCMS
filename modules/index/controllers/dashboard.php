<?php
/**
 * @filesource modules/index/controllers/dashboard.php
 *
 * @copyright 2026 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Index\Dashboard;

use Kotchasan;
use Kotchasan\ApiController;
use Kotchasan\Http\Request;
use Kotchasan\Http\Response;

/**
 * API Dashboard Controller
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Controller extends \Kotchasan\ApiController
{
    /**
     * GET /api/index/dashboard
     * Get full dashboard data (core stats, module stats, system info)
     *
     * @param Request $request
     *
     * @return Response
     */
    public function index(Request $request)
    {
        try {
            ApiController::validateMethod($request, 'GET');
            $data = \Index\Dashboard\Model::get();
            return $this->successResponse($data, 'Dashboard data retrieved successfully');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), $e->getCode() ?: 500, $e);
        }
    }

    /**
     * GET /api/index/dashboard/logs
     * Get recent activity log entries
     *
     * @param Request $request
     *
     * @return Response
     */
    public function logs(Request $request)
    {
        try {
            ApiController::validateMethod($request, 'GET');
            $limit = min((int) $request->get('limit', 10)->toString(), 50);
            $data = \Index\Dashboard\Model::getRecentLogs($limit);
            return $this->successResponse($data, 'Logs retrieved successfully');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), $e->getCode() ?: 500, $e);
        }
    }
}

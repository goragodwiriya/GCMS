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

use Gcms\Api as ApiController;
use Kotchasan\Http\Request;
use Kotchasan\Http\Response;

/**
 * API Dashboard Controller
 *
 * ทุก endpoint ต้องเข้าระบบ · สมาชิกทั่วไปเข้าหน้า dashboard ได้ แต่เห็นแค่บันทึกกิจกรรมของตัวเอง
 *
 * ผู้ที่ไม่มีสิทธิ์ได้ 200 ที่ไม่มีข้อมูล ไม่ใช่ 403 — ตัวดัก 403 ของ AuthManager พาผู้ใช้ทั้งหน้าไปหน้า
 * forbidden ทั้งที่ dashboard เป็นหน้าที่สมาชิกเข้าได้ (คำขอของกราฟ/ตารางยิงก่อน template จะซ่อนส่วนนั้น)
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Controller extends ApiController
{
    /**
     * GET /api/index/dashboard
     * Get full dashboard data (core stats, module stats, system info) — only for canViewOverview()
     *
     * @param Request $request
     *
     * @return Response
     */
    public function index(Request $request)
    {
        try {
            ApiController::validateMethod($request, 'GET');
            $login = $this->authenticateRequest($request);
            if (!$login) {
                return $this->errorResponse('Unauthorized', 401);
            }
            $overview = self::canViewOverview($login);
            $data = ['can' => ['overview' => $overview]];
            if ($overview) {
                $data += \Index\Dashboard\Model::get();
            }

            return $this->successResponse($data, 'Dashboard data retrieved successfully');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), $e->getCode() ?: 500, $e);
        }
    }

    /**
     * GET /api/index/dashboard/logs
     * Get recent activity log entries — everyone's for canViewAllLogs(), otherwise the member's own
     *
     * @param Request $request
     *
     * @return Response
     */
    public function logs(Request $request)
    {
        try {
            ApiController::validateMethod($request, 'GET');
            $login = $this->authenticateRequest($request);
            if (!$login) {
                return $this->errorResponse('Unauthorized', 401);
            }
            $limit = min((int) $request->get('limit', 10)->toString(), 50);
            $memberId = self::canViewAllLogs($login) ? 0 : (int) $login->id;
            $data = \Index\Dashboard\Model::getRecentLogs($limit, $memberId);

            return $this->successResponse($data, 'Logs retrieved successfully');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), $e->getCode() ?: 500, $e);
        }
    }

    /**
     * ภาพรวมของเว็บไซต์ (สถิติผู้เยี่ยมชม สมาชิก เนื้อหา ขนาดข้อมูล และกราฟ)
     * แอดมิน (status = 1) หรือผู้มีสิทธิ์ตั้งค่าระบบ
     *
     * @param object|null $login
     *
     * @return bool
     */
    public static function canViewOverview($login)
    {
        return ApiController::hasPermission($login, ['can_config']);
    }

    /**
     * บันทึกกิจกรรมของทุกคน — กติกาเดียวกับหน้า usage (Index\Usage\Controller)
     * บัญชีตัวอย่างของ demo_mode ได้สถานะแอดมิน จึงต้องกัน isNotDemoMode() อีกชั้น
     *
     * @param object|null $login
     *
     * @return bool
     */
    public static function canViewAllLogs($login)
    {
        return ApiController::hasPermission($login, ['can_view_usage_history']) && ApiController::isNotDemoMode($login);
    }
}

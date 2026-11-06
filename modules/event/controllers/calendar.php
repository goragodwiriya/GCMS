<?php
/**
 * @filesource modules/event/controllers/calendar.php
 *
 * @copyright 2026 Goragod.com
 * @license https://www.kotchasan.com/license/
 */

namespace Event\Calendar;

use Gcms\Api as ApiController;
use Kotchasan\Http\Request;

/**
 * Public API — returns published events within a date range as JSON for
 * the frontend calendar (Now.js EventCalendar component). The component
 * requests the visible grid range via ?start=&end=. Ports gcms241021
 * Event\Calendar\Model::toJSON() onto the current API convention.
 *
 * GET /api/event/calendar?module_id={id}&start={YYYY-MM-DD}&end={YYYY-MM-DD}
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Controller extends ApiController
{
    /**
     * @param Request $request
     *
     * @return mixed
     */
    public function index(Request $request)
    {
        try {
            ApiController::validateMethod($request, 'GET');

            $moduleId = $request->get('module_id')->toInt();
            $module = \Index\Module\Model::getModuleWithConfig('event', $moduleId);
            if (!$module) {
                return $this->errorResponse('No data available', 404);
            }

            $start = $request->get('start')->date() ?: date('Y-m-01');
            $end = $request->get('end')->date() ?: date('Y-m-t');

            $rows = \Event\Calendar\Model::get($moduleId, $start, $end);
            $events = [];
            foreach ($rows as $item) {
                $hasEnd = !empty($item->end_date) && strpos($item->end_date, '0000-00-00') !== 0;
                $events[] = [
                    'id' => (int) $item->id,
                    'title' => $item->topic,
                    'start' => $item->begin_date,
                    'end' => $hasEnd ? $item->end_date : $item->begin_date,
                    'allDay' => !$hasEnd,
                    'color' => $item->color === '' ? '#4CAF50' : $item->color,
                    'url' => \Event\Index\Controller::url($module->module, $item->id)
                ];
            }

            return $this->successResponse($events, 'Events retrieved');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), $e->getCode() ?: 500, $e);
        }
    }
}

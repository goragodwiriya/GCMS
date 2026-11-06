<?php
/**
 * @filesource modules/video/controllers/view.php
 *
 * @copyright 2026 Goragod.com
 * @license https://www.kotchasan.com/license/
 */

namespace Video\View;

use Gcms\Api as ApiController;
use Kotchasan\Database\Sql;
use Kotchasan\Http\Request;

/**
 * Public API — counts a video play, ports gcms241021 Video\View (xhr modal)
 * onto the current API convention. The player itself is opened client-side
 * by modules/video/script.js.
 *
 * GET /api/video/view?id={id}
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

            $id = $request->get('id')->toInt();
            $item = \Kotchasan\Model::createQuery()
                ->select('V.id', 'V.youtube', 'V.topic', 'V.views')
                ->from('video V')
                ->join('modules M', [['M.id', 'V.module_id'], ['M.owner', 'video']])
                ->where(['V.id', $id])
                ->first();

            if (!$item) {
                return $this->errorResponse('No data available', 404);
            }

            // นับจำนวนการเปิดดู
            \Kotchasan\DB::create()->update('video', ['id', $item->id], ['views' => Sql::create('`views`+1')]);

            return $this->successResponse([
                'id' => $item->id,
                'youtube' => $item->youtube,
                'topic' => $item->topic,
                'views' => (int) $item->views + 1
            ], 'Video retrieved');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), $e->getCode() ?: 500, $e);
        }
    }
}

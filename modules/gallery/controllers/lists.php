<?php
/**
 * @filesource modules/gallery/controllers/lists.php
 *
 * Gallery List Controller - for frontend widget/API
 */

namespace Gallery\Lists;

use Gcms\Api as ApiController;
use Kotchasan\Http\Request;

class Controller extends ApiController
{
    /**
     * GET /api/gallery/lists
     * Get latest albums for widget
     *
     * @param Request $request
     */
    public function index(Request $request)
    {
        try {
            ApiController::validateMethod($request, 'GET');

            $limit = $request->get('limit')->toInt();
            $limit = $limit > 0 ? $limit : 6;

            $albums = \Gallery\Lists\Model::create([])->widget($limit);
            foreach ($albums as $album) {
                if (!empty($album->cover) && file_exists(ROOT_PATH.DATA_FOLDER.'gallery/'.$album->cover)) {
                    $album->cover_url = WEB_URL.DATA_FOLDER.'gallery/'.$album->cover;
                } else {
                    $album->cover_url = WEB_URL.'images/no-image.webp';
                }
                $album->url = WEB_URL.'gallery/id/'.$album->id;
            }

            return $this->successResponse($albums, 'Albums retrieved');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), $e->getCode() ?: 500, $e);
        }
    }
}

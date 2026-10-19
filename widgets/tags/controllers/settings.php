<?php
/**
 * @filesource widgets/tags/controllers/settings.php
 *
 * @copyright 2026 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Widgets\Tags\Controllers;

use Gcms\Api as ApiController;
use Kotchasan\File;
use Kotchasan\Http\Request;

/**
 * Tags Widget — Settings Controller
 *
 * GET  ../api/index/widgets/get?widget=tags  → load current settings
 * POST ../api/index/widgets/save?widget=tags → validate and persist settings
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Settings extends \Kotchasan\ApiController
{
    /**
     * GET  ../api/index/widgets/get?widget=tags
     * Return current Tags widget settings as JSON.
     *
     * @param Request $request
     *
     * @return \Kotchasan\Http\Response
     */
    public function get(Request $request)
    {
        try {
            $login = $this->authenticateRequest($request);
            if (!$login) {
                return $this->errorResponse('Unauthorized', 401);
            }

            if (!ApiController::hasPermission($login, ['can_config'])) {
                return $this->errorResponse('Permission required', 403);
            }

            $tags = \Widgets\Tags\Models\Settings::get();

            if (!$tags) {
                return $this->redirectResponse('/404', 'No data available', 404);
            }

            return $this->successResponse([
                'data' => [
                    'options' => [
                        'data' => $tags
                    ]
                ]
            ], 'Tags details retrieved');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), $e->getCode() ?: 500, $e);
        }
    }

    /**
     * POST ../api/index/widgets/save?widget=tags
     *
     * @param Request $request
     *
     * @return \Kotchasan\Http\Response
     */
    public function save(Request $request)
    {
        try {
            ApiController::validateMethod($request, 'POST');
            $this->validateCsrfToken($request);

            // Authentication check (required)
            $login = $this->authenticateRequest($request);
            if (!$login) {
                return $this->redirectResponse('/login', 'Unauthorized', 401);
            }

            // Authorization for saving
            if (!ApiController::canModify($login)) {
                return $this->errorResponse('Permission required', 403);
            }

            // Database connection
            $db = \Kotchasan\DB::create();

            // Clear existing tags
            $db->emptyTable('tags');

            $ids = $request->post('id', [])->toInt();
            $tags = $request->post('tag', [])->topic();
            $counts = $request->post('count', [])->toInt();
            foreach ($tags as $key => $tag) {
                if (empty($tag)) {
                    continue;
                }
                $db->insert('tags', [
                    'id' => $ids[$key] ?? null,
                    'tag' => $tag,
                    'count' => $counts[$key] ?? 0
                ]);
            }

            // Log
            \Index\Log\Model::add(0, 'index', 'Index', 'Saved tags', $login->id);

            // Redirect to reload
            return $this->redirectResponse('reload', 'Saved successfully', 200, 1000);
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), $e->getCode() ?: 500, $e);
        }
    }

    /**
     * POST ../api/index/widgets/click?widget=tags
     * Increment the click count for a tag (public, no login required).
     *
     * Expects POST body: { id: <int> }
     *
     * @param Request $request
     *
     * @return \Kotchasan\Http\Response
     */
    public function click(Request $request)
    {
        try {
            $id = (int) $request->post('id')->toInt();

            if ($id <= 0) {
                return $this->errorResponse('Invalid tag id', 400);
            }

            \Widgets\Tags\Models\Settings::incrementCount($id);

            return $this->successResponse([], 'OK');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), $e->getCode() ?: 500, $e);
        }
    }
}

<?php
/**
 * @filesource widgets/facebook/controllers/settings.php
 *
 * @copyright 2026 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Widgets\Facebook\Controllers;

use Gcms\Api as ApiController;
use Gcms\Config;
use Kotchasan\Http\Request;

/**
 * Facebook Widget — Settings Controller
 *
 * GET  ../api/index/widgets/get?widget=facebook  → load current settings
 * POST ../api/index/widgets/save?widget=facebook → validate and persist settings
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Settings extends \Kotchasan\ApiController
{
    /**
     * GET  ../api/index/widgets/get?widget=facebook
     * Return current Facebook widget settings as JSON.
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

            // Load config
            $config = Config::load(ROOT_PATH.'settings/config.php');

            $facebookConfig = $config->facebook ?? [
                'height' => 500,
                'width' => 306,
                'user' => '',
                'show_facepile' => 1,
                'cover_image' => 1,
                'small_header' => 0
            ];

            $params = [
                'href' => 'https://www.facebook.com/'.$facebookConfig['user'],
                'tabs' => 'timeline',
                'width' => $facebookConfig['width'],
                'height' => $facebookConfig['height'],
                'show_facepile' => $facebookConfig['show_facepile'] ? 'true' : 'false',
                'hide_cover' => $facebookConfig['cover_image'] ? 'false' : 'true',
                'small_header' => $facebookConfig['small_header'] ? 'true' : 'false',
                'adapt_container_width' => 'true'
            ];

            $facebookConfig['iframe_url'] = 'https://www.facebook.com/plugins/page.php?'.http_build_query($params);

            return $this->successResponse($facebookConfig, 'Settings retrieved successfully');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), $e->getCode() ?: 500, $e);
        }
    }

    /**
     * POST ../api/index/widgets/save?widget=facebook
     * Validate and persist Facebook widget settings.
     *
     * Expected POST fields:
     *   height        int    Widget height in pixels (≥ 70)
     *   user          string Facebook page username
     *   show_facepile int    1 | 0
     *   cover_image   int    1 | 0
     *   small_header  int    1 | 0
     *
     * @param Request $request
     *
     * @return \Kotchasan\Http\Response
     */
    public function save(Request $request)
    {
        try {
            $login = $this->authenticateRequest($request);
            if (!$login) {
                return $this->errorResponse('Unauthorized', 401);
            }

            if (!ApiController::canModify($login, ['can_config'])) {
                return $this->errorResponse('Permission required', 403);
            }

            // Load config
            $config = Config::load(ROOT_PATH.'settings/config.php');

            $config->facebook = [
                'height' => max(70, $request->post('height')->toInt()), // Facebook plugin height must be at least 70 pixels
                'width' => min(500, max(180, $request->post('width')->toInt())), // Facebook plugin width must be between 180 and 500
                'user' => $request->post('user')->topic(),
                'show_facepile' => $request->post('show_facepile')->toInt() ? 1 : 0,
                'cover_image' => $request->post('cover_image')->toInt() ? 1 : 0,
                'small_header' => $request->post('small_header')->toInt() ? 1 : 0
            ];

            if (Config::save($config, ROOT_PATH.'settings/config.php')) {
                // Log
                \Index\Log\Model::add(0, 'widgets', 'Facebook Widget', 'Updated Facebook widget settings', $login->id);

                // Reload page
                return $this->redirectResponse('reload', 'Saved successfully', 200, 1000);
            }
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), $e->getCode() ?: 500, $e);
        }
    }
}

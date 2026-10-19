<?php
/**
 * @filesource widgets/map/controllers/settings.php
 *
 * @copyright 2026 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Widgets\Map\Controllers;

use Gcms\Api as ApiController;
use Gcms\Config;
use Kotchasan\Http\Request;

/**
 * Map Widget — Settings Controller (Leaflet / OpenStreetMap)
 *
 * GET  ../api/index/widgets/get?widget=map  → load current settings
 * POST ../api/index/widgets/save?widget=map → validate and persist settings
 *
 * Stored fields:
 *   lat           float   Latitude  (e.g. 13.727639)
 *   lng           float   Longitude (e.g. 100.520194)
 *   zoom          int     Zoom level 1–19 (default 16)
 *   height        int     Map container height in px (min 200)
 *   marker_icon   string  Emoji or short text displayed inside the pin
 *   popup_name    string  Location name shown in the popup
 *   popup_address string  Address shown in the popup
 *   google_maps_url string Full Google Maps share-link for "View in Maps" CTA
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Settings extends \Kotchasan\ApiController
{
    /** Default map configuration */
    private static function defaults(): array
    {
        return [
            'lat' => 13.727639,
            'lng' => 100.520194,
            'zoom' => 16,
            'height' => 450,
            'marker_icon' => '📍',
            'popup_name' => '',
            'popup_address' => '',
            'google_maps_url' => ''
        ];
    }

    /**
     * GET  ../api/index/widgets/get?widget=map
     * Return current Map widget settings as JSON.
     * Public endpoint — no authentication required (map location is public data).
     *
     * @param Request $request
     *
     * @return \Kotchasan\Http\Response
     */
    public function get(Request $request)
    {
        try {
            $config = Config::load(ROOT_PATH.'settings/config.php');
            $mapConfig = array_merge(self::defaults(), (array) ($config->map ?? []));

            return $this->successResponse($mapConfig, 'Settings retrieved successfully');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), $e->getCode() ?: 500, $e);
        }
    }

    /**
     * POST ../api/index/widgets/save?widget=map
     * Validate and persist Map widget settings.
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

            $config = Config::load(ROOT_PATH.'settings/config.php');

            // Clamp lat/lng to valid WGS-84 ranges
            $lat = max(-90.0, min(90.0, (float) $request->post('lat')->toString()));
            $lng = max(-180.0, min(180.0, (float) $request->post('lng')->toString()));

            $config->map = [
                'lat' => round($lat, 7),
                'lng' => round($lng, 7),
                'zoom' => max(1, min(19, $request->post('zoom')->toInt())),
                'height' => max(200, $request->post('height')->toInt()),
                'marker_icon' => mb_substr($request->post('marker_icon')->toString(), 0, 8),
                'popup_name' => $request->post('popup_name')->topic(),
                'popup_address' => $request->post('popup_address')->toString(),
                'google_maps_url' => filter_var(
                    $request->post('google_maps_url')->toString(),
                    FILTER_SANITIZE_URL
                )
            ];

            if (Config::save($config, ROOT_PATH.'settings/config.php')) {
                \Index\Log\Model::add(0, 'widgets', 'Map Widget', 'Updated Map widget settings', $login->id);

                return $this->redirectResponse('reload', 'Saved successfully', 200, 1000);
            }

            return $this->errorResponse('Could not save settings', 500);
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), $e->getCode() ?: 500, $e);
        }
    }
}

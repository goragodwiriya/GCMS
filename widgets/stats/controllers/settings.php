<?php
/**
 * @filesource widgets/stats/controllers/settings.php
 *
 * Stats Widget — Settings Controller
 *
 * GET  ../api/index/widgets/get?widget=stats[&id=N]  → load one stat item (or blank defaults)
 * POST ../api/index/widgets/save?widget=stats         → insert / update one stat item
 *
 * @copyright 2026 Goragod.com
 * @license https://www.kotchasan.com/license/
 */

namespace Widgets\Stats\Controllers;

use Gcms\Api as ApiController;
use Kotchasan\Http\Request;

/**
 * Stats Widget — Settings Controller
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Settings extends \Kotchasan\ApiController
{
    /**
     * GET ../api/index/widgets/get?widget=stats[&id=N]
     *
     * Returns one stat item by ID, or an object with blank defaults for a new item.
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

            $id = $request->get('id')->toInt();

            if ($id > 0) {
                $item = \Widgets\Stats\Models\Index::getById($id);
                if (!$item) {
                    return $this->errorResponse('Item not found', 404);
                }
                // Ensure new fields exist for older records
                $item += [
                    'countMode' => 'up',
                    'animation' => 'default',
                    'delay' => 0,
                    'easing' => 'easeOutExpo'
                ];
            } else {
                // Defaults for a new item
                $item = [
                    'id' => 0,
                    'name' => 'default',
                    'icon' => '',
                    'label' => '',
                    'value' => 0,
                    'suffix' => '',
                    'prefix' => '',
                    'duration' => 2000,
                    'format' => 'number',
                    'separator' => ',',
                    'decimal' => '.',
                    'decimals' => 0,
                    'countMode' => 'up',
                    'animation' => 'default',
                    'delay' => 0,
                    'easing' => 'easeOutExpo',
                    'color' => '',
                    'description' => ''
                ];
            }

            return $this->successResponse((object) $item, 'Stat item retrieved');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), $e->getCode() ?: 500, $e);
        }
    }

    /**
     * POST ../api/index/widgets/save?widget=stats
     *
     * Insert or update one stat item.
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

            $errors = [];

            $id = $request->post('id')->toInt();
            $name = $request->post('name')->filter('a-z0-9_');
            $icon = $request->post('icon')->filter('a-z0-9-_');
            $label = $request->post('label')->topic();

            if (empty($name)) {
                $name = 'default';
            }
            if (empty($label)) {
                $errors['label'] = 'Label is required';
            }

            $value = $request->post('value')->toFloat();
            $suffix = $request->post('suffix')->toString();
            $prefix = $request->post('prefix')->toString();
            $duration = max(100, $request->post('duration', 2000)->toInt());
            $format = $request->post('format')->filter('a-z');
            $separator = $request->post('separator')->toString();
            $decimal = $request->post('decimal')->toString();
            $decimals = max(0, min(10, $request->post('decimals', 0)->toInt()));
            $countMode = $request->post('countMode')->filter('a-z');
            $animation = $request->post('animation')->filter('a-z');
            $delay = max(0, $request->post('delay', 0)->toInt());
            $easing = $request->post('easing')->filter('a-zA-Z');
            $color = $request->post('color')->toString();
            $description = $request->post('description')->topic();

            // Validate format against CounterComponent's supported values
            if (!in_array($format, ['number', 'percentage', 'currency', 'time', 'timer'], true)) {
                $format = 'number';
            }
            if (!in_array($countMode, ['up', 'down'], true)) {
                $countMode = 'up';
            }
            if (!in_array($animation, ['default', 'odometer', 'rollup'], true)) {
                $animation = 'default';
            }
            if (empty($easing)) {
                $easing = 'easeOutExpo';
            }

            if (!empty($errors)) {
                return $this->errorResponse(implode(', ', $errors), 422);
            }

            $item = [
                'id' => $id,
                'name' => $name,
                'icon' => $icon,
                'label' => $label,
                'value' => $value,
                'suffix' => $suffix,
                'prefix' => $prefix,
                'duration' => $duration,
                'format' => $format,
                'separator' => $separator,
                'decimal' => $decimal,
                'decimals' => $decimals,
                'countMode' => $countMode,
                'animation' => $animation,
                'delay' => $delay,
                'easing' => $easing,
                'color' => $color,
                'description' => $description
            ];

            $savedId = \Widgets\Stats\Models\Index::saveItem($item);

            \Index\Log\Model::add(0, 'widgets', 'Stats', ($id > 0 ? 'Updated' : 'Created').' stat item ID '.$savedId, $login->id);

            return $this->redirectResponse('back', 'Saved successfully', 200, 1000);
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), $e->getCode() ?: 500, $e);
        }
    }
}

<?php
/**
 * @filesource modules/video/controllers/init.php
 *
 * @copyright 2026 Goragod.com
 * @license https://www.kotchasan.com/license/
 */

namespace Video\Init;

/**
 * Init Controller for Video Module
 *
 * Registers admin menus and permissions for each installed video instance.
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Controller extends \Gcms\Controller
{
    /**
     * Register admin menus
     *
     * @param array $menus
     * @param array $params
     * @param object $login
     *
     * @return array
     */
    public static function initMenus($menus, $params, $login)
    {
        $modules = \Index\Modules\Model::getInstalledModules('video');
        foreach ($modules as $item) {
            $children = [];

            if (\Web\Login::checkStatus($login, $item->config, ['can_write'])) {
                $children[] = [
                    'title' => '{LNG_Video}',
                    'url' => '/video-list?module_id='.$item->module_id,
                    'icon' => 'icon-list'
                ];
            }

            if ($params['isAdmin']) {
                $children[] = [
                    'title' => '{LNG_Settings}',
                    'url' => '/video-settings?module_id='.$item->module_id,
                    'icon' => 'icon-cog'
                ];
            }

            if (empty($children)) {
                continue;
            }

            $menus = parent::insertMenuAfter($menus, [
                [
                    'title' => $item->topic,
                    'icon' => 'icon-video',
                    'children' => $children
                ]
            ], 0);
        }

        return $menus;
    }

    /**
     * Get permission data
     *
     * @param array $permissions
     * @param array $params
     *
     * @return array
     */
    public static function initPermission($permissions = [], $params = [])
    {
        $permissions[] = ['value' => 'can_write', 'text' => '{LNG_Can write} {LNG_Video}'];

        return $permissions;
    }
}

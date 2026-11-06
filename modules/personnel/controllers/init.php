<?php
/**
 * @filesource modules/personnel/controllers/init.php
 *
 * @copyright 2026 Goragod.com
 * @license https://www.kotchasan.com/license/
 */

namespace Personnel\Init;

/**
 * Init Controller for Personnel Module
 *
 * Handles initialization of menus and permissions
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Controller extends \Gcms\Controller
{
    /**
     * Only one installed instance of this owner is allowed — see
     * Index\Page\Model::isSingletonInstalled().
     *
     * @var bool
     */
    public static $singleton = true;

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
        $modules = \Index\Modules\Model::getInstalledModules('personnel');
        foreach ($modules as $item) {
            $children = [];

            if (\Web\Login::checkStatus($login, $item->config, ['can_manage'])) {
                $children[] = [
                    'title' => '{LNG_Personnels}',
                    'url' => '/personnels?module_id='.$item->module_id,
                    'icon' => 'icon-list'
                ];
            }

            if ($params['isAdmin']) {
                $children[] = [
                    'title' => '{LNG_Department}',
                    'url' => '/personnel-category?type=department&module_id='.$item->module_id,
                    'icon' => 'icon-tags'
                ];
                $children[] = [
                    'title' => '{LNG_Settings}',
                    'url' => '/personnel-settings?module_id='.$item->module_id,
                    'icon' => 'icon-cog'
                ];
            }

            if (empty($children)) {
                continue;
            }

            // Insert menus
            $menus = parent::insertMenuAfter($menus, [
                [
                    'title' => $item->topic,
                    'icon' => 'icon-customer',
                    'children' => $children
                ]
            ], 0);
        }

        // return menus
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
    public static function initPermission($permissions, $params)
    {
        $permissions[] = ['value' => 'can_manage_personnel', 'text' => '{LNG_Can manage} {LNG_Personnel}'];

        // return permissions
        return $permissions;
    }
}

<?php
/**
 * @filesource modules/board/controllers/init.php
 *
 * @copyright 2026 Goragod.com
 * @license https://www.kotchasan.com/license/
 */

namespace Board\Init;

/**
 * Init Controller for Board Module
 *
 * Registers admin menus and permissions
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
        $modules = \Index\Modules\Model::getInstalledModules('board');
        foreach ($modules as $item) {
            $children = [];

            if ($params['isAdmin']) {
                $children[] = [
                    'title' => '{LNG_Categories}',
                    'url' => '/board-categories?module_id='.$item->module_id,
                    'icon' => 'icon-tags'
                ];
                $children[] = [
                    'title' => '{LNG_Settings}',
                    'url' => '/board-settings?module_id='.$item->module_id,
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
                    'icon' => 'icon-comments',
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
    public static function initPermission($permissions = [], $params = [])
    {
        $permissions[] = ['value' => 'can_post', 'text' => '{LNG_Can post} {LNG_Board}'];
        $permissions[] = ['value' => 'moderator', 'text' => '{LNG_Moderator} {LNG_Board}'];

        // return permissions
        return $permissions;
    }
}

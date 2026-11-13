<?php
/**
 * @filesource modules/download/controllers/init.php
 *
 * @copyright 2026 Goragod.com
 * @license https://www.kotchasan.com/license/
 */

namespace Download\Init;

/**
 * Init Controller for Download Module
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
        $modules = \Index\Modules\Model::getInstalledModules('download');
        foreach ($modules as $item) {
            $children = [];

            if (\Web\Login::checkStatus($login, $item->config, ['can_upload', 'moderator'])) {
                $children[] = [
                    'title' => '{LNG_List of} {LNG_Download file}',
                    'url' => '/download-setup?module_id='.$item->module_id,
                    'icon' => 'icon-list'
                ];
                $children[] = [
                    'title' => '{LNG_Add} {LNG_Download file}',
                    'url' => '/download-write?module_id='.$item->module_id,
                    'icon' => 'icon-upload'
                ];
            }

            if ($params['isAdmin']) {
                $children[] = [
                    'title' => '{LNG_Category}',
                    'url' => '/download-category?module_id='.$item->module_id,
                    'icon' => 'icon-tags'
                ];
                $children[] = [
                    'title' => '{LNG_Settings}',
                    'url' => '/download-settings?module_id='.$item->module_id,
                    'icon' => 'icon-cog'
                ];
            }

            if (empty($children)) {
                continue;
            }

            $menus = parent::insertMenuAfter($menus, [
                [
                    'title' => $item->topic,
                    'icon' => 'icon-download',
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
        $permissions[] = ['value' => 'can_download', 'text' => '{LNG_Can download}'];
        $permissions[] = ['value' => 'can_upload', 'text' => '{LNG_Can upload}'];
        $permissions[] = ['value' => 'moderator', 'text' => '{LNG_Moderator}'];

        return $permissions;
    }
}

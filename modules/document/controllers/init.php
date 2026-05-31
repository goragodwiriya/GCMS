<?php
/**
 * @filesource modules/document/controllers/init.php
 *
 * @copyright 2026 Goragod.com
 * @license https://www.kotchasan.com/license/
 */

namespace Document\Init;

/**
 * Init Controller for Document Module
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
        $modules = \Index\Modules\Model::getInstalledModules('document');
        foreach ($modules as $item) {
            $children = [];

            if (\Web\Login::checkStatus($login, $item->config, ['can_write', 'can_approve'])) {
                $children[] = [
                    'title' => '{LNG_Articles}',
                    'url' => '/documents?module_id='.$item->module_id,
                    'icon' => 'icon-list'
                ];
            }

            if ($params['isAdmin']) {
                $children[] = [
                    'title' => '{LNG_Categories}',
                    'url' => '/document-categories?module_id='.$item->module_id,
                    'icon' => 'icon-documents'
                ];
                $children[] = [
                    'title' => '{LNG_Settings}',
                    'url' => '/document-settings?module_id='.$item->module_id,
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
                    'icon' => 'icon-file',
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
        $permissions[] = ['value' => 'can_write', 'text' => '{LNG_Can write} {LNG_Document}'];
        $permissions[] = ['value' => 'can_approve', 'text' => '{LNG_Can approve} {LNG_Document}'];

        // return permissions
        return $permissions;
    }
}

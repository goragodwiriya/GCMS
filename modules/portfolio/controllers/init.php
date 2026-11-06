<?php
/**
 * @filesource modules/portfolio/controllers/init.php
 *
 * @copyright 2026 Goragod.com
 * @license https://www.kotchasan.com/license/
 */

namespace Portfolio\Init;

/**
 * Init Controller for Portfolio Module — registers admin menus and
 * permissions for each installed portfolio instance. Not flagged
 * $singleton — a site can reasonably want more than one portfolio
 * section, same as `document` already has multiple installed instances
 * (news/knowledge/blogs on service_goro).
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Controller extends \Gcms\Controller
{
    /**
     * @param array  $menus
     * @param array  $params
     * @param object $login
     *
     * @return array
     */
    public static function initMenus($menus, $params, $login)
    {
        $modules = \Index\Modules\Model::getInstalledModules('portfolio');
        foreach ($modules as $item) {
            $children = [];

            if (\Web\Login::checkStatus($login, $item->config, ['can_write'])) {
                $children[] = [
                    'title' => '{LNG_Portfolio}',
                    'url' => '/portfolio-list?module_id='.$item->module_id,
                    'icon' => 'icon-list'
                ];
            }

            if ($params['isAdmin']) {
                $children[] = [
                    'title' => '{LNG_Settings}',
                    'url' => '/portfolio-settings?module_id='.$item->module_id,
                    'icon' => 'icon-cog'
                ];
            }

            if (empty($children)) {
                continue;
            }

            $menus = parent::insertMenuAfter($menus, [
                [
                    'title' => $item->topic,
                    'icon' => 'icon-portfolio',
                    'children' => $children
                ]
            ], 0);
        }

        return $menus;
    }

    /**
     * @param array $permissions
     * @param array $params
     *
     * @return array
     */
    public static function initPermission($permissions, $params)
    {
        $permissions[] = ['value' => 'can_write', 'text' => '{LNG_Can write} {LNG_Portfolio}'];

        return $permissions;
    }
}

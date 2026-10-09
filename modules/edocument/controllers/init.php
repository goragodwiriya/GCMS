<?php
/**
 * @filesource modules/edocument/controllers/init.php
 *
 * @copyright 2026 Goragod.com
 * @license https://www.kotchasan.com/license/
 */

namespace Edocument\Init;

/**
 * Init Controller for E-Document Module
 *
 * Handles initialization of menus
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
        $modules = \Index\Modules\Model::getInstalledModules('edocument');
        foreach ($modules as $item) {
            $config = \Edocument\Settings\Model::normalizeConfig($item->config);
            $children = [];

            if (\Web\Login::checkStatus($login, $config, ['can_upload', 'moderator'])) {
                $children[] = [
                    'title' => '{LNG_List of} {LNG_E-Document}',
                    'url' => '/edocument-setup?module_id='.$item->module_id,
                    'icon' => 'icon-list'
                ];
                $children[] = [
                    'title' => '{LNG_Add} {LNG_E-Document}',
                    'url' => '/edocument-write?module_id='.$item->module_id,
                    'icon' => 'icon-upload'
                ];
            }

            if ($params['isAdmin'] || \Web\Login::checkStatus($login, $config, ['can_config'])) {
                $children[] = [
                    'title' => '{LNG_Settings}',
                    'url' => '/edocument-settings?module_id='.$item->module_id,
                    'icon' => 'icon-cog'
                ];
            }

            if (empty($children)) {
                continue;
            }

            $menus = parent::insertMenuAfter($menus, [
                [
                    'title' => $item->topic,
                    'icon' => 'icon-edocument',
                    'children' => $children
                ]
            ], 0);
        }

        return $menus;
    }
}

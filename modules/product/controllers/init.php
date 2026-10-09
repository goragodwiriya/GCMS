<?php
/**
 * @filesource modules/product/controllers/init.php
 *
 * @copyright 2026 Goragod.com
 * @license https://www.kotchasan.com/license/
 */

namespace Product\Init;

/**
 * Init Controller for Product Module
 *
 * Registers admin menus and permissions for each installed product instance.
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Controller extends \Gcms\Controller
{
    /**
     * Every public page: the floating cart button of the first product module
     * (\Product\Shop\View::context()), not only the store's own pages — a
     * product added from the home page widget must still be reachable.
     *
     * @param array $modules installed product modules
     */
    public function init($modules)
    {
        if (!empty($modules)) {
            \Product\Shop\View::context(reset($modules));
        }
    }

    /**
     * Register admin menus
     *
     * @param array  $menus
     * @param array  $params
     * @param object $login
     *
     * @return array
     */
    public static function initMenus($menus, $params, $login)
    {
        $modules = \Index\Modules\Model::getInstalledModules('product');
        foreach ($modules as $item) {
            $children = [];

            if (\Product\Init\Controller::allowed($login, $item->config, ['can_manage'])) {
                $children[] = [
                    'title' => '{LNG_Products}',
                    'url' => '/products?module_id='.$item->module_id,
                    'icon' => 'icon-list'
                ];
                $children[] = [
                    'title' => '{LNG_Orders}',
                    'url' => '/product-orders?module_id='.$item->module_id,
                    'icon' => 'icon-cart'
                ];
            }

            if (\Product\Init\Controller::allowed($login, $item->config, ['can_pos'])) {
                $children[] = [
                    'title' => '{LNG_POS}',
                    'url' => '/product-pos?module_id='.$item->module_id,
                    'icon' => 'icon-product'
                ];
            }

            if (\Product\Init\Controller::allowed($login, $item->config, ['can_manage_stock'])) {
                $children[] = [
                    'title' => '{LNG_Stock}',
                    'url' => '/product-stock?module_id='.$item->module_id,
                    'icon' => 'icon-inbox'
                ];
            }

            if ($params['isAdmin']) {
                $children[] = [
                    'title' => '{LNG_Category}',
                    'url' => '/product-category?module_id='.$item->module_id,
                    'icon' => 'icon-tags'
                ];
                $children[] = [
                    'title' => '{LNG_Attributes}',
                    'url' => '/product-attribute?module_id='.$item->module_id,
                    'icon' => 'icon-config'
                ];
                $children[] = [
                    'title' => '{LNG_Shipping}',
                    'url' => '/product-shipping?module_id='.$item->module_id,
                    'icon' => 'icon-shipping'
                ];
                $children[] = [
                    'title' => '{LNG_Settings}',
                    'url' => '/product-settings?module_id='.$item->module_id,
                    'icon' => 'icon-cog'
                ];
            }

            if (empty($children)) {
                continue;
            }

            $menus = parent::insertMenuAfter($menus, [
                [
                    'title' => $item->topic,
                    'icon' => 'icon-product',
                    'children' => $children
                ]
            ], 0);
        }

        return $menus;
    }

    /**
     * Status key in a product module's config => the user permission (the
     * checkboxes registered by initPermission()) that grants the same.
     *
     * @var array
     */
    private static $permissionFor = [
        'can_manage' => 'can_manage_product',
        'can_pos' => 'can_pos',
        'can_verify_payment' => 'can_verify_payment',
        'can_manage_stock' => 'can_manage_stock'
    ];

    /**
     * Can this login do one of $keys in a product module? Either its status
     * is in the module's allowed statuses (Settings page) or the account has
     * the matching permission ticked on the user form — both are offered to
     * the admin, so both have to count.
     *
     * @param object|null  $login
     * @param object|array $config module config
     * @param array        $keys   e.g. ['can_manage']
     *
     * @return object|null the login when allowed
     */
    public static function allowed($login, $config, array $keys)
    {
        if (\Web\Login::checkStatus($login, $config, $keys)) {
            return $login;
        }
        if (empty($login) || !isset($login->permission) || !is_array($login->permission)) {
            return null;
        }
        foreach ($keys as $key) {
            if (isset(self::$permissionFor[$key]) && in_array(self::$permissionFor[$key], $login->permission, true)) {
                return $login;
            }
        }

        return null;
    }

    /**
     * Register module permissions
     *
     * @param array $permissions
     * @param array $params
     *
     * @return array
     */
    public static function initPermission($permissions, $params)
    {
        $permissions[] = ['value' => 'can_manage_product', 'text' => '{LNG_Can manage} {LNG_Products}'];
        $permissions[] = ['value' => 'can_pos', 'text' => '{LNG_Can use} {LNG_POS}'];
        $permissions[] = ['value' => 'can_verify_payment', 'text' => '{LNG_Can verify} {LNG_Payment}'];
        $permissions[] = ['value' => 'can_manage_stock', 'text' => '{LNG_Can manage} {LNG_Stock}'];

        return $permissions;
    }
}

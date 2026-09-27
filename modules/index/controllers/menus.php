<?php
/**
 * @filesource modules/index/controllers/menus.php
 *
 * @copyright 2026 Goragod.com
 * @license https://www.kotchasan.com/license/
 */

namespace Index\Menus;

use Gcms\Api as ApiController;

/**
 * API Authentication Controller
 *
 * Handles user authentication endpoints with production-grade security
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Controller extends \Gcms\Controller
{
    /**
     * Get menu data
     *
     * @param Object $login
     *
     * @return array
     */
    public static function getMenus($login)
    {
        // Menu data - 2-level nested structure
        $menus = [
            'dashboard' => [
                'title' => 'Dashboard',
                'url' => '/',
                'icon' => 'icon-dashboard'
            ]
        ];

        $isAdmin = ApiController::isAdmin($login);

        // Add admin menus if user is admin
        if ($isAdmin) {
            $submenus = [
                [
                    'title' => 'General Settings',
                    'url' => '/general-settings',
                    'icon' => 'icon-cog'
                ],
                [
                    'title' => 'Company Settings',
                    'url' => '/company-settings',
                    'icon' => 'icon-office'
                ],
                [
                    'title' => 'SEO & Social',
                    'url' => '/meta',
                    'icon' => 'icon-world'
                ],
                [
                    'title' => 'Choose Theme',
                    'url' => '/themes',
                    'icon' => 'icon-template'
                ],
                [
                    'title' => 'AI Theme Generator',
                    'url' => '/aitheme',
                    'icon' => 'icon-brush'
                ],
                [
                    'title' => '{LNG_Theme Settings} (Admin)',
                    'url' => '/theme-settings',
                    'icon' => 'icon-brush'
                ],
                [
                    'title' => 'Email Settings',
                    'url' => '/email-settings',
                    'icon' => 'icon-email'
                ],
                [
                    'title' => 'Mail templates',
                    'url' => '/mailtemplates',
                    'icon' => 'icon-email'
                ],
                [
                    'title' => 'Cookie Policy',
                    'url' => '/cookie-policy',
                    'icon' => 'icon-verfied'
                ],
                [
                    'title' => 'Manage languages',
                    'url' => '/languages',
                    'icon' => 'icon-language'
                ],
                [
                    'title' => 'Menus',
                    'icon' => 'icon-menus',
                    'children' => [
                        [
                            'title' => 'Main Menu',
                            'url' => '/menus?parent=0_MAINMENU',
                            'icon' => 'icon-menu'
                        ],
                        [
                            'title' => 'Side Menu',
                            'url' => '/menus?parent=1_SIDEMENU',
                            'icon' => 'icon-menu'
                        ],
                        [
                            'title' => 'Bottom Menu',
                            'url' => '/menus?parent=2_BOTTOMMENU',
                            'icon' => 'icon-menu'
                        ]
                    ]
                ],
                [
                    'title' => 'Web pages',
                    'url' => '/pages',
                    'icon' => 'icon-index'
                ],
                [
                    'title' => 'Installed modules',
                    'url' => '/modules',
                    'icon' => 'icon-modules'
                ],
                [
                    'title' => 'Intro page',
                    'url' => '/intro',
                    'icon' => 'icon-index'
                ],
                [
                    'title' => 'Maintenance mode',
                    'url' => '/maintenance',
                    'icon' => 'icon-index'
                ],
                'widgets' => [
                    'title' => 'Widgets',
                    'icon' => 'icon-widgets',
                    'children' => [
                        [
                            'title' => 'Tags',
                            'url' => '/widgets/tags',
                            'icon' => 'icon-tags'
                        ],
                        [
                            'title' => 'Facebook',
                            'url' => '/widgets/facebook',
                            'icon' => 'icon-facebook'
                        ],
                        [
                            'title' => 'Map',
                            'url' => '/widgets/map',
                            'icon' => 'icon-map'
                        ],
                        [
                            'title' => 'Stats',
                            'url' => '/widgets/stats',
                            'icon' => 'icon-number'
                        ],
                        [
                            'title' => 'Text links',
                            'url' => '/widgets/textlinks',
                            'icon' => 'icon-ads'
                        ]
                    ]
                ]
            ];

            if (ApiController::isSuperAdmin($login) || ApiController::isNotDemoMode($login)) {
                $menus['users'] = [
                    'title' => 'Users',
                    'url' => '/users',
                    'icon' => 'icon-users'
                ];
                $submenus[] = [
                    'title' => 'Member status',
                    'url' => '/user-status',
                    'icon' => 'icon-star0'
                ];
                $submenus[] = [
                    'title' => 'LINE Settings',
                    'url' => '/line-settings',
                    'icon' => 'icon-line'
                ];
                $submenus[] = [
                    'title' => 'Telegram Settings',
                    'url' => '/telegram-settings',
                    'icon' => 'icon-telegram'
                ];
                $submenus[] = [
                    'title' => 'SMS Settings',
                    'url' => '/sms-settings',
                    'icon' => 'icon-mobile'
                ];
                $submenus[] = [
                    'title' => 'AI Settings',
                    'url' => '/ai-settings',
                    'icon' => 'icon-support'
                ];
                $submenus['database-backup'] = [
                    'title' => 'Database Import/Export',
                    'url' => '/database-backup',
                    'icon' => 'icon-database'
                ];
            }
            $submenus[] = [
                'title' => 'Usage history',
                'url' => '/usage',
                'icon' => 'icon-report'
            ];
            $menus['settings'] = [
                'title' => 'Settings',
                'icon' => 'icon-settings',
                'children' => $submenus
            ];
        }

        // Load module menus
        $params = ['isAdmin' => $isAdmin];
        $menus = self::initModule($menus, 'initMenus', $login, $params);

        // return menus
        return self::normalizeMenus($menus);
    }

    /**
     * Normalize menu array for rendering:
     * Recursively strips string keys from all children arrays
     * so they serialize as JSON arrays (not objects).
     *
     * @param array $menus Menu array (may have string keys used for manipulation)
     *
     * @return array Sequential array safe for JSON/frontend rendering
     */
    private static function normalizeMenus($menus)
    {
        $result = [];
        foreach ($menus as $item) {
            if (isset($item['children'])) {
                $item['children'] = self::normalizeMenus($item['children']);
            }
            $result[] = $item;
        }
        return $result;
    }
}

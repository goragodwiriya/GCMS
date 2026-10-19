<?php
/**
 * @filesource modules/index/models/adminmenus.php
 *
 * @copyright 2026 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Index\AdminMenus;

/**
 * API Admin Menus Model
 *
 * Handles menu table operations
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Model extends \Kotchasan\Model
{
    /**
     * Query data to send to DataTable
     *
     * @param array $params
     *
     * @return \Kotchasan\QueryBuilder\QueryBuilderInterface
     */
    public static function toDataTable($params)
    {
        return static::createQuery()
            ->select(
                'U.id',
                'U.menu_text',
                'U.menu_url',
                'U.alias',
                'U.level',
                'U.parent',
                'U.menu_order',
                'U.language',
                'U.id move_left',
                'U.id move_right',
                'U.published',
                'U.menu_tooltip',
                'U.accesskey',
                'U.index_id',
                'M.module',
                'D.language ilanguage'
            )
            ->from('menus U')
            ->join('index I', ['I.id', 'U.index_id'], 'LEFT')
            ->join('modules M', ['M.id', 'I.module_id'], 'LEFT')
            ->join('index_detail D', [['D.id', 'I.id'], ['D.module_id', 'I.module_id']], 'LEFT')
            ->where(['U.parent', $params['parent']]);
    }
}

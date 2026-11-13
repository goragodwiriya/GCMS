<?php
/**
 * @filesource modules/product/models/sitemap.php
 *
 * @copyright 2026 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Product\Sitemap;

/**
 * All published products
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Model
{
    /**
     * All published products
     *
     * @param array $ids Array of module_id
     *
     * @return array
     */
    public static function getProducts($ids)
    {
        return \Kotchasan\DB::create()->select(
            'product',
            [['module_id', $ids], ['published', 1]],
            ['cache' => true],
            ['id', 'module_id', 'alias', 'updated_at']
        );
    }
}

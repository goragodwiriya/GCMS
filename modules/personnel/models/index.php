<?php
/**
 * @filesource modules/personnel/models/index.php
 *
 * @copyright 2026 Goragod.com
 * @license https://www.kotchasan.com/license/
 */

namespace Personnel\Index;

/**
 * Personnel Index Model – Frontend
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Model extends \Kotchasan\Model
{
    /**
     * Get single person detail
     *
     * @param object $index
     *
     * @return object|false
     */
    public static function getPerson($index)
    {
        if (empty($index->id)) {
            return false;
        }
        $person = static::createQuery()
            ->select()
            ->from('personnel')
            ->where([
                ['id', $index->id],
                ['published', 1]
            ])
            ->cacheOn()
            ->first();
        if ($person) {
            foreach ($person as $key => $value) {
                $index->$key = $value;
            }
            return $index;
        }
        return false;
    }

    /**
     * Get all personnel for the module (grouped for frontend display)
     *
     * @param object $index
     *
     * @return object
     */
    public static function getPersonnel($index)
    {
        $where = [
            ['module_id', $index->module_id],
            ['published', 1]
        ];
        if (!empty($index->department)) {
            $where[] = ['department', $index->department];
        }
        $items = static::createQuery()
            ->select('id', 'name', 'department', 'position', 'phone', 'email', 'detail', 'level', 'picture')
            ->from('personnel')
            ->where($where)
            ->orderBy('department', 'ASC')
            ->orderBy('level', 'ASC')
            ->orderBy('name', 'ASC')
            ->cacheOn()
            ->fetchAll();

        $index->items = $items;

        return $index;
    }
}

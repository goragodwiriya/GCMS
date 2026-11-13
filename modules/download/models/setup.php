<?php
/**
 * @filesource modules/download/models/setup.php
 *
 * @copyright 2026 Goragod.com
 * @license https://www.kotchasan.com/license/
 */

namespace Download\Setup;

/**
 * Download Setup Model
 *
 * DataTable query + bulk delete helpers
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Model extends \Kotchasan\Model
{
    /**
     * Build DataTable query.
     *
     * @param array $params
     *
     * @return \Kotchasan\QueryBuilder\QueryBuilderInterface
     */
    public static function toDataTable($params)
    {
        $where = [
            ['module_id', $params['module_id']]
        ];

        if (isset($params['category_id']) && (int) $params['category_id'] >= 0) {
            $where[] = ['category_id', (int) $params['category_id']];
        }

        if (!empty($params['member_id'])) {
            $where[] = ['member_id', (int) $params['member_id']];
        }

        $query = static::createQuery()
            ->select('id', 'module_id', 'category_id', 'member_id', 'name', 'ext', 'detail', 'size', 'updated_at', 'downloads', 'file')
            ->from('download')
            ->where($where);

        if (!empty($params['search'])) {
            $search = '%'.$params['search'].'%';
            $query->where([
                ['name', 'LIKE', $search],
                ['ext', 'LIKE', $search],
                ['detail', 'LIKE', $search],
                ['file', 'LIKE', $search]
            ], 'OR');
        }

        return $query;
    }

    /**
     * Remove selected files.
     *
     * @param array $ids
     * @param int $module_id
     * @param int $member_id 0 = no owner restriction
     *
     * @return array [removed ids]
     */
    public static function remove(array $ids, $module_id, $member_id = 0)
    {
        if (empty($ids)) {
            return [];
        }

        $where = [
            ['id', $ids],
            ['module_id', (int) $module_id]
        ];
        if ($member_id > 0) {
            $where[] = ['member_id', (int) $member_id];
        }

        $query = static::createQuery()
            ->select('id', 'file')
            ->from('download')
            ->where($where)
            ->fetchAll();

        if (empty($query)) {
            return [];
        }

        $removed = [];
        foreach ($query as $item) {
            $removed[] = (int) $item->id;
            $path = self::toFilePath($item->file);
            if ($path && is_file($path)) {
                @unlink($path);
            }
        }

        if (!empty($removed)) {
            \Kotchasan\DB::create()->delete('download', [
                ['id', $removed],
                ['module_id', (int) $module_id]
            ], 0);
        }

        return $removed;
    }

    /**
     * Resolve stored file value to absolute path.
     *
     * @param string $file
     *
     * @return string|null
     */
    public static function toFilePath($file)
    {
        $file = trim(str_replace('\\', '/', (string) $file));
        if ($file === '') {
            return null;
        }

        if (strpos($file, '..') !== false) {
            return null;
        }

        if (strpos($file, DATA_FOLDER) === 0) {
            $file = substr($file, strlen(DATA_FOLDER));
        }
        $file = ltrim($file, '/');

        if (!preg_match('/^[a-zA-Z0-9_\-\/\.]+$/', $file)) {
            return null;
        }

        return ROOT_PATH.DATA_FOLDER.$file;
    }
}

<?php
/**
 * @filesource modules/edocument/models/setup.php
 *
 * @copyright 2026 Goragod.com
 * @license https://www.kotchasan.com/license/
 */

namespace Edocument\Setup;

/**
 * E-Document Setup Model
 *
 * DataTable query + bulk delete + file path helpers
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
            ['module_id', (int) $params['module_id']]
        ];

        if (!empty($params['sender_id'])) {
            $where[] = ['sender_id', (int) $params['sender_id']];
        }

        $query = static::createQuery()
            ->select('id', 'module_id', 'sender_id', 'document_no', 'topic', 'ext', 'detail', 'size', 'last_update', 'downloads', 'file', 'reciever')
            ->from('edocument')
            ->where($where);

        if (!empty($params['search'])) {
            $search = '%'.$params['search'].'%';
            $query->where([
                ['document_no', 'LIKE', $search],
                ['topic', 'LIKE', $search],
                ['detail', 'LIKE', $search]
            ], 'OR');
        }

        return $query;
    }

    /**
     * Display names of the given members, [id => name].
     *
     * @param array $ids
     *
     * @return array
     */
    public static function memberNames(array $ids)
    {
        $ids = array_values(array_unique(array_filter(array_map('intval', $ids))));
        if (empty($ids)) {
            return [];
        }

        $names = [];
        $query = static::createQuery()
            ->select('id', 'name', 'username')
            ->from('user')
            ->where(['id', $ids]);
        foreach ($query->fetchAll() as $item) {
            $name = trim((string) $item->name);
            $names[(int) $item->id] = $name === '' ? (string) $item->username : $name;
        }

        return $names;
    }

    /**
     * Remove selected documents, their files and download records.
     *
     * @param array $ids
     * @param int $module_id
     * @param int $sender_id 0 = no owner restriction
     *
     * @return array [removed ids]
     */
    public static function remove(array $ids, $module_id, $sender_id = 0)
    {
        if (empty($ids)) {
            return [];
        }

        $where = [
            ['id', $ids],
            ['module_id', (int) $module_id]
        ];
        if ($sender_id > 0) {
            $where[] = ['sender_id', (int) $sender_id];
        }

        $query = static::createQuery()
            ->select('id', 'file')
            ->from('edocument')
            ->where($where)
            ->fetchAll();

        $removed = [];
        foreach ($query as $item) {
            $removed[] = (int) $item->id;
            $path = self::toFilePath($item->file);
            if ($path && is_file($path)) {
                @unlink($path);
            }
        }

        if (!empty($removed)) {
            $db = \Kotchasan\DB::create();
            $db->delete('edocument', [
                ['id', $removed],
                ['module_id', (int) $module_id]
            ], 0);
            $db->delete('edocument_download', ['document_id', $removed], 0);
        }

        return $removed;
    }

    /**
     * Directory (relative to ROOT_PATH) that holds uploaded documents.
     *
     * @return string
     */
    public static function uploadDir()
    {
        return DATA_FOLDER.'edocument/';
    }

    /**
     * Resolve a stored file name to its absolute path.
     * `edocument`.`file` holds just the file name inside uploadDir().
     *
     * @param string $file
     *
     * @return string|null
     */
    public static function toFilePath($file)
    {
        $file = trim((string) $file);
        if ($file === '' || !preg_match('/^[a-zA-Z0-9_\-\.]+$/', $file) || strpos($file, '..') !== false) {
            return null;
        }

        return ROOT_PATH.self::uploadDir().$file;
    }

    /**
     * Public URL of a stored file, empty when the file is missing.
     *
     * @param string $file
     *
     * @return string
     */
    public static function toFileUrl($file)
    {
        $path = self::toFilePath($file);

        return $path && is_file($path) ? WEB_URL.self::uploadDir().$file : '';
    }

    /**
     * Decode `edocument`.`reciever` into member statuses.
     * Legacy rows hold either JSON ([-1]) or PHP serialize (a:1:{i:0;i:-1;}).
     *
     * @param string $value
     *
     * @return array
     */
    public static function parseReciever($value)
    {
        $value = (string) $value;
        $datas = json_decode($value, true);
        if (!is_array($datas)) {
            $datas = @unserialize($value, ['allowed_classes' => false]);
        }
        if (!is_array($datas)) {
            return [];
        }

        return array_values(array_unique(array_map('intval', $datas)));
    }
}

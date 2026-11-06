<?php
/**
 * @filesource modules/edocument/models/report.php
 *
 * @copyright 2026 Goragod.com
 * @license https://www.kotchasan.com/license/
 */

namespace Edocument\Report;

/**
 * E-Document download history model
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Model extends \Kotchasan\Model
{
    /**
     * Build DataTable query: who downloaded one document.
     * Guests share one row per document (member_id 0).
     *
     * @param array $params
     *
     * @return \Kotchasan\QueryBuilder\QueryBuilderInterface
     */
    public static function toDataTable($params)
    {
        $query = static::createQuery()
            ->select('D.id', 'D.member_id', 'D.downloads', 'D.last_update', 'U.name', 'U.username', 'U.status')
            ->from('edocument_download D')
            ->join('user U', ['U.id', 'D.member_id'], 'LEFT')
            ->where([
                ['D.document_id', (int) $params['document_id']],
                ['D.module_id', (int) $params['module_id']]
            ]);

        if (!empty($params['search'])) {
            $search = '%'.$params['search'].'%';
            $query->where([
                ['U.name', 'LIKE', $search],
                ['U.username', 'LIKE', $search]
            ], 'OR');
        }

        return $query;
    }

    /**
     * Document header for the report page.
     *
     * @param int $id
     * @param int $module_id
     *
     * @return object|null
     */
    public static function getDocument($id, $module_id)
    {
        return static::createQuery()
            ->select('id', 'module_id', 'sender_id', 'document_no', 'topic', 'ext', 'detail', 'downloads')
            ->from('edocument')
            ->where([
                ['id', (int) $id],
                ['module_id', (int) $module_id]
            ])
            ->first();
    }
}

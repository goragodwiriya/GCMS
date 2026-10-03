<?php
/**
 * @filesource modules/index/models/adminpages.php
 *
 * @copyright 2026 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Index\AdminPages;

use Gcms\Api as ApiController;
use Kotchasan\Language;

/**
 * API Admin Pages Model
 *
 * Handles page table operations
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Model extends \Kotchasan\Model
{
    /**
     * User permission that lets a member write web pages
     *
     * @var string
     */
    const WRITE_PERMISSION = 'can_write_page';

    /**
     * Full control of web pages: create, delete, copy, rename and choose the
     * writer of a page (admins and can_config)
     *
     * @param object|null $login
     *
     * @return bool
     */
    public static function canManage($login)
    {
        return ApiController::canModify($login, ['can_config']);
    }

    /**
     * May open the web pages list (managers and writers, demo accounts included)
     *
     * @param object|null $login
     *
     * @return bool
     */
    public static function canView($login)
    {
        return ApiController::hasPermission($login, ['can_config', self::WRITE_PERMISSION]);
    }

    /**
     * May change pages at all (managers and writers, not demo accounts)
     *
     * @param object|null $login
     *
     * @return bool
     */
    public static function canWrite($login)
    {
        return ApiController::canModify($login, ['can_config', self::WRITE_PERMISSION]);
    }

    /**
     * Writer assigned to a page: index.member_id when that member holds the
     * write permission, otherwise 0 (every writer). Pages created before writers
     * existed keep their creator (usually an admin) in member_id — they count as
     * open to every writer, as does a page whose writer lost the permission.
     *
     * @param int $memberId index.member_id
     *
     * @return int
     */
    public static function writerId($memberId)
    {
        static $writers = [];
        $memberId = (int) $memberId;
        if ($memberId <= 0) {
            return 0;
        }
        if (!isset($writers[$memberId])) {
            $user = static::createQuery()
                ->select('permission')
                ->from('user')
                ->where(['id', $memberId])
                ->first();
            $writers[$memberId] = $user && strpos((string) $user->permission, ','.self::WRITE_PERMISSION.',') !== false
                ? $memberId
                : 0;
        }

        return $writers[$memberId];
    }

    /**
     * May edit this page: managers edit every page; a writer edits the pages
     * without an assigned writer and the pages assigned to them — both the
     * permission and the assignment are needed
     *
     * @param object|null $login
     * @param object      $page  index row with member_id (and owner)
     *
     * @return bool
     */
    public static function canEdit($login, $page)
    {
        if (self::canManage($login)) {
            return true;
        }
        if (!$page || !self::canWrite($login) || (isset($page->owner) && $page->owner !== 'index')) {
            return false;
        }
        $writer = self::writerId($page->member_id ?? 0);

        return $writer === 0 || $writer === (int) $login->id;
    }

    /**
     * Condition group (OR) on `index I` LEFT JOIN `user U` ON U.id = I.member_id
     * matching the pages a writer may edit — see writerId()
     *
     * @param object $login
     *
     * @return array
     */
    private static function openToWriter($login)
    {
        return [
            ['I.member_id', [0, (int) $login->id]],
            ['U.id', null],
            ['U.permission', null],
            ['U.permission', 'NOT LIKE', '%,'.self::WRITE_PERMISSION.',%']
        ];
    }

    /**
     * The given page ids this user may edit
     *
     * @param array       $ids
     * @param object|null $login
     *
     * @return int[]
     */
    public static function editableIds($ids, $login)
    {
        $ids = array_values(array_filter(array_map('intval', (array) $ids)));
        if (empty($ids) || self::canManage($login)) {
            return $ids;
        }
        if (!self::canWrite($login)) {
            return [];
        }

        $rows = static::createQuery()
            ->select('I.id')
            ->from('index I')
            ->join('modules M', [['M.id', 'I.module_id']])
            ->join('user U', [['U.id', 'I.member_id']], 'LEFT')
            ->where([
                ['I.id', $ids],
                ['I.index', 1],
                ['M.owner', 'index']
            ])
            ->where(self::openToWriter($login), 'OR')
            ->fetchAll();

        return array_map(fn($row) => (int) $row->id, $rows);
    }

    /**
     * Writer choices for a page: "All writers" (0) and the members holding the
     * write permission (an inactive member stays listed while assigned)
     *
     * @param int $current Assigned writer (writerId())
     *
     * @return array [{value, text}, ...]
     */
    public static function getWriterOptions($current = 0)
    {
        $users = static::createQuery()
            ->select('id', 'name', 'username', 'active')
            ->from('user')
            ->where(['permission', 'LIKE', '%,'.self::WRITE_PERMISSION.',%'])
            ->orderBy('name')
            ->fetchAll();

        $options = [['value' => '0', 'text' => 'All writers']];
        foreach ($users as $user) {
            if (empty($user->active) && (int) $user->id !== (int) $current) {
                continue;
            }
            $name = trim((string) $user->name);
            $options[] = [
                'value' => (string) $user->id,
                'text' => $name === '' ? $user->username : $name.' ('.$user->username.')'
            ];
        }

        return $options;
    }

    /**
     * May this member be assigned as writer (0 = all writers)
     *
     * @param int $memberId
     *
     * @return bool
     */
    public static function isWriterOption($memberId)
    {
        $memberId = (int) $memberId;

        return $memberId === 0 || self::writerId($memberId) === $memberId;
    }

    /**
     * Query data to send to DataTable
     * Writers see only the pages without a writer and the pages assigned to them.
     *
     * @param array       $params
     * @param object|null $login
     *
     * @return \Kotchasan\QueryBuilder\QueryBuilderInterface
     */
    public static function toDataTable($params, $login = null)
    {
        $query = static::createQuery()
            ->select(
                'D.id',
                'D.module_id',
                'D.topic',
                'I.published',
                'I.published_date',
                'D.language',
                'M.module',
                'I.member_id',
                'U.name writer',
                'U.permission writer_permission',
                'I.updated_at',
                'I.visited'
            )
            ->from('index I')
            ->join('modules M', [['M.id', 'I.module_id']])
            ->join('index_detail D', [['D.id', 'I.id'], ['D.module_id', 'I.module_id']])
            ->join('user U', [['U.id', 'I.member_id']], 'LEFT')
            ->where([
                ['M.owner', 'index'],
                ['I.index', 1]
            ]);
        if (!self::canManage($login)) {
            $query->where(self::openToWriter($login), 'OR');
        }

        // Search (OR across topic / module)
        if (!empty($params['search'])) {
            $search = '%'.$params['search'].'%';
            $query->where([
                ['D.topic', 'LIKE', $search],
                ['M.module', 'LIKE', $search]
            ], 'OR');
        }

        return $query;
    }

    /**
     * Delete page by ID
     * Removes records from index_detail and index tables,
     * and unlinks any menus that pointed to this page.
     *
     * @param int|array $ids Page ID or array of IDs
     *
     * @return int Number of deleted index records (0 if not found)
     */
    public static function remove($ids)
    {
        if (empty($ids)) {
            return 0;
        }

        $search = \Kotchasan\Model::createQuery()
            ->select('D.id', 'D.module_id', 'D.language')
            ->from('index_detail D')
            ->join('index_detail T', ['T.module_id', 'D.module_id'])
            ->join('index I', ['I.module_id', 'T.module_id'])
            ->where([
                ['I.index', 1],
                ['T.id', $ids]
            ])
            ->fetchAll();
        $db = \Kotchasan\DB::create();
        $removed = 0;
        foreach ($search as $item) {
            $removed++;
            $db->delete('index', ['id', $item->id]);
            $db->delete('index_detail', [['id', $item->id], ['language', $item->language]]);
        }
        return $removed;
    }

    /**
     * Update status column for given page IDs
     *
     * @param int|array $ids Page ID or array of IDs
     * @param string $column
     * @param mixed $value
     *
     * @return int
     */
    public static function updateStatus($ids, $column, $value)
    {
        if (empty($ids)) {
            return 0;
        }

        return \Kotchasan\DB::create()->update('index', ['id', $ids], [$column => $value]);
    }
}

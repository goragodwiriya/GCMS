<?php
/**
 * @filesource widgets/stats/controllers/table.php
 *
 * Stats Widget — Table Controller
 *
 * Handles GET data and POST bulk-actions for the Stats widget table.
 * Called indirectly via Index\Widgets\Controller::table() /
 * Index\Widgets\Controller::tableaction().
 *
 * @copyright 2026 Goragod.com
 * @license https://www.kotchasan.com/license/
 */

namespace Widgets\Stats\Controllers;

use Gcms\Api as ApiController;
use Kotchasan\Http\Request;

/**
 * Stats Widget — Table Controller
 *
 * Extends \Gcms\Table but overrides executeDataTable() to work with JSON
 * file storage instead of a database table.
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Table extends \Gcms\Table
{
    // -------------------------------------------------------------------------
    // \Gcms\Table hooks
    // -------------------------------------------------------------------------

    /**
     * Extract widget-specific query parameters from the request.
     *
     * @param Request $request
     * @param object  $login
     *
     * @return array
     */
    protected function getCustomParams(Request $request, $login): array
    {
        return [
            'name' => $request->get('name')->filter('a-z0-9_'),
            'search' => $request->get('search')->topic(),
            'sort' => $request->get('sort')->toString() ?: 'order asc'
        ];
    }

    /**
     * Authorization check.
     *
     * @param Request $request
     * @param object  $login
     *
     * @return true|\Kotchasan\Http\Response
     */
    protected function checkAuthorization(Request $request, $login)
    {
        if (!ApiController::hasPermission($login, ['can_config'])) {
            return $this->errorResponse('Permission required', 403);
        }

        return true;
    }

    /**
     * Override executeDataTable() to source data from JSON instead of DB.
     *
     * @param array  $params
     * @param object $login
     *
     * @return array  ['data' => [...], 'meta' => [...]]
     */
    protected function executeDataTable(array $params, $login)
    {
        $all = \Widgets\Stats\Models\Index::getAll();

        // Filter by name (set/group)
        $filterName = $params['name'] ?? '';
        if (!empty($filterName)) {
            $all = array_values(array_filter($all, function ($item) use ($filterName) {
                return ($item['name'] ?? 'default') === $filterName;
            }));
        }

        // Filter by search
        $search = $params['search'] ?? '';
        if (!empty($search)) {
            $searchLower = mb_strtolower($search);
            $all = array_values(array_filter($all, function ($item) use ($searchLower) {
                return mb_strpos(mb_strtolower($item['label'] ?? ''), $searchLower) !== false
                || mb_strpos(mb_strtolower($item['description'] ?? ''), $searchLower) !== false
                || mb_strpos(mb_strtolower($item['name'] ?? ''), $searchLower) !== false;
            }));
        }

        // Sort
        $sortStr = $params['sort'] ?? 'order asc';
        $sortData = $this->parseSort($sortStr);
        $sorts = $sortData['columns'];
        $sortOrders = $sortData['directions'];

        if (!empty($sorts)) {
            usort($all, function ($a, $b) use ($sorts, $sortOrders) {
                foreach ($sorts as $k => $col) {
                    $dir = $sortOrders[$k] ?? 'asc';
                    $av = $a[$col] ?? 0;
                    $bv = $b[$col] ?? 0;
                    $cmp = is_numeric($av) && is_numeric($bv)
                        ? ($av <=> $bv)
                        : strcmp((string) $av, (string) $bv);
                    if ($cmp !== 0) {
                        return $dir === 'desc' ? -$cmp : $cmp;
                    }
                }
                return 0;
            });
        } else {
            // Default sort by order ascending
            usort($all, function ($a, $b) {
                return ($a['order'] ?? 0) <=> ($b['order'] ?? 0);
            });
        }

        // Pagination
        $pageSize = max(1, min(100, (int) ($params['pageSize'] ?? 25)));
        $page = max(1, (int) ($params['page'] ?? 1));
        $total = count($all);
        $totalPages = $total > 0 ? (int) ceil($total / $pageSize) : 1;

        if ($page > $totalPages) {
            $page = $totalPages;
        }

        $offset = ($page - 1) * $pageSize;
        $data = array_slice($all, $offset, $pageSize);

        // Convert arrays to stdClass objects for consistency
        $data = array_map(function ($item) {
            return (object) $item;
        }, $data);

        $meta = $params;
        $meta['page'] = $page;
        $meta['pageSize'] = $pageSize;
        $meta['total'] = $total;
        $meta['totalPages'] = $totalPages;

        return ['data' => $data, 'meta' => $meta];
    }

    /**
     * Available filter options for the DataTable name column.
     *
     * @param array  $params
     * @param object $login
     *
     * @return array
     */
    protected function getFilters($params, $login = null)
    {
        $names = \Widgets\Stats\Models\Index::getAllSetNames();
        $result = [];
        foreach ($names as $name) {
            $result[] = (object) ['id' => $name, 'name' => $name];
        }
        return ['name' => $result];
    }

    // -------------------------------------------------------------------------
    // Action handlers
    // -------------------------------------------------------------------------

    /**
     * Handle edit row action — redirect to edit page.
     *
     * @param Request $request
     * @param object  $login
     *
     * @return \Kotchasan\Http\Response
     */
    protected function handleEditAction(Request $request, $login)
    {
        $id = $request->post('id')->toInt();
        return $this->redirectResponse('/widgets/stats/stat?id='.$id);
    }

    /**
     * Handle delete bulk action.
     *
     * @param Request $request
     * @param object  $login
     *
     * @return \Kotchasan\Http\Response
     */
    protected function handleDeleteAction(Request $request, $login)
    {
        if (!ApiController::canModify($login, ['can_config'])) {
            return $this->errorResponse('Permission required', 403);
        }

        $ids = $request->request('ids', [])->toInt();
        $removed = \Widgets\Stats\Models\Index::removeItems($ids);

        if ($removed === 0) {
            return $this->errorResponse('Delete action failed', 400);
        }

        \Index\Log\Model::add(0, 'widgets', 'Stats', 'Deleted stat items: '.implode(', ', $ids), $login->id);

        return $this->redirectResponse('reload', 'Deleted '.$removed.' item(s) successfully');
    }

    /**
     * Handle reorder action (drag-and-drop row sorting).
     *
     * @param Request $request
     * @param object  $login
     *
     * @return \Kotchasan\Http\Response
     */
    protected function handleReorderAction(Request $request, $login)
    {
        if (!ApiController::canModify($login, ['can_config'])) {
            return $this->errorResponse('Permission required', 403);
        }

        $order = $request->request('order', [])->toArray();
        if (empty($order)) {
            return $this->errorResponse('Order data is required', 400);
        }

        \Widgets\Stats\Models\Index::reorder($order);

        return $this->successResponse(null, 'Reordered successfully');
    }
}

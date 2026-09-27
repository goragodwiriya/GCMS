<?php
/**
 * @filesource modules/index/controllers/adminmenus.php
 *
 * @copyright 2026 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Index\AdminMenus;

use Gcms\Api as ApiController;
use Kotchasan\Http\Request;
use Kotchasan\Http\Response;

/**
 * API Admin Menus Controller
 *
 * Handles menu management endpoints
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Controller extends \Gcms\Table
{
    /**
     * Allowed sort columns (empty = allow all)
     *
     * @var array
     */
    protected $allowedSortColumns = ['menu_order'];

    /**
     * @var int
     */
    protected $toplvl = 0;

    /**
     * Get custom parameters for users table
     *
     * @param Request $request
     * @param object $login
     *
     * @return array
     */
    protected function getCustomParams(Request $request, $login): array
    {
        return [
            'parent' => $request->get('parent', '0_MAINMENU')->topic(),
            'sort' => 'menu_order'
        ];
    }

    /**
     * Check authorization for user management
     * Only admins can access
     *
     * @param Request $request
     * @param $login
     *
     * @return mixed
     */
    protected function checkAuthorization(Request $request, $login)
    {
        // Super-admin, or a staff account with the can_config permission and not in demo mode
        if (!ApiController::canModify($login)) {
            return $this->errorResponse('Permission required', 403);
        }

        return true;
    }

    /**
     * Query data to send to DataTable
     *
     * @param array $params
     * @param object $login
     *
     * @return \Kotchasan\QueryBuilder\QueryBuilderInterface
     */
    protected function toDataTable($params, $login = null)
    {
        return \Index\AdminMenus\Model::toDataTable($params);
    }

    /**
     * Format user list with additional display fields
     *
     * @param array $datas
     * @param object $login
     *
     * @return array
     */
    protected function formatDatas(array $datas, $login = null): array
    {
        $data = [];
        foreach ($datas as $i => $row) {
            $text = '';
            for ($j = 0; $j < $row->level; ++$j) {
                $text .= "\xE2\x80\x83";
            }
            $row->menu_text = (empty($text) ? '' : $text.'↳ ').$row->menu_text;
            $row->move_left = $i === 0 || $row->level == 0 ? 0 : $row->move_left;
            $row->move_right = $i === 0 || $row->level > $this->toplvl ? 0 : $row->move_right;
            if (empty($row->index_id)) {
                $row->module = $row->menu_url;
            } else {
                $row->module .= empty($row->ilanguage) ? '' : ' ('.$row->ilanguage.')';
            }

            $data[] = $row;

            $this->toplvl = $row->level;
        }
        return $data;
    }

    /**
     * Get filters for table response
     *
     * @param array $params
     * @param object $login
     *
     * @return array
     */
    protected function getFilters($params, $login = null)
    {
        return [
            'published' => [
                ['value' => '0', 'text' => '{LNG_Do not show}'],
                ['value' => '1', 'text' => '{LNG_Show}'],
                ['value' => '2', 'text' => '{LNG_Display when logged in}'],
                ['value' => '3', 'text' => '{LNG_Display when not logged in}']
            ]
        ];
    }

    /**
     * Handle delete action
     *
     * @param Request $request
     * @param object $login
     *
     * @return array
     */
    protected function handleDeleteAction(Request $request, $login)
    {
        if (!ApiController::canModify($login)) {
            return $this->errorResponse('Failed to process request', 403);
        }

        $id = $request->request('id')->toInt();
        $removeCount = \Kotchasan\DB::create()->delete('menus', ['id', $id]);

        if (empty($removeCount)) {
            return $this->errorResponse('Delete action failed', 400);
        }

        \Index\Log\Model::add(0, 'index', 'Index', 'Delete Menu ID: '.$id, $login->id);

        return $this->redirectResponse('reload', 'Deleted menu successfully');
    }

    /**
     * Handle edit action
     *
     * @param Request $request
     * @param object $login
     *
     * @return array
     */
    protected function handleEditAction(Request $request, $login)
    {
        $id = $request->post('id')->toInt();

        return $this->redirectResponse('/menu?id='.$id);
    }

    /**
     * Handle reorder action
     *
     * @param Request $request
     * @param object $login
     *
     * @return Response
     */
    protected function handleReorderAction(Request $request, $login)
    {
        // Same permission rule as checkAuthorization() above.
        if (!ApiController::canModify($login)) {
            return $this->errorResponse('Failed to process request', 403);
        }

        // Get new menu order from request
        $orders = $request->post('order')->toArray();
        if (empty($orders)) {
            return $this->errorResponse('No menu order provided', 400);
        }

        $menus = [];
        foreach ($orders as $order) {
            $menus[$order['id']] = $order['position'];
        }

        // Database connection
        $db = \Kotchasan\DB::create();

        // Get current levels of menus
        $query = $db->select('menus', [['id', array_keys($menus)]], [], ['id', 'level']);
        foreach ($query as $item) {
            $levels[$item->id] = $item->level;
        }

        // Reorder menus and update levels
        $top_id = 0;
        foreach ($menus as $id => $position) {
            if ($top_id == 0) {
                $level = 0;
            } else {
                $level = max(0, min($levels[$top_id] + 1, $levels[$id]));
            }
            $top_id = $id;
            // save
            $db->update('menus', ['id', $id], [
                'menu_order' => $position,
                'level' => $level
            ]);
        }

        // Log the action
        \Index\Log\Model::add(0, 'index', 'Index', 'Reorder menus: '.implode(', ', array_keys($menus)), $login->id);

        // Reload
        return $this->redirectResponse('reload', 'Menu order updated successfully');
    }

    /**
     * Handle inactive action - Unable to log in
     *
     * @param Request $request
     * @param object $login
     *
     * @return Response
     */
    protected function handleMoveLeftAction(Request $request, $login)
    {
        return $this->updateLevel($request, $login, 'move_left');
    }

    /**
     * Handle activate action - Accept member verification request
     *
     * @param Request $request
     * @param object $login
     *
     * @return Response
     */
    protected function handleMoveRightAction(Request $request, $login)
    {
        return $this->updateLevel($request, $login, 'move_right');
    }

    /**
     * Update menu level when moving left or right
     *
     * @param Request $request
     * @param $login
     * @param string $action
     *
     * @return mixed
     */
    private function updateLevel(Request $request, $login, $action)
    {
        // Same permission rule as checkAuthorization() above.
        if (!ApiController::canModify($login)) {
            return $this->errorResponse('Failed to process request', 403);
        }

        // Get menu data from request
        $row = json_decode($request->post('row')->toJson(), true);
        if (!is_array($row) || empty($row)) {
            return $this->errorResponse('Invalid menu data', 400);
        }

        // Database connection
        $db = \Kotchasan\DB::create();

        // Get all sibling menus of the same parent
        $query = $db->select('menus', [['parent', $row['parent']]], [], ['id', 'level']);

        // Update levels
        $top_level = 0;
        foreach ($query as $a => $item) {
            if ($a === 0) {
                $level = 0;
            } elseif ($item->id === $row['id']) {
                if ($action == 'move_right') {
                    $level = min($top_level + 1, $item->level + 1, 2);
                } else {
                    $level = max(0, $item->level - 1);
                }
            } else {
                $level = max(0, min($top_level + 1, $item->level));
            }
            $top_level = $level;
            if ($level != $item->level) {
                $db->update('menus', ['id', $item->id], ['level' => $level]);
            }
        }

        // Log the action
        \Index\Log\Model::add($item->id, 'index', 'Index', 'Move menu '.str_replace('move_', '', $action).': '.$item->id, $login->id);

        // Redirect to the same page with a success message
        return $this->redirectResponse('reload', 'Menu moved successfully');
    }
}

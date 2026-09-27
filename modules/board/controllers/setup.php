<?php
/**
 * @filesource modules/board/controllers/setup.php
 *
 * @copyright 2026 Goragod.com
 * @license https://www.kotchasan.com/license/
 */

namespace Board\Setup;

use Gcms\Api as ApiController;
use Kotchasan\Http\Request;
use Kotchasan\Http\Response;

/**
 * API Boards List Controller
 *
 * DataTable endpoint + action handler for topic management
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Controller extends \Gcms\Table
{
    /**
     * Allowed sort columns
     *
     * @var array
     */
    protected $allowedSortColumns = ['topic', 'created_at', 'published', 'category_id', 'pin', 'comments', 'visited'];

    /**
     * Get custom parameters for table
     *
     * @param Request $request
     * @param object $login
     *
     * @return array
     */
    protected function getCustomParams(Request $request, $login): array
    {
        return [
            'module_id' => $request->get('module_id')->toInt(),
            'category_id' => $request->get('category_id')->toInt(),
            'published' => $request->get('published')->filter('01'),
            'pin' => $request->get('pin')->filter('01'),
            'locked' => $request->get('locked')->filter('01')
        ];
    }

    /**
     * Check authorization
     *
     * @param Request $request
     * @param object  $login
     *
     * @return mixed
     */
    protected function checkAuthorization(Request $request, $login)
    {
        // Load module configuration
        $module = \Index\Module\Model::getModuleWithConfig('board', $request->get('module_id')->toInt());
        if (!$module || !\Web\Login::checkStatus($login, $module->config, ['moderator'])) {
            return $this->errorResponse('Permission required', 403);
        }

        return true;
    }

    /**
     * Query data for DataTable
     *
     * @param array  $params
     * @param object $login
     *
     * @return \Kotchasan\QueryBuilder\QueryBuilderInterface
     */
    protected function toDataTable($params, $login = null)
    {
        return \Board\Setup\Model::toDataTable($params);
    }

    /**
     * Filters for the table response
     *
     * @param array  $params
     * @param object $login
     *
     * @return array
     */
    protected function getFilters($params, $login = null)
    {
        return [
            'published' => [
                ['value' => '0', 'text' => '{LNG_Do not show}'],
                ['value' => '1', 'text' => '{LNG_Show}']
            ],
            'pin' => [
                ['value' => '1', 'text' => '{LNG_Pinned}'],
                ['value' => '0', 'text' => '{LNG_Not pinned}']
            ],
            'locked' => [
                ['value' => '1', 'text' => '{LNG_Locked}'],
                ['value' => '0', 'text' => '{LNG_Not locked}']
            ],
            'category_id' => \Document\Category\Model::toOptions($params['module_id'])
        ];
    }

    /**
     * Handle delete action
     *
     * @param Request $request
     * @param object  $login
     *
     * @return Response
     */
    protected function handleDeleteAction(Request $request, $login)
    {
        if (!ApiController::canModify($login, ['moderator'])) {
            return $this->errorResponse('Permission required', 403);
        }

        $ids = $request->request('ids', [])->toInt();
        $removed = \Board\Setup\Model::remove($ids);

        if (empty($removed)) {
            return $this->errorResponse('Delete action failed', 400);
        }

        \Index\Log\Model::add(0, 'board', 'Board', 'Delete Topic ID(s): '.implode(', ', $ids), $login->id);

        return $this->redirectResponse('reload', 'Deleted successfully');
    }

    /**
     * Handle edit action
     *
     * @param Request $request
     * @param object  $login
     *
     * @return Response
     */
    protected function handleEditAction(Request $request, $login)
    {
        $row = json_decode($request->post('row')->toJson());
        if ($row) {
            return $this->redirectResponse('/board?id='.$row->id.'&module_id='.$row->module_id);
        }
    }

    /**
     * Handle status/toggle action
     *
     * @param Request $request
     * @param object  $login
     *
     * @return Response
     */
    protected function handleStatusAction(Request $request, $login)
    {
        if (!ApiController::canModify($login, ['moderator'])) {
            return $this->errorResponse('Permission required', 403);
        }

        $ids = $request->request('ids', [])->toInt();
        $status = $request->request('status')->filter('0-9a-z_');
        if (empty($ids) || !preg_match('/^(published|pin|locked|can_reply)_([0-9]+)$/', $status, $matches)) {
            return $this->errorResponse('Invalid status format', 400);
        }

        $column = $matches[1];
        $value = (int) $matches[2];

        $updated = \Board\Setup\Model::updateStatus($ids, $column, $value);

        if ($updated === false) {
            return $this->errorResponse('Update failed', 400);
        }

        return $this->redirectResponse('reload', 'Status updated');
    }
}

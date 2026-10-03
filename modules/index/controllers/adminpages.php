<?php
/**
 * @filesource modules/index/controllers/adminpages.php
 *
 * @copyright 2026 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Index\AdminPages;

use Index\AdminPages\Model;
use Kotchasan\Http\Request;
use Kotchasan\Http\Response;
use Kotchasan\Language;

/**
 * API Admin Pages Controller
 *
 * Handles page management endpoints
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
    protected $allowedSortColumns = ['topic', 'updated_at', 'visited', 'published'];

    /**
     * Check authorization for page management
     * Managers (can_config) and page writers (can_write_page)
     *
     * @param Request $request
     * @param object $login
     *
     * @return mixed
     */
    protected function checkAuthorization(Request $request, $login)
    {
        if (!Model::canView($login)) {
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
        return Model::toDataTable($params, $login);
    }

    /**
     * Writer column: the assigned writer, or "All writers" when the page has
     * none (member_id 0, or a member without the write permission — Model::writerId())
     *
     * @param array $datas
     * @param object $login
     *
     * @return array
     */
    protected function formatDatas(array $datas, $login = null): array
    {
        $all = Language::get('All writers');
        foreach ($datas as $i => $row) {
            $row = (object) $row;
            $assigned = !empty($row->member_id)
                && strpos((string) $row->writer_permission, ','.Model::WRITE_PERMISSION.',') !== false;
            if (!$assigned) {
                $row->writer = $all;
            }
            unset($row->writer_permission);
            $datas[$i] = $row;
        }

        return $datas;
    }

    /**
     * Writers may only publish/unpublish; delete stays with managers
     *
     * @param array $params
     * @param object $login
     *
     * @return array
     */
    protected function getOptions(array $params, $login)
    {
        if (Model::canManage($login)) {
            return [];
        }
        $canWrite = Model::canWrite($login);

        return [
            '_table' => [
                'showCheckbox' => $canWrite,
                'actions' => $canWrite ? [
                    'status|published_1' => 'Published',
                    'status|published_0' => 'Unpublished'
                ] : [],
                'actionButton' => $canWrite ? 'Process|btn-success' : null
            ]
        ];
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
     * Deletes page from index and index_detail tables,
     * and unlinks any menus pointing to it.
     *
     * @param Request $request
     * @param object $login
     *
     * @return array
     */
    protected function handleDeleteAction(Request $request, $login)
    {
        if (!Model::canManage($login)) {
            return $this->errorResponse('Failed to process request', 403);
        }

        $ids = $request->request('ids', [])->toInt();
        $removeCount = Model::remove($ids);

        if (empty($removeCount)) {
            return $this->errorResponse('Delete action failed', 400);
        }

        \Index\Log\Model::add(0, 'index', 'Index', 'Delete Page ID(s): '.implode(', ', $ids), $login->id);

        return $this->redirectResponse('reload', 'Deleted page successfully');
    }

    /**
     * Handle edit action
     * Redirects to the page editor
     *
     * @param Request $request
     * @param object $login
     *
     * @return array
     */
    protected function handleEditAction(Request $request, $login)
    {
        $id = $request->post('id')->toInt();

        return $this->redirectResponse('/page?id='.$id);
    }

    /**
     * Handle status action (published|1, published|0, etc.)
     *
     * @param Request $request
     * @param object $login
     *
     * @return Response
     */
    protected function handleStatusAction(Request $request, $login)
    {
        if (!Model::canWrite($login)) {
            return $this->errorResponse('Failed to process request', 403);
        }

        // Writers change only the pages they may edit
        $ids = Model::editableIds($request->request('ids', [])->toInt(), $login);
        if (empty($ids)) {
            return $this->errorResponse('Failed to process request', 403);
        }
        $status = $request->request('status')->filter('a-z0-9_');

        // Example: published_1, recommend_0
        if (preg_match('/^([a-z]+)_([0-1])$/', $status, $match)) {
            $column = $match[1];
            $value = (int) $match[2];

            if (in_array($column, ['published'])) {
                Model::updateStatus($ids, $column, $value);
                \Index\Log\Model::add(0, 'index', 'Index', 'Update Page ID(s) : '.implode(', ', $ids).' '.$column.'='.$value, $login->id);
                return $this->redirectResponse('reload', 'Updated successfully');
            }
        }

        return $this->errorResponse('Invalid status action', 400);
    }
}

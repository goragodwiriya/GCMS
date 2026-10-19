<?php
/**
 * @filesource widgets/textlinks/controllers/table.php
 *
 * @copyright 2026 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Widgets\Textlinks\Controllers;

use Gcms\Api as ApiController;
use Kotchasan\Http\Request;

/**
 * Textlinks Widget — Table Controller
 *
 * Handles GET data and POST bulk-actions for the Textlinks widget table.
 * Called indirectly via Index\Widgets\Controller::table() /
 * Index\Widgets\Controller::tableaction().
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
            'name' => $request->request('name')->topic(),
            'sort' => 'link_order'
        ];
    }

    /**
     * Authorization check.
     * Only users with can_config permission may access this endpoint.
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
     * Return the base query for the DataTable.
     *
     * @param array  $params
     * @param object $login
     *
     * @return \Kotchasan\QueryBuilder\QueryBuilderInterface
     */
    protected function toDataTable($params, $login = null)
    {
        return \Widgets\Textlinks\Models\Table::toDataTable($params);
    }

    /**
     * Append computed fields (thumb URL, normalised type) to each row.
     *
     * @param array  $datas
     * @param object $login
     *
     * @return array
     */
    protected function formatDatas(array $datas, $login = null): array
    {
        $result = [];

        foreach ($datas as $row) {
            // ป้ายชื่อชนิด (แปลผ่าน {LNG_Text} / {LNG_Image} ที่ฝั่ง DataTable)
            $row->type = \Widgets\Textlinks\Models\Index::normalizeType($row->type) === 'image' ? 'Image' : 'Text';
            $row->thumb = empty($row->logo) ? '' : WEB_URL.DATA_FOLDER.'image/'.$row->logo;

            $result[] = $row;
        }

        return $result;
    }

    /**
     * Available filter options rendered by the DataTable.
     *
     * @param array  $params
     * @param object $login
     *
     * @return array
     */
    protected function getFilters($params, $login = null)
    {
        return [
            'name' => \Widgets\Textlinks\Models\Table::allName()
        ];
    }

    // -------------------------------------------------------------------------
    // Action handlers  (called by \Gcms\Table::action() via naming convention)
    // -------------------------------------------------------------------------

    /**
     * Handle delete bulk action.
     *
     * @param Request $request
     * @param object  $login
     *
     * @return \Kotchasan\Http\Response
     */
    protected function handleEditAction(Request $request, $login)
    {
        $id = $request->post('id')->toInt();

        return $this->redirectResponse('/widgets/textlinks/textlink?id='.$id);
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
            return $this->errorResponse('Failed to process request', 403);
        }

        $ids = json_decode($request->request('ids')->toJson(), true);
        $removeCount = \Widgets\Textlinks\Models\Table::remove($ids);

        if (empty($removeCount)) {
            return $this->errorResponse('Delete action failed', 400);
        }

        \Index\Log\Model::add(0, 'widgets', 'Textlinks', 'Delete Textlinks ID(s) : '.implode(', ', $ids), $login->id);

        return $this->redirectResponse('reload', 'Deleted '.$removeCount.' item(s) successfully');
    }

    /**
     * Handle status bulk action.
     * Accepts status values like published_1, published_0.
     *
     * @param Request $request
     * @param object  $login
     *
     * @return \Kotchasan\Http\Response
     */
    protected function handleStatusAction(Request $request, $login)
    {
        if (!ApiController::canModify($login, ['can_config'])) {
            return $this->errorResponse('Failed to process request', 403);
        }

        $ids = $request->request('ids', [])->toInt();
        $status = $request->request('status')->filter('a-z0-9_');

        // e.g. "published_1"  →  column=published, value=1
        if (preg_match('/^([a-z]+)_([0-1])$/', $status, $match)) {
            $column = $match[1];
            $value = (int) $match[2];

            if (in_array($column, ['published'])) {
                \Widgets\Textlinks\Models\Table::updateStatus($ids, $column, $value);

                \Index\Log\Model::add(
                    0,
                    'widgets',
                    'Textlinks',
                    'Update Textlinks ID(s) : '.implode(', ', $ids).' '.$column.'='.$value,
                    $login->id
                );

                return $this->redirectResponse('reload', 'Updated successfully');
            }
        }

        return $this->errorResponse('Invalid status action', 400);
    }

    /**
     * Handle reorder action (drag-and-drop row sorting).
     *
     * Payload (POST JSON):
     *   action : "reorder"
     *   order  : [{"id": 1, "position": 0}, {"id": 3, "position": 1}, ...]
     *
     * @param Request $request
     * @param object  $login
     *
     * @return \Kotchasan\Http\Response
     */
    protected function handleReorderAction(Request $request, $login)
    {
        if (!ApiController::canModify($login, ['can_config'])) {
            return $this->errorResponse('Failed to process request', 403);
        }

        $order = $request->request('order', [])->toArray();

        if (empty($order)) {
            return $this->errorResponse('Order data is required', 400);
        }

        \Widgets\Textlinks\Models\Table::reorder($order);

        return $this->successResponse(null, 'Reordered successfully');
    }
}

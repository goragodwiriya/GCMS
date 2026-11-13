<?php
/**
 * @filesource modules/document/controllers/setup.php
 *
 * @copyright 2026 Goragod.com
 * @license https://www.kotchasan.com/license/
 */

namespace Document\Setup;

use Gcms\Api as ApiController;
use Kotchasan\Http\Request;
use Kotchasan\Http\Response;

/**
 * API Documents List Controller
 *
 * DataTable endpoint + action handler for article management
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
    protected $allowedSortColumns = ['id', 'published', 'published_date', 'visited'];

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
            'published' => $request->get('published')->filter('01')
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
        $module = \Index\Module\Model::getModuleWithConfig('document', $request->get('module_id')->toInt());
        if (!$module || !\Web\Login::checkStatus($login, $module->config, ['can_write', 'can_approve'])) {
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
        return Model::toDataTable($params);
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
            'category_id' => \Document\Category\Model::toOptions($params['module_id'])
        ];
    }

    /**
     * Format data list with additional display fields
     *
     * @param array $datas
     * @param object $login
     *
     * @return array
     */
    protected function formatDatas(array $datas, $login = null): array
    {
        $time = time();
        $data = [];
        foreach ($datas as $row) {
            if (!empty($row->picture) && file_exists(ROOT_PATH.DATA_FOLDER.'document/'.$row->picture)) {
                $row->picture = WEB_URL.DATA_FOLDER.'document/'.$row->picture.'?v='.$time;
            } else {
                $row->picture = WEB_URL.'images/no-image.webp';
            }
            $data[] = $row;
        }
        return $data;
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
        if (!ApiController::canModify($login, ['can_approve'])) {
            return $this->errorResponse('Permission required', 403);
        }

        $ids = $request->request('ids', [])->toInt();
        $removed = \Document\Setup\Model::remove($ids);

        if (empty($removed)) {
            return $this->errorResponse('Delete action failed', 400);
        }

        \Index\Log\Model::add(0, 'document', 'Document', 'Delete Article ID(s): '.implode(', ', $ids), $login->id);

        return $this->redirectResponse('reload', 'Deleted successfully', 200, 0, 'table');
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
            return $this->redirectResponse('/document?id='.$row->id.'&module_id='.$row->module_id);
        }
    }

    /**
     * Handle status action (published|1, published|0)
     *
     * @param Request $request
     * @param object  $login
     *
     * @return Response
     */
    protected function handleStatusAction(Request $request, $login)
    {
        if (!ApiController::canModify($login, ['can_approve'])) {
            return $this->errorResponse('Permission required', 403);
        }

        $ids = $request->request('ids', [])->toInt();
        $status = $request->request('status')->filter('a-z0-9_');

        if (preg_match('/^([a-z]+)_([0-1])$/', $status, $match)) {
            $column = $match[1];
            $value = (int) $match[2];

            if (in_array($column, ['published'])) {
                \Document\Setup\Model::updateStatus($ids, $column, $value);
                \Index\Log\Model::add(0, 'document', 'Document', 'Update Article ID(s): '.implode(', ', $ids).' '.$column.'='.$value, $login->id);
                return $this->redirectResponse('reload', 'Updated successfully');
            }
        }

        return $this->errorResponse('Invalid status action', 400);
    }
}

<?php
/**
 * @filesource modules/personnel/controllers/setup.php
 *
 * @copyright 2026 Goragod.com
 * @license https://www.kotchasan.com/license/
 */

namespace Personnel\Setup;

use Kotchasan\Http\Request;

/**
 * API Personnel List Controller (DataTable)
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Controller extends \Gcms\Table
{
    /**
     * Allowed level columns
     */
    protected $allowedSortColumns = ['id', 'name', 'department', 'position', 'level', 'updated_at'];

    /**
     * Get custom parameters for table
     *
     * @param Request $request
     * @param object  $login
     *
     * @return array
     */
    protected function getCustomParams(Request $request, $login): array
    {
        return [
            'module_id' => $request->get('module_id')->toInt(),
            'department' => $request->get('department')->filter('0-9'),
            'level' => $request->get('level')->filter('0-9')
        ];
    }

    /**
     * Check authorization
     */
    protected function checkAuthorization(Request $request, $login)
    {
        // Load module configuration
        $module = \Index\Module\Model::getModuleWithConfig('personnel', $request->get('module_id')->toInt());
        if (!$module || !\Web\Login::checkStatus($login, $module->config, ['can_manage'])) {
            return $this->errorResponse('Permission required', 403);
        }

        return true;
    }

    /**
     * Query data to send to DataTable
     */
    protected function toDataTable($params, $login = null)
    {
        return \Personnel\Setup\Model::toDataTable($params);
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
            'department' => \Personnel\Category\Model::toOptions($params['module_id'], 'department'),
            'level' => \Personnel\Category\Model::levelOptions()
        ];
    }

    /**
     * Format data list
     *
     * @param array  $datas
     * @param object $login
     *
     * @return array
     */
    protected function formatDatas(array $datas, $login = null): array
    {
        $time = time();
        $data = [];
        foreach ($datas as $row) {
            if (file_exists(ROOT_PATH.DATA_FOLDER.'personnel/'.$row->picture)) {
                $row->image = WEB_URL.DATA_FOLDER.'personnel/'.$row->picture;
            } else {
                $row->image = WEB_URL.'images/no-image.webp';
            }
            $data[] = $row;
        }
        return $data;
    }

    /**
     * Handle edit action
     */
    protected function handleEditAction(Request $request, $login)
    {
        $row = json_decode($request->post('row')->toJson());
        if ($row) {
            return $this->redirectResponse('/personnel?id='.$row->id.'&module_id='.$row->module_id);
        }
    }

    /**
     * Handle delete action
     */
    protected function handleDeleteAction(Request $request, $login)
    {
        // Load module configuration
        $module = \Index\Module\Model::getModuleWithConfig('personnel', $request->post('module_id')->toInt());
        if (!$module || !\Web\Login::checkStatus($login, $module->config, ['can_manage'])) {
            return $this->errorResponse('Permission required', 403);
        }

        $ids = json_decode($request->request('ids')->toJson(), true);
        if (empty($ids)) {
            return $this->errorResponse('No items selected', 400);
        }

        foreach ($ids as $id) {
            if (file_exists(ROOT_PATH.DATA_FOLDER.'personnel/'.$id.self::$cfg->stored_img_type)) {
                unlink(ROOT_PATH.DATA_FOLDER.'personnel/'.$id.self::$cfg->stored_img_type);
            }
        }

        // Delete personnel records
        $db = \Kotchasan\DB::create();
        $removeCount = $db->delete('personnel', ['id', $ids], 0);
        if (empty($removeCount)) {
            return $this->errorResponse('Delete action failed', 400);
        }

        // Log the deletion
        \Index\Log\Model::add(0, 'personnel', 'Personnel', 'Delete Personnel ID(s) : '.implode(', ', $ids), $login->id);

        // Return success response
        return $this->redirectResponse('reload', 'Deleted '.$removeCount.' personnel(s) successfully');
    }
}

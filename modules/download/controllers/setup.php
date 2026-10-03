<?php
/**
 * @filesource modules/download/controllers/setup.php
 *
 * @copyright 2026 Goragod.com
 * @license https://www.kotchasan.com/license/
 */

namespace Download\Setup;

use Kotchasan\Http\Request;

/**
 * API Download List Controller (DataTable)
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Controller extends \Gcms\Table
{
    /**
     * @var array
     */
    protected $allowedSortColumns = ['id', 'name', 'ext', 'size', 'updated_at', 'downloads'];

    /**
     * @var object|null
     */
    protected $module = null;

    /**
     * @var bool
     */
    protected $isModerator = false;

    /**
     * Get custom parameters for table.
     *
     * @param Request $request
     * @param object  $login
     *
     * @return array
     */
    protected function getCustomParams(Request $request, $login): array
    {
        $category = $request->get('category_id')->toString();

        $params = [
            'module_id' => $request->get('module_id')->toInt(),
            // -1 means "all categories"; 0 keeps support for uncategorized filter.
            'category_id' => $category === '' ? -1 : (int) $category
        ];

        if (!$this->isModerator) {
            $params['member_id'] = (int) $login->id;
        }

        return $params;
    }

    /**
     * Check authorization.
     *
     * @param Request $request
     * @param object $login
     *
     * @return mixed
     */
    protected function checkAuthorization(Request $request, $login)
    {
        $this->module = \Index\Module\Model::getModuleWithConfig('download', $request->get('module_id')->toInt());
        if (!$this->module) {
            return $this->errorResponse('No data available', 404);
        }

        $this->module->config = \Download\Settings\Model::normalizeConfig($this->module->config);

        if (!\Web\Login::checkStatus($login, $this->module->config, ['can_upload', 'moderator'])) {
            return $this->errorResponse('Permission required', 403);
        }

        $this->isModerator = \Web\Login::checkStatus($login, $this->module->config, ['moderator']) ? true : false;

        return true;
    }

    /**
     * Query data to send to DataTable.
     *
     * @param array  $params
     * @param object $login
     *
     * @return \Kotchasan\QueryBuilder\QueryBuilderInterface
     */
    protected function toDataTable($params, $login = null)
    {
        return \Download\Setup\Model::toDataTable($params);
    }

    /**
     * Get filters for table.
     *
     * @param array $params
     * @param object $login
     *
     * @return array
     */
    protected function getFilters($params, $login = null)
    {
        return [
            'category_id' => \Download\Category\Model::toOptions($params['module_id'], true)
        ];
    }

    /**
     * Format row data.
     *
     * @param array $datas
     * @param object $login
     *
     * @return array
     */
    protected function formatDatas(array $datas, $login = null): array
    {
        $data = [];
        foreach ($datas as $row) {
            $name = trim((string) $row->name);
            if ($name === '') {
                $name = basename((string) $row->file, '.'.(string) $row->ext);
            }
            $filePath = \Download\Setup\Model::toFilePath($row->file);
            $row->name = $name;
            $row->size = $filePath && is_file($filePath) ? (int) $row->size : 0;
            $row->file_url = $filePath && is_file($filePath) ? WEB_URL.DATA_FOLDER.\Download\Write\Model::normalizeFilePath($row->file) : '#';
            $row->category_id = empty($row->category_id) ? 0 : (int) $row->category_id;
            $row->widget = '{WIDGET_DOWNLOAD_'.$row->id.'}';
            $data[] = $row;
        }

        return $data;
    }

    /**
     * Handle edit action.
     *
     * @param Request $request
     * @param object $login
     *
     * @return \Kotchasan\Http\Response
     */
    protected function handleEditAction(Request $request, $login)
    {
        $row = json_decode($request->post('row')->toJson());
        if ($row) {
            return $this->redirectResponse('/download-write?id='.$row->id.'&module_id='.$row->module_id);
        }

        return $this->errorResponse('No data available', 404);
    }

    /**
     * Handle delete action.
     *
     * @param Request $request
     * @param object $login
     *
     * @return \Kotchasan\Http\Response
     */
    protected function handleDeleteAction(Request $request, $login)
    {
        $module = \Index\Module\Model::getModuleWithConfig('download', $request->post('module_id')->toInt());
        if (!$module) {
            return $this->errorResponse('No data available', 404);
        }
        $module->config = \Download\Settings\Model::normalizeConfig($module->config);

        if (!\Web\Login::checkStatus($login, $module->config, ['can_upload'])) {
            return $this->errorResponse('Permission required', 403);
        }

        $ids = $request->request('ids', [])->toInt();
        if (empty($ids)) {
            return $this->errorResponse('No items selected', 400);
        }

        $isModerator = \Web\Login::checkStatus($login, $module->config, ['moderator']) ? true : false;
        $removed = \Download\Setup\Model::remove($ids, $module->id, $isModerator ? 0 : (int) $login->id);

        if (empty($removed)) {
            return $this->errorResponse('Delete action failed', 400);
        }

        \Index\Log\Model::add(0, 'download', 'Download', 'Delete Download ID(s) : '.implode(', ', $removed), $login->id);

        return $this->redirectResponse('reload', 'Deleted successfully');
    }
}

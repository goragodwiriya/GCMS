<?php
/**
 * @filesource modules/edocument/controllers/setup.php
 *
 * @copyright 2026 Goragod.com
 * @license https://www.kotchasan.com/license/
 */

namespace Edocument\Setup;

use Kotchasan\Http\Request;

/**
 * API E-Document List Controller (DataTable)
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
    protected $allowedSortColumns = ['id', 'document_no', 'topic', 'size', 'last_update', 'downloads'];

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
        $params = [
            'module_id' => $request->get('module_id')->toInt()
        ];

        // Uploaders see only their own documents, moderators see all.
        if (!$this->isModerator) {
            $params['sender_id'] = (int) $login->id;
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
        $this->module = \Index\Module\Model::getModuleWithConfig('edocument', $request->get('module_id')->toInt());
        if (!$this->module) {
            return $this->errorResponse('No data available', 404);
        }

        $this->module->config = \Edocument\Settings\Model::normalizeConfig($this->module->config);

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
        return \Edocument\Setup\Model::toDataTable($params);
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
        $senders = \Edocument\Setup\Model::memberNames(array_column($datas, 'sender_id'));
        $statuses = [-1 => \Kotchasan\Language::get('Guest')] + (array) self::$cfg->member_status;

        $data = [];
        foreach ($datas as $row) {
            $fileUrl = \Edocument\Setup\Model::toFileUrl($row->file);
            $recievers = [];
            foreach (\Edocument\Setup\Model::parseReciever($row->reciever) as $status) {
                if (isset($statuses[$status])) {
                    $recievers[] = $statuses[$status];
                }
            }
            $data[] = (object) [
                'id' => (int) $row->id,
                'module_id' => (int) $row->module_id,
                'document_no' => $row->document_no,
                'topic' => $row->topic,
                'ext' => $row->ext,
                'detail' => $row->detail,
                'sender' => $senders[(int) $row->sender_id] ?? '',
                'reciever' => implode(', ', $recievers),
                'size' => $fileUrl === '' ? 0 : (int) $row->size,
                'file_url' => $fileUrl === '' ? '#' : $fileUrl,
                'last_update' => empty($row->last_update) ? '' : date('Y-m-d H:i:s', (int) $row->last_update),
                'downloads' => (int) $row->downloads
            ];
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
            return $this->redirectResponse('/edocument-write?id='.(int) $row->id.'&module_id='.(int) $row->module_id);
        }

        return $this->errorResponse('No data available', 404);
    }

    /**
     * Handle report action (download history of one document).
     *
     * @param Request $request
     * @param object $login
     *
     * @return \Kotchasan\Http\Response
     */
    protected function handleReportAction(Request $request, $login)
    {
        $row = json_decode($request->post('row')->toJson());
        if ($row) {
            return $this->redirectResponse('/edocument-report?id='.(int) $row->id.'&module_id='.(int) $row->module_id);
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
        $module = \Index\Module\Model::getModuleWithConfig('edocument', $request->post('module_id')->toInt());
        if (!$module) {
            return $this->errorResponse('No data available', 404);
        }
        $module->config = \Edocument\Settings\Model::normalizeConfig($module->config);

        if (!\Web\Login::checkStatus($login, $module->config, ['can_upload', 'moderator']) || !\Gcms\Api::isNotDemoMode($login)) {
            return $this->errorResponse('Permission required', 403);
        }

        $ids = $request->request('ids', [])->toInt();
        if (empty($ids)) {
            return $this->errorResponse('No items selected', 400);
        }

        $isModerator = \Web\Login::checkStatus($login, $module->config, ['moderator']) ? true : false;
        $removed = \Edocument\Setup\Model::remove($ids, $module->id, $isModerator ? 0 : (int) $login->id);

        if (empty($removed)) {
            return $this->errorResponse('Delete action failed', 400);
        }

        \Index\Log\Model::add(0, 'edocument', 'Delete', 'Delete E-Document ID(s) : '.implode(', ', $removed), $login->id);

        return $this->redirectResponse('reload', 'Deleted successfully');
    }
}

<?php
/**
 * @filesource modules/edocument/controllers/report.php
 *
 * @copyright 2026 Goragod.com
 * @license https://www.kotchasan.com/license/
 */

namespace Edocument\Report;

use Kotchasan\Http\Request;

/**
 * API E-Document download history (DataTable)
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
    protected $allowedSortColumns = ['name', 'status', 'last_update', 'downloads'];

    /**
     * GET /api/edocument/report/get
     * Document header for the report page.
     *
     * @param Request $request
     *
     * @return \Kotchasan\Http\Response
     */
    public function get(Request $request)
    {
        try {
            \Kotchasan\ApiController::validateMethod($request, 'GET');

            $login = $this->authenticateRequest($request);
            if (!$login) {
                return $this->redirectResponse('/login', 'Unauthorized', 401);
            }

            $authCheck = $this->checkAuthorization($request, $login);
            if ($authCheck !== true) {
                return $authCheck;
            }

            $document = \Edocument\Report\Model::getDocument($request->get('id')->toInt(), $request->get('module_id')->toInt());

            return $this->successResponse([
                'id' => (int) $document->id,
                'module_id' => (int) $document->module_id,
                'document_no' => $document->document_no,
                'topic' => $document->topic.'.'.$document->ext,
                'detail' => $document->detail,
                'downloads' => (int) $document->downloads
            ], 'E-Document retrieved');
        } catch (\Throwable $e) {
            return $this->handleException($e);
        }
    }

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
        return [
            'module_id' => $request->get('module_id')->toInt(),
            'document_id' => $request->get('id')->toInt()
        ];
    }

    /**
     * Uploaders may see the history of their own documents, moderators of all.
     *
     * @param Request $request
     * @param object $login
     *
     * @return mixed
     */
    protected function checkAuthorization(Request $request, $login)
    {
        $module = \Index\Module\Model::getModuleWithConfig('edocument', $request->get('module_id')->toInt());
        if (!$module) {
            return $this->errorResponse('No data available', 404);
        }

        $config = \Edocument\Settings\Model::normalizeConfig($module->config);
        if (!\Web\Login::checkStatus($login, $config, ['can_upload', 'moderator'])) {
            return $this->errorResponse('Permission required', 403);
        }

        $document = \Edocument\Report\Model::getDocument($request->get('id')->toInt(), $module->id);
        if (!$document) {
            return $this->errorResponse('No data available', 404);
        }

        if ((int) $document->sender_id !== (int) $login->id && !\Web\Login::checkStatus($login, $config, ['moderator'])) {
            return $this->errorResponse('Permission required', 403);
        }

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
        return \Edocument\Report\Model::toDataTable($params);
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
        $statuses = (array) self::$cfg->member_status;

        $data = [];
        foreach ($datas as $row) {
            if ((int) $row->member_id === 0) {
                $name = \Kotchasan\Language::get('Guest');
                $status = '';
            } else {
                $name = trim((string) $row->name) === '' ? (string) $row->username : $row->name;
                $status = $statuses[(int) $row->status] ?? '';
            }
            $data[] = (object) [
                'id' => (int) $row->id,
                'name' => $name,
                'status' => $status,
                'last_update' => empty($row->last_update) ? '' : date('Y-m-d H:i:s', (int) $row->last_update),
                'downloads' => (int) $row->downloads
            ];
        }

        return $data;
    }
}

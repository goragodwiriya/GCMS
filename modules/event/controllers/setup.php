<?php
/**
 * @filesource modules/event/controllers/setup.php
 *
 * @copyright 2026 Goragod.com
 * @license https://www.kotchasan.com/license/
 */

namespace Event\Setup;

use Kotchasan\Http\Request;

/**
 * API Event List Controller (DataTable) — mirrors
 * Portfolio\Setup\Controller's shape, ports gcms241021
 * Event\Admin\Setup onto the current API convention.
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
    protected $allowedSortColumns = ['id', 'topic', 'begin_date', 'published', 'last_update'];

    /**
     * @param Request $request
     * @param object  $login
     *
     * @return array
     */
    protected function getCustomParams(Request $request, $login): array
    {
        return [
            'module_id' => $request->get('module_id')->toInt()
        ];
    }

    /**
     * @param Request $request
     * @param object  $login
     *
     * @return mixed
     */
    protected function checkAuthorization(Request $request, $login)
    {
        $module = \Index\Module\Model::getModuleWithConfig('event', $request->request('module_id')->toInt());
        if (!$module || !\Web\Login::checkStatus($login, $module->config, ['can_write'])) {
            return $this->errorResponse('Permission required', 403);
        }

        return true;
    }

    /**
     * @param array  $params
     * @param object $login
     *
     * @return \Kotchasan\QueryBuilder\QueryBuilderInterface
     */
    protected function toDataTable($params, $login = null)
    {
        return \Event\Setup\Model::toDataTable($params);
    }

    /**
     * @param array  $datas
     * @param object $login
     *
     * @return array
     */
    protected function formatDatas(array $datas, $login = null): array
    {
        foreach ($datas as $row) {
            // last_update is stored as a unix timestamp (int) — data-format="datetime"
            // expects a JS-Date-parseable string, same conversion as
            // Portfolio\Setup\Controller::formatDatas().
            $row->last_update = empty($row->last_update) ? null : date('Y-m-d H:i:s', (int) $row->last_update);
            $row->published = (int) $row->published;
        }

        return $datas;
    }

    /**
     * @param Request $request
     * @param object  $login
     *
     * @return mixed
     */
    protected function handleEditAction(Request $request, $login)
    {
        $row = json_decode($request->post('row')->toJson());
        if ($row) {
            return $this->redirectResponse('/event?id='.$row->id.'&module_id='.$row->module_id);
        }
    }

    /**
     * Toggle published status.
     *
     * @param Request $request
     * @param object  $login
     *
     * @return mixed
     */
    protected function handleStatusAction(Request $request, $login)
    {
        if (($guard = $this->checkAuthorization($request, $login)) !== true) {
            return $guard;
        }

        $ids = $request->request('ids', [])->toInt();
        $status = $request->request('status')->filter('a-z0-9_');

        if (preg_match('/^published_([0-1])$/', $status, $match)) {
            $value = (int) $match[1];
            $moduleId = $request->request('module_id')->toInt();
            \Kotchasan\DB::create()->update('event', [['id', $ids], ['module_id', $moduleId]], ['published' => (string) $value]);
            \Index\Log\Model::add(0, 'event', 'Event', 'Update ID(s): '.implode(', ', $ids).' published='.$value, $login->id);

            return $this->redirectResponse('reload', 'Updated successfully');
        }

        return $this->errorResponse('Invalid status action', 400);
    }

    /**
     * Delete the selected events.
     *
     * @param Request $request
     * @param object  $login
     *
     * @return mixed
     */
    protected function handleDeleteAction(Request $request, $login)
    {
        if (($guard = $this->checkAuthorization($request, $login)) !== true) {
            return $guard;
        }

        $ids = $request->request('ids', [])->toInt();
        if (empty($ids)) {
            return $this->errorResponse('No items selected', 400);
        }

        $moduleId = $request->request('module_id')->toInt();
        $removed = \Kotchasan\DB::create()->delete('event', [['id', $ids], ['module_id', $moduleId]], 0);
        if (empty($removed)) {
            return $this->errorResponse('Delete action failed', 400);
        }

        \Index\Log\Model::add(0, 'event', 'Event', 'Delete ID(s): '.implode(', ', $ids), $login->id);

        return $this->redirectResponse('reload', 'Deleted '.$removed.' item(s) successfully');
    }
}

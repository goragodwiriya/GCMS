<?php
/**
 * @filesource modules/video/controllers/setup.php
 *
 * @copyright 2026 Goragod.com
 * @license https://www.kotchasan.com/license/
 */

namespace Video\Setup;

use Kotchasan\Http\Request;

/**
 * API Video List Controller (DataTable) — mirrors
 * Portfolio\Setup\Controller's shape, ports gcms241021
 * Video\Admin\Setup onto the current API convention.
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
    protected $allowedSortColumns = ['id', 'topic', 'views', 'last_update'];

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
        $module = \Index\Module\Model::getModuleWithConfig('video', $request->request('module_id')->toInt());
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
        return \Video\Setup\Model::toDataTable($params);
    }

    /**
     * @param array  $datas
     * @param object $login
     *
     * @return array
     */
    protected function formatDatas(array $datas, $login = null): array
    {
        $time = time();
        foreach ($datas as $row) {
            if (is_file(ROOT_PATH.DATA_FOLDER.'video/'.$row->youtube.'.jpg')) {
                $row->image = WEB_URL.DATA_FOLDER.'video/'.$row->youtube.'.jpg?v='.$time;
            } else {
                $row->image = WEB_URL.'images/no-image.webp';
            }
            // last_update is stored as a unix timestamp (int) — data-format="datetime"
            // expects a JS-Date-parseable string, same conversion as
            // Portfolio\Setup\Controller::formatDatas().
            $row->last_update = empty($row->last_update) ? null : date('Y-m-d H:i:s', (int) $row->last_update);
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
            return $this->redirectResponse('/video?id='.$row->id.'&module_id='.$row->module_id);
        }
    }

    /**
     * Deletes the rows and their thumbnail files.
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
        $rows = \Kotchasan\Model::createQuery()
            ->select('id', 'youtube')
            ->from('video')
            ->where([['id', $ids], ['module_id', $moduleId]])
            ->fetchAll();

        foreach ($rows as $row) {
            if (is_file(ROOT_PATH.DATA_FOLDER.'video/'.$row->youtube.'.jpg')) {
                unlink(ROOT_PATH.DATA_FOLDER.'video/'.$row->youtube.'.jpg');
            }
        }

        $removed = \Kotchasan\DB::create()->delete('video', [['id', $ids], ['module_id', $moduleId]], 0);
        if (empty($removed)) {
            return $this->errorResponse('Delete action failed', 400);
        }

        \Index\Log\Model::add(0, 'video', 'Video', 'Delete ID(s): '.implode(', ', $ids), $login->id);

        return $this->redirectResponse('reload', 'Deleted '.$removed.' item(s) successfully');
    }
}

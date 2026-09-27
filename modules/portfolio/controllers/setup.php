<?php
/**
 * @filesource modules/portfolio/controllers/setup.php
 *
 * @copyright 2026 Goragod.com
 * @license https://www.kotchasan.com/license/
 */

namespace Portfolio\Setup;

use Gcms\Api as ApiController;
use Kotchasan\Http\Request;

/**
 * API Portfolio List Controller (DataTable) — mirrors
 * Document\Setup\Controller / Personnel\Setup\Controller's shape, plus
 * image cleanup on delete (Personnel\Setup\Controller::handleDeleteAction()).
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
    protected $allowedSortColumns = ['id', 'title', 'published', 'created_at', 'visited'];

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
        $module = \Index\Module\Model::getModuleWithConfig('portfolio', $request->get('module_id')->toInt());
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
        return \Portfolio\Setup\Model::toDataTable($params);
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
            if ($row->image !== '' && file_exists(ROOT_PATH.DATA_FOLDER.'portfolio/'.$row->image)) {
                $row->image = WEB_URL.DATA_FOLDER.'portfolio/'.$row->image.'?v='.time();
            } else {
                $row->image = WEB_URL.'images/no-image.webp';
            }
            // created_at is stored as a unix timestamp (int) — data-format="datetime"
            // expects a JS-Date-parseable string, not raw seconds-since-epoch
            // (Date() would read that as milliseconds). Same conversion
            // Sysadmin\Customers\Controller::formatDatas() already applies.
            $row->created_at = empty($row->created_at) ? null : date('Y-m-d H:i:s', (int) $row->created_at);
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
            return $this->redirectResponse('/portfolio?id='.$row->id.'&module_id='.$row->module_id);
        }
    }

    /**
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

        if (preg_match('/^([a-z]+)_([0-1])$/', $status, $match) && $match[1] === 'published') {
            $value = (int) $match[2];
            \Kotchasan\DB::create()->update('portfolio', ['id', $ids], ['published' => (string) $value]);
            \Index\Log\Model::add(0, 'portfolio', 'Portfolio', 'Update ID(s): '.implode(', ', $ids).' published='.$value, $login->id);

            return $this->redirectResponse('reload', 'Updated successfully');
        }

        return $this->errorResponse('Invalid status action', 400);
    }

    /**
     * Deletes the rows and their uploaded image files.
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
            ->select('id', 'image')
            ->from('portfolio')
            ->where([['id', $ids], ['module_id', $moduleId]])
            ->fetchAll();

        foreach ($rows as $row) {
            if ($row->image !== '' && is_file(ROOT_PATH.DATA_FOLDER.'portfolio/'.$row->image)) {
                unlink(ROOT_PATH.DATA_FOLDER.'portfolio/'.$row->image);
            }
        }

        $removed = \Kotchasan\DB::create()->delete('portfolio', [['id', $ids], ['module_id', $moduleId]], 0);
        if (empty($removed)) {
            return $this->errorResponse('Delete action failed', 400);
        }

        \Index\Log\Model::add(0, 'portfolio', 'Portfolio', 'Delete ID(s): '.implode(', ', $ids), $login->id);

        return $this->redirectResponse('reload', 'Deleted '.$removed.' item(s) successfully');
    }
}

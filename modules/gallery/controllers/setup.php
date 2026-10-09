<?php
/**
 * @filesource modules/gallery/controllers/setup.php
 *
 * @copyright 2026 Goragod.com
 * @license https://www.kotchasan.com/license/
 */

namespace Gallery\Setup;

use Kotchasan\File;
use Kotchasan\Http\Request;

/**
 * API Gallery Albums Controller (DataTable)
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Controller extends \Gcms\Table
{
    /**
     * Allowed sort columns
     */
    protected $allowedSortColumns = ['id', 'published_date', 'updated_at'];

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
            'module_id' => $request->get('module_id')->toInt()
        ];
    }

    /**
     * Check authorization
     */
    protected function checkAuthorization(Request $request, $login)
    {
        // Load module configuration
        $module = \Index\Module\Model::getModuleWithConfig('gallery', $request->get('module_id')->toInt());
        if (!$module || !\Web\Login::checkStatus($login, $module->config, ['can_upload'])) {
            return $this->errorResponse('Permission required', 403);
        }

        return true;
    }

    /**
     * Query data to send to DataTable
     */
    protected function toDataTable($params, $login = null)
    {
        return \Gallery\Setup\Model::toDataTable($params);
    }

    /**
     * Format data list
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
            if (!empty($row->image) && file_exists(ROOT_PATH.DATA_FOLDER.'gallery/'.$row->id.'/'.$row->image)) {
                $row->image = WEB_URL.DATA_FOLDER.'gallery/'.$row->id.'/'.$row->image.'?v='.$time;
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
            return $this->redirectResponse('/gallery-album?id='.$row->id.'&module_id='.$row->module_id);
        }
    }

    /**
     * Remove images and delete albums
     */
    protected function handleDeleteAction(Request $request, $login)
    {
        // Load module configuration
        $module = \Index\Module\Model::getModuleWithConfig('gallery', $request->post('module_id')->toInt());
        if (!$module || !\Web\Login::checkStatus($login, $module->config, ['can_upload'])) {
            return $this->errorResponse('Permission required', 403);
        }

        $ids = $request->post('ids', [])->toInt();
        if (empty($ids)) {
            return $this->errorResponse('No items selected', 400);
        }

        // Remove image files
        foreach ($ids as $id) {
            File::removeDirectory(ROOT_PATH.DATA_FOLDER.'gallery/'.$id.'/');
        }

        // Delete albums and related images from database
        $db = \Kotchasan\DB::create();
        $removeCount = $db->delete('gallery_album', ['id', $ids], 0);
        $db->delete('gallery_image', ['album_id', $ids], 0);

        if (empty($removeCount)) {
            return $this->errorResponse('Delete action failed', 400);
        }

        // Log the deletion
        \Index\Log\Model::add(0, 'gallery', 'Gallery', 'Delete Album ID(s) : '.implode(', ', $ids), $login->id);

        // Return success response
        return $this->redirectResponse('reload', 'Deleted '.$removeCount.' album(s) successfully');
    }
}

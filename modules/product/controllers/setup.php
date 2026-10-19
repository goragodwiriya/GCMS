<?php
/**
 * @filesource modules/product/controllers/setup.php
 *
 * @copyright 2026 Goragod.com
 * @license https://www.kotchasan.com/license/
 */

namespace Product\Setup;

use Kotchasan\Http\Request;

/**
 * API Product List Controller (DataTable)
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
    protected $allowedSortColumns = ['id', 'topic', 'sku', 'base_price', 'stock_qty', 'published', 'updated_at'];

    /**
     * Custom query parameters
     */
    protected function getCustomParams(Request $request, $login): array
    {
        return [
            'module_id' => $request->get('module_id')->toInt(),
            // the column's field name: the table sends and looks up category_id
            'category_id' => $request->get('category_id')->filter('0-9'),
            'published' => $request->get('published')->filter('0-9')
        ];
    }

    /**
     * Authorization
     */
    protected function checkAuthorization(Request $request, $login)
    {
        $module = \Index\Module\Model::getModuleWithConfig('product', $request->get('module_id')->toInt());
        if (!$module || !\Product\Init\Controller::allowed($login, $module->config, ['can_manage'])) {
            return $this->errorResponse('Permission required', 403);
        }
        return true;
    }

    /**
     * DataTable query
     */
    protected function toDataTable($params, $login = null)
    {
        return \Product\Setup\Model::toDataTable($params);
    }

    /**
     * Filters
     */
    protected function getFilters($params, $login = null)
    {
        return [
            'category_id' => \Product\Category\Model::toOptions($params['module_id']),
            'published' => [
                ['value' => '1', 'text' => '{LNG_Published}'],
                ['value' => '0', 'text' => '{LNG_Unpublished}']
            ]
        ];
    }

    /**
     * Format rows (attach thumbnail)
     */
    protected function formatDatas(array $datas, $login = null): array
    {
        $time = time();
        $data = [];
        foreach ($datas as $row) {
            $img = \Kotchasan\DB::create()->first('product_image', [['product_id', $row->id], ['is_primary', 1]], ['filename']);
            // not tracked (downloads, legacy products): no stock count to show
            if (empty($row->manage_stock)) {
                $row->stock_qty = '∞';
            }
            if ($img) {
                $row->image = WEB_URL.DATA_FOLDER.'product/'.$row->id.'/'.$img->filename.'?v='.$time;
            } else {
                $row->image = WEB_URL.'images/no-image.webp';
            }
            $data[] = $row;
        }
        return $data;
    }

    /**
     * Edit action -> redirect to editor
     */
    protected function handleEditAction(Request $request, $login)
    {
        $row = json_decode($request->post('row')->toJson());
        if ($row) {
            return $this->redirectResponse('/product?id='.$row->id.'&module_id='.$row->module_id);
        }
    }

    /**
     * Delete action
     */
    protected function handleDeleteAction(Request $request, $login)
    {
        $module = \Index\Module\Model::getModuleWithConfig('product', $request->post('module_id')->toInt());
        if (!$module || !\Product\Init\Controller::allowed($login, $module->config, ['can_manage'])) {
            return $this->errorResponse('Permission required', 403);
        }

        $ids = json_decode($request->request('ids')->toJson(), true);
        if (empty($ids)) {
            return $this->errorResponse('No items selected', 400);
        }
        $ids = array_map('intval', $ids);

        $db = \Kotchasan\DB::create();
        foreach ($ids as $id) {
            // Remove associated variants + mapping
            $variants = $db->select('product_variant', [['product_id', $id], ['module_id', $module->id]], [], ['id']);
            $vids = array_map(fn($r) => (int) $r->id, $variants);
            if (!empty($vids)) {
                $db->delete('product_variant_value', ['variant_id', $vids], 0);
            }
            $db->delete('product_variant', [['product_id', $id], ['module_id', $module->id]], 0);
            $db->delete('product_detail', [['id', $id], ['module_id', $module->id]], 0);
            $db->delete('product_image', [['product_id', $id], ['module_id', $module->id]], 0);
            // Remove image directory
            $dir = ROOT_PATH.DATA_FOLDER.'product/'.$id.'/';
            if (is_dir($dir)) {
                \Kotchasan\File::removeDirectory($dir);
            }
        }
        $removeCount = $db->delete('product', [['id', $ids], ['module_id', $module->id]], 0);
        if (empty($removeCount)) {
            return $this->errorResponse('Delete action failed', 400);
        }

        \Index\Log\Model::add(0, 'product', 'Product', 'Delete Product ID(s) : '.implode(', ', $ids), $login->id);

        return $this->redirectResponse('reload', 'Deleted '.$removeCount.' product(s) successfully');
    }
}

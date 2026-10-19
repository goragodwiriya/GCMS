<?php
/**
 * @filesource modules/product/controllers/stocks.php
 *
 * @copyright 2026 Goragod.com
 * @license https://www.kotchasan.com/license/
 */

namespace Product\Stocks;

use Kotchasan\Http\Request;

/**
 * API Product Stock List Controller (DataTable)
 *
 * Lists products with their total stock for the stock management page.
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
    protected $allowedSortColumns = ['id', 'topic', 'sku', 'stock_qty', 'updated_at'];

    /**
     * Custom query parameters
     */
    protected function getCustomParams(Request $request, $login): array
    {
        return [
            'module_id' => $request->get('module_id')->toInt(),
            // the column's field name: the table sends and looks up category_id
            'category_id' => $request->get('category_id')->filter('0-9')
        ];
    }

    /**
     * Authorization
     */
    protected function checkAuthorization(Request $request, $login)
    {
        $module = \Index\Module\Model::getModuleWithConfig('product', $request->get('module_id')->toInt());
        if (!$module || !\Product\Init\Controller::allowed($login, $module->config, ['can_manage_stock'])) {
            return $this->errorResponse('Permission required', 403);
        }
        return true;
    }

    /**
     * DataTable query
     */
    protected function toDataTable($params, $login = null)
    {
        return \Product\Stocks\Model::toDataTable($params);
    }

    /**
     * Filters
     */
    protected function getFilters($params, $login = null)
    {
        return [
            'category_id' => \Product\Category\Model::toOptions($params['module_id'])
        ];
    }

    /**
     * Products that do not track stock have no count to show
     *
     * @param array  $datas
     * @param object $login
     *
     * @return array
     */
    protected function formatDatas(array $datas, $login = null): array
    {
        foreach ($datas as $row) {
            if (empty($row->manage_stock)) {
                $row->stock_qty = '∞';
            }
        }

        return $datas;
    }

    /**
     * Edit action -> redirect to the receive form for this product
     */
    protected function handleEditAction(Request $request, $login)
    {
        $row = json_decode($request->post('row')->toJson());
        if ($row) {
            return $this->redirectResponse('/product-stock?id='.$row->id.'&module_id='.$row->module_id);
        }
    }
}

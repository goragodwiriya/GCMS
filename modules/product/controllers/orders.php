<?php
/**
 * @filesource modules/product/controllers/orders.php
 *
 * @copyright 2026 Goragod.com
 * @license https://www.kotchasan.com/license/
 */

namespace Product\Orders;

use Kotchasan\Http\Request;

/**
 * API Product Order List Controller (DataTable)
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
    protected $allowedSortColumns = ['id', 'order_no', 'created_at', 'cust_name', 'grand_total', 'order_status', 'payment_status'];

    /**
     * Custom query parameters
     */
    protected function getCustomParams(Request $request, $login): array
    {
        return [
            'module_id' => $request->get('module_id')->toInt(),
            'order_status' => $request->get('order_status')->filter('0-9'),
            'payment_status' => $request->get('payment_status')->filter('a-z_')
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
        return \Product\Orders\Model::toDataTable($params);
    }

    /**
     * Filters
     */
    protected function getFilters($params, $login = null)
    {
        return [
            'order_status' => \Product\Order\Model::statusOptions(),
            'payment_status' => [
                ['value' => 'unpaid', 'text' => '{LNG_Unpaid}'],
                ['value' => 'pending_verify', 'text' => '{LNG_Pending verification}'],
                ['value' => 'paid', 'text' => '{LNG_Paid}'],
                ['value' => 'failed', 'text' => '{LNG_Failed}']
            ]
        ];
    }

    /**
     * Format rows (attach translated status text)
     */
    protected function formatDatas(array $datas, $login = null): array
    {
        $data = [];
        foreach ($datas as $row) {
            $row->order_status_text = \Product\Order\Model::statusText((int) $row->order_status);
            $data[] = $row;
        }
        return $data;
    }

    /**
     * Edit action -> redirect to admin order detail
     */
    protected function handleEditAction(Request $request, $login)
    {
        $row = json_decode($request->post('row')->toJson());
        if ($row) {
            return $this->redirectResponse('/product-order?id='.$row->id.'&module_id='.$row->module_id);
        }
    }
}

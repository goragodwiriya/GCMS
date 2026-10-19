<?php
/**
 * @filesource modules/product/controllers/stock.php
 *
 * @copyright 2026 Goragod.com
 * @license https://www.kotchasan.com/license/
 */

namespace Product\Stock;

use Gcms\Api as ApiController;
use Kotchasan\Http\Request;

/**
 * API Product Stock Controller
 *
 * Admin stock operations: load a product's variants for the receive form,
 * receive stock into FIFO lots, manual adjustments and the movement ledger.
 * The FIFO engine itself lives in \Product\Stock\Model.
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Controller extends ApiController
{
    /**
     * Resolve and authorize the module for a stock request.
     *
     * @param object $login
     * @param int    $module_id
     *
     * @return object|null module row or null when unauthorized
     */
    protected function resolveModule($login, $module_id)
    {
        $module = \Index\Module\Model::getModuleWithConfig('product', $module_id);
        if (!$module || !\Product\Init\Controller::allowed($login, $module->config, ['can_manage_stock'])) {
            return null;
        }
        return $module;
    }

    /**
     * Variants of a product with their readable label + current available stock.
     *
     * @param int $module_id
     * @param int $product_id
     *
     * @return array
     */
    protected static function loadVariants($module_id, $product_id)
    {
        $rows = \Kotchasan\Model::createQuery()
            ->select('id', 'sku')
            ->from('product_variant')
            ->where([['product_id', $product_id], ['module_id', $module_id]])
            ->orderBy('id')
            ->fetchAll();

        $variants = [];
        foreach ($rows as $r) {
            $valueIds = [];
            foreach (\Kotchasan\Model::createQuery()->select('attribute_value_id')->from('product_variant_value')->where(['variant_id', $r->id])->fetchAll() as $vv) {
                $valueIds[] = (int) $vv->attribute_value_id;
            }
            $label = \Product\Attribute\Model::buildVariantLabel($module_id, $valueIds);
            $variants[] = [
                'id' => (int) $r->id,
                'sku' => $r->sku,
                'label' => $label,
                'stock_qty' => \Product\Stock\Model::available((int) $r->id)
            ];
        }
        return $variants;
    }

    /**
     * GET /api/product/stock/get
     * Load a product's variants (with available stock), recent lots and the
     * recent movement ledger for the receive form.
     *
     * @param Request $request
     *
     * @return mixed
     */
    public function get(Request $request)
    {
        try {
            ApiController::validateMethod($request, 'GET');

            $login = $this->authenticateRequest($request);
            if (!$login) {
                return $this->redirectResponse('/login', 'Unauthorized', 401);
            }

            $module = $this->resolveModule($login, $request->get('module_id')->toInt());
            if (!$module) {
                return $this->redirectResponse('/404', 'No data available', 404);
            }

            $product_id = $request->get('id')->toInt();
            $product = \Kotchasan\Model::createQuery()
                ->select('P.id', 'P.sku', 'P.manage_stock', 'D.topic')
                ->from('product P')
                ->join('product_detail D', [['D.id', 'P.id'], ['D.module_id', 'P.module_id'], ['D.language', \Product\Index\Model::languages()]])
                ->where([['P.id', $product_id], ['P.module_id', $module->id]])
                ->first();
            if (!$product) {
                return $this->redirectResponse('/404', 'No data available', 404);
            }

            $variants = self::loadVariants($module->id, $product_id);

            $lots = \Kotchasan\Model::createQuery()
                ->select('id', 'variant_id', 'qty_in', 'qty_remaining', 'unit_cost', 'received_at', 'ref')
                ->from('product_stock_lot')
                ->where([['product_id', $product_id], ['module_id', $module->id]])
                ->orderBy('received_at', 'DESC')
                ->orderBy('id', 'DESC')
                ->limit(50)
                ->fetchAll();

            $movements = \Kotchasan\Model::createQuery()
                ->select('id', 'variant_id', 'type', 'qty', 'unit_cost', 'ref_type', 'ref_id', 'created_at')
                ->from('product_stock_movement')
                ->where([['product_id', $product_id], ['module_id', $module->id]])
                ->orderBy('id', 'DESC')
                ->limit(50)
                ->fetchAll();

            return $this->successResponse([
                'module_id' => $module->id,
                'product' => [
                    'id' => (int) $product->id,
                    'sku' => $product->sku,
                    'topic' => $product->topic,
                    'manage_stock' => (int) $product->manage_stock
                ],
                'variants' => $variants,
                'lots' => $lots,
                'movements' => $movements
            ], 'Product stock detail retrieved');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), $e->getCode() ?: 500, $e);
        }
    }

    /**
     * POST /api/product/stock/receive
     * Receive stock into a new FIFO lot.
     *
     * @param Request $request
     *
     * @return mixed
     */
    public function receive(Request $request)
    {
        try {
            ApiController::validateMethod($request, 'POST');
            $this->validateCsrfToken($request);

            $login = $this->authenticateRequest($request);
            if (!$login) {
                return $this->redirectResponse('/login', 'Unauthorized', 401);
            }

            $module = $this->resolveModule($login, $request->post('module_id')->toInt());
            if (!$module) {
                return $this->errorResponse('No data available', 404);
            }

            $product_id = $request->post('product_id')->toInt();
            $variant_id = $request->post('variant_id')->toInt();
            $qty = $request->post('qty')->toInt();
            $unit_cost = $request->post('unit_cost')->toFloat();
            $ref = $request->post('ref')->topic();

            $errors = [];
            if ($variant_id <= 0 || !$this->variantBelongs($module->id, $product_id, $variant_id)) {
                $errors['variant_id'] = \Kotchasan\Language::get('Please select an item');
            }
            if ($qty <= 0) {
                $errors['qty'] = \Kotchasan\Language::get('Please fill in');
            }
            if (!empty($errors)) {
                return $this->formErrorResponse($errors, 422);
            }

            \Product\Stock\Model::receive($module->id, $product_id, $variant_id, $qty, $unit_cost, $ref, $login->id);
            \Index\Log\Model::add($product_id, 'product', 'Product', 'Receive stock variant '.$variant_id.' qty '.$qty, $login->id);

            return $this->redirectResponse('reload', 'Saved successfully', 200, 1000);
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), $e->getCode() ?: 500, $e);
        }
    }

    /**
     * POST /api/product/stock/adjust
     * Manual stock adjustment. A positive delta creates a new lot (so FIFO has
     * cost to draw from); a negative delta deducts from the oldest lots. Either
     * way a product_stock_movement type='adjust' row is recorded and the cache
     * recomputed. Wrapped in a transaction.
     *
     * @param Request $request
     *
     * @return mixed
     */
    public function adjust(Request $request)
    {
        try {
            ApiController::validateMethod($request, 'POST');
            $this->validateCsrfToken($request);

            $login = $this->authenticateRequest($request);
            if (!$login) {
                return $this->redirectResponse('/login', 'Unauthorized', 401);
            }

            $module = $this->resolveModule($login, $request->post('module_id')->toInt());
            if (!$module) {
                return $this->errorResponse('No data available', 404);
            }

            $product_id = $request->post('product_id')->toInt();
            $variant_id = $request->post('variant_id')->toInt();
            $qty = $request->post('qty')->toInt();
            $unit_cost = $request->post('unit_cost')->toFloat();
            $ref = $request->post('ref')->topic();

            $errors = [];
            if ($variant_id <= 0 || !$this->variantBelongs($module->id, $product_id, $variant_id)) {
                $errors['variant_id'] = \Kotchasan\Language::get('Please select an item');
            }
            if ($qty === 0) {
                $errors['qty'] = \Kotchasan\Language::get('Please fill in');
            }
            if (!empty($errors)) {
                return $this->formErrorResponse($errors, 422);
            }

            $db = \Kotchasan\DB::create();
            $db->beginTransaction();
            try {
                if ($qty > 0) {
                    // Positive adjustment: add a new lot at the provided cost.
                    $lot_id = $db->nextId('product_stock_lot');
                    $db->insert('product_stock_lot', [
                        'id' => $lot_id,
                        'module_id' => $module->id,
                        'product_id' => $product_id,
                        'variant_id' => $variant_id,
                        'qty_in' => $qty,
                        'qty_remaining' => $qty,
                        'unit_cost' => $unit_cost,
                        'received_at' => date('Y-m-d H:i:s'),
                        'ref' => $ref !== '' ? $ref : 'adjust'
                    ]);
                    $db->insert('product_stock_movement', [
                        'id' => $db->nextId('product_stock_movement'),
                        'module_id' => $module->id,
                        'product_id' => $product_id,
                        'variant_id' => $variant_id,
                        'lot_id' => $lot_id,
                        'type' => 'adjust',
                        'qty' => $qty,
                        'unit_cost' => $unit_cost,
                        'ref_type' => 'manual',
                        'ref_id' => 0,
                        'created_by' => $login->id,
                        'created_at' => date('Y-m-d H:i:s')
                    ]);
                } else {
                    // Negative adjustment: reduce oldest lots (FIFO).
                    $need = -$qty;
                    if ($need > \Product\Stock\Model::available($variant_id)) {
                        $db->rollback();
                        return $this->errorResponse('Insufficient stock', 422);
                    }
                    $lots = \Kotchasan\Model::createQuery()
                        ->select('id', 'qty_remaining', 'unit_cost')
                        ->from('product_stock_lot')
                        ->where([['variant_id', $variant_id], ['qty_remaining', '>', 0]])
                        ->orderBy('received_at', 'ASC')
                        ->orderBy('id', 'ASC')
                        ->fetchAll();
                    foreach ($lots as $lot) {
                        if ($need <= 0) {
                            break;
                        }
                        $take = min($need, (int) $lot->qty_remaining);
                        $db->update('product_stock_lot', ['id', $lot->id], ['qty_remaining' => (int) $lot->qty_remaining - $take]);
                        $db->insert('product_stock_movement', [
                            'id' => $db->nextId('product_stock_movement'),
                            'module_id' => $module->id,
                            'product_id' => $product_id,
                            'variant_id' => $variant_id,
                            'lot_id' => $lot->id,
                            'type' => 'adjust',
                            'qty' => -$take,
                            'unit_cost' => $lot->unit_cost,
                            'ref_type' => 'manual',
                            'ref_id' => 0,
                            'created_by' => $login->id,
                            'created_at' => date('Y-m-d H:i:s')
                        ]);
                        $need -= $take;
                    }
                }
                $db->commit();
            } catch (\Exception $e) {
                $db->rollback();
                throw $e;
            }

            \Product\Stock\Model::recomputeCache($variant_id);
            \Index\Log\Model::add($product_id, 'product', 'Product', 'Adjust stock variant '.$variant_id.' qty '.$qty, $login->id);

            return $this->redirectResponse('reload', 'Saved successfully', 200, 1000);
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), $e->getCode() ?: 500, $e);
        }
    }

    /**
     * GET /api/product/stock/movements
     * Movement ledger for a variant (paginated).
     *
     * @param Request $request
     *
     * @return mixed
     */
    public function movements(Request $request)
    {
        try {
            ApiController::validateMethod($request, 'GET');

            $login = $this->authenticateRequest($request);
            if (!$login) {
                return $this->redirectResponse('/login', 'Unauthorized', 401);
            }

            $module = $this->resolveModule($login, $request->get('module_id')->toInt());
            if (!$module) {
                return $this->redirectResponse('/404', 'No data available', 404);
            }

            $variant_id = $request->get('variant_id')->toInt();
            $page = max(1, $request->get('page', 1)->toInt());
            $perPage = min(100, max(1, $request->get('pageSize', 25)->toInt()));

            $rows = \Kotchasan\Model::createQuery()
                ->select('id', 'variant_id', 'type', 'qty', 'unit_cost', 'ref_type', 'ref_id', 'created_by', 'created_at')
                ->from('product_stock_movement')
                ->where([['module_id', $module->id], ['variant_id', $variant_id]])
                ->orderBy('id', 'DESC')
                ->limit($perPage, ($page - 1) * $perPage)
                ->fetchAll();

            return $this->successResponse([
                'module_id' => $module->id,
                'variant_id' => $variant_id,
                'movements' => $rows
            ], 'Stock movements retrieved');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), $e->getCode() ?: 500, $e);
        }
    }

    /**
     * Verify a variant belongs to the given product/module.
     *
     * @param int $module_id
     * @param int $product_id
     * @param int $variant_id
     *
     * @return bool
     */
    protected function variantBelongs($module_id, $product_id, $variant_id)
    {
        return (bool) \Kotchasan\DB::create()->first('product_variant', [
            ['id', $variant_id],
            ['product_id', $product_id],
            ['module_id', $module_id]
        ], ['id']);
    }
}

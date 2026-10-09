<?php
/**
 * @filesource modules/product/controllers/write.php
 *
 * @copyright 2026 Goragod.com
 * @license https://www.kotchasan.com/license/
 */

namespace Product\Write;

use Gcms\Api as ApiController;
use Kotchasan\File;
use Kotchasan\Http\Request;
use Kotchasan\Language;

/**
 * API Product CRUD Controller
 *
 * Handles create/edit of products together with their multilingual content,
 * attribute variants (SKU) and gallery images.
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Controller extends ApiController
{
    /**
     * GET /api/product/write/get
     * Load a product (or blank skeleton) for the admin editor.
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
                return $this->errorResponse('Unauthorized', 401);
            }

            $module = \Index\Module\Model::getModuleWithConfig('product', $request->get('module_id')->toInt());
            if (!$module || !\Product\Init\Controller::allowed($login, $module->config, ['can_manage'])) {
                return $this->redirectResponse('/404', 'No data available', 404);
            }

            $product = \Product\Write\Model::get($request->get('id')->toInt(), $module->id);
            if (!$product) {
                return $this->redirectResponse('/404', 'No data available', 404);
            }

            $product->img_typies = implode(', ', self::$cfg->img_typies ?? ['jpg', 'jpeg', 'png', 'webp']);
            $product->options = [
                'category' => \Product\Category\Model::toOptions($module->id),
                'attributes' => \Product\Attribute\Model::toOptions($module->id)
            ];

            return $this->successResponse($product, 'Product detail retrieved');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), $e->getCode() ?: 500, $e);
        }
    }

    /**
     * POST /api/product/write/save
     * Create or update a product.
     *
     * @param Request $request
     *
     * @return mixed
     */
    public function save(Request $request)
    {
        try {
            ApiController::validateMethod($request, 'POST');
            $this->validateCsrfToken($request);

            $login = $this->authenticateRequest($request);
            if (!$login) {
                return $this->redirectResponse('/login', 'Unauthorized', 401);
            }

            $module = \Index\Module\Model::getModuleWithConfig('product', $request->post('module_id')->toInt());
            if (!$module || !\Product\Init\Controller::allowed($login, $module->config, ['can_manage'])) {
                return $this->errorResponse('No data available', 404);
            }

            $product = \Product\Write\Model::get($request->post('id')->toInt(), $module->id);
            if (!$product) {
                return $this->errorResponse('No data available', 404);
            }

            $product_type = $request->post('product_type')->filter('a-z') === 'variable' ? 'variable' : 'simple';

            $save = [
                'module_id' => $module->id,
                'category_id' => $request->post('category_id')->toInt(),
                'sku' => $request->post('sku')->topic(),
                'product_type' => $product_type,
                'base_price' => $request->post('base_price')->toFloat(),
                'manage_stock' => $request->post('manage_stock')->toBoolean() ? 1 : 0,
                'weight' => $request->post('weight')->toFloat(),
                'featured' => $request->post('featured')->toBoolean() ? 1 : 0,
                'published' => $request->post('published')->toBoolean() ? 1 : 0,
                'alias' => \Web\Gcms::aliasName($request->post('alias')->topic()),
                'updated_at' => date('Y-m-d H:i:s')
            ];

            // Multilingual detail — the editor posts it (and `variants`) as a
            // JSON string in a hidden input: json() decodes that string, where
            // toJson() would only re-encode it and json_decode() give a string back
            $detail = $request->post('detail')->json([]);
            if (!is_array($detail)) {
                $detail = [];
            }

            // Variants
            $variants = $request->post('variants')->json([]);
            if (!is_array($variants)) {
                $variants = [];
            }

            $errors = [];
            $topicTh = isset($detail['th']['topic']) ? trim($detail['th']['topic']) : '';
            $topicEn = isset($detail['en']['topic']) ? trim($detail['en']['topic']) : '';
            if ($topicTh === '' && $topicEn === '') {
                $errors['topic_th'] = Language::get('Please fill in');
            }
            if ($product_type === 'variable' && empty($variants)) {
                $errors['variants'] = Language::get('Please add at least one variant');
            }

            if (!empty($errors)) {
                return $this->formErrorResponse($errors, 422);
            }

            $db = \Kotchasan\DB::create();

            if ($product->id > 0) {
                $save['id'] = $product->id;
                $db->update('product', ['id', $product->id], $save);
            } else {
                $save['id'] = $db->nextId('product');
                $save['created_at'] = date('Y-m-d H:i:s');
                // Fill legacy columns an upgraded site's `product` table may
                // still carry (NOT NULL, no default) — see
                // Product\Write\Model::legacyColumnDefaults().
                $db->insert('product', $save + \Product\Write\Model::legacyColumnDefaults());
            }
            $product_id = $save['id'];

            // Multilingual detail rows. Pages join product_detail on
            // language IN ('', current), so every language needs a row:
            // content in one language only is stored once as the shared ''
            // row (as the legacy module did) instead of a filled row plus an
            // empty one that would show a nameless product in the other
            // language.
            $rows = [];
            foreach (\Product\Write\Model::$languages as $lng) {
                $d = $detail[$lng] ?? [];
                $rows[$lng] = [
                    'id' => $product_id,
                    'module_id' => $module->id,
                    'language' => $lng,
                    'topic' => isset($d['topic']) ? trim($d['topic']) : '',
                    'description' => isset($d['description']) ? trim($d['description']) : '',
                    'detail' => isset($d['detail']) ? $d['detail'] : '',
                    'keywords' => isset($d['keywords']) ? trim($d['keywords']) : ''
                ];
            }
            $filled = array_filter($rows, fn($row) => $row['topic'] !== '');
            if (count($filled) < count($rows)) {
                $rows = ['' => ['language' => ''] + reset($filled)];
            }
            $db->delete('product_detail', ['id', $product_id], 0);
            foreach ($rows as $row) {
                $db->insert('product_detail', $row);
            }

            // Replace variants
            self::saveVariants($db, $module->id, $product_id, $product_type, $variants, $save);

            // Upload gallery images
            self::saveImages($request, $db, $module->id, $product_id, $errors);

            if (!empty($errors)) {
                return $this->formErrorResponse($errors, 422);
            }

            \Index\Log\Model::add($product_id, 'product', 'Product', 'Save Product: '.($topicTh ?: $topicEn), $login->id);

            return $this->redirectResponse('back', 'Saved successfully');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), $e->getCode() ?: 500, $e);
        }
    }

    /**
     * Persist variants and their attribute-value mapping.
     * For a simple product a single implicit variant is kept so the cart /
     * stock subsystems always have a variant_id to reference.
     *
     * Variants are matched to the existing rows by their attribute-value
     * combination and updated in place: stock lots, stock movements, cart
     * items and order lines all reference product_variant.id, so re-creating
     * the rows on every save would orphan the stock (available() sums lots by
     * variant_id) and reset stock_qty to 0. Only variants that are no longer
     * sent are deleted.
     *
     * @param object $db
     * @param int    $module_id
     * @param int    $product_id
     * @param string $product_type
     * @param array  $variants
     * @param array  $save  parent product row (for base price/stock fallback)
     *
     * @return void
     */
    protected static function saveVariants($db, $module_id, $product_id, $product_type, $variants, $save)
    {
        // Existing variants: id => sorted attribute_value_id list
        $existing = [];
        foreach ($db->select('product_variant', [['product_id', $product_id], ['module_id', $module_id]], [], ['id']) as $r) {
            $existing[(int) $r->id] = [];
        }
        if (!empty($existing)) {
            foreach ($db->select('product_variant_value', [['variant_id', array_keys($existing)], ['module_id', $module_id]], [], ['variant_id', 'attribute_value_id']) as $m) {
                $existing[(int) $m->variant_id][] = (int) $m->attribute_value_id;
            }
        }
        $byKey = [];
        foreach ($existing as $id => $valueIds) {
            sort($valueIds);
            $key = implode(',', $valueIds);
            if (!isset($byKey[$key])) {
                $byKey[$key] = $id;
            }
        }

        // A variant's picture must be one of this product's pictures
        $imageIds = [];
        foreach ($db->select('product_image', [['product_id', $product_id], ['module_id', $module_id]], [], ['id']) as $r) {
            $imageIds[(int) $r->id] = true;
        }

        if ($product_type === 'simple') {
            // Implicit single variant mirroring the parent product
            $rows = [[
                'key' => '',
                'values' => [],
                'data' => [
                    'sku' => $save['sku'],
                    'price' => $save['base_price'],
                    'sale_price' => null,
                    'weight' => $save['weight'],
                    'published' => $save['published'],
                    'image_id' => 0
                ]
            ]];
            if (!isset($byKey['']) && !empty($existing)) {
                // switched from variable: reuse the oldest variant
                $byKey[''] = min(array_keys($existing));
            }
        } else {
            $rows = [];
            foreach ($variants as $v) {
                $values = [];
                foreach (($v['values'] ?? []) as $val) {
                    $aid = (int) ($val['attribute_id'] ?? 0);
                    $avid = (int) ($val['attribute_value_id'] ?? 0);
                    if ($aid > 0 && $avid > 0) {
                        $values[$aid] = $avid;
                    }
                }
                $valueIds = array_values($values);
                sort($valueIds);
                $rows[] = [
                    'key' => implode(',', $valueIds),
                    'values' => $values,
                    'data' => [
                        'sku' => isset($v['sku']) ? trim($v['sku']) : '',
                        'price' => isset($v['price']) ? (float) $v['price'] : 0,
                        'sale_price' => isset($v['sale_price']) && $v['sale_price'] !== '' ? (float) $v['sale_price'] : null,
                        'weight' => isset($v['weight']) ? (float) $v['weight'] : 0,
                        'published' => !empty($v['published']) ? 1 : 0,
                        'image_id' => isset($imageIds[(int) ($v['image_id'] ?? 0)]) ? (int) $v['image_id'] : 0
                    ]
                ];
            }
        }

        $kept = [];
        foreach ($rows as $row) {
            $vid = $byKey[$row['key']] ?? 0;
            if ($vid > 0 && !isset($kept[$vid])) {
                $db->update('product_variant', ['id', $vid], $row['data']);
                $db->delete('product_variant_value', [['variant_id', $vid], ['module_id', $module_id]], 0);
            } else {
                $vid = $db->nextId('product_variant');
                $db->insert('product_variant', $row['data'] + [
                    'id' => $vid,
                    'product_id' => $product_id,
                    'module_id' => $module_id,
                    'stock_qty' => 0
                ]);
            }
            $kept[$vid] = true;
            foreach ($row['values'] as $aid => $avid) {
                $db->insert('product_variant_value', [
                    'variant_id' => $vid,
                    'attribute_id' => $aid,
                    'attribute_value_id' => $avid,
                    'module_id' => $module_id
                ]);
            }
        }

        $removed = array_values(array_diff(array_keys($existing), array_keys($kept)));
        if (!empty($removed)) {
            $db->delete('product_variant', [['id', $removed], ['module_id', $module_id]], 0);
            $db->delete('product_variant_value', [['variant_id', $removed], ['module_id', $module_id]], 0);
        }
        // product.stock_qty caches the sum of its variants' stock
        $sum = \Product\Write\Model::createQuery()
            ->select(\Kotchasan\Database\Sql::create('COALESCE(SUM(`stock_qty`), 0) AS `qty`'))
            ->from('product_variant')
            ->where(['product_id', $product_id])
            ->first();
        $db->update('product', ['id', $product_id], ['stock_qty' => $sum ? (int) $sum->qty : 0]);
    }

    /**
     * Store uploaded gallery images for a product.
     *
     * @param Request $request
     * @param object  $db
     * @param int     $module_id
     * @param int     $product_id
     * @param array   $errors  (by reference)
     *
     * @return void
     */
    protected static function saveImages(Request $request, $db, $module_id, $product_id, &$errors)
    {
        $dir = ROOT_PATH.DATA_FOLDER.'product/'.$product_id.'/';
        $hasPrimary = (bool) $db->first('product_image', [['product_id', $product_id], ['is_primary', 1]], ['id']);

        foreach ($request->getUploadedFiles() as $field => $file) {
            // Only handle image upload fields (image, image[], images[]...)
            if (strpos($field, 'image') === false) {
                continue;
            }
            $files = is_array($file) ? $file : [$file];
            foreach ($files as $f) {
                if (!is_object($f) || !method_exists($f, 'hasUploadFile') || !$f->hasUploadFile()) {
                    continue;
                }
                if (!File::makeDirectory($dir)) {
                    $errors['image'] = Language::replace('Directory %s cannot be created or is read-only.', DATA_FOLDER.'product/'.$product_id.'/');
                    return;
                }
                $imgId = $db->nextId('product_image');
                $filename = $imgId.self::$cfg->stored_img_type;
                try {
                    $f->resizeImage(self::$cfg->img_typies, $dir, $filename, self::$cfg->stored_img_size);
                    $db->insert('product_image', [
                        'id' => $imgId,
                        'product_id' => $product_id,
                        'module_id' => $module_id,
                        'filename' => $filename,
                        'sort' => $imgId,
                        'is_primary' => $hasPrimary ? 0 : 1
                    ]);
                    $hasPrimary = true;
                } catch (\Exception $exc) {
                    $errors['image'] = Language::get($exc->getMessage());
                }
            }
        }
    }

    /**
     * POST /api/product/write/remove-image
     * The gallery field's actions (FileElementFactory): delete one image
     * (id), or action=sort with the new order — the first image becomes the
     * product's main picture.
     *
     * @param Request $request
     *
     * @return mixed
     */
    public function removeImage(Request $request)
    {
        try {
            ApiController::validateMethod($request, 'POST');
            $this->validateCsrfToken($request);

            $login = $this->authenticateRequest($request);
            if (!$login) {
                return $this->errorResponse('Unauthorized', 401);
            }

            $module_id = $request->post('module_id')->toInt();
            $module = \Index\Module\Model::getModuleWithConfig('product', $module_id);
            if (!$module || !\Product\Init\Controller::allowed($login, $module->config, ['can_manage'])) {
                return $this->errorResponse('No data available', 404);
            }

            $db = \Kotchasan\DB::create();
            if ($request->post('action')->filter('a-z') === 'sort') {
                return $this->sortImages($db, $module_id, $request->post('order')->toArray(), $login);
            }

            $image_id = $request->post('id')->toInt() ?: $request->post('image_id')->toInt();
            $img = $db->first('product_image', [['id', $image_id], ['module_id', $module_id]], ['id', 'product_id', 'filename', 'is_primary']);
            if ($img) {
                $path = ROOT_PATH.DATA_FOLDER.'product/'.$img->product_id.'/'.$img->filename;
                if (file_exists($path)) {
                    unlink($path);
                }
                $db->delete('product_image', ['id', $image_id], 1);
                $db->update('product_variant', [['image_id', $image_id], ['module_id', $module_id]], ['image_id' => 0]);
                // The next picture in order takes over as the main one
                if ($img->is_primary) {
                    $next = \Kotchasan\Model::createQuery()
                        ->select('id')
                        ->from('product_image')
                        ->where([['product_id', $img->product_id], ['module_id', $module_id]])
                        ->orderBy('sort')
                        ->first();
                    if ($next) {
                        $db->update('product_image', ['id', $next->id], ['is_primary' => 1]);
                    }
                }
                \Index\Log\Model::add($module_id, 'product', 'Product', 'Delete product image ID:'.$image_id, $login->id);
            }
            return $this->successResponse([], 'Image deleted');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), $e->getCode() ?: 500, $e);
        }
    }

    /**
     * Save a dragged image order. Every id must be a picture of the same
     * product; the first one becomes the main picture.
     *
     * @param object $db
     * @param int    $module_id
     * @param array  $order  [{id, position}, ...]
     * @param object $login
     *
     * @return mixed
     */
    protected function sortImages($db, $module_id, array $order, $login)
    {
        $ids = [];
        foreach ($order as $item) {
            if (is_array($item) && !empty($item['id'])) {
                $ids[(int) ($item['position'] ?? count($ids))] = (int) $item['id'];
            }
        }
        ksort($ids);
        $ids = array_values(array_unique($ids));
        if (empty($ids)) {
            return $this->errorResponse('Invalid data', 400);
        }

        $rows = \Kotchasan\Model::createQuery()
            ->select('id', 'product_id')
            ->from('product_image')
            ->where([['id', $ids], ['module_id', $module_id]])
            ->fetchAll();
        $products = array_unique(array_map(fn($r) => (int) $r->product_id, $rows));
        if (count($rows) !== count($ids) || count($products) !== 1) {
            return $this->errorResponse('Invalid data', 400);
        }

        foreach ($ids as $i => $id) {
            $db->update('product_image', ['id', $id], [
                'sort' => $i + 1,
                'is_primary' => $i === 0 ? 1 : 0
            ]);
        }
        \Index\Log\Model::add($module_id, 'product', 'Product', 'Sort product images ID:'.reset($products), $login->id);

        return $this->successResponse([], 'Saved successfully');
    }
}

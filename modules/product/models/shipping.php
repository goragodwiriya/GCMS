<?php
/**
 * @filesource modules/product/models/shipping.php
 *
 * @copyright 2026 Goragod.com
 * @license https://www.kotchasan.com/license/
 */

namespace Product\Shipping;

use Kotchasan\Language;

/**
 * Shipping Method Model
 *
 * Methods are stored in {prefix}_product_shipping_method. The name is JSON
 * encoded {th, en} like the shared category topic. The fee for an order is
 * computed from calc_type (flat | by_weight | free) and optional free_over.
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Model extends \Kotchasan\Model
{
    /**
     * @var array
     */
    public static $languages = ['th', 'en'];

    /**
     * Allowed calculation types.
     *
     * @var array
     */
    public static $calcTypes = ['flat', 'by_weight', 'free'];

    /**
     * Get all shipping methods for the editor (name decoded {th,en}).
     *
     * @param int $module_id
     *
     * @return array
     */
    public static function getAll($module_id)
    {
        $rows = static::createQuery()
            ->select('id', 'name', 'calc_type', 'base_rate', 'rate_per_kg', 'free_over', 'published', 'sort')
            ->from('product_shipping_method')
            ->where(['module_id', $module_id])
            ->orderBy('sort', 'id')
            ->fetchAll();

        $data = [];
        foreach ($rows as $r) {
            $data[] = [
                'id' => (int) $r->id,
                'name' => self::decodeLang($r->name),
                'calc_type' => in_array($r->calc_type, self::$calcTypes, true) ? $r->calc_type : 'flat',
                'base_rate' => (float) $r->base_rate,
                'rate_per_kg' => (float) $r->rate_per_kg,
                'free_over' => $r->free_over === null ? '' : (float) $r->free_over,
                'published' => (int) $r->published
            ];
        }
        return $data;
    }

    /**
     * Replace all shipping methods for a module (delete then insert).
     *
     * Expected $items structure:
     * [ ['id'=>0,'name'=>['th'=>'','en'=>''],'calc_type'=>'flat','base_rate'=>0,
     *    'rate_per_kg'=>0,'free_over'=>'','published'=>1], ... ]
     *
     * @param int   $module_id
     * @param array $items
     *
     * @return void
     */
    public static function saveAll($module_id, $items)
    {
        // Update rows in place by id (orders keep shipping_method_id),
        // insert new ones, delete the ones no longer sent
        $db = \Kotchasan\DB::create();
        $existing = [];
        foreach ($db->select('product_shipping_method', ['module_id', $module_id], [], ['id']) as $r) {
            $existing[(int) $r->id] = true;
        }

        $kept = [];
        $sort = 0;
        foreach ($items as $item) {
            $name = self::encodeLang($item['name'] ?? []);
            if ($name === '') {
                continue;
            }
            $calc = isset($item['calc_type']) && in_array($item['calc_type'], self::$calcTypes, true) ? $item['calc_type'] : 'flat';
            $freeOver = (isset($item['free_over']) && $item['free_over'] !== '' && $item['free_over'] !== null) ? (float) $item['free_over'] : null;
            $row = [
                'name' => $name,
                'calc_type' => $calc,
                'base_rate' => isset($item['base_rate']) ? (float) $item['base_rate'] : 0,
                'rate_per_kg' => isset($item['rate_per_kg']) ? (float) $item['rate_per_kg'] : 0,
                'free_over' => $freeOver,
                'published' => !empty($item['published']) ? 1 : 0,
                'sort' => $sort++
            ];
            $id = (int) ($item['id'] ?? 0);
            if ($id > 0 && isset($existing[$id]) && !isset($kept[$id])) {
                $db->update('product_shipping_method', ['id', $id], $row);
            } else {
                $id = $db->nextId('product_shipping_method');
                $db->insert('product_shipping_method', $row + ['id' => $id, 'module_id' => $module_id]);
            }
            $kept[$id] = true;
        }

        $removed = array_keys(array_diff_key($existing, $kept));
        if (!empty($removed)) {
            $db->delete('product_shipping_method', [['id', $removed], ['module_id', $module_id]], 0);
        }
    }

    /**
     * Compute the shipping fee for a method.
     *
     * flat       => base_rate
     * by_weight  => base_rate + rate_per_kg * weight
     * free       => 0
     * If free_over is set and subtotal >= free_over => 0.
     *
     * @param int   $methodId
     * @param float $subtotal
     * @param float $totalWeightKg
     *
     * @return float
     */
    public static function calcFee($methodId, $subtotal, $totalWeightKg = 0)
    {
        $methodId = (int) $methodId;
        if ($methodId <= 0) {
            return 0.0;
        }
        $method = static::createQuery()
            ->select('calc_type', 'base_rate', 'rate_per_kg', 'free_over', 'published')
            ->from('product_shipping_method')
            ->where(['id', $methodId])
            ->first();
        if (!$method) {
            return 0.0;
        }
        if ($method->free_over !== null && (float) $subtotal >= (float) $method->free_over) {
            return 0.0;
        }
        switch ($method->calc_type) {
            case 'free':
                return 0.0;
            case 'by_weight':
                return round((float) $method->base_rate + (float) $method->rate_per_kg * (float) $totalWeightKg, 2);
            case 'flat':
            default:
                return (float) $method->base_rate;
        }
    }

    /**
     * Published methods of a module with their fee for this order
     * (subtotal and weight), for the checkout's shipping choice.
     *
     * @param int   $module_id
     * @param float $subtotal
     * @param float $weightKg
     *
     * @return array list of { id, name, fee }
     */
    public static function quotes($module_id, $subtotal, $weightKg)
    {
        $quotes = [];
        foreach (self::options($module_id) as $option) {
            $quotes[] = [
                'id' => (int) $option['value'],
                'name' => $option['text'],
                'fee' => self::calcFee((int) $option['value'], $subtotal, $weightKg)
            ];
        }

        return $quotes;
    }

    /**
     * Published shipping methods as options for selects (current language).
     *
     * @param int $module_id
     *
     * @return array
     */
    public static function options($module_id)
    {
        $rows = static::createQuery()
            ->select('id', 'name')
            ->from('product_shipping_method')
            ->where([['module_id', $module_id], ['published', 1]])
            ->orderBy('sort', 'id')
            ->fetchAll();
        $lng = Language::name();
        $options = [];
        foreach ($rows as $r) {
            $name = json_decode($r->name, true);
            $text = is_array($name) ? ($name[$lng] ?? reset($name)) : (string) $r->name;
            $options[] = ['value' => (string) $r->id, 'text' => $text];
        }
        return $options;
    }

    /**
     * Decode a JSON {th,en} field into an associative array with all languages present.
     *
     * @param string $json
     *
     * @return array
     */
    protected static function decodeLang($json)
    {
        $data = json_decode($json, true);
        if (!is_array($data)) {
            $data = [];
        }
        $out = [];
        foreach (self::$languages as $lng) {
            $out[$lng] = $data[$lng] ?? '';
        }
        return $out;
    }

    /**
     * Encode an associative {th,en} array to JSON. Returns '' if empty.
     *
     * @param array $arr
     *
     * @return string
     */
    protected static function encodeLang($arr)
    {
        $clean = [];
        foreach (self::$languages as $lng) {
            $val = isset($arr[$lng]) ? trim($arr[$lng]) : '';
            if ($val !== '') {
                $clean[$lng] = $val;
            }
        }
        if (empty($clean)) {
            return '';
        }
        return json_encode($clean, JSON_UNESCAPED_UNICODE);
    }
}

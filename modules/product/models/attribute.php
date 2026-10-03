<?php
/**
 * @filesource modules/product/models/attribute.php
 *
 * @copyright 2026 Goragod.com
 * @license https://www.kotchasan.com/license/
 */

namespace Product\Attribute;

use Kotchasan\Language;

/**
 * Product Attribute & Attribute Value Model
 *
 * Attributes (e.g. Size, Color, Grind) and their allowed values are stored in
 * {prefix}_product_attribute and {prefix}_product_attribute_value. Names/values
 * are JSON encoded {th, en} like the shared category topic.
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
     * Get all attributes with their values for the editor
     *
     * @param int $module_id
     *
     * @return array
     */
    public static function getAll($module_id)
    {
        $attributes = static::createQuery()
            ->select('id', 'name', 'sort')
            ->from('product_attribute')
            ->where(['module_id', $module_id])
            ->orderBy('sort', 'id')
            ->cacheOn()
            ->fetchAll();

        $values = static::createQuery()
            ->select('id', 'attribute_id', 'value', 'sort')
            ->from('product_attribute_value')
            ->where(['module_id', $module_id])
            ->orderBy('sort', 'id')
            ->cacheOn()
            ->fetchAll();

        $byAttr = [];
        foreach ($values as $v) {
            $byAttr[$v->attribute_id][] = [
                'id' => (int) $v->id,
                'value' => self::decodeLang($v->value)
            ];
        }

        $data = [];
        foreach ($attributes as $a) {
            $data[] = [
                'id' => (int) $a->id,
                'name' => self::decodeLang($a->name),
                'values' => $byAttr[$a->id] ?? []
            ];
        }
        return $data;
    }

    /**
     * Save all attributes + values of a module.
     *
     * Rows sent with the id they were loaded with (getAll()) are updated in
     * place, new rows are inserted and rows no longer sent are deleted.
     * product_variant_value references these ids, so re-creating every row
     * on each save would detach every variant from its attribute values.
     *
     * Expected $items structure:
     * [ ['id'=>0, 'name'=>['th'=>'','en'=>''], 'values'=>[ ['id'=>0,'value'=>['th'=>'','en'=>'']], ... ] ], ... ]
     *
     * @param int   $module_id
     * @param array $items
     *
     * @return void
     */
    public static function saveAll($module_id, $items)
    {
        $db = \Kotchasan\DB::create();
        $attributeIds = [];
        foreach ($db->select('product_attribute', ['module_id', $module_id], [], ['id']) as $r) {
            $attributeIds[(int) $r->id] = true;
        }
        $valueOwner = [];
        foreach ($db->select('product_attribute_value', ['module_id', $module_id], [], ['id', 'attribute_id']) as $r) {
            $valueOwner[(int) $r->id] = (int) $r->attribute_id;
        }

        $keptAttributes = [];
        $keptValues = [];
        $sort = 0;
        foreach ($items as $attr) {
            $name = self::encodeLang($attr['name'] ?? []);
            if ($name === '') {
                continue;
            }
            $attribute_id = (int) ($attr['id'] ?? 0);
            if ($attribute_id > 0 && isset($attributeIds[$attribute_id]) && !isset($keptAttributes[$attribute_id])) {
                $db->update('product_attribute', ['id', $attribute_id], ['name' => $name, 'sort' => $sort++]);
            } else {
                $attribute_id = $db->nextId('product_attribute');
                $db->insert('product_attribute', [
                    'id' => $attribute_id,
                    'module_id' => $module_id,
                    'name' => $name,
                    'sort' => $sort++
                ]);
            }
            $keptAttributes[$attribute_id] = true;
            $vsort = 0;
            foreach (($attr['values'] ?? []) as $val) {
                $value = self::encodeLang($val['value'] ?? []);
                if ($value === '') {
                    continue;
                }
                $value_id = (int) ($val['id'] ?? 0);
                if ($value_id > 0 && ($valueOwner[$value_id] ?? 0) === $attribute_id && !isset($keptValues[$value_id])) {
                    $db->update('product_attribute_value', ['id', $value_id], ['value' => $value, 'sort' => $vsort++]);
                } else {
                    $value_id = $db->nextId('product_attribute_value');
                    $db->insert('product_attribute_value', [
                        'id' => $value_id,
                        'attribute_id' => $attribute_id,
                        'module_id' => $module_id,
                        'value' => $value,
                        'sort' => $vsort++
                    ]);
                }
                $keptValues[$value_id] = true;
            }
        }

        $removedAttributes = array_keys(array_diff_key($attributeIds, $keptAttributes));
        if (!empty($removedAttributes)) {
            $db->delete('product_attribute', [['id', $removedAttributes], ['module_id', $module_id]], 0);
        }
        $removedValues = array_keys(array_diff_key($valueOwner, $keptValues));
        if (!empty($removedValues)) {
            $db->delete('product_attribute_value', [['id', $removedValues], ['module_id', $module_id]], 0);
        }
    }

    /**
     * Resolve a list of attribute_value IDs into a readable label in current language
     * e.g. "Size: L / Color: Red"
     *
     * @param int   $module_id
     * @param array $valueIds  attribute_value_id list
     *
     * @return string
     */
    public static function buildVariantLabel($module_id, $valueIds)
    {
        if (empty($valueIds)) {
            return '';
        }
        $rows = static::createQuery()
            ->select('AV.id value_id', 'AV.value', 'A.name')
            ->from('product_attribute_value AV')
            ->join('product_attribute A', ['A.id', 'AV.attribute_id'])
            ->where([
                ['AV.module_id', $module_id],
                ['AV.id', $valueIds]
            ])
            ->cacheOn()
            ->fetchAll();

        $lng = Language::name();
        $parts = [];
        foreach ($rows as $row) {
            $name = json_decode($row->name, true);
            $value = json_decode($row->value, true);
            $parts[] = ($name[$lng] ?? reset($name)).': '.($value[$lng] ?? reset($value));
        }
        return implode(' / ', $parts);
    }

    /**
     * Build options list of attributes+values for the product editor UI
     *
     * @param int $module_id
     *
     * @return array
     */
    public static function toOptions($module_id)
    {
        return self::getAll($module_id);
    }

    /**
     * Decode a JSON {th,en} field into an associative array with all languages present
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

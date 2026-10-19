<?php
/**
 * @filesource widgets/stats/models/index.php
 *
 * Stats Widget — Data Model
 * Reads/writes stats items from datas/widgets/stats.json.
 *
 * JSON structure:
 * {
 *   "items": [
 *     { "id": 1, "name": "default", "order": 0, "icon": "icon-users",
 *       "label": "Customers", "value": 1500, "suffix": "+", "prefix": "",
 *       "duration": 2000, "format": "number", "separator": ",",
 *       "decimal": ".", "decimals": 0, "color": "", "description": "",
 *       "countMode": "up", "animation": "default", "delay": 0, "easing": "easeOutExpo" },
 *     ...
 *   ],
 *   "next_id": 2
 * }
 *
 * @copyright 2026 Goragod.com
 * @license https://www.kotchasan.com/license/
 */

namespace Widgets\Stats\Models;

/**
 * Stats Widget — Model
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Index extends \Kotchasan\Model
{
    /** Path to the JSON file relative to ROOT_PATH */
    const DATA_FILE = 'datas/widgets/stats.json';

    /**
     * Return the full filesystem path to the data file.
     *
     * @return string
     */
    public static function dataFile(): string
    {
        return ROOT_PATH.self::DATA_FILE;
    }

    /**
     * Load raw data from the JSON file.
     *
     * @return array  { items: [...], next_id: N }
     */
    public static function loadRaw(): array
    {
        $file = static::dataFile();
        if (!file_exists($file)) {
            return ['items' => [], 'next_id' => 1];
        }

        $content = file_get_contents($file);
        if ($content === false) {
            return ['items' => [], 'next_id' => 1];
        }

        $data = json_decode($content, true);
        if (!is_array($data)) {
            return ['items' => [], 'next_id' => 1];
        }

        // Migrate old format { setname: [...] } → new flat format
        if (!isset($data['items'])) {
            $items = [];
            $id = 1;
            $order = 0;
            foreach ($data as $name => $setItems) {
                if (!is_array($setItems)) {
                    continue;
                }
                foreach ($setItems as $item) {
                    $item['id'] = $id++;
                    $item['name'] = $name;
                    $item['order'] = $order++;
                    $items[] = $item;
                }
            }
            return ['items' => $items, 'next_id' => $id];
        }

        return $data;
    }

    /**
     * Get all stat items as a flat array (all sets).
     *
     * @return array
     */
    public static function getAll(): array
    {
        $data = static::loadRaw();
        return $data['items'] ?? [];
    }

    /**
     * Get stats items for a named set, sorted by order.
     *
     * @param string $name  Set name (default: 'default')
     *
     * @return array  Ordered list of stat item arrays.
     */
    public static function getItems(string $name = 'default'): array
    {
        $items = array_filter(static::getAll(), function ($item) use ($name) {
            return ($item['name'] ?? 'default') === $name;
        });
        usort($items, function ($a, $b) {
            return ($a['order'] ?? 0) <=> ($b['order'] ?? 0);
        });
        return array_values($items);
    }

    /**
     * Get a single stat item by ID.
     *
     * @param int $id
     *
     * @return array|null
     */
    public static function getById(int $id): ?array
    {
        foreach (static::getAll() as $item) {
            if ((int) ($item['id'] ?? 0) === $id) {
                return $item;
            }
        }
        return null;
    }

    /**
     * Get all unique set names.
     *
     * @return string[]
     */
    public static function getAllSetNames(): array
    {
        $names = [];
        foreach (static::getAll() as $item) {
            $n = $item['name'] ?? 'default';
            if (!in_array($n, $names, true)) {
                $names[] = $n;
            }
        }
        sort($names);
        return $names;
    }

    /**
     * Insert or update a stat item.
     *
     * @param array $item  Item data; id=0 means insert
     *
     * @return int  The saved item's ID
     */
    public static function saveItem(array $item): int
    {
        $data = static::loadRaw();
        $items = $data['items'];
        $nextId = (int) ($data['next_id'] ?? 1);

        $id = (int) ($item['id'] ?? 0);

        if ($id > 0) {
            // Update
            foreach ($items as $k => $existing) {
                if ((int) ($existing['id'] ?? 0) === $id) {
                    $item['id'] = $id;
                    // Preserve order unless explicitly set
                    if (!isset($item['order'])) {
                        $item['order'] = $existing['order'] ?? 0;
                    }
                    $items[$k] = $item;
                    break;
                }
            }
        } else {
            // Insert
            $id = $nextId;
            $item['id'] = $id;
            if (!isset($item['order'])) {
                $item['order'] = count($items);
            }
            $items[] = $item;
            $data['next_id'] = $nextId + 1;
        }

        $data['items'] = $items;
        static::persist($data);
        return $id;
    }

    /**
     * Delete stat items by IDs.
     *
     * @param int[] $ids
     *
     * @return int  Number of items deleted
     */
    public static function removeItems(array $ids): int
    {
        if (empty($ids)) {
            return 0;
        }

        $data = static::loadRaw();
        $before = count($data['items']);
        $data['items'] = array_values(array_filter($data['items'], function ($item) use ($ids) {
            return !in_array((int) ($item['id'] ?? 0), $ids, true);
        }));
        $removed = $before - count($data['items']);
        static::persist($data);
        return $removed;
    }

    /**
     * Reorder items given an array of [{ id, position }, ...].
     *
     * @param array $order  Array of { id: int, position: int }
     *
     * @return void
     */
    public static function reorder(array $order): void
    {
        $data = static::loadRaw();
        $map = [];
        foreach ($order as $entry) {
            $map[(int) ($entry['id'] ?? 0)] = (int) ($entry['position'] ?? 0);
        }

        foreach ($data['items'] as &$item) {
            $id = (int) ($item['id'] ?? 0);
            if (isset($map[$id])) {
                $item['order'] = $map[$id];
            }
        }
        unset($item);

        static::persist($data);
    }

    /**
     * Write data to disk.
     *
     * @param array $data
     *
     * @return bool
     */
    protected static function persist(array $data): bool
    {
        $file = static::dataFile();
        $dir = dirname($file);

        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        return file_put_contents($file, $json) !== false;
    }
}

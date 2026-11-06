<?php
/**
 * @filesource install/seeds/build.php
 *
 * สร้าง install/seeds/<type>/seed.sql และ images.json จาก <type>/content.php
 * แล้ว (ถ้ามี python3 + Pillow) สร้างไฟล์ตัวอย่างใน <type>/datas/ ด้วย
 * install/seeds/images.py
 *
 *   php install/seeds/build.php company            ประเภทเดียว
 *   php install/seeds/build.php company school     หลายประเภท
 *   php install/seeds/build.php --all              ทุกโฟลเดอร์ที่มี content.php
 *   php install/seeds/build.php --all --no-images  ไม่เรียก images.py (ขนาดไฟล์อ่านจาก datas/ ที่มีอยู่)
 *
 * ลำดับการทำงานของแต่ละประเภท
 *   1. อ่าน content.php แล้วสร้างแถวทั้งหมด (ยังไม่เขียนไฟล์)
 *   2. ตรวจทุกแถวกับสคีมาจริงของตัวติดตั้ง — schemaCommands() ชุดเดียวกับที่
 *      install/step5.php และ cli-fresh.php ใช้ (schemaFiles(): core.sql →
 *      database.sql → modules/<owner>/install/database.sql) คอลัมน์ที่ไม่มีอยู่,
 *      คอลัมน์ NOT NULL ที่ไม่มีค่าเริ่มต้นแต่ไม่ได้ใส่, ข้อความยาวเกิน varchar,
 *      ค่า enum ที่ไม่อยู่ในรายการ = หยุดทันที ไม่เขียนไฟล์ใด ๆ
 *   3. เขียน images.json (รายการไฟล์ที่ seed อ้างถึง พร้อมขนาดและชนิดของรูป)
 *   4. เรียก images.py สร้างไฟล์ใน datas/
 *   5. เขียน seed.sql — ขนาดไฟล์ดาวน์โหลด/เอกสารอ่านจากไฟล์จริงใน datas/
 *
 * กติกาของ seed.sql (ตาม install/seeds/README.md และ sqlCommands())
 *   - หนึ่งคำสั่งต่อหนึ่งบรรทัด ปิดด้วย ; — ขึ้นบรรทัดใหม่ในข้อความเขียนเป็น \n
 *   - ตารางเขียนเป็น `{prefix}_ชื่อ`
 *   - {SITE_NAME} {SITE_EMAIL} คงไว้เป็น token ให้ตัวติดตั้งแทนค่า,
 *     {WEBURL} ระบบแทนเองตอนแสดงผล
 *   - วันที่เป็นนิพจน์สัมพันธ์กับวันติดตั้ง (NOW() - INTERVAL 3 DAY ...)
 *   - ไม่มีแถวใน {prefix}_user — ทุกอย่างที่ต้องมีเจ้าของใช้ member_id = 1
 *     (ผู้ดูแลสูงสุดที่ตัวติดตั้งสร้าง)
 *   - ค่า config ของโมดูลมาจาก defaultSettings() ของโมดูลนั้นจริง
 *     (Index\Page\Model::defaultConfig() ตัวเดียวกับที่หน้าผู้ดูแลใช้) แล้วทับ
 *     เฉพาะคีย์ที่ content.php ระบุ
 *
 * @copyright 2026 Goragod.com
 * @license https://www.kotchasan.com/license/
 */

if (PHP_SAPI !== 'cli') {
    exit('CLI only');
}

define('ROOT_PATH', str_replace('\\', '/', dirname(__DIR__, 2)).'/');
define('SEED_ROOT', str_replace('\\', '/', __DIR__).'/');

// schemaCommands() / sqlCommands() — ตัวอ่านสคีมาตัวเดียวกับตัวติดตั้ง
include_once ROOT_PATH.'install/common.php';

// โหลดคลาสเท่าที่จำเป็นเพื่อเรียก defaultSettings() ของแต่ละโมดูล
// (ไม่ต้องบูตเว็บทั้งระบบ — โครงเดียวกับที่ Index\Page\Model::settingsClass() หา)
spl_autoload_register(function ($class) {
    foreach (['Kotchasan\\', 'Gcms\\', 'Web\\'] as $prefix) {
        if (strpos($class, $prefix) === 0) {
            $file = ROOT_PATH.str_replace('\\', '/', $class).'.php';
            if (is_file($file)) {
                require_once $file;
            }

            return;
        }
    }
    if (preg_match('/^([A-Z][a-z0-9]*)\\\\([A-Z][a-z0-9]*)\\\\(Controller|Model|View)$/', $class, $m)) {
        $dir = ['Controller' => 'controllers', 'Model' => 'models', 'View' => 'views'][$m[3]];
        $file = ROOT_PATH.'modules/'.strtolower($m[1]).'/'.$dir.'/'.strtolower($m[2]).'.php';
        if (is_file($file)) {
            require_once $file;
        }
    }
});

// =============================================================================
// สคีมา
// =============================================================================

/**
 * นิยามคอลัมน์ของทุกตารางที่ตัวติดตั้งสร้าง (นิยามตัวสุดท้ายของแต่ละตารางชนะ
 * เหมือนตัวติดตั้ง ที่ใส่ DROP TABLE IF EXISTS ก่อนทุก CREATE)
 *
 * @return array [table (ไม่มี prefix) => [column => meta]]
 */
function seedSchema()
{
    static $schema = null;
    if ($schema !== null) {
        return $schema;
    }
    $schema = [];
    foreach (schemaCommands('P') as $command) {
        if (!preg_match('/^\s*CREATE\s+TABLE\s+(?:IF\s+NOT\s+EXISTS\s+)?`P_([^`]+)`\s*\((.*)\)[^)]*$/is', $command, $match)) {
            continue;
        }
        $columns = [];
        foreach (preg_split('/\n/', $match[2]) as $line) {
            if (!preg_match('/^\s*`([^`]+)`\s+([a-z]+)(\((.*?)\))?(.*)$/i', trim($line), $col)) {
                continue;
            }
            $type = strtolower($col[2]);
            $rest = rtrim($col[5], ', ');
            $meta = [
                'type' => $type,
                'length' => null,
                'enum' => null,
                'nullable' => !preg_match('/\bNOT\s+NULL\b/i', $rest),
                'default' => (bool) preg_match('/\bDEFAULT\b/i', $rest),
                'auto' => (bool) preg_match('/\bAUTO_INCREMENT\b/i', $rest),
                'unsigned' => (bool) preg_match('/\bunsigned\b/i', $rest)
            ];
            if ($type === 'enum' || $type === 'set') {
                preg_match_all("/'((?:[^'\\\\]|\\\\.)*)'/", $col[4], $values);
                $meta['enum'] = $values[1];
            } elseif (in_array($type, ['varchar', 'char'], true)) {
                $meta['length'] = (int) $col[4];
            }
            $columns[$col[1]] = $meta;
        }
        $schema[$match[1]] = $columns;
    }

    return $schema;
}

/**
 * ตรวจแถวกับสคีมา — ผิดข้อไหนหยุดทันที ข้อความบอกตาราง คอลัมน์ และค่าที่ผิด
 *
 * @param string $table
 * @param array  $row
 *
 * @throws RuntimeException
 */
function checkRow($table, array $row)
{
    $schema = seedSchema();
    if (!isset($schema[$table])) {
        throw new RuntimeException("table `$table` is not created by the installer schema (install/*.sql, modules/*/install/database.sql)");
    }
    $columns = $schema[$table];
    foreach ($columns as $name => $meta) {
        if (!array_key_exists($name, $row) && !$meta['nullable'] && !$meta['default'] && !$meta['auto']) {
            throw new RuntimeException("`$table`.`$name` is NOT NULL without a default but the seed row has no value for it");
        }
    }
    foreach ($row as $name => $value) {
        if (!isset($columns[$name])) {
            throw new RuntimeException("`$table`.`$name` does not exist in the schema");
        }
        $meta = $columns[$name];
        if ($value === null) {
            if (!$meta['nullable']) {
                throw new RuntimeException("`$table`.`$name` is NOT NULL but the seed gives NULL");
            }
            continue;
        }
        if (is_object($value)) {
            // นิพจน์ SQL (NOW() ...) — ตรวจตอนนำเข้าจริงแทน
            continue;
        }
        if (is_array($value)) {
            $value = json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }
        if (in_array($meta['type'], ['tinyint', 'smallint', 'mediumint', 'int', 'bigint'], true)) {
            if (!is_int($value) && !is_bool($value)) {
                throw new RuntimeException("`$table`.`$name` is {$meta['type']} but the seed gives ".var_export($value, true));
            }
            if ($meta['unsigned'] && (int) $value < 0) {
                throw new RuntimeException("`$table`.`$name` is unsigned but the seed gives $value");
            }
            continue;
        }
        if ($meta['enum'] !== null) {
            if (!in_array((string) $value, $meta['enum'], true)) {
                throw new RuntimeException("`$table`.`$name` accepts ".implode('|', $meta['enum']).' but the seed gives '.var_export($value, true));
            }
            continue;
        }
        if ($meta['length'] !== null) {
            // token ถูกแทนด้วยชื่อเว็บ/อีเมลตอนติดตั้ง — กันที่ไว้ 60 ตัวอักษร
            $expanded = str_replace(['{SITE_NAME}', '{SITE_EMAIL}'], str_repeat('x', 60), (string) $value);
            if (mb_strlen($expanded) > $meta['length']) {
                throw new RuntimeException("`$table`.`$name` is {$meta['type']}({$meta['length']}) but the seed value is ".mb_strlen($expanded).' characters: '.mb_strimwidth((string) $value, 0, 80, '…'));
            }
        }
        if (in_array($meta['type'], ['date', 'datetime'], true) && !preg_match('/^\d{4}-\d{2}-\d{2}( \d{2}:\d{2}:\d{2})?$/', (string) $value)) {
            throw new RuntimeException("`$table`.`$name` is {$meta['type']} but the seed gives ".var_export($value, true));
        }
    }
}

// =============================================================================
// ตัวช่วยสร้าง SQL
// =============================================================================

/**
 * นิพจน์ SQL ที่ไม่ต้องใส่เครื่องหมายคำพูด (NOW(), CURDATE() ...)
 *
 * @param string $sql
 *
 * @return object
 */
function raw($sql)
{
    return (object) ['raw' => $sql];
}

/**
 * วัน-เวลา สัมพันธ์กับวันติดตั้ง
 *
 * @param int    $days  จำนวนวันย้อนหลัง (ติดลบ = อนาคต)
 * @param string $time  HH:MM (ว่าง = เวลาปัจจุบัน - $hours)
 * @param int    $hours ชั่วโมงที่ลบเพิ่มเมื่อไม่ระบุ $time (ให้แต่ละแถวเวลาไม่ซ้ำกัน)
 *
 * @return object
 */
function ago($days, $time = '', $hours = 0)
{
    if ($time !== '') {
        $base = $days >= 0 ? 'CURDATE() - INTERVAL '.$days.' DAY' : 'CURDATE() + INTERVAL '.(-$days).' DAY';

        return raw($base." + INTERVAL '".$time."' HOUR_MINUTE");
    }
    $sql = $days >= 0 ? 'NOW() - INTERVAL '.$days.' DAY' : 'NOW() + INTERVAL '.(-$days).' DAY';
    if ($hours > 0) {
        $sql .= ' - INTERVAL '.$hours.' HOUR';
    }

    return raw($sql);
}

/**
 * วันที่ (ไม่มีเวลา) สัมพันธ์กับวันติดตั้ง
 *
 * @param int $days จำนวนวันย้อนหลัง (ติดลบ = อนาคต)
 *
 * @return object
 */
function agoDate($days)
{
    if ($days == 0) {
        return raw('CURDATE()');
    }

    return raw($days > 0 ? 'CURDATE() - INTERVAL '.$days.' DAY' : 'CURDATE() + INTERVAL '.(-$days).' DAY');
}

/**
 * ค่า SQL หนึ่งค่า บนบรรทัดเดียวเสมอ
 *
 * @param mixed $value
 *
 * @return string
 */
function q($value)
{
    if ($value === null) {
        return 'NULL';
    }
    if (is_object($value) && isset($value->raw)) {
        return $value->raw;
    }
    if (is_bool($value)) {
        return $value ? '1' : '0';
    }
    if (is_int($value) || is_float($value)) {
        return (string) $value;
    }
    if (is_array($value)) {
        $value = json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    return "'".addcslashes((string) $value, "\\'\0\n\r\x1a")."'";
}

/**
 * INSERT หนึ่งแถว (ตรวจกับสคีมาก่อน)
 *
 * @param string $table ไม่มี prefix
 * @param array  $row
 *
 * @return string
 */
function insert($table, array $row)
{
    checkRow($table, $row);
    $cols = [];
    $vals = [];
    foreach ($row as $col => $value) {
        $cols[] = '`'.$col.'`';
        $vals[] = q($value);
    }

    return 'INSERT INTO `{prefix}_'.$table.'` ('.implode(', ', $cols).') VALUES ('.implode(', ', $vals).');';
}

/**
 * JSON ภาษา (th/en) ของตาราง category
 *
 * @param array|string $value
 *
 * @return string
 */
function lang($value)
{
    if (is_string($value)) {
        $value = ['th' => $value, 'en' => $value];
    }

    return json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}

/**
 * ชื่อเล่นของบทความแบบเดียวกับ Web\Gcms::aliasName() (ตัวที่หน้าเขียนบทความใช้)
 *
 * @param string $text
 * @param int    $len
 *
 * @return string
 */
function aliasName($text, $len = 64)
{
    $text = preg_replace(['/[\{\}\[\]\(\)]{1,}/isu', '/[_\:\$\@~,;\%\-\+\#\r\n\s\"\'<>\.\/\\\?&]{1,}/isu', '/^(_)?(.*?)(_)?$/'], ['', '_', '\\2'], strtolower(trim(strip_tags($text))));

    return mb_substr($text, 0, $len);
}

/**
 * ตัดข้อความ HTML เป็นคำอธิบายสั้น (ไม่เกิน $len ตัวอักษร)
 *
 * @param string $html
 * @param int    $len
 *
 * @return string
 */
function plain($html, $len = 149)
{
    $text = trim(preg_replace('/\s+/u', ' ', html_entity_decode(strip_tags(str_replace('<', ' <', $html)), ENT_QUOTES, 'UTF-8')));

    return mb_substr($text, 0, $len);
}

/**
 * `modules`.`config` ของโมดูลใหม่ — defaultSettings() ของโมดูลนั้น
 * ทับด้วยคีย์ที่ content.php กำหนด
 *
 * @param string $owner
 * @param array  $override
 *
 * @return string JSON
 */
function moduleConfig($owner, array $override)
{
    $config = json_decode(\Index\Page\Model::defaultConfig($owner), true);
    if (!is_array($config)) {
        $config = [];
    }
    foreach ($override as $key => $value) {
        $config[$key] = $value;
    }

    return empty($config) ? '{}' : json_encode($config, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}

// =============================================================================
// ตัวสร้าง
// =============================================================================

/**
 * ข้อมูลตัวอย่างของหนึ่งประเภท
 */
class SeedBuilder
{
    /** @var string */
    public $type;
    /** @var string โฟลเดอร์ของประเภท (ลงท้าย /) */
    public $dir;
    /** @var array content.php */
    public $content;
    /** @var array ตาราง => [INSERT ...] */
    public $sections = [];
    /** @var array รายการไฟล์ที่ต้องมีใน datas/ */
    public $images = [];
    /** @var array ตัวนับ id ของแต่ละตาราง */
    private $ids = [];
    /** @var array ชื่อโมดูล => id ของแถว index ที่เป็นหน้าโมดูล */
    private $moduleIndex = [];
    /** @var array ชื่อโมดูล => module_id */
    private $moduleIds = [];
    /** @var array tag => จำนวนบทความ */
    private $tags = [];

    /**
     * @param string $type
     */
    public function __construct($type)
    {
        $this->type = $type;
        $this->dir = SEED_ROOT.$type.'/';
        $this->content = include $this->dir.'content.php';
        if (!is_array($this->content) || empty($this->content['modules'])) {
            throw new RuntimeException($type.'/content.php must return an array with a "modules" key');
        }
    }

    /**
     * @param string $table
     *
     * @return int id ถัดไปของตาราง
     */
    private function nextId($table)
    {
        $this->ids[$table] = ($this->ids[$table] ?? 0) + 1;

        return $this->ids[$table];
    }

    /**
     * @param string $table
     * @param array  $row
     */
    private function add($table, array $row)
    {
        $this->sections[$table][] = insert($table, $row);
    }

    /**
     * ไฟล์ที่ images.py ต้องสร้าง
     *
     * @param string $file  ที่อยู่ใน datas/ เช่น document/picture-2-5.webp
     * @param string $kind  article | category | photo | avatar | video | slide | banner | imagemenu | portfolio | product | pdf | docx | zip
     * @param array  $extra caption, label, size [w, h], seed, lines ...
     */
    private function asset($file, $kind, array $extra = [])
    {
        foreach ($this->images as $item) {
            if ($item['file'] === $file) {
                return;
            }
        }
        $this->images[] = ['file' => $file, 'kind' => $kind] + $extra + ['seed' => crc32($this->type.'/'.$file)];
    }

    /**
     * ขนาดของไฟล์ใน datas/ (0 ถ้ายังไม่ได้สร้าง)
     *
     * @param string $file
     *
     * @return int
     */
    public function fileSize($file)
    {
        $path = $this->dir.'datas/'.$file;

        return is_file($path) ? (int) filesize($path) : 0;
    }

    /**
     * สร้างแถวทั้งหมดจาก content.php
     */
    public function build()
    {
        foreach ($this->content['modules'] as $name => $module) {
            $this->module($name, $module);
        }
        foreach ($this->content['modules'] as $name => $module) {
            $moduleId = $this->moduleIds[$name];
            $owner = $module['owner'];
            if ($owner === 'document') {
                $this->articles($moduleId, $name, $module);
            } elseif ($owner === 'board') {
                $this->board($moduleId, $module);
            } elseif ($owner === 'personnel') {
                $this->personnel($moduleId, $module);
            } elseif ($owner === 'gallery') {
                $this->gallery($moduleId, $module);
            } elseif ($owner === 'download') {
                $this->downloads($moduleId, $module);
            } elseif ($owner === 'edocument') {
                $this->edocuments($moduleId, $module);
            } elseif ($owner === 'event') {
                $this->events($moduleId, $module);
            } elseif ($owner === 'video') {
                $this->videos($moduleId, $module);
            } elseif ($owner === 'portfolio') {
                $this->portfolio($moduleId, $module);
            } elseif ($owner === 'product') {
                $this->products($moduleId, $name, $module);
            }
        }
        $this->tagRows();
        $this->textlinks();
        $this->menus();
        $this->counter();
    }

    /**
     * modules + หน้าโมดูล (index = 1) + หมวดหมู่
     *
     * @param string $name
     * @param array  $module
     */
    private function module($name, array $module)
    {
        $owner = $module['owner'];
        if (!is_dir(ROOT_PATH.'modules/'.$owner)) {
            throw new RuntimeException("module \"$name\": owner \"$owner\" has no modules/$owner folder");
        }
        $moduleId = $this->nextId('modules');
        $indexId = $this->nextId('index');
        $this->moduleIds[$name] = $moduleId;
        $this->moduleIndex[$name] = $indexId;

        $override = $module['config'] ?? [];
        // รูปเริ่มต้นของโมดูล (บทความที่ไม่มีรูป / หมวดที่ไม่มีไอคอน)
        if (!empty($module['default_icon'])) {
            $file = ($owner === 'board' ? 'board/' : 'document/').'default-'.$moduleId.'.webp';
            $override['default_icon'] = 'datas/'.$file;
            $this->asset($file, 'article', ['caption' => $module['topic'], 'label' => $module['default_icon'], 'size' => [800, 450]]);
        }
        $this->add('modules', [
            'id' => $moduleId,
            'owner' => $owner,
            'module' => $name,
            'config' => moduleConfig($owner, $override)
        ]);

        // หน้าของโมดูล — แถวเดียวกับที่หน้าผู้ดูแล › หน้าเพจ สร้าง
        // ย้อนวันที่ไว้ 30 วัน: Index\Index\Model::get() ต้องการ published_date <= วันนี้
        // ของ PHP ซึ่งอาจอยู่คนละโซนเวลากับฐานข้อมูล
        $this->add('index', [
            'id' => $indexId,
            'index' => 1,
            'module_id' => $moduleId,
            'category_id' => 0,
            'language' => '',
            'member_id' => 1,
            'visited' => (int) ($module['visited'] ?? 0),
            'can_reply' => 0,
            'show_news' => '',
            'published' => 1,
            'published_date' => agoDate(30),
            'created_at' => ago(30),
            'updated_at' => ago(30)
        ]);
        $this->add('index_detail', [
            'id' => $indexId,
            'module_id' => $moduleId,
            'language' => '',
            'topic' => $module['topic'],
            'description' => $module['description'] ?? plain($module['detail'] ?? $module['topic']),
            'detail' => $module['detail'] ?? '',
            'keywords' => $module['keywords'] ?? $module['topic']
        ]);

        // หมวดหมู่ (document / board / download : category, personnel : department)
        $type = $owner === 'personnel' ? 'department' : 'category';
        foreach ($module['categories'] ?? [] as $categoryId => $category) {
            $rowId = $this->nextId('category');
            $topic = isset($category['th']) ? ['th' => $category['th'], 'en' => $category['en'] ?? $category['th']] : $category;
            $detail = $category['detail'] ?? [];
            $icon = [];
            if (!empty($category['icon'])) {
                // ชื่อไฟล์แบบเดียวกับหน้าจัดการหมวดหมู่ ({owner}/cat_{lng}_{id})
                $folder = $owner === 'board' ? 'board' : 'document';
                $file = $folder.'/cat_th_'.$rowId.'.webp';
                $icon = ['th' => 'datas/'.$file];
                $this->asset($file, 'category', ['caption' => $topic['th'], 'label' => $category['icon'], 'size' => [480, 320]]);
            }
            $this->add('category', [
                'id' => $rowId,
                'module_id' => $moduleId,
                'category_id' => (int) $categoryId,
                'config' => '{}',
                'topic' => lang($topic),
                'detail' => json_encode(is_array($detail) ? $detail : ['th' => $detail], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'icon' => empty($icon) ? '{}' : json_encode($icon, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'published' => '1',
                'type' => $type,
                'language' => ''
            ]);
        }
    }

    /**
     * บทความของโมดูล document + tag + ความคิดเห็น
     *
     * @param int    $moduleId
     * @param string $name
     * @param array  $module
     */
    private function articles($moduleId, $name, array $module)
    {
        $categories = $module['categories'] ?? [];
        foreach ($module['articles'] ?? [] as $n => $article) {
            $indexId = $this->nextId('index');
            $daysAgo = max(1, (int) ($article['days_ago'] ?? 1));
            $hours = ($n * 5) % 9;
            $picture = null;
            if (!empty($article['picture'])) {
                // ชื่อไฟล์แบบเดียวกับหน้าเขียนบทความ (picture-{module_id}-{id})
                $picture = 'picture-'.$moduleId.'-'.$indexId.'.webp';
                $category = $categories[$article['category'] ?? 0] ?? null;
                $label = is_array($category) ? ($category['th'] ?? '') : (string) $category;
                $this->asset('document/'.$picture, 'article', [
                    'caption' => $article['topic'],
                    'label' => $article['label'] ?? ($label !== '' ? $label : $module['topic']),
                    'size' => [800, 450],
                    'art' => $article['art'] ?? ''
                ]);
            }
            // ความคิดเห็น
            $comments = [];
            foreach ($article['comments'] ?? [] as $i => $comment) {
                $commentId = $this->nextId('comment');
                $cDays = max(0, min($daysAgo - 1, (int) ($comment['days_ago'] ?? ($daysAgo - 1 - $i))));
                $comments[] = ['id' => $commentId, 'sender' => $comment['sender'], 'days' => $cDays, 'hours' => $i + 1];
                $this->add('comment', [
                    'id' => $commentId,
                    'module_id' => $moduleId,
                    'index_id' => $indexId,
                    'detail' => $comment['detail'],
                    'sender' => $comment['sender'],
                    'member_id' => 0,
                    'email' => '',
                    'ip' => '',
                    'created_at' => ago($cDays, '', $i + 1),
                    'updated_at' => ago($cDays, '', $i + 1)
                ]);
            }
            $last = empty($comments) ? null : end($comments);
            $this->add('index', [
                'id' => $indexId,
                'index' => 0,
                'module_id' => $moduleId,
                'category_id' => (int) ($article['category'] ?? 0),
                'language' => '',
                'sender' => '',
                'member_id' => 1,
                'email' => '',
                'ip' => '',
                'visited' => (int) ($article['visited'] ?? 0),
                'visited_today' => 0,
                'comments' => count($comments),
                'comment_id' => $last ? $last['id'] : 0,
                'commentator' => $last ? $last['sender'] : '',
                'commentator_id' => 0,
                'comment_date' => $last ? ago($last['days'], '', $last['hours']) : null,
                'picture' => $picture,
                'can_reply' => empty($article['can_reply']) ? 0 : 1,
                'show_news' => '1',
                'published' => 1,
                'published_date' => agoDate($daysAgo),
                'alias' => aliasName($article['alias'] ?? $article['topic']),
                'created_at' => ago($daysAgo, '', $hours),
                'updated_at' => ago($daysAgo, '', $hours)
            ]);
            $this->add('index_detail', [
                'id' => $indexId,
                'module_id' => $moduleId,
                'language' => '',
                'topic' => $article['topic'],
                'description' => $article['description'] ?? plain($article['detail']),
                'detail' => $article['detail'],
                'keywords' => mb_substr($article['keywords'] ?? implode(',', $article['tags'] ?? [$article['topic']]), 0, 149)
            ]);
            foreach ($article['tags'] ?? [] as $tag) {
                $this->add('index_tag', ['index_id' => $indexId, 'tag' => $tag]);
                $this->tags[$tag] = ($this->tags[$tag] ?? 0) + 1;
            }
        }
    }

    /**
     * กระทู้และคำตอบ (member_id = 1 ทั้งหมด เพราะ seed สร้างสมาชิกไม่ได้)
     *
     * @param int   $moduleId
     * @param array $module
     */
    private function board($moduleId, array $module)
    {
        foreach ($module['topics'] ?? [] as $topic) {
            $questionId = $this->nextId('board_q');
            $daysAgo = max(1, (int) ($topic['days_ago'] ?? 1));
            $replies = [];
            foreach ($topic['replies'] ?? [] as $i => $reply) {
                $replyId = $this->nextId('board_r');
                $rDays = max(0, $daysAgo - 1 - $i);
                $replies[] = ['id' => $replyId, 'days' => $rDays, 'hours' => $i + 1];
                $this->add('board_r', [
                    'id' => $replyId,
                    'module_id' => $moduleId,
                    'index_id' => $questionId,
                    'detail' => $reply['detail'],
                    'sender' => $reply['sender'] ?? '',
                    'member_id' => 1,
                    'email' => '',
                    'ip' => '',
                    'picture' => null,
                    'updated_at' => ago($rDays, '', $i + 1)
                ]);
            }
            $last = empty($replies) ? null : end($replies);
            $updated = $last ? ago($last['days'], '', $last['hours']) : ago($daysAgo, '', 2);
            $this->add('board_q', [
                'id' => $questionId,
                'module_id' => $moduleId,
                'category_id' => (int) ($topic['category'] ?? 0),
                'sender' => $topic['sender'] ?? '',
                'member_id' => 1,
                'email' => '',
                'ip' => '',
                'visited' => (int) ($topic['visited'] ?? 0),
                'comments' => count($replies),
                'comment_id' => $last ? $last['id'] : 0,
                'commentator' => $last ? ($topic['replies'][count($replies) - 1]['sender'] ?? '') : '',
                'commentator_id' => $last ? 1 : 0,
                'comment_date' => $last ? ago($last['days'], '', $last['hours']) : null,
                'picture' => null,
                'can_reply' => 1,
                'published' => 1,
                'pin' => empty($topic['pin']) ? 0 : 1,
                'locked' => empty($topic['locked']) ? 0 : 1,
                'topic' => $topic['topic'],
                'detail' => $topic['detail'],
                'created_at' => ago($daysAgo, '', 2),
                // รายการกระทู้เรียงตาม updated_at — กระทู้ที่มีคำตอบใหม่ขึ้นก่อน
                'updated_at' => $updated
            ]);
        }
    }

    /**
     * บุคลากร (รูปใน datas/personnel/{picture})
     *
     * @param int   $moduleId
     * @param array $module
     */
    private function personnel($moduleId, array $module)
    {
        $order = 0;
        foreach ($module['people'] ?? [] as $person) {
            $personId = $this->nextId('personnel');
            ++$order;
            $picture = null;
            if (!isset($person['picture']) || $person['picture']) {
                $picture = sprintf('person-%02d.webp', $personId);
                $this->asset('personnel/'.$picture, 'avatar', [
                    'caption' => $person['name'],
                    'size' => [320, 400],
                    'style' => $person['style'] ?? ''
                ]);
            }
            $this->add('personnel', [
                'id' => $personId,
                'module_id' => $moduleId,
                'category_id' => (int) $person['department'],
                'name' => $person['name'],
                'position' => $person['position'] ?? '',
                'detail' => $person['detail'] ?? '',
                'address' => null,
                'phone' => $person['phone'] ?? '',
                'email' => $person['email'] ?? '',
                'picture' => $picture,
                'order' => $order,
                'department' => (int) $person['department'],
                'level' => (int) ($person['level'] ?? 1),
                'published' => 1,
                'created_at' => ago(30),
                'updated_at' => ago(30)
            ]);
        }
    }

    /**
     * อัลบั้มและรูป (datas/gallery/{album_id}/{image}; count = 0 คือปก)
     *
     * @param int   $moduleId
     * @param array $module
     */
    private function gallery($moduleId, array $module)
    {
        foreach ($module['albums'] ?? [] as $album) {
            $albumId = $this->nextId('gallery_album');
            $daysAgo = max(1, (int) ($album['days_ago'] ?? 1));
            $captions = $album['captions'] ?? [];
            $count = (int) ($album['images'] ?? count($captions));
            $this->add('gallery_album', [
                'id' => $albumId,
                'module_id' => $moduleId,
                'topic' => $album['topic'],
                'detail' => $album['detail'] ?? '',
                'last_update' => raw('UNIX_TIMESTAMP(NOW() - INTERVAL '.$daysAgo.' DAY)'),
                'count' => $count,
                'visited' => (int) ($album['visited'] ?? 0),
                'member_id' => 1,
                'published_date' => agoDate($daysAgo),
                'updated_at' => ago($daysAgo)
            ]);
            for ($i = 0; $i < $count; ++$i) {
                $image = sprintf('album%d-%02d.webp', $albumId, $i + 1);
                $this->asset('gallery/'.$albumId.'/'.$image, 'photo', [
                    'caption' => $captions[$i] ?? $album['topic'],
                    'label' => $album['label'] ?? $album['topic'],
                    'size' => [800, 533],
                    'scene' => $album['scene'] ?? 'landscape'
                ]);
                $this->add('gallery_image', [
                    'id' => $this->nextId('gallery_image'),
                    'module_id' => $moduleId,
                    'album_id' => $albumId,
                    'image' => $image,
                    'updated_at' => ago($daysAgo),
                    'count' => $i
                ]);
            }
        }
    }

    /**
     * ไฟล์ดาวน์โหลด (file = ที่อยู่เทียบกับ datas/ เช่น download/xxx.pdf)
     *
     * @param int   $moduleId
     * @param array $module
     */
    private function downloads($moduleId, array $module)
    {
        foreach ($module['files'] ?? [] as $file) {
            $daysAgo = max(1, (int) ($file['days_ago'] ?? 1));
            $path = 'download/'.$file['file'];
            $ext = strtolower(pathinfo($file['file'], PATHINFO_EXTENSION));
            $this->asset($path, $ext, ['caption' => $file['title'] ?? $file['name'], 'lines' => $file['lines'] ?? [], 'label' => $module['topic']]);
            $this->add('download', [
                'id' => $this->nextId('download'),
                'module_id' => $moduleId,
                'category_id' => isset($file['category']) ? (int) $file['category'] : 0,
                'member_id' => 1,
                'detail' => $file['detail'] ?? '',
                'created_at' => ago($daysAgo),
                'updated_at' => ago($daysAgo),
                'name' => $file['name'],
                'ext' => $ext,
                'size' => $this->fileSize($path),
                'file' => $path,
                'downloads' => (int) ($file['downloads'] ?? 0),
                'reciever' => json_encode($file['reciever'] ?? [-1, 0, 1])
            ]);
        }
    }

    /**
     * เอกสารอิเล็กทรอนิกส์ (file = ชื่อไฟล์ใน datas/edocument/ ไม่เกิน 20 ตัวอักษร)
     *
     * @param int   $moduleId
     * @param array $module
     */
    private function edocuments($moduleId, array $module)
    {
        foreach ($module['documents'] ?? [] as $doc) {
            $daysAgo = max(1, (int) ($doc['days_ago'] ?? 1));
            $ext = strtolower(pathinfo($doc['file'], PATHINFO_EXTENSION));
            $path = 'edocument/'.$doc['file'];
            $this->asset($path, $ext, ['caption' => $doc['topic'], 'lines' => $doc['lines'] ?? [], 'label' => $doc['document_no']]);
            $this->add('edocument', [
                'id' => $this->nextId('edocument'),
                'module_id' => $moduleId,
                'sender_id' => 1,
                'reciever' => json_encode($doc['reciever'] ?? [-1, 0, 1]),
                'last_update' => raw('UNIX_TIMESTAMP(NOW() - INTERVAL '.$daysAgo.' DAY)'),
                'downloads' => (int) ($doc['downloads'] ?? 0),
                'document_no' => $doc['document_no'],
                'detail' => $doc['detail'] ?? '',
                'topic' => $doc['topic'],
                'ext' => $ext,
                'size' => $this->fileSize($path),
                'file' => $doc['file'],
                'ip' => ''
            ]);
        }
    }

    /**
     * กิจกรรมในปฏิทิน
     *
     * วันที่ระบุได้สองแบบ
     *   'days' => n              n วันนับจากวันติดตั้ง (ติดลบ = ผ่านมาแล้ว)
     *   'month' => 0|1, 'day' => d   วันที่ d+1 ของเดือนนี้ / เดือนหน้า
     *                                 (ให้ปฏิทินเดือนนี้และเดือนหน้ามีกิจกรรมเสมอ)
     *
     * @param int   $moduleId
     * @param array $module
     */
    private function events($moduleId, array $module)
    {
        foreach ($module['events'] ?? [] as $event) {
            if (isset($event['month'])) {
                $base = $event['month'] > 0
                    ? "DATE_FORMAT(CURDATE() + INTERVAL {$event['month']} MONTH, '%Y-%m-01')"
                    : "DATE_FORMAT(CURDATE(), '%Y-%m-01')";
                $day = $base.' + INTERVAL '.(int) $event['day'].' DAY';
            } else {
                $days = (int) ($event['days'] ?? 0);
                $day = $days >= 0 ? 'CURDATE() + INTERVAL '.$days.' DAY' : 'CURDATE() - INTERVAL '.(-$days).' DAY';
            }
            $begin = raw('('.$day.") + INTERVAL '".($event['time'] ?? '08:30')."' HOUR_MINUTE");
            $end = null;
            if (isset($event['end_time']) || isset($event['length'])) {
                $end = raw('('.$day.') + INTERVAL '.(int) ($event['length'] ?? 0)." DAY + INTERVAL '".($event['end_time'] ?? '16:30')."' HOUR_MINUTE");
            }
            $this->add('eventcalendar', [
                'id' => $this->nextId('eventcalendar'),
                'module_id' => $moduleId,
                'topic' => $event['topic'],
                'detail' => $event['detail'] ?? '',
                'description' => $event['description'] ?? plain($event['detail'] ?? $event['topic']),
                'keywords' => mb_substr($event['keywords'] ?? $event['topic'], 0, 149),
                'member_id' => 1,
                'end_date' => $end,
                'begin_date' => $begin,
                'color' => $event['color'] ?? '#2563EB',
                'published' => 1,
                'published_date' => agoDate(1),
                'created_at' => ago(7),
                'updated_at' => ago(7),
                'last_update' => raw('UNIX_TIMESTAMP(NOW() - INTERVAL 7 DAY)')
            ]);
        }
    }

    /**
     * วิดีโอ YouTube (รูปย่อ datas/video/{youtube}.jpg ที่หน้าเพิ่มวิดีโอดาวน์โหลดมาเก็บ)
     *
     * @param int   $moduleId
     * @param array $module
     */
    private function videos($moduleId, array $module)
    {
        foreach ($module['videos'] ?? [] as $n => $video) {
            $this->asset('video/'.$video['youtube'].'.jpg', 'video', [
                'caption' => $video['topic'],
                'label' => $video['label'] ?? 'VIDEO',
                'size' => [480, 360]
            ]);
            $daysAgo = (int) ($video['days_ago'] ?? ($n * 6 + 2));
            $this->add('video', [
                'id' => $this->nextId('video'),
                'module_id' => $moduleId,
                'youtube' => $video['youtube'],
                'topic' => $video['topic'],
                'description' => $video['description'] ?? '',
                'views' => (int) ($video['views'] ?? 0),
                'last_update' => raw('UNIX_TIMESTAMP(NOW() - INTERVAL '.$daysAgo.' DAY)')
            ]);
        }
    }

    /**
     * ผลงาน (รูป datas/portfolio/{id}.webp แบบเดียวกับหน้าเพิ่มผลงาน)
     *
     * @param int   $moduleId
     * @param array $module
     */
    private function portfolio($moduleId, array $module)
    {
        foreach ($module['items'] ?? [] as $n => $item) {
            $id = $this->nextId('portfolio');
            $image = $id.'.webp';
            $this->asset('portfolio/'.$image, 'portfolio', [
                'caption' => $item['title'],
                'label' => $item['label'] ?? '',
                'size' => [800, 600]
            ]);
            $daysAgo = (int) ($item['days_ago'] ?? ($n * 20 + 10));
            $this->add('portfolio', [
                'id' => $id,
                'module_id' => $moduleId,
                'title' => $item['title'],
                'keywords' => implode(',', $item['keywords'] ?? []),
                'detail' => $item['detail'],
                'created_at' => raw('UNIX_TIMESTAMP(NOW() - INTERVAL '.$daysAgo.' DAY)'),
                'image' => $image,
                'url' => $item['url'] ?? '',
                'published' => '1',
                'visited' => (int) ($item['visited'] ?? 0)
            ]);
        }
    }

    /**
     * สินค้า — ตารางของ modules/product/install/database.sql
     *
     *   attributes : ['size' => ['name' => [th, en], 'values' => ['s' => [th, en], ...]], ...]
     *   shipping   : [['name' => [th, en], 'calc_type' => flat|by_weight|free, 'base_rate', 'rate_per_kg', 'free_over'], ...]
     *   products   : [[
     *       'sku', 'alias', 'category', 'featured', 'price', 'weight', 'manage_stock', 'days_ago', 'visited',
     *       'th' => [topic, description, detail, keywords], 'en' => [...],
     *       'images' => [['shape' => mug|shirt|..., 'color' => '#hex', 'label' => ...], ...],
     *       'stock' => n, 'cost' => ต้นทุนต่อหน่วย                           (สินค้าแบบ simple)
     *       'variants' => [['values' => ['size' => 's', 'color' => 'white'], 'price', 'sale_price', 'stock', 'image' => ลำดับรูป], ...]
     *   ]]
     *
     * สินค้าแบบ simple มี variant แฝงหนึ่งรายการ (price = base_price) เหมือนที่หน้าเพิ่มสินค้าสร้าง
     * สต็อกมาจาก lot (product_stock_lot + product_stock_movement ชนิด in/receipt)
     * ส่วน stock_qty ของ product / product_variant เป็นค่าแคชที่คำนวณให้ตรงกับ lot
     * สินค้าที่ manage_stock = 0 ไม่ต้องมี lot (ขายได้เสมอ)
     *
     * @param int    $moduleId
     * @param string $name
     * @param array  $module
     */
    private function products($moduleId, $name, array $module)
    {
        // คุณสมบัติสินค้า (ขนาด สี ...) — ใช้ร่วมกันทั้งโมดูล
        $attributes = [];
        $sort = 0;
        foreach ($module['attributes'] ?? [] as $key => $attribute) {
            $attributeId = $this->nextId('product_attribute');
            $attributes[$key] = ['id' => $attributeId, 'values' => []];
            $this->add('product_attribute', [
                'id' => $attributeId,
                'module_id' => $moduleId,
                'name' => lang($attribute['name']),
                'sort' => $sort++
            ]);
            $vsort = 0;
            foreach ($attribute['values'] as $valueKey => $value) {
                $valueId = $this->nextId('product_attribute_value');
                $attributes[$key]['values'][$valueKey] = $valueId;
                $this->add('product_attribute_value', [
                    'id' => $valueId,
                    'attribute_id' => $attributeId,
                    'module_id' => $moduleId,
                    'value' => lang($value),
                    'sort' => $vsort++
                ]);
            }
        }
        // วิธีจัดส่ง
        foreach ($module['shipping'] ?? [] as $i => $method) {
            $this->add('product_shipping_method', [
                'id' => $this->nextId('product_shipping_method'),
                'module_id' => $moduleId,
                'name' => lang($method['name']),
                'calc_type' => $method['calc_type'] ?? 'flat',
                'base_rate' => (float) ($method['base_rate'] ?? 0),
                'rate_per_kg' => (float) ($method['rate_per_kg'] ?? 0),
                'free_over' => isset($method['free_over']) ? (float) $method['free_over'] : null,
                'published' => 1,
                'sort' => $i
            ]);
        }
        $categories = $module['categories'] ?? [];
        foreach ($module['products'] ?? [] as $n => $product) {
            $productId = $this->nextId('product');
            $daysAgo = max(1, (int) ($product['days_ago'] ?? ($n + 1)));
            $manage = !isset($product['manage_stock']) || $product['manage_stock'] ? 1 : 0;
            $category = $categories[$product['category'] ?? 0] ?? [];
            // รูป — datas/product/{product_id}/{image_id}.webp แบบเดียวกับหน้าเพิ่มสินค้า
            $imageIds = [];
            foreach ($product['images'] ?? [] as $i => $image) {
                $imageId = $this->nextId('product_image');
                $imageIds[$i] = $imageId;
                $filename = $imageId.'.webp';
                $this->asset('product/'.$productId.'/'.$filename, 'product', [
                    'caption' => $image['caption'] ?? $product['th']['topic'],
                    'label' => $image['label'] ?? ($i === 0 && !empty($product['featured']) ? ($category['th'] ?? '') : ''),
                    'shape' => $image['shape'] ?? 'box',
                    'color' => $image['color'] ?? '',
                    'size' => [800, 800]
                ]);
                $this->add('product_image', [
                    'id' => $imageId,
                    'product_id' => $productId,
                    'module_id' => $moduleId,
                    'filename' => $filename,
                    'sort' => $i,
                    'is_primary' => $i === 0 ? 1 : 0
                ]);
            }
            // variants
            $variable = !empty($product['variants']);
            $variants = $variable ? $product['variants'] : [[
                'values' => [],
                'price' => $product['price'],
                'sale_price' => null,
                'stock' => $product['stock'] ?? 0,
                'sku' => $product['sku']
            ]];
            $rows = [];
            $prices = [];
            $stockTotal = 0;
            foreach ($variants as $i => $variant) {
                $variantId = $this->nextId('product_variant');
                $stock = $manage ? (int) ($variant['stock'] ?? 0) : 0;
                $stockTotal += $stock;
                $prices[] = (float) $variant['price'];
                $suffix = [];
                foreach ($variant['values'] as $attrKey => $valueKey) {
                    $suffix[] = strtoupper((string) $valueKey);
                }
                $rows[] = ['id' => $variantId, 'stock' => $stock, 'cost' => (float) ($variant['cost'] ?? $product['cost'] ?? round($variant['price'] * 0.55))];
                $this->add('product_variant', [
                    'id' => $variantId,
                    'product_id' => $productId,
                    'module_id' => $moduleId,
                    'sku' => $variant['sku'] ?? ($product['sku'].(empty($suffix) ? '' : '-'.implode('-', $suffix))),
                    'price' => (float) $variant['price'],
                    'sale_price' => isset($variant['sale_price']) ? (float) $variant['sale_price'] : null,
                    'stock_qty' => $stock,
                    'weight' => (float) ($variant['weight'] ?? $product['weight'] ?? 0),
                    'published' => 1,
                    'image_id' => isset($variant['image']) ? $imageIds[$variant['image']] : 0
                ]);
                foreach ($variant['values'] as $attrKey => $valueKey) {
                    if (!isset($attributes[$attrKey]['values'][$valueKey])) {
                        throw new RuntimeException('product '.$product['sku'].': unknown attribute value '.$attrKey.'='.$valueKey);
                    }
                    $this->add('product_variant_value', [
                        'variant_id' => $variantId,
                        'attribute_id' => $attributes[$attrKey]['id'],
                        'attribute_value_id' => $attributes[$attrKey]['values'][$valueKey],
                        'module_id' => $moduleId
                    ]);
                }
            }
            $this->add('product', [
                'id' => $productId,
                'module_id' => $moduleId,
                'category_id' => (int) ($product['category'] ?? 0),
                'sku' => $product['sku'],
                'alias' => aliasName($product['alias'] ?? $product['th']['topic']),
                'product_type' => $variable ? 'variable' : 'simple',
                'base_price' => $variable ? min($prices) : (float) $product['price'],
                'manage_stock' => $manage,
                'stock_qty' => $stockTotal,
                'weight' => (float) ($product['weight'] ?? 0),
                'featured' => empty($product['featured']) ? 0 : 1,
                'published' => 1,
                'visited' => (int) ($product['visited'] ?? 0),
                'created_at' => ago($daysAgo, '', $n % 7),
                'updated_at' => ago($daysAgo, '', $n % 7)
            ]);
            // รายละเอียด — th/en แยกแถว (ภาษาเดียว = แถว language '' ใช้กับทุกภาษา)
            $languages = isset($product['en']) ? ['th', 'en'] : [''];
            foreach ($languages as $lng) {
                $detail = $product[$lng === '' ? 'th' : $lng];
                $this->add('product_detail', [
                    'id' => $productId,
                    'module_id' => $moduleId,
                    'language' => $lng,
                    'topic' => $detail['topic'],
                    'keywords' => mb_substr($detail['keywords'] ?? $detail['topic'], 0, 255),
                    'description' => $detail['description'] ?? plain($detail['detail'] ?? $detail['topic']),
                    'detail' => $detail['detail'] ?? ''
                ]);
            }
            // lot สต็อกเข้า (รับสินค้า) ของแต่ละ variant
            foreach ($rows as $row) {
                if ($row['stock'] <= 0) {
                    continue;
                }
                $lotId = $this->nextId('product_stock_lot');
                $received = ago($daysAgo, '09:00');
                $this->add('product_stock_lot', [
                    'id' => $lotId,
                    'module_id' => $moduleId,
                    'product_id' => $productId,
                    'variant_id' => $row['id'],
                    'qty_in' => $row['stock'],
                    'qty_remaining' => $row['stock'],
                    'unit_cost' => $row['cost'],
                    'received_at' => $received,
                    'ref' => 'SEED-'.$lotId,
                    'note' => 'ยอดยกมา (ข้อมูลตัวอย่าง)'
                ]);
                $this->add('product_stock_movement', [
                    'id' => $this->nextId('product_stock_movement'),
                    'module_id' => $moduleId,
                    'product_id' => $productId,
                    'variant_id' => $row['id'],
                    'lot_id' => $lotId,
                    'type' => 'in',
                    'qty' => $row['stock'],
                    'unit_cost' => $row['cost'],
                    'ref_type' => 'receipt',
                    'ref_id' => $lotId,
                    'created_by' => 1,
                    'created_at' => $received
                ]);
            }
        }
    }

    /**
     * tags — count คือจำนวนคลิก (วิดเจ็ต tags ใช้ขนาดตัวอักษรตามค่านี้)
     */
    private function tagRows()
    {
        $clicks = $this->content['tag_clicks'] ?? [];
        foreach ($this->tags as $tag => $articles) {
            $this->add('tags', [
                'id' => $this->nextId('tags'),
                'tag' => $tag,
                'count' => (int) ($clicks[$tag] ?? ($articles * 9 + crc32($tag) % 23))
            ]);
        }
    }

    /**
     * Textlinks — กลุ่ม slideshow / banner / imagemenu ...
     * วิดเจ็ต textlinks อ่านรูปจาก DATA_FOLDER.'image/'.logo (widgets/textlinks/views/index.php)
     * logo จึงเป็นชื่อไฟล์ล้วน และไฟล์อยู่ที่ datas/image/textlink-{id}.webp
     */
    private function textlinks()
    {
        $orders = [];
        foreach ($this->content['textlinks'] ?? [] as $link) {
            $id = $this->nextId('textlink');
            $name = $link['name'];
            $orders[$name] = ($orders[$name] ?? 0) + 1;
            $type = $link['type'] ?? ($name === 'slideshow' ? 'slideshow' : ($name === 'banner' ? 'banner' : 'image'));
            $logo = null;
            $size = $link['size'] ?? [0, 0];
            if (in_array($type, ['slideshow', 'banner', 'image', 'hero'], true)) {
                $logo = 'textlink-'.$id.'.webp';
                $kind = $type === 'slideshow' ? 'slide' : ($type === 'image' ? 'imagemenu' : 'banner');
                $defaults = ['slide' => [1200, 450], 'banner' => [970, 250], 'imagemenu' => [400, 120]];
                $size = $link['size'] ?? $defaults[$kind];
                $this->asset('image/'.$logo, $kind, [
                    'caption' => $link['text'],
                    'subtitle' => $link['description'] ?? '',
                    'label' => $link['label'] ?? '',
                    'button' => $link['button'] ?? '',
                    'icon' => $link['icon'] ?? '',
                    'scene' => $link['scene'] ?? '',
                    'size' => $size
                ]);
            }
            $this->add('textlink', array_merge([
                'id' => $id,
                'name' => $name,
                // widget textlinks เก็บแค่ 2 ชนิด (text/image) — สไลด์หรือแบนเนอร์
                // เป็นวิธีแสดงผลที่ธีมกำหนดด้วย layout= ส่วน $type ข้างบนใช้แค่เลือกขนาดรูป
                'type' => $logo === null ? 'text' : 'image',
                'text' => $link['text'],
                'description' => $link['description'] ?? $link['text'],
                'url' => $link['url'] ?? '',
                'target' => $link['target'] ?? '',
                'logo' => $logo,
                'width' => (int) $size[0],
                'height' => (int) $size[1],
                'published' => 1,
                'link_order' => $orders[$name],
                'created_at' => ago(30)
            ], $this->textlinkPeriod()));
        }
    }

    /**
     * ช่วงเวลาแสดงของ textlink — แสดงตลอด (ไม่กำหนดวันเริ่ม/วันสิ้นสุด)
     *
     * สคีมาเก็บ publish_start / publish_end เป็น int (unix time) โดย 0 = ไม่กำหนดขอบเขต
     * (วิดเจ็ต textlinks รุ่นเดียวกับ gcms.in.th) ถ้าวันหนึ่งคอลัมน์กลายเป็น date
     * ก็ออกค่าให้ตรงชนิด: เริ่ม 30 วันก่อนติดตั้ง และ publish_end = NULL ถ้าเป็น NULL ได้
     *
     * @return array
     */
    private function textlinkPeriod()
    {
        $columns = seedSchema()['textlink'] ?? [];
        $row = [];
        if (isset($columns['publish_start'])) {
            $row['publish_start'] = $columns['publish_start']['type'] === 'date' ? agoDate(30) : 0;
        }
        if (isset($columns['publish_end'])) {
            if ($columns['publish_end']['type'] !== 'date') {
                $row['publish_end'] = 0;
            } elseif ($columns['publish_end']['nullable']) {
                $row['publish_end'] = null;
            } else {
                $row['publish_end'] = raw('CURDATE() + INTERVAL 10 YEAR');
            }
        }

        return $row;
    }

    /**
     * เมนู — ['0_MAINMENU' => [['text', 'module' | 'url', 'children' => [...]]]]
     * เมนูแรกของเมนูหลักคือหน้าแรกของเว็บ
     */
    private function menus()
    {
        foreach ($this->content['menus'] ?? [] as $parent => $items) {
            $order = 0;
            foreach ($items as $item) {
                $this->menu($parent, $item, 0, $order);
                foreach ($item['children'] ?? [] as $child) {
                    $this->menu($parent, $child, 1, $order);
                }
            }
        }
        $main = $this->content['menus']['0_MAINMENU'][0]['module'] ?? '';
        $first = array_key_first($this->content['modules']);
        if ($main !== $first) {
            throw new RuntimeException("the first main menu must open the first module \"$first\" (README contract)");
        }
    }

    /**
     * @param string $parent
     * @param array  $item
     * @param int    $level
     * @param int    $order
     */
    private function menu($parent, array $item, $level, &$order)
    {
        ++$order;
        $indexId = 0;
        if (!empty($item['module'])) {
            if (!isset($this->moduleIndex[$item['module']])) {
                throw new RuntimeException('menu "'.$item['text'].'" opens unknown module "'.$item['module'].'"');
            }
            $indexId = $this->moduleIndex[$item['module']];
        }
        $this->add('menus', [
            'id' => $this->nextId('menus'),
            'index_id' => $indexId,
            'level' => $level,
            'language' => '',
            'menu_text' => $item['text'],
            'menu_tooltip' => $item['tooltip'] ?? $item['text'],
            'accesskey' => '',
            'menu_order' => $order,
            'menu_url' => $item['url'] ?? '',
            'menu_target' => $item['target'] ?? '',
            'alias' => '',
            'published' => '1',
            'icon' => $item['icon'] ?? null,
            'parent' => $parent
        ]);
    }

    /**
     * สถิติผู้เยี่ยมชมย้อนหลัง ให้วิดเจ็ต counter มีตัวเลข
     */
    private function counter()
    {
        $days = (int) ($this->content['counter_days'] ?? 0);
        $base = (int) ($this->content['counter_base'] ?? 120);
        for ($d = $days; $d >= 1; --$d) {
            $seed = crc32($this->type.$d);
            $visitors = $base + ($seed % $base) - (int) ($base / 3) + ($d % 7 === 0 ? -(int) ($base / 4) : 0);
            $views = $visitors * (2 + $seed % 3) + $seed % 17;
            $this->add('counter', [
                'id' => $this->nextId('counter'),
                'counter' => $visitors,
                'visited' => $views,
                'pages_view' => $views,
                'time' => raw('UNIX_TIMESTAMP(CURDATE() - INTERVAL '.$d." DAY + INTERVAL '21:15' HOUR_MINUTE)"),
                'date' => agoDate($d)
            ]);
        }
    }

    /**
     * เขียน images.json
     */
    public function writeImages()
    {
        $manifest = [
            'type' => $this->type,
            'theme' => $this->content['theme'] ?? [],
            'images' => $this->images
        ];
        file_put_contents($this->dir.'images.json', json_encode($manifest, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT)."\n");
    }

    /**
     * datas/widgets/stats.json — ตัวเลขของ {WIDGET_STATS} (ชุดชื่อ default)
     *
     * วิดเจ็ตอ่านไฟล์นี้จาก ROOT_PATH.'datas/widgets/stats.json' (Widgets\Stats\Models\Index)
     * รูปแบบเดียวกับที่หน้าตั้งค่าวิดเจ็ตบันทึก: {"items": [...], "next_id": n}
     */
    public function writeStats()
    {
        if (empty($this->content['stats'])) {
            return;
        }
        $items = [];
        foreach ($this->content['stats'] as $i => $stat) {
            $items[] = [
                'id' => $i + 1,
                'name' => $stat['name'] ?? 'default',
                'icon' => $stat['icon'] ?? 'icon-star',
                'label' => $stat['label'],
                'value' => $stat['value'],
                'suffix' => $stat['suffix'] ?? '',
                'prefix' => $stat['prefix'] ?? '',
                'duration' => 2000,
                'format' => 'number',
                'separator' => ',',
                'decimal' => '.',
                'decimals' => (int) ($stat['decimals'] ?? 0),
                'countMode' => 'up',
                'animation' => 'default',
                'delay' => 0,
                'easing' => 'easeOutExpo',
                'color' => $stat['color'] ?? '',
                'description' => $stat['description'] ?? '',
                'order' => $i
            ];
        }
        $dir = $this->dir.'datas/widgets/';
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        file_put_contents($dir.'stats.json', json_encode(['items' => $items, 'next_id' => count($items) + 1], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT)."\n");
    }

    /**
     * เขียน seed.sql
     */
    public function writeSql()
    {
        $out = [];
        $out[] = '-- Generated by install/seeds/build.php from '.$this->type.'/content.php — do not edit by hand.';
        $out[] = '-- Imported by the installer (install/seeds.php importSiteSeed()) after install/database.sql.';
        $out[] = '-- Tokens: {prefix} {SITE_NAME} {SITE_EMAIL} (installer), {WEBURL} (replaced when a page is shown).';
        $order = ['modules', 'index', 'index_detail', 'category', 'index_tag', 'tags', 'comment', 'personnel', 'gallery_album', 'gallery_image',
            'download', 'edocument', 'eventcalendar', 'video', 'portfolio', 'board_q', 'board_r', 'textlink', 'menus', 'counter',
            'product_attribute', 'product_attribute_value', 'product_shipping_method', 'product', 'product_detail', 'product_image',
            'product_variant', 'product_variant_value', 'product_stock_lot', 'product_stock_movement'];
        foreach (array_keys($this->sections) as $table) {
            if (!in_array($table, $order, true)) {
                $order[] = $table;
            }
        }
        foreach ($order as $table) {
            if (empty($this->sections[$table])) {
                continue;
            }
            $out[] = '';
            $out[] = '-- '.$table;
            foreach ($this->sections[$table] as $statement) {
                $out[] = $statement;
            }
        }
        file_put_contents($this->dir.'seed.sql', implode("\n", $out)."\n");
    }

    /**
     * @return string สรุปจำนวนแถว
     */
    public function summary()
    {
        $parts = [];
        foreach ($this->sections as $table => $rows) {
            $parts[] = $table.' '.count($rows);
        }

        return implode(', ', $parts).'; '.count($this->images).' file(s) in images.json';
    }

    /**
     * ไฟล์ใน images.json ที่ยังไม่มีใน datas/
     *
     * @return array
     */
    public function missingFiles()
    {
        $missing = [];
        foreach ($this->images as $item) {
            if (!is_file($this->dir.'datas/'.$item['file'])) {
                $missing[] = $item['file'];
            }
        }

        return $missing;
    }
}

// =============================================================================
// main
// =============================================================================

$args = array_slice($argv, 1);
$runImages = !in_array('--no-images', $args, true);
$types = array_values(array_filter($args, function ($arg) {
    return substr($arg, 0, 2) !== '--';
}));
if (in_array('--all', $args, true)) {
    $types = [];
    foreach (glob(SEED_ROOT.'*/content.php') as $file) {
        $types[] = basename(dirname($file));
    }
    sort($types);
}
if (empty($types)) {
    fwrite(STDERR, "usage: php install/seeds/build.php <type> [type …] | --all [--no-images]\n");
    exit(1);
}

$status = 0;
foreach ($types as $type) {
    if (!preg_match('/^[a-z0-9_]+$/', $type) || !is_file(SEED_ROOT.$type.'/content.php')) {
        fwrite(STDERR, "no content.php in install/seeds/$type/\n");
        exit(1);
    }
    try {
        $builder = new SeedBuilder($type);
        $builder->build();
        $builder->writeImages();
        if ($runImages) {
            $cmd = 'python3 '.escapeshellarg(SEED_ROOT.'images.py').' '.escapeshellarg($type);
            passthru($cmd, $code);
            if ($code !== 0) {
                throw new RuntimeException('images.py failed (exit '.$code.')');
            }
            // ขนาดไฟล์ (download/edocument) อ่านจากไฟล์ที่เพิ่งสร้าง — สร้างแถวใหม่
            $builder = new SeedBuilder($type);
            $builder->build();
        }
        $builder->writeStats();
        $builder->writeSql();
        echo $type.': seed.sql written — '.$builder->summary()."\n";
        $missing = $builder->missingFiles();
        if (!empty($missing)) {
            echo '  WARNING '.count($missing)." file(s) listed in images.json are not in datas/ yet (run python3 install/seeds/images.py $type):\n";
            foreach (array_slice($missing, 0, 10) as $file) {
                echo '    '.$file."\n";
            }
        }
    } catch (RuntimeException $e) {
        fwrite(STDERR, $type.': '.$e->getMessage()."\n");
        $status = 1;
    }
}
exit($status);

<?php
/**
 * install/cli-test.php — ชุดทดสอบตัวติดตั้งและตัวปรับรุ่นของ GCMS
 *
 * ทุกขั้นเรียก "ตัวติดตั้งของจริง" (install/cli-fresh.php, install/cli-upgrade.php
 * ซึ่ง include step/upgrade2.php ตัวเดียวกับหน้าเว็บ) ไม่ใช่สำเนา SQL ที่เขียนขึ้นเอง
 *
 *   ก. ติดตั้งใหม่ : ฐานอ้างอิง (ไม่เลือกประเภท) + ทุกประเภทใน install/seeds/
 *      + ประเภทแรกแบบไม่ติดตั้งข้อมูลตัวอย่าง — ตรวจธีม/ค่ากำหนด, สคีมาต้องเท่าฐานอ้างอิง,
 *      ข้อมูลตัวอย่างต้องครบตามสัญญาใน install/seeds/README.md และตามที่ธีมอ้าง
 *   ข. ปรับรุ่น : โคลนฐานต้นทาง (--from=) ลงฐานทดสอบ แล้วรันตัวปรับรุ่นสองรอบ
 *      รอบแรกต้องผ่าน · รอบสองต้องไม่แก้อะไรเลย (สคีมาและจำนวนแถวเท่าเดิม)
 *   ค. เทียบสคีมาของฐานที่ปรับรุ่นแล้วกับฐานที่ติดตั้งใหม่ — ต้องเหมือนกันทุกตาราง
 *      (ตาราง/คอลัมน์ของรุ่นเดิมที่เก็บไว้รายงานเป็นหมายเหตุ ไม่ถือว่าผิด)
 *   ง. ตรวจการเขียนข้อมูลจริงใต้ STRICT_TRANS_TABLES (สมัครสมาชิก หมวดหมู่ เมนู ...
 *      ในธุรกรรมที่ย้อนกลับทุกครั้ง) + รหัสผ่านผู้ดูแล + ค่ากำหนดหลังติดตั้ง/ปรับรุ่น
 *
 * ใช้:  php install/cli-test.php [ตัวเลือก]
 *
 *   --db-prefix=<name>        คำนำหน้าชื่อฐานทดสอบ (ค่าเริ่มต้น gcms15test)
 *                             ชุดทดสอบ "ปฏิเสธ" การเขียนฐานใดก็ตามที่ไม่ขึ้นต้นด้วยค่านี้
 *   --types=<a,b|none>        ประเภทที่ทดสอบ (ค่าเริ่มต้น ทุกประเภท · none = ข้าม)
 *   --from=<db>[:<config>]    ฐานต้นทางที่จะโคลนแล้วปรับรุ่น ระบุซ้ำได้ ฐานต้นทางถูก
 *                             อ่านอย่างเดียว (SELECT) · <config> = settings/config.php
 *                             ของไซต์นั้น (ไม่ระบุ = ค่ากำหนดเปล่า) ใช้แค่สำเนาชั่วคราว
 *   --from-prefix=<prefix>    prefix ของตารางในฐานต้นทาง (ไม่ระบุ = หาจากตาราง <prefix>_user)
 *   --seeds=<dir>             อ่านประเภทเว็บไซต์จากโฟลเดอร์อื่นแทน install/seeds/
 *                             (ทดสอบ seed ที่ยังไม่ได้วางลงชุดติดตั้ง — ส่งต่อให้ cli-fresh.php)
 *   --tmp=<dir>               โฟลเดอร์ชั่วคราว (ค่าเริ่มต้น sys_get_temp_dir())
 *   --keep                    ไม่ลบฐานทดสอบและโฟลเดอร์ชั่วคราวเมื่อจบ
 *   --verbose                 แสดงผลของ cli-fresh/cli-upgrade ทุกครั้ง (ปกติแสดงเมื่อล้ม)
 *
 * ตัวอย่าง : php install/cli-test.php --from=gcms --from=wk_gcms
 *
 * ความปลอดภัย
 *   • ค่าการเชื่อมต่ออ่านจาก settings/database.php ภายในโปรแกรม ไม่รับ/ไม่พิมพ์ออกมา
 *   • ฐานที่เขียนได้ต้องขึ้นต้นด้วย --db-prefix และต้องไม่ใช่ฐานของเว็บนี้หรือฐานต้นทาง
 *   • ผู้ดูแลของฐานโคลนได้รหัสผ่านสุ่มและ password_key ใหม่ (ไม่ใช้ของไซต์จริง)
 *   • ไฟล์ที่ตัวปรับรุ่นเขียนลงเว็บ (datas/logs/upgrade-*.log, รูปที่ hook ของโมดูลคัดลอกลง datas/)
 *     ถูกลบคืนหลังแต่ละรอบ · ไฟล์ตัวอย่างของประเภทเว็บไซต์ลงโฟลเดอร์ชั่วคราว
 *
 * exit code : 0 = ผ่านทั้งหมด, 1 = มีข้อที่ไม่ผ่าน, 2 = ใช้งานผิด/ถูกปฏิเสธ
 */
if (PHP_SAPI !== 'cli') {
    exit('CLI only');
}

define('ROOT_PATH', str_replace(['\\', 'install/cli-test.php'], ['/', ''], __FILE__));
include_once ROOT_PATH.'install/common.php';
include_once ROOT_PATH.'install/seeds.php';
include_once ROOT_PATH.'Kotchasan/Password.php';

// =============================================================================
// ตัวเลือก
// =============================================================================
$OPT = [
    'db-prefix' => 'gcms15test',
    'types' => '',
    'from' => [],
    'from-prefix' => '',
    'tmp' => '',
    'seeds' => '',
    'keep' => false,
    'verbose' => false
];
foreach (array_slice($argv, 1) as $arg) {
    if (!preg_match('/^--([a-z-]+)(?:=(.*))?$/', $arg, $m) || !array_key_exists($m[1], $OPT)) {
        fwrite(STDERR, "ไม่รู้จักตัวเลือก $arg\n");
        exit(2);
    }
    if ($m[1] === 'from') {
        $OPT['from'][] = isset($m[2]) ? $m[2] : '';
    } else {
        $OPT[$m[1]] = isset($m[2]) ? $m[2] : true;
    }
}
if (!is_string($OPT['db-prefix']) || !preg_match('/^[a-z0-9_]{4,40}$/i', $OPT['db-prefix'])) {
    fwrite(STDERR, "--db-prefix ต้องเป็น a-z 0-9 _ ยาว 4-40 ตัว\n");
    exit(2);
}

if (is_string($OPT['seeds']) && $OPT['seeds'] !== '') {
    if (!is_dir($OPT['seeds'])) {
        fwrite(STDERR, "ไม่พบโฟลเดอร์ {$OPT['seeds']}\n");
        exit(2);
    }
    define('INSTALL_SEEDS_DIR', $OPT['seeds']);
}

$SITE = include ROOT_PATH.'settings/database.php';
$MYSQL = $SITE['mysql'];
$SITE_DB = isset($MYSQL['dbname']) ? (string) $MYSQL['dbname'] : '';
$SOURCES = [];
foreach ($OPT['from'] as $spec) {
    $parts = explode(':', (string) $spec, 2);
    if (!preg_match('/^[A-Za-z0-9_]+$/', $parts[0])) {
        fwrite(STDERR, "--from ไม่ถูกต้อง : $spec\n");
        exit(2);
    }
    $SOURCES[$parts[0]] = isset($parts[1]) ? $parts[1] : '';
}
// ฐานของเว็บนี้ขึ้นต้นด้วย --db-prefix ไม่ได้ ไม่งั้นด่านข้างล่างไม่มีความหมาย
if ($SITE_DB !== '' && stripos($SITE_DB, $OPT['db-prefix']) === 0) {
    fwrite(STDERR, "ปฏิเสธ : ฐานของเว็บนี้ (settings/database.php) ขึ้นต้นด้วย --db-prefix={$OPT['db-prefix']}\n");
    exit(2);
}

// =============================================================================
// ผลการทดสอบ
// =============================================================================
$RESULT = ['pass' => 0, 'fail' => 0, 'warn' => 0, 'failed' => []];
$SECTION = '';

/**
 * @param string $title
 */
function section($title)
{
    $GLOBALS['SECTION'] = $title;
    echo "\n== $title\n";
}

/**
 * @param bool   $ok
 * @param string $label
 * @param string $detail แสดงเมื่อไม่ผ่าน (หรือเมื่อ --verbose)
 *
 * @return bool
 */
function check($ok, $label, $detail = '')
{
    if ($ok) {
        ++$GLOBALS['RESULT']['pass'];
        echo "  [ok]   $label\n";
    } else {
        ++$GLOBALS['RESULT']['fail'];
        $GLOBALS['RESULT']['failed'][] = $GLOBALS['SECTION'].' › '.$label;
        echo "  [FAIL] $label\n";
    }
    if ($detail !== '' && (!$ok || $GLOBALS['OPT']['verbose'] === true)) {
        echo '         '.str_replace("\n", "\n         ", rtrim($detail))."\n";
    }

    return $ok;
}

/**
 * @param string $text
 */
function warn($text)
{
    ++$GLOBALS['RESULT']['warn'];
    echo "  [warn] $text\n";
}

/**
 * @param string $text
 */
function note($text)
{
    echo "  [--]   $text\n";
}

// =============================================================================
// ฐานข้อมูล — ค่าการเชื่อมต่ออยู่ในตัวแปรเท่านั้น ไม่พิมพ์ ไม่ส่งต่อทางบรรทัดคำสั่ง
// =============================================================================

/**
 * @param string $dbname ว่าง = ไม่เลือกฐาน
 *
 * @return PDO
 */
function testPdo($dbname = '')
{
    $cfg = $GLOBALS['MYSQL'];
    $dsn = 'mysql:host='.(empty($cfg['hostname']) ? 'localhost' : $cfg['hostname'])
        .';port='.(empty($cfg['port']) ? 3306 : $cfg['port']).';charset=utf8mb4'
        .($dbname === '' ? '' : ';dbname='.$dbname);

    return new PDO($dsn, $cfg['username'], $cfg['password'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
}

/**
 * ชื่อฐานทดสอบ — ผ่านด่าน assertScratch() ทุกครั้ง
 *
 * @param string $suffix
 *
 * @return string
 */
function scratchName($suffix)
{
    $prefix = $GLOBALS['OPT']['db-prefix'];
    $name = $prefix.(substr($prefix, -1) === '_' ? '' : '_').preg_replace('/[^a-z0-9_]/i', '_', $suffix);
    assertScratch($name);

    return $name;
}

/**
 * ด่านเดียวของทุกการเขียนฐาน : ต้องขึ้นต้นด้วย --db-prefix และไม่ใช่ฐานของเว็บ/ฐานต้นทาง
 *
 * @param string $name
 */
function assertScratch($name)
{
    $ok = preg_match('/^[A-Za-z0-9_]{1,64}$/', $name)
        && stripos($name, $GLOBALS['OPT']['db-prefix']) === 0
        && strcasecmp($name, $GLOBALS['SITE_DB']) !== 0;
    foreach (array_keys($GLOBALS['SOURCES']) as $source) {
        $ok = $ok && strcasecmp($name, $source) !== 0;
    }
    if (!$ok) {
        fwrite(STDERR, "ปฏิเสธ : `$name` ไม่ใช่ฐานทดสอบ (ต้องขึ้นต้นด้วย {$GLOBALS['OPT']['db-prefix']} และไม่ใช่ฐานของเว็บหรือฐานต้นทาง)\n");
        exit(2);
    }
}

/**
 * @param string $name
 */
function dropScratch($name)
{
    assertScratch($name);
    testPdo()->exec("DROP DATABASE IF EXISTS `$name`");
}

/**
 * รันสคริปต์ใน install/ เป็นโปรเซสแยก (ไม่ผ่าน shell)
 *
 * @param string $script
 * @param array  $args
 *
 * @return array [exit code, ผลลัพธ์ (stdout+stderr)]
 */
function runInstaller($script, array $args)
{
    $command = array_merge([PHP_BINARY, ROOT_PATH.'install/'.$script], $args);
    $proc = proc_open($command, [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['redirect', 1]], $pipes, ROOT_PATH);
    if (!is_resource($proc)) {
        return [255, "เรียก $script ไม่ได้"];
    }
    fclose($pipes[0]);
    $out = stream_get_contents($pipes[1]);
    fclose($pipes[1]);

    return [proc_close($proc), (string) $out];
}

/**
 * สคีมาของตารางที่ขึ้นต้นด้วย <prefix>_ (ชื่อตารางถูกตัด prefix ออก จะได้เทียบข้าม prefix ได้)
 *
 * @param string $dbname
 * @param string $prefix
 *
 * @return array ['tables' => [t => engine collation], 'columns' => [t => [c => นิยาม]], 'indexes' => [t => [ชื่อ => นิยาม]]]
 */
function snapshot($dbname, $prefix)
{
    $pdo = testPdo();
    $like = str_replace('_', '\\_', $prefix).'\\_%';
    $cut = strlen($prefix) + 1;
    $snap = ['tables' => [], 'columns' => [], 'indexes' => []];
    $q = $pdo->prepare('SELECT TABLE_NAME, ENGINE, TABLE_COLLATION FROM information_schema.TABLES
        WHERE TABLE_SCHEMA = ? AND TABLE_TYPE = \'BASE TABLE\' AND TABLE_NAME LIKE ? ORDER BY TABLE_NAME');
    $q->execute([$dbname, $like]);
    foreach ($q->fetchAll(PDO::FETCH_NUM) as $r) {
        $snap['tables'][substr($r[0], $cut)] = $r[1].' '.$r[2];
    }
    $q = $pdo->prepare('SELECT TABLE_NAME, COLUMN_NAME, COLUMN_TYPE, IS_NULLABLE, COLUMN_DEFAULT, EXTRA, COLLATION_NAME
        FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = ? AND TABLE_NAME LIKE ? ORDER BY TABLE_NAME, ORDINAL_POSITION');
    $q->execute([$dbname, $like]);
    foreach ($q->fetchAll(PDO::FETCH_NUM) as $r) {
        $snap['columns'][substr($r[0], $cut)][$r[1]] = preg_replace('/\b(tinyint|smallint|mediumint|int|bigint)\(\d+\)/', '$1', $r[2])
            .' '.($r[3] === 'YES' ? 'NULL' : 'NOT NULL').' DEFAULT '.var_export($r[4], true)
            .($r[5] === '' ? '' : ' '.$r[5]).($r[6] === null ? '' : ' '.$r[6]);
    }
    $q = $pdo->prepare('SELECT TABLE_NAME, INDEX_NAME, NON_UNIQUE, INDEX_TYPE, GROUP_CONCAT(COLUMN_NAME ORDER BY SEQ_IN_INDEX)
        FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = ? AND TABLE_NAME LIKE ?
        GROUP BY TABLE_NAME, INDEX_NAME, NON_UNIQUE, INDEX_TYPE ORDER BY TABLE_NAME, INDEX_NAME');
    $q->execute([$dbname, $like]);
    foreach ($q->fetchAll(PDO::FETCH_NUM) as $r) {
        $snap['indexes'][substr($r[0], $cut)][$r[1]] = ($r[2] ? '' : 'UNIQUE ').$r[3].'('.$r[4].')';
    }

    return $snap;
}

/**
 * จำนวนแถวจริงของทุกตาราง <prefix>_ (ชื่อถูกตัด prefix ออก)
 *
 * @param string $dbname
 * @param string $prefix
 *
 * @return array
 */
function rowCounts($dbname, $prefix)
{
    $pdo = testPdo();
    $counts = [];
    foreach (array_keys(snapshot($dbname, $prefix)['tables']) as $table) {
        $counts[$table] = (int) $pdo->query("SELECT COUNT(*) FROM `$dbname`.`{$prefix}_$table`")->fetchColumn();
    }

    return $counts;
}

/**
 * เทียบสคีมาของฐาน $b กับฐานอ้างอิง $a
 *
 * @param array $a ฐานอ้างอิง (ติดตั้งใหม่)
 * @param array $b ฐานที่ตรวจ
 *
 * @return array [ปัญหา[], หมายเหตุ[]] — ทุกอย่างของ $a ที่ $b ไม่มีหรือไม่ตรง = ปัญหา
 *               ของที่ $b มีเกิน (ของรุ่นเดิมที่เก็บไว้) และลำดับคอลัมน์ = หมายเหตุ
 */
function compareSchema(array $a, array $b)
{
    $problems = [];
    $notes = [];
    foreach ($a['tables'] as $t => $options) {
        if (!isset($b['tables'][$t])) {
            $problems[] = "ไม่มีตาราง $t";
            continue;
        }
        if ($b['tables'][$t] !== $options) {
            $problems[] = "$t: ตาราง [{$b['tables'][$t]}] ต้องเป็น [$options]";
        }
        $ca = isset($a['columns'][$t]) ? $a['columns'][$t] : [];
        $cb = isset($b['columns'][$t]) ? $b['columns'][$t] : [];
        foreach ($ca as $c => $def) {
            if (!isset($cb[$c])) {
                $problems[] = "$t.$c: ไม่มีคอลัมน์ (ต้องเป็น $def)";
            } elseif ($cb[$c] !== $def) {
                $problems[] = "$t.$c: [{$cb[$c]}] ต้องเป็น [$def]";
            }
        }
        if (array_keys($ca) !== array_values(array_intersect(array_keys($cb), array_keys($ca)))) {
            $notes[] = "$t: ลำดับคอลัมน์ต่างจากติดตั้งใหม่ (ไม่มีผลกับการทำงาน)";
        }
        $extra = array_diff(array_keys($cb), array_keys($ca));
        if (!empty($extra)) {
            $notes[] = "$t: เก็บคอลัมน์ของรุ่นเดิมไว้ ".implode(', ', $extra);
        }
        $ia = isset($a['indexes'][$t]) ? $a['indexes'][$t] : [];
        $ib = isset($b['indexes'][$t]) ? $b['indexes'][$t] : [];
        foreach ($ia as $name => $def) {
            if (!isset($ib[$name]) || $ib[$name] !== $def) {
                $problems[] = "$t: ดัชนี $name [".(isset($ib[$name]) ? $ib[$name] : 'ไม่มี')."] ต้องเป็น [$def]";
            }
        }
        foreach (array_diff_key($ib, $ia) as $name => $def) {
            $notes[] = "$t: เก็บดัชนีเดิมไว้ $name [$def]";
        }
    }
    foreach (array_diff_key($b['tables'], $a['tables']) as $t => $options) {
        $notes[] = "เก็บตารางที่ระบบนี้ไม่ใช้ไว้ $t ($options)";
    }

    return [$problems, $notes];
}

/**
 * ลบ log ที่ตัวปรับรุ่นเขียนไว้ใน datas/logs/ ตามชื่อที่มันรายงาน
 *
 * @param string $output
 */
function removeUpgradeLogs($output)
{
    if (preg_match_all('#datas/logs/upgrade-\d{8}-\d{6}\.log#', $output, $m)) {
        foreach (array_unique($m[0]) as $file) {
            if (is_file(ROOT_PATH.$file)) {
                @unlink(ROOT_PATH.$file);
            }
        }
    }
}

/**
 * รายชื่อไฟล์และโฟลเดอร์ทั้งหมดใต้ datas/ ของเว็บ (ยกเว้น cache/ และ logs/)
 *
 * ตัวปรับรุ่นคัดลอกไฟล์ลง datas/ ของเว็บจริงได้ (เช่นรูปสินค้าของ hook modules/product)
 * ชุดทดสอบจึงจดไว้ก่อนรันแล้วลบของที่งอกใหม่ทิ้ง ไม่ให้เหลือร่องรอยในเว็บที่ใช้งานอยู่
 * — ไฟล์เดิมไม่ถูกแตะเลย
 *
 * @param string $dir
 *
 * @return array [ที่อยู่เทียบกับ $dir => true] (โฟลเดอร์ลงท้ายด้วย /)
 */
function listTree($dir)
{
    $list = [];
    if (!is_dir($dir)) {
        return $list;
    }
    $items = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::SELF_FIRST
    );
    foreach ($items as $item) {
        $path = substr(str_replace('\\', '/', $item->getPathname()), strlen($dir));
        if (preg_match('#^(cache|logs)(/|$)#', $path)) {
            continue;
        }
        $list[$path.($item->isDir() ? '/' : '')] = true;
    }

    return $list;
}

/**
 * ลบไฟล์/โฟลเดอร์ที่งอกขึ้นใต้ $dir หลังจากจด listTree() ไว้
 *
 * @param string $dir
 * @param array  $before
 *
 * @return int จำนวนไฟล์ที่ลบ
 */
function restoreTree($dir, array $before)
{
    $new = array_diff_key(listTree($dir), $before);
    // ลึกสุดก่อน โฟลเดอร์จะว่างก่อนถูกลบ
    krsort($new);
    $removed = 0;
    foreach (array_keys($new) as $path) {
        if (substr($path, -1) === '/') {
            @rmdir($dir.$path);
        } elseif (@unlink($dir.$path)) {
            ++$removed;
        }
    }

    return $removed;
}

/**
 * @param string $dir
 */
function removeTree($dir)
{
    if (!is_dir($dir)) {
        return;
    }
    $items = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST
    );
    foreach ($items as $item) {
        $item->isDir() ? @rmdir($item->getPathname()) : @unlink($item->getPathname());
    }
    @rmdir($dir);
}

/**
 * บันทึกค่ากำหนดชั่วคราว (สิทธิ 0600 — อาจมีค่าความลับของไซต์ต้นทาง)
 *
 * @param array  $config
 * @param string $file
 */
function writeConfig(array $config, $file)
{
    save($config, $file);
    @chmod($file, 0600);
}

// =============================================================================
// ตรวจการเขียนข้อมูลจริงใต้ STRICT mode
// =============================================================================

/**
 * @param string $dbname
 * @param string $prefix
 * @param string $config_file ค่ากำหนดของฐานนี้
 * @param string $password    รหัสผ่านผู้ดูแล id 1
 */
function runtimeChecks($dbname, $prefix, $config_file, $password)
{
    $p = $prefix;
    $pdo = testPdo($dbname);
    $pdo->exec("SET SESSION sql_mode = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION'");
    $try = function ($label, callable $fn) {
        try {
            $detail = $fn();
            check(true, $label, (string) $detail);
        } catch (\Throwable $exc) {
            check(false, $label, $exc->getMessage());
        }
    };
    $pdo->beginTransaction();
    // สมัครสมาชิก — คอลัมน์ชุดเดียวกับที่ Index\Register\Model เขียน (phone/id_card ว่างเป็น NULL)
    $try('STRICT: สมัครสมาชิก 2 คน (phone/id_card ว่าง)', function () use ($pdo, $p) {
        $st = $pdo->prepare("INSERT INTO `{$p}_user` (`username`,`password`,`salt`,`name`,`phone`,`id_card`,`line_uid`,`telegram_id`,`created_at`,`status`,`social`,`active`,`activatecode`,`permission`)
            VALUES (?,?,?,?,NULL,NULL,NULL,NULL,NOW(),0,'user',1,'','')");
        foreach (['cli-test-1@example.com', 'cli-test-2@example.com'] as $email) {
            $st->execute([$email, 'x', 's', 'Member']);
        }
    });
    $try('STRICT: ชื่อผู้ใช้ซ้ำถูกปฏิเสธ (UNIQUE username)', function () use ($pdo, $p) {
        try {
            $pdo->exec("INSERT INTO `{$p}_user` (`username`,`password`,`name`,`created_at`) VALUES ('cli-test-1@example.com','x','Dup',NOW())");
        } catch (\PDOException $exc) {
            return '';
        }
        throw new \Exception('รับชื่อผู้ใช้ซ้ำ');
    });
    // แม่แบบอีเมล — Gcms\EmailTemplate::get() ค้นด้วย code + language
    $try('แม่แบบอีเมลครบตาม install/database.sql', function () use ($pdo, $p) {
        preg_match_all("/INSERT INTO `\\{prefix\\}_emailtemplate`.*?VALUES\\s*\\('[^']*',\\s*\\d+,\\s*'([^']+)',\\s*'([a-z]{2})'/",
            (string) file_get_contents(ROOT_PATH.'install/database.sql'), $m, PREG_SET_ORDER);
        $st = $pdo->prepare("SELECT COUNT(*) FROM `{$p}_emailtemplate` WHERE `code` = ? AND `language` = ?");
        $missing = [];
        foreach ($m as $row) {
            $st->execute([$row[1], $row[2]]);
            if ((int) $st->fetchColumn() === 0) {
                $missing[] = $row[1].'/'.$row[2];
            }
        }
        if (empty($m) || !empty($missing)) {
            throw new \Exception(empty($m) ? 'อ่านรายการแม่แบบจาก database.sql ไม่ได้' : 'ไม่มี '.implode(', ', $missing));
        }

        return count($m).' แม่แบบ';
    });
    $try('หมวดหมู่ topic/detail/icon/config เป็น JSON', function () use ($pdo, $p) {
        $bad = [];
        foreach ($pdo->query("SELECT `id`, `topic`, `detail`, `icon`, `config` FROM `{$p}_category`")->fetchAll(PDO::FETCH_ASSOC) as $row) {
            foreach (['topic', 'detail', 'icon', 'config'] as $c) {
                if ((string) $row[$c] !== '' && json_decode($row[$c]) === null) {
                    $bad[] = $row['id'].'.'.$c;
                }
            }
        }
        if (!empty($bad)) {
            throw new \Exception('ไม่ใช่ JSON : '.implode(', ', array_slice($bad, 0, 20)));
        }
    });
    $try('modules.config เป็น JSON (ไม่เหลือ PHP serialize)', function () use ($pdo, $p) {
        $bad = [];
        foreach ($pdo->query("SELECT `id`, `config` FROM `{$p}_modules`")->fetchAll(PDO::FETCH_ASSOC) as $row) {
            if ((string) $row['config'] !== '' && json_decode($row['config']) === null) {
                $bad[] = $row['id'];
            }
        }
        if (!empty($bad)) {
            throw new \Exception('ไม่ใช่ JSON : id '.implode(', ', array_slice($bad, 0, 20)));
        }
    });
    $try('STRICT: เพิ่มหมวดหมู่ / เมนู / ป้ายกำกับ', function () use ($pdo, $p) {
        $pdo->exec("INSERT INTO `{$p}_category` (`module_id`,`category_id`,`config`,`topic`,`detail`,`icon`,`published`,`type`,`language`)
            VALUES (999,1,'{}','{\"th\":\"x\"}','{}','{}','1','category','')");
        $pdo->exec("INSERT INTO `{$p}_menus` (`index_id`,`level`,`language`,`menu_text`,`menu_tooltip`,`accesskey`,`menu_order`,`menu_url`,`menu_target`,`alias`,`published`,`parent`)
            VALUES (1,0,'','x','','',1,'','','','1','0_MAINMENU')");
        $pdo->exec("INSERT INTO `{$p}_tags` (`tag`,`count`) VALUES ('cli-test-แท็ก', 1)");
        $pdo->exec("UPDATE `{$p}_tags` SET `count` = `count` + 1 WHERE `tag` = 'cli-test-แท็ก'");
    });
    $try('STRICT: login_attempt / user_session / logs', function () use ($pdo, $p) {
        $pdo->exec("INSERT INTO `{$p}_login_attempt` (`username`,`ip_address`,`user_agent`,`attempted_at`) VALUES ('x','::1','cli',NOW())");
        $pdo->exec("INSERT INTO `{$p}_user_session` (`sid`,`member_id`,`expires_at`) VALUES ('".md5(uniqid('', true))."',1,".(time() + 60).')');
        $pdo->exec("INSERT INTO `{$p}_logs` (`src_id`,`module`,`action`,`created_at`,`member_id`,`topic`) VALUES (0,'index','cli-test',NOW(),1,'x')");
    });
    $pdo->rollBack();

    // ค่ากำหนดและผู้ดูแล
    $config = is_file($config_file) ? include $config_file : [];
    $new_config = include ROOT_PATH.'install/settings/config.php';
    check(is_array($config) && isset($config['version']) && $config['version'] === $new_config['version']
        && !empty($config['password_key']) && !empty($config['jwt_secret']) && !empty($config['api_tokens']['internal']),
        'ค่ากำหนด : version '.(isset($config['version']) ? $config['version'] : '-').', password_key/jwt_secret/api_tokens ครบ');
    if (!empty($config['skin'])) {
        check(is_file(ROOT_PATH.'themes/'.basename($config['skin']).'/index.html'), 'ธีม '.$config['skin'].' มีอยู่จริง');
    }
    $user = $pdo->query("SELECT `password`, `salt`, `status` FROM `{$p}_user` WHERE `id` = 1")->fetch(PDO::FETCH_ASSOC);
    check($user && (int) $user['status'] === 1
        && \Kotchasan\Password::verify($password, $user['password'], $user['salt'], (string) $config['password_key'])
        && !\Kotchasan\Password::isLegacyHash($user['password']),
        'ผู้ดูแล id 1 เข้าระบบได้ด้วย password_key ของไซต์ และเก็บเป็น bcrypt');
}

// =============================================================================
// ตรวจข้อมูลตัวอย่างตามสัญญาใน install/seeds/README.md และตามที่ธีมอ้าง
// =============================================================================

/**
 * รายชื่อโมดูลที่แต่ละประเภทต้องมี จากหัวข้อ "ชื่อโมดูล" ของ README
 *
 * @return array [type => [[module, owner], ...]]
 */
function readmeModules()
{
    $readme = (string) @file_get_contents(ROOT_PATH.'install/seeds/README.md');
    $result = [];
    if (preg_match_all('/^- \*\*([a-z0-9_]+)\*\*\s*:(.*?)(?=^- \*\*|^\s*$|\z)/ms', $readme, $blocks, PREG_SET_ORDER)) {
        foreach ($blocks as $block) {
            if (preg_match_all('/`([a-z0-9_]+)\(([a-z0-9_]+)\)`/', $block[2], $m, PREG_SET_ORDER)) {
                foreach ($m as $item) {
                    $result[$block[1]][] = [$item[1], $item[2]];
                }
            }
        }
    }

    return $result;
}

/**
 * @param string $dbname
 * @param string $prefix
 * @param array  $info   จาก siteTypes()
 * @param string $skin
 */
function seedChecks($dbname, $prefix, array $info, $skin)
{
    $p = $prefix;
    $pdo = testPdo($dbname);
    $modules = [];
    foreach ($pdo->query("SELECT `module`, `owner` FROM `{$p}_modules`")->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $modules[$row['module']] = $row['owner'];
    }
    check(!empty($modules), 'ข้อมูลตัวอย่างสร้างโมดูล '.count($modules).' โมดูล');
    $no_folder = [];
    foreach (array_unique($modules) as $owner) {
        if (!is_dir(ROOT_PATH.'modules/'.basename($owner))) {
            $no_folder[] = $owner;
        }
    }
    check(empty($no_folder), 'ทุกโมดูลมีโฟลเดอร์ใน modules/', empty($no_folder) ? '' : 'ไม่มี : modules/'.implode(', modules/', $no_folder));
    // สัญญาใน README
    $contract = readmeModules();
    if (!isset($contract[$info['key']])) {
        warn('อ่านรายชื่อโมดูลของ '.$info['key'].' จาก install/seeds/README.md ไม่ได้ — ข้ามการเทียบสัญญา');
    } else {
        $missing = [];
        foreach ($contract[$info['key']] as $item) {
            if (!isset($modules[$item[0]]) || $modules[$item[0]] !== $item[1]) {
                $missing[] = $item[0].'('.$item[1].')'.(isset($modules[$item[0]]) ? ' มีแต่ owner='.$modules[$item[0]] : '');
            }
        }
        check(empty($missing), 'โมดูลครบตาม README ('.count($contract[$info['key']]).' โมดูล)', implode(', ', $missing));
    }
    // เมนูแรกของเมนูหลักต้องเป็นหน้าแรก (home)
    $first = $pdo->query("SELECT M.`module` FROM `{$p}_menus` U
        LEFT JOIN `{$p}_index` I ON I.`id` = U.`index_id`
        LEFT JOIN `{$p}_modules` M ON M.`id` = I.`module_id`
        WHERE U.`parent` = '0_MAINMENU' ORDER BY U.`menu_order`, U.`id` LIMIT 1")->fetchColumn();
    check($first === 'home', 'เมนูแรกของเมนูหลักคือโมดูล home', 'ได้ '.var_export($first, true));
    // widget ที่ธีมอ้าง ต้องมีข้อมูลรองรับ
    $html = '';
    foreach (glob(ROOT_PATH.'themes/'.basename($skin).'/*.html') ?: [] as $file) {
        $html .= file_get_contents($file);
    }
    preg_match_all('/\{WIDGET_[A-Z0-9_]+\s[^}]*\bmodule=([a-z0-9_]+)/i', $html, $m);
    $refs = array_unique($m[1]);
    $missing = array_diff($refs, array_keys($modules));
    check(empty($missing), 'ธีม '.$skin.' อ้างโมดูล '.count($refs).' ตัว มีครบ', empty($missing) ? '' : 'ไม่มี : '.implode(', ', $missing));
    preg_match_all('/\{WIDGET_TEXTLINKS(?:_([a-z0-9_]+)|\s[^}]*\bname=([a-z0-9_]+))/i', $html, $m, PREG_SET_ORDER);
    $names = [];
    foreach ($m as $item) {
        $names[] = $item[1] !== '' ? $item[1] : $item[2];
    }
    $names = array_unique($names);
    $empty = [];
    $st = $pdo->prepare("SELECT COUNT(*) FROM `{$p}_textlink` WHERE `name` = ?");
    foreach ($names as $name) {
        $st->execute([$name]);
        if ((int) $st->fetchColumn() === 0) {
            $empty[] = $name;
        }
    }
    if (!empty($empty)) {
        warn('ธีม '.$skin.' อ้างกลุ่ม Textlinks ที่ไม่มีข้อมูล : '.implode(', ', $empty).' (แสดงเป็นช่องว่าง)');
    } elseif (!empty($names)) {
        check(true, 'กลุ่ม Textlinks ที่ธีมอ้างมีข้อมูลครบ ('.implode(', ', $names).')');
    }
}

// =============================================================================
// เก็บกวาดเสมอ แม้ล้มกลางทาง
// =============================================================================
$CREATED = [];
$TMP = rtrim(is_string($OPT['tmp']) && $OPT['tmp'] !== '' ? $OPT['tmp'] : sys_get_temp_dir(), '/').'/gcms-cli-test-'.getmypid().'/';
if (!@mkdir($TMP, 0700, true) && !is_dir($TMP)) {
    fwrite(STDERR, "สร้างโฟลเดอร์ชั่วคราว $TMP ไม่ได้\n");
    exit(2);
}
$FINISHED = false;
register_shutdown_function(function () {
    if ($GLOBALS['OPT']['keep'] === true) {
        echo "\n(--keep) เก็บฐานทดสอบไว้ : ".implode(', ', $GLOBALS['CREATED'])."\n";
        echo "(--keep) ไฟล์ค่ากำหนดชั่วคราว : {$GLOBALS['TMP']}\n";
    } else {
        foreach ($GLOBALS['CREATED'] as $name) {
            try {
                dropScratch($name);
            } catch (\Throwable $exc) {
                fwrite(STDERR, "ลบฐาน $name ไม่ได้ : ".$exc->getMessage()."\n");
            }
        }
        removeTree($GLOBALS['TMP']);
    }
    if (!$GLOBALS['FINISHED']) {
        echo "\n[FAIL] ชุดทดสอบหยุดกลางทาง — ดูข้อผิดพลาดด้านบน\n";
        exit(1);
    }
});

/**
 * ติดตั้งใหม่ผ่าน cli-fresh.php
 *
 * @param string $dbname
 * @param array  $args      ตัวเลือกเพิ่มเติม
 * @param string $password
 * @param string $label
 *
 * @return string|null ไฟล์ค่ากำหนด (null = ล้ม)
 */
function freshInstall($dbname, array $args, $password, $label)
{
    assertScratch($dbname);
    $GLOBALS['CREATED'][] = $dbname;
    $config_file = $GLOBALS['TMP'].$dbname.'.config.php';
    list($code, $out) = runInstaller('cli-fresh.php', array_merge(
        [$dbname, 'gcms', '--overwrite', '--strict', '--password='.$password, '--config='.$config_file],
        defined('INSTALL_SEEDS_DIR') ? array_merge(['--seeds='.INSTALL_SEEDS_DIR], $args) : $args
    ));
    $ok = check($code === 0 && strpos($out, '[FAIL]') === false, $label, $out);

    return $ok ? $config_file : null;
}

// =============================================================================
// ก. ติดตั้งใหม่
// =============================================================================
$new_config = include ROOT_PATH.'install/settings/config.php';
echo 'GCMS '.$new_config['version'].' — ชุดทดสอบตัวติดตั้ง/ตัวปรับรุ่น (ฐานทดสอบ '.$OPT['db-prefix']."*)\n";

section('ติดตั้งใหม่ : ฐานอ้างอิง (ไม่เลือกประเภท)');
$REF = scratchName('fresh');
$password = bin2hex(random_bytes(8));
$ref_config = freshInstall($REF, [], $password, 'cli-fresh.php '.$REF);
if ($ref_config === null) {
    $FINISHED = true;
    echo "\nติดตั้งฐานอ้างอิงไม่ได้ — ทดสอบต่อไม่ได้\n";
    exit(1);
}
$REF_SNAP = snapshot($REF, 'gcms');
$REF_COUNTS = rowCounts($REF, 'gcms');
check(count($REF_SNAP['tables']) > 0, 'สร้าง '.count($REF_SNAP['tables']).' ตาราง', implode(', ', array_keys($REF_SNAP['tables'])));
runtimeChecks($REF, 'gcms', $ref_config, $password);

$TYPES = siteTypes();
if ($OPT['types'] === 'none') {
    $want = [];
} elseif (is_string($OPT['types']) && $OPT['types'] !== '') {
    $want = array_filter(array_map('trim', explode(',', $OPT['types'])));
    foreach (array_diff($want, array_keys($TYPES)) as $unknown) {
        section('ติดตั้งใหม่ : '.$unknown);
        check(false, 'ไม่รู้จักประเภท '.$unknown.' (มี : '.implode(', ', array_keys($TYPES)).')');
    }
    $want = array_values(array_intersect($want, array_keys($TYPES)));
} else {
    $want = array_keys($TYPES);
}
if (empty($TYPES)) {
    section('ติดตั้งใหม่ : ประเภทเว็บไซต์');
    warn('ไม่พบประเภทเว็บไซต์ใน install/seeds/*/info.php');
}
foreach ($want as $key) {
    $info = $TYPES[$key];
    section('ติดตั้งใหม่ : ประเภท '.$key.' — '.$info['label']);
    $dbname = scratchName('type_'.$key);
    $datas = $TMP.'datas_'.$key.'/';
    $password = bin2hex(random_bytes(8));
    $config_file = freshInstall($dbname, ['--type='.$key, '--datas='.$datas], $password, 'cli-fresh.php --type='.$key);
    if ($config_file === null) {
        continue;
    }
    $config = include $config_file;
    $site_values = siteTypeConfig($info)[0];
    $skin = basename((string) (isset($site_values['skin']) ? $site_values['skin'] : $info['skin']));
    check(isset($config['skin']) && $config['skin'] === $skin, 'ธีมของไซต์คือ '.$skin, 'config skin = '.var_export(isset($config['skin']) ? $config['skin'] : null, true));
    list($problems) = compareSchema($REF_SNAP, snapshot($dbname, 'gcms'));
    $extra = array_diff_key(snapshot($dbname, 'gcms')['tables'], $REF_SNAP['tables']);
    check(empty($problems) && empty($extra), 'สคีมาเท่าฐานอ้างอิง (ข้อมูลตัวอย่างไม่แก้โครงสร้าง)', implode("\n", array_merge($problems, array_keys($extra))));
    if ($info['has_seed']) {
        seedChecks($dbname, 'gcms', $info, $skin);
        if (is_dir($info['dir'].'datas')) {
            $copied = 0;
            if (is_dir($datas)) {
                foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($datas, FilesystemIterator::SKIP_DOTS)) as $file) {
                    $copied += $file->isFile() ? 1 : 0;
                }
            }
            check($copied > 0, 'คัดลอกไฟล์ตัวอย่าง '.$copied.' ไฟล์ (ลงโฟลเดอร์ชั่วคราว ไม่ใช่ datas/ ของเว็บ)');
        }
    } else {
        warn('ยังไม่มี install/seeds/'.$key.'/seed.sql — ทดสอบเฉพาะธีม/ค่ากำหนด');
    }
    runtimeChecks($dbname, 'gcms', $config_file, $password);
}
if (!empty($want)) {
    $key = reset($want);
    section('ติดตั้งใหม่ : ประเภท '.$key.' แบบไม่ติดตั้งข้อมูลตัวอย่าง (--no-sample)');
    $dbname = scratchName('nosample');
    $password = bin2hex(random_bytes(8));
    $config_file = freshInstall($dbname, ['--type='.$key, '--no-sample', '--datas='.$TMP.'datas_nosample/'], $password, 'cli-fresh.php --type='.$key.' --no-sample');
    if ($config_file !== null) {
        $config = include $config_file;
        check(!empty($config['skin']) || $TYPES[$key]['skin'] === '', 'ตั้งธีมแล้ว ('.(isset($config['skin']) ? $config['skin'] : '-').')');
        $counts = rowCounts($dbname, 'gcms');
        $diff = [];
        foreach ($REF_COUNTS as $table => $n) {
            if ($table !== 'migration' && isset($counts[$table]) && $counts[$table] !== $n) {
                $diff[] = "$table $n → {$counts[$table]}";
            }
        }
        check(empty($diff), 'ไม่มีข้อมูลตัวอย่าง (จำนวนแถวเท่าฐานอ้างอิง)', implode(', ', $diff));
        check(!is_dir($TMP.'datas_nosample/'), 'ไม่คัดลอกไฟล์ตัวอย่าง');
    }
}

// =============================================================================
// ข. + ค. + ง. ปรับรุ่นฐานโคลน
// =============================================================================
foreach ($SOURCES as $source => $source_config) {
    section('ปรับรุ่น : โคลนจาก `'.$source.'`');
    $pdo = testPdo();
    $st = $pdo->prepare('SELECT DEFAULT_CHARACTER_SET_NAME, DEFAULT_COLLATION_NAME FROM information_schema.SCHEMATA WHERE SCHEMA_NAME = ?');
    $st->execute([$source]);
    $charset = $st->fetch(PDO::FETCH_NUM);
    if (!check((bool) $charset, 'มีฐานต้นทาง '.$source)) {
        continue;
    }
    // prefix ของต้นทาง
    if (is_string($OPT['from-prefix']) && $OPT['from-prefix'] !== '') {
        $prefix = $OPT['from-prefix'];
    } else {
        $st = $pdo->prepare("SELECT TABLE_NAME FROM information_schema.TABLES WHERE TABLE_SCHEMA = ? AND TABLE_NAME LIKE '%\\_user'");
        $st->execute([$source]);
        $found = array_map(function ($t) {
            return substr($t, 0, -5);
        }, $st->fetchAll(PDO::FETCH_COLUMN));
        if (count($found) !== 1) {
            check(false, 'หา prefix ของ '.$source.' ไม่ได้ (พบ '.implode(', ', $found).') ระบุ --from-prefix=');
            continue;
        }
        $prefix = $found[0];
    }
    if (!preg_match('/^[A-Za-z0-9_]+$/', $prefix)) {
        check(false, 'prefix ไม่ถูกต้อง : '.$prefix);
        continue;
    }
    $clone = scratchName('up_'.$source);
    $CREATED[] = $clone;

    // ---- โคลน (ต้นทางอ่านอย่างเดียว)
    $source_counts = rowCounts($source, $prefix);
    $state = '';
    if (isset($source_counts['migration'])) {
        $state = (string) $pdo->query("SELECT `version` FROM `$source`.`{$prefix}_migration` ORDER BY `id` DESC LIMIT 1")->fetchColumn();
    }
    note('ต้นทาง prefix '.$prefix.' · '.count($source_counts).' ตาราง · '.array_sum($source_counts).' แถว'
        .($state === '' ? ' · ยังไม่เคยปรับรุ่นเป็น 15 (ไม่มี migration)' : ' · migration ล่าสุด '.$state));
    dropScratch($clone);
    $pdo->exec("CREATE DATABASE `$clone` CHARACTER SET {$charset[0]} COLLATE {$charset[1]}");
    $pdo->exec("SET SESSION sql_mode = ''");
    $pdo->exec('SET FOREIGN_KEY_CHECKS = 0');
    $pdo->exec("USE `$clone`");
    foreach (array_keys($source_counts) as $table) {
        $create = $pdo->query("SHOW CREATE TABLE `$source`.`{$prefix}_$table`")->fetch(PDO::FETCH_NUM)[1];
        $pdo->exec($create);
        $pdo->exec("INSERT INTO `$clone`.`{$prefix}_$table` SELECT * FROM `$source`.`{$prefix}_$table`");
    }
    $pdo->exec('SET FOREIGN_KEY_CHECKS = 1');
    check(rowCounts($clone, $prefix) === $source_counts, 'โคลนลง '.$clone.' ครบทุกแถว');

    // ---- ค่ากำหนดชั่วคราว + ผู้ดูแล id 1 ที่รู้รหัสผ่าน (password_key ใหม่ ไม่ใช้ของไซต์จริง)
    $config = [];
    if ($source_config !== '') {
        $config = is_file($source_config) ? include $source_config : null;
        if (!check(is_array($config), 'อ่านค่ากำหนดของต้นทาง '.basename($source_config))) {
            continue;
        }
    }
    $config['password_key'] = uniqid();
    $config_file = $TMP.$clone.'.config.php';
    writeConfig($config, $config_file);
    // ชื่อเข้าระบบ : username หรือ email (GCMS รุ่นแรก ๆ ไม่มี username — loginColumn())
    $st = $pdo->prepare("SELECT `COLUMN_NAME` FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND COLUMN_NAME IN ('username', 'email')");
    $st->execute([$clone, $prefix.'_user']);
    $columns = $st->fetchAll(PDO::FETCH_COLUMN);
    $login = in_array('username', $columns, true) ? 'username' : (in_array('email', $columns, true) ? 'email' : '');
    if (!check($login !== '', 'ตาราง user มีคอลัมน์ชื่อเข้าระบบ'.($login === '' ? '' : ' ('.$login.')'))) {
        continue;
    }
    if ($login === 'email') {
        note('ระบบเดิมเข้าระบบด้วย email (ไม่มี username) — ตัวปรับรุ่นต้องย้ายเป็น username ให้');
    }
    $admin = $pdo->query("SELECT `id`, `$login` AS `login` FROM `$clone`.`{$prefix}_user` WHERE `id` = 1")->fetch(PDO::FETCH_ASSOC);
    if (!check((bool) $admin && (string) $admin['login'] !== '', 'ฐานต้นทางมีผู้ดูแล id 1')) {
        continue;
    }
    $password = bin2hex(random_bytes(8));
    $salt = uniqid();
    // รูปแบบ sha1 รุ่นเก่า (GCMS 11–14) — ทดสอบเส้นทางเก็บใหม่เป็น bcrypt ของ updateAdmin() ไปด้วย
    $st = $pdo->prepare("UPDATE `$clone`.`{$prefix}_user` SET `salt` = ?, `password` = ?, `status` = 1 WHERE `id` = 1");
    $st->execute([$salt, sha1($config['password_key'].$password.$salt)]);

    // ---- ปรับรุ่นสองรอบ
    $args = [(string) $admin['login'], $password, '--confirm-backup', '--db='.$clone, '--prefix='.$prefix, '--config='.$config_file];
    $datas_dir = ROOT_PATH.'datas/';
    $snaps = [];
    $counts = [];
    $outputs = [];
    foreach ([1, 2] as $round) {
        $before = listTree($datas_dir);
        list($code, $out) = runInstaller('cli-upgrade.php', $args);
        removeUpgradeLogs($out);
        $removed = restoreTree($datas_dir, $before);
        if ($removed > 0) {
            note('ลบไฟล์ '.$removed.' ไฟล์ที่ตัวปรับรุ่นรอบที่ '.$round.' คัดลอกลง datas/ ของเว็บ (เว็บจริงจะได้ไฟล์เหล่านี้ตอนปรับรุ่น)');
        }
        $outputs[$round] = $out;
        check($code === 0 && strpos($out, 'ปรับรุ่นเรียบร้อย') !== false && strpos($out, '[FAIL]') === false,
            'cli-upgrade.php รอบที่ '.$round, $out);
        $snaps[$round] = snapshot($clone, $prefix);
        $counts[$round] = rowCounts($clone, $prefix);
        if ($code !== 0 && $round === 1) {
            break;
        }
    }
    if (!isset($outputs[2])) {
        note('รอบที่ 1 ไม่ผ่าน — ข้ามการตรวจที่เหลือของต้นทางนี้ (ผลจะไม่มีความหมาย)');
        continue;
    }
    // รอบสองต้องไม่แก้อะไร — ทุกบรรทัด [ok] ต้องเป็นบรรทัดสถานะ ไม่ใช่การแก้ไข
    $steady = ['เชื่อมต่อฐานข้อมูลสำเร็จ', 'ฐานข้อมูล ', 'ปรับรุ่นจาก ', 'ตารางที่ระบบนี้ไม่ใช้ ', 'บันทึก config.php', 'นำเข้า `'];
    $changes = [];
    foreach (preg_split('/\R/', $outputs[2]) as $line) {
        if (!preg_match('/^\s*\[ok\]\s+(.*)$/', $line, $m)) {
            continue;
        }
        $is_steady = false;
        foreach ($steady as $start) {
            $is_steady = $is_steady || strpos($m[1], $start) === 0;
        }
        if (!$is_steady) {
            $changes[] = $m[1];
        }
    }
    check(empty($changes), 'รอบที่ 2 ไม่มีการแก้ไข (ตัวปรับรุ่นรันซ้ำได้)', implode("\n", $changes));
    list($p1) = compareSchema($snaps[1], $snaps[2]);
    list($p2) = compareSchema($snaps[2], $snaps[1]);
    check(empty($p1) && empty($p2) && $snaps[1] === $snaps[2], 'สคีมาหลังรอบ 1 = หลังรอบ 2', implode("\n", array_merge($p1, $p2)));
    $diff = [];
    foreach ($counts[1] + $counts[2] as $table => $n) {
        $a = isset($counts[1][$table]) ? $counts[1][$table] : null;
        $b = isset($counts[2][$table]) ? $counts[2][$table] : null;
        // migration ได้แถวใหม่หนึ่งแถวทุกรอบที่สำเร็จ (stampMigration) — ตั้งใจ
        if ($a !== $b && !($table === 'migration' && $b === $a + 1)) {
            $diff[] = "$table ".var_export($a, true).' → '.var_export($b, true);
        }
    }
    check(empty($diff), 'จำนวนแถวหลังรอบ 1 = หลังรอบ 2 (ยกเว้น migration +1)', implode(', ', $diff));
    // ข้อมูลเดิมต้องไม่หาย (ตัวปรับรุ่นตรวจเองแล้ว แต่ตรวจซ้ำจากภายนอก — ตารางที่ถูกเปลี่ยนชื่อเก็บไว้นับรวม)
    $lost = [];
    foreach ($source_counts as $table => $n) {
        // แถวที่ตัวปรับรุ่นย้ายไปเก็บ อยู่ในตาราง <ชื่อเดิม>_..legacy.. (logs_access_legacy,
        // activities_legacy, tags_duplicate_legacy)
        $kept = isset($counts[2][$table]) ? $counts[2][$table] : 0;
        foreach ($counts[2] as $t => $c) {
            if (strpos($t, $table.'_') === 0 && strpos($t, 'legacy') !== false) {
                $kept += $c;
            }
        }
        if ($kept < $n) {
            $lost[] = "$table $n → $kept";
        }
    }
    check(empty($lost), 'ข้อมูลเดิมไม่ลดลง (นับรวมตาราง *_legacy ที่ย้ายไปเก็บ)', implode(', ', $lost));

    // ---- ค. เทียบกับฐานติดตั้งใหม่
    list($problems, $notes) = compareSchema($REF_SNAP, $snaps[2]);
    check(empty($problems), 'สคีมาเท่าฐานที่ติดตั้งใหม่ ('.count($REF_SNAP['tables']).' ตาราง)', implode("\n", $problems));
    foreach ($notes as $text) {
        note($text);
    }

    // ---- ง. STRICT mode
    runtimeChecks($clone, $prefix, $config_file, $password);
    // ต้นทางต้องไม่ถูกแตะ (หมายเหตุเท่านั้น — ไซต์ต้นทางอาจมีคนใช้งานอยู่ระหว่างทดสอบ)
    if (rowCounts($source, $prefix) !== $source_counts) {
        warn('จำนวนแถวของฐานต้นทาง `'.$source.'` เปลี่ยนระหว่างทดสอบ (มีคนใช้งานไซต์อยู่?) ชุดทดสอบอ่านต้นทางอย่างเดียว');
    }
}
if (empty($SOURCES)) {
    section('ปรับรุ่น');
    note('ไม่ได้ระบุ --from=<ฐาน> — ข้ามการทดสอบปรับรุ่น');
}

// =============================================================================
// สรุป
// =============================================================================
$FINISHED = true;
section('สรุป');
echo '  ผ่าน '.$RESULT['pass'].' · ไม่ผ่าน '.$RESULT['fail'].' · คำเตือน '.$RESULT['warn']."\n";
foreach ($RESULT['failed'] as $label) {
    echo "  [FAIL] $label\n";
}
echo $RESULT['fail'] === 0 ? "\nผ่านทั้งหมด\n" : "\nมีข้อที่ไม่ผ่าน\n";
exit($RESULT['fail'] === 0 ? 0 : 1);

<?php
/**
 * install/cli-fresh.php — ติดตั้งใหม่ลงฐานข้อมูลเปล่าจากบรรทัดคำสั่ง
 *
 * มีไว้เทียบกับผลของ install/cli-upgrade.php: ฐานที่ "ปรับรุ่นมา" กับฐานที่
 * "ติดตั้งใหม่" ต้องได้สคีมาเหมือนกันทุกตัวอักษร ถ้าต่างกันแปลว่า database.sql
 * กับ upgrade2.php เริ่มเพี้ยนจากกันแล้ว (install/cli-test.php ใช้ไฟล์นี้)
 *
 * ทำเหมือน install/step5.php ทุกขั้นตอน (สคีมา → ผู้ดูแลสูงสุด → ประเภทเว็บไซต์
 * → นำเข้าภาษา) ฐานทดสอบจึงเป็นผลของ "ตัวติดตั้งของจริง" ไม่ใช่สำเนา SQL
 *
 * ใช้:  php install/cli-fresh.php <dbname> [prefix] [ตัวเลือก]
 *
 *   --admin=<username>   ผู้ดูแลสูงสุด (ค่าเริ่มต้น admin@localhost)
 *   --password=<pass>    รหัสผ่านของผู้ดูแลสูงสุด (ค่าเริ่มต้น admin)
 *   --config=<file>      เขียนไฟล์ค่ากำหนดของฐานนี้ไว้ (ต้องใช้คู่กับ cli-upgrade --config=)
 *   --type=<key>         ประเภทเว็บไซต์ (โฟลเดอร์ใน install/seeds/ เช่น company school shop)
 *   --no-sample          ไม่นำเข้าข้อมูลตัวอย่างของประเภทนั้น (ตั้งแค่ธีม/ค่ากำหนด)
 *   --datas=<dir>        ปลายทางของไฟล์ตัวอย่าง (ค่าเริ่มต้น datas/ ของโปรเจ็ค)
 *                        ชุดทดสอบชี้ไปโฟลเดอร์ชั่วคราว จะได้ไม่ทิ้งไฟล์ไว้ในเว็บจริง
 *   --seeds=<dir>        อ่านประเภทเว็บไซต์จากโฟลเดอร์อื่นแทน install/seeds/ (ทดสอบ seed ที่ยังไม่วางลงชุดติดตั้ง)
 *   --strict             นำเข้าข้อมูลตัวอย่างใต้ STRICT_TRANS_TABLES (หน้าเว็บใช้ sql_mode
 *                        ว่างตาม install/db.php — ชุดทดสอบเข้มกว่าโดยตั้งใจ)
 *   --overwrite          ยอมลบฐานที่มีอยู่แล้วและมีตาราง (ไม่ระบุ = ปฏิเสธ)
 *   --no-admin           สร้างแต่ตาราง ไม่สร้างผู้ดูแลและไม่นำเข้าภาษา (ไว้เทียบสคีมาล้วน ๆ)
 *
 * ⚠️ ไฟล์นี้ DROP DATABASE ปลายทางทั้งฐาน จึงปฏิเสธเสมอเมื่อ <dbname> คือฐานใน
 * settings/database.php (ฐานของเว็บนี้เอง) และปฏิเสธฐานที่มีตารางอยู่แล้วถ้าไม่มี --overwrite
 * ค่าการเชื่อมต่ออ่านจาก settings/database.php ภายในไฟล์นี้ ไม่รับทางบรรทัดคำสั่ง
 */
if (PHP_SAPI !== 'cli') {
    exit('CLI only');
}

$params = [];
$options = [
    'admin' => 'admin@localhost',
    'password' => 'admin',
    'config' => '',
    'type' => '',
    'no-sample' => false,
    'datas' => '',
    'seeds' => '',
    'strict' => false,
    'overwrite' => false,
    'no-admin' => false
];
foreach (array_slice($argv, 1) as $arg) {
    if (preg_match('/^--([a-z-]+)(?:=(.*))?$/', $arg, $match)) {
        if (!array_key_exists($match[1], $options)) {
            fwrite(STDERR, "ไม่รู้จักตัวเลือก $arg\n");
            exit(1);
        }
        $options[$match[1]] = isset($match[2]) ? $match[2] : true;
    } else {
        $params[] = $arg;
    }
}
$dbname = isset($params[0]) ? $params[0] : '';
$prefix = isset($params[1]) ? $params[1] : 'gcms';
if ($dbname === '') {
    fwrite(STDERR, "ใช้: php install/cli-fresh.php <dbname> [prefix] [--type= --no-sample --admin= --password= --config= --datas= --seeds= --strict --overwrite --no-admin]\n");
    exit(1);
}
if (!preg_match('/^[A-Za-z0-9_]+$/', $dbname) || !preg_match('/^[A-Za-z0-9_]+$/', $prefix)) {
    fwrite(STDERR, "ชื่อฐานและ prefix ใช้ได้เฉพาะ A-Z a-z 0-9 _\n");
    exit(1);
}

define('ROOT_PATH', str_replace(['\\', 'install/cli-fresh.php'], ['/', ''], __FILE__));
include_once ROOT_PATH.'install/common.php';
include_once ROOT_PATH.'install/db.php';
include_once ROOT_PATH.'install/preflight.php';
if (is_string($options['seeds']) && $options['seeds'] !== '') {
    if (!is_dir($options['seeds'])) {
        fwrite(STDERR, 'ไม่พบโฟลเดอร์ '.$options['seeds']."\n");
        exit(1);
    }
    define('INSTALL_SEEDS_DIR', $options['seeds']);
}
include_once ROOT_PATH.'install/seeds.php';

if ($options['type'] !== '') {
    $types = siteTypes();
    if ($options['type'] === true || !isset($types[$options['type']])) {
        fwrite(STDERR, 'ไม่รู้จักประเภทเว็บไซต์ '.($options['type'] === true ? '(ว่าง)' : $options['type'])
            .' — มี : '.implode(', ', array_keys($types))."\n");
        exit(1);
    }
    if ($options['no-admin'] === true) {
        fwrite(STDERR, "--type ใช้คู่กับ --no-admin ไม่ได้ (ประเภทเว็บไซต์ต้องมีผู้ดูแลและค่ากำหนด)\n");
        exit(1);
    }
}

// --config ต้องไม่ใช่ไฟล์ค่ากำหนดของเว็บนี้เอง (จะถูกเขียนทับด้วย password_key ใหม่)
if (is_string($options['config']) && $options['config'] !== '') {
    $_target = realpath(dirname($options['config']));
    if ($_target !== false && in_array($_target.'/'.basename($options['config']), [
        realpath(ROOT_PATH.'settings').'/config.php',
        realpath(ROOT_PATH.'settings').'/database.php'
    ], true)) {
        fwrite(STDERR, "ปฏิเสธ : --config ชี้ไปที่ไฟล์ค่ากำหนดของเว็บนี้ (settings/) ให้ใช้ไฟล์ชั่วคราวแทน\n");
        exit(1);
    }
}

$db_settings = include ROOT_PATH.'settings/database.php';
$db_config = $db_settings['mysql'];

// ด่านกันฐานของเว็บนี้เอง — ไฟล์นี้ลบทั้งฐาน
if (isset($db_config['dbname']) && strcasecmp((string) $db_config['dbname'], $dbname) === 0) {
    fwrite(STDERR, "ปฏิเสธ : `$dbname` คือฐานของเว็บนี้ (settings/database.php) — cli-fresh.php ลบทั้งฐานก่อนติดตั้ง\n");
    exit(1);
}

$dsn = 'mysql:host='.(empty($db_config['hostname']) ? 'localhost' : $db_config['hostname'])
    .';port='.(empty($db_config['port']) ? 3306 : $db_config['port']).';charset=utf8mb4';
$pdo = new PDO($dsn, $db_config['username'], $db_config['password'], [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
]);

// ฐานที่มีอยู่แล้วและมีตาราง ต้องยืนยันด้วย --overwrite
$stmt = $pdo->prepare('SELECT COUNT(*) FROM `information_schema`.`TABLES` WHERE `TABLE_SCHEMA` = ?');
$stmt->execute([$dbname]);
$existing = (int) $stmt->fetchColumn();
if ($existing > 0 && $options['overwrite'] !== true) {
    fwrite(STDERR, "ปฏิเสธ : ฐาน `$dbname` มีอยู่แล้วและมี $existing ตาราง — ระบุ --overwrite ถ้าต้องการลบทิ้งแล้วติดตั้งใหม่\n");
    exit(1);
}

$pdo->exec("DROP DATABASE IF EXISTS `$dbname`");
$pdo->exec("CREATE DATABASE `$dbname` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
$pdo->exec("USE `$dbname`");

$count = 0;
// GCMS : ต้องใส่ DROP ก่อน CREATE เหมือน step5.php — database.sql ของ GCMS
// นิยาม category ทับของ core.sql ถ้าไม่ DROP จะล้มด้วย "Table already exists"
foreach (schemaCommands($prefix, true) as $command) {
    $pdo->exec($command);
    $count++;
}

echo "ติดตั้งใหม่ลง `$dbname` (prefix $prefix) สำเร็จ — รัน $count คำสั่ง\n";
foreach (schemaFiles() as $file) {
    echo '  จาก '.str_replace(ROOT_PATH, '', $file)."\n";
}

if ($options['no-admin'] === true) {
    exit(0);
}

// ต่อจากนี้ทำเหมือน step5.php — ผู้ดูแลสูงสุด, ประเภทเว็บไซต์, ค่ากำหนด, นำเข้าภาษา
$db_config['dbname'] = $dbname;
$db_config['prefix'] = $prefix;
$db = new Db($db_config);

$password_key = uniqid();
createAdmin($db, $prefix.'_user', $options['admin'], $options['password'], $password_key);
// ไม่พิมพ์รหัสผ่าน — ผู้เรียกรู้อยู่แล้ว และผลลัพธ์นี้อาจถูกเก็บเป็น log
echo '  ผู้ดูแลสูงสุด '.$options['admin']."\n";

$new_config = include ROOT_PATH.'install/settings/config.php';
$cfg = ensureConfigDefaults(include ROOT_PATH.'install/settings/config.php', $new_config);
$cfg['password_key'] = $password_key;

// ประเภทเว็บไซต์ (install/seeds.php) — ฟังก์ชันเดียวกับหน้าเว็บ
$content = [];
if ($options['type'] !== '') {
    if ($options['strict'] === true) {
        $db->query("SET SESSION sql_mode = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION'");
    }
    $site_email = filter_var($options['admin'], FILTER_VALIDATE_EMAIL) ? $options['admin'] : '';
    $datas_dir = is_string($options['datas']) ? $options['datas'] : '';
    $ok = applySiteType($db, $prefix, $options['type'], $options['no-sample'] !== true, ['SITE_EMAIL' => $site_email], $content, $cfg, $datas_dir);
    echo '  '.upgradeReportText('<ul>'.implode('', $content).'</ul>')."\n";
    if (!$ok) {
        fwrite(STDERR, "ติดตั้งประเภทเว็บไซต์ไม่สำเร็จ\n");
        exit(1);
    }
    if ($options['strict'] === true) {
        $db->query("SET SESSION sql_mode = ''");
    }
}

// นำเข้าภาษา (language.php ต้องการ $db, $db_config['prefix'] และ $content)
include ROOT_PATH.'install/language.php';

// บันทึกรุ่นลงฐานข้อมูลของไซต์เอง เหมือนที่ตัวปรับรุ่นทำ
stampMigration($db, $prefix, 'core', $new_config['version'], 'cli-fresh.php');

if ($options['config'] !== '' && $options['config'] !== true) {
    // ไฟล์ค่ากำหนดของฐานนี้ ใช้คู่กับ cli-upgrade.php --config= เพื่อให้ทดสอบ
    // ปรับรุ่นหลายฐานได้โดยไม่ต้องแตะ settings/config.php ของโปรเจ็ค
    if (save($cfg, $options['config'])) {
        echo '  ค่ากำหนด '.$options['config']."\n";
    } else {
        fwrite(STDERR, 'เขียนไฟล์ค่ากำหนด '.$options['config']." ไม่ได้\n");
        exit(1);
    }
}

<?php
/**
 * install/seeds.php — ประเภทเว็บไซต์และข้อมูลตัวอย่างของตัวติดตั้ง
 *
 * สัญญากลางของโฟลเดอร์ install/seeds/<type>/ (info.php, config.php, seed.sql,
 * datas/) อยู่ที่ install/seeds/README.md — ไฟล์นี้คือฝั่ง "ผู้อ่าน" ของสัญญานั้น
 * ใช้ร่วมกันระหว่างหน้าเว็บ (step2.php เลือกประเภท, step5.php ติดตั้ง) และ
 * install/cli-fresh.php --type= เพื่อให้ฐานทดสอบเป็นผลของตัวติดตั้งตัวจริง
 *
 * ต้อง include install/common.php ก่อน (ใช้ sqlCommands() และ makeDirectory())
 */
if (!defined('ROOT_PATH')) {
    exit;
}

/**
 * โฟลเดอร์ของประเภทเว็บไซต์ — ปกติคือ install/seeds/
 *
 * ชุดทดสอบชี้ไปที่อื่นได้ด้วย define('INSTALL_SEEDS_DIR', ...) ก่อนเรียก
 * (cli-fresh.php / cli-test.php --seeds=<dir>) จะได้ทดสอบ seed ที่ยังไม่ได้วางลงชุดติดตั้ง
 *
 * @return string ลงท้ายด้วย /
 */
function siteSeedsDir()
{
    return defined('INSTALL_SEEDS_DIR') ? rtrim(str_replace('\\', '/', INSTALL_SEEDS_DIR), '/').'/' : ROOT_PATH.'install/seeds/';
}

/**
 * ประเภทเว็บไซต์ทั้งหมดในชุดติดตั้ง เรียงตาม 'order' ใน info.php
 *
 * โฟลเดอร์ที่ยังไม่มี seed.sql ก็นับเป็นประเภทที่เลือกได้ (ตั้งได้แค่ธีม/ค่ากำหนด)
 * ตัวติดตั้งจึงใช้งานได้ตั้งแต่ก่อนข้อมูลตัวอย่างจะเสร็จ
 *
 * @return array [key => ['key', 'label', 'description', 'skin', 'columns', 'order', 'dir', 'has_seed']]
 */
function siteTypes()
{
    $types = [];
    foreach (glob(siteSeedsDir().'*/info.php') ?: [] as $file) {
        $key = basename(dirname($file));
        // ชื่อโฟลเดอร์คือค่าที่ส่งผ่านฟอร์มและบรรทัดคำสั่ง รับเฉพาะอักขระที่ปลอดภัย
        if (!preg_match('/^[a-z0-9_]+$/', $key)) {
            continue;
        }
        $info = include $file;
        if (!is_array($info)) {
            continue;
        }
        $info += [
            'label' => $key,
            'description' => '',
            'skin' => '',
            'columns' => 0,
            'order' => 100
        ];
        $dir = dirname($file).'/';
        $info['key'] = $key;
        $info['dir'] = $dir;
        $info['has_seed'] = is_file($dir.'seed.sql');
        $types[$key] = $info;
    }
    uasort($types, function ($a, $b) {
        return [(int) $a['order'], $a['key']] <=> [(int) $b['order'], $b['key']];
    });

    return $types;
}

/**
 * รูปตัวอย่างของธีม (themes/<skin>/screenshot.*)
 *
 * @param string $skin
 *
 * @return string ที่อยู่เทียบกับ ROOT_PATH เช่น themes/rw/screenshot.webp (ว่าง = ไม่มีรูป)
 */
function siteTypeScreenshot($skin)
{
    $skin = basename((string) $skin);
    if ($skin === '') {
        return '';
    }
    foreach (['webp', 'png', 'jpg', 'jpeg', 'svg', 'gif'] as $ext) {
        if (is_file(ROOT_PATH.'themes/'.$skin.'/screenshot.'.$ext)) {
            return 'themes/'.$skin.'/screenshot.'.$ext;
        }
    }

    return '';
}

/**
 * ค่ากำหนดของประเภทเว็บไซต์ (install/seeds/<type>/config.php)
 *
 * README ห้ามใส่ค่าความลับไว้ในไฟล์นี้ แต่ต้องกันไว้ที่ฝั่งผู้อ่านด้วย — ค่าพวกนี้
 * ถ้าหลุดไปอยู่ในชุดติดตั้ง ทุกเว็บที่ติดตั้งจากชุดเดียวกันจะได้กุญแจเดียวกันหมด
 * และ password_key ที่ถูกทับหลังสร้างผู้ดูแลแล้ว = ผู้ดูแลเข้าระบบไม่ได้
 *
 * @param array $info จาก siteTypes()
 *
 * @return array [ค่าที่ใช้ได้, ชื่อคีย์ที่ถูกข้าม]
 */
function siteTypeConfig(array $info)
{
    $file = $info['dir'].'config.php';
    $values = is_file($file) ? include $file : [];
    if (!is_array($values)) {
        return [[], []];
    }
    $ignored = [];
    foreach (['version', 'reversion', 'password_key', 'api_tokens', 'api_secret', 'jwt_secret'] as $key) {
        if (array_key_exists($key, $values)) {
            $ignored[] = $key;
            unset($values[$key]);
        }
    }

    return [$values, $ignored];
}

/**
 * ติดตั้งประเภทเว็บไซต์ลงไซต์ที่เพิ่งสร้างตารางเสร็จ
 *
 *   1. รวม <type>/config.php (skin ฯลฯ) เข้า $config — ผู้เรียกเป็นคนบันทึกไฟล์เอง
 *   2. นำเข้า <type>/seed.sql เมื่อ $withSample (ทั้งชุดหรือไม่เลย — ล้มกลางทางถูกยกเลิกทั้งชุด)
 *   3. คัดลอก <type>/datas/ ไป datas/ ของเว็บ เมื่อ $withSample (ไม่ทับไฟล์ที่มีอยู่)
 *
 * ประเภทที่ยังไม่มี seed.sql ทำแค่ข้อ 1 (และ 3 ถ้ามีไฟล์) พร้อมหมายเหตุ ไม่ถือว่าผิดพลาด
 * ทุกส่วนรายงานลง $content เป็น <li> แบบเดียวกับขั้นตอนอื่นของตัวติดตั้ง
 *
 * @param Db     $db         เชื่อมต่อฐานของไซต์แล้ว
 * @param string $prefix     คำนำหน้าตาราง
 * @param string $type       key ของประเภท ('' = ไม่เลือก ใช้ค่าเริ่มต้น)
 * @param bool   $withSample true = ติดตั้งข้อมูลตัวอย่าง
 * @param array  $tokens     ค่าแทน token ใน seed.sql เช่น ['SITE_EMAIL' => ...]
 *                           ({prefix} แทนให้เสมอ, SITE_NAME ไม่ระบุ = web_title หลังรวมค่ากำหนด)
 * @param array  $content    รายการผลลัพธ์ (<li>)
 * @param array  $config     ค่ากำหนดของไซต์ใหม่ (ถูกแก้ในที่)
 * @param string $datas_dir  โฟลเดอร์ปลายทางของไฟล์ตัวอย่าง (ว่าง = ROOT_PATH.'datas/')
 *
 * @return bool false = มีข้อผิดพลาด (ประเภทไม่รู้จัก หรือนำเข้าข้อมูลตัวอย่างไม่สำเร็จ)
 */
function applySiteType($db, $prefix, $type, $withSample, array $tokens, array &$content, array &$config, $datas_dir = '')
{
    $type = (string) $type;
    if ($type === '') {
        $content[] = '<li class="correct">ไม่ได้เลือกประเภทเว็บไซต์ ใช้ธีมและค่ากำหนดเริ่มต้น</li>';

        return true;
    }
    $types = siteTypes();
    if (!isset($types[$type])) {
        $content[] = '<li class="incorrect">ไม่รู้จักประเภทเว็บไซต์ <code>'.htmlspecialchars($type, ENT_QUOTES).'</code> '
            .'(ไม่พบ install/seeds/'.htmlspecialchars($type, ENT_QUOTES).'/info.php)</li>';

        return false;
    }
    $info = $types[$type];

    // -------------------------------------------------------------------------
    // 1. ค่ากำหนด — ธีมต้องมีอยู่จริง ไม่งั้นหน้าเว็บว่างทั้งหน้า
    // -------------------------------------------------------------------------
    list($values, $ignored) = siteTypeConfig($info);
    $skin = basename((string) (isset($values['skin']) ? $values['skin'] : $info['skin']));
    unset($values['skin']);
    if ($skin !== '' && !is_file(ROOT_PATH.'themes/'.$skin.'/index.html')) {
        $content[] = '<li class="warning">ไม่พบธีม <code>'.htmlspecialchars($skin, ENT_QUOTES).'</code> '
            .'ของประเภทนี้ ใช้ธีมเริ่มต้นแทน (เลือกธีมใหม่ได้ที่หน้าตั้งค่า)</li>';
        $skin = '';
    } elseif ($skin !== '') {
        $values['skin'] = $skin;
    }
    $config = array_replace($config, $values);
    $content[] = '<li class="correct">ประเภทเว็บไซต์ : '.htmlspecialchars($info['label'], ENT_QUOTES)
        .($skin === '' ? '' : ' (ธีม '.htmlspecialchars($skin, ENT_QUOTES).')').'</li>';
    if (!empty($ignored)) {
        $content[] = '<li class="warning">ข้ามค่ากำหนดที่ประเภทเว็บไซต์ตั้งไม่ได้ : <code>'
            .htmlspecialchars(implode(', ', $ignored), ENT_QUOTES).'</code></li>';
    }
    if (!$withSample) {
        $content[] = '<li class="correct">ไม่ติดตั้งข้อมูลตัวอย่าง (ตามที่เลือก)</li>';

        return true;
    }

    // -------------------------------------------------------------------------
    // 2. ข้อมูลตัวอย่าง
    // -------------------------------------------------------------------------
    if (!$info['has_seed']) {
        $content[] = '<li class="warning">ประเภทนี้ยังไม่มีข้อมูลตัวอย่าง (install/seeds/'.$type.'/seed.sql) '
            .'ติดตั้งเฉพาะธีมและค่ากำหนด</li>';
    } else {
        $tokens += [
            'SITE_NAME' => isset($config['web_title']) ? $config['web_title'] : '',
            'SITE_EMAIL' => ''
        ];
        try {
            $count = importSiteSeed($db, $info['dir'].'seed.sql', $prefix, $tokens);
            $content[] = '<li class="correct">นำเข้าข้อมูลตัวอย่าง '.number_format($count).' คำสั่ง</li>';
        } catch (\Exception $exc) {
            $content[] = '<li class="incorrect">นำเข้าข้อมูลตัวอย่างไม่สำเร็จ ยกเลิกข้อมูลตัวอย่างทั้งชุดแล้ว '
                .'(ติดตั้งใหม่โดยไม่เลือก "ติดตั้งข้อมูลตัวอย่าง" ได้)<br>'
                .htmlspecialchars($exc->getMessage(), ENT_QUOTES).'</li>';

            return false;
        }
    }

    // -------------------------------------------------------------------------
    // 3. ไฟล์ตัวอย่าง (รูปที่ข้อมูลตัวอย่างอ้างถึง)
    // -------------------------------------------------------------------------
    if (is_dir($info['dir'].'datas')) {
        $target = $datas_dir === '' ? ROOT_PATH.'datas/' : rtrim(str_replace('\\', '/', $datas_dir), '/').'/';
        $result = copySiteDatas($info['dir'].'datas/', $target);
        $where = htmlspecialchars(str_replace(ROOT_PATH, '', $target), ENT_QUOTES);
        $content[] = '<li class="'.($result['failed'] > 0 ? 'warning' : 'correct').'">คัดลอกไฟล์ตัวอย่าง '
            .number_format($result['copied']).' ไฟล์ไปที่ '.$where
            .($result['skipped'] > 0 ? ' (มีอยู่แล้ว '.number_format($result['skipped']).' ไฟล์ ไม่ทับ)' : '')
            .($result['failed'] > 0 ? ' — คัดลอกไม่ได้ '.number_format($result['failed']).' ไฟล์ กรุณาตรวจสิทธิการเขียนของ datas/' : '')
            .'</li>';
    }

    return true;
}

/**
 * นำเข้า seed.sql ทั้งไฟล์ในธุรกรรมเดียว
 *
 * อ่านด้วย sqlCommands() ตัวเดียวกับ database.sql (บรรทัด -- ถูกตัด, {prefix} ถูกแทน)
 * แล้วแทน token อื่นตาม $tokens — {WEBURL} ไม่ถูกแตะ ระบบแทนเองตอนแสดงผล
 *
 * @param Db     $db
 * @param string $file
 * @param string $prefix
 * @param array  $tokens [ชื่อ token (ไม่มีวงเล็บ) => ค่า]
 *
 * @return int จำนวนคำสั่งที่รัน
 *
 * @throws \Exception คำสั่งใดล้ม — ข้อมูลตัวอย่างทั้งชุดถูก ROLLBACK แล้ว
 */
function importSiteSeed($db, $file, $prefix, array $tokens)
{
    $replace = [];
    foreach ($tokens as $name => $value) {
        $replace['{'.$name.'}'] = siteSeedValue($value);
    }
    $commands = sqlCommands($file, $prefix);
    // db.php เชื่อมต่อด้วย utf8 (3 ไบต์) — ข้อความตัวอย่างที่มีอีโมจิจะพัง
    $db->query('SET NAMES utf8mb4');
    $db->query('START TRANSACTION');
    $count = 0;
    foreach ($commands as $command) {
        $command = strtr($command, $replace);
        try {
            $db->query($command);
        } catch (\Exception $exc) {
            $db->query('ROLLBACK');
            throw new \Exception('คำสั่งที่ '.($count + 1).' : '.$exc->getMessage()
                .' — '.mb_strimwidth(trim($command), 0, 160, '…'));
        }
        ++$count;
    }
    $db->query('COMMIT');

    return $count;
}

/**
 * ค่าที่จะแทน token ใน seed.sql
 *
 * token อยู่ในสตริงของ SQL และบางที่อยู่ในสตริง JSON ซ้อนอยู่อีกชั้น (เช่นชื่อ
 * หมวดหมู่) escape ให้ถูกทั้งสองชั้นพร้อมกันไม่ได้ จึงตัดอักขระที่มีความหมาย
 * พิเศษทิ้ง (" \ < > และอักขระควบคุม) แล้ว escape ' แบบ SQL
 * ชื่อเว็บและอีเมลปกติไม่มีอักขระพวกนี้อยู่แล้ว
 *
 * @param mixed $value
 *
 * @return string
 */
function siteSeedValue($value)
{
    $value = preg_replace('/[\x00-\x1F\x7F"\\\\<>]/u', '', (string) $value);

    return str_replace("'", "''", (string) $value);
}

/**
 * คัดลอกโฟลเดอร์แบบลึก โดยไม่ทับไฟล์ที่มีอยู่แล้ว
 *
 * โฟลเดอร์ปลายทางที่มีอยู่แล้วไม่ถูกเปลี่ยนสิทธิ (ไม่ใช้ makeDirectory() กับมัน
 * เพราะ makeDirectory() chmod โฟลเดอร์เดิมของเว็บด้วย)
 *
 * @param string $from ลงท้ายด้วย /
 * @param string $to   ลงท้ายด้วย /
 *
 * @return array ['copied' => n, 'skipped' => n, 'failed' => n]
 */
function copySiteDatas($from, $to)
{
    $result = ['copied' => 0, 'skipped' => 0, 'failed' => 0];
    $from = rtrim(str_replace('\\', '/', $from), '/').'/';
    if (!is_dir($to)) {
        makeDirectory($to);
    }
    $items = new \RecursiveIteratorIterator(
        new \RecursiveDirectoryIterator($from, \FilesystemIterator::SKIP_DOTS),
        \RecursiveIteratorIterator::SELF_FIRST
    );
    foreach ($items as $item) {
        $relative = substr(str_replace('\\', '/', $item->getPathname()), strlen($from));
        $target = $to.$relative;
        if ($item->isDir()) {
            if (!is_dir($target)) {
                makeDirectory($target);
            }
        } elseif (file_exists($target)) {
            ++$result['skipped'];
        } elseif (@copy($item->getPathname(), $target)) {
            ++$result['copied'];
        } else {
            ++$result['failed'];
        }
    }

    return $result;
}

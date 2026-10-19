<?php
/**
 * install/upgrade2.php — ปรับรุ่นฐานข้อมูลของ GCMS
 *
 * รองรับฐานของ GCMS รุ่นเดิม (GCMS 11 – 14.x : MyISAM/utf8, วันที่เป็น UNIX
 * timestamp, ค่ากำหนดเป็น PHP serialize) และฐานของ GCMS 15 เอง (รันซ้ำได้)
 *
 * เป้าหมายเดียวของตัวปรับรุ่น — ฐานที่ปรับรุ่นแล้วต้อง "หน้าตาเหมือนติดตั้งใหม่"
 * นิยามตารางอ่านจากไฟล์ชุดเดียวกับตัวติดตั้ง (schemaFiles() : core.sql,
 * database.sql, modules/<ชื่อ>/install/database.sql) ถ้าตารางไหน
 * ถูกนิยามซ้ำ ใช้นิยามตัวสุดท้าย เหมือนที่ตัวติดตั้งใส่ DROP แล้ว CREATE ทับ
 * (GCMS ใช้ category คนละแบบกับ core.sql — ดูหัวไฟล์ install/database.sql)
 *
 * ⚠️ ห้ามนำ install/upgrade_core.php ของ adminframework กลับมาใช้ (ลบออกจากชุดนี้แล้ว)
 * บล็อก category ในไฟล์นั้นแปลงตารางเป็นหมวดหมู่แบบ adminframework (ตัด topic
 * เหลือ varchar(150), ลบ published) ซึ่งทำลายหมวดหมู่ของ GCMS ที่เก็บชื่อหลายภาษา
 * การปรับรุ่นตารางแกนของ GCMS จึงทำผ่าน gcmsSyncSchema() ข้างล่างแทน
 *
 * ลำดับการทำงาน
 *   1. ยืนยันว่าสำรองฐานข้อมูลแล้ว           — ไม่ยืนยัน ไม่เริ่ม
 *   2. preflight ตรวจก่อนแตะ (+ install/preflight-module.php) — ไม่ผ่าน หยุดทันที
 *   3. ยืนยันผู้ดูแลระบบสูงสุด
 *   4. ย้ายข้อมูลรุ่นเก่าให้อยู่ในชื่อ/ชนิดคอลัมน์ใหม่   gcmsBeforeSync()
 *      แล้ว hook ของโมดูล modules/<ชื่อ>/install/upgrade.php   gcmsModuleHooks()
 *      แล้วย้ายแถวที่จะชน PRIMARY KEY ตัวใหม่ไปเก็บ          gcmsResolveKeyConflicts()
 *   5. ปรับทุกตารางให้ตรงกับนิยามของตัวติดตั้ง         gcmsSyncSchema()
 *   6. แปลงข้อมูล (serialize → JSON, หมวดหมู่, รูปภาพ)   gcmsAfterSync()
 *   7. เพิ่มแม่แบบอีเมลที่ยังไม่มี, ค่ากำหนด, นำเข้าภาษา
 *   8. เทียบจำนวนแถวก่อน/หลัง บันทึกรุ่น และเขียน log ลง datas/logs/
 *
 * กฎของทุกเงื่อนไข — ถามว่า "ต้องแก้ไหม" ไม่ใช่ "ตอนนี้เป็นอะไร" เพื่อให้รันซ้ำได้
 * โดยไม่แก้อะไรซ้ำ และรันต่อจากรอบที่ค้างไว้ได้
 *
 * ไม่ลบข้อมูลของใคร — คอลัมน์ที่ระบบนี้ไม่ใช้ถูกเก็บไว้ (ให้มีค่าเริ่มต้นเท่านั้น)
 * ตารางที่ระบบนี้ไม่ใช้ถูกเก็บไว้ตามเดิม ส่วนตารางที่ต้องใช้ชื่อซ้ำ (logs) ถูก
 * เปลี่ยนชื่อเก็บไว้ คอลัมน์ที่ถูกลบมีเฉพาะคอลัมน์ที่ย้ายข้อมูลไปคอลัมน์ใหม่แล้ว
 */
if (defined('ROOT_PATH')) {
    if (empty($_POST['username']) || empty($_POST['password'])) {
        include ROOT_PATH.'install/upgrade1.php';
    } elseif (empty($_POST['confirm_backup'])) {
        adminForm(2, true, 'กรุณายืนยันว่าคุณสำรองฐานข้อมูลไว้แล้ว ก่อนเริ่มการปรับรุ่น');
    } else {
        $error = false;
        // Database Class
        include_once ROOT_PATH.'install/db.php';
        // เครื่องมือตรวจก่อนแตะ + รายงานผล (เหมือนกันทุกโปรเจ็ค)
        include_once ROOT_PATH.'install/preflight.php';
        // ค่าติดตั้งฐานข้อมูล — install/cli-upgrade.php --db= ชี้ฐานทดสอบมาให้ได้
        $db_settings = isset($db_config_override) ? $db_config_override : include ROOT_PATH.'settings/database.php';
        $config_file = isset($config_file_override) ? $config_file_override : ROOT_PATH.'settings/config.php';
        try {
            $db_config = $db_settings['mysql'];
            // เขื่อมต่อฐานข้อมูล
            $db = new Db($db_config);
        } catch (\Exception $exc) {
            $error = true;
            echo '<h2>ความผิดพลาดในการเชื่อมต่อกับฐานข้อมูล</h2>';
            echo '<p class=warning>ไม่สามารถเชื่อมต่อกับฐานข้อมูลของคุณได้ในขณะนี้</p>';
            echo '<p>อาจเป็นไปได้ว่า</p>';
            echo '<ol>';
            echo '<li>เซิร์ฟเวอร์ของฐานข้อมูลของคุณไม่สามารถใช้งานได้ในขณะนี้</li>';
            echo '<li>ค่ากำหนดของฐานข้อมูลไม่ถูกต้อง (ตรวจสอบไฟล์ settings/database.php)</li>';
            echo '<li>ไม่พบฐานข้อมูลที่ต้องการติดตั้ง กรุณาสร้างฐานข้อมูลก่อน หรือใช้ฐานข้อมูลที่มีอยู่แล้ว</li>';
            echo '<li class="incorrect">'.$exc->getMessage().'</li>';
            echo '</ol>';
            echo '<p>หากคุณไม่สามารถดำเนินการแก้ไขข้อผิดพลาดด้วยตัวของคุณเองได้ ให้ติดต่อผู้ดูแลระบบเพื่อขอข้อมูลที่ถูกต้อง หรือ ลองติดตั้งใหม่</p>';
            echo '<p class="submit"><a href="index.php?step=1" class="btn large btn-secondary">กลับไปลองใหม่</a></p>';
        }
        if (!$error) {
            // =================================================================
            // ตรวจก่อนแตะ — ทุกอย่างในบล็อกนี้อ่านอย่างเดียว
            // =================================================================
            try {
                $preflight = preflight($db, $db_config, $config, $new_config, $config_file);
            } catch (\Exception $exc) {
                // ข้อตรวจเองล้ม (ฐานหน้าตาแปลกเกินคาด) — ยังไม่ได้แตะอะไร หยุดพร้อมบอกสาเหตุ
                $preflight = ['errors' => ['ตรวจฐานข้อมูลก่อนปรับรุ่นไม่สำเร็จ : <code>'
                    .htmlspecialchars($exc->getMessage(), ENT_QUOTES).'</code>'], 'warnings' => [], 'notes' => [], 'counts' => []];
            }
            if (!empty($preflight['errors'])) {
                $html = preflightHtml($preflight);
                $html .= '<p class="submit"><a href="." class="btn btn-primary large">ลองใหม่</a></p>';
                echo $html;
                $log = writeUpgradeLog(upgradeReportText($html));
                if ($log !== '') {
                    echo '<p class=comment>บันทึกผลการตรวจไว้ที่ <code>'.htmlspecialchars(str_replace(ROOT_PATH, '', $log), ENT_QUOTES).'</code></p>';
                }

                return;
            }

            $content = ['<li class="correct">เชื่อมต่อฐานข้อมูลสำเร็จ</li>'];
            foreach ($preflight['notes'] as $_note) {
                $content[] = '<li class="correct">'.$_note.'</li>';
            }
            foreach ($preflight['warnings'] as $_warn) {
                $content[] = '<li class="warning">'.$_warn.'</li>';
            }
            $counts_before = $preflight['counts'];
            try {
                $prefix = $db_config['prefix'];
                $table_user = $prefix.'_user';
                // db.php เชื่อมต่อด้วย utf8 (3 ไบต์) ข้อความที่มีอีโมจิจะเพี้ยนถ้าอ่าน
                // ผ่าน PHP แล้วเขียนกลับ (การแปลง serialize → JSON ทำแบบนั้น)
                $db->query('SET NAMES utf8mb4');
                if (empty($config['password_key'])) {
                    // สร้างใหม่ได้เฉพาะตอนที่ยังไม่มีรหัสผ่านให้เสียหาย (preflight ตรวจแล้ว)
                    $config['password_key'] = uniqid();
                }
                // GCMS รุ่นเดิมเก็บรหัสผ่านใน varchar(50) — updateAdmin() เก็บรหัสผ่าน
                // bcrypt (60 ตัวอักษร) กลับลงไปทันทีที่ยืนยันผ่าน ถ้ายังไม่ขยายคอลัมน์
                // รหัสผ่านจะถูกตัดเงียบ ๆ แล้วผู้ดูแลเข้าระบบไม่ได้อีกเลย
                ensureColumn($db, $table_user, 'password', 'varchar(255)', false, null, '', 'salt');
                // ตรวจสอบการ login (และเก็บรหัสผ่านใหม่เป็น bcrypt)
                // GCMS รุ่นแรก ๆ ไม่มีคอลัมน์ username (เข้าระบบด้วย email) — ยืนยันจากคอลัมน์เดิม
                // ก่อน แล้วค่อยย้ายเป็น username ใน gcmsLegacyUser() หลังยืนยันผ่านแล้วเท่านั้น
                updateAdmin($db, $table_user, $_POST['username'], $_POST['password'], $config['password_key'], loginColumn($db, $table_user));

                // =========================================================
                // ปรับฐานข้อมูล
                // =========================================================
                $schema = gcmsSchema($prefix);
                gcmsBeforeSync($db, $prefix, $schema, $content);
                // ข้อมูลรุ่นเก่าของแต่ละโมดูล — ก่อนปรับสคีมา (ดู gcmsModuleHooks())
                gcmsModuleHooks($db, $prefix, $schema, $content, $config);
                // แถวที่จะชน PRIMARY KEY ตัวใหม่ ย้ายไปเก็บก่อนปรับดัชนี (ดู gcmsResolveKeyConflicts())
                gcmsResolveKeyConflicts($db, $prefix, $schema, $content);
                $added = gcmsSyncSchema($db, $schema, $content);
                gcmsAfterSync($db, $prefix, $added, $config, $content);
                // คอลัมน์ที่ระบบนี้ไม่รู้จัก (ของรุ่นเดิม) ต้องไม่ขวางการเขียนของระบบนี้
                // ชนิดที่ตั้งค่าเริ่มต้นไม่ได้ (text/date) ให้รับ NULL ก่อน ที่เหลือให้มีค่าเริ่มต้น
                gcmsRelaxForeignColumns($db, $schema, $content);
                ensureForeignColumnsDefault($db, $prefix, $content);
                gcmsSeedRows($db, $schema, $content);

                // =========================================================
                // settings/database.php — ชื่อตารางที่โค้ดเรียกไม่ตรงกับชื่อจริง
                // (ข้ามเมื่อรันจาก cli-upgrade.php --db= เพราะเป็นฐานทดสอบ)
                // =========================================================
                if (!isset($db_config_override)) {
                    gcmsTableMap($db_settings, ROOT_PATH.'settings/database.php', $content);
                }

                // =========================================================
                // บันทึก settings/config.php — ผ่านฟังก์ชันเดียวกับที่ตัวติดตั้งใช้
                // =========================================================
                $config = ensureConfigDefaults($config, $new_config);
                gcmsConfig($config, $content);
                $f = save($config, $config_file);
                $content[] = '<li class="'.($f ? 'correct' : 'incorrect').'">บันทึก <b>config.php</b> ...</li>';
                // นำเข้าภาษา
                include ROOT_PATH.'install/language.php';
            } catch (\PDOException $exc) {
                $content[] = '<li class="incorrect">'.$exc->getMessage().'</li>';
                $error = true;
            } catch (\Exception $exc) {
                $content[] = '<li class="incorrect">'.$exc->getMessage().'</li>';
                $error = true;
            }

            // =================================================================
            // นับใหม่แล้วเทียบให้ผู้ใช้เห็นเองว่าข้อมูลไม่หาย
            // =================================================================
            $counts_after = countRows($db, prefixTables($db, $db_config['prefix']));
            list($count_html, $data_lost) = rowCountHtml($counts_before, $counts_after);
            if ($data_lost) {
                $error = true;
                $content[] = '<li class="incorrect">จำนวนข้อมูลบางตารางลดลงหลังปรับรุ่น '
                    .'กรุณากู้คืนฐานข้อมูลจากไฟล์สำรอง แล้วส่งไฟล์บันทึกผลใน <code>datas/logs/</code> มาให้ผู้พัฒนา</li>';
            }

            $html = '';
            if (!$error) {
                stampMigration($db, $db_config['prefix'], 'core', $new_config['version'], 'upgrade2.php');
                $html .= '<h2>ปรับรุ่นเรียบร้อย</h2>';
                $html .= '<p>การปรับรุ่นได้ดำเนินการเสร็จเรียบร้อยแล้ว หากคุณต้องการความช่วยเหลือในการใช้งาน คุณสามารถ ติดต่อสอบถามได้ที่ <a href="https://www.kotchasan.com" target="_blank">https://www.kotchasan.com</a></p>';
                $html .= '<ul>'.implode('', $content).'</ul>';
                $html .= $count_html;
                $html .= '<p class=warning>กรุณาลบไดเร็คทอรี่ <em>install/</em> ออกจาก Server ของคุณ</p>';
                $html .= '<p>คุณควรปรับ chmod ให้ไดเร็คทอรี่ <em>datas/</em> และ <em>settings/</em> (และไดเร็คทอรี่อื่นๆที่คุณได้ปรับ chmod ไว้ก่อนการปรับรุ่น) ให้เป็น 644 ก่อนดำเนินการต่อ (ถ้าคุณได้ทำการปรับ chmod ไว้ด้วยตัวเอง)</p>';
                $html .= '<p class="submit"><a href="../" class="btn btn-primary large">เข้าระบบ</a></p>';
            } else {
                $html .= '<h2>ปรับรุ่นไม่สำเร็จ</h2>';
                $html .= '<p>การปรับรุ่นยังไม่สมบูรณ์ ตัวปรับรุ่นนี้รันซ้ำได้ ถ้าแก้ข้อผิดพลาดข้างล่างแล้วกดปรับรุ่นใหม่ ระบบจะทำต่อจากจุดที่ค้างไว้ หากคุณต้องการความช่วยเหลือ คุณสามารถ ติดต่อสอบถามได้ที่ <a href="https://www.kotchasan.com" target="_blank">https://www.kotchasan.com</a></p>';
                $html .= '<ul>'.implode('', $content).'</ul>';
                $html .= $count_html;
                $html .= '<p class="submit"><a href="." class="btn btn-primary large">ลองใหม่</a></p>';
            }
            echo $html;

            $log = writeUpgradeLog(upgradeReportText($html));
            if ($log !== '') {
                echo '<p class=comment>บันทึกผลการปรับรุ่นไว้ที่ <code>'.htmlspecialchars(str_replace(ROOT_PATH, '', $log), ENT_QUOTES).'</code></p>';
            }
        }
    }
}

// =============================================================================
// นิยามตารางของตัวติดตั้ง
// =============================================================================

/**
 * อ่านนิยามตารางทั้งหมดจากไฟล์ชุดเดียวกับตัวติดตั้ง
 *
 * CREATE ของตารางเดียวกันที่มาทีหลังทับตัวก่อน (เหมือน DROP + CREATE ตอนติดตั้ง)
 * ALTER TABLE ... ADD จากไฟล์อื่นถูกรวมเข้านิยามของตารางนั้น
 *
 * @param string $prefix
 *
 * @return array ['tables' => [ชื่อ => นิยาม], 'inserts' => [ชื่อ => [คำสั่ง]]]
 */
function gcmsSchema($prefix)
{
    $schema = ['tables' => [], 'inserts' => []];
    foreach (schemaFiles() as $file) {
        foreach (sqlCommands($file, $prefix) as $command) {
            $command = rtrim(trim($command), ';');
            if (preg_match('/^CREATE\s+TABLE\s+(?:IF\s+NOT\s+EXISTS\s+)?`([^`]+)`\s*\((.*)\)([^()]*)$/is', $command, $match)) {
                $schema['tables'][$match[1]] = gcmsParseCreate($command, $match[2]);
            } elseif (preg_match('/^ALTER\s+TABLE\s+`([^`]+)`\s+(.*)$/is', $command, $match)) {
                if (isset($schema['tables'][$match[1]])) {
                    gcmsParseDefinitions($schema['tables'][$match[1]], $match[2], true);
                }
            } elseif (preg_match('/^INSERT\s+INTO\s+`([^`]+)`/i', $command, $match)) {
                $schema['inserts'][$match[1]][] = $command;
            }
        }
    }

    return $schema;
}

/**
 * @param string $sql  คำสั่ง CREATE TABLE ทั้งคำสั่ง
 * @param string $body เนื้อในวงเล็บ
 *
 * @return array ['create' => sql, 'columns' => [ชื่อ => นิยาม], 'indexes' => [ชื่อ => ['kind', 'columns']]]
 */
function gcmsParseCreate($sql, $body)
{
    $table = ['create' => $sql, 'columns' => [], 'indexes' => []];
    gcmsParseDefinitions($table, $body, false);

    return $table;
}

/**
 * แยกนิยามคอลัมน์และดัชนีทีละบรรทัด (sqlCommands() ให้บรรทัดละหนึ่งนิยาม)
 *
 * @param array  $table
 * @param string $body
 * @param bool   $alter true = เนื้อของ ALTER TABLE (ขึ้นต้นด้วย ADD)
 */
function gcmsParseDefinitions(array &$table, $body, $alter)
{
    foreach (preg_split('/\R/', $body) as $line) {
        $line = trim(rtrim(trim($line), ','));
        if ($alter) {
            if (!preg_match('/^ADD\s+(?:COLUMN\s+)?(.*)$/i', $line, $match)) {
                continue;
            }
            $line = $match[1];
        }
        if ($line === '') {
            continue;
        }
        if (preg_match('/^`([^`]+)`\s+(.+)$/', $line, $match)) {
            $table['columns'][$match[1]] = $match[2];
        } elseif (preg_match('/^PRIMARY\s+KEY\s*\(([^)]+)\)/i', $line, $match)) {
            $table['indexes']['PRIMARY'] = ['kind' => 'PRIMARY', 'columns' => gcmsIndexColumns($match[1])];
        } elseif (preg_match('/^(UNIQUE|FULLTEXT)?\s*(?:KEY|INDEX)\s+`([^`]+)`\s*\(([^)]+)\)/i', $line, $match)) {
            $kind = $match[1] === '' ? 'KEY' : strtoupper($match[1]);
            $table['indexes'][$match[2]] = ['kind' => $kind, 'columns' => gcmsIndexColumns($match[3])];
        }
    }
}

/**
 * @param string $sql เช่น "`module_id`,`index`"
 *
 * @return array
 */
function gcmsIndexColumns($sql)
{
    preg_match_all('/`([^`]+)`/', $sql, $match);

    return $match[1];
}

// =============================================================================
// อ่านสภาพจริงของฐานข้อมูล
// =============================================================================

/**
 * @param Db     $db
 * @param string $table
 *
 * @return array [ชื่อคอลัมน์ => แถวของ SHOW FULL COLUMNS] เรียงตามลำดับในตาราง
 */
function gcmsColumns($db, $table)
{
    $columns = [];
    foreach ($db->customQuery("SHOW FULL COLUMNS FROM `$table`", true) as $row) {
        $columns[$row['Field']] = $row;
    }

    return $columns;
}

/**
 * @param Db     $db
 * @param string $table
 *
 * @return array [ชื่อดัชนี => ['kind' => PRIMARY|UNIQUE|FULLTEXT|KEY, 'columns' => [...]]]
 */
function gcmsIndexes($db, $table)
{
    $indexes = [];
    foreach ($db->customQuery("SHOW INDEX FROM `$table`", true) as $row) {
        $name = $row['Key_name'];
        if (!isset($indexes[$name])) {
            if ($name === 'PRIMARY') {
                $kind = 'PRIMARY';
            } elseif (strtoupper((string) $row['Index_type']) === 'FULLTEXT') {
                $kind = 'FULLTEXT';
            } else {
                $kind = (int) $row['Non_unique'] === 0 ? 'UNIQUE' : 'KEY';
            }
            $indexes[$name] = ['kind' => $kind, 'columns' => []];
        }
        $indexes[$name]['columns'][(int) $row['Seq_in_index']] = $row['Column_name'];
    }
    foreach ($indexes as $name => $index) {
        ksort($index['columns']);
        $indexes[$name]['columns'] = array_values($index['columns']);
    }

    return $indexes;
}

/**
 * แยกนิยามคอลัมน์เป็นส่วน ๆ ไว้เทียบกับของจริง
 *
 * @param string $definition เช่น "varchar(50) NOT NULL DEFAULT ''"
 *
 * @return array ['type', 'nullable', 'default' (null = ไม่มี/NULL), 'auto_increment', 'on_update']
 */
function gcmsParseColumn($definition)
{
    $definition = trim($definition);
    $type = $definition;
    if (preg_match('/^(.+?)(?=\s+(?:NOT\s+NULL|NULL|DEFAULT|AUTO_INCREMENT|ON\s+UPDATE|COMMENT|CHARACTER\s+SET|COLLATE)\b|$)/is', $definition, $match)) {
        $type = $match[1];
    }
    $default = null;
    if (preg_match("/\bDEFAULT\s+('(?:[^']|'')*'|[^\s]+)/i", $definition, $match)) {
        $default = gcmsNormalizeDefault($match[1]);
    }

    return [
        'type' => gcmsNormalizeType($type),
        'nullable' => !preg_match('/\bNOT\s+NULL\b/i', $definition),
        'default' => $default,
        'auto_increment' => (bool) preg_match('/\bAUTO_INCREMENT\b/i', $definition),
        'on_update' => (bool) preg_match('/\bON\s+UPDATE\b/i', $definition)
    ];
}

/**
 * ตัดความกว้างของ integer ออกก่อนเทียบ — MySQL 8.0.19+ ไม่รายงาน int(11) แล้ว
 * แต่ MariaDB ยังรายงาน ถ้าไม่ตัดจะ ALTER ซ้ำทุกครั้งที่ปรับรุ่น
 *
 * @param string $type
 *
 * @return string
 */
function gcmsNormalizeType($type)
{
    $type = strtolower(preg_replace('/\s+/', ' ', trim($type)));

    return preg_replace('/\b(tinyint|smallint|mediumint|int|bigint)\(\d+\)/', '$1', $type);
}

/**
 * @param mixed $value ค่า DEFAULT จาก SQL หรือจาก SHOW COLUMNS
 *
 * @return string|null
 */
function gcmsNormalizeDefault($value)
{
    if ($value === null) {
        return null;
    }
    $value = trim((string) $value);
    if (strcasecmp($value, 'NULL') === 0) {
        return null;
    }
    if (strlen($value) >= 2 && $value[0] === "'" && substr($value, -1) === "'") {
        $value = str_replace("''", "'", substr($value, 1, -1));
    }
    if (preg_match('/^current_timestamp(\(\))?$/i', $value)) {
        return 'current_timestamp';
    }

    return $value;
}

/**
 * คอลัมน์ตรงกับนิยามหรือยัง
 *
 * @param array  $current    แถวของ SHOW FULL COLUMNS
 * @param string $definition
 *
 * @return bool
 */
function gcmsColumnMatches(array $current, $definition)
{
    $target = gcmsParseColumn($definition);
    $extra = strtolower((string) $current['Extra']);

    return gcmsNormalizeType($current['Type']) === $target['type']
        && ($current['Null'] === 'YES') === $target['nullable']
        && gcmsNormalizeDefault($current['Default']) === $target['default']
        && (strpos($extra, 'auto_increment') !== false) === $target['auto_increment']
        && (strpos($extra, 'on update') !== false) === $target['on_update'];
}

/**
 * @param string $type
 *
 * @return bool
 */
function gcmsIsNumericType($type)
{
    return (bool) preg_match('/^(tinyint|smallint|mediumint|int|bigint|decimal|float|double)\b/i', trim($type));
}

// =============================================================================
// ขั้นที่ 4 : ย้ายข้อมูลรุ่นเก่าก่อนปรับสคีมา
// =============================================================================

/**
 * @param Db     $db
 * @param string $prefix
 * @param array  $schema
 * @param array  $content
 */
function gcmsBeforeSync($db, $prefix, array $schema, array &$content)
{
    gcmsLegacyUser($db, $prefix, $content);
    gcmsLegacyLogs($db, $prefix, $content);
    gcmsLegacyTimestamps($db, $schema, $content);

    // category : ตารางรุ่นเดิมไม่มี type/language ซึ่งเป็นส่วนหนึ่งของ UNIQUE ใหม่
    $table = $prefix.'_category';
    if ($db->tableExists($table) && $db->fieldExists($table, 'module_id')) {
        if (!$db->fieldExists($table, 'type')) {
            $db->query("ALTER TABLE `$table` ADD `type` varchar(20) NOT NULL DEFAULT 'category'");
            $content[] = '<li class="correct">'.$table.': เพิ่มคอลัมน์ type</li>';
        }
        $db->query("UPDATE `$table` SET `type` = 'category' WHERE `type` IS NULL OR `type` = ''");
    }

    // menus : ตำแหน่งเมนูรุ่นเดิมไม่มีเลขนำหน้า ต้องแปลงก่อนเปลี่ยนเป็น ENUM
    // ไม่งั้นทุกค่าจะกลายเป็น '' แล้วเมนูหายทั้งเว็บ
    $table = $prefix.'_menus';
    if ($db->tableExists($table) && $db->fieldExists($table, 'parent')) {
        $moved = 0;
        foreach (['MAINMENU' => '0_MAINMENU', 'SIDEMENU' => '1_SIDEMENU', 'BOTTOMMENU' => '2_BOTTOMMENU'] as $from => $to) {
            $moved += gcmsAffected($db, "UPDATE `$table` SET `parent` = '$to' WHERE `parent` = '$from'");
        }
        if ($moved > 0) {
            $content[] = '<li class="correct">'.$table.': แปลงตำแหน่งเมนู '.$moved.' รายการ</li>';
        }
    }

    // tags : รวมป้ายกำกับที่ซ้ำกันก่อนสร้าง UNIQUE (นับรวมจำนวนการใช้งาน)
    $table = $prefix.'_tags';
    if ($db->tableExists($table) && !$db->indexExists($table, 'tag')) {
        // ตารางย่อยซ้อนสองชั้นบังคับให้ MySQL สร้างตารางชั่วคราว
        // (ไม่งั้นจะล้มด้วย "You can't specify target table for update")
        $dupes = "SELECT * FROM (SELECT `tag`, MIN(`id`) AS `keep`, SUM(`count`) AS `total`
                  FROM `$table` GROUP BY `tag` HAVING COUNT(*) > 1) AS X";
        $has_dupes = $db->customQuery($dupes.' LIMIT 1');
        if (!empty($has_dupes)) {
            // แถวซ้ำย้ายไปเก็บในตารางสำรอง ไม่ลบทิ้ง (รายงานจำนวนแถวจะเห็นว่าย้ายไปไหน)
            $backup = gcmsFreeTableName($db, $prefix.'_tags_duplicate_legacy');
            $db->query("CREATE TABLE `$backup` LIKE `$table`");
            $n = gcmsAffected($db, "INSERT INTO `$backup` SELECT T.* FROM `$table` T INNER JOIN ($dupes) D ON D.`tag` = T.`tag` AND T.`id` != D.`keep`");
            $db->query("UPDATE `$table` T INNER JOIN ($dupes) D ON D.`keep` = T.`id` SET T.`count` = D.`total`");
            $db->query("DELETE T FROM `$table` T INNER JOIN `$backup` B ON B.`id` = T.`id`");
            noteRowsMoved($table, $backup, $n);
            $content[] = '<li class="correct">'.$table.': รวมป้ายกำกับที่ซ้ำกัน (แถวซ้ำ '.$n.' แถวเก็บไว้ที่ '.$backup.')</li>';
        }
    }

    // language : owner/js เป็นข้อมูลภายในของรุ่นเดิม (ตัวปรับรุ่นของแกนก็ลบเช่นกัน)
    // คอลัมน์ภาษาอื่น (เช่น ja) เป็นคำแปลของผู้ใช้ เก็บไว้
    $table = $prefix.'_language';
    if ($db->tableExists($table)) {
        foreach (['owner', 'js'] as $column) {
            if ($db->fieldExists($table, $column)) {
                $db->query("ALTER TABLE `$table` DROP COLUMN `$column`");
                $content[] = '<li class="correct">'.$table.': ลบคอลัมน์ '.$column.'</li>';
            }
        }
    }
}

/**
 * user ของ GCMS รุ่นเดิม → user ของ install/core.sql
 *
 * @param Db     $db
 * @param string $prefix
 * @param array  $content
 */
function gcmsLegacyUser($db, $prefix, array &$content)
{
    $table = $prefix.'_user';
    if (!$db->tableExists($table)) {
        return;
    }
    // email → username : GCMS รุ่นแรก ๆ ไม่มี username สมาชิกเข้าระบบด้วย email
    // เปลี่ยนชื่อคอลัมน์ (ชนิดเดิม ข้อมูลอยู่ครบ) ให้ gcmsSyncSchema() ปรับชนิดทีหลัง
    // preflight-module.php ตรวจแล้วว่าไม่มีค่าซ้ำและไม่ยาวเกิน username
    if (!$db->fieldExists($table, 'username') && $db->fieldExists($table, 'email')) {
        $type = gcmsColumns($db, $table)['email']['Type'];
        $db->query("ALTER TABLE `$table` CHANGE `email` `username` $type NULL DEFAULT NULL");
        $content[] = '<li class="correct">'.$table.': ย้าย email (ชื่อเข้าระบบของรุ่นเดิม) → username</li>';
    }
    // address1 → address (รุ่นแรก ๆ ใช้ชื่อ address1)
    if ($db->fieldExists($table, 'address1')) {
        if (!$db->fieldExists($table, 'address')) {
            $type = gcmsColumns($db, $table)['address1']['Type'];
            $db->query("ALTER TABLE `$table` CHANGE `address1` `address` $type NULL DEFAULT NULL");
            $content[] = '<li class="correct">'.$table.': ย้าย address1 → address</li>';
        } else {
            // มีทั้งคู่ — เติมเฉพาะแถวที่ address ยังว่าง address1 เก็บไว้ตามเดิม
            $n = gcmsAffected($db, "UPDATE `$table` SET `address` = `address1` WHERE (`address` IS NULL OR `address` = '') AND `address1` IS NOT NULL AND `address1` != ''");
            if ($n > 0) {
                $content[] = '<li class="correct">'.$table.': คัดลอก address1 → address '.$n.' รายการ</li>';
            }
        }
    }
    // idcard → id_card
    if ($db->fieldExists($table, 'idcard')) {
        if (!$db->fieldExists($table, 'id_card')) {
            $db->query("ALTER TABLE `$table` CHANGE `idcard` `id_card` varchar(13) NULL DEFAULT NULL");
        } else {
            $db->query("UPDATE `$table` SET `id_card` = `idcard` WHERE (`id_card` IS NULL OR `id_card` = '') AND `idcard` IS NOT NULL AND `idcard` != ''");
            $db->query("ALTER TABLE `$table` DROP COLUMN `idcard`");
        }
        $content[] = '<li class="correct">'.$table.': ย้าย idcard → id_card</li>';
    }
    // create_date (UNIX) → created_at (datetime)
    if ($db->fieldExists($table, 'create_date')) {
        if (!$db->fieldExists($table, 'created_at')) {
            $db->query("ALTER TABLE `$table` ADD `created_at` datetime NULL DEFAULT NULL");
        }
        if (gcmsIsNumericType(gcmsColumns($db, $table)['create_date']['Type'])) {
            $db->query("UPDATE `$table` SET `created_at` = FROM_UNIXTIME(`create_date`) WHERE `created_at` IS NULL AND `create_date` > 0");
        } else {
            $db->query("UPDATE `$table` SET `created_at` = `create_date` WHERE `created_at` IS NULL");
        }
        $db->query("ALTER TABLE `$table` DROP COLUMN `create_date`");
        $content[] = '<li class="correct">'.$table.': ย้าย create_date → created_at</li>';
    }
    // social : 0-4 → ชื่อผู้ให้บริการ (ก่อนเปลี่ยนเป็น ENUM)
    if ($db->fieldExists($table, 'social') && gcmsIsNumericType(gcmsColumns($db, $table)['social']['Type'])) {
        $db->query("ALTER TABLE `$table` CHANGE `social` `social` varchar(32) NULL DEFAULT NULL");
        foreach (['0' => 'user', '1' => 'facebook', '2' => 'google', '3' => 'line', '4' => 'telegram'] as $from => $to) {
            $db->query("UPDATE `$table` SET `social` = '$to' WHERE `social` = '$from'");
        }
        $db->query("UPDATE `$table` SET `social` = 'user' WHERE `social` IS NULL OR `social` NOT IN ('user','facebook','google','line','telegram')");
        $content[] = '<li class="correct">'.$table.': แปลง social เป็นชื่อผู้ให้บริการ</li>';
    }
    // token : ย้ายไปเก็บที่ user_session แล้ว (เหมือนตัวปรับรุ่นของ adminframework)
    if ($db->fieldExists($table, 'token')) {
        $db->query("ALTER TABLE `$table` DROP COLUMN `token`");
        $content[] = '<li class="correct">'.$table.': ลบ token (ย้ายไป user_session)</li>';
    }
    // phone2 (เบอร์ที่สองของรุ่นเดิม) → phone1 · phone2 เก็บไว้ตามเดิม
    if ($db->fieldExists($table, 'phone2')) {
        if (!$db->fieldExists($table, 'phone1')) {
            $db->query("ALTER TABLE `$table` ADD `phone1` varchar(20) NULL DEFAULT NULL");
        }
        $db->query("UPDATE `$table` SET `phone1` = `phone2` WHERE (`phone1` IS NULL OR `phone1` = '') AND `phone2` IS NOT NULL AND `phone2` != ''");
    }
    // คอลัมน์ที่จะเป็น UNIQUE/ดัชนี ค่าว่างต้องเป็น NULL (NULL ซ้ำได้ '' ซ้ำไม่ได้)
    // ต้องให้คอลัมน์รับ NULL ได้ก่อน — รุ่นเดิมบางคอลัมน์เป็น NOT NULL
    $columns = gcmsColumns($db, $table);
    foreach (['username', 'id_card', 'phone', 'line_uid', 'telegram_id'] as $column) {
        if (!isset($columns[$column])) {
            continue;
        }
        if ($columns[$column]['Null'] === 'NO') {
            $db->query("ALTER TABLE `$table` MODIFY `$column` {$columns[$column]['Type']} NULL DEFAULT NULL");
        }
        $db->query("UPDATE `$table` SET `$column` = NULL WHERE `$column` = ''");
    }
}

/**
 * logs ของรุ่นเดิมคือ "บันทึกการเข้าชม" (time, ip, session_id ...) คนละตารางกับ
 * logs ของระบบนี้ซึ่งเป็นบันทึกกิจกรรม — เปลี่ยนชื่อเก็บไว้ ไม่ลบ
 *
 * @param Db     $db
 * @param string $prefix
 * @param array  $content
 */
function gcmsLegacyLogs($db, $prefix, array &$content)
{
    $table = $prefix.'_logs';
    if (!$db->tableExists($table) || $db->fieldExists($table, 'src_id')
        || !$db->fieldExists($table, 'session_id')) {
        return;
    }
    $count = getTableRowCount($db, $table);
    $backup = gcmsFreeTableName($db, $prefix.'_logs_access_legacy');
    $db->query("RENAME TABLE `$table` TO `$backup`");
    noteRowsMoved($table, $backup, $count);
    $content[] = '<li class="correct">'.$table.': เก็บบันทึกการเข้าชมของรุ่นเดิมไว้ที่ '.$backup.'</li>';
}

/**
 * วันที่ของรุ่นเดิม (create_date/last_update เป็น UNIX timestamp) → created_at/updated_at
 *
 * ทำเฉพาะตารางที่นิยามใหม่ "ไม่มี" คอลัมน์ต้นทางแล้ว — ตารางที่ยังใช้ last_update
 * (video, gallery_album, eventcalendar, edocument) ไม่ถูกแตะ
 *
 * @param Db    $db
 * @param array $schema
 * @param array $content
 */
function gcmsLegacyTimestamps($db, array $schema, array &$content)
{
    foreach ($schema['tables'] as $table => $def) {
        if (!$db->tableExists($table) || substr($table, -5) === '_user') {
            continue;
        }
        foreach (['create_date' => 'created_at', 'last_update' => 'updated_at'] as $from => $to) {
            if (isset($def['columns'][$from]) || !isset($def['columns'][$to]) || !$db->fieldExists($table, $from)) {
                continue;
            }
            gcmsMoveColumn($db, $table, $from, $to, $def['columns'][$to]);
            $content[] = '<li class="correct">'.$table.': ย้าย '.$from.' → '.$to.'</li>';
        }
        // comment_date เป็นเลข UNIX ในรุ่นเดิม แต่นิยามใหม่เป็น datetime
        if (isset($def['columns']['comment_date']) && $db->fieldExists($table, 'comment_date')) {
            $current = gcmsColumns($db, $table)['comment_date'];
            $target = gcmsParseColumn($def['columns']['comment_date']);
            if (gcmsIsNumericType($current['Type']) && !gcmsIsNumericType($target['type'])) {
                $temp = '__gcms_comment_date';
                if (!$db->fieldExists($table, $temp)) {
                    $db->query("ALTER TABLE `$table` ADD `$temp` datetime NULL DEFAULT NULL");
                }
                $db->query("UPDATE `$table` SET `$temp` = FROM_UNIXTIME(`comment_date`) WHERE `comment_date` > 0");
                $db->query("ALTER TABLE `$table` DROP COLUMN `comment_date`");
                $db->query("ALTER TABLE `$table` CHANGE `$temp` `comment_date` datetime NULL DEFAULT NULL");
                $content[] = '<li class="correct">'.$table.': แปลง comment_date เป็นวันที่</li>';
            }
        }
    }
}

/**
 * ย้ายค่าจากคอลัมน์เก่าไปคอลัมน์ใหม่ (แปลง UNIX timestamp ถ้าจำเป็น) แล้วลบคอลัมน์เก่า
 *
 * คอลัมน์ใหม่ถูกเพิ่มแบบรับ NULL ก่อน ให้ gcmsSyncSchema() ปรับเป็นนิยามจริงทีหลัง
 * (เติมค่าให้แถวที่ว่างก่อนเปลี่ยนเป็น NOT NULL)
 *
 * @param Db     $db
 * @param string $table
 * @param string $from
 * @param string $to
 * @param string $definition นิยามของคอลัมน์ปลายทาง
 */
function gcmsMoveColumn($db, $table, $from, $to, $definition)
{
    $target = gcmsParseColumn($definition);
    $columns = gcmsColumns($db, $table);
    $source_numeric = gcmsIsNumericType($columns[$from]['Type']);
    if (!isset($columns[$to])) {
        $db->query("ALTER TABLE `$table` ADD `$to` {$target['type']} NULL DEFAULT NULL");
    }
    if (preg_match('/^(datetime|timestamp)/', $target['type'])) {
        $value = $source_numeric ? "FROM_UNIXTIME(`$from`)" : "`$from`";
        $where = $source_numeric ? "`$from` > 0" : "`$from` IS NOT NULL";
    } elseif (preg_match('/^date\b/', $target['type'])) {
        $value = $source_numeric ? "DATE(FROM_UNIXTIME(`$from`))" : "DATE(`$from`)";
        $where = $source_numeric ? "`$from` > 0" : "`$from` IS NOT NULL";
    } else {
        $value = "`$from`";
        $where = "`$from` IS NOT NULL";
    }
    $db->query("UPDATE `$table` SET `$to` = $value WHERE `$to` IS NULL AND $where");
    $db->query("ALTER TABLE `$table` DROP COLUMN `$from`");
}

// =============================================================================
// ขั้นที่ 4 (ต่อ) : hook ของโมดูล
// =============================================================================

/**
 * รัน modules/<ชื่อ>/install/upgrade.php ของทุกโมดูลที่มีไฟล์นี้ (เรียงตามชื่อโฟลเดอร์)
 *
 * เรียกหลัง gcmsBeforeSync() และ "ก่อน" gcmsSyncSchema() — hook มีไว้ย้ายหรือแปลง
 * ข้อมูลรุ่นเก่าของโมดูล (เช่นตาราง product ของ GCMS 11) ให้อยู่ในรูปที่นิยามใหม่
 * รับได้ ส่วน "หน้าตา" ของตาราง (สร้างตารางที่ขาด เพิ่ม/แก้คอลัมน์ ดัชนี InnoDB/utf8mb4)
 * gcmsSyncSchema() ทำให้เองจาก modules/<ชื่อ>/install/database.sql ทันทีหลัง hook
 * hook จึงไม่ต้อง (และไม่ควร) ALTER ตารางให้ตรงนิยามเอง
 *
 * ตัวแปรที่ hook ใช้ได้ (ไฟล์ถูก include ใน closure ของตัวเอง ตัวแปรอื่นของตัวปรับรุ่นมองไม่เห็น)
 *   $db       Db (install/db.php) เชื่อมต่อฐานของไซต์แล้ว SET NAMES utf8mb4 แล้ว
 *             tableExists() fieldExists() indexExists() query() customQuery() first() insert() update()
 *   $prefix   คำนำหน้าตาราง เช่น 'gcms' — อ้างตารางด้วย $prefix.'_ชื่อ' เสมอ
 *   $schema   นิยามจากตัวติดตั้ง (อ่านอย่างเดียว) ['tables' => [ชื่อเต็ม => ['create' => SQL,
 *             'columns' => [คอลัมน์ => นิยาม], 'indexes' => [...]]], 'inserts' => [...]]
 *   $content  รายการผลลัพธ์ เพิ่มบรรทัดด้วย $content[] = '<li class="correct">...</li>'
 *             (correct = ทำแล้ว, warning = ต้องให้ผู้ดูแลรู้) — รายงานเฉพาะเมื่อทำอะไรจริง
 *   $config   ค่ากำหนดของไซต์ (settings/config.php) แก้ได้ จะถูกบันทึกตอนท้ายการปรับรุ่น
 *
 * เครื่องมือที่มีให้ : gcmsColumns() gcmsMoveColumn() gcmsFreeTableName() gcmsAffected()
 * gcmsSerializedToJson() gcmsJson() getTableRowCount() columnInfo() ensureColumn()
 * noteRowsMoved() noteTableDropped() (install/preflight.php)
 *
 * กติกาของ hook
 *   • ต้องรันซ้ำได้ (idempotent) — ตัวปรับรุ่นถูกรันซ้ำทั้งตอนรุ่นเดียวกันและตอนรันต่อ
 *     จากรอบที่ค้าง ทุกเงื่อนไขต้อง "ถามว่าต้องทำไหม ไม่ใช่ถามว่าตอนนี้เป็นอะไร"
 *     เช่น "มีตาราง/คอลัมน์รุ่นเก่าเหลืออยู่ไหม" ไม่ใช่ "คอลัมน์เป็น int อยู่ไหม" ซึ่งยัง
 *     จริงหลังแปลงเสร็จแล้วทำให้แปลงซ้ำทุกรอบ รอบที่สองต้องไม่มีบรรทัดใน $content เลย
 *   • ห้ามลบข้อมูล — ตารางรุ่นเก่าที่ต้องหลบทางให้ตารางใหม่ ให้ RENAME ไปชื่อที่ได้จาก
 *     gcmsFreeTableName() แล้วแจ้ง noteRowsMoved() ไม่งั้นรายงานจำนวนแถวจะตัดสินว่าข้อมูลหาย
 *   • ถ้าต้องคัดลอกข้อมูลเข้าตารางใหม่ที่ยังไม่มี ให้สร้างเองจาก
 *     $schema['tables'][ชื่อเต็ม]['create'] ก่อน (gcmsSyncSchema() จะข้ามการสร้างและปรับส่วนที่เหลือ)
 *   • ไม่มีอะไรต้องทำก็ return; ได้เลย · ผิดพลาดให้ throw \Exception — การปรับรุ่นหยุดและรายงานว่า hook ไหนล้ม
 *   • ถ้าประกาศฟังก์ชัน ให้ขึ้นต้นด้วยชื่อโมดูลและครอบด้วย function_exists()
 *
 * @param Db     $db
 * @param string $prefix
 * @param array  $schema
 * @param array  $content
 * @param array  $config
 */
function gcmsModuleHooks($db, $prefix, array $schema, array &$content, array &$config)
{
    // แต่ละ hook ได้ขอบเขตตัวแปรของตัวเอง — hook หนึ่งตั้ง $db หรือ $table ทับ
    // ต้องไม่กระทบ hook ถัดไป (มองเห็นเฉพาะตัวแปรที่ประกาศไว้ข้างบน)
    $run = static function ($__hook, $db, $prefix, array $schema, array &$content, array &$config) {
        include $__hook;
    };
    foreach (glob(ROOT_PATH.'modules/*/install/upgrade.php') ?: [] as $file) {
        try {
            $run($file, $db, $prefix, $schema, $content, $config);
        } catch (\Throwable $exc) {
            // \Throwable ด้วย — TypeError/Error ของ hook ต้องกลายเป็นรายงาน "ปรับรุ่นไม่สำเร็จ"
            // ไม่ใช่หน้าขาวกลางการปรับรุ่น
            throw new \Exception(str_replace(ROOT_PATH, '', $file).' : '.$exc->getMessage(), 0, $exc);
        }
    }
}

// =============================================================================
// ขั้นที่ 5 : ปรับทุกตารางให้ตรงกับนิยามของตัวติดตั้ง
// =============================================================================

/**
 * @param Db    $db
 * @param array $schema
 * @param array $content
 *
 * @return array คอลัมน์ที่เพิ่งเพิ่ม [ตาราง => [คอลัมน์ => true]]
 */
function gcmsSyncSchema($db, array $schema, array &$content)
{
    $added = [];
    foreach ($schema['tables'] as $table => $def) {
        if (!$db->tableExists($table)) {
            $db->query($def['create']);
            $content[] = '<li class="correct">'.$table.': สร้างตารางใหม่</li>';
        }
    }
    foreach ($schema['tables'] as $table => $def) {
        $added[$table] = gcmsSyncTable($db, $table, $def, $content);
    }

    return $added;
}

/**
 * @param Db     $db
 * @param string $table
 * @param array  $def
 * @param array  $content
 *
 * @return array คอลัมน์ที่เพิ่งเพิ่ม
 */
function gcmsSyncTable($db, $table, array $def, array &$content)
{
    $changes = [];
    // -------------------------------------------------------------------------
    // 1. ดัชนี FULLTEXT ที่ไม่อยู่ในนิยาม (รุ่นเดิมมี topic_2, topic_3, detail_2 ...)
    //    และทุก FULLTEXT ของตาราง MyISAM ต้องลบก่อนแปลงเป็น InnoDB
    //    ("InnoDB presently supports one FULLTEXT index creation at a time")
    //    ตัวที่อยู่ในนิยามจะถูกสร้างกลับในข้อ 4 ทีละตัว
    // -------------------------------------------------------------------------
    $status = $db->customQuery("SHOW TABLE STATUS LIKE '".addslashes($table)."'", true);
    $myisam = !empty($status) && strcasecmp((string) $status[0]['Engine'], 'InnoDB') !== 0;
    foreach (gcmsIndexes($db, $table) as $name => $index) {
        if ($index['kind'] === 'FULLTEXT' && ($myisam || !isset($def['indexes'][$name]))) {
            $db->query("ALTER TABLE `$table` DROP INDEX `$name`");
        }
    }
    if (convertToInnoDB($db, $table)) {
        $changes[] = 'InnoDB';
    }
    if (convertToUtf8mb4($db, $table)) {
        $changes[] = 'utf8mb4';
    }

    // -------------------------------------------------------------------------
    // 2. คอลัมน์ — เพิ่มตัวที่ขาด (ตามตำแหน่งเดียวกับติดตั้งใหม่) และปรับตัวที่ไม่ตรง
    // -------------------------------------------------------------------------
    $added = [];
    $current = gcmsColumns($db, $table);
    $previous = '';
    foreach ($def['columns'] as $column => $definition) {
        $target = gcmsParseColumn($definition);
        if (!isset($current[$column])) {
            if ($target['auto_increment']) {
                // AUTO_INCREMENT ต้องมีดัชนีคลุมก่อน — ไม่มีกรณีนี้ใน GCMS ข้ามไว้กันพัง
                continue;
            }
            $position = $previous === '' ? ' FIRST' : " AFTER `$previous`";
            $db->query("ALTER TABLE `$table` ADD `$column` $definition".$position);
            $added[$column] = true;
            $changes[] = '+'.$column;
        } elseif (!gcmsColumnMatches($current[$column], $definition)) {
            if (!$target['nullable']) {
                gcmsFillNulls($db, $table, $column, $target);
            }
            if ($target['auto_increment'] && !$db->indexExists($table, 'PRIMARY')) {
                $db->query("ALTER TABLE `$table` ADD PRIMARY KEY (`$column`)");
            }
            $db->query("ALTER TABLE `$table` MODIFY `$column` $definition");
            $changes[] = $column;
        }
        $previous = $column;
    }

    // -------------------------------------------------------------------------
    // 3-4. ดัชนี — PRIMARY ก่อน แล้วดัชนีอื่น (FULLTEXT ทีละคำสั่ง)
    //      ดัชนีอื่นที่ไม่อยู่ในนิยามและไม่ใช่ FULLTEXT เก็บไว้ (อาจเป็นของผู้ดูแลเอง)
    // -------------------------------------------------------------------------
    $indexes = gcmsIndexes($db, $table);
    foreach ($def['indexes'] as $name => $want) {
        $have = isset($indexes[$name]) ? $indexes[$name] : null;
        if ($have !== null && $have['kind'] === $want['kind'] && $have['columns'] === $want['columns']) {
            continue;
        }
        $columns = '`'.implode('`,`', $want['columns']).'`';
        if ($name === 'PRIMARY') {
            $db->query("ALTER TABLE `$table` ".($have === null ? '' : 'DROP PRIMARY KEY, ')."ADD PRIMARY KEY ($columns)");
        } else {
            if ($have !== null) {
                $db->query("ALTER TABLE `$table` DROP INDEX `$name`");
            }
            $kind = $want['kind'] === 'KEY' ? 'INDEX' : $want['kind'].' INDEX';
            $db->query("ALTER TABLE `$table` ADD $kind `$name` ($columns)");
        }
        $changes[] = 'index '.$name;
    }

    if (!empty($changes)) {
        $content[] = '<li class="correct">'.$table.': ปรับ '.htmlspecialchars(implode(', ', $changes), ENT_QUOTES).'</li>';
    }

    return $added;
}

/**
 * เติมค่าให้แถวที่เป็น NULL ก่อนเปลี่ยนคอลัมน์เป็น NOT NULL
 * (ไม่เติมเอง MySQL จะใส่ค่าปริยายตามชนิด ซึ่งกับวันที่คือ 0000-00-00)
 *
 * @param Db     $db
 * @param string $table
 * @param string $column
 * @param array  $target ผลของ gcmsParseColumn()
 */
function gcmsFillNulls($db, $table, $column, array $target)
{
    if ($target['auto_increment']) {
        return;
    }
    $value = gcmsFillValue($target);
    $db->query("UPDATE `$table` SET `$column` = $value WHERE `$column` IS NULL");
}

/**
 * ค่าที่ใช้แทน NULL เมื่อคอลัมน์กำลังจะเป็น NOT NULL (นิพจน์ SQL)
 *
 * @param array $target ผลของ gcmsParseColumn()
 *
 * @return string
 */
function gcmsFillValue(array $target)
{
    if ($target['default'] === 'current_timestamp' || preg_match('/^(datetime|timestamp)/', $target['type'])) {
        return 'NOW()';
    }
    if (preg_match('/^date\b/', $target['type'])) {
        return 'CURDATE()';
    }
    if ($target['default'] !== null) {
        return "'".str_replace("'", "''", $target['default'])."'";
    }

    return gcmsIsNumericType($target['type']) ? '0' : "''";
}

// =============================================================================
// ขั้นที่ 4 (ท้าย) : แถวที่จะชนกับ PRIMARY KEY / UNIQUE ตัวใหม่
//
// ⚠️ เกิดจริงกับไซต์ GCMS 11 รุ่นแรก ๆ : index_detail เดิมมี PRIMARY KEY (id, module_id,
// language) และมีแถวกำพร้าที่ id ซ้ำแต่คนละ module_id — พอตัวปรับรุ่นเปลี่ยนเป็น
// PRIMARY KEY (id, language) ตามสคีมาใหม่ ALTER ล้มด้วย "Duplicate entry '42-'" กลางทาง
//
// ตรวจทุกตารางทุกดัชนีแบบเดียวกัน ไม่ผูกกับ index_detail : ดัชนี PRIMARY/UNIQUE ใน
// สคีมาที่ตารางจริงยังไม่ได้บังคับ (ชื่อ ชนิด หรือคอลัมน์ต่าง) = ข้อมูลเดิมอาจชน
// จัดกลุ่มแบบเดียวกับที่ดัชนีใหม่จะเทียบ : NULL ของคอลัมน์ที่จะเป็น NOT NULL คือค่าที่จะ
// ถูกเติม, NULL ของ UNIQUE ที่รับ NULL ได้ไม่ชนกัน, ข้อความเทียบด้วย utf8mb4_general_ci
// (collation หลังแปลงตาราง) และคอลัมน์ที่ยังไม่มีคือค่าเริ่มต้นที่มันจะได้
// =============================================================================

/**
 * กลุ่มแถวที่จะชนกับดัชนี PRIMARY/UNIQUE ตัวใหม่ (อ่านอย่างเดียว — preflight ใช้ด้วย)
 *
 * @param Db    $db
 * @param array $schema
 * @param int   $limit  จำนวนกลุ่มสูงสุดต่อดัชนี (0 = ทั้งหมด)
 *
 * @return array [['table', 'index', 'kind', 'columns', 'exprs', 'where', 'groups' => [[k0, k1, ..., 'n']]]]
 */
function gcmsKeyConflicts($db, array $schema, $limit = 10)
{
    $result = [];
    foreach ($schema['tables'] as $table => $def) {
        if (!$db->tableExists($table)) {
            continue;
        }
        $current = gcmsColumns($db, $table);
        $have = gcmsIndexes($db, $table);
        foreach ($def['indexes'] as $name => $want) {
            if ($want['kind'] !== 'PRIMARY' && $want['kind'] !== 'UNIQUE') {
                continue;
            }
            // ดัชนีเดิมที่บังคับไม่ซ้ำบนคอลัมน์ชุดย่อยของดัชนีใหม่อยู่แล้ว = ชนไม่ได้
            foreach ($have as $index) {
                if (($index['kind'] === 'PRIMARY' || $index['kind'] === 'UNIQUE')
                    && !array_diff($index['columns'], $want['columns'])) {
                    continue 2;
                }
            }
            $exprs = [];
            $where = [];
            foreach ($want['columns'] as $column) {
                $target = gcmsParseColumn(isset($def['columns'][$column]) ? $def['columns'][$column] : 'varchar(255) NULL');
                $nullable = $want['kind'] === 'UNIQUE' && $target['nullable'];
                if (!isset($current[$column])) {
                    // คอลัมน์ที่ยังไม่มี : AUTO_INCREMENT ได้ค่าไม่ซ้ำเสมอ, รับ NULL ได้ = ไม่ชน
                    // (ตาราง logs ของรุ่นเดิมไม่มี id แต่ถูกเปลี่ยนชื่อเก็บไว้ก่อนอยู่แล้ว)
                    // นอกนั้นทุกแถวได้ค่าเริ่มต้นเดียวกันจาก gcmsSyncSchema()
                    if ($target['auto_increment'] || ($nullable && $target['default'] === null)) {
                        continue 2;
                    }
                    $exprs[] = gcmsFillValue($target);
                    continue;
                }
                $expr = "`$column`";
                if (!gcmsIsNumericType($current[$column]['Type'])) {
                    $expr = "CONVERT(`$column` USING utf8mb4) COLLATE utf8mb4_general_ci";
                }
                if ($nullable) {
                    $where[] = "`$column` IS NOT NULL";
                } else {
                    $expr = "IFNULL($expr, ".gcmsFillValue($target).')';
                }
                $exprs[] = $expr;
            }
            // GROUP BY ด้วยชื่อแทน (alias) — นิพจน์ที่เป็นค่าคงที่ตัวเลข (คอลัมน์ที่ยังไม่มี)
            // ถ้าใส่ตรง ๆ MySQL อ่านเป็นลำดับคอลัมน์ แล้วล้มด้วย Unknown column '0'
            $select = [];
            $alias = [];
            foreach ($exprs as $i => $expr) {
                $select[] = "$expr AS `k$i`";
                $alias[] = "`k$i`";
            }
            $groups = $db->customQuery(
                'SELECT '.implode(', ', $select).", COUNT(*) AS `n` FROM `$table`"
                .(empty($where) ? '' : ' WHERE '.implode(' AND ', $where))
                .' GROUP BY '.implode(', ', $alias).' HAVING COUNT(*) > 1'
                .($limit > 0 ? ' LIMIT '.(int) $limit : ''),
                true
            );
            if (!empty($groups)) {
                $result[] = [
                    'table' => $table,
                    'index' => $name,
                    'kind' => $want['kind'],
                    'columns' => $want['columns'],
                    'exprs' => $exprs,
                    'where' => $where,
                    'groups' => $groups
                ];
            }
        }
    }

    return $result;
}

/**
 * ย้ายแถวที่จะชน PRIMARY KEY ตัวใหม่ไปเก็บที่ <ตาราง>_duplicate_legacy (ไม่ลบข้อมูล)
 *
 * แต่ละกลุ่มเก็บไว้หนึ่งแถว — แถวที่ gcmsKeyPreference() ให้คะแนนสูงสุด (เสมอกันเลือก
 * ตามคีย์เดิมน้อยสุด) ที่เหลือย้ายไปตารางสำรองแล้วแจ้ง noteRowsMoved()
 *
 * UNIQUE และตาราง user ไม่ย้ายให้เอง — เป็นเรื่องที่คนต้องตัดสิน (บัญชีสมาชิก เลขหมวด)
 * preflight-module.php หยุดการปรับรุ่นพร้อมบอกแถวที่ต้องแก้ไว้ก่อนแล้ว
 * (ป้ายกำกับซ้ำใน tags ถูกรวมให้แล้วใน gcmsBeforeSync())
 *
 * @param Db     $db
 * @param string $prefix
 * @param array  $schema
 * @param array  $content
 */
function gcmsResolveKeyConflicts($db, $prefix, array $schema, array &$content)
{
    foreach (gcmsKeyConflicts($db, $schema, 0) as $conflict) {
        $table = $conflict['table'];
        if ($conflict['kind'] !== 'PRIMARY' || $table === $prefix.'_user') {
            continue;
        }
        // ตัวตนของแถว = PRIMARY KEY เดิม (ไม่มีก็ใช้ทุกคอลัมน์)
        $indexes = gcmsIndexes($db, $table);
        $identity = isset($indexes['PRIMARY']) ? $indexes['PRIMARY']['columns'] : array_keys(gcmsColumns($db, $table));
        $preference = gcmsKeyPreference($db, $prefix, $table);
        $backup = gcmsFreeTableName($db, $table.'_duplicate_legacy');
        $db->query("CREATE TABLE `$backup` LIKE `$table`");
        $match = [];
        foreach ($conflict['exprs'] as $expr) {
            $match[] = "$expr = ?";
        }
        $match = array_merge($match, $conflict['where']);
        $moved = 0;
        foreach ($conflict['groups'] as $group) {
            $values = [];
            foreach (array_keys($conflict['exprs']) as $i) {
                $values[] = $group['k'.$i];
            }
            $rows = $db->customQuery(
                'SELECT `'.implode('`, `', $identity)."`, $preference AS `__keep` FROM `$table` T WHERE "
                .implode(' AND ', $match).' ORDER BY `__keep` DESC, `'.implode('`, `', $identity).'`',
                true,
                $values
            );
            array_shift($rows);
            foreach ($rows as $row) {
                $where = [];
                $params = [];
                foreach ($identity as $column) {
                    $where[] = "`$column` <=> ?";
                    $params[] = $row[$column];
                }
                $where = implode(' AND ', $where);
                $db->execute("INSERT INTO `$backup` SELECT * FROM `$table` WHERE $where LIMIT 1", $params);
                $db->execute("DELETE FROM `$table` WHERE $where LIMIT 1", $params);
                ++$moved;
            }
        }
        noteRowsMoved($table, $backup, $moved);
        $content[] = '<li class="correct">'.$table.': ย้ายแถวที่ซ้ำตาม PRIMARY KEY ใหม่ ('
            .implode(', ', $conflict['columns']).') '.$moved.' แถว ไปเก็บที่ '.$backup.'</li>';
    }
}

/**
 * นิพจน์ SQL ให้คะแนนแถวที่ควร "เก็บไว้" เมื่อแถวซ้ำกันตามคีย์ใหม่ (มาก = เก็บ)
 *
 * @param Db     $db
 * @param string $prefix
 * @param string $table
 *
 * @return string ใช้กับ alias T ของตารางนั้น
 */
function gcmsKeyPreference($db, $prefix, $table)
{
    // index_detail : แถวที่ module_id ตรงกับบทความจริงใน index คือเนื้อหาจริง
    // แถวที่ไม่ตรงคือแถวกำพร้าที่หลงเหลือจากการย้ายบทความข้ามโมดูลของรุ่นเดิม
    if ($table === $prefix.'_index_detail' && $db->fieldExists($table, 'module_id')
        && $db->tableExists($prefix.'_index') && $db->fieldExists($prefix.'_index', 'module_id')) {
        return "(SELECT COUNT(*) FROM `{$prefix}_index` I WHERE I.`id` = T.`id` AND I.`module_id` = T.`module_id`)";
    }

    return '0';
}

// =============================================================================
// ขั้นที่ 6 : แปลงข้อมูลหลังปรับสคีมา
// =============================================================================

/**
 * @param Db     $db
 * @param string $prefix
 * @param array  $added   คอลัมน์ที่เพิ่งเพิ่ม (จาก gcmsSyncSchema)
 * @param array  $config  ค่ากำหนดของไซต์ (ใช้รายชื่อภาษา)
 * @param array  $content
 */
function gcmsAfterSync($db, $prefix, array $added, array $config, array &$content)
{
    $languages = gcmsLanguages($config);

    // activities (บันทึกกิจกรรมของรุ่นเดิม) → logs
    gcmsLegacyActivities($db, $prefix, $content);

    // วันที่สร้างที่ว่าง (ตารางที่รุ่นเดิมไม่มีวันที่สร้าง หรือเก็บไว้เป็น 0)
    // ใช้วันที่แก้ไขล่าสุดแทน ไม่งั้นรายการจะแสดงวันที่ว่าง/1970
    foreach (['comment', 'download', 'index', 'board_q'] as $name) {
        $table = $prefix.'_'.$name;
        if ($db->tableExists($table)) {
            $db->query("UPDATE `$table` SET `created_at` = `updated_at` WHERE `created_at` IS NULL AND `updated_at` IS NOT NULL");
        }
    }
    $table = $prefix.'_index';
    if ($db->tableExists($table)) {
        $db->query("UPDATE `$table` SET `created_at` = `published_date` WHERE `created_at` IS NULL AND `published_date` > '1000-01-01'");
        $db->query("UPDATE `$table` SET `updated_at` = `created_at` WHERE `updated_at` IS NULL AND `created_at` IS NOT NULL");
    }
    // ตารางที่ยังเก็บ last_update ไว้ แต่มี updated_at เพิ่มมา
    foreach (['gallery_album', 'eventcalendar'] as $name) {
        $table = $prefix.'_'.$name;
        if (!empty($added[$table]['updated_at'])) {
            $db->query("UPDATE `$table` SET `updated_at` = FROM_UNIXTIME(`last_update`) WHERE `last_update` > 0");
        }
    }

    // modules.config / index.show_news / category.config : serialize → JSON
    gcmsSerializedToJson($db, $prefix.'_modules', 'id', 'config', $content);
    gcmsSerializedToJson($db, $prefix.'_index', 'id', 'show_news', $content);
    gcmsSerializedToJson($db, $prefix.'_category', 'id', 'config', $content);

    // category : topic/detail/icon เป็น JSON แยกภาษา, หมวดของ personnel คือ 'department'
    gcmsLegacyCategory($db, $prefix, $languages, $content);

    // personnel : แผนกคือเลขหมวดเดิม
    $table = $prefix.'_personnel';
    if ($db->tableExists($table)) {
        $n = gcmsAffected($db, "UPDATE `$table` SET `department` = `category_id` WHERE `department` = 0 AND `category_id` > 0");
        if ($n > 0) {
            $content[] = '<li class="correct">'.$table.': กำหนดแผนกจากหมวดเดิม '.$n.' รายการ</li>';
        }
    }

    // gallery (รุ่นเดิม) → gallery_image · ตาราง gallery เดิมเก็บไว้ตามเดิม
    $from = $prefix.'_gallery';
    $to = $prefix.'_gallery_image';
    if ($db->tableExists($from) && $db->tableExists($to) && $db->fieldExists($from, 'album_id')) {
        $updated = $db->fieldExists($from, 'last_update') ? 'IF(G.`last_update` > 0, FROM_UNIXTIME(G.`last_update`), NOW())' : 'NOW()';
        $n = gcmsAffected($db,
            "INSERT INTO `$to` (`id`, `module_id`, `album_id`, `image`, `updated_at`, `count`)
             SELECT G.`id`, G.`module_id`, G.`album_id`, G.`image`, $updated, G.`count`
             FROM `$from` G LEFT JOIN `$to` I ON I.`id` = G.`id` WHERE I.`id` IS NULL"
        );
        if ($n > 0) {
            $content[] = '<li class="correct">'.$to.': คัดลอกรูปภาพจาก '.$from.' '.$n.' รายการ</li>';
        }
    }

    // emailtemplate : แม่แบบของรุ่นเดิมไม่มี code — ตั้งชื่อ legacy_ ไว้ให้แก้ต่อได้
    // (ไม่ผูกกับ code ที่ระบบส่งจริง เพราะใช้ตัวแปรและรูปแบบลิงก์ของรุ่นเดิม)
    $table = $prefix.'_emailtemplate';
    if ($db->tableExists($table)) {
        $n = gcmsAffected($db, "UPDATE `$table` SET `code` = LEFT(CONCAT('legacy_', `module`, '_', `email_id`), 20) WHERE `code` = ''");
        if ($n > 0) {
            $content[] = '<li class="correct">'.$table.': เก็บแม่แบบอีเมลของรุ่นเดิม '.$n.' รายการ (code ขึ้นต้นด้วย legacy_)</li>';
        }
    }
}

/**
 * ภาษาของไซต์ — ใช้แปลงข้อความเดี่ยวของรุ่นเดิมเป็น JSON แยกภาษา
 *
 * @param array $config
 *
 * @return array
 */
function gcmsLanguages(array $config)
{
    $languages = [];
    if (!empty($config['languages']) && is_array($config['languages'])) {
        foreach ($config['languages'] as $lang) {
            if (preg_match('/^[a-z]{2}$/', (string) $lang)) {
                $languages[] = $lang;
            }
        }
    }
    if (empty($languages)) {
        foreach (glob(ROOT_PATH.'language/*.json') ?: [] as $file) {
            $languages[] = basename($file, '.json');
        }
    }

    return empty($languages) ? ['th'] : $languages;
}

/**
 * แปลงค่าแบบ PHP serialize เป็น JSON ทีละแถว (แถวที่เป็น JSON แล้วไม่ถูกแตะ)
 *
 * @param Db     $db
 * @param string $table
 * @param string $key
 * @param string $column
 * @param array  $content
 */
function gcmsSerializedToJson($db, $table, $key, $column, array &$content)
{
    if (!$db->tableExists($table) || !$db->fieldExists($table, $column)) {
        return;
    }
    $n = 0;
    foreach ((array) $db->customQuery("SELECT `$key` AS `k`, `$column` AS `v` FROM `$table` WHERE `$column` LIKE 'a:%'", true) as $row) {
        $value = @unserialize($row['v'], ['allowed_classes' => false]);
        if (is_array($value)) {
            $db->update($table, [$key => $row['k']], [$column => gcmsJson($value)]);
            ++$n;
        }
    }
    if ($n > 0) {
        $content[] = '<li class="correct">'.$table.': แปลง '.$column.' เป็น JSON '.$n.' รายการ</li>';
    }
}

/**
 * category ของรุ่นเดิม → รูปแบบของระบบนี้
 *
 *   topic/detail : serialize ['th' => .., 'en' => ..] หรือข้อความเดี่ยว → JSON แยกภาษา
 *   icon         : ชื่อไฟล์ใน datas/<โมดูล>/ → ที่อยู่เต็ม "datas/<โมดูล>/<ไฟล์>"
 *   type         : หมวดของโมดูล personnel คือ 'department'
 *
 * @param Db     $db
 * @param string $prefix
 * @param array  $languages
 * @param array  $content
 */
function gcmsLegacyCategory($db, $prefix, array $languages, array &$content)
{
    $table = $prefix.'_category';
    $modules = $prefix.'_modules';
    if (!$db->tableExists($table) || !$db->tableExists($modules)) {
        return;
    }
    $n = gcmsAffected($db,
        "UPDATE `$table` C INNER JOIN `$modules` M ON M.`id` = C.`module_id`
         SET C.`type` = 'department' WHERE M.`owner` = 'personnel' AND C.`type` = 'category'"
    );
    if ($n > 0) {
        $content[] = '<li class="correct">'.$table.': หมวดของโมดูลบุคลากรเป็นแผนก '.$n.' รายการ</li>';
    }

    $converted = 0;
    $rows = $db->customQuery(
        "SELECT C.`id`, C.`topic`, C.`detail`, C.`icon`, M.`owner`
         FROM `$table` C LEFT JOIN `$modules` M ON M.`id` = C.`module_id`",
        true
    );
    foreach ((array) $rows as $row) {
        $save = [];
        foreach (['topic', 'detail', 'icon'] as $column) {
            $value = (string) $row[$column];
            if ($value === '' || gcmsIsJsonObject($value)) {
                continue;
            }
            $data = @unserialize($value, ['allowed_classes' => false]);
            if (!is_array($data)) {
                // ข้อความเดี่ยวของรุ่นเดิม ใช้กับทุกภาษา
                $data = array_fill_keys($languages, $value);
            }
            if ($column === 'icon') {
                foreach ($data as $lang => $file) {
                    $file = (string) $file;
                    if ($file !== '' && strpos($file, gcmsDataFolder()) !== 0) {
                        $data[$lang] = gcmsDataFolder().preg_replace('/[^a-z0-9_]/', '', (string) $row['owner']).'/'.$file;
                    }
                }
            }
            $save[$column] = gcmsJson($data);
        }
        if (!empty($save)) {
            $db->update($table, ['id' => $row['id']], $save);
            ++$converted;
        }
    }
    if ($converted > 0) {
        $content[] = '<li class="correct">'.$table.': แปลงชื่อ/รายละเอียด/ไอคอนหมวดหมู่เป็น JSON '.$converted.' รายการ</li>';
    }
}

/**
 * activities ของรุ่นเดิม → logs แล้วเปลี่ยนชื่อตารางเดิมเก็บไว้
 *
 * @param Db     $db
 * @param string $prefix
 * @param array  $content
 */
function gcmsLegacyActivities($db, $prefix, array &$content)
{
    $from = $prefix.'_activities';
    $to = $prefix.'_logs';
    if (!$db->tableExists($from) || !$db->tableExists($to) || !$db->fieldExists($to, 'src_id')) {
        return;
    }
    $date = $db->fieldExists($from, 'created_at') ? 'created_at' : 'create_date';
    foreach (['id', 'src_id', 'module', 'action', $date, 'reason', 'member_id', 'topic', 'datas'] as $column) {
        if (!$db->fieldExists($from, $column)) {
            return;
        }
    }
    $count = getTableRowCount($db, $from);
    // id เดิมคงไว้ถ้าไม่ชน (logs ที่เพิ่งสร้างว่างอยู่) ถ้าชนให้ได้ id ใหม่
    $db->query(
        "INSERT INTO `$to` (`id`, `src_id`, `module`, `action`, `created_at`, `reason`, `member_id`, `topic`, `datas`)
         SELECT IF(L.`id` IS NULL, A.`id`, NULL), A.`src_id`, A.`module`, A.`action`, A.`$date`, A.`reason`, A.`member_id`, A.`topic`, A.`datas`
         FROM `$from` A LEFT JOIN `$to` L ON L.`id` = A.`id`"
    );
    $backup = gcmsFreeTableName($db, $prefix.'_activities_legacy');
    $db->query("RENAME TABLE `$from` TO `$backup`");
    noteRowsMoved($from, $backup, $count);
    $content[] = '<li class="correct">'.$to.': ย้ายบันทึกกิจกรรมจาก '.$from.' '.$count.' รายการ (ตารางเดิมเก็บไว้ที่ '.$backup.')</li>';
}

/**
 * คอลัมน์ที่ไม่อยู่ในนิยาม เป็น NOT NULL ไม่มีค่าเริ่มต้น และเป็นชนิดที่ตั้งค่า
 * เริ่มต้นแบบพกพาไม่ได้ (TEXT/BLOB/วันที่/ENUM) → ให้รับ NULL ได้
 *
 * ensureForeignColumnsDefault() (common.php) ดูแลได้เฉพาะตัวเลขและ varchar
 * ส่วนชนิดเหล่านี้มันทำได้แค่เตือน แต่ถ้าปล่อยไว้ การเพิ่มสมาชิกใหม่จะล้มทั้งหมด
 * (เช่น user.pm / user.unread ของฐานรุ่น gcms.in.th) ข้อมูลเดิมไม่ถูกแตะ
 *
 * @param Db    $db
 * @param array $schema
 * @param array $content
 */
function gcmsRelaxForeignColumns($db, array $schema, array &$content)
{
    foreach ($schema['tables'] as $table => $def) {
        if (!$db->tableExists($table)) {
            continue;
        }
        foreach (gcmsColumns($db, $table) as $column => $info) {
            if (isset($def['columns'][$column]) || $info['Null'] !== 'NO' || $info['Default'] !== null
                || stripos((string) $info['Extra'], 'auto_increment') !== false) {
                continue;
            }
            if (preg_match('/^(tinytext|text|mediumtext|longtext|tinyblob|blob|mediumblob|longblob|json|date|datetime|timestamp|time|year|enum|set)\b/i', $info['Type'])) {
                $db->query("ALTER TABLE `$table` MODIFY `$column` {$info['Type']} NULL DEFAULT NULL");
                $content[] = '<li class="correct">'.$table.': ให้คอลัมน์ '.$column.' ที่ระบบนี้ไม่ได้ใช้ รับค่าว่างได้ (ข้อมูลเดิมอยู่ครบ)</li>';
            }
        }
    }
}

// =============================================================================
// ขั้นที่ 7 : ข้อมูลเริ่มต้น ค่ากำหนด ชื่อตาราง
// =============================================================================

/**
 * เพิ่มแม่แบบอีเมล (INSERT ใน database.sql) เฉพาะ code + language ที่ยังไม่มี
 * ไม่ทับแม่แบบที่ผู้ดูแลแก้ไว้
 *
 * @param Db    $db
 * @param array $schema
 * @param array $content
 */
function gcmsSeedRows($db, array $schema, array &$content)
{
    $n = 0;
    foreach ($schema['inserts'] as $table => $commands) {
        if (substr($table, -14) !== '_emailtemplate' || !$db->tableExists($table)) {
            continue;
        }
        foreach ($commands as $command) {
            if (!preg_match("/VALUES\s*\('[^']*',\s*\d+,\s*'([^']+)',\s*'([a-z]{2})'/", $command, $match)) {
                continue;
            }
            if (!$db->first($table, ['code' => $match[1], 'language' => $match[2]])) {
                $db->query($command);
                ++$n;
            }
        }
    }
    if ($n > 0) {
        $content[] = '<li class="correct">เพิ่มแม่แบบอีเมลที่ยังไม่มี '.$n.' รายการ</li>';
    }
}

/**
 * settings/database.php — เติมการแมปชื่อตารางที่ระบบนี้ต้องใช้
 * (โค้ดเรียก 'event' แต่ตารางจริงชื่อ eventcalendar)
 *
 * @param array  $db_settings
 * @param string $file
 * @param array  $content
 */
function gcmsTableMap(array $db_settings, $file, array &$content)
{
    $template = include ROOT_PATH.'install/settings/database.php';
    $tables = isset($db_settings['tables']) && is_array($db_settings['tables']) ? $db_settings['tables'] : [];
    $missing = array_diff_key($template['tables'], $tables);
    if (empty($missing)) {
        return;
    }
    $db_settings['tables'] = $tables + $missing;
    $f = save($db_settings, $file);
    $content[] = '<li class="'.($f ? 'correct' : 'incorrect').'">บันทึก <b>database.php</b> (เพิ่มชื่อตาราง '
        .htmlspecialchars(implode(', ', array_keys($missing)), ENT_QUOTES).')</li>';
}

/**
 * ค่ากำหนดเฉพาะของ GCMS
 *
 * @param array $config
 * @param array $content
 */
function gcmsConfig(array &$config, array &$content)
{
    // ธีมของรุ่นเดิมบางตัวไม่มีในรุ่นนี้ — ถ้าปล่อยไว้หน้าเว็บจะว่างทั้งหน้า
    if (!empty($config['skin']) && !is_file(ROOT_PATH.'themes/'.basename($config['skin']).'/index.html')) {
        $content[] = '<li class="warning">ไม่พบธีม <code>'.htmlspecialchars($config['skin'], ENT_QUOTES).'</code> ในรุ่นนี้ '
            .'เปลี่ยนไปใช้ธีม <code>default</code> (เลือกธีมใหม่ได้ที่หน้าตั้งค่า)</li>';
        $config['skin'] = 'default';
    }
}

// =============================================================================
// เครื่องมือเล็ก ๆ
// =============================================================================

/**
 * @param array $value
 *
 * @return string
 */
function gcmsJson(array $value)
{
    return json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}

/**
 * @param string $value
 *
 * @return bool
 */
function gcmsIsJsonObject($value)
{
    $value = trim($value);

    return $value !== '' && ($value[0] === '{' || $value[0] === '[') && json_decode($value) !== null;
}

/**
 * รันคำสั่งแล้วคืนจำนวนแถวที่ถูกแก้
 *
 * @param Db     $db
 * @param string $sql
 *
 * @return int
 */
function gcmsAffected($db, $sql)
{
    return max(0, (int) $db->query($sql));
}

/**
 * โฟลเดอร์เก็บไฟล์ของไซต์ — ตัวติดตั้งไม่ได้โหลด load.php จึงไม่มี DATA_FOLDER
 *
 * @return string
 */
function gcmsDataFolder()
{
    return defined('DATA_FOLDER') ? DATA_FOLDER : 'datas/';
}

/**
 * @param Db     $db
 * @param string $table
 *
 * @return int
 */
function getTableRowCount($db, $table)
{
    if (!$db->tableExists($table)) {
        return 0;
    }
    $row = $db->customQuery("SELECT COUNT(*) AS `n` FROM `$table`");

    return empty($row) ? 0 : (int) $row[0]->n;
}

/**
 * ชื่อตารางที่ยังว่าง (base, base_1, base_2 ...)
 *
 * @param Db     $db
 * @param string $base
 *
 * @return string
 */
function gcmsFreeTableName($db, $base)
{
    $name = $base;
    $i = 1;
    while ($db->tableExists($name)) {
        $name = $base.'_'.$i;
        ++$i;
    }

    return $name;
}

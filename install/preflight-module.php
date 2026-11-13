<?php
/**
 * install/preflight-module.php — ข้อตรวจก่อนปรับรุ่นเฉพาะของ GCMS
 *
 * install/preflight.php เรียก preflightModule() ท้ายการตรวจของแกน ทุกอย่างใน
 * ไฟล์นี้ "อ่านอย่างเดียว" ถ้าเจอ error การปรับรุ่นจะหยุดก่อนแตะฐานข้อมูล
 *
 * ครอบคลุมฐานของ GCMS รุ่นเดิม (11 – 14.x) ซึ่งหน้าตาต่างจากแกนตรงที่
 *   • user ไม่มี username (รุ่นแรก ๆ เข้าระบบด้วย email) — จะถูกย้ายเป็น username
 *   • user.idcard (ไม่ใช่ id_card) — แกนตรวจค่าซ้ำเฉพาะ id_card จึงมองไม่เห็น
 *   • PRIMARY KEY/UNIQUE ใหม่ที่ข้อมูลเดิมชน (index_detail กำพร้า) — gcmsKeyConflicts()
 *   • category ไม่มีคอลัมน์ type — UNIQUE (module_id, type, category_id, language) จะล้ม
 *     ถ้ามีหมวดเลขซ้ำในโมดูลเดียวกัน
 *   • menus.parent เป็นข้อความอิสระ — ค่าที่ไม่รู้จักจะหายเมื่อเปลี่ยนเป็น ENUM
 */
if (!defined('ROOT_PATH')) {
    exit;
}

/**
 * @param Db    $db
 * @param array $db_config
 * @param array $result    ผลของ preflight() — เติม errors / warnings / notes ได้
 */
function preflightModule($db, $db_config, array &$result)
{
    $prefix = $db_config['prefix'];
    $table_user = $prefix.'_user';

    // -------------------------------------------------------------------------
    // ชื่อเข้าระบบ : GCMS รุ่นแรก ๆ ไม่มี username (เข้าระบบด้วย email) ตัวปรับรุ่น
    // จะย้าย email → username ซึ่งห้ามค่าซ้ำและยาวได้ไม่เกินนิยามใหม่
    // -------------------------------------------------------------------------
    $login = loginColumn($db, $table_user);
    if ($login === '') {
        $result['errors'][] = 'ตาราง <code>'.$table_user.'</code> ไม่มีคอลัมน์ชื่อเข้าระบบ (<code>username</code> หรือ <code>email</code>)<br>'
            .'ตัวปรับรุ่นยืนยันผู้ดูแลระบบและย้ายบัญชีสมาชิกไม่ได้ กรุณาตรวจว่าเลือกฐานข้อมูลและ prefix ถูกต้อง';
    } elseif ($login === 'email') {
        $result['notes'][] = 'ระบบเดิมใช้อีเมลเป็นชื่อเข้าระบบ (ไม่มีคอลัมน์ username) — '
            .'จะย้ายคอลัมน์ email เป็น username ของระบบใหม่ ผู้ดูแลกรอกอีเมลของตัวเองในช่องชื่อผู้ใช้';
        $duplicates = duplicateValues($db, $table_user, 'email');
        if (!empty($duplicates)) {
            $list = [];
            foreach ($duplicates as $item) {
                $list[] = '<code>'.htmlspecialchars($item['value'], ENT_QUOTES).'</code> '
                    .'ซ้ำ '.$item['rows'].' แถว (id '.htmlspecialchars($item['ids'], ENT_QUOTES).')';
            }
            $result['errors'][] = 'อีเมลของสมาชิก (<code>email</code> ในตาราง <code>'.$table_user.'</code>) จะกลายเป็นชื่อเข้าระบบที่ห้ามซ้ำ '
                .'แต่ตอนนี้ยังมีค่าซ้ำอยู่<br>'.implode('<br>', $list).'<br>'
                .'กรุณาแก้อีเมลของสมาชิกให้ไม่ซ้ำกัน (หรือลบสมาชิกที่ซ้ำ) แล้วปรับรุ่นใหม่อีกครั้ง';
        }
    }
    if ($login !== '' && function_exists('gcmsSchema')) {
        $schema = gcmsSchema($prefix);
        $definition = isset($schema['tables'][$table_user]['columns']['username']) ? $schema['tables'][$table_user]['columns']['username'] : '';
        if (preg_match('/char\((\d+)\)/i', $definition, $match)) {
            $rows = $db->customQuery(
                "SELECT `id` FROM `$table_user` WHERE CHAR_LENGTH(`$login`) > ".(int) $match[1].' ORDER BY `id` LIMIT 10'
            );
            if (!empty($rows)) {
                $ids = [];
                foreach ($rows as $row) {
                    $ids[] = (int) $row->id;
                }
                $result['errors'][] = 'ชื่อเข้าระบบ (<code>'.$login.'</code>) ของสมาชิก id '.implode(', ', $ids)
                    .' ยาวเกิน '.(int) $match[1].' ตัวอักษรที่ระบบใหม่เก็บได้ ถ้าปรับรุ่นต่อจะถูกตัดและเข้าระบบไม่ได้<br>'
                    .'กรุณาแก้ให้สั้นลงก่อน แล้วปรับรุ่นใหม่อีกครั้ง';
            }
        }
    }

    // -------------------------------------------------------------------------
    // user.idcard (GCMS รุ่นเดิม) จะกลายเป็น id_card ที่ห้ามค่าซ้ำ
    // -------------------------------------------------------------------------
    if ($db->fieldExists($table_user, 'idcard') && !$db->fieldExists($table_user, 'id_card')) {
        $duplicates = duplicateValues($db, $table_user, 'idcard');
        if (!empty($duplicates)) {
            $list = [];
            foreach ($duplicates as $item) {
                $list[] = '<code>'.htmlspecialchars($item['value'], ENT_QUOTES).'</code> '
                    .'ซ้ำ '.$item['rows'].' แถว (id '.htmlspecialchars($item['ids'], ENT_QUOTES).')';
            }
            $result['errors'][] = 'เลขประจำตัวประชาชน (<code>idcard</code>) ของตาราง <code>'.$table_user.'</code> '
                .'กำลังจะห้ามค่าซ้ำ แต่ตอนนี้ยังมีค่าซ้ำอยู่<br>'.implode('<br>', $list).'<br>'
                .'กรุณาแก้ข้อมูลสมาชิกให้ไม่ซ้ำกัน หรือลบค่าที่ซ้ำออกให้ว่าง แล้วปรับรุ่นใหม่อีกครั้ง';
        }
    }

    // -------------------------------------------------------------------------
    // category : หมวดเลขซ้ำในโมดูลเดียวกัน
    // -------------------------------------------------------------------------
    $table_category = $prefix.'_category';
    if ($db->tableExists($table_category) && $db->fieldExists($table_category, 'module_id')
        && !$db->indexExists($table_category, 'type')) {
        $type = $db->fieldExists($table_category, 'type') ? "IFNULL(`type`, '')" : "''";
        // UNIQUE ใหม่คือ (module_id, type, category_id, language)
        $language = $db->fieldExists($table_category, 'language') ? "IFNULL(`language`, '')" : "''";
        $rows = $db->customQuery(
            "SELECT `module_id` AS `m`, `category_id` AS `c`, COUNT(*) AS `n`, GROUP_CONCAT(`id` SEPARATOR ', ') AS `ids`,
                    $type AS `t`, $language AS `l`
             FROM `$table_category` GROUP BY `module_id`, `t`, `category_id`, `l` HAVING `n` > 1 LIMIT 10"
        );
        if (!empty($rows)) {
            $list = [];
            foreach ($rows as $row) {
                $list[] = 'module_id '.(int) $row->m.' หมวด '.(int) $row->c.' ซ้ำ '.(int) $row->n.' แถว (id '.htmlspecialchars($row->ids, ENT_QUOTES).')';
            }
            $result['errors'][] = 'ตาราง <code>'.$table_category.'</code> มีเลขหมวดหมู่ซ้ำกันในโมดูลเดียวกัน '
                .'ระบบใหม่ห้ามค่าซ้ำ<br>'.implode('<br>', $list).'<br>'
                .'กรุณาลบหรือแก้เลขหมวดที่ซ้ำก่อน แล้วปรับรุ่นใหม่อีกครั้ง';
        }
    }

    // -------------------------------------------------------------------------
    // แถวที่จะชนกับ PRIMARY KEY / UNIQUE ตัวใหม่ของสคีมา (ทุกตาราง — gcmsKeyConflicts())
    //
    // PRIMARY KEY : ตัวปรับรุ่นย้ายแถวที่ซ้ำไปเก็บที่ <ตาราง>_duplicate_legacy ให้เอง
    //               (เช่น index_detail กำพร้าของ GCMS 11 ที่ id ซ้ำคนละ module_id)
    // UNIQUE / user : ต้องให้คนตัดสิน — หยุดก่อนแตะ ยกเว้นดัชนีที่มีข้อตรวจเฉพาะอยู่แล้ว
    //               (user.username/id_card/phone ใน preflight.php, email/idcard/category
    //               ข้างบน) และ tags.tag ที่ตัวปรับรุ่นรวมให้เอง
    // -------------------------------------------------------------------------
    if (isset($schema)) {
        $covered = [
            $table_user => ['username', 'id_card', 'phone'],
            $prefix.'_category' => ['type'],
            $prefix.'_tags' => ['tag']
        ];
        foreach (gcmsKeyConflicts($db, $schema) as $conflict) {
            $table = $conflict['table'];
            if (isset($covered[$table]) && in_array($conflict['index'], $covered[$table], true)) {
                continue;
            }
            $sample = [];
            $rows = 0;
            foreach ($conflict['groups'] as $group) {
                $values = [];
                foreach (array_keys($conflict['columns']) as $i) {
                    $values[] = $group['k'.$i];
                }
                $sample[] = '('.htmlspecialchars(implode(', ', $values), ENT_QUOTES).') '.(int) $group['n'].' แถว';
                $rows += (int) $group['n'];
            }
            $what = 'ตาราง <code>'.$table.'</code> มีแถวที่ซ้ำกันตาม '.($conflict['kind'] === 'PRIMARY' ? 'PRIMARY KEY' : 'UNIQUE <code>'.$conflict['index'].'</code>')
                .' ใหม่ ('.implode(', ', $conflict['columns']).') '
                .(count($conflict['groups']) >= 10 ? 'อย่างน้อย ' : '').count($conflict['groups']).' กลุ่ม : '.implode(' · ', $sample);
            if ($conflict['kind'] === 'PRIMARY' && $table !== $table_user) {
                $result['warnings'][] = $what.'<br>ตัวปรับรุ่นจะเก็บไว้กลุ่มละหนึ่งแถว (แถวที่ตรงกับข้อมูลหลักมากที่สุด) '
                    .'และย้ายแถวที่เหลือไปเก็บที่ <code>'.$table.'_duplicate_legacy</code> ไม่มีข้อมูลถูกลบ';
            } else {
                $result['errors'][] = $what.'<br>กรุณาแก้หรือลบแถวที่ซ้ำก่อน แล้วปรับรุ่นใหม่อีกครั้ง';
            }
        }
    }

    // -------------------------------------------------------------------------
    // menus.parent : ค่าที่ระบบใหม่ไม่รู้จัก
    // -------------------------------------------------------------------------
    $table_menus = $prefix.'_menus';
    if ($db->tableExists($table_menus) && $db->fieldExists($table_menus, 'parent')) {
        $rows = $db->customQuery(
            "SELECT `parent` AS `p`, COUNT(*) AS `n` FROM `$table_menus`
             WHERE `parent` NOT IN ('MAINMENU', 'SIDEMENU', 'BOTTOMMENU', '0_MAINMENU', '1_SIDEMENU', '2_BOTTOMMENU', '')
             GROUP BY `parent`"
        );
        foreach ((array) $rows as $row) {
            $result['warnings'][] = 'เมนู '.(int) $row->n.' รายการอยู่ในตำแหน่ง <code>'.htmlspecialchars($row->p, ENT_QUOTES).'</code> '
                .'ซึ่งระบบใหม่ไม่มี (มีเฉพาะเมนูหลัก เมนูข้าง เมนูล่าง) เมนูเหล่านี้จะไม่แสดงจนกว่าจะเลือกตำแหน่งใหม่ในหน้าจัดการเมนู';
        }
    }

    // -------------------------------------------------------------------------
    // โมดูลที่ติดตั้งไว้ในระบบเดิม แต่ไม่มีในระบบนี้
    // ข้อมูลของโมดูลเหล่านี้ไม่ถูกแตะ แต่หน้าเว็บของมันจะเปิดไม่ได้
    // -------------------------------------------------------------------------
    $table_modules = $prefix.'_modules';
    if ($db->tableExists($table_modules)) {
        $missing = [];
        foreach ((array) $db->customQuery("SELECT DISTINCT `owner` AS `o` FROM `$table_modules`") as $row) {
            $owner = preg_replace('/[^a-z0-9_]/', '', strtolower((string) $row->o));
            if ($owner !== '' && !is_dir(ROOT_PATH.'modules/'.$owner)) {
                $missing[] = $owner;
            }
        }
        if (!empty($missing)) {
            $result['warnings'][] = 'ระบบเดิมติดตั้งโมดูลที่ไม่มีในรุ่นนี้ : <code>'.implode('</code>, <code>', $missing).'</code><br>'
                .'ข้อมูลและตารางของโมดูลเหล่านี้จะถูกเก็บไว้ตามเดิม แต่หน้าเว็บของโมดูลจะเปิดไม่ได้ '
                .'จนกว่าจะติดตั้งโมดูลรุ่นใหม่ที่รองรับ';
        }
    }
}

<?php
/**
 * install/step5.php — ติดตั้งฐานข้อมูลและสร้างไฟล์ค่ากำหนด (ขั้นตอนสุดท้าย)
 *
 * ลำดับ : สร้างตาราง (schemaCommands) → ผู้ดูแลสูงสุด → ประเภทเว็บไซต์
 * (applySiteType : ธีม/ค่ากำหนด + ข้อมูลตัวอย่าง + ไฟล์ตัวอย่าง) → บันทึก
 * settings/database.php และ settings/config.php → นำเข้าภาษา
 *
 * install/cli-fresh.php ทำขั้นตอนเดียวกันนี้จากบรรทัดคำสั่ง ถ้าแก้ที่นี่ต้องแก้ที่นั่นด้วย
 */
if (defined('ROOT_PATH') && !isset($_POST['db_name'])) {
    // เปิด ?step=5 ตรง ๆ โดยไม่ได้ส่งฟอร์มฐานข้อมูลมา
    include ROOT_PATH.'install/step4.php';
} elseif (defined('ROOT_PATH')) {
    include_once ROOT_PATH.'install/seeds.php';
    // ค่าที่ส่งมา
    $_SESSION['db_username'] = $_POST['db_username'];
    $_SESSION['db_password'] = $_POST['db_password'];
    $_SESSION['db_server'] = $_POST['db_server'];
    $_SESSION['db_port'] = preg_replace('/[^0-9]+/', '', $_POST['db_port']);
    $_SESSION['db_name'] = preg_replace('/[^a-zA-Z0-9_]+/', '', $_POST['db_name']);
    $_SESSION['prefix'] = preg_replace('/[^a-zA-Z0-9_]+/', '', $_POST['prefix']);
    $content = [];
    $error = false;
    // Database Class
    include_once ROOT_PATH.'install/db.php';
    try {
        // เขื่อมต่อฐานข้อมูล
        $db = new Db([
            'dbname' => 'INFORMATION_SCHEMA',
            'username' => $_SESSION['db_username'],
            'password' => $_SESSION['db_password'],
            'port' => $_SESSION['db_port'],
            'hostname' => $_SESSION['db_server']
        ]);
        if (!$db->databaseExists($_SESSION['db_name'])) {
            $db->query('CREATE DATABASE '.$_SESSION['db_name'].' CHARACTER SET utf8mb4');
        }
        $db->query('USE '.$_SESSION['db_name']);
    } catch (\PDOException $e) {
        $error = true;
        echo '<h2>ความผิดพลาดในการเชื่อมต่อกับฐานข้อมูล</h2>';
        echo '<p class=warning>'.$e->getMessage().'</p>';
        echo '<p>อาจเป็นไปได้ว่า</p>';
        echo '<ol>';
        echo '<li>เซิร์ฟเวอร์ของฐานข้อมูลของคุณไม่สามารถใช้งานได้ในขณะนี้</li>';
        echo '<li>ไม่มีฐานข้อมูลที่ต้องการติดตั้ง กรุณาสร้างฐานข้อมูลก่อน หรือใช้ฐานข้อมูลที่มีอยู่แล้ว</li>';
        echo '<li>ข้อมูลต่างๆที่กรอกไม่ถูกต้อง กรุณากลับไปตรวจสอบ</li>';
        echo '</ol>';
        echo '<p>หากคุณไม่สามารถดำเนินการแก้ไขข้อผิดพลาดด้วยตัวของคุณเองได้ ให้ติดต่อผู้ดูแลระบบเพื่อขอข้อมูลที่ถูกต้อง</p>';
        echo '<p class="submit"><a href="index.php?step=4" class="btn large btn-secondary">กลับไปลองใหม่</a></p>';
    }
    // ⚠️ ด่านกันติดตั้งทับไซต์ที่มีอยู่แล้ว
    //
    // ติดตั้งใหม่ลงฐานที่มีไซต์อยู่แล้วโดยใช้คำนำหน้าตารางคนละอัน จะได้ไซต์เปล่า
    // ขึ้นมาอีกชุดวางซ้อนกับข้อมูลจริง ตัวติดตั้งไม่ฟ้องอะไรเลยเพราะตารางที่มัน
    // สร้างไม่ชนกับของเดิมสักตัว ผู้ใช้เปิดเว็บมาเห็น "ข้อมูลหายทั้งหมด"
    // ทั้งที่ข้อมูลยังอยู่ครบใต้คำนำหน้าเดิม — เกิดขึ้นจริงมาแล้ว
    if (!$error) {
        $_existing = existingInstallations($db, $_SESSION['db_name']);
        $_others = array_diff($_existing, [$_SESSION['prefix']]);
        if (!empty($_existing) && empty($_POST['confirm_overwrite'])) {
            $error = true;
            echo '<h2>ฐานข้อมูลนี้มีระบบติดตั้งอยู่แล้ว</h2>';
            if (!empty($_others)) {
                echo '<p class=warning>พบตารางของระบบที่ติดตั้งอยู่แล้วในฐาน <em>'
                    .htmlspecialchars($_SESSION['db_name'], ENT_QUOTES).'</em> '
                    .'โดยใช้คำนำหน้าตาราง <em>'.htmlspecialchars(implode(', ', $_others), ENT_QUOTES).'_</em> '
                    .'แต่คุณกำลังจะติดตั้งด้วยคำนำหน้า <em>'
                    .htmlspecialchars($_SESSION['prefix'], ENT_QUOTES).'_</em></p>';
                echo '<p>ถ้าติดตั้งต่อ ระบบจะสร้างตารางชุดใหม่ที่<b>ว่างเปล่า</b>วางซ้อนกับข้อมูลเดิม '
                    .'เปิดเว็บมาจะเหมือนข้อมูลหายทั้งหมด ทั้งที่ข้อมูลเดิมยังอยู่ครบ</p>';
                echo '<p><b>ถ้าต้องการใช้ข้อมูลเดิม</b> ให้ย้อนกลับไปแก้คำนำหน้าตารางเป็น <em>'
                    .htmlspecialchars(reset($_others), ENT_QUOTES).'</em> '
                    .'แล้วใช้การปรับรุ่นแทนการติดตั้งใหม่</p>';
            } else {
                echo '<p class=warning>ฐาน <em>'.htmlspecialchars($_SESSION['db_name'], ENT_QUOTES)
                    .'</em> มีตารางของคำนำหน้า <em>'.htmlspecialchars($_SESSION['prefix'], ENT_QUOTES)
                    .'_</em> อยู่แล้ว การติดตั้งใหม่จะเขียนทับข้อมูลเดิม</p>';
            }
            echo '<form method=post action=index.php>';
            echo '<input type=hidden name=step value=5>';
            foreach (['db_username', 'db_password', 'db_server', 'db_port', 'db_name', 'prefix'] as $_f) {
                echo '<input type=hidden name="'.$_f.'" value="'
                    .htmlspecialchars($_SESSION[$_f === 'prefix' ? 'prefix' : $_f], ENT_QUOTES).'">';
            }
            echo '<p class="submit"><a href="index.php?step=4" class="btn large btn-primary">ย้อนกลับไปแก้คำนำหน้าตาราง</a> ';
            echo '<button type=submit name=confirm_overwrite value=1 class="btn large btn-danger">ยืนยันติดตั้งใหม่ทับ</button></p>';
            echo '</form>';
        }
    }

    if (!$error) {
        // เชื่อมต่อฐานข้อมูลสำเร็จ
        $content[] = '<li class="correct">เชื่อมต่อฐานข้อมูลสำเร็จ</li>';
        // ประมวลผลฐานข้อมูล — อ่านจาก schemaFiles() ซึ่งเป็นรายการเดียวกับที่
        // ตัวปรับรุ่นใช้ (core.sql, database.sql, modules/*/install/database.sql)
        foreach (schemaCommands($_SESSION['prefix'], true) as $command) {
            try {
                $db->query($command);
                // ต้อง escape — database.sql ของ GCMS มี INSERT แม่แบบอีเมลที่เป็น HTML
                // ถ้าพ่นตรง ๆ เนื้อหาอีเมลจะถูกวาดลงกลางหน้าติดตั้ง
                $content[] = '<li class="correct">'.htmlspecialchars(mb_strimwidth($command, 0, 300, '…'), ENT_QUOTES).'</li>';
            } catch (\PDOException $ex) {
                $error = true;
                $content[] = '<li class="incorrect">'.$ex->getMessage().'</li>';
            }
        }
    }
    if (!$error) {
        try {
            // ผู้ดูแลระบบสูงสุด — สร้างเฉพาะบัญชีนี้บัญชีเดียว
            //
            // ของเดิมสร้างบัญชีตัวอย่าง id 2-5 ที่ "รหัสผ่านเท่ากับชื่อผู้ใช้"
            // และเปิดใช้งานอยู่ (active = 1) ติดมาจากระบบซ่อมบำรุงคนละระบบ
            // (สิทธิ can_repair ที่ไม่มีอยู่จริงในโปรเจ็คนี้) ทุกเครื่องที่ติดตั้ง
            // ด้วยตัวติดตั้งชุดนี้จึงมีบัญชีที่เดารหัสผ่านได้ทันทีสี่บัญชี
            $password_key = uniqid();
            $username = $_SESSION['admin_username'];
            createAdmin($db, $_SESSION['prefix'].'_user', $username, $_SESSION['admin_password'], $password_key);
        } catch (\PDOException $ex) {
            $error = true;
            $content[] = '<li class="incorrect">'.$ex->getMessage().'</li>';
        }
    }
    if (!$error) {
        // ค่ากำหนดของไซต์ใหม่ — ผ่านฟังก์ชันเดียวกับที่ตัวปรับรุ่นใช้ เพื่อให้
        // เครื่องที่ติดตั้งใหม่ได้ค่ากำหนดชุดเดียวกับเครื่องที่ปรับรุ่นมา
        $cfg = ensureConfigDefaults(include ROOT_PATH.'install/settings/config.php', $new_config);
        $cfg['password_key'] = $password_key;
        // ประเภทเว็บไซต์ที่เลือกไว้ที่ step2 — ธีม/ค่ากำหนด + ข้อมูลตัวอย่าง + ไฟล์ตัวอย่าง
        // ถ้านำเข้าข้อมูลตัวอย่างไม่สำเร็จ ยังไม่บันทึกไฟล์ค่ากำหนด (ติดตั้งใหม่ได้ทันที)
        $site_type = isset($_SESSION['site_type']) ? (string) $_SESSION['site_type'] : '';
        $with_sample = isset($_SESSION['with_sample']) ? (bool) $_SESSION['with_sample'] : true;
        $site_email = filter_var($username, FILTER_VALIDATE_EMAIL) ? $username : '';
        if (!applySiteType($db, $_SESSION['prefix'], $site_type, $with_sample, ['SITE_EMAIL' => $site_email], $content, $cfg)) {
            $error = true;
        }
    }
    if (!$error) {
        // บันทึก settings/database.php
        $database_cfg = include ROOT_PATH.'install/settings/database.php';
        $database_cfg['mysql']['username'] = $_SESSION['db_username'];
        $database_cfg['mysql']['password'] = $_SESSION['db_password'];
        $database_cfg['mysql']['dbname'] = $_SESSION['db_name'];
        $database_cfg['mysql']['hostname'] = $_SESSION['db_server'];
        $database_cfg['mysql']['port'] = $_SESSION['db_port'];
        $database_cfg['mysql']['prefix'] = $_SESSION['prefix'];
        $f = save($database_cfg, ROOT_PATH.'settings/database.php');
        $content[] = '<li class="'.($f ? 'correct' : 'incorrect').'">สร้างไฟล์ตั้งค่า <b>database.php</b> ...</li>';
        // บันทึก settings/config.php (ค่าที่เตรียมไว้ข้างบน รวมค่าของประเภทเว็บไซต์แล้ว)
        $f = save($cfg, ROOT_PATH.'settings/config.php');
        $content[] = '<li class="'.($f ? 'correct' : 'incorrect').'">สร้างไฟล์ตั้งค่า <b>config.php</b> ...</li>';
        // นำเข้าภาษา
        $db_config = ['prefix' => $_SESSION['prefix']];
        include ROOT_PATH.'install/language.php';
    }
    if (!$error) {
        // ล้างค่าของตัวติดตั้งทั้งหมด (รวมรหัสผ่านฐานข้อมูลและผู้ดูแล) ออกจาก session
        // ของเดิมใช้ unset($_SESSION) ซึ่ง PHP ไม่ได้ลบข้อมูลใน session ให้จริง
        $_SESSION = [];
        echo '<h2>ติดตั้งเรียบร้อย</h2>';
        echo '<p>การติดตั้งได้ดำเนินการเสร็จเรียบร้อยแล้ว หากคุณต้องการความช่วยเหลือในการใช้งาน คุณสามารถ ติดต่อสอบถามได้ที่ <a href="https://www.kotchasan.com" target="_blank">https://www.kotchasan.com</a></p>';
        echo '<ul>'.implode('', $content).'</ul>';
        echo '<p class=warning>กรุณาลบไดเร็คทอรี่ <em>install/</em> ออกจาก Server ของคุณ</p>';
        echo '<p>คุณควรปรับ chmod ให้ไดเร็คทอรี่ <em>datas/</em> และ <em>settings/</em> (และไดเร็คทอรี่อื่นๆที่คุณได้ปรับ chmod ไว้ก่อนการติดตั้ง) ให้เป็น 644 ก่อนดำเนินการต่อ (ถ้าคุณได้ทำการปรับ chmod ไว้ด้วยตัวเอง)</p>';
        echo '<p>เมื่อเรียบร้อยแล้ว กรุณา<b>เข้าระบบ</b>เพื่อตั้งค่าที่จำเป็นอื่นๆโดยใช้ขื่ออีเมล <em>'.htmlspecialchars($username, ENT_QUOTES).'</em> และรหัสผ่านตามที่ได้ลงทะเบียนไว้</p>';
        echo '<p><a href="../" class="btn btn-primary large">เข้าระบบ</a></p>';
    } elseif (!empty($content)) {
        echo '<h2>ติดตั้งไม่สำเร็จ</h2>';
        echo '<p>การติดตั้งยังไม่สมบูรณ์ ลองตรวจสอบข้อผิดพลาดที่เกิดขึ้นและแก้ไขดู หากคุณต้องการความช่วยเหลือการติดตั้ง คุณสามารถ ติดต่อสอบถามได้ที่ <a href="https://www.kotchasan.com" target="_blank">https://www.kotchasan.com</a></p>';
        echo '<ul>'.implode('', $content).'</ul>';
        echo '<p><a href="." class="btn btn-primary large">ลองใหม่</a></p>';
    }
}

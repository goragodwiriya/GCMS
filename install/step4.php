<?php
/**
 * install/step4.php — ค่ากำหนดของฐานข้อมูล (ขั้นตอนการติดตั้ง)
 *
 * รับผู้ดูแลระบบจาก step3.php เก็บลง $_SESSION แล้วแสดงฟอร์มฐานข้อมูล
 * ฟอร์มนี้ส่งต่อไปติดตั้งจริงที่ step5.php
 */
if (defined('ROOT_PATH')) {
    if (isset($_POST['username']) || isset($_POST['password'])) {
        // มาจากฟอร์มผู้ดูแล — ค่าที่เพิ่งกรอกต้องครบ ไม่งั้นกลับไปกรอกใหม่
        if (empty($_POST['username']) || empty($_POST['password'])) {
            unset($_SESSION['admin_username'], $_SESSION['admin_password']);
        } else {
            include_once ROOT_PATH.'Kotchasan/Text.php';
            $_SESSION['admin_username'] = \Kotchasan\Text::username($_POST['username']);
            $_SESSION['admin_password'] = \Kotchasan\Text::password($_POST['password']);
        }
    }
    if (empty($_SESSION['admin_username']) || empty($_SESSION['admin_password'])) {
        include ROOT_PATH.'install/step3.php';
    } else {
        // database settings
        $database = include ROOT_PATH.'install/settings/database.php';
        $db_username = isset($_SESSION['db_username']) ? $_SESSION['db_username'] : $database['mysql']['username'];
        $db_password = isset($_SESSION['db_password']) ? $_SESSION['db_password'] : $database['mysql']['password'];
        $db_server = isset($_SESSION['db_server']) ? $_SESSION['db_server'] : 'localhost';
        $db_port = isset($_SESSION['db_port']) ? $_SESSION['db_port'] : 3306;
        $db_name = isset($_SESSION['db_name']) ? $_SESSION['db_name'] : $database['mysql']['dbname'];
        $prefix = isset($_SESSION['prefix']) ? $_SESSION['prefix'] : $database['mysql']['prefix'];
        echo '<form method=post action=index.php autocomplete=off>';
        echo '<h2>ค่ากำหนดของฐานข้อมูล</h2>';
        echo '<p>คุณจะต้องระบุข้อมูลการเชื่อมต่อที่ถูกต้องด้านล่างเพื่อเริ่มดำเนินการติดตั้งฐานข้อมูล</p>';
        echo '<p class=item><label for=db_username>ชื่อผู้ใช้</label><span class="form-control icon-user"><input type=text size=50 id=db_username name=db_username value="'.htmlspecialchars($db_username, ENT_QUOTES).'"></span></p>';
        echo '<p class=comment>ชื่อผู้ใช้ของ MySQL ของคุณ</p>';
        echo '<p class=item><label for=db_password>รหัสผ่าน</label><span class="form-control icon-password"><input type=password size=50 id=db_password name=db_password value="'.htmlspecialchars($db_password, ENT_QUOTES).'"></span></p>';
        echo '<p class=comment>รหัสผ่านของ MySQL ของคุณ</p>';
        echo '<p class=item><label for=db_server>โฮสท์ของฐานข้อมูล</label><span class="form-control icon-world"><input type=text size=50 id=db_server name=db_server value="'.htmlspecialchars($db_server, ENT_QUOTES).'"></span></p>';
        echo '<p class=comment>ดาตาเบสเซิร์ฟเวอร์ของคุณ (โฮสท์ส่วนใหญ่ใช้ localhost)</p>';
        echo '<p class=item><label for=db_port>พอร์ต</label><span class="form-control icon-link"><input type=text size=50 id=db_port name=db_port value="'.htmlspecialchars($db_port, ENT_QUOTES).'"></span></p>';
        echo '<p class=comment>ดาตาเบสเซิร์ฟเวอร์พอร์ตของคุณ (โฮสท์ส่วนใหญ่ใช้ 3306)</p>';
        echo '<p class=item><label for=db_name>ชื่อฐานข้อมูล</label><span class="form-control icon-database"><input type=text size=50 id=db_name name=db_name value="'.htmlspecialchars($db_name, ENT_QUOTES).'"></span></p>';
        echo '<p class=comment>ชื่อฐานข้อมูลที่ใช้ในการติดตั้งโปรแกรม ต้องเป็นฐานข้อมูลที่มีอยู่แล้ว หรือสร้างฐานข้อมูลก่อน</p>';
        echo '<p class=item><label for=prefix>คำนำหน้าตาราง</label><span class="form-control icon-table"><input type=text size=50 id=prefix name=prefix value="'.htmlspecialchars($prefix, ENT_QUOTES).'"></span></p>';
        echo '<p class=comment>ใช้สำหรับแยกฐานข้อมูลที่กำลังจะติดตั้งออกจากฐานข้อมูลอื่นๆ หากมีการติดตั้งข้อมูลอื่นๆร่วมกันบนฐานข้อมูลนี้</p>';
        echo '<input type=hidden name=step value=5>';
        echo '<p class="submit"><a href="index.php?step=3" class="btn large btn-secondary">ย้อนกลับ</a> ';
        echo '<button class="btn large btn-primary" type=submit>ติดตั้ง</button></p>';
        echo '</form>';
    }
}

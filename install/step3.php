<?php
/**
 * install/step3.php — ฟอร์มสมาชิกผู้ดูแลระบบ (ขั้นตอนการติดตั้ง)
 *
 * รับประเภทเว็บไซต์จาก step2.php เก็บลง $_SESSION แล้วแสดงฟอร์มผู้ดูแล
 * เนื้อหาฟอร์มอยู่ที่ adminForm() ใน install/common.php เพราะการปรับรุ่น
 * (install/upgrade1.php) ใช้ฟอร์มเดียวกันนี้
 */
if (defined('ROOT_PATH')) {
    include_once ROOT_PATH.'install/seeds.php';
    $site_type_warning = '';
    if (isset($_POST['site_type'])) {
        $types = siteTypes();
        $type = (string) $_POST['site_type'];
        if (isset($types[$type]) || ($type === '' && empty($types))) {
            $_SESSION['site_type'] = $type;
            // checkbox ที่ไม่ติ๊กจะไม่ถูกส่งมาเลย จึงอ่านจาก POST ตรง ๆ ได้
            $_SESSION['with_sample'] = !empty($_POST['with_sample']);
        } else {
            unset($_SESSION['site_type']);
            $site_type_warning = 'กรุณาเลือกประเภทเว็บไซต์';
        }
    }
    if (!isset($_SESSION['site_type'])) {
        // ยังไม่ได้เลือกประเภท (เปิด ?step=3 ตรง ๆ หรือค่าที่ส่งมาไม่ถูกต้อง)
        include ROOT_PATH.'install/step2.php';
    } else {
        adminForm(4, false);
    }
}

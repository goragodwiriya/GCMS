<?php
/**
 * @filesource install/seeds/school/config.php
 *
 * ค่าที่ตัวติดตั้งรวมเข้า settings/config.php เมื่อเลือกประเภท
 * "เว็บไซต์หน่วยงานราชการ / อบต. / โรงเรียน" (install/seeds.php applySiteType())
 * ห้ามมีค่าความลับ (password_key, api_*, jwt_*)
 */
return [
    // themes/gts — หน้าแรกอ่าน module=announce / news / knowledge / download /
    // gallery / video / forum / personnel / event ที่ seed.sql สร้าง
    'skin' => 'gts',
    'web_description' => 'เว็บไซต์โรงเรียน ข่าวประชาสัมพันธ์ ประกาศ กิจกรรม บุคลากร และเอกสารสำหรับนักเรียนและผู้ปกครอง'
];

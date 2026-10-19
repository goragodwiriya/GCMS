<?php
/**
 * @filesource install/seeds/company/config.php
 *
 * ค่าที่ตัวติดตั้งรวมเข้า settings/config.php เมื่อเลือกประเภท "เว็บไซต์บริษัท / ข่าวสาร"
 * (install/seeds.php applySiteType()) — ห้ามมีค่าความลับ (password_key, api_*, jwt_*)
 */
return [
    // themes/rw — หน้าแรกอ่าน module=news (highlight/carousel/icon) และ module=forum
    // ที่ seed.sql สร้าง
    'skin' => 'rw',
    'web_description' => 'บริการออกแบบเว็บไซต์ ระบบองค์กร และโซลูชันดิจิทัล พร้อมข่าวสาร บทความ และผลงานของเรา'
];

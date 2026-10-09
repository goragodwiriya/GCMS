<?php
/**
 * @filesource install/seeds/shop/config.php
 *
 * ค่าที่ตัวติดตั้งรวมเข้า settings/config.php เมื่อเลือกประเภท
 * "เว็บไซต์ร้านค้าออนไลน์ (e-commerce)" (install/seeds.php applySiteType())
 * ห้ามมีค่าความลับ (password_key, api_*, jwt_*)
 */
return [
    // themes/shop — หน้าแรกอ่าน module=product (carousel category=1 และ thumb),
    // module=news / gallery / video / forum ที่ seed.sql สร้าง
    'skin' => 'shop',
    'web_description' => 'ร้านค้าออนไลน์ของใช้ ของแต่งบ้าน แฟชั่น และของฝาก จัดส่งทั่วประเทศ'
];

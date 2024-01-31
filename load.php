<?php
/**
 * load.php
 *
 * @author Goragod Wiriya <admin@goragod.com>
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */
/**
 * สำหรับจับเวลาประมวลผลเพจ
 */
define('REQUEST_TIME', microtime(true));
/**
 * document root (full path)
 * eg /home/user/public_html/
 *
 * @var string
 */
define('ROOT_PATH', str_replace('\\', '/', dirname(__FILE__)).'/');
/**
 * โฟลเดอร์เก็บข้อมูล
 *
 * @var string
 */
define('DATA_FOLDER', 'datas/');
/**
 * 0 (default) บันทึกเฉพาะข้อผิดพลาดร้ายแรงลง error_log.php
 * 1 บันทึกข้อผิดพลาดและคำเตือนลง error_log.php
 * 2 แสดงผลข้อผิดพลาดและคำเตือนออกทางหน้าจอ (ใช้เฉพาะตอนออกแบบเท่านั้น)
 *
 * @var int
 */
define('DEBUG', 0);
/**
 * ไฟล์ตั้งค่าระบบ (full path)
 */
define('CONFIG', ROOT_PATH.'settings/config.php');
/*
 * false (default)
 * true บันทึกการ query ฐานข้อมูลลง log (ใช้เฉพาะตอนออกแบบเท่านั้น)
 *
 * @var bool
 */
define('DB_LOG', false);
/**
 * ภาษาเริ่มต้น
 * auto = อัตโนมัติจากบราวเซอร์
 * th, en ตามภาษาที่เลือก
 *
 * @var string
 */
define('INIT_LANGUAGE', 'th');
/*
 * ระบุ SQL Mode ที่ต้องการ
 * หากพบปัญหาการใช้งาน
 *
 * @var string
 */
//define('SQL_MODE', '');
/**
 * load Kotchasan
 */
include 'Kotchasan/load.php';

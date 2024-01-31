<?php
/**
 * cron.php
 * หน้าเพจสำหรับให้ Cron เรียกใช้
 *
 * @author Goragod Wiriya <admin@goragod.com>
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */
// load Kotchasan
include 'load.php';
// สำหรับบอกว่ามาจากการเรียกโดย cron
define('MAIN_INIT', 'cron');
// Initial Kotchasan Framework
$app = Kotchasan::createWebApplication('Gcms\Config');
$app->defaultController = 'Index\Cron\Controller';
$app->run();

<?php
/**
 * index.php
 *
 * @author Goragod Wiriya <admin@goragod.com>
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */
if (is_file('settings/config.php') && is_file('settings/database.php')) {
    // load Kotchasan
    include 'load.php';
    // Initial Kotchasan Framework
    $app = Kotchasan::createWebApplication('Gcms\Config');
    $app->defaultRouter = 'Gcms\Router';
    $app->run();
} elseif (is_file('install/index.php')) {
    // ติดตั้ง
    header('Location: ./install/index.php');
}

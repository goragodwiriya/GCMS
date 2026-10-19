<?php
/**
 * Root test bootstrap — autoloads Kotchasan\, Gcms\, Web\ classes and module
 * classes (Index\Name\Model → modules/index/models/name.php) from the project
 * tree so code can be unit-tested without the full app bootstrap (no DB / Config).
 */

define('PROJECT_ROOT', dirname(__DIR__));
define('ROOT_PATH', PROJECT_ROOT.'/');
define('DATA_FOLDER', 'datas/');

spl_autoload_register(function ($class) {
    foreach (['Kotchasan\\', 'Gcms\\', 'Web\\'] as $prefix) {
        if (strpos($class, $prefix) === 0) {
            $file = PROJECT_ROOT.'/'.str_replace('\\', '/', $class).'.php';
            if (is_file($file)) {
                require_once $file;
            }

            return;
        }
    }
    // Index\Aithemecolor\Model → modules/index/models/aithemecolor.php
    $parts = explode('\\', $class);
    if (count($parts) === 3) {
        $folders = ['Model' => 'models', 'Controller' => 'controllers', 'View' => 'views'];
        if (isset($folders[$parts[2]])) {
            $file = PROJECT_ROOT.'/modules/'.strtolower($parts[0]).'/'.$folders[$parts[2]].'/'.strtolower($parts[1]).'.php';
            if (is_file($file)) {
                require_once $file;
            }
        }
    }
});

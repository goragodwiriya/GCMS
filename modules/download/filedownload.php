<?php
/**
 * @filesource modules/download/filedownload.php
 *
 * @copyright 2026 Goragod.com
 * @license https://www.kotchasan.com/license/
 */

@session_cache_limiter('none');
@session_start();

$token = isset($_GET['id']) ? preg_replace('/[^a-f0-9]/', '', (string) $_GET['id']) : '';
$item = null;
if ($token !== '' && isset($_SESSION['download_files'][$token]) && is_array($_SESSION['download_files'][$token])) {
    $item = $_SESSION['download_files'][$token];
    unset($_SESSION['download_files'][$token]);
}

if (!$item || empty($item['file']) || !is_file($item['file'])) {
    header('HTTP/1.0 404 Not Found');
    exit;
}

$file = $item['file'];
$size = !empty($item['size']) ? (int) $item['size'] : (int) filesize($file);
$name = isset($item['name']) ? (string) $item['name'] : basename($file);
$name = str_replace(["\r", "\n"], '', $name);

while (ob_get_level() > 0) {
    @ob_end_clean();
}

$f = @fopen($file, 'rb');
if ($f === false) {
    header('HTTP/1.0 404 Not Found');
    exit;
}

$encodedName = rawurlencode($name);

header('Pragma: public');
header('Expires: 0');
header('Cache-Control: private, must-revalidate');
header('Content-Description: File Transfer');
header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename="'.$name.'"; filename*=UTF-8\'\''.$encodedName);
header('Content-Length: '.$size);
header('Accept-Ranges: bytes');

while (!feof($f)) {
    echo @fread($f, 8192);
    @flush();
    if (connection_status() !== CONNECTION_NORMAL) {
        break;
    }
}

@fclose($f);
exit;

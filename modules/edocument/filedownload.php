<?php
/**
 * @filesource modules/edocument/filedownload.php
 *
 * Streams a file prepared by Edocument\Download\Controller::action()
 * through a one-time session token.
 *
 * @copyright 2026 Goragod.com
 * @license https://www.kotchasan.com/license/
 */

@session_cache_limiter('none');
@session_start();

$token = isset($_GET['id']) ? preg_replace('/[^a-f0-9]/', '', (string) $_GET['id']) : '';
$item = null;
if ($token !== '' && isset($_SESSION['edocument_files'][$token]) && is_array($_SESSION['edocument_files'][$token])) {
    $item = $_SESSION['edocument_files'][$token];
    unset($_SESSION['edocument_files'][$token]);
}
session_write_close();

if (!$item || empty($item['file']) || !is_file($item['file'])) {
    header('HTTP/1.0 404 Not Found');
    exit;
}

$file = $item['file'];
$size = (int) filesize($file);
$name = isset($item['name']) ? (string) $item['name'] : basename($file);
$name = str_replace(["\r", "\n", '"'], '', $name);
$inline = !empty($item['inline']);
$mime = $inline && !empty($item['mime']) ? (string) $item['mime'] : 'application/octet-stream';

while (ob_get_level() > 0) {
    @ob_end_clean();
}

$f = @fopen($file, 'rb');
if ($f === false) {
    header('HTTP/1.0 404 Not Found');
    exit;
}

header('Pragma: public');
header('Expires: 0');
header('Cache-Control: private, must-revalidate');
header('X-Content-Type-Options: nosniff');
header('Content-Type: '.$mime);
header('Content-Disposition: '.($inline ? 'inline' : 'attachment').'; filename="'.$name.'"; filename*=UTF-8\'\''.rawurlencode($name));
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

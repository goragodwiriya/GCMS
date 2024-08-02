<?php
/**
 * BingSiteAuth.php
 *
 * @author Goragod Wiriya <admin@goragod.com>
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */
// load Kotchasan
include 'load.php';
// config
$cfg = Gcms\Config::create();
if (empty($cfg->msvalidate)) {
    new \Kotchasan\Http\NotFound();
} else {
    $response = new Kotchasan\Http\Response();
    $content = '<'.'?xml version="1.0"?'.'>';
    $content .= "\n<users>";
    $content .= "\n\t<user>".$cfg->msvalidate.'</user>';
    $content .= "\n</users>";
    $response->withContent($content)
        ->withHeader('Content-Type', 'application/xml; charset=UTF-8')
        ->send();
}

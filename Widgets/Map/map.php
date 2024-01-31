<?php
/**
 * map.php
 *
 * @author Goragod Wiriya <admin@goragod.com>
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */
$lng = isset($_GET['lang']) ? preg_replace('/[^a-z]/', '', $_GET['lang']) : 'th';
$api_key = isset($_GET['api_key']) ? htmlspecialchars($_GET['api_key']) : '';
$latitude = isset($_GET['latitude']) ? preg_replace('/[^0-9\.]/', '', $_GET['latitude']) : '';
$lantitude = isset($_GET['lantitude']) ? preg_replace('/[^0-9\.]/', '', $_GET['lantitude']) : '';
$zoom = isset($_GET['zoom']) ? (int) $_GET['zoom'] : 16;
$info = isset($_GET['info']) ? $_GET['info'] : '';
$info = str_replace(array('&lt;', '&gt;', '&#92;', "\r", "\n"), array('<', '>', '\\', '', '<br>'), $info);
$info = preg_replace('/<script.*\/script>/is', '', $info);
$info_latitude = isset($_GET['info_latitude']) ? preg_replace('/[^0-9\.]/', '', $_GET['info_latitude']) : '';
$info_lantitude = isset($_GET['info_lantitude']) ? preg_replace('/[^0-9\.]/', '', $_GET['info_lantitude']) : '';
$map = array('<!DOCTYPE html>');
$map[] = '<html lang='.$lng.' dir=ltr>';
$map[] = '<head>';
$map[] = '<title>Google Map</title>';
$map[] = '<style>';
$map[] = 'html,body,#map_canvas{height:100%}';
$map[] = 'body{margin:0 auto;padding:0;font-family:Tahoma;font-size:12px;text-align:center;line-height:1.5em}';
$map[] = '</style>';
$map[] = '<script src="//maps.google.com/maps/api/js?key='.$api_key.'&language='.$lng.'"></script>';
$map[] = '<meta charset=utf-8>';
$map[] = '<script>';
$map[] = 'function initialize() {';
$map[] = 'var myLatlng = new google.maps.LatLng("'.$latitude.'","'.$lantitude.'");';
$map[] = 'var myOptions = {';
$map[] = 'zoom:'.$zoom.',';
$map[] = 'center:myLatlng,';
$map[] = 'mapTypeId:google.maps.MapTypeId.ROADMAP';
$map[] = '};';
$map[] = 'var map = new google.maps.Map(document.getElementById("map_canvas"), myOptions);';

if (!empty($info)) {
    $map[] = "var infowindow = new google.maps.InfoWindow({content:'".$info."'});";
    $map[] = 'var info = new google.maps.LatLng("'.$info_latitude.'","'.$info_lantitude.'");';
    $map[] = 'var marker = new google.maps.Marker({position:info,map:map});';
    $map[] = 'infowindow.open(map,marker);';
    $map[] = 'google.maps.event.addListener(marker,"click",function(){';
    $map[] = 'infowindow.open(map,marker);';
    $map[] = '});';
}
$map[] = '}';
$map[] = '</script>';
$map[] = '</head>';
$map[] = '<body onload="initialize()">';
$map[] = '<div id=map_canvas>Google Map</div>';
$map[] = '</body>';
$map[] = '</html>';
echo implode("\n", $map);

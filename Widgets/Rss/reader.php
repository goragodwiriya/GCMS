<?php
// widgets/rss/reader.php
error_reporting(E_ALL ^ E_NOTICE);
// ค่าที่ส่งมา
$qs = array();
foreach ($_POST as $key => $value) {
    $$key = $value;
    if ($key == 'rnd') {
        $qs[] = 'rnd';
    } elseif ($key != 'url') {
        $qs[] = "$key=".rawurlencode($value);
    }
}
if (count($qs) > 0) {
    $url .= (preg_match('/[\?]/u', $url) ? '&' : '?').implode('&', $qs);
}
if (function_exists('curl_init') && $ch = @curl_init()) {
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_HEADER, 0);
    curl_setopt($ch, CURLOPT_POST, 0);
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    $contents = curl_exec($ch);
    curl_close($ch);
} else {
    $contents = @file_get_contents($url);
}
if ($contents != '') {
    $charset = getXMLHeader($contents);
    $charset = ($charset == '') ? 'utf-8' : strtolower($charset);
    header("content-type: text/html; charset=$charset");
    if ($charset == 'utf-8' && function_exists('mb_internal_encoding')) {
        mb_internal_encoding('utf-8');
    }
    $rss = RSStoArray($contents);
    $listcount = $rows * $cols;
    echo '<table class="'.$className.'"><tr>';
    for ($i = 0; $i < count($rss) && $listcount > 0; ++$i) {
        if ($i > 0 && $i % $cols == 0) {
            echo '</tr><tr>';
        }
        echo '<td>';
        $image = $rss[$i]['media:thumbnail']['url'] == '' ? $rss[$i]['enclosure']['url'] : $rss[$i]['media:thumbnail']['url'];
        if ($rssimage && $image != '') {
            if (!preg_match('/^http(s)?:\/\/.*$/', $image)) {
                $urls = parse_url($url);
                $image = "$urls[scheme]://$urls[host]$image";
            }
            echo '<a href="'.$rss[$i]['link']['data'].'" target="_blank" class="thumbnail"><img src="'.$image.'" alt="" class="nozoom"></a>';
            echo '<a href="'.$rss[$i]['link']['data'].'" target="_blank" class="topic">'.$rss[$i]['title']['data'].'</a>';
        } else {
            echo '<a class="icon-rss topic" href="'.$rss[$i]['link']['data'].'" target="_blank">'.$rss[$i]['title']['data'].'</a>';
        }
        echo '<span>'._cutstr($rss[$i]['description']['data'], 0, $detaillen).'</span>';
        echo '</td>';
        --$listcount;
    }
    echo '</tr></table>';
}

/**
 * @param  $xml
 *
 * @return mixed
 */
function getXMLHeader($xml)
{
    $headers = explode('<'.'?xml', $xml);
    $ret = '';
    for ($i = 0; $i < count($headers); ++$i) {
        $ret .= parseXMLHeader(trim($headers[$i]));
    }
    return $ret;
}

/**
 * @param $data
 */
function parseXMLHeader($data)
{
    if ($data != '') {
        $EndPos = _strpos($data, '?>');
        $datas = explode(' ', _substr($data, 0, $EndPos));
        for ($i = 0; $i < count($datas); ++$i) {
            $temps = explode('=', $datas[$i]);
            if (trim($temps[0]) == 'encoding') {
                $value = trim($temps[1]);
                $value = str_replace('"', '', $value);
                $value = str_replace("'", '', $value);
                return $value;
            }
        }
    }
    return;
}

/**
 * @param  $xml
 *
 * @return mixed
 */
function RSStoArray($xml)
{
    $items = preg_split('/<item[\s|>]/', $xml, -1, PREG_SPLIT_NO_EMPTY);
    array_shift($items);
    $i = 0;
    foreach ($items as $item) {
        $array[$i]['title'] = getTextBetweenTags($item, 'title');
        $array[$i]['link'] = getTextBetweenTags($item, 'link');
        $array[$i]['description'] = getTextBetweenTags($item, 'description');
        $array[$i]['author'] = getTextBetweenTags($item, 'author');
        $array[$i]['category'] = getTextBetweenTags($item, 'category');
        $array[$i]['comments'] = getTextBetweenTags($item, 'comments');
        $array[$i]['enclosure'] = getTextBetweenTags($item, 'enclosure');
        $array[$i]['guid'] = getTextBetweenTags($item, 'guid');
        $array[$i]['pubDate'] = getTextBetweenTags($item, 'pubDate');
        $array[$i]['source'] = getTextBetweenTags($item, 'source');
        if (preg_match('/<img.*src=\"?(http:\/\/.*\.(jpg|gif|png))\".*>/', $array[$i]['description']['data'], $match)) {
            $array[$i]['enclosure']['url'] = $match[1];
            $typies = array('jpg' => 'image/jpeg', 'gif' => 'image/gif', 'png' => 'image/png');
            $array[$i]['enclosure']['type'] = $typies[$match[2]];
        } else {
            $array[$i]['media:thumbnail'] = getTextBetweenTags($item, 'media:thumbnail');
            $array[$i]['enclosure'] = getTextBetweenTags($item, 'enclosure');
        }
        $array[$i]['description']['data'] = strip_tags($array[$i]['description']['data']);
        ++$i;
    }
    return $array;
}

/**
 * @param  $text
 * @param  $tag
 *
 * @return mixed
 */
function getTextBetweenTags($text, $tag)
{
    $StartTag = "<$tag";
    $EndTag = "</$tag";
    $StartPosTemp = _strpos($text, $StartTag);
    $StartPos = _strpos($text, '>', $StartPosTemp);
    $StartPos = $StartPos + 1;
    $EndPos = _strpos($text, $EndTag);
    $StartAttr = $StartPosTemp + _strlen($StartTag) + 1;
    $EndAttr = $StartPos;
    if ($EndAttr > $StartAttr) {
        $attribute = _substr($text, $StartAttr, $EndAttr - $StartAttr - 1);
        $datas = explode(' ', $attribute);
        for ($i = 0; $i < count($datas); ++$i) {
            if (preg_match('/^([a-zA-Z:]+)=["\'](.*)["\']/', $datas[$i], $match)) {
                $items[$match[1]] = $match[2];
            }
        }
    }
    $text = _substr($text, $StartPos, ($EndPos - $StartPos));
    if (_strpos($text, '[CDATA[') == false) {
        $text = str_replace('&lt;', '<', $text);
        $text = str_replace('&gt;', '>', $text);
        $text = str_replace('&amp;', '&', $text);
        $text = str_replace('&quot;', '"', $text);
    } else {
        $text = str_replace('<![CDATA[', '', $text);
        $text = str_replace(']]>', '', $text);
    }
    $items['data'] = trim($text);
    return $items;
}

/**
 * @param $str
 * @param $from
 * @param $len
 */
function _substr($str, $from, $len)
{
    global $charset;
    if ($charset == 'utf-8') {
        return mb_substr($str, $from, $len);
    } else {
        return substr($str, $from, $len);
    }
}

/**
 * @param $str
 * @param $from
 * @param $len
 */
function _cutstr($str, $from, $len)
{
    global $charset;
    if ($charset == 'utf-8') {
        return (mb_strlen($str) <= $len || $len < 3) ? $str : mb_substr($str, $from, $len - 2).'..';
    } else {
        return (strlen($str) <= $len || $len < 3) ? $str : substr($str, $from, $len - 2).'..';
    }
}

/**
 * @param $data
 */
function _strlen($data)
{
    global $charset;
    if ($charset == 'utf-8') {
        return mb_strlen($str);
    } else {
        return strlen($data);
    }
}

/**
 * @param $haystack
 * @param $needle
 * @param $offset
 */
function _strpos($haystack, $needle, $offset = 0)
{
    global $charset;
    if ($charset == 'utf-8') {
        return mb_strpos($haystack, $needle, $offset);
    } else {
        return strpos($haystack, $needle, $offset);
    }
}

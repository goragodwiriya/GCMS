<?php
/**
 * @filesource Widgets/Rss/Controllers/Ready.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Widgets\Rss\Controllers;

use Kotchasan\Http\Request;

/**
 * Controller หลัก สำหรับแสดงผล Widget
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Reader extends \Kotchasan\Controller
{
    /**
     * @var mixed
     */
    public $charset;

    /**
     * อ่านข้อมูล RSS
     *
     * @param Request $request
     */
    public function get(Request $request)
    {
        // ค่าที่ส่งมา
        $url = $request->post('url')->url();
        $rows = $request->post('rows')->toInt();
        $cols = $request->post('cols')->toInt();
        $className = $request->post('className')->topic();
        $rssimage = $request->post('rssimage')->toBoolean();
        // โหลด URL
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
            $this->charset = $this->getXMLHeader($contents);
            $this->charset = ($this->charset == '') ? 'utf-8' : strtolower($this->charset);
            $rss = $this->RSStoArray($contents);
            $listcount = $rows * $cols;
            echo '<table class="'.$className.'"><tr>';
            for ($i = 0; $i < count($rss) && $listcount > 0; ++$i) {
                if ($i > 0 && $i % $cols == 0) {
                    echo '</tr><tr>';
                }
                echo '<td>';
                if (empty($rss[$i]['media:thumbnail']['url'])) {
                    $image = empty($rss[$i]['enclosure']['url']) ? '' : $rss[$i]['enclosure']['url'];
                } else {
                    $image = $rss[$i]['media:thumbnail']['url'];
                }
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
                echo '<span>'.$rss[$i]['description']['data'].'</span>';
                echo '</td>';
                --$listcount;
            }
            echo '</tr></table>';
        }
    }

    /**
     * @param  $xml
     *
     * @return mixed
     */
    private function getXMLHeader($xml)
    {
        $headers = explode('<'.'?xml', $xml);
        $ret = '';
        for ($i = 0; $i < count($headers); ++$i) {
            $ret .= $this->parseXMLHeader(trim($headers[$i]));
        }
        return $ret;
    }

    /**
     * @param $data
     */
    private function parseXMLHeader($data)
    {
        if ($data != '') {
            $EndPos = $this->strpos($data, '?>');
            $datas = explode(' ', $this->substr($data, 0, $EndPos));
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
    private function RSStoArray($xml)
    {
        $items = preg_split('/<item[\s|>]/', $xml, -1, PREG_SPLIT_NO_EMPTY);
        array_shift($items);
        $i = 0;
        $array = array();
        foreach ($items as $item) {
            $array[$i]['title'] = $this->getTextBetweenTags($item, 'title');
            $array[$i]['link'] = $this->getTextBetweenTags($item, 'link');
            $array[$i]['description'] = $this->getTextBetweenTags($item, 'description');
            $array[$i]['author'] = $this->getTextBetweenTags($item, 'author');
            $array[$i]['category'] = $this->getTextBetweenTags($item, 'category');
            $array[$i]['comments'] = $this->getTextBetweenTags($item, 'comments');
            $array[$i]['enclosure'] = $this->getTextBetweenTags($item, 'enclosure');
            $array[$i]['guid'] = $this->getTextBetweenTags($item, 'guid');
            $array[$i]['pubDate'] = $this->getTextBetweenTags($item, 'pubDate');
            $array[$i]['source'] = $this->getTextBetweenTags($item, 'source');
            if (preg_match('/<img.*src=\"?(http:\/\/.*\.(jpg|gif|png))\".*>/', $array[$i]['description']['data'], $match)) {
                $array[$i]['enclosure']['url'] = $match[1];
                $typies = array('jpg' => 'image/jpeg', 'gif' => 'image/gif', 'png' => 'image/png');
                $array[$i]['enclosure']['type'] = $typies[$match[2]];
            } else {
                $array[$i]['media:thumbnail'] = $this->getTextBetweenTags($item, 'media:thumbnail');
                $array[$i]['enclosure'] = $this->getTextBetweenTags($item, 'enclosure');
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
    private function getTextBetweenTags($text, $tag)
    {
        $StartTag = "<$tag";
        $EndTag = "</$tag";
        $StartPosTemp = $this->strpos($text, $StartTag);
        $StartPos = $this->strpos($text, '>', $StartPosTemp);
        $StartPos = $StartPos + 1;
        $EndPos = $this->strpos($text, $EndTag);
        $StartAttr = $StartPosTemp + $this->strlen($StartTag) + 1;
        $EndAttr = $StartPos;
        if ($EndAttr > $StartAttr) {
            $attribute = $this->substr($text, $StartAttr, $EndAttr - $StartAttr - 1);
            $datas = explode(' ', $attribute);
            for ($i = 0; $i < count($datas); ++$i) {
                if (preg_match('/^([a-zA-Z:]+)=["\'](.*)["\']/', $datas[$i], $match)) {
                    $items[$match[1]] = $match[2];
                }
            }
        }
        $text = $this->substr($text, $StartPos, ($EndPos - $StartPos));
        if ($this->strpos($text, '[CDATA[') == false) {
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
    private function substr($str, $from, $len)
    {
        if ($this->charset == 'utf-8') {
            return mb_substr($str, $from, $len);
        } else {
            return substr($str, $from, $len);
        }
    }

    /**
     * @param $data
     */
    private function strlen($data)
    {
        if ($this->charset == 'utf-8') {
            return mb_strlen($data);
        } else {
            return strlen($data);
        }
    }

    /**
     * @param $haystack
     * @param $needle
     * @param $offset
     */
    private function strpos($haystack, $needle, $offset = 0)
    {
        if ($this->charset == 'utf-8') {
            return mb_strpos($haystack, $needle, $offset);
        } else {
            return strpos($haystack, $needle, $offset);
        }
    }
}

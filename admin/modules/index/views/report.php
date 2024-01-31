<?php
/**
 * @filesource modules/index/views/report.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Index\Report;

use Kotchasan\DataTable;
use Kotchasan\Date;
use Kotchasan\Http\Request;

/**
 * module=report
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class View extends \Gcms\Adminview
{
    /**
     * @var mixed
     */
    private $date;
    /**
     * @var mixed
     */
    private $ip;

    /**
     * แสดงข้อมูลประวัติการเยียมชม
     *
     * @param Request $request
     * @param array  $params
     *
     * @return string
     */
    public function render(Request $request, $params)
    {
        $logs = \Index\Report\Model::logPerHour($params['date']);
        $thead = array();
        $tbody = array();
        for ($i = 0; $i < 24; ++$i) {
            $thead[] = '<th>'.sprintf('%02d', $i).'</th>';
            $tbody[] = '<td>'.(isset($logs[$i]) ? $logs[$i] : 0).'</td>';
        }
        $content = '<div id=report_graph class="ggraphs">';
        $content .= '<table class="hidden">';
        $content .= '<thead><tr><th>{LNG_hour}</th>'.implode('', $thead).'</tr></thead>';
        $content .= '<tbody>';
        $content .= '<tr><th scope=row>{LNG_People visit the site}</th>'.implode('', $tbody).'</tr>';
        $content .= '</tbody>';
        $content .= '</table>';
        $content .= '</div>';
        $content .= '<script>';
        $content .= 'new GGraphs("report_graph", {type: "vchart", showTitle: false});';
        $content .= '</script>';
        // ข้อมูล ที่กำลังแสดงอยู่
        $this->date = $params['date'];
        $this->ip = $params['ip'];
        // URL สำหรับส่งให้ตาราง
        $uri = $request->createUriWithGlobals(WEB_URL.'admin/index.php');
        $times = array(-1 => '{LNG_all items}');
        for ($i = 0; $i < 24; $i++) {
            $t = sprintf('%02d', $i);
            $times[$i] = $t.' ('.$t.':00-'.$t.':59)';
        }
        // ตาราง
        $table = new DataTable(array(
            /* Uri */
            'uri' => $uri,
            /* Model */
            'model' => \Index\Report\Model::get($params),
            /* รายการต่อหน้า */
            'perPage' => $request->cookie('counter_perPage', 30)->toInt(),
            /* เรียงลำดับ */
            'sort' => $request->cookie('counter_sort', 'time desc')->toString(),
            /* คอลัมน์ที่สามารถค้นหาได้ */
            'searchColumns' => array('referer', 'ip'),
            /* ฟังก์ชั่นจัดรูปแบบการแสดงผลแถวของตาราง */
            'onRow' => array($this, 'onRow'),
            /* ตัวเลือกด้านบนของตาราง ใช้จำกัดผลลัพท์การ query */
            'filters' => array(
                array(
                    'name' => 'h',
                    'text' => '{LNG_Time}',
                    'options' => $times,
                    'value' => $params['h']
                )
            ),
            /* ส่วนหัวของตาราง และการเรียงลำดับ (thead) */
            'headers' => array(
                'time' => array(
                    'text' => '{LNG_Time}',
                    'sort' => 'time'
                ),
                'ip' => array(
                    'text' => '{LNG_IP}',
                    'sort' => 'ip'
                ),
                'count' => array(
                    'text' => '{LNG_Count}',
                    'class' => 'center',
                    'sort' => 'count'
                ),
                'url' => array(
                    'text' => '{LNG_URL}',
                    'sort' => 'url'
                ),
                'referer' => array(
                    'text' => '{LNG_Referer}',
                    'class' => 'tablet',
                    'sort' => 'referer'
                ),
                'user_agent' => array(
                    'text' => '{LNG_User Agent}',
                    'class' => 'tablet',
                    'sort' => 'user_agent'
                )
            ),
            /* รูปแบบการแสดงผลของคอลัมน์ (tbody) */
            'cols' => array(
                'count' => array(
                    'class' => 'center'
                ),
                'referer' => array(
                    'class' => 'tablet'
                ),
                'user_agent' => array(
                    'class' => 'tablet'
                )
            )
        ));
        // save cookie
        setcookie('counter_perPage', $table->perPage, time() + 2592000, '/', HOST, HTTPS, true);
        setcookie('counter_sort', $table->sort, time() + 2592000, '/', HOST, HTTPS, true);
        // คืนค่า HTML
        return $content.$table->render();
    }

    /**
     * จัดรูปแบบการแสดงผลในแต่ละแถว
     *
     * @param array  $item ข้อมูลแถว
     * @param int    $o    ID ของข้อมูล
     * @param object $prop กำหนด properties ของ TR
     *
     * @return array คืนค่า $item กลับไป
     */
    public function onRow($item, $o, $prop)
    {
        $item['time'] = Date::format($item['time'], 'H:i:s');
        $item['url'] = '<a class="cuttext block" style="max-width:30vw" href="'.htmlspecialchars($item['url']).'" target=_blank>'.htmlspecialchars($item['url']).'</a>';
        if (preg_match_all('%(?P<browser>Firefox|Safari|MSIE|AppleWebKit|bingbot|MJ12bot|Baiduspider|Googlebot|DotBot|Twitterbot|LivelapBot|facebookexternalhit|StatusNet|PaperLiBot|SurdotlyBot|Trident|archive\.org_bot|Yahoo\!\sSlurp|Go[a-z\-]+)([\/\s](?P<version>[^;\s]+))?%ix', $item['user_agent'], $result, PREG_PATTERN_ORDER)) {
            $item['user_agent'] = '<span title="'.htmlspecialchars($item['user_agent']).'">'.$result['browser'][0].(empty($result['version'][0]) ? '' : '/'.$result['version'][0]).'</span>';
        } elseif ($item['user_agent'] != '') {
            $item['user_agent'] = '<span title="'.htmlspecialchars($item['user_agent']).'">unknown</span>';
        }
        if ($item['referer'] != '') {
            if (preg_match('/^(https?:\/\/[a-z]+\.google(.*)?.*)\/.*[\&\?](url|q)=([^&]+)($|\&.*)/iu', $item['referer'], $match)) {
                // จาก google
                $title = $match[4];
            } else {
                $title = $item['referer'];
            }
            $item['referer'] = '<a class="cuttext block" style="max-width:30vw" href="'.htmlspecialchars($item['referer']).'" target=_blank>'.htmlspecialchars($title).'</a>';
        }
        if ($this->ip == '') {
            $item['ip'] = '<a href="index.php?module=report&amp;ip='.$item['ip'].'&amp;date='.$this->date.'">'.$item['ip'].'</a>';
        } else {
            $item['ip'] = '<a href="https://www.dnswatch.info/dns/dnslookup?host='.$item['ip'].'" target=_blank>'.$item['ip'].'</a>';
        }
        return $item;
    }
}

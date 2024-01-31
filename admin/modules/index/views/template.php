<?php
/**
 * @filesource modules/index/views/template.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Index\Template;

use Kotchasan\Html;
use Kotchasan\Http\Request;
use Kotchasan\Language;

/**
 * รายการ template
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class View extends \Gcms\Adminview
{
    /**
     * แสดงรายการ template (template.php)
     *
     * @param Request $request
     * @param string  $dir
     * @param object  $config
     * @param array   $themes
     *
     * @return string
     */
    public function render(Request $request, $dir, $config, $themes)
    {
        // จำนวนแถวที่ต้องการ (3 column)
        $rows = 3;
        // theme ทั้งหมด
        $count = count($themes);
        // จำนวนรายการต่อหน้า
        $list_per_page = $rows * 3;
        // จำนวนหน้าทั้งหมด
        $totalpage = round($count / $list_per_page);
        $totalpage += ($totalpage * $list_per_page < $count) ? 1 : 0;
        // หน้าที่เลือก
        $page = $request->request('page', 1)->toInt();
        $page = ($page < 1) ? 1 : $page;
        $page = max(1, $page > $totalpage ? $totalpage : $page);
        // admin_template
        $div = Html::create('div', array(
            'id' => 'admin_template'
        ));
        // template ปัจจุบัน
        $info = $this->parseTheme("$dir/".$config->skin.'/style.css');
        if (!empty($info)) {
            $article = $div->add('article', array(
                'class' => 'current clear'
            ));
            $article->add('h2', array(
                'innerHTML' => '{LNG_Template is in use}'
            ));
            $article = $article->add('article', array(
                'class' => 'item bg-0'
            ));
            if (isset($info['responsive'])) {
                $article->add('span', array(
                    'class' => 'icon-responsive',
                    'title' => 'Responsive'
                ));
            }
            $article->add('span', array(
                'class' => 'preview',
                'title' => '{LNG_Templates in use}',
                'style' => 'background-image:url('.WEB_URL.'skin/'.$config->skin.'/screenshot.jpg)'
            ));
            $article->add('h3', array(
                'innerHTML' => $info['name']
            ));
            $article->add('p', array(
                'class' => 'detail',
                'innerHTML' => $info['description']
            ));
            $article->add('p', array(
                'class' => 'folder',
                'innerHTML' => str_replace('%s', $config->skin, Language::get('All template files are stored in <span>%s</span>'))
            ));
        }
        // template อื่นๆ
        $article = $div->add('article', array(
            'class' => 'list clear'
        ));
        $article->add('h2', array(
            'innerHTML' => '{LNG_Other templates}'
        ));
        // รายการแรกที่แสดง
        $c = 1;
        $n = 1;
        $start = $list_per_page * ($page - 1);
        $max = min($count, $start + $list_per_page);
        for ($r = $start; $r < $max; ++$r) {
            $text = $themes[$r];
            $info = $this->parseTheme("$dir/$text/style.css");
            ++$n;
            $c = $c == 4 ? 0 : $c + 1;
            $article2 = $article->add('article', array(
                'class' => 'item'
            ));
            if (isset($info['responsive'])) {
                $article2->add('span', array(
                    'class' => 'icon-responsive',
                    'title' => 'Responsive'
                ));
            }
            $article2->add('span', array(
                'class' => 'preview',
                'title' => '{LNG_Thumbnail}',
                'style' => 'background-image:url('.WEB_URL.'skin/'.$text.'/screenshot.jpg)'
            ));
            $article2->add('h3', array(
                'innerHTML' => $info['name']
            ));
            $article2->add('p', array(
                'class' => 'detail',
                'innerHTML' => $info['description']
            ));
            $article2->add('p', array(
                'class' => 'folder cuttext',
                'innerHTML' => str_replace('%s', $text, Language::get('All template files are stored in <span>%s</span>'))
            ));
            $p = $article2->add('p');
            $p->add('a', array(
                'innerHTML' => '{LNG_Use this template}',
                'href' => 'index.php?module=template&amp;page='.$page.'&amp;action=use&amp;theme='.$text
            ));
            $p->appendChild('&nbsp;|&nbsp;');
            $p->add('a', array(
                'innerHTML' => '{LNG_Delete}',
                'href' => 'index.php?module=template&amp;page='.$page.'&amp;action=delete&amp;theme='.$text
            ));
        }
        $div->appendChild('<div class="splitpage">'.$request->createUriWithGlobals(WEB_URL.'admin/index.php')->pagination($totalpage, $page).'</div>');
        // คืนค่า HTML
        return $div->render();
    }

    /**
     * ฟังก์ชั่น อ่าน info ของ theme
     *
     * @param string $theme ชื่อไฟล์ css ของ theme รวม full path
     *
     * @return array คืนค่า แอเรย์ข้อมูลส่วน header ของ css
     */
    public function parseTheme($theme)
    {
        $result = array();
        if (is_file($theme) && preg_match('/^[\s]{0,}\/\*(.*?)\*\//is', file_get_contents($theme), $match)) {
            if (preg_match_all('/([a-zA-Z]+)[\s:]{0,}(.*)?[\r\n]+/i', $match[1], $datas)) {
                foreach ($datas[1] as $i => $v) {
                    $result[strtolower($v)] = $datas[2][$i];
                }
            }
        }
        return $result;
    }
}

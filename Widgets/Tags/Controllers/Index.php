<?php
/**
 * @filesource Widgets/Tags/Controllers/Index.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Widgets\Tags\Controllers;

use Gcms\Gcms;

/**
 * Controller หลัก สำหรับแสดงผล Widget
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Index extends \Kotchasan\Controller
{
    /**
     * @var mixed
     */
    private $module;

    /**
     * แสดงผล Widget
     *
     * @param array $query_string ข้อมูลที่ส่งมาจากการเรียก Widget
     *
     * @return string
     */
    public function get($query_string)
    {
        if (defined('MAIN_INIT')) {
            $this->module = empty($query_string['module']) ? 'tag' : $query_string['module'];
            $tag_result = \Index\Tag\Model::all();
            $min = 1000000;
            $max = 0;
            $nmax = count($tag_result) - 1;
            $min = isset($tag_result[1]) ? $tag_result[1]['count'] : 0;
            $max = isset($tag_result[$nmax - 1]) ? $tag_result[$nmax - 1]['count'] : 0;
            $step = ($max - $min > 0) ? ($max - $min) / 7 : 0.1;
            $items = array();
            for ($i = $nmax; $i >= 0; --$i) {
                $value = $tag_result[$i]['count'];
                $key = $tag_result[$i]['tag'];
                $id = $tag_result[$i]['id'];
                if ($i == 0) {
                    $classname = 'class0';
                } elseif ($i == $nmax) {
                    $classname = 'class9';
                } else {
                    $classname = 'class'.(floor(($value - $min) / $step) + 1);
                }
                $url = $this->url($key);
                $items[] = '<a href="'.$url.'" class='.$classname.' id=tags-'.$id.'>'.str_replace(' ', '&nbsp;', $key).'</a>';
            }
            // คืนค่า HTML
            return \Widgets\Tags\Views\Index::render($items);
        }
    }

    /**
     * ฟังก์ชั่นสร้าง URL
     *
     * @param string $tag ชื่อ Tag
     *
     * @return string
     */
    private function url($tag)
    {
        if ($this->module == 'tag' && self::$cfg->module_url == 1) {
            return Gcms::createUrl('tag', $tag);
        } else {
            return Gcms::createUrl($this->module, '', 0, 0, 'tag='.rawurlencode($tag));
        }
    }
}

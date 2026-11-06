<?php
/**
 * @filesource widgets/tags/controllers/index.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Widgets\Tags\Controllers;

use Kotchasan\Language;
use Kotchasan\Text;

/**
 * Controller Main for displaying Widget
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
     * Display Widget
     *
     * @param array $query_string Data sent from the Widget call
     *
     * @return string
     */
    public function get($query_string)
    {
        if (defined('MAIN_INIT')) {
            $this->module = empty($query_string['module']) ? 'tag' : $query_string['module'];
            $tag_result = \Widgets\Tags\Models\Index::all();
            $min = 1000000;
            $max = 0;
            $nmax = count($tag_result) - 1;
            $min = isset($tag_result[1]) ? $tag_result[1]->count : 0;
            $max = isset($tag_result[$nmax - 1]) ? $tag_result[$nmax - 1]->count : 0;
            $step = ($max - $min > 0) ? ($max - $min) / 7 : 0.1;
            $items = [];
            for ($i = $nmax; $i >= 0; --$i) {
                $value = $tag_result[$i]->count;
                $key = $tag_result[$i]->tag;
                $id = $tag_result[$i]->id;
                if ($i == 0) {
                    $classname = 'class0';
                } elseif ($i == $nmax) {
                    $classname = 'class9';
                } else {
                    $classname = 'class'.(floor(($value - $min) / $step) + 1);
                }
                $url = \Document\Tag\Controller::url($key);
                $title = Language::replace('Clicked %s times', number_format($value));
                $items[] = '<a href="'.$url.'" class="'.$classname.'" data-id="'.$id.'" title="'.$title.'">'.Text::htmlspecialchars($key).'</a>';
            }
            // Return HTML
            return \Widgets\Tags\Views\Index::render($items);
        }
    }
}

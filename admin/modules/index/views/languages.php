<?php
/**
 * @filesource modules/index/views/languages.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Index\Languages;

use Kotchasan\Html;
use Kotchasan\Language;

/**
 * module=languages
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class View extends \Gcms\Adminview
{
    /**
     * รายการภาษาที่ติดตั้งแล้ว
     *
     * @return string
     */
    public function render()
    {
        $section = Html::create('div');
        $section->add('div', [
            'class' => 'subtitle',
            'innerHTML' => '{LNG_Add, edit, and reorder the language of the site. The first item is the default language of the site.}'
        ]);
        $list = $section->add('ol', [
            'class' => 'editinplace_list',
            'id' => 'languages'
        ]);
        $languages = [];
        foreach (array_merge(self::$cfg->languages, Language::installedLanguage()) as $item) {
            if (empty($languages[$item])) {
                $languages[$item] = $item;
                $row = $list->add('li', [
                    'id' => 'L_'.$item,
                    'class' => 'sort'
                ]);
                $row->add('span', [
                    'class' => 'icon-move'
                ]);
                $row->add('span', [
                    'id' => 'delete_'.$item,
                    'class' => 'icon-delete',
                    'title' => '{LNG_Delete}'
                ]);
                $row->add('a', [
                    'class' => 'icon-edit',
                    'href' => '?module=languageadd&amp;id='.$item,
                    'title' => '{LNG_Edit}'
                ]);
                $chk = in_array($item, self::$cfg->languages) ? 'check' : 'uncheck';
                $row->add('span', [
                    'id' => 'check_'.$item,
                    'class' => 'icon-'.$chk
                ]);
                $row->add('span', [
                    'style' => 'background-image:url('.WEB_URL.'language/'.$item.'.gif)'
                ]);
                $row->add('span', [
                    'innerHTML' => $item
                ]);
            }
        }
        $div = $section->add('div', [
            'class' => 'submit'
        ]);
        $a = $div->add('a', [
            'class' => 'button add large',
            'href' => '?module=languageadd'
        ]);
        $a->add('span', [
            'class' => 'icon-plus',
            'innerHTML' => '{LNG_Add New} {LNG_Language}'
        ]);
        // Javascript
        $section->script('initLanguages("languages");');
        // คืนค่า HTML
        return $section->render();
    }
}

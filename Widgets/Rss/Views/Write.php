<?php
/**
 * @filesource Widgets/Rss/Views/Settings.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Widgets\Rss\Views;

use Kotchasan\Html;

/**
 * โมดูลสำหรับจัดการการตั้งค่าเริ่มต้น
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Write extends \Gcms\Adminview
{
    /**
     * module=Rss-Write
     *
     * @param object $datas
     *
     * @return string
     */
    public function render($datas)
    {
        $form = Html::create('form', array(
            'id' => 'setup_frm',
            'class' => 'setup_frm',
            'autocomplete' => 'off',
            'action' => 'index.php/Widgets/Rss/Models/Settings/submit',
            'onsubmit' => 'doFormSubmit',
            'ajax' => true,
            'token' => true
        ));
        $fieldset = $form->add('fieldset', array(
            'title' => '{LNG_You can add the RSS URL and name of Tab to display Tab}'
        ));
        // rss_url
        $fieldset->add('text', array(
            'id' => 'rss_url',
            'labelClass' => 'g-input icon-world',
            'itemClass' => 'item',
            'label' => '{LNG_URL}',
            'comment' => '{LNG_RSS URL for the desired results}',
            'value' => $datas['url']
        ));
        // rss_topic
        $fieldset->add('text', array(
            'id' => 'rss_topic',
            'labelClass' => 'g-input icon-edit',
            'itemClass' => 'item',
            'label' => '{LNG_Topic}',
            'comment' => '{LNG_Name or title to be displayed in RSS Tab}',
            'value' => $datas['topic']
        ));
        // rss_index
        $groups = $fieldset->add('groups-table', array(
            'label' => '{LNG_ID}',
            'id' => 'rss_index',
            'comment' => '{LNG_Fill in the numbers 1-9 to determine the ID of the RSS Tab To display multiple RSS Tab}'
        ));
        $groups->add('text', array(
            'id' => 'rss_index',
            'labelClass' => 'g-input icon-edit',
            'itemClass' => 'width',
            'title' => '{LNG_Fill in the numbers 1-9 to determine the ID of the RSS Tab To display multiple RSS Tab}',
            'pattern' => '[0-9]+',
            'value' => $datas['index']
        ));
        $groups->add('em', array(
            'id' => 'rss_index_result',
            'class' => 'width',
            'innerHTML' => '{WIDGET_RSS}'
        ));
        $groups = $fieldset->add('groups-table', array(
            'comment' => '{LNG_Settings the number and display as row * column}'
        ));
        // cols
        $groups->add('number', array(
            'id' => 'rss_cols',
            'labelClass' => 'g-input icon-width',
            'itemClass' => 'width',
            'label' => '{LNG_Cols}',
            'value' => $datas['cols']
        ));
        // rows
        $groups->add('number', array(
            'id' => 'rss_rows',
            'labelClass' => 'g-input icon-height',
            'itemClass' => 'width',
            'label' => '{LNG_Rows}',
            'value' => $datas['rows']
        ));
        $fieldset = $form->add('fieldset', array(
            'class' => 'submit'
        ));
        // submit
        $fieldset->add('submit', array(
            'class' => 'button save large icon-save',
            'value' => '{LNG_Save}'
        ));
        // id
        $fieldset->add('hidden', array(
            'id' => 'rss_id',
            'value' => $datas['id']
        ));
        // Javascript
        $form->script('initRssWrite()');
        // คืนค่า HTML
        return $form->render();
    }
}

<?php
/**
 * @filesource modules/index/views/addmodule.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Index\Addmodule;

use Kotchasan\Html;

/**
 * เพิ่มโมดูลแบบที่สามารถใช้ซ้ำได้
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class View extends \Gcms\Adminview
{
    /**
     * module=addmodule
     *
     * @param array $modules
     *
     * @return string
     */
    public function render($modules)
    {
        // form
        $form = Html::create('form', array(
            'id' => 'setup_frm',
            'class' => 'setup_frm',
            'action' => 'index.php',
            'method' => 'get'
        ));
        $fieldset = $form->add('fieldset', array(
            'title' => '{LNG_Create a new module from the module installed, according to usage}'
        ));
        $fieldset->add('select', array(
            'id' => 'owner',
            'labelClass' => 'g-input icon-modules',
            'itemClass' => 'item',
            'label' => '{LNG_Installed module}',
            'options' => $modules
        ));
        $fieldset = $form->add('fieldset', array(
            'class' => 'submit'
        ));
        // submit
        $fieldset->add('submit', array(
            'class' => 'button ok large icon-next',
            'value' => '{LNG_Create}'
        ));
        // module
        $fieldset->add('hidden', array(
            'id' => 'module',
            'value' => 'pagewrite'
        ));
        return $form->render();
    }
}

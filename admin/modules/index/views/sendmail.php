<?php
/**
 * @filesource modules/index/views/sendmail.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Index\Sendmail;

use Gcms\Login;
use Kotchasan\Html;
use Kotchasan\Http\Request;
use Kotchasan\Language;
use Kotchasan\Template;

/**
 * ฟอร์มส่งอีเมลจากแอดมิน
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class View extends \Gcms\Adminview
{
    /**
     * ฟอร์มส่งอีเมล sendmail.php
     *
     * @param Request $request
     * @param array   $login
     *
     * @return string
     */
    public function render(Request $request, $login)
    {
        // send email form
        $form = Html::create('form', array(
            'id' => 'write_frm',
            'class' => 'setup_frm',
            'action' => 'index.php/index/model/sendmail/submit',
            'onsubmit' => 'doFormSubmit',
            'token' => true,
            'ajax' => true
        ));
        $fieldset = $form->add('fieldset', array(
            'title' => '{LNG_Details of} {LNG_Email}'
        ));
        // reciever
        $reciever = $request->request('to')->topic();
        $fieldset->add('text', array(
            'id' => 'reciever',
            'itemClass' => 'item',
            'labelClass' => 'g-input icon-email-sent',
            'label' => '{LNG_Reciever}',
            'comment' => '{LNG_Email addresses can be sent to multiple recipients} ({LNG_Separate them with a comma})',
            'autofocus',
            'value' => $reciever
        ));
        // email_from
        $datas = array($login['email'] => $login['email']);
        if (Login::isAdmin() && empty($login['social'])) {
            $datas[self::$cfg->noreply_email] = self::$cfg->noreply_email;
            foreach (\Index\Sendmail\Model::findAdmin() as $item) {
                $datas[$item] = $item;
            }
        }
        $fieldset->add('select', array(
            'id' => 'from',
            'itemClass' => 'item',
            'labelClass' => 'g-input icon-email',
            'label' => '{LNG_Sender}',
            'options' => $datas
        ));
        // subject
        $fieldset->add('text', array(
            'id' => 'subject',
            'itemClass' => 'item',
            'labelClass' => 'g-input icon-edit',
            'label' => '{LNG_Subject}',
            'comment' => ''.'{LNG_Please fill in} {LNG_Subject}'
        ));
        // detail
        $fieldset->add('ckeditor', array(
            'id' => 'detail',
            'itemClass' => 'item',
            'height' => 300,
            'language' => Language::name(),
            'toolbar' => 'Email',
            'label' => '{LNG_Detail}',
            'value' => Template::load('', '', 'mailtemplate')
        ));
        $fieldset = $form->add('fieldset', array(
            'class' => 'submit'
        ));
        // submit
        $fieldset->add('submit', array(
            'class' => 'button save large icon-save',
            'value' => '{LNG_Send message}'
        ));
        return $form->render();
    }
}

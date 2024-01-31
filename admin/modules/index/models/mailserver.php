<?php
/**
 * @filesource modules/index/models/mailserver.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Index\Mailserver;

use Gcms\Config;
use Gcms\Login;
use Kotchasan\Http\Request;
use Kotchasan\Language;
use Kotchasan\Validator;

/**
 * module=mailserver
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Model extends \Kotchasan\KBase
{
    /**
     * บันทึกการตั้งค่าระบบอีเมล (mailserver.php)
     *
     * @param Request $request
     */
    public function submit(Request $request)
    {
        $ret = array();
        // session, token, แอดมิน, can_config, ไม่ใช่สมาชิกตัวอย่าง
        if ($request->initSession() && $request->isSafe() && $login = Login::adminAccess()) {
            if (Login::checkPermission($login, 'can_config') && Login::notDemoMode($login)) {
                try {
                    // โหลด config
                    $config = Config::load(CONFIG);
                    // รับค่าจากการ POST
                    $save = array(
                        'noreply_email' => $request->post('noreply_email')->url(),
                        'email_charset' => $request->post('email_charset')->text(),
                        'email_use_phpMailer' => $request->post('email_use_phpMailer')->toInt(),
                        'email_Host' => $request->post('email_Host')->text(),
                        'email_Port' => $request->post('email_Port')->toInt(),
                        'email_SMTPAuth' => $request->post('email_SMTPAuth')->toBoolean(),
                        'email_SMTPSecure' => $request->post('email_SMTPSecure')->text(),
                        'email_Username' => $request->post('email_Username')->quote(),
                        'email_Password' => $request->post('email_Password')->quote()
                    );
                    // อีเมล
                    if (empty($save['noreply_email'])) {
                        $ret['ret_noreply_email'] = 'Please fill in';
                    } elseif (!Validator::email($save['noreply_email'])) {
                        $ret['ret_noreply_email'] = Language::replace('Invalid :name', array(':name' => Language::get('Email')));
                    } else {
                        $config->noreply_email = $save['noreply_email'];
                    }
                    $config->email_charset = empty($save['email_charset']) ? 'utf-8' : strtolower($save['email_charset']);
                    if (empty($save['email_Host'])) {
                        $config->email_Host = 'localhost';
                        $config->email_Port = 25;
                        $config->email_SMTPSecure = '';
                        $config->email_Username = '';
                        $config->email_Password = '';
                    } else {
                        $config->email_Host = $save['email_Host'];
                        $config->email_Port = empty($save['email_Port']) ? 25 : $save['email_Port'];
                        $config->email_SMTPSecure = isset($save['email_SMTPSecure']) ? $save['email_SMTPSecure'] : '';
                        $config->email_Username = isset($save['email_Username']) ? $save['email_Username'] : '';
                        if (!empty($save['email_Password'])) {
                            $config->email_Password = $save['email_Password'];
                        }
                    }
                    $config->email_use_phpMailer = $save['email_use_phpMailer'];
                    $config->email_SMTPAuth = $save['email_SMTPAuth'];
                    if (empty($ret)) {
                        // save config
                        if (Config::save($config, CONFIG)) {
                            $ret['alert'] = Language::get('Saved successfully');
                            $ret['location'] = 'reload';
                            // เคลียร์
                            $request->removeToken();
                        } else {
                            // ไม่สามารถบันทึก config ได้
                            $ret['alert'] = Language::replace('File %s cannot be created or is read-only.', 'settings/config.php');
                        }
                    }
                } catch (\Kotchasan\InputItemException $e) {
                    $ret['alert'] = $e->getMessage();
                }
            }
        }
        if (empty($ret)) {
            $ret['alert'] = Language::get('Unable to complete the transaction');
        }
        // คืนค่าเป็น JSON
        echo json_encode($ret);
    }
}

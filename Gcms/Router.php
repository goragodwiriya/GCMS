<?php
/**
 * @filesource Gcms/Router.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Gcms;

/**
 * Router Class สำหรับ GCMS
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Router extends \Kotchasan\Router
{
    /**
     * กฏของ Router สำหรับการแยกหน้าเว็บไซต์
     *
     * @var array
     */
    protected $rules = array(
        // api.php/<modules>/<method>/<action>
        '/(api)\.php\/([a-z0-9]+)\/([a-z]+)\/([a-z]+)/i' => array('', 'module', 'method', 'action'),
        // css, js
        '/(css|js)\/(view)\/(index)/i' => array('module', '_mvc', '_dir'),
        // index.php/module/model/folder/_dir/_method
        '/^[a-z0-9]+\.php\/([a-z]+)\/(model)(\/([\/a-z0-9_]+)\/([a-z]+))?$/i' => array('module', '_mvc', '', '_dir', '_method'),
        // index.php/Widgets/Textlink/Models/Write/save
        '/^[a-z0-9]+\.php\/(Widgets\/[a-z]+\/Models\/[a-z]+)\/([a-z]+)$/i' => array('_class', '_method'),
        // install
        '/index\.php\/(index)\/(controller)\/(installing)/i' => array('module', '_mvc', '_dir'),
        // module/cat/id
        '/^([a-z]+)\/([0-9]+)\/([0-9]+)$/' => array('module', 'cat', 'id'),
        // module/cat module/alias, module/cat/alias
        '/^([a-z]+)(\/([0-9]+))?(\/(.*))?$/' => array('module', '', 'cat', '', 'alias'),
        // module, module.php
        '/^([a-z0-9_]+)(\.php)?$/' => array('module'),
        // alias
        '/^(.*)$/' => array('alias')
    );
}

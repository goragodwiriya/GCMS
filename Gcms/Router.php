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
    protected $rules = [
        // api.php/<modules>/<method>/<action>
        '/(api)\.php\/([a-z0-9]+)\/([a-z]+)\/([a-z]+)/i' => ['', 'module', 'method', 'action'],
        // css, js
        '/(css|js)\/(view)\/(index)/i' => ['module', '_mvc', '_dir'],
        // index.php/module/model/folder/_dir/_method
        '/^[a-z0-9]+\.php\/([a-z]+)\/(model)(\/([\/a-z0-9_]+)\/([a-z]+))?$/i' => ['module', '_mvc', '', '_dir', '_method'],
        // index.php/Widgets/Textlink/Models/Write/save
        '/^[a-z0-9]+\.php\/(Widgets\/[a-z]+\/Models\/[a-z]+)\/([a-z]+)$/i' => ['_class', '_method'],
        // install
        '/index\.php\/(index)\/(controller)\/(installing)/i' => ['module', '_mvc', '_dir'],
        // module/cat/id
        '/^([a-z]+)\/([0-9]+)\/([0-9]+)$/' => ['module', 'cat', 'id'],
        // module/cat module/alias, module/cat/alias
        '/^([a-z]+)(\/([0-9]+))?(\/(.*))?$/' => ['module', '', 'cat', '', 'alias'],
        // module, module.php
        '/^([a-z0-9_]+)(\.php)?$/' => ['module'],
        // alias
        '/^(.*)$/' => ['alias']
    ];
}

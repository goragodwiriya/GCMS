<?php
/**
 * @filesource modules/index/controllers/widgets.php
 *
 * @copyright 2026 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Index\Widgets;

use Kotchasan\Http\Request;
use Kotchasan\Language;
use Kotchasan\Template;
use Web\Gcms;
use Web\Login;

/**
 * Generic Widget Dispatcher
 *
 * All widget API routes pass through this single controller.
 * Adding a new widget = create Widgets/{name}/controllers/*.php
 * Removing a widget   = delete the widget folder.
 *
 * Route: api/index/widgets/manifest    → manifest()    (GET  widget catalog)
 * Route: api/index/widgets/render      → render()      (GET  frontend HTML)
 * Route: api/index/widgets/table       → table()       (GET  table data)
 * Route: api/index/widgets/tableaction → tableaction() (POST table bulk-actions)
 * Route: api/index/widgets/get         → get()         (GET  settings)
 * Route: api/index/widgets/save        → save()        (POST save settings)
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Controller extends \Kotchasan\ApiController
{
    /**
     * GET ../api/index/widgets/manifest
     * Returns a lightweight catalog for the designer. The catalog describes
     * installable widget frames only; each widget still owns its settings UI.
     *
     * @param Request $request
     *
     * @return \Kotchasan\Http\Response
     */
    public function manifest(Request $request)
    {
        self::validateMethod($request, 'GET');

        $items = [];
        $base = ROOT_PATH.'widgets/';
        foreach (glob($base.'*/controllers/index.php') ?: [] as $file) {
            $widget = basename(dirname(dirname($file)));
            if (!preg_match('/^[a-z0-9]+$/', $widget)) {
                continue;
            }

            $title = ucwords(str_replace(['-', '_'], ' ', $widget));
            // hasSettings requires both the settings backend AND the settings page
            // template that /widgets/:module resolves to (widgets/{id}/{id}.html) —
            // a settings.php alone may only back the Designer's inline params form
            // (e.g. personnel), which has no standalone page to link to.
            $items[] = [
                'id' => $widget,
                'title' => $title,
                'icon' => 'icon-'.$widget,
                'category' => 'widget',
                'hasSettings' => is_file($base.$widget.'/controllers/settings.php') && is_file($base.$widget.'/'.$widget.'.html'),
                'settingsUrl' => WEB_URL.'admin/widgets/'.$widget,
                'renderUrl' => WEB_URL.'api/index/widgets/render?widget='.$widget
            ];
        }

        usort($items, static fn($a, $b) => strcmp($a['title'], $b['title']));

        return $this->successResponse([
            'items' => $items
        ], 'Widgets retrieved successfully');
    }

    /**
     * GET ../api/index/widgets/render?widget={name}&params=key=value;...
     * Delegates frontend rendering to Widgets\{Name}\Controllers\Index::get().
     *
     * @param Request $request
     *
     * @return \Kotchasan\Http\Response
     */
    public function render(Request $request)
    {
        self::validateMethod($request, 'GET');

        $widget = $request->get('widget')->filter('a-z0-9');
        if (empty($widget)) {
            return $this->errorResponse('Widget parameter is required', 400);
        }

        $className = 'Widgets\\'.ucfirst($widget).'\\Controllers\\Index';
        if (!class_exists($className) || !method_exists($className, 'get')) {
            return $this->errorResponse('Widget not found: '.$widget, 404);
        }

        $params = $this->parseWidgetParams($request->get('params')->toString());
        $params['owner'] = $widget;

        // Bootstrap the minimal web context that widget get() methods require
        if (!defined('MAIN_INIT')) {
            define('MAIN_INIT', 'indexhtml');
        }
        Language::name();
        Template::init(self::$cfg->skin ?? '');
        if (Gcms::$module === null) {
            Login::create($request);
            $login = Login::isMember();
            Gcms::$menu = \Index\Menu\Controller::init($login);
            Gcms::$module = \Index\Module\Controller::init(Gcms::$menu);
        }

        $html = createClass($className)->get($params);

        return $this->successResponse([
            'html' => is_string($html) ? $html : ''
        ], 'Widget rendered successfully');
    }

    /**
     * GET  ../api/index/widgets/table?widget={name}
     * Delegates to Widgets\{Name}\Controllers\Table::index()
     *
     * @param Request $request
     *
     * @return \Kotchasan\Http\Response
     */
    public function table(Request $request)
    {
        return $this->delegateToWidget($request, 'Table', 'index');
    }

    /**
     * POST ../api/index/widgets/tableaction?widget={name}
     * Delegates to Widgets\{Name}\Controllers\Table::action()
     *
     * @param Request $request
     *
     * @return \Kotchasan\Http\Response
     */
    public function tableaction(Request $request)
    {
        return $this->delegateToWidget($request, 'Table', 'action');
    }

    /**
     * GET  ../api/index/widgets/get?widget={name}
     * Delegates to Widgets\{Name}\Controllers\Settings::get()
     *
     * @param Request $request
     *
     * @return \Kotchasan\Http\Response
     */
    public function get(Request $request)
    {
        return $this->delegateToWidget($request, 'Settings', 'get');
    }

    /**
     * POST ../api/index/widgets/save?widget={name}
     * Delegates to Widgets\{Name}\Controllers\Settings::save()
     *
     * @param Request $request
     *
     * @return \Kotchasan\Http\Response
     */
    public function save(Request $request)
    {
        return $this->delegateToWidget($request, 'Settings', 'save');
    }

    /**
     * POST ../api/index/widgets/click?widget={name}
     * Delegates to Widgets\{Name}\Controllers\Settings::click()
     *
     * @param Request $request
     *
     * @return \Kotchasan\Http\Response
     */
    public function click(Request $request)
    {
        return $this->delegateToWidget($request, 'Settings', 'click');
    }

    /**
     * POST ../api/index/widgets/remove?widget={name}&type={type}
     * Delegates to Widgets\{Name}\Controllers\Settings::remove()
     *
     * @param Request $request
     *
     * @return \Kotchasan\Http\Response
     */
    public function remove(Request $request)
    {
        $type = $request->get('type')->filter('a-z');

        return $this->delegateToWidget($request, 'Settings', 'remove'.ucfirst($type));
    }

    /**
     * Resolve the widget controller class and call the requested method.
     *
     * @param Request $request
     * @param string  $controller  Controller class suffix: 'Table' | 'Settings'
     * @param string  $method      Method to call on the resolved controller
     *
     * @return \Kotchasan\Http\Response
     */
    private function delegateToWidget(Request $request, string $controller, string $method)
    {
        $widget = $request->get('widget')->filter('a-z0-9');

        if (empty($widget)) {
            return $this->errorResponse('Widget parameter is required', 400);
        }

        // Convention: Widgets\Facebook\Controllers\Settings
        //             Widgets\Document\Controllers\Table
        $className = 'Widgets\\'.ucfirst($widget).'\\Controllers\\'.$controller;
        if (!class_exists($className) || !method_exists($className, $method)) {
            return $this->errorResponse('Widget not found: '.$widget, 404);
        }

        $obj = new $className();

        return $obj->$method($request);
    }

    /**
     * Parse widget placeholder-style params: "module=news;layout=list;limit=5".
     *
     * @param string $raw
     *
     * @return array
     */
    private function parseWidgetParams(string $raw): array
    {
        $params = [];
        foreach (explode(';', $raw) as $item) {
            if (strpos($item, '=') === false) {
                continue;
            }
            list($key, $value) = explode('=', $item, 2);
            $key = preg_replace('/[^a-zA-Z0-9_]/', '', trim($key));
            if ($key === '' || $key === 'owner') {
                continue;
            }
            $params[$key] = trim(strip_tags($value));
        }

        return $params;
    }
}

<?php
/**
 * @filesource modules/product/controllers/index.php
 *
 * @copyright 2026 Goragod.com
 * @license https://www.kotchasan.com/license/
 */

namespace Product\Index;

use Kotchasan\Http\Request;
use Web\Gcms;

/**
 * Frontend Controller for the Product (storefront) module
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Controller extends \Web\Controller
{
    /**
     * Storefront pages served under the module (reserved product aliases)
     *
     * @var array
     */
    public static $pages = ['checkout', 'myorders', 'orderdetail'];

    /**
     * Module entry point.
     *
     * @param Request $request
     * @param object  $index   Module data
     *
     * @return object
     */
    public function init(Request $request, $index)
    {
        $order_no = $request->get('order_no')->topic();
        if ($order_no !== '') {
            // Payment hand-off after checkout — shows the shared bank/QR/
            // notify-payment partial instead of a listing or product page.
            return \Product\Index\View::create()->renderPayment($index, [
                'order_no' => $order_no
            ], $request->get('amount')->toDouble());
        }

        $index->alias = $request->get('alias')->topic();
        $index->id = $request->get('id')->toInt();
        $index->category_id = $request->get('cat')->filter('0-9,');
        if (in_array($index->alias, self::$pages, true)) {
            // {module}/checkout, {module}/myorders, {module}/orderdetail?id= —
            // the URLs the themes' product templates link to
            return MAIN_INIT === 'indexhtml'
                ? \Product\Shop\View::create()->render($index, $index->alias)
                : \Index\Error\Controller::create()->init('product');
        }
        if ($index->alias !== '' && preg_match('/^[0-9,]+$/', $index->alias)) {
            // {module}/{category}.html — the URL the legacy (GCMS 11–14)
            // product module and Gcms::createUrl() build for a category. A lone
            // number that is no category of this module is a product id (a
            // product without an alias), same rule as the document module.
            $index->category_id = $index->alias;
            $index->alias = '';
            if ($index->id === 0
                && preg_match('/^[0-9]+$/', $index->category_id)
                && \Web\Category::create($index->module_id)->get(\Product\Category\Model::$type, (int) $index->category_id) === null
            ) {
                $index->id = (int) $index->category_id;
                $index->category_id = '';
            }
        }

        if ($index->alias !== '' || $index->id > 0) {
            // Single product page
            $data = \Product\View\Model::get($index->module_id, $index->id, $index->alias);
            if ($data !== false) {
                $index->product = $data;
                return \Product\View\View::create()->render($index);
            }
        } elseif (MAIN_INIT === 'indexhtml') {
            // Product listing (optionally filtered by category / search)
            $index->category_id = array_values(array_filter(array_map('intval', explode(',', $index->category_id))));
            $index->search = $request->get('search')->topic();
            $index->page = max(1, $request->get('page')->toInt());
            $index = \Product\Index\Model::getProducts($index);
            return \Product\Index\View::create()->render($index);
        }

        return \Index\Error\Controller::create()->init('product');
    }

    /**
     * URL generation for the product module.
     *
     * @param string $module
     * @param string $alias
     * @param int    $id
     * @param bool   $encode
     * @param string $query
     *
     * @return string
     */
    public static function url($module, $alias = '', $id = 0, $encode = true, $query = '')
    {
        if ($alias !== '') {
            return Gcms::createUrl($module, $alias, 0, 0, $query, $encode);
        }
        return Gcms::createUrl($module, '', 0, $id, $query, $encode);
    }

    /**
     * URL of a storefront page ({module}/checkout, ...), see self::$pages.
     *
     * @param string $module
     * @param string $page
     * @param string $query
     *
     * @return string
     */
    public static function pageUrl($module, $page, $query = '')
    {
        if (self::$cfg->module_url == 1) {
            return Gcms::createUrl($module, $page, 0, 0, $query, false);
        }
        // index.php?module={module}-{page} would look for a page controller
        return WEB_URL.'index.php?module='.rawurlencode($module).'&alias='.$page.($query === '' ? '' : '&'.$query);
    }

    /**
     * URL of a category listing ({module}/{category_id}).
     *
     * @param string    $module
     * @param int|array $category_id
     * @param string    $query
     *
     * @return string
     */
    public static function categoryUrl($module, $category_id, $query = '')
    {
        $category_id = is_array($category_id) ? implode(',', $category_id) : (string) $category_id;
        if ($category_id === '' || $category_id === '0') {
            return Gcms::createUrl($module, '', 0, 0, $query);
        }
        if (self::$cfg->module_url == 1) {
            return Gcms::createUrl($module, $category_id, 0, 0, $query, false);
        }
        return Gcms::createUrl($module, '', 0, 0, 'cat='.$category_id.($query === '' ? '' : '&'.$query));
    }
}

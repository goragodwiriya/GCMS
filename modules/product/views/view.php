<?php
/**
 * @filesource modules/product/views/view.php
 *
 * @copyright 2026 Goragod.com
 * @license https://www.kotchasan.com/license/
 */

namespace Product\View;

use Kotchasan\Template;
use Kotchasan\Text;
use Web\Gcms;

/**
 * Product storefront detail view.
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class View extends \Web\View
{
    /**
     * Render a single product page.
     *
     * @param object $index
     *
     * @return object
     */
    public function render($index)
    {
        $product = $index->product;
        $topic = Text::htmlspecialchars($product->topic);

        // Primary image (fallback to first image, then a placeholder)
        $thumb = WEB_URL.'images/no-image.webp';
        if (!empty($product->images)) {
            $thumb = $product->images[0]['url'];
            foreach ($product->images as $img) {
                if (!empty($img['is_primary'])) {
                    $thumb = $img['url'];
                    break;
                }
            }
        }

        // Price label: variant range for variable products, otherwise base price
        $isVariable = $product->product_type === 'variable' && !empty($product->variants);
        $price = $isVariable
            ? $this->variantPriceRange($product->variants)
            : number_format((float) $product->base_price, 2);
        $inStock = \Product\Index\Model::inStock($product);

        // Breadcrumbs: module > category > product
        if (!Gcms::$menu->isHomeMenu($index->index_id)) {
            Gcms::$view->addBreadcrumb(Gcms::createUrl($index->module), $index->topic, $index->description);
        }
        $category = \Web\Category::create($index->module_id)->get(\Product\Category\Model::$type, (int) $product->category_id);
        if ($category) {
            Gcms::$view->addBreadcrumb(\Product\Index\Controller::categoryUrl($index->module, (int) $product->category_id), $category->topic, $category->detail);
        }
        $index->canonical = \Product\Index\Controller::url($index->module, $product->alias, $product->id);
        Gcms::$view->addBreadcrumb($index->canonical, $product->topic);

        $thumbs = $this->thumbnails($index, $product->images, $thumb);
        $details = $this->details($index, $product->attributes);
        $template = Template::create($index->owner, $index->module, 'view');
        $template->add(\Product\Shop\View::values($index) + [
            '/{ID}/' => (int) $product->id,
            '/{THUMB}/' => $thumb,
            '/{TOPIC}/' => $topic,
            '/{NEW_HIDDEN}/' => \Product\Index\View::hidden(\Product\Index\Model::isNew($product->created_at)),
            '/{IN_STOCK_HIDDEN}/' => \Product\Index\View::hidden($inStock),
            '/{OUT_OF_STOCK_HIDDEN}/' => \Product\Index\View::hidden(!$inStock),
            '/{THUMBS}/' => $thumbs,
            '/{THUMBS_HIDDEN}/' => \Product\Index\View::hidden($thumbs !== ''),
            '/{PRODUCT_NO}/' => Text::htmlspecialchars($product->sku),
            '/{PRICE}/' => $price,
            // products that do not track stock have no quantity limit
            '/{STOCK}/' => empty($product->manage_stock) ? '∞' : (int) $product->stock_qty,
            '/{MAX}/' => empty($product->manage_stock) || !$inStock ? '' : (int) $product->stock_qty,
            '/{QTY_DISABLED}/' => $inStock ? '' : 'disabled',
            '/{VARIANTS}/' => $isVariable ? $this->variants($index, $product) : '',
            '/{VARIANTS_HIDDEN}/' => \Product\Index\View::hidden($isVariable),
            // a variable product starts disabled until script.js copies the chosen variant
            '/{CART_DISABLED}/' => $inStock && !$isVariable ? '' : 'disabled',
            '/{DETAILS}/' => $details,
            '/{DETAILS_HIDDEN}/' => \Product\Index\View::hidden($details !== ''),
            '/{DESCRIPTION}/' => str_replace(['\\', '$'], ['&#92;', '&#36;'], Gcms::highlighter($product->detail)),
            '/{CATEGORIES}/' => (int) $product->category_id
        ]);

        $index->topic = $product->topic;
        $index->description = $product->description;
        $index->keywords = $product->keywords;
        $index->detail = $template->render();
        \Product\Shop\View::context($index);

        return $index;
    }

    /**
     * One thumb.html per picture, under the main one (nothing for one
     * picture). modules/product/script.js swaps the main picture on click.
     *
     * @param object $index
     * @param array  $images  from Product\Write\Model::getImages()
     * @param string $current url shown as the main picture
     *
     * @return string
     */
    protected function thumbnails($index, array $images, $current)
    {
        if (count($images) < 2) {
            return '';
        }
        $template = Template::create($index->owner, $index->module, 'thumb');
        foreach ($images as $img) {
            $template->add([
                '/{ACTIVE}/' => $img['url'] === $current ? ' active' : '',
                '/{SRC}/' => Text::htmlspecialchars($img['url'])
            ]);
        }

        return $template->render();
    }

    /**
     * One detailrow.html per attribute of the "Product details" table
     * (getAttributeMatrix() returns plain arrays).
     *
     * @param object $index
     * @param array  $attributes
     *
     * @return string
     */
    protected function details($index, array $attributes)
    {
        $template = Template::create($index->owner, $index->module, 'detailrow');
        foreach ($attributes as $attr) {
            $values = [];
            foreach ($attr['values'] as $val) {
                $values[] = Text::htmlspecialchars((string) $val['text']);
            }
            $template->add([
                '/{NAME}/' => Text::htmlspecialchars((string) $attr['name']),
                '/{VALUES}/' => implode(', ', $values)
            ]);
        }

        return $template->hasItem() ? $template->render() : '';
    }

    /**
     * One variant.html per variant of a variable product. Each option carries
     * its selling price, stock and picture so script.js can update the price
     * label, the quantity limit, the picture and the add-to-cart button.
     *
     * @param object $index
     * @param object $product
     *
     * @return string
     */
    protected function variants($index, $product)
    {
        $imageUrl = [];
        foreach ($product->images as $img) {
            $imageUrl[$img['id']] = $img['url'];
        }
        $template = Template::create($index->owner, $index->module, 'variant');
        foreach ($product->variants as $v) {
            $price = $v->sale_price !== null ? (float) $v->sale_price : (float) $v->price;
            $available = empty($product->manage_stock) || (int) $v->stock_qty > 0;
            $template->add([
                '/{ID}/' => (int) $v->id,
                '/{PRICE}/' => number_format($price, 2),
                '/{STOCK}/' => empty($product->manage_stock) ? '' : (int) $v->stock_qty,
                '/{IMAGE}/' => isset($imageUrl[(int) $v->image_id]) ? Text::htmlspecialchars($imageUrl[(int) $v->image_id]) : '',
                '/{DISABLED}/' => $available ? '' : 'disabled',
                '/{LABEL}/' => Text::htmlspecialchars($v->label !== '' ? $v->label : $v->sku)
            ]);
        }

        return $template->hasItem() ? $template->render() : '';
    }

    /**
     * Build a min-max price label across variants.
     *
     * @param array $variants
     *
     * @return string
     */
    protected function variantPriceRange($variants)
    {
        $prices = array_map(fn($v) => $v->sale_price !== null ? (float) $v->sale_price : (float) $v->price, $variants);
        $min = min($prices);
        $max = max($prices);
        if ($min == $max) {
            return number_format($min, 2);
        }
        return number_format($min, 2).' - '.number_format($max, 2);
    }
}

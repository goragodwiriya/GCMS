/**
 * Product storefront frontend script.
 *
 * Auto-loaded on every public page by modules/index/controllers/index.php.
 * All markup lives in the themes' product/*.html; this file only adds the
 * behaviour the templates cannot declare:
 *
 *  - add to cart (.add-to-cart buttons: data-id, data-variant) through the
 *    product cart API, and the item count on the floating cart button
 *    (#productCartFab in product/shop.html; #productShop names the module);
 *  - the quantity stepper ([data-quantity-step]) of the product page;
 *  - the thumbnails and the variant selector of the product page;
 *  - the filter sidebar of the listing (data-on-load="initProductFilters").
 *
 * The checkout page is declarative (api/product/checkout/get + requestApi).
 */
(function () {
  'use strict';

  var WEB = (typeof WEB_URL === 'string' ? WEB_URL : (window.WEB_URL || '/'));
  var API_BASE = WEB + 'api.php/product';

  /**
   * Context of the product module this page belongs to — the first product
   * module outside the store's pages, null when none is installed.
   */
  function shop() {
    var el = document.getElementById('productShop');
    return el ? el.dataset : null;
  }

  function notify(type, message) {
    if (window.NotificationManager && typeof NotificationManager[type] === 'function') {
      NotificationManager[type](message);
    }
  }

  /**
   * Call the product API. Resolves with the response `data`, rejects with
   * the server's message (already translated).
   */
  function api(path, params, method) {
    method = method || 'GET';
    var ctx = shop();
    params = params || {};
    if (ctx && !params.module_id) {
      params.module_id = ctx.moduleId;
    }
    var body = new URLSearchParams(params);
    var url = API_BASE + path + (method === 'GET' ? '?' + body.toString() : '');
    return fetch(url, {
      method: method,
      credentials: 'same-origin',
      headers: {'X-Requested-With': 'XMLHttpRequest'},
      body: method === 'GET' ? undefined : body
    })
      .then(function (r) { return r.json(); })
      .then(function (res) {
        if (!res || !res.success) {
          throw new Error((res && res.message) || 'Unable to complete the request');
        }
        return res.data || {};
      });
  }

  /* -------------------------------------------------------------------- */
  /* Floating cart button (product/shop.html)                             */
  /* -------------------------------------------------------------------- */

  function setCount(count) {
    var btn = document.getElementById('productCartFab');
    if (btn) {
      count = parseInt(count, 10) || 0;
      btn.querySelector('.count').textContent = count;
      // no button on the checkout page itself
      btn.hidden = count === 0 || !!document.getElementById('checkoutForm');
    }
  }

  function refreshCount() {
    if (shop()) {
      api('/cart/index').then(function (data) {
        setCount(data.count);
      }).catch(function () {});
    }
  }

  /* -------------------------------------------------------------------- */
  /* Add to cart                                                          */
  /* -------------------------------------------------------------------- */

  /**
   * Quantity for a button: the product page's #productQuantity applies only
   * to the product's own button, not to the related products.
   */
  function quantityFor(btn) {
    var box = btn.closest('.product-actions');
    var input = box ? box.querySelector('#productQuantity') : null;
    var qty = parseInt(input ? input.value : 1, 10);
    return qty > 0 ? qty : 1;
  }

  // Capture phase: the listing's buttons sit inside the product link — the
  // click must not follow it
  document.addEventListener('click', function (e) {
    var btn = e.target.closest ? e.target.closest('.add-to-cart') : null;
    if (!btn || btn.disabled || !btn.dataset.id) {
      return;
    }
    e.preventDefault();
    e.stopPropagation();
    api('/cart/add', {product_id: btn.dataset.id, variant_id: btn.dataset.variant || 0, qty: quantityFor(btn)}, 'POST')
      .then(function (data) {
        setCount(data.count);
        notify('success', data.message || 'Added to cart');
      })
      .catch(function (err) {
        notify('error', err.message);
      });
  }, true);

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', refreshCount);
  } else {
    refreshCount();
  }

  /* -------------------------------------------------------------------- */
  /* Product page: quantity stepper, thumbnails, variant selector          */
  /* -------------------------------------------------------------------- */

  document.addEventListener('click', function (e) {
    var step = e.target.closest ? e.target.closest('[data-quantity-step]') : null;
    if (step) {
      var input = document.getElementById('productQuantity');
      if (!input) {
        return;
      }
      var val = (parseInt(input.value, 10) || 1) + (parseInt(step.dataset.quantityStep, 10) || 0);
      var min = parseInt(input.min, 10) || 1;
      var max = parseInt(input.max, 10);
      input.value = Math.max(min, !isNaN(max) && max > 0 ? Math.min(val, max) : val);
      return;
    }
    var thumb = e.target.closest ? e.target.closest('.product-thumb') : null;
    if (thumb) {
      e.preventDefault();
      showPicture(thumb.dataset.src);
    }
  });

  function showPicture(src) {
    var main = document.querySelector('.product-main-img');
    if (!main || !src) {
      return;
    }
    main.src = src;
    document.querySelectorAll('.product-thumb').forEach(function (thumb) {
      thumb.classList.toggle('active', thumb.dataset.src === src);
    });
  }

  document.addEventListener('change', function (e) {
    var select = e.target;
    if (!select.classList || !select.classList.contains('product-variant-select')) {
      return;
    }
    var option = select.options[select.selectedIndex];
    var box = select.closest('.product-actions');
    var btn = box ? box.querySelector('.add-to-cart') : null;
    if (btn) {
      btn.dataset.variant = select.value || '0';
      btn.disabled = !select.value;
    }
    if (!option || !select.value) {
      return;
    }
    if (option.dataset.image) {
      showPicture(option.dataset.image);
    }
    var price = document.querySelector('.product-view-page .current-price');
    if (price && option.dataset.price) {
      price.textContent = option.dataset.price;
    }
    var qtyInput = document.getElementById('productQuantity');
    if (qtyInput) {
      if (option.dataset.stock) {
        qtyInput.max = option.dataset.stock;
        if (parseInt(qtyInput.value, 10) > parseInt(option.dataset.stock, 10)) {
          qtyInput.value = option.dataset.stock;
        }
      } else {
        qtyInput.removeAttribute('max');
      }
    }
  });

  /* -------------------------------------------------------------------- */
  /* Listing: filter sidebar (data-on-load of product/list.html)           */
  /* -------------------------------------------------------------------- */

  /**
   * api/product/filter returns the categories, the ones this page shows
   * (`selected`), the search text and the module listing `url`; a change
   * reloads the listing as `url?cat=1,2&search=...`.
   *
   * @param {HTMLElement} element the .filters-wrapper ApiComponent
   * @param {Object} context
   * @returns {Function} cleanup (TemplateManager calls it before a re-render)
   */
  window.initProductFilters = function (element, context) {
    var data = (context && context.data) || {};
    if (!element || !data.url) {
      return;
    }
    var selected = (data.selected || []).map(String);
    element.querySelectorAll('input[name="category_id[]"]').forEach(function (cb) {
      cb.checked = selected.indexOf(String(cb.value)) !== -1;
    });
    var searchInput = element.querySelector('#searchInput');

    function go(clear) {
      var params = new URLSearchParams();
      if (!clear) {
        var ids = [];
        element.querySelectorAll('input[name="category_id[]"]:checked').forEach(function (cb) {
          ids.push(cb.value);
        });
        if (ids.length) {
          params.set('cat', ids.join(','));
        }
        var q = searchInput ? searchInput.value.trim() : '';
        if (q) {
          params.set('search', q);
        }
      }
      var qs = params.toString();
      window.location.href = data.url + (qs ? (data.url.indexOf('?') === -1 ? '?' : '&') + qs : '');
    }

    var ac = new AbortController();
    element.addEventListener('change', function (e) {
      if (e.target && e.target.name === 'category_id[]') {
        go(false);
      }
    }, {signal: ac.signal});
    var searchButton = element.querySelector('#searchButton');
    if (searchButton) {
      searchButton.addEventListener('click', function () { go(false); }, {signal: ac.signal});
    }
    if (searchInput) {
      searchInput.addEventListener('keydown', function (e) {
        if (e.key === 'Enter') {
          e.preventDefault();
          go(false);
        }
      }, {signal: ac.signal});
    }
    var clearButton = element.querySelector('#clearFilters');
    if (clearButton) {
      clearButton.addEventListener('click', function () { go(true); }, {signal: ac.signal});
    }
    return function () {
      ac.abort();
    };
  };

  // data-on-load of the related products (product/view.html); no carousel behaviour yet
  window.initCarousel = window.initCarousel || function () {};
})();

// Register Routes for Product Module via EventManager
EventManager.on('router:initialized', () => {
  if (window.RouterManager) {
    // Phase 1 — Catalog
    RouterManager.register('/products', {
      template: WEB_URL + 'modules/product/template/setup.html',
      title: '{LNG_Products}',
      requireAuth: true
    });
    RouterManager.register('/product', {
      template: WEB_URL + 'modules/product/template/write.html',
      title: '{LNG_Product}',
      requireAuth: true
    });
    RouterManager.register('/product-category', {
      template: WEB_URL + 'modules/product/template/category.html',
      title: '{LNG_Category}',
      requireAuth: true
    });
    RouterManager.register('/product-attribute', {
      template: WEB_URL + 'modules/product/template/attribute.html',
      title: '{LNG_Attributes}',
      requireAuth: true
    });
    RouterManager.register('/product-settings', {
      template: WEB_URL + 'modules/product/template/settings.html',
      title: '{LNG_Settings}',
      requireAuth: true
    });
    // Phase 3 — Admin operations
    RouterManager.register('/product-orders', {
      template: WEB_URL + 'modules/product/template/orders.html',
      title: '{LNG_Orders}',
      requireAuth: true
    });
    RouterManager.register('/product-order', {
      template: WEB_URL + 'modules/product/template/order.html',
      title: '{LNG_Order}',
      requireAuth: true
    });
    RouterManager.register('/product-pos', {
      template: WEB_URL + 'modules/product/template/pos.html',
      title: '{LNG_POS}',
      requireAuth: true
    });
    RouterManager.register('/product-stock', {
      template: WEB_URL + 'modules/product/template/stock.html',
      title: '{LNG_Stock}',
      requireAuth: true
    });
    RouterManager.register('/product-shipping', {
      template: WEB_URL + 'modules/product/template/shipping.html',
      title: '{LNG_Shipping}',
      requireAuth: true
    });
  }
});

/**
 * Languages supported by the product editor (kept in sync with Product\*\Model::$languages).
 */
const PRODUCT_LANGS = ['th', 'en'];

/**
 * Escape a value for safe insertion into HTML attributes/text.
 */
function productEsc(v) {
  return String(v == null ? '' : v)
    .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;').replace(/'/g, '&#39;');
}

/**
 * data-script hook for the product editor (write.html).
 * Builds multilingual tabs, the attribute chooser and the variant matrix,
 * and serializes `detail` + `variants` into hidden JSON inputs on submit.
 *
 * @param {HTMLElement} element
 * @param {Object} data API payload from ../api/product/write/get
 */
function initProductEditor(element, data) {
  const form = (element.matches('#productForm') ? element : element.querySelector('#productForm'));
  if (!form) {
    return;
  }
  const product = (data && (data.data || data)) || {};
  const options = product.options || {};
  const attributes = options.attributes || [];
  const detail = product.detail || {};

  // --- Multilingual tabs -------------------------------------------------
  const tabs = element.querySelector('#langTabs');
  const panels = element.querySelector('#langPanels');
  if (tabs && panels) {
    tabs.innerHTML = '';
    panels.innerHTML = '';
    PRODUCT_LANGS.forEach((lng, i) => {
      const d = detail[lng] || {};
      const tab = document.createElement('button');
      tab.type = 'button';
      tab.className = 'lang-tab' + (i === 0 ? ' active' : '');
      tab.textContent = lng.toUpperCase();
      tab.dataset.lng = lng;
      tab.addEventListener('click', () => {
        tabs.querySelectorAll('.lang-tab').forEach(t => t.classList.remove('active'));
        panels.querySelectorAll('.lang-panel').forEach(p => { p.hidden = true; });
        tab.classList.add('active');
        panels.querySelector('.lang-panel[data-lng="' + lng + '"]').hidden = false;
      });
      tabs.appendChild(tab);

      const panel = document.createElement('div');
      panel.className = 'lang-panel';
      panel.dataset.lng = lng;
      panel.hidden = i !== 0;
      panel.innerHTML =
        '<div><label>{LNG_Name} (' + lng.toUpperCase() + ')</label>' +
        '<span class="form-control icon-shop"><input type="text" class="pd-topic" data-lng="' + lng + '" value="' + productEsc(d.topic) + '"></span></div>' +
        '<div><label>{LNG_Short description}</label>' +
        '<span class="form-control icon-file"><input type="text" class="pd-description" data-lng="' + lng + '" value="' + productEsc(d.description) + '"></span></div>' +
        '<div><label>{LNG_Detail}</label>' +
        '<textarea rows="5" class="pd-detail" id="pd_detail_' + lng + '" data-lng="' + lng + '">' + productEsc(d.detail) + '</textarea></div>';
      panels.appendChild(panel);
    });
  }

  // Rich text editor for the description (same set-up as the document
  // editor); the content is read back from the editor on submit
  const detailEditors = {};
  if (panels && window.ElementManager && typeof ElementManager.enhance === 'function') {
    panels.querySelectorAll('.pd-detail').forEach(textarea => {
      textarea.dataset.element = 'richtext';
      textarea.dataset.rteProfile = 'basic';
      textarea.dataset.rteMinHeight = '250';
      const instance = ElementManager.enhance(textarea);
      if (instance) {
        detailEditors[textarea.dataset.lng] = instance;
      }
    });
  }

  // --- Product type toggle ----------------------------------------------
  const typeSel = element.querySelector('#product_type');
  const variantSection = element.querySelector('#variantSection');
  const basePriceWrap = element.querySelector('#basePriceWrap');
  const syncType = () => {
    const isVar = typeSel && typeSel.value === 'variable';
    if (variantSection) variantSection.hidden = !isVar;
    if (basePriceWrap) basePriceWrap.hidden = isVar;
  };
  if (typeSel) {
    typeSel.addEventListener('change', syncType);
    setTimeout(syncType, 0);
  }

  // --- Attribute chooser -------------------------------------------------
  const chooser = element.querySelector('#attributeChooser');
  if (chooser) {
    chooser.innerHTML = '';
    attributes.forEach(attr => {
      const name = (attr.name && (attr.name.th || attr.name.en)) || ('#' + attr.id);
      let html = '<fieldset class="attr-group"><legend>' + productEsc(name) + '</legend>';
      (attr.values || []).forEach(val => {
        const label = (val.value && (val.value.th || val.value.en)) || ('#' + val.id);
        html += '<label class="attr-chk"><input type="checkbox" class="attr-value" data-attr-id="' + attr.id + '" value="' + val.id + '"> ' + productEsc(label) + '</label>';
      });
      html += '</fieldset>';
      chooser.insertAdjacentHTML('beforeend', html);
    });
  }

  // Map attribute_value_id -> { attribute_id, label }
  const valueIndex = {};
  attributes.forEach(attr => {
    const name = (attr.name && (attr.name.th || attr.name.en)) || ('#' + attr.id);
    (attr.values || []).forEach(val => {
      const label = (val.value && (val.value.th || val.value.en)) || ('#' + val.id);
      valueIndex[val.id] = { attribute_id: attr.id, attr_name: name, label: label };
    });
  });

  const variantRows = element.querySelector('#variantRows');

  /** Build a single variant row. */
  function buildVariantRow(valueIds, preset) {
    preset = preset || {};
    const labelParts = valueIds.map(id => (valueIndex[id] ? valueIndex[id].attr_name + ': ' + valueIndex[id].label : ''));
    const tr = document.createElement('tr');
    tr.dataset.valueIds = JSON.stringify(valueIds);
    tr.innerHTML =
      '<td>' + productEsc(labelParts.join(' / ')) + '</td>' +
      '<td><input type="text" class="v-sku" value="' + productEsc(preset.sku) + '"></td>' +
      '<td><input type="number" step="0.01" class="v-price" value="' + productEsc(preset.price != null ? preset.price : '') + '"></td>' +
      '<td><input type="number" step="0.01" class="v-sale" value="' + productEsc(preset.sale_price != null ? preset.sale_price : '') + '"></td>' +
      '<td>' + variantImageSelect(preset.image_id) + '</td>' +
      '<td class="center"><input type="checkbox" class="v-pub" ' + (preset.published === 0 ? '' : 'checked') + '></td>';
    return tr;
  }

  /** The variant's picture: one of the product's saved pictures (1 = main). */
  function variantImageSelect(imageId) {
    let html = '<select class="v-image"><option value="0">—</option>';
    (product.images || []).forEach((img, i) => {
      html += '<option value="' + img.id + '"' + (String(img.id) === String(imageId) ? ' selected' : '') + '>' + (i + 1) + '. ' + productEsc(img.name) + '</option>';
    });
    return html + '</select>';
  }

  /** Cartesian product of selected values grouped by attribute. */
  function cartesian(groups) {
    return groups.reduce((acc, group) => {
      const out = [];
      acc.forEach(prefix => group.forEach(item => out.push(prefix.concat([item]))));
      return out;
    }, [[]]);
  }

  const genBtn = element.querySelector('#generateVariantsBtn');
  if (genBtn && variantRows) {
    genBtn.addEventListener('click', () => {
      const byAttr = {};
      element.querySelectorAll('.attr-value:checked').forEach(cb => {
        const aid = cb.dataset.attrId;
        (byAttr[aid] = byAttr[aid] || []).push(parseInt(cb.value, 10));
      });
      const groups = Object.values(byAttr);
      variantRows.innerHTML = '';
      if (!groups.length) {
        return;
      }
      cartesian(groups).forEach(combo => variantRows.appendChild(buildVariantRow(combo)));
    });
  }

  // Pre-populate existing variants (edit mode)
  if (variantRows && Array.isArray(product.variants) && product.variants.length) {
    variantRows.innerHTML = '';
    product.variants.forEach(v => {
      const valueIds = (v.values || []).map(x => x.attribute_value_id);
      variantRows.appendChild(buildVariantRow(valueIds, v));
    });
    // Reflect selected attribute values in the chooser
    const used = {};
    product.variants.forEach(v => (v.values || []).forEach(x => used[x.attribute_value_id] = true));
    element.querySelectorAll('.attr-value').forEach(cb => { if (used[cb.value]) cb.checked = true; });
  }

  // --- Serialize on submit ----------------------------------------------
  form.addEventListener('submit', () => {
    const detailOut = {};
    PRODUCT_LANGS.forEach(lng => {
      detailOut[lng] = {
        topic: (element.querySelector('.pd-topic[data-lng="' + lng + '"]') || {}).value || '',
        description: (element.querySelector('.pd-description[data-lng="' + lng + '"]') || {}).value || '',
        detail: detailEditors[lng] && typeof detailEditors[lng].getValue === 'function'
          ? detailEditors[lng].getValue() || ''
          : (element.querySelector('.pd-detail[data-lng="' + lng + '"]') || {}).value || '',
        // no keywords field in the form — keep what the product already has
        keywords: (detail[lng] && detail[lng].keywords) || ''
      };
    });
    const detailInput = element.querySelector('#detailJson');
    if (detailInput) detailInput.value = JSON.stringify(detailOut);

    const variants = [];
    if (typeSel && typeSel.value === 'variable') {
      (variantRows ? variantRows.querySelectorAll('tr') : []).forEach(tr => {
        const valueIds = JSON.parse(tr.dataset.valueIds || '[]');
        variants.push({
          sku: tr.querySelector('.v-sku').value,
          price: tr.querySelector('.v-price').value,
          sale_price: tr.querySelector('.v-sale').value,
          image_id: tr.querySelector('.v-image').value,
          published: tr.querySelector('.v-pub').checked ? 1 : 0,
          values: valueIds.map(id => ({
            attribute_id: valueIndex[id] ? valueIndex[id].attribute_id : 0,
            attribute_value_id: id
          }))
        });
      });
    }
    const variantsInput = element.querySelector('#variantsJson');
    if (variantsInput) variantsInput.value = JSON.stringify(variants);
  }, true);
}

/**
 * data-script hook for the attribute editor (attribute.html).
 * Lets the admin add/remove attributes and their values, serialized to `datas`.
 *
 * @param {HTMLElement} element
 * @param {Object} data API payload from ../api/product/attribute/get
 */
function initProductAttributeEditor(element, data) {
  const form = (element.matches('#attributeForm') ? element : element.querySelector('#attributeForm'));
  const list = element.querySelector('#attributeList');
  if (!form || !list) {
    return;
  }
  const payload = (data && (data.data || data)) || {};
  const existing = payload.datas || [];

  function addAttribute(attr) {
    attr = attr || { name: {}, values: [] };
    const card = document.createElement('div');
    card.className = 'attribute-card card';
    // ids go back to the server so it updates rows in place (variants reference them)
    card.dataset.id = attr.id || 0;
    let nameInputs = '';
    PRODUCT_LANGS.forEach(lng => {
      nameInputs += '<span class="form-control"><input type="text" class="a-name" data-lng="' + lng + '" placeholder="' + lng.toUpperCase() + '" value="' + productEsc((attr.name || {})[lng]) + '"></span>';
    });
    card.innerHTML =
      '<div class="attr-head"><strong data-i18n>Attribute</strong> ' + nameInputs +
      ' <button type="button" class="btn btn-danger icon-delete a-del" data-i18n>Remove</button></div>' +
      '<div class="attr-values"></div>' +
      '<button type="button" class="btn icon-new a-add-val" data-i18n>Add value</button>';
    const valuesWrap = card.querySelector('.attr-values');

    function addValue(val) {
      val = val || { value: {} };
      const row = document.createElement('div');
      row.className = 'attr-value-row';
      row.dataset.id = val.id || 0;
      let inputs = '';
      PRODUCT_LANGS.forEach(lng => {
        inputs += '<span class="form-control"><input type="text" class="av-value" data-lng="' + lng + '" placeholder="' + lng.toUpperCase() + '" value="' + productEsc((val.value || {})[lng]) + '"></span>';
      });
      row.innerHTML = inputs + ' <button type="button" class="btn btn-danger icon-delete av-del"></button>';
      row.querySelector('.av-del').addEventListener('click', () => row.remove());
      valuesWrap.appendChild(row);
    }

    card.querySelector('.a-del').addEventListener('click', () => card.remove());
    card.querySelector('.a-add-val').addEventListener('click', () => addValue());
    (attr.values || []).forEach(addValue);
    list.appendChild(card);
  }

  existing.forEach(addAttribute);

  const addBtn = element.querySelector('#addAttributeBtn');
  if (addBtn) {
    addBtn.addEventListener('click', () => addAttribute());
  }

  form.addEventListener('submit', () => {
    const datas = [];
    list.querySelectorAll('.attribute-card').forEach(card => {
      const name = {};
      card.querySelectorAll('.attr-head .a-name').forEach(inp => { if (inp.value.trim()) name[inp.dataset.lng] = inp.value.trim(); });
      const values = [];
      card.querySelectorAll('.attr-value-row').forEach(row => {
        const value = {};
        row.querySelectorAll('.av-value').forEach(inp => { if (inp.value.trim()) value[inp.dataset.lng] = inp.value.trim(); });
        if (Object.keys(value).length) values.push({ id: parseInt(row.dataset.id, 10) || 0, value: value });
      });
      if (Object.keys(name).length) datas.push({ id: parseInt(card.dataset.id, 10) || 0, name: name, values: values });
    });
    const input = element.querySelector('#attributeDatas');
    if (input) input.value = JSON.stringify(datas);
  }, true);
}

/**
 * Resolve the API base URL (honouring sub-directory deployments).
 *
 * @returns {string}
 */
function productApiBase() {
  const base = (typeof window.WEB_URL === 'string' && window.WEB_URL !== '') ? window.WEB_URL : '/';
  return (base.endsWith('/') ? base : base + '/') + 'api/product';
}

/**
 * Translate a string via the framework when available.
 *
 * @param {string} text
 * @returns {string}
 */
function productT(text) {
  if (window.Now && typeof window.Now.translate === 'function') {
    return window.Now.translate(text);
  }
  return text;
}

/**
 * Show a notification via the framework when available.
 *
 * @param {string} type success|error|warning|info
 * @param {string} message
 */
function productNotify(type, message) {
  if (window.NotificationManager && typeof window.NotificationManager[type] === 'function') {
    window.NotificationManager[type](message);
  } else if (window.NotificationManager && typeof window.NotificationManager.show === 'function') {
    window.NotificationManager.show({ type: type, message: message });
  }
}

/**
 * Normalize the various envelopes the API may return into { success, message, data }.
 *
 * @param {*} payload
 * @returns {{success: boolean, message: string, data: *}}
 */
function productUnwrap(payload) {
  if (payload && typeof payload === 'object') {
    if (typeof payload.success === 'boolean') {
      return payload;
    }
    if (payload.data && typeof payload.data === 'object' && typeof payload.data.success === 'boolean') {
      return payload.data;
    }
  }
  return { success: false, message: 'Unexpected API response format.', data: null };
}

/**
 * POST JSON to a product API endpoint with CSRF header and cookie credentials.
 *
 * @param {string} action e.g. 'pos/checkout'
 * @param {Object} payload
 * @returns {Promise<Object>} resolved data object
 */
async function productGet(action, params) {
  const query = new URLSearchParams(params || {}).toString();
  const response = await fetch(productApiBase() + '/' + action + (query ? '?' + query : ''), {
    headers: { Accept: 'application/json' },
    credentials: 'same-origin'
  });
  const raw = await response.json().catch(() => ({}));
  const normalized = productUnwrap(raw);
  if (!response.ok || !normalized.success) {
    throw new Error(normalized.message || ('Request failed (' + response.status + ')'));
  }
  return normalized.data || {};
}
async function productPost(action, payload) {
  const headers = { Accept: 'application/json', 'Content-Type': 'application/json' };
  const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
  if (csrfToken) {
    headers['X-CSRF-Token'] = csrfToken;
  }
  const response = await fetch(productApiBase() + '/' + action, {
    method: 'POST',
    headers: headers,
    credentials: 'same-origin',
    body: JSON.stringify(payload || {})
  });
  const raw = await response.json().catch(() => ({}));
  const normalized = productUnwrap(raw);
  if (!response.ok || !normalized.success) {
    throw new Error(normalized.message || ('Request failed (' + response.status + ')'));
  }
  return normalized.data || {};
}

/**
 * data-script hook for the stock page (stock.html).
 * Wires the DataTable "Receive" row action to open the receive/adjust panel,
 * loads variants + lots + movements and posts receive/adjust requests.
 *
 * @param {HTMLElement} element
 */
function initProductStock(element) {
  const params = new URLSearchParams(window.location.search);
  const moduleId = params.get('module_id') || '';
  const panel = element.querySelector('#stockPanel');
  const variantSel = element.querySelector('#receiveVariant');
  const varRows = element.querySelector('#stockVariantRows');
  const moveRows = element.querySelector('#stockMovementRows');
  const topicEl = element.querySelector('#stockProductTopic');
  let currentProductId = parseInt(params.get('id') || '0', 10);

  async function loadProduct(productId) {
    currentProductId = productId;
    try {
      const url = productApiBase() + '/stock/get?module_id=' + encodeURIComponent(moduleId) + '&id=' + encodeURIComponent(productId);
      const response = await fetch(url, { headers: { Accept: 'application/json' }, credentials: 'same-origin' });
      const data = productUnwrap(await response.json().catch(() => ({}))).data || {};
      if (topicEl) topicEl.textContent = (data.product && data.product.topic) || '';
      if (variantSel) {
        variantSel.innerHTML = '';
        (data.variants || []).forEach(v => {
          const opt = document.createElement('option');
          opt.value = v.id;
          opt.textContent = (v.sku ? v.sku + ' — ' : '') + (v.label || productT('Default')) + ' (' + v.stock_qty + ')';
          variantSel.appendChild(opt);
        });
      }
      if (varRows) {
        varRows.innerHTML = '';
        (data.variants || []).forEach(v => {
          varRows.insertAdjacentHTML('beforeend',
            '<tr><td>' + productEsc(v.sku) + '</td><td>' + productEsc(v.label) + '</td><td class="center">' + productEsc(v.stock_qty) + '</td></tr>');
        });
      }
      if (moveRows) {
        moveRows.innerHTML = '';
        (data.movements || []).forEach(m => {
          moveRows.insertAdjacentHTML('beforeend',
            '<tr><td class="center">' + productEsc(m.created_at) + '</td><td class="center">' + productEsc(m.type) + '</td>' +
            '<td class="right">' + productEsc(m.qty) + '</td><td class="right">' + productEsc(m.unit_cost) + '</td>' +
            '<td>' + productEsc(m.ref_type) + (m.ref_id ? ' #' + m.ref_id : '') + '</td></tr>');
        });
      }
      if (panel) panel.hidden = false;
    } catch (e) {
      productNotify('error', e.message);
    }
  }

  // Intercept the DataTable "edit" (Receive) action: open the panel instead of navigating.
  element.addEventListener('click', ev => {
    const btn = ev.target.closest('.icon-purchase[data-row-action], button.icon-purchase');
    const tr = ev.target.closest('tr[data-id]');
    if (tr && ev.target.closest('button.icon-purchase')) {
      ev.preventDefault();
      ev.stopPropagation();
      loadProduct(parseInt(tr.dataset.id, 10));
    }
  }, true);

  async function submitStock(endpoint) {
    if (!currentProductId) {
      productNotify('warning', productT('Please select an item'));
      return;
    }
    try {
      await productPost('stock/' + endpoint, {
        module_id: moduleId,
        product_id: currentProductId,
        variant_id: variantSel ? variantSel.value : 0,
        qty: (element.querySelector('#receiveQty') || {}).value || 0,
        unit_cost: (element.querySelector('#receiveCost') || {}).value || 0,
        ref: (element.querySelector('#receiveRef') || {}).value || ''
      });
      productNotify('success', productT('Saved successfully'));
      loadProduct(currentProductId);
    } catch (e) {
      productNotify('error', e.message);
    }
  }

  const receiveForm = element.querySelector('#receiveForm');
  if (receiveForm) {
    receiveForm.addEventListener('submit', ev => { ev.preventDefault(); submitStock('receive'); });
  }
  const adjustBtn = element.querySelector('#adjustBtn');
  if (adjustBtn) {
    adjustBtn.addEventListener('click', () => submitStock('adjust'));
  }

  if (currentProductId > 0) {
    loadProduct(currentProductId);
  }
}

/**
 * data-script hook for the shipping editor (shipping.html).
 * Adds/removes shipping methods serialized to the `datas` hidden input.
 *
 * @param {HTMLElement} element
 * @param {Object} data API payload from ../api/product/shipping/get
 */
function initProductShipping(element, data) {
  const form = (element.matches('#shippingForm') ? element : element.querySelector('#shippingForm'));
  const list = element.querySelector('#shippingList');
  if (!form || !list) {
    return;
  }
  const payload = (data && (data.data || data)) || {};
  const existing = payload.datas || [];

  function addMethod(method) {
    method = method || { name: {}, calc_type: 'flat', base_rate: '', rate_per_kg: '', free_over: '', published: 1 };
    const card = document.createElement('div');
    card.className = 'shipping-card card';
    card.dataset.id = method.id || 0;
    let nameInputs = '';
    PRODUCT_LANGS.forEach(lng => {
      nameInputs += '<span class="form-control"><input type="text" class="s-name" data-lng="' + lng + '" placeholder="' + lng.toUpperCase() + '" value="' + productEsc((method.name || {})[lng]) + '"></span>';
    });
    const sel = ['flat', 'by_weight', 'free'].map(t =>
      '<option value="' + t + '"' + (method.calc_type === t ? ' selected' : '') + '>' + t + '</option>').join('');
    card.innerHTML =
      '<div class="ship-head"><strong data-i18n>Method</strong> ' + nameInputs +
      ' <button type="button" class="btn btn-danger icon-delete s-del" data-i18n>Remove</button></div>' +
      '<div class="ship-rates">' +
      '<label><span data-i18n>Calc type</span> <select class="s-calc">' + sel + '</select></label> ' +
      '<label><span data-i18n>Base rate</span> <input type="number" step="0.01" class="s-base" value="' + productEsc(method.base_rate) + '"></label> ' +
      '<label><span data-i18n>Rate per kg</span> <input type="number" step="0.01" class="s-perkg" value="' + productEsc(method.rate_per_kg) + '"></label> ' +
      '<label><span data-i18n>Free over</span> <input type="number" step="0.01" class="s-free" value="' + productEsc(method.free_over) + '"></label> ' +
      '<label><input type="checkbox" class="s-pub"' + (method.published === 0 ? '' : ' checked') + '> <span data-i18n>{LNG_Published}</span></label>' +
      '</div>';
    card.querySelector('.s-del').addEventListener('click', () => card.remove());
    list.appendChild(card);
  }

  existing.forEach(addMethod);

  const addBtn = element.querySelector('#addShippingBtn');
  if (addBtn) {
    addBtn.addEventListener('click', () => addMethod());
  }

  form.addEventListener('submit', () => {
    const datas = [];
    list.querySelectorAll('.shipping-card').forEach(card => {
      const name = {};
      card.querySelectorAll('.s-name').forEach(inp => { if (inp.value.trim()) name[inp.dataset.lng] = inp.value.trim(); });
      if (!Object.keys(name).length) {
        return;
      }
      datas.push({
        id: parseInt(card.dataset.id, 10) || 0,
        name: name,
        calc_type: card.querySelector('.s-calc').value,
        base_rate: card.querySelector('.s-base').value || 0,
        rate_per_kg: card.querySelector('.s-perkg').value || 0,
        free_over: card.querySelector('.s-free').value,
        published: card.querySelector('.s-pub').checked ? 1 : 0
      });
    });
    const input = element.querySelector('#shippingDatas');
    if (input) input.value = JSON.stringify(datas);
  }, true);
}

/**
 * data-script hook for the POS register (pos.html).
 * Drives product search, the in-memory cart, totals and checkout.
 *
 * @param {HTMLElement} element
 */
function initProductPos(element) {
  const params = new URLSearchParams(window.location.search);
  const moduleId = (element.querySelector('#posModuleId') || {}).value || params.get('module_id') || '';
  const searchInput = element.querySelector('#posSearch');
  const results = element.querySelector('#posResults');
  const cartRows = element.querySelector('#posCartRows');
  const subtotalEl = element.querySelector('#posSubtotal');
  const changeEl = element.querySelector('#posChange');
  const paidInput = element.querySelector('#posPaid');
  const receiptEl = element.querySelector('#posReceipt');
  const cart = [];

  function money(n) {
    return (Math.round((Number(n) || 0) * 100) / 100).toFixed(2);
  }

  function renderCart() {
    let subtotal = 0;
    cartRows.innerHTML = '';
    cart.forEach((item, i) => {
      const lineTotal = item.price * item.qty;
      subtotal += lineTotal;
      cartRows.insertAdjacentHTML('beforeend',
        '<tr><td>' + productEsc(item.topic) + (item.label ? ' <small>(' + productEsc(item.label) + ')</small>' : '') + '</td>' +
        '<td class="right">' + money(item.price) + '</td>' +
        '<td class="center"><input type="number" min="1" step="1" class="pos-qty" data-i="' + i + '" value="' + item.qty + '"></td>' +
        '<td class="right">' + money(lineTotal) + '</td>' +
        '<td><button type="button" class="btn btn-danger icon-delete pos-rm" data-i="' + i + '"></button></td></tr>');
    });
    subtotalEl.textContent = money(subtotal);
    updateChange();
  }

  function updateChange() {
    const subtotal = parseFloat(subtotalEl.textContent) || 0;
    const paid = parseFloat(paidInput.value) || 0;
    changeEl.textContent = money(Math.max(0, paid - subtotal));
  }

  function addToCart(p) {
    const found = cart.find(c => c.variant_id === p.variant_id);
    if (found) {
      found.qty += 1;
    } else {
      cart.push({ product_id: p.product_id, variant_id: p.variant_id, topic: p.topic, label: p.label, price: p.price, qty: 1 });
    }
    renderCart();
  }

  let searchTimer = null;
  async function runSearch() {
    const q = searchInput.value.trim();
    try {
      const data = await productPost('pos/search', { module_id: moduleId, q: q });
      results.innerHTML = '';
      (data.items || []).forEach(it => {
        const div = document.createElement('div');
        div.className = 'pos-result';
        div.innerHTML = '<strong>' + productEsc(it.topic) + '</strong> ' + (it.label ? '<small>' + productEsc(it.label) + '</small> ' : '') +
          '<span class="right">' + money(it.price) + '</span> <small>(' + productEsc(it.stock) + ')</small>';
        div.addEventListener('click', () => addToCart(it));
        results.appendChild(div);
      });
    } catch (e) {
      productNotify('error', e.message);
    }
  }

  if (searchInput) {
    searchInput.addEventListener('input', () => {
      clearTimeout(searchTimer);
      searchTimer = setTimeout(runSearch, 250);
    });
  }
  if (paidInput) {
    paidInput.addEventListener('input', updateChange);
  }

  cartRows.addEventListener('click', ev => {
    const rm = ev.target.closest('.pos-rm');
    if (rm) {
      cart.splice(parseInt(rm.dataset.i, 10), 1);
      renderCart();
    }
  });
  cartRows.addEventListener('change', ev => {
    const q = ev.target.closest('.pos-qty');
    if (q) {
      const i = parseInt(q.dataset.i, 10);
      cart[i].qty = Math.max(1, parseInt(q.value, 10) || 1);
      renderCart();
    }
  });

  const checkoutBtn = element.querySelector('#posCheckoutBtn');
  if (checkoutBtn) {
    checkoutBtn.addEventListener('click', async () => {
      if (!cart.length) {
        productNotify('warning', productT('Cart is empty'));
        return;
      }
      try {
        const data = await productPost('pos/checkout', {
          module_id: moduleId,
          items: cart.map(c => ({ product_id: c.product_id, variant_id: c.variant_id, qty: c.qty })),
          paid: parseFloat(paidInput.value) || 0,
          cust_name: (element.querySelector('#posCustName') || {}).value || ''
        });
        productNotify('success', productT('Saved successfully'));
        if (receiptEl) {
          receiptEl.hidden = false;
          receiptEl.innerHTML = '<h2 class="icon-print">' + productEsc(data.order_no) + '</h2>' +
            '<p>' + productT('Total') + ': <strong>' + money(data.grand_total) + '</strong></p>' +
            '<p>' + productT('Paid') + ': ' + money(data.paid) + ' / ' + productT('Change') + ': <strong>' + money(data.change) + '</strong></p>';
        }
        cart.length = 0;
        renderCart();
        if (paidInput) paidInput.value = 0;
        const custName = element.querySelector('#posCustName');
        if (custName) custName.value = '';
      } catch (e) {
        productNotify('error', e.message);
      }
    });
  }

  renderCart();
}

/**
 * data-script hook for the admin order detail (order.html).
 * Renders the order from the loaded payload and wires the verify-payment,
 * update-status and ship actions.
 *
 * @param {HTMLElement} element
 * @param {Object} data API payload from ../api/product/order/get
 */
function initProductOrder(element, data) {
  const payload = (data && (data.data || data)) || {};
  if (!payload.order) {
    // Called by the router (data-script) with the route only: load the order
    const query = (data && data.query) || Object.fromEntries(new URLSearchParams(window.location.search));
    productGet('order/get', { id: query.id || '', module_id: query.module_id || '' })
      .then(loaded => initProductOrder(element, loaded))
      .catch(error => productNotify('error', error.message));
    return;
  }
  const moduleId = payload.module_id || (element.querySelector('#orderModuleId') || {}).value || '';
  const order = payload.order || {};
  const orderId = order.id || 0;

  function money(n) {
    return (Math.round((Number(n) || 0) * 100) / 100).toFixed(2);
  }
  function setText(sel, value) {
    const el = element.querySelector(sel);
    if (el) el.textContent = value == null ? '' : value;
  }

  setText('#orderNo', order.order_no);
  setText('#orderStatusText', order.order_status_text);

  const cust = element.querySelector('#orderCustomer');
  if (cust) {
    cust.innerHTML =
      '<div>' + productEsc(order.cust_name) + ' — ' + productEsc(order.cust_phone) + '</div>' +
      '<div>' + productEsc(order.cust_email) + '</div>' +
      '<div>' + productEsc(order.ship_address) + ' ' + productEsc(order.ship_province) + ' ' + productEsc(order.ship_zipcode) + '</div>';
  }

  const itemRows = element.querySelector('#orderItemRows');
  if (itemRows) {
    itemRows.innerHTML = '';
    (payload.items || []).forEach(it => {
      itemRows.insertAdjacentHTML('beforeend',
        '<tr><td>' + productEsc(it.product_name) + (it.variant_label ? ' <small>(' + productEsc(it.variant_label) + ')</small>' : '') + '</td>' +
        '<td class="center">' + productEsc(it.sku) + '</td>' +
        '<td class="right">' + money(it.unit_price) + '</td>' +
        '<td class="center">' + productEsc(it.qty) + '</td>' +
        '<td class="right">' + money(it.line_total) + '</td></tr>');
    });
  }
  const totals = element.querySelector('#orderTotals');
  if (totals) {
    totals.innerHTML =
      '<div>' + productT('Subtotal') + ': ' + money(order.subtotal) + '</div>' +
      '<div>' + productT('Shipping') + ': ' + money(order.shipping_fee) + '</div>' +
      '<div>' + productT('Discount') + ': ' + money(order.discount) + '</div>' +
      '<div><strong>' + productT('Total') + ': ' + money(order.grand_total) + '</strong></div>';
  }

  const pay = element.querySelector('#orderPayment');
  const payment = payload.payment || null;
  if (pay) {
    pay.innerHTML = payment
      ? '<div>' + productEsc(productT(payment.method)) + ' — ' + productEsc(productT(payment.status)) + '</div>' +
        '<div>' + productT('Amount') + ': ' + money(payment.amount) + (payment.ref ? ' / ' + productEsc(payment.ref) : '') + '</div>'
      : '<div>-</div>';
  }
  const proofWrap = element.querySelector('#orderPaymentProof');
  if (proofWrap && payload.payment_proof) {
    proofWrap.innerHTML = '<a href="' + productEsc(payload.payment_proof) + '" target="_blank"><img class="payment-proof-img" src="' + productEsc(payload.payment_proof) + '" alt="slip"></a>';
  }
  const verifyActions = element.querySelector('#verifyActions');
  if (verifyActions && payment && payment.status === 'pending') {
    verifyActions.hidden = false;
  }

  async function postAction(action, body) {
    try {
      await productPost('order/' + action, Object.assign({ module_id: moduleId, order_id: orderId }, body));
      productNotify('success', productT('Saved successfully'));
      if (window.RouterManager && typeof window.RouterManager.reload === 'function') {
        window.RouterManager.reload();
      } else {
        window.location.reload();
      }
    } catch (e) {
      productNotify('error', e.message);
    }
  }

  const approveBtn = element.querySelector('#verifyApproveBtn');
  if (approveBtn) approveBtn.addEventListener('click', () => postAction('verify-payment', { decision: 'approve' }));
  const rejectBtn = element.querySelector('#verifyRejectBtn');
  if (rejectBtn) rejectBtn.addEventListener('click', () => postAction('verify-payment', { decision: 'reject' }));

  // Status select
  const statusSel = element.querySelector('#statusSelect');
  if (statusSel) {
    statusSel.innerHTML = '';
    (payload.status_options || []).forEach(o => {
      const opt = document.createElement('option');
      opt.value = o.value;
      opt.textContent = o.text;
      if (String(order.order_status) === String(o.value)) opt.selected = true;
      statusSel.appendChild(opt);
    });
  }
  const statusForm = element.querySelector('#statusForm');
  if (statusForm) {
    statusForm.addEventListener('submit', ev => {
      ev.preventDefault();
      postAction('update-status', {
        order_status: statusSel ? statusSel.value : 0,
        note: (element.querySelector('#statusNote') || {}).value || ''
      });
    });
  }

  // Shipping method select
  const shipSel = element.querySelector('#shipMethod');
  if (shipSel) {
    shipSel.innerHTML = '';
    (payload.shipping_options || []).forEach(o => {
      const opt = document.createElement('option');
      opt.value = o.value;
      opt.textContent = o.text;
      shipSel.appendChild(opt);
    });
  }
  const shipForm = element.querySelector('#shipForm');
  if (shipForm) {
    shipForm.addEventListener('submit', ev => {
      ev.preventDefault();
      postAction('ship', {
        shipping_method_id: shipSel ? shipSel.value : 0,
        tracking_no: (element.querySelector('#shipTracking') || {}).value || ''
      });
    });
  }

  const shipmentRows = element.querySelector('#shipmentRows');
  if (shipmentRows) {
    shipmentRows.innerHTML = '';
    (payload.shipments || []).forEach(s => {
      shipmentRows.insertAdjacentHTML('beforeend',
        '<tr><td class="center">' + productEsc(s.shipped_at || s.created_at) + '</td>' +
        '<td>' + productEsc(s.tracking_no) + '</td><td class="center">' + productEsc(s.status) + '</td></tr>');
    });
  }

  const historyRows = element.querySelector('#historyRows');
  if (historyRows) {
    historyRows.innerHTML = '';
    (payload.history || []).forEach(h => {
      historyRows.insertAdjacentHTML('beforeend',
        '<tr><td class="center">' + productEsc(h.created_at) + '</td>' +
        '<td>' + productEsc(h.order_status_text) + '</td><td>' + productEsc(h.note) + '</td></tr>');
    });
  }
}

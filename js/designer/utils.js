/**
 * GCMS Designer – Utilities
 *
 * Pure helper functions: string escaping, DOM helpers, notifications,
 * text utilities. Uses NotificationManager when available.
 *
 * @filesource js/designer/utils.js
 */

/**
 * แท็กที่อนุญาตใน Raw HTML textarea ของ Text Element inspector
 * และ contenteditable (หลัง sanitize) — แก้รายการนี้ที่เดียว
 */
/** @type {Set<string>} */
const INLINE_HTML_ALLOWED_TAGS = new Set([
  'b', 'strong', 'i', 'em', 'u', 's', 'strike', 'del', 'ins', 'mark',
  'sub', 'sup', 'small', 'span', 'br', 'wbr', 'hr',
  'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'blockquote',
  'ul', 'ol', 'li', 'p', 'div', 'a'
]);

/**
 * Containers whose direct children can be reordered / deleted via context menu.
 * Keep in sync with template structures in state.js.
 */
export const SWAPPABLE_PARENT_SELECTOR = [
  '[data-drop-zone]',
  '[data-repeat-container]',
  '.contents-grid',
  '.sections-list',
  '.section-content',
  '.section-actions',
  '.section-title',
  '.section-body',
  '.section-row',
  '.gcms-column',
  '.card-body',
  '.about-content',
  '.content-card',
  '.gcms-stats-bar',
  '.gcms-stat-item',
  '.cta-features',
  '.author-info',
  '.member-social',
  '.testimonial-author',
  '.home-content',
  '.footer-content',
  '.footer-section',
  '.footer-links'
].join(', ');

/** @type {Set<string>} */
const INLINE_HTML_BLOCKED_TAGS = new Set([
  'script', 'iframe', 'object', 'embed', 'style', 'link', 'meta', 'base',
  'form', 'input', 'textarea', 'button', 'select', 'option', 'optgroup',
  'svg', 'math', 'template', 'noscript', 'applet', 'frame', 'frameset',
  'audio', 'video', 'source', 'track', 'picture', 'canvas', 'map', 'area',
  'img', 'table', 'thead', 'tbody', 'tfoot', 'tr', 'th', 'td',
  'caption', 'colgroup', 'col', 'details', 'dialog', 'fieldset', 'legend',
  'label', 'output', 'datalist', 'marquee', 'blink'
]);

/** @type {Set<string>} */
const INLINE_HTML_ALLOWED_ATTRS = new Set([
  'class', 'style', 'href', 'target',
  'data-editable', 'data-drop-zone', 'data-editor-field-name',
  'data-repeat-container', 'data-repeat-item'
]);

/** สีปุ่ม link ที่ inspector รองรับ (sync กับ select ใน Editable.js) */
const LINK_BTN_COLORS = [
  'primary', 'secondary', 'success', 'warning', 'danger', 'info',
  'orange', 'brown', 'cyan', 'gold', 'green', 'magenta', 'pink', 'purple', 'rosy', 'red'
];

const LINK_STYLE_RESET_CLASSES = [
  'btn',
  ...LINK_BTN_COLORS.map(color => `btn-${color}`)
];
const BTN_SHAPE_CLASSES = ['rounded', 'pill', 'circle'];
const BTN_SIZE_CLASSES = ['small', 'large'];

function isUnsafeUrl(value) {
  const normalized = String(value || '').trim().replace(/[\u0000-\u001F\u007F\s]+/g, '').toLowerCase();
  return /^(?:javascript|vbscript|data):/i.test(normalized);
}

function sanitizeStyleAttr(value) {
  const style = String(value || '');
  if (/expression\s*\(|javascript:|vbscript:|behavior\s*:|@import|url\s*\(\s*['"]?\s*javascript/i.test(style)) {
    return '';
  }
  return style;
}

function unwrapDomNode(node) {
  const parent = node.parentNode;
  if (!parent) return;
  while (node.firstChild) {
    parent.insertBefore(node.firstChild, node);
  }
  parent.removeChild(node);
}

function sanitizeInlineHtmlFallback(html) {
  const template = document.createElement('template');
  template.innerHTML = String(html || '');
  const nodes = Array.from(template.content.querySelectorAll('*')).reverse();

  nodes.forEach(node => {
    const tag = node.tagName.toLowerCase();

    if (INLINE_HTML_BLOCKED_TAGS.has(tag)) {
      node.remove();
      return;
    }

    if (node.hasAttribute('data-editable') && !INLINE_HTML_ALLOWED_TAGS.has(tag)) {
      return;
    }

    if (!INLINE_HTML_ALLOWED_TAGS.has(tag)) {
      unwrapDomNode(node);
      return;
    }

    Array.from(node.attributes).forEach(attr => {
      const name = attr.name.toLowerCase();
      const value = attr.value;

      if (!INLINE_HTML_ALLOWED_ATTRS.has(name)) {
        node.removeAttribute(attr.name);
        return;
      }

      if (name.startsWith('on')) {
        node.removeAttribute(attr.name);
        return;
      }

      if (name === 'href' && isUnsafeUrl(value)) {
        node.removeAttribute(attr.name);
        return;
      }

      if (name === 'target') {
        node.setAttribute('target', '_blank');
        node.setAttribute('rel', 'noopener noreferrer');
        return;
      }

      if (name === 'style') {
        const cleanStyle = sanitizeStyleAttr(value);
        if (cleanStyle) {
          node.setAttribute('style', cleanStyle);
        } else {
          node.removeAttribute('style');
        }
        return;
      }

      if (name === 'class') {
        node.setAttribute('class', String(value || '').trim());
      }
    });
  });

  return template.innerHTML;
}

export function registerUtils(ctx) {

  /**
   * Translate a phrase using the same i18n as the admin (Now.js).
   * @param {string} key
   * @param {Record<string, string|number>=} params
   * @returns {string}
   */
  ctx.translate = function(key, params) {
    if (typeof key !== 'string') return '';
    if (typeof window.Now?.translate === 'function') {
      return window.Now.translate(key, params || {});
    }
    if (typeof window.translate === 'function') {
      return window.translate(key, params || {});
    }
    if (params && typeof params === 'object') {
      return key.replace(/\{(\w+)\}/g, (_, k) => (params[k] != null ? String(params[k]) : `{${k}}`));
    }
    return key;
  };

  /**
   * Apply I18nManager to a DOM subtree ([data-i18n], {LNG_} in attributes/text).
   * @param {Element|null|undefined} root
   */
  ctx.localizeDom = function(root) {
    if (!root) return;
    const i18n = window.Now?.getManager?.('i18n') || window.I18nManager;
    if (i18n && typeof i18n.translateNode === 'function') {
      i18n.translateNode(root);
    }
  };

  /**
   * เตรียมฟิลด์ในแผง designer ให้ FormManager ไม่เตือน (ต้องมี name หรือ id)
   * — โค้ด inspector หลายจุดใช้ input ไม่มี name
   */
  ctx.prepareDesignerFormFieldsForFormManager = function(root, suffix = 'panel') {
    if (!root || !root.querySelectorAll) return;
    const safe = String(suffix).replace(/[^a-zA-Z0-9_-]/g, '_');
    let n = 0;
    root.querySelectorAll('input, select, textarea').forEach(el => {
      if (el.hasAttribute('data-form-exclude')) return;
      if (el.name || el.id) return;
      el.id = `gcms-dsgn-${safe}-${++n}`;
    });
  };

  /** ถอน FormManager instance ของแผง designer (ก่อนล้าง innerHTML / ลบแผง) */
  ctx.teardownDesignerPanelFormManager = function(form) {
    if (!form || !window.FormManager || typeof window.FormManager.destroyFormByElement !== 'function') return;
    try {
      window.FormManager.destroyFormByElement(form);
    } catch (e) {
      /* ignore */
    }
  };

  ctx.webUrl = function() {
    return typeof WEB_URL !== 'undefined' ? WEB_URL : '/';
  };

  /**
   * Show a notification.
   * Prefers window.NotificationManager (now.core.min.js); falls back to inline div.
   */
  ctx.notify = function(message, type = 'success') {
    if (window.NotificationManager?.state?.initialized) {
      window.NotificationManager.show(message, type);
      return;
    }
    if (window.NotificationManager?.init) {
      window.NotificationManager.init().then(() =>
        window.NotificationManager.show(message, type)
      );
      return;
    }
    const note = document.createElement('div');
    const bg = type === 'error' ? '#dc2626' : type === 'warning' ? '#d97706' : '#16a34a';
    note.style.cssText = `position:fixed;right:1rem;top:4rem;z-index:10300;background:${bg};color:#fff;padding:.65rem 1rem;border-radius:.6rem;box-shadow:0 8px 24px rgba(0,0,0,.18)`;
    note.textContent = message;
    document.body.appendChild(note);
    setTimeout(() => note.remove(), 2200);
  };

  ctx.escapeAttr = function(value) {
    return String(value)
      .replace(/&/g, '&amp;')
      .replace(/"/g, '&quot;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;');
  };

  ctx.escapeHtml = function(value) {
    if (typeof Utils !== 'undefined' && Utils.string?.escape) {
      return Utils.string.escape(value);
    }
    return String(value == null ? '' : value)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#039;');
  };

  ctx.formatDate = function(value) {
    if (!value) return '-';
    const date = new Date(value);
    if (Number.isNaN(date.getTime())) return value;
    return date.toLocaleString();
  };

  ctx.trimText = function(value, length) {
    const text = String(value || '').replace(/\s+/g, ' ').trim();
    return text.length > length ? text.slice(0, length - 1) + '…' : text;
  };

  ctx.isTypingInField = function(target) {
    return !!target?.closest?.('input, textarea, select, [contenteditable="true"]');
  };

  ctx.selectorValue = function(value) {
    if (window.CSS && typeof window.CSS.escape === 'function') {
      return window.CSS.escape(String(value));
    }
    return String(value).replace(/\\/g, '\\\\').replace(/"/g, '\\"');
  };

  ctx.createElementFromHtml = function(html) {
    const template = document.createElement('template');
    template.innerHTML = html.trim();
    return template.content.firstElementChild;
  };

  ctx.isSwappableParent = function(el) {
    return !!el?.matches?.(SWAPPABLE_PARENT_SELECTOR);
  };

  /** Remove counter runtime DOM and text — delegates to CounterComponent when available. */
  ctx.sanitizeCounterElement = function(el) {
    if (!el?.matches?.('[data-component="counter"]')) return;
    if (window.CounterComponent?.clearElement) {
      window.CounterComponent.clearElement(el);
      return;
    }
    el.classList.remove('counter-component');
    el.querySelectorAll('.counter-container, .counter-wrapper, .counter-value, .counter-prefix, .counter-suffix').forEach(node => node.remove());
    delete el.dataset.counterComponentId;
    el.textContent = '';
  };

  ctx.stripCounterElementsForStorage = function(root = document) {
    root.querySelectorAll('[data-component="counter"]').forEach(el => ctx.sanitizeCounterElement(el));
  };

  /** Static preview for counter editables — CounterComponent runs only in preview / live site. */
  ctx.syncCounterPreview = function(el) {
    if (!el?.matches?.('[data-component="counter"]')) return;
    if (ctx.state.preview) return;

    ctx.sanitizeCounterElement(el);

    const end = parseFloat(el.dataset.end);
    if (Number.isNaN(end)) {
      return;
    }

    const options = {
      format: el.dataset.format || 'number',
      decimalPlaces: parseInt(el.dataset.decimalPlaces, 10) || 0,
      separator: el.dataset.separator ?? ',',
      decimal: el.dataset.decimal ?? '.',
      currencySymbol: el.dataset.currencySymbol || '฿'
    };

    let formatted;
    if (window.CounterComponent?.formatValue) {
      formatted = window.CounterComponent.formatValue(end, options);
    } else {
      formatted = String(end);
    }

    const prefix = el.dataset.prefix || '';
    const suffix = el.dataset.suffix || '';
    el.textContent = prefix + formatted + suffix;
  };

  ctx.syncAllCounterPreviews = function(root = document) {
    root.querySelectorAll('[data-component="counter"]').forEach(ctx.syncCounterPreview);
  };

  /** Strip runtime CounterComponent DOM before save / leaving preview. */
  ctx.resetCounterElements = function(root = document) {
    ctx.syncAllCounterPreviews(root);
  };

  let _counterScriptPromise = null;

  ctx.ensureCounterComponent = function() {
    if (window.CounterComponent) return Promise.resolve(window.CounterComponent);
    if (_counterScriptPromise) return _counterScriptPromise;

    _counterScriptPromise = new Promise((resolve, reject) => {
      const webUrl = () => (typeof WEB_URL !== 'undefined' ? WEB_URL : '/');
      let v = '';
      const globalScript = document.querySelector('script[src*="js/global.js"]');
      if (globalScript) {
        try {
          v = new URL(globalScript.src, window.location.origin).searchParams.get('v') || '';
        } catch (e) {
          v = '';
        }
      }
      const query = v ? `?v=${encodeURIComponent(v)}` : '';
      const script = document.createElement('script');
      script.src = webUrl() + 'js/components/CounterComponent.js' + query;
      script.onload = () => resolve(window.CounterComponent);
      script.onerror = () => reject(new Error('CounterComponent failed to load'));
      document.head.appendChild(script);
    });

    return _counterScriptPromise;
  };

  ctx.initPreviewCounters = async function(root = document) {
    try {
      await ctx.ensureCounterComponent();
    } catch (e) {
      console.warn('CounterComponent unavailable in preview:', e);
      return;
    }

    const Counter = window.CounterComponent;
    if (!Counter) return;

    root.querySelectorAll('[data-component="counter"]').forEach(el => {
      const options = Counter.extractOptionsFromElement(el);
      options.scrollTrigger = options.scrollTrigger !== false;
      Counter.create(el, options);
    });
  };

  /** Upgrade legacy stats-bar `<strong>` values to CounterComponent spans. */
  ctx.upgradeStatCounterElements = function(root = document) {
    root.querySelectorAll('[data-block-type="stats-bar"] .gcms-stat-item').forEach(item => {
      if (item.querySelector('[data-component="counter"]')) return;

      const legacy = item.querySelector(':scope > strong[data-editable="text"], .gcms-stat-value > strong[data-editable="text"], :scope > strong:not([data-component="counter"])');
      if (!legacy || legacy.matches('[data-component="counter"]')) return;

      const text = legacy.textContent.trim();
      const match = text.match(/^([^\d]*?)([\d,]+(?:\.\d+)?)([^\d]*)$/);
      const prefix = match?.[1] || '';
      const num = (match?.[2] || text).replace(/,/g, '');
      const suffix = match?.[3] || '';

      const span = document.createElement('strong');
      span.dataset.component = 'counter';
      if (legacy.dataset.editorFieldName) span.dataset.editorFieldName = legacy.dataset.editorFieldName;
      span.dataset.end = String(parseFloat(num) || 0);
      span.dataset.duration = '2000';
      span.dataset.format = 'number';
      span.dataset.separator = ',';
      span.dataset.scrollTrigger = 'true';
      if (prefix) span.dataset.prefix = prefix;
      if (suffix) span.dataset.suffix = suffix;
      if (legacy.className) span.className = legacy.className;
      if (legacy.getAttribute('style')) span.setAttribute('style', legacy.getAttribute('style'));

      const wrapper = legacy.closest('.gcms-stat-value') || document.createElement('div');
      if (!wrapper.classList.contains('gcms-stat-value')) {
        wrapper.className = 'gcms-stat-value';
        wrapper.appendChild(span);
        legacy.replaceWith(wrapper);
      } else {
        legacy.replaceWith(span);
      }
      ctx.syncCounterPreview(span);
    });
  };

  ctx.isTopLevelCustomBlock = function(block) {
    if (!block || block.dataset.gcmsCustom !== '1') return false;
    const ancestorCustom = block.parentElement?.closest('[data-gcms-custom="1"]');
    return !ancestorCustom || ancestorCustom === block;
  };

  ctx.isInsideCustomBlock = function(el) {
    return !!el?.closest?.('[data-gcms-custom="1"]');
  };

  ctx.prepareRestoredBlock = function(block, options = {}) {
    if (!block) return;
    if (options.forStorage) {
      ctx.stripCounterElementsForStorage?.(block);
    } else {
      ctx.resetCounterElements?.(block);
    }
    ctx.cleanEditorUi(block);
    block.removeAttribute('draggable');
    delete block.dataset.gcmsDesignerReady;
    block.querySelectorAll('[data-gcms-editable-ready]').forEach(item => {
      delete item.dataset.gcmsEditableReady;
      item.removeAttribute('contenteditable');
    });
    block.querySelectorAll('[data-gcms-counter-wrap-ready]').forEach(item => {
      delete item.dataset.gcmsCounterWrapReady;
    });
    block.querySelectorAll('[data-component="counter"]').forEach(item => {
      item.removeAttribute('contenteditable');
      item.removeAttribute('data-editable');
    });
    ctx.resetWidgetMounts?.(block);
  };

  ctx.cleanEditorUi = function(root = document) {
    root.querySelectorAll('.gcms-block-tools, .gcms-drop-placeholder, .gcms-drop-zone, .gcms-repeat-remove, .gcms-repeat-add').forEach(el => el.remove());
    root.querySelectorAll('.gcms-block-selected, .gcms-editable-active').forEach(el => {
      el.classList.remove('gcms-block-selected', 'gcms-editable-active');
    });
  };

  /**
   * คลาส UI ชั่วคราวของ designer — ต้องไม่ถูก save/load ใน state.className (เช่น gcms-block-selected ติด hero)
   */
  ctx.stripTransientDesignerClasses = function(className) {
    if (!className || typeof className !== 'string') return '';
    const drop = new Set(['gcms-block-selected', 'gcms-block-flash', 'gcms-editable-active']);
    return className.split(/\s+/).filter(Boolean).filter(c => !drop.has(c)).join(' ');
  };

  ctx.rgbToHex = function(value) {
    const match = String(value).match(/rgba?\((\d+),\s*(\d+),\s*(\d+)/);
    if (!match) return '#ffffff';
    return '#' + [match[1], match[2], match[3]]
      .map(num => Number(num).toString(16).padStart(2, '0'))
      .join('');
  };

  ctx.cssColorToHexForInput = function(value) {
    const s = String(value || '').trim();
    if (!s) return null;
    if (/^#[0-9a-fA-F]{6}$/.test(s)) return s.toLowerCase();
    if (/^#[0-9a-fA-F]{3}$/.test(s)) {
      return '#' + s[1] + s[1] + s[2] + s[2] + s[3] + s[3];
    }
    return ctx.rgbToHex(s);
  };

  ctx.getInlineHtmlAllowedTags = function() {
    return [...INLINE_HTML_ALLOWED_TAGS].sort();
  };

  /** คำอธิบายสำหรับ hint ใต้ textarea Raw HTML */
  ctx.getInlineHtmlAllowedHint = function() {
    return ctx.translate(
      'Allowed HTML tags: {tags}. Attributes: class, style only. Scripts, iframes, images, links and other disallowed tags are removed on apply.',
      {tags: ctx.getInlineHtmlAllowedTags().join(', ')}
    );
  };

  ctx.getInlineHtmlPurifyConfig = function() {
    return {
      ALLOWED_TAGS: [...INLINE_HTML_ALLOWED_TAGS],
      ALLOWED_ATTR: [...INLINE_HTML_ALLOWED_ATTRS],
      ALLOW_DATA_ATTR: false,
      ALLOW_UNKNOWN_PROTOCOLS: false,
      FORBID_TAGS: [...INLINE_HTML_BLOCKED_TAGS],
      FORBID_ATTR: ['onerror', 'onload', 'onclick', 'onmouseover', 'onfocus', 'onblur']
    };
  };

  ctx.sanitizeInlineHtml = function(html) {
    const raw = String(html || '').trim();
    if (!raw) return '';

    if (window.DOMPurify && typeof window.DOMPurify.sanitize === 'function') {
      return window.DOMPurify.sanitize(raw, ctx.getInlineHtmlPurifyConfig());
    }

    return sanitizeInlineHtmlFallback(raw);
  };

  ctx.sanitizeEditableElement = function(el) {
    if (!el || el.matches('img')) return false;
    if (el.hasAttribute('data-drop-zone') && el.querySelector(':scope > [data-editable]')) {
      return false;
    }
    const clean = ctx.sanitizeInlineHtml(el.innerHTML);
    if (el.innerHTML === clean) return false;
    el.innerHTML = clean;
    return true;
  };

  ctx.isSafeEditableUrl = function(value) {
    const clean = String(value || '').trim();
    if (!clean) return true;
    if (isUnsafeUrl(clean)) return false;
    if (/^#/.test(clean)) return true;
    if (/^\/(?!\/)/.test(clean)) return true;
    if (/^[\w+.-]+:/.test(clean)) {
      return /^(https?|mailto|tel|sms|ftp):/i.test(clean);
    }
    return true;
  };

  ctx.clearInlineFormatting = function(el) {
    const text = el.textContent;
    el.textContent = text;
    el.removeAttribute('style');
  };

  ctx.sanitizeClassName = function(value) {
    return String(value || '').trim().split(/\s+/)[0]?.replace(/[^a-zA-Z0-9_-]/g, '') || '';
  };

  ctx.sanitizeHtmlId = function(value) {
    return String(value || '').trim().replace(/[^a-zA-Z0-9_-]/g, '');
  };

  ctx.flashBlock = function(block) {
    block.classList.add('gcms-block-flash');
    block.scrollIntoView({behavior: 'smooth', block: 'center'});
    setTimeout(() => block.classList.remove('gcms-block-flash'), 1200);
  };

  ctx.replaceTextCaseInsensitive = function(text, findText, replacement, once) {
    const escaped = findText.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
    return text.replace(new RegExp(escaped, once ? 'i' : 'gi'), replacement);
  };

  ctx.replaceTextInElement = function(el, findText, replacement, once) {
    const keyword = String(findText || '');
    if (!keyword) return 0;
    if (ctx.isEditableLocked(el)) return 0;
    let count = 0;
    const walker = document.createTreeWalker(el, NodeFilter.SHOW_TEXT);
    const nodes = [];
    while (walker.nextNode()) nodes.push(walker.currentNode);
    for (const node of nodes) {
      const before = node.nodeValue;
      if (!before || !before.toLowerCase().includes(keyword.toLowerCase())) continue;
      node.nodeValue = ctx.replaceTextCaseInsensitive(before, keyword, replacement, once);
      count++;
      if (once) break;
    }
    return count;
  };

  ctx.replaceAllText = function(findText, replacement) {
    const keyword = String(findText || '').trim();
    if (!keyword) return 0;
    let count = 0;
    ctx.editableElements().forEach(el => {
      if (el.matches('img')) return;
      if (ctx.isEditableLocked(el)) return;
      count += ctx.replaceTextInElement(el, keyword, replacement, false);
    });
    return count;
  };

  ctx.getTheme = function() {
    const links = Array.from(document.querySelectorAll('link[href*="/themes/"],link[href*="themes/"]'));
    const themeLink = links.find(link => /themes\/[^/]+\/css\/styles\.css/.test(link.href));
    const match = themeLink ? themeLink.href.match(/themes\/([^/]+)\/css\/styles\.css/) : null;
    return match ? match[1] : 'wk';
  };

  ctx.isHomePage = function() {
    return !!document.querySelector('.homepage[data-gcms-page="home"], .homepage');
  };

  ctx.homepageDesignerRoot = function() {
    return document.querySelector('.homepage[data-gcms-page="home"]') || document.querySelector('.homepage');
  };

  ctx.globalScriptCacheParams = function() {
    const el = document.querySelector('script[src*="js/global.js"]');
    if (!el) return {query: '', idSuffix: ''};
    try {
      const v = new URL(el.src, window.location.origin).searchParams.get('v') || '';
      if (!v) return {query: '', idSuffix: ''};
      const safe = v.replace(/\W/g, '_').replace(/_+/g, '_').replace(/^_|_$/g, '');
      return {
        query: `?v=${encodeURIComponent(v)}`,
        idSuffix: safe ? `_${safe}` : ''
      };
    } catch (e) {
      return {query: '', idSuffix: ''};
    }
  };

  ctx.draftKey = function() {
    return `gcms_home_designer_draft_${ctx.getTheme()}_home`;
  };

  ctx.skipDraftRestoreKey = function() {
    return `gcms_home_designer_skip_draft_${ctx.getTheme()}_home`;
  };

  ctx.resumeEditKey = function() {
    return `gcms_home_designer_resume_edit_${ctx.getTheme()}_home`;
  };

  ctx.waitForDesignerFab = function(maxMs = 4000) {
    return new Promise(resolve => {
      const existing = document.getElementById('gcms-admin-fab');
      if (existing) {
        resolve(existing);
        return;
      }

      const deadline = Date.now() + maxMs;
      const observer = new MutationObserver(() => {
        const fab = document.getElementById('gcms-admin-fab');
        if (fab) {
          observer.disconnect();
          resolve(fab);
        } else if (Date.now() >= deadline) {
          observer.disconnect();
          resolve(null);
        }
      });
      observer.observe(document.body, {childList: true});

      setTimeout(() => {
        observer.disconnect();
        resolve(document.getElementById('gcms-admin-fab'));
      }, maxMs);
    });
  };

  ctx.userTemplateKey = function() {
    return `gcms_home_designer_templates_${ctx.getTheme()}_home`;
  };

  ctx.containerList = function() {
    return Array.from(document.querySelectorAll('[data-editor-container]'));
  };

  ctx.blocks = function() {
    return Array.from(document.querySelectorAll('[data-block-type]'))
      .filter(block => !block.closest('[data-widget-mount]'))
      .filter(block => !block.closest('#gcms-designer-panel, #designer-bar'));
  };

  ctx.editableElements = function(root = document) {
    return Array.from(root.querySelectorAll('[data-editable]'))
      .filter(el => !el.closest('[data-widget-mount]'))
      .filter(el => !el.closest('#gcms-designer-panel, #designer-bar'));
  };

  ctx.isWidgetFrame = function(block) {
    return !!(block?.dataset?.widget && block.querySelector?.('[data-widget-mount]'));
  };

  ctx.widgetTitle = function(widgetId) {
    const manifest = ctx.state.widgetManifests.find(item => item.id === widgetId);
    return manifest?.title || widgetId || ctx.translate('Widget');
  };

  ctx.widgetSettingsUrl = function(widgetId) {
    const manifest = ctx.state.widgetManifests.find(item => item.id === widgetId);
    return manifest?.settingsUrl || `${ctx.webUrl()}admin/widgets/${encodeURIComponent(widgetId || '')}`;
  };

  ctx.isGcmsCustomBlock = function(block) {
    return block?.dataset?.gcmsCustom === '1';
  };

  ctx.ensureBlockIds = function() {
    const counters = {};
    ctx.blocks().forEach(block => {
      const type = block.dataset.blockType || 'block';
      counters[type] = (counters[type] || 0) + 1;
      if (!block.dataset.editorId) {
        block.dataset.editorId = `${type}-${counters[type]}`;
      }
    });
  };

  ctx.ensureEditableNames = function(root = document) {
    ctx.editableElements(root).forEach((el, index) => {
      if (!el.dataset.editorFieldName) {
        const block = el.closest('[data-block-type]');
        const blockId = block?.dataset.editorId || 'home';
        el.dataset.editorFieldName = `${blockId}-editable-${index + 1}`;
      }
    });
  };

  ctx.assignEditableNames = function(root) {
    const blockId = root.dataset.editorId || `custom-${Date.now()}`;
    ctx.editableElements(root).forEach((el, index) => {
      if (!el.dataset.editorFieldName || el.dataset.editorFieldName.startsWith('custom_')) {
        el.dataset.editorFieldName = `${blockId}-${el.dataset.editorFieldName || `field-${index + 1}`}`;
      }
    });
    ctx.counterElements?.(root).forEach((el, index) => {
      if (!el.dataset.editorFieldName || el.dataset.editorFieldName.startsWith('custom_')) {
        el.dataset.editorFieldName = `${blockId}-${el.dataset.editorFieldName || `stat-${index + 1}`}`;
      }
    });
  };

  ctx.markChanged = function() {
    if (ctx.state.restoring) return;
    ctx.state.dirty = true;
    document.body.dataset.gcmsDesignerChanged = '1';
    ctx.updateStatus();
    ctx.scheduleHistory();
    ctx.scheduleDraftSave();
  };

  ctx.updateStatus = function() {
    const status = ctx.state.bar?.querySelector('[data-status]');
    if (!status) return;
    const statusText = ctx.state.saving
      ? ctx.translate('Saving...')
      : ctx.state.dirty
        ? ctx.translate('Unsaved changes')
        : ctx.state.lastSavedAt
          ? ctx.translate('Saved {date}', {date: ctx.formatDate(ctx.state.lastSavedAt)})
          : ctx.translate('Saved');
    status.textContent = statusText;
    status.classList.toggle('dirty', ctx.state.dirty);
    status.classList.toggle('saving', ctx.state.saving);
  };

  ctx.setSaving = function(value) {
    ctx.state.saving = !!value;
    ctx.state.bar?.querySelectorAll('[data-action="save"], [data-action="draft"], [data-action="discard"]').forEach(button => {
      button.disabled = ctx.state.saving;
    });
    ctx.updateStatus();
  };

  ctx.editableClassList = function(block) {
    return Array.from(block.classList).filter(className => {
      return !className.startsWith('gcms-') &&
        !className.startsWith('editor-') &&
        !['gcms-block-selected'].includes(className);
    });
  };

  ctx.isBlockLocked = function(block) {
    return block?.dataset?.gcmsLocked === '1';
  };

  ctx.editableHostBlock = function(el) {
    return el?.closest?.('[data-block-type]') || null;
  };

  ctx.isEditableLocked = function(el) {
    const block = ctx.editableHostBlock(el);
    return !!(block && ctx.isBlockLocked(block));
  };

  ctx.setBlockLocked = function(block, locked) {
    block.dataset.gcmsLocked = locked ? '1' : '0';
  };

  ctx.extractBlockAttrs = function(block) {
    return {
      id: block.getAttribute('id') || '',
      'aria-label': block.getAttribute('aria-label') || '',
      role: block.getAttribute('role') || ''
    };
  };

  ctx.applyBlockAttrs = function(block, attrs) {
    ['id', 'aria-label', 'role'].forEach(name => {
      if (Object.prototype.hasOwnProperty.call(attrs, name)) {
        ctx.setBlockAttr(block, name, attrs[name]);
      }
    });
  };

  ctx.setBlockAttr = function(block, name, value) {
    if (!['id', 'aria-label', 'role'].includes(name)) return;
    const clean = name === 'id' ? ctx.sanitizeHtmlId(value) : String(value || '').trim();
    if (clean === '') {
      block.removeAttribute(name);
    } else {
      block.setAttribute(name, clean);
    }
  };

  ctx.extractEditableAttrs = function(el) {
    const attrs = {};
    if (el.matches('a')) {
      attrs.href = el.getAttribute('href') || '';
      attrs.target = el.getAttribute('target') || '';
      attrs.rel = el.getAttribute('rel') || '';
    }
    return attrs;
  };

  ctx.extractCounterEditableAttrs = function(el) {
    const attrs = {};
    [...el.attributes].forEach(({name, value}) => {
      if (name.startsWith('data-')) attrs[name] = value;
    });
    return attrs;
  };

  ctx.setEditableAttr = function(el, name, value) {
    if (!['href', 'target', 'rel'].includes(name)) return;
    const clean = String(value || '').trim();
    if (clean === '') {
      el.removeAttribute(name);
    } else if (name === 'href' && !ctx.isSafeEditableUrl(clean)) {
      el.removeAttribute(name);
    } else {
      el.setAttribute(name, clean);
    }
    if (name === 'target' && clean === '_blank' && !el.getAttribute('rel')) {
      el.setAttribute('rel', 'noopener noreferrer');
    }
  };

  ctx.getLinkStyleValue = function(el) {
    const cls = el.classList;
    if (!cls.contains('btn')) return '';
    const color = LINK_BTN_COLORS.find(name => cls.contains(`btn-${name}`));
    if (!color) return '';
    return `btn btn-${color}`;
  };

  ctx.applyLinkStyle = function(el, value) {
    LINK_STYLE_RESET_CLASSES.forEach(className => el.classList.remove(className));
    if (!value) {
      el.classList.remove('outline');
      return;
    }
    value.split(/\s+/).filter(Boolean).forEach(className => el.classList.add(className));
  };

  ctx.getBtnOutlineValue = function(el) {
    return el.classList.contains('outline');
  };

  ctx.applyBtnOutline = function(el, enabled) {
    if (!enabled) {
      el.classList.remove('outline');
      return;
    }
    if (!el.classList.contains('btn')) return;
    el.classList.add('outline');
  };

  ctx.getBtnSizeValue = function(el) {
    return BTN_SIZE_CLASSES.find(size => el.classList.contains(size)) || '';
  };

  ctx.applyBtnSize = function(el, value) {
    BTN_SIZE_CLASSES.forEach(size => el.classList.remove(size));
    if (value) el.classList.add(value);
  };

  ctx.getBtnStyleValue = function(el) {
    return BTN_SHAPE_CLASSES.find(shape => el.classList.contains(shape)) || '';
  };

  ctx.applyBtnStyle = function(el, value) {
    BTN_SHAPE_CLASSES.forEach(shape => el.classList.remove(shape));
    if (value) el.classList.add(value);
  };

  ctx.getBtnWidthValue = function(el) {
    return el.classList.contains('fullwidth') ? 'fullwidth' : '';
  };

  ctx.applyBtnWidth = function(el, value) {
    el.classList.remove('fullwidth');
    if (value) el.classList.add(value);
  };

  ctx.focusEditable = function(el) {
    const block = el.closest('[data-block-type]');
    if (block) ctx.selectBlock(block);
    el.scrollIntoView({behavior: 'smooth', block: 'center'});
    el.classList.add('gcms-editable-active', 'gcms-block-flash');
    setTimeout(() => el.classList.remove('gcms-block-flash'), 1200);
  };

  ctx.setImageAlignment = function(img, align) {
    img.style.display = align === 'left' ? '' : 'block';
    img.style.marginLeft = align === 'center' || align === 'right' ? 'auto' : '';
    img.style.marginRight = align === 'center' ? 'auto' : '';
  };
}

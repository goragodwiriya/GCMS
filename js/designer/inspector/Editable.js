/**
 * GCMS Designer – Inspector: Editable Element
 *
 * Text / link element styling inspector (Panel เดียว — ไม่มี floating toolbar)
 *
 * @filesource js/designer/inspector/Editable.js
 */

export function registerEditableInspector(ctx) {

  ctx.showEditableInspector = function(el) {
    if (ctx.isEditableLocked(el)) {
      const wrap = document.createElement('div');
      wrap.innerHTML = '<p>' + ctx.escapeHtml(ctx.translate('This element is in a locked block — unlock the block toolbar first.')) + '</p>';
      ctx.showPanel('Text Element', wrap);
      return;
    }

    const isLink = el.matches('a');
    const href = isLink ? el.getAttribute('href') || '' : '';
    const target = isLink ? el.getAttribute('target') || '' : '';
    const rel = isLink ? el.getAttribute('rel') || '' : '';

    const wrap = document.createElement('div');
    wrap.innerHTML = `
      <div class="gcms-panel-section">
        <div>
          <label data-i18n>Font family</label>
          <span class="form-control icon-fontfamily">
            <select data-prop="fontFamily"> ${ctx.state.fonts.map(f => `<option value="${ctx.escapeAttr(f.value)}" data-i18n>${ctx.escapeHtml(f.label)}</option>`).join('')}
            </select>
          </span>
        </div>
        <div>
          <label data-i18n>Font size</label>
          <span class="form-control icon-font-size">
            <select data-prop="fontSize">
              <option value="" data-i18n>Default</option>
              <option value=".875rem" data-i18n>Small</option>
              <option value="1rem" data-i18n>Normal</option>
              <option value="1.25rem" data-i18n>Large</option>
              <option value="1.5rem" data-i18n>XL</option>
              <option value="2rem" data-i18n>2XL</option>
            </select>
          </span>
        </div>
        <div>
          <label data-i18n>Line height</label>
          <span class="form-control icon-height">
            <select data-prop="lineHeight">
              <option value="" data-i18n>Default</option>
              <option value="1.25">1.25</option>
              <option value="1.5">1.5</option>
              <option value="1.75">1.75</option>
              <option value="2">2</option>
            </select>
          </span>
        </div>
        <div>
          <label data-i18n>Text Color</label>
          <span class="form-control icon-color">
            <input type="color" data-prop="color"></span>
          </span>
        </div>
        <div>
          <label data-i18n>Text align</label>
          <span class="form-control icon-align-left">
            <select data-prop="textAlign">
              <option value="" data-i18n>Default</option>
              <option value="left" data-i18n>Left</option>
              <option value="center" data-i18n>Center</option>
              <option value="right" data-i18n>Right</option>
              <option value="justify" data-i18n>Justify</option>
            </select>
          </span>
        </div>
        <div>
          <label data-i18n>Font weight</label>
          <span class="form-control icon-bold">
            <select data-prop="fontWeight">
              <option value="" data-i18n>Default</option>
              <option value="400" data-i18n>Normal</option>
              <option value="500" data-i18n>Medium</option>
              <option value="600" data-i18n>Semibold</option>
              <option value="700" data-i18n>Bold</option>
            </select>
          </span>
        </div>
        <div>
          <label data-i18n>Letter spacing</label>
          <span class="form-control icon-cols">
            <select data-prop="letterSpacing">
              <option value="" data-i18n>Default</option>
              <option value="-0.02em" data-i18n>Tight</option>
              <option value="0.02em" data-i18n>Slight</option>
              <option value="0.05em" data-i18n>Wide</option>
              <option value="0.1em" data-i18n>Wider</option>
              <option value="0.2em" data-i18n>Widest</option>
            </select>
          </span>
        </div>

        ${isLink ? `
        <div>
          <label data-i18n>Link URL</label>
          <span class="form-control icon-link">
            <input type="url" data-attr="href" value="${ctx.escapeAttr(href)}">
          </span>
        </div>
        <div>
          <label data-i18n>Open in</label>
          <span class="form-control icon-forward">
            <select data-attr="target">
              <option value="" data-i18n>Same tab</option>
              <option value="_blank" data-i18n>New tab</option>
            </select>
          </span>
        </div>
        <div>
          <label data-i18n>Rel</label>
          <span class="form-control icon-link">
            <input type="text" data-attr="rel" value="${ctx.escapeAttr(rel)}" placeholder="noopener noreferrer">
          </span>
        </div>
        <div>
          <label data-i18n>Button color</label>
          <span class="form-control icon-color">
            <select data-link-style>
              <option value="" data-i18n>Plain link</option>
              <option value="btn btn-primary" data-i18n>Primary</option>
              <option value="btn btn-secondary" data-i18n>Secondary</option>
              <option value="btn btn-success" data-i18n>Success</option>
              <option value="btn btn-warning" data-i18n>Warning</option>
              <option value="btn btn-danger" data-i18n>Danger</option>
              <option value="btn btn-info" data-i18n>Info</option>
              <option value="btn btn-orange" data-i18n>Orange</option>
              <option value="btn btn-green" data-i18n>Green</option>
              <option value="btn btn-brown" data-i18n>Brown</option>
              <option value="btn btn-cyan" data-i18n>Cyan</option>
              <option value="btn btn-gold" data-i18n>Gold</option>
              <option value="btn btn-magenta" data-i18n>Magenta</option>
              <option value="btn btn-pink" data-i18n>Pink</option>
              <option value="btn btn-purple" data-i18n>Purple</option>
              <option value="btn btn-rosy" data-i18n>Rosy</option>
              <option value="btn btn-red" data-i18n>Red</option>
            </select>
          </span>
        </div>
        <div>
          <label data-i18n>Button shape</label>
          <span class="form-control icon-expand">
            <select data-btn-style>
              <option value="" data-i18n>Default</option>
              <option value="rounded" data-i18n>Rounded</option>
              <option value="pill" data-i18n>Pill</option>
              <option value="circle" data-i18n>Circle</option>
            </select>
          </span>
        </div>
        <div>
          <input type="checkbox" class="switch" id="btn-outline" data-btn-outline>
          <label for="btn-outline" data-i18n>Outline</label>
        </div>
        <div>
          <label data-i18n>Button size</label>
          <span class="form-control icon-expand">
            <select data-btn-size>
              <option value="" data-i18n>Default</option>
              <option value="small" data-i18n>Small</option>
              <option value="large" data-i18n>Large</option>
            </select>
          </span>
        </div>
        <div>
          <label data-i18n>Button width</label>
          <span class="form-control icon-cols">
            <select data-btn-width>
              <option value="" data-i18n>Auto</option>
              <option value="fullwidth" data-i18n>Full width</option>
            </select>
          </span>
        </div>` : ''}
        <div>
          <label data-i18n>Raw HTML</label>
          <span class="form-control icon-code">
            <textarea rows="5" data-editable-html>${ctx.escapeHtml(el.innerHTML)}</textarea>
          </span>
          <!-- แท็กที่ใส่ได้ sync กับ INLINE_HTML_ALLOWED_TAGS ใน js/designer/utils.js -->
          <p class="gcms-hint-text">${ctx.escapeHtml(ctx.getInlineHtmlAllowedHint())}</p>
        </div>
        <div class="form-group">
          <button type="button" class="btn btn-primary icon-save" data-content-action="apply-html" data-i18n>Apply HTML</button>
          <button type="button" class="btn icon-reset" data-content-action="clear-format" data-i18n>Clear formatting</button>
        </div>
      </div>`;

    wrap.querySelectorAll('[data-attr]').forEach(input => {
      input.addEventListener('input', () => {ctx.setEditableAttr(el, input.dataset.attr, input.value); ctx.markChanged();});
    });

    wrap.querySelectorAll('[data-link-style]').forEach(input => {
      input.addEventListener('input', () => {ctx.applyLinkStyle(el, input.value); ctx.markChanged();});
    });

    wrap.querySelectorAll('[data-btn-size]').forEach(input => {
      input.addEventListener('input', () => {ctx.applyBtnSize(el, input.value); ctx.markChanged();});
    });

    wrap.querySelectorAll('[data-btn-style]').forEach(input => {
      input.addEventListener('input', () => {ctx.applyBtnStyle(el, input.value); ctx.markChanged();});
    });

    wrap.querySelectorAll('[data-btn-width]').forEach(input => {
      input.addEventListener('input', () => {ctx.applyBtnWidth(el, input.value); ctx.markChanged();});
    });

    wrap.querySelectorAll('[data-btn-outline]').forEach(input => {
      input.addEventListener('change', () => {ctx.applyBtnOutline(el, input.checked); ctx.markChanged();});
    });

    wrap.querySelector('[data-content-action="apply-html"]').addEventListener('click', () => {
      const textarea = wrap.querySelector('[data-editable-html]');
      el.innerHTML = ctx.sanitizeInlineHtml(textarea.value);
      textarea.value = el.innerHTML;
      ctx.markChanged();
    });

    wrap.querySelector('[data-content-action="clear-format"]').addEventListener('click', () => {
      ctx.clearInlineFormatting(el);
      const textarea = wrap.querySelector('[data-editable-html]');
      if (textarea) textarea.value = el.innerHTML;
      ctx.markChanged();
    });

    // Pre-set color value attribute before showPanel so EmbeddedColorPicker reads it during
    // FormManager.initForm and takes the setColor() path instead of scheduling a setTimeout(0)
    // clear — which would fire after onFormReady and wipe the color the inspector just set.
    const preColorIn = wrap.querySelector('[data-prop="color"]');
    if (preColorIn) {
      const preColor = _resolveEditableStyleValue(el, 'color');
      if (preColor) {
        const preHex = _colorToHex(preColor);
        if (preHex) preColorIn.setAttribute('value', preHex);
      }
    }

    ctx.showPanel('Text Element', wrap, {
      onFormReady: () => {
        _wireInitialValues(wrap, el);
        if (isLink) {
          wrap.querySelector('[data-attr="target"]').value = target;
          wrap.querySelector('[data-link-style]').value = ctx.getLinkStyleValue(el);
          wrap.querySelector('[data-btn-style]').value = ctx.getBtnStyleValue(el);
          wrap.querySelector('[data-btn-size]').value = ctx.getBtnSizeValue(el);
          wrap.querySelector('[data-btn-width]').value = ctx.getBtnWidthValue(el);
          const outlineInput = wrap.querySelector('[data-btn-outline]');
          if (outlineInput) outlineInput.checked = ctx.getBtnOutlineValue(el);
        }
        _wireDataPropListeners(wrap, el);
      }
    });
  };

  /** อ่านค่า style จาก element → block ที่ครอบ → computed (สำหรับแสดงใน inspector) */
  function _resolveEditableStyleValue(el, prop) {
    const inline = (el.style[prop] || '').trim();
    if (inline) return inline;
    const block = el.closest('[data-block-type], [data-editor-id]');
    if (block) {
      const fromBlock = (block.style[prop] || '').trim();
      if (fromBlock) return fromBlock;
    }
    const computed = getComputedStyle(el)[prop];
    return computed && computed !== 'inherit' && computed !== 'initial' ? String(computed).trim() : '';
  }

  function _matchSelectOption(input, value) {
    if (!value || !input?.options) return '';
    const exact = [...input.options].find(o => o.value === value);
    if (exact) return value;
    if (input.dataset.prop === 'fontFamily') {
      const norm = value.replace(/['"]/g, '').toLowerCase();
      const partial = [...input.options].find(o => {
        const ov = (o.value || '').replace(/['"]/g, '').toLowerCase();
        return ov && norm.includes(ov.split(',')[0].trim());
      });
      if (partial) return partial.value;
    }
    return '';
  }

  function _wireInitialValues(wrap, el) {
    const fontFamilySel = wrap.querySelector('[data-prop="fontFamily"]');
    if (fontFamilySel) {
      fontFamilySel.value = _matchSelectOption(fontFamilySel, _resolveEditableStyleValue(el, 'fontFamily'));
    }

    ['fontSize', 'lineHeight', 'letterSpacing', 'textAlign', 'fontWeight'].forEach(prop => {
      const input = wrap.querySelector(`[data-prop="${prop}"]`);
      if (!input?.options) return;
      input.value = _matchSelectOption(input, _resolveEditableStyleValue(el, prop));
    });

    const textColorIn = wrap.querySelector('[data-prop="color"]');
    if (textColorIn) {
      const c = _resolveEditableStyleValue(el, 'color');
      if (c) {
        const hex = _colorToHex(c);
        if (hex) _syncColorInput(textColorIn, hex);
      }
    }
  }

  function _colorToHex(value) {
    const s = String(value || '').trim();
    if (!s) return null;
    if (/^#[0-9a-fA-F]{3,8}$/.test(s)) {
      return ctx.cssColorToHexForInput(s);
    }
    if (/^rgba?\(\s*\d+/i.test(s)) {
      return ctx.cssColorToHexForInput(s);
    }
    if (typeof document !== 'undefined' && document.body) {
      const probe = document.createElement('span');
      probe.style.color = s;
      if (!probe.style.color) return null;
      probe.style.position = 'absolute';
      probe.style.visibility = 'hidden';
      document.body.appendChild(probe);
      const computed = window.getComputedStyle(probe).color;
      probe.remove();
      return computed ? ctx.cssColorToHexForInput(computed) : null;
    }
    return null;
  }

  function _syncColorInput(input, hex) {
    input.setAttribute('value', hex);
    input.value = hex;
    const inst = window.ElementManager?.getInstanceByElement?.(input);
    if (inst?.syncValue) {
      inst.syncValue(hex);
    } else if (inst?.setColor) {
      inst.setColor(hex, {dispatchChange: false});
    } else if (inst?.colorPicker?.syncFromElementValue) {
      inst.colorPicker.syncFromElementValue(hex);
    }
  }

  /** ผูก [data-prop] → el.style (แบบเดียวกับ Block inspector) */
  function _wireDataPropListeners(wrap, el) {
    if (wrap.dataset.gcmsEditablePropWired === '1') return;
    wrap.dataset.gcmsEditablePropWired = '1';

    const colorProps = new Set(['color']);

    const applyFromInput = input => {
      if (!input?.matches?.('[data-prop]')) return;
      if (ctx.isEditableLocked(el)) return;
      const prop = input.dataset.prop;
      const rawNext = (input.value || '').trim();
      const rawCur = (el.style[prop] || '').trim();
      if (!colorProps.has(prop) && rawNext === rawCur) return;

      if (prop === 'fontFamily') {
        ctx.ensureGoogleFont(input.value);
      }
      el.style[prop] = rawNext;
      ctx.markChanged();
    };

    const onFormControl = e => {
      const t = e.target;
      if (t && typeof t.matches === 'function' && t.matches('[data-prop]')) {
        applyFromInput(t);
      }
    };

    wrap.addEventListener('input', onFormControl, true);
    wrap.addEventListener('change', onFormControl, true);
  }
}

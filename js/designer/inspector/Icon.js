/**
 * GCMS Designer – Inspector: Icon Element
 *
 * Clicking a [data-editable="icon"] element opens this panel.
 * - Icon picker  (swap the icon-* class)
 * - Icon color   (el.style.color — inherited by ::before)
 * - Background   (el.style.backgroundColor + shape preset via padding / border-radius)
 * - Size         (el.style.fontSize — inherited by ::before)
 *
 * Save/restore: className + style are both persisted by the designer's
 * extractState / applyState cycle, so all changes survive page reload.
 *
 * @filesource js/designer/inspector/Icon.js
 */

export function registerIconInspector(ctx) {

  ctx.showIconInspector = function(el) {
    if (ctx.isEditableLocked(el)) {
      const wrap = document.createElement('div');
      wrap.innerHTML = '<p>' + ctx.escapeHtml(ctx.translate('This element is in a locked block — unlock the block toolbar first.')) + '</p>';
      ctx.showPanel('Icon', wrap);
      return;
    }

    ctx.state.selectedEditable = el;
    const currentIcon = _getIconClass(el);

    const wrap = document.createElement('div');
    wrap.innerHTML = `
      <div class="gcms-panel-section">
        <h4 data-i18n>Style</h4>
        <div>
          <label data-i18n>Size</label>
          <span class="form-control icon-font-size">
            <select data-icon-prop="fontSize">
              <option value="" data-i18n>Default</option>
              <option value="1rem">1rem</option>
              <option value="1.25rem">1.25rem</option>
              <option value="1.5rem">1.5rem</option>
              <option value="2rem">2rem</option>
              <option value="2.5rem">2.5rem</option>
              <option value="3rem">3rem</option>
              <option value="4rem">4rem</option>
            </select>
          </span>
        </div>
        <div>
          <label data-i18n>Icon color</label>
          <span class="form-control icon-color">
            <input type="color" data-icon-prop="color" placeholder="${ctx.escapeAttr(ctx.translate('Icon color'))}">
          </span>
        </div>
        <div>
          <label data-i18n>Background</label>
          <span class="form-control icon-color">
            <input type="color" data-icon-prop="backgroundColor" placeholder="${ctx.escapeAttr(ctx.translate('Background'))}">
          </span>
        </div>
        <div>
          <label data-i18n>Shape</label>
          <span class="form-control icon-expand">
            <select data-icon-shape>
              <option value="" data-i18n>{LNG_None} ({LNG_icon only})</option>
              <option value="square" data-i18n>Square</option>
              <option value="rounded" data-i18n>Rounded</option>
              <option value="circle" data-i18n>Circle</option>
            </select>
          </span>
        </div>
      </div>
      <div class="gcms-panel-section">
        <h4 data-i18n>Select Icon</h4>
        <div data-icon-picker></div>
      </div>`;

    // ── Pre-set color attribute values BEFORE showPanel ──
    // EmbeddedColorPicker reads the HTML `value` attribute during FormManager.initForm.
    // Setting .value (property) here would be wiped; setAttribute persists through init.
    const colorIn = wrap.querySelector('[data-icon-prop="color"]');
    if (el.style.color) colorIn.setAttribute('value', _colorToHex(el.style.color));

    const bgIn = wrap.querySelector('[data-icon-prop="backgroundColor"]');
    if (el.style.backgroundColor) bgIn.setAttribute('value', _colorToHex(el.style.backgroundColor));

    ctx.showPanel('Icon', wrap, {
      onFormReady: () => {
        // Restore select values (FormManager doesn't touch these)
        wrap.querySelector('[data-icon-prop="fontSize"]').value = el.style.fontSize || '';
        wrap.querySelector('[data-icon-shape]').value = _detectShape(el);

        // ── Delegated prop listener (handles native inputs + EmbeddedColorPicker) ──
        // EmbeddedColorPicker fires `change` on the hidden input; selects fire `change` too.
        // Native text/select inputs fire `input`. Capturing both at wrap level covers all cases.
        const onProp = e => {
          const t = e.target;
          if (!t?.dataset?.iconProp) return;
          el.style[t.dataset.iconProp] = t.value || '';
          ctx.markChanged();
        };
        wrap.addEventListener('input', onProp, true);
        wrap.addEventListener('change', onProp, true);

        // ── Shape select ──
        wrap.querySelector('[data-icon-shape]').addEventListener('change', e => {
          _applyShape(el, e.target.value);
          ctx.markChanged();
        });

        // ── Icon picker ──
        const pickerEl = wrap.querySelector('[data-icon-picker]');
        if (window.IconPicker) {
          window.IconPicker.create(pickerEl, {name: '_designer_icon', value: currentIcon});
        }
        pickerEl.addEventListener('change', e => {
          if (e.target.name === '_designer_icon') {
            _setIconClass(el, e.target.value);
            ctx.markChanged();
          }
        });
      }
    });
  };

  /* ── Helpers ── */

  function _getIconClass(el) {
    for (const cls of el.classList) {
      if (cls.startsWith('icon-')) return cls;
    }
    return '';
  }

  function _setIconClass(el, newIcon) {
    const toRemove = Array.from(el.classList).filter(c => c.startsWith('icon-'));
    toRemove.forEach(c => el.classList.remove(c));
    if (newIcon) el.classList.add(newIcon);
  }

  function _detectShape(el) {
    const br = el.style.borderRadius;
    const pd = el.style.padding;
    if (!pd) return '';
    if (br === '50%') return 'circle';
    if (br && parseInt(br) > 0) return 'rounded';
    return 'square';
  }

  function _applyShape(el, shape) {
    switch (shape) {
      case 'square':
        el.style.padding = '0.4em';
        el.style.borderRadius = '0';
        break;
      case 'rounded':
        el.style.padding = '0.4em';
        el.style.borderRadius = '0.4em';
        break;
      case 'circle':
        el.style.padding = '0.5em';
        el.style.borderRadius = '50%';
        break;
      default:
        el.style.padding = '';
        el.style.borderRadius = '';
        break;
    }
  }

  // Convert any CSS color string to #rrggbb for <input type="color">
  function _colorToHex(color) {
    const tmp = document.createElement('div');
    tmp.style.color = color;
    document.body.appendChild(tmp);
    const computed = getComputedStyle(tmp).color;
    document.body.removeChild(tmp);
    const m = computed.match(/\d+/g);
    if (!m || m.length < 3) return '';
    return '#' + m.slice(0, 3).map(n => parseInt(n).toString(16).padStart(2, '0')).join('');
  }
}


/**
 * GCMS Designer – Inspector: Counter Element
 *
 * Edit data-end / prefix / suffix for stats-bar counter fields.
 *
 * @filesource js/designer/inspector/Counter.js
 */

export function registerCounterInspector(ctx) {

  ctx.showCounterInspector = function(el) {
    if (ctx.isEditableLocked(el)) {
      const wrap = document.createElement('div');
      wrap.innerHTML = '<p>' + ctx.escapeHtml(ctx.translate('This element is in a locked block — unlock the block toolbar first.')) + '</p>';
      ctx.showPanel('Counter', wrap);
      return;
    }

    const wrap = document.createElement('div');
    wrap.innerHTML = `
      <div class="gcms-panel-section">
        <div>
          <label data-i18n>Value</label>
          <span class="form-control icon-number">
            <input type="number" step="any" data-counter-prop="end" value="${ctx.escapeAttr(el.dataset.end || '0')}">
          </span>
        </div>
        <div>
          <label data-i18n>Prefix</label>
          <span class="form-control icon-edit">
            <input type="text" data-counter-prop="prefix" value="${ctx.escapeAttr(el.dataset.prefix || '')}">
          </span>
        </div>
        <div>
          <label data-i18n>Suffix</label>
          <span class="form-control icon-edit">
            <input type="text" data-counter-prop="suffix" value="${ctx.escapeAttr(el.dataset.suffix || '')}">
          </span>
        </div>
        <div>
          <label data-i18n>{LNG_Duration} ({LNG_ms})</label>
          <span class="form-control icon-clock">
            <input type="number" min="0" step="100" data-counter-prop="duration" value="${ctx.escapeAttr(el.dataset.duration || '2000')}">
          </span>
        </div>
        <div>
          <label data-i18n>Thousand separator</label>
          <span class="form-control icon-edit">
            <input type="text" maxlength="1" data-counter-prop="separator" value="${ctx.escapeAttr(el.dataset.separator ?? ',')}">
          </span>
        </div>
        <p class="gcms-hint-text" data-i18n>Numbers animate when previewing the page or on the live site.</p>
      </div>`;

    const applyProp = (prop, raw) => {
      const value = String(raw ?? '').trim();
      if (prop === 'end' || prop === 'duration') {
        if (value === '') {
          delete el.dataset[prop];
        } else {
          el.dataset[prop] = value;
        }
      } else if (value === '') {
        delete el.dataset[prop];
      } else {
        el.dataset[prop] = value;
      }
      ctx.syncCounterPreview(el);
      ctx.markChanged();
    };

    wrap.querySelectorAll('[data-counter-prop]').forEach(input => {
      input.addEventListener('input', () => applyProp(input.dataset.counterProp, input.value));
    });

    ctx.showPanel('Counter', wrap);
  };
}

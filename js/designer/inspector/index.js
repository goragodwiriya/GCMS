/**
 * GCMS Designer – Inspector Entry Point
 *
 * Registers all inspector helpers: applyStylePreset, renderClassTags,
 * resetBlockStyle and wires image-click + editable-focus events so the
 * right panel opens automatically.
 *
 * @filesource js/designer/inspector/index.js
 */

import {registerBlockInspector} from './Block.js';
import {registerWidgetInspector} from './Widget.js';
import {registerEditableInspector} from './Editable.js';
import {registerCounterInspector} from './Counter.js';
import {registerImageInspector} from './Image.js';
import {registerIconInspector} from './Icon.js';
import {registerPersonnelWidgetInspector} from './widgets/Personnel.js';

export function registerInspector(ctx) {
  /* sub-modules */
  registerBlockInspector(ctx);
  registerWidgetInspector(ctx);
  registerPersonnelWidgetInspector(ctx);
  registerEditableInspector(ctx);
  registerCounterInspector(ctx);
  registerImageInspector(ctx);
  registerIconInspector(ctx);

  /* ─── Shared helpers needed by inspectors ─── */

  ctx.applyStylePreset = function(block, preset) {
    if (!block) return;
    const clear = ['backgroundColor', 'backgroundImage', 'backgroundSize', 'backgroundPosition',
      'backgroundRepeat', 'backgroundAttachment', 'color', 'border', 'borderRadius', 'boxShadow', 'padding'];
    clear.forEach(p => {block.style[p] = '';});

    if (preset === 'soft-card') {
      block.style.backgroundColor = 'var(--color-background,#fff)';
      block.style.borderRadius = '1rem';
      block.style.boxShadow = '0 8px 24px rgba(15,23,42,.12)';
      block.style.padding = '1.5rem';
    } else if (preset === 'primary-band') {
      block.style.backgroundColor = 'var(--color-primary,#2563eb)';
      block.style.color = '#fff';
      block.style.borderRadius = '1rem';
      block.style.padding = '1.5rem';
    } else if (preset === 'outline') {
      block.style.backgroundColor = 'transparent';
      block.style.border = '1px solid var(--color-border,#e5e7eb)';
      block.style.borderRadius = '1rem';
      block.style.padding = '1.5rem';
    }
    ctx.markChanged();
    ctx.showInspector(block);
  };

  ctx.resetBlockStyle = function(block) {
    if (!block || ctx.isBlockLocked(block)) return;
    block.style.cssText = '';
    block.classList.remove('center-block');
    ctx.markChanged();
    ctx.showInspector(block);
  };

  ctx.renderClassTags = function(container, block) {
    if (!container) return;
    container.innerHTML = '';
    ctx.editableClassList(block).forEach(cls => {
      const tag = document.createElement('span');
      tag.className = 'gcms-class-tag';
      tag.innerHTML = `${ctx.escapeHtml(cls)} <button type="button" aria-label="${ctx.escapeAttr(ctx.translate('Remove class {name}', {name: cls}))}">&times;</button>`;
      tag.querySelector('button').addEventListener('click', () => {
        if (!ctx.isBlockLocked(block)) {
          block.classList.remove(cls);
          ctx.renderClassTags(container, block);
          ctx.markChanged();
        }
      });
      container.appendChild(tag);
    });
  };
}

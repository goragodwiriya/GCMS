/**
 * GCMS Designer – Counter Elements
 *
 * [data-component="counter"] is not inline-editable — values are edited via panel only.
 *
 * @filesource js/designer/blocks/Counters.js
 */

export function registerCounters(ctx) {

  ctx.counterElements = function(root = document) {
    return Array.from(root.querySelectorAll('[data-component="counter"]'))
      .filter(el => !el.closest('[data-widget-mount]'))
      .filter(el => !el.closest('#gcms-designer-panel, #designer-bar'));
  };

  ctx.setupCounterElements = function(root = document) {
    ctx.counterElements(root).forEach(ctx.setupSingleCounter);
  };

  ctx.setupSingleCounter = function(el) {
    el.removeAttribute('contenteditable');
    el.removeAttribute('data-editable');
    el.draggable = false;
    ctx.syncCounterPreview?.(el);

    const wrapper = el.closest('.gcms-stat-value');
    if (!wrapper || wrapper.dataset.gcmsCounterWrapReady === '1') return;
    wrapper.dataset.gcmsCounterWrapReady = '1';

    wrapper.addEventListener('click', event => {
      if (!ctx.state.active || ctx.state.preview) return;
      if (event.target.closest('[data-editable="text"]')) return;

      const counter = wrapper.querySelector('[data-component="counter"]');
      if (!counter) return;

      if (ctx.isEditableLocked(counter)) {
        event.preventDefault();
        ctx.notify(ctx.translate('This block is locked — unlock it before editing'));
        return;
      }

      event.preventDefault();
      event.stopPropagation();
      ctx.state.selectedEditable = counter;
      wrapper.classList.add('gcms-counter-wrap-active');
      ctx.showCounterInspector?.(counter);
    });
  };
}

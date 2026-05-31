/**
 * GCMS Designer – Widget Frame Management
 *
 * Handles rendering widget HTML into data-widget-mount containers,
 * building widget frame HTML, adding/resetting widget mounts.
 *
 * @filesource js/designer/widgets.js
 */

export function registerWidgets(ctx) {

  ctx.widgetParamsToSearch = function(params) {
    const search = new URLSearchParams();
    const trimmed = String(params || '').trim();
    if (trimmed) search.set('params', trimmed);
    return search.toString();
  };

  ctx.resetWidgetMount = function(block, message) {
    const mount = block?.querySelector?.('[data-widget-mount]');
    if (!mount) return;
    delete mount.dataset.widgetRendered;
    const msg = message != null ? message : ctx.translate('Loading widget...');
    mount.innerHTML = `<div class="gcms-widget-placeholder">${ctx.escapeHtml(msg)}</div>`;
  };

  ctx.resetWidgetMounts = function(root) {
    root.querySelectorAll?.('[data-widget][data-widget-mount], [data-widget] [data-widget-mount]').forEach(node => {
      const block = node.matches?.('[data-widget]') ? node : node.closest('[data-widget]');
      ctx.resetWidgetMount(block);
    });
  };

  ctx.renderWidgetFrame = async function(block, force = false) {
    if (!block || !ctx.isWidgetFrame(block)) return;
    const widget = block.dataset.widget;
    const mount = block.querySelector('[data-widget-mount]');
    if (!widget || !mount) return;
    if (!force && mount.dataset.widgetRendered === '1') return;

    mount.dataset.widgetRendered = 'loading';
    ctx.resetWidgetMount(block);

    try {
      const query = ctx.widgetParamsToSearch(block.dataset.widgetParams || '');
      const url = `${ctx.webUrl()}api/index/widgets/render?widget=${encodeURIComponent(widget)}${query ? `&${query}` : ''}`;
      const result = await ctx.fetchJson(url, {method: 'GET'});
      const html = result?.data?.html || result?.html || '';
      if (html.trim()) {
        mount.innerHTML = html;
      } else {
        mount.innerHTML = `<div class="gcms-widget-placeholder">${ctx.escapeHtml(ctx.translate('Widget {name} has no content to display', {name: ctx.widgetTitle(widget)}))}</div>`;
      }
      mount.dataset.widgetRendered = '1';
    } catch (e) {
      mount.dataset.widgetRendered = 'error';
      mount.innerHTML = `<div class="gcms-widget-placeholder error">${ctx.escapeHtml(ctx.translate('Failed to load widget: {message}', {message: e.message}))}</div>`;
    }
  };

  ctx.renderWidgetFrames = function(root = document, force = false) {
    root.querySelectorAll('[data-widget]').forEach(block => ctx.renderWidgetFrame(block, force));
  };

  ctx.buildWidgetFrameHtml = function(widget) {
    const id = String(widget?.id || '').replace(/[^a-z0-9]/g, '');
    const title = widget?.title || id || 'Widget';
    return `
      <section class="section-bg gcms-custom-block gcms-widget-frame" data-block-type="widget" data-editor-template="widget-frame" data-gcms-custom="1" data-widget="${ctx.escapeAttr(id)}" data-editor-label="${ctx.escapeAttr(title)}">
        <div class="gcms-widget-mount" data-widget-mount>
          <div class="gcms-widget-placeholder">${ctx.escapeHtml(ctx.translate('Loading widget...'))}</div>
        </div>
      </section>`;
  };

  ctx.addWidgetFrame = function(widget, target = 'content') {
    if (!widget?.id) return;
    const selected = ctx.state.selectedBlock;
    const targetContainer = target === 'sidebar' ? 'sidebar' : 'content';
    const container = target.includes('selected') && selected?.parentElement
      ? selected.parentElement
      : document.querySelector(`[data-editor-container="${ctx.selectorValue(targetContainer)}"]`);
    if (!container) return;

    const block = ctx.createElementFromHtml(ctx.buildWidgetFrameHtml(widget));
    block.dataset.editorId = `widget-${widget.id}-${Date.now()}`;
    block.dataset.widgetInstance = block.dataset.editorId;

    if (target === 'after-selected' && selected?.parentElement === container) {
      container.insertBefore(block, selected.nextSibling);
    } else if (target === 'before-selected' && selected?.parentElement === container) {
      container.insertBefore(block, selected);
    } else {
      container.appendChild(block);
    }

    ctx.setupSingleBlock(block);
    ctx.selectBlock(block);
    ctx.flashBlock(block);
    ctx.renderWidgetFrame(block, true);
    ctx.markChanged();
    ctx.notify(ctx.translate('Widget frame installed'));
  };
}

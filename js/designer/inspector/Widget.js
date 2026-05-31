/**
 * GCMS Designer – Inspector: Widget Frame
 *
 * Properties specific to a widget frame block.
 * Links to the widget's native settings page.
 *
 * @filesource js/designer/inspector/Widget.js
 */

export function registerWidgetInspector(ctx) {

  // Registry for widget-specific panel builders.
  // Each entry: ctx.widgetPanelBuilders[widgetId] = function(block, sectionEl) { ... }
  // Called synchronously; the function may populate sectionEl asynchronously.
  ctx.widgetPanelBuilders = ctx.widgetPanelBuilders || {};

  ctx.showWidgetInspector = function(block) {
    const widgetId = block.dataset.widget || '';
    const manifest = ctx.state.widgetManifests.find(m => m.id === widgetId);
    const settingsUrl = ctx.widgetSettingsUrl(widgetId);
    const currentParams = block.dataset.widgetParams || '';

    const wrap = document.createElement('div');

    // Section 1: identity + action links
    const titleSection = document.createElement('div');
    titleSection.className = 'gcms-panel-section';
    titleSection.innerHTML = `
      <h4>${ctx.escapeHtml(ctx.translate('Widget'))}: ${ctx.escapeHtml(ctx.widgetTitle(widgetId) || widgetId)}</h4>
      <p class="gcms-hint-text">${ctx.escapeHtml(ctx.translate('Designer manages only the frame (position/style). Inner logic is handled by the widget itself.'))}</p>
      ${settingsUrl ? `<a href="${ctx.escapeAttr(settingsUrl)}" target="_blank" class="btn btn-secondary icon-cog">${ctx.escapeHtml(ctx.translate('Open widget settings'))}</a>` : ''}
      ${manifest?.renderUrl ? `<button type="button" class="btn btn-secondary icon-refresh" data-action="reload-widget-frame">${ctx.escapeHtml(ctx.translate('Reload widget content'))}</button>` : ''}`;
    wrap.appendChild(titleSection);
    titleSection.querySelector('[data-action="reload-widget-frame"]')?.addEventListener('click',
      () => ctx.renderWidgetFrame(block, true));

    // Section 2: params — custom builder or raw textarea fallback
    const paramsSection = document.createElement('div');
    paramsSection.className = 'gcms-panel-section';
    const customBuilder = ctx.widgetPanelBuilders[widgetId];
    if (customBuilder) {
      customBuilder(block, paramsSection);
    } else {
      paramsSection.innerHTML = `
        <h4>${ctx.escapeHtml(ctx.translate('Widget Params'))}</h4>
        <div>
          <span class="form-control icon-code">
            <textarea rows="4" data-widget-params>${ctx.escapeHtml(currentParams)}</textarea>
          </span>
        </div>`;
      paramsSection.querySelector('[data-widget-params]').addEventListener('change', e => {
        if (ctx.isBlockLocked(block)) {
          e.target.value = block.dataset.widgetParams || '';
          ctx.notify(ctx.translate('This block is locked.'));
          return;
        }
        const params = e.target.value.trim();
        if (params) block.dataset.widgetParams = params; else delete block.dataset.widgetParams;
        ctx.renderWidgetFrame(block, true);
        ctx.markChanged();
      });
    }
    wrap.appendChild(paramsSection);

    // Section 3: lock protection
    const lockSection = document.createElement('div');
    lockSection.className = 'gcms-panel-section';
    lockSection.innerHTML = `
      <h4>${ctx.escapeHtml(ctx.translate('Protection'))}</h4>
      <div>
        <input type="checkbox" class="switch" id="block-lock" data-block-lock>
        <label for="block-lock">${ctx.escapeHtml(ctx.translate('Lock this block'))}</label>
      </div>`;
    wrap.appendChild(lockSection);

    const lockCb = lockSection.querySelector('[data-block-lock]');
    lockCb.checked = ctx.isBlockLocked(block);
    lockCb.addEventListener('change', () => {ctx.setBlockLocked(block, lockCb.checked); ctx.markChanged();});

    ctx.showPanel('Widget Properties', wrap);
  };
}

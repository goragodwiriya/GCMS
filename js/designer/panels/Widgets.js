/**
 * GCMS Designer – Widgets Panel
 *
 * Widget Library: browse manifests, install widget frames.
 *
 * @filesource js/designer/panels/Widgets.js
 */

export function registerWidgetsPanel(ctx) {

  ctx.showWidgetsPanel = async function() {
    const wrap = document.createElement('div');
    wrap.innerHTML = `
      <div class="gcms-panel-section">
        <h4 data-i18n>Insert location</h4>
        <div>
          <label data-i18n>Where to place the widget</label>
          <span class="form-control icon-menus">
            <select data-widget-target>
              <option value="content" data-i18n>End of content</option>
              <option value="sidebar" data-i18n>End of sidebar</option>
              <option value="after-selected" data-i18n>After selected block</option>
              <option value="before-selected" data-i18n>Before selected block</option>
            </select>
          </span>
        </div>
      </div>
      <div class="gcms-panel-section">
        <h4 data-i18n>Widgets</h4>
        <p class="gcms-hint-text" data-i18n>Designer installs the frame and position only; settings and rendering belong to each widget.</p>
        <div class="form-group">
          <button type="button" class="btn icon-reset" data-action="reload-widgets" data-i18n>Reload</button>
        </div>
        <div class="gcms-widget-grid" data-i18n>Loading...</div>
      </div>
      <div class="gcms-panel-section">
        <h4 data-i18n>Widget Params</h4>
        <p class="gcms-hint-text" data-i18n>After installing, select the widget frame to set params like <code>module=news;layout=carousel;limit=6</code></p>
      </div>`;

    const grid = wrap.querySelector('.gcms-widget-grid');
    const renderGrid = () => ctx.renderWidgetGrid(
      grid,
      wrap.querySelector('[data-widget-target]').value
    );

    wrap.querySelector('[data-widget-target]').addEventListener('change', renderGrid);
    wrap.querySelector('[data-action="reload-widgets"]').addEventListener('click', async () => {
      grid.textContent = ctx.translate('Loading...');
      try {
        await ctx.loadWidgetManifests(true);
        renderGrid();
      } catch (e) {
        grid.removeAttribute('data-i18n');
        grid.textContent = ctx.translate('Could not load widgets: {message}', {message: e.message});
      }
    });

    ctx.showPanel('Widget Library', wrap);
    try {
      await ctx.loadWidgetManifests();
      grid.removeAttribute('data-i18n');
      renderGrid();
    } catch (e) {
      grid.removeAttribute('data-i18n');
      grid.textContent = ctx.translate('Could not load widgets: {message}', {message: e.message});
    }
  };

  ctx.renderWidgetGrid = function(grid, target = 'content') {
    grid.innerHTML = '';
    const items = ctx.state.widgetManifests;
    if (items.length === 0) {
      grid.innerHTML = '<p class="gcms-template-empty" data-i18n>No widgets found</p>';
      ctx.localizeDom(grid);
      return;
    }
    items.forEach(widget => {
      const card = document.createElement('button');
      card.type = 'button';
      card.className = 'gcms-template-card gcms-widget-card';
      const settingsHint = widget.hasSettings ? ` · ${ctx.translate('Has settings')}` : '';
      card.innerHTML = `
        <strong><span class="${ctx.escapeAttr(widget.icon || 'icon-widgets')}"></span> ${ctx.escapeHtml(widget.title)}</strong>
        <span>${ctx.escapeHtml(widget.id)}${ctx.escapeHtml(settingsHint)}</span>`;
      card.addEventListener('click', () => ctx.addWidgetFrame(widget, target));
      grid.appendChild(card);
    });
  };
}

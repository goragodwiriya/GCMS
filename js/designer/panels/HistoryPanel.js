/**
 * GCMS Designer – History Panel
 *
 * Server-side revision history with restore support.
 *
 * @filesource js/designer/panels/HistoryPanel.js
 */

export function registerHistoryPanel(ctx) {

  ctx.showHistoryPanel = async function() {
    const wrap = document.createElement('div');
    wrap.innerHTML = `
      <div class="gcms-panel-section">
        <h4 data-i18n>Revision History</h4>
        <div class="gcms-history-list" data-i18n>Loading...</div>
      </div>
      <div class="gcms-panel-section">
        <p data-i18n>Pick a version to restore into the editor, then save the workspace or export a theme.</p>
      </div>`;
    ctx.showPanel('Revision History', wrap);

    const list = wrap.querySelector('.gcms-history-list');
    try {
      const url = `${ctx.webUrl()}api/designer/home/history?theme=${encodeURIComponent(ctx.getTheme())}&page=home`;
      const result = await ctx.fetchJson(url, {method: 'GET'});
      if (!result?.success) {
        list.removeAttribute('data-i18n');
        list.textContent = result?.message || ctx.translate('Could not load history');
        return;
      }
      _renderList(list, result.items || []);
    } catch (e) {
      list.removeAttribute('data-i18n');
      list.textContent = ctx.translate('Could not load history: {message}', {message: e.message});
    }
  };

  /* ─── Private ─── */

  function _renderList(list, items) {
    list.removeAttribute('data-i18n');
    list.innerHTML = '';
    if (items.length === 0) {
      list.textContent = ctx.translate('No revisions yet');
      return;
    }
    items.forEach(item => {
      const row = document.createElement('div');
      row.className = 'gcms-history-item';
      row.innerHTML = `
        <div>
          <strong>${ctx.formatDate(item.savedAt)}</strong>
          <span>${Math.ceil((item.size || 0) / 1024)} KB</span>
        </div>
        <button type="button" class="btn btn-secondary" data-i18n>Restore</button>`;
      row.querySelector('button').addEventListener('click', () => ctx.restoreServerRevision(item.id));
      ctx.localizeDom(row);
      list.appendChild(row);
    });
  }
}

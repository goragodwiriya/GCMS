/**
 * GCMS Designer – Layers Panel
 *
 * Page Structure overview: view, reorder, hide/show blocks.
 *
 * @filesource js/designer/panels/Layers.js
 */

export function registerLayersPanel(ctx) {

  ctx.showLayersPanel = function() {
    const wrap = document.createElement('div');
    wrap.innerHTML = `
      <div class="gcms-panel-section">
        <h4 data-i18n>Page Structure</h4>
        <div class="gcms-layer-bulk-actions">
          <button type="button" data-layer-bulk="show-hidden" data-i18n>Show all hidden</button>
          <button type="button" data-layer-bulk="expand" data-i18n>Expand all</button>
          <button type="button" data-layer-bulk="collapse" data-i18n>Collapse all</button>
        </div>
        <div class="gcms-layers-list"></div>
      </div>
      <div class="gcms-panel-section">
        <p data-i18n>Select a block to edit properties or reorder/hide from this panel.</p>
      </div>`;

    const list = wrap.querySelector('.gcms-layers-list');
    wrap.querySelectorAll('[data-layer-bulk]').forEach(btn => {
      btn.addEventListener('click', () => {
        const a = btn.dataset.layerBulk;
        if (a === 'show-hidden') _showAllHidden();
        if (a === 'expand') _setGroupsCollapsed(wrap, false);
        if (a === 'collapse') _setGroupsCollapsed(wrap, true);
        _renderList(list);
      });
    });
    _renderList(list);
    ctx.showPanel('Layers', wrap);
  };

  /* ─── Private ─── */

  function _renderList(list) {
    list.innerHTML = '';

    const standalone = ctx.blocks().filter(block => !block.closest('[data-editor-container]'));
    list.appendChild(_createGroup(ctx.translate('Page'), standalone));

    ctx.containerList().forEach(container => {
      const name = container.dataset.editorContainer || 'container';
      const items = [...container.children].filter(c => c.matches?.('[data-block-type]'));
      list.appendChild(_createGroup(ctx.translate(name), items));
    });
  }

  function _createGroup(title, items) {
    const group = document.createElement('div');
    group.className = 'gcms-layer-group';
    group.innerHTML = `<button type="button" class="gcms-layer-group-title" aria-expanded="true">${ctx.escapeHtml(title)} <span>${items.length}</span></button>`;
    const titleBtn = group.querySelector('.gcms-layer-group-title');
    titleBtn.addEventListener('click', () => {
      const collapsed = group.classList.toggle('collapsed');
      titleBtn.setAttribute('aria-expanded', collapsed ? 'false' : 'true');
    });

    if (items.length === 0) {
      group.insertAdjacentHTML('beforeend', '<p class="gcms-layer-empty" data-i18n>No blocks</p>');
      ctx.localizeDom(group);
      return group;
    }

    items.forEach(block => {
      const row = document.createElement('div');
      row.className = 'gcms-layer-item' + (block === ctx.state.selectedBlock ? ' active' : '');
      const hideLabel = block.dataset.gcmsHidden === '1' ? ctx.translate('Show') : ctx.translate('Hide');
      row.innerHTML = `
        <button type="button" class="gcms-layer-main">
          <strong>${ctx.escapeHtml(block.dataset.editorLabel || block.dataset.blockType || ctx.translate('Block'))}</strong>
          <span>${ctx.escapeHtml(block.dataset.editorId || '')}</span>
          <small>${_layerBadges(block).map(b => `<em>${ctx.escapeHtml(b)}</em>`).join('')}</small>
        </button>
        <div class="gcms-layer-actions">
          <button type="button" data-la="up" title="{LNG_Move up}">↑</button>
          <button type="button" data-la="down" title="{LNG_Move down}">↓</button>
          <button type="button" data-la="hide">${ctx.escapeHtml(hideLabel)}</button>
        </div>`;
      row.querySelector('.gcms-layer-main').addEventListener('click', () => {
        ctx.selectBlock(block);
        block.scrollIntoView({behavior: 'smooth', block: 'center'});
      });
      row.querySelectorAll('[data-la]').forEach(btn => {
        btn.addEventListener('click', () => {
          if (btn.dataset.la === 'up') ctx.moveBlock(block, -1);
          if (btn.dataset.la === 'down') ctx.moveBlock(block, 1);
          if (btn.dataset.la === 'hide') ctx.toggleHidden(block);
          ctx.showLayersPanel();
        });
      });
      ctx.localizeDom(row);
      group.appendChild(row);
    });
    return group;
  }

  function _layerBadges(block) {
    const b = [];
    if (ctx.isBlockLocked(block)) b.push(ctx.translate('Locked'));
    if (block.dataset.gcmsHidden === '1') b.push(ctx.translate('Hidden'));
    if (block.dataset.gcmsCustom === '1') b.push(ctx.translate('Custom'));
    return b;
  }

  function _showAllHidden() {
    let changed = false;
    ctx.blocks().forEach(block => {
      if (block.dataset.gcmsHidden === '1') {block.dataset.gcmsHidden = '0'; changed = true;}
    });
    if (changed) {
      ctx.markChanged();
      ctx.notify(ctx.translate('All hidden blocks are now visible'));
    }
  }

  function _setGroupsCollapsed(root, collapsed) {
    root.querySelectorAll('.gcms-layer-group').forEach(g => {
      g.classList.toggle('collapsed', collapsed);
      g.querySelector('.gcms-layer-group-title')?.setAttribute('aria-expanded', collapsed ? 'false' : 'true');
    });
  }
}

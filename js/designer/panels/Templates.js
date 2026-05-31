/**
 * GCMS Designer – Templates Panel
 *
 * Template Library: insert built-in/user templates, custom HTML blocks,
 * save/rename/delete user templates.
 *
 * @filesource js/designer/panels/Templates.js
 */

export function registerTemplatesPanel(ctx) {

  ctx.showTemplatesPanel = function() {
    const wrap = document.createElement('div');
    wrap.innerHTML = `
      <div class="gcms-panel-section">
        <h4 data-i18n>Insert location</h4>
        <div>
          <label data-i18n>Where to insert the section</label>
          <span class="form-control icon-menus">
            <select data-template-target>
              <option value="content" data-i18n>End of content</option>
              <option value="sidebar" data-i18n>End of sidebar</option>
              <option value="after-selected" data-i18n>After selected block</option>
              <option value="before-selected" data-i18n>Before selected block</option>
            </select>
          </span>
        </div>
      </div>

      <div class="gcms-panel-section">
        <h4 data-i18n>Templates</h4>
        <div class="form-group">
          <span class="form-control">
            <select data-template-category>
              <option value="all" data-i18n>All categories</option>
              <option value="content" data-i18n>Content</option>
              <option value="media" data-i18n>Media</option>
              <option value="layout" data-i18n>Layout</option>
              <option value="saved" data-i18n>My templates</option>
            </select>
          </span>
        </div>
        <div class="gcms-template-grid"></div>
      </div>`;

    const grid = wrap.querySelector('.gcms-template-grid');
    const renderGrid = () => ctx.renderTemplateGrid(
      grid,
      wrap.querySelector('[data-template-category]').value,
      wrap.querySelector('[data-template-target]').value
    );

    wrap.querySelector('[data-template-category]').addEventListener('change', renderGrid);
    wrap.querySelector('[data-template-target]').addEventListener('change', renderGrid);
    renderGrid();
    ctx.showPanel('Template Library', wrap);
  };

  ctx.renderTemplateGrid = function(grid, category = 'all', target = 'content') {
    grid.innerHTML = '';
    const all = [...ctx.state.templates, ...ctx.state.userTemplates];
    const items = all.filter(t => category === 'all' || t.category === category);
    if (items.length === 0) {
      grid.innerHTML = '<p class="gcms-template-empty" data-i18n>No templates found</p>';
      ctx.localizeDom(grid);
      return;
    }
    items.forEach(tpl => {
      const card = document.createElement(tpl.userTemplate ? 'div' : 'button');
      card.className = `gcms-template-card${tpl.userTemplate ? ' user-template' : ''}`;
      if (tpl.userTemplate) {
        card.innerHTML = `
          <div class="gcms-template-card-main">
            <strong>${ctx.escapeHtml(tpl.title)}</strong>
            <span>${ctx.escapeHtml(tpl.description === 'My template' ? ctx.translate('My template') : (tpl.description || ''))}</span>
          </div>
          <div class="gcms-template-card-actions">
            <button type="button" data-tpl-action="insert" data-i18n>Insert</button>
            <button type="button" data-tpl-action="rename" data-i18n>Rename</button>
            <button type="button" data-tpl-action="delete" data-i18n>Delete</button>
          </div>`;
        card.querySelector('[data-tpl-action="insert"]').addEventListener('click', () => ctx.addTemplate(tpl, target));
        card.querySelector('[data-tpl-action="rename"]').addEventListener('click', () => {
          ctx.renameUserTemplate(tpl.id);
          ctx.renderTemplateGrid(grid, category, target);
        });
        card.querySelector('[data-tpl-action="delete"]').addEventListener('click', () => {
          ctx.deleteUserTemplate(tpl.id);
          ctx.renderTemplateGrid(grid, category, target);
        });
        ctx.localizeDom(card);
      } else {
        card.type = 'button';
        card.innerHTML = `<strong>${ctx.escapeHtml(ctx.translate(tpl.title))}</strong><span>${ctx.escapeHtml(ctx.translate(tpl.description || ''))}</span>`;
        card.addEventListener('click', () => ctx.addTemplate(tpl, target));
      }
      grid.appendChild(card);
    });
  };

  ctx.addTemplate = function(template, target = 'content') {
    const selected = ctx.state.selectedBlock;
    const container = _resolveContainer(target, selected);
    if (!container) return;
    const block = ctx.createElementFromHtml(template.html);
    block.dataset.editorId = `custom-${template.id}-${Date.now()}`;
    _insertRelative(container, block, target, selected);
    ctx.assignEditableNames(block);
    ctx.setupSingleBlock(block);
    ctx.setupEditables(block);
    ctx.selectBlock(block);
    ctx.flashBlock(block);
    ctx.markChanged();
    ctx.notify(ctx.translate('Section added'));
  };

  ctx.insertCustomHtmlBlock = function(raw, target = 'content', mode = 'wrap') {
    const html = _buildCustomBlockHtml(raw, mode);
    if (!html) {
      alert(ctx.translate('Please enter HTML'));
      return false;
    }
    ctx.addTemplate({id: 'custom-html', html}, target);
    return true;
  };

  ctx.loadUserTemplates = function() {
    try {
      const raw = localStorage.getItem(ctx.userTemplateKey());
      const items = raw ? JSON.parse(raw) : [];
      ctx.state.userTemplates = Array.isArray(items) ? items : [];
    } catch {
      ctx.state.userTemplates = [];
    }
  };

  ctx.saveSelectedBlockAsTemplate = function() {
    const block = ctx.state.selectedBlock;
    if (!block) {
      alert(ctx.translate('Please select a block before saving as template'));
      return;
    }
    const title = prompt(
      ctx.translate('Template name'),
      block.dataset.editorLabel || block.dataset.blockType || ctx.translate('My template')
    );
    if (!title) return;
    const clone = block.cloneNode(true);
    ctx.cleanEditorUi(clone);
    clone.removeAttribute('draggable');
    delete clone.dataset.gcmsDesignerReady;
    clone.dataset.gcmsCustom = '1';
    clone.dataset.blockType = clone.dataset.blockType || 'custom-template';
    ctx.resetWidgetMounts(clone);
    ctx.state.userTemplates.push({
      id: `user-${Date.now()}`,
      userTemplate: true,
      category: 'saved',
      title: title.trim(),
      description: 'My template',
      html: clone.outerHTML
    });
    _persistUserTemplates();
    ctx.notify(ctx.translate('Template saved'));
  };

  ctx.renameUserTemplate = function(id) {
    const tpl = ctx.state.userTemplates.find(t => t.id === id);
    if (!tpl) return;
    const name = prompt(ctx.translate('New name'), tpl.title);
    if (!name) return;
    tpl.title = name.trim();
    _persistUserTemplates();
  };

  ctx.deleteUserTemplate = function(id) {
    if (!confirm(ctx.translate('Delete this template?'))) return;
    ctx.state.userTemplates = ctx.state.userTemplates.filter(t => t.id !== id);
    _persistUserTemplates();
  };

  ctx.getCustomBlockInnerHtml = function(block) {
    return [...block.children]
      .filter(c => !c.classList.contains('gcms-block-tools'))
      .map(c => c.outerHTML).join('\n');
  };

  ctx.applyCustomBlockInnerHtml = function(block, raw) {
    if (ctx.isBlockLocked(block)) {
      ctx.notify(ctx.translate('This block is locked.'));
      return;
    }
    const html = ctx.sanitizeInlineHtml(String(raw || ''));
    [...block.children].forEach(c => {if (!c.classList.contains('gcms-block-tools')) c.remove();});
    const tpl = document.createElement('template');
    tpl.innerHTML = html.trim();
    while (tpl.content.firstChild) block.appendChild(tpl.content.firstChild);
    ctx.assignEditableNames(block);
    ctx.setupEditables(block);
    ctx.markChanged();
    ctx.notify(ctx.translate('Block HTML updated'));
  };

  /* ───── Private helpers ───── */

  function _resolveContainer(target, selected) {
    const containerName = target === 'sidebar' ? 'sidebar' : 'content';
    if (target.includes('selected') && selected?.parentElement) return selected.parentElement;
    return document.querySelector(`[data-editor-container="${ctx.selectorValue(containerName)}"]`);
  }

  function _insertRelative(container, block, target, selected) {
    if (target === 'after-selected' && selected?.parentElement === container) {
      container.insertBefore(block, selected.nextSibling);
    } else if (target === 'before-selected' && selected?.parentElement === container) {
      container.insertBefore(block, selected);
    } else {
      container.appendChild(block);
    }
  }

  function _buildCustomBlockHtml(raw, mode = 'wrap') {
    const trimmed = String(raw || '').trim();
    if (!trimmed) return '';
    if (mode === 'full') {
      const clean = ctx.sanitizeInlineHtml(trimmed);
      const tpl = document.createElement('template');
      tpl.innerHTML = clean.trim();
      const nodes = [...tpl.content.children];
      if (nodes.length === 0) return '';
      if (nodes.length > 1) return `<section class="section-bg gcms-custom-block" data-block-type="custom-html" data-editor-template="custom-html" data-gcms-custom="1">${clean}</section>`;
      const root = nodes[0];
      root.classList.add('gcms-custom-block');
      root.dataset.gcmsCustom = '1';
      if (!root.dataset.blockType) root.dataset.blockType = 'custom-html';
      if (!root.dataset.editorTemplate) root.dataset.editorTemplate = 'custom-html';
      return root.outerHTML;
    }
    const inner = ctx.sanitizeInlineHtml(trimmed);
    if (!inner.trim()) return '';
    return `<section class="section-bg gcms-custom-block" data-block-type="custom-html" data-editor-template="custom-html" data-gcms-custom="1">${inner}</section>`;
  }

  function _persistUserTemplates() {
    try {localStorage.setItem(ctx.userTemplateKey(), JSON.stringify(ctx.state.userTemplates.slice(0, 30)));}
    catch { /* quota */}
  }
}

/**
 * GCMS Designer – Block Operations
 *
 * moveBlock(), duplicateBlock(), removeBlock(), toggleHidden(),
 * toggleBlockLock(), copyBlockStyle(), pasteBlockStyle()
 *
 * @filesource js/designer/blocks/Operations.js
 */

export function registerBlocksOperations(ctx) {

  /* ─── Drop-insert helpers (used by DropZones + InsertDrawer click) ─── */

  /**
   * Insert a dragged item (section | widget | element) between blocks.
   * @param {{type:string, id?:string, html?:string, widgetId?:string, title?:string, label?:string}} data
   * @param {Element} container  – the [data-editor-container] or .gcms-column parent
   * @param {Element|null} afterBlock – insert after this block; null = prepend / append
   */
  ctx.insertFromDrop = function(data, container, afterBlock, prepend = false) {
    if (!container) return;
    if (data.type !== 'section') return;  // elements/widgets go into blocks via insertIntoBlock

    let block;
    block = ctx.createElementFromHtml(data.html || '');
    if (!block) return;
    block.dataset.editorId = `custom-${data.id || 'tpl'}-${Date.now()}`;
    ctx.assignEditableNames(block);

    if (prepend) {
      const firstBlock = container.querySelector(':scope > [data-block-type]');
      container.insertBefore(block, firstBlock || null);
    } else if (afterBlock && afterBlock.parentElement === container) {
      container.insertBefore(block, afterBlock.nextSibling);
    } else {
      container.appendChild(block);
    }

    ctx.setupSingleBlock(block);
    ctx.setupEditables(block);
    if (ctx.isWidgetFrame(block)) ctx.renderWidgetFrame(block, true);
    ctx.selectBlock(block);
    ctx.flashBlock(block);
    ctx.markChanged();
    ctx.pushHistory(`insert ${data.type}`);
    ctx.notify(ctx.translate('Added'));
  };

  /**
   * Insert a widget or element into a .gcms-column cell.
   */
  ctx.insertIntoColumn = function(data, column) {
    if (!column) return;
    // Remove any leftover drop zones inside the column
    column.querySelectorAll('.gcms-drop-zone').forEach(z => z.remove());

    let newEl;
    if (data.type === 'widget') {
      newEl = ctx.createElementFromHtml(
        ctx.buildWidgetFrameHtml({id: data.widgetId, title: data.title})
      );
      if (!newEl) return;
      newEl.dataset.editorId = `widget-${data.widgetId}-${Date.now()}`;
      newEl.dataset.widgetInstance = newEl.dataset.editorId;
    } else if (data.type === 'element') {
      const label = ctx.escapeAttr(data.label || '');
      newEl = ctx.createElementFromHtml(
        `<div class="gcms-col-item gcms-custom-block" data-block-type="col-element" data-gcms-custom="1" data-editor-label="${label}">${data.html || ''}</div>`
      );
      if (!newEl) return;
      newEl.dataset.editorId = `col-el-${data.id || 'el'}-${Date.now()}`;
      ctx.assignEditableNames(newEl);
    } else {
      return;
    }

    column.appendChild(newEl);
    ctx.setupSingleBlock(newEl);
    ctx.setupEditables(newEl);
    if (ctx.isWidgetFrame(newEl)) ctx.renderWidgetFrame(newEl, true);
    ctx.selectBlock(newEl);
    ctx.flashBlock(newEl);
    ctx.markChanged();
    ctx.pushHistory('insert into column');
    ctx.notify(ctx.translate('Added to column'));
  };

  /**
   * Quick-insert an element snippet into an existing custom block's content area.
   * Called by the click handler in the Insert Drawer Elements tab.
   */
  ctx.insertIntoBlock = function(data, block) {
    if (!block) return;

    // Find the best content container inside the block
    const contentArea = block.querySelector('.section-content')
      || block.querySelector('.gcms-column')
      || block;

    if (data.type === 'widget') {
      const newEl = ctx.createElementFromHtml(
        ctx.buildWidgetFrameHtml({id: data.widgetId, title: data.title})
      );
      if (!newEl) return;
      newEl.dataset.editorId = `widget-${data.widgetId}-${Date.now()}`;
      newEl.dataset.widgetInstance = newEl.dataset.editorId;
      contentArea.appendChild(newEl);
      ctx.setupSingleBlock(newEl);
      ctx.setupEditables(newEl);
      ctx.renderWidgetFrame(newEl, true);
      ctx.selectBlock(newEl);
      ctx.flashBlock(newEl);
    } else if (data.type === 'element') {
      const tpl = document.createElement('template');
      tpl.innerHTML = (data.html || '').trim();
      const newEl = tpl.content.firstElementChild;
      if (!newEl) return;
      ctx.assignEditableNames(newEl);
      contentArea.appendChild(newEl);
      ctx.setupSingleEditable(newEl);
      ctx.syncDropZoneContentEditable?.(contentArea);
    } else {
      return;
    }

    ctx.markChanged();
    ctx.pushHistory(`insert ${data.type} into block`);
    ctx.notify(ctx.translate('Added'));
  };

  /**
   * Insert an element or widget at a specific position within a block's content area.
   * Called by the intra-block pick mode (into-block-at drop type).
   * @param {{type:string, html?:string, widgetId?:string, title?:string, id?:string, label?:string}} data
   * @param {Element} contentArea  – the .section-content/.gcms-column/block element
   * @param {Element|null} afterEl – sibling to insert after; null = end
   * @param {boolean} prepend      – when true, insert before all children
   */
  ctx.insertElementAt = function(data, contentArea, afterEl, prepend = false) {
    if (!contentArea) return;

    let newEl;
    if (data.type === 'widget') {
      newEl = ctx.createElementFromHtml(
        ctx.buildWidgetFrameHtml({id: data.widgetId, title: data.title})
      );
      if (!newEl) return;
      newEl.dataset.editorId = `widget-${data.widgetId}-${Date.now()}`;
      newEl.dataset.widgetInstance = newEl.dataset.editorId;
    } else if (data.type === 'element') {
      const tpl = document.createElement('template');
      tpl.innerHTML = (data.html || '').trim();
      newEl = tpl.content.firstElementChild;
      if (!newEl) return;
      ctx.assignEditableNames(newEl);
    } else {
      return;
    }

    if (prepend) {
      const firstChild = contentArea.querySelector(':scope > :not(.gcms-drop-zone):not(.gcms-block-tools)');
      contentArea.insertBefore(newEl, firstChild || null);
    } else if (afterEl && afterEl.parentElement === contentArea) {
      contentArea.insertBefore(newEl, afterEl.nextSibling);
    } else {
      contentArea.appendChild(newEl);
    }

    if (data.type === 'widget') {
      ctx.setupSingleBlock(newEl);
      ctx.setupEditables(newEl);
      ctx.renderWidgetFrame(newEl, true);
      ctx.selectBlock(newEl);
      ctx.flashBlock(newEl);
    } else {
      ctx.setupSingleEditable(newEl);
      ctx.syncDropZoneContentEditable?.(contentArea);
      ctx.flashBlock?.(newEl);
    }

    ctx.markChanged();
    ctx.pushHistory(`insert ${data.type} at position`);
    ctx.notify(ctx.translate('Added'));
  };

  ctx.moveBlock = function(block, direction) {
    if (!ctx.state.active || ctx.isBlockLocked(block)) return;
    const parent = block.parentElement;
    const siblings = [...parent.querySelectorAll(':scope > [data-block-type]')];
    const index = siblings.indexOf(block);
    const target = siblings[index + direction];
    if (!target) return;
    if (direction < 0) parent.insertBefore(block, target);
    else target.after(block);
    ctx.markChanged();
    ctx.pushHistory('move block');
  };

  ctx.duplicateBlock = function(block) {
    if (!ctx.state.active || ctx.isBlockLocked(block)) return;
    const clone = block.cloneNode(true);
    delete clone.dataset.editorId;
    delete clone.dataset.gcmsDesignerReady;
    clone.querySelectorAll('.gcms-block-tools').forEach(el => el.remove());
    clone.querySelectorAll('[data-editor-id]').forEach(el => delete el.dataset.editorId);
    block.after(clone);
    ctx.ensureBlockIds();
    ctx.ensureEditableNames(clone);
    ctx.setupSingleBlock(clone);
    ctx.setupEditables(clone);
    if (ctx.isWidgetFrame(clone)) ctx.renderWidgetFrame(clone, true);
    ctx.selectBlock(clone);
    ctx.markChanged();
    ctx.pushHistory('duplicate block');
  };

  ctx.removeBlock = function(block) {
    if (!ctx.state.active) return;
    if (ctx.isBlockLocked(block)) {ctx.notify(ctx.translate('This block is locked.')); return;}
    if (!confirm(ctx.translate('Delete this block?'))) return;
    block.remove();
    if (ctx.state.selectedBlock === block) {
      ctx.state.selectedBlock = null;
      ctx.hidePanel();
    }
    ctx.markChanged();
    ctx.pushHistory('remove block');
  };

  ctx.toggleHidden = function(block) {
    if (!ctx.state.active) return;
    if (ctx.isBlockLocked(block)) {
      ctx.notify(ctx.translate('This block is locked.'));
      return;
    }
    const hidden = block.dataset.gcmsHidden === '1';
    block.dataset.gcmsHidden = hidden ? '0' : '1';
    ctx.markChanged();
    ctx.pushHistory('toggle hidden');
  };

  ctx.toggleBlockLock = function(block) {
    const locked = ctx.isBlockLocked(block);
    ctx.setBlockLocked(block, !locked);
    ctx.markChanged();
  };

  ctx.copyBlockStyle = function(block) {
    ctx.state.copiedBlockStyle = ctx.editableClassList(block);
    ctx.notify(ctx.translate('Style copied'), 'info');
  };

  ctx.pasteBlockStyle = function(block) {
    if (!ctx.state.copiedBlockStyle) {ctx.notify(ctx.translate('No style copied yet'), 'info'); return;}
    if (ctx.isBlockLocked(block)) {ctx.notify(ctx.translate('This block is locked.')); return;}
    const current = ctx.editableClassList(block);
    current.forEach(cls => block.classList.remove(cls));
    ctx.state.copiedBlockStyle.forEach(cls => block.classList.add(cls));
    ctx.markChanged();
    ctx.pushHistory('paste style');
  };
}

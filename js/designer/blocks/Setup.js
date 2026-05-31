/**
 * GCMS Designer – Block Setup & Drag/Drop
 *
 * setupBlocks(), setupSingleBlock(), selectBlock() – attaches
 * drag-and-drop reordering and selection (inspector opens via Panel).
 *
 * @filesource js/designer/blocks/Setup.js
 */

export function registerBlocksSetup(ctx) {

  ctx.setupBlocks = function() {
    ctx.blocks().forEach(ctx.setupSingleBlock);
  };

  ctx.setupSingleBlock = function(block) {
    if (block.dataset.gcmsDesignerReady === '1') return;
    block.dataset.gcmsDesignerReady = '1';
    block.draggable = true;

    block.addEventListener('click', event => {
      if (!ctx.state.active || event.target.closest('[data-editable]')) return;
      event.stopPropagation();
      ctx.selectBlock(block);
    });

    block.addEventListener('dragstart', event => {
      if (!ctx.state.active) return;
      if (ctx.isBlockLocked(block)) {
        event.preventDefault();
        ctx.notify(ctx.translate('This block is locked.'));
        return;
      }
      ctx.state.dragBlock = block;
      event.dataTransfer.effectAllowed = 'move';
    });

    block.addEventListener('dragover', event => {
      if (!ctx.state.dragBlock || ctx.state.dragBlock === block || ctx.state.dragBlock.parentElement !== block.parentElement) return;
      event.preventDefault();
      const rect = block.getBoundingClientRect();
      const before = event.clientY < rect.top + rect.height / 2;
      block.parentElement.insertBefore(ctx.state.dragBlock, before ? block : block.nextSibling);
    });

    block.addEventListener('dragend', () => {
      ctx.state.dragBlock = null;
      ctx.markChanged();
    });

    ctx.setupRepeatItems?.(block);

    // Custom blocks: double-click opens the HTML editor modal
    if (block.dataset.gcmsCustom === '1') {
      block.addEventListener('dblclick', event => {
        if (!ctx.state.active) return;
        if (event.target.closest('[data-editable]')) return;
        event.stopPropagation();
        ctx.openCustomBlockEditor?.(block);
      });
    }
    // Col-items: delete now via right-click context menu (no hover button)
  };

  ctx.selectBlock = function(block) {
    if (ctx.state.preview) return;
    ctx.blocks().forEach(item => item.classList.remove('gcms-block-selected'));
    ctx.state.selectedBlock = block;
    block.classList.add('gcms-block-selected');
    ctx.showInspector(block);
  };

  /** ถอนการเลือกบล็อก/เนื้อหาหลังเปิดโหมดแก้ไข (กัน class จาก state เก่าติด hero) */
  ctx.clearDesignerUiSelection = function() {
    ctx.blocks().forEach(item => item.classList.remove('gcms-block-selected', 'gcms-block-flash'));
    ctx.editableElements().forEach(el => el.classList.remove('gcms-editable-active', 'gcms-block-flash'));
    ctx.state.selectedBlock = null;
    ctx.state.selectedEditable = null;
    ctx.hidePanel();
  };
}

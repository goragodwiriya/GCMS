/**
 * GCMS Designer – Top Bar UI
 *
 * แถบควบคุมอยู่ใน #gcms-admin-fab → .gcms-fab-menu (ชิ้นเดียวกับเมนู FAB)
 * แสดง/ซ่อนตาม .gcms-fab-open เหมือนปุ่มเปิดเมนู
 *
 * @filesource js/designer/ui/TopBar.js
 */

export function registerTopBar(ctx) {

  ctx.buildTopBar = function() {
    const bar = document.createElement('div');
    bar.id = 'designer-bar';
    bar.setAttribute('role', 'menu');
    bar.setAttribute('aria-label', '{LNG_GCMS Designer tools}');
    bar.innerHTML = `
      <div class="designer-title">
        <span class="designer-title-icon icon-template" aria-hidden="true"></span>
        <span>
          <strong data-i18n>GCMS Designer</strong>
          <small data-i18n>Page editing tools</small>
        </span>
        <span class="designer-status" data-status data-i18n>Saved</span>
      </div>
      <div class="designer-actions">
        <div class="designer-action-section">
          <button type="button" class="designer-menu-item icon-template" data-action="insert-drawer" data-i18n>Insert</button>
          <button type="button" class="designer-menu-item icon-list" data-action="layers" data-i18n>Layers</button>
          <button type="button" class="designer-menu-item icon-brush" data-action="design" data-i18n>Design</button>
          <button type="button" class="designer-menu-item icon-settings" data-action="inspector" data-i18n>Properties</button>
        </div>
        <div class="designer-action-section">
          <button type="button" class="designer-menu-item icon-clock" data-action="history" data-i18n>History</button>
          <button type="button" class="designer-menu-item icon-valid" data-action="audit" data-i18n>Audit</button>
          <button type="button" class="designer-menu-item icon-file" data-action="transfer" data-i18n>Import / Export</button>
          <button type="button" class="designer-menu-item icon-fullscreen" data-action="preview" data-i18n>Preview</button>
        </div>
        <div class="designer-quick-actions">
          <button type="button" class="btn icon-undo" data-action="undo" title="{LNG_Undo}"></button>
          <button type="button" class="btn icon-redo" data-action="redo" title="{LNG_Redo}"></button>
          <button type="button" class="btn icon-book" data-action="draft" title="{LNG_Save Draft}"></button>
          <button type="button" class="btn icon-reset" data-action="discard" title="{LNG_Discard}"></button>
          <button type="button" class="btn btn-primary icon-save" data-action="save" title="{LNG_Save}"></button>
          <button type="button" class="btn icon-signout" data-action="exit" title="{LNG_Exit}"></button>
        </div>
      </div>`;

    bar.addEventListener('click', event => {
      event.stopPropagation();

      const action = event.target.closest('[data-action]')?.dataset.action;
      if (!action) return;

      const actionMap = {
        'insert-drawer': () => ctx.toggleInsertDrawer(),
        layers: () => ctx.showLayersPanel(),
        design: () => ctx.showDesignPanel(),
        inspector: () => ctx.showInspector(ctx.state.selectedBlock),
        history: () => ctx.showHistoryPanel(),
        audit: () => ctx.showAuditPanel(),
        transfer: () => ctx.showTransferPanel(),
        preview: () => ctx.togglePreview(),
        find: () => ctx.showFindPanel(),
        help: () => ctx.showHelpPanel(),
        undo: () => ctx.undo(),
        redo: () => ctx.redo(),
        draft: () => ctx.saveDraft(true),
        discard: () => ctx.discardUnpublished(),
        save: () => ctx.save(),
        exit: () => {
          ctx.deactivate();
          if (!ctx.state.active) {
            document.getElementById('gcms-admin-fab')?.classList.remove('gcms-fab-editing');
          }
        }
      };

      actionMap[action]?.();

      if (!['preview', 'undo', 'redo', 'draft', 'save'].includes(action)) {
        const fab = document.getElementById('gcms-admin-fab');
        fab?.classList.remove('gcms-fab-open');
        fab?.querySelector('.gcms-fab-trigger')?.setAttribute('aria-expanded', 'false');
      }
    });

    const fab = document.getElementById('gcms-admin-fab');
    const globalMenu = fab?.querySelector('.gcms-fab-menu');
    if (fab && globalMenu) {
      globalMenu.classList.add('gcms-fab-menu-designer');
      globalMenu.prepend(bar);
    } else if (fab) {
      fab.prepend(bar);
    } else {
      document.body.appendChild(bar);
    }
    ctx.state.bar = bar;
    ctx.localizeDom(bar);
    ctx.updateStatus();
  };
}

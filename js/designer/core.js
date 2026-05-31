/**
 * GCMS Designer – Core Lifecycle
 *
 * activate(), deactivate(), togglePreview(),
 * and overall page-state orchestration.
 *
 * @filesource js/designer/core.js
 */

export function registerCore(ctx) {

  ctx.activate = async function() {
    if (ctx.state.active || !ctx.isHomePage()) return;
    if (typeof window.matchMedia === 'function'
      && !window.matchMedia('(min-width: 1024px)').matches) {
      ctx.notify(ctx.translate('Theme Designer is available on large screens only.'), 'warning');
      return;
    }

    try {
      await ctx.loadSavedWorkspaceState();
    } catch (e) {
      ctx.notify(ctx.translate('Could not load saved workspace'), 'warning');
    }

    ctx.state.active = true;

    ctx.ensureBlockIds();
    ctx.ensureEditableNames();
    ctx.blocks().forEach(block => {delete block.dataset.gcmsHideDevices;});
    document.getElementById('gcms-admin-fab')?.classList.add('gcms-fab-editing');
    document.body.classList.add('gcms-home-edit-mode');
    ctx.buildTopBar();
    ctx.buildPanel();
    ctx.hidePanel();
    ctx.buildInsertDrawer();
    ctx.showInsertDrawer();
    ctx.loadUserTemplates();
    ctx.upgradeStatCounterElements?.();
    ctx.setupBlocks();
    ctx.setupEditables();
    ctx.syncAllCounterPreviews?.();
    ctx.initContextMenu?.();
    ctx.renderWidgetFrames();
    ctx.offerDraftRestore();
    ctx.state.originalSnapshot = JSON.stringify(ctx.extractState());
    ctx.pushHistory('initial');
    ctx.clearDesignerUiSelection();
  };

  ctx.deactivate = function(options = {}) {
    const force = options && options.force === true;
    if (!ctx.state.active) return;
    if (!force && ctx.state.dirty && !confirm(ctx.translate('You have unsaved changes. Exit Edit Mode?'))) {
      return;
    }
    if (ctx.state.dirty) {
      try {
        ctx.saveDraft(false);
      } catch (e) {
        /* ignore — เช่น localStorage เต็มระหว่างย่อจอ */
      }
      ctx.restoreOriginalSnapshot();
    }
    ctx.state.active = false;
    ctx.state.preview = false;
    ctx.resetCounterElements?.(document);
    document.getElementById('gcms-admin-fab')?.classList.remove('gcms-fab-editing');
    document.body.classList.remove('gcms-home-edit-mode');
    document.body.classList.remove('gcms-home-preview-mode');
    ctx.cleanEditorUi();

    ctx.editableElements().forEach(el => {
      el.removeAttribute('contenteditable');
      el.removeAttribute('draggable');
      delete el.dataset.gcmsEditableReady;
    });
    ctx.blocks().forEach(block => {
      block.removeAttribute('draggable');
      delete block.dataset.gcmsDesignerReady;
    });

    ctx.state.bar?.remove();
    document.querySelector('#gcms-admin-fab .gcms-fab-menu')?.classList.remove('gcms-fab-menu-designer');
    ctx.teardownDesignerPanelFormManager(ctx.state.panelContent);
    ctx.state.panel?.remove();
    ctx.removeInsertDrawer();
    ctx.state.bar = null;
    ctx.state.panel = null;
    ctx.state.panelTitle = null;
    ctx.state.panelContent = null;
    ctx.state.selectedBlock = null;
    ctx.state.selectedEditable = null;
    ctx.state.dirty = false;
    ctx.state.originalSnapshot = null;
    clearTimeout(ctx.state.draftTimer);
  };

  ctx.togglePreview = async function(force) {
    if (!ctx.state.active) return;

    ctx.state.preview = typeof force === 'boolean' ? force : !ctx.state.preview;
    document.body.classList.toggle('gcms-home-preview-mode', ctx.state.preview);
    if (ctx.state.preview) {
      ctx.state.panel?.classList.remove('visible');
      ctx.hideInsertDrawer?.();
      ctx.cleanEditorUi();
      await ctx.initPreviewCounters?.(document);
    } else {
      ctx.resetCounterElements?.(document);
      ctx.setupBlocks();
      ctx.setupEditables();
      ctx.syncAllCounterPreviews?.();
    }

    const button = ctx.state.bar?.querySelector('[data-action="preview"]');
    if (button) {
      button.textContent = ctx.translate(ctx.state.preview ? 'Back to Edit' : 'Preview');
      button.classList.toggle('active', ctx.state.preview);
    }
  };

  ctx.maybeResumeEditAfterDiscard = async function() {
    let shouldResume = false;
    try {
      shouldResume = sessionStorage.getItem(ctx.resumeEditKey()) === '1';
      if (shouldResume) sessionStorage.removeItem(ctx.resumeEditKey());
    } catch (e) {
      return;
    }
    if (!shouldResume || !ctx.isHomePage() || ctx.state.active) return;

    await ctx.waitForDesignerFab();
    await ctx.activate();
  };

}

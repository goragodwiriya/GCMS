/**
 * GCMS Designer – Undo / Redo / Draft Autosave
 *
 * @filesource js/designer/history.js
 */

export function registerHistory(ctx) {

  ctx.scheduleHistory = function() {
    clearTimeout(ctx.state.historyTimer);
    ctx.state.historyTimer = setTimeout(() => ctx.pushHistory('change'), 350);
  };

  ctx.pushHistory = function(label) {
    if (!ctx.state.active || ctx.state.restoring) return;

    const snapshot = JSON.stringify(ctx.extractState());
    if (ctx.state.history[ctx.state.historyIndex]?.snapshot === snapshot) return;

    ctx.state.history = ctx.state.history.slice(0, ctx.state.historyIndex + 1);
    ctx.state.history.push({label, snapshot, timestamp: Date.now()});
    if (ctx.state.history.length > 50) {
      ctx.state.history.shift();
    }
    ctx.state.historyIndex = ctx.state.history.length - 1;
  };

  ctx.restoreHistory = function(index, options = {}) {
    const item = ctx.state.history[index];
    if (!item) return;

    ctx.state.restoring = true;
    try {
      ctx.cleanEditorUi();
      const saved = JSON.parse(item.snapshot);
      ctx.applyState(saved, {replaceCustom: true});
      ctx.setupBlocks();
      ctx.setupEditables();
      ctx.renderWidgetFrames(document, true);
      ctx.state.historyIndex = index;
      ctx.state.dirty = options.markDirty !== false;
      ctx.updateStatus();
    } finally {
      ctx.state.restoring = false;
    }
  };

  ctx.restoreOriginalSnapshot = function() {
    if (!ctx.state.originalSnapshot) return false;

    ctx.state.restoring = true;
    try {
      ctx.cleanEditorUi();
      const saved = JSON.parse(ctx.state.originalSnapshot);
      ctx.applyState(saved, {replaceCustom: true});
      ctx.setupBlocks();
      ctx.setupEditables();
      ctx.renderWidgetFrames(document, true);
      ctx.state.dirty = false;
      ctx.state.history = [{
        label: 'initial',
        snapshot: ctx.state.originalSnapshot,
        timestamp: Date.now()
      }];
      ctx.state.historyIndex = 0;
      ctx.clearDesignerUiSelection();
      ctx.updateStatus();
      return true;
    } finally {
      ctx.state.restoring = false;
    }
  };

  ctx.undo = function() {
    if (ctx.state.historyIndex <= 0) return;
    ctx.restoreHistory(ctx.state.historyIndex - 1);
  };

  ctx.redo = function() {
    if (ctx.state.historyIndex >= ctx.state.history.length - 1) return;
    ctx.restoreHistory(ctx.state.historyIndex + 1);
  };

  ctx.scheduleDraftSave = function() {
    clearTimeout(ctx.state.draftTimer);
    ctx.state.draftTimer = setTimeout(() => ctx.saveDraft(false), 900);
  };

  ctx.getDraft = function() {
    try {
      const raw = localStorage.getItem(ctx.draftKey());
      return raw ? JSON.parse(raw) : null;
    } catch (e) {
      return null;
    }
  };

  ctx.clearDraft = function() {
    try {
      localStorage.removeItem(ctx.draftKey());
    } catch (e) {
      // ignore private mode/storage errors
    }
  };

  ctx.offerDraftRestore = function() {
    try {
      if (sessionStorage.getItem(ctx.skipDraftRestoreKey())) {
        sessionStorage.removeItem(ctx.skipDraftRestoreKey());
        ctx.clearDraft();
        return;
      }
    } catch (e) {
      // ignore private mode/storage errors
    }

    const draft = ctx.getDraft();
    if (!draft?.state) return;

    const when = draft.savedAt ? new Date(draft.savedAt).toLocaleString() : '';
    const msg = when
      ? ctx.translate('An unpublished draft was found ({when}). Restore it?', {when})
      : ctx.translate('An unpublished draft was found. Restore it?');
    if (!confirm(msg)) return;

    ctx.state.restoring = true;
    try {
      ctx.cleanEditorUi();
      ctx.applyState(draft.state, {replaceCustom: true});
      ctx.setupBlocks();
      ctx.setupEditables();
      ctx.state.dirty = true;
      ctx.updateStatus();
    } finally {
      ctx.state.restoring = false;
    }
    ctx.pushHistory('draft');
  };

  ctx.discardUnpublished = function() {
    if (!ctx.state.active) return;

    const hasDraft = !!ctx.getDraft()?.state;
    if (!ctx.state.dirty && !hasDraft) {
      ctx.notify(ctx.translate('No unpublished changes to discard'));
      return;
    }

    if (!confirm(ctx.translate('Discard all changes and reload the original page?'))) return;

    clearTimeout(ctx.state.draftTimer);
    ctx.state.draftTimer = null;
    ctx.clearDraft();
    ctx.state.discarding = true;

    try {
      sessionStorage.setItem(ctx.skipDraftRestoreKey(), '1');
      sessionStorage.setItem(ctx.resumeEditKey(), '1');
    } catch (e) {
      // ignore private mode/storage errors
    }

    location.reload();
  };
}

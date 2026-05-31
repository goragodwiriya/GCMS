/**
 * GCMS Designer – Entry Point / Orchestrator
 *
 * Imports every module, registers all functions into a single `ctx`
 * object backed by `state`, then exposes `Designer` globally.
 *
 * Load order mirrors the original IIFE: utils → api → fonts → state helpers
 *   → history → save → widgets → blocks → inspector → panels → ui → core.
 *
 * @filesource js/designer/entry.js
 */

import './index.css';

import {state} from './state.js';
import {registerUtils} from './utils.js';
import {registerApi} from './api.js';
import {registerFonts} from './fonts.js';
import {registerHistory} from './history.js';
import {registerSave} from './save.js';
import {registerWidgets} from './widgets.js';

import {registerBlocksSetup} from './blocks/Setup.js';
import {registerBlocksOperations} from './blocks/Operations.js';
import {registerEditables} from './blocks/Editables.js';
import {registerCounters} from './blocks/Counters.js';
import {registerRepeatable} from './blocks/Repeatable.js';

import {registerInspector} from './inspector/index.js';

import {registerDropZones} from './blocks/DropZones.js';

import {registerTemplatesPanel} from './panels/Templates.js';
import {registerWidgetsPanel} from './panels/Widgets.js';
import {registerElementsPanel} from './panels/Elements.js';
import {registerLayersPanel} from './panels/Layers.js';
import {registerDesignPanel} from './panels/Design.js';
import {registerAuditPanel} from './panels/Audit.js';
import {registerTransferPanel} from './panels/Transfer.js';
import {registerHistoryPanel} from './panels/HistoryPanel.js';

import {registerTopBar} from './ui/TopBar.js';
import {registerPanel} from './ui/Panel.js';
import {registerInsertDrawer} from './ui/InsertDrawer.js';
import {registerCustomBlockEditor} from './ui/CustomBlockEditor.js';
import {registerContextMenu} from './ui/ContextMenu.js';

import {registerCore} from './core.js';

/* ─────────────────────────────────────────────
   Build shared context
   ───────────────────────────────────────────── */

const ctx = {state};

registerUtils(ctx);
registerApi(ctx);
registerFonts(ctx);
registerHistory(ctx);
registerSave(ctx);
registerWidgets(ctx);

registerBlocksSetup(ctx);
registerBlocksOperations(ctx);
registerEditables(ctx);
registerCounters(ctx);
registerRepeatable(ctx);
registerDropZones(ctx);

registerInspector(ctx);

registerTemplatesPanel(ctx);
registerWidgetsPanel(ctx);
registerElementsPanel(ctx);
registerLayersPanel(ctx);
registerDesignPanel(ctx);
registerAuditPanel(ctx);
registerTransferPanel(ctx);
registerHistoryPanel(ctx);

registerTopBar(ctx);
registerPanel(ctx);
registerInsertDrawer(ctx);
registerCustomBlockEditor(ctx);
registerContextMenu(ctx);

registerCore(ctx);

/* ─────────────────────────────────────────────
   Global keyboard shortcuts
   ───────────────────────────────────────────── */

window.addEventListener('keydown', event => {
  if (!state.active) {
    if (event.key === '?' && !ctx.isTypingInField(event.target)) {
      if (typeof window.matchMedia === 'function'
        && !window.matchMedia('(min-width: 1024px)').matches) {
        return;
      }
      ctx.showHelpPanel();
    }
    return;
  }

  if (event.key === 'Escape') {
    if (state.preview) {ctx.togglePreview(false); return;}
    if (state.panel?.classList.contains('visible')) {ctx.hidePanel(); return;}
    if (document.getElementById('gcms-insert-drawer')?.classList.contains('visible')) {ctx.hideInsertDrawer(); return;}
    return;
  }

  if (!ctx.isTypingInField(event.target) && event.altKey && !(event.ctrlKey || event.metaKey)) {
    const ak = event.key.toLowerCase();
    if (event.shiftKey) {
      const shiftMap = {d: 'showDesignPanel', b: () => ctx.showInspector(state.selectedBlock), h: 'showHistoryPanel', a: 'showAuditPanel', e: 'showTransferPanel'};
      const action = shiftMap[ak];
      if (action) {event.preventDefault(); typeof action === 'string' ? ctx[action]() : action(); return;}
    }
    if (ak === 't') {event.preventDefault(); ctx.showTemplatesPanel(); return;}
    if (ak === 'l') {event.preventDefault(); ctx.showLayersPanel(); return;}
    if (ak === '?') {event.preventDefault(); ctx.showHelpPanel(); return;}
  }

  const meta = event.ctrlKey || event.metaKey;
  if (!ctx.isTypingInField(event.target) && state.selectedBlock) {
    if (event.altKey && event.key === 'ArrowUp') {event.preventDefault(); ctx.moveBlock(state.selectedBlock, -1); return;}
    if (event.altKey && event.key === 'ArrowDown') {event.preventDefault(); ctx.moveBlock(state.selectedBlock, 1); return;}
    if (event.key === 'Delete' && !meta) {event.preventDefault(); ctx.removeBlock(state.selectedBlock); return;}
  }

  if (!meta) return;
  const key = event.key.toLowerCase();
  if (key === 's') {event.preventDefault(); ctx.save();}
  else if (key === 'f' && !ctx.isTypingInField(event.target)) {event.preventDefault(); ctx.showFindPanel();}
  else if (key === 'd' && state.selectedBlock && !ctx.isTypingInField(event.target)) {event.preventDefault(); ctx.duplicateBlock(state.selectedBlock);}
  else if (key === 'z' && event.shiftKey) {event.preventDefault(); ctx.redo();}
  else if (key === 'z') {event.preventDefault(); ctx.undo();}
});

window.addEventListener('beforeunload', event => {
  if (!state.active || !state.dirty || state.discarding) return;
  ctx.saveDraft(false);
  event.preventDefault();
  event.returnValue = '';
});

/* ─────────────────────────────────────────────
   Public API
   ───────────────────────────────────────────── */

const Designer = {
  isActive: () => state.active,
  activate: () => ctx.activate(),
  deactivate: (options) => ctx.deactivate(options),
  togglePreview: (v) => ctx.togglePreview(v),
  save: () => ctx.save(),
  applySavedState: () => ctx.applySavedState(),
  resumeEditIfRequested: () => ctx.maybeResumeEditAfterDiscard()
};

window.Designer = Designer;
export default Designer;

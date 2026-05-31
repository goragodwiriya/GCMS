/**
 * GCMS Designer – State Extract, Apply, Save & Draft
 *
 * @filesource js/designer/save.js
 */

export function registerSave(ctx) {

  ctx.extractState = function() {
    ctx.ensureBlockIds();
    ctx.ensureEditableNames();

    const data = {
      version: 1,
      theme: ctx.getTheme(),
      page: 'home',
      updatedAt: new Date().toISOString(),
      cssVars: {...ctx.state.cssVars},
      bodyClasses: [...(ctx.state.bodyClasses || [])],
      googleFonts: ctx.collectFontFamilyNamesFromHomepageDom(),
      containers: {},
      blocks: {},
      editables: {},
      customBlocks: {}
    };

    ctx.containerList().forEach(container => {
      const id = container.dataset.editorContainer;
      data.containers[id] = Array.from(container.children)
        .filter(child => child.matches?.('[data-block-type]'))
        .map(child => child.dataset.editorId);
    });

    ctx.blocks().forEach(block => {
      const id = block.dataset.editorId;
      data.blocks[id] = {
        hidden: block.dataset.gcmsHidden === '1',
        locked: ctx.isBlockLocked(block),
        label: block.dataset.editorLabel || '',
        className: ctx.stripTransientDesignerClasses(block.className),
        style: block.getAttribute('style') || '',
        attrs: ctx.extractBlockAttrs(block)
      };
      if (block.dataset.gcmsCustom === '1') {
        // Skip blocks that are nested inside another custom block — they are
        // serialised as part of their ancestor's outerHTML and must not be
        // restored separately (would land in the wrong container).
        const ancestorCustom = block.parentElement?.closest('[data-gcms-custom="1"]');
        if (ancestorCustom) return;

        const clone = block.cloneNode(true);
        ctx.prepareRestoredBlock(clone, {forStorage: true});
        data.customBlocks[id] = {
          id,
          container: block.parentElement?.dataset.editorContainer || 'content',
          html: clone.outerHTML
        };
      }
    });

    ctx.editableElements().forEach(el => {
      if (ctx.isInsideCustomBlock(el)) return;
      const field = el.dataset.editorFieldName;
      if (!field) return;
      if (el.matches('img')) {
        data.editables[field] = {
          src: el.getAttribute('src') || '',
          alt: el.getAttribute('alt') || '',
          className: ctx.stripTransientDesignerClasses(el.className || ''),
          style: el.getAttribute('style') || ''
        };
      } else {
        data.editables[field] = {
          html: ctx.sanitizeInlineHtml(el.innerHTML),
          className: ctx.stripTransientDesignerClasses(el.className || ''),
          style: el.getAttribute('style') || '',
          attrs: ctx.extractEditableAttrs(el)
        };
      }
    });

    ctx.counterElements?.().forEach(el => {
      if (ctx.isInsideCustomBlock(el)) return;
      const field = el.dataset.editorFieldName;
      if (!field) return;
      data.editables[field] = {
        html: '',
        className: ctx.stripTransientDesignerClasses(el.className || ''),
        style: el.getAttribute('style') || '',
        attrs: ctx.extractCounterEditableAttrs(el)
      };
    });

    return data;
  };

  ctx.syncContainersFromSaved = function(saved, options = {}) {
    if (!saved?.containers) return;

    const replaceCustom = options.replaceCustom === true;

    Object.entries(saved.containers).forEach(([containerId, order]) => {
      if (!Array.isArray(order)) return;
      const container = document.querySelector(`[data-editor-container="${ctx.selectorValue(containerId)}"]`);
      if (!container) return;

      const savedIds = new Set(order.filter(Boolean));

      if (replaceCustom) {
        [...container.children].forEach(child => {
          if (!child.matches?.('[data-block-type]')) return;
          if (!savedIds.has(child.dataset.editorId)) {
            child.remove();
          }
        });
      }

      order.forEach(blockId => {
        if (!blockId) return;
        const custom = saved.customBlocks?.[blockId];
        let block = container.querySelector(`[data-editor-id="${ctx.selectorValue(blockId)}"]`)
          || document.querySelector(`[data-editor-id="${ctx.selectorValue(blockId)}"]`);

        if (custom?.html) {
          const fresh = ctx.createElementFromHtml(custom.html);
          fresh.dataset.editorId = blockId;
          fresh.dataset.gcmsCustom = '1';
          ctx.prepareRestoredBlock(fresh);
          if (block) {
            block.replaceWith(fresh);
            block = fresh;
          } else {
            container.appendChild(fresh);
            block = fresh;
          }
        }

        if (block && block.parentElement === container) {
          container.appendChild(block);
        }
      });
    });

    if (!replaceCustom || !saved.customBlocks) return;

    Object.values(saved.customBlocks).forEach(item => {
      if (!item?.id || !item?.html) return;
      if (document.querySelector(`[data-editor-id="${ctx.selectorValue(item.id)}"]`)) return;
      const container = document.querySelector(`[data-editor-container="${item.container || 'content'}"]`)
        || document.querySelector('[data-editor-container="content"]');
      if (!container) return;
      const block = ctx.createElementFromHtml(item.html);
      block.dataset.editorId = item.id;
      block.dataset.gcmsCustom = '1';
      ctx.prepareRestoredBlock(block);
      container.appendChild(block);
    });
  };

  ctx.applyState = function(saved, options = {}) {
    ctx.ensureBlockIds();
    ctx.applyFontsFromSavedState(saved);

    if (saved.cssVars && typeof saved.cssVars === 'object') {
      ctx.state.cssVars = {...saved.cssVars};
      ctx.applyCssVars(ctx.state.cssVars);
    }

    if (Array.isArray(saved.bodyClasses)) {
      ctx.applyBodyClasses?.(saved.bodyClasses);
    }

    if (options.replaceCustom && saved.containers) {
      ctx.syncContainersFromSaved(saved, {replaceCustom: true});
    } else if (options.replaceCustom && saved.customBlocks) {
      ctx.blocks().forEach(block => {
        if (ctx.isTopLevelCustomBlock(block)) {
          block.remove();
        }
      });
      Object.values(saved.customBlocks).forEach(item => {
        if (!item?.id || !item?.html) return;
        const container = document.querySelector(`[data-editor-container="${item.container || 'content'}"]`) || document.querySelector('[data-editor-container="content"]');
        if (!container) return;
        const block = ctx.createElementFromHtml(item.html);
        block.dataset.editorId = item.id;
        block.dataset.gcmsCustom = '1';
        ctx.prepareRestoredBlock(block);
        container.appendChild(block);
      });
    } else if (saved.customBlocks) {
      Object.values(saved.customBlocks).forEach(item => {
        if (!item?.id || !item?.html) return;
        const existing = document.querySelector(`[data-editor-id="${ctx.selectorValue(item.id)}"]`);
        if (existing) return;
        const container = document.querySelector(`[data-editor-container="${item.container || 'content'}"]`) || document.querySelector('[data-editor-container="content"]');
        if (!container) return;
        const block = ctx.createElementFromHtml(item.html);
        block.dataset.editorId = item.id;
        block.dataset.gcmsCustom = '1';
        ctx.prepareRestoredBlock(block);
        container.appendChild(block);
      });
    }

    if (saved.editables) {
      Object.entries(saved.editables).forEach(([field, value]) => {
        const el = document.querySelector(`[data-editor-field-name="${ctx.selectorValue(field)}"]`);
        if (!el || !value || ctx.isInsideCustomBlock(el)) return;

        if (el.matches('img')) {
          if (value.src) el.setAttribute('src', value.src);
          if (value.alt !== undefined) el.setAttribute('alt', value.alt);
        } else if (value.html !== undefined) {
          el.innerHTML = ctx.sanitizeInlineHtml(value.html);
        }
        if (value.style !== undefined) el.setAttribute('style', value.style);
        if (value.className !== undefined) el.className = ctx.stripTransientDesignerClasses(value.className);
        if (value.attrs && typeof value.attrs === 'object') {
          Object.entries(value.attrs).forEach(([name, attrValue]) => {
            if (attrValue === null || attrValue === undefined || attrValue === '') {
              el.removeAttribute(name);
            } else {
              el.setAttribute(name, attrValue);
            }
          });
        }
        if (el.dataset.editable === 'counter' || el.matches('[data-component="counter"]')) {
          ctx.syncCounterPreview?.(el);
        }
      });
    }

    if (saved.blocks) {
      Object.entries(saved.blocks).forEach(([id, value]) => {
        const block = document.querySelector(`[data-editor-id="${ctx.selectorValue(id)}"]`);
        if (!block || !value) return;

        block.dataset.gcmsHidden = value.hidden ? '1' : '0';
        block.dataset.gcmsLocked = value.locked ? '1' : '0';
        if (value.className !== undefined) block.className = ctx.stripTransientDesignerClasses(value.className);
        if (value.label !== undefined) block.dataset.editorLabel = value.label;
        if (value.style !== undefined) block.setAttribute('style', value.style);
        if (value.attrs && typeof value.attrs === 'object') ctx.applyBlockAttrs(block, value.attrs);
      });
    }

    if (saved.containers && !options.replaceCustom) {
      Object.entries(saved.containers).forEach(([containerId, order]) => {
        const container = document.querySelector(`[data-editor-container="${ctx.selectorValue(containerId)}"]`);
        if (!container || !Array.isArray(order)) return;
        order.forEach(blockId => {
          const block = container.querySelector(`[data-editor-id="${ctx.selectorValue(blockId)}"]`) || document.querySelector(`[data-editor-id="${ctx.selectorValue(blockId)}"]`);
          if (block && block.parentElement === container) container.appendChild(block);
        });
      });
    }

    ctx.blocks().forEach(block => {delete block.dataset.gcmsHideDevices;});

    ctx.preloadGoogleFontsForHomepage();
    ctx.renderWidgetFrames(document, true);
    ctx.upgradeStatCounterElements?.(document);
    ctx.syncAllCounterPreviews?.(document);
  };

  ctx.save = async function(options = {}) {
    if (ctx.state.saving) {
      ctx.notify(ctx.translate('Saving…'));
      return;
    }

    try {
      const issues = ctx.collectAuditIssues();
      if (!options.skipAudit && issues.length > 0) {
        ctx.showAuditPanel();
        const shouldContinue = confirm(ctx.translate('Page Audit found {count} item(s). Continue saving?', {count: issues.length}));
        if (!shouldContinue) return;
      }

      const payload = {
        theme: ctx.getTheme(),
        page: 'home',
        state: ctx.extractState()
      };
      ctx.setSaving(true);
      const result = await ctx.fetchJson(ctx.webUrl() + 'api/designer/home/save', {
        method: 'POST',
        body: JSON.stringify(payload)
      });
      if (!result?.success) {
        alert(result?.message || ctx.translate('Save failed'));
        return;
      }
      ctx.notify(ctx.translate('Designer workspace saved'));
      ctx.state.dirty = false;
      ctx.state.workspaceLoaded = true;
      ctx.state.workspaceStateAvailable = true;
      ctx.state.lastSavedAt = result.savedAt || new Date().toISOString();
      ctx.clearDraft();
      ctx.updateStatus();
    } catch (e) {
      alert(`${ctx.translate('Save failed')}: ${e.message}`);
    } finally {
      ctx.setSaving(false);
    }
  };

  ctx.saveDraft = function(showNotice) {
    if (!ctx.state.active) return;

    try {
      localStorage.setItem(ctx.draftKey(), JSON.stringify({
        state: ctx.extractState(),
        savedAt: new Date().toISOString()
      }));
      if (showNotice) ctx.notify(ctx.translate('Draft saved locally'));
    } catch (e) {
      if (showNotice) alert(ctx.translate('Could not save draft locally'));
    }
  };

  ctx.downloadStateJson = function() {
    const data = ctx.extractState();
    const json = JSON.stringify(data, null, 2);
    const blob = new Blob([json], {type: 'application/json'});
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = `gcms-home-${ctx.getTheme()}-${new Date().toISOString().slice(0, 10)}.json`;
    document.body.appendChild(a);
    a.click();
    a.remove();
    URL.revokeObjectURL(url);
  };

  ctx.exportThemeHtml = function() {
    const root = ctx.homepageDesignerRoot();
    if (!root) return '';

    const clone = root.cloneNode(true);
    const stripElement = el => {
      if (!el?.classList) return;
      el.classList.remove('gcms-block-selected', 'gcms-block-flash', 'gcms-editable-active');
      el.removeAttribute('contenteditable');
      el.removeAttribute('draggable');
      delete el.dataset.gcmsEditableReady;
      delete el.dataset.gcmsDesignerReady;
      delete el.dataset.widgetRendered;
      delete el.dataset.gcmsLocked;
      delete el.dataset.gcmsHideDevices;

      if (el.dataset.gcmsHidden === '1') {
        el.setAttribute('hidden', 'hidden');
        if (!/display\s*:/.test(el.getAttribute('style') || '')) {
          el.style.display = 'none';
        }
      } else {
        el.removeAttribute('hidden');
      }
    };

    ctx.cleanEditorUi(clone);
    ctx.stripCounterElementsForStorage?.(clone);
    clone.querySelectorAll('[data-widget]').forEach(block => {
      const widget = String(block.dataset.widget || '').replace(/[^a-z0-9_]/gi, '').toUpperCase();
      const mount = block.querySelector('[data-widget-mount]');
      if (!widget || !mount) return;
      const params = String(block.dataset.widgetParams || '').trim();
      mount.textContent = params ? `{WIDGET_${widget} ${params}}` : `{WIDGET_${widget}}`;
      delete mount.dataset.widgetRendered;
    });
    [clone, ...clone.querySelectorAll('*')].forEach(stripElement);

    clone.querySelectorAll('#gcms-admin-fab, #designer-bar, #gcms-designer-panel, .gcms-block-tools, .gcms-drop-placeholder, .gcms-repeat-remove, .gcms-repeat-add').forEach(el => el.remove());

    return clone.outerHTML.trim();
  };

  ctx.saveThemeFiles = async function(options = {}) {
    const html = ctx.exportThemeHtml();
    if (!html) {
      throw new Error(ctx.translate('Could not capture homepage HTML'));
    }

    const payload = {
      theme: ctx.getTheme(),
      page: 'home',
      mode: options.mode === 'overwrite' ? 'overwrite' : 'new',
      targetTheme: String(options.targetTheme || '').trim(),
      themeName: String(options.themeName || '').trim(),
      description: String(options.description || '').trim(),
      html,
      cssVars: {...ctx.state.cssVars},
      googleFonts: ctx.collectFontFamilyNamesFromHomepageDom()
    };

    const result = await ctx.fetchJson(ctx.webUrl() + 'api/designer/home/publish', {
      method: 'POST',
      body: JSON.stringify(payload)
    });

    if (!result?.success) {
      throw new Error(result?.message || ctx.translate('Theme files could not be saved'));
    }

    ctx.notify(ctx.translate('Theme files saved. Activate the theme from Themes to use the new homepage.'));
    return result;
  };

  ctx.getThemePublishInfo = async function(theme = ctx.getTheme()) {
    const url = `${ctx.webUrl()}api/designer/home/themeinfo?theme=${encodeURIComponent(theme)}`;
    const result = await ctx.fetchJson(url, {method: 'GET'});

    if (!result?.success) {
      throw new Error(result?.message || ctx.translate('Could not load current theme details'));
    }

    return result;
  };

  ctx.restoreThemeToSource = async function(theme = ctx.getTheme()) {
    const result = await ctx.fetchJson(ctx.webUrl() + 'api/designer/home/restoretheme', {
      method: 'POST',
      body: JSON.stringify({theme})
    });

    if (!result?.success) {
      throw new Error(result?.message || ctx.translate('Theme reset failed'));
    }

    sessionStorage.setItem(ctx.resumeEditKey(), '1');
    ctx.notify(ctx.translate('Theme files restored from the source theme.'));
    setTimeout(() => location.reload(), 700);
    return result;
  };

  ctx.importStateJson = function(raw) {
    if (!raw) {
      alert(ctx.translate('Please select a file or paste JSON first'));
      return;
    }
    const data = JSON.parse(raw);
    if (!data || typeof data !== 'object' || data.page !== 'home') {
      alert(ctx.translate('This JSON is not a home designer state file'));
      return;
    }
    if (ctx.state.dirty && !confirm(ctx.translate('You have unsaved edits. Import and replace the editor content?'))) return;

    ctx.state.restoring = true;
    try {
      ctx.cleanEditorUi();
      ctx.applyState(data, {replaceCustom: true});
      ctx.setupBlocks();
      ctx.setupEditables();
      ctx.state.dirty = true;
      ctx.updateStatus();
    } finally {
      ctx.state.restoring = false;
    }
    ctx.pushHistory('import');
    ctx.notify(ctx.translate('Imported into the editor. Save the workspace or export a theme.'));
  };

  ctx.resetPublishedState = async function() {
    const message = ctx.translate('Delete saved designer workspace? The editor will revert to the theme template after reload.');
    if (!confirm(message)) return;

    try {
      const result = await ctx.fetchJson(ctx.webUrl() + 'api/designer/home/reset', {
        method: 'POST',
        body: JSON.stringify({theme: ctx.getTheme(), page: 'home'})
      });
      if (!result?.success) {
        alert(result?.message || ctx.translate('Reset failed'));
        return;
      }
      ctx.state.workspaceLoaded = true;
      ctx.state.workspaceStateAvailable = false;
      ctx.state.lastSavedAt = null;
      ctx.clearDraft();
      ctx.notify(ctx.translate('Designer workspace was reset'));
      setTimeout(() => location.reload(), 700);
    } catch (e) {
      alert(`${ctx.translate('Reset failed')}: ${e.message}`);
    }
  };

  ctx.restoreServerRevision = async function(id) {
    if (!id) return;
    if (ctx.state.dirty && !confirm(ctx.translate('You have unsaved edits. Restore this revision in the editor?'))) return;

    try {
      const url = `${ctx.webUrl()}api/designer/home/version?theme=${encodeURIComponent(ctx.getTheme())}&page=home&id=${encodeURIComponent(id)}`;
      const result = await ctx.fetchJson(url, {method: 'GET'});
      if (!result?.success || !result.state) {
        alert(result?.message || ctx.translate('Could not load revision'));
        return;
      }

      ctx.state.restoring = true;
      try {
        ctx.cleanEditorUi();
        ctx.applyState(result.state, {replaceCustom: true});
        ctx.setupBlocks();
        ctx.setupEditables();
        ctx.state.dirty = true;
        ctx.updateStatus();
      } finally {
        ctx.state.restoring = false;
      }
      ctx.pushHistory('server-revision');
      ctx.notify(ctx.translate('Revision loaded into the editor. Save the workspace or export a theme.'));
    } catch (e) {
      alert(`${ctx.translate('Restore failed')}: ${e.message}`);
    }
  };

  ctx.loadPublishedHomeState = async function() {
    const url = `${ctx.webUrl()}api/designer/home/load?theme=${encodeURIComponent(ctx.getTheme())}&page=home`;
    const result = await ctx.fetchJson(url, {method: 'GET'});
    if (!result?.success) return null;
    return result;
  };

  ctx.loadSavedWorkspaceState = async function(options = {}) {
    if (!ctx.isHomePage()) return false;
    if (ctx.state.workspaceLoaded && !options.force) {
      return ctx.state.workspaceStateAvailable;
    }

    const result = await ctx.loadPublishedHomeState();
    ctx.state.workspaceLoaded = true;
    ctx.state.workspaceStateAvailable = !!result?.state;
    ctx.state.lastSavedAt = result?.savedAt || null;

    if (!result?.state) return false;

    ctx.cleanEditorUi();
    ctx.applyState(result.state, {replaceCustom: true});
    if (ctx.state.active) {
      ctx.setupBlocks();
      ctx.setupEditables();
    }
    return true;
  };

  ctx.applySavedState = async function() {
    try {
      await ctx.loadSavedWorkspaceState();
    } catch (e) {
      // Editor workspace loading is non-fatal.
    }
  };
}

/**
 * GCMS Designer – Transfer Panel
 *
 * Import / Export designer state as JSON, and reset published state.
 *
 * @filesource js/designer/panels/Transfer.js
 */

export function registerTransferPanel(ctx) {

  ctx.showTransferPanel = function() {
    const wrap = document.createElement('div');
    const defaultSlug = `${ctx.getTheme()}-designer`;
    const defaultName = ctx.getTheme().replace(/[-_]+/g, ' ').replace(/\b\w/g, ch => ch.toUpperCase()) + ' Designer';
    const overwriteModeLabel = ctx.translate('Save current theme');
    wrap.innerHTML = `
      <div class="gcms-panel-section" data-ai-theme-info hidden>
        <h4 data-i18n>AI-Generated Theme</h4>
        <p class="gcms-hint-text" data-ai-prompt-display></p>
        <p class="gcms-hint-text" data-ai-layout-note hidden data-i18n>Includes AI-generated homepage layout.</p>
      </div>
      <div class="gcms-panel-section">
        <h4 data-i18n>Export</h4>
        <p data-i18n>Download the homepage state as JSON for backup or moving to another environment.</p>
        <button type="button" class="btn btn-primary" data-action="export-json" data-i18n>Export JSON</button>
      </div>
      <div class="gcms-panel-section">
        <h4 data-i18n>Import</h4>
        <p data-i18n>Import loads state into the editor only. Save the workspace or export a theme when ready.</p>
        <div>
          <label data-i18n>Select JSON file</label>
          <span class="form-control icon-upload">
            <input type="file" accept="application/json,.json" data-import-file>
          </span>
        </div>
        <div>
          <label data-i18n>Or paste JSON</label>
          <span class="form-control icon-file">
            <textarea rows="8" data-import-text placeholder='{"version":1,...}'></textarea>
          </span>
        </div>
        <button type="button" class="btn btn-secondary" data-action="import-json" data-i18n>Import into editor</button>
      </div>
      <div class="gcms-panel-section">
        <h4 data-i18n>Save as Theme</h4>
        <p data-i18n>Designer changes are written into theme files. The public website changes only after you activate that theme.</p>
        <div>
          <label data-i18n>Save mode</label>
          <span class="form-control icon-menus">
            <select data-theme-save-mode>
              <option value="new" data-i18n>Create new theme</option>
              <option value="overwrite">${ctx.escapeHtml(overwriteModeLabel)}</option>
            </select>
          </span>
        </div>
        <div data-theme-slug-field>
          <label data-i18n>Theme slug</label>
          <span class="form-control icon-edit">
            <input type="text" data-theme-slug value="${ctx.escapeAttr(defaultSlug)}" placeholder="wk-designer-variant">
          </span>
        </div>
        <div>
          <label data-i18n>Theme name</label>
          <span class="form-control icon-edit">
            <input type="text" data-theme-name placeholder="${ctx.escapeHtml(defaultName)}">
          </span>
        </div>
        <div>
          <label data-i18n>Description</label>
          <span class="form-control icon-file">
            <textarea rows="3" data-theme-description></textarea>
          </span>
        </div>
        <p class="gcms-hint-text" data-theme-save-hint></p>
        <button type="button" class="btn btn-primary" data-action="save-theme" data-i18n>Save theme files</button>
      </div>
      <div class="gcms-panel-section" data-theme-restore-section hidden>
        <h4 data-i18n>Reset Theme Files</h4>
        <p class="gcms-hint-text" data-theme-restore-hint></p>
        <button type="button" class="btn btn-danger" data-action="restore-theme" data-i18n>Reset current theme to source</button>
      </div>
      <div class="gcms-panel-section">
        <h4 data-i18n>Reset saved workspace</h4>
        <p data-i18n>Remove the saved workspace to fall back to the theme homepage in the editor. The current revision is backed up first.</p>
        <button type="button" class="btn btn-danger" data-action="reset-published" data-i18n>Reset saved workspace</button>
      </div>`;

    wrap.querySelector('[data-action="export-json"]').addEventListener('click', ctx.downloadStateJson);
    wrap.querySelector('[data-action="import-json"]').addEventListener('click', async () => {
      const file = wrap.querySelector('[data-import-file]').files?.[0];
      const text = wrap.querySelector('[data-import-text]').value.trim();
      try {
        const raw = file ? await file.text() : text;
        ctx.importStateJson(raw);
      } catch (e) {
        alert(ctx.translate('Import failed: {message}', {message: e.message}));
      }
    });
    const mode = wrap.querySelector('[data-theme-save-mode]');
    const slugField = wrap.querySelector('[data-theme-slug-field]');
    const slugInput = wrap.querySelector('[data-theme-slug]');
    const nameInput = wrap.querySelector('[data-theme-name]');
    const descriptionInput = wrap.querySelector('[data-theme-description]');
    const saveThemeButton = wrap.querySelector('[data-action="save-theme"]');
    const saveThemeHint = wrap.querySelector('[data-theme-save-hint]');
    const overwriteOption = mode.querySelector('option[value="overwrite"]');
    const restoreSection = wrap.querySelector('[data-theme-restore-section]');
    const restoreHint = wrap.querySelector('[data-theme-restore-hint]');
    const restoreButton = wrap.querySelector('[data-action="restore-theme"]');
    let themeInfo = null;
    let lastSuggestedName = defaultName;

    const applySuggestedName = nextValue => {
      const currentValue = nameInput.value.trim();
      const userEdited = nameInput.dataset.userEdited === '1';
      if (!userEdited || currentValue === '' || currentValue === lastSuggestedName) {
        nameInput.value = nextValue;
      }
      lastSuggestedName = nextValue;
      nameInput.dataset.userEdited = nameInput.value.trim() === lastSuggestedName ? '0' : '1';
    };

    nameInput.addEventListener('input', () => {
      nameInput.dataset.userEdited = nameInput.value.trim() === lastSuggestedName ? '0' : '1';
    });

    const updateThemeMode = () => {
      const overwrite = mode.value === 'overwrite';
      const canOverwrite = Boolean(themeInfo?.can_overwrite);
      const currentLabel = themeInfo?.label || ctx.getTheme();
      const sourceLabel = themeInfo?.source_label || themeInfo?.source_theme || currentLabel;
      const newThemeLabel = themeInfo?.label ? `${themeInfo.label} Designer` : defaultName;

      overwriteOption.hidden = !canOverwrite;
      overwriteOption.disabled = !canOverwrite;
      if (overwrite && !canOverwrite) {
        mode.value = 'new';
      }

      slugField.hidden = overwrite;
      saveThemeHint.textContent = overwrite
        ? ctx.translate('Save changes back into the current derivative theme. Original themes stay protected.')
        : ctx.translate('Create a new theme from the current one, then activate it from Themes when ready.');

      applySuggestedName(overwrite ? currentLabel : newThemeLabel);

      restoreSection.hidden = !themeInfo?.can_reset_to_source;
      if (themeInfo?.can_reset_to_source) {
        restoreHint.textContent = ctx.translate('Restore the current theme files from {source}. A backup of the current theme files is saved first.', {source: sourceLabel});
      }

      // Pre-fill description from AI prompt when creating a new derived theme
      if (!overwrite && themeInfo?.is_ai_theme && themeInfo?.ai_prompt && !descriptionInput.value.trim()) {
        descriptionInput.value = themeInfo.ai_prompt;
      }
    };

    mode.addEventListener('change', updateThemeMode);
    updateThemeMode();

    saveThemeButton.addEventListener('click', async () => {
      const originalText = saveThemeButton.textContent;
      saveThemeButton.disabled = true;
      saveThemeButton.textContent = ctx.translate('Saving…');

      try {
        const result = await ctx.saveThemeFiles({
          mode: mode.value,
          targetTheme: slugInput.value,
          themeName: nameInput.value,
          description: descriptionInput.value
        });
        if (result?.theme) {
          slugInput.value = result.theme;
        }
      } catch (e) {
        alert(ctx.translate(e.message || 'Theme files could not be saved'));
      } finally {
        saveThemeButton.disabled = false;
        saveThemeButton.textContent = originalText;
      }
    });
    restoreButton.addEventListener('click', async () => {
      const currentLabel = themeInfo?.label || ctx.getTheme();
      const sourceLabel = themeInfo?.source_label || themeInfo?.source_theme || ctx.translate('the source theme');
      const message = ctx.translate('Reset {theme} from {source}? The current theme files will be backed up first.', {
        theme: currentLabel,
        source: sourceLabel
      });
      if (!confirm(message)) return;

      const originalText = restoreButton.textContent;
      restoreButton.disabled = true;
      restoreButton.textContent = ctx.translate('Resetting…');

      try {
        await ctx.restoreThemeToSource();
      } catch (e) {
        alert(ctx.translate(e.message || 'Theme reset failed'));
      } finally {
        restoreButton.disabled = false;
        restoreButton.textContent = originalText;
      }
    });
    wrap.querySelector('[data-action="reset-published"]').addEventListener('click', ctx.resetPublishedState);

    ctx.showPanel('{LNG_Import}/{LNG_Export}', wrap);

    (async () => {
      try {
        const info = await ctx.getThemePublishInfo();
        if (!wrap.isConnected) return;

        themeInfo = info;
        if (themeInfo?.default_mode === 'overwrite' || themeInfo?.default_mode === 'new') {
          mode.value = themeInfo.default_mode;
        }
        updateThemeMode();

        if (info?.is_ai_theme) {
          const aiSection = wrap.querySelector('[data-ai-theme-info]');
          const promptDisplay = wrap.querySelector('[data-ai-prompt-display]');
          const layoutNote = wrap.querySelector('[data-ai-layout-note]');
          if (info.ai_prompt) {
            promptDisplay.textContent = ctx.translate('Original prompt: {prompt}', {prompt: info.ai_prompt});
          }
          if (info.home_html_generated) {
            layoutNote.hidden = false;
          }
          aiSection.hidden = false;
        }
      } catch (e) {
        if (!wrap.isConnected) return;
        saveThemeHint.textContent = ctx.translate(e.message || 'Could not load current theme details');
        restoreSection.hidden = true;
      }
    })();
  };
}

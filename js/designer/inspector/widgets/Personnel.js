/**
 * GCMS Designer – Inspector: Personnel Widget
 *
 * Registers a custom properties panel for [data-widget="personnel"] blocks.
 * Replaces the generic raw-params textarea with structured form controls:
 * module select, level select, department select, and a menu toggle.
 *
 * Registered via ctx.widgetPanelBuilders (set up by Widget.js).
 *
 * @filesource js/designer/inspector/widgets/Personnel.js
 */

export function registerPersonnelWidgetInspector(ctx) {
  ctx.widgetPanelBuilders = ctx.widgetPanelBuilders || {};

  ctx.widgetPanelBuilders['personnel'] = function(block, section) {
    // --- helpers ---

    const parseParams = str => {
      const p = {};
      String(str || '').split(';').forEach(part => {
        const idx = part.indexOf('=');
        if (idx > 0) p[part.slice(0, idx).trim()] = part.slice(idx + 1).trim();
      });
      return p;
    };

    const buildParams = p =>
      Object.entries(p)
        .filter(([, v]) => v !== '' && v != null)
        .map(([k, v]) => `${k}=${v}`)
        .join(';');

    const applyParams = p => {
      if (ctx.isBlockLocked(block)) {
        ctx.notify(ctx.translate('This block is locked.'));
        return;
      }
      const str = buildParams(p);
      if (str) block.dataset.widgetParams = str;
      else delete block.dataset.widgetParams;
      ctx.renderWidgetFrame(block, true);
      ctx.markChanged();
    };

    const buildOptions = (items, selected, emptyLabel) => {
      let html = '';
      if (emptyLabel !== undefined) {
        html += `<option value="">${ctx.escapeHtml(emptyLabel)}</option>`;
      }
      items.forEach(item => {
        const val = String(item.value ?? '');
        const sel = val === String(selected ?? '') ? ' selected' : '';
        html += `<option value="${ctx.escapeAttr(val)}"${sel}>${ctx.escapeHtml(String(item.text ?? ''))}</option>`;
      });
      return html;
    };

    // Show loading state
    section.innerHTML = `<p class="gcms-hint-text" data-i18n>${ctx.escapeHtml(ctx.translate('Loading...'))}</p>`;

    (async () => {
      // --- Load modules and levels ---
      let modules = [];
      let levels = [];
      try {
        const res = await ctx.fetchJson(
          `${ctx.webUrl()}api/index/widgets/get?widget=personnel`,
          { method: 'GET' }
        );
        modules = res?.data?.modules || [];
        levels = res?.data?.levels || [];
      } catch (e) {
        section.innerHTML = `<p class="gcms-hint-text">${ctx.escapeHtml(ctx.translate('Failed to load settings'))}</p>`;
        return;
      }

      const params = parseParams(block.dataset.widgetParams || '');
      const currentModule = params.module || (modules[0]?.value ?? '');
      const currentLevel = params.level ?? '';
      const currentCat = params.cat ?? '';
      const currentMenu = params.menu === '1';

      // --- Load departments for current module ---
      let departments = [];
      const currentModuleObj = modules.find(m => String(m.value) === String(currentModule));
      if (currentModuleObj?.module_id > 0) {
        try {
          const res = await ctx.fetchJson(
            `${ctx.webUrl()}api/index/widgets/get?widget=personnel&module_id=${encodeURIComponent(currentModuleObj.module_id)}`,
            { method: 'GET' }
          );
          departments = res?.data?.departments || [];
        } catch (e) { /* leave empty */ }
      }

      // --- Build form ---
      section.innerHTML = `
        <h4 data-i18n>${ctx.escapeHtml(ctx.translate('Widget Settings'))}</h4>
        <div>
          <label data-i18n>${ctx.escapeHtml(ctx.translate('Module'))}</label>
          <span class="form-control">
            <select data-pp="module">
              ${buildOptions(modules, currentModule)}
            </select>
          </span>
        </div>
        <div>
          <label data-i18n>${ctx.escapeHtml(ctx.translate('Level'))}</label>
          <span class="form-control">
            <select data-pp="level">
              ${buildOptions(levels, currentLevel, ctx.translate('All levels'))}
            </select>
          </span>
        </div>
        <div>
          <label data-i18n>${ctx.escapeHtml(ctx.translate('Department'))}</label>
          <span class="form-control">
            <select data-pp="cat">
              ${buildOptions(departments, currentCat, ctx.translate('All departments'))}
            </select>
          </span>
        </div>
        <div>
          <input type="checkbox" class="switch" id="pp-personnel-menu" data-pp-check="menu"${currentMenu ? ' checked' : ''}>
          <label for="pp-personnel-menu" data-i18n>${ctx.escapeHtml(ctx.translate('Show menu'))}</label>
        </div>`;

      // Module-id lookup map built from the list returned by the API
      const moduleMap = Object.fromEntries(modules.map(m => [String(m.value), m]));

      // --- Event: module ---
      section.querySelector('[data-pp="module"]').addEventListener('change', async e => {
        const p = parseParams(block.dataset.widgetParams || '');
        const newModule = e.target.value;
        p.module = newModule;
        delete p.cat; // reset department when module changes
        applyParams(p);

        const moduleObj = moduleMap[newModule];
        const moduleId = moduleObj?.module_id || 0;
        const deptSelect = section.querySelector('[data-pp="cat"]');
        deptSelect.innerHTML = `<option value="">${ctx.escapeHtml(ctx.translate('Loading...'))}</option>`;
        deptSelect.disabled = true;
        try {
          const res = await ctx.fetchJson(
            `${ctx.webUrl()}api/index/widgets/get?widget=personnel&module_id=${encodeURIComponent(moduleId)}`,
            { method: 'GET' }
          );
          const newDepts = res?.data?.departments || [];
          deptSelect.innerHTML = buildOptions(newDepts, '', ctx.translate('All departments'));
        } catch (e) {
          deptSelect.innerHTML = `<option value="">${ctx.escapeHtml(ctx.translate('All departments'))}</option>`;
        } finally {
          deptSelect.disabled = false;
        }
      });

      // --- Event: level ---
      section.querySelector('[data-pp="level"]').addEventListener('change', e => {
        const p = parseParams(block.dataset.widgetParams || '');
        p.level = e.target.value;
        applyParams(p);
      });

      // --- Event: department ---
      section.querySelector('[data-pp="cat"]').addEventListener('change', e => {
        const p = parseParams(block.dataset.widgetParams || '');
        p.cat = e.target.value;
        applyParams(p);
      });

      // --- Event: menu ---
      section.querySelector('[data-pp-check="menu"]').addEventListener('change', e => {
        const p = parseParams(block.dataset.widgetParams || '');
        p.menu = e.target.checked ? '1' : '0';
        applyParams(p);
      });
    })();
  };
}

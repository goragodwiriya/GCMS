/**
 * GCMS Designer – Design Panel
 *
 * CSS Variable overrides, page-level color theming, and AI color generation.
 *
 * @filesource js/designer/panels/Design.js
 */

/** Persist prompt/scheme across panel rebuilds within the same designer session */
let _lastPrompt = '';
let _lastScheme = 'light';
/** Cached themeInfo for current Designer session (null = not yet fetched, false = fetch failed) */
let _themeInfo = null;

/** Pre-built prompt suggestions — label (short) + full prompt text */
const _AI_PROMPT_SUGGESTIONS = [
  {
    label: 'โรงเรียนมัธยม',
    prompt: 'โรงเรียนมัธยมทั่วไป สีหลักฟ้าเข้ม (#1e40af) กับสีขาว footer สีกรมท่าเข้ม sidebar ซ้าย header sticky บรรยากาศน่าเชื่อถือ เป็นทางการแต่ไม่น่าเบื่อ'
  },
  {
    label: 'มหาวิทยาลัย',
    prompt: 'มหาวิทยาลัย สีหลักกรมท่าเข้ม (#1e3a5f) single column hero gradient footer เข้ม บรรยากาศทางวิชาการ น่าเชื่อถือ'
  },
  {
    label: 'Dark / Tech',
    prompt: 'มหาวิทยาลัยวิทยาศาสตร์และเทคโนโลยี dark theme สีหลักม่วงอมน้ำเงิน (#4c1d95) ไม่มี sidebar single column section มีมุมโค้งมน hero gradient ม่วง-น้ำเงิน'
  },
  {
    label: 'โรงพยาบาล',
    prompt: 'โรงพยาบาลชุมชน โทนสีเขียว (#166534) กับสีขาว sidebar ขวา บรรยากาศสะอาด สงบ น่าไว้ใจ footer สีเขียวเข้ม'
  },
  {
    label: 'สาธารณสุข',
    prompt: 'สำนักงานสาธารณสุขจังหวัด สีเขียวมรกต (#065f46) กับขาว sidebar ขวา footer สีเขียว บรรยากาศสะอาด เป็นมืออาชีพ'
  },
  {
    label: 'เทศบาล / อบต.',
    prompt: 'เทศบาลเมือง/อบต. สีหลักน้ำเงิน-แดงธงชาติ sidebar ซ้าย footer สีเข้ม บรรยากาศเป็นทางการ เข้าถึงได้ง่าย'
  },
  {
    label: 'หน่วยงานราชการ',
    prompt: 'หน่วยงานราชการทั่วไป สีหลักน้ำเงินเข้ม (#1e3a8a) footer สีกรมท่า single column บรรยากาศเป็นทางการ น่าเชื่อถือ header sticky'
  },
  {
    label: 'อาชีวศึกษา',
    prompt: 'สถาบันอาชีวศึกษา warm amber สีทอง-น้ำตาล (#92400e) sidebar ซ้าย บรรยากาศกระตือรือร้น สร้างสรรค์ footer สีน้ำตาลเข้ม'
  },
  {
    label: 'Minimal Light',
    prompt: 'เว็บไซต์ราชการ minimal สีขาวเป็นหลัก accent สีฟ้า (#2563eb) เส้นเบา single column ไม่มี sidebar บรรยากาศสะอาด ทันสมัย'
  },
];

export function registerDesignPanel(ctx) {

  ctx.showDesignPanel = function() {
    const wrap = document.createElement('div');
    wrap.innerHTML = `
      <div class="gcms-panel-section">
        <h4 data-i18n>AI Color Generator</h4>
        <p data-i18n>Describe the color theme and let AI generate a palette.</p>
        <div>
          <div class="form-control icon-file">
            <textarea rows="3" data-ai-prompt placeholder="${ctx.escapeAttr(ctx.translate('e.g. modern blue school website, dark footer, sticky header'))}">${ctx.escapeHtml(_lastPrompt)}</textarea>
          </div>
        </div>
        <div>
          <label data-i18n>Prompt suggestions</label>
          <div class="gcms-prompt-chips" data-ai-prompt-chips></div>
        </div>
        <div>
          <label data-i18n>Color scheme</label>
          <span class="form-control icon-contrast">
            <select data-ai-scheme>
              <option value="light"${_lastScheme === 'dark' ? '' : ' selected'} data-i18n>Light</option>
              <option value="dark"${_lastScheme === 'dark' ? ' selected' : ''} data-i18n>Dark</option>
            </select>
          </span>
        </div>
        <button type="button" class="btn btn-primary fullwidth" data-action="ai-generate" data-i18n>Generate colors</button>
        <p class="gcms-hint-text" data-ai-status hidden></p>
      </div>
      <div class="gcms-panel-section" data-global-settings>
        <h4 data-i18n>Global Settings</h4>
        <div>
          <label data-i18n>Layout width</label>
          <span class="form-control icon-menus">
            <select data-body-layout>
              <option value="" data-i18n>Default</option>
              <option value="wide" data-i18n>Wide</option>
              <option value="fullwidth" data-i18n>Full Width</option>
            </select>
          </span>
        </div>
        <div>
          <label data-i18n>Body font</label>
          <span class="form-control icon-edit">
            <select data-css-var="--font-family-base">
              ${ctx.state.fonts.map(f => `<option value="${ctx.escapeAttr(f.value)}">${ctx.escapeHtml(f.label)}</option>`).join('')}
            </select>
          </span>
        </div>
        <div>
          <label data-i18n>Heading font</label>
          <span class="form-control icon-edit">
            <select data-css-var="--font-family-heading">
              ${ctx.state.fonts.map(f => `<option value="${ctx.escapeAttr(f.value)}">${ctx.escapeHtml(f.label)}</option>`).join('')}
            </select>
          </span>
        </div>
        <div>
          <label data-i18n>Sidebar width</label>
          <span class="form-control icon-menus">
            <select data-css-var="--menu-width">
              <option value="" data-i18n>Theme default</option>
              <option value="220px">220px</option>
              <option value="240px">240px</option>
              <option value="280px">280px</option>
              <option value="320px">320px</option>
              <option value="360px">360px</option>
              <option value="400px">400px</option>
            </select>
          </span>
        </div>
        <div>
          <label data-i18n>Card radius</label>
          <span class="form-control icon-expand">
            <select data-css-var="--border-radius">
              <option value="" data-i18n>Default</option>
              <option value="0" data-i18n>None</option>
              <option value="0.25rem" data-i18n>Small</option>
              <option value="0.5rem" data-i18n>Medium</option>
              <option value="0.75rem" data-i18n>Large</option>
              <option value="1rem" data-i18n>Extra large</option>
              <option value="9999px" data-i18n>Pill</option>
            </select>
          </span>
        </div>
      </div>
      <div class="gcms-panel-section">
        <h4 data-i18n>Theme Colors</h4>
        <p data-i18n>Adjust homepage-only CSS variables to align with your theme and AI colors.</p>
        <div class="gcms-design-fields"></div>
      </div>
      <div class="gcms-panel-section">
        <button type="button" class="btn icon-reset" data-action="reset-vars" data-i18n>Reset overrides</button>
      </div>`;

    const fields = wrap.querySelector('.gcms-design-fields');
    ctx.state.cssVarFields.forEach(field => {
      const value = ctx.state.cssVars[field.name] || _getCssVar(field.name) || '#000000';
      const row = document.createElement('div');
      row.innerHTML = `
        <label data-i18n>${ctx.escapeHtml(field.label)}</label>
        <div>
          <span class="form-control icon-color">
            <input type="color" value="${_normalizeColor(value)}" data-css-var="${ctx.escapeAttr(field.name)}">
          </span>
        </div>`;
      fields.appendChild(row);
    });

    wrap.querySelector('[data-action="reset-vars"]').addEventListener('click', () => {
      Object.keys(ctx.state.cssVars).forEach(name => document.documentElement.style.removeProperty(name));
      ctx.state.cssVars = {};
      ctx.applyBodyClasses([]);
      ctx.markChanged();
      ctx.showDesignPanel();
    });

    ctx.showPanel('Design', wrap, {
      onFormReady: () => {
        if (fields.dataset.gcmsCssVarWired === '1') return;
        fields.dataset.gcmsCssVarWired = '1';

        // ── Global Settings ──
        const globalSection = wrap.querySelector('[data-global-settings]');
        const bodyLayoutSel = globalSection.querySelector('[data-body-layout]');
        const currentLayout = ctx.state.bodyClasses.includes('fullwidth') ? 'fullwidth'
          : ctx.state.bodyClasses.includes('wide') ? 'wide' : '';
        bodyLayoutSel.value = currentLayout;
        bodyLayoutSel.addEventListener('change', () => {
          ctx.applyBodyClasses(bodyLayoutSel.value ? [bodyLayoutSel.value] : []);
          ctx.markChanged();
        });

        globalSection.querySelectorAll('select[data-css-var]').forEach(sel => {
          const name = sel.dataset.cssVar;
          const saved = ctx.state.cssVars[name];
          if (saved !== undefined) {
            const opt = [...sel.options].find(o => o.value === saved);
            if (opt) sel.value = saved;
          }
          sel.addEventListener('change', () => {
            const val = sel.value;
            if (!val) {
              delete ctx.state.cssVars[name];
              document.documentElement.style.removeProperty(name);
              ctx.markChanged();
            } else {
              _setCssVar(name, val);
              if (name === '--font-family-base' || name === '--font-family-heading') {
                ctx.ensureGoogleFont(val);
              }
            }
          });
        });

        // ── Manual color pickers ──
        fields.querySelectorAll('[data-css-var]').forEach(input => {
          const sync = () => {
            _setCssVar(input.dataset.cssVar, input.value);
          };
          input.addEventListener('input', sync);
          input.addEventListener('change', sync);
        });

        // ── AI Color Generator ──
        const promptEl = wrap.querySelector('[data-ai-prompt]');
        const schemeEl = wrap.querySelector('[data-ai-scheme]');
        const statusEl = wrap.querySelector('[data-ai-status]');
        const generateBtn = wrap.querySelector('[data-action="ai-generate"]');

        // ── Prompt suggestion chips ──
        const chipsEl = wrap.querySelector('[data-ai-prompt-chips]');
        if (chipsEl) {
          _AI_PROMPT_SUGGESTIONS.forEach(s => {
            const btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'gcms-prompt-chip';
            btn.textContent = ctx.translate(s.label);
            btn.title = s.prompt;
            btn.addEventListener('click', () => {
              promptEl.value = s.prompt;
              _lastPrompt = s.prompt;
              promptEl.dispatchEvent(new Event('input'));
              promptEl.focus();
            });
            chipsEl.appendChild(btn);
          });
        }

        promptEl.addEventListener('input', () => {_lastPrompt = promptEl.value;});
        schemeEl.addEventListener('change', () => {_lastScheme = schemeEl.value;});

        // ── Auto-load AI theme context once per session; re-apply on every panel open ──
        const _applyThemeInfo = (info) => {
          if (!wrap.isConnected || !info?.is_ai_theme) return;
          if (info.ai_prompt && !_lastPrompt) {
            _lastPrompt = info.ai_prompt;
            promptEl.value = _lastPrompt;
          }
          if (info.ai_color_scheme && info.ai_color_scheme !== 'light' && _lastScheme === 'light') {
            _lastScheme = info.ai_color_scheme;
            schemeEl.value = _lastScheme;
          }
          _setStatus(statusEl, ctx.translate('AI-generated theme detected — edit the prompt and click Generate to regenerate'), false);
        };
        if (_themeInfo === null) {
          ctx.getThemePublishInfo?.().then(info => {
            _themeInfo = info ?? false;
            _applyThemeInfo(info);
          }).catch(() => {
            _themeInfo = false;
          });
        } else if (_themeInfo) {
          _applyThemeInfo(_themeInfo);
        }

        generateBtn.addEventListener('click', async () => {
          const prompt = promptEl.value.trim();
          if (!prompt) {
            ctx.notify(ctx.translate('Enter a color theme description first'), 'warning');
            return;
          }

          const origText = generateBtn.textContent;
          generateBtn.disabled = true;
          generateBtn.textContent = ctx.translate('Generating…');
          statusEl.hidden = true;

          try {
            const result = await ctx.fetchJson(
              ctx.webUrl() + 'api/index/aitheme/generate',
              {method: 'POST', body: JSON.stringify({prompt, color_scheme: schemeEl.value, base_css_vars: {...ctx.state.cssVars}})}
            );
            const payload = result?.data ?? result;
            const css = payload?.css ?? '';
            if (!css) throw new Error(result?.message || ctx.translate('AI returned no CSS'));

            const vars = _parseCssVarsFromString(css);
            if (!Object.keys(vars).length) {
              throw new Error(ctx.translate('Could not parse generated CSS variables'));
            }

            ctx.applyCssVars(vars);
            Object.assign(ctx.state.cssVars, vars);
            ctx.markChanged();

            // Update color pickers in-place without rebuilding the panel
            fields.querySelectorAll('[data-css-var]').forEach(input => {
              const v = vars[input.dataset.cssVar];
              if (v) input.value = _normalizeColor(v);
            });

            _setStatus(statusEl, ctx.translate('Colors applied — use Export to save as a new theme or update the current one'), false);
          } catch (e) {
            _setStatus(statusEl, e.message, true);
          } finally {
            generateBtn.disabled = false;
            generateBtn.textContent = origText;
          }
        });
      }
    });
  };

  ctx.applyCssVars = function(vars) {
    Object.entries(vars || {}).forEach(([name, value]) => {
      if (name.startsWith('--') && value) {
        document.documentElement.style.setProperty(name, value);
        if (name === '--font-family-base' || name === '--font-family-heading') {
          ctx.ensureGoogleFont(value);
        }
      }
    });
  };

  ctx.applyBodyClasses = function(classes) {
    const layoutClasses = ['wide', 'fullwidth'];
    layoutClasses.forEach(c => document.body.classList.remove(c));
    ctx.state.bodyClasses = Array.isArray(classes)
      ? classes.filter(c => layoutClasses.includes(c))
      : [];
    ctx.state.bodyClasses.forEach(c => document.body.classList.add(c));
  };

  /* ─── Private ─── */

  function _getCssVar(name) {
    return getComputedStyle(document.documentElement).getPropertyValue(name).trim();
  }

  function _setCssVar(name, value) {
    if (!name || !value) return;
    ctx.state.cssVars[name] = value;
    document.documentElement.style.setProperty(name, value);
    ctx.markChanged();
  }

  function _normalizeColor(value) {
    const raw = String(value || '').trim();
    if (/^#[0-9a-f]{6}$/i.test(raw)) return raw;
    if (/^#[0-9a-f]{3}$/i.test(raw)) return '#' + raw.slice(1).split('').map(ch => ch + ch).join('');
    return ctx.rgbToHex(raw);
  }

  function _parseCssVarsFromString(css) {
    const vars = {};
    const re = /(--[\w-]+)\s*:\s*([^;]+);/g;
    let m;
    while ((m = re.exec(css)) !== null) {
      const v = m[2].trim();
      if (v) vars[m[1].trim()] = v;
    }
    return vars;
  }

  function _setStatus(el, text, isError) {
    el.textContent = text;
    el.style.color = isError
      ? 'var(--color-danger, #dc2626)'
      : 'var(--color-success, #166534)';
    el.hidden = false;
  }
}

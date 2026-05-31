/**
 * GCMS Designer – Inspector: Block Properties
 *
 * Full block-level styling inspector.
 * โครงสร้างวางใน `form[data-form="gcms-designer-panel"]` — FormManager จะ init หลัง `showPanel`;
 * ฟิลด์ที่กำหนดเอง (สี, preset, FileBrowser) ยังสร้างด้วย DOM ตรง ไม่ผ่าน FormBuilderManager
 *
 * @filesource js/designer/inspector/Block.js
 */

export function registerBlockInspector(ctx) {
  ctx.showInspector = function(block) {
    if (!block) {
      const wrap = document.createElement('div');
      wrap.innerHTML = '<p>' + ctx.escapeHtml(ctx.translate('Select a block to edit properties')) + '</p>';
      ctx.showPanel('Properties', wrap);
      return;
    }

    if (ctx.isWidgetFrame(block)) {
      ctx.showWidgetInspector(block);
      return;
    }

    const wrap = document.createElement('div');
    wrap.innerHTML = _html(block);

    _wireAll(wrap, block);

    const blockTitle = block.dataset.editorLabel || block.dataset.blockType || 'Block';
    ctx.showPanel(blockTitle, wrap, {
      onFormReady: () => {
        _wireDataPropListeners(wrap, block);
        _wireClassTagsInput(wrap, block);
      }
    });
  };

  /* ─── HTML template ─── */

  function _html(block) {
    const isCustom = block.dataset.gcmsCustom === '1';
    return `<div data-component="tabs" class="gcms-inspector-tabs" data-default-tab="style">
  <div class="tab-buttons">
    <button type="button" class="tab-button" data-tab="style" data-i18n>Style</button>
    <button type="button" class="tab-button" data-tab="typo" data-i18n>Typography</button>
    <button type="button" class="tab-button" data-tab="settings" data-i18n>Settings</button>
  </div>
  <div class="tab-content">
    <!-- ── Tab: Style ── -->
    <div class="tab-pane" data-tab="style">
      <div class="gcms-panel-section">
        <h4 data-i18n>Background</h4>
        <div>
          <label data-i18n>Color</label>
          <span class="form-control"><input type="color" data-prop="backgroundColor"></span>
        </div>
        <div>
          <label data-i18n>Gradient</label>
          <div class="gcms-preset-grid gcms-bg-gradient-presets">
            <button type="button" title="{LNG_Gradient Purple}" data-bg-gradient="linear-gradient(135deg,#667eea 0%,#764ba2 100%)"></button>
            <button type="button" title="{LNG_Gradient Pink}" data-bg-gradient="linear-gradient(135deg,#f093fb 0%,#f5576c 100%)"></button>
            <button type="button" title="{LNG_Gradient Blue}" data-bg-gradient="linear-gradient(to right,#4facfe 0%,#00f2fe 100%)"></button>
            <button type="button" title="{LNG_Gradient Mint}" data-bg-gradient="linear-gradient(120deg,#84fab0 0%,#8fd3f4 100%)"></button>
            <button type="button" title="{LNG_Gradient Green}" data-bg-gradient="linear-gradient(135deg,#43e97b 0%,#38f9d7 100%)"></button>
            <button type="button" title="{LNG_Gradient Orange}" data-bg-gradient="linear-gradient(135deg,#fa8231 0%,#f7b733 100%)"></button>
            <button type="button" title="{LNG_Gradient Red}" data-bg-gradient="linear-gradient(135deg,#f5515f 0%,#9f041b 100%)"></button>
            <button type="button" title="{LNG_Gradient Sunset}" data-bg-gradient="linear-gradient(135deg,#f83600 0%,#f9d423 100%)"></button>
            <button type="button" title="{LNG_Gradient Gold}" data-bg-gradient="linear-gradient(135deg,#f6d365 0%,#fda085 100%)"></button>
            <button type="button" title="{LNG_Gradient Dark Transparent Top}" data-bg-gradient="linear-gradient(to bottom,rgba(0,0,0,.62) 0%,rgba(0,0,0,0) 75%)"></button>
            <button type="button" title="{LNG_Gradient Dark Transparent Bottom}" data-bg-gradient="linear-gradient(to top,rgba(0,0,0,.62) 0%,rgba(0,0,0,0) 75%)"></button>
            <button type="button" title="{LNG_Gradient Dark Transparent Left}" data-bg-gradient="linear-gradient(to right,rgba(0,0,0,.56) 0%,rgba(0,0,0,0) 75%)"></button>
            <button type="button" title="{LNG_Gradient Dark Transparent Right}" data-bg-gradient="linear-gradient(to left,rgba(0,0,0,.56) 0%,rgba(0,0,0,0) 75%)"></button>
            <button type="button" title="{LNG_Gradient Dark Transparent Center}" data-bg-gradient="radial-gradient(circle at center,rgba(0,0,0,.58) 0%,rgba(0,0,0,0) 70%)"></button>
            <button type="button" title="{LNG_Gradient White Transparent Top}" data-bg-gradient="linear-gradient(to bottom,rgba(255,255,255,.82) 0%,rgba(255,255,255,0) 75%)"></button>
            <button type="button" title="{LNG_Gradient White Transparent Bottom}" data-bg-gradient="linear-gradient(to top,rgba(255,255,255,.82) 0%,rgba(255,255,255,0) 75%)"></button>
            <button type="button" title="{LNG_Gradient White Transparent Left}" data-bg-gradient="linear-gradient(to right,rgba(255,255,255,.78) 0%,rgba(255,255,255,0) 75%)"></button>
            <button type="button" title="{LNG_Gradient White Transparent Right}" data-bg-gradient="linear-gradient(to left,rgba(255,255,255,.78) 0%,rgba(255,255,255,0) 75%)"></button>
          </div>
        </div>
        <div class="form-group">
          <button type="button" class="btn icon-image width50" data-action="block-bg-filebrowser" data-i18n>Background Image</button>
          <button type="button" class="btn icon-reset width50" data-action="block-bg-clear" data-i18n>Reset</button>
        </div>
        <div class="form-group">
          <div class="width50">
            <label data-i18n>Size</label>
            <span class="form-control">
              <select data-block-bg-size>
                <option value="" data-i18n>Default</option>
                <option value="cover" data-i18n>Cover</option>
                <option value="contain" data-i18n>Contain</option>
                <option value="unset" data-i18n>Unset</option>
              </select>
            </span>
          </div>
          <div class="width50">
            <label data-i18n>Position</label>
            <span class="form-control">
              <select data-block-bg-position>
                <option value="" data-i18n>Default</option>
                <option value="center center" data-i18n>Center</option>
                <option value="top center" data-i18n>Top</option>
                <option value="bottom center" data-i18n>Bottom</option>
                <option value="top left" data-i18n>{LNG_Top} {LNG_Left}</option>
                <option value="top right" data-i18n>{LNG_Top} {LNG_Right}</option>
                <option value="bottom left" data-i18n>{LNG_Bottom} {LNG_Left}</option>
                <option value="bottom right" data-i18n>{LNG_Bottom} {LNG_Right}</option>
              </select>
            </span>
          </div>
        </div>
        <div class="form-group">
          <div class="width50">
            <label data-i18n>Repeat</label>
            <span class="form-control">
              <select data-block-bg-repeat>
                <option value="" data-i18n>Default</option>
                <option value="no-repeat" data-i18n>No-repeat</option>
                <option value="repeat" data-i18n>Repeat</option>
                <option value="repeat-x" data-i18n>Repeat-x</option>
                <option value="repeat-y" data-i18n>Repeat-y</option>
              </select>
            </span>
          </div>
          <div class="width50">
            <label data-i18n>Attachment</label>
            <span class="form-control">
              <select data-block-bg-attachment>
                <option value="" data-i18n>Default</option>
                <option value="fixed" data-i18n>{LNG_Fixed} ({LNG_Parallax})</option>
                <option value="local" data-i18n>Local</option>
              </select>
            </span>
          </div>
        </div>
      </div>
      <div class="gcms-panel-section">
        <h4 data-i18n>Border</h4>
        <div class="form-group">
          <div class="width50">
            <label data-i18n>Width</label>
            <span class="form-control">
              <select data-prop="borderWidth">
                <option value="" data-i18n>Default</option>
                <option value="0" data-i18n>None</option>
                <option value="1px" data-i18n>1px</option>
                <option value="2px" data-i18n>2px</option>
                <option value="3px" data-i18n>3px</option>
                <option value="4px" data-i18n>4px</option>
                <option value="5px" data-i18n>5px</option>
              </select>
            </span>
          </div>
          <div class="width50">
            <label data-i18n>Style</label>
            <span class="form-control">
              <select data-prop="borderStyle">
                <option value="" data-i18n>Default</option>
                <option value="solid" data-i18n>Solid</option>
                <option value="dashed" data-i18n>Dashed</option>
                <option value="dotted" data-i18n>Dotted</option>
              </select>
            </span>
          </div>
        </div>
        <div class="form-group">
          <div class="width50">
            <label data-i18n>Color</label>
            <span class="form-control"><input type="color" data-prop="borderColor"></span>
          </div>
          <div class="width50">
            <label data-i18n>Radius</label>
            <span class="form-control">
              <select data-prop="borderRadius">
                <option value="" data-i18n>Default</option>
                <option value="0" data-i18n>None</option>
                <option value="var(--border-radius-sm)" data-i18n>Small</option>
                <option value="var(--border-radius)" data-i18n>Medium</option>
                <option value="var(--border-radius-lg)" data-i18n>Large</option>
                <option value="var(--border-radius-2xl)" data-i18n>Rounded</option>
                <option value="var(--border-radius-full)" data-i18n>Circle</option>
              </select>
            </span>
          </div>
        </div>
      </div>
    </div>
    <!-- ── Tab: Typography ── -->
    <div class="tab-pane" data-tab="typo">
      <div class="gcms-panel-section">
        <div>
          <label data-i18n>Font family</label>
          <span class="form-control icon-fontfamily">
            <select data-prop="fontFamily"> ${ctx.state.fonts.map(f => `<option value="${ctx.escapeAttr(f.value)}" data-i18n>${ctx.escapeHtml(f.label)}</option>`).join('')}
            </select>
          </span>
        </div>
        <div>
          <label data-i18n>Font size</label>
          <span class="form-control icon-font-size">
            <select data-prop="fontSize">
              <option value="" data-i18n>Default</option>
              <option value=".875rem" data-i18n>Small</option>
              <option value="1rem" data-i18n>Normal</option>
              <option value="1.25rem" data-i18n>Large</option>
              <option value="1.5rem" data-i18n>XL</option>
              <option value="2rem" data-i18n>2XL</option>
            </select>
          </span>
        </div>
        <div>
          <label data-i18n>Line height</label>
          <span class="form-control icon-height">
            <select data-prop="lineHeight">
              <option value="" data-i18n>Default</option>
              <option value="1.25">1.25</option>
              <option value="1.5">1.5</option>
              <option value="1.75">1.75</option>
              <option value="2">2</option>
            </select>
          </span>
        </div>
        <div>
          <label data-i18n>Text Color</label>
          <span class="form-control icon-color">
            <input type="color" data-prop="color"></span>
          </span>
        </div>
        <div>
          <label data-i18n>Text align</label>
          <span class="form-control icon-align-left">
            <select data-prop="textAlign">
              <option value="" data-i18n>Default</option>
              <option value="left" data-i18n>Left</option>
              <option value="center" data-i18n>Center</option>
              <option value="right" data-i18n>Right</option>
              <option value="justify" data-i18n>Justify</option>
            </select>
          </span>
        </div>
        <div>
          <label data-i18n>Font weight</label>
          <span class="form-control icon-bold">
            <select data-prop="fontWeight">
              <option value="" data-i18n>Default</option>
              <option value="400" data-i18n>Normal</option>
              <option value="500" data-i18n>Medium</option>
              <option value="600" data-i18n>Semibold</option>
              <option value="700" data-i18n>Bold</option>
            </select>
          </span>
        </div>
        <div>
          <label data-i18n>Letter spacing</label>
          <span class="form-control icon-cols">
            <select data-prop="letterSpacing">
              <option value="" data-i18n>Default</option>
              <option value="-0.02em" data-i18n>Tight</option>
              <option value="0.02em" data-i18n>Slight</option>
              <option value="0.05em" data-i18n>Wide</option>
              <option value="0.1em" data-i18n>Wider</option>
              <option value="0.2em" data-i18n>Widest</option>
            </select>
          </span>
        </div>
        <div>
          <label data-i18n>Max width</label>
          <span class="form-control icon-width">
            <select data-prop="maxWidth">
              <option value="" data-i18n>Default</option>
              <option value="36rem" data-i18n>36rem</option>
              <option value="42rem" data-i18n>42rem</option>
              <option value="48rem" data-i18n>48rem</option>
              <option value="64rem" data-i18n>64rem</option>
              <option value="80rem" data-i18n>80rem</option>
              <option value="100%" data-i18n>100%</option>
            </select>
          </span>
        </div>
        <div>
          <input type="checkbox" class="switch" id="data-block-h-center" data-block-h-center>
          <label for="data-block-h-center" data-i18n>Center horizontal</label>
        </div>
      </div>
    </div>
    <!-- ── Tab: Settings ── -->
    <div class="tab-pane" data-tab="settings">
      <div>
        <label for="block-css-classes" data-i18n>CSS Classes</label>
        <span class="form-control icon-tags">
          <input type="tags" id="block-css-classes" name="block-css-classes" data-block-css-classes placeholder="{LNG_Add class...}">
        </span>
      </div>
      <div class="gcms-panel-section">
        <h4 data-i18n>Actions</h4>
        <div class="gcms-panel-actions">
          <button type="button" class="btn icon-move_up" data-action="move-up" data-i18n>Move up</button>
          <button type="button" class="btn icon-move_down" data-action="move-down" data-i18n>Move down</button>
          <button type="button" class="btn icon-documents" data-action="duplicate" data-i18n>Duplicate</button>
          <button type="button" class="btn icon-delete" data-action="delete" data-i18n>Delete</button>
          ${isCustom && block.querySelector('.contents-grid, .section-row') ? `<button type="button" class="btn icon-compare" data-action="toggle-reverse" data-i18n>Reverse</button>` : ''}
        </div>
      </div>
      <div class="gcms-panel-section">
        <h4 data-i18n>Protection</h4>
        <div>
          <input type="checkbox" class="switch" id="data-block-lock" data-block-lock>
          <label for="data-block-lock" data-i18n>Lock this block</label>
        </div>
        ${isCustom ? `<div class="gcms-panel-section">
          <h4 data-i18n>Custom HTML Source</h4>
          <div>
            <span class="form-control icon-code">
              <textarea rows="8" data-custom-block-html>${ctx.escapeHtml(ctx.getCustomBlockInnerHtml(block))}</textarea>
            </span>
          </div>
          <button type="button" class="btn secondary fullwidth" data-action="apply-custom-html" data-i18n>Apply HTML</button>
        </div>` : ''}
        <div class="gcms-panel-actions">
          <button type="button" class="btn icon-copy" data-action="copy-style" data-i18n>Copy style</button>
          <button type="button" class="btn icon-copy" data-action="paste-style" data-i18n>Paste style</button>
          <button type="button" class="btn icon-visited" data-action="toggle-hidden" data-i18n>Toggle Hidden</button>
          <button type="button" class="btn icon-reset" data-action="reset-style" data-i18n>Reset style</button>
        </div>
      </div>
    </div>
  </div>
</div>`;
  }

  /* ─── Wire events ─── */

  function _normalizedClassList(values) {
    return [...new Set(values.map(cls => ctx.sanitizeClassName(cls)).filter(Boolean))].sort();
  }

  function _getClassTagsInput(wrap) {
    return wrap.querySelector('[data-block-css-classes]');
  }

  function _getClassTagsValues(input) {
    if (!input) return [];
    const inst = window.ElementManager?.getInstanceByElement?.(input);
    if (inst && window.TagsElementFactory) {
      return window.TagsElementFactory.getTags(inst).map(tag => tag.key);
    }
    const wrapper = input.closest('.tags-input-wrapper');
    if (!wrapper) return [];
    return Array.from(wrapper.querySelectorAll('.tags-hidden-inputs input[type="hidden"]')).map(el => el.value);
  }

  function _syncClassTagsFromBlock(wrap, block) {
    const input = _getClassTagsInput(wrap);
    if (!input) return;
    const fromBlock = _normalizedClassList(ctx.editableClassList(block));
    const fromTags = _normalizedClassList(_getClassTagsValues(input));
    if (fromBlock.join('\0') === fromTags.join('\0')) return;

    const inst = window.ElementManager?.getInstanceByElement?.(input);
    if (inst && window.TagsElementFactory) {
      wrap.dataset.gcmsClassTagsSyncing = '1';
      try {
        window.TagsElementFactory.setTags(inst, fromBlock);
      } finally {
        delete wrap.dataset.gcmsClassTagsSyncing;
      }
      return;
    }
    input.value = fromBlock.join(',');
  }

  function _applyClassesFromTags(wrap, block) {
    if (ctx.isBlockLocked(block)) return;
    const input = _getClassTagsInput(wrap);
    if (!input) return;
    const nextClasses = _normalizedClassList(_getClassTagsValues(input));
    const currentClasses = _normalizedClassList(ctx.editableClassList(block));
    if (nextClasses.join('\0') === currentClasses.join('\0')) return;
    ctx.editableClassList(block).forEach(cls => block.classList.remove(cls));
    nextClasses.forEach(cls => block.classList.add(cls));
    ctx.markChanged();
  }

  /** ผูก tags input หลัง FormManager.init — ใช้ TagsElementFactory แบบเดียวกับฟอร์ม admin */
  function _wireClassTagsInput(wrap, block) {
    const input = _getClassTagsInput(wrap);
    if (!input || wrap.dataset.gcmsClassTagsWired === '1') return;
    wrap.dataset.gcmsClassTagsWired = '1';

    _syncClassTagsFromBlock(wrap, block);

    input.addEventListener('change', () => {
      if (wrap.dataset.gcmsClassTagsSyncing === '1') return;
      if (ctx.isBlockLocked(block)) {
        _syncClassTagsFromBlock(wrap, block);
        ctx.notify(ctx.translate('This block is locked.'));
        return;
      }
      _applyClassesFromTags(wrap, block);
      _syncClassTagsFromBlock(wrap, block);
    });
  }

  /** ผูก [data-prop] หลัง FormManager.init (หรือหมดเวลา) — delegation แบบ capture + ไม่เทียบ hex สำหรับสี (กัน transparent/rgba ผิดพลาด) */
  function _wireDataPropListeners(wrap, block) {
    if (wrap.dataset.gcmsDataPropWired === '1') return;
    wrap.dataset.gcmsDataPropWired = '1';

    const colorProps = new Set(['backgroundColor', 'color', 'borderColor']);

    const applyFromInput = input => {
      if (!input?.matches?.('[data-prop]')) return;
      if (ctx.isBlockLocked(block)) return;
      const prop = input.dataset.prop;
      const rawNext = (input.value || '').trim();
      const rawCur = (block.style[prop] || '').trim();
      if (!colorProps.has(prop) && rawNext === rawCur) return;

      if (prop === 'fontFamily') {
        ctx.ensureGoogleFont(input.value);
      }
      if (prop === 'backgroundColor' && rawNext) {
        block.style.backgroundImage = 'none';
      }
      block.style[prop] = rawNext;
      ctx.markChanged();
    };

    const onFormControl = (/** @type {Event} */ e) => {
      const t = e.target;
      if (t && typeof t.matches === 'function' && t.matches('[data-prop]')) {
        applyFromInput(t);
      }
    };

    wrap.addEventListener('input', onFormControl, true);
    wrap.addEventListener('change', onFormControl, true);
  }

  function _wireAll(wrap, block) {
    // Tab navigation
    const _tabsEl = wrap.querySelector('[data-component="tabs"]');
    if (_tabsEl) {
      const _btns = [..._tabsEl.querySelectorAll(':scope > .tab-buttons > .tab-button')];
      const _panes = [..._tabsEl.querySelectorAll(':scope > .tab-content > .tab-pane')];
      const _activate = id => {_btns.forEach(b => b.classList.toggle('active', b.dataset.tab === id)); _panes.forEach(p => p.classList.toggle('active', p.dataset.tab === id));};
      _btns.forEach(b => b.addEventListener('click', () => _activate(b.dataset.tab)));
      if (_btns.length > 0) _activate(_btns[0].dataset.tab);
    }



    // CSS props via [data-prop]
    const layoutSelectProps = ['borderWidth', 'borderStyle', 'borderRadius'];
    layoutSelectProps.forEach(prop => {
      const input = wrap.querySelector(`[data-prop="${prop}"]`);
      if (!input?.options) return;
      const v = (block.style[prop] || '').trim();
      input.value = [...input.options].find(o => o.value === v) ? v : '';
    });

    const fontFamilySel = wrap.querySelector('[data-prop="fontFamily"]');
    if (fontFamilySel) {
      const ff = (block.style.fontFamily || '').trim();
      fontFamilySel.value = [...fontFamilySel.options].find(o => o.value === ff) ? ff : '';
    }

    ['fontSize', 'lineHeight', 'textAlign', 'fontWeight', 'letterSpacing', 'maxWidth'].forEach(prop => {
      const input = wrap.querySelector(`[data-prop="${prop}"]`);
      if (!input) return;
      const v = (block.style[prop] || '').trim();
      input.value = [...input.options].find(o => o.value === v) ? v : '';
    });

    wrap.querySelectorAll('input[type="color"][data-prop]').forEach(el => {
      const prop = el.dataset.prop;
      const inline = (block.style[prop] || '').trim();
      if (inline) {
        const hex = ctx.cssColorToHexForInput(inline);
        if (hex) {
          el.setAttribute('value', hex);
          el.value = hex;
        } else {
          el.removeAttribute('value');
        }
      } else {
        el.removeAttribute('value');
      }
    });


    // H-center
    const hCenter = wrap.querySelector('[data-block-h-center]');
    if (hCenter) {
      const hasLegacyAutoMargins = (block.style.marginLeft || '').trim() === 'auto' && (block.style.marginRight || '').trim() === 'auto';
      hCenter.checked = block.classList.contains('center-block') || hasLegacyAutoMargins;
      hCenter.addEventListener('change', () => {
        if (ctx.isBlockLocked(block)) {hCenter.checked = !hCenter.checked; ctx.notify(ctx.translate('This block is locked.')); return;}
        block.classList.toggle('center-block', hCenter.checked);
        block.style.marginLeft = '';
        block.style.marginRight = '';
        _syncClassTagsFromBlock(wrap, block);
        ctx.markChanged();
      });
    }

    // [data-prop] — ผูกหลัง FormManager ใน _wireDataPropListeners

    // Gradient presets + FileBrowser (no manual background-image textarea)
    wrap.querySelectorAll('[data-bg-gradient]').forEach(btn => {
      const g = btn.dataset.bgGradient || '';
      if (g) btn.style.backgroundImage = g;
      if (/rgba\(/.test(g)) btn.style.backgroundColor = '#64748b';
      btn.addEventListener('click', () => {
        block.style.backgroundImage = g;
        ctx.markChanged();
      });
    });

    const removeUrlBackgroundLayers = value => {
      const text = (value || '').trim();
      if (!text) return '';
      const layers = [];
      let start = 0;
      let depth = 0;
      let quote = '';
      for (let i = 0; i < text.length; i++) {
        const ch = text[i];
        if (quote) {
          if (ch === quote && text[i - 1] !== '\\') quote = '';
          continue;
        }
        if (ch === '"' || ch === "'") {
          quote = ch;
          continue;
        }
        if (ch === '(') {
          depth++;
          continue;
        }
        if (ch === ')') {
          depth = Math.max(0, depth - 1);
          continue;
        }
        if (ch === ',' && depth === 0) {
          layers.push(text.slice(start, i).trim());
          start = i + 1;
        }
      }
      const last = text.slice(start).trim();
      if (last) layers.push(last);
      return layers.filter(layer => layer && !/^url\(/i.test(layer)).join(', ');
    };

    const bgProps = [
      ['[data-block-bg-size]', 'backgroundSize'],
      ['[data-block-bg-position]', 'backgroundPosition'],
      ['[data-block-bg-repeat]', 'backgroundRepeat'],
      ['[data-block-bg-attachment]', 'backgroundAttachment']
    ];
    bgProps.forEach(([sel, prop]) => {
      const el = wrap.querySelector(sel);
      if (!el) return;
      const v = (block.style[prop] || '').trim();
      el.value = [...el.options].find(o => o.value === v) ? v : '';
      el.addEventListener('input', () => {block.style[prop] = el.value; ctx.markChanged();});
    });

    wrap.querySelector('[data-action="block-bg-filebrowser"]')?.addEventListener('click',
      () => ctx.chooseBackgroundImageForBlock(block));

    wrap.querySelector('[data-action="block-bg-clear"]')?.addEventListener('click', () => {
      if (ctx.isBlockLocked(block)) return;
      const current = (block.style.backgroundImage || '').trim();
      const next = removeUrlBackgroundLayers(current);
      if (next === current) return;
      block.style.backgroundImage = next;
      ctx.markChanged();
    });

    // Lock
    const lockCb = wrap.querySelector('[data-block-lock]');
    lockCb.checked = ctx.isBlockLocked(block);
    lockCb.addEventListener('change', () => {ctx.setBlockLocked(block, lockCb.checked); ctx.markChanged();});

    // Custom HTML
    wrap.querySelector('[data-action="apply-custom-html"]')?.addEventListener('click', () => {
      const ta = wrap.querySelector('[data-custom-block-html]');
      ctx.applyCustomBlockInnerHtml(block, ta.value);
    });

    // Action buttons
    const actions = {
      'move-up': () => {
        ctx.moveBlock(block, -1);
        block.scrollIntoView({behavior: 'smooth', block: 'center'});
      },
      'move-down': () => {
        ctx.moveBlock(block, 1);
        block.scrollIntoView({behavior: 'smooth', block: 'center'});
      },
      'duplicate': () => ctx.duplicateBlock(block),
      'delete': () => ctx.removeBlock(block),
      'copy-style': () => ctx.copyBlockStyle(block),
      'paste-style': () => ctx.pasteBlockStyle(block),
      'toggle-hidden': () => {ctx.toggleHidden(block); ctx.showInspector(block);},
      'reset-style': () => {ctx.resetBlockStyle(block); ctx.showInspector(block);},
      'add-row': () => ctx.addRepeatRow?.(block),
      'toggle-reverse': () => {
        if (ctx.isBlockLocked(block)) {ctx.notify(ctx.translate('This block is locked.')); return;}
        const grid = block.querySelector('.contents-grid, .section-row') || block;
        grid.classList.toggle('reverse');
        ctx.markChanged();
        ctx.notify(grid.classList.contains('reverse') ? ctx.translate('Layout reversed') : ctx.translate('Layout restored'));
      }
    };
    wrap.querySelectorAll('[data-action]').forEach(btn => actions[btn.dataset.action] && btn.addEventListener('click', actions[btn.dataset.action]));
  }
}

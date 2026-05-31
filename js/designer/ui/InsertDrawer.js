/**
 * GCMS Designer – Insert Drawer
 *
 * Persistent left-side panel with three tabs:
 *   • Templates – drag or click to insert section blocks
 *   • Widgets   – drag or click to install widget frames
 *   • Elements  – drag or click to insert inline HTML elements
 *
 * Drag flow:
 *   dragstart on card → ctx.startInsertDrag(data) + ctx.minimizeInsertDrawer()
 *   dragend           → ctx.endInsertDrag()       + ctx.restoreInsertDrawer()
 *   drop on zone      → ctx.handleDropZone(zone) (handled in DropZones.js)
 *
 * Click flow (fallback):
 *   Templates → ctx.addTemplate(tpl, 'after-selected')
 *   Widgets   → ctx.addWidgetFrame(widget, 'after-selected')
 *   Elements  → ctx.quickInsertElement(el)
 *
 * @filesource js/designer/ui/InsertDrawer.js
 */

export function registerInsertDrawer(ctx) {

    let _drawer = null;
    let _activeTab = 'templates';

    /* ─── Public API ─── */

    ctx.buildInsertDrawer = function() {
        if (_drawer) return;

        _drawer = document.createElement('div');
        _drawer.id = 'gcms-insert-drawer';
        _drawer.setAttribute('aria-label', ctx.translate('Insert'));
        _drawer.innerHTML = `
      <div class="gcms-drawer-header">
        <span class="gcms-drawer-header-title" data-i18n>Insert</span>
        <button type="button" class="gcms-drawer-close" aria-label="${ctx.escapeAttr(ctx.translate('Close'))}">&times;</button>
      </div>
      <div class="gcms-drawer-tabs">
        <button type="button" class="gcms-drawer-tab active" data-tab="templates" data-i18n>Sections</button>
        <button type="button" class="gcms-drawer-tab" data-tab="widgets" data-i18n>Widgets</button>
        <button type="button" class="gcms-drawer-tab" data-tab="elements" data-i18n>Elements</button>
      </div>
      <div class="gcms-drawer-body">
        <div class="gcms-drawer-pane active" data-pane="templates">
          <div class="gcms-drawer-grid" data-tpl-grid></div>
        </div>
        <div class="gcms-drawer-pane" data-pane="widgets">
          <div class="gcms-drawer-grid gcms-drawer-loading" data-i18n data-widget-grid>Loading...</div>
        </div>
        <div class="gcms-drawer-pane" data-pane="elements">
          <div class="gcms-element-grid" data-el-grid></div>
        </div>
      </div>`;

        document.body.appendChild(_drawer);
        _wireDrawer();
    };

    function _syncToggleButton(visible) {
        ctx.state.bar?.querySelector('[data-action="insert-drawer"]')?.classList.toggle('active', visible);
    }

    ctx.showInsertDrawer = function() {
        if (!_drawer) ctx.buildInsertDrawer();
        _drawer.classList.add('visible');
        document.body.classList.add('gcms-drawer-open');
        _syncToggleButton(true);
        _renderActiveTab();
    };

    ctx.hideInsertDrawer = function() {
        if (!_drawer) return;
        _drawer.classList.remove('visible');
        document.body.classList.remove('gcms-drawer-open');
        _syncToggleButton(false);
    };

    ctx.toggleInsertDrawer = function(force) {
        if (!_drawer) ctx.buildInsertDrawer();
        const show = force !== undefined ? force : !_drawer.classList.contains('visible');
        if (show) ctx.showInsertDrawer();
        else ctx.hideInsertDrawer();
    };

    ctx.minimizeInsertDrawer = function() {
        _drawer?.classList.add('minimized');
    };

    ctx.restoreInsertDrawer = function() {
        _drawer?.classList.remove('minimized');
    };

    ctx.removeInsertDrawer = function() {
        if (!_drawer) return;
        _drawer.remove();
        _syncToggleButton(false);
        _drawer = null;
        document.body.classList.remove('gcms-drawer-open');
    };

    /* ─── Private ─── */

    function _wireDrawer() {
        // Tab switching
        _drawer.querySelectorAll('[data-tab]').forEach(btn => {
            btn.addEventListener('click', () => {
                _activeTab = btn.dataset.tab;
                _drawer.querySelectorAll('[data-tab]').forEach(b => b.classList.toggle('active', b === btn));
                _drawer.querySelectorAll('[data-pane]').forEach(p => p.classList.toggle('active', p.dataset.pane === _activeTab));
                _renderActiveTab();
            });
        });

        // Close button
        _drawer.querySelector('.gcms-drawer-close').addEventListener('click', ctx.hideInsertDrawer);
    }

    function _renderActiveTab() {
        if (_activeTab === 'templates') {
            _renderTemplatePane();
        } else if (_activeTab === 'widgets') {
            _renderWidgetPane();
        } else if (_activeTab === 'elements') {
            const grid = _drawer.querySelector('[data-el-grid]');
            if (!grid.children.length) {
                ctx.renderElementGrid(grid, 'all');
            }
        }
    }

    /* ── Templates tab ── */
    function _renderTemplatePane() {
        const grid = _drawer.querySelector('[data-tpl-grid]');
        grid.innerHTML = '';

        const all = [...ctx.state.templates, ...ctx.state.userTemplates];
        if (!all.length) {
            ctx.localizeDom(grid);
            return;
        }
        all.forEach(tpl => {
            const tplData = {type: 'section', id: tpl.id, label: tpl.title, html: tpl.html};
            const card = _makeCard(
                tpl.title,
                tpl.description || '',
                () => ctx.addTemplate(tpl, 'after-selected'),
                tplData
            );
            grid.appendChild(card);
        });
        ctx.localizeDom(grid);
    }

    /* ── Widgets tab ── */
    async function _renderWidgetPane() {
        const grid = _drawer.querySelector('[data-widget-grid]');
        try {
            await ctx.loadWidgetManifests();
            grid.removeAttribute('data-i18n');
            grid.innerHTML = '';
            const items = ctx.state.widgetManifests;
            if (!items.length) {
                grid.innerHTML = '<p class="gcms-drawer-loading" data-i18n>No widgets found</p>';
                ctx.localizeDom(grid);
                return;
            }
            items.forEach(widget => {
                const widgetData = {type: 'widget', widgetId: widget.id, title: widget.title};
                const card = _makeCard(
                    widget.title,
                    widget.id,
                    () => {
                        ctx.enterPickMode(widgetData);
                    },
                    widgetData
                );
                grid.appendChild(card);
            });
        } catch (e) {
            grid.removeAttribute('data-i18n');
            grid.textContent = ctx.translate('Could not load widgets: {message}', {message: e.message});
        }
    }

    /* ── Card factory ── */
    function _makeCard(title, subtitle, onFallbackClick, dragData) {
        const card = document.createElement('div');
        card.className = 'gcms-drawer-card';
        card.innerHTML = `<strong>${ctx.escapeHtml(ctx.translate(title))}</strong><span>${ctx.escapeHtml(ctx.translate(subtitle))}</span>`;

        // Primary: pointer-event drag (reliable — zones already in DOM before movement)
        card.addEventListener('pointerdown', e => {
            if (e.button !== 0) return;
            e.preventDefault();
            ctx.minimizeInsertDrawer();
            ctx.startPointerDrag(e, dragData);
        });

        // Click: use provided fallback handler (type-specific logic is set by caller)
        card.addEventListener('click', () => {
            ctx.minimizeInsertDrawer();
            onFallbackClick();
        });

        return card;
    }
}

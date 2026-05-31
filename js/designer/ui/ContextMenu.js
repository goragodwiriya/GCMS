/**
 * GCMS Designer – Canvas Context Menu
 *
 * Right-click any swappable item inside [data-editor-container] to get
 * move-up / move-down / duplicate / delete actions.
 *
 * "Swappable items" are direct children of:
 *   .contents-grid  (news/team/testimonial cards)
 *   .sections-list  (programs / image-text rows)
 *   [data-repeat-container]   (explicitly repeatable lists)
 *   [data-drop-zone]          (elements inserted via the drop system)
 *
 * @filesource js/designer/ui/ContextMenu.js
 */

export function registerContextMenu(ctx) {

    let _menu = null;
    let _target = null;
    let _attached = false;

    /* ─────────────────────────────────────────────
       Public: wire once during activate()
       ───────────────────────────────────────────── */

    ctx.initContextMenu = function() {
        if (_attached) return;
        _attached = true;
        document.addEventListener('contextmenu', _onContextMenu);
    };

    /* ─────────────────────────────────────────────
       Context-menu trigger
       ───────────────────────────────────────────── */

    function _onContextMenu(e) {
        if (!ctx.state.active) return;

        // Only inside edit canvas
        const editorContainer = e.target.closest('[data-editor-container]');
        if (!editorContainer) return;

        const found = _findSwappableTarget(e.target, editorContainer);
        if (!found) return;

        e.preventDefault();
        e.stopPropagation();

        _target = found;
        _renderMenu();
        _show(e.clientX, e.clientY);
    }

    /* ─────────────────────────────────────────────
       Target detection
       ───────────────────────────────────────────── */

    /**
     * Walk up from `el` toward `container` to find the nearest
     * "swappable" child — a direct child of a swappable parent that
     * is not a designer-UI overlay.
     */
    function _findSwappableTarget(el, container) {
        let current = el;
        while (current && current !== container) {
            if (_isDesignerUi(current)) {
                current = current.parentElement;
                continue;
            }
            const parent = current.parentElement;
            if (parent && _isSwappableParent(parent)) {
                return current;
            }
            current = current.parentElement;
        }
        return null;
    }

    /** Containers whose direct children are swappable/sortable. */
    function _isSwappableParent(el) {
        return ctx.isSwappableParent?.(el) ?? false;
    }

    /** Designer overlay elements that should never be context targets. */
    function _isDesignerUi(el) {
        return el.classList.contains('gcms-drop-zone') ||
            el.classList.contains('gcms-block-tools') ||
            el.classList.contains('gcms-repeat-remove') ||
            el.classList.contains('gcms-repeat-add') ||
            el.classList.contains('gcms-col-item-remove');
    }

    /* ─────────────────────────────────────────────
       Menu DOM (built once, reused)
       ───────────────────────────────────────────── */

    function _buildMenu() {
        if (_menu) return;
        _menu = document.createElement('div');
        _menu.id = 'gcms-ctx-menu';
        _menu.className = 'gcms-ctx-menu';
        _menu.setAttribute('role', 'menu');
        _menu.innerHTML = `
            <button type="button" data-ctx="move-up"   role="menuitem" class="icon-move_up"   data-i18n>Move up</button>
            <button type="button" data-ctx="move-down" role="menuitem" class="icon-move_down" data-i18n>Move down</button>
            <div data-ctx-group="reverse">
                <div class="gcms-ctx-sep" role="separator"></div>
                <button type="button" data-ctx="reverse" role="menuitem" class="icon-compare" data-i18n>Reverse layout</button>
            </div>
            <div class="gcms-ctx-sep" role="separator"></div>
            <button type="button" data-ctx="duplicate" role="menuitem" class="icon-documents" data-i18n>Duplicate</button>
            <button type="button" data-ctx="edit-html" role="menuitem" class="icon-code"      data-i18n>Edit HTML</button>
            <div class="gcms-ctx-sep" role="separator"></div>
            <button type="button" data-ctx="delete"    role="menuitem" class="danger icon-delete" data-i18n>Delete</button>
        `;
        _menu.addEventListener('click', _onMenuClick);
        document.body.appendChild(_menu);
    }

    function _renderMenu() {
        _buildMenu();
        ctx.localizeDom?.(_menu);

        const prev = _prevRealSibling(_target);
        const next = _nextRealSibling(_target);
        _menu.querySelector('[data-ctx="move-up"]').disabled = !prev;
        _menu.querySelector('[data-ctx="move-down"]').disabled = !next;
        // Show Reverse only when the block contains a reversible grid
        const block = _target.closest('[data-block-type]');
        const hasGrid = !!(block?.querySelector('.contents-grid, .section-row'));
        _menu.querySelector('[data-ctx-group="reverse"]').hidden = !hasGrid;
    }

    function _show(x, y) {
        _menu.style.cssText = `display:block;left:${x}px;top:${y}px`;
        // Clamp to viewport after paint
        requestAnimationFrame(() => {
            if (!_menu) return;
            const r = _menu.getBoundingClientRect();
            if (r.right > window.innerWidth) _menu.style.left = (x - r.width) + 'px';
            if (r.bottom > window.innerHeight) _menu.style.top = (y - r.height) + 'px';
        });

        // Auto-dismiss on any outside click or Escape
        setTimeout(() => {
            document.addEventListener('pointerdown', _onOutsideDown, {capture: true, once: true});
            document.addEventListener('keydown', _onEscKey, {capture: true, once: true});
        }, 0);
    }

    function _hide() {
        if (_menu) _menu.style.display = 'none';
        _target = null;
    }

    /* ─────────────────────────────────────────────
       Dismiss handlers
       ───────────────────────────────────────────── */

    function _onOutsideDown(e) {
        if (_menu && !_menu.contains(e.target)) _hide();
        else document.addEventListener('pointerdown', _onOutsideDown, {capture: true, once: true});
    }

    function _onEscKey(e) {
        if (e.key === 'Escape') {_hide(); return;}
        document.addEventListener('keydown', _onEscKey, {capture: true, once: true});
    }

    /* ─────────────────────────────────────────────
       Menu actions
       ───────────────────────────────────────────── */

    function _onMenuClick(e) {
        const btn = e.target.closest('[data-ctx]');
        if (!btn || !_target) return;
        const action = btn.dataset.ctx;
        const tgt = _target;
        _hide();
        switch (action) {
            case 'move-up': _moveUp(tgt); break;
            case 'move-down': _moveDown(tgt); break; case 'reverse': _reverse(tgt); break; case 'duplicate': _duplicate(tgt); break;
            case 'edit-html': _editHtml(tgt); break;
            case 'delete': _delete(tgt); break;
        }
    }

    /* ─────────────────────────────────────────────
       Actions implementation
       ───────────────────────────────────────────── */

    function _moveUp(el) {
        const prev = _prevRealSibling(el);
        if (!prev) return;
        el.parentNode.insertBefore(el, prev);
        ctx.markChanged();
        ctx.pushHistory?.('move item up');
    }

    function _moveDown(el) {
        const next = _nextRealSibling(el);
        if (!next) return;
        el.parentNode.insertBefore(next, el);
        ctx.markChanged();
        ctx.pushHistory?.('move item down');
    }

    function _reverse(item) {
        const block = item.closest('[data-block-type]');
        if (!block) return;
        // Prefer the grid/row the item belongs to; fall back to first in block
        const grid = item.closest('.contents-grid, .section-row')
            ?? block.querySelector('.contents-grid, .section-row');
        if (!grid) return;
        if (ctx.isBlockLocked?.(block)) {
            ctx.notify(ctx.translate('This block is locked.'));
            return;
        }
        grid.classList.toggle('reverse');
        ctx.markChanged();
        ctx.pushHistory?.('reverse layout');
        ctx.notify(grid.classList.contains('reverse')
            ? ctx.translate('Layout reversed')
            : ctx.translate('Layout restored'));
    }

    function _duplicate(item) {
        const clone = item.cloneNode(true);

        // Strip designer overlays from clone
        clone.querySelectorAll(
            '.gcms-repeat-remove, .gcms-repeat-add, .gcms-block-tools, .gcms-col-item-remove, .gcms-drop-zone'
        ).forEach(el => el.remove());

        // Reset editable ready flags so setup re-initialises them
        clone.querySelectorAll('[data-gcms-editable-ready]').forEach(el => {
            delete el.dataset.gcmsEditableReady;
            el.removeAttribute('contenteditable');
        });
        clone.querySelectorAll('[data-gcms-counter-wrap-ready]').forEach(el => {
            delete el.dataset.gcmsCounterWrapReady;
        });
        clone.querySelectorAll('[data-component="counter"]').forEach(el => {
            el.removeAttribute('contenteditable');
            el.removeAttribute('data-editable');
        });
        clone.querySelectorAll('[data-gcms-repeat-ready]').forEach(el => delete el.dataset.gcmsRepeatReady);
        delete clone.dataset.gcmsRepeatReady;

        // Insert after the original item
        item.parentNode.insertBefore(clone, item.nextSibling);

        // Re-wire within parent block
        const block = clone.closest('[data-block-type]');
        if (block) {
            ctx.assignEditableNames?.(block);
            ctx.setupRepeatItems?.(block);
            ctx.setupEditables?.(clone);
            ctx.syncAllCounterPreviews?.(clone);
        }

        ctx.markChanged();
        ctx.pushHistory?.('duplicate item');
        ctx.notify(ctx.translate('Duplicated'));
    }

    function _delete(item) {
        if (item.hasAttribute('data-repeat-item')) {
            // removeRepeatRow keeps minimum 1 row and shows a notification
            ctx.removeRepeatRow?.(item);
        } else {
            item.remove();
            ctx.markChanged();
            ctx.pushHistory?.('delete item');
            ctx.notify(ctx.translate('Deleted'));
        }
    }

    function _editHtml(item) {
        const ta = document.createElement('textarea');
        ta.className = 'gcms-html-modal-textarea';
        ta.spellcheck = false;

        // Populate textarea with a clean copy (strip designer UI)
        const tmpClean = item.cloneNode(true);
        tmpClean.querySelectorAll('.gcms-repeat-remove, .gcms-repeat-add, .gcms-block-tools, .gcms-col-item-remove, .gcms-drop-zone').forEach(el => el.remove());
        ta.value = tmpClean.outerHTML;

        const controlWrap = document.createElement('span');
        controlWrap.className = 'form-control icon-code';
        controlWrap.appendChild(ta);

        const modal = new window.Modal({
            title: ctx.translate('Edit HTML'),
            closeButton: true,
            onHidden: () => modal.modal.remove()
        });

        modal.setContent(controlWrap);

        const footer = document.createElement('div');
        footer.className = 'modal-footer';
        footer.innerHTML = `<button type="button" class="btn btn-primary">${ctx.escapeHtml(ctx.translate('Apply'))}</button><button type="button" class="btn">${ctx.escapeHtml(ctx.translate('Cancel'))}</button>`;
        modal.dialog.appendChild(footer);

        modal.show();

        footer.querySelector('.btn-primary').addEventListener('click', () => {
            const html = ta.value.trim();
            if (!html) return;
            const tmp = document.createElement('div');
            tmp.innerHTML = html;
            const newEl = tmp.firstElementChild;
            if (!newEl) return;

            // Preserve repeat-ready / editable-ready so re-setup runs correctly
            newEl.querySelectorAll('[data-gcms-editable-ready]').forEach(el => {
                delete el.dataset.gcmsEditableReady;
                el.removeAttribute('contenteditable');
            });
            newEl.querySelectorAll('[data-gcms-repeat-ready]').forEach(el => delete el.dataset.gcmsRepeatReady);
            delete newEl.dataset.gcmsRepeatReady;

            item.parentNode.replaceChild(newEl, item);
            const block = newEl.closest('[data-block-type]');
            if (block) {
                ctx.assignEditableNames?.(block);
                ctx.setupRepeatItems?.(block);
                ctx.setupEditables?.(newEl);
            }
            ctx.markChanged();
            ctx.pushHistory?.('edit html');
            modal.hide();
        });

        footer.querySelector('.btn').addEventListener('click', () => modal.hide());
        ta.focus();
    }

    /* ─────────────────────────────────────────────
       Sibling helpers — skip designer-UI overlays
       ───────────────────────────────────────────── */

    function _prevRealSibling(el) {
        let s = el.previousElementSibling;
        while (s && _isDesignerUi(s)) s = s.previousElementSibling;
        return s;
    }

    function _nextRealSibling(el) {
        let s = el.nextElementSibling;
        while (s && _isDesignerUi(s)) s = s.nextElementSibling;
        return s;
    }
}

/**
 * GCMS Designer – Drop Zone System
 *
 * Two insert flows:
 *
 *   DRAG  – pointerdown on drawer card → startPointerDrag() → ghost follows cursor,
 *            drop zones highlight on hover → pointerup → insertFromDrop/insertIntoColumn
 *
 *   PICK  – click on drawer card → enterPickMode() → zones become large clickable
 *            targets → click a zone → insert → Escape to cancel
 *
 * HTML5 DnD is kept as a passive fallback (zones wire dragover/drop listeners) but
 * the primary mechanism is pointer-event based, which is fully reliable because the
 * zones are already in the DOM before any pointer movement occurs.
 *
 * @filesource js/designer/blocks/DropZones.js
 */

export function registerDropZones(ctx) {

    /** Payload of the active operation — set by startPointerDrag / enterPickMode, cleared on finish. */
    let _dragData = null;

    /** Zone currently highlighted during pointer drag or pick mode. */
    let _activeZone = null;

    /** Floating ghost element that follows the cursor during pointer drag. */
    let _ghost = null;

    /** Monotonic counter for generating unique intra-zone child IDs. Reset on zone cleanup. */
    let _intraIdSeq = 0;

    /* ═══════════════════════════════════════════
       PUBLIC: pointer-event drag (primary)
    ═══════════════════════════════════════════ */

    ctx.startPointerDrag = function(e, data) {
        _dragData = data;
        _showDropZones(data.type);
        document.body.classList.add('gcms-insert-dragging', `gcms-drag-type-${data.type}`);

        // Floating ghost label
        _ghost = document.createElement('div');
        _ghost.className = 'gcms-drag-ghost';
        _ghost.textContent = ctx.translate(data.label || data.type);
        _ghost.style.left = (e.clientX + 16) + 'px';
        _ghost.style.top = (e.clientY + 16) + 'px';
        document.body.appendChild(_ghost);

        document.addEventListener('pointermove', _onPointerMove, {passive: true});
        document.addEventListener('pointerup', _onPointerUp);
        document.addEventListener('pointercancel', _onPointerCancel);
        document.addEventListener('keydown', _onDragKey);
    };

    function _onPointerMove(e) {
        // Move ghost
        if (_ghost) {
            _ghost.style.left = (e.clientX + 16) + 'px';
            _ghost.style.top = (e.clientY + 16) + 'px';
            _ghost.style.visibility = 'hidden';   // hide before hit-test
        }

        // Find the zone under the cursor
        const hit = document.elementFromPoint(e.clientX, e.clientY);
        if (_ghost) _ghost.style.visibility = '';

        const zone = hit?.closest('.gcms-drop-zone') ?? null;
        if (zone !== _activeZone) {
            _activeZone?.classList.remove('gcms-drop-zone--over');
            _activeZone = zone;
            zone?.classList.add('gcms-drop-zone--over');
        }
    }

    function _onPointerUp() {
        const zone = _activeZone;
        _cleanupPointerDrag();
        _finishInsert(zone);
    }

    function _onPointerCancel() {
        _cleanupPointerDrag();
        ctx.endInsertDrag();
        ctx.restoreInsertDrawer?.();
    }

    function _onDragKey(e) {
        if (e.key === 'Escape') _onPointerCancel();
    }

    function _cleanupPointerDrag() {
        document.removeEventListener('pointermove', _onPointerMove);
        document.removeEventListener('pointerup', _onPointerUp);
        document.removeEventListener('pointercancel', _onPointerCancel);
        document.removeEventListener('keydown', _onDragKey);
        _activeZone?.classList.remove('gcms-drop-zone--over');
        _activeZone = null;
        if (_ghost) {_ghost.remove(); _ghost = null;}
        document.body.classList.remove('gcms-insert-dragging', 'gcms-drag-type-section', 'gcms-drag-type-element', 'gcms-drag-type-widget');
    }

    function _finishInsert(zone) {
        if (zone) ctx.handleDropZone(zone);
        ctx.endInsertDrag();
        ctx.restoreInsertDrawer?.();
    }

    /* ═══════════════════════════════════════════
       PUBLIC: pick mode (sections only — click to place between blocks)
    ═══════════════════════════════════════════ */

    ctx.enterPickMode = function(data) {
        _dragData = data;
        _showDropZones(data.type);

        const zones = document.querySelectorAll('.gcms-drop-zone');
        if (!zones.length) {
            ctx.notify(ctx.translate('Add a section to the page first.'), 'info');
            _dragData = null;
            ctx.restoreInsertDrawer?.();
            return;
        }

        document.body.classList.add('gcms-pick-mode', `gcms-pick-type-${data.type}`);

        // Show a brief canvas hint so user knows what's happening
        const hint = document.createElement('div');
        hint.className = 'gcms-pick-hint';
        const hintMsg = (data.type === 'section')
            ? 'Click where to place the section. Press Escape to cancel.'
            : 'Click where to insert. Press Escape to cancel.';
        hint.textContent = ctx.translate(hintMsg);
        document.body.appendChild(hint);
        setTimeout(() => hint.remove(), 4200);

        zones.forEach(z => {
            z.addEventListener('click', _onPickZoneClick, {once: true});
        });
        document.addEventListener('keydown', _onPickKey);
    };

    function _onPickZoneClick(e) {
        const zone = e.currentTarget;
        _exitPickMode();
        if (zone) ctx.handleDropZone(zone);
    }

    function _onPickKey(e) {
        if (e.key === 'Escape') {
            _exitPickMode();
            _dragData = null;
            ctx.restoreInsertDrawer?.();
        }
    }

    function _exitPickMode() {
        document.body.classList.remove('gcms-pick-mode', 'gcms-pick-type-section', 'gcms-pick-type-element', 'gcms-pick-type-widget');
        document.querySelectorAll('.gcms-drop-zone').forEach(z => {
            z.removeEventListener('click', _onPickZoneClick);
        });
        document.removeEventListener('keydown', _onPickKey);
        _removeAllDropZones();
        ctx.restoreInsertDrawer?.();
    }

    /* ═══════════════════════════════════════════
       PUBLIC: block-pick mode (elements/widgets — click a block to insert into it)
    ═══════════════════════════════════════════ */

    ctx.enterBlockPickMode = function(data) {
        _dragData = data;
        const blocks = _getPickableBlocks();
        if (!blocks.length) {
            ctx.notify(ctx.translate('Add a block first, then insert elements into it.'), 'info');
            _dragData = null;
            ctx.restoreInsertDrawer?.();
            return;
        }

        document.body.classList.add('gcms-block-pick-mode');

        const hint = document.createElement('div');
        hint.className = 'gcms-pick-hint';
        hint.textContent = ctx.translate('Click a block to insert into it. Press Escape to cancel.');
        document.body.appendChild(hint);
        setTimeout(() => hint.remove(), 4200);

        blocks.forEach(b => {
            b.classList.add('gcms-block-pick-target');
            b.addEventListener('click', _onBlockPickClick, {once: true});
        });
        document.addEventListener('keydown', _onBlockPickKey);
    };

    function _onBlockPickClick(e) {
        const block = e.currentTarget;
        e.stopPropagation();
        const data = _dragData;
        _exitBlockPickMode();
        if (data && block) ctx.enterIntraBlockPickMode(data, block);
    }

    function _onBlockPickKey(e) {
        if (e.key === 'Escape') {
            _exitBlockPickMode();
            _dragData = null;
            ctx.restoreInsertDrawer?.();
        }
    }

    function _exitBlockPickMode() {
        document.body.classList.remove('gcms-block-pick-mode');
        _getPickableBlocks().forEach(b => {
            b.classList.remove('gcms-block-pick-target');
            b.removeEventListener('click', _onBlockPickClick);
        });
        document.removeEventListener('keydown', _onBlockPickKey);
        ctx.restoreInsertDrawer?.();
    }

    function _getPickableBlocks() {
        return [...document.querySelectorAll('[data-editor-container] > [data-block-type]')];
    }

    /* ═══════════════════════════════════════════
       PUBLIC: intra-block pick mode
       (elements/widgets — click position inside a block)
    ═══════════════════════════════════════════ */

    ctx.enterIntraBlockPickMode = function(data, block) {
        _dragData = data;

        const contentArea = block.querySelector('.section-content')
            || block.querySelector('.gcms-column')
            || block;

        // Tag children with temporary IDs for zone reference
        const children = [...contentArea.children].filter(
            c => !c.classList.contains('gcms-drop-zone') && !c.classList.contains('gcms-block-tools')
        );

        if (!children.length) {
            // Empty block — insert directly without pick mode
            const saved = _dragData;
            _dragData = null;
            ctx.insertElementAt(saved, contentArea, null, false);
            ctx.restoreInsertDrawer?.();
            return;
        }

        children.forEach((child, i) => {child.dataset.intraId = `intra-${i}`;});

        // Zone before first child
        const firstZone = _makeIntraZone(null, true);
        contentArea.insertBefore(firstZone, children[0]);

        // Zone after each child
        children.forEach(child => {
            const zone = _makeIntraZone(child.dataset.intraId, false);
            contentArea.insertBefore(zone, child.nextSibling);
        });

        document.body.classList.add('gcms-intra-pick-mode');

        const hint = document.createElement('div');
        hint.className = 'gcms-pick-hint';
        hint.textContent = ctx.translate('Click where to insert. Press Escape to cancel.');
        document.body.appendChild(hint);
        setTimeout(() => hint.remove(), 3500);

        contentArea.querySelectorAll('.gcms-intra-zone').forEach(z => {
            z.addEventListener('click', _onIntraZoneClick, {once: true});
        });
        document.addEventListener('keydown', _onIntraPickKey);
    };

    function _onIntraZoneClick(e) {
        const zone = e.currentTarget;
        e.stopPropagation();
        _exitIntraPickMode();
        ctx.handleDropZone(zone);
    }

    function _onIntraPickKey(e) {
        if (e.key === 'Escape') {
            _exitIntraPickMode();
            _dragData = null;
            ctx.restoreInsertDrawer?.();
        }
    }

    function _exitIntraPickMode() {
        document.body.classList.remove('gcms-intra-pick-mode');
        document.querySelectorAll('.gcms-intra-zone').forEach(z => {
            z.removeEventListener('click', _onIntraZoneClick);
            z.remove();
        });
        // Remove temporary intraId attributes
        document.querySelectorAll('[data-intra-id]').forEach(el => delete el.dataset.intraId);
        document.removeEventListener('keydown', _onIntraPickKey);
        ctx.restoreInsertDrawer?.();
    }

    /* ═══════════════════════════════════════════
       PUBLIC: handleDropZone (shared by both modes)
    ═══════════════════════════════════════════ */

    ctx.handleDropZone = function(zoneEl) {
        const data = _dragData;
        _dragData = null;
        if (!data) return;

        const dropType = zoneEl.dataset.dropType;
        if (dropType === 'between') {
            const container = zoneEl.parentElement;
            if (!container) return;
            if (zoneEl.dataset.dropPosition === 'prepend') {
                // First zone — insert before the first real block
                ctx.insertFromDrop(data, container, null, true);
            } else {
                const afterId = zoneEl.dataset.afterId || '';
                const afterBlock = afterId
                    ? container.querySelector(`:scope > [data-editor-id="${CSS.escape(afterId)}"]`)
                    : null;
                ctx.insertFromDrop(data, container, afterBlock, false);
            }
        } else if (dropType === 'into-block') {
            const block = zoneEl.closest('[data-block-type]');
            if (block) ctx.insertIntoBlock(data, block);
        } else if (dropType === 'into-block-at') {
            // Intra-block positional insert (from enterIntraBlockPickMode)
            const contentArea = zoneEl.parentElement;
            if (!contentArea) return;
            const afterElId = zoneEl.dataset.afterElId;
            const afterEl = afterElId
                ? contentArea.querySelector(`[data-intra-id="${CSS.escape(afterElId)}"]`)
                : null;
            const prepend = zoneEl.dataset.dropPosition === 'prepend';
            ctx.insertElementAt(data, contentArea, afterEl, prepend);
        }
    };

    /* ═══════════════════════════════════════════
       PUBLIC: legacy HTML5 DnD entry points
       (kept so existing callers don't break)
    ═══════════════════════════════════════════ */

    ctx.startInsertDrag = function(data) {
        _dragData = data;
        _showDropZones(data.type);
        document.body.classList.add('gcms-insert-dragging', `gcms-drag-type-${data.type}`);
    };

    ctx.endInsertDrag = function() {
        _dragData = null;
        _removeAllDropZones();
        document.body.classList.remove('gcms-insert-dragging', 'gcms-drag-type-section', 'gcms-drag-type-element', 'gcms-drag-type-widget');
    };

    /* ═══════════════════════════════════════════
       PRIVATE: zone creation
    ═══════════════════════════════════════════ */

    function _showDropZones(type) {
        _removeAllDropZones();

        if (type === 'element' || type === 'widget') {
            // Show intra-dividers inside every drop-zone container across the canvas
            const containers = _getAllDropZoneContainers();
            containers.forEach((c, i) => _injectIntraDividers(c, i));
            return;
        }

        // Sections: between-block zones in every [data-editor-container]
        document.querySelectorAll('[data-editor-container]').forEach(container => {
            const blocks = [...container.querySelectorAll(':scope > [data-block-type]')];

            // Zone before the first block (marked as prepend)
            const firstZone = _makeBetweenZone(null);
            firstZone.dataset.dropPosition = 'prepend';
            container.insertBefore(firstZone, blocks[0] || null);

            // Zone after every block
            blocks.forEach(block => {
                const zone = _makeBetweenZone(block.dataset.editorId || null);
                container.insertBefore(zone, block.nextSibling);
            });
        });
    }

    function _removeAllDropZones() {
        document.querySelectorAll('.gcms-drop-zone').forEach(z => z.remove());
        document.querySelectorAll('[data-intra-id]').forEach(el => delete el.dataset.intraId);
        _intraIdSeq = 0;
    }

    /** Collect all insertable containers across the canvas (one list per block). */
    function _getAllDropZoneContainers() {
        return [...document.querySelectorAll('[data-editor-container] > [data-block-type]')]
            .flatMap(block => {
                // Prefer explicit data-drop-zone annotations inside the block
                const explicit = [...block.querySelectorAll('[data-drop-zone]')];
                if (explicit.length) return explicit;
                // Fallback: well-known container classes
                const areas = [...block.querySelectorAll(
                    '.section-content, .gcms-column, .card-body, .about-content, .section-body, .content-card, .gcms-stats-bar'
                )];
                return areas.length ? areas : [block];
            });
    }

    /** Inject intra-zone dividers between children of a container. */
    function _injectIntraDividers(container, ci = 0) {
        const children = [...container.children].filter(
            c => !c.classList.contains('gcms-drop-zone') && !c.classList.contains('gcms-block-tools')
        );
        children.forEach((child, i) => {child.dataset.intraId = `iz-${ci}-${_intraIdSeq++}-${i}`;});
        // Zone before the first child (prepend)
        container.insertBefore(_makeIntraZone(null, true), children[0] || null);
        // Zone after each child
        children.forEach(child => {
            container.insertBefore(_makeIntraZone(child.dataset.intraId, false), child.nextSibling);
        });
    }

    function _makeBetweenZone(afterId) {
        const zone = document.createElement('div');
        zone.className = 'gcms-drop-zone gcms-drop-zone--between';
        zone.dataset.dropType = 'between';
        if (afterId) zone.dataset.afterId = afterId;
        zone.innerHTML = '<span class="gcms-dz-label" data-i18n>Drop here</span>';
        _wireHtml5(zone);
        return zone;
    }

    function _makeIntoBlockZone() {
        const zone = document.createElement('div');
        zone.className = 'gcms-drop-zone gcms-drop-zone--into-block';
        zone.dataset.dropType = 'into-block';
        zone.innerHTML = '<span class="gcms-dz-label" data-i18n>Drop here</span>';
        _wireHtml5(zone);
        return zone;
    }

    function _makeIntraZone(afterElId, isPrepend) {
        const zone = document.createElement('div');
        zone.className = 'gcms-drop-zone gcms-intra-zone';
        zone.dataset.dropType = 'into-block-at';
        if (isPrepend) {
            zone.dataset.dropPosition = 'prepend';
        } else if (afterElId) {
            zone.dataset.afterElId = afterElId;
        }
        zone.innerHTML = '<span class="gcms-dz-label" data-i18n>Insert here</span>';
        return zone;
    }

    /** Wire passive HTML5 DnD handlers so external HTML5 drags still work. */
    function _wireHtml5(zone) {
        zone.addEventListener('dragover', e => {
            e.preventDefault();
            e.dataTransfer.dropEffect = 'copy';
            zone.classList.add('gcms-drop-zone--over');
        });
        zone.addEventListener('dragleave', e => {
            if (!zone.contains(/** @type {Node} */(e.relatedTarget))) {
                zone.classList.remove('gcms-drop-zone--over');
            }
        });
        zone.addEventListener('drop', e => {
            e.preventDefault();
            e.stopPropagation();
            zone.classList.remove('gcms-drop-zone--over');
            ctx.handleDropZone(zone);
            ctx.endInsertDrag();
            ctx.restoreInsertDrawer?.();
        });
    }
}


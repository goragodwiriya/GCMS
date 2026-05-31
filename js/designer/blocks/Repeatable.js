/**
 * GCMS Designer – Repeatable Rows
 *
 * Supports [data-repeat-container] zones inside custom blocks (data-gcms-custom="1").
 * Each direct child [data-repeat-item] gets a remove button injected in edit mode.
 * "Add row" clones the first item, re-assigns editable field names, and wires events.
 *
 * Save/restore: custom blocks persist via outerHTML (extractState.customBlocks).
 * The .gcms-repeat-remove button is stripped by cleanEditorUi before save.
 *
 * @filesource js/designer/blocks/Repeatable.js
 */

export function registerRepeatable(ctx) {

    /**
     * Inject remove buttons on every [data-repeat-item] and "Add row" buttons
     * on every [data-repeat-container] in the block.
     * Called from setupSingleBlock and after addRepeatRow.
     */
    ctx.setupRepeatItems = function(block) {
        block.querySelectorAll('[data-repeat-container]').forEach(container => {
            container.querySelectorAll(':scope > [data-repeat-item]').forEach(_setupRepeatItem);
        });
    };

    /**
     * Clone the first [data-repeat-item] and append it to the given container
     * (or the first container in the block if none specified).
     * @param {Element} block
     * @param {Element} [container] – specific data-repeat-container to add to
     */
    ctx.addRepeatRow = function(block, container) {
        if (ctx.isBlockLocked(block)) {
            ctx.notify(ctx.translate('This block is locked.'));
            return;
        }
        const c = container ?? block.querySelector('[data-repeat-container]');
        if (!c) return;
        const items = [...c.querySelectorAll(':scope > [data-repeat-item]')];
        if (!items.length) return;

        const clone = items[0].cloneNode(true);

        // Strip edit-mode UI injected by designer
        clone.querySelectorAll('.gcms-repeat-remove, .gcms-repeat-add').forEach(el => el.remove());

        // Reset editable ready flags so setupSingleEditable re-initialises
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
        // Reset repeat-item ready flags on clone and any nested items
        clone.querySelectorAll('[data-gcms-repeat-ready]').forEach(el => {
            delete el.dataset.gcmsRepeatReady;
        });
        delete clone.dataset.gcmsRepeatReady;

        c.appendChild(clone);

        // Give all editables in the whole block unique field names
        ctx.assignEditableNames(block);

        // Re-setup all containers (handles nested containers inside the new clone)
        ctx.setupRepeatItems(block);
        ctx.setupEditables(clone);

        ctx.syncAllCounterPreviews?.(clone);
        ctx.markChanged();
        ctx.notify(ctx.translate('Row added'));
    };

    /**
     * Remove a single [data-repeat-item]. Keeps at least one row.
     */
    ctx.removeRepeatRow = function(item) {
        const container = item.parentElement;
        if (!container) return;
        const items = container.querySelectorAll(':scope > [data-repeat-item]');
        if (items.length <= 1) {
            ctx.notify(ctx.translate('At least one row required'));
            return;
        }
        item.remove();
        ctx.markChanged();
        ctx.notify(ctx.translate('Row removed'));
    };

    /* ── Private ── */

    function _setupRepeatItem(item) {
        if (item.dataset.gcmsRepeatReady === '1') return;
        item.dataset.gcmsRepeatReady = '1';
        // Delete button removed — use right-click context menu instead
    }
}

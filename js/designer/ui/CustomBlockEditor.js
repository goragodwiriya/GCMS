/**
 * GCMS Designer – Custom Block HTML Editor Modal
 *
 * Opens a focused modal to edit the inner HTML of a custom block.
 * Only exposes the block's content area (children of .section-content),
 * not the wrapper element or any designer attributes.
 *
 * Safe: all output is passed through ctx.applyCustomBlockInnerHtml which
 * sanitises via ctx.sanitizeInlineHtml (strips script, iframe, on* attrs).
 *
 * Usage: ctx.openCustomBlockEditor(block)
 *
 * @filesource js/designer/ui/CustomBlockEditor.js
 */

export function registerCustomBlockEditor(ctx) {

  ctx.openCustomBlockEditor = function(block) {
    if (!block) return;
    if (ctx.isBlockLocked(block)) {
      ctx.notify(ctx.translate('This block is locked.'));
      return;
    }

    const current = ctx.getCustomBlockInnerHtml(block);

    const modal = new window.Modal({
      title: ctx.translate('Edit HTML'),
      content: `<p class="gcms-hint-text">${ctx.escapeHtml(ctx.translate('Edit the content HTML of this block. Scripts, iframes and event handlers are removed on apply.'))}</p><span class="form-control icon-code"><textarea class="gcms-html-editor-textarea" rows="14" spellcheck="false">${ctx.escapeHtml(current)}</textarea></span>`,
      closeButton: true,
      onHidden: () => modal.modal.remove()
    });

    const footer = document.createElement('div');
    footer.className = 'modal-footer';
    footer.innerHTML = `<button type="button" class="btn btn-primary">${ctx.escapeHtml(ctx.translate('Apply'))}</button><button type="button" class="btn">${ctx.escapeHtml(ctx.translate('Cancel'))}</button>`;
    modal.dialog.appendChild(footer);

    modal.show();

    const ta = modal.body.querySelector('textarea');
    footer.querySelector('.btn-primary').addEventListener('click', () => {
      ctx.applyCustomBlockInnerHtml(block, ta.value);
      modal.hide();
    });
    footer.querySelector('.btn').addEventListener('click', () => modal.hide());
    ta?.focus();
  };
}

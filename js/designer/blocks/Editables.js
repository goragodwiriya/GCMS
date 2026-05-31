/**
 * GCMS Designer – Editable Elements
 *
 * contenteditable และ focus events บน [data-editable]
 *
 * @filesource js/designer/blocks/Editables.js
 */

export function registerEditables(ctx) {

  ctx.setupEditables = function(root = document) {
    ctx.editableElements(root).forEach(ctx.setupSingleEditable);
    ctx.setupCounterElements?.(root);
    ctx.syncAllCounterPreviews?.(root);
  };

  /** Drop-zone containers with child elements are layout shells — not inline text fields. */
  ctx.syncDropZoneContentEditable = function(el) {
    if (['counter', 'icon', 'image'].includes(el.dataset.editable)) {
      el.removeAttribute('contenteditable');
      return;
    }
    if (!el?.hasAttribute?.('data-drop-zone')) {
      el.setAttribute('contenteditable', 'true');
      return;
    }
    const hasChildEditables = !!el.querySelector(':scope > [data-editable]');
    if (hasChildEditables) {
      el.removeAttribute('contenteditable');
    } else if (el.dataset.editable === 'text') {
      el.setAttribute('contenteditable', 'true');
    }
  };

  ctx.setupSingleEditable = function(el) {
    if (el.dataset.gcmsEditableReady === '1') {
      ctx.syncDropZoneContentEditable(el);
      return;
    }
    el.dataset.gcmsEditableReady = '1';

    if (el.dataset.editable === 'icon') {
      el.addEventListener('click', event => {
        if (!ctx.state.active) return;
        if (ctx.isEditableLocked(el)) {
          event.preventDefault();
          ctx.notify(ctx.translate('This block is locked — unlock it before editing'));
          return;
        }
        event.preventDefault();
        ctx.showIconInspector(el);
      });
      return;
    }

    if (el.dataset.editable === 'image' || el.matches('img')) {
      el.addEventListener('click', event => {
        if (!ctx.state.active) return;
        if (ctx.isEditableLocked(el)) {
          event.preventDefault();
          ctx.notify(ctx.translate('This block is locked — unlock it before editing'));
          return;
        }
        event.preventDefault();
        ctx.showImageInspector(el);
      });
      return;
    }

    ctx.syncDropZoneContentEditable(el);
    if (!el.hasAttribute('contenteditable')) {
      el.draggable = false;
      return;
    }
    el.draggable = false;

    el.addEventListener('beforeinput', event => {
      if (!ctx.state.active) return;
      if (ctx.isEditableLocked(el)) event.preventDefault();
    });

    el.addEventListener('focus', () => {
      if (!ctx.state.active) return;
      if (ctx.isEditableLocked(el)) {
        el.blur();
        ctx.notify(ctx.translate('This block is locked — unlock it before editing'));
        return;
      }
      ctx.state.selectedEditable = el;
      el.classList.add('gcms-editable-active');
      ctx.showEditableInspector(el);
    });

    el.addEventListener('blur', () => {
      el.classList.remove('gcms-editable-active');
      ctx.state.selectedEditable = null;
      if (!ctx.isEditableLocked(el)) {
        ctx.sanitizeEditableElement(el);
      }
      ctx.markChanged();
    });

    el.addEventListener('input', () => {
      if (ctx.isEditableLocked(el)) return;
      ctx.markChanged();
    });

    el.addEventListener('keydown', event => {
      if (ctx.isTypingInField(el)) return;

      if ((event.ctrlKey || event.metaKey) && event.key === 'z') {
        event.preventDefault();
        ctx.undo();
      }

      if ((event.ctrlKey || event.metaKey) && event.key === 'y') {
        event.preventDefault();
        ctx.redo();
      }
    });

    el.addEventListener('paste', event => {
      if (!ctx.state.active) return;
      if (ctx.isEditableLocked(el)) {
        event.preventDefault();
        return;
      }
      event.preventDefault();
      const html = event.clipboardData?.getData('text/html') ?? '';
      const plain = event.clipboardData?.getData('text/plain') ?? '';
      if (html) {
        const clean = ctx.sanitizeInlineHtml(html);
        if (clean) {
          document.execCommand('insertHTML', false, clean);
          ctx.sanitizeEditableElement(el);
        } else if (plain) {
          document.execCommand('insertText', false, plain);
        }
      } else if (plain) {
        document.execCommand('insertText', false, plain);
      }
      ctx.markChanged();
    });

    el.addEventListener('dblclick', event => {
      const img = event.target.closest('img');
      if (img) {
        event.stopPropagation();
        ctx.chooseImage(img);
      }
    });
  };

  ctx.focusEditable = function(el) {
    el.focus();
    const selection = window.getSelection();
    const range = document.createRange();
    range.selectNodeContents(el);
    range.collapse(false);
    selection.removeAllRanges();
    selection.addRange(range);
  };
}

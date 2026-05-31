/**
 * GCMS Designer – Inspector: Image
 *
 * Image element settings: src, alt, dimensions, alignment.
 *
 * @filesource js/designer/inspector/Image.js
 */

export function registerImageInspector(ctx) {

  ctx.showImageInspector = function(img) {
    if (ctx.isEditableLocked(img)) {
      const wrap = document.createElement('div');
      wrap.innerHTML = '<p>' + ctx.escapeHtml(ctx.translate('This element is in a locked block — unlock the block toolbar first.')) + '</p>';
      ctx.showPanel('Image Settings', wrap);
      return;
    }

    ctx.state.selectedEditable = img;
    const src = img.getAttribute('src') || '';
    const alt = img.getAttribute('alt') || '';
    const align = img.style.display === 'block' && img.style.marginLeft === 'auto' && img.style.marginRight === 'auto'
      ? 'center'
      : img.style.marginLeft === 'auto' ? 'right' : 'left';

    const wrap = document.createElement('div');
    wrap.innerHTML = `
      <div class="gcms-panel-section">
        <h4 data-i18n>Image Settings</h4>
        <div class="gcms-image-preview">
          <img src="${ctx.escapeAttr(src)}" alt="${ctx.escapeAttr(alt)}">
        </div>
        <button type="button" class="btn btn-primary fullwidth icon-upload" data-action="choose-image" data-i18n>Choose image</button>
      </div>
      <div class="gcms-panel-section">
        <div>
          <label data-i18n>Image URL</label>
          <span class="form-control icon-link">
            <input type="text" data-image-prop="src" value="${ctx.escapeAttr(src)}">
          </span>
        </div>
        <div>
          <label data-i18n>Alt text</label>
          <span class="form-control icon-edit">
            <input type="text" data-image-prop="alt" value="${ctx.escapeAttr(alt)}">
          </span>
        </div>
        <div>
          <label data-i18n>Width</label>
          <span class="form-control icon-width">
            <select data-image-style="width">
              <option value="" data-i18n>Default</option>
              <option value="100%" data-i18n>Full width</option>
              <option value="75%">75%</option>
              <option value="50%">50%</option>
              <option value="320px">320px</option>
            </select>
          </span>
        </div>
        <div>
          <label data-i18n>Border radius</label>
          <span class="form-control icon-expand">
            <select data-image-style="borderRadius">
              <option value="" data-i18n>Default</option>
              <option value=".5rem" data-i18n>Small</option>
              <option value="1rem" data-i18n>Medium</option>
              <option value="999px" data-i18n>{LNG_Circle}/{LNG_Pill}</option>
            </select>
          </span>
        </div>
        <div>
          <label data-i18n>Alignment</label>
          <span class="form-control icon-align-left">
            <select data-image-align>
              <option value="left" data-i18n>Left</option>
              <option value="center" data-i18n>Center</option>
              <option value="right" data-i18n>Right</option>
            </select>
          </span>
        </div>
      </div>`;

    const preview = wrap.querySelector('.gcms-image-preview img');
    wrap.querySelector('[data-action="choose-image"]').addEventListener('click',
      () => ctx.chooseImage(img, preview));

    wrap.querySelectorAll('[data-image-prop]').forEach(input => {
      input.addEventListener('input', () => {
        img.setAttribute(input.dataset.imageProp, input.value);
        if (input.dataset.imageProp === 'src') preview.src = input.value;
        if (input.dataset.imageProp === 'alt') preview.alt = input.value;
        ctx.markChanged();
      });
    });

    wrap.querySelector('[data-image-style="width"]').value = img.style.width || '';
    wrap.querySelector('[data-image-style="borderRadius"]').value = img.style.borderRadius || '';
    wrap.querySelector('[data-image-align]').value = align;

    wrap.querySelectorAll('[data-image-style]').forEach(input => {
      input.addEventListener('input', () => {img.style[input.dataset.imageStyle] = input.value; ctx.markChanged();});
    });

    wrap.querySelector('[data-image-align]').addEventListener('input', e => {
      ctx.setImageAlignment(img, e.target.value);
      ctx.markChanged();
    });

    ctx.showPanel('Image Settings', wrap);
  };
}

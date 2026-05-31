/**
 * GCMS Designer – Elements Panel
 *
 * Draggable element library for inserting inline HTML elements onto the canvas.
 *
 * Elements can be:
 *   • Dragged to a drop zone  → insertFromDrop (wraps in standalone block)
 *   • Dragged to a column     → insertIntoColumn
 *   • Clicked                 → quickInsertElement (inserts into selected block or new block)
 *
 * @filesource js/designer/panels/Elements.js
 */

/** Element definitions catalog. */
const ELEMENT_CATALOG = [
  /* Text */
  {
    id: 'h2', category: 'text', label: 'Heading 2', icon: 'icon-heading',
    html: '<h2 data-editable="text">Heading title</h2>'
  },
  {
    id: 'h3', category: 'text', label: 'Heading 3', icon: 'icon-heading',
    html: '<h3 data-editable="text">Subheading</h3>'
  },
  {
    id: 'h4', category: 'text', label: 'Heading 4', icon: 'icon-heading',
    html: '<h4 data-editable="text">Minor heading</h4>'
  },
  {
    id: 'para', category: 'text', label: 'Paragraph', icon: 'icon-paragraph',
    html: '<p data-editable="text">Paragraph text</p>'
  },
  {
    id: 'btn-primary', category: 'action', label: 'Button primary', icon: 'icon-button',
    html: '<a class="btn btn-primary" href="#" data-editable="text">Button</a>'
  },
  {
    id: 'btn-primary-large', category: 'action', label: 'Button large', icon: 'icon-button',
    html: '<a class="btn btn-primary large" href="#" data-editable="text">Button</a>'
  },
  {
    id: 'btn-primary-small', category: 'action', label: 'Button small', icon: 'icon-button',
    html: '<a class="btn btn-primary small" href="#" data-editable="text">Button</a>'
  },
  {
    id: 'btn-primary-pill', category: 'action', label: 'Button pill', icon: 'icon-button',
    html: '<a class="btn btn-primary pill" href="#" data-editable="text">Button</a>'
  },
  {
    id: 'btn-secondary', category: 'action', label: 'Button secondary', icon: 'icon-button',
    html: '<a class="btn btn-secondary" href="#" data-editable="text">Button</a>'
  },
  {
    id: 'btn-outline', category: 'action', label: 'Button outline', icon: 'icon-button',
    html: '<a class="btn outline" href="#" data-editable="text">Outline button</a>'
  },
  {
    id: 'link', category: 'action', label: 'Link', icon: 'icon-link',
    html: '<a href="#" data-editable="text">Text link</a>'
  },
  {
    id: 'quote', category: 'text', label: 'Blockquote', icon: 'icon-quote',
    html: '<blockquote data-editable="text">"Quote text"</blockquote>'
  },
  /* Media */
  {
    id: 'image', category: 'media', label: 'Image', icon: 'icon-image',
    html: '<img src="" alt="" data-editable="image" style="max-width:100%;height:auto;border-radius:.5rem">'
  },
  {
    id: 'icon', category: 'media', label: 'Icon', icon: 'icon-star0',
    html: '<span class="icon-star" data-editable="icon" style="font-size:2rem"></span>'
  },
  /* List */
  {
    id: 'ul', category: 'list', label: 'Bullet list', icon: 'icon-listview',
    html: '<ul data-repeat-container><li data-repeat-item><span data-editable="text">List item</span></li></ul>'
  },
  {
    id: 'ol', category: 'list', label: 'Ordered list', icon: 'icon-listnumber',
    html: '<ol data-repeat-container><li data-repeat-item><span data-editable="text">Item 1</span></li></ol>'
  },
  /* Layout */
  {
    id: 'divider', category: 'layout', label: 'Divider', icon: 'icon-minus',
    html: '<hr>'
  },
  {
    id: 'div', category: 'layout', label: 'Div', icon: 'icon-code',
    html: '<div data-editable="text" data-drop-zone>Empty container</div>'
  },
  {
    id: 'section-actions', category: 'layout', label: 'Section actions', icon: 'icon-code',
    html: '<div><div class="section-actions" data-editable="text" data-drop-zone><span>Empty container</span></div></div>'
  },
  {
    id: 'spacer', category: 'layout', label: 'Spacer', icon: 'icon-expand',
    html: '<div class="spacer" aria-hidden="true"></div>'
  },
];

export function registerElementsPanel(ctx) {

  /** Expose catalog for other modules (InsertDrawer). */
  ctx.elementCatalog = ELEMENT_CATALOG;

  /**
   * Render the element grid into `container`.
   * Cards are draggable and clickable.
   */
  ctx.renderElementGrid = function(container, category = 'all') {
    container.innerHTML = '';
    const items = ELEMENT_CATALOG.filter(el =>
      category === 'all' || el.category === category
    );
    if (!items.length) {
      container.innerHTML = '<p class="gcms-template-empty" data-i18n>No elements found</p>';
      ctx.localizeDom(container);
      return;
    }
    items.forEach(el => {
      const card = document.createElement('div');
      card.className = `gcms-element-card ${el.icon}`;
      card.title = ctx.translate(el.label);
      card.innerHTML = `<span>${ctx.escapeHtml(ctx.translate(el.label))}</span>`;

      const elData = {type: 'element', id: el.id, label: el.label, html: el.html};

      // Primary: pointer drag
      card.addEventListener('pointerdown', e => {
        if (e.button !== 0) return;
        e.preventDefault();
        ctx.minimizeInsertDrawer?.();
        ctx.startPointerDrag(e, elData);
      });

      // Click: show canvas-wide drop zones
      card.addEventListener('click', () => {
        ctx.minimizeInsertDrawer?.();
        ctx.enterPickMode(elData);
      });

      container.appendChild(card);
    });
    ctx.localizeDom(container);
  };

  /**
   * Quick-insert on click: put element inside selected custom block, or create a new block.
   */
  ctx.quickInsertElement = function(el) {
    const data = {type: 'element', id: el.id, label: el.label, html: el.html};
    ctx.enterPickMode(data);
  };
}

import {describe, it, expect} from 'vitest';

import '../Now/js/SecurityManager.js';
import '../Now/js/GraphRenderer.js';
import '../js/components/DocumentViewer.js';

describe('GraphRenderer.createLegendItem', () => {
  it('renders legend label as inert text (no XSS)', () => {
    const gr = Object.create(window.GraphRenderer.prototype);
    const item = gr.createLegendItem('red', '<img src=x onerror="window.__g=1">');
    expect(item.querySelector('img')).toBeNull();
    expect(item.textContent).toContain('<img'); // shown literally as text
    expect(window.__g).toBeUndefined();
  });
});

describe('DocumentViewer.renderSignatureField placeholder escaping', () => {
  it('escapes a malicious placeholder', () => {
    const dv = Object.create(window.DocumentViewer.prototype);
    const page = document.createElement('div');
    page.setAttribute('data-page-number', '1');
    const pages = document.createElement('div');
    pages.appendChild(page);
    dv.elements = {pages};
    dv.renderSignatureField({
      id: 'f1', type: 'signature', page: 1, x: 0, y: 0, width: 10, height: 10,
      placeholder: '<img src=x onerror="window.__d=1">'
    });
    expect(page.querySelector('img')).toBeNull();
    expect(window.__d).toBeUndefined();
    expect(page.querySelector('.field-placeholder').textContent).toContain('<img');
  });
});

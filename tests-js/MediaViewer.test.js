import {describe, it, expect} from 'vitest';

import '../Now/js/SecurityManager.js';
import '../Now/js/MediaViewer.js';

function makeViewer() {
  const mv = Object.create(window.MediaViewer.prototype);
  mv.stage = document.createElement('div');
  return mv;
}

describe('MediaViewer.renderHtml sanitization', () => {
  it('does not execute or keep live <script> from server html', () => {
    const mv = makeViewer();
    mv.renderHtml({content: '<div>hi</div><script>window.__pwned=1</script>'});
    expect(mv.stage.querySelector('script')).toBeNull();
    expect(window.__pwned).toBeUndefined();
  });

  it('does not leave a live onerror handler attribute', () => {
    const mv = makeViewer();
    mv.renderHtml({html: '<img src=x onerror="window.__pwned2=1">'});
    // Either the img was escaped to inert text, or it survives with the
    // onerror attribute stripped — both must leave no live handler.
    expect(mv.stage.querySelector('[onerror]')).toBeNull();
    expect(window.__pwned2).toBeUndefined();
  });
});

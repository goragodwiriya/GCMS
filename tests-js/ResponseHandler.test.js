import {describe, it, expect, beforeEach} from 'vitest';

import '../Now/js/SecurityManager.js';
import '../Now/js/ResponseHandler.js';

const RH = () => window.ResponseHandler;

// Fresh init per test so config changes don't leak.
beforeEach(() => {
  RH().state.initialized = false;
  RH().state.actionHandlers = new Map();
  RH().config.allowEval = false;
  RH().config.sanitizeHtml = true;
});

function run(action) {
  RH().init();
  const handler = RH().state.actionHandlers.get(action.type);
  handler(action, {});
}

describe('ResponseHandler update action hardening', () => {
  it('does NOT execute server <script> when allowEval is false', () => {
    delete window.__rhPwned;
    const el = document.createElement('div');
    el.id = 'rh-target-1';
    document.body.appendChild(el);
    run({type: 'update', target: '#rh-target-1', method: 'html',
         content: '<div>ok</div><script>window.__rhPwned=1</script>'});
    expect(window.__rhPwned).toBeUndefined();
    expect(el.querySelector('script')).toBeNull();
  });

  it('sanitizes inline event handlers in injected html', () => {
    delete window.__rhPwned2;
    const el = document.createElement('div');
    el.id = 'rh-target-2';
    document.body.appendChild(el);
    run({type: 'update', target: '#rh-target-2', method: 'html',
         content: '<img src=x onerror="window.__rhPwned2=1">'});
    expect(el.querySelector('[onerror]')).toBeNull();
    expect(window.__rhPwned2).toBeUndefined();
  });

  it('blocks event-handler attribute via attr method', () => {
    const el = document.createElement('div');
    el.id = 'rh-target-3';
    document.body.appendChild(el);
    run({type: 'update', target: '#rh-target-3', method: 'attr',
         attr: 'onclick', content: 'window.__x=1'});
    expect(el.hasAttribute('onclick')).toBe(false);
  });

  it('strips javascript: scheme from href via attribute action', () => {
    const el = document.createElement('a');
    el.id = 'rh-target-4';
    document.body.appendChild(el);
    run({type: 'attribute', target: '#rh-target-4', name: 'href',
         value: 'javascript:alert(1)'});
    expect(el.getAttribute('href') || '').not.toContain('javascript:');
  });

  it('still executes server <script> when allowEval explicitly enabled', () => {
    delete window.__rhAllowed;
    const el = document.createElement('div');
    el.id = 'rh-target-5';
    document.body.appendChild(el);
    RH().init();
    RH().config.allowEval = true;
    RH().config.sanitizeHtml = false; // privileged/raw mode
    const handler = RH().state.actionHandlers.get('update');
    handler({type: 'update', target: '#rh-target-5', method: 'html', raw: true,
             content: '<script>window.__rhAllowed=1</script>'}, {});
    expect(window.__rhAllowed).toBe(1);
  });
});

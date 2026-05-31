import {describe, it, expect, beforeEach} from 'vitest';

import '../Now/js/ApiService.js';

// Capture the request interceptor ApiService registers, so we can run it
// against a fake request config and inspect the headers it would send.
function installHttpCapture() {
  let captured = null;
  window.http = {addRequestInterceptor: (fn) => { captured = fn; }};
  return () => captured;
}

describe('cookie-based auth: ApiService transport', () => {
  beforeEach(() => {
    try { localStorage.removeItem('auth_token'); } catch (e) {}
  });

  it("does NOT attach Authorization under 'cookie' strategy, even if a token sits in localStorage", () => {
    const getInterceptor = installHttpCapture();
    window.ApiService.config.security.authStrategy = 'cookie';
    window.ApiService.setupBearerAuth();
    localStorage.setItem('auth_token', 'LEAKED_TOKEN_SHOULD_NOT_BE_SENT');

    const out = getInterceptor()({headers: {}});
    expect(out.headers.Authorization).toBeUndefined();
  });

  it("control: 'storage' strategy DOES attach Authorization (proves the test is meaningful)", () => {
    const getInterceptor = installHttpCapture();
    window.ApiService.config.security.authStrategy = 'storage';
    window.ApiService.setupBearerAuth();
    localStorage.setItem('auth_token', 'abc123');

    const out = getInterceptor()({headers: {}});
    expect(out.headers.Authorization).toBe('Bearer abc123');
  });
});

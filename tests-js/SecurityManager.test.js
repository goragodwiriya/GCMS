import {describe, it, expect, beforeAll} from 'vitest';

import '../Now/js/SecurityManager.js';

const SM = () => window.SecurityManager;

// base64url-encode an object the way a real JWT segment is encoded.
function b64url(obj) {
  return btoa(JSON.stringify(obj))
    .replace(/\+/g, '-')
    .replace(/\//g, '_')
    .replace(/=+$/, '');
}
function makeJwt(header, payload) {
  return `${b64url(header)}.${b64url(payload)}.signature`;
}

describe('SecurityManager — proto-pollution guards', () => {
  it('isUnsafeKey flags dangerous keys', () => {
    expect(SM().isUnsafeKey('__proto__')).toBe(true);
    expect(SM().isUnsafeKey('constructor')).toBe(true);
    expect(SM().isUnsafeKey('prototype')).toBe(true);
    expect(SM().isUnsafeKey('name')).toBe(false);
  });

  it('safeMerge does not pollute Object.prototype', () => {
    const malicious = JSON.parse('{"__proto__":{"polluted":true}}');
    SM().safeMerge({}, malicious);
    expect({}.polluted).toBeUndefined();
  });

  it('mergeDeep (used by init) does not pollute Object.prototype', () => {
    const malicious = JSON.parse('{"__proto__":{"hacked":1}}');
    SM().mergeDeep({}, malicious);
    expect({}.hacked).toBeUndefined();
  });

  it('safeSetByPath refuses __proto__ traversal', () => {
    const obj = {};
    SM().safeSetByPath(obj, '__proto__.polluted', true);
    expect({}.polluted).toBeUndefined();
    expect(obj.polluted).toBeUndefined();
  });

  it('safeSetByPath still sets ordinary nested paths', () => {
    const obj = {};
    SM().safeSetByPath(obj, 'a.b.c', 5);
    expect(obj.a.b.c).toBe(5);
  });
});

describe('SecurityManager — HTML/URL sanitization', () => {
  it('escapeHtml neutralizes angle brackets and quotes', () => {
    const out = SM().escapeHtml('<img src=x onerror="alert(1)">');
    expect(out).not.toContain('<');
    expect(out).not.toContain('"');
    expect(out).toContain('&lt;');
  });

  it('sanitizeHtml falls back to escaping when no sanitizer is present', () => {
    // TemplateManager/DOMPurify are not loaded in this test → escape fallback.
    const out = SM().sanitizeHtml('<script>alert(1)</script>');
    expect(out).not.toContain('<script>');
  });

  it('sanitizeUrl blocks javascript: and data: schemes', () => {
    expect(SM().sanitizeUrl('javascript:alert(1)')).toBe('');
    expect(SM().sanitizeUrl('data:text/html,<script>')).toBe('');
    expect(SM().sanitizeUrl('https://example.com/x')).toBe('https://example.com/x');
  });
});

describe('SecurityManager — JWT structural validation', () => {
  const future = Math.floor(Date.now() / 1000) + 3600;
  const host = window.location.hostname;

  it('accepts a well-formed unexpired HS256 token for our iss/aud', () => {
    const t = makeJwt({alg: 'HS256', typ: 'JWT'}, {exp: future, iss: host, aud: host});
    expect(SM().validateJWTToken(t)).toBe(true);
  });

  it('rejects alg:none tokens', () => {
    const t = makeJwt({alg: 'none'}, {exp: future, iss: host, aud: host});
    expect(SM().validateJWTToken(t)).toBe(false);
  });

  it('rejects a token whose algorithm is not the configured one', () => {
    const t = makeJwt({alg: 'RS256'}, {exp: future, iss: host, aud: host});
    expect(SM().validateJWTToken(t)).toBe(false);
  });

  it('rejects expired tokens', () => {
    const past = Math.floor(Date.now() / 1000) - 10;
    const t = makeJwt({alg: 'HS256'}, {exp: past, iss: host, aud: host});
    expect(SM().validateJWTToken(t)).toBe(false);
  });

  it('rejects not-yet-valid (nbf) tokens', () => {
    const t = makeJwt({alg: 'HS256'}, {exp: future, nbf: future, iss: host, aud: host});
    expect(SM().validateJWTToken(t)).toBe(false);
  });

  it('rejects malformed tokens', () => {
    expect(SM().validateJWTToken('not-a-jwt')).toBe(false);
    expect(SM().validateJWTToken('')).toBe(false);
  });
});

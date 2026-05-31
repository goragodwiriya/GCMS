import {describe, it, expect, beforeAll} from 'vitest';

// SecurityManager must exist first — TableManager helpers delegate to it.
import '../Now/js/SecurityManager.js';
import '../Now/js/TableManager.js';

const TM = () => window.TableManager;

describe('TableManager XSS helpers', () => {
  it('escapeCellValue neutralizes HTML payloads', () => {
    const out = TM().escapeCellValue('<img src=x onerror=alert(1)>');
    expect(out).not.toContain('<img');
    expect(out).toContain('&lt;');
  });

  it('escapeCellValue handles null/number', () => {
    expect(TM().escapeCellValue(null)).toBe('');
    expect(TM().escapeCellValue(42)).toBe('42');
  });

  it('sanitizeCellHtml strips script tags from opt-in html', () => {
    const out = TM().sanitizeCellHtml('<b>ok</b><script>alert(1)</script>');
    expect(out.toLowerCase()).not.toContain('<script');
  });
});

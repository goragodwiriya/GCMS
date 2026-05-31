import {describe, it, expect} from 'vitest';

// Verifies the test harness can side-effect-import a global-attach classic
// script and read the global it installs on window.
import '../Now/js/Utils.js';

describe('test harness', () => {
  it('loads a global-attach module and exposes it on window', () => {
    expect(window.Utils).toBeDefined();
    expect(typeof window.Utils.string.escape).toBe('function');
  });
});

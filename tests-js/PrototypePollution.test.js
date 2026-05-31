import {describe, it, expect, afterEach} from 'vitest';

import '../Now/js/Utils.js';
import '../Now/js/SecurityConfig.js';
import '../Now/js/ReactiveManager.js';
import '../Now/js/StateManager.js';

// Ensure a leaked pollution from one test can't mask another.
afterEach(() => {
  delete Object.prototype.polluted;
  delete Object.prototype.hacked;
});

describe('Utils.object proto-pollution guards', () => {
  it('merge does not pollute Object.prototype', () => {
    window.Utils.object.merge({}, JSON.parse('{"__proto__":{"polluted":true}}'));
    expect({}.polluted).toBeUndefined();
  });

  it('merge still merges ordinary nested keys', () => {
    const out = window.Utils.object.merge({a: {x: 1}}, {a: {y: 2}});
    expect(out.a).toEqual({x: 1, y: 2});
  });

  it('deepClone does not carry __proto__ into the prototype chain', () => {
    const clone = window.Utils.object.deepClone(JSON.parse('{"__proto__":{"hacked":true},"safe":1}'));
    expect({}.hacked).toBeUndefined();
    expect(clone.safe).toBe(1);
  });
});

describe('SecurityConfig.mergeDeep proto-pollution guard', () => {
  it('does not pollute via __proto__', () => {
    window.SecurityConfig.mergeDeep({}, JSON.parse('{"__proto__":{"polluted":true}}'));
    expect({}.polluted).toBeUndefined();
  });

  it('getMergedConfig ignores malicious overrides', () => {
    window.SecurityConfig.getMergedConfig('production', JSON.parse('{"__proto__":{"isAdmin":true}}'));
    expect({}.isAdmin).toBeUndefined();
  });
});

describe('ReactiveManager.setStateValue proto-pollution guard', () => {
  it('refuses __proto__ path', () => {
    window.ReactiveManager.setStateValue({}, '__proto__.polluted', true);
    expect({}.polluted).toBeUndefined();
  });

  it('still sets ordinary paths', () => {
    const state = {a: {b: 0}};
    window.ReactiveManager.setStateValue(state, 'a.b', 9);
    expect(state.a.b).toBe(9);
  });
});

describe('StateManager.set proto-pollution guard', () => {
  it('refuses __proto__ path without throwing', () => {
    expect(() => window.StateManager.set('__proto__.polluted', true)).not.toThrow();
    expect({}.polluted).toBeUndefined();
  });
});

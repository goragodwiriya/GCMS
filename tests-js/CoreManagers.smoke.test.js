import {describe, it, expect} from 'vitest';

// Load the framework core first (defines window.Now), then managers, mirroring
// Now.resources.core order. A throw here = a load-time regression.
import '../Now/Now.js';
import '../Now/js/Utils.js';
import '../Now/js/NotificationManager.js';
import '../Now/js/ErrorManager.js';
import '../Now/js/EventManager.js';
import '../Now/js/I18nManager.js';
import '../Now/js/TemplateManager.js';
import '../Now/js/StateManager.js';
import '../Now/js/ReactiveManager.js';
import '../Now/js/StorageManager.js';
import '../Now/js/SecurityConfig.js';
import '../Now/js/SecurityManager.js';
import '../Now/js/ResponseHandler.js';
import '../Now/js/HttpClient.js';
import '../Now/js/ApiService.js';
import '../Now/js/TokenService.js';

const expected = {
  Now: [],
  Utils: ['object', 'string'],
  TemplateManager: ['sanitizeElement', 'sanitizeUrlAttribute'],
  StateManager: ['set', 'get'],
  ReactiveManager: ['setStateValue'],
  SecurityConfig: ['mergeDeep', 'getMergedConfig'],
  SecurityManager: ['sanitizeHtml', 'escapeHtml', 'sanitizeUrl', 'safeMerge', 'safeSetByPath', 'validateJWTToken', 'isUnsafeKey'],
  ResponseHandler: ['init', 'sanitizeContent', 'isUnsafeAttrName', 'sanitizeAttrValue'],
  HttpClient: [],
  ApiService: [],
  TokenService: [],
};

describe('core managers load and expose their global', () => {
  for (const [name, methods] of Object.entries(expected)) {
    it(`${name} is registered on window`, () => {
      expect(window[name], `${name} missing on window`).toBeDefined();
      for (const m of methods) {
        const target = window[name];
        const has = typeof target[m] === 'function' || typeof target[m] === 'object' ||
          (target.prototype && typeof target.prototype[m] === 'function');
        expect(has, `${name}.${m} missing`).toBe(true);
      }
    });
  }
});

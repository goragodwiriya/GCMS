// Real-browser acceptance for the httpOnly-cookie auth migration.
// Drives Chrome via puppeteer-core against the running app and asserts the
// access token is NEVER visible to JavaScript (no localStorage, no JS cookie),
// while the session still works (cookie-backed /verify) across a reload.
//
//   node scripts/browser_auth_check.js
//
// Requires: a running web server + a test user (the runner script creates one).
import puppeteer from 'puppeteer-core';
import {existsSync} from 'fs';

const BASE = 'http://localhost/projects';
const API = `${BASE}/api.php/index/auth`;
const USER = 'browsertest@example.test';
const PASS = 'Test#12345';

const CHROME = ['/usr/bin/google-chrome', '/usr/bin/chromium-browser', '/snap/bin/chromium']
  .find(p => existsSync(p));

let failed = false;
const check = (label, cond) => { console.log(`${cond ? 'PASS' : 'FAIL'}  ${label}`); if (!cond) failed = true; };

const browser = await puppeteer.launch({
  executablePath: CHROME,
  headless: 'new',
  args: ['--no-sandbox', '--disable-setuid-sandbox']
});

try {
  const page = await browser.newPage();
  await page.goto(`${BASE}/`, {waitUntil: 'networkidle2', timeout: 30000});

  // Perform the login the way the client does: get a CSRF token, then POST
  // credentials with credentials:'include' so the browser stores the httpOnly cookie.
  const loginResult = await page.evaluate(async (api, user, pass) => {
    const csrfRes = await fetch(`${api}/csrf-token`, {credentials: 'include'});
    const csrf = (await csrfRes.json())?.data?.csrf_token;
    const body = new URLSearchParams({username: user, password: pass});
    const res = await fetch(`${api}/login`, {
      method: 'POST',
      credentials: 'include',
      headers: {'X-CSRF-TOKEN': csrf, 'X-Requested-With': 'XMLHttpRequest'},
      body
    });
    const json = await res.json();
    return {status: res.status, success: json.success, hasTokenInBody: 'token' in (json.data || {})};
  }, API, USER, PASS);

  check('login succeeds in-browser', loginResult.success === true);
  check('login response body carries NO token', loginResult.hasTokenInBody === false);

  // The critical XSS guarantee: the token must be invisible to JavaScript.
  const jsVisible = await page.evaluate((api) => {
    const ls = Object.keys(localStorage);
    return {
      localStorageKeys: ls,
      hasAuthTokenLS: ls.includes('auth_token'),
      // httpOnly cookies are NOT exposed in document.cookie
      cookieHasAuthToken: document.cookie.includes('auth_token'),
      documentCookie: document.cookie
    };
  }, API);

  check('auth_token NOT in localStorage', jsVisible.hasAuthTokenLS === false);
  check('auth_token NOT readable via document.cookie (httpOnly)', jsVisible.cookieHasAuthToken === false);

  // Session works via the cookie: /verify returns the user with no JS token involved.
  const verify1 = await page.evaluate(async (api) => {
    const r = await fetch(`${api}/verify`, {credentials: 'include'});
    return (await r.json()).success;
  }, API);
  check('verify succeeds via httpOnly cookie (no JS token)', verify1 === true);

  // Reload, then verify again — proves the session survives a page reload using
  // only the server-set cookie (no token rehydrated from JS storage).
  await page.reload({waitUntil: 'networkidle2'});
  const verify2 = await page.evaluate(async (api) => {
    const r = await fetch(`${api}/verify`, {credentials: 'include'});
    return (await r.json()).success;
  }, API);
  check('session persists after reload (cookie-backed)', verify2 === true);

  console.log(`\ndocument.cookie after login: ${JSON.stringify(jsVisible.documentCookie)}`);
  console.log(`localStorage keys: ${JSON.stringify(jsVisible.localStorageKeys)}`);
} finally {
  await browser.close();
}

console.log(failed ? '\nBROWSER ACCEPTANCE: FAILURES PRESENT' : '\nBROWSER ACCEPTANCE: ALL PASSED');
process.exit(failed ? 1 : 0);

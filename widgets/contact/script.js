/**
 * Contact widget — frontend submit handler.
 *
 * Loaded on every frontend page (widgets/{name}/script.js are auto-included).
 * Handles any `<form data-contact-notify>` (the Contact widget's form) by
 * posting to api/index/contactsend, which notifies every admin across all
 * channels (e-mail, LINE, Telegram).
 */
(function () {
  'use strict';

  var ENDPOINT = (typeof WEB_URL === 'string' ? WEB_URL : '/') + 'api/index/contactsend';

  function setStatus(form, message, ok) {
    var el = form.querySelector('[data-contact-status]');
    if (!el) {
      return;
    }
    el.hidden = false;
    el.textContent = message;
    el.className = 'widget-contact__status ' + (ok ? 'is-ok' : 'is-error');
  }

  async function fetchCsrfToken() {
    try {
      var res = await fetch((typeof WEB_URL === 'string' ? WEB_URL : '/') + 'api/index/auth/csrf-token', {
        credentials: 'same-origin',
        headers: {'X-Requested-With': 'XMLHttpRequest'}
      });
      var json = await res.json();
      return (json && json.data && json.data.csrf_token) || '';
    } catch (e) {
      return '';
    }
  }

  async function onSubmit(event) {
    var form = event.target;
    if (!form || typeof form.matches !== 'function' || !form.matches('form[data-contact-notify]')) {
      return;
    }
    event.preventDefault();

    var button = form.querySelector('[type="submit"]');
    if (button) {
      button.disabled = true;
    }

    try {
      var token = await fetchCsrfToken();
      var body = new URLSearchParams(new FormData(form)).toString();
      var res = await fetch(ENDPOINT, {
        method: 'POST',
        credentials: 'same-origin',
        headers: {
          'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
          'X-Requested-With': 'XMLHttpRequest',
          'X-CSRF-TOKEN': token
        },
        body: body
      });
      var json = await res.json().catch(function () { return {}; });

      if (res.ok && json && json.success) {
        form.reset();
        setStatus(form, json.message || 'OK', true);
      } else {
        var firstError = json && json.errors ? Object.values(json.errors)[0] : null;
        setStatus(form, firstError || (json && json.message) || 'Error', false);
      }
    } catch (e) {
      setStatus(form, (e && e.message) || String(e), false);
    } finally {
      if (button) {
        button.disabled = false;
      }
    }
  }

  document.addEventListener('submit', onSubmit);
}());

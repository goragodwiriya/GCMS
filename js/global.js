/* ─────────────────────────────────────────────────────────────
 * GCMS Designer launcher
 * Loads the homepage-only visual editor and shows controls to Super Admin.
 * ───────────────────────────────────────────────────────────── */
(function initDesignerLauncher() {
  const webUrl = () => (typeof WEB_URL !== 'undefined' ? WEB_URL : '/');

  /** Match cache-busting on global.js (e.g. ?v=...) so designer assets refresh with theme deploys. */
  function designerAssetIdsAndQuery() {
    let v = '';
    const el = document.querySelector('script[src*="js/global.js"]');
    if (el) {
      try {
        v = new URL(el.src, window.location.origin).searchParams.get('v') || '';
      } catch (e) {
        v = '';
      }
    }
    const safe = v.replace(/\W/g, '_').replace(/_+/g, '_').replace(/^_|_$/g, '');
    const q = v ? `?v=${encodeURIComponent(v)}` : '';
    const suf = safe ? `_${safe}` : '';
    return {
      query: q,
      cssId: `designer-css${suf}`,
      jsId: `designer-js${suf}`
    };
  }

  function isHomePage() {
    return !!document.querySelector('.homepage[data-gcms-page="home"], .homepage');
  }

  function getCurrentTheme() {
    const links = Array.from(document.querySelectorAll('link[href*="/themes/"],link[href*="themes/"]'));
    const themeLink = links.find(link => /themes\/[^/]+\/css\/styles\.css/.test(link.href));
    const match = themeLink ? themeLink.href.match(/themes\/([^/]+)\/css\/styles\.css/) : null;
    return match ? match[1] : 'wk';
  }

  function resumeEditKey() {
    return `gcms_home_designer_resume_edit_${getCurrentTheme()}_home`;
  }

  function shouldResumeDesigner() {
    try {
      return sessionStorage.getItem(resumeEditKey()) === '1';
    } catch (e) {
      return false;
    }
  }

  function loadAssetOnce(type, url, id) {
    if (document.getElementById(id)) return Promise.resolve();

    return new Promise((resolve, reject) => {
      const el = document.createElement(type === 'css' ? 'link' : 'script');
      el.id = id;
      if (type === 'css') {
        el.rel = 'stylesheet';
        el.href = url;
      } else {
        el.src = url;
        el.async = false;
      }
      el.onload = resolve;
      el.onerror = () => reject(new Error(`Cannot load ${url}`));
      document.head.appendChild(el);
    });
  }

  async function loadHomeDesignerCss() {
    const {query, cssId} = designerAssetIdsAndQuery();
    await loadAssetOnce('css', webUrl() + 'Now/dist/gcms.designer.min.css' + query, cssId);
  }

  async function loadHomeDesigner() {
    const {query, jsId} = designerAssetIdsAndQuery();
    await loadHomeDesignerCss();
    await loadAssetOnce('js', webUrl() + 'Now/dist/gcms.designer.min.js' + query, jsId);
    return window.Designer;
  }

  function getStoredUser() {
    try {
      const raw = localStorage.getItem('auth_user');
      return raw ? JSON.parse(raw) : null;
    } catch (e) {
      return null;
    }
  }

  async function getCurrentUser() {
    const stored = getStoredUser();
    if (stored && stored.id) return stored;

    try {
      const response = await fetch(webUrl() + 'api/index/auth/me', {
        method: 'GET',
        credentials: 'include'
      });
      const result = await response.json();
      return result?.data || null;
    } catch (e) {
      return null;
    }
  }

  let fabCheckTimer = null;

  /** Theme Designer / FAB แสดงเฉพาะหน้าจอใหญ่ (desktop) */
  const DESIGNER_MIN_WIDTH_PX = 1024;
  function isLargeScreenForDesigner() {
    return typeof window.matchMedia === 'function'
      && window.matchMedia(`(min-width: ${DESIGNER_MIN_WIDTH_PX}px)`).matches;
  }

  /** ปิด Theme Designer แบบไม่ถาม (logout) */
  async function forceExitDesignerIfActive() {
    try {
      if (window.Designer?.isActive?.()) {
        window.Designer.deactivate({force: true});
      }
    } catch (e) {
      /* ignore */
    }
  }

  /**
   * ถ้ากำลังแก้หน้า (Designer active) อย่าลบ FAB ตอนจอเล็ก — ซ่อนด้วย CSS แทน
   * เมื่อจอกลับใหญ่จะแก้ต่อได้ทันทีโดยไม่ต้องเข้าโหมดใหม่
   */
  async function refreshSuperAdminFab() {
    if (!isHomePage()) return;
    try {
      const user = await getCurrentUser();
      const superAdmin = Number(user?.id) === 1;
      const large = isLargeScreenForDesigner();
      const editing = typeof window.Designer !== 'undefined' && window.Designer.isActive?.();

      if (superAdmin && large) {
        await loadHomeDesignerCss();
        createFAB();
      } else if (!editing) {
        removeHomeDesignerFab();
      }
    } catch (e) {
      /* silent — guests / offline */
    }
  }

  function scheduleSuperAdminFabCheck() {
    if (!isHomePage()) return;
    clearTimeout(fabCheckTimer);
    fabCheckTimer = setTimeout(() => refreshSuperAdminFab(), 80);
  }

  async function tryInit() {
    if (!isHomePage()) return;

    scheduleSuperAdminFabCheck();
    if (!shouldResumeDesigner()) return;

    try {
      const designer = await loadHomeDesigner();
      if (designer?.resumeEditIfRequested) {
        await designer.resumeEditIfRequested();
      }
    } catch (e) {
      console.warn('[GCMS] Home designer assets or state load failed', e);
    }
  }

  function removeHomeDesignerFab() {
    document.getElementById('gcms-admin-fab')?.remove();
  }

  function createFAB() {
    if (!isLargeScreenForDesigner()) return;
    if (document.getElementById('gcms-admin-fab')) return;

    const fab = document.createElement('div');
    fab.id = 'gcms-admin-fab';

    // Trigger button
    const trigger = document.createElement('button');
    trigger.className = 'gcms-fab-trigger';
    trigger.setAttribute('title', Now.translate('Theme Designer'));
    trigger.setAttribute('aria-haspopup', 'menu');
    trigger.setAttribute('aria-expanded', 'false');
    trigger.innerHTML = '<span class="icon-design"></span>';
    trigger.addEventListener('click', (e) => {
      e.stopPropagation();
      fab.classList.toggle('gcms-fab-open');
      trigger.setAttribute('aria-expanded', fab.classList.contains('gcms-fab-open') ? 'true' : 'false');
    });

    // Menu
    const menu = document.createElement('div');
    menu.className = 'gcms-fab-menu';

    // ── Edit Webpage (visual designer) ──────────────────────
    const editBtn = document.createElement('button');
    editBtn.className = 'gcms-fab-item gcms-fab-item-edit';
    editBtn.innerHTML = `<span class='icon-edit'></span>${Now.translate('Edit Webpage')}`;
    editBtn.addEventListener('click', async (e) => {
      e.stopPropagation();
      fab.classList.remove('gcms-fab-open');
      trigger.setAttribute('aria-expanded', 'false');
      const designer = await loadHomeDesigner();
      if (designer) {
        if (designer.isActive()) {
          designer.deactivate();
          if (!designer.isActive()) {
            fab.classList.remove('gcms-fab-editing');
          }
        } else {
          if (!isLargeScreenForDesigner()) {
            if (typeof Now !== 'undefined' && typeof Now.translate === 'function') {
              window.alert(Now.translate('Theme Designer is available on large screens only.'));
            }
            return;
          }
          await designer.activate();
          if (designer.isActive()) {
            fab.classList.add('gcms-fab-editing');
          }
        }
      }
    });
    menu.appendChild(editBtn);
    fab.appendChild(menu);
    fab.appendChild(trigger);
    document.body.appendChild(fab);

    // Close on outside click
    document.addEventListener('click', () => {
      fab.classList.remove('gcms-fab-open');
      trigger.setAttribute('aria-expanded', 'false');
    });
  }

  // After Now.init + createApp (auth_user / session ready for /me)
  document.addEventListener('gcms:now-ready', () => scheduleSuperAdminFabCheck());

  // Auth lifecycle: re-check FAB when session may have changed (detail shape varies by event)
  ['auth:initialized', 'auth:login', 'auth:restored'].forEach(evt => {
    document.addEventListener(evt, () => scheduleSuperAdminFabCheck());
  });
  document.addEventListener('auth:logout', async () => {
    await forceExitDesignerIfActive();
    removeHomeDesignerFab();
  });

  document.addEventListener('DOMContentLoaded', tryInit);
  window.addEventListener('load', tryInit);
  window.addEventListener('resize', () => {
    if (isHomePage()) scheduleSuperAdminFabCheck();
  });
})();

function initProfile(element, data) {
  const input = element.querySelector('#birthday');
  const display = element.querySelector('.dropdown-display');

  const updateAge = () => {
    if (input.value) {
      const birth = new Date(input.value);
      const age = Math.floor((Date.now() - birth) / 31557600000);

      // Format date with standard pattern (YYYY uses locale-based year: BE for Thai, CE for others)
      const formattedDate = Utils.date.format(input.value, 'D MMMM YYYY');

      display.textContent = `${formattedDate} (${age} ${Now.translate('years')})`;
    } else {
      display.textContent = '';
    }
  };

  input.addEventListener('change', updateAge);
  updateAge();

  // Return cleanup function (optional)
  return () => {
    input.removeEventListener('change', updateAge);
  };
}

function copyToClipboard(cell, rawValue, rowData, attributes) {
  if (rawValue) {
    const link = document.createElement('a');
    link.className = 'icon-copy';
    link.textContent = rawValue;
    link.style.cursor = 'pointer';
    link.addEventListener('click', () => Utils.dom.copyToClipboard(String(rawValue)));
    cell.innerHTML = '';
    cell.appendChild(link);
  } else {
    cell.textContent = '';
  }
}

(function initScrollPerformanceAudit() {
  const STORAGE_KEY = 'gcms_perf_debug';
  const SAMPLE_LIMIT = 600;

  const safeStorage = {
    get(key) {
      try {
        return window.localStorage.getItem(key);
      } catch (e) {
        return null;
      }
    },
    set(key, value) {
      try {
        window.localStorage.setItem(key, value);
      } catch (e) {
        // Ignore storage errors in private browsing or restricted contexts.
      }
    },
    remove(key) {
      try {
        window.localStorage.removeItem(key);
      } catch (e) {
        // Ignore storage errors in private browsing or restricted contexts.
      }
    }
  };

  const params = new URLSearchParams(window.location.search);
  const queryFlag = params.get('perf_debug');

  if (queryFlag === '1') {
    safeStorage.set(STORAGE_KEY, '1');
  } else if (queryFlag === '0') {
    safeStorage.remove(STORAGE_KEY);
  }

  const enabled = queryFlag === '1' || safeStorage.get(STORAGE_KEY) === '1';

  const state = {
    enabled,
    startedAt: performance.now(),
    observers: [],
    frameId: null,
    frameCount: 0,
    slowFrameCount: 0,
    frameDeltas: [],
    scrollEmitDurations: [],
    eventEmitDurations: [],
    longTasks: [],
    blurDisabled: false,
    blurStyleEl: null
  };

  const pushSample = (target, value) => {
    if (!Number.isFinite(value)) return;
    target.push(value);
    if (target.length > SAMPLE_LIMIT) {
      target.shift();
    }
  };

  const percentile = (values, p) => {
    if (!values.length) return 0;
    const sorted = [...values].sort((a, b) => a - b);
    const index = Math.min(sorted.length - 1, Math.max(0, Math.round((p / 100) * (sorted.length - 1))));
    return sorted[index];
  };

  const durationStats = (values) => {
    if (!values.length) {
      return {count: 0, avg: 0, p95: 0, max: 0};
    }

    const total = values.reduce((sum, value) => sum + value, 0);
    return {
      count: values.length,
      avg: total / values.length,
      p95: percentile(values, 95),
      max: Math.max(...values)
    };
  };

  const collectScriptResources = () => {
    return performance
      .getEntriesByType('resource')
      .filter((entry) => entry.initiatorType === 'script')
      .map((entry) => ({
        name: entry.name,
        duration: entry.duration,
        transferKB: entry.transferSize > 0 ? entry.transferSize / 1024 : 0,
        encodedKB: entry.encodedBodySize > 0 ? entry.encodedBodySize / 1024 : 0
      }))
      .sort((a, b) => b.duration - a.duration)
      .slice(0, 10);
  };

  const getScrollListenerInfo = () => {
    if (window.EventManager && typeof window.EventManager.getEventInfo === 'function') {
      return window.EventManager.getEventInfo('scroll:progress');
    }
    return null;
  };

  const createBlurStyle = () => {
    const style = document.createElement('style');
    style.id = 'gcms-perf-disable-blur';
    style.textContent = [
      '.section-bg,',
      '.widget__bg,',
      '.page-content section {',
      '  background-color: rgba(255, 255, 255, 0.94) !important;',
      '  border: 1px solid rgba(15, 23, 42, 0.08) !important;',
      '}'
    ].join('\n');
    return style;
  };

  const setBlurDisabled = (disabled) => {
    if (disabled) {
      if (!state.blurStyleEl) {
        state.blurStyleEl = createBlurStyle();
      }
      if (!state.blurStyleEl.isConnected) {
        document.head.appendChild(state.blurStyleEl);
      }
    } else if (state.blurStyleEl && state.blurStyleEl.isConnected) {
      state.blurStyleEl.remove();
    }
    state.blurDisabled = disabled;
  };

  const report = () => {
    const now = performance.now();
    const runtimeSec = (now - state.startedAt) / 1000;
    const frameStats = durationStats(state.frameDeltas);
    const scrollEmit = durationStats(state.scrollEmitDurations);
    const eventEmit = durationStats(state.eventEmitDurations);
    const longTaskStats = durationStats(state.longTasks);
    const scriptResources = collectScriptResources();
    const scrollInfo = getScrollListenerInfo();
    const blurCandidates = Array.from(document.querySelectorAll('.section-bg, .widget__bg, .page-content section'));
    const activeBlurTargets = blurCandidates.filter((element) => {
      const styles = window.getComputedStyle(element);
      const backdrop = (styles.backdropFilter || styles.webkitBackdropFilter || '').toLowerCase();
      const filter = (styles.filter || '').toLowerCase();
      return backdrop.includes('blur(') || filter.includes('blur(');
    }).length;

    const summary = {
      runtimeSec: Number(runtimeSec.toFixed(1)),
      frameSamples: state.frameCount,
      slowFrames: state.slowFrameCount,
      frameDropRate: state.frameCount > 0 ? Number(((state.slowFrameCount / state.frameCount) * 100).toFixed(2)) : 0,
      frameAvgMs: Number(frameStats.avg.toFixed(2)),
      frameP95Ms: Number(frameStats.p95.toFixed(2)),
      longTaskCount: longTaskStats.count,
      longTaskP95Ms: Number(longTaskStats.p95.toFixed(2)),
      scrollEmitAvgMs: Number(scrollEmit.avg.toFixed(2)),
      scrollEmitP95Ms: Number(scrollEmit.p95.toFixed(2)),
      eventEmitAvgMs: Number(eventEmit.avg.toFixed(2)),
      eventEmitP95Ms: Number(eventEmit.p95.toFixed(2)),
      scrollProgressListeners: scrollInfo?.listenerCount || 0,
      blurTargets: blurCandidates.length,
      activeBlurTargets,
      blurDisabled: state.blurDisabled
    };

    console.group('[PerfAudit] Scroll diagnosis report');
    console.table(summary);

    if (scriptResources.length > 0) {
      console.log('[PerfAudit] Top script resources by duration');
      console.table(scriptResources.map((entry) => ({
        name: entry.name,
        durationMs: Number(entry.duration.toFixed(2)),
        transferKB: Number(entry.transferKB.toFixed(1)),
        encodedKB: Number(entry.encodedKB.toFixed(1))
      })));
    }

    if (activeBlurTargets >= 5) {
      console.warn('[PerfAudit] Many blur targets detected. Test with GcmsPerfAudit.disableBlur() and compare report.');
    } else if (summary.blurTargets >= 5 && activeBlurTargets === 0) {
      console.info('[PerfAudit] Blur candidate nodes exist, but active blur is currently disabled by styles.');
    }

    if (summary.scrollProgressListeners >= 5) {
      console.warn('[PerfAudit] High scroll:progress listener count detected. Inspect EventManager.getEventInfo("scroll:progress").');
    }

    console.log('[PerfAudit] Commands: GcmsPerfAudit.report(), GcmsPerfAudit.disableBlur(), GcmsPerfAudit.enableBlur(), GcmsPerfAudit.disable()');
    console.groupEnd();

    return {
      summary,
      scripts: scriptResources,
      scrollEventInfo: scrollInfo
    };
  };

  const stop = () => {
    if (state.frameId) {
      cancelAnimationFrame(state.frameId);
      state.frameId = null;
    }

    state.observers.forEach((observer) => observer.disconnect());
    state.observers = [];
  };

  const instrumentScrollManager = () => {
    if (!window.ScrollManager || typeof window.ScrollManager.emit !== 'function') return;
    if (window.ScrollManager.__perfAuditPatched) return;

    const originalEmit = window.ScrollManager.emit.bind(window.ScrollManager);
    window.ScrollManager.emit = function patchedScrollEmit(eventName, data) {
      const started = performance.now();
      const result = originalEmit(eventName, data);
      const duration = performance.now() - started;

      if (eventName === 'scroll:progress') {
        pushSample(state.scrollEmitDurations, duration);
      }

      return result;
    };

    window.ScrollManager.__perfAuditPatched = true;
  };

  const instrumentEventManager = () => {
    if (!window.EventManager || typeof window.EventManager.emit !== 'function') return;
    if (window.EventManager.__perfAuditPatched) return;

    const originalEmit = window.EventManager.emit.bind(window.EventManager);
    window.EventManager.emit = async function patchedEventEmit(eventName, data) {
      const started = performance.now();
      const result = await originalEmit(eventName, data);
      const duration = performance.now() - started;

      if (eventName === 'scroll:progress') {
        pushSample(state.eventEmitDurations, duration);
      }

      return result;
    };

    window.EventManager.__perfAuditPatched = true;
  };

  const startFrameProbe = () => {
    let previous = performance.now();

    const tick = (time) => {
      const delta = time - previous;
      previous = time;

      state.frameCount += 1;
      pushSample(state.frameDeltas, delta);

      if (delta > 20) {
        state.slowFrameCount += 1;
      }

      state.frameId = requestAnimationFrame(tick);
    };

    state.frameId = requestAnimationFrame(tick);
  };

  const startPerformanceObservers = () => {
    if (typeof PerformanceObserver === 'undefined') return;

    const supported = PerformanceObserver.supportedEntryTypes || [];

    if (supported.includes('longtask')) {
      const longTaskObserver = new PerformanceObserver((list) => {
        list.getEntries().forEach((entry) => {
          pushSample(state.longTasks, entry.duration);
        });
      });

      longTaskObserver.observe({entryTypes: ['longtask']});
      state.observers.push(longTaskObserver);
    }
  };

  window.GcmsPerfAudit = {
    isEnabled: enabled,
    enable() {
      safeStorage.set(STORAGE_KEY, '1');
      window.location.reload();
    },
    disable() {
      safeStorage.remove(STORAGE_KEY);
      window.location.reload();
    },
    report,
    disableBlur() {
      setBlurDisabled(true);
      return report();
    },
    enableBlur() {
      setBlurDisabled(false);
      return report();
    },
    stop
  };

  if (!enabled) {
    return;
  }

  instrumentScrollManager();
  instrumentEventManager();
  startPerformanceObservers();
  startFrameProbe();

  window.addEventListener('beforeunload', stop, {once: true});

  window.setTimeout(() => {
    report();
  }, 2500);

  console.info('[PerfAudit] Enabled. Use GcmsPerfAudit.report() in DevTools console.');
}());

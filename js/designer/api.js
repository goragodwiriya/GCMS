/**
 * GCMS Designer – API & Asset Loading
 *
 * CSRF token management, JSON fetch wrapper, dynamic asset injection,
 * and FileBrowser loader.
 *
 * @filesource js/designer/api.js
 */

let csrfTokenMemo = null;

export function registerApi(ctx) {

  ctx.getCsrfTokenForApi = async function() {
    const meta = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')?.trim();
    if (meta) return meta;
    if (csrfTokenMemo) return csrfTokenMemo;
    try {
      const response = await fetch(ctx.webUrl() + 'api/index/auth/csrf-token', {credentials: 'include'});
      const data = await response.json().catch(() => ({}));
      csrfTokenMemo = data?.data?.csrf_token || data?.csrf_token || '';
      return csrfTokenMemo;
    } catch (e) {
      return '';
    }
  };

  ctx.fetchJson = async function(url, options = {}) {
    const method = String(options.method || 'GET').toUpperCase();
    const headers = {
      'Content-Type': 'application/json',
      ...(options.headers || {})
    };
    if (['POST', 'PUT', 'PATCH', 'DELETE'].includes(method)) {
      const t = await ctx.getCsrfTokenForApi();
      if (t) headers['X-CSRF-Token'] = t;
    }
    const response = await fetch(url, {
      credentials: 'include',
      ...options,
      headers
    });
    const data = await response.json().catch(() => ({}));
    if (!response.ok) {
      throw new Error(data.message || data.error || `HTTP ${response.status}`);
    }
    return data;
  };

  ctx.loadAssetOnce = function(type, url, id) {
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
  };

  ctx.loadFileBrowser = async function() {
    if (window.FileBrowser) return window.FileBrowser;

    const base = ctx.webUrl() + 'js/components/editor/plugins/filebrowser/';
    const {query, idSuffix} = ctx.globalScriptCacheParams();
    await ctx.loadAssetOnce('css', base + 'filebrowser.css' + query, 'gcms-filebrowser-css' + idSuffix);

    const jsUrl = base + 'FileBrowser.js' + query;
    try {
      const mod = await import(jsUrl);
      const FB = mod?.default;
      if (typeof FB === 'function') {
        window.FileBrowser = FB;
        return FB;
      }
    } catch (e) {
      console.warn('[GCMS Designer] FileBrowser module load failed', e);
    }
    return null;
  };

  ctx.loadWidgetManifests = async function(force = false) {
    if (ctx.state.widgetManifestsLoaded && !force) return ctx.state.widgetManifests;

    const result = await ctx.fetchJson(`${ctx.webUrl()}api/index/widgets/manifest`, {method: 'GET'});
    const items = result?.data?.items || result?.items || [];
    ctx.state.widgetManifests = Array.isArray(items) ? items : [];
    ctx.state.widgetManifestsLoaded = true;
    return ctx.state.widgetManifests;
  };

  ctx.chooseImage = async function(img, previewEl = null) {
    try {
      const Browser = await ctx.loadFileBrowser();
      if (!Browser) {
        ctx.notify(ctx.translate('Could not load FileBrowser — enter the image URL in the field below instead'));
        ctx.editImageByUrl(img, previewEl);
        return;
      }

      const endpoint = `${ctx.webUrl()}js/components/editor/php/filebrowser.php?action=`;
      const browser = new Browser({
        showPresetTab: true,
        activeTab: 1,
        allowedFileTypes: 'image/*',
        useApiClient: false,
        apiActions: {
          getPresetCategories: endpoint + 'get_preset_categories',
          getPresets: endpoint + 'get_presets',
          getFiles: endpoint + 'get_files',
          getFolderTree: endpoint + 'get_folder_tree',
          upload: endpoint + 'upload',
          createFolder: endpoint + 'create_folder',
          rename: endpoint + 'rename',
          delete: endpoint + 'delete',
          copy: endpoint + 'copy',
          move: endpoint + 'move'
        },
        onSelect: (file) => {
          if (file?.url) {
            img.setAttribute('src', file.url);
            img.setAttribute('alt', file.name || '');
            if (previewEl) {
              previewEl.setAttribute('src', file.url);
              previewEl.setAttribute('alt', file.name || '');
            }
            ctx.markChanged();
          }
        },
        onError: () => ctx.editImageByUrl(img, previewEl)
      });
      browser.open();
    } catch (e) {
      console.warn('[GCMS Designer] chooseImage', e);
      ctx.notify(ctx.translate('Could not open FileBrowser — enter the image URL manually'));
      ctx.editImageByUrl(img, previewEl);
    }
  };

  ctx.editImageByUrl = function(img, previewEl = null) {
    const src = prompt(ctx.translate('Image URL'), img.getAttribute('src') || '');
    if (src !== null && src.trim() !== '') {
      img.setAttribute('src', src.trim());
      if (previewEl) previewEl.setAttribute('src', src.trim());
      ctx.markChanged();
    }
  };

  ctx.chooseBackgroundImageForBlock = async function(block, textareaEl = null) {
    try {
      const Browser = await ctx.loadFileBrowser();
      if (!Browser) {
        ctx.notify(ctx.translate('Could not load FileBrowser — set the background value manually'));
        return;
      }
      const endpoint = `${ctx.webUrl()}js/components/editor/php/filebrowser.php?action=`;
      const browser = new Browser({
        showPresetTab: true,
        activeTab: 1,
        allowedFileTypes: 'image/*',
        useApiClient: false,
        apiActions: {
          getPresetCategories: endpoint + 'get_preset_categories',
          getPresets: endpoint + 'get_presets',
          getFiles: endpoint + 'get_files',
          getFolderTree: endpoint + 'get_folder_tree',
          upload: endpoint + 'upload',
          createFolder: endpoint + 'create_folder',
          rename: endpoint + 'rename',
          delete: endpoint + 'delete',
          copy: endpoint + 'copy',
          move: endpoint + 'move'
        },
        onSelect: (file) => {
          if (file?.url) {
            const v = `url(${JSON.stringify(file.url)})`;
            block.style.backgroundImage = v;
            if (textareaEl) textareaEl.value = v;
            ctx.markChanged();
          }
        },
        onError: () => {
          const raw = prompt(ctx.translate('background-image (e.g. url(https://...) or linear-gradient(...))'), (block.style.backgroundImage || '').trim());
          if (raw !== null && raw.trim() !== '') {
            const t = raw.trim();
            block.style.backgroundImage = t;
            if (textareaEl) textareaEl.value = t;
            ctx.markChanged();
          }
        }
      });
      browser.open();
    } catch (e) {
      console.warn('[GCMS Designer] chooseBackgroundImageForBlock', e);
      ctx.notify(ctx.translate('Could not open FileBrowser'));
    }
  };
}

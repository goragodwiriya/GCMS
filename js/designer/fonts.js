/**
 * GCMS Designer – Font Management
 *
 * Google Fonts detection, loading, and homepage font collection.
 *
 * @filesource js/designer/fonts.js
 */

export function registerFonts(ctx) {

  ctx.extractGoogleFontFamilyName = function(fontValue) {
    const raw = String(fontValue || '').trim();
    if (!raw) return '';
    const fromQuotes = raw.match(/"([^"]+)"/)?.[1];
    if (fromQuotes) return fromQuotes.trim();
    const first = raw.split(',')[0].trim().replace(/^['"]|['"]$/g, '');
    return first || '';
  };

  ctx.isGenericOrBundledFontName = function(name) {
    const n = String(name || '').toLowerCase().trim();
    if (!n || n.startsWith('var(')) return true;
    const generic = new Set([
      'inherit', 'initial', 'unset', 'sans-serif', 'serif', 'monospace', 'system-ui', 'cursive', 'fantasy',
      'ui-sans-serif', 'ui-serif', 'ui-monospace', 'apple-system', 'blinkmacsystemfont', 'segoe ui', 'emoji',
      'helvetica', 'arial', 'roboto', 'noto sans', 'noto serif',
      'prompt', 'sarabun'
    ]);
    return generic.has(n);
  };

  ctx.loadGoogleFontStylesheet = function(familyName) {
    const family = String(familyName || '').trim();
    if (!family) return;
    if (ctx.isGenericOrBundledFontName(family)) return;

    const idSlug = family.toLowerCase().replace(/\s+/g, '-').replace(/[^a-z0-9-]/g, '');
    const id = `gcms-font-${idSlug || 'custom'}`;
    if (document.getElementById(id)) return;

    const link = document.createElement('link');
    link.id = id;
    link.rel = 'stylesheet';
    link.href = `https://fonts.googleapis.com/css2?family=${encodeURIComponent(family)}:wght@300;400;500;600;700&display=swap`;
    document.head.appendChild(link);
  };

  ctx.ensureGoogleFont = function(fontValue) {
    const family = ctx.extractGoogleFontFamilyName(fontValue);
    if (!family) return;
    ctx.loadGoogleFontStylesheet(family);
  };

  ctx.fontFamiliesFromStyleString = function(styleStr) {
    const out = [];
    const m = String(styleStr || '').match(/font-family\s*:\s*([^;]+)/i);
    if (!m) return out;
    for (const part of m[1].split(',')) {
      let name = part.trim();
      const quoted = name.match(/^["']([^"']+)["']$/);
      if (quoted) name = quoted[1];
      else name = name.replace(/^["']|["']$/g, '').trim();
      if (!name || ctx.isGenericOrBundledFontName(name)) continue;
      out.push(name);
    }
    return out;
  };

  ctx.collectFontFamilyNamesFromHomepageDom = function() {
    const root = ctx.homepageDesignerRoot();
    if (!root) return [];
    const seen = new Set();
    const out = [];
    root.querySelectorAll('[style]').forEach(el => {
      if (el.closest('#gcms-designer-panel, #designer-bar')) return;
      ctx.fontFamiliesFromStyleString(el.getAttribute('style') || '').forEach(name => {
        const key = name.toLowerCase();
        if (seen.has(key)) return;
        seen.add(key);
        out.push(name);
      });
    });
    return out;
  };

  ctx.preloadGoogleFontsForHomepage = function() {
    ctx.collectFontFamilyNamesFromHomepageDom().forEach(name => ctx.loadGoogleFontStylesheet(name));
  };

  ctx.applyFontsFromSavedState = function(saved) {
    if (!saved || !Array.isArray(saved.googleFonts)) return;
    saved.googleFonts.forEach(raw => {
      const name = String(raw || '').trim();
      if (!name || ctx.isGenericOrBundledFontName(name)) return;
      ctx.loadGoogleFontStylesheet(name);
    });
  };
}

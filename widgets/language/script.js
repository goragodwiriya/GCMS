/**
 * Language links carry the query of the page they were rendered on (?module=…&id=…&lang=xx).
 * PageNavigator (Now.js) changes the page without re-rendering this widget, so the
 * links are rebuilt from the address now shown.
 */
document.addEventListener('page:loaded', () => {
  document.querySelectorAll('.widget-language a[hreflang]').forEach(link => {
    const url = new URL(location.href);
    url.searchParams.set('lang', link.getAttribute('hreflang'));
    link.setAttribute('href', url.search);
  });
});

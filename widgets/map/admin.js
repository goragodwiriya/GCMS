/**
 * Map widget — settings page live binding.
 *
 * Wired via data-on-load="initMapSettings" on the Map settings form. Keeps the
 * Leaflet preview (widgets/map/iframe.html?edit=1) and the form in sync:
 *   - form field changes  → postMessage config to the iframe (live refresh)
 *   - map click / drag / search in the iframe → update lat/lng/zoom fields
 *
 * The iframe is same-origin, so postMessage works both ways. Returns a
 * teardown function so the listener is removed when the panel is closed.
 */
function initMapSettings(element) {
  const form = element && element.matches && element.matches('form')
    ? element
    : (element && element.querySelector('#map-settings-form')) || element;
  if (!form) {
    return;
  }
  const iframe = form.querySelector('#map-preview');
  if (!iframe) {
    return;
  }

  const names = ['lat', 'lng', 'zoom', 'height', 'marker_icon', 'popup_name', 'popup_address', 'google_maps_url'];
  const els = {};
  names.forEach((n) => { els[n] = form.querySelector('[name="' + n + '"]'); });

  const readConfig = () => ({
    lat: els.lat ? els.lat.value : '',
    lng: els.lng ? els.lng.value : '',
    zoom: els.zoom ? els.zoom.value : '',
    marker_icon: els.marker_icon ? els.marker_icon.value : '',
    popup_name: els.popup_name ? els.popup_name.value : '',
    popup_address: els.popup_address ? els.popup_address.value : '',
    google_maps_url: els.google_maps_url ? els.google_maps_url.value : ''
  });

  const sendConfig = () => {
    if (iframe.contentWindow) {
      iframe.contentWindow.postMessage({type: 'gcms-map-config', config: readConfig()}, '*');
    }
  };

  let timer = null;
  const sendDebounced = () => {
    clearTimeout(timer);
    timer = setTimeout(sendConfig, 250);
  };

  const applyHeight = () => {
    if (els.height) {
      iframe.style.height = (parseInt(els.height.value, 10) || 450) + 'px';
    }
  };

  // Live refresh when any map-config field changes.
  ['lat', 'lng', 'zoom', 'marker_icon', 'popup_name', 'popup_address', 'google_maps_url'].forEach((n) => {
    if (els[n]) {
      els[n].addEventListener('input', sendDebounced);
      els[n].addEventListener('change', sendDebounced);
    }
  });
  if (els.height) {
    els.height.addEventListener('input', applyHeight);
    els.height.addEventListener('change', applyHeight);
    applyHeight();
  }

  // Messages coming back from the map (ready / user picked a location).
  const onMessage = (ev) => {
    if (ev.source !== iframe.contentWindow) {
      return;
    }
    const d = ev.data;
    if (!d || typeof d !== 'object') {
      return;
    }
    if (d.type === 'gcms-map-ready') {
      applyHeight();
      sendConfig();
    } else if (d.type === 'gcms-map-pick') {
      if (els.lat && d.lat != null) {
        els.lat.value = Number(d.lat).toFixed(7);
      }
      if (els.lng && d.lng != null) {
        els.lng.value = Number(d.lng).toFixed(7);
      }
      if (els.zoom && d.zoom != null) {
        els.zoom.value = d.zoom;
      }
      // Reflect the change into the form (marks it dirty for save). The map is
      // already showing the pick, so no config is echoed back.
      [els.lat, els.lng, els.zoom].forEach((el) => {
        if (el) {
          el.dispatchEvent(new Event('change', {bubbles: true}));
        }
      });
    }
  };
  window.addEventListener('message', onMessage);

  return () => {
    window.removeEventListener('message', onMessage);
    clearTimeout(timer);
  };
}

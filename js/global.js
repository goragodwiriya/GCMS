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

/**
 * Format with options status
 */
function formatTableOptionStatus(cell, rawValue, rowData, attributes) {
  const opts = attributes.lookupOptions || attributes.tableDataOptions || attributes.tableFilterOptions;

  // Normalizer: build a map value->text
  const makeMap = (options) => {
    if (!options) return new Map();
    if (Array.isArray(options)) {
      // [{value,text}, ...]
      return new Map(options.map(o => [String(o.value), o.text]));
    }
    // object map {val: label, ...}
    return new Map(Object.entries(options).map(([k, v]) => [String(k), v]));
  };

  const map = makeMap(opts);

  const key = rawValue === null || rawValue === undefined ? '' : String(rawValue);
  const label = map.has(key) ? map.get(key) : (rawValue && rawValue.text) ? rawValue.text : key;
  const index = map.has(key) ? Array.from(map.keys()).indexOf(key) : -1;


  cell.innerHTML = `<span class="status${index}" data-i18n>${label}</span>`;
}

function formatStarStatus(cell, rawValue, rowData, attributes) {
  if (rawValue === 'active' || parseInt(rawValue) > 0) {
    cell.innerHTML = '<span class="icon-star2 color-primary"></span>';
  } else {
    cell.innerHTML = '<span class="icon-star0 color-silver"></span>';
  }
}

function formatActiveStatus(cell, rawValue, rowData, attributes) {
  if (rawValue === 'active' || parseInt(rawValue) === 1) {
    cell.innerHTML = '<span class="icon-valid color-red" title="' + Now.translate('Active') + '"></span>';
  } else {
    cell.innerHTML = '<span class="icon-invalid color-silver" title="' + Now.translate('Inactive') + '"></span>';
  }
}

function formatOffline(cell, rawValue, rowData, attributes) {
  if (parseInt(rawValue) === 0) {
    cell.innerHTML = '<span class="icon-host color-green" title="' + Now.translate('Online') + '"></span>';
  } else {
    cell.innerHTML = '<span class="icon-host color-silver" title="' + Now.translate('Offline') + '"></span>';
  }
}

function formatLink(cell, rawValue, rowData, attributes) {
  if (!rawValue) {
    cell.innerHTML = '-';
    return;
  }

  const value = String(rawValue).trim();

  // Simple recognizers
  const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
  const phoneRegex = /^\+?[0-9()\s\-./]{6,}$/;
  const urlProtocolRegex = /^https?:\/\//i;

  const makeLink = (href, text, iconClass) => {
    const a = document.createElement('a');
    a.href = href;
    // Open http(s) links in new tab, others (mailto/tel) in same
    if (/^https?:\/\//i.test(href)) {
      a.target = '_blank';
      a.rel = 'noopener';
    }
    if (iconClass) a.className = iconClass;
    a.textContent = text;
    cell.innerHTML = '';
    cell.appendChild(a);
  };

  if (/^mailto:/i.test(value)) {
    makeLink(value, value.replace(/^mailto:/i, ''), 'icon-mail');
    return;
  }

  if (/^tel:/i.test(value)) {
    makeLink(value, value.replace(/^tel:/i, ''), 'icon-phone');
    return;
  }

  if (emailRegex.test(value)) {
    makeLink('mailto:' + value, value, 'icon-email');
    return;
  }

  if (phoneRegex.test(value)) {
    // Normalize phone for href (keep leading + if present)
    const telHref = 'tel:' + value.replace(/[^\d+]/g, '');
    makeLink(telHref, value, 'icon-phone');
    return;
  }

  // Fallback: treat as URL
  let href = value;
  if (!urlProtocolRegex.test(href)) href = 'http://' + href;
  const displayUrl = href.replace(/^https?:\/\//, '').replace(/\/$/, '');
  makeLink(href, displayUrl, 'icon-world');
}

function formatImage(cell, rawValue, rowData, attributes) {
  cell.innerHTML = '';
  if (!rawValue) return;

  // Build the thumbnail via DOM + style API instead of string concatenation.
  // Reject dangerous schemes and any character that could break out of the
  // CSS url() context (quotes, parens, angle brackets, whitespace, backslash).
  const url = String(rawValue).trim();
  const isDangerousScheme = /^(?:javascript|data|vbscript|file|about):/i.test(url);
  const hasUnsafeChars = /["'()\\\s<>]/.test(url);
  if (isDangerousScheme || hasUnsafeChars) return;

  const thumb = document.createElement('div');
  thumb.className = 'thumbnail';
  thumb.style.backgroundImage = `url("${url}")`;
  cell.appendChild(thumb);
}

function formatFileSize(cell, rawValue, rowData, attributes) {
  if (rawValue) {
    cell.textContent = Utils.number.fileSize(rawValue);
  } else {
    cell.textContent = '';
  }
}

/**
 * Attach a "copy to language" handler to the #copy_menu button.
 * @param {HTMLElement} element - Form root element
 * @param {string} endpoint - API endpoint to POST to
 * @param {string} saveFirstMsg - Translation key shown when id is missing
 * @returns {Function} Cleanup function that removes the listener
 */
function makeCopyButton(element, endpoint, saveFirstMsg) {
  const copyBtn = element.querySelector('#copy_menu');
  const languageSelect = element.querySelector('#language');
  if (!copyBtn || !languageSelect) return () => {};

  const handler = async () => {
    const lang = languageSelect.value;
    if (!lang) {
      NotificationManager.warning(Now.translate('Please select a language to copy to'));
      return;
    }
    const id = element.querySelector('[name="id"]')?.value;
    if (!id) {
      NotificationManager.warning(Now.translate(saveFirstMsg));
      return;
    }
    try {
      const response = await ApiService.post(endpoint, {id, language: lang});
      if (response.success) {
        NotificationManager.success(response.data.message || Now.translate('Copied successfully'));
      } else {
        NotificationManager.error(response.data.message || Now.translate('Copy failed'));
      }
    } catch {
      NotificationManager.error(Now.translate('Copy failed'));
    }
  };

  copyBtn.addEventListener('click', handler);
  return () => copyBtn.removeEventListener('click', handler);
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

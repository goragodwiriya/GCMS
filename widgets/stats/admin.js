/**
 * admin.js — Stats Widget
 *
 * initWidgetStats(element, data) is called by the widget admin system
 * after the stat.html form is loaded and populated with data.
 *
 * Responsibilities:
 *  - Update the placeholder demo text when the group name changes
 */

function initWidgetStats(element, data) {
  const nameInput = element.querySelector('input[name="name"]');
  const demoElement = element.querySelector('#name_demo');

  const nameChanged = () => {
    const name = (nameInput.value || '').trim().toLowerCase();
    if (name === '' || name === 'default') {
      demoElement.textContent = '{WIDGET_STATS}';
    } else {
      demoElement.textContent = `{WIDGET_STATS_${name}}`;
    }
  };

  if (nameInput) {
    nameInput.addEventListener('input', nameChanged);
    nameChanged();
  }

  return () => {
    if (nameInput) {
      nameInput.removeEventListener('input', nameChanged);
    }
  };
}

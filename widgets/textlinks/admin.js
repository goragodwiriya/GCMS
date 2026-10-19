/**
 * admin.js — Textlinks Widget
 *
 * initWidgetTextlink(element, data) is called by the widget admin system
 * after textlink.html is loaded and populated with data.
 *
 * Responsibilities:
 *  - Update the placeholder demo text when the group name changes
 *  - Offer the existing group names as a datalist
 *  - Show the image field only for an image menu
 *  - Hide the publish dates when the link has no schedule
 */

function initWidgetTextlink(element, data) {
  const nameInput = element.querySelector('input[name="name"]');
  const demoElement = element.querySelector('#name_demo');
  const groupList = element.querySelector('#textlink_groups');
  const typeElement = element.querySelector('#type');
  const logoGroup = element.querySelector('#logo_group');
  const datelessElement = element.querySelector('#dateless');
  const publishGroup = element.querySelector('#publish_group');

  // รายชื่อกลุ่มที่มีอยู่แล้ว มาจาก Settings::get() (modules)
  if (groupList && Array.isArray(data?.modules)) {
    groupList.innerHTML = data.modules
      .map(item => `<option value="${item.value}"></option>`)
      .join('');
  }

  const nameChanged = () => {
    const name = (nameInput.value || '').trim().toLowerCase();
    demoElement.textContent = name === '' ? '{WIDGET_TEXTLINKS}' : `{WIDGET_TEXTLINKS_${name}}`;
  };
  nameInput.addEventListener('input', nameChanged);
  nameChanged();

  const typeChanged = () => {
    logoGroup.style.display = typeElement.value === 'image' ? 'block' : 'none';
  };
  typeElement.addEventListener('change', typeChanged);
  typeChanged();

  const datelessChanged = () => {
    publishGroup.style.display = datelessElement.checked ? 'none' : 'block';
  };
  datelessElement.addEventListener('change', datelessChanged);
  datelessChanged();

  // Return cleanup function
  return () => {
    nameInput.removeEventListener('input', nameChanged);
    typeElement.removeEventListener('change', typeChanged);
    datelessElement.removeEventListener('change', datelessChanged);
  };
}

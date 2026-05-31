function initWidgetTextlink(element, data) {
  const nameInput = element.querySelector('input[name="name"]');
  const demoElement = element.querySelector('#name_demo');
  const selectElement = element.querySelector('#type');
  const templateElement = element.querySelector('#template');
  const stylesElement = element.querySelector('#styles');
  const styles = JSON.parse(stylesElement.value || '{}');
  const datelessElement = element.querySelector('#dateless');
  const publishEndElement = element.querySelector('#publish_end');

  const nameChanged = () => {
    const name = nameInput.value.trim();
    if (name === '') {
      demoElement.textContent = '{WIDGET_TEXTLINKS}';
    } else {
      demoElement.textContent = `{WIDGET_TEXTLINKS_${name.toLowerCase()}}`;
    }
  };
  nameInput.addEventListener('input', nameChanged);
  nameChanged();

  let customTemplate = null;
  const typeChanged = () => {
    if (customTemplate === null) {
      customTemplate = templateElement.value;
    }
    templateElement.disabled = selectElement.value !== 'custom';
    if (selectElement.value === 'custom') {
      templateElement.value = customTemplate || '';
    } else {
      templateElement.value = styles[selectElement.value] || '';
    }
  };
  selectElement.addEventListener('change', typeChanged);
  typeChanged();

  const datelessChanged = () => {
    publishEndElement.parentElement.parentElement.style.display = datelessElement.checked ? 'none' : 'block';
  };
  datelessElement.addEventListener('change', datelessChanged);
  datelessChanged();

  // Return cleanup function
  return () => {
    nameInput.removeEventListener('input', nameChanged);
    selectElement.removeEventListener('change', typeChanged);
    datelessElement.removeEventListener('change', datelessChanged);
  };
}

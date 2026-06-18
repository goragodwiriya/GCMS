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

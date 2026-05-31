/**
 * GCMS Designer – Audit Panel
 *
 * Collects accessibility and SEO issues from the current page state.
 *
 * @filesource js/designer/panels/Audit.js
 */

export function registerAuditPanel(ctx) {

  ctx.showAuditPanel = function() {
    const wrap = document.createElement('div');
    const issues = _collectIssues();
    const summaryHtml = issues.length === 0
      ? `<span data-i18n>No major issues found</span>`
      : ctx.escapeHtml(ctx.translate('Found {count} item(s) to review', {count: issues.length}));
    wrap.innerHTML = `
      <div class="gcms-panel-section">
        <h4 data-i18n>Page Audit</h4>
        <div class="gcms-audit-summary ${issues.length === 0 ? 'ok' : 'warn'}">
          ${summaryHtml}
        </div>
        <div class="gcms-audit-list"></div>
        <button type="button" class="btn btn-primary fullwidth" data-action="save-anyway" data-i18n>Save anyway</button>
      </div>`;

    const list = wrap.querySelector('.gcms-audit-list');
    if (issues.length === 0) {
      list.innerHTML = '<p class="gcms-layer-empty" data-i18n>Homepage looks good at a basic level.</p>';
    } else {
      issues.forEach(issue => {
        const row = document.createElement('button');
        row.type = 'button';
        row.className = `gcms-audit-item ${issue.level}`;
        row.innerHTML = `<strong>${ctx.escapeHtml(issue.title)}</strong><span>${ctx.escapeHtml(issue.detail)}</span>`;
        row.addEventListener('click', () => _focusIssue(issue));
        list.appendChild(row);
      });
    }

    wrap.querySelector('[data-action="save-anyway"]').addEventListener('click', () => ctx.save({skipAudit: true}));
    ctx.showPanel('Page Audit', wrap);
  };

  ctx.collectAuditIssues = function() {
    return _collectIssues();
  };

  ctx.hasAuditIssues = function() {
    return ctx.collectAuditIssues().length > 0;
  };

  /* ─── Private ─── */

  function _collectIssues() {
    const issues = [];
    const ids = new Map();

    ctx.editableElements().forEach(el => {
      const label = el.dataset.editorFieldName || el.dataset.editable || el.tagName.toLowerCase();
      if (!el.matches('img') && el.textContent.trim() === '') {
        issues.push({
          level: 'warn',
          title: ctx.translate('Empty text'),
          detail: label,
          element: el
        });
      }
      if (el.matches('a')) {
        const href = (el.getAttribute('href') || '').trim();
        const target = (el.getAttribute('target') || '').trim();
        const rel = (el.getAttribute('rel') || '').trim();
        if (href === '' || href === '#') {
          issues.push({
            level: 'info',
            title: ctx.translate('Link not set'),
            detail: label,
            element: el
          });
        }
        if (target === '_blank' && !/\bnoopener\b/.test(rel)) {
          issues.push({
            level: 'warn',
            title: ctx.translate('External link missing rel noopener'),
            detail: label,
            element: el
          });
        }
      }
      if (el.matches('img')) {
        const alt = (el.getAttribute('alt') || '').trim();
        if (!alt) {
          issues.push({
            level: 'warn',
            title: ctx.translate('Image missing alt text'),
            detail: el.getAttribute('src') || label,
            element: el
          });
        }
      }
    });

    ctx.blocks().forEach(block => {
      const id = block.getAttribute('id') || block.dataset.editorId || '';
      if (id) {
        if (ids.has(id)) {
          issues.push({
            level: 'error',
            title: ctx.translate('Duplicate ID: {id}', {id}),
            detail: block.dataset.blockType || '',
            element: block
          });
        } else {
          ids.set(id, block);
        }
      }
    });

    return issues;
  }

  function _focusIssue(issue) {
    if (!issue.element) return;
    const block = issue.element.closest('[data-block-type]');
    if (block) {
      ctx.selectBlock(block);
      block.scrollIntoView({behavior: 'smooth', block: 'center'});
    }
    issue.element.scrollIntoView({behavior: 'smooth', block: 'center'});
    issue.element.classList.add('gcms-block-flash');
    setTimeout(() => issue.element.classList.remove('gcms-block-flash'), 1200);
  }
}

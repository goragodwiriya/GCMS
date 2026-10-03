function initDocumentView() {
  const buttons = document.querySelectorAll('.comment-actions button[data-id]');

  buttons.forEach(button => {
    button.addEventListener('click', async () => {
      const [action, , moduleId, commentId] = button.dataset.id.split('-');

      if (action === 'delete' && !confirm(Now.translate('You want to {action} the selected items ?', {action: Now.translate('Delete')}))) {
        return;
      }

      await httpAction.post(`${WEB_URL}api/document/reply/delete`, {
        module_id: moduleId,
        id: commentId
      });
    });
  });
}

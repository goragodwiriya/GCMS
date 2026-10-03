// Register Routes for Board Module via EventManager
EventManager.on('router:initialized', () => {
  if (window.RouterManager) {
    RouterManager.register('/board-categories', {
      template: WEB_URL + 'modules/board/template/categories.html',
      title: '{LNG_Categories}',
      requireAuth: true
    });
    RouterManager.register('/board-category', {
      template: WEB_URL + 'modules/board/template/category.html',
      title: '{LNG_Category}',
      menuPath: '/board-categories',
      requireAuth: true
    });
    RouterManager.register('/board-settings', {
      template: WEB_URL + 'modules/board/template/settings.html',
      title: '{LNG_Settings}',
      requireAuth: true
    });
  }
});

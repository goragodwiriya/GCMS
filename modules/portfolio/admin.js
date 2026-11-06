// Register Routes for Portfolio Module via EventManager
EventManager.on('router:initialized', () => {
  if (window.RouterManager) {
    RouterManager.register('/portfolio-list', {
      template: WEB_URL + 'modules/portfolio/template/setup.html',
      title: '{LNG_Portfolio}',
      requireAuth: true
    });
    RouterManager.register('/portfolio', {
      template: WEB_URL + 'modules/portfolio/template/write.html',
      title: '{LNG_Portfolio}',
      requireAuth: true
    });
    RouterManager.register('/portfolio-settings', {
      template: WEB_URL + 'modules/portfolio/template/settings.html',
      title: '{LNG_Settings}',
      requireAuth: true
    });
  }
});

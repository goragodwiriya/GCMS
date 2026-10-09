// Register Routes for Event Module via EventManager
EventManager.on('router:initialized', () => {
  if (window.RouterManager) {
    RouterManager.register('/event-list', {
      template: WEB_URL + 'modules/event/template/setup.html',
      title: '{LNG_Event Calendar}',
      requireAuth: true
    });
    RouterManager.register('/event', {
      template: WEB_URL + 'modules/event/template/write.html',
      title: '{LNG_Event}',
      menuPath: '/event-list',
      requireAuth: true
    });
    RouterManager.register('/event-settings', {
      template: WEB_URL + 'modules/event/template/settings.html',
      title: '{LNG_Settings}',
      requireAuth: true
    });
  }
});

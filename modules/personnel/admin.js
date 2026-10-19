// Register Routes for Personnel Module via EventManager
EventManager.on('router:initialized', () => {
  if (window.RouterManager) {
    RouterManager.register('/personnels', {
      template: WEB_URL + 'modules/personnel/template/setup.html',
      title: '{LNG_Personnel}',
      requireAuth: true
    });
    RouterManager.register('/personnel', {
      template: WEB_URL + 'modules/personnel/template/write.html',
      title: '{LNG_Personnel}',
      requireAuth: true
    });
    RouterManager.register('/personnel-settings', {
      template: WEB_URL + 'modules/personnel/template/settings.html',
      title: '{LNG_Settings}',
      requireAuth: true
    });
    RouterManager.register('/personnel-category', {
      template: WEB_URL + 'modules/personnel/template/category.html',
      title: '{LNG_Category}',
      requireAuth: true
    });
  }
});

// Register Routes for Gallery Module via EventManager
EventManager.on('router:initialized', () => {
  if (window.RouterManager) {
    RouterManager.register('/gallery-albums', {
      template: WEB_URL + 'modules/gallery/template/setup.html',
      title: '{LNG_Gallery Albums}',
      requireAuth: true
    });
    RouterManager.register('/gallery-album', {
      template: WEB_URL + 'modules/gallery/template/write.html',
      title: '{LNG_Album}',
      menuPath: '/gallery-albums',
      requireAuth: true
    });
    RouterManager.register('/gallery-settings', {
      template: WEB_URL + 'modules/gallery/template/settings.html',
      title: '{LNG_Settings}',
      requireAuth: true
    });
  }
});

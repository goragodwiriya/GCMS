// Register Routes for Video Module via EventManager
EventManager.on('router:initialized', () => {
  if (window.RouterManager) {
    RouterManager.register('/video-list', {
      template: WEB_URL + 'modules/video/template/setup.html',
      title: '{LNG_Video}',
      requireAuth: true
    });
    RouterManager.register('/video', {
      template: WEB_URL + 'modules/video/template/write.html',
      title: '{LNG_Video}',
      menuPath: '/video-list',
      requireAuth: true
    });
    RouterManager.register('/video-settings', {
      template: WEB_URL + 'modules/video/template/settings.html',
      title: '{LNG_Settings}',
      requireAuth: true
    });
  }
});

// Register routes for Download module via EventManager
EventManager.on('router:initialized', () => {
    if (window.RouterManager) {
        RouterManager.register('/download-setup', {
            template: WEB_URL + 'modules/download/template/setup.html',
            title: '{LNG_List of} {LNG_Download file}',
            requireAuth: true
        });

        RouterManager.register('/download-write', {
            template: WEB_URL + 'modules/download/template/write.html',
            title: '{LNG_Download file}',
            menuPath: '/download-setup',
            requireAuth: true
        });

        RouterManager.register('/download-category', {
            template: WEB_URL + 'modules/download/template/category.html',
            title: '{LNG_Category}',
            requireAuth: true
        });

        RouterManager.register('/download-settings', {
            template: WEB_URL + 'modules/download/template/settings.html',
            title: '{LNG_Settings}',
            requireAuth: true
        });
    }
});

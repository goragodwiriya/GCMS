// Register routes for E-Document module via EventManager
EventManager.on('router:initialized', () => {
    if (window.RouterManager) {
        RouterManager.register('/edocument-setup', {
            template: WEB_URL + 'modules/edocument/template/setup.html',
            title: '{LNG_List of} {LNG_E-Document}',
            requireAuth: true
        });

        RouterManager.register('/edocument-write', {
            template: WEB_URL + 'modules/edocument/template/write.html',
            title: '{LNG_E-Document}',
            menuPath: '/edocument-setup',
            requireAuth: true
        });

        RouterManager.register('/edocument-report', {
            template: WEB_URL + 'modules/edocument/template/report.html',
            title: '{LNG_Download Details}',
            menuPath: '/edocument-setup',
            requireAuth: true
        });

        RouterManager.register('/edocument-settings', {
            template: WEB_URL + 'modules/edocument/template/settings.html',
            title: '{LNG_Settings}',
            requireAuth: true
        });
    }
});

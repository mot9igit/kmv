Ext.onReady(function () {
    kmv.config.connector_url = OfficeConfig.actionUrl;

    var grid = new kmv.panel.Home();
    grid.render('office-kmv-wrapper');

    var preloader = document.getElementById('office-preloader');
    if (preloader) {
        preloader.parentNode.removeChild(preloader);
    }
});
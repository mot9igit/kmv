kmv.page.Home = function (config) {
    config = config || {};
    Ext.applyIf(config, {
        components: [{
            xtype: 'kmv-panel-home',
            renderTo: 'kmv-panel-home-div'
        }]
    });
    kmv.page.Home.superclass.constructor.call(this, config);
};
Ext.extend(kmv.page.Home, MODx.Component);
Ext.reg('kmv-page-home', kmv.page.Home);
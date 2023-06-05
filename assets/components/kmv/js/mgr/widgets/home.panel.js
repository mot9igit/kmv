kmv.panel.Home = function (config) {
    config = config || {};
    Ext.apply(config, {
        baseCls: 'modx-formpanel',
        layout: 'anchor',
        /*
         stateful: true,
         stateId: 'kmv-panel-home',
         stateEvents: ['tabchange'],
         getState:function() {return {activeTab:this.items.indexOf(this.getActiveTab())};},
         */
        hideMode: 'offsets',
        items: [{
            html: '<h2>' + _('kmv') + '</h2>',
            cls: '',
            style: {margin: '15px 0'}
        }, {
            xtype: 'modx-tabs',
            defaults: {border: false, autoHeight: true},
            border: true,
            hideMode: 'offsets',
            items: [{
                title: _('kmv_items'),
                layout: 'anchor',
                items: [{
                    html: _('kmv_intro_msg'),
                    cls: 'panel-desc',
                }, {
                    xtype: 'kmv-grid-items',
                    cls: 'main-wrapper',
                }]
            }]
        }]
    });
    kmv.panel.Home.superclass.constructor.call(this, config);
};
Ext.extend(kmv.panel.Home, MODx.Panel);
Ext.reg('kmv-panel-home', kmv.panel.Home);

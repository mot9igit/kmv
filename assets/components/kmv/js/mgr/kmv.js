var kmv = function (config) {
    config = config || {};
    kmv.superclass.constructor.call(this, config);
};
Ext.extend(kmv, Ext.Component, {
    page: {}, window: {}, grid: {}, tree: {}, panel: {}, combo: {}, config: {}, view: {}, utils: {}
});
Ext.reg('kmv', kmv);

kmv = new kmv();
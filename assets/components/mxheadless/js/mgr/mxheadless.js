var MxHeadless = function(config) {
    config = config || {};
    MxHeadless.superclass.constructor.call(this, config);
};
Ext.extend(MxHeadless, Ext.Component, {
    page: {}, window: {}, grid: {}, tree: {}, panel: {}, combo: {}, config: {}
});
Ext.reg('mxheadless', MxHeadless);
MxHeadless = new MxHeadless();

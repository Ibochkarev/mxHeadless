MxHeadless.page.Home = function(config) {
    config = config || {};
    Ext.applyIf(config, {
        components: [{
            xtype: 'mxheadless-panel-home',
            renderTo: 'mxheadless-panel-home-div'
        }]
    });
    MxHeadless.page.Home.superclass.constructor.call(this, config);
};
Ext.extend(MxHeadless.page.Home, MODx.Component);
Ext.reg('mxheadless-page-home', MxHeadless.page.Home);

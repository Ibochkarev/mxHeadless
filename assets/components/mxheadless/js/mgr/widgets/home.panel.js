MxHeadless.panel.Home = function(config) {
    config = config || {};
    Ext.apply(config, {
        border: false,
        baseCls: 'modx-formpanel',
        cls: 'container',
        items: [{
            html: '<h2>' + _('mxheadless') + '</h2>',
            border: false,
            cls: 'modx-page-header'
        }, {
            xtype: 'modx-tabs',
            defaults: {border: false, autoHeight: true},
            items: [{
                title: _('mxheadless_apikeys'),
                layout: 'anchor',
                items: [{
                    html: '<p>' + _('mxheadless_apikeys_intro') + '</p>',
                    border: false,
                    bodyCssClass: 'panel-desc'
                }, {
                    xtype: 'mxheadless-grid-apikeys',
                    cls: 'main-wrapper',
                    preventRender: true
                }]
            }]
        }]
    });
    MxHeadless.panel.Home.superclass.constructor.call(this, config);
};
Ext.extend(MxHeadless.panel.Home, MODx.Panel);
Ext.reg('mxheadless-panel-home', MxHeadless.panel.Home);

MxHeadless.grid.ApiKeys = function(config) {
    config = config || {};
    Ext.applyIf(config, {
        id: 'mxheadless-grid-apikeys',
        url: MxHeadless.config.connectorUrl,
        baseParams: {
            action: 'mgr/apikey/getlist'
        },
        fields: [
            'id', 'name', 'lookup_id', 'scopes', 'revoked',
            'created_on', 'created_on_formatted',
            'last_used_on', 'last_used_on_formatted',
            'rate_limit_max', 'rate_limit_window'
        ],
        paging: true,
        remoteSort: true,
        autoHeight: true,
        columns: [{
            header: _('mxheadless_apikey_id'),
            dataIndex: 'id',
            sortable: true,
            width: 50
        }, {
            header: _('mxheadless_apikey_name'),
            dataIndex: 'name',
            sortable: true,
            width: 160
        }, {
            header: _('mxheadless_apikey_lookup'),
            dataIndex: 'lookup_id',
            sortable: true,
            width: 140
        }, {
            header: _('mxheadless_apikey_scopes'),
            dataIndex: 'scopes',
            sortable: false,
            width: 220
        }, {
            header: _('mxheadless_apikey_revoked'),
            dataIndex: 'revoked',
            sortable: true,
            width: 80,
            renderer: function(v) {
                return v ? _('mxheadless_yes') : _('mxheadless_no');
            }
        }, {
            header: _('mxheadless_apikey_created_on'),
            dataIndex: 'created_on_formatted',
            sortable: true,
            width: 120
        }, {
            header: _('mxheadless_apikey_last_used'),
            dataIndex: 'last_used_on_formatted',
            sortable: false,
            width: 120
        }],
        tbar: [{
            text: _('mxheadless_btn_create'),
            cls: 'primary-button',
            handler: this.createKey,
            scope: this
        }, '->', {
            xtype: 'textfield',
            emptyText: _('mxheadless_search'),
            listeners: {
                change: {fn: this.filter, scope: this},
                specialkey: {
                    fn: function(f, e) {
                        if (e.getKey() === e.ENTER) {
                            this.filter(f);
                        }
                    },
                    scope: this
                }
            }
        }],
        listeners: {
            rowDblClick: function(grid, rowIndex) {
                var rec = grid.store.getAt(rowIndex);
                this.updateKey(rec);
            },
            scope: this
        }
    });
    MxHeadless.grid.ApiKeys.superclass.constructor.call(this, config);
};

Ext.extend(MxHeadless.grid.ApiKeys, MODx.grid.Grid, {
    getMenu: function() {
        var m = [];
        m.push({
            text: _('mxheadless_apikey_update'),
            handler: this.updateKeyFromMenu
        });
        if (!this.menu.record.revoked) {
            m.push('-');
            m.push({
                text: _('mxheadless_apikey_revoke'),
                handler: this.revokeKey
            });
        }
        return m;
    },

    filter: function(tf) {
        this.getStore().baseParams.query = tf.getValue();
        this.getBottomToolbar().changePage(1);
    },

    createKey: function() {
        var w = MODx.load({
            xtype: 'mxheadless-window-apikey',
            title: _('mxheadless_apikey_create'),
            action: 'mgr/apikey/create',
            listeners: {
                success: {
                    fn: function(r) {
                        this.refresh();
                        var token = r.a && r.a.result && r.a.result.object
                            ? r.a.result.object.token
                            : (r.object ? r.object.token : '');
                        if (token) {
                            Ext.Msg.alert(
                                _('mxheadless_apikey_create'),
                                _('mxheadless_apikey_created_token') + '<br><br><textarea style="width:100%;height:60px">' +
                                    Ext.util.Format.htmlEncode(token) + '</textarea>'
                            );
                        }
                    },
                    scope: this
                }
            }
        });
        w.show();
    },

    updateKeyFromMenu: function() {
        this.updateKey(this.menu.record);
    },

    updateKey: function(rec) {
        var data = rec.data ? rec.data : rec;
        var w = MODx.load({
            xtype: 'mxheadless-window-apikey',
            title: _('mxheadless_apikey_update'),
            action: 'mgr/apikey/update',
            record: data,
            listeners: {
                success: {fn: this.refresh, scope: this}
            }
        });
        w.fp.getForm().setValues(data);
        w.show();
    },

    revokeKey: function() {
        if (!this.menu.record || !this.menu.record.id) {
            return;
        }
        Ext.Msg.confirm(
            _('mxheadless_apikey_revoke'),
            _('mxheadless_apikey_revoke_confirm'),
            function(btn) {
                if (btn !== 'yes') {
                    return;
                }
                MODx.Ajax.request({
                    url: this.config.url,
                    params: {
                        action: 'mgr/apikey/revoke',
                        id: this.menu.record.id
                    },
                    listeners: {
                        success: {fn: this.refresh, scope: this}
                    }
                });
            },
            this
        );
    }
});
Ext.reg('mxheadless-grid-apikeys', MxHeadless.grid.ApiKeys);

MxHeadless.window.ApiKey = function(config) {
    config = config || {};
    Ext.applyIf(config, {
        title: _('mxheadless_apikey_create'),
        url: MxHeadless.config.connectorUrl,
        width: 520,
        autoHeight: true,
        fields: [{
            xtype: 'hidden',
            name: 'id'
        }, {
            xtype: 'textfield',
            fieldLabel: _('mxheadless_apikey_name'),
            name: 'name',
            anchor: '100%',
            allowBlank: false
        }, {
            xtype: 'textarea',
            fieldLabel: _('mxheadless_apikey_scopes'),
            name: 'scopes',
            anchor: '100%',
            height: 80,
            value: 'resources.read,contexts.read',
            description: _('mxheadless_apikey_scopes_help')
        }, {
            xtype: 'numberfield',
            fieldLabel: _('mxheadless_apikey_rate_limit_max'),
            name: 'rate_limit_max',
            anchor: '100%',
            allowNegative: false
        }, {
            xtype: 'numberfield',
            fieldLabel: _('mxheadless_apikey_rate_limit_window'),
            name: 'rate_limit_window',
            anchor: '100%',
            allowNegative: false
        }]
    });
    MxHeadless.window.ApiKey.superclass.constructor.call(this, config);
};
Ext.extend(MxHeadless.window.ApiKey, MODx.Window);
Ext.reg('mxheadless-window-apikey', MxHeadless.window.ApiKey);

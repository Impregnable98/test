/**
 * Loads the TV creation page
 *
 * @class MODx.page.CreateTV
 * @extends MODx.Component
 * @param {Object} config An object of config properties
 * @xtype modx-page-tv-create
 */
MODx.page.CreateTV = function(config) {
    config = config || {};
    Ext.applyIf(config,{
        formpanel: 'modx-panel-tv'
        ,buttons: [{
            process: 'Element/Tv/Create'
            ,reload: true
            ,text: _('save')
            ,id: 'modx-abtn-save'
            ,cls: 'primary-button'
            ,method: 'remote'
            ,keys: [{
                key: MODx.config.keymap_save || 's'
                ,ctrl: true
            }]
        },{
            text: _('cancel')
            ,id: 'modx-abtn-cancel'
            ,handler: function() {
                MODx.loadPage('?');
            }
        },{
            text: '<i class="icon icon-question-circle"></i>'
            ,id: 'modx-abtn-help'
            ,handler: MODx.loadHelpPane
        }]
        ,components: [{
            xtype: 'modx-panel-tv'
            ,renderTo: 'modx-panel-tv-div'
            ,record: config.record || {}
        }]
    });
    MODx.page.CreateTV.superclass.constructor.call(this,config);
};
Ext.extend(MODx.page.CreateTV,MODx.Component);
Ext.reg('modx-page-tv-create',MODx.page.CreateTV);

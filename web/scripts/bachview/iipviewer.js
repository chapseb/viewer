var BIIPMooViewer = new Class({
    Extends: IIPMooViewer,

    /*
     * Constructor - see documentation for options
     */
    initialize: function( main_id, options ) {
        this.parent(main_id, options);
        // Navigation window options
        this.navigation = null;
        if( (typeof(BNavigation)==="function") ){
            this.navigation = new BNavigation({
                showNavWindow:options.showNavWindow,
                showNavButtons: options.showNavButtons,
                navWinSize: options.navWinSize,
                showCoords: options.showCoords,
                prefix: this.prefix
            });
        }
    },

    /**
     * 
     */
    createWindows: function(){
        var _this = this;
        this.parent();

        this.navigation.addEvents({
            'rotate': function(r){
                var _r = _this.view.rotation;
                _r += r % 360;
                _this.rotate(_r);
                if( IIPMooViewer.sync ) IIPMooViewer.windows(_this).invoke( 'rotate', _r );
            }
        });
    }
});

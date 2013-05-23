var BIIPMooViewer = new Class({
    Extends: IIPMooViewer,

    /*
     * Constructor - see documentation for options
     */
    initialize: function( main_id, options ) {
        this.parent(main_id, options);
        if ( options.disableContextMenu == false ) {
            this.disableContextMenu = false;
        }
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

        //replace infos, since we do not provide annotations
        $$('div.info').destroy();
        new Element( 'div', {
            'class': 'info',
            'styles': { opacity: 0 },
            'events': {
                click: function(){ this.fade('out'); }
            },
            'html': '<div><div><h2><a href="http://iipimage.sourceforge.net"><img src="'+this.prefix+'iip.32x32.png"/></a>IIPMooViewer</h2>IIPImage HTML5 Ajax High Resolution Image Viewer - Version '+this.version+'<br/><ul><li>'+IIPMooViewer.lang.navigate+'</li><li>'+IIPMooViewer.lang.zoomIn+'</li><li>'+IIPMooViewer.lang.zoomOut+'</li><li>'+IIPMooViewer.lang.rotate+'</li><li>'+IIPMooViewer.lang.fullscreen+'</li><li>'+IIPMooViewer.lang.navigation+'</li></ul><br/>'+IIPMooViewer.lang.more+' <a href="http://iipimage.sourceforge.net">http://iipimage.sourceforge.net</a></div></div>'
        }).inject( this.container );
    },


    /*
     * Calculate navigation view size
     */
    calculateNavSize: function()    {

        var thumb_width = Math.round(this.view.w * this.navigation.options.navWinSize);

        // For panoramic images, use a large navigation window
        /*if( this.max_size.w > 2*this.max_size.h ) thumb_width = Math.round( this.view.w/2 );*/

        // Make sure our height is not more than 50% of view height
        if( (this.max_size.h/this.max_size.w)*thumb_width > this.view.h*0.5 ){
            thumb_width = Math.round( this.view.h * 0.5 * this.max_size.w/this.max_size.h );
        }

        this.navigation.size.x = thumb_width;
        this.navigation.size.y = Math.round( (this.max_size.h/this.max_size.w)*thumb_width );
    }
});

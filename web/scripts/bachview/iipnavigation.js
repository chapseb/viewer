var BNavigation = new Class({
    Extends: Navigation,

    /**
     * Overrides IIP navigation
     */
    initialize: function( options ) {
        this.parent(options);
        this.options.navWinSize = options.navWinSize || 0.3;
    },

    /**
     * Overrides IIP navigation
     */
    create: function( container ) {
        this.parent(container);

        // Add events to our buttons
        var _this = this;
        $$('#zoomin').addEvent( 'click', function(){ _this.fireEvent('zoomIn'); });
        $$('#zoomout').addEvent( 'click', function(){ _this.fireEvent('zoomOut'); });
        $$('#fitsize').addEvent( 'click', function(){ _this.fireEvent('reload'); });
        $$('#rrotate').addEvent( 'click', function(){
            _r = 90;
            _this.fireEvent('rotate', _r);
        });
        $$('#lrotate').addEvent( 'click', function(){
            _r = -90;
            _this.fireEvent('rotate', _r);
        });
    },

    /**
     * Overrides IIP navigation
     */
    update: function(x,y,w,h){
        this.parent(x,y,w,h);
        this.update_status();
    },

    /* update scale info in the container */
    update_status: function()
    {
        var _current_size = _iipviewer.max_size.w / Math.pow(2, _iipviewer.num_resolutions -1 - _iipviewer.view.res);
        var percent = (_current_size * 100 /  _iipviewer.max_size.w).round(2);
        if(percent)
        {
            $$('#zoominfos').set('text', percent + "%");
        }
    },

});

var BNavigation = new Class({
    Extends: Navigation,

    /**
     * Overrides IIP navigation
     */
    create: function( container ){
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
    }
});

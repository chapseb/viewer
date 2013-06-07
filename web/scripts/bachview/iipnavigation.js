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
        $$('#thumbnails').addEvent('click', function(){
            var _thumbview = $$('#thumbnails_view');
            if ( _thumbview.length > 0 ) {
                _thumbview.destroy();
            } else {
                _thumbview = new Element('div', {id: 'thumbnails_view'});

                var _req = new Request.JSON({
                    url: '/ajax/series/thumbs',
                    onSuccess: function(data){
                        var _thumbs = data['thumbs'];
                        var _meta = data['meta'];
                        for ( var i = 0 ; i < data['thumbs'].length ; i++ ) {
                            var _src =  _iipviewer.server + '?FIF=' + _thumbs[i].path + '&SDS=0,90&CNT=1.0&WID=' + _meta.width  + '&HEI=' + _meta.height  + '&QLT=99&CVT=jpeg'
                            var _img = new Element('img', {
                                src: _src,
                                alt: ''
                            });
                            var _a = new Element('a', {
                                href: '?img=' + _thumbs[i].name,
                                style: 'width:' + _meta.width  + 'px;height:' + _meta.height + 'px;line-height:' + _meta.height  + 'px;'
                            });
                            _a.store('name', _thumbs[i].name);
                            _a.store('path', _thumbs[i].path);
                            _a.addEvent('click', function(e){
                                _iipviewer.changeImage(this.retrieve('name'), this.retrieve('path'));
                                _thumbview.destroy();
                                e.preventDefault();
                            });
                            _img.inject(_a);
                            _a.inject(_thumbview);
                        }
                        var _body = $$('body');
                        _body = _body[0];
                        _thumbview.inject(_body, 'top');
                    },
                    onFailure: function(){
                        alert('An error occured loading series thumbnails.');
                    }
                }).get();
            }
        });
        $$('#zoomin').addEvent( 'click', function(){ _this.fireEvent('zoomIn'); });
        $$('#zoomout').addEvent( 'click', function(){ _this.fireEvent('zoomOut'); });
        $$('#fitsize').addEvent( 'click', function(){ _this.fireEvent('reload'); });
        $$("#fullsize").addEvent('click', function(){
            _iipviewer.zoomTo(_iipviewer.num_resolutions -1);
        });
        //rotation is buggy, see https://github.com/ruven/iipmooviewer/issues/13
        /*$$('#rrotate').addEvent( 'click', function(){
            _r = 90;
            _this.fireEvent('rotate', _r);
        });
        $$('#lrotate').addEvent( 'click', function(){
            _r = -90;
            _this.fireEvent('rotate', _r);
        });*/
        //navbar
        $$('#previmg,#nextimg').addEvent('click', function(e){
            var _str = this.get('href');
            var _re = /img=(.*)/;
            var _img = _str.match(_re)[1];
            _iipviewer.changeImage(_img, _iipviewer.fpath + _img);
            e.preventDefault();
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
    }
});

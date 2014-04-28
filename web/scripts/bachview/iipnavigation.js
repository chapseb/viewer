/*
Copyright (c) 2014, Anaphore
All rights reserved.

Redistribution and use in source and binary forms, with or without
modification, are permitted provided that the following conditions are
met:

    (1) Redistributions of source code must retain the above copyright
    notice, this list of conditions and the following disclaimer.

    (2) Redistributions in binary form must reproduce the above copyright
    notice, this list of conditions and the following disclaimer in
    the documentation and/or other materials provided with the
    distribution.

    (3)The name of the author may not be used to
   endorse or promote products derived from this software without
   specific prior written permission.

THIS SOFTWARE IS PROVIDED BY THE AUTHOR ``AS IS'' AND ANY EXPRESS OR
IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
DISCLAIMED. IN NO EVENT SHALL THE AUTHOR BE LIABLE FOR ANY DIRECT,
INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
(INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR
SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION)
HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT,
STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING
IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
POSSIBILITY OF SUCH DAMAGE.
*/

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

        //Redefine sizes
        this.navcontainer.setStyle('width', 'auto');
        $$('.navwin').setStyle('width', this.size.x);

        //add toolbar btn
        var tbbtn = new Element( 'div', {
            'class': 'toolbarbtn',
        });
        var _tbs = $$('.toolbar');
        tbbtn.inject(_tbs[0]);

        this.outerZone = new Element('div', {
            'class': 'outerzone'
        });
        this.outerZone.inject(this.zone, 'before');

        $$('.loadBarContainer').setStyle('width', this.size.x);

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
        var me = this;
        $$('.toolbarbtn').addEvent('click', function(e) {
            me.toggleWindow();
        })

        $$('.navwin').addEvent('click', function(e) {
            me.scroll(e);
        });
    },

    /**
     * Overrides IIP navigation
     */
    update: function(x,y,w,h){
        this.parent(x,y,w,h);
        this.update_status();

        var me = this;
        //resize outer zone
        this.zone.get('morph').chain(function() {
            var _navWin = $$('.navwin')[0];
            var _toolbar = $$('.toolbar')[0];

            var _styles = me.zone.getStyles(
                'width',
                'height',
                'border-top-width',
                'border-bottom-width',
                'border-left-width',
                'border-right-width',
                'top',
                'left'
            );

            var _outerHeight = _styles.height.toInt();
            var _outerTopBorder = _styles.top.toInt() - _toolbar.getStyle('height').toInt() + _styles['border-top-width'].toInt()
            var _outerBottomBorder = _navWin.getStyle('height').toInt() - _outerHeight - _outerTopBorder + _styles['border-bottom-width'].toInt();

            var _outerWidth = _styles.width.toInt();
            var _outerLeftBorder = _styles.left.toInt() + _styles['border-left-width'].toInt()
            var _outerRightBorder = _navWin.getStyle('width').toInt() - _outerWidth - _outerLeftBorder + _styles['border-right-width'].toInt();

            me.outerZone.setStyles({
                'border-top-width':     _outerTopBorder,
                'border-left-width':    _outerLeftBorder,
                'border-bottom-width':  _outerBottomBorder,
                'border-right-width':   _outerRightBorder,
                'height':               _outerHeight,
                'width':                _outerWidth
            });

        });
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


    /*
     * Handle click or drag scroll events
     * Overrides IIP navigation
    */
    scroll: function(e){
        this.parent(e);
        var _navWin = $$('.navwin')[0];
        var _toolbar = $$('.toolbar')[0];

        var _styles = this.zone.getStyles(
            'width',
            'height',
            'border-top-width',
            'border-bottom-width',
            'border-left-width',
            'border-right-width',
            'top',
            'left'
        );

        var _outerHeight = _styles.height.toInt();
        var _outerTopBorder = _styles.top.toInt() - _toolbar.getStyle('height').toInt() + _styles['border-top-width'].toInt()
        var _outerBottomBorder = _navWin.getStyle('height').toInt() - _outerHeight - _outerTopBorder + _styles['border-bottom-width'].toInt();

        var _outerWidth = _styles.width.toInt();
        var _outerLeftBorder = _styles.left.toInt() + _styles['border-left-width'].toInt()
        var _outerRightBorder = _navWin.getStyle('width').toInt() - _outerWidth - _outerLeftBorder + _styles['border-right-width'].toInt();

        this.outerZone.setStyles({
            'border-top-width':     _outerTopBorder,
            'border-left-width':    _outerLeftBorder,
            'border-bottom-width':  _outerBottomBorder,
            'border-right-width':   _outerRightBorder,
            'height':               _outerHeight,
            'width':                _outerWidth
        });
    },


    /*
     * Toggle the visibility of our navigation window
     * Overrides IIP navigation
    */
    toggleWindow: function(){
        $$('.navwin').toggle();
        $$('.toolbar').toggleClass('invisible');
        $$('.loadBarContainer').toggleClass('invisible');
        $$('.toolbarbtn').toggleClass('off');
  }
});

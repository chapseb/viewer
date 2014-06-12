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

( function( $, undefined ) {
$.widget("ui.bviewer", $.extend({}, $.ui.iviewer.prototype, {

    /* Overrides iviewer method to:
     *  - add specific UI
     *  - add actions on some events
     *  - handle navigation overview
     */
    _create: function() {
        var me = this;
        $.ui.iviewer.prototype._create.apply(this, arguments);

        this.image_name = this.options.imageName;

        //add navigation overview
        this.drawNavigation();

        this.options.onStartLoad = function(){
            $('#progressbar').fadeIn();
        };

        this.options.onFinishLoad = function(){
            var _io = me.img_object;
            if ( _io.orig_height() < _io.display_height()
                || _io.orig_width() < _io.display_width()
            ) {
                //set intial zoom to 100% max
                me.set_zoom(100);
            }

            if ( series_path != '' ) {
                me.updateSeriesInfos();
            }

            //load navigation overview image
            var _src;
            if ( me.image_name && series_path != '' ) {
                _src = app_url + '/ajax/img/'  + series_path  + '/' + me.image_name + '/format/thumb';
            } else {
                _src  = me.options.src.replace(/show\/.+\//, 'show/thumb/');
            }
            me.nav_img_object.load(_src, function() {
                //remove buggy styles...
                $('.navwin, .navwin > img, .outerzone').removeAttr('style')
                    .height(me.nav_img_object.display_height())
                    .width(me.nav_img_object.display_width());
                me._setOverviewMaskSize();
            }, function() {
                me._trigger("onErrorLoad", 0, src);
            });

            $('#progressbar').fadeOut('slow');
        };

        this.options.onAfterZoom = function(ev, new_zoom) {
            me._setOverviewMaskSize();
        };


        this.options.onStopDrag = function(ev, point) {
            me._setOverviewMaskSize();
        }

        this.options.angle = function(ev, angle) {
            me.nav_img_object.angle(angle.angle);

            var _margin = 0;
            var _orig = '50% 50%';

            var _w = me.nav_img_object.display_width();
            var _h = me.nav_img_object.display_height();

            var _nav_win_h;
            var _nav_win_w;

            if ( me.nav_img_object._swapDimensions ) {

                if ( angle.angle == 270 ) {
                    //set origin to 0-0 and add a margin of full image height if angle is 270deg
                    _orig = '0px 0px';
                    _margin = _h;
                } else {
                    //if angle is 90deg, set origin to half displayed width
                    _orig = _w/2 + 'px ' + _w/2 + 'px';
                }

                //invert w and h
                _nav_win_h = _w;
                _nav_win_w = _h;

                $('.navwin img')
                    .width(_h)
                    .height(_w)
                    .css({
                        'transform-origin': _orig,
                        'margin-top': _margin
                    });
            } else {
                _nav_win_h = _h;
                _nav_win_w = _w;
            }

            $('.navwin img')
                .width(_nav_win_w)
                .height(_nav_win_h)
                .css({
                    'transform-origin': _orig,
                    'margin-top': _margin
                });


            $('.navwin').width(_w).height(_h);

            me._setOverviewMaskSize();
        }

        this.createui();
    },

    /* Overrides iviewer method to add specific UI */
    destroy: function() {
        $.ui.iviewer.prototype.destroy.apply(this, arguments);
        this.nav_img_object.object().remove();
    },

    /** Overrides iviewr method to rotate navigation image */
    angle: function(deg, abs) {
         var me = this;
        $.ui.iviewer.prototype.angle.apply(this, arguments);
    },

    print: function()
    {
        var me = this;
        var _img_height = this.nav_img_object.display_height();
        var _img_width = this.nav_img_object.display_width();
        var _width;
        var _height;
        var _topPos = 0;
        var _leftPos = 0;

        var _container = $(this.container[0]);
        //is image taller than window?
        var percent = Math.round(100*this.img_object.display_height()/this.img_object.orig_height())/100;

        if ( this.img_object.display_width() < this.container[0].clientWidth ) {
            var   scale_width = Math.round(this.img_object.display_width() / percent);
        } else {
            var   scale_width = Math.round( this.container[0].clientWidth  / percent);
        }

        if ( this.img_object.display_height() < this.container[0].clientHeight ) {
            var   scale_height = Math.round(this.container[0].clientHeight / percent);
        } else {
            var   scale_height = Math.round( this.container[0].clientHeight / percent);
        }

        //image is taller than window. Calculate zone size and position
        if ( this.img_object._y < 0 ) {
            _topPos = Math.round(this.img_object._y * -1 / this.img_object.display_height() * _img_height);
        }
        _height = Math.round(this.container[0].clientHeight / this.img_object.display_height() * _img_height);
        _topPosHD = Math.round(scale_height * _topPos / _height);
        if ( this.img_object._x < 0 ) {
            _leftPos = Math.round(this.img_object._x * -1 / this.img_object.display_width() * _img_width );
        }

        _width = Math.round(this.container[0].clientWidth / this.img_object.display_width() * _img_width);
        _leftPosHD = Math.round(scale_width * _leftPos /_width); 

        _src  = $('#viewer > img').attr('src').replace(/show\//, '' );
        var res = 'printpdf/'  + _leftPosHD  +  '/' +  _topPosHD +  '/' + scale_width +  '/' + scale_height +  _src ;

        var _path_info = window.location.href.split('/');
        res = _path_info[0] + '//' + _path_info[2] + '/' + res;

        window.location.href = res;
    },

    /**
     * Create zoom buttons info box
     */
    createui: function()
    {
        var me=this;

        //toolbar
        $('#thumbnails').bind('click touchstart', function(){
            var _thumbview = $('#thumbnails_view');
            if ( _thumbview.length > 0 ) {
                _thumbview.remove();
            } else {
                _thumbview = $('<div id="thumbnails_view"></div>');

                $.get(
                    app_url + '/ajax/series/thumbs',
                    function(data){
                        var _thumbs = data['thumbs'];
                        var _meta = data['meta'];
                        for ( var i = 0 ; i < data['thumbs'].length ; i++ ) {
                            var _src = app_url + '/ajax/img/' + series_path + '/' + _thumbs[i].name + '/format/thumb';
                            var _img = $('<img src="' + _src  + '" alt=""/>');
                            var _style = 'width:' + _meta.width  + 'px;height:' + _meta.height + 'px;line-height:' + _meta.height  + 'px;';
                            var _a = $('<a href="?img=' + _thumbs[i].path  + '" style="' + _style  + '"></a>');
                            if ( me.image_name == _thumbs[i].name ) {
                                _a.addClass('current');
                            }
                            _a.bind('click touch', function(){
                                me.display(me._imgNameFromLink($(this)));
                                $('#formats > select').val('default');
                                _thumbview.remove();
                                return false;
                            });
                            _img.appendTo(_a);
                            _a.appendTo(_thumbview);
                        }
                        _thumbview.prependTo('body');
                    },
                    'json'
                ).fail(function(){
                    alert('An error occured loading series thumbnails.');
                });


            }
        });
        $("#zoomin").bind('click touchstart', function(){ me.zoom_by(1); });
        $("#zoomout").bind('click touchstart', function(){ me.zoom_by(-1); });
        $("#fitsize").bind('click touchstart', function(){ me.fit(); });
        $("#fullsize").bind('click touchstart', function(){ me.set_zoom(100); });
        $("#lrotate").bind('click touchstart', function(){ me.angle(-90); });
        $("#rrotate").bind('click touchstart', function(){ me.angle(90); });
        $('#negate').bind('click touchstart', function(){ me.negate(); });
        $("#print").bind('click touchstart', function(){ me.print(); });
        this.zoom_object = $('#zoominfos');

        //prevent this to execute on ios...
        var agent = navigator.userAgent.toLowerCase();
        if ( !(agent.indexOf('iphone') > 0 || agent.indexOf('ipad') >= 0) && navigator.platform != 'Win32' ) {
            var tflagin = false;
            var tflagout = false;
            var _hammer = $('#viewer').hammer({
                prevent_default: true
            });
            _hammer.on('pinchin', function(ev){
                if ( !tflagin ) {
                    tflagin = true;
                    setTimeout(function(){ tflagin = false;}, 100);
                    me.zoom_by(-1);
                }
                ev.gesture.preventDefault()
            });
            _hammer.on('pinchout', function(ev){
                if ( !tflagout ) {
                    tflagout = true;
                    timer = setTimeout(function(){tflagout = false;}, 100);
                    me.zoom_by(1);
                }
                ev.gesture.preventDefault()
            });
        }

        //resize image
        $('#formats > select').change(function(){
            var _format = $("select option:selected").attr('value');
            if ( series_path != '') {
                var _src = app_url + '/ajax/img/' + series_path + '/' + me.image_name  + '/format/' + _format;
            } else {
                _src  = me.options.src.replace(/show\/.+\//, 'show/' + _format + '/');
            }
            me.loadImage(_src);
        }).val('default');

        //navbar
        $('#previmg,#nextimg').bind('click touchstart', function(){
            me.display(me._imgNameFromLink($(this)));
            $('#formats > select').val('default');
            me.drawNavigation();
            return false;
        });

        //adapted from mouse wheel event
        this.container.dblclick(function(ev){
            //this event is there instead of containing div, because
            //at opera it triggers many times on div
            var container_offset = me.container.offset(),
                mouse_pos = {
                    //jquery.mousewheel 3.1.0 uses strange MozMousePixelScroll event
                    //which is not being fixed by jQuery.Event
                    x: (ev.pageX || ev.originalEvent.pageX) - container_offset.left,
                    y: (ev.pageY || ev.originalEvent.pageX) - container_offset.top
                };

            me.zoom_by(1, mouse_pos);
            return false;
        });

        $('.toolbarbtn').bind('click touchstart', function() {
            $('.navwin').toggle();
            $(this).toggleClass('off');
        });

        //prevent double click to be passed to viewer container
        $("#thumbnails,#zoomin,#zoomout,#fitsize,#fullsize,#lrotate,#rrotate,#nextimg,#previmg,#formats,#negate").on('dblclick', function(e){
            e.stopPropagation();
        });

        //bind keys
        $('body').bind('keydown', function(event) {
            if (event.which == 107) { //+
                me.zoom_by(1);
                event.preventDefault();
            }
            if (event.which == 109) { //-
                me.zoom_by(-1);
                event.preventDefault();
            }
            if ( event.which == 82 && !event.ctrlKey ) { //r
                if ( event.shiftKey ) { //shift pressed
                    me.angle(-90);
                } else {
                    me.angle(90);
                }
                event.preventDefault();
            }
            if ( event.which == 34 && !event.ctrlKey ) { //page down
                $('#previmg').click();
                event.preventDefault();
            }
            if ( event.which == 33 && !event.ctrlKey ) { //page up
                $('#nextimg').click();
                event.preventDefault();
            }
        });
    },

    /* update scale info in the container */
    /* Overrides iviewer method to remove ui check */
    update_status: function()
    {
        var percent = Math.round(100*this.img_object.display_height()/this.img_object.orig_height());
        if(percent)
        {
            this.zoom_object.html(percent + "%");
        }
    },

    /**
     * Display specific image
     *
     * @param string img Image name
     */
    display: function(img)
    {
        this.image_name = img;
        if ( series_path != '') {
            img = series_path + '/' + img;
        }
        this.loadImage(app_url + '/ajax/img/' + img);
    },

    negate: function()
    {
        var _img_path = $('#viewer > img').attr('src').replace('/show', '');
        this.loadImage(app_url + '/transform/negate' + _img_path);
    },

    /**
     * Update series informations:
     * - navigation on previous/next images
     * - position in current series
     * - title
     */
    updateSeriesInfos: function()
    {
        var _url = app_url + '/ajax/series/infos';
        if ( this.image_name != undefined ) {
            _url += '/' + this.image_name;
        }
        //check for subseries
        var _subs = $('#previmg').attr('href').replace(/&img=(.+)/, '');
        if ( _subs != '?img' ) {
            _url += _subs;
        }
        $.get(
            _url,
            function(data){
                var _prev = $('#previmg');
                _prev.attr('href', _prev.attr('href').replace(/img=(.+)/, 'img=' + data.prev));
                var _next = $('#nextimg');
                _next.attr('href', _next.attr('href').replace(/img=(.+)/, 'img=' + data.next));
                $('#current_pos').html(data.position);
                $('header > h2').html(data.current);
            },
            'json'
        ).fail(function(){
            alert('An error occured loading series informations, navigation may fail.');
        });
    },

    drawNavigation: function()
    {
        var me = this;

        if ($('#overview') ) {
            $('#overview').remove();
        }
        var _navContainer = $('<div class="navcontainer" id="overview"></div>');
        var _navContainerBar = $('<div class="toolbar"></div>');
        var _navContainerBarButton = $('<div class="toolbarbtn"></div>');
        _navContainerBar.append(_navContainerBarButton);
        _navContainer.append(_navContainerBar);
        var _navWin = $('<div class="navwin"></div>');
        var _outerZone = $('<div class="outerzone"></div>');
        var _navWinZone = $('<div class="zone"></div>');

        _navWin.bind('click touchstart', function(e) {
            var _this = _navWin;
            var _container = _navContainer;
            var _zone = _navWinZone;
            var _bar = _navContainerBar;

            var _borders = _zone.css([
                'border-top-width',
                'border-right-width',
                'border-bottom-width',
                'border-left-width'
            ]);
            var _borderTop = parseInt(_borders['border-top-width'], 10);
            if ( isNaN(_borderTop) ) {
                _borderTop = 0;
            }
            var _borderBottom = parseInt(_borders['border-bottom-width'], 10);
            if ( isNaN(_borderBottom) ) {
                _borderBottom = 0;
            }
            var _borderLeft = parseInt(_borders['border-left-width'], 10);
            if ( isNaN(_borderLeft) ) {
                _borderLeft = 0;
            }
            var _borderRight = parseInt(_borders['border-right-width'], 10);
            if ( isNaN(_borderRight) ) {
                _borderRight = 0;
            }

            //Calculate zone size, with borders
            var _w = _zone.width() + _borderLeft + _borderRight;
            var _h = _zone.height() + _borderTop + _borderBottom;

            var _container_position = _container.position();
            var _margins = _container.css([
                'margin-top',
                'margin-left'
            ]);
            var _marginTop = parseInt(_margins['margin-top'], 10);
            if ( isNaN(_marginTop) ) {
                _marginTop = 0;
            }
            var _marginLeft = parseInt(_margins['margin-left'], 10);
            if ( isNaN(_marginLeft) ) {
                _marginLeft = 0;
            }

            var _origLeft = _container_position.left + _marginLeft;
            var _origTop = _container_position.top + _marginTop;

            var _posx = e.pageX - _origLeft - _w/2;
            var _posy = e.pageY - _origTop - _h/2;

            if ( _posx < 0 ) {
                _posx = 0;
            } else if ( _posx + _w > _this.width() ) {
                _posx = _this.width() - _w;
            }

            if ( _posy - _bar.height() < 0 ) {
                _posy = 0 + _bar.height();
            } else if ( _posy + _h > _this.height() + _bar.height() ) {
                _posy = _this.height() - _h + _bar.height();
            }

            var _outerBottomBorder = _navWin.height() - Math.round(_posy) + _bar.height() + _borderTop - _outerZone.height() + _borderBottom;

            var _outerRightBorder = _navWin.width() - Math.round(_posx) + _borderLeft - _outerZone.width() + _borderRight;

            //move zone
            _outerZone.css({
                'border-top-width':     Math.round(_posy) - _bar.height() + _borderTop,
                'border-left-width':    Math.round(_posx) + _borderLeft,
                'border-bottom-width':  _outerBottomBorder,
                'border-right-width':   _outerRightBorder
            });

            _zone.css({
                'top': Math.round(_posy),
                'left': Math.round(_posx)
            });

            //update image position
            var _ratio = me.img_object.display_width() / _this.width();
            me.setCoords(
                _posx * _ratio * -1,
                (_posy - _bar.height()) * _ratio * -1
            );
        }).on('dblclick', function(e){
            //prevent double click to be passed to viewer container
            e.stopPropagation();
        });
        _navWinZone.hide();
        _navWin.append(_outerZone);
        _navWin.append(_navWinZone);
        _navContainer.append(_navWin);
        $('#viewer').append(_navContainer);
        _navContainer.draggable({
            handle: 'div.toolbar',
            containment: 'parent'
        });
        _navWinZone.draggable({
            containment: 'parent',
             drag: function() {
                //get zone coords
                var _coords = _navWinZone.css(['top', 'left']);
                var _outerSizes = _outerZone.css(['width', 'height']);

                //update outer zone coordinates
                var _outerHeight = parseFloat(_outerSizes['height']);
                var _outerBottomBorder = _navWin.height() - parseInt(_coords['top']) + _navContainerBar.height() - parseInt(_outerSizes['height']) + 1;

                var _outerWidth = parseFloat(_outerSizes['width']);
                var _outerRightBorder = _navWin.width() - parseInt(_coords['left']) - parseInt(_outerSizes['width']) + 1;

                _outerZone.css({
                    'border-top-width':     parseFloat(_coords['top']) - _navContainerBar.height(),
                    'border-left-width':    parseFloat(_coords['left']),
                    'border-bottom-width':  _outerBottomBorder,
                    'border-right-width':   _outerRightBorder,
                    'height':               _outerHeight,
                    'width':                _outerWidth
                });

                //update image position
                var _ratio = me.img_object.display_width() / _navWin.width();
                var _posy = parseFloat(_coords['top']);
                var _posx = parseFloat(_coords['left']);

                me.setCoords(
                    _posx * _ratio * -1,
                    (_posy - _navContainerBar.height()) * _ratio * -1
                );
             },
        });

        //add navigation overview image
        this.nav_img_object = new $.ui.iviewer.ImageObject(this.options.zoom_animation);
        this.nav_img_object.object()
            .prependTo($('div.navwin'));
    },

    _setOverviewMaskSize: function()
    {
        var _zone = $('#overview > .navwin > .zone');
        var _outerZone = $('#overview > .navwin > .outerzone');
        var _img_height = this.nav_img_object.display_height();
        var _img_width = this.nav_img_object.display_width();
        var _bar = $('#overview > .toolbar');
        var _navWin = $('.navwin');

        var _borders = _zone.css([
            'border-top-width',
            'border-right-width',
            'border-bottom-width',
            'border-left-width'
        ]);
        var _borderTop = parseInt(_borders['border-top-width'], 10);
        if ( isNaN(_borderTop) ) {
            _borderTop = 0;
        }
        var _borderBottom = parseInt(_borders['border-bottom-width'], 10);
         if ( isNaN(_borderBottom) ) {
            _borderBottom = 0;
        }
        var _borderLeft = parseInt(_borders['border-left-width'], 10);
          if ( isNaN(_borderLeft) ) {
            _borderLeft = 0;
        }
        var _borderRight = parseInt(_borders['border-right-width'], 10);
        if ( isNaN(_borderRight) ) {
            _borderRight = 0;
        }

        var _width;
        var _height;
        var _topPos = 0;
        var _leftPos = 0;

        var _container = $(this.container[0]);
        //is image taller than window?
        if ( this.img_object.display_width() <= _container.width()  && this.img_object.display_height() <= _container.height() ) {
            //image is smaller than window. Zone is full sized, and top-left placed.
            _width = _img_width;
            _height = _img_height;
        } else {
            //image is taller than window. Calculate zone size and position
            if ( this.img_object._y < 0 ) {
                _topPos = Math.round(this.img_object._y * -1 / this.img_object.display_height() * _img_height);
            }

            if ( this.img_object._x < 0 ) {
                _leftPos = Math.round(this.img_object._x * -1 / this.img_object.display_width() * _img_width);
            }

            if ( this.img_object.display_height() > this.container[0].clientHeight ) {
                _height = Math.round(this.container[0].clientHeight / this.img_object.display_height() * _img_height);
            } else {
                _height = _img_height;
            }

            if ( this.img_object.display_width() > this.container[0].clientWidth ) {
                _width = Math.round(this.container[0].clientWidth / this.img_object.display_width() * _img_width);
            } else {
                _width = _img_width;
            }
        }

        var _outerHeight = _height;
        var _outerBottomBorder = _navWin.height() - _topPos - _height + 1;

        var _outerWidth = _width;
        var _outerRightBorder = _navWin.width() - _leftPos - _width + 1;

        _outerZone.css({
            'border-top-width':     _topPos,
            'border-left-width':    _leftPos,
            'border-bottom-width':  _outerBottomBorder,
            'border-right-width':   _outerRightBorder,
            'height':               _outerHeight,
            'width':                _outerWidth
        });

        _zone.width(_width);
        _zone.height(_height);
        _zone.css({
            'top': _topPos + _bar.height(),
            'left': _leftPos,
            'position': 'absolute'
        });

        if ( _zone.is(':hidden') ) {
            _zone.show();
        }
    },

    _imgNameFromLink: function(link) {
        var _str = link.attr('href');
        var _re = /img=(.*)/;
        var _img = _str.match(_re)[1];
        return _img;
    }
}));

$.ui.bviewer.defaults = $.extend({}, $.ui.iviewer.defaults);

} )( jQuery, undefined );

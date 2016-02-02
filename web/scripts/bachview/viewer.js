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

    /**
     * Display options
     */
    display_options: {
        zoom: 'auto',
        format: 'default',
        transform: {
            negate: false,
            contrast: false,
            brightness: false,
            rotate: 0
        }
    },
    params_win_init: false,

    remote: false,

    hasTransformations: function()
    {
        var _t = this.display_options.transform;
        if ( _t.negate != false
            || _t.contrast != false
            || _t.brightness != false
            || _t.rotate != 0 ) {
            return true;
        }
    },

    /* Overrides iviewer method to:
     *  - add specific UI
     *  - add actions on some events
     *  - handle navigation overview
     */
    _create: function() {
        var me = this;
        $.ui.iviewer.prototype._create.apply(this, arguments);

        this.image_name = this.options.imageName;

        if ( this.options.remote ) {
            this.remote = this.options.remote;
        }

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
            } else {
                me.updateImageInfos();
            }

            //load navigation overview image
            var _src;
            _src  = me.options.src.replace(/show\/.[^\/]+/, 'show/thumb');

            if ( me.hasTransformations() ) {
                _src = _src.replace('/show', '/transform') + '?';

                var _t = me.display_options.transform;

                if ( _t.negate ) {
                    _src += '&n=true';
                }

                if ( _t.contrast ) {
                    _src += '&c=' + _t.contrast;
                }

                if ( _t.brightness ) {
                    _src += '&b=' + _t.brightness;
                }

                if ( _t.rotate > 0 ) {
                    _src += '&r=' + _t.rotate;
                }
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
            me.display_options.transform.rotate = angle.angle

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

    /** Overrides iviewer method to rotate navigation image */
    angle: function(deg, abs) {
        var me = this;
        if ( typeof _isOldIE != 'undefined' ) {
            deg += this.display_options.transform.rotate;
            if (deg < 0) { deg += 360; }
            if (deg >= 360) { deg -= 360; }

            this.display_options.transform.rotate = deg;
            var _img = this.image_name;
            if ( series_path != '' ) {
                _img = series_path + '/' + _img;
            }
            this.display(_img);
        } else {
            $.ui.iviewer.prototype.angle.apply(this, arguments);
        }
    },

    /**
     * Overrides iviewer method to inform user a hidef version is available
     */
    zoom_by: function(delta, zoom_center)
    {
        $.ui.iviewer.prototype.zoom_by.apply(this, arguments);

        if ( this.display_options.format !== 'full' ) {
            var percent = Math.round(100*this.img_object.display_height()/this.img_object.orig_height());
            var _uinfos = $('#hidef .userinfo');

            if ( percent && percent >= 150 && !_uinfos.is(':visible') ) {
                if ( !_uinfos.data('placed') ) {
                    //calculate position.
                    var _parent = $('#hidef');
                    var _ppos = _parent.position();
                    var _pheight = _parent.outerHeight();
                    var _pwidth = _parent.outerWidth();

                    var _height = _uinfos.outerHeight();
                    var _width = _uinfos.outerWidth();

                    var _top = _height + 5;
                    var _left = _ppos.left + (_pwidth - _width) / 2;

                    _uinfos.css('top', '-' + _top + 'px');
                    _uinfos.css('left', _left  + 'px');
                    _uinfos.data('placed', true);
                }
                _uinfos.fadeIn('slow');
            } else if (percent && percent < 150 && _uinfos.is(':visible')) {
                _uinfos.fadeOut('slow');
            }
        }
    },

    print: function()
    {
        var _img_height = this.nav_img_object.display_height();
        var _img_width = this.nav_img_object.display_width();
        var _width;
        var _height;
        var _topPos = 0;
        var _leftPos = 0;

        //is image taller than window?
        var percent = Math.round(100*this.img_object.display_height()/this.img_object.orig_height())/100;

        if ( this.img_object.display_width() < this.container[0].clientWidth ) {
            var   scale_width = Math.round(this.img_object.display_width() / percent);
        } else {
            var   scale_width = Math.round( this.container[0].clientWidth  / percent);
        }

        if ( this.img_object.display_height() < this.container[0].clientHeight ) {
            var   scale_height = Math.round(this.img_object.display_height() / percent);
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

        var _src = this.options.src.replace(/.*\/show\/default/, this.display_options.format);
        var res = app_url.replace(/^\//, '') + '/print/'  +  _src ;
        res += '?x=' + _leftPosHD + '&y=' + _topPosHD + '&w=' + scale_width + '&h=' + scale_height;

        if ( this.hasTransformations() ) {
            var _t = this.display_options.transform;

            if ( _t.negate ) {
                res += '&n=true';
            }

            if ( _t.contrast ) {
                res += '&c=' + _t.contrast;
            }

            if ( _t.brightness ) {
                res += '&b=' + _t.brightness;
            }

            if ( _t.rotate > 0 ) {
                res += '&r=' + _t.rotate;
            }
        }

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

                var _url = app_url + '/ajax/series/' + series_path  + '/thumbs';
                if ( typeof series_start != 'undefined' && typeof series_end != 'undefined' ) {
                    _url += '?s=' + series_start + '&e=' + series_end;
                }

                $.get(
                    _url,
                    function(data){
                        var _thumbs = data['thumbs'];
                        var _meta = data['meta'];
                        for ( var i = 0 ; i < data['thumbs'].length ; i++ ) {
                            var _src = app_url + '/ajax/img/' + series_path + '/' + _thumbs[i].name + '/format/thumb';
                            var _img = $('<img src="' + _src  + '" alt=""/>');
                            var _style = 'width:' + _meta.width  + 'px;height:' + _meta.height + 'px;line-height:' + _meta.height  + 'px;';
                            var _a = $('<a href="' + series_path + '?img=' + _thumbs[i].path  + '" style="' + _style  + '"></a>');
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
        $('#moreparams').bind('click touchstart', function(){ me.imageParamsWindow(); });
        $('#hidef').bind('click touchstart', function(){
            var _this = $(this);
            var _state = _this.data('state');
            var _format;
            if ( _state == 'on' ) {
                _format = 'default';
                _this.data('state', 'off');
                _this.attr('data-state', 'off');
                _this.attr('title', hidef_off_title);
            } else {
                _format = 'full';
                _this.data('state', 'on');
                _this.attr('data-state', 'on');
                _this.attr('title', hidef_on_title);
            }
            _this.toggleClass('on');
            if ( $('#formats > select').length > 0 ) {
                $('#formats > select').val(_format);
            }
            $('#hidef .userinfo').hide();
            me.display_options.format = _format;
            _src  = me.options.src.replace(/show\/.[^\/]+/, 'show/' + _format);
            me.loadImage(_src);
        });
        $("#print").bind('click touchstart', function(){ me.print(); });
        $('#comments').bind('click touchstart', function(){ me.imageCommentsWindow() });
        this.zoom_object = $('#zoominfos');

        //resize image
        $('#formats > select').change(function(){
            var _format = $("select option:selected").attr('value');
            me.display_options.format = _format;
            _src  = me.options.src.replace(/show\/.[^\/]+/, 'show/' + _format);
            me.loadImage(_src);
        }).val('default');

        //navbar
        $('#previmg,#nextimg').bind('click touchstart', function(){
            me.display(me._imgNameFromLink($(this)));
            $('#formats > select').val(me.display_options.format);
            me.drawNavigation();

            // rebind of toolbarbtn navigation when change image
            $('.toolbarbtn').bind('click touchstart', function() {
                $('.navwin').toggle();
                $(this).toggleClass('off');
            });

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
        $("#thumbnails,#zoomin,#zoomout,#fitsize,#fullsize,#lrotate,#rrotate,#nextimg,#previmg,#formats,#image_params").on('dblclick', function(e){
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
        this.image_name = (img.split('/')).pop();

        //store default format as source
        this.options.src = app_url + '/show/default/' + img;
        var _img_path = this.options.src.replace(
            'default',
            this.display_options.format
        );

        if ( this.hasTransformations() ) {
            _img_path = _img_path.replace('/show', '/transform') + '?';

            var _t = this.display_options.transform;

            if ( _t.negate ) {
                _img_path += '&n=true';
            }

            if ( _t.contrast ) {
                _img_path += '&c=' + _t.contrast;
            }

            if ( _t.brightness ) {
                _img_path += '&b=' + _t.brightness;
            }

            if ( _t.rotate > 0 ) {
                _img_path += '&r=' + _t.rotate;
            }
        }

        this.loadImage(_img_path);
    },

    /**
     * Display image parameters window
     */
    imageParamsWindow: function()
    {
        var _win = $('#image_params');
        if ( this.params_win_init == true ) {
            if ( _win.is(':visible') ) {
                _win.fadeOut();
            } else {
                _win.fadeIn();
            }
        } else {
            var me = this;
            this.params_win_init = true;
            _win.css('display', 'block');

            $('#image_params .close').on('click', function(){
                me.imageParamsWindow();
            });

            _win.submit(function(event){
                event.preventDefault();

                var _v = $('#contrast_value').val();
                if ( _v != 0 ) {
                    me.display_options.transform.contrast = _v;
                } else {
                    me.display_options.transform.contrast = false;
                }

                _v = $('#brightness_value').val();
                if ( _v != 0 ) {
                    me.display_options.transform.brightness = _v;
                } else {
                    me.display_options.transform.brightness = false;
                }

                _v = $('#negate:checked');
                if ( _v.length > 0 ) {
                    me.display_options.transform.negate = true;
                } else {
                    me.display_options.transform.negate = false;
                }

                me.display(me.options.src.replace(/.*\/show\/default\//, ''));
            });

            _win.draggable({
                handle: 'legend',
                containment: 'parent'
            });

            if ( $('#change_contrast') ) {
                $('#change_contrast').noUiSlider({
                    start: 0,
                    range: {
                        'min': -10,
                        'max': 10
                    },
                    step: 1,
                    serialization: {
                        lower: [
                            $.Link ({
                                target: $('#contrast_value')
                            })
                        ]
                    }
                });
            }

            if ( $('#change_brightness') ) {
                $('#change_brightness').noUiSlider({
                    start: 0,
                    range: {
                        'min': -80,
                        'max': 80
                    },
                    step: 1,
                    serialization: {
                        lower: [
                            $.Link ({
                                target: $('#brightness_value')
                            })
                        ]
                    }
                });
            }

            $('#reset_parameters').on('click', function(event) {
                var _t = me.display_options.transform;
                _t.negate = false;
                $('#negate').attr('checked', false);
                _t.contrast = false;
                $('#change_contrast').val(0);
                _t.brightness = false;
                $('#change_brightness').val(0);
                _t.rotate = 0;
                me.display_options.format = 'default';
                $('#formats > select').val('default');
            });
        }
    },

    /**
     * Display image comments window
     */
    imageCommentsWindow: function()
    {
        var _win = $('#image_comments');
        var _name = this.image_name;
        var _path = series_path;

        if ( this.comments_win_init == true ) {
            if ( _win.is(':visible') ) {
                _win.fadeOut();
            } else {
                _win.fadeIn();
            }
        } else {
            var me = this;
            this.comments_win_init = true;
            _win.css('display', 'block');

            // get the comments of the image
            if(($('#allComments').is(':empty'))){
                me.getComment(app_url, _path, _name);
            }

            me.addComment(app_url, _path, _name);

            $('#image_comments .close').on('click', function(){
                me.imageCommentsWindow();
            });

            _win.draggable({
                handle: '#comm_header',
                containment: 'parent'
            });

        }
    },

    getComment: function(app_url, path, name)
    {
        if (path != '' ) {
            path += '/';
        }
        $.get(
            app_url + '/ajax/image/comments/' + path + name,
            function(data){
                var comments = JSON.parse(data);
                for (var idx in comments ) {
                    $("#allComments").append(
                        "<div class='oneComment'><strong>" +
                        comments[idx].subject +
                        "</strong><p class='contentComment'>" +
                        comments[idx].message +
                        "</p></div><hr />"
                    );
                }
            },
            'json'
        ).fail(function(){
            alert('An error occured loading form commentary, navigation may fail.');
        });
    },

    /**
     * Add a comment
     */
    addComment: function(appUrl, path, name)
    {
        $('#add_comment').unbind();

        if (path != '' ) {
            path += '/';
        }
        $('#add_comment').on('click', function(event) {
            event.preventDefault();
            $.get(
                appUrl + '/ajax/image/comment/bachURL',
                function(urlBach) {
                    urlBach += 'comment/images/'+ path + name +'/add';
                    var commentTab = window.open(urlBach);
                    commentTab.focus();
                }
            );
        });
    },


    /**
     * Update series informations:
     * - navigation on previous/next images
     * - position in current series
     * - title
     */
    updateSeriesInfos: function()
    {
        var _url = app_url + '/ajax/series/infos/';
        _url += series_path + '/' + this.image_name;

        //check for subseries
        if ( typeof series_start != 'undefined' && typeof series_end != 'undefined' ) {
            _url += '?s=' + series_start + '&e=' + series_end;
        }
        $.get(
            _url,
            function(data){
                var _prev = $('#previmg');
                _prev.attr('href', series_path + '?img=' + data.prev);
                var _next = $('#nextimg');
                _next.attr('href', series_path + '?img=' + data.next);
                $('#current_pos').html(data.position);
                if ( data.remote ) {
                    $('header > h2').html(data.remote);
                } else {
                    $('header > h2').html(data.current);
                }
            },
            'json'
        ).fail(function(){
            alert('An error occured loading series informations, navigation may fail.');
        });

        $('#allComments').empty();
        var _name = this.image_name;
        var _path = series_path;

        this.getComment(app_url, _path, _name);

        this.addComment(app_url, _path, _name);

    },

    /**
     * Update image informations:
     * - title
     */
    updateImageInfos: function()
    {
        var _url = app_url + '/ajax/image/infos/';
        if ( image_path ) {
            _url += image_path + '/';
        }
        _url += this.image_name;

        $.get(
            _url,
            function(data){
                if ( data.remote ) {
                    $('header > h2').html(data.remote);
                }            },
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
        var _str = link.attr('href').replace('/series', '');
        var _re = /\?img=(.+)/;
        return _str.replace(_re, '/$1');
    }
}));

$.ui.bviewer.defaults = $.extend({}, $.ui.iviewer.defaults);

} )( jQuery, undefined );

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
            if ( me.image_name ) {
                _src = '/ajax/img/' + me.image_name + '/format/thumb';
            } else {
                _src  = me.options.src.replace(/show\/.+\//, 'show/thumb/');
            }
            me.nav_img_object.load(_src, function() {
                //remove buggy styles...
                $('.navwin > img').removeAttr('style')
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

    /**
     * Create zoom buttons info box
     */
    createui: function()
    {
        var me=this;

        //toolbar
        $("#zoomin").click(function(){ me.zoom_by(1); });
        $("#zoomout").click(function(){ me.zoom_by(-1); });
        $("#fitsize").click(function(){ me.fit(); });
        $("#fullsize").click(function(){ me.set_zoom(100); });
        $("#lrotate").click(function(){ me.angle(-90); });
        $("#rrotate").click(function(){ me.angle(90); });
        this.zoom_object = $('#zoominfos');

        //resize image
        $('#formats').change(function(){
            var _format = $("select option:selected").attr('value');
            me.loadImage('/ajax/img/' + me.image_name + '/format/' + _format);
        });

        //navbar
        $('#previmg,#nextimg').click(function(){
            var _this = $(this);
            var _str = _this.attr('href');
            var _re = /img=(.*)/;
            var _img = _str.match(_re)[1];
            me.display(_img);
            $('#formats').val('default');
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
            if ( event.which == 34 ) { //page down
                $('#previmg').click();
                event.preventDefault();
            }
            if ( event.which == 33 ) { //page up
                $('#nextimg').click();
                event.preventDefault();
            }
        });
    },

    /* update scale info in the container */
    /* Overrideserrides iviewer method to remove ui check */
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
        this.loadImage('/ajax/img/' + img);
    },

    /**
     * Update series informations (mainly navigation on previous/next images)
     */
    updateSeriesInfos: function()
    {
        var _url = '/ajax/series/infos';
        if ( this.image_name != undefined ) {
            _url += '/' + this.image_name;
        }
        $.get(
            _url,
            function(data){
                $('#previmg').attr('href', '?img=' + data.prev);
                $('#nextimg').attr('href', '?img=' + data.next);
                $('#current_pos').html(data.position);
                $('header > h1').html(data.current);
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
        _navContainer.append(_navContainerBar);
        var _navWin = $('<div class="navwin"></div>');
        var _navWinZone = $('<div class="zone"></div>');

        _navWin.on('click', function(e) {
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

            //move zone
            _zone.css({
                'top': _posy,
                'left': _posx
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
        var _img_height = this.nav_img_object.display_height();
        var _img_width = this.nav_img_object.display_width();
        var _bar = $('#overview > .toolbar');

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
                _topPos = this.img_object._y * -1 / this.img_object.display_height() * _img_height;
            }

            if ( this.img_object._x < 0 ) {
                _leftPos = this.img_object._x * -1 / this.img_object.display_width() * _img_width;
            }

            if ( this.img_object.display_height() > this.container[0].clientHeight ) {
                _height = this.container[0].clientHeight / this.img_object.display_height() * _img_height;
            } else {
                _height = _img_height;
            }

            if ( this.img_object.display_width() > this.container[0].clientWidth ) {
                _width = this.container[0].clientWidth / this.img_object.display_width() * _img_width;
            } else {
                _width = _img_width;
            }
        }

        //add bar size
        _topPos = _topPos + _bar.height();

        //remove borders sizes
        _width = _width - _borderLeft - _borderRight;
        _height = _height - _borderTop - _borderBottom;

        _zone.width(_width);
        _zone.height(_height);
        _zone.css({
            'top': _topPos,
            'left': _leftPos,
            'position': 'absolute'
        });

        if ( _zone.is(':hidden') ) {
            _zone.show();
        }
    }
}));

$.ui.bviewer.defaults = $.extend({}, $.ui.iviewer.defaults);

} )( jQuery, undefined );

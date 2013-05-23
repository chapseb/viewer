( function( $, undefined ) {
$.widget("ui.bviewer", $.extend({}, $.ui.iviewer.prototype, {

    /* Overrides iviewer method to add specific UI */
    _create: function() {
        var me = this;
        $.ui.iviewer.prototype._create.apply(this, arguments);

        this.options.onStartLoad = function(){
            $('#progressbar').fadeIn();
        };

        this.options.onFinishLoad = function(){
            var _io = me.img_object;
            if ( _io.orig_height() < _io.display_height()
                || _io.orig_width() < _io.display_width()
            ) {
                me.set_zoom(100);
            }


            if ( series_path != '' ) {
                me.updateSeriesInfos();
            }

            $('#progressbar').fadeOut('slow');
        };
        this.createui();
    },

    /**
    *   create zoom buttons info box
    **/
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
            if ( event.which == 82 ) { //r
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
    }
}));

$.ui.bviewer.defaults = $.extend({}, $.ui.iviewer.defaults);

} )( jQuery, undefined );

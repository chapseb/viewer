( function( $, undefined ) {
$.widget("ui.bviewer", $.extend({}, $.ui.iviewer.prototype, {

    /* Overrides iviewer method to add specific UI */
    _create: function() {
        var me = this;
        $.ui.iviewer.prototype._create.apply(this, arguments);
        if ( series_path != '' ) {
            this.options.onFinishLoad = function(){
                me.updateSeriesInfos();
            }
        }
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

        //navbar
        $('#previmg,#nextimg').click(function(){
            var _this = $(this);
            var _str = _this.attr('href');
            var _re = /img=(.*)/;
            var _img = _str.match(_re)[1];
            me.display(_img);
            return false;
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
        $.get(
            '/ajax/series/infos',
            function(data){
                $('#previmg').attr('href', '?img=' + data.prev);
                $('#nextimg').attr('href', '?img=' + data.next);
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

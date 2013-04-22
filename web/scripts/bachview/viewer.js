( function( $, undefined ) {
$.widget("ui.bviewer", $.extend({}, $.ui.iviewer.prototype, {

    /* Overrides iviewer method to add specific UI */
    _create: function() {
        $.ui.iviewer.prototype._create.apply(this, arguments);
        this.createui();
    },

    /**
    *   create zoom buttons info box
    **/
    createui: function()
    {
        var me=this;

        $("#zoomin").click(function(){ me.zoom_by(1); });
        $("#zoomout").click(function(){ me.zoom_by(-1); });
        $("#fitsize").click(function(){ me.fit(); });
        $("#fullsize").click(function(){ me.set_zoom(100); });
        $("#lrotate").click(function(){ me.angle(-90); });
        $("#rrotate").click(function(){ me.angle(90); });
        this.zoom_object = $('#zoominfos');
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
    }
}));

$.ui.bviewer.defaults = $.extend({}, $.ui.iviewer.defaults);

} )( jQuery, undefined );

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

var BIIPMooViewer = new Class({
    Extends: IIPMooViewer,

    /*
     * Constructor - see documentation for options
     */
    initialize: function( main_id, options ) {
        this.parent(main_id, options);
        if ( options.disableContextMenu == false ) {
            this.disableContextMenu = false;
        }

        //path
        this.fpath = options.image.replace(options.imageName, '');

        // Navigation window options
        this.navigation = null;
        if( (typeof(BNavigation)==="function") ){
            this.navigation = new BNavigation({
                showNavWindow:options.showNavWindow,
                showNavButtons: options.showNavButtons,
                navWinSize: options.navWinSize,
                showCoords: options.showCoords,
                prefix: this.prefix
            });
        }
    },

    /**
     * 
     */
    createWindows: function(){
        var _this = this;
        this.parent();

        this.navigation.addEvents({
            'rotate': function(r){
                var _r = _this.view.rotation;
                _r += r % 360;
                _this.rotate(_r);
                if( IIPMooViewer.sync ) IIPMooViewer.windows(_this).invoke( 'rotate', _r );
            }
        });

        //replace infos, since we do not provide annotations
        $$('div.info').destroy();
        new Element( 'div', {
            'class': 'info',
            'styles': { opacity: 0 },
            'events': {
                click: function(){ this.fade('out'); }
            },
            'html': '<div><div><h2><a href="http://iipimage.sourceforge.net"><img src="'+this.prefix+'iip.32x32.png"/></a>IIPMooViewer</h2>IIPImage HTML5 Ajax High Resolution Image Viewer - Version '+this.version+'<br/><ul><li>'+IIPMooViewer.lang.navigate+'</li><li>'+IIPMooViewer.lang.zoomIn+'</li><li>'+IIPMooViewer.lang.zoomOut+'</li><li>'+IIPMooViewer.lang.fullscreen+'</li><li>'+IIPMooViewer.lang.navigation+'</li></ul><br/>'+IIPMooViewer.lang.more+' <a href="http://iipimage.sourceforge.net">http://iipimage.sourceforge.net</a></div></div>'
        }).inject( this.container );
    },


    /*
     * Calculate navigation view size
     */
    calculateNavSize: function()    {

        //var thumb_width = Math.round(this.view.w * this.navigation.options.navWinSize);
        var thumb_width = this.navigation.options.navWinSize;

        // For panoramic images, use a large navigation window
        /*if( this.max_size.w > 2*this.max_size.h ) thumb_width = Math.round( this.view.w/2 );*/

        // Make sure our height is not more than 50% of view height
        /*if( (this.max_size.h/this.max_size.w)*thumb_width > this.view.h*0.5 ){
            thumb_width = Math.round( this.view.h * 0.5 * this.max_size.w/this.max_size.h );
        }*/

        this.navigation.size.x = thumb_width;
        this.navigation.size.y = Math.round( (this.max_size.h/this.max_size.w)*thumb_width );
    },

  /**
   * Overrides IIPMooViewer to disable buggy rotation, see https://github.com/ruven/iipmooviewer/issues/13
   */
  key: function(e){

    var event = new DOMEvent(e);

    var d = Math.round(this.view.w/4);

    switch( e.code ){
    case 37: // left
      this.nudge(-d,0);
      if( IIPMooViewer.sync ) IIPMooViewer.windows(this).invoke( 'nudge', -d, 0 );
      event.preventDefault(); // Prevent default only for navigational keys
      break;
    case 38: // up
      this.nudge(0,-d);
      if( IIPMooViewer.sync ) IIPMooViewer.windows(this).invoke( 'nudge', 0, -d );
      event.preventDefault();
      break;
    case 39: // right
      this.nudge(d,0);
      if( IIPMooViewer.sync ) IIPMooViewer.windows(this).invoke( 'nudge', d, 0 );
      event.preventDefault();
      break;
    case 40: // down
      this.nudge(0,d);
      if( IIPMooViewer.sync ) IIPMooViewer.windows(this).invoke( 'nudge', 0, d );
      event.preventDefault();
      break;
    case 107: // plus
      if(!e.control){
	this.zoomIn();
	if( IIPMooViewer.sync ) IIPMooViewer.windows(this).invoke('zoomIn');
	event.preventDefault();
      }
      break;
    case 109: // minus
    case 189: // minus
      if(!e.control){
	this.zoomOut();
	if( IIPMooViewer.sync ) IIPMooViewer.windows(this).invoke('zoomOut');
	event.preventDefault();
      }
      break;
    case 72: // h
      if( this.navigation ) this.navigation.toggleWindow();
      if( this.credit ) this.container.getElement('div.credit').get('reveal').toggle();
      break;
    //rotation is buggy, see https://github.com/ruven/iipmooviewer/issues/13
    /*case 82: // r
      if(!e.control){
	var r = this.view.rotation;
	if(e.shift) r -= 90 % 360;
	else r += 90 % 360;

	this.rotate( r );
	if( IIPMooViewer.sync ) IIPMooViewer.windows(this).invoke( 'rotate', r );
      }
      break;*/
    case 65: // a
      if( this.annotations ) this.toggleAnnotations();
      break;
    case 27: // esc
      if( this.fullscreen && this.fullscreen.isFullscreen ) if(!IIPMooViewer.sync) this.toggleFullScreen();
      this.container.getElement('div.info').fade('out');
      break;
    case 70: // f fullscreen, but if we have multiple views
      if(!IIPMooViewer.sync) this.toggleFullScreen();
      break;
    case 67: // For control-c, show our current view location
      if(e.control) prompt( "URL of current view:", window.location.href.split("#")[0] + '#' +
			    (this.view.x+this.view.w/2)/this.wid + ',' +
			    (this.view.y+this.view.h/2)/this.hei + ',' +
			    this.view.res );
      break;
    default:
      break;
    }

  },

    /**
     * Update series informations:
     * - navigation on previous/next images
     * - position in current series
     * - title
     */
    updateSeriesInfos: function()
    {
        var _url = '/ajax/series/infos';
        if ( this.image_name != undefined ) {
            _url += '/' + this.image_name;
        }

        var _req = new Request.JSON({
            url: _url,
            onSuccess: function(data){
                $$('#previmg').set('href', '?img=' + data.prev);
                $$('#nextimg').set('href', '?img=' + data.next);
                $$('#current_pos').set('text', data.position);
                $$('header > h2').set('text', data.current);
            },
            onFailure: function(){
                alert('An error occured loading series informations, navigation may fail.');
            }
        }).get();
    },

    /**
     * Update images informations:
     * - navigation on previous/next images
     * - position in current series
     * - title
     */
    updateImageInfos: function(image)
    {
        var _url = image + '/ajax/image/infos';
        if ( this.fpath != undefined ) {
            _url += '/' + this.fpath;
        }

        var _req = new Request.JSON({
            url: _url,
            onSuccess: function(data){
                if(data.remote.ead ) {
                    $$('header > h2').set('html', data.remote.ead.link);
                    $$('#allInfosRemote').set('html', '<h3 class="header_infos">' + header_ead + '</h3><ul id="ead_list_infos"><li id="intitule_ead"></li><li id="unitid_ead"></li><li id="link_document"></li></ul>');
                    if (data.remote.ead.cUnittitle != null) {
                        $$('#intitule_ead').set('html', '<li><span class="ead_description_head">' + intitule_ead + '</span> ' + data.remote.ead.cUnittitle + '</li>');
                    }
                    if (data.remote.ead.unitid != null) {
                        $$('#unitid_ead').set('html', '<li><span class="ead_description_head">' + unitid_ead + '</span> ' + data.remote.ead.unitid + '</li>');
                    }
                    if (data.remote.ead.doclink != null) {
                        $$('#link_document').set('html', '<li><span class="ead_description_head">' + link_document + '</span> ' + data.remote.ead.doclink + '</li>');
                    }
                }
            },
            onFailure: function(){
                alert('An error occured loading series informations, navigation may fail.');
            }
        }).get();
    },

    /**
     * Display remote infos window
     */
    displayRemoteInfosWindow: function()
    {
        var _win = $$('#remoteInfos_content');

        this.remoteInfos_win_init = true;
        _win.set('style', 'display: block');

        $$('#remoteInfos_content .close').addEvent('click', function(){
            _win.set('style', 'display: none');
            this.remoteInfos_win_init = false;
        });
    },

    /**
     * Overrides IIP navigation
     */
    changeImage: function( name, path ) {
        this.image_name = name;
        this.parent(path);
        this.updateSeriesInfos();
    }
});

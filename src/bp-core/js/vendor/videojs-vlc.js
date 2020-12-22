videojs.Vlc = videojs.MediaTechController.extend({
    init: function (player, options, ready) {
        videojs.MediaTechController.call(this, player, options, ready);
       
        var source = options.source;

        // Generate ID for vlc object
        var objId = player.id() + '_vlc_api';
		
        var self = this;
        this.player_ = player;
        // Merge default parames with ones passed in
        var params = videojs.util.mergeOptions({
            'animationatstart': 'true',
            'transparentatstart': 'true',
            'windowless': 'true',
            'controls': 'false',
            'bgcolor': '#000000',
            'autostart': player.options().autoplay ? 'true' : 'false',
            'allowfullscreen': 'true',
            'text': 'Video.js VLC plug-in'
        }, options.params);
        
        if (source) {
            params.source = source.src;
            this.ready(function() {
                this.setSrc(source.src);
                if (player.options().autoplay) {
                    this.play();
                }
            });
        }
        
        // Merge default attributes with ones passed in
        var attributes = videojs.util.mergeOptions({
            'id': objId,
            'name': objId, // Both ID and Name needed or xap to identify itself
            'class': 'vjs-tech'
        }, options.attributes);
        
        var parentEl = options.parentEl;
        var placeHolder = this.el_ = videojs.Component.prototype.createEl('div', {id: player.id() + 'temp_vlc'});
        
        // Add placeholder to player div
        if (parentEl.firstChild) {
            parentEl.insertBefore(placeHolder, parentEl.firstChild);
        } else {
            parentEl.appendChild(placeHolder);
        }
        
        player.on('fullscreenchange', function() {
            // Workaround to force vmem to resize the video
            var pos = self.getApi().input.position;
            self.getApi().playlist.stop();
            self.getApi().playlist.play();
            self.getApi().input.position = pos;
        });
        
        // Having issues with VLC reloading on certain page actions (hide/resize/fullscreen) in certain browsers
        // This allows resetting the playhead when we catch the reload
        if (options.startTime) {
            this.ready(function(){
                this.load();
                this.play();
                this.currentTime(options.startTime);
            });
        }
        
        player.ready(function() {
            //player.trigger('loadstart');
        });
        
        this.el_ = videojs.Vlc.embed(placeHolder, params, attributes);
        this.el_.tech = this;
        
        // Add VLC events
        videojs.Vlc.registerEvent(this.getApi(), 'MediaPlayerOpening', function() {
            player.trigger('loadstart');
        });
        videojs.Vlc.registerEvent(this.getApi(), 'MediaPlayerBuffering', function() {
            player.trigger('loadeddata');
            
            // Notify video.js to refresh some data from VLC
            player.trigger('volumechange');
        });
        videojs.Vlc.registerEvent(this.getApi(), 'MediaPlayerPlaying', function() {
            player.trigger('play');
            player.trigger('playing');
        });
        videojs.Vlc.registerEvent(this.getApi(), 'MediaPlayerPaused', function() {
            player.trigger('pause');
        });
        videojs.Vlc.registerEvent(this.getApi(), 'MediaPlayerStopped', function() {
            player.trigger('pause');
            player.trigger('ended');
        });
        videojs.Vlc.registerEvent(this.getApi(), 'MediaPlayerEndReached', function() {
            player.trigger('pause');
            player.trigger('ended');
        });
        videojs.Vlc.registerEvent(this.getApi(), 'MediaPlayerTimeChanged', function() {
            player.trigger('timeupdate');
        });
        videojs.Vlc.registerEvent(this.getApi(), 'MediaPlayerPositionChanged', function() {
            player.trigger('progress');
        });
        videojs.Vlc.registerEvent(this.getApi(), 'MediaPlayerLengthChanged', function() {
            player.trigger('durationchange');
        });
        
        // VLC plug-in doesn't have 'ready' event. We assume it is ready after few milliseconds
        setTimeout(function() {
            self.triggerReady();
        }, 100);
    }
});

videojs.Vlc.prototype.params = [];

videojs.Vlc.prototype.dispose = function () {
    if (this.el_) {
        this.el_.parentNode.removeChild(this.el_);
    }

    videojs.MediaTechController.prototype.dispose.call(this);
};

videojs.Vlc.prototype.src = function (src) {
    if (src === undefined) {
        return this.currentSrc();
    }

    // Setting src through `src` not `setSrc` will be deprecated
    return this.setSrc(src);
};

videojs.Vlc.prototype.setSrc = function(src){
    src = videojs.Vlc.getAbsoluteURL(src);
    this.getApi().playlist.items.clear();
    this.getApi().playlist.add(src);
};

videojs.Vlc.prototype.currentSrc = function() {
    if (this.currentSource_) {
        return this.currentSource_.src;
    }
    else {
        return this.getApi().playlist.items[this.getApi().playlist.currentItem];
    }
};

videojs.Vlc.prototype.load = function() {
    // Done automatically
};

videojs.Vlc.prototype.poster = function(){
  this.el_.vjs_getProperty('poster');
};

videojs.Vlc.prototype.setPoster = function(){
  // poster images are not handled by the VLC tech so make this a no-op
};

videojs.Vlc.prototype.play = function() {
    this.getApi().playlist.play();
};

videojs.Vlc.prototype.ended = function() {
    var state = this.getApi().input.state;
    return (state === 6 /* ENDED */ || state === 7 /* ERROR */);
};

videojs.Vlc.prototype.pause = function() {
    this.getApi().playlist.pause();
};

videojs.Vlc.prototype.paused = function() {
    var state = this.getApi().input.state;
    return (state === 4 /* PAUSED */ || state === 6 /* ENDED */);
};

videojs.Vlc.prototype.currentTime = function() {
    return (this.getApi().input.time / 1000);
};

videojs.Vlc.prototype.setCurrentTime = function(seconds) {
    this.getApi().input.time = (seconds * 1000);
};

videojs.Vlc.prototype.duration = function () {
    return (this.getApi().input.length / 1000);
};

videojs.Vlc.prototype.buffered = function () {
    // Not supported
    return [];
};

videojs.Vlc.prototype.volume = function () {
    return this.getApi().audio.volume / 100;
};

videojs.Vlc.prototype.setVolume = function (percentAsDecimal) {
    if (percentAsDecimal) {
        this.getApi().audio.volume = percentAsDecimal * 100;
    }
};

videojs.Vlc.prototype.muted = function () {
    return this.getApi().audio.mute;
};
videojs.Vlc.prototype.setMuted = function (muted) {
    this.getApi().audio.mute.mute = muted;
};

videojs.Vlc.prototype.supportsFullScreen = function () {
    return true;
};

videojs.Vlc.prototype.enterFullScreen = function(){
    this.getApi().video.fullscreen = true;
    this.player_.trigger('fullscreenchange');
};

videojs.Vlc.prototype.exitFullScreen = function(){
    this.getApi().video.fullscreen = false;
    this.player_.trigger('fullscreenchange');
};

videojs.Vlc.prototype.getApi = function() {
    return this.el_;
};

videojs.Vlc.registerEvent = function(vlc, event, handler) {
    if (vlc) {
        if (vlc.attachEvent) {
            // Microsoft
            vlc.attachEvent (event, handler);
        } else if (vlc.addEventListener) {
            // Mozilla: DOM level 2
            vlc.addEventListener(event, handler, false);
        } else {
            // DOM level 0
            vlc['on' + event] = handler;
        }
    }
};

videojs.Vlc.unregisterEvent = function(vlc, event, handler) {
    if (vlc) {
        if (vlc.detachEvent) {
            // Microsoft
            vlc.detachEvent (event, handler);
        } else if (vlc.removeEventListener) {
            // Mozilla: DOM level 2
            vlc.removeEventListener(event, handler, false);
        } else {
            // DOM level 0
            vlc['on' + event] = null;
        }
    }
};

videojs.Vlc.embed = function (placeHolder, params, attributes) {
    var code = videojs.Vlc.getEmbedCode(params, attributes);
    // Get element by embedding code and retrieving created element
    var obj = videojs.Component.prototype.createEl('div', { innerHTML: code }).childNodes[0];
    var par = placeHolder.parentNode;
    
    placeHolder.parentNode.replaceChild(obj, placeHolder);

    return obj;
};

videojs.Vlc.getEmbedCode = function(params, attributes) {

    var objTag,
        key,
        paramsString = '',
        attrsString = '';

    if(window.ActiveXObject) {
        objTag = '<object classid="clsid:9BE31822-FDAD-461B-AD51-BE1D1C159921" codebase="http://download.videolan.org/pub/videolan/vlc/last/win32/axvlc.cab" ';
        
        attributes = videojs.util.mergeOptions({
            // Default to 100% width/height
            'width': '100%',
            'height': '100%',
        
            'tabindex': '-1'
        }, attributes);

        // Create param tags string
        for (key in params) {
            paramsString += '<param name="'+key+'" value="'+params[key]+'" />';
        }

        // Create Attributes string
        for (key in attributes) {
            attrsString += (key + '="' + attributes[key] + '" ');
        }

        return objTag + attrsString + '>' + paramsString + '</object>';
    } else {
        objTag = '<embed type="application/x-vlc-plugin" pluginspage="http://www.videolan.org" ';

        attributes = videojs.util.mergeOptions(params, attributes);
        // Create Attributes string
        for (key in attributes) {
            attrsString += (key + '="' + attributes[key] + '" ');
        }
        
        return objTag + attrsString + '/>';
    }
};

videojs.Vlc.getAbsoluteURL = function(url){
    // Check if absolute URL
    if (!url.match(/^https?:\/\//)) {
        // Convert to absolute URL.
        url = videojs.Component.prototype.createEl('div', {
            innerHTML: '<a href="'+url+'">x</a>'
        }).firstChild.href;
    }
    return url;
};

/* Vlc Support Testing */

videojs.Vlc.isSupported = function () {
    var vlc;

    if(window.ActiveXObject) {
        try {
            vlc = new window.ActiveXObject('VideoLAN.VLCPlugin.2');
        } catch(e) {}
    }
    else if(navigator.plugins && navigator.mimeTypes.length > 0) {
        var name = 'VLC';
        if (navigator.plugins && (navigator.plugins.length > 0)) {
            for(var i=0;i<navigator.plugins.length;++i) {
                if (navigator.plugins[i].name.indexOf(name) !== -1) {
                    vlc = navigator.plugins[i];
                    return true;
                }
            }
        }
    }
    else {
        var obj = document.getElementById('test_vlc');
        if(obj === null){
          obj =  document.createElement('embed');
          obj.setAttribute('style', 'width:0px; height:0px;');
          obj.setAttribute('type', 'application/x-vlc-plugin');
          obj.setAttribute('id','test_vlc');
          document.body.appendChild(obj);
        }
        if(typeof obj.playlist !== 'undefined'){
            obj.parentNode.removeChild(obj);
            return true;
        }
    }

    if (vlc) {
        return true;
    }
    return false;
};

videojs.Vlc.canPlaySource = function (srcObj) {
    // Supported file type depends on VLC installation.
    // It is likely that VLC support the provided file and we cannot ask VLC for that
    // => always accept to play a source
    return 'maybe';
};
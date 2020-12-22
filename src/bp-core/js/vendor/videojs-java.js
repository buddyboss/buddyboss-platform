videojs.Java = videojs.MediaTechController.extend({
    init: function (player, options, ready) {
        var self = this;
        videojs.MediaTechController.call(this, player, options, ready);
       
        var source = options.source;

        // Generate ID for xap object
        var objId = player.id() + '_java_api';
		
        this.player_ = player;
        // Unfortunately we cannot draw div over the applet video because the video constantly refresh the graphic
        // We keep some space for the controls bar
        var height = player.height();
        if (player.controls()) {
            height -= 48;
        }
        
        // Merge default parames with ones passed in
        var params = videojs.util.mergeOptions({
            'id': objId,
            'jscallbackfunction': 'videojs.Java.onEvent',
            'bgcolor': '#000000',
            'permissions': 'sandbox',
            'java_status_events': 'true',
        }, options.params);
        
        if (player.options.autoplay) {
            params.autoplay = player.options.autoplay;
        }
        if (player.options.preload) {
            params.preload = player.options.preload;
        }
        
        // Merge default attributes with ones passed in
        var attributes = videojs.util.mergeOptions({
            'id': objId,
            'name': objId, // Both ID and Name needed or xap to identify itself
            'class': 'vjs-tech',
            'style': 'visibility: hidden; width: ' + player.width() + 'px !important; height: ' + height + 'px !important;'
        }, options.attributes);
        
        // If source was supplied
        if (source) {
            this.ready(function(){
                this.setSrc(source.src);
            });
        }
        
        var parentEl = options.parentEl;
        var placeHolder = this.el_ = videojs.Component.prototype.createEl('div', {id: player.id() + 'temp_java'});
        
        // Add placeholder to player div
        if (parentEl.firstChild) {
            parentEl.insertBefore(placeHolder, parentEl.firstChild);
        } else {
            parentEl.appendChild(placeHolder);
        }
        
        player.ready(function() {
            player.trigger('loadstart');
        });
        
        this.el_ = videojs.Java.embed(placeHolder, params, attributes);
        this.el_.tech = this;
        
        if (this.el_.status < 2 /* READY */) {
            this.el_.onLoad = function() {
                self.triggerReady();
            };
        } else {
            // Applet already loaded or error, trigger ready
            this.triggerReady();
        }
    }
});

videojs.Java.prototype.params = [];

videojs.Java.prototype.dispose = function () {
    if (this.el_) {
        this.el_.parentNode.removeChild(this.el_);
    }

    videojs.MediaTechController.prototype.dispose.call(this);
};

videojs.Java.prototype.src = function (src) {
    if (src === undefined) {
        return this.currentSrc();
    }

    // Setting src through `src` not `setSrc` will be deprecated
    return this.setSrc(src);
};

videojs.Java.prototype.setSrc = function(src){
    src = videojs.Java.getAbsoluteURL(src);
    this.getApi().setSrc(src);
};

videojs.Java.prototype.currentSrc = function() {
    if (this.currentSource_) {
        return this.currentSource_.src;
    }
    
    return '';
};

videojs.Java.prototype.load = function() {
    this.getApi().loadMedia();
};

videojs.Java.prototype.play = function() {
    this.getApi().playMedia();
    
    var controls = this.player_.controls();
    if(controls) {
        this.player_.controls(false);
    }
    this.el_.style.visibility = 'visible';
    if(controls) {
        this.player_.controls(true);
    }
};

videojs.Java.prototype.ended = function() {
    var status = this.getApi().getPlayerStatus();
    return (status !== 2 /* PLAYING */ && status !== 3 /* PAUSED */);
};

videojs.Java.prototype.pause = function() {
    this.getApi().pauseMedia();
};

videojs.Java.prototype.paused = function() {
    var status = this.getApi().getPlayerStatus();
    return (status !== 2 /* PLAYING */);
};

videojs.Java.prototype.currentTime = function() {
    return this.getApi().getCurrentTime();
};

videojs.Java.prototype.setCurrentTime = function(seconds) {
    this.getApi().setCurrentTime(seconds);
};

videojs.Java.prototype.duration = function () {
    return this.getApi().getDuration();
};


videojs.Java.prototype.buffered = function () {
    return [];
};

videojs.Java.prototype.volume = function () {
    return this.getApi().getVolume();
};

videojs.Java.prototype.setVolume = function (percentAsDecimal) {
    if (percentAsDecimal) {
        this.getApi().setVolume(percentAsDecimal);
    }
};

videojs.Java.prototype.muted = function () {
    return this.getApi().getMute();
};
videojs.Java.prototype.setMuted = function (muted) {
    this.getApi().setMute(muted);
};

videojs.Java.prototype.supportsFullScreen = function () {
    return true;
};

videojs.Java.prototype.enterFullScreen = function(){
    this.getApi().enterFullscreen();
};

videojs.Java.prototype.exitFullScreen = function(){
    this.getApi().leaveFullscreen();
};

videojs.Java.prototype.getApi = function() {
    return this.el_;
};

videojs.Java.onEvent = function(javaId, event) {
    var el = document.getElementById(javaId);
    if (el.tech) {
        switch (event) {
            case 'init':
                el.tech.triggerReady();
                break;
            default:
                el.tech.player_.trigger(event);
                break;
        }
    }
};

videojs.Java.embed = function (placeHolder, params, attributes) {
    var code = videojs.Java.getEmbedCode(params, attributes);
    // Get element by embedding code and retrieving created element
    var obj = videojs.Component.prototype.createEl('div', { innerHTML: code }).childNodes[0];
    placeHolder.parentNode.replaceChild(obj, placeHolder);

    return obj;
};

videojs.Java.getEmbedCode = function(params, attributes) {
    var objTag = '<applet code="com.videojs.java.JavaPlayer" archive="' + videojs.options.java.jar + '" ',
        paramsString = '',
        attrsString = '';

    var key;
    // Create param tags string
    for (key in params) {
        paramsString += '<param name="'+key+'" value="'+params[key]+'" />';
    }

    // Create Attributes string
    for (key in attributes) {
        attrsString += (key + '="' + attributes[key] + '" ');
    }

    objTag += attrsString + '>' + paramsString + '</applet>';
    
    return objTag;
};

videojs.Java.getAbsoluteURL = function(url){
    // Check if absolute URL
    if (!url.match(/^https?:\/\//)) {
        // Convert to absolute URL.
        url = videojs.Component.prototype.createEl('div', {
            innerHTML: '<a href="'+url+'">x</a>'
        }).firstChild.href;
    }
    return url;
};

videojs.options.java = {
    'jar': 'video-js.jar'
};

/* Java Support Testing */

videojs.Java.isSupported = function () {
    
    // If deployJava available use it to check Java version
    if (typeof deployJava !== 'undefined') {
        return deployJava.versionCheck('1.7');
    }
    
    // Otherwise only use the minimalist Java test support provided by browsers
    return navigator.javaEnabled();
};

videojs.Java.formats = {
    'audio/aiff': 'AIFF',
    'audio/wav': 'WAV',
    'video/msvideo': 'AVI',
    'video/avi': 'AVI',
    'audio/x-gsm': 'GSM',
    'audio/midi': 'MID',
    'video/mpeg': 'MPG',
    'video/quicktime': 'MOV',
    'audio/basic': 'AU'
};

videojs.Java.canPlaySource = function (srcObj) {
    if (!srcObj.type) {
        return '';
    }
    
    // Strip code information from the type because we don't get that specific
    var type = srcObj.type.replace(/;.*/,'').toLowerCase();
    if (type in videojs.Java.formats) {
        return 'maybe';
    }
    
    return '';
};
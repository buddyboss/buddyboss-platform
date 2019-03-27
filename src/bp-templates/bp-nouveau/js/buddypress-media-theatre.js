/* jshint browser: true */
/* global bp */
/* @version 1.0.0 */
window.bp = window.bp || {};

( function( exports, $ ) {

	// Bail if not set
	if ( typeof BP_Nouveau === 'undefined' ) {
		return;
	}

	bp.Nouveau = bp.Nouveau || {};

	/**
	 * [Media description]
	 * @type {Object}
	 */
	bp.Nouveau.Media.Theatre = {

		/**
		 * [start description]
		 * @return {[type]} [description]
		 */
		start: function() {
			this.setupGlobals();

			// Listen to events ("Add hooks!")
			this.addListeners();

		},

		/**
		 * [setupGlobals description]
		 * @return {[type]} [description]
		 */
		setupGlobals: function() {

			this.medias = [];
			this.current_media = false;
			this.current_index = 0;
			this.is_open = false;
			this.nextLink = $('.bb-next-media');
			this.previousLink = $('.bb-prev-media');

		},

		/**
		 * [addListeners description]
		 */
		addListeners: function() {

			$( document ).on( 'click', '.bb-open-media-theatre',    this.openTheatre.bind( this ) );
			$( document ).on( 'click', '.bb-close-media-theatre',   this.closeTheatre.bind( this ) );
			$( document ).on( 'click', '.bb-prev-media',            this.previous.bind( this ) );
			$( document ).on( 'click', '.bb-next-media',            this.next.bind( this ) );

		},

		documentClick: function( e ) {
			var self = this;
			if ( self.is_open ) {
				var target = e.target;
				var model = document.getElementById('bb-media-model-container');
				if (model != null && !model.contains(target) && document.body.contains(target)) {
					self.closeTheatre(e);
				}
			}
		},

		checkPressedKey: function( e ) {
			var self = this;
			e = e || window.event;
			switch ( e.keyCode ) {
				case 27: // escape key
					self.closeTheatre(e);
					break;
				case 37: // left arrow key code
					self.previous(e);
					break;
				case 39: // right arrow key code
					self.next(e);
					break;
			}
		},

		openTheatre: function(event) {
			event.preventDefault();
			var target = $(event.currentTarget), id, self = this;

			self.setupGlobals();
			self.setMedias();

			id = target.data('id');
			self.setCurrentMedia( id );
			self.showMedia();
			self.navigationCommands();

			$('.bb-media-model-wrapper').show();
			self.is_open = true;

			document.addEventListener( 'keyup', self.checkPressedKey.bind(self) );
			//document.addEventListener( 'click', self.documentClick.bind(self) );
		},

		closeTheatre: function(event) {
			event.preventDefault();
			var self = this;

			$('.bb-media-model-wrapper').hide();
			self.is_open = false;

			document.removeEventListener( 'keyup', self.checkPressedKey.bind(self) );
			//document.removeEventListener( 'click', self.documentClick.bind(self) );
		},

		setMedias: function() {
			var media_elements = $('.bb-open-media-theatre'), i = 0, self = this;
			if ( typeof media_elements !== 'undefined' ) {
				self.medias = [];
				for( i = 0; i < media_elements.length; i++ ) {
					var media_element = $(media_elements[i]);
					self.medias.push({ id : media_element.data( 'id' ), attachment : media_element.data( 'attachment-full' ), activity_id : media_element.data( 'activity-id' ) });
				}

			}
		},

		setCurrentMedia: function( id ) {
			var self = this, i = 0;
			for( i = 0; i < self.medias.length; i++ ) {
				if ( id === self.medias[i].id ) {
					self.current_media = self.medias[i];
					self.current_index = i;
					break;
				}
			}
		},

		showMedia: function() {
			var self = this;
			$('.bb-media-model-wrapper .bb-media-section').find('img').attr('src',self.current_media.attachment+'?'+new Date().getTime());
			self.navigationCommands();
		},

		next: function(event) {
			event.preventDefault();
			var self = this;
			if ( typeof self.medias[self.current_index + 1] !== 'undefined' ) {
				self.current_index = self.current_index + 1;
				self.current_media = self.medias[self.current_index];
				self.showMedia();
			} else {
				self.nextLink.hide();
			}
		},

		previous: function(event) {
			event.preventDefault();
			var self = this;
			if ( typeof self.medias[self.current_index - 1] !== 'undefined' ) {
				self.current_index = self.current_index - 1;
				self.current_media = self.medias[self.current_index];
				self.showMedia();
			} else {
				self.previousLink.hide();
			}
		},

		navigationCommands: function() {
			var self = this;
			if ( self.current_index == 0 && self.current_index != ( self.medias.length - 1 ) ) {
				self.previousLink.hide();
				self.nextLink.show();
			} else if ( self.current_index == 0 && self.current_index == ( self.medias.length - 1 ) ) {
				self.previousLink.hide();
				self.nextLink.hide();
			} else if ( self.current_index == ( self.medias.length - 1 ) ) {
				self.previousLink.show();
				self.nextLink.hide();
			} else {
				self.previousLink.show();
				self.nextLink.show();
			}
		}
	};

	// Launch BP Nouveau Media Theatre
	bp.Nouveau.Media.Theatre.start();

} )( bp, jQuery );

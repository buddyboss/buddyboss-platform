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
			this.activity_ajax = false;

		},

		/**
		 * [addListeners description]
		 */
		addListeners: function() {

			$( document ).on( 'click', '.bb-open-media-theatre',    this.openTheatre.bind( this ) );
			$( document ).on( 'click', '.bb-close-media-theatre',   this.closeTheatre.bind( this ) );
			$( document ).on( 'click', '.bb-prev-media',            this.previous.bind( this ) );
			$( document ).on( 'click', '.bb-next-media',            this.next.bind( this ) );
			$( document ).on( 'bp_activity_ajax_delete_request',    this.activityDeleted.bind( this ) );

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

			if ( target.closest('#bp-existing-media-content').length ) {
				return false;
			}

			self.setupGlobals();
			self.setMedias(target);

			id = target.data('id');
			self.setCurrentMedia( id );
			self.showMedia();
			self.navigationCommands();
			self.getActivity();

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

		setMedias: function(target) {
			var media_elements = $('.bb-open-media-theatre'), i = 0, self = this;

			//check if on activity page, load only activity media in theatre
			if ( $('body').hasClass('activity') ) {
				media_elements = $(target).closest('.bb-activity-media-wrap').find('.bb-open-media-theatre');
			}

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
			var self = this, activity_id;
			if ( typeof self.medias[self.current_index + 1] !== 'undefined' ) {
				self.current_index = self.current_index + 1;
				activity_id = self.current_media.activity_id;
				self.current_media = self.medias[self.current_index];
				self.showMedia();
				if ( activity_id != self.current_media.activity_id ) {
					self.getActivity();
				}
			} else {
				self.nextLink.hide();
			}
		},

		previous: function(event) {
			event.preventDefault();
			var self = this, activity_id;
			if ( typeof self.medias[self.current_index - 1] !== 'undefined' ) {
				self.current_index = self.current_index - 1;
				activity_id = self.current_media.activity_id;
				self.current_media = self.medias[self.current_index];
				self.showMedia();
				if ( activity_id != self.current_media.activity_id ) {
					self.getActivity();
				}
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
		},

		getActivity: function() {
			var self = this;
			$('.bb-media-info-section .activity-list').addClass('loading').html('<i class="dashicons dashicons-update animate-spin"></i>');
			if ( self.current_media && typeof self.current_media.activity_id !== 'undefined' ) {

				if ( self.activity_ajax != false ) {
					self.activity_ajax.abort();
				}

				self.activity_ajax = $.ajax({
					type: 'POST',
					url: BP_Nouveau.ajaxurl,
					data: {
						action: 'media_get_activity',
						id: self.current_media.activity_id,
						nonce: BP_Nouveau.nonces.media
					},
					success: function (response) {
						if (response.success) {
							$('.bb-media-info-section .activity-list').removeClass('loading').html(response.data.activity);
							$('.bb-media-info-section').show();
						}
					}
				});
			} else {
				$('.bb-media-info-section').hide();
			}
		},

		activityDeleted: function(event,data) {
			var self = this, i = 0;
			if (self.is_open && typeof data !== 'undefined' && data.action === 'delete_activity' && self.current_media.activity_id == data.id) {

				$(document).find('[data-bp-list="media"] .bb-open-media-theatre[data-id="' + self.current_media.id + '"]').closest('li').remove();
				$(document).find('[data-bp-list="activity"] .bb-open-media-theatre[data-id="' + self.current_media.id + '"]').closest('.bb-activity-media-elem').remove();

				for (i = 0; i < self.medias.length; i++) {
					if (self.medias[i].activity_id == data.id) {
						self.medias.splice(i, 1);
						break;
					}
				}

				if (self.current_index == 0 && self.current_index != (self.medias.length)) {
					self.current_index = -1;
					self.next(event);
				} else if (self.current_index == 0 && self.current_index == (self.medias.length)) {
					self.closeTheatre(event);
				} else if (self.current_index == (self.medias.length)) {
					self.previous(event);
				} else {
					self.current_index = -1;
					self.next(event);
				}
			}
		}
	};

	// Launch BP Nouveau Media Theatre
	bp.Nouveau.Media.Theatre.start();

} )( bp, jQuery );

/* global bp, BP_Uploader, _, Backbone */

window.bp = window.bp || {};

( function( exports, $ ) {

	// Bail if not set
	if ( typeof BP_Uploader === 'undefined' ) {
		return;
	}

	bp.Models      = bp.Models || {};
	bp.Collections = bp.Collections || {};
	bp.Views       = bp.Views || {};

	bp.CoverImage = {
		start: function() {

			// Init some vars
			this.views   = new Backbone.Collection();
			this.warning = null;

			// The Cover Photo Attachment object.
			this.Attachment = new Backbone.Model();

			// Set up views
			this.uploaderView();

			// Add click handler for the remove cover button
            $( document ).on(
                'click',
                '.bb-rl-remove-cover-button',
                function( e ) {
                    e.preventDefault();
                    
                    // Create a model with the necessary data for deletion
                    var deleteModel = new Backbone.Model(
                        _.pick(
                            BP_Uploader.settings.defaults.multipart_params.bp_params,
                            ['object', 'item_id', 'nonces']
                        )
                    );
                    
                    // Call the deleteCoverImage method
                    bp.CoverImage.deleteCoverImage( deleteModel );
                }
            );
		},

		uploaderView: function() {
			// Listen to the Queued uploads
			bp.Uploader.filesQueue.on( 'add', this.uploadProgress, this );

			// Create the BuddyPress Uploader
			var uploader = new bp.Views.Uploader();

			// Add it to views
			this.views.add( { id: 'upload', view: uploader } );

			// Display it
			uploader.inject( '.bp-cover-image' );
		},

		uploadProgress: function() {
			// Create the Uploader status view
			var coverImageUploadProgress = new bp.Views.coverImageUploadProgress( { collection: bp.Uploader.filesQueue } );

			if ( ! _.isUndefined( this.views.get( 'status' ) ) ) {
				this.views.set( { id: 'status', view: coverImageUploadProgress } );
			} else {
				this.views.add( { id: 'status', view: coverImageUploadProgress } );
			}

			// Display it
			coverImageUploadProgress.inject( '.bp-cover-image-status-progress' );
		},

		deleteCoverImage: function( model ) {
			var self = this;

			// Remove the cover photo !
			bp.ajax.post(
				'bp_cover_image_delete',
				{
					json:          true,
					item_id:       model.get( 'item_id' ),
					object:        model.get( 'object' ),
					nonce:         model.get( 'nonces' ).remove
				}
			).done(
				function( response ) {
						var coverImageStatus = new bp.Views.CoverImageStatus(
							{
								value : BP_Uploader.strings.feedback_messages[ response.feedback_code ],
								type : 'success'
							}
						);

						self.views.add(
							{
								id   : 'status',
								view : coverImageStatus
							}
						);

						coverImageStatus.inject( '.bp-cover-image-status' );

					// Reset the header of the page.
					var $headerCoverImage = $( '#header-cover-image' );
					if ( '' === response.reset_url ) {
						$headerCoverImage.css(
							{
								'background-image': 'none'
							}
						);

						$( '.group-create #header-cover-image' ).css(
							{
								'display': 'none'
							}
						);

					} else {
						$headerCoverImage.css(
							{
								'background-image': 'url( ' + response.reset_url + ' )'
							}
						);
					}

					$headerCoverImage.removeClass( 'has-default' ).addClass( BP_Uploader.settings.defaults.multipart_params.bp_params.has_default_class );
					$headerCoverImage.find( '.header-cover-img' ).remove();
					$headerCoverImage.find( '.position-change-cover-image' ).remove();

						// Reset the has_cover_image bp_param
						BP_Uploader.settings.defaults.multipart_params.bp_params.has_cover_image = false;

						/**
						 * Reset the Attachment object
						 *
						 * You can run extra actions once the cover photo is set using:
						 * bp.CoverImage.Attachment.on( 'change:url', function( data ) { your code } );
						 *
						 * In this case data.attributes will include the default url for the
						 * cover photo (most of the time: ''), the object and the item_id concerned.
						 */
						self.Attachment.set(
							_.extend(
								_.pick( model.attributes, ['object', 'item_id'] ),
								{ url: response.reset_url, action: 'deleted' }
							)
						);

						// Update UI for the cover image preview
						$( '.bb-rl-cover-preview img' ).attr( 'src', '' );
						$( '.bb-rl-cover-container' )
							.removeClass( 'bb-rl-cover-container--has-cover' )
							.addClass( 'bb-rl-cover-container--no-cover' );
						$( '.bp-cover-image-status-progress' ).empty();

				}
			).fail(
				function( response ) {
						var feedback = BP_Uploader.strings.default_error;
					if ( ! _.isUndefined( response ) ) {
						  feedback = BP_Uploader.strings.feedback_messages[ response.feedback_code ];
					}

						var coverImageStatus = new bp.Views.CoverImageStatus(
							{
								value : feedback,
								type : 'error'
							}
						);

						self.views.add(
							{
								id   : 'status',
								view : coverImageStatus
							}
						);

						coverImageStatus.inject( '.bp-cover-image-status' );
				}
			);
		}
	};

	// Custom Uploader Files view
	bp.Views.coverImageUploadProgress = bp.Views.uploaderStatus.extend(
		{
			className: 'files',

			initialize: function() {
				bp.Views.uploaderStatus.prototype.initialize.apply( this, arguments );

				this.collection.on( 'change:url', this.uploadResult, this );
			},

			uploadResult: function( model ) {
				var message, type;

				if ( ! _.isUndefined( model.get( 'url' ) ) ) {

					/*
					* if image url is defined meaning cover image uploaded successfully
					* so feedback message should always be 1
					*/
					message = BP_Uploader.strings.feedback_messages[1];
					type    = 'success';

					var statusView = new bp.Views.CoverImageStatus( {
						value: message,
						type: type
					} );

					// Inject into the other container
					statusView.inject( '.bp-cover-image-status' );

					// Update the header of the page and reset the position.
					var $headerCoverImage = $( '#header-cover-image' );
					if ( $headerCoverImage.find( '.header-cover-img' ).length ) {
						$headerCoverImage.find( '.header-cover-img' ).prop( 'src', model.get( 'url' ) );
					} else {
						$headerCoverImage.prepend( '<img src="' + model.get( 'url' ) + '" class="header-cover-img" alt=""/>' );
					}

					if ( $headerCoverImage.find( '.header-cover-reposition-wrap .guillotine-window img' ).length ) {
						var reposition_img = $headerCoverImage.find( '.header-cover-reposition-wrap .guillotine-window img' );
						$headerCoverImage.find( '.header-cover-reposition-wrap .guillotine-window' ).remove();
						$headerCoverImage.find( '.header-cover-reposition-wrap' ).append( reposition_img );
					}

					$headerCoverImage.find( '.header-cover-reposition-wrap img' ).prop( 'src', model.get( 'url' ) );
					$headerCoverImage.removeClass( 'has-position' ).find( '.header-cover-img' ).removeAttr( 'data-top' ).removeAttr( 'style' );

					$( '.group-create #header-cover-image' ).css(
						{
							'background-image': 'url( ' + model.get( 'url' ) + ' )',
							'display': 'block'
						}
					);

					/**
					 * Set the Attachment object
					 *
					 * You can run extra actions once the cover photo is set using:
					 * bp.CoverImage.Attachment.on( 'change:url', function( data ) { your code } );
					 *
					 * In this case data.attributes will include the url to the newly
					 * uploaded cover photo, the object and the item_id concerned.
					 */
					bp.CoverImage.Attachment.set(
						_.extend(
							_.pick( BP_Uploader.settings.defaults.multipart_params.bp_params, ['object', 'item_id'] ),
							{ url: model.get( 'url' ), action: 'uploaded' }
						)
					);

					// Update UI for the cover image preview
					$( '.bb-rl-cover-preview img' ).attr( 'src', model.get( 'url' ) );
					$( '.bb-rl-cover-container' )
						.removeClass( 'bb-rl-cover-container--no-cover' )
						.addClass( 'bb-rl-cover-container--has-cover' );
					$( '.bp-cover-image-status-progress' ).empty();
				}
			}
		}
	);

	// BuddyPress Cover Photo Feedback view
	bp.Views.CoverImageStatus = bp.View.extend(
		{
			tagName: 'p',
			className: 'updated',
			id: 'bp-cover-image-feedback',

			initialize: function() {
				this.el.className += ' ' + this.options.type;
				this.value         = this.options.value;
			},

			render: function() {
				this.$el.html( this.value );
				return this;
			}
		}
	);

	// BuddyPress Cover Photo Delete view
	bp.Views.DeleteCoverImage = bp.View.extend(
		{
			tagName: 'div',
			id: 'bp-delete-cover-image-container',
			template: bp.template( 'bp-cover-image-delete' ),

			events: {
				'click #bp-delete-cover-image': 'deleteCoverImage'
			},

			deleteCoverImage: function( event ) {
				event.preventDefault();

				bp.CoverImage.deleteCoverImage( this.model );
			}
		}
	);

	bp.CoverImage.start();

})( bp, jQuery );

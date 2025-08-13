/* global bp, BP_Uploader, _, Backbone, Cropper */

window.bp = window.bp || {};

( function( exports, $ ) {

	// Bail if not set
	if ( typeof BP_Uploader === 'undefined' ) {
		return;
	}

	bp.Models      = bp.Models || {};
	bp.Collections = bp.Collections || {};
	bp.Views       = bp.Views || {};

	bp.Avatar = {
		start: function() {
			var self = this;

			/**
			 * Remove the bp-legacy UI
			 *
			 * bp.Avatar successfully loaded, we can now
			 * safely remove the Legacy UI.
			 */
			this.removeLegacyUI();

			// Init some vars
			this.views    = new Backbone.Collection();
			this.cropperInstance = null;
			this.warning  = null;

			// Set up nav
			this.setupNav();

			// Avatars are uploaded files
			this.avatars = bp.Uploader.filesUploaded;

			// The Avatar Attachment object.
			this.Attachment = new Backbone.Model();

			// Wait till the queue is reset
			bp.Uploader.filesQueue.on('reset', this.cropView, this);

			var bodyWpAdmin = $( 'body.wp-admin' ),
			    $document   = $( document );

			/**
			 * In Administration screens we're using Thickbox
			 * We need to make sure to reset the views if it's closed or opened
			 */
			bodyWpAdmin.on(
				'tb_unload',
				'#TB_window',
				function() {
					self.resetViews();
				}
			);

			bodyWpAdmin.on(
				'click',
				'.bp-xprofile-avatar-user-edit',
				function() {
					self.resetViews();
				}
			);

			$document.on(
				'click',
				'.avatar-crop-cancel',
				function( e ) {
					e.preventDefault();
					self.resetViews();
				}
			);

			// Add click handler for the remove avatar button
			$document.on(
				'click',
				'.bb-rl-remove-avatar-button',
				function( e ) {
					e.preventDefault();
					
					// Create a model with the necessary data for deletion
					var deleteModel = new Backbone.Model(
						_.pick(
							BP_Uploader.settings.defaults.multipart_params.bp_params,
							'object',
							'item_id',
							'nonces'
						)
					);
					
					// Call the deleteAvatar method
					self.deleteAvatar( deleteModel );
				}
			);
		},

		removeLegacyUI: function() {
			// User
			if ( $( '#avatar-upload-form' ).length ) {
				$( '#avatar-upload' ).remove();
				$( '#avatar-upload-form p' ).remove();

				// Group Manage
			} else if ( $( '#group-settings-form' ).length ) {
				$( '#group-settings-form p' ).each(
					function( i ) {
						if ( 0 !== i ) {
							  $( this ).remove();
						}
					}
				);

				var avatarDeleteButton = $( '#delete-group-avatar-button' );
				if ( avatarDeleteButton.length ) {
					avatarDeleteButton.remove();
				}

				// Group Create
			} else if ( $( '#group-create-body' ).length ) {
				$( '.main-column p #file' ).remove();
				$( '.main-column p #upload' ).remove();

				// Admin Extended Profile
			} else if ( $( '#bp_xprofile_user_admin_avatar a.bp-xprofile-avatar-user-admin' ).length ) {
				$( '#bp_xprofile_user_admin_avatar a.bp-xprofile-avatar-user-admin' ).remove();
			}

			var avatarFeedback = $( '.bb-custom-profile-group-avatar-feedback' );
			if ( avatarFeedback.find( 'p' ).length ) {
				avatarFeedback.hide();
				avatarFeedback.find( 'p' ).removeClass( 'success error' ).html( '' );
			}

		},

		setView: function( view ) {
			// Clear views
			if ( ! _.isUndefined( this.views.models ) ) {
				_.each(
					this.views.models,
					function( model ) {
						model.get( 'view' ).remove();
					},
					this
				);
			}

			// Reset Views
			this.views.reset();

			// Reset Avatars (file uploaded)
			if ( ! _.isUndefined( this.avatars ) ) {
				this.avatars.reset();
			}

			// Load the required view
			switch ( view ) {
				case 'upload':
					this.uploaderView();
					break;
			}
		},

		resetViews: function() {
			// Reset to the uploader view
			this.setView( 'upload' );

			if ( $( '.bb-custom-profile-group-avatar-feedback p' ).length ) {
				this.removeWarning();
				$( '.bb-custom-profile-group-avatar-feedback' ).hide().find( '.bp-feedback' ).removeClass( 'success error' ).find( 'p' ).html( '' );
			}
		},

		setupNav: function() {
			// Activate the initial view (uploader)
			this.setView( 'upload' );
		},

		uploaderView: function() {
			// Listen to the Queued uploads
			bp.Uploader.filesQueue.on( 'add', this.uploadProgress, this );

			// Create the BuddyPress Uploader
			var uploader = new bp.Views.Uploader();

			// Add it to views
			this.views.add( { id: 'upload', view: uploader } );

			// Display it
			uploader.inject( '.bp-avatar' );
		},

		uploadProgress: function() {

			// Create the Uploader status view
			var avatarStatus = new bp.Views.uploaderStatus( { collection: bp.Uploader.filesQueue } );

			if ( ! _.isUndefined( this.views.get( 'status' ) ) ) {
				this.views.set( { id: 'status', view: avatarStatus } );
			} else {
				this.views.add( { id: 'status', view: avatarStatus } );
			}

			// Display it
			avatarStatus.inject( '.bp-avatar-status-progress' );
		},

		cropView: function() {
			var status;

			// Bail there was an error during the Upload
			if ( _.isEmpty( this.avatars.models ) ) {
				return;
			}

			// Make sure to remove the uploads status
			if ( ! _.isUndefined( this.views.get( 'status' ) ) ) {
				status = this.views.get( 'status' );
				status.get( 'view' ).remove();
				this.views.remove( { id: 'status', view: status } );
			}

			// Create the Avatars view
			var avatar = new bp.Views.Avatars( { collection: this.avatars } );

			// Instead of injecting directly into .bp-avatar, inject into a new container
			if ( !$( '.bp-avatar-crop-container' ).length ) {
				$( '.bp-avatar' ).append( '<div class="bb-rl-crop-container"></div>' );
			}

			this.views.add( { id: 'crop', view: avatar } );

			// Inject into the new container instead of .bp-avatar
			avatar.inject( '.bb-rl-crop-container' );

			if ( $( '.bb-custom-profile-group-avatar-feedback p' ).length ) {
				this.removeWarning();
			}
		},

		setAvatar: function( avatar ) {
			var self = this,
				crop;

			// Remove the crop view
			if ( ! _.isUndefined( this.views.get( 'crop' ) ) ) {
				// Destroy the Cropper instance
				if ( this.cropperInstance ) {
					this.cropperInstance.destroy();
					this.cropperInstance = null;
				}
				crop = this.views.get( 'crop' );
				crop.get( 'view' ).remove();
				this.views.remove( { id: 'crop', view: crop } );

				// Remove the crop container but preserve the uploader
				$( '.bb-rl-crop-container').remove();
			}

			if ( $( '.bb-custom-profile-group-avatar-feedback p' ).length ) {
				$('.buddyboss_page_bp-settings #TB_window #TB_closeWindowButton').trigger('click');
				var avatarUserEdit = $( '.bp-xprofile-avatar-user-edit' );
				if ( avatarUserEdit.length ) {
					avatarUserEdit.html( avatarUserEdit.data( 'uploading' ) );
				}
			}

			// Set the avatar !
			bp.ajax.post(
				'bp_avatar_set',
				{
					json:          true,
					original_file: avatar.get( 'url' ),
					crop_w:        avatar.get( 'w' ),
					crop_h:        avatar.get( 'h' ),
					crop_x:        avatar.get( 'x' ),
					crop_y:        avatar.get( 'y' ),
					item_id:       avatar.get( 'item_id' ),
					item_type:     avatar.get( 'item_type' ),
					object:        avatar.get( 'object' ),
					type:          _.isUndefined( avatar.get( 'type' ) ) ? 'crop' : avatar.get( 'type' ),
					nonce:         avatar.get( 'nonces' ).set
				}
			).done(
				function( response ) {

					if ( $( '.bb-custom-profile-group-avatar-feedback p' ).length ) {
						var avatarUserEdit = $('.bp-xprofile-avatar-user-edit');
						if ( avatarUserEdit.length ) {
							avatarUserEdit.html( avatarUserEdit.data( 'upload' ) );
						}
					}

					var avatarStatus = new bp.Views.AvatarStatus(
						{
							value : BP_Uploader.strings.feedback_messages[ response.feedback_code ],
							type : 'success'
						}
					);

					self.views.add(
						{
							id   : 'status',
							view : avatarStatus
						}
					);

					avatarStatus.inject( '.bp-avatar-status' );

					// Update each avatars of the page
					$( '.' + avatar.get( 'object' ) + '-' + response.item_id + '-avatar' ).each(
						function() {
							$( this ).prop( 'src', response.avatar );
						}
					);

					var avatarHeaderAside = $( '.header-aside-inner .user-link .avatar' );
					if ( avatarHeaderAside.length && ! $( 'body' ).hasClass( 'group-avatar' ) ) {
						avatarHeaderAside.prop( 'src', response.avatar );
						avatarHeaderAside.prop( 'srcset', response.avatar );
					}

					/**
					 * Set the Attachment object
					 *
					 * You can run extra actions once the avatar is set using:
					 * bp.Avatar.Attachment.on( 'change:url', function( data ) { your code } );
					 *
					 * In this case data.attributes will include the url to the newly
					 * uploaded avatar, the object and the item_id concerned.
					 */
					self.Attachment.set(
						_.extend(
							_.pick( avatar.attributes, ['object', 'item_id'] ),
							{ url: response.avatar, action: 'uploaded' }
						)
					);

					// Update container class to reflect has avatar state
					$( '.bb-rl-avatar-container' ).removeClass( 'bb-rl-avatar-container--no-avatar' ).addClass( 'bb-rl-avatar-container--has-avatar' );

					// Show 'Remove' button when upload a new avatar.
					var avatarRemoveButton = $( '.custom-profile-group-avatar a.bb-img-remove-button' );
					if ( avatarRemoveButton.length ) {
						avatarRemoveButton.removeClass( 'bp-hide' );
					}

					// Show image preview when avatar deleted.
					$( '.custom-profile-group-avatar .' + avatar.get( 'object' ) + '-' + response.item_id + '-avatar' ).removeClass( 'bp-hide' );

					// Update each avatars fields of the page
					$( '.custom-profile-group-avatar .bb-upload-container .bb-default-custom-avatar-field' ).val( response.avatar );
					$( '.custom-profile-group-avatar .bb-upload-container img' ).prop( 'src', response.avatar ).removeClass( 'bp-hide' );
					$( '.preview_avatar_cover .preview-item-avatar img' ).prop( 'src', response.avatar );
				}
			).fail(
				function( response ) {
					var avatarFeedback = $( '.bb-custom-profile-group-avatar-feedback' );
					if ( avatarFeedback.find( 'p' ).length ) {
						var avatarUserEdit = $( '.bp-xprofile-avatar-user-edit' );
						if ( avatarUserEdit.length ) {
							avatarUserEdit.html( avatarUserEdit.data( 'upload' ) );
						}
					}

					var feedback = BP_Uploader.strings.default_error;
					if ( ! _.isUndefined( response ) ) {
						feedback = BP_Uploader.strings.feedback_messages[ response.feedback_code ];
					}

					if ( avatarFeedback.find( 'p' ).length ) {
						avatarFeedback.find( 'p' ).removeClass( 'success error' ).addClass( 'error' ).html( feedback );
						avatarFeedback.show();
					}

					var avatarStatus = new bp.Views.AvatarStatus(
						{
							value : feedback,
							type : 'error'
						}
					);

					self.views.add(
						{
							id   : 'status',
							view : avatarStatus
						}
					);

					avatarStatus.inject( '.bp-avatar-status' );
				}
			);
		},

		deleteView:function() {
			// Destroy the Cropper instance if it exists
			if ( this.cropperInstance ) {
				this.cropperInstance.destroy();
				this.cropperInstance = null;
			}

			// Create the delete model
			var delete_model = new Backbone.Model(
				_.pick(
					BP_Uploader.settings.defaults.multipart_params.bp_params,
					'object',
					'item_id',
					'nonces'
				)
			);

			// Create the delete view
			var deleteView = new bp.Views.DeleteAvatar( { model: delete_model } );

			// Add it to views
			this.views.add( { id: 'delete', view: deleteView } );

			// Display it
			deleteView.inject( '.bp-avatar' );
		},

		deleteAvatar: function( model ) {
			var self = this,
				deleteView;

			// Remove the delete view
			if ( ! _.isUndefined( this.views.get( 'delete' ) ) ) {
				deleteView = this.views.get( 'delete' );
				deleteView.get( 'view' ).remove();
				this.views.remove( { id: 'delete', view: deleteView } );
			}

			// Remove the avatar !
			bp.ajax.post(
				'bp_avatar_delete',
				{
					json:          true,
					item_id:       model.get( 'item_id' ),
					object:        model.get( 'object' ),
					nonce:         model.get( 'nonces' ).remove
				}
			).done(
				function( response ) {
						var avatarStatus = new bp.Views.AvatarStatus(
							{
								value : BP_Uploader.strings.feedback_messages[ response.feedback_code ],
								type : 'success'
							}
						);

						self.views.add(
							{
								id   : 'status',
								view : avatarStatus
							}
						);

						avatarStatus.inject( '.bp-avatar-status' );

						// Update each avatars of the page
						$( '.' + model.get( 'object' ) + '-' + response.item_id + '-avatar' ).each(
							function() {
								$( this ).prop( 'src', response.avatar );
							}
						);

						/**
						 * Reset the Attachment object
						 *
						 * You can run extra actions once the avatar is set using:
						 * bp.Avatar.Attachment.on( 'change:url', function( data ) { your code } );
						 *
						 * In this case data.attributes will include the url to the gravatar,
						 * the object and the item_id concerned.
						 */
						self.Attachment.set(
							_.extend(
								_.pick( model.attributes, ['object', 'item_id'] ),
								{ url: response.avatar, action: 'deleted' }
							)
						);

						// Update container class to reflect no avatar state
						$( '.bb-rl-avatar-container' ).removeClass( 'bb-rl-avatar-container--has-avatar' ).addClass( 'bb-rl-avatar-container--no-avatar' );

					var avatarHeaderAside = $( '.header-aside-inner .user-link .avatar' );
					if ( avatarHeaderAside.length && ! $( 'body' ).hasClass( 'group-avatar' ) ) {
						avatarHeaderAside.prop( 'src', response.avatar );
						avatarHeaderAside.prop( 'srcset', response.avatar );
					}
				}
			).fail(
				function( response ) {
						var feedback = BP_Uploader.strings.default_error;
					if ( ! _.isUndefined( response ) ) {
						  feedback = BP_Uploader.strings.feedback_messages[ response.feedback_code ];
					}

						var avatarStatus = new bp.Views.AvatarStatus(
							{
								value : feedback,
								type : 'error'
							}
						);

						self.views.add(
							{
								id   : 'status',
								view : avatarStatus
							}
						);

						avatarStatus.inject( '.bp-avatar-status' );
				}
			);
		},

		removeWarning: function() {
			if ( ! _.isNull( this.warning ) ) {
				this.warning.remove();
			}
		},

		displayWarning: function( message ) {
			this.removeWarning();

			this.warning = new bp.Views.uploaderWarning(
				{
					value: message
				}
			);

			this.warning.inject( '.bp-avatar-status' );
		}
	};

	// Avatars view
	bp.Views.Avatars = bp.View.extend(
		{
			className: 'items',

			initialize: function() {
				// Reset the collection first to remove old models
				this.collection.reset( this.collection.models[ this.collection.models.length - 1 ] );

				_.each( this.collection.models, this.addItemView, this );
			},

			addItemView: function( item ) {
				// Defaults to 150
				var full_d = { full_h: 150, full_w: 150, item_type: '' };

				// Make sure to take in account bp_core_avatar_full_height or bp_core_avatar_full_width php filters
				if ( ! _.isUndefined( BP_Uploader.settings.crop.full_h ) && ! _.isUndefined( BP_Uploader.settings.crop.full_w ) ) {
					full_d.full_h = BP_Uploader.settings.crop.full_h;
					full_d.full_w = BP_Uploader.settings.crop.full_w;
				}

				if ( ! _.isUndefined( BP_Uploader.settings.defaults.multipart_params.bp_params.item_type ) ) {
					full_d.item_type = BP_Uploader.settings.defaults.multipart_params.bp_params.item_type;
				}

				// Set the avatar model
				item.set(
					_.extend(
						_.pick(
							BP_Uploader.settings.defaults.multipart_params.bp_params,
							'object',
							'item_id',
							'nonces'
						),
						full_d
					)
				);

				// Add the view
				this.views.add( new bp.Views.Avatar( { model: item } ) );
			}
		}
	);

	// Avatar view
	bp.Views.Avatar = bp.View.extend(
		{
			className: 'item',
			template: bp.template( 'bp-avatar-item' ),

			events: {
				'click .avatar-crop-submit': 'cropAvatar'
			},

			initialize: function() {
				_.defaults(
					this.options,
					{
						full_h:  BP_Uploader.settings.crop.full_h,
						full_w:  BP_Uploader.settings.crop.full_w,
						aspectRatio : 1
						}
				);

				// Display a warning if the image is smaller than minimum advised
				if ( false !== this.model.get( 'feedback' ) ) {
					bp.Avatar.displayWarning( this.model.get( 'feedback' ) );
				}

				this.on( 'ready', this.initCropper );
			},

			initCropper: function() {
				var self = this,
					tocrop = this.$el.find( '#avatar-to-crop img' ),
					availableWidth = this.$el.width();
			
				if ( !_.isUndefined( this.options.full_h ) && !_.isUndefined( this.options.full_w ) ) {
					this.options.aspectRatio = this.options.full_w / this.options.full_h;
				}
			
				// Make sure the crop preview is at the right of the avatar
				// if the available width allows it.
				if ( this.options.full_w + this.model.get( 'width' ) + 20 < availableWidth ) {
					$( '#avatar-to-crop' ).addClass( 'adjust' );
					this.$el.find( '.avatar-crop-management' ).addClass( 'adjust' );
				}
			
				// Store original image dimensions
				this.originalWidth = tocrop.width();
				this.originalHeight = tocrop.height();
			
				var minContainerWidth = 400;
				if ( window.innerWidth < 544 ) {
					minContainerWidth = 340;
				}
			
				// Initialize Cropper.js
				this.cropper = new Cropper( tocrop[0], {
					aspectRatio: 1,
					viewMode: 1,
					dragMode: 'move',
					autoCropArea: 1,
					minContainerWidth: minContainerWidth,
					minContainerHeight: 400,
					cropBoxMovable: false,
					cropBoxResizable: false,
					toggleDragModeOnDblclick: false,
					crop: function( event ) {
						// Update the model with crop coordinates
						self.model.set( {
							x: Math.round( event.detail.x ),
							y: Math.round( event.detail.y ),
							w: Math.round( event.detail.width ),
							h: Math.round( event.detail.height )
						} );
			
						// Update preview
						self.showPreview( event.detail );
					},
					ready: function() {
						// Store the cropper instance correctly
						bp.Avatar.cropperInstance = self.cropper;
						
						var $slider = self.$el.find( '.bb-rl-zoom-slider' );

						// Set initial value
						updateSliderBackground( $slider[0] );
						
						// Add zoom functionality for slider
						$slider.on( 'input change', function() {
							// Update the slider background
    						updateSliderBackground(this);

							var zoomValue = parseInt( $( this ).val() ) / 100;
							bp.Avatar.cropperInstance.zoomTo( zoomValue );
						} );

						// Function to update slider background
						function updateSliderBackground( slider ) {
							if ( !slider ) { return; }
							
							var min = slider.min || 100;
							var max = slider.max || 200;
							var value = slider.value || 100;
							
							// Calculate percentage
							var percentage = ( ( value - min ) / ( max - min ) ) * 100;
							
							// Set the CSS variable
							slider.style.setProperty( '--slider-value', percentage + '%' );
						}
					}
				} );
			},

			cropAvatar: function( event ) {
				event.preventDefault();

				bp.Avatar.setAvatar( this.model );
			},

			showPreview: function( coords ) {
				if ( !coords.width || !coords.height ) {
					return;
				}
			
				if ( parseInt( coords.width, 10 ) > 0 ) {
					var fw = this.options.full_w;
					var fh = this.options.full_h;
					var rx = fw / coords.width;
					var ry = fh / coords.height;
			
					$( '#avatar-crop-preview' ).css( {
						maxWidth: 'none',
						width: Math.round( rx * this.model.get( 'width' ) ) + 'px',
						height: Math.round(ry * this.model.get( 'height' ) ) + 'px',
						marginLeft: '-' + Math.round( rx * coords.x ) + 'px',
						marginTop: '-' + Math.round( ry * coords.y ) + 'px',
						borderRadius: '50%'
					} );
				}
			},
		}
	);

	// BuddyPress Avatar Feedback view
	bp.Views.AvatarStatus = bp.View.extend(
		{
			tagName: 'p',
			className: 'updated',
			id: 'bp-avatar-feedback',

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

	// BuddyPress Avatar Delete view
	bp.Views.DeleteAvatar = bp.View.extend(
		{
			tagName: 'div',
			id: 'bp-delete-avatar-container',
			template: bp.template( 'bp-avatar-delete' ),

			events: {
				'click #bp-delete-avatar': 'deleteAvatar'
			},

			deleteAvatar: function( event ) {
				event.preventDefault();

				bp.Avatar.deleteAvatar( this.model );
			}
		}
	);

	bp.Avatar.start();

})( bp, jQuery );

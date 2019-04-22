/* global wp, bp, BP_Nouveau, _, Backbone, tinymce, tinyMCE */
/* jshint devel: true */
/* @version 3.1.0 */
window.wp = window.wp || {};
window.bp = window.bp || {};

( function( exports, $ ) {

	// Bail if not set
	if ( typeof BP_Nouveau === 'undefined' ) {
		return;
	}

	_.extend( bp, _.pick( wp, 'Backbone', 'ajax', 'template' ) );

	bp.Models      = bp.Models || {};
	bp.Collections = bp.Collections || {};
	bp.Views       = bp.Views || {};

	bp.Nouveau = bp.Nouveau || {};

	/**
	 * [Nouveau description]
	 * @type {Object}
	 */
	bp.Nouveau.Messages = {
		/**
		 * [start description]
		 * @return {[type]} [description]
		 */
		start: function() {
			this.views    = new Backbone.Collection();
			this.threads  = new bp.Collections.Threads();
			this.messages = new bp.Collections.Messages();
			this.router   = new bp.Nouveau.Messages.Router();
			this.box      = 'inbox';

			if ( !_.isUndefined( window.Dropzone ) && !_.isUndefined( BP_Nouveau.media ) ) {
				this.dropzoneView();
			}

			this.setupNav();

			Backbone.history.start( {
				pushState: true,
				root: BP_Nouveau.messages.rootUrl
			} );
		},

		dropzoneView: function() {
			this.dropzone = null;

			// set up dropzones auto discover to false so it does not automatically set dropzones
			window.Dropzone.autoDiscover = false;

			this.dropzone_options = {
				url: BP_Nouveau.ajaxurl,
				timeout: 3 * 60 * 60 * 1000,
				acceptedFiles: 'image/*',
				autoProcessQueue: true,
				addRemoveLinks: true,
				uploadMultiple: false,
				maxFilesize: typeof BP_Nouveau.media.max_upload_size !== 'undefined' ? BP_Nouveau.media.max_upload_size : 2
			};
		},

		setupNav: function() {
			var self = this;

			// First adapt the compose nav
			$( '#compose-personal-li' ).addClass( 'last' );

			// Then listen to nav click and load the appropriate view
			$( '#subnav a' ).on( 'click', function( event ) {
				event.preventDefault();

				var view_id = $( event.target ).prop( 'id' );

				// Remove the editor to be sure it will be added dynamically later
				self.removeTinyMCE();

				// The compose view is specific (toggle behavior)
				if ( 'compose' === view_id ) {
					// If it exists, it means the user wants to remove it
					if ( ! _.isUndefined( self.views.get( 'compose' ) ) ) {
						var form = self.views.get( 'compose' );
						form.get( 'view' ).remove();
						self.views.remove( { id: 'compose', view: form } );

						// Back to inbox
						if ( 'single' === self.box ) {
							self.box = 'inbox';
						}

						// Navigate back to current box
						self.router.navigate( self.box + '/', { trigger: true } );

					// Otherwise load it
					} else {
						self.router.navigate( 'compose/', { trigger: true } );
					}

				// Other views are classic.
				} else {

					if ( self.box !== view_id || ! _.isUndefined( self.views.get( 'compose' ) ) ) {
						self.clearViews();

						self.router.navigate( view_id + '/', { trigger: true } );
					}
				}
			} );
		},

		removeTinyMCE: function() {
			if ( typeof tinymce !== 'undefined' ) {
				var editor = tinymce.get( 'message_content' );

				if ( editor !== null ) {
					tinymce.EditorManager.execCommand( 'mceRemoveEditor', true, 'message_content' );
				}
			}
		},

		tinyMCEinit: function() {
			if ( typeof window.tinyMCE === 'undefined' || window.tinyMCE.activeEditor === null || typeof window.tinyMCE.activeEditor === 'undefined' ) {
				return;
			} else {
				$( window.tinyMCE.activeEditor.contentDocument.activeElement )
					.atwho( 'setIframe', $( '#message_content_ifr' )[0] )
					.bp_mentions( {
						data: [],
						suffix: ' '
					} );
			}
		},

		removeFeedback: function() {
			var feedback;

			if ( ! _.isUndefined( this.views.get( 'feedback' ) ) ) {
				feedback = this.views.get( 'feedback' );
				feedback.get( 'view' ).remove();
				this.views.remove( { id: 'feedback', view: feedback } );
			}
		},

		displayFeedback: function( message, type ) {
			var feedback;

			// Make sure to remove the feedbacks
			this.removeFeedback();

			if ( ! message ) {
				return;
			}

			feedback = new bp.Views.Feedback( {
				value: message,
				type:  type || 'info'
			} );

			this.views.add( { id: 'feedback', view: feedback } );

			feedback.inject( '.bp-messages-feedback' );
		},

		clearViews: function() {
			// Clear views
			if ( ! _.isUndefined( this.views.models ) ) {
				_.each( this.views.models, function( model ) {
					model.get( 'view' ).remove();
				}, this );

				this.views.reset();
			}
		},

		composeView: function() {
			// Remove all existing views.
			//this.clearViews();

			var threadView = false;
			if ( ! _.isUndefined( this.views.models ) ) {
				_.each( this.views.models, function( model ) {
					if ( model.get('id') === 'threads' ) {
						threadView = true;
					}
				}, this );
			}

			if ( ! threadView ) {
				this.threadsView();
			}

			// Create the loop view
			var form = new bp.Views.messageForm( {
				model: new bp.Models.Message()
			} );

			// Activate the appropriate nav
			$( '#subnav ul li' ).removeClass( 'current selected' );
			$( '#subnav a#compose' ).closest( 'li' ).addClass( 'current selected' );

			this.views.add( { id: 'compose', view: form } );

			form.inject( '.bp-messages-content' );
		},

		threadsView: function() {

			if ( this.box === 'inbox' ) {
				$('.bp-messages-content').html('');
			}

			// Activate the appropriate nav
			$( '#subnav ul li' ).removeClass( 'current selected' );
			$( '#subnav a#' + this.box ).closest( 'li' ).addClass( 'current selected' );

			// Create the loop view
			var threads_list = new bp.Views.userThreads( { collection: this.threads, box: this.box } );

			this.views.add( { id: 'threads', view: threads_list } );

			threads_list.inject( '.bp-messages-threads-list' );

			// Attach filters
			this.displayFilters( this.threads );
		},

		displayFilters: function( collection ) {
			var filters_view;

			// Create the model
			this.filters = new Backbone.Model( {
				'page'         : 1,
				'total_page'   : 0,
				'search_terms' : '',
				'box'          : this.box
			} );

			// Use it in the filters viex
			filters_view = new bp.Views.messageFilters( { model: this.filters, threads: collection } );

			this.views.add( { id: 'filters', view: filters_view } );

			filters_view.inject( '.bp-messages-filters' );
		},

		singleView: function( thread ) {

			this.box = 'single';

			// Remove the editor to be sure it will be added dynamically later
			this.removeTinyMCE();

			var threadView = false;
			if ( ! _.isUndefined( this.views.models ) ) {
				_.each( this.views.models, function( model ) {
					if ( model.get('id') === 'threads' ) {
						threadView = true;
					}
				}, this );
			}

			if ( ! threadView ) {
				// Remove all existing views except threads view.
				this.clearViews();

				this.threadsView();
			}

			// Create the single thread view
			var single_thread = new bp.Views.userMessages( { collection: this.messages, thread: thread } );

			this.views.add( { id: 'single', view: single_thread } );

			single_thread.inject( '.bp-messages-content' );
		}
	};

	bp.Models.Message = Backbone.Model.extend( {
		defaults: {
			send_to         : [],
			subject         : '',
			message_content : '',
			meta            : {}
		},

		sendMessage: function() {
			if ( true === this.get( 'sending' ) ) {
				return;
			}

			this.set( 'sending', true, { silent: true } );

			var sent = bp.ajax.post( 'messages_send_message', _.extend(
				{
					nonce: BP_Nouveau.messages.nonces.send
				},
				this.attributes
			) );

			this.set( 'sending', false, { silent: true } );

			return sent;
		}
	} );

	bp.Models.Thread = Backbone.Model.extend( {
		defaults: {
			id            : 0,
			message_id    : 0,
			subject       : '',
			excerpt       : '',
			content       : '',
			unread        : true,
			sender_name   : '',
			sender_link   : '',
			sender_avatar : '',
			count         : 0,
			date          : 0,
			display_date  : '',
			recipients    : []
		},

		updateReadState: function( options ) {
			options = options || {};
			options.data = _.extend(
				_.pick( this.attributes, ['id', 'message_id'] ),
				{
					action : 'messages_thread_read',
					nonce  : BP_Nouveau.nonces.messages
				}
			);

			return bp.ajax.send( options );
		}
	} );

	bp.Models.messageThread = Backbone.Model.extend( {
		defaults: {
			id            : 0,
			content       : '',
			sender_id     : 0,
			sender_name   : '',
			sender_link   : '',
			sender_avatar : '',
			date          : 0,
			display_date  : ''
		}
	} );

	bp.Collections.Threads = Backbone.Collection.extend( {
		model: bp.Models.Thread,

		initialize : function() {
			this.options = { page: 1, total_page: 0 };
		},

		sync: function( method, model, options ) {
			options         = options || {};
			options.context = this;
			options.data    = options.data || {};

			// Add generic nonce
			options.data.nonce = BP_Nouveau.nonces.messages;

			if ( 'read' === method ) {
				options.data = _.extend( options.data, {
					action: 'messages_get_user_message_threads'
				} );

				return bp.ajax.send( options );
			}
		},

		parse: function( resp ) {

			if ( ! _.isArray( resp.threads ) ) {
				resp.threads = [resp.threads];
			}

			_.each( resp.threads, function( value, index ) {
				if ( _.isNull( value ) ) {
					return;
				}

				resp.threads[index].id            = value.id;
				resp.threads[index].message_id    = value.message_id;
				resp.threads[index].subject       = value.subject;
				resp.threads[index].excerpt       = value.excerpt;
				resp.threads[index].content       = value.content;
				resp.threads[index].unread        = value.unread;
				resp.threads[index].sender_name   = value.sender_name;
				resp.threads[index].sender_link   = value.sender_link;
				resp.threads[index].sender_avatar = value.sender_avatar;
				resp.threads[index].count         = value.count;
				resp.threads[index].date          = new Date( value.date );
				resp.threads[index].display_date  = value.display_date;
				resp.threads[index].recipients    = value.recipients;
				resp.threads[index].star_link     = value.star_link;
				resp.threads[index].is_starred    = value.is_starred;
			} );

			if ( ! _.isUndefined( resp.meta ) ) {
				this.options.page       = resp.meta.page;
				this.options.total_page = resp.meta.total_page;
			}

			if ( bp.Nouveau.Messages.box ) {
				this.options.box = bp.Nouveau.Messages.box;
			}

			if ( ! _.isUndefined( resp.extraContent ) ) {
				_.extend( this.options, _.pick( resp.extraContent, [
					'beforeLoop',
					'afterLoop'
				] ) );
			}

			return resp.threads;
		},

		doAction: function( action, ids, options ) {
			options         = options || {};
			options.context = this;
			options.data    = options.data || {};

			options.data = _.extend( options.data, {
				action: 'messages_' + action,
				nonce : BP_Nouveau.nonces.messages,
				id    : ids
			} );

			return bp.ajax.send( options );
		}
	} );

	bp.Collections.Messages = Backbone.Collection.extend( {
		before: null,
		model: bp.Models.messageThread,
		options: {},

		sync: function( method, model, options ) {
			options         = options || {};
			options.context = this;
			options.data    = options.data || {};

			// Add generic nonce
			options.data.nonce = BP_Nouveau.nonces.messages;

			if ( 'read' === method ) {
				options.data = _.extend( options.data, {
					action: 'messages_get_thread_messages',
					before: this.before
				} );

				return bp.ajax.send( options );
			}

			if ( 'create' === method ) {
				options.data = _.extend( options.data, {
					action : 'messages_send_reply',
					nonce  : BP_Nouveau.messages.nonces.send
				}, model || {} );

				return bp.ajax.send( options );
			}
		},

		parse: function( resp ) {

			if ( ! _.isArray( resp.messages ) ) {
				resp.messages = [resp.messages];
			}

			this.before = resp.next_messages_timestamp;

			_.each( resp.messages, function( value, index ) {
				if ( _.isNull( value ) ) {
					return;
				}

				resp.messages[index].id            = value.id;
				resp.messages[index].content       = value.content;
				resp.messages[index].sender_id     = value.sender_id;
				resp.messages[index].sender_name   = value.sender_name;
				resp.messages[index].sender_link   = value.sender_link;
				resp.messages[index].sender_avatar = value.sender_avatar;
				resp.messages[index].date          = new Date( value.date );
				resp.messages[index].display_date  = value.display_date;
				resp.messages[index].star_link     = value.star_link;
				resp.messages[index].is_starred    = value.is_starred;
			} );

			if ( ! _.isUndefined( resp.thread ) ) {
				this.options.thread_id      = resp.thread.id;
				this.options.thread_subject = resp.thread.subject;
				this.options.recipients     = resp.thread.recipients;
			}

			return resp.messages;
		}
	} );

	// Extend wp.Backbone.View with .prepare() and .inject()
	bp.Nouveau.Messages.View = bp.Backbone.View.extend( {
		inject: function( selector ) {
			this.render();
			$(selector).html( this.el );
			this.views.ready();
		},

		prepare: function() {
			if ( ! _.isUndefined( this.model ) && _.isFunction( this.model.toJSON ) ) {
				return this.model.toJSON();
			} else {
				return {};
			}
		}
	} );

	// Feedback view
	bp.Views.Feedback = bp.Nouveau.Messages.View.extend( {
		tagName: 'div',
		className: 'bp-messages bp-user-messages-feedback',
		template  : bp.template( 'bp-messages-feedback' ),

		initialize: function() {
			this.model = new Backbone.Model( {
				type: this.options.type || 'info',
				message: this.options.value
			} );
		}
	} );

	// Hook view
	bp.Views.Hook = bp.Nouveau.Messages.View.extend( {
		tagName: 'div',
		template  : bp.template( 'bp-messages-hook' ),

		initialize: function() {
			this.model = new Backbone.Model( {
				extraContent: this.options.extraContent
			} );

			this.el.className = 'bp-messages-hook';

			if ( this.options.className ) {
				this.el.className += ' ' + this.options.className;
			}
		}
	} );

	bp.Views.messageEditor = bp.Nouveau.Messages.View.extend( {
		template  : bp.template( 'bp-messages-editor' ),

		initialize: function() {
			this.on( 'ready', this.activateTinyMce, this );
		},

		activateTinyMce: function() {
			if ( typeof tinymce !== 'undefined' ) {
				tinymce.EditorManager.execCommand( 'mceAddEditor', true, 'message_content' );
			}
		}
	} );

	// Messages Media
	bp.Views.MessagesMedia = bp.Nouveau.Messages.View.extend({
		tagName: 'div',
		className: 'messages-media-container',
		template: bp.template( 'messages-media' ),
		media : [],

		initialize: function () {

			this.model.set( 'media', this.media );

			document.addEventListener( 'messages_media_toggle', this.toggle_media_uploader.bind(this) );
			document.addEventListener( 'messages_media_close', this.destroy.bind(this) );
		},

		toggle_media_uploader: function() {
			var self = this;
			if ( self.$el.find('#messages-post-media-uploader').hasClass('open') ) {
				self.destroy();
			} else {
				self.open_media_uploader();
			}
		},

		destroy: function() {
			var self = this;
			self.media = [];
			self.model.set( 'media', self.media );
			if ( ! _.isNull( bp.Nouveau.Messages.dropzone ) ) {
				bp.Nouveau.Messages.dropzone.destroy();
				self.$el.find('#messages-post-media-uploader').html('');
			}
			self.$el.find('#messages-post-media-uploader').removeClass('open').addClass('closed');

			document.removeEventListener( 'messages_media_toggle', this.toggle_media_uploader.bind(this) );
			document.removeEventListener( 'messages_media_close', this.destroy.bind(this) );
		},

		open_media_uploader: function() {
			var self = this;

			if ( self.$el.find('#messages-post-media-uploader').hasClass('open') ) {
				return false;
			}
			self.destroy();

			bp.Nouveau.Messages.dropzone = new window.Dropzone('#messages-post-media-uploader', bp.Nouveau.Messages.dropzone_options );

			bp.Nouveau.Messages.dropzone.on('sending', function(file, xhr, formData) {
				formData.append('action', 'media_upload');
				formData.append('_wpnonce', BP_Nouveau.nonces.media);
			});

			bp.Nouveau.Messages.dropzone.on('success', function(file, response) {
				if ( response.data.id ) {
					file.id = response.data.id;
					response.data.uuid = file.upload.uuid;
					response.data.menu_order = self.media.length;
					self.media.push( response.data );
					self.model.set( 'media', self.media );
				}
			});

			bp.Nouveau.Messages.dropzone.on('removedfile', function(file) {
				if ( self.media.length ) {
					for ( var i in self.media ) {
						if ( file.id === self.media[i].id ) {
							self.media.splice( i, 1 );
							self.model.set( 'media', self.media );
						}
					}
				}
			});

			self.$el.find('#messages-post-media-uploader').addClass('open').removeClass('closed');
		}

	});

	bp.Views.MessagesToolbar = bp.Nouveau.Messages.View.extend( {
		tagName: 'div',
		id: 'whats-new-messages-toolbar',
		template: bp.template( 'whats-new-messages-toolbar' ),
		events: {
			'click #messages-media-button': 'toggleMediaSelector'
		},

		initialize: function() {},

		render: function() {
			this.$el.html(this.template(this.model.toJSON()));
			return this;
		},

		toggleMediaSelector: function( e ) {
			e.preventDefault();
			var event = new Event('messages_media_toggle');
			document.dispatchEvent(event);
		},

		closeMediaSelector: function() {
			var event = new Event('messsages_media_close');
			document.dispatchEvent(event);
		}

	} );

	bp.Views.MessagesAttachments = bp.Nouveau.Messages.View.extend( {
		tagName: 'div',
		id: 'whats-new-messages-attachments',
		messagesMedia: null,
		initialize: function() {
			if ( !_.isUndefined( window.Dropzone ) && !_.isUndefined( BP_Nouveau.media ) && BP_Nouveau.media.messages_media ) {
				this.messagesMedia = new bp.Views.MessagesMedia({model: this.model});
				this.views.add(this.messagesMedia);
			}
		},
		onClose: function() {
			if( ! _.isNull( this.messagesMedia ) ) {
				this.messagesMedia.destroy();
			}
		}
	});

	bp.Views.messageForm = bp.Nouveau.Messages.View.extend( {
		tagName   : 'form',
		id        : 'send_message_form',
		className : 'standard-form',
		template  : bp.template( 'bp-messages-form' ),
		messagesAttachments : false,

		events: {
			'click #bp-messages-send'  : 'sendMessage',
			'click #bp-messages-reset' : 'resetForm'
		},

		initialize: function() {
			// Clone the model to set the resetted one
			this.resetModel = this.model.clone();

			// Add the editor view
			this.views.add( '#bp-message-content', new bp.Views.messageEditor() );
			this.messagesAttachments = new bp.Views.MessagesAttachments( { model: this.model } );
			this.views.add( '#bp-message-content', this.messagesAttachments );
			this.views.add( '#bp-message-content', new bp.Views.MessagesToolbar( { model: this.model } ) );

			this.model.on( 'change', this.resetFields, this );

			// Activate bp_mentions
			this.on( 'ready', this.addSelect2, this );
		},

		addMentions: function() {
			// Add autocomplete to send_to field
			$( this.el ).find( '#send-to-input' ).bp_mentions( {
				data: [],
				suffix: ' '
			} );
		},

		addSelect2: function() {
			var $input = $( this.el ).find( '#send-to-input' );

			if ( $input.prop('tagName') != 'SELECT' ) {
				this.addMentions();
				return;
			}

			$input.select2({
				placeholder: $input.attr('placeholder'),
				minimumInputLength: 1,
				ajax: {
					url: bp.ajax.settings.url,
					dataType: 'json',
					delay: 250,
					data: function(params) {
						return $.extend( {}, params, {
							nonce: BP_Nouveau.messages.nonces.load_recipient,
							action: 'messages_search_recipients'
						});
					},
					processResults: function( data ) {
						return {
							results: data && data.success? data.data.results : []
						};
					}
				}
			});
		},

		resetFields: function( model ) {
			// Clean inputs
			_.each( model.previousAttributes(), function( value, input ) {
				if ( 'message_content' === input ) {
					// tinyMce
					if ( undefined !== tinyMCE.activeEditor && null !== tinyMCE.activeEditor ) {
						tinyMCE.activeEditor.setContent( '' );
					}

				// All except meta or empty value
				} else if ( 'meta' !== input && false !== value ) {
					$( 'input[name="' + input + '"]' ).val( '' );
				}
			} );

			if (this.messageAttachments.onClose){
				this.messageAttachments.onClose();
			}

			// Listen to this to eventually reset your custom inputs.
			$( this.el ).trigger( 'message:reset', _.pick( model.previousAttributes(), 'meta' ) );
		},

		sendMessage: function( event ) {
			var meta = {}, errors = [], self = this;
			event.preventDefault();

			bp.Nouveau.Messages.removeFeedback();

			// Set the content and meta
			_.each( this.$el.serializeArray(), function( pair ) {
				pair.name = pair.name.replace( '[]', '' );

				// Group extra fields in meta
				if ( -1 === _.indexOf( ['send_to', 'message_content'], pair.name ) ) {
					if ( _.isUndefined( meta[ pair.name ] ) ) {
						meta[ pair.name ] = pair.value;
					} else {
						if ( ! _.isArray( meta[ pair.name ] ) ) {
							meta[ pair.name ] = [ meta[ pair.name ] ];
						}

						meta[ pair.name ].push( pair.value );
					}

				// Prepare the core model
				} else {
					// Send to
					if ( 'send_to' === pair.name ) {
						var usernames = pair.value.match( /(^|[^@\w\-])@([a-zA-Z0-9_\-\.]{1,50})\b/g );

						if ( ! usernames ) {
							errors.push( 'send_to' );
						} else {
							usernames = usernames.map( function( username ) {
								username = $.trim( username );
								return username;
							} );

							if ( ! usernames || ! $.isArray( usernames ) ) {
								errors.push( 'send_to' );
							}

							var send_to = this.model.get( 'send_to' );
							usernames = _.union(send_to, usernames);
							this.model.set( 'send_to', usernames, { silent: true } );
						}

					// Subject and content
					} else {
						// Message content
						if ( 'message_content' === pair.name && undefined !== tinyMCE.activeEditor ) {
							pair.value = tinyMCE.activeEditor.getContent();
						}

						if ( ! pair.value ) {
							errors.push( pair.name );
						} else {
							this.model.set( pair.name, pair.value, { silent: true } );
						}
					}
				}

			}, this );

			if ( errors.length ) {
				var feedback = '';
				_.each( errors, function( e ) {
					feedback += BP_Nouveau.messages.errors[ e ] + '<br/>';
				} );

				bp.Nouveau.Messages.displayFeedback( feedback, 'error' );
				return;
			}

			// Set meta
			this.model.set( 'meta', meta, { silent: true } );

			// Send the message.
			this.model.sendMessage().done( function( response ) {
				// Reset the model
				self.model.set( self.resetModel );

				bp.Nouveau.Messages.displayFeedback( response.feedback, response.type );

				// Remove tinyMCE
				bp.Nouveau.Messages.removeTinyMCE();

				// clear message attachments and toolbar
				if (this.messageAttachments.onClose){
					this.messageAttachments.onClose();
				}

				// Remove the form view
				var form = bp.Nouveau.Messages.views.get( 'compose' );
				form.get( 'view' ).remove();
				bp.Nouveau.Messages.views.remove( { id: 'compose', view: form } );

				bp.Nouveau.Messages.router.navigate( 'view/' + response.thread_id + '/' );
				window.location.reload();
			} ).fail( function( response ) {
				if ( response.feedback ) {
					bp.Nouveau.Messages.displayFeedback( response.feedback, response.type );
				}
			} );
		},

		resetForm: function( event ) {
			event.preventDefault();

			this.model.set( this.resetModel );
		}
	} );

	bp.Views.userThreads = bp.Nouveau.Messages.View.extend( {
		tagName   : 'div',

		events: {
			'click .subject' : 'changePreview',
			'scroll' : 'scrolled'
		},

		initialize: function() {
			var Views = [
				new bp.Nouveau.Messages.View( { tagName: 'ul', id: 'message-threads', className: 'message-lists' } )
			];

			_.each( Views, function( view ) {
				this.views.add( view );
			}, this );

			// Load threads for the active view
			this.requestThreads();

			this.collection.on( 'reset', this.cleanContent, this );
			this.collection.on( 'add', this.addThread, this );
		},

		requestThreads: function() {
			this.collection.reset();

			bp.Nouveau.Messages.displayFeedback( BP_Nouveau.messages.loading, 'loading' );

			this.collection.fetch( {
				data    : _.pick( this.options, 'box' ),
				success : _.bind( this.threadsFetched, this ),
				error   : this.threadsFetchError
			} );
		},

		threadsFetched: function() {
			if ( bp.Nouveau.Messages.box !== 'single' ) {
				bp.Nouveau.Messages.removeFeedback();
			}

			// Display the bp_after_member_messages_loop hook.
			if ( this.collection.options.afterLoop ) {
				this.views.add( new bp.Views.Hook( { extraContent: this.collection.options.afterLoop, className: 'after-messages-loop' } ), { at: 1 } );
			}

			// Display the bp_before_member_messages_loop hook.
			if ( this.collection.options.beforeLoop ) {
				this.views.add( new bp.Views.Hook( { extraContent: this.collection.options.beforeLoop, className: 'before-messages-loop' } ), { at: 0 } );
			}

			if ( this.collection.length ) {
				$('.bp-messages-threads-list').removeClass('bp-no-messages');
			}
		},

		scrolled: function( event ) {
			var target = $( event.currentTarget );

			if ( ( target[0].scrollHeight - ( target.scrollTop() - 10 ) ) == target.innerHeight() &&
				this.collection.length &&
				this.collection.options.page < this.collection.options.total_page
			) {
				this.collection.options.page = this.collection.options.page + 1;

				bp.Nouveau.Messages.displayFeedback( BP_Nouveau.messages.loading, 'loading' );

				_.extend( this.collection.options, _.pick( bp.Nouveau.Messages.filters.attributes, ['box', 'search_terms'] ) );

				this.collection.fetch( {
					remove  : false,
					data    : _.pick( this.collection.options, ['box', 'search_terms', 'page'] ),
					success : this.threadsFetched,
					error   : this.threadsFetchError
				} );
			}
		},

		threadsFetchError: function( collection, response ) {
			bp.Nouveau.Messages.displayFeedback( response.feedback, response.type );

			if ( ! collection.length ) {
				$('.bp-messages-threads-list').addClass('bp-no-messages');
			}
		},

		cleanContent: function() {
			_.each( this.views._views['#message-threads'], function( view ) {
				view.remove();
			} );
		},

		addThread: function( thread ) {
			var selected = this.collection.findWhere( { active: true } );

			if ( _.isUndefined( selected ) ) {
				thread.set( 'active', true );
			}

			this.views.add( '#message-threads', new bp.Views.userThread( { model: thread } ) );
		},

		setActiveThread: function( active ) {
			if ( ! active ) {
				return;
			}

			_.each( this.collection.models, function( thread ) {
				if ( thread.id === active ) {
					thread.set( 'active', true );
				} else {
					thread.unset( 'active' );
				}
			}, this );
		},

		changePreview: function( event ) {
			var target = $( event.currentTarget );

			event.preventDefault();
			bp.Nouveau.Messages.removeFeedback();

			this.setActiveThread( target.closest( '.thread-content' ).data( 'thread-id' ) );
			var selected = this.collection.findWhere( { active: true } );

			// selected.updateReadState();
			if ( selected.get('unread') ) {
				selected.updateReadState().done( function() {
					selected.set( 'unread', false );

					bp.Nouveau.Messages.router.navigate(
						'view/' + target.closest( '.thread-content' ).data( 'thread-id' ) + '/',
						{ trigger: true }
					);
				} );
			} else {
				bp.Nouveau.Messages.router.navigate(
					'view/' + target.closest( '.thread-content' ).data( 'thread-id' ) + '/',
					{ trigger: true }
				);
			}

			$.each( $( '.thread-content' ), function() {
				$(this).closest('.thread-item').removeClass('current');
			} );

			target.closest( '.thread-item' ).addClass('current');
		}
	} );

	bp.Views.userThread = bp.Nouveau.Messages.View.extend( {
		tagName   : 'li',
		template  : bp.template( 'bp-messages-thread' ),
		className : 'thread-item',

		events: {
			'click .message-check' : 'singleSelect'
		},

		initialize: function() {
			if ( this.model.get( 'unread' ) ) {
				this.el.className += ' unread';
			}

			if ( $('#thread-id').val() == this.model.get('id') ) {
				this.el.className += ' current';
			}

			var recipientsCount = this.model.get( 'recipients' ).length, toOthers = '';

			if ( recipientsCount === 5 ) {
				toOthers = BP_Nouveau.messages.toOthers.one;
			} else if ( recipientsCount > 4 ) {
				toOthers = BP_Nouveau.messages.toOthers.more.replace( '%d', Number( recipientsCount - 4 ) );
			}

			this.model.set( {
				recipientsCount: recipientsCount,
				toOthers: toOthers
			}, { silent: true } );

			this.model.on( 'change:unread', this.updateReadState, this );
			this.model.on( 'change:checked', this.bulkSelect, this );
			this.model.on( 'remove', this.cleanView, this );
		},

		updateReadState: function( model, state ) {
			if ( false === state ) {
				$( this.el ).removeClass( 'unread' );
			} else {
				$( this.el ).addClass( 'unread' );
			}
		},

		bulkSelect: function( model ) {
			if ( $( '#bp-message-thread-' + model.get( 'id' ) ).length ) {
				$( '#bp-message-thread-' + model.get( 'id' ) ).prop( 'checked',model.get( 'checked' ) );
			}
		},

		singleSelect: function( event ) {
			var isChecked = $( event.currentTarget ).prop( 'checked' );

			// To avoid infinite loops
			this.model.set( 'checked', isChecked, { silent: true } );

			var hasChecked = false;

			_.each( this.model.collection.models, function( model ) {
				if ( true === model.get( 'checked' ) ) {
					hasChecked = true;
				}
			} );

			if ( hasChecked ) {
				$( '#user-messages-bulk-actions' ).closest( '.bulk-actions-wrap' ).removeClass( 'bp-hide' );

				// Inform the user about how to use the bulk actions.
				bp.Nouveau.Messages.displayFeedback( BP_Nouveau.messages.howtoBulk, 'info' );
			} else {
				$( '#user-messages-bulk-actions' ).closest( '.bulk-actions-wrap' ).addClass( 'bp-hide' );

				bp.Nouveau.Messages.removeFeedback();
			}
		},

		cleanView: function() {
			this.views.view.remove();
		}
	} );

	bp.Views.Pagination = bp.Nouveau.Messages.View.extend( {
		tagName   : 'li',
		className : 'last filter',
		template  :  bp.template( 'bp-messages-paginate' )
	} );

	bp.Views.messageFilters = bp.Nouveau.Messages.View.extend( {
		tagName: 'ul',
		template:  bp.template( 'bp-messages-filters' ),

		events : {
			'search #user_messages_search'      : 'resetSearchTerms',
			'submit #user_messages_search_form' : 'setSearchTerms',
			'click #bp-messages-next-page'      : 'nextPage',
			'click #bp-messages-prev-page'      : 'prevPage'
		},

		initialize: function() {
			this.model.on( 'change', this.filterThreads, this );
			this.options.threads.on( 'sync', this.addPagination, this );
		},

		addPagination: function( collection ) {
			_.each( this.views._views, function( view ) {
				if ( ! _.isUndefined( view ) ) {
					_.first( view ).remove();
				}
			} );

			this.views.add( new bp.Views.Pagination( { model: new Backbone.Model( collection.options ) } ) );

			// this.views.add( '.user-messages-bulk-actions', new bp.Views.BulkActions( {
			// 	model: new Backbone.Model( BP_Nouveau.messages.bulk_actions ),
			// 	collection : collection
			// } ) );
		},

		filterThreads: function() {
			bp.Nouveau.Messages.displayFeedback( BP_Nouveau.messages.loading, 'loading' );

			this.options.threads.reset();
			_.extend( this.options.threads.options, _.pick( this.model.attributes, ['box', 'search_terms'] ) );

			this.options.threads.fetch( {
				data    : _.pick( this.model.attributes, ['box', 'search_terms', 'page'] ),
				success : this.threadsFiltered,
				error   : this.threadsFilterError
			} );
		},

		threadsFiltered: function() {
			bp.Nouveau.Messages.removeFeedback();
		},

		threadsFilterError: function( collection, response ) {
			bp.Nouveau.Messages.displayFeedback( response.feedback, response.type );
		},

		resetSearchTerms: function( event ) {
			event.preventDefault();

			if ( ! $( event.target ).val() ) {
				$( event.target ).closest( 'form' ).submit();
			} else {
				$( event.target ).closest( 'form' ).find( '[type=submit]' ).addClass('bp-show').removeClass('bp-hide');
			}
		},

		setSearchTerms: function( event ) {
			event.preventDefault();

			this.model.set( {
				'search_terms': $( event.target ).find( 'input[type=search]' ).val() || '',
				page: 1
			} );
		},

		nextPage: function( event ) {
			event.preventDefault();

			this.model.set( 'page', this.model.get( 'page' ) + 1 );
		},

		prevPage: function( event ) {
			event.preventDefault();

			this.model.set( 'page', this.model.get( 'page' ) - 1 );
		}
	} );

	bp.Views.userMessagesLoadMore = bp.Nouveau.Messages.View.extend( {
		tagName  : 'div',
		template : bp.template( 'bp-messages-single-load-more' ),

		events: {
			'click button' : 'loadMoreMessages'
		},

		loadMoreMessages: function(e) {
			e.preventDefault();

			var data = {};

			$(this.$el).find('button').addClass('loading');
			bp.Nouveau.Messages.displayFeedback( BP_Nouveau.messages.loading, 'loading' );

			if ( _.isUndefined( this.options.thread.attributes ) ) {
				data.id = this.options.thread.id;
			} else {
				data.id        = this.options.thread.get( 'id' );
				data.js_thread = ! _.isEmpty( this.options.thread.get( 'subject' ) );
			}

			this.collection.fetch( {
				data: data,
				success: _.bind( this.options.userMessage.messagesFetched, this.options.userMessage ),
				error: _.bind( this.options.userMessage.messagesFetchError, this.options.userMessage )
			});
		}
	} );

	bp.Views.userMessagesHeader = bp.Nouveau.Messages.View.extend( {
		tagName  : 'div',
		template : bp.template( 'bp-messages-single-header' ),

		events: {
			'click .actions a' : 'doAction',
			'click .actions button' : 'doAction'
		},

		doAction: function( event ) {
			var action = $( event.currentTarget ).data( 'bp-action' ), self = this, options = {},
			    feedback = BP_Nouveau.messages.doingAction;

			if ( ! action ) {
				return event;
			}

			event.preventDefault();

			if ( ! this.model.get( 'id' ) ) {
				return;
			}

			if ( 'star' === action || 'unstar' === action ) {
				var opposite = {
					'star'  : 'unstar',
					'unstar' : 'star'
				};

				options.data = {
					'star_nonce' : this.model.get( 'star_nonce' )
				};

				$( event.currentTarget ).addClass( 'bp-hide' );
				$( event.currentTarget ).parent().find( '[data-bp-action="' + opposite[ action ] + '"]' ).removeClass( 'bp-hide' );

			}

			if ( ! _.isUndefined( feedback[ action ] ) ) {
				bp.Nouveau.Messages.displayFeedback( feedback[ action ], 'loading' );
			}

			if ( 'delete' === action ) {
				if (! confirm(BP_Nouveau.messages.delete_confirmation) ) {
					bp.Nouveau.Messages.removeFeedback();
					return;
				}
			}

			bp.Nouveau.Messages.threads.doAction( action, this.model.get( 'id' ), options ).done( function( response ) {
				// Remove all views
				if ( 'delete' === action ) {
					//bp.Nouveau.Messages.clearViews();
					// Navigate back to current box
					bp.Nouveau.Messages.router.navigate( 'inbox/', { trigger: true } );
				} else if ( response.messages ) {
					self.model.set( _.first( response.messages ) );
				}

				// Remove previous feedback.
				bp.Nouveau.Messages.removeFeedback();

				// Display the feedback
				bp.Nouveau.Messages.displayFeedback( response.feedback, response.type );
			} ).fail( function( response ) {
				// Remove previous feedback.
				bp.Nouveau.Messages.removeFeedback();

				bp.Nouveau.Messages.displayFeedback( response.feedback, response.type );
			} );
		}
	} );

	bp.Views.userMessagesEntry = bp.Views.userMessagesHeader.extend( {
		tagName  : 'li',
		template : bp.template( 'bp-messages-single-list' ),

		events: {
			'click [data-bp-action]' : 'doAction'
		},

		initialize: function() {
			this.model.on( 'change:is_starred', this.updateMessage, this );
		},

		updateMessage: function( model ) {
			if ( this.model.get( 'id' ) !== model.get( 'id' ) ) {
				return;
			}

			this.render();
		}
	} );

	bp.Views.userMessages = bp.Nouveau.Messages.View.extend( {
		tagName  : 'div',
		template : bp.template( 'bp-messages-single' ),
		messageAttachments : false,
		firstFetch : true,
		firstLi : true,

		initialize: function() {
			// Load Messages
			this.requestMessages();

			// Init a reply
			this.model = new bp.Models.messageThread();

			this.collection.on( 'add', this.addMessage, this );

			// Add the editor view
			this.views.add( '#bp-message-content', new bp.Views.messageEditor() );
			this.messageAttachments = new bp.Views.MessagesAttachments( { model: this.model } );
			this.views.add( '#bp-message-content', this.messageAttachments );
			this.views.add( '#bp-message-content', new bp.Views.MessagesToolbar( { model: this.model } ) );

			this.views.add( '#bp-message-load-more', new bp.Views.userMessagesLoadMore( {
				collection: this.collection,
				thread: this.options.thread,
				userMessage: this
			} ) );
		},

		events: {
			'click #send_reply_button' : 'sendReply'
		},

		requestMessages: function() {
			var data = {};
			this.options.collection.before = null;

			// this.collection.reset();

			bp.Nouveau.Messages.displayFeedback( BP_Nouveau.messages.loading, 'loading' );

			if ( _.isUndefined( this.options.thread.attributes ) ) {
				data.id = this.options.thread.id;

			} else {
				data.id        = this.options.thread.get( 'id' );
				data.js_thread = ! _.isEmpty( this.options.thread.get( 'subject' ) );
			}

			this.collection.fetch( {
				data: data,
				success : _.bind( this.messagesFetched, this ),
				error: _.bind( this.messagesFetchError, this )
			} );
		},

		messagesFetched: function( collection, response ) {
			var loadMore = null;
			collection.before = response.next_messages_timestamp;

			if ( ! _.isUndefined( response.thread ) ) {
				this.options.thread = new Backbone.Model( response.thread );
			}

			bp.Nouveau.Messages.removeFeedback();

			if ( response.feedback_error && response.feedback_error.feedback && response.feedback_error.type ) {
				bp.Nouveau.Messages.displayFeedback( response.feedback_error.feedback, response.feedback_error.type );
				//hide reply form
				this.$('#send-reply').hide();
			}

			if ( this.firstFetch ) {
				document.getElementById('bp-message-thread-list').scrollTop = $('#bp-message-thread-list>li:last-child').position().top;
				this.firstFetch = false;
			} else {
				document.getElementById('bp-message-thread-list').scrollTop = this.firstLi.position().top - this.firstLi.outerHeight();
			}

			if ( response.messages.length < response.per_page && ! _.isUndefined( this.views.get( '#bp-message-load-more' ) ) ) {
				loadMore = this.views.get( '#bp-message-load-more' )[0];
				loadMore.views.view.remove();
			} else {
				loadMore = this.views.get( '#bp-message-load-more' )[0];
				loadMore.views.view.$el.find('button').removeClass('loading').show();

				this.firstLi = $('#bp-message-thread-list>li:first-child');

				// add scroll event for the auto load messages without user having to click the button
				$('#bp-message-thread-list').on('scroll', this.messages_scrolled );
			}

			if ( ! this.views.get( '#bp-message-thread-header' ) ) {
				this.views.add( '#bp-message-thread-header', new bp.Views.userMessagesHeader( { model: this.options.thread } ) );
			}
		},

		messages_scrolled: function( event ) {
			var target = $( event.currentTarget );
			if ( target.scrollTop() <= 1 ) {
				var button = $('#bp-message-load-more').find('button');
				if ( !button.hasClass('loading') ) {
					button.trigger('click');
				}
			}
		},

		messagesFetchError: function( collection, response ) {
			var loadMore = null;
			if ( ! response.messages ) {
				collection.hasMore = false;
			}

			bp.Nouveau.Messages.removeFeedback();

			if ( ! response.messages && ! _.isUndefined( this.views.get( '#bp-message-load-more' ) ) ) {
				loadMore = this.views.get( '#bp-message-load-more' )[0];
				loadMore.views.view.remove();
			} else {
				loadMore = this.views.get( '#bp-message-load-more' )[0];
				loadMore.views.view.$el.find('button').removeClass('loading');
			}

			if ( response.feedback && response.type ) {
				bp.Nouveau.Messages.displayFeedback( response.feedback, response.type );
			}
		},

		addMessage: function( message ) {
			var options = {};

			if ( ! message.attributes.is_new ) {
				options.at = 0;
			}

			this.views.add( '#bp-message-thread-list', new bp.Views.userMessagesEntry( { model: message } ), options );
		},

		addEditor: function() {
			// Load the Editor
			this.views.add( '#bp-message-content', new bp.Views.messageEditor() );
		},

		sendReply: function( event ) {
			event.preventDefault();

			if ( true === this.model.get( 'sending' ) ) {
				return;
			}

			this.model.set ( {
				thread_id : this.options.thread.get( 'id' ),
				content   : tinyMCE.activeEditor.getContent(),
				sending   : true
			} );

			jQuery(tinyMCE.activeEditor.formElement).addClass('loading');

			this.collection.sync( 'create', this.model.attributes, {
				success : _.bind( this.replySent, this ),
				error   : _.bind( this.replyError, this )
			} );
		},

		replySent: function( response ) {
			var reply = this.collection.parse( response );

			// Reset the form
			tinyMCE.activeEditor.setContent( '' );
			this.model.set( 'sending', false );
			jQuery(tinyMCE.activeEditor.formElement).removeClass('loading');

			if (this.messageAttachments.onClose){
				this.messageAttachments.onClose();
			}

			this.collection.add( _.first( reply ) );
		},

		replyError: function( response ) {
			if ( response.feedback && response.type ) {
				bp.Nouveau.Messages.displayFeedback( response.feedback, response.type );
			}
		}
	} );

	bp.Nouveau.Messages.Router = Backbone.Router.extend( {
		routes: {
			'compose/' : 'composeMessage',
			'view/:id/': 'viewMessage',
			'starred/' : 'starredView',
			'inbox/'   : 'inboxView',
			''        : 'inboxView'
		},

		composeMessage: function() {
			bp.Nouveau.Messages.composeView();

			$('body').removeClass('view').removeClass('inbox').addClass('compose');
		},

		viewMessage: function( thread_id ) {
			if ( ! thread_id ) {
				return;
			}

			// Try to get the corresponding thread
			var thread = bp.Nouveau.Messages.threads.get( thread_id );

			if ( undefined === thread ) {
				thread    = {};
				thread.id = thread_id;
			}

			bp.Nouveau.Messages.singleView( thread );

			// set current thread id
			$('#thread-id').val(thread_id);

			$.each( $( '.thread-content' ), function() {
				var _this = $(this);
				if ( _this.data('thread-id') == thread_id ) {
					_this.closest('.thread-item').addClass('current');
				} else {
					_this.closest('.thread-item').removeClass('current');
				}
			} );

			$('body').removeClass('compose').removeClass('inbox').addClass('view');
		},

		starredView: function() {
			bp.Nouveau.Messages.box = 'starred';
			bp.Nouveau.Messages.threadsView();
		},

		inboxView: function() {
			bp.Nouveau.Messages.box = 'inbox';
			bp.Nouveau.Messages.threadsView();

			$('body').removeClass('view').removeClass('compose').addClass('inbox');
		}
	} );

	// Launch BP Nouveau Groups
	bp.Nouveau.Messages.start();

} )( bp, jQuery );

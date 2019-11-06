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
			this.views          = new Backbone.Collection();
			this.threads        = new bp.Collections.Threads();
			this.messages       = new bp.Collections.Messages();
			this.router         = new bp.Nouveau.Messages.Router();
			this.box            = 'inbox';
			this.mediumEditor    = false;

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
					self.router.navigate( 'compose/', { trigger: true } );
                                        $( event.target ).parents('.bp-messages-container').removeClass('bp-view-message').addClass('bp-compose-message');
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

			if ( ! threadView || ! this.threads.length ) {
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
			
			//show compose message screen
			$('.bp-messages-container').removeClass('bp-view-message').addClass('bp-compose-message');
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

			if ( collection.length ) {
				// Use it in the filters viex
				filters_view = new bp.Views.messageFilters({model: this.filters, threads: collection});

				this.views.add({id: 'filters', view: filters_view});

				filters_view.inject('.bp-messages-filters');
			}
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

			if ( ! threadView || ! this.threads.length ) {

				// Set has threads expliciltely true if single view is on because obviously.
				BP_Nouveau.messages.hasThreads = true;

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

	bp.Models.GifResults = Backbone.Model.extend( {
		defaults: {
			q: '',
			data: []
		}
	});

	bp.Models.GifData = Backbone.Model.extend({});

	// Git results collection returned from giphy api
	bp.Collections.GifDatas =  Backbone.Collection.extend( {
		// Reference to this collection's model.
		model: bp.Models.GifData
	});

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

	// Loading view
	bp.Views.MessagesLoading = bp.Nouveau.Messages.View.extend( {
		tagName: 'div',
		className: 'bp-messages bp-user-messages-loading loading',
		template  : bp.template( 'bp-messages-loading' )
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
			if ( !_.isUndefined(window.MediumEditor) ) {

				bp.Nouveau.Messages.mediumEditor = new window.MediumEditor('#message_content',{
					placeholder: {
						text: BP_Nouveau.messages.type_message,
						hideOnClick: true
					},
					toolbar: {
						buttons: ['bold', 'italic', 'unorderedlist','orderedlist', 'quote', 'anchor' ]
					}
				});

				if (!_.isUndefined(BP_Nouveau.media) && !_.isUndefined(BP_Nouveau.media.emoji)) {
					$('#message_content').emojioneArea({
						standalone: true,
						hideSource: false,
						container: $('#whats-new-messages-toolbar > .post-emoji'),
						autocomplete: false,
						pickerPosition: 'bottom',
						hidePickerOnBlur: true,
						useInternalCDN: false,
						events: {
							emojibtn_click: function () {
								$('#message_content')[0].emojioneArea.hidePicker();
							},
						}
					});
				}

				// check for mentions in the url, if set any then focus to editor
				var mention = bp.Nouveau.getLinkParams( null, 'r' ) || null;

				// Check for mention
				if ( ! _.isNull( mention ) ) {
					$('#message_content').focus();
				}

			} else if ( typeof tinymce !== 'undefined' ) {
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
			if ( ! _.isNull( bp.Nouveau.Messages.dropzone ) ) {
				bp.Nouveau.Messages.dropzone.destroy();
				self.$el.find('#messages-post-media-uploader').html('');
			}
			self.media = [];
			self.model.set( 'media', self.media );
			self.$el.find('#messages-post-media-uploader').removeClass('open').addClass('closed');

			document.removeEventListener( 'messages_media_toggle', this.toggle_media_uploader.bind(this) );
			document.removeEventListener( 'messages_media_close', this.destroy.bind(this) );

			$('#whats-new-messages-attachments').addClass('empty');
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
					response.data.menu_order = $(file.previewElement).closest('.dropzone').find(file.previewElement).index() - 1;
					response.data.saved = false;
					self.media.push( response.data );
					self.model.set( 'media', self.media );
				}
			});

			bp.Nouveau.Messages.dropzone.on('error', function(file,response) {
				if ( file.accepted ) {
					if ( typeof response !== 'undefined' && typeof response.data !== 'undefined' && typeof response.data.feedback !== 'undefined' ) {
						$(file.previewElement).find('.dz-error-message span').text(response.data.feedback);
					}
				} else {
					bp.Nouveau.Messages.dropzone.removeFile(file);
				}
			});

			bp.Nouveau.Messages.dropzone.on('removedfile', function(file) {
				if ( self.media.length ) {
					for ( var i in self.media ) {
						if ( file.id === self.media[i].id ) {
							if ( typeof self.media[i].saved !== 'undefined' && ! self.media[i].saved ) {
								bp.Nouveau.Media.removeAttachment(file.id);
							}
							self.media.splice( i, 1 );
							self.model.set( 'media', self.media );
						}
					}
				}
			});

			self.$el.find('#messages-post-media-uploader').addClass('open').removeClass('closed');
			$('#whats-new-messages-attachments').addClass('empty');
		}

	});

	// Activity gif selector
	bp.Views.MessagesAttachedGifPreview = bp.Nouveau.Messages.View.extend( {
		tagName: 'div',
		className: 'messages-attached-gif-container',
		template: bp.template( 'messages-attached-gif' ),
		events: {
			'click .gif-image-remove': 'destroy'
		},

		initialize: function() {
			this.model.set( 'gif_data', {} );
			this.listenTo( this.model, 'change', this.render );
			document.addEventListener( 'messages_gif_close', this.destroy.bind(this) );
		},

		render: function() {
			this.$el.html( this.template( this.model.toJSON() ) );

			var gifData = this.model.get('gif_data');
			if ( ! _.isEmpty( gifData ) ) {
				this.el.style.backgroundImage = 'url(' + gifData.images.fixed_width.url + ')';
				this.el.style.backgroundSize = 'contain';
				this.el.style.height = gifData.images.original.height + 'px';
				this.el.style.width = gifData.images.original.width + 'px';
				$('#whats-new-messages-attachments').addClass('empty');
			}

			return this;
		},

		destroy: function() {
			this.model.set('gif_data', {} );
			this.el.style.backgroundImage = '';
			this.el.style.backgroundSize = '';
			this.el.style.height = '0px';
			this.el.style.width = '0px';
			document.removeEventListener( 'messages_gif_close', this.destroy.bind(this) );
			$('#whats-new-messages-attachments').addClass('empty');
		}
	} );

	// Gif search dropdown
	bp.Views.MessagesGifMediaSearchDropdown = bp.Nouveau.Messages.View.extend( {
		tagName: 'div',
		className: 'messages-attached-gif-container',
		template: bp.template( 'messages-gif-media-search-dropdown' ),
		total_count: 0,
		offset: 0,
		limit: 20,
		q: null,
		requests: [],
		events: {
			'keyup .search-query-input': 'search',
			'click .found-media-item': 'select'
		},

		initialize: function( options ) {
			this.options = options || {};
			this.giphy = new window.Giphy( BP_Nouveau.media.gif_api_key );

			this.gifDataItems = new bp.Collections.GifDatas();
			this.listenTo( this.gifDataItems, 'add', this.addOne );
			this.listenTo( this.gifDataItems, 'reset', this.addAll );

			document.addEventListener( 'scroll', _.bind( this.loadMore, this ), true );

		},

		render: function() {
			this.$el.html( this.template( this.model.toJSON() ) );
			this.$gifResultItem = this.$el.find( '.gif-search-results-list' );
			this.loadTrending();
			return this;
		},

		search: function( e ) {
			var self = this;

			if ( this.Timeout != null ) {
				clearTimeout( this.Timeout );
			}

			this.Timeout = setTimeout( function() {
				this.Timeout = null;
				self.searchGif( e.target.value );
			}, 1000 );
		},

		searchGif: function( q ) {
			var self = this;
			self.q = q;
			self.offset = 0;

			self.clearRequests();
			self.el.classList.add('loading');

			var request = self.giphy.search( {
					q: q,
					offset: self.offset,
					fmt: 'json',
					limit: this.limit
				},
				function( response ) {
					self.gifDataItems.reset( response.data );
					self.total_count = response.pagination.total_count;
					self.el.classList.remove('loading');
				}
			);

			self.requests.push( request );
			self.offset = self.offset + self.limit;
		},

		select: function( e ) {
			e.preventDefault();
			this.$el.parent().removeClass( 'open' );
			var model = this.gifDataItems.findWhere({id: e.currentTarget.dataset.id});
			this.model.set( 'gif_data', model.attributes );
		},

		// Add a single GifDataItem to the list by creating a view for it, and
		// appending its element to the `<ul>`.
		addOne: function( data ) {
			var view = new bp.Views.MessagesGifDataItem( { model: data } );
			this.$gifResultItem.append( view.render().el );
		},

		// Add all items in the **GifDataItem** collection at once.
		addAll: function() {
			this.$gifResultItem.html( '' );
			this.gifDataItems.each( this.addOne, this );
		},

		loadTrending: function() {
			var self = this;
			self.offset = 0;
			self.q = null;

			self.clearRequests();
			self.el.classList.add('loading');

			var request = self.giphy.trending( {
				offset: self.offset,
				fmt: 'json',
				limit: this.limit
			}, function( response ) {
				self.gifDataItems.reset( response.data );
				self.total_count = response.pagination.total_count;
				self.el.classList.remove('loading');
			});

			self.requests.push( request );
			self.offset = self.offset + self.limit;
		},

		loadMore: function( event ) {
			if ( event.target.id === 'gif-search-results' ) { // or any other filtering condition
				var el = event.target;
				if ( el.scrollTop + el.offsetHeight >= el.scrollHeight &&  ! el.classList.contains('loading') ) {
					if ( this.total_count > 0 && this.offset <= this.total_count ) {
						var self = this,
							params = {
								offset: self.offset,
								fmt: 'json',
								limit: self.limit
							};

						self.el.classList.add('loading');
						var request = null;
						if ( _.isNull( self.q ) ) {
							request = self.giphy.trending( params, _.bind( self.loadMoreResponse, self ) );
						} else {
							request = self.giphy.search( _.extend( { q: self.q }, params ), _.bind( self.loadMoreResponse, self ) );
						}

						self.requests.push( request );
						this.offset = this.offset + this.limit;
					}
				}
			}
		},

		clearRequests: function() {
			this.gifDataItems.reset();

			for ( var i = 0; i < this.requests.length; i++ ) {
				this.requests[i].abort();
			}

			this.requests = [];
		},

		loadMoreResponse: function( response ) {
			this.el.classList.remove('loading');
			this.gifDataItems.add( response.data );
		}
	} );

	// Gif search dropdown single item
	bp.Views.MessagesGifDataItem = bp.Nouveau.Messages.View.extend( {
		tagName: 'li',
		template: bp.template( 'messages-gif-result-item' ),
		initialize: function() {
			this.listenTo( this.model, 'change', this.render );
			this.listenTo( this.model, 'destroy', this.remove );
		},

		render: function() {
			var bgNo = Math.floor( Math.random() * (6 - 1 + 1) ) + 1,
				images = this.model.get('images');

			this.$el.html( this.template( this.model.toJSON() ) );
			this.el.classList.add('bg' + bgNo);
			this.el.style.height = images.fixed_width.height + 'px';

			return this;
		}

	} );

	bp.Views.MessagesToolbar = bp.Nouveau.Messages.View.extend( {
		tagName: 'div',
		id: 'whats-new-messages-toolbar',
		template: bp.template( 'whats-new-messages-toolbar' ),
		events: {
			'click #messages-media-button': 'toggleMediaSelector',
			'click #messages-gif-button': 'toggleGifSelector'
		},

		initialize: function() {
			document.addEventListener( 'keydown', _.bind( this.closePickersOnEsc, this ) );
			$( document ).on( 'click', _.bind( this.closePickersOnClick, this ) );
		},

		render: function() {
			this.$el.html(this.template(this.model.toJSON()));
			this.$self = this.$el.find('#messages-gif-button');
			this.$gifPickerEl = this.$el.find('.gif-media-search-dropdown');
			return this;
		},

		toggleMediaSelector: function( e ) {
			e.preventDefault();
			this.closeGifSelector();
			var event = new Event('messages_media_toggle');
			document.dispatchEvent(event);
			$(e.currentTarget).toggleClass('active');
		},

		closeMediaSelector: function() {
			var event = new Event('messages_media_close');
			document.dispatchEvent(event);
			$('#messages-media-button').removeClass('active');
		},

		toggleGifSelector: function( e ) {
			e.preventDefault();
			this.closeMediaSelector();
			if ( this.$gifPickerEl.is(':empty') ) {
				var gifMediaSearchDropdownView = new bp.Views.MessagesGifMediaSearchDropdown({model: this.model});
				this.$gifPickerEl.html( gifMediaSearchDropdownView.render().el );
			}
			this.$self.toggleClass('open');
			this.$gifPickerEl.toggleClass('open');
			$(e.currentTarget).toggleClass('active');
		},

		closeGifSelector: function() {
			var event = new Event('messages_gif_close');
			document.dispatchEvent(event);
			$('#messages-gif-button').removeClass('active');
		},

		closePickersOnEsc: function( event ) {
			if ( event.key === 'Escape' || event.keyCode === 27 ) {
				if (!_.isUndefined(BP_Nouveau.media) && !_.isUndefined(BP_Nouveau.media.gif_api_key)) {
					this.$self.removeClass('open');
					this.$gifPickerEl.removeClass('open');
				}
			}
		},

		closePickersOnClick: function( event ) {
			var $targetEl = $(event.target);

			if (!_.isUndefined(BP_Nouveau.media) && !_.isUndefined(BP_Nouveau.media.gif_api_key) &&
				!$targetEl.closest('.post-gif').length) {
				this.$self.removeClass('open');
				this.$gifPickerEl.removeClass('open');
			}

		}

	} );

	bp.Views.MessagesAttachments = bp.Nouveau.Messages.View.extend( {
		tagName: 'div',
		id: 'whats-new-messages-attachments',
		messagesMedia: null,
		messagesAttachedGifPreview: null,
		initialize: function() {
			if ( !_.isUndefined( window.Dropzone ) && !_.isUndefined( BP_Nouveau.media ) && BP_Nouveau.media.messages_media ) {
				this.messagesMedia = new bp.Views.MessagesMedia({model: this.model});
				this.views.add(this.messagesMedia);
			}

			this.messagesAttachedGifPreview = new bp.Views.MessagesAttachedGifPreview( { model: this.model } );
			this.views.add( this.messagesAttachedGifPreview );
		},
		onClose: function() {
			if( ! _.isNull( this.messagesMedia ) ) {
				this.messagesMedia.destroy();
			}
			if( ! _.isNull( this.messagesAttachedGifPreview ) ) {
				this.messagesAttachedGifPreview.destroy();
			}
		}
	});

	bp.Views.MessagesNoThreads = bp.Nouveau.Messages.View.extend( {
		tagName: 'div',
		template  : bp.template( 'bp-messages-no-threads' ),
		events: {
			'click #bp-new-message'  : 'openComposeMessage'
		},
		initialize: function() {
			this.$el.html(this.template());
			return this;
		},
		openComposeMessage: function(e) {
			e.preventDefault();

			bp.Nouveau.Messages.router.navigate( 'compose/', { trigger: true } );
		}
	});

	bp.Views.MessageFormSubmit = bp.Nouveau.Messages.View.extend( {
		tagName   : 'div',
		className   : 'submit',
		id        : 'message-new-submit',
		template  : bp.template( 'bp-messages-form-submit' )
	} );

	bp.Views.MessageFormSubmitWrapper = bp.Nouveau.Messages.View.extend( {
		tagName: 'div',
		id: 'message-form-submit-wrapper',
		initialize: function() {
			this.views.add( new bp.Views.MessagesToolbar( { model: this.model } ) );
			this.views.add( new bp.Views.MessageFormSubmit( { model: this.model } ) );
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
			'click #bp-messages-reset' : 'resetForm',
			'click .bp-close-compose-form' : 'closeComposeForm'
		},

		initialize: function() {
			// Clone the model to set the resetted one
			this.resetModel = this.model.clone();

			// Add the editor view
			this.views.add( '#bp-message-content', new bp.Views.messageEditor() );
			this.messagesAttachments = new bp.Views.MessagesAttachments( { model: this.model } );
			this.views.add( '#bp-message-content', this.messagesAttachments );

			this.views.add( '#bp-message-content', new bp.Views.MessageFormSubmitWrapper( { model: this.model } ) );

			//this.model.on( 'change', this.resetFields, this );

			// Activate bp_mentions
			this.on( 'ready', this.addSelect2, this );
		},

		closeComposeForm: function( event ) {
			event.preventDefault();
                        var form = bp.Nouveau.Messages.views.get( 'compose' );
                        form.get( 'view' ).remove();
                        bp.Nouveau.Messages.views.remove( { id: 'compose', view: form } );
                        bp.Nouveau.Messages.router.navigate( '/' );
                        $('.bp-messages-container').removeClass('bp-compose-message');
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
			var ArrayData = [];
			if ( $input.prop('tagName') != 'SELECT' ) {
				this.addMentions();
				return;
			}

			$input.select2({
				placeholder: $input.attr('placeholder'),
				minimumInputLength: 1,
                                dropdownCssClass: 'bb-select-dropdown',
                                containerCssClass: 'bb-select-container',
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
					cache: true,
					processResults: function( data ) {

						// Removed the element from results if already selected.
						if ( false === jQuery.isEmptyObject( ArrayData ) ) {
							$.each( ArrayData, function( index, value ) {
								for(var i=0;i<data.data.results.length;i++){
									if(data.data.results[i].id === value){
										data.data.results.splice(i,1);
									}
								}
							});
						}

						return {
							results: data && data.success? data.data.results : []
						};
					}
				}
			});

			// Add element into the Arrdata array.
			$input.on('select2:select', function(e) {
				var data = e.params.data;
				ArrayData.push(data.id);
			});

			// Remove element into the Arrdata array.
			$input.on('select2:unselect', function(e) {
				var data = e.params.data;
				ArrayData = jQuery.grep(ArrayData, function(value) {
					return value != data.id;
				});
			});

		},

		resetFields: function( model ) {
			// Clean inputs
			_.each( model.previousAttributes(), function( value, input ) {
				if ( 'message_content' === input ) {
					// tinyMce
					if ( typeof tinyMCE !== 'undefined' && undefined !== tinyMCE.activeEditor && null !== tinyMCE.activeEditor ) {
						tinyMCE.activeEditor.setContent( '' );
					} else if ( undefined !== bp.Nouveau.Messages.mediumEditor && false !== bp.Nouveau.Messages.mediumEditor ) {
						bp.Nouveau.Messages.mediumEditor.setContent( '' );
					}

				// All except meta or empty value
				} else if ( 'meta' !== input && false !== value ) {
					$( 'input[name="' + input + '"]' ).val( '' );
				}
			} );

			// if (this.messagesAttachments !== false){
			// 	this.messagesAttachments.onClose();
			// }

			// Listen to this to eventually reset your custom inputs.
			$( this.el ).trigger( 'message:reset', _.pick( model.previousAttributes(), 'meta' ) );
		},

		sendMessage: function( event ) {
			var meta = {}, errors = [], self = this;
			event.preventDefault();

			bp.Nouveau.Messages.removeFeedback();

			// reset receipients field before send
			this.model.set( 'send_to', [], { silent: true } );

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
						} else if ( 'message_content' === pair.name && undefined !== bp.Nouveau.Messages.mediumEditor ) {
							pair.value = bp.Nouveau.Messages.mediumEditor.getContent();
						}

						if ( ! pair.value ) {
							errors.push( pair.name );
						} else {
							this.model.set( pair.name, pair.value, { silent: true } );
						}
					}
				}

			}, this );

			// quill editor support
			if ( bp.Nouveau.Messages.mediumEditor !== false && typeof bp.Nouveau.Messages.mediumEditor !== 'undefined' ) {
				$('#message_content').find('img.emojioneemoji').replaceWith(function () {
					return this.dataset.emojiChar;
				});
				this.model.set( 'message_content', bp.Nouveau.Messages.mediumEditor.getContent(), { silent: true } );
			}

			// check recipients empty
			if ( ! this.model.get( 'send_to' ).length ) {
				errors.push( 'send_to' );
			}

			//check message content empty
			if ( this.model.get( 'message_content' ) === '' && ( typeof this.model.get('media') !== 'undefined' && ! this.model.get('media').length ) && ( typeof this.model.get('gif_data') !== 'undefined' && ! Object.keys(this.model.get('gif_data')).length ) ) {
				errors.push( 'message_content' );
			}

			if ( errors.length ) {
				var feedback = '';
				_.each( errors, function( e ) {
					feedback += BP_Nouveau.messages.errors[ e ] + '<br/>';
				} );

				bp.Nouveau.Messages.displayFeedback( feedback, 'error' );
				return;
			}

			if ( this.model.get( 'message_content' ) === '' && ( ( typeof this.model.get('media') !== 'undefined' && this.model.get('media').length ) || ( typeof this.model.get('gif_data') !== 'undefined' && Object.keys(this.model.get('gif_data')).length ) ) ) {
				this.model.set( 'message_content', '&nbsp;', { silent: true } );
			}

			// Set meta
			this.model.set( 'meta', meta, { silent: true } );

			$('#bp-messages-send').prop('disabled',true).addClass('loading');

			// Send the message.
			this.model.sendMessage().done( function( response ) {
				// Reset the model
				self.model.set( self.resetModel );

				//bp.Nouveau.Messages.displayFeedback( response.feedback, response.type );

				// Remove tinyMCE
				bp.Nouveau.Messages.removeTinyMCE();

				//media modal images remove or save option if saved
				var medias = self.model.get('media');
				if ( typeof medias !== 'undefined' && medias.length ) {
					for( var k = 0; k < medias.length; k++ ) {
						medias[k].saved = true;
					}
					self.model.set('media',medias);
				}

				// clear message attachments and toolbar
				if (self.messagesAttachments !== false){
					self.messagesAttachments.onClose();
				}

				// Remove the form view
				var form = bp.Nouveau.Messages.views.get( 'compose' );
				form.get( 'view' ).remove();
				bp.Nouveau.Messages.views.remove( { id: 'compose', view: form } );

				bp.Nouveau.Messages.router.navigate( 'view/' + response.thread.id + '/', { trigger: true } );

				var threads = bp.Nouveau.Messages.threads.parse( { threads : [ response.thread ] } );
				bp.Nouveau.Messages.threads.unshift( _.first( threads ) );

			} ).fail( function( response ) {
				if ( response.feedback ) {
					bp.Nouveau.Messages.displayFeedback( response.feedback, response.type );
				}
				$('#bp-messages-send').prop('disabled',false).removeClass('loading');
			} );
		},

		resetForm: function( event ) {
			event.preventDefault();

			this.model.set( this.resetModel );
		}
	} );

	bp.Views.userThreads = bp.Nouveau.Messages.View.extend( {
		tagName   : 'div',
		className : 'bp-messages-user-threads',
		loadingFeedback : false,

		events: {
			'click .bp-message-link' : 'changePreview',
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
			if ( BP_Nouveau.messages.hasThreads ) {
				this.requestThreads();
			} else {
				this.threadsFetchError( {}, { feedback : BP_Nouveau.messages.errors.no_messages, type: 'info' } );
			}

			this.collection.on( 'reset', this.cleanContent, this );
			this.collection.on( 'add', this.addThread, this );
		},

		requestThreads: function() {
			this.collection.reset();

			this.loadingFeedback = new bp.Views.MessagesLoading();
			this.views.add(this.loadingFeedback);

			this.collection.fetch( {
				data    : _.pick( this.options, 'box' ),
				success : _.bind( this.threadsFetched, this ),
				error   : _.bind( this.threadsFetchError, this )
			} );
		},

		threadsFetched: function() {
			this.loadingFeedback.remove();

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

				bp.Nouveau.Messages.displayFilters( this.collection );
			}
		},

		threadsFetchError: function( collection, response ) {
			if ( ! _.isUndefined( this.options.search_terms ) && this.options.search_terms !== '' ) {
				this.loadingFeedback = new bp.Views.Feedback({
					value: response.feedback,
					type: response.type
				});
				this.views.add(this.loadingFeedback);
			}

			if ( ! collection.length ) {
				$('.bp-messages-threads-list').addClass('bp-no-messages');
				this.views.add( new bp.Views.MessagesNoThreads() );
			}
		},

		scrolled: function( event ) {
			var target = $( event.currentTarget );

			if ( ( target[0].scrollHeight - ( target.scrollTop() ) ) == target.innerHeight() &&
				this.collection.length &&
				this.collection.options.page < this.collection.options.total_page &&
				! target.find('.bp-user-messages-loading').length
			) {
				this.collection.options.page = this.collection.options.page + 1;

				this.loadingFeedback = new bp.Views.MessagesLoading();
				this.views.add(this.loadingFeedback);

				_.extend( this.collection.options, _.pick( bp.Nouveau.Messages.filters.attributes, ['box', 'search_terms'] ) );

				this.collection.fetch( {
					remove  : false,
					data    : _.pick( this.collection.options, ['box', 'search_terms', 'page'] ),
					success : _.bind( this.threadsFetched, this ),
					error   : _.bind( this.threadsFetchError, this )
				} );
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

			this.views.add( '#message-threads', new bp.Views.userThread( { model: thread } ), { at: this.collection.indexOf(thread) } );
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

			this.setActiveThread( target.data( 'thread-id' ) );
			var selected = this.collection.findWhere( { active: true } );

			// selected.updateReadState();
			if ( selected.get('unread') ) {
				selected.updateReadState().done( function() {
					selected.set( 'unread', false );

					bp.Nouveau.Messages.router.navigate(
						'view/' + target.data( 'thread-id' ) + '/',
						{ trigger: true }
					);
				} );
			} else {
				bp.Nouveau.Messages.router.navigate(
					'view/' + target.data( 'thread-id' ) + '/',
					{ trigger: true }
				);
			}

			$.each( $( '.thread-content' ), function() {
				$(this).removeClass('current');
			} );

			target.addClass('current');
			target.parents('.bp-messages-container').removeClass('bp-compose-message').addClass('bp-view-message');
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
			//this.options.threads.on( 'sync', this.addPagination, this );
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
			$(this.$el).parent().addClass('loading');

			//bp.Nouveau.Messages.displayFeedback( BP_Nouveau.messages.loading, 'loading' );

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
			'click .actions button' : 'doAction',
			'click .bp-back-to-thread-list' : 'navigateToList'
		},

                navigateToList: function( event ) {
                        event.preventDefault();
                        bp.Nouveau.Messages.router.navigate( '/' );
                        $('.bp-messages-container').removeClass('bp-view-message');
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

				// Remove previous feedback.
				bp.Nouveau.Messages.removeFeedback();

				// Remove all views
				if ( 'delete' === action ) {
					//bp.Nouveau.Messages.clearViews();
					// Navigate back to current box
					bp.Nouveau.Messages.threads.remove( bp.Nouveau.Messages.threads.get( self.model.get( 'id' ) ) );
					if ( bp.Nouveau.Messages.threads.length > 1 ) {
						bp.Nouveau.Messages.router.navigate( 'view/' + bp.Nouveau.Messages.threads.at(0).id + '/', { trigger: true } );
					} else {
						BP_Nouveau.messages.hasThreads = false;
						bp.Nouveau.Messages.router.navigate( 'compose/', { trigger: true } );
					}
				} else if ( response.messages ) {
					self.model.set( _.first( response.messages ) );

					// Display the feedback
					bp.Nouveau.Messages.displayFeedback( response.feedback, response.type );
				}

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

	bp.Views.MessageReplyFormSubmit = bp.Nouveau.Messages.View.extend( {
		tagName   : 'div',
		className   : 'submit',
		id        : 'message-reply-new-submit',
		template  : bp.template( 'bp-messages-reply-form-submit' )
	} );

	bp.Views.MessageReplyFormSubmitWrapper = bp.Nouveau.Messages.View.extend( {
		tagName: 'div',
		id: 'message-reply-form-submit-wrapper',
		initialize: function() {
			this.views.add( new bp.Views.MessagesToolbar( { model: this.model } ) );
			this.views.add( new bp.Views.MessageReplyFormSubmit( { model: this.model } ) );
		}
	});

	bp.Views.userMessages = bp.Nouveau.Messages.View.extend( {
		tagName  : 'div',
		className  : 'bp-messages-content-wrapper',
		template : bp.template( 'bp-messages-single' ),
		messageAttachments : false,
		firstFetch : true,
		firstLi : true,
		loadingFeedback: false,

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

			this.views.add( '#bp-message-content', new bp.Views.MessageReplyFormSubmitWrapper( { model: this.model } ) );

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

			this.collection.reset();

			this.loadingFeedback = new bp.Views.MessagesLoading();
			this.views.add('#bp-message-content',this.loadingFeedback);

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

			this.loadingFeedback.remove();

			if ( response.feedback_error && response.feedback_error.feedback && response.feedback_error.type ) {
				bp.Nouveau.Messages.displayFeedback( response.feedback_error.feedback, response.feedback_error.type );
				//hide reply form
				this.$('#send-reply').hide();
			}

			if ( this.firstFetch ) {
				$('#bp-message-thread-list').animate({ scrollTop: $('#bp-message-thread-list').prop('scrollHeight')}, 100);
				this.firstFetch = false;
			} else {
				$('#bp-message-thread-list').animate({ scrollTop: this.firstLi.position().top - this.firstLi.outerHeight()}, 0);
			}

			$('#bp-message-load-more').removeClass('loading');

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

			$('#bp-message-load-more').removeClass('loading');

			if ( ! response.messages && ! _.isUndefined( this.views.get( '#bp-message-load-more' ) ) ) {
				loadMore = this.views.get( '#bp-message-load-more' )[0];
				loadMore.views.view.remove();
			} else {
				loadMore = this.views.get( '#bp-message-load-more' )[0];
				loadMore.views.view.$el.find('button').removeClass('loading');
			}

			if ( response.feedback && response.type ) {
				this.loadingFeedback = new bp.Views.Feedback({
					value: response.feedback,
					type: response.type
				});
				this.views.add('#bp-message-content',this.loadingFeedback);
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
			var errors = [];
			event.preventDefault();

			if ( true === this.model.get( 'sending' ) ) {
				return;
			}

			var content = '';
			if ( typeof tinyMCE !== 'undefined' ) {
				content = tinyMCE.activeEditor.getContent();
				jQuery(tinyMCE.activeEditor.formElement).addClass('loading');
			} else if ( typeof bp.Nouveau.Messages.mediumEditor !== 'undefined' ) {
				$('#message_content').find('img.emojioneemoji').replaceWith(function () {
					return this.dataset.emojiChar;
				});
				content = bp.Nouveau.Messages.mediumEditor.getContent();
				jQuery('#message_content').addClass('loading');
			}

			//check message content empty
			if ( content === '' && ( ( typeof this.model.get('media') !== 'undefined' && ! this.model.get('media').length ) && ( typeof this.model.get('gif_data') !== 'undefined' && ! Object.keys(this.model.get('gif_data')).length ) ) ) {
				errors.push( 'message_content' );
			}

			if ( errors.length ) {
				var feedback = '';
				_.each( errors, function( e ) {
					feedback += BP_Nouveau.messages.errors[ e ] + '<br/>';
				} );

				bp.Nouveau.Messages.displayFeedback( feedback, 'error' );
				return;
			}

			if ( content === '' && ( ( typeof this.model.get('media') !== 'undefined' && this.model.get('media').length ) || ( typeof this.model.get('gif_data') !== 'undefined' && Object.keys(this.model.get('gif_data')).length ) ) ) {
				content = '&nbsp;';
			}

			this.model.set ( {
				thread_id : this.options.thread.get( 'id' ),
				content   : content,
				sending   : true
			} );

			$('#send_reply_button').prop('disabled',true).addClass('loading');

			this.collection.sync( 'create', this.model.attributes, {
				success : _.bind( this.replySent, this ),
				error   : _.bind( this.replyError, this )
			} );
		},

		replySent: function( response ) {
			var reply = this.collection.parse( response );

			// Reset the form
			if ( typeof tinyMCE !== 'undefined' ) {
				tinyMCE.activeEditor.setContent( '' );
				jQuery(tinyMCE.activeEditor.formElement).removeClass('loading');
			} else if ( typeof bp.Nouveau.Messages.mediumEditor !== 'undefined' ) {
				bp.Nouveau.Messages.mediumEditor.setContent('');
				jQuery('#message_content').removeClass('loading');
			}

			this.model.set( 'sending', false );

			//media modal images remove or save option if saved
			var medias = this.model.get('media');
			if ( typeof medias !== 'undefined' && medias.length ) {
				for( var k = 0; k < medias.length; k++ ) {
					medias[k].saved = true;
				}
				this.model.set('media',medias);
			}

			if (this.messageAttachments.onClose){
				this.messageAttachments.onClose();
			}

			this.collection.add( _.first( reply ) );

			bp.Nouveau.Messages.removeFeedback();
			$('#send_reply_button').prop('disabled',false).removeClass('loading');

			$('#bp-message-thread-list').animate({ scrollTop: $('#bp-message-thread-list').prop('scrollHeight')}, 0);
		},

		replyError: function( response ) {
			this.model.set( 'sending', false );
			if ( response.feedback && response.type ) {
				bp.Nouveau.Messages.displayFeedback( response.feedback, response.type );
			}
			$('#send_reply_button').prop('disabled',false).removeClass('loading');
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

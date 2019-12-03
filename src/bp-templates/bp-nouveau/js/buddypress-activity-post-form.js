/* global bp, BP_Nouveau, _, Backbone */
/* @version 3.1.0 */
window.wp = window.wp || {};
window.bp = window.bp || {};

( function( exports, $ ) {
	bp.Nouveau = bp.Nouveau || {};

	// Bail if not set
	if ( typeof bp.Nouveau.Activity === 'undefined' || typeof BP_Nouveau === 'undefined' ) {
		return;
	}

	_.extend( bp, _.pick( wp, 'Backbone', 'ajax', 'template' ) );

	bp.Models      = bp.Models || {};
	bp.Collections = bp.Collections || {};
	bp.Views       = bp.Views || {};

	/**
	 * [Activity description]
	 * @type {Object}
	 */
	bp.Nouveau.Activity.postForm = {
		start: function() {
			this.views           = new Backbone.Collection();
			this.ActivityObjects = new bp.Collections.ActivityObjects();
			this.buttons         = new Backbone.Collection();

			if ( ! _.isUndefined( window.Dropzone ) && ! _.isUndefined( BP_Nouveau.media ) ) {
				this.dropzoneView();
			}

			this.postFormView();
		},

		postFormView: function() {
			// Do not carry on if the main element is not available.
			if ( ! $( '#bp-nouveau-activity-form' ).length ) {
				return;
			}

			// Create the BuddyPress Uploader
			var postForm = new bp.Views.PostForm();

			// Add it to views
			this.views.add( { id: 'post_form', view: postForm } );

			// Display it
			postForm.inject( '#bp-nouveau-activity-form' );
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
		}
	};

	bp.Backbone.View.prototype.close = function(){
		this.remove();
		this.unbind();
		if (this.onClose) {
			this.onClose();
		}
	};

	if ( typeof bp.View === 'undefined' ) {
		// Extend wp.Backbone.View with .prepare() and .inject()
		bp.View = bp.Backbone.View.extend(
			{
				inject: function( selector ) {
					this.render();
					$( selector ).html( this.el );
					this.views.ready();
				},

				prepare: function() {
					if ( ! _.isUndefined( this.model ) && _.isFunction( this.model.toJSON ) ) {
						return this.model.toJSON();
					} else {
						return {};
					}
				}
			}
		);
	}

	/** Models ****************************************************************/

	// The Activity to post
	bp.Models.Activity = Backbone.Model.extend(
		{
			defaults: {
				user_id:   0,
				item_id:   0,
				object:   '',
				content:  '',
				posting: false,
				link_success: false,
				link_error: false,
				link_error_msg: '',
				link_scrapping: false,
				link_images: [],
				link_image_index: 0,
				link_title: '',
				link_description: '',
				link_url: '',
				gif_data: {}
			}
		}
	);

	bp.Models.GifResults = Backbone.Model.extend(
		{
			defaults: {
				q: '',
				data: []
			}
		}
	);

	bp.Models.GifData = Backbone.Model.extend( {} );

	// Git results collection returned from giphy api
	bp.Collections.GifDatas = Backbone.Collection.extend(
		{
				// Reference to this collection's model.
			model: bp.Models.GifData
		}
	);

	// Object, the activity is attached to (group or blog or any other)
	bp.Models.ActivityObject = Backbone.Model.extend(
		{
			defaults: {
				id          : 0,
				name        : '',
				avatar_url  : '',
				object_type : 'group'
			}
		}
	);

	/** Collections ***********************************************************/

	// Objects, the activity can be attached to (groups or blogs or any others)
	bp.Collections.ActivityObjects = Backbone.Collection.extend(
		{
			model: bp.Models.ActivityObject,

			sync: function( method, model, options ) {

				if ( 'read' === method ) {
					options         = options || {};
					options.context = this;
					options.data    = _.extend(
						options.data || {},
						{
							action: 'bp_nouveau_get_activity_objects'
							}
					);

						return bp.ajax.send( options );
				}
			},

			parse: function( resp ) {
				if ( ! _.isArray( resp ) ) {
					resp = [resp];
				}

				return resp;
			}

		}
	);

	/** Views *****************************************************************/

	// Feedback messages
	bp.Views.activityFeedback = bp.View.extend(
		{
			tagName  : 'div',
			id       : 'message',
			template : bp.template( 'activity-post-form-feedback' ),

			initialize: function() {
				this.model = new Backbone.Model();

				if ( this.options.value ) {
					this.model.set( 'message', this.options.value, { silent: true } );
				}

				this.type = 'info';

				if ( ! _.isUndefined( this.options.type ) && 'info' !== this.options.type ) {
					this.type = this.options.type;
				}

				this.el.className = 'bp-messages bp-feedback ' + this.type;
			}
		}
	);

	// Activity Media
	bp.Views.ActivityMedia = bp.View.extend(
		{
			tagName: 'div',
			className: 'activity-media-container',
			template: bp.template( 'activity-media' ),
			media : [],

			initialize: function () {

				this.model.set( 'media', this.media );

				document.addEventListener( 'activity_media_toggle', this.toggle_media_uploader.bind( this ) );
				document.addEventListener( 'activity_media_close', this.destroy.bind( this ) );
			},

			toggle_media_uploader: function() {
				var self = this;
				if ( self.$el.find( '#activity-post-media-uploader' ).hasClass( 'open' ) ) {
					self.destroy();
				} else {
					self.open_media_uploader();
				}
			},

			destroy: function() {
				var self = this;
				if ( ! _.isNull( bp.Nouveau.Activity.postForm.dropzone ) ) {
					bp.Nouveau.Activity.postForm.dropzone.destroy();
					self.$el.find( '#activity-post-media-uploader' ).html( '' );
				}
				self.media = [];
				self.$el.find( '#activity-post-media-uploader' ).removeClass( 'open' ).addClass( 'closed' );

				document.removeEventListener( 'activity_media_toggle', this.toggle_media_uploader.bind( this ) );
				document.removeEventListener( 'activity_media_close', this.destroy.bind( this ) );

				$( '#whats-new-attachments' ).addClass( 'empty' );
			},

			open_media_uploader: function() {
				var self = this;

				if ( self.$el.find( '#activity-post-media-uploader' ).hasClass( 'open' ) ) {
					return false;
				}
				self.destroy();

				bp.Nouveau.Activity.postForm.dropzone = new window.Dropzone( '#activity-post-media-uploader', bp.Nouveau.Activity.postForm.dropzone_options );

				bp.Nouveau.Activity.postForm.dropzone.on(
					'sending',
					function(file, xhr, formData) {
						formData.append( 'action', 'media_upload' );
						formData.append( '_wpnonce', BP_Nouveau.nonces.media );
					}
				);

				bp.Nouveau.Activity.postForm.dropzone.on(
					'success',
					function(file, response) {
						if ( response.data.id ) {
							file.id                  = response.data.id;
							response.data.uuid       = file.upload.uuid;
							response.data.group_id   = typeof BP_Nouveau.media !== 'undefined' && typeof BP_Nouveau.media.group_id !== 'undefined' ? BP_Nouveau.media.group_id : false;
							response.data.saved      = false;
							response.data.menu_order = $( file.previewElement ).closest( '.dropzone' ).find( file.previewElement ).index() - 1;
							self.media.push( response.data );
							self.model.set( 'media', self.media );
						}
					}
				);

				bp.Nouveau.Activity.postForm.dropzone.on(
					'error',
					function(file,response) {
						if ( file.accepted ) {
							if ( typeof response !== 'undefined' && typeof response.data !== 'undefined' && typeof response.data.feedback !== 'undefined' ) {
								$( file.previewElement ).find( '.dz-error-message span' ).text( response.data.feedback );
							}
						} else {
							bp.Nouveau.Activity.postForm.dropzone.removeFile( file );
						}
					}
				);

				bp.Nouveau.Activity.postForm.dropzone.on(
					'removedfile',
					function(file) {
						if ( self.media.length ) {
							for ( var i in self.media ) {
								if ( file.id === self.media[i].id ) {
									if ( typeof self.media[i].saved !== 'undefined' && ! self.media[i].saved ) {
										bp.Nouveau.Media.removeAttachment( file.id );
									}
									self.media.splice( i, 1 );
									self.model.set( 'media', self.media );
								}
							}
						}
					}
				);

				self.$el.find( '#activity-post-media-uploader' ).addClass( 'open' ).removeClass( 'closed' );
				$( '#whats-new-attachments' ).removeClass( 'empty' );
			}

		}
	);

	// Activity link preview
	bp.Views.ActivityLinkPreview = bp.View.extend(
		{
			tagName: 'div',
			className: 'activity-url-scrapper-container',
			template: bp.template( 'activity-link-preview' ),
			events: {
				'click #activity-link-preview-button': 'toggleURLInput',
				'click #activity-url-prevPicButton': 'prev',
				'click #activity-url-nextPicButton': 'next',
				'click #activity-link-preview-close-image': 'close',
				'click #activity-close-link-suggestion': 'destroy'
			},

			initialize: function() {
				this.model.set( 'link_scrapping', false );
				this.listenTo( this.model, 'change', this.render );
				document.addEventListener( 'activity_link_preview_open', this.open.bind( this ) );
				document.addEventListener( 'activity_link_preview_close', this.destroy.bind( this ) );
			},

			render: function() {
				this.$el.html( this.template( this.model.toJSON() ) );
				return this;
			},

			prev: function() {
				var imageIndex = this.model.get( 'link_image_index' );
				if ( imageIndex > 0 ) {
					this.model.set( 'link_image_index', imageIndex - 1 );
				}
			},

			next: function() {
				var imageIndex = this.model.get( 'link_image_index' );
				var images     = this.model.get( 'link_images' );
				if ( imageIndex < images.length - 1 ) {
					this.model.link_image_index++;
					this.model.set( 'link_image_index', imageIndex + 1 );
				}
			},

			open: function(e) {
				e.preventDefault();
				this.model.set( 'link_scrapping', true );
				this.$el.addClass( 'open' );
			},

			close: function(e) {
				e.preventDefault();
				this.model.set(
					{
						link_images: [],
						link_image_index: 0
						}
				);
			},

			destroy: function( e ) {
				if ( ! _.isUndefined( e ) ) {
					e.preventDefault();
				}
				// Set default values
				this.model.set(
					{
						link_success: false,
						link_error: false,
						link_error_msg: '',
						link_scrapping: false,
						link_images: [],
						link_image_index: 0,
						link_title: '',
						link_description: '',
						link_url: ''
						}
				);
				document.removeEventListener( 'activity_link_preview_open', this.open.bind( this ) );
				document.removeEventListener( 'activity_link_preview_close', this.destroy.bind( this ) );

				$( '#whats-new-attachments' ).addClass( 'empty' );
			}
		}
	);

	// Activity gif selector
	bp.Views.ActivityAttachedGifPreview = bp.View.extend(
		{
			tagName: 'div',
			className: 'activity-attached-gif-container',
			template: bp.template( 'activity-attached-gif' ),
			events: {
				'click .gif-image-remove': 'destroy'
			},

			initialize: function() {
				this.listenTo( this.model, 'change', this.render );
				document.addEventListener( 'activity_gif_close', this.destroy.bind( this ) );
			},

			render: function() {
				this.$el.html( this.template( this.model.toJSON() ) );

				var gifData = this.model.get( 'gif_data' );
				if ( ! _.isEmpty( gifData ) ) {
					this.el.style.backgroundImage = 'url(' + gifData.images.fixed_width.url + ')';
					this.el.style.backgroundSize  = 'contain';
					this.el.style.height          = gifData.images.original.height + 'px';
					this.el.style.width           = gifData.images.original.width + 'px';
					$( '#whats-new-attachments' ).removeClass( 'empty' );
				}

				return this;
			},

			destroy: function() {
				this.model.set( 'gif_data', {} );
				this.el.style.backgroundImage = '';
				this.el.style.backgroundSize  = '';
				this.el.style.height          = '0px';
				this.el.style.width           = '0px';
				document.removeEventListener( 'activity_gif_close', this.destroy.bind( this ) );
				$( '#whats-new-attachments' ).addClass( 'empty' );
			}
		}
	);

	// Gif search dropdown
	bp.Views.GifMediaSearchDropdown = bp.View.extend(
		{
			tagName: 'div',
			className: 'activity-attached-gif-container',
			template: bp.template( 'gif-media-search-dropdown' ),
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
				this.giphy   = new window.Giphy( BP_Nouveau.media.gif_api_key );

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

				this.Timeout = setTimeout(
					function() {
							this.Timeout = null;
							self.searchGif( e.target.value );
					},
					1000
				);
			},

			searchGif: function( q ) {
				var self    = this;
				self.q      = q;
				self.offset = 0;

				self.clearRequests();
				self.el.classList.add( 'loading' );

				var request = self.giphy.search(
					{
						q: q,
						offset: self.offset,
						fmt: 'json',
						limit: this.limit
						},
					function( response ) {
						self.gifDataItems.reset( response.data );
						self.total_count = response.pagination.total_count;
						self.el.classList.remove( 'loading' );
					}
				);

				self.requests.push( request );
				self.offset = self.offset + self.limit;
			},

			select: function( e ) {
				e.preventDefault();
				this.$el.parent().removeClass( 'open' );
				var model = this.gifDataItems.findWhere( {id: e.currentTarget.dataset.id} );
				this.model.set( 'gif_data', model.attributes );
			},

				// Add a single GifDataItem to the list by creating a view for it, and
				// appending its element to the `<ul>`.
			addOne: function( data ) {
				var view = new bp.Views.GifDataItem( { model: data } );
				this.$gifResultItem.append( view.render().el );
			},

				// Add all items in the **GifDataItem** collection at once.
			addAll: function() {
				this.$gifResultItem.html( '' );
				this.gifDataItems.each( this.addOne, this );
			},

			loadTrending: function() {
				var self    = this;
				self.offset = 0;
				self.q      = null;

				self.clearRequests();
				self.el.classList.add( 'loading' );

				var request = self.giphy.trending(
					{
						offset: self.offset,
						fmt: 'json',
						limit: this.limit
						},
					function( response ) {
						self.gifDataItems.reset( response.data );
						self.total_count = response.pagination.total_count;
						self.el.classList.remove( 'loading' );
					}
				);

				self.requests.push( request );
				self.offset = self.offset + self.limit;
			},

			loadMore: function( event ) {
				if ( event.target.id === 'gif-search-results' ) { // or any other filtering condition
					var el = event.target;
					if ( el.scrollTop + el.offsetHeight >= el.scrollHeight && ! el.classList.contains( 'loading' ) ) {
						if ( this.total_count > 0 && this.offset <= this.total_count ) {
							var self = this,
							params   = {
								offset: self.offset,
								fmt: 'json',
								limit: self.limit
							};

							self.el.classList.add( 'loading' );
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
				this.el.classList.remove( 'loading' );
				this.gifDataItems.add( response.data );
			}
		}
	);

	// Gif search dropdown single item
	bp.Views.GifDataItem = bp.View.extend(
		{
			tagName: 'li',
			template: wp.template( 'gif-result-item' ),
			initialize: function() {
				this.listenTo( this.model, 'change', this.render );
				this.listenTo( this.model, 'destroy', this.remove );
			},

			render: function() {
				var bgNo   = Math.floor( Math.random() * (6 - 1 + 1) ) + 1,
					images = this.model.get( 'images' );

				this.$el.html( this.template( this.model.toJSON() ) );
				this.el.classList.add( 'bg' + bgNo );
				this.el.style.height = images.fixed_width.height + 'px';

				return this;
			}

		}
	);

	// Regular input
	bp.Views.ActivityInput = bp.View.extend(
		{
			tagName: 'input',

			attributes: {
				type: 'text'
			},

			initialize: function() {
				if ( ! _.isObject( this.options ) ) {
					return;
				}

				_.each(
					this.options,
					function( value, key ) {
						this.$el.prop( key, value );
					},
					this
				);

				this.listenTo( this.model, 'change:link_loading', this.onLinkScrapping );
			},

			onLinkScrapping: function() {
				this.$el.prop( 'disabled', false );
			}
		}
	);

	// The content of the activity
	bp.Views.WhatsNew = bp.View.extend(
		{
			tagName: 'div',
			className: 'bp-suggestions',
			id: 'whats-new',
			events: {
				'paste': 'handlePaste',
				'keyup': 'handleKeyUp'
			},
			attributes: {
				name: 'whats-new',
				cols: '50',
				rows: '4',
				placeholder: BP_Nouveau.activity.strings.whatsnewPlaceholder,
				'aria-label': BP_Nouveau.activity.strings.whatsnewLabel,
				contenteditable: true
			},

			initialize: function () {
				this.on('ready', this.adjustContent, this);
				this.options.activity.on('change:content', this.resetContent, this);
				this.linkTimeout = null;

				if (_.isUndefined(BP_Nouveau.activity.params.link_preview)) {
					this.$el.off('keyup');
				}
			},

			adjustContent: function () {

				// this.$el.toTextarea({
				// 	allowHTML: true,//allow HTML formatting with CTRL+b, CTRL+i, etc.
				// 	allowImg: true,//allow drag and drop images
				// 	pastePlainText: false,//paste text without styling as source
				// 	placeholder: false
				// });

				// First adjust layout
				this.$el.css(
					{
						resize: 'none',
						height: '50px'
					}
				);

				// Check for mention
				var mention = bp.Nouveau.getLinkParams(null, 'r') || null;

				if (!_.isNull(mention)) {
					this.$el.text('@' + _.escape(mention) + ' ');
					this.$el.focus();
				}
			},

			resetContent: function (activity) {
				if (_.isUndefined(activity)) {
					return;
				}

				this.$el.html(activity.get('content'));
			},

			handlePaste: function (event) {
				// Get user's pasted data
				var clipboardData = event.clipboardData || window.clipboardData || event.originalEvent.clipboardData,
					data = clipboardData.getData('text/plain');

				// Insert the filtered content
				document.execCommand('insertHTML', false, data);

				// Prevent the standard paste behavior
				event.preventDefault();
			},

			handleKeyUp: function (event) {
				var self = this;

				if (this.linkTimeout != null) {
					clearTimeout(this.linkTimeout);
				}

				this.linkTimeout = setTimeout(
					function () {
						this.linkTimeout = null;
						self.scrapURL(event.target.textContent);
					},
					500
				);
			},

			scrapURL: function (urlText) {
				var urlString = '';
				if (urlText.indexOf('http://') >= 0) {
					urlString = this.getURL('http://', urlText);
				} else if (urlText.indexOf('https://') >= 0) {
					urlString = this.getURL('https://', urlText);
				} else if (urlText.indexOf('www.') >= 0) {
					urlString = this.getURL('www', urlText);
				}

				if (urlString !== '') {
					//check if the url of any of the excluded video oembeds
					var url_a = document.createElement('a');
					url_a.href = urlString;
					var hostname = url_a.hostname;
					if (BP_Nouveau.activity.params.excluded_hosts.indexOf(hostname) !== -1) {
						urlString = '';
					}
				}

				if ('' !== urlString) {
					this.loadURLPreview(urlString);
				} else {
					$('#activity-close-link-suggestion').click();
				}
			},

			getURL: function (prefix, urlText) {
				var urlString = '';
				var startIndex = urlText.indexOf(prefix);
				for (var i = startIndex; i < urlText.length; i++) {
					if (urlText[i] === ' ' || urlText[i] === '\n') {
						break;
					} else {
						urlString += urlText[i];
					}
				}
				if (prefix === 'www') {
					prefix = 'http://';
					urlString = prefix + urlString;
				}
				return urlString;
			},

			loadURLPreview: function (url) {
				var self = this;

				var regexp = /^(http:\/\/www\.|https:\/\/www\.|http:\/\/|https:\/\/)?[a-z0-9]+([\-\.]{1}[a-z0-9]+)*\.[a-z]{2,5}(:[0-9]{1,5})?(\/.*)?$/;
				url = $.trim(url);
				if (regexp.test(url)) {

					if (typeof self.options.activity.get('link_success') !== 'undefined' && self.options.activity.get('link_success') == true) {
						return false;
					}

					self.options.activity.set(
						{
							link_scrapping: true,
							link_loading: true,
							link_error: false,
							link_url: url
						}
					);

					bp.ajax.post('bp_activity_parse_url', {url: url}).always(
						function (response) {
							self.options.activity.set('link_loading', false);

							if (response.title === '' && response.images === '') {
								self.options.activity.set('link_scrapping', false);
								return;
							}

							if (response.error === '') {
								self.options.activity.set(
									{
										link_success: true,
										link_title: response.title,
										link_description: response.description,
										link_images: response.images,
										link_image_index: 0
									}
								);

								$('#whats-new-attachments').removeClass('empty');

								if ($('#whats-new-attachments').hasClass('activity-video-preview')) {
									$('#whats-new-attachments').removeClass('activity-video-preview');
								}

								if ($('#whats-new-attachments').hasClass('activity-link-preview')) {
									$('#whats-new-attachments').removeClass('activity-link-preview');
								}

								if ($('.activity-media-container').length) {
									if (response.description.indexOf('iframe') > -1) {
										$('#whats-new-attachments').addClass('activity-video-preview');
									} else {
										$('#whats-new-attachments').addClass('activity-link-preview');
									}
								}
							} else {
								self.options.activity.set({
									link_success: false,
									link_error: true,
									link_error_msg: response.error
								});
							}
						});
				}
			}
		}
	);

	bp.Views.WhatsNewPostIn = bp.View.extend(
		{
			tagName:   'select',
			id:        'whats-new-post-in',

			attributes: {
				name         : 'whats-new-post-in',
				'aria-label' : BP_Nouveau.activity.strings.whatsnewpostinLabel
			},

			events: {
				change: 'change'
			},

			keys: [],

			initialize: function() {
				this.model = new Backbone.Model();

				this.filters = this.options.filters || {};

				// Build `<option>` elements.
				this.$el.html(
					_.chain( this.filters ).map(
						function( filter, value ) {
							return {
								el: $( '<option></option>' ).val( value ).html( filter.text )[0],
								priority: filter.priority || 50
							};
						},
						this
					).sortBy( 'priority' ).pluck( 'el' ).value()
				);
			},

			change: function() {
				var filter = this.filters[ this.el.value ];
				if ( filter ) {
					this.model.set( { 'selected': this.el.value, 'placeholder': filter.autocomplete_placeholder } );
				}
			}
		}
	);

	bp.Views.Item = bp.View.extend(
		{
			tagName:   'li',
			className: 'bp-activity-object',
			template:  bp.template( 'activity-target-item' ),

			attributes: {
				role: 'checkbox'
			},

			initialize: function() {
				if ( this.model.get( 'selected' ) ) {
					this.el.className += ' selected';
				}
			},

			events: {
				click : 'setObject'
			},

			setObject:function( event ) {
				event.preventDefault();

				if ( true === this.model.get( 'selected' ) ) {
					this.model.clear();
				} else {
					this.model.set( 'selected', true );
				}
			}
		}
	);

	bp.Views.AutoComplete = bp.View.extend(
		{
			tagName : 'ul',
			id      : 'whats-new-post-in-box-items',

			events: {
				keyup :  'autoComplete'
			},

			initialize: function() {
				var autocomplete = new bp.Views.ActivityInput(
					{
						type        : 'text',
						id          : 'activity-autocomplete',
						placeholder : this.options.placeholder || ''
						}
				).render();

				this.$el.prepend( $( '<li></li>' ).html( autocomplete.$el ) );

				this.on( 'ready', this.setFocus, this );
				this.collection.on( 'add', this.addItemView, this );
				this.collection.on( 'reset', this.cleanView, this );
			},

			setFocus: function() {
				this.$el.find( '#activity-autocomplete' ).focus();
			},

			addItemView: function( item ) {
				this.views.add( new bp.Views.Item( { model: item } ) );
			},

			autoComplete: function() {
				var search = $( '#activity-autocomplete' ).val();

				// Reset the collection before starting a new search
				this.collection.reset();

				if ( 2 > search.length ) {
					return;
				}

				this.collection.fetch(
					{
						data: {
							type   : this.options.type,
							search : search,
							nonce  : BP_Nouveau.nonces.activity
						},
						success : _.bind( this.itemFetched, this ),
						error   : _.bind( this.itemFetched, this )
						}
				);
			},

			itemFetched: function( items ) {
				if ( ! items.length ) {
					this.cleanView();
				}
			},

			cleanView: function() {
				_.each(
					this.views._views[''],
					function( view ) {
						view.remove();
					}
				);
			}
		}
	);

	bp.Views.FormAvatar = bp.View.extend(
		{
			tagName  : 'div',
			id       : 'whats-new-avatar',
			template : bp.template( 'activity-post-form-avatar' ),

			initialize: function() {
				this.model = new Backbone.Model(
					_.pick(
						BP_Nouveau.activity.params,
						[
						'user_id',
						'avatar_url',
						'avatar_width',
						'avatar_height',
						'avatar_alt',
						'user_domain',
						'user_display_name'
						]
					)
				);

				if ( this.model.has( 'avatar_url' ) ) {
					this.model.set( 'display_avatar', true );
				}
			}
		}
	);

	bp.Views.FormContent = bp.View.extend(
		{
			tagName  : 'div',
			id       : 'whats-new-content',

			initialize: function() {
				this.$el.html( $( '<div></div>' ).prop( 'id', 'whats-new-textarea' ) );
				this.views.set( '#whats-new-textarea', new bp.Views.WhatsNew( { activity: this.options.activity } ) );
			}
		}
	);

	bp.Views.FormOptions = bp.View.extend(
		{
			tagName  : 'div',
			id       : 'whats-new-options',
			template : bp.template( 'activity-post-form-options' )
		}
	);

	bp.Views.FormTarget = bp.View.extend(
		{
			tagName   : 'div',
			id        : 'whats-new-post-in-box',
			className : 'in-profile',

			initialize: function() {
				var select = new bp.Views.WhatsNewPostIn( { filters: BP_Nouveau.activity.params.objects } );
				this.views.add( select );

				select.model.on( 'change', this.attachAutocomplete, this );
				bp.Nouveau.Activity.postForm.ActivityObjects.on( 'change:selected', this.postIn, this );

				this.toggleMultiMediaOptions();
			},

			attachAutocomplete: function( model ) {
				if ( 0 !== bp.Nouveau.Activity.postForm.ActivityObjects.models.length ) {
					bp.Nouveau.Activity.postForm.ActivityObjects.reset();
				}

				// Clean up views
				_.each(
					this.views._views[''],
					function( view ) {
						if ( ! _.isUndefined( view.collection ) ) {
							view.remove();
						}
					}
				);

				if ( 'profile' !== model.get( 'selected' ) ) {
					this.views.add(
						new bp.Views.AutoComplete(
							{
								collection:   bp.Nouveau.Activity.postForm.ActivityObjects,
								type:         model.get( 'selected' ),
								placeholder : model.get( 'placeholder' )
								}
						)
					);

					// Set the object type
					this.model.set( 'object', model.get( 'selected' ) );
				} else {
					this.model.set( { object: 'user', item_id: 0 } );
				}

				this.updateDisplay();
				this.toggleMultiMediaOptions();
			},

			postIn: function( model ) {
				if ( _.isUndefined( model.get( 'id' ) ) ) {
					// Reset the item id
					this.model.set( 'item_id', 0 );

					// When the model has been cleared, Attach Autocomplete!
					this.attachAutocomplete( new Backbone.Model( { selected: this.model.get( 'object' ) } ) );
					return;
				}

				// Set the item id for the selected object
				this.model.set( 'item_id', model.get( 'id' ) );

				// Set the view to the selected object
				this.views.set( '#whats-new-post-in-box-items', new bp.Views.Item( { model: model } ) );
			},

			updateDisplay: function() {
				if ( 'user' !== this.model.get( 'object' ) ) {
					this.$el.removeClass();
				} else if ( ! this.$el.hasClass( 'in-profile' ) ) {
					this.$el.addClass( 'in-profile' );
				}
			},

			toggleMultiMediaOptions: function() {
				if ( ! _.isUndefined( BP_Nouveau.media )) {

					if ('user' !== this.model.get( 'object' )) {

						// check media is enable in groups or not
						if (BP_Nouveau.media.group_media === false) {
							$( '#whats-new-toolbar .post-media' ).hide();
							var mediaCloseEvent = new Event( 'activity_media_close' );
							document.dispatchEvent( mediaCloseEvent );
						} else {
							$( '#whats-new-toolbar .post-media' ).show();
						}

						// check gif is enable in groups or not
						if (BP_Nouveau.media.gif.groups === false) {
							$( '#whats-new-toolbar .post-gif' ).hide();
							var gifCloseEvent = new Event( 'activity_gif_close' );
							document.dispatchEvent( gifCloseEvent );
						} else {
							$( '#whats-new-toolbar .post-gif' ).show();
						}

						// check emoji is enable in groups or not
						if (BP_Nouveau.media.emoji.groups === false) {
							$( '#whats-new-textarea' ).find( 'img.emojioneemoji' ).remove();
							$( '#whats-new-toolbar .post-emoji' ).hide();
						} else {
							$( '#whats-new-toolbar .post-emoji' ).show();
						}
					} else {

						// check media is enable in profile or not
						if (BP_Nouveau.media.profile_media === false) {
							$( '#whats-new-toolbar .post-media' ).hide();
							var event = new Event( 'activity_media_close' );
							document.dispatchEvent( event );
						} else {
							$( '#whats-new-toolbar .post-media' ).show();
						}

						// check gif is enable in profile or not
						if (BP_Nouveau.media.gif.profile === false) {
							$( '#whats-new-toolbar .post-gif' ).hide();
							var gifCloseEvent2 = new Event( 'activity_gif_close' );
							document.dispatchEvent( gifCloseEvent2 );
						} else {
							$( '#whats-new-toolbar .post-gif' ).show();
						}

						// check emoji is enable in profile or not
						if (BP_Nouveau.media.emoji.profile === false) {
							$( '#whats-new-toolbar .post-emoji' ).hide();
							$( '#whats-new-textarea' ).find( 'img.emojioneemoji' ).remove();
						} else {
							$( '#whats-new-toolbar .post-emoji' ).show();
						}
					}
				}
			}
		}
	);

	bp.Views.ActivityToolbar = bp.View.extend(
		{
			tagName: 'div',
			id: 'whats-new-toolbar',
			template: bp.template( 'whats-new-toolbar' ),
			events: {
				'click #activity-link-preview-button': 'toggleURLInput',
				'click #activity-gif-button': 'toggleGifSelector',
				'click #activity-media-button': 'toggleMediaSelector',
				'click .post-elements-buttons-item': 'activeButton'
			},

			initialize: function() {
				document.addEventListener( 'keydown', _.bind( this.closePickersOnEsc, this ) );
				$( document ).on( 'click', _.bind( this.closePickersOnClick, this ) );
			},

			render: function() {
				this.$el.html( this.template( this.model.toJSON() ) );
				this.$self          = this.$el.find( '#activity-gif-button' );
				this.$gifPickerEl   = this.$el.find( '.gif-media-search-dropdown' );
				this.$emojiPickerEl = $( '#whats-new' );
				return this;
			},

			toggleURLInput: function( e ) {
				var event;
				e.preventDefault();
				this.closeMediaSelector();
				this.closeGifSelector();
				if ( this.model.get( 'link_scrapping' ) ) {
					event = new Event( 'activity_link_preview_close' );
				} else {
					event = new Event( 'activity_link_preview_open' );
				}
				document.dispatchEvent( event );
			},

			closeURLInput: function() {
				var event = new Event( 'activity_link_preview_close' );
				document.dispatchEvent( event );
			},

			toggleGifSelector: function( e ) {
				e.preventDefault();
				this.closeURLInput();
				this.closeMediaSelector();
				if ( this.$gifPickerEl.is( ':empty' ) ) {
					var gifMediaSearchDropdownView = new bp.Views.GifMediaSearchDropdown( {model: this.model} );
					this.$gifPickerEl.html( gifMediaSearchDropdownView.render().el );
				}
				this.$self.toggleClass( 'open' );
				this.$gifPickerEl.toggleClass( 'open' );
			},

			closeGifSelector: function() {
				var event = new Event( 'activity_gif_close' );
				document.dispatchEvent( event );
			},

			toggleMediaSelector: function( e ) {
				e.preventDefault();
				this.closeURLInput();
				this.closeGifSelector();
				var event = new Event( 'activity_media_toggle' );
				document.dispatchEvent( event );
			},

			closeMediaSelector: function() {
				var event = new Event( 'activity_media_close' );
				document.dispatchEvent( event );
			},

			closePickersOnEsc: function( event ) {
				if ( event.key === 'Escape' || event.keyCode === 27 ) {
					if ( ! _.isUndefined( BP_Nouveau.media ) && ! _.isUndefined( BP_Nouveau.media.gif_api_key )) {
						this.$self.removeClass( 'open' );
						this.$gifPickerEl.removeClass( 'open' );
					}
				}
			},

			closePickersOnClick: function( event ) {
				var $targetEl = $( event.target );

				if ( ! _.isUndefined( BP_Nouveau.media ) && ! _.isUndefined( BP_Nouveau.media.gif_api_key ) &&
					! $targetEl.closest( '.post-gif' ).length) {
					this.$self.removeClass( 'open' );
					this.$gifPickerEl.removeClass( 'open' );
				}

			},

			activeButton: function (event) {
				this.$el.find( '.post-elements-buttons-item' ).removeClass( 'active' );
				event.currentTarget.classList.add( 'active' );
			}
		}
	);

	bp.Views.ActivityAttachments = bp.View.extend(
		{
			tagName: 'div',
			id: 'whats-new-attachments',
			activityLinkPreview: null,
			activityAttachedGifPreview: null,
			activityMedia: null,
			className : 'empty',
			initialize: function() {
				if ( ! _.isUndefined( window.Dropzone ) ) {
					this.activityMedia = new bp.Views.ActivityMedia( {model: this.model} );
					this.views.add( this.activityMedia );
				}

				if ( ! _.isUndefined( BP_Nouveau.activity.params.link_preview ) ) {
					this.activityLinkPreview = new bp.Views.ActivityLinkPreview( { model: this.model } );
					this.views.add( this.activityLinkPreview );
				}

				this.activityAttachedGifPreview = new bp.Views.ActivityAttachedGifPreview( { model: this.model } );
				this.views.add( this.activityAttachedGifPreview );
			},
			onClose: function() {
				if ( ! _.isNull( this.activityLinkPreview ) ) {
					this.activityLinkPreview.destroy();
				}
				if ( ! _.isNull( this.activityAttachedGifPreview ) ) {
					this.activityAttachedGifPreview.destroy();
				}
				if ( ! _.isNull( this.activityMedia ) ) {
					this.activityMedia.destroy();
				}
			}
		}
	);

		/**
	 * Now build the buttons!
	 * @type {[type]}
	 */
	bp.Views.FormButtons = bp.View.extend(
		{
			tagName : 'div',
			id      : 'whats-new-actions',

			initialize: function() {
				this.views.add( new bp.View( { tagName: 'ul', id: 'whats-new-buttons' } ) );

				_.each(
					this.collection.models,
					function( button ) {
						this.addItemView( button );
					},
					this
				);

				this.collection.on( 'change:active', this.isActive, this );
			},

			addItemView: function( button ) {
				this.views.add( '#whats-new-buttons', new bp.Views.FormButton( { model: button } ) );
			},

			isActive: function( button ) {
				// Clean up views
				_.each(
					this.views._views[''],
					function( view, index ) {
						if ( 0 !== index ) {
							view.remove();
						}
					}
				);

				// Then loop threw all buttons to update their status
				if ( true === button.get( 'active' ) ) {
					_.each(
						this.views._views['#whats-new-buttons'],
						function( view ) {
							if ( view.model.get( 'id' ) !== button.get( 'id' ) ) {
								// Silently update the model
								view.model.set( 'active', false, { silent: true } );

								// Remove the active class
								view.$el.removeClass( 'active' );

								// Trigger an even to let Buttons reset
								// their modifications to the activity model
								this.collection.trigger( 'reset:' + view.model.get( 'id' ), this.model );
							}
						},
						this
					);

					// Tell the active Button to load its content
					this.collection.trigger( 'display:' + button.get( 'id' ), this );

					// Trigger an even to let Buttons reset
					// their modifications to the activity model
				} else {
					this.collection.trigger( 'reset:' + button.get( 'id' ), this.model );
				}
			}
		}
	);

	bp.Views.FormButton = bp.View.extend(
		{
			tagName   : 'li',
			className : 'whats-new-button',
			template  : bp.template( 'activity-post-form-buttons' ),

			events: {
				click : 'setActive'
			},

			setActive: function( event ) {
				var isActive = this.model.get( 'active' ) || false;

				// Stop event propagation
				event.preventDefault();

				if ( false === isActive ) {
					this.$el.addClass( 'active' );
					this.model.set( 'active', true );
				} else {
					this.$el.removeClass( 'active' );
					this.model.set( 'active', false );
				}
			}
		}
	);

	bp.Views.FormSubmit = bp.View.extend(
		{
			tagName   : 'div',
			id        : 'whats-new-submit',
			className : 'in-profile',

			initialize: function() {
				this.reset = new bp.Views.ActivityInput(
					{
						type  : 'reset',
						id    : 'aw-whats-new-reset',
						className : 'text-button small',
						value : BP_Nouveau.activity.strings.cancelButton
						}
				);

				this.submit = new bp.Views.ActivityInput(
					{
						model: this.model,
						type  : 'submit',
						id    : 'aw-whats-new-submit',
						className : 'button',
						name  : 'aw-whats-new-submit',
						value : BP_Nouveau.activity.strings.postUpdateButton
						}
				);

				this.views.set( [ this.submit, this.reset ] );

				this.model.on( 'change:object', this.updateDisplay, this );
				this.model.on( 'change:posting', this.updateStatus, this );
			},

			updateDisplay: function( model ) {
				if ( _.isUndefined( model ) ) {
					return;
				}

				if ( 'user' !== model.get( 'object' ) ) {
					this.$el.removeClass( 'in-profile' );
				} else if ( ! this.$el.hasClass( 'in-profile' ) ) {
					this.$el.addClass( 'in-profile' );
				}
			},

			updateStatus: function( model ) {
				if ( _.isUndefined( model ) ) {
					return;
				}

				if ( model.get( 'posting' ) ) {
					this.submit.el.disabled = true;
					this.reset.el.disabled  = true;

					this.submit.el.classList.add( 'loading' );
				} else {
					this.submit.el.disabled = false;
					this.reset.el.disabled  = false;

					this.submit.el.classList.remove( 'loading' );
				}
			}
		}
	);

	bp.Views.FormSubmitWrapper = bp.View.extend(
		{
			tagName: 'div',
			id: 'activity-form-submit-wrapper',
			initialize: function() {
				// Select box for the object
				if ( ! _.isUndefined( BP_Nouveau.activity.params.objects ) && 1 < _.keys( BP_Nouveau.activity.params.objects ).length ) {
					this.views.add( new bp.Views.FormTarget( { model: this.model } ) );
				}

				$( '#whats-new-form' ).addClass( 'focus-in' ); // add some class to form so that DOM knows about focus

				this.views.add( new bp.Views.FormSubmit( { model: this.model } ) );
			}
		}
	);

	bp.Views.PostForm = bp.View.extend(
		{
			tagName   : 'form',
			className : 'activity-form',
			id        : 'whats-new-form',

			attributes: {
				name   : 'whats-new-form',
				method : 'post'
			},

			events: {
				'focus #whats-new' : 'displayFull',
				'reset'            : 'resetForm',
				'submit'           : 'postUpdate',
				'keydown'          : 'postUpdate'
			},

			initialize: function() {
				this.model = new bp.Models.Activity(
					_.pick(
						BP_Nouveau.activity.params,
						['user_id', 'item_id', 'object' ]
					)
				);

				// Clone the model to set the resetted one
				this.resetModel = this.model.clone();

				this.views.set(
					[
					new bp.Views.FormAvatar(),
					new bp.Views.FormContent( { activity: this.model, model: this.model } )
					]
				);

				this.model.on( 'change:errors', this.displayFeedback, this );
			},

			displayFull: function( event ) {

				// Remove feedback.
				this.cleanFeedback();

				if ( 2 !== this.views._views[''].length ) {
					return;
				}

				$( event.target ).css(
					{
						resize : 'vertical',
						height : 'auto'
						}
				);

				// Backcompat custom fields
				if ( true === BP_Nouveau.activity.params.backcompat ) {
					this.views.add( new bp.Views.FormOptions( { model: this.model } ) );
				}

				// Attach buttons
				if ( ! _.isUndefined( BP_Nouveau.activity.params.buttons ) ) {
					// Global
					bp.Nouveau.Activity.postForm.buttons.set( BP_Nouveau.activity.params.buttons );
					this.views.add( new bp.Views.FormButtons( { collection: bp.Nouveau.Activity.postForm.buttons, model: this.model } ) );
				}

				this.views.add( new bp.Views.ActivityAttachments( { model: this.model } ) );
				this.views.add( new bp.Views.ActivityToolbar( { model: this.model } ) );

				this.views.add( new bp.Views.FormSubmitWrapper( { model: this.model } ) );

				if ( ! _.isUndefined( BP_Nouveau.media ) && ! _.isUndefined( BP_Nouveau.media.emoji )) {
					$( '#whats-new' ).emojioneArea(
						{
							standalone: true,
							hideSource: false,
							container: '#whats-new-toolbar > .post-emoji',
							autocomplete: false,
							pickerPosition: 'bottom',
							hidePickerOnBlur: true,
							useInternalCDN: false,
							events: {
								emojibtn_click: function () {
									$( '#whats-new' )[0].emojioneArea.hidePicker();
								},
							}
							}
					);
				}

			},

			resetForm: function() {
				_.each(
					this.views._views[''],
					function( view, index ) {
						if ( index > 1 ) {
							view.close();
						}
					}
				);

				$( '#whats-new' ).css(
					{
						resize : 'none',
						height : '50px'
						}
				);

				$( '#whats-new-form' ).removeClass( 'focus-in' ); // remove class when reset

				// Reset the model
				this.model.clear();
				this.model.set( this.resetModel.attributes );
			},

			cleanFeedback: function() {
				_.each(
					this.views._views[''],
					function( view ) {
						if ( 'message' === view.$el.prop( 'id' ) ) {
							view.remove();
						}
					}
				);
			},

			displayFeedback: function( model ) {
				if ( _.isUndefined( this.model.get( 'errors' ) ) ) {
					this.cleanFeedback();
				} else {
					this.views.add( new bp.Views.activityFeedback( model.get( 'errors' ) ) );
				}
			},

			postUpdate: function( event ) {
				var self = this,
					meta = {};

				if ( event ) {
					if ( 'keydown' === event.type && ( 13 !== event.keyCode || ! event.ctrlKey ) ) {
						return event;
					}

					event.preventDefault();
				}

				// Set the content and meta
				_.each(
					this.$el.serializeArray(),
					function( pair ) {
						pair.name = pair.name.replace( '[]', '' );
						if ( -1 === _.indexOf( ['aw-whats-new-submit', 'whats-new-post-in'], pair.name ) ) {
							if ( _.isUndefined( meta[ pair.name ] ) ) {
									meta[ pair.name ] = pair.value;
							} else {
								if ( ! _.isArray( meta[ pair.name ] ) ) {
									meta[ pair.name ] = [ meta[ pair.name ] ];
								}

								meta[ pair.name ].push( pair.value );
							}
						}
					}
				);

				// Post content
				var $whatsNew = this.$el.find( '#whats-new' );

				// Transform emoji image into emoji unicode
				$whatsNew.find( 'img.emojioneemoji' ).replaceWith(
					function () {
						return this.dataset.emojiChar;
					}
				);

			// Add valid line breaks
			var content = $.trim( $whatsNew[0].innerHTML.replace(/<div>/gi,'\n').replace(/<\/div>/gi,'') );
			content = content.replace(/&nbsp;/g, ' ');

			self.model.set( 'content', content, { silent: true } );

				// Silently add meta
				this.model.set( meta, { silent: true } );

				// update posting status true
				this.model.set( 'posting', true );

				var data = {
					'_wpnonce_post_update': BP_Nouveau.activity.params.post_nonce
				};

				// Add the Akismet nonce if it exists.
				if ( $( '#_bp_as_nonce' ).val() ) {
					data._bp_as_nonce = $( '#_bp_as_nonce' ).val();
				}

				// Remove all unused model attribute
				data = _.omit(
					_.extend( data, this.model.attributes ),
					[
					'link_images',
					'link_image_index',
					'link_success',
					'link_error',
					'link_error_msg',
					'link_scrapping',
					'link_loading',
					'posting'
					]
				);

				// Form link preview data to pass in request if available
				if ( this.model.get( 'link_success' ) ) {
					var images = this.model.get( 'link_images' ),
						index  = this.model.get( 'link_image_index' );
					if ( images.length ) {
						data = _.extend(
							data,
							{
								'link_image': images[ index ]
								}
						);
					}

					// Append zero-width character to allow post link without activity content
					if ( _.isEmpty( data.content ) ) {
						data.content = '&#8203;';
					}
				} else {
					data = _.omit(
						data,
						[
						'link_title',
						'link_description',
						'link_url'
						]
					);
				}

				// Append zero-width character to allow post gif without activity content
				if ( ! _.isEmpty( data.gif_data ) && _.isEmpty( data.content ) ) {
					data.content = '&#8203;';
				}

				bp.ajax.post( 'post_update', data ).done(
					function( response ) {
							var store   = bp.Nouveau.getStorage( 'bp-activity' ),
							searchTerms = $( '[data-bp-search="activity"] input[type="search"]' ).val(), matches = {},
							toPrepend   = false;

							// Look for matches if the stream displays search results.
						if ( searchTerms ) {
							   searchTerms = new RegExp( searchTerms, 'im' );
							   matches     = response.activity.match( searchTerms );
						}

							/**
							 * Before injecting the activity into the stream, we need to check the filter
							 * and search terms are consistent with it when posting from a single item or
							 * from the Activity directory.
							 */
						if ( ( ! searchTerms || matches ) ) {
							   toPrepend = ! store.filter || 0 === parseInt( store.filter, 10 ) || 'activity_update' === store.filter;
						}

							/**
							 * In the Activity directory, we also need to check the active scope.
							 * eg: An update posted in a private group should only show when the
							 * "My Groups" tab is active.
							 */
						if ( toPrepend && response.is_directory ) {
							toPrepend = ( 'all' === store.scope && ( 'user' === self.model.get( 'object' ) || false === response.is_private ) ) || ( self.model.get( 'object' ) + 's' === store.scope );
						}

							var medias = self.model.get( 'media' );
						if ( typeof medias !== 'undefined' && medias.length ) {
							for ( var k = 0; k < medias.length; k++ ) {
								medias[k].saved = true;
							}
							self.model.set( 'media',medias );
						}

							// Reset the form
							self.resetForm();

							// Display a successful feedback if the acticity is not consistent with the displayed stream.
						if ( ! toPrepend ) {
							self.views.add( new bp.Views.activityFeedback( { value: response.message, type: 'updated' } ) );

							// Inject the activity into the stream only if it hasn't been done already (HeartBeat).
						} else if ( ! $( '#activity-' + response.id ).length ) {

							// It's the very first activity, let's make sure the container can welcome it!
							if ( ! $( '#activity-stream ul.activity-list' ).length ) {
								   $( '#activity-stream' ).html( $( '<ul></ul>' ).addClass( 'activity-list item-list bp-list' ) );
							}

					// Prepend the activity.
					bp.Nouveau.inject( '#activity-stream ul.activity-list', response.activity, 'prepend' );

					//replace dummy image with original image by faking scroll event
					jQuery(window).scroll();
				}
			} ).fail( function( response ) {

							self.model.set( 'posting', false );

							self.model.set( 'errors', { type: 'error', value: response.message } );
					}
				);
			}
		}
	);

	bp.Nouveau.Activity.postForm.start();

} )( bp, jQuery );

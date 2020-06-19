/* global wp, bp, BP_Nouveau, JSON */
/* jshint devel: true */
/* jshint browser: true */
/* @version 3.0.0 */
window.wp = window.wp || {};
window.bp = window.bp || {};

( function( exports, $ ) {

	// Bail if not set
	if ( typeof BP_Nouveau === 'undefined' ) {
		return;
	}

	/**
	 * [Nouveau description]
	 *
	 * @type {Object}
	 */
	bp.Nouveau = {
		/**
		 * [start description]
		 *
		 * @return {[type]} [description]
		 */
		start: function() {

			// Setup globals
			this.setupGlobals();

			// Adjust Document/Forms properties
			this.prepareDocument();

			// $.ajaxPrefilter( this.mediaPreFilter );

			// Init the BuddyPress objects
			this.initObjects();

			// Set BuddyPress HeartBeat
			this.setHeartBeat();

			// Listen to events ("Add hooks!")
			this.addListeners();

			// Toggle Grid/List View
			this.switchGridList();

			// Email Invites popup revoke access
			this.sendInvitesRevokeAccess();

			this.sentInvitesFormValidate();

			// Privacy Policy & Terms Popup on Register page
			this.registerPopUp();

			// Privacy Policy Popup on Login page and Lost Password page
			this.loginPopUp();

			// Check for lazy images and load them also register scroll event to load on scroll
			bp.Nouveau.lazyLoad( '.lazy' );
			$( window ).on(
				'scroll resize',
				function(){
					bp.Nouveau.lazyLoad( '.lazy' );
				}
			);
		},

		/**
		 * [setupGlobals description]
		 *
		 * @return {[type]} [description]
		 */
		setupGlobals: function() {

			this.ajax_request = null;

			// Object Globals
			this.objects         = $.map( BP_Nouveau.objects, function( value ) { return value; } );
			this.objectNavParent = BP_Nouveau.object_nav_parent;

			// HeartBeat Global
			this.heartbeat = wp.heartbeat || false;

			// An object containing each query var
			this.querystring = this.getLinkParams();
		},

		/**
		 * [prepareDocument description]
		 *
		 * @return {[type]} [description]
		 */
		prepareDocument: function() {

			// Remove the no-js class and add the js one
			if ( $( 'body' ).hasClass( 'no-js' ) ) {
				$( 'body' ).removeClass( 'no-js' ).addClass( 'js' );
			}

			// Log Warnings into the console instead of the screen
			if ( BP_Nouveau.warnings && 'undefined' !== typeof console && console.warn ) {
				$.each(
					BP_Nouveau.warnings,
					function( w, warning ) {
						console.warn( warning );
					}
				);
			}

			// Remove the directory title if there's a widget containing it
			if ( $( '.buddypress_object_nav .widget-title' ).length ) {
				var text = $( '.buddypress_object_nav .widget-title' ).html();

				$( 'body' ).find( '*:contains("' + text + '")' ).each(
					function( e, element ) {
						if ( ! $( element ).hasClass( 'widget-title' ) && text === $( element ).html() && ! $( element ).is( 'a' ) ) {
							  $( element ).remove();
						}
					}
				);
			}
		},

		/** Helpers *******************************************************************/

		/**
		 * [getStorage description]
		 *
		 * @param  {[type]} type     [description]
		 * @param  {[type]} property [description]
		 * @return {[type]}          [description]
		 */
		getStorage: function( type, property ) {

			var store = sessionStorage.getItem( type );

			if ( store ) {
				store = JSON.parse( store );
			} else {
				store = {};
			}

			if ( undefined !== property ) {
				return store[property] || false;
			}

			return store;
		},

		/**
		 * [setStorage description]
		 *
		 * @param {[type]} type     [description]
		 * @param {[type]} property [description]
		 * @param {[type]} value    [description]
		 */
		setStorage: function( type, property, value ) {

			var store = this.getStorage( type );

			if ( undefined === value && undefined !== store[ property ] ) {
				delete store[ property ];
			} else {
				// Set property.
				store[ property ] = value;
			}

			sessionStorage.setItem( type, JSON.stringify( store ) );

			return sessionStorage.getItem( type ) !== null;
		},

		/**
		 * [getLinkParams description]
		 *
		 * @param  {[type]} url   [description]
		 * @param  {[type]} param [description]
		 * @return {[type]}       [description]
		 */
		getLinkParams: function( url, param ) {

			var qs;
			if ( url ) {
				qs = ( -1 !== url.indexOf( '?' ) ) ? '?' + url.split( '?' )[1] : '';
			} else {
				qs = document.location.search;
			}

			if ( ! qs ) {
				return null;
			}

			var params = qs.replace( /(^\?)/, '' ).split( '&' ).map(
				function( n ) {
						return n = n.split( '=' ), this[n[0]] = n[1], this;
				}.bind( {} )
			)[0];

			if ( param ) {
				return params[param];
			}

			return params;
		},

		/**
		 * URL Decode a query variable.
		 *
		 * @param  {string} qv    The query variable to decode.
		 * @param  {object} chars The specific characters to use. Optionnal.
		 * @return {string}       The URL decoded variable.
		 */
		urlDecode: function( qv, chars ) {

			var specialChars = chars || {
				amp: '&',
				lt: '<',
				gt: '>',
				quot: '"',
				'#039': '\''
			};

			return decodeURIComponent( qv.replace( /\+/g, ' ' ) ).replace(
				/&([^;]+);/g,
				function( v, q ) {
					return specialChars[q] || '';
				}
			);
		},

		/**
		 * [ajax description]
		 *
		 * @param  {[type]} post_data [description]
		 * @param  {[type]} object    [description]
		 * @param  {[type]} button    [description]
		 * @return {[type]}           [description]
		 */
		ajax: function( post_data, object, button ) {

			if ( this.ajax_request && typeof button === 'undefined' ) {
				this.ajax_request.abort();
			}

			// Extend posted data with stored data and object nonce
			var postData = $.extend( {}, bp.Nouveau.getStorage( 'bp-' + object ), { nonce: BP_Nouveau.nonces[object] }, post_data );

			if ( undefined !== BP_Nouveau.customizer_settings ) {
				postData.customized = BP_Nouveau.customizer_settings;
			}

			this.ajax_request = $.post( BP_Nouveau.ajaxurl, postData, 'json' );

			return this.ajax_request;
		},

		inject: function( selector, content, method ) {

			if ( ! $( selector ).length || ! content ) {
				return;
			}

			/**
			 * How the content should be injected in the selector
			 *
			 * possible methods are
			 * - reset: the selector will be reset with the content
			 * - append:  the content will be added after selector's content
			 * - prepend: the content will be added before selector's content
			 */
			method = method || 'reset';
			if ( 'append' === method ) {
				$( selector ).append( content ).find( 'li.activity-item' ).each( this.hideSingleUrl	);
			} else if ( 'prepend' === method ) {
				$( selector ).prepend( content ).find( 'li.activity-item' ).each( this.hideSingleUrl );
			} else {
				$( selector ).html( content ).find( 'li.activity-item' ).each( this.hideSingleUrl );
			}

			if ( 'undefined' !== typeof bp_mentions || 'undefined' !== typeof bp.mentions ) {
				$( '.bp-suggestions' ).bp_mentions( bp.mentions.users );
			}
		},
		/**
		 * [hideSingleUrl description]
		 *
		 * @param  {[type]} event [description]
		 * @param  {[type]} request [description]
		 * @param  {[type]} settings [description]
		 * @return {[type]}       [description]
		 */
		hideSingleUrl: function() {
			var _findtext  = $( this ).find( '.activity-inner > p' ).removeAttr( 'br' ).removeAttr( 'a' ).text();
			var	_url       = '',
				_newString = '',
				startIndex = '',
				_is_exist  = 0;
			if ( 0 <= _findtext.indexOf( 'http://' )) {
				startIndex = _findtext.indexOf( 'http://' );
				_is_exist  = 1;
			} else if (0 <= _findtext.indexOf( 'https://' )) {
				startIndex = _findtext.indexOf( 'https://' );
				_is_exist  = 1;
			} else if (0 <= _findtext.indexOf( 'www.' )) {
				startIndex = _findtext.indexOf( 'www' );
				_is_exist  = 1;
			}
			if ( 1 === _is_exist ) {
				for ( var i = startIndex; i < _findtext.length; i ++ ) {
					if ( _findtext[i] === ' ' || _findtext[i] === '\n' ) {
						break;
					} else {
						_url += _findtext[i];
					}
				}

				if ( _url !== '' ) {
					_newString = $.trim( _findtext.replace( _url, '' ) );
				}
				if (0 >= _newString.length) {
					if ( $( this ).find( '.activity-inner > .activity-link-preview-container ' ).length || $( this ).hasClass( 'wp-link-embed' ) ) {
						$( this ).find( '.activity-inner > p:first a' ).hide();
					}
				}
			}

		},
		/**
		 * [objectRequest description]
		 *
		 * @param  {[type]} data [description]
		 * @return {[type]}      [description]
		 */
		objectRequest: function( data ) {

			var postdata = {}, self = this;

			data = $.extend(
				{
					object       : '',
					scope        : null,
					filter       : null,
					target       : '#buddypress [data-bp-list]',
					search_terms : '',
					page         : 1,
					extras       : null,
					caller       : null,
					template     : null,
					method       : 'reset'
				},
				data
			);

			// Do not request if we don't have the object or the target to inject results into
			if ( ! data.object || ! data.target ) {
				return;
			}

			// prevent activity response to append to media model activity list element
			if ( data.object == 'activity' && data.target == '#buddypress [data-bp-list] ul.bp-list' ) {
				data.target = '#buddypress [data-bp-list] ul.bp-list:not(#bb-media-model-container ul.bp-list)';
			}

			// if object is activity and object nav does not exists fallback to scope = all
			if ( data.object == 'activity' && ! $( this.objectNavParent + ' [data-bp-scope="' + data.scope + '"]' ).length ) {
				data.scope = 'all';
			}

			// Prepare the search terms for the request
			if ( data.search_terms ) {
				data.search_terms = data.search_terms.replace( /</g, '&lt;' ).replace( />/g, '&gt;' );
			}

			// Set session's data
			if ( null !== data.scope ) {
				this.setStorage( 'bp-' + data.object, 'scope', data.scope );
			}

			if ( null !== data.filter ) {
				this.setStorage( 'bp-' + data.object, 'filter', data.filter );
			}

			if ( null !== data.extras ) {
				this.setStorage( 'bp-' + data.object, 'extras', data.extras );
			}

			/* Set the correct selected nav and filter */
			$( this.objectNavParent + ' [data-bp-object]' ).each(
				function() {
						$( this ).removeClass( 'selected loading' );
						// $( this ).find( 'span' ).hide();
						// $( this ).find( 'span' ).text('');
				}
			);

			if ( $( this.objectNavParent + ' [data-bp-scope="' + data.scope + '"]' ).length ) {
				$( this.objectNavParent + ' [data-bp-scope="' + data.scope + '"], #object-nav li.current' ).addClass( 'selected loading' );
			} else {
				$( this.objectNavParent + ' [data-bp-scope]:eq(0), #object-nav li.current' ).addClass( 'selected loading' );
			}
			// $( this.objectNavParent + ' [data-bp-scope="' + data.scope + '"], #object-nav li.current' ).find( 'span' ).text('');
			// $( this.objectNavParent + ' [data-bp-scope="' + data.scope + '"], #object-nav li.current' ).find( 'span' ).show();
			$( '#buddypress [data-bp-filter="' + data.object + '"] option[value="' + data.filter + '"]' ).prop( 'selected', true );

			if ( 'friends' === data.object || 'group_members' === data.object ) {
				data.template = data.object;
				data.object   = 'members';
			} else if ( 'group_requests' === data.object ) {
				data.object   = 'groups';
				data.template = 'group_requests';
			} else if ( 'group_subgroups' === data.object ) {
				data.object	  = 'groups';
				data.template = 'group_subgroups';
			} else if ( 'notifications' === data.object ) {
				data.object	  = 'members';
				data.template = 'member_notifications';
			}

			postdata = $.extend(
				{
					action: data.object + '_filter'
				},
				data
			);

			return this.ajax( postdata, data.object ).done(
				function( response ) {
					if ( false === response.success || _.isUndefined( response.data ) ) {
						  return;
					}

					if ( $( 'body.group-members.members.buddypress' ).length && ! _.isUndefined( response.data ) && ! _.isUndefined( response.data.count ) ) {
						  $( 'body.group-members.members.buddypress ul li#members-groups-li' ).find( 'span' ).text( response.data.count );
					}

						$( self.objectNavParent + ' [data-bp-scope="' + data.scope + '"]' ).removeClass( 'loading' );
						$( self.objectNavParent + ' [data-bp-scope="' + data.scope + '"]' ).find( 'span' ).text( '' );

					if ( ! _.isUndefined( response.data ) && ! _.isUndefined( response.data.count )) {
						  $( self.objectNavParent + ' [data-bp-scope="' + data.scope + '"]' ).find( 'span' ).text( response.data.count );
					}

					if ( ! _.isUndefined( response.data ) && ! _.isUndefined( response.data.scopes )) {
						for (var i in response.data.scopes) {
							$( self.objectNavParent + ' [data-bp-scope="' + i + '"]' ).find( 'span' ).text( response.data.scopes[i] );
						}
					}

					if ( 'reset' !== data.method ) {
						  self.inject( data.target, response.data.contents, data.method );

						  $( data.target ).trigger( 'bp_ajax_' + data.method, $.extend( data, { response: response.data } ) );
					} else {
						  /* animate to top if called from bottom pagination */
						if ( data.caller === 'pag-bottom' ) {
							var top = null;
							if ( $( '#subnav' ).length ) {
								  top = $( '#subnav' ).parent();
							} else {
								  top = $( data.target );
							}
							$( 'html,body' ).animate(
								{ scrollTop: top.offset().top },
								'slow',
								function() {
									$( data.target ).fadeOut(
										100,
										function() {
											self.inject( this, response.data.contents, data.method );
											$( this ).fadeIn( 100 );

											// Inform other scripts the list of objects has been refreshed.
											$( data.target ).trigger( 'bp_ajax_request', $.extend( data, { response: response.data } ) );

											// Lazy Load Images
											if (bp.Nouveau.lazyLoad) {
												setTimeout(
													function(){ // Waiting to load dummy image
														bp.Nouveau.lazyLoad( '.lazy' );
													},
													1000
												);
											}
										}
									);
								}
							);

						} else {
							$( data.target ).fadeOut(
								100,
								function() {
									self.inject( this, response.data.contents, data.method );
									$( this ).fadeIn( 100 );

									// Inform other scripts the list of objects has been refreshed.
									$( data.target ).trigger( 'bp_ajax_request', $.extend( data, { response: response.data } ) );

									// Lazy Load Images
									if (bp.Nouveau.lazyLoad) {
										setTimeout(
											function(){ // Waiting to load dummy image
												bp.Nouveau.lazyLoad( '.lazy' );
											},
											1000
										);
									}
								}
							);
						}
					}
				}
			);
		},

		/**
		 * [initObjects description]
		 *
		 * @return {[type]} [description]
		 */
		initObjects: function() {
			var self = this, objectData = {}, queryData = {}, scope = 'all', search_terms = '', extras = null, filter = null;

			$.each(
				this.objects,
				function( o, object ) {
					objectData = self.getStorage( 'bp-' + object );

					var typeType = window.location.hash.substr( 1 );
					if ( undefined !== typeType && typeType == 'following' ) {
						scope = typeType;
					} else if ( undefined !== objectData.scope ) {
						scope = objectData.scope;
					}

					// Notifications always need to start with Newest ones
					if ( undefined !== objectData.extras && 'notifications' !== object ) {
						extras = objectData.extras;
					}

					if (  $( '#buddypress [data-bp-filter="' + object + '"]' ).length ) {
						if ( undefined !== objectData.filter ) {
							filter = objectData.filter;
							$( '#buddypress [data-bp-filter="' + object + '"] option[value="' + filter + '"]' ).prop( 'selected', true );
						} else if ( '-1' !== $( '#buddypress [data-bp-filter="' + object + '"]' ).val() && '0' !== $( '#buddypress [data-bp-filter="' + object + '"]' ).val() ) {
							filter = $( '#buddypress [data-bp-filter="' + object + '"]' ).val();
						}
					}

					if ( $( this.objectNavParent + ' [data-bp-object="' + object + '"]' ).length ) {
						$( this.objectNavParent + ' [data-bp-object="' + object + '"]' ).each(
							function() {
								$( this ).removeClass( 'selected' );
							}
						);

						$( this.objectNavParent + ' [data-bp-scope="' + object + '"], #object-nav li.current' ).addClass( 'selected' );
					}

					// Check the querystring to eventually include the search terms
					if ( null !== self.querystring ) {
						if ( undefined !== self.querystring[ object + '_search'] ) {
							search_terms = self.querystring[ object + '_search'];
						} else if ( undefined !== self.querystring.s ) {
							search_terms = self.querystring.s;
						}

						if ( search_terms ) {
							$( '#buddypress [data-bp-search="' + object + '"] input[type=search]' ).val( search_terms );
						}
					}

					if ( $( '#buddypress [data-bp-list="' + object + '"]' ).length ) {
						queryData = {
							object       : object,
							scope        : scope,
							filter       : filter,
							search_terms : search_terms,
							extras       : extras
						};

						if ( $( '#buddypress [data-bp-member-type-filter="' + object + '"]' ).length ) {
							queryData.member_type_id = $( '#buddypress [data-bp-member-type-filter="' + object + '"]' ).val();
						} else if ( $( '#buddypress [data-bp-group-type-filter="' + object + '"]' ).length ) {
							queryData.group_type = $( '#buddypress [data-bp-group-type-filter="' + object + '"]' ).val();
						}

						// Populate the object list
						self.objectRequest( queryData );
					}
				}
			);
		},

		/**
		 * [setHeartBeat description]
		 */
		setHeartBeat: function() {
			if ( typeof BP_Nouveau.pulse === 'undefined' || ! this.heartbeat ) {
				return;
			}

			this.heartbeat.interval( Number( BP_Nouveau.pulse ) );

			// Extend "send" with BuddyPress namespace
			$.fn.extend(
				{
					'heartbeat-send': function() {
						return this.bind( 'heartbeat-send' );
					}
				}
			);

			// Extend "tick" with BuddyPress namespace
			$.fn.extend(
				{
					'heartbeat-tick': function() {
						return this.bind( 'heartbeat-tick' );
					}
				}
			);
		},

		/** Event Listeners ***********************************************************/

		/**
		 * [addListeners description]
		 */
		addListeners: function() {
			// Disabled inputs
			$( '[data-bp-disable-input]' ).on( 'change', this.toggleDisabledInput );

			// Refreshing
			$( this.objectNavParent + ' .bp-navs' ).on( 'click', 'a', this, this.scopeQuery );

			// Filtering
			$( document ).on( 'change', '#buddypress [data-bp-filter]', this, this.filterQuery );

			// Group Type & Member Type Filter
			$( document ).on( 'change', '#buddypress [data-bp-group-type-filter]', this, this.typeGroupFilterQuery );
			$( document ).on( 'change', '#buddypress [data-bp-member-type-filter]', this, this.typeMemberFilterQuery );

			// Searching
			$( '#buddypress [data-bp-search]' ).on( 'submit', 'form', this, this.searchQuery );
			$( '#buddypress [data-bp-search] form' ).on( 'search', 'input[type=search]', this.resetSearch );

			// Buttons
			$( '#buddypress [data-bp-list], #buddypress #item-header, #buddypress.bp-shortcode-wrap .dir-list' ).on( 'click', '[data-bp-btn-action]', this, this.buttonAction );
			$( '#buddypress [data-bp-list], #buddypress #item-header, #buddypress.bp-shortcode-wrap .dir-list' ).on( 'blur', '[data-bp-btn-action]', this, this.buttonRevert );
			$( document ).on( 'click', '#buddypress table.invite-settings .field-actions .field-actions-remove, #buddypress table.invite-settings .field-actions-add', this, this.addRemoveInvite );

			$( document ).on( 'keyup', this, this.keyUp );

			// Close notice
			$( '[data-bp-close]' ).on( 'click', this, this.closeNotice );

			// Pagination
			$( '#buddypress [data-bp-list]' ).on( 'click', '[data-bp-pagination] a', this, this.paginateAction );

			$( document ).on( 'click', this.closePickersOnClick );
			document.addEventListener( 'keydown', this.closePickersOnEsc );
		},

		/**
		 * [switchGridList description]
		 *
		 * @return {[type]} [description]
		 */
		switchGridList: function() {
			var _this = this, group_members = false, object = $( '.grid-filters' ).data( 'object' );

			if ( 'group_members' === object ) {
				group_members = true;
			}

			if ( 'friends' === object ) {
				object = 'members';
			} else if ( 'group_requests' === object ) {
				object = 'groups';
			} else if ( 'notifications' === object ) {
				object = 'members';
			}

			var objectData = _this.getStorage( 'bp-' + object );

			var extras = {};
			if ( undefined !== objectData.extras ) {
				extras = objectData.extras;

				if ( undefined !== extras.layout ) {
					$( '.grid-filters .layout-view' ).removeClass( 'active' );
					if ( extras.layout === 'list' ) {
						$( '.grid-filters .layout-list-view' ).addClass( 'active' );
					} else {
						$( '.grid-filters .layout-grid-view' ).addClass( 'active' );
					}
				}
			}

			$( document ).on(
				'click',
				'.grid-filters .layout-view',
				function(e) {
					e.preventDefault();

					if ( $( this ).hasClass( 'layout-list-view' ) ) {
						$( '.layout-grid-view' ).removeClass( 'active' );
						$( this ).addClass( 'active' );
						$( '.bp-list' ).removeClass( 'grid' );
						extras.layout = 'list';
					} else {
						$( '.layout-list-view' ).removeClass( 'active' );
						$( this ).addClass( 'active' );
						$( '.bp-list' ).addClass( 'grid' );
						extras.layout = 'grid';
					}

					// Added this condition to fix the list and grid view on Groups members page pagination.
					if ( group_members ) {
						_this.setStorage( 'bp-group_members', 'extras', extras );
					} else {
						_this.setStorage( 'bp-' + object, 'extras', extras );
					}
				}
			);
		},

		sentInvitesFormValidate: function() {

			if ( $( 'body.send-invites #send-invite-form #member-invites-table' ).length ) {

				$( 'body.send-invites #send-invite-form' ).submit(
					function() {

						var prevent 			= false;
						var title 				= '';
						var id 					= '';
						var email 				= '';
						var id_lists 			= [];
						var all_lists 			= [];
						var alert_message 		= '';
						var inviteMessage 		= 0;
						var inviteSubject 		= 0;
						var subject 			= '';
						var subjectErrorMessage = '';
						var message 			= '';
						var messageErrorMessage = '';
						var emailRegex 			= /^([a-zA-Z0-9_.+-])+\@(([a-zA-Z0-9-])+\.)+([a-zA-Z0-9]{2,4})+$/;
						var emptyName 			= $( 'body.send-invites #send-invite-form #error-message-empty-name-field' ).val();
						var invalidEmail 		= $( 'body.send-invites #send-invite-form #error-message-invalid-email-address-field' ).val();

						alert_message = $( 'body.send-invites #send-invite-form #error-message-required-field' ).val();
						inviteSubject = $( 'body.send-invites #send-invite-form #error-message-empty-subject-field' ).length;
						inviteMessage = $( 'body.send-invites #send-invite-form #error-message-empty-body-field' ).length;

						if ( 1 === inviteSubject ) {
							subject 			= $( 'body.send-invites #send-invite-form #bp-member-invites-custom-subject' ).val();
							subjectErrorMessage = $( 'body.send-invites #send-invite-form #error-message-empty-subject-field' ).val();
						}

						if ( 1 === inviteMessage ) {
							// message = $('body.send-invites #send-invite-form #bp-member-invites-custom-content').val();
							/* jshint ignore:start */
							message = tinyMCE.get( 'bp-member-invites-custom-content' ).getContent();
							/* jshint ignore:end */
							messageErrorMessage = $( 'body.send-invites #send-invite-form #error-message-empty-body-field' ).val();
						}

						if ( 1 === inviteSubject && 1 === inviteMessage ) {

							var bothFieldsErrorMessage = $( 'body.send-invites #send-invite-form #error-message-empty-subject-body-field' ).val();

							if ( '' === subject && '' === message ) {
								if ( ! confirm( bothFieldsErrorMessage )) {
									return false;
								}
							} else if ( '' !== subject && '' === message ) {
								if ( ! confirm( messageErrorMessage )) {
									return false;
								}
							} else if ( '' === subject && '' !== message ) {
								if ( ! confirm( subjectErrorMessage )) {
									return false;
								}
							}

						} else if ( 0 === inviteSubject && 1 === inviteMessage ) {
							if ( '' === message ) {
								if ( ! confirm( messageErrorMessage )) {
									return false;
								}
							}
						} else if ( 1 === inviteSubject && 0 === inviteMessage ) {
							if ( '' === subject ) {
								if ( ! confirm( subjectErrorMessage )) {
									return false;
								}
							}
						}

						$( 'body.send-invites #send-invite-form #member-invites-table > tbody  > tr' ).each(
							function() {
								$( this ).find( 'input[type="text"]' ).removeAttr( 'style' );
								$( this ).find( 'input[type="email"]' ).removeAttr( 'style' );
							}
						);

						$( 'body.send-invites #send-invite-form #member-invites-table > tbody  > tr' ).each(
							function() {

								title = $.trim( $( this ).find( 'input[type="text"]' ).val() );
								id    = $( this ).find( 'input' ).attr( 'id' );
								email = $.trim( $( this ).find( 'input[type="email"]' ).val() );

								if ( '' === title && '' === email ) {
									prevent = false;
								} else if ( '' !== title && '' === email ) {
									id      = $( this ).find( 'input[type="email"]' ).attr( 'id' );
									prevent = true;
									id_lists.push( id );
								} else if ( '' === title && '' !== email ) {
									id      = $( this ).find( 'input[type="text"]' ).attr( 'id' );
									prevent = true;
									id_lists.push( id );
								} else {
									if ( ! emailRegex.test( email ) ) {
										id      = $( this ).find( 'input[type="email"]' ).attr( 'id' );
										prevent = true;
										id_lists.push( id );
									} else {
										prevent = false;
										all_lists.push( 1 );
									}
								}
							}
						);

						$( '.span_error' ).remove();

						if (id_lists.length === 0) {

						} else {
							id_lists.forEach(
								function(item) {
									$( '#' + item ).attr( 'style','border:1px solid #ef3e46' );
									if (item.indexOf( 'email_' ) !== -1) {
										$( '#' + item ).after( '<span class="span_error" style="color:#ef3e46">' + invalidEmail + '</span>' );
									} else {
										$( '#' + item ).after( '<span class="span_error" style="color:#ef3e46">' + emptyName + '</span>' );
									}
								}
							);
							$( 'html, body' ).animate(
								{
									scrollTop: $( '#item-body' ).offset().top
								},
								2000
							);
							alert( alert_message );
							return false;
						}

						if ( $( '#email_0_email_error' ).length ) {
							$( '#email_0_email_error' ).remove();
						}

						if (all_lists.length === 0) {
							var name 	   = $( '#invitee_0_title' ).val();
							var emailField = $( '#email_0_email' ).val();
							if ( '' === name && '' === emailField ) {
								$( '#invitee_0_title' ).attr( 'style', 'border:1px solid #ef3e46' );
								$( '#invitee_0_title' ).focus();
								$( '#email_0_email' ).attr( 'style','border:1px solid #ef3e46' );
								return false;
							} else if ( '' !== name && '' === emailField ) {
								$( '#email_0_email' ).attr( 'style','border:1px solid #ef3e46' );
								$( '#email_0_email' ).focus();
								return false;
							}
							if ( ! emailRegex.test( emailField ) ) {
								$( '#email_0_email' ).attr( 'style','border:1px solid #ef3e46' );
								$( '#email_0_email' ).focus();
								$( '#email_0_email_error' ).remove();
								$( '#email_0_email' ).after( '<span id="email_0_email_error" style="color:#ef3e46">' + invalidEmail + '</span>' );
							}
							alert( alert_message );
							return false;
						}

					}
				);
			}
		},

		sendInvitesRevokeAccess: function() {

			if ( $( 'body.sent-invites #member-invites-table' ).length ) {

				$( 'body.sent-invites #member-invites-table tr td span a.revoked-access' ).click(
					function( e ) {
						e.preventDefault();

						var alert_message = $( this ).attr( 'data-name' );
						var id            = $( this ).attr( 'id' );
						var action        = $( this ).attr( 'data-revoke-access' );

						if ( confirm( alert_message ) ) {
							$.ajax(
								{
									url : action,
									type : 'post',
									data : {
										item_id  : id
									},success : function() {
										window.location.reload( true );
									}
								}
							);
						} else {
							return false;
						}
					}
				);
			}
		},

		/** Event Callbacks ***********************************************************/

		/**
		 * [enableDisabledInput description]
		 *
		 * @param  {[type]} event [description]
		 * @param  {[type]} data  [description]
		 * @return {[type]}       [description]
		 */
		toggleDisabledInput: function() {

			// Fetch the data attr value (id)
			// This a pro tem approach due to current conditions see
			// https://github.com/buddypress/next-template-packs/issues/180.
			var disabledControl = $( this ).attr( 'data-bp-disable-input' );

			if ( $( disabledControl ).prop( 'disabled', true ) && ! $( this ).hasClass( 'enabled' ) ) {
				$( this ).addClass( 'enabled' ).removeClass( 'disabled' );
				$( disabledControl ).removeProp( 'disabled' );

			} else if ( $( disabledControl ).prop( 'disabled', false ) && $( this ).hasClass( 'enabled' ) ) {
				$( this ).removeClass( 'enabled' ).addClass( 'disabled' );
				// Set using attr not .prop else DOM renders as 'disable=""' CSS needs 'disable="disable"'.
				$( disabledControl ).attr( 'disabled', 'disabled' );
			}
		},

		/**
		 * [keyUp description]
		 *
		 * @param  {[type]} event [description]
		 * @return {[type]}       [description]
		 */
		keyUp: function( event ) {
			var self = event.data;
			if ( event.keyCode === 27 ) { // escape key
				self.buttonRevertAll();
			}
		},

		/**
		 * [queryScope description]
		 *
		 * @param  {[type]} event [description]
		 * @return {[type]}       [description]
		 */
		scopeQuery: function( event ) {
			var self = event.data, target = $( event.currentTarget ).parent(), scope = 'all', object, filter = null, search_terms = '', extras = null, queryData = {};

			if ( target.hasClass( 'no-ajax' ) || $( event.currentTarget ).hasClass( 'no-ajax' ) || ! target.attr( 'data-bp-scope' ) ) {
				return event;
			}

			scope  = target.data( 'bp-scope' );
			object = target.data( 'bp-object' );

			if ( ! scope || ! object ) {
				return event;
			}

			// Stop event propagation
			event.preventDefault();

			var objectData = self.getStorage( 'bp-' + object );

			// Notifications always need to start with Newest ones
			if ( undefined !== objectData.extras && 'notifications' !== object ) {
				extras = objectData.extras;
			}

			filter = $( '#buddypress' ).find( '[data-bp-filter="' + object + '"]' ).first().val();

			if ( $( '#buddypress [data-bp-search="' + object + '"] input[type=search]' ).length ) {
				search_terms = $( '#buddypress [data-bp-search="' + object + '"] input[type=search]' ).val();
			}

			// Remove the New count on dynamic tabs
			if ( target.hasClass( 'dynamic' ) ) {
				target.find( 'a span' ).html( '' );
			}

			queryData = {
				object       : object,
				scope        : scope,
				filter       : filter,
				search_terms : search_terms,
				page         : 1,
				extras       : extras
			};

			if ( $( '#buddypress [data-bp-member-type-filter="' + object + '"]' ).length ) {
				queryData.member_type_id = $( '#buddypress [data-bp-member-type-filter="' + object + '"]' ).val();
			} else if ( $( '#buddypress [data-bp-group-type-filter="' + object + '"]' ).length ) {
				queryData.group_type = $( '#buddypress [data-bp-group-type-filter="' + object + '"]' ).val();
			}

			self.objectRequest( queryData );
		},

		/**
		 * [filterQuery description]
		 *
		 * @param  {[type]} event [description]
		 * @return {[type]}       [description]
		 */
		filterQuery: function( event ) {
			var self = event.data, object = $( event.target ).data( 'bp-filter' ), scope = 'all', filter = $( event.target ).val(), search_terms = '', template = null, extras = false;

			if ( ! object ) {
				return event;
			}

			if ( $( self.objectNavParent + ' [data-bp-object].selected' ).length ) {
				scope = $( self.objectNavParent + ' [data-bp-object].selected' ).data( 'bp-scope' );
			}

			if ( $( '#buddypress [data-bp-search="' + object + '"] input[type=search]' ).length ) {
				search_terms = $( '#buddypress [data-bp-search="' + object + '"] input[type=search]' ).val();
			}

			if ( 'friends' === object ) {
				object = 'members';
			}

			var objectData = self.getStorage( 'bp-' + object );

			// Notifications always need to start with Newest ones
			if ( undefined !== objectData.extras && 'notifications' !== object ) {
				extras = objectData.extras;
			}

			if ( 'members' === object ) {
				self.objectRequest(
					{
						object         : object,
						scope          : scope,
						filter         : filter,
						search_terms   : search_terms,
						page           : 1,
						extras         : extras,
						template       : template,
						member_type_id : $( '#buddypress [data-bp-member-type-filter="' + object + '"]' ).val()
					}
				);
			} else if ( 'groups' === object ) {
				self.objectRequest(
					{
						object       : object,
						scope        : scope,
						filter       : filter,
						search_terms : search_terms,
						page         : 1,
						extras       : extras,
						template     : template,
						group_type   : $( '#buddypress [data-bp-group-type-filter="' + object + '"]' ).val()
					}
				);
			} else {
				self.objectRequest(
					{
						object       : object,
						scope        : scope,
						filter       : filter,
						search_terms : search_terms,
						page         : 1,
						extras       : extras,
						template     : template
					}
				);
			}

		},

		/**
		 * [typeGroupFilterQuery description]
		 *
		 * @param  {[type]} event [description]
		 * @return {[type]}       [description]
		 */
		typeGroupFilterQuery: function( event ) {
			var self = event.data, object = $( event.target ).data( 'bp-group-type-filter' ), scope = 'all', filter = null, objectData = {}, extras = null, search_terms = '', template = null;

			if ( ! object ) {
				return event;
			}

			objectData = self.getStorage( 'bp-' + object );

			// Notifications always need to start with Newest ones
			if ( undefined !== objectData.extras && 'notifications' !== object ) {
				extras = objectData.extras;
			}

			if (  $( '#buddypress [data-bp-filter="' + object + '"]' ).length ) {
				if ( undefined !== objectData.filter ) {
					filter = objectData.filter;
					$( '#buddypress [data-bp-filter="' + object + '"] option[value="' + filter + '"]' ).prop( 'selected', true );
				} else if ( '-1' !== $( '#buddypress [data-bp-filter="' + object + '"]' ).val() && '0' !== $( '#buddypress [data-bp-filter="' + object + '"]' ).val() ) {
					filter = $( '#buddypress [data-bp-filter="' + object + '"]' ).val();
				}
			}

			if ( $( self.objectNavParent + ' [data-bp-object].selected' ).length ) {
				scope = $( self.objectNavParent + ' [data-bp-object].selected' ).data( 'bp-scope' );
			}

			if ( $( '#buddypress [data-bp-search="' + object + '"] input[type=search]' ).length ) {
				search_terms = $( '#buddypress [data-bp-search="' + object + '"] input[type=search]' ).val();
			}

			self.objectRequest(
				{
					object       : object,
					scope        : scope,
					filter       : filter,
					search_terms : search_terms,
					page         : 1,
					template     : template,
					extras       : extras,
					group_type   : $( '#buddypress [data-bp-group-type-filter="' + object + '"]' ).val()
				}
			);
		},

		/**
		 * [typeMemberFilterQuery description]
		 *
		 * @param  {[type]} event [description]
		 * @return {[type]}       [description]
		 */
		typeMemberFilterQuery: function( event ) {
			var self = event.data, object = $( event.target ).data( 'bp-member-type-filter' ), scope = 'all', filter = null, objectData = {}, extras = null, search_terms = '', template = null;

			if ( ! object ) {
				return event;
			}

			if ( 'friends' === object ) {
				object = 'members';
			}

			objectData = self.getStorage( 'bp-' + object );

			// Notifications always need to start with Newest ones
			if ( undefined !== objectData.extras && 'notifications' !== object ) {
				extras = objectData.extras;
			}

			if (  $( '#buddypress [data-bp-filter="' + object + '"]' ).length ) {
				if ( undefined !== objectData.filter ) {
					filter = objectData.filter;
					$( '#buddypress [data-bp-filter="' + object + '"] option[value="' + filter + '"]' ).prop( 'selected', true );
				} else if ( '-1' !== $( '#buddypress [data-bp-filter="' + object + '"]' ).val() && '0' !== $( '#buddypress [data-bp-filter="' + object + '"]' ).val() ) {
					filter = $( '#buddypress [data-bp-filter="' + object + '"]' ).val();
				}
			}

			if ( $( self.objectNavParent + ' [data-bp-object].selected' ).length ) {
				scope = $( self.objectNavParent + ' [data-bp-object].selected' ).data( 'bp-scope' );
			}

			if ( $( '#buddypress [data-bp-search="' + object + '"] input[type=search]' ).length ) {
				search_terms = $( '#buddypress [data-bp-search="' + object + '"] input[type=search]' ).val();
			}

			self.objectRequest(
				{
					object         : object,
					scope          : scope,
					filter         : filter,
					search_terms   : search_terms,
					page           : 1,
					template       : template,
					extras         : extras,
					member_type_id : $( '#buddypress [data-bp-member-type-filter="' + object + '"]' ).val()
				}
			);
		},

		/**
		 * [searchQuery description]
		 *
		 * @param  {[type]} event [description]
		 * @return {[type]}       [description]
		 */
		searchQuery: function( event ) {
			var self = event.data, object, scope = 'all', filter = null, template = null, search_terms = '', extras = false;

			if ( $( event.delegateTarget ).hasClass( 'no-ajax' ) || undefined === $( event.delegateTarget ).data( 'bp-search' ) ) {
				return event;
			}

			// Stop event propagation
			event.preventDefault();

			object       = $( event.delegateTarget ).data( 'bp-search' );
			filter       = $( '#buddypress' ).find( '[data-bp-filter="' + object + '"]' ).first().val();
			search_terms = $( event.delegateTarget ).find( 'input[type=search]' ).first().val();

			if ( $( self.objectNavParent + ' [data-bp-object]' ).length ) {
				scope = $( self.objectNavParent + ' [data-bp-object="' + object + '"].selected' ).data( 'bp-scope' );
			}

			var objectData = self.getStorage( 'bp-' + object );

			// Notifications always need to start with Newest ones
			if ( undefined !== objectData.extras && 'notifications' !== object ) {
				extras = objectData.extras;
			}

			self.objectRequest(
				{
					object       : object,
					scope        : scope,
					filter       : filter,
					search_terms : search_terms,
					page         : 1,
					extras       : extras,
					template     : template
				}
			);
		},

		/**
		 * [showSearchSubmit description]
		 *
		 * @param  {[type]} event [description]
		 * @return {[type]}       [description]
		 */
		showSearchSubmit: function( event ) {
			$( event.delegateTarget ).find( '[type=submit]' ).addClass( 'bp-show' );
			if ( $( '[type=submit]' ).hasClass( 'bp-hide' ) ) {
				$( '[type=submit]' ).removeClass( 'bp-hide' );
			}
		},

		/**
		 * [resetSearch description]
		 *
		 * @param  {[type]} event [description]
		 * @return {[type]}       [description]
		 */
		resetSearch: function( event ) {
			if ( ! $( event.target ).val() ) {
				$( event.delegateTarget ).submit();
			} else {
				$( event.delegateTarget ).find( '[type=submit]' ).show();
			}
		},

		/**
		 * [buttonAction description]
		 *
		 * @param  {[type]} event [description]
		 * @return {[type]}       [description]
		 */
		buttonAction: function( event ) {
			var self   = event.data, target = $( event.currentTarget ), action = target.data( 'bp-btn-action' ), nonceUrl = target.data( 'bp-nonce' ),
				item   = target.closest( '[data-bp-item-id]' ), item_id = item.data( 'bp-item-id' ), item_inner = target.closest( '.list-wrap' ),
				object = item.data( 'bp-item-component' ), nonce = '';

			// Simply let the event fire if we don't have needed values
			if ( ! action || ! item_id || ! object ) {
				return event;
			}

			// Stop event propagation
			event.preventDefault();

			if ( target.hasClass( 'bp-toggle-action-button' ) ) {

				// support for buddyboss theme for button actions and icons and texts
				if ( $( document.body ).hasClass( 'buddyboss-theme' ) && typeof target.data( 'balloon' ) !== 'undefined' ) {
					target.attr( 'data-balloon', target.data( 'title' ) );
				} else {
					target.text( target.data( 'title' ) );
				}

				target.removeClass( 'bp-toggle-action-button' );
				target.addClass( 'bp-toggle-action-button-clicked' );
				return false;
			}

			// check if only admin trying to leave the group
			if ( typeof target.data( 'only-admin' ) !== 'undefined' ) {
				if ( undefined !== BP_Nouveau.only_admin_notice ) {
					window.alert( BP_Nouveau.only_admin_notice );
				}
				return false;
			}

			if ( ( undefined !== BP_Nouveau[ action + '_confirm'] && false === window.confirm( BP_Nouveau[ action + '_confirm'] ) ) || target.hasClass( 'pending' ) ) {
				return false;
			}

			// Find the required wpnonce string.
			// if  button element set we'll have our nonce set on a data attr
			// Check the value & if exists split the string to obtain the nonce string
			// if no value, i.e false, null then the href attr is used.
			if ( nonceUrl ) {
				nonce = nonceUrl.split( '?_wpnonce=' );
				nonce = nonce[1];
			} else {
				nonce = self.getLinkParams( target.prop( 'href' ), '_wpnonce' );
			}

			// Unfortunately unlike groups
			// Connections actions does not match the wpnonce
			var friends_actions_map = {
				is_friend         : 'remove_friend',
				not_friends       : 'add_friend',
				pending           : 'withdraw_friendship',
				accept_friendship : 'accept_friendship',
				reject_friendship : 'reject_friendship'
			};

			if ( 'members' === object && undefined !== friends_actions_map[ action ] ) {
				action = friends_actions_map[ action ];
				object = 'friends';
			}

			var follow_actions_map = {
				not_following     : 'follow',
				following         : 'unfollow'
			};

			if ( 'members' === object && undefined !== follow_actions_map[ action ] ) {
				action = follow_actions_map[ action ];
				object = 'follow';
			}

			// Add a pending class to prevent queries while we're processing the action
			target.addClass( 'pending loading' );

			self.ajax(
				{
					action   : object + '_' + action,
					item_id  : item_id,
					_wpnonce : nonce
				},
				object,
				true
			).done(
				function( response ) {
					if ( false === response.success ) {
						  item_inner.prepend( response.data.feedback );
						  target.removeClass( 'pending loading' );
						  item.find( '.bp-feedback' ).fadeOut( 6000 );
					} else {
						  // Specific cases for groups
						if ( 'groups' === object ) {

							// Group's header button
							if ( undefined !== response.data.is_group && response.data.is_group ) {
								  return window.location.reload();
							}
						}

						// User main nav update friends counts
						if ( $( '#friends-personal-li' ).length ) {
							var friend_with_count 	 = $( '#friends-personal-li a span' );
							var friend_without_count = $( '#friends-personal-li a' );

							// Check friend count set
							if (undefined !== response.data.is_user && response.data.is_user && undefined !== response.data.friend_count) {
								// Check friend count > 0 then show the count span
								if (response.data.friend_count > 0) {
									if ((friend_with_count).length) {
										// Update count span
										$( friend_with_count ).html( response.data.friend_count );
									} else {
										// If no friend then add count span
										$( friend_without_count ).append( '<span class="count">' + response.data.friend_count + '</span>' );
									}
								} else {
									// If no friend then hide count span
									$( friend_with_count ).hide();
								}
							} else if (undefined !== response.data.friend_count) {
								if (response.data.friend_count > 0) {
									if ((friend_with_count).length) {
										// Update count span
										$( friend_with_count ).html( response.data.friend_count );
									} else {
										// If no friend then add count span
										$( friend_without_count ).append( '<span class="count">' + response.data.friend_count + '</span>' );
									}
								} else {
									// If no friend then hide count span
									$( friend_with_count ).hide();
								}
							}
						}

						// User's groups invitations screen & User's friend screens
						if ( undefined !== response.data.is_user && response.data.is_user ) {
							target.parent().html( response.data.feedback );
							item.fadeOut( 1500 );
							return;
						}

						// Update count
						if ( $( self.objectNavParent + ' [data-bp-scope="personal"]' ).length ) {
							var personal_count = Number( $( self.objectNavParent + ' [data-bp-scope="personal"] span' ).html() ) || 0;

							if ( -1 !== $.inArray( action, ['leave_group', 'remove_friend'] ) ) {
								personal_count -= 1;
							} else if ( -1 !== $.inArray( action, ['join_group'] ) ) {
								personal_count += 1;
							}

							if ( personal_count < 0 ) {
								personal_count = 0;
							}

							$( self.objectNavParent + ' [data-bp-scope="personal"] span' ).html( personal_count );
						}

						target.parent().replaceWith( response.data.contents );
					}
				}
			);
		},

		/**
		 * [buttonRevert description]
		 *
		 * @param  {[type]} event [description]
		 * @return {[type]}       [description]
		 */
		buttonRevert: function( event ) {
			var target = $( event.currentTarget );

			if ( target.hasClass( 'bp-toggle-action-button-clicked' ) && ! target.hasClass( 'loading' ) ) {

				// support for buddyboss theme for button actions and icons and texts
				if ( $( document.body ).hasClass( 'buddyboss-theme' ) && typeof target.data( 'balloon' ) !== 'undefined' ) {
					target.attr( 'data-balloon', target.data( 'title-displayed' ) );
				} else {
					target.text( target.data( 'title-displayed' ) ); // change text to displayed context
				}

				target.removeClass( 'bp-toggle-action-button-clicked' ); // remove class to detect event
				target.addClass( 'bp-toggle-action-button' ); // add class to detect event to confirm
			}
		},

		/**
		 * [buttonRevertAll description]
		 *
		 * @return {[type]}       [description]
		 */
		buttonRevertAll: function() {
			$.each(
				$( '#buddypress [data-bp-btn-action]' ),
				function() {
					if ( $( this ).hasClass( 'bp-toggle-action-button-clicked' ) && ! $( this ).hasClass( 'loading' ) ) {

						// support for buddyboss theme for button actions and icons and texts
						if ( $( document.body ).hasClass( 'buddyboss-theme' ) && typeof $( this ).data( 'balloon' ) !== 'undefined' ) {
							$( this ).attr( 'data-balloon', $( this ).data( 'title-displayed' ) );
						} else {
							$( this ).text( $( this ).data( 'title-displayed' ) ); // change text to displayed context
						}

						$( this ).removeClass( 'bp-toggle-action-button-clicked' ); // remove class to detect event
						$( this ).addClass( 'bp-toggle-action-button' ); // add class to detect event to confirm
						$( this ).trigger( 'blur' );
					}
				}
			);
		},

		/**
		 * [addRemoveInvite description]
		 *
		 * @param  {[type]} event [description]
		 * @return {[type]}       [description]
		 */
		addRemoveInvite: function(event) {

			var currentTarget = event.currentTarget, currentDataTable = $( currentTarget ).closest( 'tbody' );

			if ( $( currentTarget ).hasClass( 'field-actions-remove' ) ) {

				if ( $( this ).closest( 'tr' ).siblings().length > 1 ) {
					$( this ).closest( 'tr' ).remove();
					currentDataTable.find( '.field-actions-add.disabled' ).removeClass( 'disabled' );
				} else {

					return;

				}

			} else if ( $( currentTarget ).hasClass( 'field-actions-add' ) ) {

				if ( ! $( currentTarget ).hasClass( 'disabled' ) ) {

					var prev_data_row = $( this ).closest( 'tr' ).prev( 'tr' ).html();
					$( '<tr>' + prev_data_row + '</tr>' ).insertBefore( $( this ).closest( 'tr' ) );
					currentDataTable.find( 'tr' ).length > 20 ? $( currentTarget ).addClass( 'disabled' ) : ''; // Add Limit of 20

				} else {

					return;

				}

			}

			// reset the id of all inputs
			var data_rows = currentDataTable.find( 'tr:not(:last-child)' );
			$.each(
				data_rows,
				function(index){
					$( this ).find( '.field-name > input' ).attr( 'name','invitee[' + index + '][]' );
					$( this ).find( '.field-name > input' ).attr( 'id','invitee_' + index + '_title' );
					$( this ).find( '.field-email > input' ).attr( 'name','email[' + index + '][]' );
					$( this ).find( '.field-email > input' ).attr( 'id','email_' + index + '_email' );

				}
			);
		},

		/**
		 * [closeNotice description]
		 *
		 * @param  {[type]} event [description]
		 * @return {[type]}       [description]
		 */
		closeNotice: function( event ) {
			var closeBtn = $( event.currentTarget );

			event.preventDefault();

			// Make sure cookies are removed
			if ( 'clear' === closeBtn.data( 'bp-close' ) ) {
				if ( undefined !== $.cookie( 'bp-message' ) ) {
					$.removeCookie( 'bp-message' );
				}

				if ( undefined !== $.cookie( 'bp-message-type' ) ) {
					$.removeCookie( 'bp-message-type' );
				}
			}

			// @todo other cases...
			// Dismissing site-wide notices.
			if ( closeBtn.closest( '.bp-feedback' ).hasClass( 'bp-sitewide-notice' ) ) {
				bp.Nouveau.ajax(
					{
						action : 'messages_dismiss_sitewide_notice'
					},
					'messages'
				);
			}

			// Remove the notice
			closeBtn.closest( '.bp-feedback' ).remove();
		},

		paginateAction: function( event ) {
			var self  = event.data, navLink = $( event.currentTarget ), pagArg,
				scope = null, object, objectData, filter = null, search_terms = null, extras = null;

			pagArg = navLink.closest( '[data-bp-pagination]' ).data( 'bp-pagination' ) || null;

			if ( null === pagArg ) {
				return event;
			}

			event.preventDefault();

			object = $( event.delegateTarget ).data( 'bp-list' ) || null;

			// Set the scope & filter for local storage
			if ( null !== object ) {
				objectData = self.getStorage( 'bp-' + object );

				if ( undefined !== objectData.scope ) {
					scope = objectData.scope;
				}

				if ( undefined !== objectData.filter ) {
					filter = objectData.filter;
				}

				if ( undefined !== objectData.extras ) {
					extras = objectData.extras;
				}
			}

			// Set the scope & filter for session storage.
			if ( null !== object ) {
				objectData = self.getStorage( 'bp-' + object );
				if ( undefined !== objectData.scope ) {
					scope = objectData.scope;
				}
				if ( undefined !== objectData.filter ) {
					filter = objectData.filter;
				}
				if ( undefined !== objectData.extras ) {
					extras = objectData.extras;
				}
			}

			// Set the search terms
			if ( $( '#buddypress [data-bp-search="' + object + '"] input[type=search]' ).length ) {
				search_terms = $( '#buddypress [data-bp-search="' + object + '"] input[type=search]' ).val();
			}

			var queryData = {
				object       : object,
				scope        : scope,
				filter       : filter,
				search_terms : search_terms,
				extras       : extras,
				caller       : navLink.closest( '[data-bp-pagination]' ).hasClass( 'bottom' ) ? 'pag-bottom' : '',
				page         : self.getLinkParams( navLink.prop( 'href' ), pagArg ) || 1
			};

			// Request the page
			self.objectRequest( queryData );
		},
		registerPopUp: function() {
			if ( $( '.popup-modal-register' ).length ) {
				$( '.popup-modal-register' ).magnificPopup(
					{
						type: 'inline',
						preloader: false,
						fixedBgPos: true,
						fixedContentPos: true
					}
				);
			}
			if ( $( '.popup-modal-dismiss' ).length ) {
				$( '.popup-modal-dismiss' ).click(
					function (e) {
						e.preventDefault();
						$.magnificPopup.close();
					}
				);
			}
		},
		loginPopUp: function() {
			if ( $( '.popup-modal-login' ).length ) {
				$( '.popup-modal-login' ).magnificPopup(
					{
						type: 'inline',
						preloader: false,
						fixedBgPos: true,
						fixedContentPos: true
					}
				);
			}
			if ( $( '.popup-modal-dismiss' ).length ) {
				$( '.popup-modal-dismiss' ).click(
					function (e) {
						e.preventDefault();
						$.magnificPopup.close();
					}
				);
			}
		},

		/**
		 * Close emoji picker whenever clicked outside of emoji container
		 *
		 * @param event
		 */
		closePickersOnClick: function( event ) {
			var $targetEl = $( event.target );

			if ( ! _.isUndefined( BP_Nouveau.media ) &&
				! _.isUndefined( BP_Nouveau.media.emoji ) &&
				! $targetEl.closest( '.post-emoji' ).length &&
				! $targetEl.is( '.emojioneemoji,.emojibtn' )) {
				$( '.emojionearea-button.active' ).removeClass( 'active' );
			}
		},

		/**
		 * Close emoji picker on Esc press
		 *
		 * @param event
		 */
		closePickersOnEsc: function( event ) {
			if ( event.key === 'Escape' || event.keyCode === 27 ) {
				if ( ! _.isUndefined( BP_Nouveau.media ) &&
					! _.isUndefined( BP_Nouveau.media.emoji )) {
					$( '.emojionearea-button.active' ).removeClass( 'active' );
				}
			}
		},
		/**
		 * Lazy Load Images and iframes
		 *
		 * @param event
		 */
		lazyLoad: function( lazyTarget ) {
			var lazy = $( lazyTarget );
			if ( lazy.length ) {
				for ( var i = 0; i < lazy.length; i++ ) {
					var isInViewPort = false;
					try {
						if ( $( lazy[i] ).is( ':in-viewport' ) ) {
							isInViewPort = true;
						}
					} catch (err) {
						console.error( err.message );
						if ( ! isInViewPort && lazy[i].getBoundingClientRect().top <= (( window.innerHeight || document.documentElement.clientHeight ) + window.scrollY ) ) {
							isInViewPort = true;
						}
					}

					if ( isInViewPort && lazy[i].getAttribute( 'data-src' ) ) {
						lazy[i].src = lazy[i].getAttribute( 'data-src' );
						lazy[i].removeAttribute( 'data-src' );
						/* jshint ignore:start */
						$( lazy[i] ).on(
							'load',
							function () {
								$( this ).removeClass( 'lazy' );
							}
						);
						/* jshint ignore:end */

						// Inform other scripts about the lazy load.
						$( document ).trigger( 'bp_nouveau_lazy_load', { element: lazy[i] } );
					}
				}
			}
		}
	};

	// Launch BP Nouveau
	bp.Nouveau.start();

} )( bp, jQuery );

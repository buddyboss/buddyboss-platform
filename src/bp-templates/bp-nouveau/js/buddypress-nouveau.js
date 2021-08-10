/* global wp, bp, BP_Nouveau, JSON */
/* jshint devel: true */
/* jshint browser: true */
/* @version 3.0.0 */
window.wp = window.wp || {};
window.bp = window.bp || {};

( function ( exports, $ ) {

	// Bail if not set.
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
		start: function () {

			// Setup globals.
			this.setupGlobals();

			// Adjust Document/Forms properties.
			this.prepareDocument();

			// $.ajaxPrefilter( this.mediaPreFilter );

			// Init the BuddyPress objects.
			this.initObjects();

			// Set BuddyPress HeartBeat.
			this.setHeartBeat();

			// Listen to events ("Add hooks!").
			this.addListeners();

			// Toggle Grid/List View.
			this.switchGridList();

			// Email Invites popup revoke access.
			this.sendInvitesRevokeAccess();

			this.sentInvitesFormValidate();

			// Privacy Policy & Terms Popup on Register page.
			this.registerPopUp();

			// Privacy Policy Popup on Login page and Lost Password page.
			this.loginPopUp();

			// Report content popup.
			this.reportPopUp();
			this.reportActions();
			this.reportedPopup();

			// Toggle password text.
			this.togglePassword();

			// Legal agreement enable/disabled submit button.
			this.enableSubmitOnLegalAgreement();

			// Check for lazy images and load them also register scroll event to load on scroll.
			bp.Nouveau.lazyLoad( '.lazy' );
			$( window ).on(
				'scroll resize',
				function () {
					bp.Nouveau.lazyLoad( '.lazy' );
				}
			);
		},

		/**
		 * [setupGlobals description]
		 *
		 * @return {[type]} [description]
		 */
		setupGlobals: function () {

			this.ajax_request = null;

			// Object Globals.
			this.objects         = $.map(
				BP_Nouveau.objects,
				function ( value ) {
					return value;
				}
			);
			this.objectNavParent = BP_Nouveau.object_nav_parent;

			// HeartBeat Global.
			this.heartbeat = wp.heartbeat || false;

			// An object containing each query var.
			this.querystring = this.getLinkParams();
		},

		/**
		 * [prepareDocument description]
		 *
		 * @return {[type]} [description]
		 */
		prepareDocument: function () {

			// Remove the no-js class and add the js one.
			if ( $( 'body' ).hasClass( 'no-js' ) ) {
				$( 'body' ).removeClass( 'no-js' ).addClass( 'js' );
			}

			// Log Warnings into the console instead of the screen.
			if ( BP_Nouveau.warnings && 'undefined' !== typeof console && console.warn ) {
				$.each(
					BP_Nouveau.warnings,
					function ( w, warning ) {
						console.warn( warning );
					}
				);
			}

			// Remove the directory title if there's a widget containing it.
			if ( $( '.buddypress_object_nav .widget-title' ).length ) {
				var text = $( '.buddypress_object_nav .widget-title' ).html();

				$( 'body' ).find( '*:contains("' + text + '")' ).each(
					function ( e, element ) {
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
		getStorage: function ( type, property ) {

			var store = sessionStorage.getItem( type );

			if ( store ) {
				store = JSON.parse( store );
			} else {
				store = {};
			}

			if ( undefined !== property ) {
				return store[ property ] || false;
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
		setStorage: function ( type, property, value ) {

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
		getLinkParams: function ( url, param ) {

			var qs;
			if ( url ) {
				qs = ( -1 !== url.indexOf( '?' ) ) ? '?' + url.split( '?' )[ 1 ] : '';
			} else {
				qs = document.location.search;
			}

			if ( ! qs ) {
				return null;
			}

			var params = qs.replace( /(^\?)/, '' ).split( '&' ).map(
				function ( n ) {
					return n = n.split( '=' ), this[ n[ 0 ] ] = n[ 1 ], this;
				}.bind( {} )
			)[ 0 ];

			if ( param ) {
				return params[ param ];
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
		urlDecode: function ( qv, chars ) {

			var specialChars = chars || {
				amp: '&',
				lt: '<',
				gt: '>',
				quot: '"',
				'#039': '\''
			};

			return decodeURIComponent( qv.replace( /\+/g, ' ' ) ).replace(
				/&([^;]+);/g,
				function ( v, q ) {
					return specialChars[ q ] || '';
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
		ajax: function ( post_data, object, button ) {

			if ( this.ajax_request && typeof button === 'undefined' ) {
				this.ajax_request.abort();
			}

			// Extend posted data with stored data and object nonce.
			var postData = $.extend( {}, bp.Nouveau.getStorage( 'bp-' + object ), { nonce: BP_Nouveau.nonces[ object ] }, post_data );

			if ( undefined !== BP_Nouveau.customizer_settings ) {
				postData.customized = BP_Nouveau.customizer_settings;
			}

			/**
			 * Moderation bypass for admin
			 */
			if ( undefined !== BP_Nouveau.modbypass ) {
				postData.modbypass = BP_Nouveau.modbypass;
			}

			this.ajax_request = $.post( BP_Nouveau.ajaxurl, postData, 'json' );

			return this.ajax_request;
		},

		inject: function ( selector, content, method ) {

			if ( ! $( selector ).length || ! content ) {
				return;
			}

			/**
			 * How the content should be injected in the selector
			 *
			 * possible methods are.
			 * - reset: the selector will be reset with the content
			 * - append:  the content will be added after selector's content
			 * - prepend: the content will be added before selector's content
			 */
			method = method || 'reset';
			if ( 'append' === method ) {
				$( selector ).append( content ).find( 'li.activity-item' ).each( this.hideSingleUrl );
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
		hideSingleUrl: function () {
			var _findtext  = $( this ).find( '.activity-inner > p' ).removeAttr( 'br' ).removeAttr( 'a' ).text();
			var _url       = '',
				_newString = '',
				startIndex = '',
				_is_exist  = 0;
			if ( 0 <= _findtext.indexOf( 'http://' ) ) {
				startIndex = _findtext.indexOf( 'http://' );
				_is_exist  = 1;
			} else if ( 0 <= _findtext.indexOf( 'https://' ) ) {
				startIndex = _findtext.indexOf( 'https://' );
				_is_exist  = 1;
			} else if ( 0 <= _findtext.indexOf( 'www.' ) ) {
				startIndex = _findtext.indexOf( 'www' );
				_is_exist  = 1;
			}
			if ( 1 === _is_exist ) {
				for ( var i = startIndex; i < _findtext.length; i++ ) {
					if ( _findtext[ i ] === ' ' || _findtext[ i ] === '\n' ) {
						break;
					} else {
						_url += _findtext[ i ];
					}
				}

				if ( _url !== '' ) {
					_newString = $.trim( _findtext.replace( _url, '' ) );
				}
				if ( 0 >= _newString.length ) {
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
		objectRequest: function ( data ) {

			var postdata = {}, self = this;

			data = $.extend(
				{
					object: '',
					scope: null,
					filter: null,
					target: '#buddypress [data-bp-list]',
					search_terms: '',
					page: 1,
					extras: null,
					caller: null,
					template: null,
					method: 'reset'
				},
				data
			);

			// Do not request if we don't have the object or the target to inject results into.
			if ( ! data.object || ! data.target ) {
				return;
			}

			// prevent activity response to append to media model activity list element.
			if ( data.object == 'activity' && data.target == '#buddypress [data-bp-list] ul.bp-list' ) {
				data.target = '#buddypress [data-bp-list] ul.bp-list:not(#bb-media-model-container ul.bp-list)';
			}

			// if object is members, activity, media, document and object nav does not exists fallback to scope = all.
			if ( [ 'members', 'activity', 'media', 'document' ].includes( data.object ) && ! $( this.objectNavParent + ' [data-bp-scope="' + data.scope + '"]' ).length ) {
				data.scope = 'all';
			}

			// Prepare the search terms for the request.
			if ( data.search_terms ) {
				data.search_terms = data.search_terms.replace( /</g, '&lt;' ).replace( />/g, '&gt;' );
			}

			// Set session's data.
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
				function () {
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
				data.object   = 'groups';
				data.template = 'group_subgroups';
			} else if ( 'notifications' === data.object ) {
				data.object   = 'members';
				data.template = 'member_notifications';
			}

			postdata = $.extend(
				{
					action: data.object + '_filter'
				},
				data
			);

			return this.ajax( postdata, data.object ).done(
				function ( response ) {
					if ( false === response.success || _.isUndefined( response.data ) ) {
						return;
					}

					if ( $( 'body.group-members.members.buddypress' ).length && ! _.isUndefined( response.data ) && ! _.isUndefined( response.data.count ) ) {
						$( 'body.group-members.members.buddypress ul li#members-groups-li' ).find( 'span' ).text( response.data.count );
					}

					$( self.objectNavParent + ' [data-bp-scope="' + data.scope + '"]' ).removeClass( 'loading' );
					$( self.objectNavParent + ' [data-bp-scope="' + data.scope + '"]' ).find( 'span' ).text( '' );

					if ( ! _.isUndefined( response.data ) && ! _.isUndefined( response.data.count ) ) {
						$( self.objectNavParent + ' [data-bp-scope="' + data.scope + '"]' ).find( 'span' ).text( response.data.count );
					}

					if ( ! _.isUndefined( response.data ) && ! _.isUndefined( response.data.scopes ) ) {
						for ( var i in response.data.scopes ) {
							$( self.objectNavParent + ' [data-bp-scope="' + i + '"]' ).find( 'span' ).text( response.data.scopes[ i ] );
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
								function () {
									$( data.target ).fadeOut(
										100,
										function () {
											self.inject( this, response.data.contents, data.method );
											$( this ).fadeIn( 100 );

											// Inform other scripts the list of objects has been refreshed.
											$( data.target ).trigger( 'bp_ajax_request', $.extend( data, { response: response.data } ) );

											// Lazy Load Images.
											if ( bp.Nouveau.lazyLoad ) {
												setTimeout(
													function () { // Waiting to load dummy image.
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
								function () {
									self.inject( this, response.data.contents, data.method );
									$( this ).fadeIn( 100 );

									// Inform other scripts the list of objects has been refreshed.
									$( data.target ).trigger( 'bp_ajax_request', $.extend( data, { response: response.data } ) );

									// Lazy Load Images.
									if ( bp.Nouveau.lazyLoad ) {
										setTimeout(
											function () { // Waiting to load dummy image.
												bp.Nouveau.lazyLoad( '.lazy' );
											},
											1000
										);
									}
								}
							);
						}
					}
					setTimeout(
						function () { // Waiting to load dummy image.
							self.reportPopUp();
							self.reportedPopup();
						},
						1000
					);
				}
			);
		},

		/**
		 * [initObjects description]
		 *
		 * @return {[type]} [description]
		 */
		initObjects: function () {
			var self   = this, objectData = {}, queryData = {}, scope = 'all', search_terms = '', extras = null,
				filter = null;

			$.each(
				this.objects,
				function ( o, object ) {
					// Continue when ajax is blocked for object request.
					if ( $( '#buddypress [data-bp-list="' + object + '"][data-ajax="false"]' ).length ) {
						return;
					}

					objectData = self.getStorage( 'bp-' + object );

					var typeType = window.location.hash.substr( 1 );
					if ( undefined !== typeType && typeType == 'following' ) {
						scope = typeType;
					} else if ( undefined !== objectData.scope ) {
						scope = objectData.scope;
					}

					// Notifications always need to start with Newest ones.
					if ( undefined !== objectData.extras && 'notifications' !== object ) {
						extras = objectData.extras;
					}

					if ( $( '#buddypress [data-bp-filter="' + object + '"]' ).length ) {
						if ( undefined !== objectData.filter ) {
							filter = objectData.filter;
							$( '#buddypress [data-bp-filter="' + object + '"] option[value="' + filter + '"]' ).prop( 'selected', true );
						} else if ( '-1' !== $( '#buddypress [data-bp-filter="' + object + '"]' ).val() && '0' !== $( '#buddypress [data-bp-filter="' + object + '"]' ).val() ) {
							filter = $( '#buddypress [data-bp-filter="' + object + '"]' ).val();
						}
					}

					if ( $( this.objectNavParent + ' [data-bp-object="' + object + '"]' ).length ) {
						$( this.objectNavParent + ' [data-bp-object="' + object + '"]' ).each(
							function () {
								$( this ).removeClass( 'selected' );
							}
						);

						$( this.objectNavParent + ' [data-bp-scope="' + object + '"], #object-nav li.current' ).addClass( 'selected' );
					}

					// Check the querystring to eventually include the search terms.
					if ( null !== self.querystring ) {
						if ( undefined !== self.querystring[ object + '_search' ] ) {
							search_terms = self.querystring[ object + '_search' ];
						} else if ( undefined !== self.querystring.s ) {
							search_terms = self.querystring.s;
						}

						if ( search_terms ) {
							$( '#buddypress [data-bp-search="' + object + '"] input[type=search]' ).val( search_terms );
						}
					}

					if ( $( '#buddypress [data-bp-list="' + object + '"]' ).length ) {
						queryData = {
							object: object,
							scope: scope,
							filter: filter,
							search_terms: search_terms,
							extras: extras
						};

						if ( $( '#buddypress [data-bp-member-type-filter="' + object + '"]' ).length ) {
							queryData.member_type_id = $( '#buddypress [data-bp-member-type-filter="' + object + '"]' ).val();
						} else if ( $( '#buddypress [data-bp-group-type-filter="' + object + '"]' ).length ) {
							queryData.group_type = $( '#buddypress [data-bp-group-type-filter="' + object + '"]' ).val();
						}

						// Populate the object list.
						self.objectRequest( queryData );
					}
				}
			);
		},

		/**
		 * [setHeartBeat description]
		 */
		setHeartBeat: function () {
			if ( typeof BP_Nouveau.pulse === 'undefined' || ! this.heartbeat ) {
				return;
			}

			this.heartbeat.interval( Number( BP_Nouveau.pulse ) );

			// Extend "send" with BuddyPress namespace.
			$.fn.extend(
				{
					'heartbeat-send': function () {
						return this.bind( 'heartbeat-send' );
					}
				}
			);

			// Extend "tick" with BuddyPress namespace.
			$.fn.extend(
				{
					'heartbeat-tick': function () {
						return this.bind( 'heartbeat-tick' );
					}
				}
			);
		},

		/** Event Listeners ***********************************************************/

		/**
		 * [addListeners description]
		 */
		addListeners: function () {
			// Disabled inputs.
			$( '[data-bp-disable-input]' ).on( 'change', this.toggleDisabledInput );

			// Refreshing.
			$( this.objectNavParent + ' .bp-navs' ).on( 'click', 'a', this, this.scopeQuery );

			// Filtering.
			$( document ).on( 'change', '#buddypress [data-bp-filter]', this, this.filterQuery );

			// Group Type & Member Type Filter.
			$( document ).on( 'change', '#buddypress [data-bp-group-type-filter]', this, this.typeGroupFilterQuery );
			$( document ).on( 'change', '#buddypress [data-bp-member-type-filter]', this, this.typeMemberFilterQuery );

			// Searching.
			$( '#buddypress [data-bp-search]' ).on( 'submit', 'form', this, this.searchQuery );
			$( '#buddypress [data-bp-search] form' ).on( 'search', 'input[type=search]', this.resetSearch );

			// Buttons.
			$( '#buddypress [data-bp-list], #buddypress #item-header, #buddypress.bp-shortcode-wrap .dir-list' ).on( 'click', '[data-bp-btn-action]', this, this.buttonAction );
			$( '#buddypress [data-bp-list], #buddypress #item-header, #buddypress.bp-shortcode-wrap .dir-list' ).on( 'blur', '[data-bp-btn-action]', this, this.buttonRevert );
			$( document ).on( 'click', '#buddypress table.invite-settings .field-actions .field-actions-remove, #buddypress table.invite-settings .field-actions-add', this, this.addRemoveInvite );

			$( document ).on( 'keyup', this, this.keyUp );

			// Close notice.
			$( '[data-bp-close]' ).on( 'click', this, this.closeNotice );

			// Pagination.
			$( '#buddypress [data-bp-list]' ).on( 'click', '[data-bp-pagination] a', this, this.paginateAction );

			$( document ).on( 'click', this.closePickersOnClick );
			document.addEventListener( 'keydown', this.closePickersOnEsc );

			$( document ).on( 'click', '#item-header a.position-change-cover-image, .header-cover-reposition-wrap a.cover-image-save, .header-cover-reposition-wrap a.cover-image-cancel', this.coverPhotoCropper );

			$( document ).on( 'click', '#cover-photo-alert .bb-model-close-button', this.coverPhotoCropperAlert );

			//More Option Dropdown
			$( document ).on( 'click', this.toggleMoreOption.bind( this ) );
			$( document ).on( 'heartbeat-send', this.bbHeartbeatSend.bind( this ) );
			$( document ).on( 'heartbeat-tick', this.bbHeartbeatTick.bind( this ) );

			// Create event for remove single notification.
			bp.Nouveau.notificationRemovedAction();
			// Remove all notifications.
			bp.Nouveau.removeAllNotification();
			// Set title tag.
			bp.Nouveau.setTitle();
		},

		/**
		 * [heartbeatSend description]
		 *
		 * @param  {[type]} event [description]
		 * @param  {[type]} data  [description]
		 * @return {[type]}       [description]
		 */
		 bbHeartbeatSend: function( event, data ) {
			data.onScreenNotifications = true;

			// Add an heartbeat send event to possibly any BuddyPress pages
			$( '#buddypress' ).trigger( 'bb_heartbeat_send', data );
		},

		/**
		 * [heartbeatTick description]
		 *
		 * @param  {[type]} event [description]
		 * @param  {[type]} data  [description]
		 * @return {[type]}       [description]
		 */
		bbHeartbeatTick: function(  event, data ) {
            // Inject on-screen notification. 
			bp.Nouveau.bbInjectOnScreenNotifications(  event, data );
		},

		/**
		 * Injects all unread notifications
		 */
		bbInjectOnScreenNotifications: function( event, data ) {
			var enable = $( '.bb-onscreen-notification' ).data( 'enable' );
			if (  enable != '1' ) {
				return;
			}

			if ( typeof data.on_screen_notifications === 'undefined' && data.on_screen_notifications === '') {
				return;
			}

			var wrap          = $( '.bb-onscreen-notification' ),
			    list          = wrap.find( '.notification-list' ),
			    removedItems  = list.data('removed-items'),
				animatedItems = list.data('animated-items'),
				newItems      = [],
			    notifications = $( $.parseHTML( '<ul>'+data.on_screen_notifications+'</ul>' ) );
			
			// Ignore all view notifications.
			$.each( removedItems, function( index, id ) {
				var removedItem = notifications.find( '[data-notification-id='+id+']' );

				if ( removedItem.length ) {
					removedItem.closest( '.read-item' ).remove();
				}
			} );

			var appendItems = notifications.find( '.read-item' );

			appendItems.each( function( index, item ) {
				var id = $( item ).find( '.actions .action-close' ).data( 'notification-id' );

				if ( '-1' == $.inArray( id, animatedItems ) ) {
					$( item ).addClass( 'pull-animation' );
					animatedItems.push( id );
					newItems.push( id );
				} else {
					$( item ).removeClass( 'pull-animation' );
				}
			} );

			// Remove brder when new item is appear.
			if ( newItems.length ) {
				appendItems.each( function( index, item ) {
					var id = $( item ).find( '.actions .action-close' ).data( 'notification-id' );
					if ( '-1' == $.inArray( id, newItems ) ) {
						$( item ).removeClass( 'recent-item' );
						var borderItems  = list.data( 'border-items' );
						borderItems.push( id );
						list.attr( 'data-border-items', JSON.stringify( borderItems ) );

					} 
				} );
			}

			// Store animated notification id in 'animated-items' data attribute.
			list.attr( 'data-animated-items', JSON.stringify( animatedItems ) );

			if ( ! appendItems.length ) {
				return;
			}

			// Show all notificaitons.
			wrap.removeClass( 'close-all-items' );

			// Set class 'bb-more-item' in item when more than three notifications.
			appendItems.eq(2).nextAll().addClass( 'bb-more-item' );
			
			if ( appendItems.length > 3 ) {
				list.addClass( 'bb-more-than-3' );
			} else {
				list.removeClass( 'bb-more-than-3' );
			}

			wrap.show();
			list.empty().html( appendItems );

			// Clear all button visibility status.
			bp.Nouveau.visibilityOnScreenClearButton();
			// Remove notification border.
			bp.Nouveau.notificationBorder();
			// Notification auto hide.
			bp.Nouveau.notificationAutoHide();
			// Notification on broser tab.
			bp.Nouveau.browserTabFlashNotification();
			// Browser tab notification count.
			bp.Nouveau.browserTabCountNotification();
		},

		/**
		 * Remove notification border.
		 */
		notificationBorder: function() {
			var wrap         = $( '.bb-onscreen-notification' ),
				list         = wrap.find( '.notification-list' ),
				borderItems  = list.data( 'border-items' );
				//newItems     = [];

			// Remove border for single notificaiton after 30s later.
			list.find( '.read-item' ).each( function( index, item ) {
				var id = $( item ).find( '.actions .action-close' ).data( 'notification-id' );
				
				if ( '-1' != $.inArray( id, borderItems ) ) {
					return;
				}

				$( item ).addClass( 'recent-item' );
			} );

			// Store removed notification id in 'auto-removed-items' data attribute.
			list.attr( 'data-border-items', JSON.stringify( borderItems ) );
		},

		/**
		 * Notification count in browser tab.
		 */
		browserTabCountNotification: function() {
			var wrap         = $( '.bb-onscreen-notification' ),
				list         = wrap.find( '.notification-list' ),
				items        = list.find( '.read-item' ),
				titleTag     = $('html').find( 'title' ),
				title        = wrap.data( 'title-tag' );

			if ( items.length > 0 ) {
				titleTag.text( '('+items.length+') ' + title );
			} else {
				titleTag.text( title );
			}
		},

		/**
		 * Inject notification on browser tab.
		 */
		browserTabFlashNotification: function() {
			var wrap = $( '.bb-onscreen-notification' ),
				broserTab = wrap.data( 'broser-tab' );
			
			// Check notification broser tab settings option.
			if ( 1 != broserTab ) {
				return;
			}

			if ( window.bbFlashNotification ) {
				clearInterval( window.bbFlashNotification );
			}

			if ( document.hidden ) {
				window.bbFlashNotification = setInterval( bp.Nouveau.flashTitle, 2000 );
			} 
		},

		/**
		 * Flash browser tab notification title.
		 */
		flashTitle: function() {
			var wrap = $( '.bb-onscreen-notification' ),
				list = wrap.find( '.notification-list' );

			var items        = list.find( '.read-item' ),
				notification = items.first().find('.notification-content .bb-full-link a').text(),
				titleTag     = $('html').find( 'title' ),
				title        = wrap.attr( 'data-title-tag' ),
				flashStatus  = wrap.attr( 'data-flash-status' ),
				flashItems   = list.data( 'flash-items' );

			if ( ! document.hidden ) {
				items.each( function( index, item ) {
					var id = $( item ).find( '.actions .action-close' ).attr( 'data-notification-id' );

					if ( '-1' == $.inArray( id, flashItems ) ) {
						flashItems.push( id );
					}
				} );

				list.attr( 'data-flash-items', JSON.stringify( flashItems ) );
			}

			if ( ( ! document.hidden && window.bbFlashNotification ) || items.length <= 0 ) {
				clearInterval( window.bbFlashNotification );
				wrap.attr( 'data-flash-status', 'default_title' );
				titleTag.text( title );
				return;
			}
			
			if ( 'default_title' === flashStatus ) {
				titleTag.text( '('+items.length+') ' + title );
				var id = items.first().find( '.actions .action-close' ).attr( 'data-notification-id' );

				if ( '-1' == $.inArray( id, flashItems ) ) {
					wrap.attr( 'data-flash-status', 'notification' );
				}
			} else if ( 'notification' === flashStatus ) {
				titleTag.text( notification );
				wrap.attr( 'data-flash-status', 'default_title' );
			}
		},

		/**
		 * Inject notification autohide.
		 */
		notificationAutoHide: function() {
			var wrap         = $( '.bb-onscreen-notification' ),
				list         = wrap.find( '.notification-list' ),
				removedItems = list.data('auto-removed-items'),
				visibility   = wrap.data( 'visibility' );

			// Check notification autohide settings option.
			if ( visibility === 'never' ) {
				return;
			}

			var hideAfter = parseInt( visibility );

			if ( hideAfter <= 0 ) {
				return;
			}

			// Remove single notification according setting option time.
			list.find( '.read-item' ).each( function( index, item ) {
				var id = $( item ).find( '.actions .action-close' ).data( 'notification-id' );
				
				if ( '-1' != $.inArray( id, removedItems ) ) {
					return;
				}

				removedItems.push( id );

				setTimeout( function() {
					if ( list.find( '.actions .action-close[data-notification-id='+id+']' ).length ) {
						list.find( '.actions .action-close[data-notification-id='+id+']' ).trigger( 'click' );
					}
				}, 1000*hideAfter );
			} );

			// Store removed notification id in 'auto-removed-items' data attribute.
			list.attr( 'data-auto-removed-items', JSON.stringify( removedItems ) );
		},

		/**
		 * Click event for remove single notification.
		 */
		notificationRemovedAction: function() {
			$('.bb-onscreen-notification .notification-list').on('click', '.action-close', function(e) {
				e.preventDefault();
				bp.Nouveau.removeOnScreenNotification( this );
			});
		},

		/**
		 * Remove single notification.
		 */
		removeOnScreenNotification: function( self ) {
			var list         = $(self).closest( '.notification-list' ),
				item         = $(self).closest( '.read-item' ),
				id           = $(self).data( 'notification-id' ),
				removedItems = list.data( 'removed-items' );
				
			item.addClass('close-item');
			
			setTimeout(function() {
				removedItems.push(id);

				// Set the removed notification id in data-removed-items attribute. 
				list.attr( 'data-removed-items', JSON.stringify( removedItems ) );
				item.remove();
				bp.Nouveau.browserTabCountNotification();
				bp.Nouveau.visibilityOnScreenClearButton();

				// After removed get, rest of the notification.
				var items = list.find( '.read-item' );

				if ( ! items.length ) {
					list.closest( '.bb-onscreen-notification' ).hide();
					return;
				}

				if ( items.length < 4 ) {
					list.removeClass( 'bb-more-than-3' );
				}

				//items.first().addClass( 'recent-item' );
				items.slice(0, 3).removeClass( 'bb-more-item' );
				
			}, 500 );
		},

		/**
		 * Remove all notifications.
		 */
		removeAllNotification: function() {
			$('.bb-onscreen-notification .bb-remove-all-notification').on('click', '.action-close', function(e) {
				e.preventDefault();
				
				var list         = $(this).closest( '.bb-onscreen-notification' ).find( '.notification-list' ),
					items        = list.find( '.read-item' ),
					removedItems = list.data( 'removed-items' );   	
				
				// Collect all removed notification ids. 
				items.each( function( index, item ) {
					var id = $(item).find('.actions .action-close').data( 'notification-id' );
					
					if ( id ) {
						removedItems.push( id );
					}
				} );

				// Set all removed notification ids in data-removed-items attribute. 
				list.attr( 'data-removed-items', JSON.stringify( removedItems ) );
				items.remove();
				bp.Nouveau.browserTabCountNotification();
				bp.Nouveau.visibilityOnScreenClearButton();
				list.closest( '.bb-onscreen-notification' ).addClass('close-all-items');
				$('.bb-onscreen-notification').fadeOut(200);
				list.removeClass( 'bb-more-than-3' );
			});
		},

		/**
		 * Set title tag in notification data attribute.
		 */
		setTitle: function() {
			var title = $('html').find( 'title' ).text();
			$('.bb-onscreen-notification').attr( 'data-title-tag', title );
		},

		/**
		 * Set title tag in notification data attribute.
		 */
		visibilityOnScreenClearButton: function() {
			var wrap  = $( '.bb-onscreen-notification' ),
				list  = wrap.find( '.notification-list' ),
				items = list.find( '.read-item' );

			if ( items.length > 1 ) {
				wrap.removeClass('single-notification');
				wrap.addClass('active-button');				
				wrap.find( '.bb-remove-all-notification .action-close' ).fadeIn(600);
			} else {
				wrap.addClass('single-notification');
				wrap.removeClass('active-button');				
				wrap.find( '.bb-remove-all-notification .action-close' ).fadeOut(200);
			}
		},

		/**
		 * [switchGridList description]
		 *
		 * @return {[type]} [description]
		 */
		switchGridList: function () {
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
				function ( e ) {
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

		sentInvitesFormValidate: function () {

			if ( $( 'body.send-invites #send-invite-form #member-invites-table' ).length ) {

				$( 'body.send-invites #send-invite-form' ).submit(
					function () {

						var prevent             = false;
						var title               = '';
						var id                  = '';
						var email               = '';
						var id_lists            = [];
						var all_lists           = [];
						var alert_message       = '';
						var inviteMessage       = 0;
						var inviteSubject       = 0;
						var subject             = '';
						var subjectErrorMessage = '';
						var message             = '';
						var messageErrorMessage = '';
						var emailRegex          = /^([a-zA-Z0-9_.+-])+\@(([a-zA-Z0-9-])+\.)+([a-zA-Z0-9]{2,4})+$/;
						var emptyName           = $( 'body.send-invites #send-invite-form #error-message-empty-name-field' ).val();
						var invalidEmail        = $( 'body.send-invites #send-invite-form #error-message-invalid-email-address-field' ).val();

						alert_message = $( 'body.send-invites #send-invite-form #error-message-required-field' ).val();
						inviteSubject = $( 'body.send-invites #send-invite-form #error-message-empty-subject-field' ).length;
						inviteMessage = $( 'body.send-invites #send-invite-form #error-message-empty-body-field' ).length;

						if ( 1 === inviteSubject ) {
							subject             = $( 'body.send-invites #send-invite-form #bp-member-invites-custom-subject' ).val();
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
								if ( ! confirm( bothFieldsErrorMessage ) ) {
									return false;
								}
							} else if ( '' !== subject && '' === message ) {
								if ( ! confirm( messageErrorMessage ) ) {
									return false;
								}
							} else if ( '' === subject && '' !== message ) {
								if ( ! confirm( subjectErrorMessage ) ) {
									return false;
								}
							}

						} else if ( 0 === inviteSubject && 1 === inviteMessage ) {
							if ( '' === message ) {
								if ( ! confirm( messageErrorMessage ) ) {
									return false;
								}
							}
						} else if ( 1 === inviteSubject && 0 === inviteMessage ) {
							if ( '' === subject ) {
								if ( ! confirm( subjectErrorMessage ) ) {
									return false;
								}
							}
						}

						$( 'body.send-invites #send-invite-form #member-invites-table > tbody  > tr' ).each(
							function () {
								$( this ).find( 'input[type="text"]' ).removeAttr( 'style' );
								$( this ).find( 'input[type="email"]' ).removeAttr( 'style' );
							}
						);

						$( 'body.send-invites #send-invite-form #member-invites-table > tbody  > tr' ).each(
							function () {

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

						if ( id_lists.length === 0 ) {

						} else {
							id_lists.forEach(
								function ( item ) {
									$( '#' + item ).attr( 'style', 'border:1px solid #ef3e46' );
									if ( item.indexOf( 'email_' ) !== -1 ) {
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

						if ( all_lists.length === 0 ) {
							var name       = $( '#invitee_0_title' ).val();
							var emailField = $( '#email_0_email' ).val();
							if ( '' === name && '' === emailField ) {
								$( '#invitee_0_title' ).attr( 'style', 'border:1px solid #ef3e46' );
								$( '#invitee_0_title' ).focus();
								$( '#email_0_email' ).attr( 'style', 'border:1px solid #ef3e46' );
								return false;
							} else if ( '' !== name && '' === emailField ) {
								$( '#email_0_email' ).attr( 'style', 'border:1px solid #ef3e46' );
								$( '#email_0_email' ).focus();
								return false;
							}
							if ( ! emailRegex.test( emailField ) ) {
								$( '#email_0_email' ).attr( 'style', 'border:1px solid #ef3e46' );
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

		sendInvitesRevokeAccess: function () {

			if ( $( 'body.sent-invites #member-invites-table' ).length ) {

				$( 'body.sent-invites #member-invites-table tr td span a.revoked-access' ).click(
					function ( e ) {
						e.preventDefault();

						var alert_message = $( this ).attr( 'data-name' );
						var id            = $( this ).attr( 'id' );
						var action        = $( this ).attr( 'data-revoke-access' );

						if ( confirm( alert_message ) ) {
							$.ajax(
								{
									url: action,
									type: 'post',
									data: {
										item_id: id
									}, success: function () {
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
		toggleDisabledInput: function () {

			// Fetch the data attr value (id).
			// This a pro tem approach due to current conditions see.
			// https://github.com/buddypress/next-template-packs/issues/180.
			var disabledControl = $( this ).attr( 'data-bp-disable-input' );

			if ( $( disabledControl ).prop( 'disabled', true ) && ! $( this ).hasClass( 'enabled' ) ) {
				$( this ).addClass( 'enabled' ).removeClass( 'disabled' );
				$( disabledControl ).prop( 'disabled', false );

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
		keyUp: function ( event ) {
			var self = event.data;
			if ( event.keyCode === 27 ) { // escape key.
				self.buttonRevertAll();
			}
		},

		/**
		 * [queryScope description]
		 *
		 * @param  {[type]} event [description]
		 * @return {[type]}       [description]
		 */
		scopeQuery: function ( event ) {
			var self         = event.data, target = $( event.currentTarget ).parent(), scope = 'all', object, filter = null,
				search_terms = '', extras = null, queryData = {};

			if ( target.hasClass( 'no-ajax' ) || $( event.currentTarget ).hasClass( 'no-ajax' ) || ! target.attr( 'data-bp-scope' ) ) {
				return event;
			}

			scope  = target.data( 'bp-scope' );
			object = target.data( 'bp-object' );

			if ( ! scope || ! object ) {
				return event;
			}

			// Stop event propagation.
			event.preventDefault();

			var objectData = self.getStorage( 'bp-' + object );

			// Notifications always need to start with Newest ones.
			if ( undefined !== objectData.extras && 'notifications' !== object ) {
				extras = objectData.extras;
			}

			filter = $( '#buddypress' ).find( '[data-bp-filter="' + object + '"]' ).first().val();

			if ( $( '#buddypress [data-bp-search="' + object + '"] input[type=search]' ).length ) {
				search_terms = $( '#buddypress [data-bp-search="' + object + '"] input[type=search]' ).val();
			}

			// Remove the New count on dynamic tabs.
			if ( target.hasClass( 'dynamic' ) ) {
				target.find( 'a span' ).html( '' );
			}

			queryData = {
				object: object,
				scope: scope,
				filter: filter,
				search_terms: search_terms,
				page: 1,
				extras: extras
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
		filterQuery: function ( event ) {
			var self   = event.data, object = $( event.target ).data( 'bp-filter' ), scope = 'all',
				filter = $( event.target ).val(), search_terms = '', template = null, extras = false;

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

			// Notifications always need to start with Newest ones.
			if ( undefined !== objectData.extras && 'notifications' !== object ) {
				extras = objectData.extras;
			}

			if ( 'members' === object ) {
				self.objectRequest(
					{
						object: object,
						scope: scope,
						filter: filter,
						search_terms: search_terms,
						page: 1,
						extras: extras,
						template: template,
						member_type_id: $( '#buddypress [data-bp-member-type-filter="' + object + '"]' ).val()
					}
				);
			} else if ( 'groups' === object ) {
				self.objectRequest(
					{
						object: object,
						scope: scope,
						filter: filter,
						search_terms: search_terms,
						page: 1,
						extras: extras,
						template: template,
						group_type: $( '#buddypress [data-bp-group-type-filter="' + object + '"]' ).val()
					}
				);
			} else {
				self.objectRequest(
					{
						object: object,
						scope: scope,
						filter: filter,
						search_terms: search_terms,
						page: 1,
						extras: extras,
						template: template
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
		typeGroupFilterQuery: function ( event ) {
			var self   = event.data, object = $( event.target ).data( 'bp-group-type-filter' ), scope = 'all',
				filter = null, objectData = {}, extras = null, search_terms = '', template = null;

			if ( ! object ) {
				return event;
			}

			objectData = self.getStorage( 'bp-' + object );

			// Notifications always need to start with Newest ones.
			if ( undefined !== objectData.extras && 'notifications' !== object ) {
				extras = objectData.extras;
			}

			if ( $( '#buddypress [data-bp-filter="' + object + '"]' ).length ) {
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
					object: object,
					scope: scope,
					filter: filter,
					search_terms: search_terms,
					page: 1,
					template: template,
					extras: extras,
					group_type: $( '#buddypress [data-bp-group-type-filter="' + object + '"]' ).val()
				}
			);
		},

		/**
		 * [typeMemberFilterQuery description]
		 *
		 * @param  {[type]} event [description]
		 * @return {[type]}       [description]
		 */
		typeMemberFilterQuery: function ( event ) {
			var self   = event.data, object = $( event.target ).data( 'bp-member-type-filter' ), scope = 'all',
				filter = null, objectData = {}, extras = null, search_terms = '', template = null;

			if ( ! object ) {
				return event;
			}

			if ( 'friends' === object ) {
				object = 'members';
			}

			objectData = self.getStorage( 'bp-' + object );

			// Notifications always need to start with Newest ones.
			if ( undefined !== objectData.extras && 'notifications' !== object ) {
				extras = objectData.extras;
			}

			if ( $( '#buddypress [data-bp-filter="' + object + '"]' ).length ) {
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
					object: object,
					scope: scope,
					filter: filter,
					search_terms: search_terms,
					page: 1,
					template: template,
					extras: extras,
					member_type_id: $( '#buddypress [data-bp-member-type-filter="' + object + '"]' ).val()
				}
			);
		},

		/**
		 * [searchQuery description]
		 *
		 * @param  {[type]} event [description]
		 * @return {[type]}       [description]
		 */
		searchQuery: function ( event ) {
			var self   = event.data, object, scope = 'all', filter = null, template = null, search_terms = '',
				extras = false;

			if ( $( event.delegateTarget ).hasClass( 'no-ajax' ) || undefined === $( event.delegateTarget ).data( 'bp-search' ) ) {
				return event;
			}

			// Stop event propagation.
			event.preventDefault();

			object       = $( event.delegateTarget ).data( 'bp-search' );
			filter       = $( '#buddypress' ).find( '[data-bp-filter="' + object + '"]' ).first().val();
			search_terms = $( event.delegateTarget ).find( 'input[type=search]' ).first().val();

			if ( $( self.objectNavParent + ' [data-bp-object]' ).length ) {
				scope = $( self.objectNavParent + ' [data-bp-object="' + object + '"].selected' ).data( 'bp-scope' );
			}

			var objectData = self.getStorage( 'bp-' + object );

			// Notifications always need to start with Newest ones.
			if ( undefined !== objectData.extras && 'notifications' !== object ) {
				extras = objectData.extras;
			}

			self.objectRequest(
				{
					object: object,
					scope: scope,
					filter: filter,
					search_terms: search_terms,
					page: 1,
					extras: extras,
					template: template
				}
			);
		},

		/**
		 * [showSearchSubmit description]
		 *
		 * @param  {[type]} event [description]
		 * @return {[type]}       [description]
		 */
		showSearchSubmit: function ( event ) {
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
		resetSearch: function ( event ) {
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
		buttonAction: function ( event ) {
			var self       = event.data, target = $( event.currentTarget ), action = target.data( 'bp-btn-action' ),
				nonceUrl   = target.data( 'bp-nonce' ),
				item       = target.closest( '[data-bp-item-id]' ), item_id = item.data( 'bp-item-id' ),
				item_inner = target.closest( '.list-wrap' ),
				object     = item.data( 'bp-item-component' ), nonce = '';

			// Simply let the event fire if we don't have needed values.
			if ( ! action || ! item_id || ! object ) {
				return event;
			}

			// Stop event propagation.
			event.preventDefault();

			if ( target.hasClass( 'bp-toggle-action-button' ) ) {

				// support for buddyboss theme for button actions and icons and texts.
				if ( $( document.body ).hasClass( 'buddyboss-theme' ) && typeof target.data( 'balloon' ) !== 'undefined' ) {
					target.attr( 'data-balloon', target.data( 'title' ) );
				} else {
					target.text( target.data( 'title' ) );
				}

				target.removeClass( 'bp-toggle-action-button' );
				target.addClass( 'bp-toggle-action-button-clicked' );
				return false;
			}

			// check if only admin trying to leave the group.
			if ( typeof target.data( 'only-admin' ) !== 'undefined' ) {
				if ( undefined !== BP_Nouveau.only_admin_notice ) {
					window.alert( BP_Nouveau.only_admin_notice );
				}
				return false;
			}

			if ( ( undefined !== BP_Nouveau[ action + '_confirm' ] && false === window.confirm( BP_Nouveau[ action + '_confirm' ] ) ) || target.hasClass( 'pending' ) ) {
				return false;
			}

			// Find the required wpnonce string.
			// if  button element set we'll have our nonce set on a data attr.
			// Check the value & if exists split the string to obtain the nonce string.
			// if no value, i.e false, null then the href attr is used.
			if ( nonceUrl ) {
				nonce = self.getLinkParams( nonceUrl, '_wpnonce' );
			} else {
				nonce = self.getLinkParams( target.prop( 'href' ), '_wpnonce' );
			}

			// Unfortunately unlike groups.
			// Connections actions does not match the wpnonce.
			var friends_actions_map = {
				is_friend: 'remove_friend',
				not_friends: 'add_friend',
				pending: 'withdraw_friendship',
				accept_friendship: 'accept_friendship',
				reject_friendship: 'reject_friendship'
			};

			if ( 'members' === object && undefined !== friends_actions_map[ action ] ) {
				action = friends_actions_map[ action ];
				object = 'friends';
			}

			var follow_actions_map = {
				not_following: 'follow',
				following: 'unfollow'
			};

			if ( 'members' === object && undefined !== follow_actions_map[ action ] ) {
				action = follow_actions_map[ action ];
				object = 'follow';
			}

			// Add a pending class to prevent queries while we're processing the action.
			target.addClass( 'pending loading' );

			self.ajax(
				{
					action: object + '_' + action,
					item_id: item_id,
					_wpnonce: nonce
				},
				object,
				true
			).done(
				function ( response ) {
					if ( false === response.success ) {
						item_inner.prepend( response.data.feedback );
						target.removeClass( 'pending loading' );
						if ( item.find( '.bp-feedback' ).length ) {
							item.find( '.bp-feedback' ).show();
							item.find( '.bp-feedback' ).fadeOut( 6000 );
						} else {
							if ( 'groups' === object && 'join_group' === action ) {
								item.append( response.data.feedback );
								item.find( '.bp-feedback' ).fadeOut( 6000 );
							}
						}

					} else {
						// Specific cases for groups.
						if ( 'groups' === object ) {

							// Group's header button.
							if ( undefined !== response.data.is_group && response.data.is_group ) {
								if ( undefined !== response.data.group_url && response.data.group_url ) {
									return window.location = response.data.group_url;
								} else {
									return window.location.reload();
								}
							}
						}

						// User main nav update friends counts.
						if ( $( '#friends-personal-li' ).length ) {
							var friend_with_count    = $( '#friends-personal-li a span' );
							var friend_without_count = $( '#friends-personal-li a' );

							// Check friend count set.
							if ( undefined !== response.data.is_user && response.data.is_user && undefined !== response.data.friend_count ) {
								// Check friend count > 0 then show the count span.
								if ( response.data.friend_count > 0 ) {
									if ( ( friend_with_count ).length ) {
										// Update count span.
										$( friend_with_count ).html( response.data.friend_count );
									} else {
										// If no friend then add count span.
										$( friend_without_count ).append( '<span class="count">' + response.data.friend_count + '</span>' );
									}
								} else {
									// If no friend then hide count span.
									$( friend_with_count ).hide();
								}
							} else if ( undefined !== response.data.friend_count ) {
								if ( response.data.friend_count > 0 ) {
									if ( ( friend_with_count ).length ) {
										// Update count span.
										$( friend_with_count ).html( response.data.friend_count );
									} else {
										// If no friend then add count span.
										$( friend_without_count ).append( '<span class="count">' + response.data.friend_count + '</span>' );
									}
								} else {
									// If no friend then hide count span.
									$( friend_with_count ).hide();
								}
							}
						}

						// User's groups invitations screen & User's friend screens.
						if ( undefined !== response.data.is_user && response.data.is_user ) {
							target.parent().html( response.data.feedback );
							item.fadeOut( 1500 );
							return;
						}

						// Reject invitation from group.
						if ( undefined !== response.data.is_user && ! response.data.is_user && undefined !== response.data.group_url && response.data.group_url ) {
							return window.location = response.data.group_url;
						}

						// Update count.
						if ( $( self.objectNavParent + ' [data-bp-scope="personal"]' ).length ) {
							var personal_count = Number( $( self.objectNavParent + ' [data-bp-scope="personal"] span' ).html() ) || 0;

							if ( -1 !== $.inArray( action, [ 'leave_group', 'remove_friend' ] ) ) {
								personal_count -= 1;
							} else if ( -1 !== $.inArray( action, [ 'join_group' ] ) ) {
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
		buttonRevert: function ( event ) {
			var target = $( event.currentTarget );

			if ( target.hasClass( 'bp-toggle-action-button-clicked' ) && ! target.hasClass( 'loading' ) ) {

				// support for buddyboss theme for button actions and icons and texts.
				if ( $( document.body ).hasClass( 'buddyboss-theme' ) && typeof target.data( 'balloon' ) !== 'undefined' ) {
					target.attr( 'data-balloon', target.data( 'title-displayed' ) );
				} else {
					target.text( target.data( 'title-displayed' ) ); // change text to displayed context.
				}

				target.removeClass( 'bp-toggle-action-button-clicked' ); // remove class to detect event.
				target.addClass( 'bp-toggle-action-button' ); // add class to detect event to confirm.
			}
		},

		/**
		 * [buttonRevertAll description]
		 *
		 * @return {[type]}       [description]
		 */
		buttonRevertAll: function () {
			$.each(
				$( '#buddypress [data-bp-btn-action]' ),
				function () {
					if ( $( this ).hasClass( 'bp-toggle-action-button-clicked' ) && ! $( this ).hasClass( 'loading' ) ) {

						// support for buddyboss theme for button actions and icons and texts.
						if ( $( document.body ).hasClass( 'buddyboss-theme' ) && typeof $( this ).data( 'balloon' ) !== 'undefined' ) {
							$( this ).attr( 'data-balloon', $( this ).data( 'title-displayed' ) );
						} else {
							$( this ).text( $( this ).data( 'title-displayed' ) ); // change text to displayed context.
						}

						$( this ).removeClass( 'bp-toggle-action-button-clicked' ); // remove class to detect event.
						$( this ).addClass( 'bp-toggle-action-button' ); // add class to detect event to confirm.
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
		addRemoveInvite: function ( event ) {

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
					currentDataTable.find( 'tr' ).length > 20 ? $( currentTarget ).addClass( 'disabled' ) : ''; // Add Limit of 20.

				} else {

					return;

				}

			}

			// reset the id of all inputs.
			var data_rows = currentDataTable.find( 'tr:not(:last-child)' );
			$.each(
				data_rows,
				function ( index ) {
					$( this ).find( '.field-name > input' ).attr( 'name', 'invitee[' + index + '][]' );
					$( this ).find( '.field-name > input' ).attr( 'id', 'invitee_' + index + '_title' );
					$( this ).find( '.field-email > input' ).attr( 'name', 'email[' + index + '][]' );
					$( this ).find( '.field-email > input' ).attr( 'id', 'email_' + index + '_email' );
					$( this ).find( '.field-member-type > select' ).attr( 'name', 'member-type[' + index + '][]' );
					$( this ).find( '.field-member-type > select' ).attr( 'id', 'member_type_' + index + '_member_type' );
				}
			);
		},

		/**
		 * [closeNotice description]
		 *
		 * @param  {[type]} event [description]
		 * @return {[type]}       [description]
		 */
		closeNotice: function ( event ) {
			var closeBtn = $( event.currentTarget );

			event.preventDefault();

			// Make sure cookies are removed.
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
						action: 'messages_dismiss_sitewide_notice'
					},
					'messages'
				);
			}

			// Remove the notice.
			closeBtn.closest( '.bp-feedback' ).remove();
		},

		paginateAction: function ( event ) {
			var self  = event.data, navLink = $( event.currentTarget ), pagArg,
				scope = null, object, objectData, filter = null, search_terms = null, extras = null;

			pagArg = navLink.closest( '[data-bp-pagination]' ).data( 'bp-pagination' ) || null;

			if ( null === pagArg ) {
				return event;
			}

			event.preventDefault();

			object = $( event.delegateTarget ).data( 'bp-list' ) || null;

			// Set the scope & filter for local storage.
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

			// Set the search terms.
			if ( $( '#buddypress [data-bp-search="' + object + '"] input[type=search]' ).length ) {
				search_terms = $( '#buddypress [data-bp-search="' + object + '"] input[type=search]' ).val();
			}

			var queryData = {
				object: object,
				scope: scope,
				filter: filter,
				search_terms: search_terms,
				extras: extras,
				caller: navLink.closest( '[data-bp-pagination]' ).hasClass( 'bottom' ) ? 'pag-bottom' : '',
				page: self.getLinkParams( navLink.prop( 'href' ), pagArg ) || 1
			};

			// Set group type with pagination.
			if ( $( '#buddypress [data-bp-group-type-filter]' ).length ) {
				/* jshint ignore:start */
				queryData[ 'group_type' ] = $( '#buddypress [data-bp-group-type-filter]' ).val();
				/* jshint ignore:end */
			}

			// Set member type with pagination.
			if ( $( '#buddypress [data-bp-member-type-filter]' ).length ) {
				/* jshint ignore:start */
				queryData[ 'member_type_id' ] = $( '#buddypress [data-bp-member-type-filter]' ).val();
				/* jshint ignore:end */
			}

			// Request the page.
			self.objectRequest( queryData );
		},
		enableSubmitOnLegalAgreement: function () {
			if ( $( 'body #buddypress #register-page #signup-form #legal_agreement' ).length ) {
				$( 'body #buddypress #register-page #signup-form .submit #signup_submit' ).prop( 'disabled', true );
				$( document ).on(
					'change',
					'body #buddypress #register-page #signup-form #legal_agreement',
					function () {
						if ( $( this ).prop( 'checked' ) ) {
							$( 'body #buddypress #register-page #signup-form .submit #signup_submit' ).prop( 'disabled', false );
						} else {
							$( 'body #buddypress #register-page #signup-form .submit #signup_submit' ).prop( 'disabled', true );
						}
					}
				);
			}
		},
		registerPopUp: function () {
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
					function ( e ) {
						e.preventDefault();
						$.magnificPopup.close();
					}
				);
			}
		},
		loginPopUp: function () {
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
					function ( e ) {
						e.preventDefault();
						$.magnificPopup.close();
					}
				);
			}
		},
		reportPopUp: function () {
			if ( $( '.report-content, .block-member, .mass-block-member' ).length > 0 ) {
				var _this = this;
				$( '.report-content, .block-member' ).magnificPopup(
					{
						type: 'inline',
						midClick: true,
						callbacks: {
							open: function () {
								var contentId   = this.currItem.el.data( 'bp-content-id' );
								var contentType = this.currItem.el.data( 'bp-content-type' );
								var nonce       = this.currItem.el.data( 'bp-nonce' );
								var reportType  = this.currItem.el.attr( 'reported_type' );
								if ( 'undefined' !== typeof reportType ) {
									var mf_content = $( '#content-report' );
									mf_content.find( '.bp-reported-type' ).text( reportType );
								}
								if ( 'undefined' !== typeof contentId && 'undefined' !== typeof contentType && 'undefined' !== typeof nonce ) {
									$( document ).find( '.bp-report-form-err' ).empty();
									_this.setFormValues( { contentId: contentId, contentType: contentType, nonce: nonce } );
								}
							}
						}
					}
				);

				$( '.mass-block-member' ).magnificPopup(
					{
						type: 'inline',
						midClick: true,
						callbacks: {
							change: function () {
								var _self = this;
								setTimeout(
									function () {
										var contentId   = _self.currItem.el.data( 'bp-content-id' );
										var contentType = _self.currItem.el.data( 'bp-content-type' );
										var nonce       = _self.currItem.el.data( 'bp-nonce' );
										if ( 'undefined' !== typeof contentId && 'undefined' !== typeof contentType && 'undefined' !== typeof nonce ) {
											_this.setFormValues( { contentId: contentId, contentType: contentType, nonce: nonce } );
										}
									},
									1
								);
							}
						}
					}
				);
			}
		},
		reportActions: function () {
			var _this = this;

			$( document ).on(
				'click',
				'.bb-cancel-report-content',
				function ( e ) {
					e.preventDefault();
					$( 'form#bb-report-content' ).trigger( 'reset' );
					$( this ).closest( '.moderation-popup' ).find( '.bp-other-report-cat' ).closest( '.form-item' ).addClass( 'bp-hide' );
					$( this ).closest( '.moderation-popup' ).find( '.mfp-close' ).trigger( 'click' );
				}
			);
			$( document ).on(
				'click',
				'input[type=radio][name=report_category]',
				function () {
					if ( 'other' === this.value ) {
						$( this ).closest( '.moderation-popup' ).find( '.bp-other-report-cat' ).closest( '.form-item' ).removeClass( 'bp-hide' );
						$( this ).closest( '.moderation-popup' ).find( '.bp-other-report-cat' ).prop( 'required', true );
					} else {
						$( this ).closest( '.moderation-popup' ).find( '.bp-other-report-cat' ).closest( '.form-item' ).addClass( 'bp-hide' );
						$( this ).closest( '.moderation-popup' ).find( '.bp-other-report-cat' ).prop( 'required', false );
					}
				}
			);

			$( '#bb-report-content' ).submit(
				function ( e ) {

					$( '#bb-report-content' ).find( '.report-submit' ).addClass( 'loading' );

					$( '.bp-report-form-err' ).empty();

					var data = {
						action: 'bp_moderation_content_report',
					};
					$.each(
						$( this ).serializeArray(),
						function ( _, kv ) {
							data[ kv.name ] = kv.value;
						}
					);

					$.post(
						BP_Nouveau.ajaxurl,
						data,
						function ( response ) {
							if ( response.success ) {
								_this.resetReportPopup();
								_this.changeReportButtonStatus( response.data );
								$( '#bb-report-content' ).find( '.report-submit' ).removeClass( 'loading' );
								$( '.mfp-close' ).trigger( 'click' );
							} else {
								$( '#bb-report-content' ).find( '.report-submit' ).removeClass( 'loading' );
								_this.handleReportError( response.data.message.errors, e.currentTarget );
							}
						}
					);
				}
			);

			$( '#bb-block-member' ).submit(
				function ( e ) {

					$( '#bb-block-member' ).find( '.report-submit' ).addClass( 'loading' );

					$( '.bp-report-form-err' ).empty();

					var data = {
						action: 'bp_moderation_block_member',
					};
					$.each(
						$( this ).serializeArray(),
						function ( _, kv ) {
							data[ kv.name ] = kv.value;
						}
					);

					$.post(
						BP_Nouveau.ajaxurl,
						data,
						function ( response ) {
							if ( response.success ) {
								_this.resetReportPopup();
								_this.changeReportButtonStatus( response.data );
								$( '#bb-block-member' ).find( '.report-submit' ).removeClass( 'loading' );
								$( '.mfp-close' ).trigger( 'click' );
								if ( response.data.redirect ) {
									location.href = response.data.redirect;
								}
							} else {
								$( '#bb-block-member' ).find( '.report-submit' ).removeClass( 'loading' );
								_this.handleReportError( response.data.message.errors, e.currentTarget );
							}
						}
					);
				}
			);
		},
		resetReportPopup: function () {
			$( 'form#bb-report-content' ).trigger( 'reset' );
			var mf_content = $( '.mfp-content' );
			mf_content.find( '.bp-content-id' ).val( '' );
			mf_content.find( '.bp-content-type' ).val( '' );
			mf_content.find( '.bp-nonce' ).val( '' );
			mf_content.find( '.bp-report-form-err' ).empty();
		},
		changeReportButtonStatus: function ( data ) {
			var _this = this;
			$( '[data-bp-content-id=' + data.button.button_attr.item_id + '][data-bp-content-type=' + data.button.button_attr.item_type + ']' ).each(
				function () {
					$( this ).removeAttr( 'data-bp-content-id' );
					$( this ).removeAttr( 'data-bp-content-type' );
					$( this ).removeAttr( 'data-bp-nonce' );

					$( this ).html( data.button.link_text );
					$( this ).attr( 'class', data.button.button_attr.class );
					$( this ).attr( 'reported_type', data.button.button_attr.reported_type );
					$( this ).attr( 'href', data.button.button_attr.href );
					setTimeout(
						function () { // Waiting to load dummy image.
							_this.reportedPopup();
						},
						1
					);
				}
			);
		},
		reportedPopup: function () {
			if ( $( '.reported-content' ).length > 0 ) {
				$( '.reported-content' ).magnificPopup(
					{
						type: 'inline',
						midClick: true,
						callbacks: {
							open: function () {
								var contentType = this.currItem.el.attr( 'reported_type' );
								if ( 'undefined' !== typeof contentType ) {
									var mf_content = $( '#reported-content' );
									mf_content.find( '.bp-reported-type' ).text( contentType );
								}
							}
						}
					}
				);
			}
		},
		handleReportError: function ( errors, target ) {
			var message = '';
			if ( errors.bp_moderation_missing_data ) {
				message = errors.bp_moderation_missing_data;
			} else if ( errors.bp_moderation_already_reported ) {
				message = errors.bp_moderation_already_reported;
			} else if ( errors.bp_moderation_missing_error ) {
				message = errors.bp_moderation_missing_error;
			} else if ( errors.bp_moderation_invalid_access ) {
				message = errors.bp_moderation_invalid_access;
			} else if ( errors.bp_moderation_invalid_item_id ) {
				message = errors.bp_moderation_invalid_item_id;
			}

			jQuery( target ).closest( '.bb-report-type-wrp' ).find( '.bp-report-form-err' ).html( message );
		},
		setFormValues: function ( data ) {
			var mf_content = $( '.mfp-content' );
			mf_content.find( '.bp-content-id' ).val( data.contentId );
			mf_content.find( '.bp-content-type' ).val( data.contentType );
			mf_content.find( '.bp-nonce' ).val( data.nonce );
		},
		togglePassword: function () {
			$( document ).on(
				'click',
				'.bb-toggle-password',
				function ( e ) {
					e.preventDefault();
					var $this  = $( this );
					var $input = $this.next( 'input' );
					$this.toggleClass( 'bb-show-pass' );
					if ( $this.hasClass( 'bb-show-pass' ) ) {
						$input.attr( 'type', 'text' );
					} else {
						$input.attr( 'type', 'password' );
					}
				}
			);
		},

		/**
		 * Close emoji picker whenever clicked outside of emoji container
		 *
		 * @param event
		 */
		closePickersOnClick: function ( event ) {
			var $targetEl = $( event.target );

			if ( ! _.isUndefined( BP_Nouveau.media ) &&
				! _.isUndefined( BP_Nouveau.media.emoji ) &&
				! $targetEl.closest( '.post-emoji' ).length &&
				! $targetEl.is( '.emojioneemoji,.emojibtn' ) ) {
				$( '.emojionearea-button.active' ).removeClass( 'active' );
			}
		},

		/**
		 * Close emoji picker on Esc press
		 *
		 * @param event
		 */
		closePickersOnEsc: function ( event ) {
			if ( event.key === 'Escape' || event.keyCode === 27 ) {
				if ( ! _.isUndefined( BP_Nouveau.media ) &&
					! _.isUndefined( BP_Nouveau.media.emoji ) ) {
					$( '.emojionearea-button.active' ).removeClass( 'active' );
				}
			}
		},
		/**
		 * Lazy Load Images and iframes
		 *
		 * @param event
		 */
		lazyLoad: function ( lazyTarget ) {
			var lazy = $( lazyTarget );
			if ( lazy.length ) {
				for ( var i = 0; i < lazy.length; i++ ) {
					var isInViewPort = false;
					try {
						if ( $( lazy[ i ] ).is( ':in-viewport' ) ) {
							isInViewPort = true;
						}
					} catch ( err ) {
						console.error( err.message );
						if ( ! isInViewPort && lazy[ i ].getBoundingClientRect().top <= ( ( window.innerHeight || document.documentElement.clientHeight ) + window.scrollY ) ) {
							isInViewPort = true;
						}
					}

					if ( isInViewPort && lazy[ i ].getAttribute( 'data-src' ) ) {
						lazy[ i ].src = lazy[ i ].getAttribute( 'data-src' );
						lazy[ i ].removeAttribute( 'data-src' );
						/* jshint ignore:start */
						$( lazy[ i ] ).on(
							'load',
							function () {
								$( this ).removeClass( 'lazy' );
							}
						);
						/* jshint ignore:end */

						// Inform other scripts about the lazy load.
						$( document ).trigger( 'bp_nouveau_lazy_load', { element: lazy[ i ] } );
					}
				}
			}
		},
		/**
		 *  Cover photo Cropper
		 */
		coverPhotoCropper: function ( e ) {

			var picture, guillotineHeight, guillotineWidth, guillotineTop, guillotineScale;

			if ( $( e.currentTarget ).hasClass( 'position-change-cover-image' ) ) {
				var imageHeight   = $( e.currentTarget ).closest( '#cover-image-container' ).find( '.header-cover-img' ).height();
				var imageCenter   = ( imageHeight - $( e.currentTarget ).closest( '#header-cover-image' ).height() ) / 2;
				var currentTarget = $( e.currentTarget );
				if ( imageHeight <= currentTarget.closest( '#header-cover-image' ).height() ) {
					$( 'body' ).append( '<div id="cover-photo-alert" style="display: block;" class="open-popup"><transition name="modal"><div class="modal-mask bb-white bbm-model-wrap"><div class="modal-wrapper"><div id="boss-media-create-album-popup" class="modal-container has-folderlocationUI"><header class="bb-model-header"><h4>' + BP_Nouveau.media.cover_photo_size_error_header + '</h4><a class="bb-model-close-button" id="bp-media-create-folder-close" href="#"><span class="dashicons dashicons-no-alt"></span></a></header><div class="bb-field-wrap"><p>' + BP_Nouveau.media.cover_photo_size_error_description + '</p></div></div></div></div></transition></div>' );
					e.preventDefault();
					return;
				}
				guillotineHeight = $( e.currentTarget ).closest( '#header-cover-image' ).height();
				guillotineWidth  = $( e.currentTarget ).closest( '#header-cover-image' ).width();
				guillotineTop    = Number( $( e.currentTarget ).closest( '#cover-image-container' ).find( '.header-cover-img' ).css( 'top' ).replace( 'px', '' ) );

				guillotineScale = $( e.currentTarget ).closest( '#header-cover-image' ).width() / $( e.currentTarget ).closest( '#header-cover-image' ).find( '.header-cover-reposition-wrap img' )[ 0 ].width;
				currentTarget.closest( '#cover-image-container' ).find( '.header-cover-reposition-wrap' ).show();
				picture = $( '.header-cover-reposition-wrap img' );
				picture.guillotine(
					{
						width: guillotineWidth,
						height: guillotineHeight,
						eventOnChange: 'guillotinechange',
						init: {
							scale: guillotineScale,
							y: guillotineTop && $( e.currentTarget ).closest( '#header-cover-image' ).hasClass( 'has-position' ) ? -guillotineTop : imageCenter,
							w: guillotineWidth,
							h: guillotineHeight
						}
					}
				);
				picture.on(
					'guillotinechange',
					function ( e, data ) {
						currentTarget.closest( '#cover-image-container' ).find( '.header-cover-img' ).attr( 'data-top', -data.y );
					}
				);
			} else if ( $( e.currentTarget ).hasClass( 'cover-image-save' ) ) {
				var saveButton = $( e.currentTarget );
				var coverImage = $( e.currentTarget ).closest( '#cover-image-container' ).find( '.header-cover-img' );
				saveButton.addClass( 'loading' );

				$.post(
					BP_Nouveau.ajaxurl,
					{
						'action': 'save_cover_position',
						'position': coverImage.attr( 'data-top' ),
					}
				).done(
					function ( $response ) {
						if ( $response.success && $response.data && '' !== $response.data.content ) {
							saveButton.removeClass( 'loading' );
							saveButton.closest( '#cover-image-container' ).find( '.header-cover-reposition-wrap' ).hide();
							saveButton.closest( '#header-cover-image:not(.has-position)' ).addClass( 'has-position' );
							coverImage.css( { 'top': $response.data.content + 'px' } );
						} else {
							saveButton.removeClass( 'loading' );
							saveButton.closest( '#cover-image-container' ).find( '.header-cover-reposition-wrap' ).hide();
						}
					}
				).fail(
					function () {
						saveButton.removeClass( 'loading' );
						saveButton.closest( '#cover-image-container' ).find( '.header-cover-reposition-wrap' ).hide();
					}
				);

			} else if ( $( e.currentTarget ).hasClass( 'cover-image-cancel' ) ) {
				picture = $( '.header-cover-reposition-wrap img' );
				picture.guillotine(
					{
						width: 0,
						height: 0,
						init: { scale: 1, y: 0, w: 0, h: 0 }
					}
				);
				picture.guillotine( 'remove' );
				$( e.currentTarget ).closest( '#cover-image-container' ).find( '.header-cover-reposition-wrap' ).hide();
				$( e.currentTarget ).closest( '#cover-image-container' ).find( '.header-cover-img' ).attr( 'data-top', '' );
			}
			e.preventDefault();
		},
		/**
		 *  Cover photo Cropper Alert close
		 */
		coverPhotoCropperAlert: function ( e ) {
			e.preventDefault();
			$( '#cover-photo-alert' ).remove();
		},
		/**
		 *  Toggle More Option
		 */
		toggleMoreOption: function( event ) {

			if( $( event.target ).hasClass( 'bb_more_options_action' ) || $( event.target ).parent().hasClass( 'bb_more_options_action' ) ) {
				event.preventDefault();
				
				if( $( event.target ).closest( '.bb_more_options' ).find( '.bb_more_options_list' ).hasClass( 'is_visible' ) ) {
					$( '.bb_more_options' ).find( '.bb_more_options_list' ).removeClass( 'is_visible' );
				} else {
					$( '.bb_more_options' ).find( '.bb_more_options_list' ).removeClass( 'is_visible' );
					$( event.target ).closest( '.bb_more_options' ).find( '.bb_more_options_list' ).addClass( 'is_visible' );
				}

			} else {
				$( '.bb_more_options' ).find( '.bb_more_options_list' ).removeClass( 'is_visible' );
			}
		},

		getVideoThumb: function ( file, target ) { // target = '.node'.

			// Load Video Thumbnail.
			var fileReader    = new FileReader();
			fileReader.onload = function () {
				var blob          = new Blob( [ fileReader.result ], { type: file.type } );
				var url           = URL.createObjectURL( blob );
				var video         = document.createElement( 'video' );
				var videoDuration = null;
				video.src         = url;
				var timer         = setInterval(
					function () {
						if (video.readyState === 4) {
							videoDuration  = video.duration.toFixed( 2 );
							var timeupdate = function () {
								if ( snapImage() ) {
									video.removeEventListener( 'timeupdate', timeupdate );
									video.pause();
								}
							};

							video.addEventListener(
								'loadeddata',
								function () {
									if ( snapImage() ) {
										video.removeEventListener( 'timeupdate', timeupdate );
									}
								}
							);
							var snapImage = function () {
								var canvas    = document.createElement( 'canvas' );
								canvas.width  = video.videoWidth;
								canvas.height = video.videoHeight;
								canvas.getContext( '2d' ).drawImage( video, 0, 0, canvas.width, canvas.height );
								var image   = canvas.toDataURL();
								var success = image.length > 100000;
								if ( success ) {
									var img = document.createElement( 'img' );
									img.src = image;

									if ( file.previewElement ) {
										if ( $( file.previewElement ).find( target ).find( 'img' ).length ) {
											$( file.previewElement ).find( target ).find( 'img' ).attr( 'src', image );
										} else {
											$( file.previewElement ).find( target ).append( img );
										}

										$( file.previewElement ).closest( '.dz-preview' ).addClass( 'dz-has-thumbnail' );
									} else {
										if ( $( target ).find( 'img' ).length ) {
											$( target ).find( 'img' ).attr( 'src', image );
										} else {
											$( target ).append( img );
										}
									}

									URL.revokeObjectURL( url );
								}
								return success;
							};
							video.addEventListener( 'timeupdate', timeupdate );
							video.preload     = 'metadata';
							video.src         = url;
							video.muted       = true;
							video.playsInline = true;
							if ( videoDuration != null ) {
								video.currentTime = Math.floor( Math.random() * Math.floor( videoDuration ) ); // Seek random second before capturing thumbnail
							}
							video.play();
							clearInterval( timer );
						}
					},
					500
				);

			};

			if ( file.dataURL ) { // If file is already uploaded then convert to blob from file URL.
				var xhr = new XMLHttpRequest();
				xhr.open( 'GET', file.dataURL, true );
				xhr.responseType = 'blob';
				xhr.onload       = function() {
					if (this.status == 200) {
						var myBlob = this.response;
						fileReader.readAsArrayBuffer( myBlob );
					}
				};
				xhr.send();
			} else {
				fileReader.readAsArrayBuffer( file );
			}

		}

	};

	// Launch BP Nouveau.
	bp.Nouveau.start();

} )( bp, jQuery );

/* global wp, bp, BP_Nouveau, JSON, BB_Nouveau_Presence */
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

			// Profile Notification setting
			this.profileNotificationSetting();

			this.xProfileBlock();

			// Bail if not set.
			if ( 'undefined' !== typeof BB_Nouveau_Presence ) {
				// User Presence status.
				this.userPresenceStatus();
			}

			var _this = this;

			$( document ).on( 'bb_trigger_toast_message', function ( event, title, message, type, url, autoHide, autohide_interval ) {
				_this.bbToastMessage( title, message, type, url, autoHide, autohide_interval );
			} );

			// Check for lazy images and load them also register scroll event to load on scroll.
			bp.Nouveau.lazyLoad( '.lazy' );
			$( window ).on(
				'scroll resize',
				function () {
					bp.Nouveau.lazyLoad( '.lazy' );
				}
			);
		},

		/*
		 *	Toast Message
		 */
		 bbToastMessage: function ( title, message, type, url, autoHide, autohide_interval ) {

			if ( ! message || message.trim() == '' ) { // Toast Message can't be triggered without content.
				return;
			}

			function getTarget() {
				if ( $( '.bb-toast-messages-enable' ).length ) {
					return '.bb-toast-messages-enable .toast-messages-list';
				}

				if ( $( '.bb-onscreen-notification-enable ul.notification-list' ).length ) {
					var toastPosition = $( '.bb-onscreen-notification' ).hasClass( 'bb-position-left' ) ? 'left' : 'right';
					var toastMessageWrapPosition = $( '<div class="bb-toast-messages-enable bb-toast-messages-enable-mobile-support"><div class="bb-toast-messages bb-position-' + toastPosition + ' single-toast-messages"><ul class="toast-messages-list bb-toast-messages-list"></u></div></div>' );
					$( '.bb-onscreen-notification' ).show();
					$( toastMessageWrapPosition ).insertBefore( '.bb-onscreen-notification-enable ul.notification-list' );
				} else {
					var toastMessageWrap = $( '<div class="bb-toast-messages-enable bb-toast-messages-enable-mobile-support"><div class="bb-toast-messages bb-position-right single-toast-messages"><ul class="toast-messages-list bb-toast-messages-list"></u></div></div>' );
					$( 'body' ).append( toastMessageWrap );
				}
				return '.bb-toast-messages-enable .toast-messages-list';
			}

			function hideMessage() {
				$( currentEl ).removeClass( 'pull-animation' ).addClass( 'close-item' ).delay( 500 ).remove();
			}

			// Add Toast Message
			var unique_id = 'unique-' + Math.floor( Math.random() * 1000000 );
			var currentEl = '.' + unique_id;
			var urlClass = '';
			var bp_msg_type = '';
			var bp_icon_type = '';
			/* jshint ignore:start */
			var autohide_interval = autohide_interval && typeof autohide_interval == 'number' ? ( autohide_interval * 1000 ) : 5000;
			/* jshint ignore:end */

			if ( type ) {
				bp_msg_type = type;
				if ( bp_msg_type === 'success' ) {
					bp_icon_type = 'check';
				} else if ( bp_msg_type === 'warning' ) {
					bp_icon_type = 'exclamation-triangle';
				} else {
					bp_icon_type = 'info';
				}
			}

			if ( url !== null ) {
				urlClass = 'has-url';
			}

			var messageContent = '';
			messageContent += '<div class="toast-messages-icon"><i class="bb-icon bb-icon-' + bp_icon_type + '"></i></div>';
			messageContent += '<div class="toast-messages-content">';
			if ( title ) {
				messageContent += '<span class="toast-messages-title">' + title + '</span>';
			}

			if ( message ) {
				messageContent += '<span class="toast-messages-content">' + message + '</span>';
			}

			messageContent += '</div>';
			messageContent += '<div class="actions"><a class="action-close primary" data-bp-tooltip-pos="left" data-bp-tooltip="' + BP_Nouveau.close + '"><i class="bb-icon bb-icon-times" aria-hidden="true"></i></a></div>';
			messageContent += url ? '<a class="toast-messages-url" href="' + url + '"></a>' : '';

			$( getTarget() ).append( '<li class="item-list read-item pull-animation bp-message-' + bp_msg_type + ' ' + unique_id + ' ' + urlClass + '"> ' + messageContent + ' </li>' );

			if ( autoHide ) {
				setInterval( function () {
					hideMessage();
				}, autohide_interval );
			}

			$( currentEl + ' .actions .action-close' ).on( 'click', function () {
				hideMessage();
			} );

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
				$( '#whats-new' ).on(
					'inserted.atwho',
					function () { // Get caret position when user adds mention.
						if (window.getSelection && document.createRange) {
							var sel = window.getSelection && window.getSelection();
							if (sel && sel.rangeCount > 0) {
								window.activityCaretPosition = sel.getRangeAt( 0 );
							}
						} else {
							window.activityCaretPosition = document.selection.createRange();
						}
					}
				);
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
				newString  = '',
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
					newString = $.trim( _findtext.replace( _url, '' ) );
				}

				if ( $.trim( newString ).length === 0 && $( this ).find( 'iframe' ).length !== 0 && _url !== '' ) {
					$( this ).find( '.activity-inner > p:first' ).hide();
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

			// Add loader at custom place for few search types
			if ( $( this.objectNavParent + ' [data-bp-scope="' + data.scope + '"]' ).length === 0 ) {

				var component_conditions = [
					data.object === 'group_members' && $( 'body' ).hasClass( 'group-members' ),
					data.object === 'activity' && $( 'body.groups' ).hasClass( 'activity' ),
					data.object === 'document' && $( 'body' ).hasClass( 'documents' ),
					data.object === 'document' && ( $( 'body' ).hasClass( 'document' ) || $( 'body' ).hasClass( 'documents' ) ),
				];

				var component_targets = [
					$( '.groups .group-search.members-search' ),
					$( '.groups .group-search.activity-search' ),
					$( '.documents .bp-document-listing .bb-title' ),
					$( '#bp-media-single-folder .bb-title' ),
				];

				component_conditions.forEach( function ( condition, i ) {
					if ( condition ) {
						component_targets[ i ].addClass( 'loading' );
					}
				} );

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

					if ( $( self.objectNavParent + ' [data-bp-scope="' + data.scope + '"]' ).length === 0 ) {
						component_targets.forEach( function ( target ) {
							target.removeClass( 'loading' );
						} );
					}

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
							search_terms = decodeURI( self.querystring[ object + '_search' ] );
						} else if ( undefined !== self.querystring.s ) {
							search_terms = decodeURI( self.querystring.s );
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
			$( '#buddypress [data-bp-list], #buddypress #item-header, #buddypress.bp-shortcode-wrap .dir-list, #buddypress .bp-messages-content' ).on( 'click', '[data-bp-btn-action]', this, this.buttonAction );
			$( '#buddypress [data-bp-list], #buddypress #item-header, #buddypress.bp-shortcode-wrap .dir-list, #buddypress .messages-screen' ).on( 'blur', '[data-bp-btn-action]', this, this.buttonRevert );
			$( '#buddypress [data-bp-list], #buddypress #item-header, #buddypress.bp-shortcode-wrap .dir-list, #buddypress .messages-screen' ).on( 'mouseover', '[data-bp-btn-action]', this, this.buttonHover );
			$( '#buddypress [data-bp-list], #buddypress #item-header, #buddypress.bp-shortcode-wrap .dir-list, #buddypress .messages-screen' ).on( 'mouseout', '[data-bp-btn-action]', this, this.buttonHoverout );
			$( '#buddypress [data-bp-list], #buddypress #item-header, #buddypress.bp-shortcode-wrap .dir-list, #buddypress .messages-screen' ).on( 'mouseover', '.awaiting_response_friend', this, this.awaitingButtonHover );
			$( '#buddypress [data-bp-list], #buddypress #item-header, #buddypress.bp-shortcode-wrap .dir-list, #buddypress .messages-screen' ).on( 'mouseout', '.awaiting_response_friend', this, this.awaitingButtonHoverout );
			$( document ).on( 'click', '#buddypress .bb-leave-group-popup .bb-confirm-leave-group', this.leaveGroupAction );
			$( document ).on( 'click', '#buddypress .bb-leave-group-popup .bb-close-leave-group', this.leaveGroupClose );
			$( document ).on( 'click', '#buddypress .bb-remove-connection .bb-confirm-remove-connection', this.removeConnectionAction );
			$( document ).on( 'click', '#buddypress .bb-remove-connection .bb-close-remove-connection', this.removeConnectionClose );
			$( document ).on( 'click', '#buddypress table.invite-settings .field-actions .field-actions-remove, #buddypress table.invite-settings .field-actions-add', this, this.addRemoveInvite );
			$( document ).on( 'click', '.show-action-popup', this.showActionPopup );
			$( document ).on( 'click', '#message-threads .block-member', this.threadListBlockPopup );
			$( document ).on( 'click', '#message-threads .report-content', this.threadListReportPopup );
			$( document ).on( 'click', '.bb-close-action-popup, .action-popup-overlay', this.closeActionPopup );
			$( document ).on( 'keyup', '.search-form-has-reset input[type="search"], .search-form-has-reset input#bbp_search', _.throttle( this.directorySearchInput, 900 ) );
			$( document ).on( 'click', '.search-form-has-reset .search-form_reset', this.resetDirectorySearch );

			$( document ).on( 'keyup', this, this.keyUp );

			// Close notice.
			$( '[data-bp-close]' ).on( 'click', this, this.closeNotice );

			// Pagination.
			$( '#buddypress [data-bp-list]' ).on( 'click', '[data-bp-pagination] a', this, this.paginateAction );

			$( document ).on( 'click', this.closePickersOnClick );
			document.addEventListener( 'keydown', this.closePickersOnEsc );

			$( document ).on( 'click', '#item-header a.position-change-cover-image, .header-cover-reposition-wrap a.cover-image-save, .header-cover-reposition-wrap a.cover-image-cancel', this.coverPhotoCropper );

			$( document ).on( 'click', '#cover-photo-alert .bb-model-close-button', this.coverPhotoCropperAlert );

			// More Option Dropdown.
			$( document ).on( 'click', this.toggleMoreOption.bind( this ) );
			$( document ).on( 'heartbeat-send', this.bbHeartbeatSend.bind( this ) );
			$( document ).on( 'heartbeat-tick', this.bbHeartbeatTick.bind( this ) );

			// Create event for remove single notification.
			bp.Nouveau.notificationRemovedAction();
			// Remove all notifications.
			bp.Nouveau.removeAllNotification();
			// Set title tag.
			bp.Nouveau.setTitle();

			// Following widget more button click.
			$( document ).on( 'click', '.more-following .count-more', this.bbWidgetMoreFollowing );

			// Accordion open/close event
			$( '.bb-accordion .bb-accordion_trigger' ).on( 'click', this.toggleAccordion );
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

			// Add an heartbeat send event to possibly any BuddyPress pages.
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
			bp.Nouveau.bbInjectOnScreenNotifications( event, data );
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
				removedItems  = list.data( 'removed-items' ),
				animatedItems = list.data( 'animated-items' ),
				newItems      = [],
				notifications = $( $.parseHTML( '<ul>' + data.on_screen_notifications + '</ul>' ) );

			// Ignore all view notifications.
			$.each(
				removedItems,
				function( index, id ) {
					var removedItem = notifications.find( '[data-notification-id=' + id + ']' );

					if ( removedItem.length ) {
						removedItem.closest( '.read-item' ).remove();
					}
				}
			);

			var appendItems = notifications.find( '.read-item' );

			appendItems.each(
				function( index, item ) {
					var id = $( item ).find( '.actions .action-close' ).data( 'notification-id' );

					if ( '-1' == $.inArray( id, animatedItems ) ) {
						$( item ).addClass( 'pull-animation' );
						animatedItems.push( id );
						newItems.push( id );
					} else {
						$( item ).removeClass( 'pull-animation' );
					}
				}
			);

			// Remove brder when new item is appear.
			if ( newItems.length ) {
				appendItems.each(
					function( index, item ) {
						var id = $( item ).find( '.actions .action-close' ).data( 'notification-id' );
						if ( '-1' == $.inArray( id, newItems ) ) {
							$( item ).removeClass( 'recent-item' );
							var borderItems = list.data( 'border-items' );
							borderItems.push( id );
							list.attr( 'data-border-items', JSON.stringify( borderItems ) );

						}
					}
				);
			}

			// Store animated notification id in 'animated-items' data attribute.
			list.attr( 'data-animated-items', JSON.stringify( animatedItems ) );

			if ( ! appendItems.length ) {
				return;
			}

			// Show all notificaitons.
			wrap.removeClass( 'close-all-items' );

			// Set class 'bb-more-item' in item when more than three notifications.
			appendItems.eq( 2 ).nextAll().addClass( 'bb-more-item' );

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
			var wrap        = $( '.bb-onscreen-notification' ),
				list        = wrap.find( '.notification-list' ),
				borderItems = list.data( 'border-items' );
			// newItems     = [];

			// Remove border for single notificaiton after 30s later.
			list.find( '.read-item' ).each(
				function( index, item ) {
					var id = $( item ).find( '.actions .action-close' ).data( 'notification-id' );

					if ( '-1' != $.inArray( id, borderItems ) ) {
						return;
					}

					$( item ).addClass( 'recent-item' );
				}
			);

			// Store removed notification id in 'auto-removed-items' data attribute.
			list.attr( 'data-border-items', JSON.stringify( borderItems ) );
		},

		/**
		 * Notification count in browser tab.
		 */
		browserTabCountNotification: function() {
			var wrap     = $( '.bb-onscreen-notification' ),
				list     = wrap.find( '.notification-list' ),
				items    = list.find( '.read-item' ),
				titleTag = $( 'html' ).find( 'title' ),
				title    = wrap.data( 'title-tag' );

			if ( items.length > 0 ) {
				titleTag.text( '(' + items.length + ') ' + title );
			} else {
				titleTag.text( title );
			}
		},

		/**
		 * Inject notification on browser tab.
		 */
		browserTabFlashNotification: function() {
			var wrap      = $( '.bb-onscreen-notification' ),
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
				notification = items.first().find( '.notification-content .bb-full-link a' ).text(),
				titleTag     = $( 'html' ).find( 'title' ),
				title        = wrap.attr( 'data-title-tag' ),
				flashStatus  = wrap.attr( 'data-flash-status' ),
				flashItems   = list.data( 'flash-items' );

			if ( ! document.hidden ) {
				items.each(
					function( index, item ) {
						var id = $( item ).find( '.actions .action-close' ).attr( 'data-notification-id' );

						if ( '-1' == $.inArray( id, flashItems ) ) {
							flashItems.push( id );
						}
					}
				);

				list.attr( 'data-flash-items', JSON.stringify( flashItems ) );
			}

			if ( ( ! document.hidden && window.bbFlashNotification ) || items.length <= 0 ) {
				clearInterval( window.bbFlashNotification );
				wrap.attr( 'data-flash-status', 'default_title' );
				titleTag.text( title );
				return;
			}

			if ( 'default_title' === flashStatus ) {
				titleTag.text( '(' + items.length + ') ' + title );
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
				removedItems = list.data( 'auto-removed-items' ),
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
			list.find( '.read-item' ).each(
				function( index, item ) {
					var id = $( item ).find( '.actions .action-close' ).data( 'notification-id' );

					if ( '-1' != $.inArray( id, removedItems ) ) {
						return;
					}

					removedItems.push( id );

					setTimeout(
						function() {
							if ( list.find( '.actions .action-close[data-notification-id=' + id + ']' ).length ) {
								list.find( '.actions .action-close[data-notification-id=' + id + ']' ).trigger( 'click' );
							}
						},
						1000 * hideAfter
					);
				}
			);

			// Store removed notification id in 'auto-removed-items' data attribute.
			list.attr( 'data-auto-removed-items', JSON.stringify( removedItems ) );
		},

		/**
		 * Click event for remove single notification.
		 */
		notificationRemovedAction: function() {
			$( '.bb-onscreen-notification .notification-list' ).on(
				'click',
				'.action-close',
				function(e) {
					e.preventDefault();
					bp.Nouveau.removeOnScreenNotification( this );
				}
			);
		},

		/**
		 * Remove single notification.
		 */
		removeOnScreenNotification: function( self ) {
			var list         = $( self ).closest( '.notification-list' ),
				item         = $( self ).closest( '.read-item' ),
				id           = $( self ).data( 'notification-id' ),
				removedItems = list.data( 'removed-items' );

			item.addClass( 'close-item' );

			setTimeout(
				function() {
					removedItems.push( id );

					// Set the removed notification id in data-removed-items attribute.
					list.attr( 'data-removed-items', JSON.stringify( removedItems ) );
					item.remove();
					bp.Nouveau.browserTabCountNotification();
					bp.Nouveau.visibilityOnScreenClearButton();

					// After removed get, rest of the notification.
					var items = list.find( '.read-item' );

					if ( items.length < 4 ) {
						list.removeClass( 'bb-more-than-3' );
					}

					// items.first().addClass( 'recent-item' );
					items.slice( 0, 3 ).removeClass( 'bb-more-item' );

				},
				500
			);
		},

		/**
		 * Remove all notifications.
		 */
		removeAllNotification: function() {
			$( '.bb-onscreen-notification .bb-remove-all-notification' ).on(
				'click',
				'.action-close',
				function(e) {
					e.preventDefault();

					var list         = $( this ).closest( '.bb-onscreen-notification' ).find( '.notification-list' ),
						items        = list.find( '.read-item' ),
						removedItems = list.data( 'removed-items' );

					// Collect all removed notification ids.
					items.each(
						function( index, item ) {
							var id = $( item ).find( '.actions .action-close' ).data( 'notification-id' );

							if ( id ) {
								removedItems.push( id );
							}
						}
					);

					// Set all removed notification ids in data-removed-items attribute.
					list.attr( 'data-removed-items', JSON.stringify( removedItems ) );
					items.remove();
					bp.Nouveau.browserTabCountNotification();
					bp.Nouveau.visibilityOnScreenClearButton();
					list.closest( '.bb-onscreen-notification' ).addClass( 'close-all-items' );
					$( '.toast-messages-list > li' ).each( function () {
						$( this ).removeClass( 'pull-animation' ).addClass( 'close-item' ).delay( 500 ).remove();
					} );
					$( '.toast-messages-list > li' ).each( function () {
						$( this ).removeClass( 'pull-animation' ).addClass( 'close-item' ).delay( 500 ).remove();
					} );
					list.removeClass( 'bb-more-than-3' );
				}
			);
		},

		/**
		 * Set title tag in notification data attribute.
		 */
		setTitle: function() {
			var title = $( 'html head' ).find( 'title' ).text();
			$( '.bb-onscreen-notification' ).attr( 'data-title-tag', title );
		},

		/**
		 * Set title tag in notification data attribute.
		 */
		visibilityOnScreenClearButton: function() {
			var wrap  = $( '.bb-onscreen-notification' ),
				list  = wrap.find( '.notification-list' ),
				items = list.find( '.read-item' );

			if ( items.length > 1 ) {
				wrap.removeClass( 'single-notification' );
				wrap.addClass( 'active-button' );
				wrap.find( '.bb-remove-all-notification .action-close' ).fadeIn( 600 );
			} else {
				wrap.addClass( 'single-notification' );
				wrap.removeClass( 'active-button' );
				wrap.find( '.bb-remove-all-notification .action-close' ).fadeOut( 200 );
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
				object     = item.data( 'bp-item-component' ), nonce = '', component = item.data( 'bp-used-to-component' );

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

			if ( 'is_friend' !== action ) {

				if ( ( undefined !== BP_Nouveau[ action + '_confirm' ] && false === window.confirm( BP_Nouveau[ action + '_confirm' ] ) ) || target.hasClass( 'pending' ) ) {
					return false;
				}

			}

			// show popup if it is leave_group action.
			var leave_group_popup        = $( '.bb-leave-group-popup' );
			var leave_group__name        = $( target ).data( 'bb-group-name' );
			var leave_group_anchor__link = $( target ).data( 'bb-group-link' );
			if ( 'leave_group' === action && 'true' !== $( target ).attr( 'data-popup-shown' ) ) {
				if ( leave_group_popup.length ) {
					leave_group_popup.find( '.bb-leave-group-content .bb-group-name' ).html( '<a href="' + leave_group_anchor__link + '">' + leave_group__name + '</a>' );
					$( 'body' ).find( '[data-current-anchor="true"]' ).removeClass( 'bp-toggle-action-button bp-toggle-action-button-hover' ).addClass( 'bp-toggle-action-button-clicked' ); // Add clicked class manually to run function.
					leave_group_popup.show();
					$( target ).attr( 'data-current-anchor', 'true' );
					$( target ).attr( 'data-popup-shown', 'true' );
					return false;
				}
			} else {
				$( 'body' ).find( '[data-popup-shown="true"]' ).attr( 'data-popup-shown' , 'false' );
				$( 'body' ).find( '[data-current-anchor="true"]' ).attr( 'data-current-anchor' , 'false' );
				leave_group_popup.find( '.bb-leave-group-content .bb-group-name' ).html( '' );
				leave_group_popup.hide();
			}

			// show popup if it is is_friend action.
			var remove_connection_popup = $( '.bb-remove-connection' );
			var member__name            = $( target ).data( 'bb-user-name' );
			var member_link             = $( target ).data( 'bb-user-link' );
			if ( 'is_friend' === action && 'opened' !== $( target ).attr( 'data-popup-shown' ) ) {
				if ( remove_connection_popup.length ) {
					remove_connection_popup.find( '.bb-remove-connection-content .bb-user-name' ).html( '<a href="' + member_link + '">' + member__name + '</a>' );
					$( 'body' ).find( '[data-current-anchor="true"]' ).removeClass( 'bp-toggle-action-button bp-toggle-action-button-hover' ).addClass( 'bp-toggle-action-button-clicked' ); // Add clicked class manually to run function.
					remove_connection_popup.show();
					$( target ).attr( 'data-current-anchor', 'true' );
					$( target ).attr( 'data-popup-shown', 'opened' );
					return false;
				}
			} else {
				$( 'body' ).find( '[data-popup-shown="opened"]' ).attr( 'data-popup-shown' , 'closed' );
				$( 'body' ).find( '[data-current-anchor="true"]' ).attr( 'data-current-anchor' , 'false' );
				remove_connection_popup.find( '.bb-remove-connection-content .bb-user-name' ).html( '' );
				remove_connection_popup.hide();
			}

			// Find the required wpnonce string.
			// if  button element set we'll have our nonce set on a data attr.
			// Check the value & if exists split the string to obtain the nonce string.
			// if no value, i.e false, null then the href attr is used.
			if ( nonceUrl ) {
				nonce = self.getLinkParams( nonceUrl, '_wpnonce' );
			} else {
				if ( 'undefined' === typeof target.prop( 'href' ) ) {
					nonce = self.getLinkParams( target.attr( 'href' ), '_wpnonce' );
				} else {
					nonce = self.getLinkParams( target.prop( 'href' ), '_wpnonce' );
				}
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

			var current_page = '';
			if ( ( $( document.body ).hasClass( 'directory' ) && $( document.body ).hasClass( 'members' ) ) || $( document.body ).hasClass( 'group-members' ) ) {
				current_page = 'directory';
			} else if ( $( document.body ).hasClass( 'bp-user' ) ) {
				current_page = 'single';
			}

			var button_clicked  = 'primary';
			var button_activity = ( 'single' === current_page ) ? target.closest( '.header-dropdown' ) : target.closest( '.footer-button-wrap' );

			if ( typeof button_activity.length !== 'undefined' && button_activity.length > 0 ) {
				button_clicked = 'secondary';
			}

			component = 'undefined' === typeof component ? object : component;

			self.ajax(
				{
					action: object + '_' + action,
					item_id: item_id,
					current_page: current_page,
					button_clicked: button_clicked,
					component: component,
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

						if (
							'undefined' !== typeof response.data.is_group_subscription &&
							true === response.data.is_group_subscription &&
							'undefined' !== typeof response.data.feedback
						) {
							$( document ).trigger(
								'bb_trigger_toast_message',
								[
									'',
									'<div>' + response.data.feedback + '</div>',
									'error',
									null,
									true
								]
							);
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

							if (
								'undefined' !== typeof response.data.is_group_subscription &&
								true === response.data.is_group_subscription &&
								'undefined' !== typeof response.data.feedback
							) {
								$( document ).trigger(
									'bb_trigger_toast_message',
									[
										'',
										'<div>' + response.data.feedback + '</div>',
										'info',
										null,
										true
									]
								);
							}
						}

						// User main nav update friends counts.
						if ( $( '#friends-personal-li' ).length ) {
							var friend_with_count    = $( '#friends-personal-li a span' );
							var friend_without_count = $( '#friends-personal-li a' );

							// Check friend count set.
							if ( undefined !== response.data.is_user && response.data.is_user && undefined !== response.data.friend_count ) {
								// Check friend count > 0 then show the count span.
								if ( '0' !== response.data.friend_count ) {
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
								if ( '0' !== response.data.friend_count ) {
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

						if ( 'follow' === object && item.find( '.followers-wrap' ).length > 0 && typeof response.data.count !== 'undefined' && response.data.count !== '' ) {
							item.find( '.followers-wrap' ).replaceWith( response.data.count );
						}

						target.parent().replaceWith( response.data.contents );
					}
				}
			).fail(
				function () {

					if ( ['unsubscribe', 'subscribe'].includes( action ) ) {
						var title = $( target ).data( 'bb-group-name' );

						if ( 25 < title.length ) {
							title = title.substring( 0, 25 ) + '...';
						}

						var display_error = '<div>' + BP_Nouveau.subscriptions.error + '<strong>' + title + '</strong>.</div>';
						if ( 'subscribe' === action ) {
							display_error = '<div>' + BP_Nouveau.subscriptions.subscribe_error + '<strong>' + title + '</strong></div>';
						}
						jQuery( document ).trigger(
							'bb_trigger_toast_message',
							[
								'',
								display_error,
								'error',
								null,
								true
							]
						);
					}
					target.removeClass( 'pending loading' );
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
		 * [buttonHover description]
		 *
		 * @param  {[type]} event [description]
		 * @return {[type]}       [description]
		 */
		buttonHover: function ( event ) {
			var target = $( event.currentTarget ), action = target.data( 'bp-btn-action' ),
				item   = target.closest( '[data-bp-item-id]' ), item_id = item.data( 'bp-item-id' ),
				object = item.data( 'bp-item-component' );

			// Simply let the event fire if we don't have needed values.
			if ( ! action || ! item_id || ! object ) {
				return event;
			}

			// Stop event propagation.
			event.preventDefault();

			if ( target.hasClass( 'bp-toggle-action-button' ) ) {
				if (
					target.hasClass( 'group-subscription' ) &&
					'undefined' !== typeof target.data( 'title' ) &&
					'undefined' !== typeof target.data( 'title-displayed' ) &&
					0 === target.data( 'title' ).replace( /<(.|\n)*?>/g, '' ).length &&
					0 === target.data( 'title-displayed' ).replace( /<(.|\n)*?>/g, '' ).length
				) {
					target.removeClass( 'bp-toggle-action-button' );
					target.addClass( 'bp-toggle-action-button-hover' );
					return false;
				}

				// support for buddyboss theme for button actions and icons and texts.
				if ( $( document.body ).hasClass( 'buddyboss-theme' ) && typeof target.data( 'balloon' ) !== 'undefined' ) {
					if ( ! target.hasClass( 'following' ) ) {
						target.attr( 'data-balloon', target.data( 'title' ).replace( /<(.|\n)*?>/g, '' ) );
					}
					target.find( 'span' ).html( target.data( 'title' ) );
					target.html( target.data( 'title' ) );
				} else {
					target.html( target.data( 'title' ) );
				}

				target.removeClass( 'bp-toggle-action-button' );
				target.addClass( 'bp-toggle-action-button-hover' );
				return false;
			}
		},

		/**
		 * [buttonHoverout description]
		 *
		 * @param  {[type]} event [description]
		 * @return {[type]}       [description]
		 */
		buttonHoverout: function ( event ) {
			var target = $( event.currentTarget );

			if ( target.hasClass( 'bp-toggle-action-button-hover' ) && ! target.hasClass( 'loading' ) ) {

				if (
					target.hasClass( 'group-subscription' ) &&
					'undefined' !== typeof target.data( 'title' ) &&
					'undefined' !== typeof target.data( 'title-displayed' ) &&
					0 === target.data( 'title' ).replace( /<(.|\n)*?>/g, '' ).length &&
					0 === target.data( 'title-displayed' ).replace( /<(.|\n)*?>/g, '' ).length
				) {
					target.removeClass( 'bp-toggle-action-button-hover' ); // remove class to detect event.
					target.addClass( 'bp-toggle-action-button' ); // add class to detect event to confirm.
					return false;
				}

				// support for BuddyBoss theme for button actions and icons and texts.
				if ( $( document.body ).hasClass( 'buddyboss-theme' ) && typeof target.data( 'balloon' ) !== 'undefined' ) {
					if ( ! target.hasClass( 'following' ) ) {
						target.attr( 'data-balloon', target.data( 'title-displayed' ).replace( /<(.|\n)*?>/g, '' ) );
					}
					target.find( 'span' ).html( target.data( 'title-displayed' ) );
					target.html( target.data( 'title-displayed' ) );
				} else {
					target.html( target.data( 'title-displayed' ) ); // change text to displayed context.
				}

				target.removeClass( 'bp-toggle-action-button-hover' ); // remove class to detect event.
				target.addClass( 'bp-toggle-action-button' ); // add class to detect event to confirm.
			}
		},

		/**
		 * [awaitingButtonHover description]
		 *
		 * @param  {[type]} event [description]
		 * @return {[type]}       [description]
		 */
		awaitingButtonHover: function ( event ) {
			var target = $( event.currentTarget );

			// Stop event propagation.
			event.preventDefault();

			if ( target.hasClass( 'bp-toggle-action-button' ) ) {

				// support for buddyboss theme for button actions and icons and texts.
				if ( $( document.body ).hasClass( 'buddyboss-theme' ) && typeof target.data( 'balloon' ) !== 'undefined' ) {
					if ( ! target.hasClass( 'following' ) ) {
						target.attr( 'data-balloon', target.data( 'title' ).replace( /<(.|\n)*?>/g, '' ) );
					}
					target.find( 'span' ).html( target.data( 'title' ) );
					target.html( target.data( 'title' ) );
				} else {
					target.html( target.data( 'title' ) );
				}

				target.removeClass( 'bp-toggle-action-button' );
				target.addClass( 'bp-toggle-action-button-hover' );
				return false;
			}
		},

		/**
		 * [buttonHoverout description]
		 *
		 * @param  {[type]} event [description]
		 * @return {[type]}       [description]
		 */
		awaitingButtonHoverout: function ( event ) {
			var target = $( event.currentTarget );

			if ( target.hasClass( 'bp-toggle-action-button-hover' ) && ! target.hasClass( 'loading' ) ) {

				// support for BuddyBoss theme for button actions and icons and texts.
				if ( $( document.body ).hasClass( 'buddyboss-theme' ) && typeof target.data( 'balloon' ) !== 'undefined' ) {
					if ( ! target.hasClass( 'following' ) ) {
						target.attr( 'data-balloon', target.data( 'title-displayed' ).replace( /<(.|\n)*?>/g, '' ) );
					}
					target.find( 'span' ).html( target.data( 'title-displayed' ) );
					target.html( target.data( 'title-displayed' ) );
				} else {
					target.html( target.data( 'title-displayed' ) ); // change text to displayed context.
				}

				target.removeClass( 'bp-toggle-action-button-hover' ); // remove class to detect event.
				target.addClass( 'bp-toggle-action-button' ); // add class to detect event to confirm.
			}
		},

		/**
		 * [Leave Group Action]
		 *
		 * @param event
		 */
		leaveGroupAction: function ( event ) {
			event.preventDefault();
			$( 'body' ).find( '[data-current-anchor="true"]' ).removeClass( 'bp-toggle-action-button bp-toggle-action-button-hover' ).addClass( 'bp-toggle-action-button-clicked' );
			$( 'body' ).find( '[data-current-anchor="true"]' ).trigger( 'click' );
		},

		/**
		 * [Leave Group Close]
		 *
		 * @param event
		 */
		leaveGroupClose: function ( event ) {
			event.preventDefault();
			var target            = $( event.currentTarget );
			var leave_group_popup = $( target ).closest( '.bb-leave-group-popup' );

			$( 'body' ).find( '[data-current-anchor="true"]' ).attr( 'data-current-anchor' , 'false' );
			$( 'body' ).find( '[data-popup-shown="true"]' ).attr( 'data-popup-shown' , 'false' );
			leave_group_popup.find( '.bb-leave-group-content .bb-group-name' ).html( '' );
			leave_group_popup.hide();
		},

		/**
		 * [Remove Connection Action]
		 *
		 * @param event
		 */
		removeConnectionAction: function ( event ) {
			event.preventDefault();
			$( 'body' ).find( '[data-current-anchor="true"]' ).removeClass( 'bp-toggle-action-button bp-toggle-action-button-hover' ).addClass( 'bp-toggle-action-button-clicked' );
			$( 'body' ).find( '[data-current-anchor="true"]' ).trigger( 'click' );
		},

		/**
		 * [Remove Connection Close]
		 *
		 * @param event
		 */
		removeConnectionClose: function ( event ) {
			event.preventDefault();
			var target            = $( event.currentTarget );
			var leave_group_popup = $( target ).closest( '.bb-remove-connection' );

			$( 'body' ).find( '[data-current-anchor="true"]' ).attr( 'data-current-anchor' , 'false' );
			$( 'body' ).find( '[data-popup-shown="opened"]' ).attr( 'data-popup-shown' , 'closed' );
			leave_group_popup.find( '.bb-remove-connection-content .bb-user-name' ).html( '' );
			leave_group_popup.hide();
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

		threadListBlockPopup: function ( e ) {
			e.preventDefault();
			var contentId   = $( this ).data( 'bp-content-id' );
			var contentType = $( this ).data( 'bp-content-type' );
			var nonce       = $( this ).data( 'bp-nonce' );
			var currentHref = $( this ).attr( 'href' );

			if ( 'undefined' !== typeof contentId && 'undefined' !== typeof contentType && 'undefined' !== typeof nonce ) {
				$( document ).find( '.bp-report-form-err' ).empty();
				var mf_content = $( currentHref );
				mf_content.find( '.bp-content-id' ).val( contentId );
				mf_content.find( '.bp-content-type' ).val( contentType );
				mf_content.find( '.bp-nonce' ).val( nonce );
			}
			if ( $( '#message-threads .block-member' ).length > 0 ) {
				$( '#message-threads .block-member' ).magnificPopup(
					{
						items: {
							src: currentHref,
							type: 'inline'
						},
					}
				).magnificPopup( 'open' );
			}
		},

		threadListReportPopup: function ( e ) {
			e.preventDefault();
			var contentId   = $( this ).data( 'bp-content-id' );
			var contentType = $( this ).data( 'bp-content-type' );
			var nonce       = $( this ).data( 'bp-nonce' );
			var currentHref = $( this ).attr( 'href' );
			var reportType  = $( this ).attr( 'reported_type' );
			var mf_content  = $( currentHref );

			if ( 'undefined' !== typeof contentId && 'undefined' !== typeof contentType && 'undefined' !== typeof nonce ) {
				$( document ).find( '.bp-report-form-err' ).empty();
				mf_content.find( '.bp-content-id' ).val( contentId );
				mf_content.find( '.bp-content-type' ).val( contentType );
				mf_content.find( '.bp-nonce' ).val( nonce );
			}
			if ( $( '#message-threads .report-content' ).length > 0 ) {

				$( '#bb-report-content .form-item-category' ).show();
				if ( 'user_report' === contentType ) {
					$( '#bb-report-content .form-item-category.content' ).hide();
				} else {
					$( '#bb-report-content .form-item-category.members' ).hide();
				}

				$( '#bb-report-content .form-item-category:visible:first label input[type="radio"]' ).attr( 'checked', true );

				if ( ! $( '#bb-report-content .form-item-category:visible label input[type="radio"]' ).length ) {
					$( '#report-category-other' ).attr( 'checked', true );
					$( '#report-category-other' ).trigger( 'click' );
					$( 'label[for="report-category-other"]' ).hide();
				}

				if ( 'undefined' !== typeof reportType ) {
					mf_content.find( '.bp-reported-type' ).text( reportType );
				}

				$( '#message-threads .report-content' ).magnificPopup(
					{
						items: {
							src: currentHref,
							type: 'inline'
						},
					}
				).magnificPopup( 'open' );
			}
		},

		reportPopUp: function () {
			if ( $( '.report-content, .block-member' ).length > 0 ) {
				$( '.report-content, .block-member' ).magnificPopup(
					{
						type: 'inline',
						midClick: true,
						callbacks: {
							open: function () {
								console.log( 'called' );
								$( '#notes-error' ).hide();
								var contentId   = this.currItem.el.data( 'bp-content-id' );
								var contentType = this.currItem.el.data( 'bp-content-type' );
								var nonce       = this.currItem.el.data( 'bp-nonce' );
								var reportType  = this.currItem.el.attr( 'reported_type' );
								$( '#bb-report-content .form-item-category' ).show();
								if ( 'user_report' === contentType ) {
									$( '#bb-report-content .form-item-category.content' ).hide();
								} else {
									$( '#bb-report-content .form-item-category.members' ).hide();
								}

								$( '#bb-report-content .form-item-category:visible:first label input[type="radio"]' ).attr( 'checked', true );

								if ( ! $( '#bb-report-content .form-item-category:visible label input[type="radio"]' ).length ) {
									$( '#report-category-other' ).attr( 'checked', true );
									$( '#report-category-other' ).trigger( 'click' );
									$( 'label[for="report-category-other"]' ).hide();
								}

								var content_report = $( '#content-report' );
								content_report.find( '.bp-reported-type' ).text( this.currItem.el.data( 'reported_type' ) );
								if ( 'undefined' !== typeof reportType ) {
									content_report.find( '.bp-reported-type' ).text( reportType );
								}

								if ( 'undefined' !== typeof contentId && 'undefined' !== typeof contentType && 'undefined' !== typeof nonce ) {
									$( document ).find( '.bp-report-form-err' ).empty();
									var mf_content = $( '.mfp-content' );
									mf_content.find( '.bp-content-id' ).val( contentId );
									mf_content.find( '.bp-content-type' ).val( contentType );
									mf_content.find( '.bp-nonce' ).val( nonce );
								}
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
					} else {
						$( this ).closest( '.moderation-popup' ).find( '.bp-other-report-cat' ).closest( '.form-item' ).addClass( 'bp-hide' );
					}
				}
			);

			$( '#bb-report-content' ).submit(
				function ( e ) {

					if ( $( '#report-category-other' ).is( ':checked' ) && '' === $( '#report-note' ).val() ) {
						$( '#notes-error' ).show();
						return false;
					}

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
								jQuery( document ).trigger(
									'bb_trigger_toast_message',
									[ '', response.data.toast_message, 'info', null, true ]
								);
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
								var contentType = undefined !== this.currItem.el.attr( 'reported_type' ) ? this.currItem.el.attr( 'reported_type' ) : this.currItem.el.data( 'reported_type' );
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
		togglePassword: function () {
			$( document ).on(
				'click',
				'.bb-toggle-password, .bb-hide-pw',
				function ( e ) {
					e.preventDefault();
					var $this = $( this ), $input;

					if ( $this.hasClass( 'bb-hide-pw' ) ) {
						$input = $this.closest( '.password-toggle' ).find( 'input' );
					} else {
						$input = $this.next( 'input' );
					}
					var $default_type = $input.data( 'type' ) ? $input.data( 'type' ) : 'text';
					$this.toggleClass( 'bb-show-pass' );
					if ( $this.hasClass( 'bb-show-pass' ) ) {
						$input.attr( 'type', $default_type );
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
				$( '.post-emoji.active, .emojionearea-button.active' ).removeClass( 'active' );
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
					$( '.post-emoji.active, .emojionearea-button.active' ).removeClass( 'active' );
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
						console.log( err.message );
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

			if ( $( event.target ).hasClass( 'bb_more_options_action' ) || $( event.target ).parent().hasClass( 'bb_more_options_action' ) ) {
				event.preventDefault();

				if ( $( event.target ).closest( '.bb_more_options' ).find( '.bb_more_options_list' ).hasClass( 'is_visible' ) ) {
					$( '.bb_more_options' ).find( '.bb_more_options_list' ).removeClass( 'is_visible' );
				} else {
					$( '.bb_more_options' ).find( '.bb_more_options_list' ).removeClass( 'is_visible' );
					$( event.target ).closest( '.bb_more_options' ).find( '.bb_more_options_list' ).addClass( 'is_visible' );
				}

			} else {
				$( '.bb_more_options' ).find( '.bb_more_options_list' ).removeClass( 'is_visible' );
				$( '.optionsOpen' ).removeClass( 'optionsOpen' );
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
				var attempts 	  = 0;
				var timer         = setInterval(
					function () {
						if (video.readyState > 0) {
							videoDuration  = video.duration.toFixed( 2 );
							var timeupdate = function () {
								if ( snapImage() ) {
									video.removeEventListener( 'timeupdate', timeupdate );
									video.pause();
								}
							};
							var snapImage = function () {
								var canvas    = document.createElement( 'canvas' );
								canvas.width  = video.videoWidth;
								canvas.height = video.videoHeight;
								canvas.getContext( '2d' ).drawImage( video, 0, 0, canvas.width, canvas.height );
								var image   = canvas.toDataURL();
								var success = image.length > 50000;
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
								} else {
									if( attempts >= 2 ) {
										$( file.previewElement ).closest( '.dz-preview' ).addClass( 'dz-has-no-thumbnail' );
										clearInterval( timer );
									}
								}
								return success;
							};
							video.addEventListener( 'timeupdate', timeupdate );
							video.preload     = 'metadata';
							video.src         = url;
							video.muted       = true;
							video.playsInline = true;
							if ( videoDuration != null ) {
								video.currentTime = Math.floor( videoDuration ); // Seek fixed second before capturing thumbnail.
							}
							video.play();
							clearInterval( timer );
						}
						if( attempts >= 2 ) {
							$( file.previewElement ).closest( '.dz-preview' ).addClass( 'dz-has-no-thumbnail' );
							clearInterval( timer );
						}
						attempts++;
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

		},

		/**
		 *  Click event on more button of following widget.
		 */
		bbWidgetMoreFollowing: function ( event ) {
			var target = $( event.currentTarget ),
				link   = target.attr( 'href' );
			var parts  = link.split( '#' );
			if ( parts.length > 1 ) {
				var hash_text = parts.pop();
				if ( hash_text && $( '[data-bp-scope="' + hash_text + '"]' ).length > 0 ) {
					$( '[data-bp-scope="' + hash_text + '"] a' ).trigger( 'click' );
					return false;
				}
			}
		},

		/**
		 * [toggleAccordion description]
		 * @return {[type]} [description]
		 */
		 toggleAccordion: function() {
			var accordion = $( this ).closest( '.bb-accordion' );
			if( accordion.find( '.bb-accordion_trigger' ).attr( 'aria-expanded' ) == 'true' ) {
				accordion.find( '.bb-accordion_trigger' ).attr( 'aria-expanded', 'false' );
				accordion.find( '.bb-icon-angle-up' ).removeClass( 'bb-icon-angle-up' ).addClass( 'bb-icon-angle-down' );
			} else {
				accordion.find( '.bb-accordion_trigger' ).attr( 'aria-expanded', 'true' );
				accordion.find( '.bb-icon-angle-down' ).removeClass( 'bb-icon-angle-down' ).addClass( 'bb-icon-angle-up' );
			}
			accordion.toggleClass('is_closed');
			accordion.find( '.bb-accordion_panel' ).slideToggle();
		},

		/**
		 *  Make Medium Editor buttons wrap.
		 *
		 *  @param  {JQuery node} editorWrap The jQuery node.
		 */
		mediumEditorButtonsWarp: function ( editorWrap ) { // Pass jQuery $(node).
			if ( editorWrap.hasClass( 'wrappingInitialised' ) ) { // Do not go through if it is initialed already.
				return;
			}
			editorWrap.addClass( 'wrappingInitialised' );
			var buttonsWidth = 0;
			editorWrap.find( '.medium-editor-toolbar-actions > li' ).each(
				function() {
					buttonsWidth += $( this ).outerWidth();
				}
			);
			if ( buttonsWidth > editorWrap.width() - 10 ) { // No need to calculate if space is available.
				editorWrap.data( 'childerWith', buttonsWidth );
				if ( buttonsWidth > editorWrap.width() ) {
					if ( editorWrap.find( '.medium-editor-toolbar-actions .medium-editor-action-more' ).length === 0 ) {
						editorWrap.find( '.medium-editor-toolbar-actions' ).append( '<li class="medium-editor-action-more"><button class="medium-editor-action medium-editor-action-more-button"><b></b></button><ul></ul></li>' );
					}
					editorWrap.find( '.medium-editor-action-more' ).show();
					buttonsWidth += editorWrap.find( '.medium-editor-toolbar-actions .medium-editor-action-more' ).outerWidth();
					$( editorWrap.find( '.medium-editor-action' ).get().reverse() ).each(
						function() {
							if ( $( this ).hasClass( 'medium-editor-action-more-button' ) ) {
								return;
							}
							if ( buttonsWidth > editorWrap.width() ) {
								buttonsWidth -= $( this ).outerWidth();
								editorWrap.find( '.medium-editor-action-more > ul' ).prepend( $( this ).parent() );
							}

						}
					);
				}
			} else { // If space is available then append <li> to parent again.
				if ( editorWrap.find( '.medium-editor-toolbar-actions .medium-editor-action-more' ).length ) {
					$( editorWrap.find( '.medium-editor-action-more ul > li' ) ).each(
						function() {
							if ( buttonsWidth + 35 < editorWrap.width() ) {
								buttonsWidth += $( this ).outerWidth();
								$( this ).insertBefore( editorWrap.find( '.medium-editor-action-more' ) );
							}
						}
					);
					if ( editorWrap.find( '.medium-editor-action-more ul > li' ).length === 0 ) {
						editorWrap.find( '.medium-editor-action-more' ).hide();
					}
				}
			}

			$( editorWrap ).find( '.medium-editor-action-more-button' ).on(
				'click',
				function( event ) {
					event.preventDefault();
					$( this ).parent( '.medium-editor-action-more' ).toggleClass( 'active' );
				}
			);

			$( editorWrap ).find( '.medium-editor-action-more ul .medium-editor-action' ).on(
				'click',
				function( event ) {
					event.preventDefault();
					$( this ).closest( '.medium-editor-action-more' ).toggleClass( 'active' );
				}
			);

			$( window ).one(
				'resize',
				function() { // Attach event once only.
					editorWrap.removeClass( 'wrappingInitialised' ); // Remove class to run trough again as screen has resized.
					$( editorWrap ).find( '.medium-editor-action-more ul .medium-editor-action' ).unbind( 'click' );
				}
			);

		},

		/**
		 *  Check if string is a valid URL
		 *
		 *  @param  {String} URL The URL to check.
		 *  @return {Boolean} Return true if it's URL or false if not.
		 */
		isURL: function ( URL ) {
			var regexp = /^(http:\/\/www\.|https:\/\/www\.|http:\/\/|https:\/\/)?[a-z0-9]+([\-\.]{1}[a-z0-9]+)*\.[a-z]{2,24}(:[0-9]{1,5})?(\/.*)?$/;
			return regexp.test( $.trim( URL ) );
		},

		/**
		 *  Close Action Popup
		 *
		 *  @param  {object} event The event object.
		 *  @return {function}
		 */
		closeActionPopup: function( event ) {
			event.preventDefault();
			$( this ).closest( '.bb-action-popup' ).hide();
		},

		/**
		 *  Show/Hide Search reset button
		 *
		 *  @return {function}
		 */
		directorySearchInput: function() {
			// Check if the current value of the input field is equal to the last recorded value,
			// OR if the current value is empty and there is no previously recorded value
			if( $( this ).val() === $( this ).data( 'last-value' ) || ( $( this ).val() === '' && $( this ).data( 'last-value' ) === undefined ) ) {
				// Return early to skip unnecessary actions
				return;
			}
			$( this ).data( 'last-value', $( this ).val() );

			var $form = $( this ).closest( '.search-form-has-reset' );
			var $resetButton = $form.find( '.search-form_reset' );

			if ( $( this ).val().length > 0 ) {
				$resetButton.show();
			} else {
				$resetButton.hide();

				// Trigger search event
				if( $form.hasClass( 'bp-invites-search-form') ) {
					$form.find( 'input[type="search"]').val('');
					$form.find( 'input[type="search"]').trigger( $.Event( 'search' ) );
				}
			}

			if ( !$( this ).hasClass( 'ui-autocomplete-input' ) ) {
				$form.find( '.search-form_submit' ).trigger( 'click' );
			}

		},

		/**
		 *  Reset search results
		 *
		 *  @param  {object} event The event object.
		 *  @return {function}
		 */
		resetDirectorySearch: function( e ) {
			e.preventDefault();
			var $form = $( this ).closest( 'form' );
			if ( $form.filter( '.bp-messages-search-form, .bp-dir-search-form' ).length > 0 ) {
				$form.find( 'input[type="search"]').val('');
				$form.find( '.search-form_submit' ).trigger( 'click' );
			} else {
				$form.find( '#bbp_search' ).val('');
			}

			$( this ).hide();

			// Trigger search event
			if ( $form.hasClass( 'bp-invites-search-form') ) {
				$form.find( 'input[type="search"]').val('');
				$form.find( 'input[type="search"]').trigger( $.Event( 'search' ) );
			}

		},

		/**
		 *  Show Action Popup
		 *
		 *  @param  {object} event The event object.
		 *  @return {function}
		 */
		showActionPopup: function( event ) {
			event.preventDefault();
			$( $( event.currentTarget ).attr( 'href' ) ).show();
		},

		/**
		 *  handle profile notification setting events
		 */
		profileNotificationSetting: function () {
			var self = this;
			self.profileNotificationSettingInputs(['.email', '.web', '.app'] );

			//Learn More section hide/show for mobile
			$( '.notification_info .notification_learn_more' ).click( function( e ) {
				e.preventDefault();

				$( this ).find( 'a span' ).toggleClass( function() {
					if ( $( this ).hasClass( 'bb-icon-chevron-down' ) ) {
						return 'bb-icon-chevron-up';
					} else {
						return 'bb-icon-chevron-down';
					}
				});
				$( this ).toggleClass( 'show' ).parent().find( '.notification_type' ).toggleClass( 'show' );

			});

			//Notification settings Mobile UI
			$( '.main-notification-settings' ).each( function() {
				self.NotificationMobileDropdown( $( this ).find( 'tr:not( .notification_heading )' ) );
			});

			$( document ).on( 'click', '.bb-mobile-setting ul li', function( e ) {
				e.preventDefault();
				if ( $( this ).find( 'input' ).is( ':checked' ) ) {
					$( this ).find( 'input' ).prop( 'checked', false );
					$( $( 'input#' + $( this ).find( 'label' ).attr( 'data-for' ) ) ).trigger( 'click' );
				} else {
					$( this ).find( 'input' ).prop( 'checked', true );
					$( $( 'input#' + $( this ).find( 'label' ).attr( 'data-for' ) ) ).trigger( 'click' );
				}
				self.NotificationMobileDropdown( $( this ).closest( 'tr' ) );
			});

			$( document ).on( 'click', '.bb-mobile-setting .bb-mobile-setting-anchor', function() {
				$( this ).parent().toggleClass( 'active');
				$( '.bb-mobile-setting' ).not( $( this ).parent() ).removeClass( 'active' );
			});

			$( document ).on( 'click', function( e ) {
				if ( ! $( e.target ).hasClass( 'bb-mobile-setting-anchor' ) ) {
					$( '.bb-mobile-setting' ).removeClass( 'active' );
				}
			});

		},

		/**
		 *  Add socialnetworks profile field type related class
		 */
		xProfileBlock: function () {
			$( '.profile-fields .field_type_socialnetworks' ).each( function () {
				$( this ).closest( '.bp-widget' ).addClass( 'social' );
			} );
		},

		/**
		 *  Enable Disable profile notification setting inputs
		 */
		profileNotificationSettingInputs: function ( node ) {
			for(var i = 0; i < node.length; i++){
				/* jshint ignore:start */
				(function (_i) {
					$( document ).on( 'click', '.main-notification-settings th' + node[_i] + ' input[type="checkbox"]', function() {
						if ( $( this ).is( ':checked' ) ) {
							$( '.main-notification-settings' ).find( 'td' + node[_i] ).removeClass( 'disabled' ).find( 'input' ).prop( 'disabled', false );
							$( '.main-notification-settings' ).find( '.bb-mobile-setting li' + node[_i] ).removeClass( 'disabled' ).find( 'input' ).prop( 'disabled', false );
						} else {
							$( '.main-notification-settings' ).find( 'td' + node[_i] ).addClass( 'disabled' ).find( 'input' ).prop( 'disabled', true );
							$( '.main-notification-settings' ).find( '.bb-mobile-setting li' + node[_i] ).addClass( 'disabled' ).find( 'input' ).prop( 'disabled', true );
						}
						bp.Nouveau.NotificationMobileDropdown( $( this ).closest( '#settings-form' ).find( 'tr:not( .notification_heading )' ) );
					});
				})(i);
				/* jshint ignore:end */
			}
		},

		/**
		 *  Notification Mobile UI
		 */
		NotificationMobileDropdown: function ( node ) {
			var textAll = $( '.main-notification-settings' ).data( 'text-all' );
			var textNone = $( '.main-notification-settings' ).data( 'text-none' );
			node.each( function() {
				var selected_text = '';
				var available_option = '';
				var nodeSelector = $( this ).find( 'td' ).length ? 'td' : 'th';
				var allInputsChecked = 0;
				$( this ).find( nodeSelector + ':not(:first-child)' ).each( function () {
					if ( $( this ).find( 'input[type="checkbox"]' ).length ) {
						var inputText = $( this ).find( 'label' ).text();
						var inputChecked = $( this ).find( 'input' ).is( ':checked' ) ? 'checked' : '';
						var inputDisabled = $( this ).hasClass( 'disabled' ) ? ' disabled' : '';
						available_option += '<li class="'+ inputText.toLowerCase() + inputDisabled +'"><input type="checkbox" class="bs-styled-checkbox" '+ inputChecked +' /><label data-for="'+ $( this ).find( 'input[type="checkbox"]' ).attr( 'id' ) +'">'+ inputText +'</label></li>';
					}
					if ( $( this ).hasClass( 'disabled' ) ) {
						return;
					}
					if ( ! $( this ).find( 'input:checked' ).length ) {
						return;
					}
					selected_text += selected_text === '' ? $( this ).find( 'input[type="checkbox"] + label' ).text().trim() : ', ' + $( this ).find( 'input[type="checkbox"] + label' ).text().trim();
					allInputsChecked++;
				} );
				if ( allInputsChecked === $( this ).find( nodeSelector + ':not(:first-child) input[type="checkbox"]' ).length ) {
					if ( $( this ).find( nodeSelector + ':not(:first-child) input[type="checkbox"]' ).length == 1 ) {
						selected_text = selected_text;
					} else {
						selected_text = textAll;
					}
				} else {
					selected_text = selected_text === '' ? textNone : selected_text;
				}
				if ( $( this ).find( nodeSelector + ':first-child .bb-mobile-setting' ).length === 0 ) {
					$( this ).find( nodeSelector + ':first-child' ).append( '<div class="bb-mobile-setting"><span class="bb-mobile-setting-anchor">' + selected_text + '</span><ul></ul></div>' );
				} else {
					$( this ).find( nodeSelector + ':first-child .bb-mobile-setting .bb-mobile-setting-anchor' ).text( selected_text );
				}
				$( this ).find( nodeSelector + ':first-child .bb-mobile-setting ul' ).html( '' );
				$( this ).find( nodeSelector + ':first-child .bb-mobile-setting ul' ).append( available_option );
			});
		},

		/**
		 *  Register Dropzone Global Progress UI
		 *
		 *  @param  {object} dropzone The Dropzone object.
		 *  @return {function}
		 */
		 dropZoneGlobalProgress: function( dropzone ) {

			if ( $( dropzone.element ).find( '.dz-global-progress' ).length == 0 ) {
				$( dropzone.element ).append( '<div class="dz-global-progress"><div class="dz-progress-bar-full"><span class="dz-progress"></span></div><p></p><span class="bb-icon-f bb-icon-times dz-remove-all"></span></div>' );
				$( dropzone.element ).addClass( 'dz-progress-view' );
				$( dropzone.element ).find( '.dz-remove-all' ).click( function() {
					$.each( dropzone.files, function( index, file ) {
						dropzone.removeFile( file );
					});
				});
			}

			var message = '';
			var progress = 0,
				totalProgress = 0;
			if ( dropzone.files.length == 1 ) {
				$( dropzone.element ).addClass( 'dz-single-view' );
				message = 'Uploading <strong>' + dropzone.files[0].name + '</strong>';
				progress = dropzone.files[0].upload.progress;
			} else {
				$( dropzone.element ).removeClass( 'dz-single-view' );
				totalProgress = 0;
				$.each( dropzone.files, function( index, file ) {
					totalProgress += file.upload.progress;
				});
				progress = totalProgress / dropzone.files.length;
				message = 'Uploading <strong>' + dropzone.files.length + ' files</strong>';
			}

			$( dropzone.element ).find( '.dz-global-progress .dz-progress').css( 'width', progress + '%' );
			$( dropzone.element ).find( '.dz-global-progress > p').html( message );
		},

		userPresenceStatus: function() {
		 	// Active user on page load.
			window.bb_is_user_active = true;
			var idle_interval = parseInt( BB_Nouveau_Presence.idle_inactive_span ) * 1000;

			// setup the idle time user check.
			bp.Nouveau.userPresenceChecker( idle_interval );

			if ( '' !== BB_Nouveau_Presence.heartbeat_enabled && parseInt( BB_Nouveau_Presence.presence_interval ) <= 60 ) {
				$( document ).on( 'heartbeat-send', function ( event, data ) {
					if (
						'undefined' !== typeof window.bb_is_user_active &&
						true === window.bb_is_user_active
					) {
						var paged_user_id  = bp.Nouveau.getPageUserIDs();
						// Add user data to Heartbeat.
						data.presence_users = paged_user_id.join( ',' );
					}

				} );

				$( document ).on( 'heartbeat-tick', function ( event, data ) {
					// Check for our data, and use it.
					if ( ! data.users_presence ) {
						return;
					}

					bp.Nouveau.updateUsersPresence( data.users_presence );
				} );
			} else {
				setInterval(
					function () {
						var params = {};

						if (
							'undefined' !== typeof window.bb_is_user_active &&
							true === window.bb_is_user_active
						) {
							params.ids = bp.Nouveau.getPageUserIDs();
						}

						if (
							'undefined' !== typeof params.ids &&
							'undefined' !== typeof params.ids.length &&
							0 < params.ids.length
						) {
							var url = '1' === BB_Nouveau_Presence.native_presence ? BB_Nouveau_Presence.native_presence_url : BB_Nouveau_Presence.presence_rest_url;
							$.ajax(
								{
									type: 'POST',
									url: url,
									data: params,
									beforeSend: function ( xhr ) {
										xhr.setRequestHeader( 'X-WP-Nonce', BB_Nouveau_Presence.rest_nonce );
									},
									success: function ( data ) {
										// Check for our data, and use it.
										if ( ! data ) {
											return;
										}

										bp.Nouveau.updateUsersPresence( data );
									}
								}
							);
						}
					},
					parseInt( BB_Nouveau_Presence.presence_default_interval ) * 1000 // 1 min.
				);
			}
		},

		getPageUserIDs: function() {
			var user_ids = [];
			var all_presence = $( document ).find( '.member-status[data-bb-user-id]' );
			if ( all_presence.length > 0 ) {
				all_presence.each( function () {
					var user_id = $( this ).attr( 'data-bb-user-id' );
					if ( $.inArray( parseInt( user_id ), user_ids ) == -1 ) {
						user_ids.push( parseInt( user_id ) );
					}
				} );
			}

			return user_ids;
		},

		updateUsersPresence: function ( presence_data ) {
			if ( presence_data && presence_data.length > 0 ) {
				$.each( presence_data, function ( index, user ) {
					bp.Nouveau.updateUserPresence( user.id, user.status );
				} );
			}
		},

		updateUserPresence: function( user_id, status ) {
			$( document )
				.find( '.member-status[data-bb-user-id="' + user_id + '"]' )
				.removeClass( 'offline online' )
				.addClass( status )
				.attr( 'data-bb-user-presence', status );
		},

		userPresenceChecker: function (inactive_timeout) {

			var wait = setTimeout( function () {
				window.bb_is_user_active = false;
			}, inactive_timeout );

			document.onmousemove = document.mousedown = document.mouseup = document.onkeydown = document.onkeyup = document.focus = function () {
				clearTimeout( wait );
				wait = setTimeout( function () {
					window.bb_is_user_active = false;
				}, inactive_timeout );
				window.bb_is_user_active = true;
			};
		},

		linkPreviews : {
			currentTarget: null,
			currentTargetForm: null,
			currentPreviewParent: null,
			controlsAdded :null,
			dataInput: null,
			loadedURLs: [],
			loadURLAjax: null,
			options: {},
			render: function( renderOptions ) {
				var self = this;
				// Link Preview Template
				var tmpl = $('#tmpl-bb-link-preview').html();

				// Compile the template
				var compiled = _.template(tmpl);

				var html = compiled( renderOptions );

				if( self.currentPreviewParent ) {
					self.currentPreviewParent.html( html );
				}

				if( self.options.link_loading === true || self.options.link_swap_image_button === 1 ) {
					return;
				}

				if( self.controlsAdded === null ){
					self.registerControls();
				}

				if( self.options.link_error === true ) {
					return;
				}

				if ( self.dataInput !== null && self.dataInput.length > 0 ) {

					var tmp_link_description = self.options.link_description;

					if ( self.options.link_embed ) {
						tmp_link_description = '';
					}

					var link_preview_data = {
						link_url: self.options.link_url,
						link_title: self.options.link_title,
						link_description: tmp_link_description,
						link_embed: self.options.link_embed,
						link_image: ( 'undefined' !== typeof self.options.link_images ) ? self.options.link_images[ self.options.link_image_index_save ] : '',
						link_image_index_save: self.options.link_image_index_save
					};

					self.dataInput.val( JSON.stringify( link_preview_data ) ).trigger('change');

				}

			},
			registerControls: function() {
				var self = this;
				self.displayNextPrevButtonView = function() {
					$( '.bb-url-scrapper-container #bb-url-prevPicButton' ).show();
					$( '.bb-url-scrapper-container #bb-url-nextPicButton' ).show();
					$( '.bb-url-scrapper-container #bb-link-preview-select-image' ).show();
					$( '.bb-url-scrapper-container #icon-exchange' ).hide();
					$( '.bb-url-scrapper-container #bb-link-preview-remove-image' ).hide();
				};

				$( self.currentPreviewParent ).on( 'click', '#bb-link-preview-remove-image', function( e ) {
					e.preventDefault();
					self.options.link_images = [];
					self.options.link_image_index = 0;
					self.options.link_image_index_save = '-1';
					self.render( self.options );
				});

				$( self.currentPreviewParent ).on( 'click', '#bb-close-link-suggestion', function( e ) {
					e.preventDefault();

					// Remove the link preview for the draft too.
					$( '#bb_link_url' ).val('');

					// Set default values.
					Object.assign( self.options, {
						link_success: false,
						link_error: false,
						link_error_msg: '',
						link_scrapping: false,
						link_images: [],
						link_image_index: 0,
						link_title: '',
						link_description: '',
						link_url: '',
						link_embed: false,
						link_swap_image_button: 0,
						link_image_index_save: '0',
					} );
					self.render( self.options );
				});

				$( self.currentPreviewParent ).on( 'click', '#icon-exchange', function( e ) {
					e.preventDefault();
					self.options.link_swap_image_button = 1;
					self.displayNextPrevButtonView();
				});

				$( self.currentPreviewParent ).on( 'click', '#bb-url-prevPicButton', function( e ) {
					e.preventDefault();
					var imageIndex = self.options.link_image_index;
					if ( imageIndex > 0 ) {
						Object.assign( self.options, {
							link_image_index : parseInt( imageIndex ) - 1,
							link_swap_image_button : 1
						} );
						self.render( self.options );
						self.displayNextPrevButtonView();
					}
				});

				$( self.currentPreviewParent ).on( 'click', '#bb-url-nextPicButton', function( e ) {
					e.preventDefault();
					var imageIndex = self.options.link_image_index;
					var images = self.options.link_images;
					if ( imageIndex < images.length - 1 ) {
						Object.assign( self.options, {
							link_image_index : parseInt( imageIndex ) + 1,
							link_swap_image_button : 1
						} );
						self.render( self.options );
						self.displayNextPrevButtonView();
					}
				});

				$( self.currentPreviewParent ).on( 'click', '#bb-link-preview-select-image', function( e ) {
					e.preventDefault();
					var imageIndex = self.options.link_image_index;
					self.options.link_image_index_save = imageIndex;
					self.options.link_swap_image_button = 0;
					$( '.bb-url-scrapper-container #icon-exchange' ).show();
					$( '.bb-url-scrapper-container #activity-link-preview-remove-image' ).show();
					$( '.bb-url-scrapper-container #activity-link-preview-select-image' ).hide();
					$( '.bb-url-scrapper-container #activity-url-prevPicButton' ).hide();
					$( '.bb-url-scrapper-container #activity-url-nextPicButton' ).hide();
					self.render( self.options );
				});

				self.controlsAdded = true;
			},
			scrapURL: function ( urlText, targetPreviewParent, targetDataInput ) {
				var self = this;
				var urlString = '';
				var bbLinkUrlInput = '';

				if ( targetPreviewParent ) {
					var formEl = targetPreviewParent.closest( 'form' );
					if( formEl.find( 'input#bb_link_url' ).length > 0 && formEl.find( 'input#bb_link_url' ).val() !== '' ){
						var currentValue = JSON.parse( formEl.find( 'input#bb_link_url').val() );
						self.options.link_url = currentValue.url ? currentValue.url : '';
						self.options.link_image_index_save = currentValue.link_image_index_save;
						bbLinkUrlInput = self.options.link_url;
					}
				}


				if ( ( urlText === null || urlText === '' ) && self.options.link_url === undefined ) {
					return;
				}

				if( targetPreviewParent ) {

					if( targetPreviewParent.children( '.bb-url-scrapper-container' ).length == 0 ) {
						targetPreviewParent.prepend('<div class="bb-url-scrapper-container"><div>');
					}
					self.currentPreviewParent = targetPreviewParent.find( '.bb-url-scrapper-container' );
				}

				if( targetDataInput.length > 0 && targetDataInput.prop('tagName').toLowerCase() === 'input' ){
					self.dataInput = targetDataInput;
				}

				//Remove mentioned members Link
				var tempNode = jQuery( '<div></div>' ).html( urlText );
				tempNode.find( 'a.bp-suggestions-mention' ).remove();
				urlText = tempNode.html();

				if ( urlText.indexOf( '<img' ) >= 0 ) {
					urlText = urlText.replace( /<img .*?>/g, '' );
				}

				if ( urlText.indexOf( 'http://' ) >= 0 ) {
					urlString = this.getURL( 'http://', urlText );
				} else if ( urlText.indexOf( 'https://' ) >= 0 ) {
					urlString = this.getURL( 'https://', urlText );
				} else if ( urlText.indexOf( 'www.' ) >= 0 ) {
					urlString = this.getURL( 'www', urlText );
				}

				if ( '' === urlString && '' === bbLinkUrlInput ) {
					return;
				}

				if ( urlString !== '' ) {
					// check if the url of any of the excluded video oembeds.
					var url_a    = document.createElement( 'a' );
					url_a.href   = urlString;
					var hostname = url_a.hostname;
					if ( 'undefined' !== typeof BP_Nouveau.forums.params.excluded_hosts && BP_Nouveau.forums.params.excluded_hosts.indexOf( hostname ) !== -1 ) {
						urlString = '';
					}
				}

				if ( '' !== urlString ) {
					this.loadURLPreview( urlString );
				} else if ( bbLinkUrlInput ) {
					this.loadURLPreview( bbLinkUrlInput );
				}
			},

			getURL: function ( prefix, urlText ) {
				var urlString   = '';
				urlText         = urlText.replace( /&nbsp;/g, '' );
				var startIndex  = urlText.indexOf( prefix );
				var responseUrl = '';

				if ( ! _.isUndefined( jQuery( $.parseHTML( urlText ) ).attr( 'href' ) ) ) {
					urlString = jQuery( urlText ).attr( 'href' );
				} else {
					for ( var i = startIndex; i < urlText.length; i++ ) {
						if (
							urlText[ i ] === ' ' ||
							urlText[ i ] === '\n' ||
							( urlText[ i ] === '"' && urlText[ i + 1 ] === '>' ) ||
							( urlText[ i ] === '<' && urlText[ i + 1 ] === 'b' && urlText[ i + 2 ] === 'r' )
						) {
							break;
						} else {
							urlString += urlText[ i ];
						}
					}
					if ( prefix === 'www' ) {
						prefix    = 'http://';
						urlString = prefix + urlString;
					}
				}

				var div       = document.createElement( 'div' );
				div.innerHTML = urlString;
				var elements  = div.getElementsByTagName( '*' );

				while ( elements[ 0 ] ) {
					elements[ 0 ].parentNode.removeChild( elements[ 0 ] );
				}

				if ( div.innerHTML.length > 0 ) {
					responseUrl = div.innerHTML;
				}

				return responseUrl;
			},

			loadURLPreview: function ( url ) {
				var self = this;

				// Already same preview then return.
				if ( 'undefined' !== typeof self.dataInput && '' !== self.dataInput.val() ) {
					var old_preview_data = JSON.parse( self.dataInput.val() );
					if (
						'undefined' !== typeof old_preview_data.link_url &&
						'' !== old_preview_data.link_url &&
						url === old_preview_data.link_url
					) {
						return;
					}
				}

				var regexp = /^(http:\/\/www\.|https:\/\/www\.|http:\/\/|https:\/\/)?[a-z0-9]+([\-\.]{1}[a-z0-9]+)*\.[a-z]{2,24}(:[0-9]{1,5})?(\/.*)?$/;
				url        = $.trim( url );
				if ( regexp.test( url ) ) {

					if ( url.includes( window.location.hostname ) && ( url.includes( 'download_document_file' ) || url.includes( 'download_media_file' ) || url.includes( 'download_video_file' ) ) ) {
						return false;
					}

					var urlResponse = false;
					if ( self.loadedURLs.length ) {
						$.each(
							self.loadedURLs,
							function ( index, urlObj ) {
								if ( urlObj.url == url ) {
									urlResponse = urlObj.response;
									return false;
								}
							}
						);
					}

					if ( self.loadURLAjax != null ) {
						self.loadURLAjax.abort();
					}

					Object.assign( self.options, {
							link_scrapping: true,
							link_loading: true,
							link_error: false,
							link_url: url,
							link_embed: false,
							link_success: false,
						}
					);

					self.controlsAdded = null;

					if ( 'undefined' !== typeof self.currentPreviewParent && self.currentPreviewParent.length ) {
						var formEl = self.currentPreviewParent.closest( 'form' );
						if( formEl.find( 'input#bb_link_url' ).length > 0 && formEl.find( 'input#bb_link_url' ).val() !== '' ){
							var prev_preview_value = JSON.parse( formEl.find( 'input#bb_link_url').val() );
							if ( '' !== prev_preview_value.url && prev_preview_value.url !== url ) {

								// Reset older preview data.
								self.options.link_image_index_save = 0;
								formEl.find( 'input#bb_link_url').val('');
							}
						}
					}
					self.render( self.options );

					if ( ! urlResponse ) {
						self.loadURLAjax = $.post(
							ajaxurl,
							{
							  action: 'bb_forums_parse_url',
							  url: url
							},
							function( response ) {
							  // success callback
							  self.setURLResponse(response, url);
							}
						  ).always(function() {
							// always callback
						});
					} else {
						self.setURLResponse( urlResponse, url );
					}
				}
			},

			setURLResponse: function ( response, url ) {
				var self = this;

				self.options.link_loading = false;

				if ( response.title === '' && response.images === '' ) {
					self.options.link_scrapping = false;
					return;
				}

				if ( response.error === '' ) {
					var urlImages = response.images;
					self.options.link_image_index = 0;
					var urlImagesIndex = '0';
					if ( '' !== self.options.link_image_index_save && ! _.isUndefined( self.options.link_image_index_save ) ) {
						urlImagesIndex =  parseInt( self.options.link_image_index_save );
					}
					if( self.options.link_image_index_save === '-1' ) {
						urlImagesIndex = '';
						urlImages = [];
					} else if( _.isUndefined( self.options.link_image_index_save ) ) {
						self.options.link_image_index_save = 0;
					}
					Object.assign( self.options, {
							link_success: true,
							link_url: url,
							link_title: ! _.isUndefined( response.title ) ? response.title : '',
							link_description: ! _.isUndefined( response.description ) ? response.description : '',
							link_images: urlImages,
							link_image_index: urlImagesIndex,
							link_embed: ! _.isUndefined( response.wp_embed ) && response.wp_embed
						}
					);

					if ( $( '#whats-new-attachments' ).hasClass( 'bb-video-preview' ) ) {
						$( '#whats-new-attachments' ).removeClass( 'bb-video-preview' );
					}

					if ( $( '#whats-new-attachments' ).hasClass( 'bb-link-preview' ) ) {
						$( '#whats-new-attachments' ).removeClass( 'bb-link-preview' );
					}

					if ( ( 'undefined' !== typeof response.description && response.description.indexOf( 'iframe' ) > -1 ) || ( ! _.isUndefined( response.wp_embed ) && response.wp_embed ) ) {
						$( '#whats-new-attachments' ).addClass( 'bb-video-preview' );
					} else {
						$( '#whats-new-attachments' ).addClass( 'bb-link-preview' );
					}

					self.loadedURLs.push( { 'url': url, 'response': response } );
					self.render( self.options );
				} else {
					Object.assign( self.options, {
							link_success: false,
							link_error: true,
							link_error_msg: response.error,
							link_loading: false,
							link_images: []
						}
					);
					self.render( self.options );
				}
			}
		}


	};

   // Launch BP Nouveau.
   bp.Nouveau.start();

} )( bp, jQuery );

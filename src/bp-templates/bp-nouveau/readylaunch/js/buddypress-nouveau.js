/* global wp, bp, BP_Nouveau, JSON, BB_Nouveau_Presence, BP_SEARCH, AbortController */
/* jshint devel: true */
/* jshint browser: true */
/* @version 3.0.0 */
window.wp = window.wp || {};
window.bp = window.bp || {};

( function ( exports, $ ) {

	var hoverAvatar = false;
	var hoverCardPopup = false;
	var hideCardTimeout = null;
	var popupCardLoaded = false;
	var currentRequest = null;
	var hoverProfileAvatar = false;
	var hoverGroupAvatar = false;
	var hoverProfileCardPopup = false;
	var hoverGroupCardPopup = false;

	// Bail if not set.
	if ( typeof BP_Nouveau === 'undefined' ) {
		return;
	}

	var bpNouveau                   = BP_Nouveau,
		bbRLClose                   = bpNouveau.close,
		bbRLObjects                 = bpNouveau.objects,
		bbRLObjectNavParent         = bpNouveau.object_nav_parent,
		bbRLWpTime                  = bpNouveau.wpTime,
		bbRLWarnings                = bpNouveau.warnings,
		bbRlNonce                   = bpNouveau.nonces,
		bbRLCustomizerSettings      = bpNouveau.customizer_settings,
		bbRLModByPass               = bpNouveau.modbypass,
		bbRLAjaxUrl                 = bpNouveau.ajaxurl,
		bbRlIsSendAjaxRequest       = bpNouveau.is_send_ajax_request,
		bbRLPulse                   = bpNouveau.pulse,
		bbRLOnlyAdminNotice         = bpNouveau.only_admin_notice,
		bbRLParentGroupLeaveConfirm = bpNouveau.parent_group_leave_confirm,
		bbRLGroupLeaveConfirm       = bpNouveau.group_leave_confirm,
		bbRLSubscriptions           = bpNouveau.subscriptions,
		bbRLMedia                   = bpNouveau.media,
		bbRLForums                  = bpNouveau.forums;

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

			// Profile Notification setting.
			this.profileNotificationSetting();

			this.xProfileBlock();

			// Bail if not set.
			if ( 'undefined' !== typeof BB_Nouveau_Presence ) {
				// User Presence status.
				this.userPresenceStatus();
			}

			var _this = this;

			$( document ).on(
				'bb_trigger_toast_message',
				function ( event, title, message, type, url, autoHide, autohide_interval ) {
					_this.bbToastMessage( title, message, type, url, autoHide, autohide_interval );
				}
			);

			// Check for lazy images and load them also register scroll event to load on scroll.
			bp.Nouveau.lazyLoad( '.lazy' );
			$( window ).on(
				'scroll resize',
				function () {
					bp.Nouveau.lazyLoad( '.lazy' );
				}
			);

			// Initialize cache
			this.cacheProfileCard = {};
			this.cacheGroupCard = {};

			// wrapNavigation dropdown events
			$( document ).on(
				'click',
				'.more-action-button',
				function ( e ) {
					e.preventDefault();
					$( this ).toggleClass( 'active open' ).next().toggleClass( 'active open' );
					$( 'body' ).toggleClass( 'nav_more_option_open' );
				}
			);

			$( document ).click(
				function ( e ) {
					var container = $( '.more-action-button, .sub-menu' );
					if ( ! container.is( e.target ) && container.has( e.target ).length === 0 ) {
						$( '.more-action-button' ).removeClass( 'active open' ).next().removeClass( 'active open' );
						$( 'body' ).removeClass( 'nav_more_option_open' );
					}

					if ( $( e.target ).hasClass( 'bb_more_dropdown__title' ) || $( e.target ).closest( '.bb_more_dropdown__title' ).length > 0 ) {
						$( '.more-action-button' ).removeClass( 'active open' ).next().removeClass( 'active open' );
						$( 'body' ).removeClass( 'nav_more_option_open' );
					}
				}
			);
		},

		/*
		 *	Toast Message
		 */
		bbToastMessage: function ( title, message, type, url, autoHide, autohideInterval ) {

			if ( ! message || '' === message.trim() ) { // Toast Message can't be triggered without content.
				return;
			}

			function getTarget() {
				if ( $( '.bb-toast-messages-enable' ).length ) {
					return '.bb-toast-messages-enable .toast-messages-list';
				}

				if ( $( '.bb-onscreen-notification-enable ul.notification-list' ).length ) {
					var toastPositionElem        = $( '.bb-onscreen-notification' ),
						toastPosition            = toastPositionElem.hasClass( 'bb-position-left' ) ? 'left' : 'right',
						toastMessageWrapPosition = $( '<div class="bb-toast-messages-enable bb-toast-messages-enable-mobile-support"><div class="bb-toast-messages bb-position-' + toastPosition + ' single-toast-messages"><ul class="toast-messages-list bb-toast-messages-list"></u></div></div>' );
					toastPositionElem.show();
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

			// Add Toast Message.
			var uniqueId   = 'unique-' + Math.floor( Math.random() * 1000000 ),
				currentEl  = '.' + uniqueId,
				urlClass   = '',
				bpMsgType  = '',
				bpIconType = '';

			var newAutohideInterval = autohideInterval && typeof autohideInterval == 'number' ? ( autohideInterval * 1000 ) : 5000;

			if ( type ) {
				bpMsgType = type;
				switch ( bpMsgType ) {
					case 'success':
						bpIconType = 'check';
						break;
					case 'warning':
						bpIconType = 'exclamation-triangle';
						break;
					case 'delete':
						bpIconType = 'trash';
						bpMsgType  = 'error';
						break;
					default:
						bpIconType = 'info';
						break;
				}
			}

			if ( null !== url ) {
				urlClass = 'has-url';
			}

			var messageContent = '';
			messageContent    += '<div class="toast-messages-icon"><i class="bb-icon bb-icon-' + bpIconType + '"></i></div>';
			messageContent    += '<div class="toast-messages-content">';
			if ( title ) {
				messageContent += '<span class="toast-messages-title">' + title + '</span>';
			}
			if ( message ) {
				messageContent += '<span class="toast-messages-content">' + message + '</span>';
			}
			messageContent += '</div>';
			messageContent += '<div class="actions"><a class="action-close primary" data-bp-tooltip-pos="left" data-bp-tooltip="' + bbRLClose + '"><i class="bb-icon bb-icon-times" aria-hidden="true"></i></a></div>';
			messageContent += url ? '<a class="toast-messages-url" href="' + url + '"></a>' : '';

			$( getTarget() ).append( '<li class="item-list read-item pull-animation bp-message-' + bpMsgType + ' ' + uniqueId + ' ' + urlClass + '"> ' + messageContent + ' </li>' );

			if ( autoHide ) {
				setInterval(
					function () {
						hideMessage();
					},
					newAutohideInterval
				);
			}

			$( currentEl + ' .actions .action-close' ).on(
				'click',
				function () {
					hideMessage();
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
				bbRLObjects,
				function ( value ) {
					return value;
				}
			);
			this.objectNavParent = bbRLObjectNavParent;

			// HeartBeat Global.
			this.heartbeat = wp.heartbeat || false;

			// An object containing each query var.
			this.querystring = this.getLinkParams();

			// Get Server Time Difference on load.
			this.bbServerTimeDiff = new Date( bbRLWpTime ).getTime() - new Date().getTime();
		},

		/**
		 * [prepareDocument description]
		 *
		 * @return {[type]} [description]
		 */
		prepareDocument: function () {

			// Remove the no-js class and add the js one.
			var $body = $( 'body' );
			if ( $body.hasClass( 'no-js' ) ) {
				$body.removeClass( 'no-js' ).addClass( 'js' );
			}

			// Log Warnings into the console instead of the screen.
			if ( bbRLWarnings && 'undefined' !== typeof console && console.warn ) {
				$.each(
					bbRLWarnings,
					function ( w, warning ) {
						console.warn( warning );
					}
				);
			}

			// Remove the directory title if there's a widget containing it.
			var $widgetTitleElem = $( '.buddypress_object_nav .widget-title' );
			if ( $widgetTitleElem.length ) {
				var text = $widgetTitleElem.html();

				$body.find( '*:contains("' + text + '")' ).each(
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
		 * @return {[type]} [description]
		 * @param type
		 * @param property
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
		 * @param type
		 * @param property
		 * @param value
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
		 * @return {[type]} [description]
		 * @param url
		 * @param param
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
		 * @return {[type]} [description]
		 * @param post_data
		 * @param object
		 * @param button
		 */
		ajax: function ( post_data, object, button ) {

			if ( this.ajax_request && typeof button === 'undefined' && post_data.status !== 'scheduled') {
				this.ajax_request.abort();
			}

			// Extend posted data with stored data and object nonce.
			var postData = $.extend( {}, bp.Nouveau.getStorage( 'bp-' + object ), { nonce: bbRlNonce[ object ] }, post_data );

			if ( undefined !== bbRLCustomizerSettings ) {
				postData.customized = bbRLCustomizerSettings;
			}

			/**
			 * Moderation bypass for admin
			 */
			if ( undefined !== bbRLModByPass ) {
				postData.modbypass = bbRLModByPass;
			}

			this.ajax_request = $.post( bbRLAjaxUrl, postData, 'json' );

			return this.ajax_request;
		},

		inject: function ( selector, content, method ) {

			if ( ! $( selector ).length || ! content ) {
				return;
			}

			/**
			 * How the content should be injected in the selector
			 *
			 * Possible methods are.
			 * - reset: the selector will be reset with the content
			 * - append:  the content will be added after selector's content
			 * - prepend: the content will be added before selector's content
			 */

			method = method || 'reset';

			var $selector = $( selector );
			switch ( method ) {
				case 'append':
					$selector.append( content );
					break;
				case 'prepend':
					$selector.prepend( content );
					break;
				case 'after':
					$selector.after( content );
					break;
				default:
					$selector.html( content );
					break;
			}
			$selector.find( 'li.activity-item' ).each( this.hideSingleUrl );

			if ( 'undefined' !== typeof bp_mentions || 'undefined' !== typeof bp.mentions ) {
				$( '.bb-rl-suggestions' ).bp_mentions( bp.mentions.users );
				$( '#bb-rl-whats-new' ).on(
					'inserted.atwho',
					function () {
						// Get caret position when user adds mention.
						if ( window.getSelection && document.createRange ) {
							var sel = window.getSelection && window.getSelection();
							if ( sel && sel.rangeCount > 0 ) {
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
		 * @return {[type]} [description]
		 */
		hideSingleUrl: function () {
			var _findtext  = $( this ).find( '.bb-rl-activity-inner > p' ).removeAttr( 'br' ).removeAttr( 'a' ).text(),
				_url       = '',
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
				var findTextLength = _findtext.length;
				for ( var i = startIndex; i < findTextLength; i++ ) {
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
					$( this ).find( '.bb-rl-activity-inner > p:first' ).hide();
				}
			}
		},
		/**
		 * [objectRequest description]
		 *
		 * @return {[type]} [description]
		 * @param data
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
					method: 'reset',
					ajaxload: true,
					order_by: '',
				},
				data
			);

			// Do not request if we don't have the object or the target to inject results into.
			if ( ! data.object || ! data.target ) {
				return;
			}

			// prevent activity response to append to media model activity list element.
			if ( 'activity' === data.object && '#buddypress [data-bp-list] ul.bb-rl-list' === data.target ) {
				data.target = '#buddypress [data-bp-list] ul.bb-rl-list:not(#bb-rl-media-model-container ul.bb-rl-list)';
			}

			// if object is members, media, document and object nav does not exists fallback to scope = all.
			if ( [ 'members', 'activity', 'media', 'document' ].includes( data.object ) && ! $( this.objectNavParent + ' [data-bp-scope="' + data.scope + '"]' ).length ) {
				data.scope = 'all';

				if ( 'activity' === data.object ) {

					// Check other next item from the filter dropdown as backward compability.
					var activityScopeFilterSelector = this.objectNavParent + ' #bb-subnav-filter-show';
					if ( $( activityScopeFilterSelector ).length ) {
						var firstItemScope = $( activityScopeFilterSelector + ' > ul > li' ).first().data( 'bp-scope' );
						data.scope = 'undefined' !== firstItemScope ? firstItemScope : data.scope;
					}
				}
			}

			// Prepare the search terms for the request.
			if ( data.search_terms ) {
				data.search_terms = data.search_terms.replace( /</g, '&lt;' ).replace( />/g, '&gt;' );
			}

			if ( $( this.objectNavParent + ' [data-bp-order]' ).length ) {
				data.order_by = $( this.objectNavParent + ' [data-bp-order="' + data.object + '"].selected' ).data( 'bp-orderby' );
			}

			// Set session's data.
			if ( null !== data.scope ) {
				if( data.object === 'activity' ) {
					if( ( 'undefined' !== data.user_timeline && true === data.user_timeline ) || $( 'body.my-activity:not(.activity-singular)' ).length ) {
						this.setStorage( 'bp-user-activity', 'scope', data.scope );
					} else if( 'undefined' !== data.save_scope && true === data.save_scope ) {
						this.setStorage( 'bp-' + data.object, 'scope', data.scope );
					}
				} else {
					this.setStorage( 'bp-' + data.object, 'scope', data.scope );
				}
			}

			if ( null !== data.filter ) {
				this.setStorage( 'bp-' + data.object, 'filter', data.filter );
			}

			if ( null !== data.extras ) {
				this.setStorage( 'bp-' + data.object, 'extras', data.extras );
			}

			if ( ! _.isUndefined( data.ajaxload ) && false === data.ajaxload ) {
				var local_scope = $( '#bb-subnav-filter-show > ul > li.selected' ).data( 'bp-scope' );
				if( undefined !== local_scope && data.scope !== local_scope ) {
					if( ( 'undefined' !== data.user_timeline && true === data.user_timeline ) || $( 'body.my-activity:not(.activity-singular)' ).length ) {
						this.setStorage( 'bp-user-activity', 'scope', local_scope );
					} else {
						this.setStorage( 'bp-' + data.object, 'scope', local_scope );
					}
				}
				return false;
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

			var selected_scope = $( this.objectNavParent + ' #bb-subnav-filter-show [data-bp-scope="' + data.scope + '"].selected' );
			if( selected_scope.length ) {
				var option_label = $( '.bb-subnav-filters-container .subnav-filters-opener[aria-controls="bb-subnav-filter-show"] .selected' );
				// Check if options starts with "I've" and "I'm" then leave it as is, otherwise lowercase the first letter
				if( selected_scope.text().startsWith( 'I\'ve' ) || selected_scope.text().startsWith( 'I\'m' ) ) {
					option_label.text( selected_scope.text() );
				} else {
					option_label.text( selected_scope.text().toLowerCase() );
				}
			}

			var selected_order = $( this.objectNavParent + ' #bb-subnav-filter-by [data-bp-order="' + data.order_by + '"].selected' );
			if( selected_order.length ) {
				$( '.bb-subnav-filters-container .subnav-filters-opener[aria-controls="bb-subnav-filter-by"] .selected' ).text( selected_order.text() );
			}

			// Add loader at custom place for few search types.
			if ( $( this.objectNavParent + ' [data-bp-scope="' + data.scope + '"]' ).length === 0 ) {
				var $body                = $( 'body' ),
					component_conditions = [
						data.object === 'group_members' && $body.hasClass( 'group-members' ),
						data.object === 'document' && $body.hasClass( 'documents' ),
						data.object === 'manage_group_members' && $body.hasClass( 'manage-members' ),
						data.object === 'document' && ( $body.hasClass( 'document' ) || $body.hasClass( 'documents' ) ),
					],
					component_targets    = [
						$( '.groups .group-search.members-search' ),
						$( '.documents .bp-document-listing .bb-title' ),
						$( '.groups .group-search.search-wrapper' ),
						$( '#bp-media-single-folder .bb-title' ),
					];

				component_conditions.forEach(
					function ( condition, i ) {
						if ( condition ) {
							component_targets[ i ].addClass( 'loading' );
						}
					}
				);

			}

			if( data.object === 'activity' && $( 'body.groups' ).hasClass( 'activity' ) ) {

				if( data.event_element && data.event_element.hasClass('group-search' ) ) {
					$( '.groups .group-search.activity-search' ).addClass( 'loading' );
				} else {
					$( 'body.groups .activity-head-bar .bb-subnav-filters-filtering li' ).first().addClass( 'loading' );
				}
			}

			$( '#buddypress [data-bp-filter="' + data.object + '"] option[value="' + data.filter + '"]' ).prop( 'selected', true );

			if ( 'friends' === data.object || 'group_members' === data.object || 'manage_group_members' === data.object ) {
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

			// Remove the unnecessary data from the postdata.
			if( ! _.isUndefined( postdata.event_element ) ) {
				delete postdata.event_element;
			}
			if( ! _.isUndefined( postdata.user_timeline ) ) {
				delete postdata.user_timeline;
			}

			return this.ajax( postdata, data.object ).done(
				function ( response ) {
					if ( false === response.success || _.isUndefined( response.data ) ) {
						return;
					}

					// Control the scheduled posts layout view.
					if ( 'scheduled' === data.status ) {
						if ( $( response.data.contents ).hasClass( 'bp-feedback' ) ) {
							$( data.target ).parent().addClass( 'has-no-content' );
						} else {
							$( data.target ).parent().addClass( 'has-content' );
						}
						var schedulePostCount = $( '.bb-view-schedule-posts_modal .bb-rl-schedule-post-count' );
						if (
							schedulePostCount.length &&
							! _.isUndefined( response.data.count ) &&
							response.data.count > 0
						) {
							schedulePostCount.text( response.data.count );
						}
					}

					if ( ! _.isUndefined( response.data.layout ) ) {
						$( '.layout-view' ).removeClass( 'active' );
						$( '.layout-' + response.data.layout + '-view' ).addClass( 'active' );
					}

					var $buddypressElem = $( 'body.group-members.members.buddypress' );
					if ( $buddypressElem.length && ! _.isUndefined( response.data ) && ! _.isUndefined( response.data.count ) ) {
						$buddypressElem.find( 'ul li#members-groups-li' ).find( 'span' ).text( response.data.count );
					}

					var scopeElem = $( self.objectNavParent + ' [data-bp-scope="' + data.scope + '"]' );
					scopeElem.removeClass( 'loading' );
					scopeElem.find( 'span' ).text( '' );

					$( '.bb-subnav-filters-container .subnav-filters-modal ul li' ).removeClass( 'loading' );

					if ( scopeElem.length === 0 ) {
						component_targets.forEach(
							function ( target ) {
								target.removeClass( 'loading' );
							}
						);
					}

					if ( ! _.isUndefined( response.data ) ) {
						var scopes   = response.data.scopes;
						var count    = response.data.count;
						var newCount = '';
						if (
							scopeElem.length > 0 &&
							scopeElem.hasClass( 'selected' ) &&
							! _.isUndefined( scopes ) &&
							! _.isUndefined( scopes[ data.scope ] ) &&
							'' !== scopes[ data.scope ]
						) {
							newCount = scopes[ data.scope ];
						} else if (
							! _.isUndefined( scopes ) &&
							! _.isUndefined( scopes.all ) &&
							'' !== scopes.all
						) {
							newCount = scopes.all;
						} else if ( ! _.isUndefined( count ) ) {
							newCount = count;
						}

						if ( '' !== newCount ) {
							$( '.bb-rl-entry-heading .bb-rl-heading-count' ).text( newCount );
						}
					}

					var subnavFiltersSearch = $( '.bb-subnav-filters-search.loading' );
					if ( subnavFiltersSearch.length ) {
						if ( 'activity' === data.object ) {
							bp.Nouveau.Activity.heartbeat_data.last_recorded = 0;
						}

						subnavFiltersSearch.removeClass( 'loading' );

						if( data.search_terms === '' && window.clear_search_trigger) {
							$( '.bb-subnav-filters-search.active' ).removeClass( 'active' );
							window.clear_search_trigger = false;
						}
					}

					if ( data.object === 'activity' && $( 'body.groups' ).hasClass( 'activity' ) ) {
						$( '.groups .group-search.activity-search.loading' ).removeClass( 'loading' );
						$( 'body.groups .activity-head-bar .bb-subnav-filters-filtering li.loading' ).removeClass( 'loading' );
					}

					if ( ! _.isUndefined( response.data ) && ! _.isUndefined( response.data.count ) ) {
						$( self.objectNavParent + ' [data-bp-scope="' + data.scope + '"]' ).find( 'span' ).text( response.data.count );
					}

					if ( 'reset' !== data.method ) {
						self.inject( data.target, response.data.contents, data.method );
						$( data.target ).trigger( 'bp_ajax_' + data.method, $.extend( data, { response: response.data } ) );
					} else {
						/* animate to top if called from bottom pagination */
						var animateToTop = function () {
							var top = $( data.target );

							if ( data.caller === 'pag-bottom' ) {
								var subNavElem = $( '#subnav' );
								if ( subNavElem.length ) {
									top = subNavElem.parent();
								}
							}

							$( 'html, body' ).animate(
								{ scrollTop : top.offset().top },
								'slow',
								function () {
									fadeAndInject();
								}
							);
						};

						var fadeAndInject = function () {
							$( data.target ).fadeOut(
								100,
								function () {
									self.inject( this, response.data.contents, data.method );
									$( this ).fadeIn( 100 );

									// Inform other scripts the list of objects has been refreshed.
									$( data.target ).trigger( 'bp_ajax_request', $.extend( data, { response : response.data } ) );

									// Lazy Load Images.
									if ( bp.Nouveau.lazyLoad ) {
										setTimeout(
											function () {
												bp.Nouveau.lazyLoad( '.lazy' );
											},
											1000
										);
									}
								}
							);
						};

						if ( 'pag-bottom' === data.caller ) {
							animateToTop();
						} else {
							fadeAndInject();
						}
					}
					setTimeout(
						function () {
							// Waiting to load dummy image.
							self.reportPopUp();
							self.reportedPopup();
							$( '.activity-item.bb-closed-comments' ).find( '.edit-activity, .acomment-edit' ).parents( '.generic-button' ).hide();
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
				filter = null, save_scope = true;

			$.each(
				this.objects,
				function ( o, object ) {
					// Continue when ajax is blocked for object request.
					if (
						$( '#buddypress [data-bp-list="' + object + '"][data-ajax="false"]' ).length &&
						(
						! _.isUndefined( bbRlIsSendAjaxRequest ) &&
						'' !== bbRlIsSendAjaxRequest
						)
					) {
						return;
					}

					var bodyElem = $( 'body' );
					if( 'activity' === object && bodyElem.hasClass( 'my-activity' ) ) {
						objectData = self.getStorage( 'bp-user-activity' );
					} else {
						objectData = self.getStorage( 'bp-' + object );
					}

					var typeType = window.location.hash.substr( 1 );
					scope        = ( undefined !== typeType && 'following' === typeType ) ? typeType : ( undefined !== objectData.scope ? objectData.scope : '' );
					filter       = ( undefined !== objectData.filter && null !== objectData.filter ) ? objectData.filter : ( !_.isNull( filter ) ? filter : 0 );

					if ( 'activity' === object ) {
						var local_scope = $( '#bb-subnav-filter-show > ul > li.selected' ).data( 'bp-scope' );
						if( undefined !== scope && objectData.scope !== scope ) {
							scope = local_scope;
							save_scope = true;
						} else {
							save_scope = false;
						}
					}

					// Single activity page.
					if ( 'activity' === object && bodyElem.hasClass( 'activity-singular' ) ) {
						scope      = 'all';
						save_scope = false;
					}

					// Prioritize query param.
					if ( 'members' === object ) {
						if ( self.querystring ) {
							scope  = self.querystring['bb-rl-scope'] ? self.querystring['bb-rl-scope'] : scope;
							filter = self.querystring['bb-rl-order-by'] ? self.querystring['bb-rl-order-by'] : filter;
						}
					}

					// Notifications always need to start with Newest ones.
					extras = ( undefined !== objectData.extras && 'notifications' !== object ) ? objectData.extras : null;

					var bbFilterElem = $( '#buddypress [data-bp-filter="' + object + '"]' );

					// Pre select saved sort filter.
					if ( bbFilterElem.length ) {
						if ( ! _.isUndefined( bbRlIsSendAjaxRequest ) && '1' === bbRlIsSendAjaxRequest && undefined !== filter && null !== filter ) {
							bbFilterElem.find( 'option[value="' + filter + '"]' ).prop( 'selected', true );
						} else if ( '-1' !== bbFilterElem.val() && '0' !== bbFilterElem.val() ) {
							filter = bbFilterElem.val();
						}
					}

					// Pre select saved scope filter.
					if ( $( self.objectNavParent + ' [data-bp-' + object + '-scope-filter="' + object + '"]' ).length ) {
						if ( ! _.isUndefined( bbRlIsSendAjaxRequest ) && '1' === bbRlIsSendAjaxRequest && undefined !== scope ) {
							$( self.objectNavParent + ' [data-bp-' + object + '-scope-filter="' + object + '"] option[data-bp-scope="' + scope + '"]' ).prop( 'selected', true );
						}
					}

					// var bbObjectNavParent = $( this.objectNavParent + ' [data-bp-object="' + object + '"]' );
					// if ( bbObjectNavParent.length ) {
					// 	bbObjectNavParent.each(
					// 		function () {
					// 			$( this ).removeClass( 'selected' );
					// 		}
					// 	);

					// 	$( this.objectNavParent + ' [data-bp-scope="' + object + '"], #object-nav li.current' ).addClass( 'selected' );
					// }

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
							extras: extras,
							save_scope: save_scope,
						};

						var bbMemberTypeFilter = $( '#buddypress [data-bp-member-type-filter="' + object + '"]' ),
							bbGroupTypeFilter  = $( '#buddypress [data-bp-group-type-filter="' + object + '"]' );
						if ( bbMemberTypeFilter.length ) {
							queryData.member_type_id = bbMemberTypeFilter.val();
						} else if ( bbGroupTypeFilter.length ) {
							queryData.group_type = bbGroupTypeFilter.val();
						}

						if ( ! _.isUndefined( bbRlIsSendAjaxRequest ) && '' === bbRlIsSendAjaxRequest ) {
							queryData.ajaxload = false;
						}

						// Topic selector.
						if ( $( '.activity-topic-selector li a' ).length ) {
							var topicId = $( '.activity-topic-selector li a.selected' ).data( 'topic-id' );
							if ( topicId ) {
								queryData.topic_id = topicId;
							} else {
								queryData.topic_id = '';
							}
						} else {
							delete queryData.topic_id;
							self.setStorage( 'bp-activity', 'topic_id', '' );
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
			if ( 'undefined' === typeof bbRLPulse || ! this.heartbeat ) {
				return;
			}

			this.heartbeat.interval( Number( bbRLPulse ) );

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
			var $buddypress = $( '#buddypress' );
			var $document   = $( document );

			// Disabled inputs.
			$( '[data-bp-disable-input]' ).on( 'change', this.toggleDisabledInput );

			// Scope filters.
			$document.on( 'change', this.objectNavParent + ' .bb-rl-scope-filter select', this, this.scopeQuery );

			// Refreshing.
			$( this.objectNavParent + ' .bp-navs' ).on( 'click', 'a', this, this.scopeQuery );

			// Filtering.
			$document.on( 'change', '#buddypress [data-bp-filter]', this, this.filterQuery );

			// Group Type & Member Type Filter.
			$document.on( 'change', '#buddypress [data-bp-group-type-filter]', this, this.typeGroupFilterQuery );
			$document.on( 'change', '#buddypress [data-bp-member-type-filter]', this, this.typeMemberFilterQuery );

			// Profile Search toggle.
			$document.on( 'click', '.bb-rl-advance-profile-search-toggle', this, this.toggleProfileSearch );
			// Close profile search
			$document.on( 'click', this.closeProfileSearch.bind( this ) );
			$document.on( 'click', '.bb-rl-profile-search-cancel', this.closeProfileSearch.bind( this ) );
			// Close profile search when pressing Escape key
			$document.on( 'keyup', function( event ) {
				if ( event.key === 'Escape' || event.keyCode === 27 ) {
					bp.Nouveau.closeProfileSearch();
				}
			} );

			// Searching.
			var $searchForm = $buddypress.find( '[data-bp-search]' );
			$searchForm.on( 'submit', 'form', this, this.searchQuery );
			$searchForm.on( 'keyup', 'input[name=group_members_search]', this, _.throttle( this.searchQuery, 900 ) );
			$( '#buddypress [data-bp-search] form' ).on( 'search', 'input[type=search]', this.resetSearch );

			// Buttons.
			var $buttons = $buddypress.find( '[data-bp-list], #item-header, .bp-shortcode-wrap .dir-list, .bb-rl-messages-content, .messages-screen, .bb_more_options, .bb-rl-group-extra-info' );
			$buttons.on( 'click', '[data-bp-btn-action]', this, this.buttonAction );
			$buttons.on( 'blur', '[data-bp-btn-action]', this, this.buttonRevert );
			$document.on( 'click', '#buddypress .bb-leave-group-popup .bb-confirm-leave-group', this.leaveGroupAction );
			$document.on( 'click', '#buddypress .bb-leave-group-popup .bb-close-leave-group', this.leaveGroupClose );
			$document.on( 'click', '#buddypress .bb-remove-connection .bb-confirm-remove-connection', this.removeConnectionAction );
			$document.on( 'click', '#buddypress .bb-remove-connection .bb-close-remove-connection', this.removeConnectionClose );
			$document.on( 'click', '#buddypress .bb-cancel-request-group-popup .bb-confirm-cancel-request-group', this.cancelRequestGroupAction );
			$document.on( 'click', '#buddypress .bb-cancel-request-group-popup .bb-close-cancel-request-group', this.closeRequestGroupAction );
			$document.on( 'click', '#buddypress table.invite-settings .field-actions .field-actions-remove, #buddypress table.invite-settings .field-actions-add', this, this.addRemoveInvite );
			$document.on( 'click', '.show-action-popup', this.showActionPopup );
			$document.on( 'click', '#bb-rl-message-threads .block-member', this.threadListBlockPopup );
			$document.on( 'click', '#bb-rl-message-threads .report-content', this.threadListReportPopup );
			$document.on( 'click', '.bb-rl-close-action-popup, .action-popup-overlay', this.closeActionPopup );
			$document.on( 'keyup', '.search-form-has-reset input[type="search"], .search-form-has-reset input#bbp_search', _.throttle( this.directorySearchInput, 900 ) );
			$document.on( 'click', '.search-form-has-reset .search-form_reset', this.resetDirectorySearch );

			$document.on( 'keyup', this, this.keyUp );

			// Close notice.
			$( '[data-bp-close]' ).on( 'click', this, this.closeNotice );

			// Pagination.
			$( '#buddypress [data-bp-list]' ).on( 'click', '[data-bp-pagination] a:not([data-method])', this, this.paginateAction );

			$document.on( 'click', this.closePickersOnClick );
			document.addEventListener( 'keydown', this.closePickersOnEsc );

			$document.on( 'click', '#header-cover-image a.position-change-cover-image, .header-cover-reposition-wrap a.cover-image-save, .header-cover-reposition-wrap a.cover-image-cancel', this.coverPhotoCropper );

			$document.on( 'click', '#cover-photo-alert .bb-rl-model-close-button', this.coverPhotoCropperAlert );

			// More Option Dropdown.
			$document.on( 'click', this.toggleMoreOption.bind( this ) );
			$document.on( 'heartbeat-send', this.bbHeartbeatSend.bind( this ) );
			$document.on( 'heartbeat-tick', this.bbHeartbeatTick.bind( this ) );

			// Display download button for media/document/video, Display more options on activity.
			$document.on( 'click', this.toggleActivityOption.bind( this ) );

			// Create event for remove single notification.
			bp.Nouveau.notificationRemovedAction();
			// Remove all notifications.
			bp.Nouveau.removeAllNotification();
			// Set title tag.
			bp.Nouveau.setTitle();

			// Following widget more button click.
			$document.on( 'click', '.more-following .count-more', this.bbWidgetMoreFollowing );

			// Accordion open/close event.
			$( '.bb-accordion .bb-accordion_trigger' ).on( 'click', this.toggleAccordion );

			// Prevent duplicated emoji from windows system emoji picker.
			$document.keydown( this.mediumFormAction.bind( this ) );

			// group manage actions.
			$document.on('change', '.bb-rl-groups-manage-members-list select.member-action-dropdown', this.groupManageAction.bind( this ) );
			$document.on('click', '.bb-rl-groups-manage-members-list .bb-rl-group-member-action-button:not(.disabled)', this.groupManageActionClick.bind( this ) );

			// Profile/Group Popup Card.
			$( document ).on( 'mouseenter', '[data-bb-hp-profile]', function () {
				hoverAvatar = true;
				hoverProfileAvatar = true;

				// Clear pending hide timeouts
				if ( hideCardTimeout ) {
					clearTimeout( hideCardTimeout );
				}

				// Close open group card
				if( $( '#group-card' ).hasClass( 'show' ) ) {
					bp.Nouveau.hidePopupCard();
					// Reset the loaded flag when switching between different card types
					popupCardLoaded = false;
				}

				// Always attempt to load the profile card
				bp.Nouveau.profilePopupCard.call( this );
			} );
			$( document ).on( 'mouseenter', '[data-bb-hp-group]', function () {
				hoverAvatar = true;
				hoverGroupAvatar = true;

				// Clear pending hide timeouts
				if ( hideCardTimeout ) {
					clearTimeout( hideCardTimeout );
				}

				// Close open profile card
				if ( $( '#profile-card' ).hasClass( 'show' ) ) {
					bp.Nouveau.hidePopupCard();
					// Reset the loaded flag when switching between different card types
					popupCardLoaded = false;
				}

				// Always attempt to load the group card
				bp.Nouveau.groupPopupCard.call( this );
			} );
			$( document ).on( 'mouseleave', '[data-bb-hp-profile], [data-bb-hp-group]', function ( event ) {
				var relatedTarget = event.relatedTarget;
				var idleProfileAvatar = $( this ).is( '[data-bb-hp-profile]' );
				var idleGroupAvatar = $( this ).is( '[data-bb-hp-group]' );

				if ( idleProfileAvatar ) {
					hoverProfileAvatar = false;
				}
				if ( idleGroupAvatar ) {
					hoverGroupAvatar = false;
				}

				// Only hide popup if we're not moving to another hoverable element or popup card
				if ( $( relatedTarget ).closest( '[data-bb-hp-profile], [data-bb-hp-group], #profile-card, #group-card' ).length === 0 ) {
					hoverAvatar = false;
					if ( !hoverCardPopup ) {
						bp.Nouveau.checkHidePopupCard();
					}
				}
			} );
			$( document ).on( 'mouseenter', '#profile-card', function () {
				hoverAvatar = false;
				hoverCardPopup = true;
				hoverProfileCardPopup = true;
				if ( hideCardTimeout ) {
					clearTimeout( hideCardTimeout );
				}
			} );
			$( document ).on( 'mouseenter', '#group-card', function () {
				hoverAvatar = false;
				hoverCardPopup = true;
				hoverGroupCardPopup = true;
				if ( hideCardTimeout ) {
					clearTimeout( hideCardTimeout );
				}
			} );
			$( document ).on( 'mouseleave', '#profile-card', function () {
				hoverProfileCardPopup = false;
				setTimeout( function () {
					hoverCardPopup = false;
					if ( ! hoverAvatar ) {
						bp.Nouveau.checkHidePopupCard();
					}
				}, 100 );
			} );
			$( document ).on( 'mouseleave', '#group-card', function () {
				hoverGroupCardPopup = false;
				setTimeout( function () {
					hoverCardPopup = false;
					if ( ! hoverAvatar ) {
						bp.Nouveau.checkHidePopupCard();
					}
				}, 100 );
			} );

			$( window ).on( 'scroll', this.hidePopupCard );
			
			$document.on( 'click', '[data-bp-list] .bb-rl-view-more a', this.loadMoreData.bind( this ) );
		},

		bindPopoverEvents: function() {
			$( document ).on( 'click', '#profile-card [data-bp-btn-action]', this, this.buttonAction );
			$( document ).on( 'blur', '#profile-card [data-bp-btn-action]', this, this.buttonRevert );
		},

		/**
		 * [heartbeatSend description]
		 *
		 * @return {[type]} [description]
		 * @param event
		 * @param data
		 */
		bbHeartbeatSend: function ( event, data ) {
			data.onScreenNotifications = true;

			// Add an heartbeat send event to possibly any BuddyPress pages.
			$( '#buddypress' ).trigger( 'bb_heartbeat_send', data );
		},

		/**
		 * [heartbeatTick description]
		 *
		 * @return {[type]} [description]
		 * @param event
		 * @param data
		 */
		bbHeartbeatTick: function (  event, data ) {
			// Inject on-screen notification.
			bp.Nouveau.bbInjectOnScreenNotifications( event, data );
		},

		/**
		 * Injects all unread notifications
		 */
		bbInjectOnScreenNotifications: function ( event, data ) {
			var onScreenElem = $( '.bb-onscreen-notification' ),
				enable       = onScreenElem.data( 'enable' );

			if ( '1' !== enable || ( 'undefined' === typeof data.on_screen_notifications && '' === data.on_screen_notifications ) ) {
				return;
			}

			var wrap          = onScreenElem,
				list          = wrap.find( '.notification-list' ),
				removedItems  = list.data( 'removed-items' ),
				animatedItems = list.data( 'animated-items' ),
				newItems      = [],
				notifications = $( $.parseHTML( '<ul>' + data.on_screen_notifications + '</ul>' ) ),
				appendItems   = notifications.find( '.read-item' );

			// Ignore all view notifications.
			$.each(
				removedItems,
				function ( index, id ) {
					var removedItem = notifications.find( '[data-notification-id=' + id + ']' );

					if ( removedItem.length ) {
						removedItem.closest( '.read-item' ).remove();
					}
				}
			);

			appendItems.each(
				function ( index, item ) {
					var $item = $( item ),
						id    = $item.find( '.actions .action-close' ).data( 'notification-id' );

					if ( '-1' === $.inArray( id, animatedItems ) ) {
						$item.addClass( 'pull-animation' );
						animatedItems.push( id );
						newItems.push( id );
					} else {
						$item.removeClass( 'pull-animation' );
					}
				}
			);

			// Remove brder when new item is appear.
			if ( newItems.length ) {
				appendItems.each(
					function ( index, item ) {
						var $item = $( item ),
							id    = $( item ).find( '.actions .action-close' ).data( 'notification-id' );
						if ( '-1' === $.inArray( id, newItems ) ) {
							$item.removeClass( 'recent-item' );
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

			// Show all notifications.
			wrap.removeClass( 'close-all-items' );

			// Set class 'bb-more-item' in item when more than three notifications.
			appendItems.eq( 2 ).nextAll().addClass( 'bb-more-item' );
			list.toggleClass( 'bb-more-than-3', appendItems.length > 3 );

			wrap.show();
			list.empty().html( appendItems );

			bp.Nouveau.visibilityOnScreenClearButton(); // Clear all button visibility status.
			bp.Nouveau.notificationBorder(); // Remove notification border.
			bp.Nouveau.notificationAutoHide(); // Notification auto hide.
			bp.Nouveau.browserTabFlashNotification(); // Notification on browser tab.
			bp.Nouveau.browserTabCountNotification(); // Browser tab notification count.
		},

		/**
		 * Remove notification border.
		 */
		notificationBorder: function () {
			var wrap        = $( '.bb-onscreen-notification' ),
				list        = wrap.find( '.notification-list' ),
				borderItems = list.data( 'border-items' );

			// Remove border for single notificaiton after 30s later.
			list.find( '.read-item' ).each(
				function ( index, item ) {
					var $item = $( item ),
						id    = $item.find( '.actions .action-close' ).data( 'notification-id' );

					if ( '-1' !== $.inArray( id, borderItems ) ) {
						return;
					}

					$item.addClass( 'recent-item' );
				}
			);

			// Store removed notification id in 'auto-removed-items' data attribute.
			list.attr( 'data-border-items', JSON.stringify( borderItems ) );
		},

		/**
		 * Notification count in the browser tab.
		 */
		browserTabCountNotification: function () {
			var wrap     = $( '.bb-onscreen-notification' ),
				list     = wrap.find( '.notification-list' ),
				items    = list.find( '.read-item' ),
				titleTag = $( 'html' ).find( 'title' ),
				title    = wrap.data( 'title-tag' );

			if ( 0 < items.length ) {
				titleTag.text( '(' + items.length + ') ' + title );
			} else {
				titleTag.text( title );
			}
		},

		/**
		 * Inject notification on browser tab.
		 */
		browserTabFlashNotification: function () {
			var wrap       = $( '.bb-onscreen-notification' ),
				browserTab = wrap.data( 'broser-tab' );

			// Check notification browser tab settings option.
			if ( 1 !== browserTab ) {
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
		flashTitle: function () {
			var wrap         = $( '.bb-onscreen-notification' ),
				list         = wrap.find( '.notification-list' ),
				items        = list.find( '.read-item' ),
				firstItem    = items.first(),
				notification = firstItem.find( '.notification-content .bb-full-link a' ).text(),
				titleTag     = $( 'html' ).find( 'title' ),
				title        = wrap.attr( 'data-title-tag' ),
				flashStatus  = wrap.attr( 'data-flash-status' ),
				flashItems   = list.data( 'flash-items' );

			if ( ! document.hidden ) {
				items.each(
					function ( index, item ) {
						var id = $( item ).find( '.actions .action-close' ).attr( 'data-notification-id' );

						if ( '-1' === $.inArray( id, flashItems ) ) {
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
				var id = firstItem.find( '.actions .action-close' ).attr( 'data-notification-id' );

				if ( '-1' === $.inArray( id, flashItems ) ) {
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
		notificationAutoHide: function () {
			var wrap         = $( '.bb-onscreen-notification' ),
				list         = wrap.find( '.notification-list' ),
				removedItems = list.data( 'auto-removed-items' ),
				visibility   = wrap.data( 'visibility' );

			// Check notification autohide settings option.
			if ( 'never' === visibility ) {
				return;
			}

			var hideAfter = parseInt( visibility );

			if ( 0 >= hideAfter ) {
				return;
			}

			// Remove single notification according setting option time.
			list.find( '.read-item' ).each(
				function ( index, item ) {
					var $item = $( item ),
						id    = $item.find( '.actions .action-close' ).data( 'notification-id' );

					if ( '-1' !== $.inArray( id, removedItems ) ) {
						return;
					}

					removedItems.push( id );

					setTimeout(
						function () {
							var notificationActELem = list.find( '.actions .action-close[data-notification-id=' + id + ']' );
							if ( notificationActELem.length ) {
								notificationActELem.trigger( 'click' );
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
		notificationRemovedAction: function () {
			$( '.bb-onscreen-notification .notification-list' ).on(
				'click',
				'.action-close',
				function (e) {
					e.preventDefault();
					bp.Nouveau.removeOnScreenNotification( this );
				}
			);
		},

		/**
		 * Remove single notification.
		 */
		removeOnScreenNotification: function ( self ) {
			var $self        = $( self ),
				list         = $self.closest( '.notification-list' ),
				item         = $self.closest( '.read-item' ),
				id           = $self.data( 'notification-id' ),
				removedItems = list.data( 'removed-items' );

			item.addClass( 'close-item' );

			setTimeout(
				function () {
					removedItems.push( id );

					// Set the removed notification id in data-removed-items attribute.
					list.attr( 'data-removed-items', JSON.stringify( removedItems ) );
					item.remove();
					bp.Nouveau.browserTabCountNotification();
					bp.Nouveau.visibilityOnScreenClearButton();

					// After removed get, rest of the notification.
					var items = list.find( '.read-item' );

					if ( 4 > items.length ) {
						list.removeClass( 'bb-more-than-3' );
					}

					items.slice( 0, 3 ).removeClass( 'bb-more-item' );

				},
				500
			);
		},

		/**
		 * Remove all notifications.
		 */
		removeAllNotification: function () {
			$( '.bb-onscreen-notification .bb-remove-all-notification' ).on(
				'click',
				'.action-close',
				function (e) {
					e.preventDefault();

					var list         = $( this ).closest( '.bb-onscreen-notification' ).find( '.notification-list' ),
						items        = list.find( '.read-item' ),
						removedItems = list.data( 'removed-items' );

					// Collect all removed notification ids.
					items.each(
						function ( index, item ) {
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
					var toastMessagesList = $( '.toast-messages-list > li' );
					toastMessagesList.each(
						function () {
							$( this ).removeClass( 'pull-animation' ).addClass( 'close-item' ).delay( 500 ).remove();
						}
					);
					list.removeClass( 'bb-more-than-3' );
				}
			);
		},

		/**
		 * Set title tag in notification data attribute.
		 */
		setTitle : function () {
			var $wrap = $( '.bb-onscreen-notification' );

			if ( $wrap.length ) {
				$wrap.attr( 'data-title-tag', $( 'html head' ).find( 'title' ).text() );
			}
		},

		/**
		 * Set title tag in notification data attribute.
		 */
		visibilityOnScreenClearButton : function () {
			var wrap        = $( '.bb-onscreen-notification' ),
				list        = wrap.find( '.notification-list' ),
				items       = list.find( '.read-item' ),
				closeBtn    = wrap.find( '.bb-remove-all-notification .action-close' ),
				hasMultiple = items.length > 1;

			wrap.toggleClass( 'single-notification', ! hasMultiple ).toggleClass( 'active-button', hasMultiple );

			hasMultiple ? closeBtn.fadeIn( 600 ) : items.fadeOut( 200 );
		},

		/**
		 * [switchGridList description]
		 *
		 * @return {[type]} [description]
		 */
		switchGridList: function () {

			$( document ).on(
				'click',
				'.bb-rl-grid-filters .layout-view:not(.active)',
				function ( e ) {
					e.preventDefault();

					var $this       = $( this ),
						gridfilters = $this.parents( '.bb-rl-grid-filters' ),
						object      = gridfilters.data( 'object' );

					if ( 'friends' === object ) {
						object = 'members';
					} else if ( 'group_requests' === object ) {
						object = 'groups';
					} else if ( 'notifications' === object ) {
						object = 'members';
					}

					if ( ! object || 'undefined' === typeof object ) {
						return;
					}

					if ( 'undefined' !== typeof bp.Nouveau.ajax_request && null !== bp.Nouveau.ajax_request && false !== bp.Nouveau.ajax_request ) {
						bp.Nouveau.ajax_request.abort();

						$( '.bb-rl-component-navigation [data-bp-object]' ).each(
							function () {
								$( this ).removeClass( 'loading' );
							}
						);
					}

					var layout = $this.hasClass( 'layout-list-view' ) ? 'list' : 'grid';
					gridfilters.find( '.layout-view' ).removeClass( 'active' );
					$this.addClass( 'active' );
					if ( 'list' === layout ) {
						$this.parents( '.buddypress-wrap' ).find( '.bp-list' ).removeClass( 'grid' );
					} else {
						$this.parents( '.buddypress-wrap' ).find( '.bp-list' ).addClass( 'grid' );
					}

					bp.Nouveau.ajax_request = $.ajax(
						{
							method: 'POST',
							url: bbRLAjaxUrl,
							data: {
								action: 'buddyboss_directory_save_layout',
								object: object,
								option: 'bb_layout_view',
								nonce: bbRlNonce[ object ],
								type: layout
							},
							success: function () {
							}
						}
					);
				}
			);
		},

		sendInvitesRevokeAccess: function () {

			var $memberInvitesTable = $( 'body.sent-invites #member-invites-table' );
			if ( $memberInvitesTable.length ) {

				$memberInvitesTable.find( 'tr td span a.revoked-access' ).click(
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
		 * @return {[type]} [description]
		 */
		toggleDisabledInput: function () {

			// Fetch the data attr value (id).
			// This a pro tem approach due to current conditions see.
			// https://github.com/buddypress/next-template-packs/issues/180.
			var $this            = $( this ),
				disabledControl  = $this.attr( 'data-bp-disable-input' ),
				$disabledControl = $( disabledControl );

			if ( $disabledControl.prop( 'disabled', true ) && ! $this.hasClass( 'enabled' ) ) {
				$this.addClass( 'enabled' ).removeClass( 'disabled' );
				$disabledControl.prop( 'disabled', false );

			} else if ( $disabledControl.prop( 'disabled', false ) && $this.hasClass( 'enabled' ) ) {
				$this.removeClass( 'enabled' ).addClass( 'disabled' );
				// Set using attr not .prop else DOM renders as 'disable=""' CSS needs 'disable="disable"'.
				$disabledControl.attr( 'disabled', 'disabled' );
			}
		},

		/**
		 * [keyUp description]
		 *
		 * @return {[type]} [description]
		 * @param event
		 */
		keyUp: function ( event ) {
			var self = event.data;
			if ( 27 === event.keyCode ) { // escape key.
				self.buttonRevertAll();
			}
		},

		/**
		 * [queryScope description]
		 *
		 * @return {[type]} [description]
		 * @param event
		 */
		scopeQuery: function ( event ) {
			var self   = event.data,
				target = $( event.currentTarget ),
				scope  = 'all',
				object;

			if ( target.hasClass( 'no-ajax' ) || target.hasClass( 'no-ajax' ) || ! target.find( ':selected' ).attr( 'data-bp-scope' ) ) {
				return event;
			}

			scope  = target.find( ':selected' ).data( 'bp-scope' );
			object = target.find( ':selected' ).data( 'bp-object' );

			if ( ! scope || ! object ) {
				return event;
			}

			// Stop event propagation.
			event.preventDefault();

			bp.Nouveau.commonQueryFilter(
				{
					scope  : scope,
					object : object,
					self   : self,
					target : target,
				}
			);
		},

		/**
		 * [filterQuery description]
		 *
		 * @return {[type]} [description]
		 * @param event
		 */
		filterQuery: function ( event ) {
			var self   = event.data,
				object = $( event.target ).data( 'bp-filter' );

			if ( ! object ) {
				return event;
			}

			bp.Nouveau.commonQueryFilter(
				{
					object     : $( event.target ).data( 'bp-filter' ),
					self       : self,
					filter     : $( event.target ).val(),
					fetchScope : true,
				}
			);
		},

		/**
		 * [typeGroupFilterQuery description]
		 *
		 * @return {[type]} [description]
		 * @param event
		 */
		typeGroupFilterQuery: function ( event ) {
			var self   = event.data,
				object = $( event.target ).data( 'bp-group-type-filter' );

			if ( ! object ) {
				return event;
			}

			bp.Nouveau.commonQueryFilter(
				{
					scope      : 'all',
					object     : $( event.target ).data( 'bp-group-type-filter' ),
					self       : self,
					type       : 'group',
					fetchScope : true,
					template   : null
				}
			);
		},

		/**
		 * [typeMemberFilterQuery description]
		 *
		 * @return {[type]} [description]
		 * @param event
		 */
		typeMemberFilterQuery: function ( event ) {
			var self   = event.data,
				object = $( event.target ).data( 'bp-member-type-filter' );

			if ( ! object ) {
				return event;
			}

			bp.Nouveau.commonQueryFilter(
				{
					scope      : 'all',
					object     : $( event.target ).data( 'bp-member-type-filter' ),
					self       : self,
					type       : 'member',
					fetchScope : true,
				}
			);
		},

		/**
		 * [searchQuery description]
		 *
		 * @return {[type]} [description]
		 * @param event
		 */
		searchQuery: function ( event ) {
			var self         = event.data,
				object,
				filter       = null,
				search_terms = '', order='';

			if ( $( event.delegateTarget ).hasClass( 'no-ajax' ) || undefined === $( event.delegateTarget ).data( 'bp-search' ) ) {
				return event;
			}

			// Stop event propagation.
			event.preventDefault();

			var $form    = $( event.delegateTarget );
			object       = $( event.delegateTarget ).data( 'bp-search' );
			filter       = $( '#buddypress' ).find( '[data-bp-filter="' + object + '"]' ).first().val();
			search_terms = $( event.delegateTarget ).find( 'input[type=search]' ).first().val();

			var search_parent = $( event.currentTarget ).closest( '.bb-subnav-filters-search' );
			if( search_parent.length ) {
				search_parent.addClass( 'loading' );
			}

			if ( $( self.objectNavParent + ' [data-bp-order]' ).length ) {
				order = $( self.objectNavParent + ' [data-bp-order="' + object + '"].selected' ).data( 'bp-orderby' );
			}

			bp.Nouveau.commonQueryFilter(
				{
					scope        : 'all',
					object       : object,
					filter       : filter,
					search_terms : search_terms,
					self         : self,
					fetchScope   : true,
					order_by     : order,
					event_element: $form,
				}
			);
		},

		/**
		 * [resetSearch description]
		 *
		 * @return {[type]} [description]
		 * @param event
		 */
		resetSearch: function ( event ) {
			var $delegateTarget = $( event.delegateTarget );
			if ( ! $( event.target ).val() ) {
				$delegateTarget.submit();
			} else {
				$delegateTarget.find( '[type=submit]' ).show();
			}
		},

		/**
		 * [buttonAction description]
		 *
		 * @return {[type]} [description]
		 * @param event
		 */
		buttonAction: function ( event ) {
			var self       = event.data,
				target     = $( event.currentTarget ),
				action     = target.data( 'bp-btn-action' ),
				nonceUrl   = target.data( 'bp-nonce' ),
				item       = target.closest( '[data-bp-item-id]' ),
				item_id    = item.data( 'bp-item-id' ),
				item_inner = target.closest( '.list-wrap' ),
				object     = item.data( 'bp-item-component' ),
				nonce      = '',
				component  = item.data( 'bp-used-to-component' ),
				body       = $( 'body' );

			// Simply let the event fire if we don't have needed values.
			if ( ! action || ! item_id || ! object ) {
				return event;
			}

			// Stop event propagation.
			event.preventDefault();

			if ( target.hasClass( 'bp-toggle-action-button' ) ) {
				target.html( target.data( 'title' ) );
				target.removeClass( 'bp-toggle-action-button' );
				target.addClass( 'bp-toggle-action-button-clicked' );
			}

			// check if only admin trying to leave the group.
			if ( 'undefined' !== typeof target.data( 'only-admin' ) ) {
				if ( undefined !== bbRLOnlyAdminNotice ) {
					window.alert( bbRLOnlyAdminNotice );
				}
				return false;
			}

			var allowToast   = false,
			    toastMessage = '';
			if ( 'request_membership' === action ) {
				allowToast   = true;
				toastMessage = bpNouveau.groups.i18n.sending_request;
			} else if ( 'membership_requested' === action && 'active' === $( target ).attr( 'data-popup-shown' ) ) {
				allowToast   = true;
				toastMessage = bpNouveau.groups.i18n.cancel_request_group;
			}
			if ( allowToast ) {
				jQuery( document ).trigger(
					'bb_trigger_toast_message',
					[
						'',
						'<div>' + toastMessage + '</div>',
						'loading',
						null,
						true
					]
				);
			}

			if ( 'is_friend' !== action ) {
				if (
					(
						undefined !== BP_Nouveau[ action + '_confirm' ] &&
						false === window.confirm( BP_Nouveau[action + '_confirm' ] )
					) ||
					(
						target.hasClass( 'pending' ) &&
						! target.hasClass( 'bb-rl-cancel-request' ) // Class is pending but make sure not for Cancel request button.
					)
				) {
					return false;
				}

			}

			// show popup if it is leave_group action.
			var leave_group_popup        = $( '.bb-leave-group-popup' ),
				leave_group__name        = $( target ).data( 'bb-group-name' ),
				leave_group_anchor__link = $( target ).data( 'bb-group-link' );
			if ( 'leave_group' === action && 'true' !== $( target ).attr( 'data-popup-shown' ) ) {
				if ( leave_group_popup.length ) {

					var leave_group_content = leave_group_popup.find( '.bb-leave-group-content' );
					var is_parent_group     = ! ! item.hasClass( 'has-child' );

					leave_group_content.html( is_parent_group ? bbRLParentGroupLeaveConfirm : bbRLGroupLeaveConfirm );
					if ( ! is_parent_group) {
						leave_group_content.find( '.bb-group-name' ).html( '<a href="' + leave_group_anchor__link + '">' + leave_group__name + '</a>' );
					}

					body.find( '[data-current-anchor="true"]' ).removeClass( 'bp-toggle-action-button bp-toggle-action-button-hover' ).addClass( 'bp-toggle-action-button-clicked' ); // Add clicked class manually to run function.
					leave_group_popup.show();
					$( target ).attr( 'data-current-anchor', 'true' );
					$( target ).attr( 'data-popup-shown', 'true' );
					return false;
				}
			} else {
				body.find( '[data-popup-shown="true"]' ).attr( 'data-popup-shown' , 'false' );
				body.find( '[data-current-anchor="true"]' ).attr( 'data-current-anchor' , 'false' );
				leave_group_popup.find( '.bb-leave-group-content .bb-group-name' ).html( '' );
				leave_group_popup.hide();
			}

			// show popup if it is cancel_request_group action.
			var cancel_request_group_popup        = $( '.bb-cancel-request-group-popup' ),
				cancel_request_group__name        = $.trim( $( target ).closest( '.bb-rl-group-block' ).find( '.bp-group-home-link' ).text() ),
				cancel_request_group_anchor__link = $( target ).closest( '.bb-rl-group-block' ).find( '.bp-group-home-link' ).attr('href');
			if ( 'membership_requested' === action && 'active' !== $( target ).attr( 'data-popup-shown' ) ) {
				if ( cancel_request_group_popup.length ) {

					var cancel_request_group_content = cancel_request_group_popup.find( '.bb-cancel-request-group-content' );
					cancel_request_group_content.find( '.bb-rl-modal-group-name' ).html( '<a href="' + cancel_request_group_anchor__link + '">' + cancel_request_group__name + '</a>' );


					body.find( '[data-current-anchor="true"]' ).removeClass( 'bp-toggle-action-button bp-toggle-action-button-hover' ).addClass( 'bp-toggle-action-button-clicked' ); // Add clicked class manually to run function.
					cancel_request_group_popup.show();
					$( target ).attr( 'data-current-anchor', 'true' );
					$( target ).attr( 'data-popup-shown', 'active' );
					return false;
				}
			} else {
				body.find( '[data-popup-shown="active"]' ).attr( 'data-popup-shown' , 'inactive' );
				body.find( '[data-current-anchor="true"]' ).attr( 'data-current-anchor' , 'false' );
				cancel_request_group_popup.find( '.bb-cancel-request-group-content .bb-rl-modal-group-name' ).html( '' );
				cancel_request_group_popup.hide();
			}

			// show popup if it is is_friend action.
			var remove_connection_popup = {};
			if ( $( target ).closest( '#item-header' ).length ) {
				remove_connection_popup = $( '#item-header .bb-remove-connection' );
			} else if ( $( target ).closest( '.members[data-bp-list="members"]' ).length ) {
				remove_connection_popup = $( '.members[data-bp-list="members"] .bb-remove-connection' );
			} else if ( $( target ).closest( '.group_members[data-bp-list="group_members"]' ).length ) {
				remove_connection_popup = $( '.group_members[data-bp-list="group_members"] .bb-remove-connection' );
			}
			var member__name = $( target ).data( 'bb-user-name' );
			var member_link  = $( target ).data( 'bb-user-link' );
			if ( 'is_friend' === action && 'opened' !== $( target ).attr( 'data-popup-shown' ) ) {
				if ( remove_connection_popup.length ) {
					remove_connection_popup.find( '.bb-remove-connection-content .bb-user-name' ).html( '<a href="' + member_link + '">' + member__name + '</a>' );
					body.find( '[data-current-anchor="true"]' ).removeClass( 'bp-toggle-action-button bp-toggle-action-button-hover' ).addClass( 'bp-toggle-action-button-clicked' ); // Add clicked class manually to run function.
					remove_connection_popup.show();
					$( target ).attr( 'data-current-anchor', 'true' );
					$( target ).attr( 'data-popup-shown', 'opened' );
					return false;
				}
			} else {
				body.find( '[data-popup-shown="opened"]' ).attr( 'data-popup-shown' , 'closed' );
				body.find( '[data-current-anchor="true"]' ).attr( 'data-current-anchor' , 'false' );
				if ( remove_connection_popup.length ) {
					remove_connection_popup.find( '.bb-remove-connection-content .bb-user-name' ).html( '' );
					remove_connection_popup.hide();
				}
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
							
							// Close modal if request membership is successful.
							if (
								'request_membership' === action ||
								(
									'membership_requested' === action &&
									'inactive' === $( target ).attr( 'data-popup-shown' )
								)
							) {
								// Remove existing toast message if it exists.
								if ( $( '.bb-toast-messages-list li' ).length ) {
									$( '.bb-toast-messages-list li' ).remove();
								}
								if ( undefined !== response.data.feedback ) {
									// Display feedback for request membership.
									jQuery(document).trigger(
										'bb_trigger_toast_message',
										[
											'',
											'<div>' + response.data.feedback + '</div>',
											'success',
											null,
											true
										]
									);
								}
							}

							// Group's header button.
							if ( undefined !== response.data.is_group && response.data.is_group ) {
								if ( undefined !== response.data.group_url && response.data.group_url ) {
									return window.location = response.data.group_url;
								} else {
									return window.location.reload();
								}
							}

							// If group is parent and page is group directory, then load active tab.
							if ( undefined !== response.data.is_group && response.data.is_parent ) {
								$( '#buddypress .groups-nav li.selected a' ).trigger( 'click' );
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
						if ( $( '#bb-rl-friends-my-friends-personal-li' ).length ) {
							var friend_with_count    = $( '#bb-rl-friends-my-friends-personal-li a span' );
							var friend_without_count = $( '#bb-rl-friends-my-friends-personal-li a' );
							var friends_content      = $( '.friends.bb-rl-members' );

							// Check friend count set.
							if ( undefined !== response.data.is_user && response.data.is_user && undefined !== response.data.friend_count ) {
								// Check friend count > 0 then show the count span.
								if ( '0' !== response.data.friend_count ) {
									if ( ( friend_with_count ).length ) {
										// Update count span.
										$( friend_with_count ).html( response.data.friend_count );
									} else {
										// If no friend then add count span.
										$( friend_without_count ).append( '<span class="count bb-rl-heading-count">' + response.data.friend_count + '</span>' );
									}
								} else {
									// If no friend then hide count span.
									$( friend_with_count ).hide();
									friends_content.html( bp.Nouveau.createFeedbackHtml( bpNouveau.friends.members_loop_none ) );
								}
							} else if ( undefined !== response.data.friend_count ) {
								if ( '0' !== response.data.friend_count ) {
									if ( ( friend_with_count ).length ) {
										// Update count span.
										$( friend_with_count ).html( response.data.friend_count );
									} else {
										// If no friend then add count span.
										$( friend_without_count ).append( '<span class="count bb-rl-heading-count">' + response.data.friend_count + '</span>' );
									}
								} else {
									// If no friend then hide count span.
									$( friend_with_count ).hide();
									friends_content.html( bp.Nouveau.createFeedbackHtml( bpNouveau.friends.members_loop_none ) );
								}
							}
						}

						// Update sub nav counts for group invitations, my group, friend requests, and friend counts.
						bp.Nouveau.updateSubNavCount( action );

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

						var display_error = '<div>' + bbRLSubscriptions.error + '<strong>' + title + '</strong>.</div>';
						if ( 'subscribe' === action ) {
							display_error = '<div>' + bbRLSubscriptions.subscribe_error + '<strong>' + title + '</strong></div>';
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
		 * @return {[type]} [description]
		 * @param event
		 */
		buttonRevert: function ( event ) {
			var target = $( event.currentTarget );

			if ( target.hasClass( 'bp-toggle-action-button-clicked' ) && ! target.hasClass( 'loading' ) ) {

				target.removeClass( 'bp-toggle-action-button-clicked' ).addClass( 'bp-toggle-action-button' ); // add class to detect event to confirm.
			}
		},

		/**
		 * [Leave Group Action]
		 *
		 * @param event
		 */
		leaveGroupAction : function ( event ) {
			bp.Nouveau.handleActionButtonState( event, 'bp-toggle-action-button-clicked' );
		},


		/**
		 * [Cancel Request Group Action]
		 *
		 * @param event
		 */
		cancelRequestGroupAction : function ( event ) {
			bp.Nouveau.handleActionButtonState( event, 'bp-toggle-action-button-clicked' );
		},

		/**
		 * [Leave Group Close]
		 *
		 * @param event
		 */
		leaveGroupClose: function ( event ) {
			event.preventDefault();
			bp.Nouveau.closePopup(
				event,
				{
					popupSelector      : '.bb-leave-group-popup',
					dataAnchorSelector : '[data-current-anchor="true"]',
					dataPopupSelector  : '[data-popup-shown="true"]',
					contentSelector    : '.bb-leave-group-content .bb-group-name',
					contentPlaceholder : '',
					newPopupState      : 'false'
				}
			);
		},

		/**
		 * [Cancel Request Group Action]
		 *
		 * @param event
		 */
		closeRequestGroupAction: function ( event ) {
			event.preventDefault();
			bp.Nouveau.closePopup(
				event,
				{
					popupSelector      : '.bb-cancel-request-group-popup',
					dataAnchorSelector : '[data-current-anchor="true"]',
					dataPopupSelector  : '[data-popup-shown="active"]',
					contentSelector    : '.bb-cancel-request-group-content .bb-rl-modal-group-name',
					contentPlaceholder : '',
					newPopupState      : 'false'
				}
			);
		},

		/**
		 * [Remove Connection Action]
		 *
		 * @param event
		 */
		removeConnectionAction: function ( event ) {
			bp.Nouveau.handleActionButtonState( event, 'bp-toggle-action-button-clicked' );
		},

		/**
		 * [Remove Connection Close]
		 *
		 * @param event
		 */
		removeConnectionClose: function ( event ) {
			event.preventDefault();
			bp.Nouveau.closePopup(
				event,
				{
					popupSelector      : '.bb-remove-connection',
					dataAnchorSelector : '[data-current-anchor="true"]',
					dataPopupSelector  : '[data-popup-shown="opened"]',
					contentSelector    : '.bb-remove-connection-content .bb-user-name',
					contentPlaceholder : '',
					newPopupState      : 'closed'
				}
			);
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
					var $button = $( this );
					if ( $button.hasClass( 'bp-toggle-action-button-clicked' ) && ! $button.hasClass( 'loading' ) ) {

						$button.removeClass( 'bp-toggle-action-button-clicked' ).addClass( 'bp-toggle-action-button' );
						$button.trigger( 'blur' );
					}
				}
			);
		},

		/**
		 * [addRemoveInvite description]
		 *
		 * @return {[type]} [description]
		 * @param event
		 */
		addRemoveInvite: function ( event ) {

			var currentTarget    = event.currentTarget,
				currentDataTable = $( currentTarget ).closest( 'tbody' ),
				$currentRow      = $( currentTarget ).closest( 'tr' );

			if ( $( currentTarget ).hasClass( 'field-actions-remove' ) ) {
				if ( $currentRow.siblings().length > 1 ) {
					$currentRow.remove();
					currentDataTable.find( '.field-actions-add.disabled' ).removeClass( 'disabled' );
				} else {
					return;
				}
			} else if ( $( currentTarget ).hasClass( 'field-actions-add' ) ) {
				if ( ! $( currentTarget ).hasClass( 'disabled' ) ) {
					var prev_data_row = $currentRow.prev( 'tr' ).html();
					$( '<tr>' + prev_data_row + '</tr>' ).insertBefore( $currentRow );
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
					var $row = $( this );
					$row.find( '.field-name > input' ).attr( 'name', 'invitee[' + index + '][]' );
					$row.find( '.field-name > input' ).attr( 'id', 'invitee_' + index + '_title' );
					$row.find( '.field-email > input' ).attr( 'name', 'email[' + index + '][]' );
					$row.find( '.field-email > input' ).attr( 'id', 'email_' + index + '_email' );
					$row.find( '.field-member-type > select' ).attr( 'name', 'member-type[' + index + '][]' );
					$row.find( '.field-member-type > select' ).attr( 'id', 'member_type_' + index + '_member_type' );
				}
			);
		},

		/**
		 * [closeNotice description]
		 *
		 * @return {[type]} [description]
		 * @param event
		 */
		closeNotice: function ( event ) {
			var closeBtn  = $( event.currentTarget ),
				$feedback = closeBtn.closest( '.bp-feedback' );

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
			if ( $feedback.hasClass( 'bp-sitewide-notice' ) ) {
				bp.Nouveau.ajax(
					{
						action: 'messages_dismiss_sitewide_notice'
					},
					'messages'
				);
			}

			// Remove the notice.
			$feedback.remove();
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
			var searchFilter = $( '#buddypress [data-bp-search="' + object + '"] input[type=search]' );
			if ( searchFilter.length ) {
				search_terms = searchFilter.val();
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
			var groupTypeFilter = $( '#buddypress [data-bp-group-type-filter]' );
			if ( groupTypeFilter.length ) {
				/* jshint ignore:start */
				queryData[ 'group_type' ] = groupTypeFilter.val();
				/* jshint ignore:end */
			}

			// Set member type with pagination.
			var memberTypeFilter = $( '#buddypress [data-bp-member-type-filter]' );
			if ( memberTypeFilter.length ) {
				/* jshint ignore:start */
				queryData[ 'member_type_id' ] = memberTypeFilter.val();
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
			this.openPopUp( '.popup-modal-register' );
		},

		loginPopUp: function () {
			this.openPopUp( '.popup-modal-login' );
		},

		modalDismiss: function () {
			var $modalDismiss = $( '.popup-modal-dismiss' );
			if ( $modalDismiss.length ) {
				$modalDismiss.click(
					function ( e ) {
						e.preventDefault();
						$.magnificPopup.close();
					}
				);
			}
		},

		threadListBlockPopup: function ( e ) {
			e.preventDefault();
			var contentId   = $( this ).data( 'bp-content-id' ),
				contentType = $( this ).data( 'bp-content-type' ),
				nonce       = $( this ).data( 'bp-nonce' ),
				currentHref = $( this ).attr( 'href' );

			if ( 'undefined' !== typeof contentId && 'undefined' !== typeof contentType && 'undefined' !== typeof nonce ) {
				$( document ).find( '.bp-report-form-err' ).empty();
				var mf_content = $( currentHref );
				mf_content.find( '.bp-content-id' ).val( contentId );
				mf_content.find( '.bp-content-type' ).val( contentType );
				mf_content.find( '.bp-nonce' ).val( nonce );
			}
			var blockMember = $( '#bb-rl-message-threads .block-member' );
			if ( blockMember.length > 0 ) {
				blockMember.magnificPopup(
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
			var contentId   = $( this ).data( 'bp-content-id' ),
				contentType = $( this ).data( 'bp-content-type' ),
				nonce       = $( this ).data( 'bp-nonce' ),
				currentHref = $( this ).attr( 'href' ),
				reportType  = $( this ).attr( 'reported_type' ),
				mf_content  = $( currentHref );

			if ( 'undefined' !== typeof contentId && 'undefined' !== typeof contentType && 'undefined' !== typeof nonce ) {
				$( document ).find( '.bp-report-form-err' ).empty();
				mf_content.find( '.bp-content-id' ).val( contentId );
				mf_content.find( '.bp-content-type' ).val( contentType );
				mf_content.find( '.bp-nonce' ).val( nonce );
			}
			var blockMember = $( '#bb-rl-message-threads .report-content' );
			if ( blockMember.length > 0 ) {

				$( '#bb-report-content .form-item-category' ).show();
				if ( 'user_report' === contentType ) {
					$( '#bb-report-content .form-item-category.content' ).hide();
				} else {
					$( '#bb-report-content .form-item-category.members' ).hide();
				}

				var $firstVisibleRadio = $( '#bb-report-content .form-item-category:visible:first label input[type="radio"]' );
				$firstVisibleRadio.attr( 'checked', true );

				if ( ! $firstVisibleRadio.length ) {
					$( '#report-category-other' ).attr( 'checked', true ).trigger( 'click' );
				}

				if ( 'undefined' !== typeof reportType ) {
					mf_content.find( '.bp-reported-type' ).text( reportType );
				}

				blockMember.magnificPopup(
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
			var $reportContentAndBlockMember = $( '.report-content, .block-member' );
			if ( $reportContentAndBlockMember.length > 0 ) {
				$reportContentAndBlockMember.magnificPopup(
					{
						type: 'inline',
						midClick: true,
						callbacks: {
							open: function () {
								$( '#notes-error' ).hide();
								var contentId   = this.currItem.el.data( 'bp-content-id' ),
									contentType = this.currItem.el.data( 'bp-content-type' ),
									nonce       = this.currItem.el.data( 'bp-nonce' ),
									reportType  = this.currItem.el.attr( 'reported_type' );

								var $reportContent = $( '#bb-report-content .form-item-category' );
								$reportContent.show();
								if ( 'user_report' === contentType ) {
									$reportContent.filter( '.content' ).hide();
								} else {
									$reportContent.filter( '.members' ).hide();
								}

								var $blockMemberPopup = $( '.bb-readylaunch-template .block-member-popup .bb-model-header h4' );
								if ( $blockMemberPopup.length > 0 ) {
									$blockMemberPopup.text( bpNouveau.moderation.block_member );
								}

								var $firstVisibleRadio = $( '#bb-report-content .form-item-category:visible:first label input[type="radio"]' );
								$firstVisibleRadio.attr( 'checked', true );

								if ( ! $firstVisibleRadio.length ) {
									$( '#report-category-other' ).attr( 'checked', true ).trigger( 'click' );
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
			var _this            = this;
			var $bbReportContent = $( '#bb-report-content' );

			$( document ).on(
				'click',
				'.bb-cancel-report-content',
				function ( e ) {
					e.preventDefault();
					var $closestPopup = $( this ).closest( '.moderation-popup' );
					$bbReportContent.trigger( 'reset' );
					$closestPopup.find( '.bp-other-report-cat' ).closest( '.form-item' ).addClass( 'bp-hide' );
					$closestPopup.find( '.mfp-close' ).trigger( 'click' );
				}
			);
			$( document ).on(
				'click',
				'input[type=radio][name=report_category]',
				function () {
					var $closestPopup = $( this ).closest( '.moderation-popup' );
					if ( 'other' === this.value ) {
						$closestPopup.find( '.bp-other-report-cat' ).closest( '.form-item' ).removeClass( 'bp-hide' );
					} else {
						$closestPopup.find( '.bp-other-report-cat' ).closest( '.form-item' ).addClass( 'bp-hide' );
					}
				}
			);

			// Prevent duplicate event bindings by checking if already bound.
			if ( ! $bbReportContent.data( 'report-submit-bound' ) ) {
				$bbReportContent.submit(
					function ( e ) {
						handleFormSubmission(
							{
								'event'   : e,
								'target'  : $( this ),
								'action'  : 'bp_moderation_content_report',
								'context' : _this
							}
						);
					}
				);
				$bbReportContent.data( 'report-submit-bound', true );
			}

			var $bbBlockMember = $( '#bb-block-member' );
			// Prevent duplicate event bindings for a block member form.
			if ( ! $bbBlockMember.data( 'block-member-submit-bound' ) ) {
				$bbBlockMember.submit(
					function ( e ) {
						handleFormSubmission(
							{
								'event'   : e,
								'target'  : $( this ),
								'action'  : 'bp_moderation_block_member',
								'context' : _this
							}
						);
					}
				);
				$bbBlockMember.data( 'block-member-submit-bound', true );
			}

			function handleFormSubmission( args ) {
				var e                = args.event,
					$form            = args.target,
					action           = args.action,
					$thisObj         = args.context,
					$reportSubmit    = $form.find( '.report-submit' ),
					$bpReportFormErr = $( '.bp-report-form-err' );

				if ( $form.find( '#report-category-other' ).is( ':checked' ) && '' === $form.find( '#report-note' ).val() ) {
					$( '#notes-error' ).show();
					return false;
				}

				$reportSubmit.addClass( 'loading' );
				$bpReportFormErr.empty();

				var data = { action : action };
				$.each(
					$form.serializeArray(),
					function ( _, kv ) {
						data[ kv.name ] = kv.value;
					}
				);

				$.post(
					bbRLAjaxUrl,
					data,
					function ( response ) {
						if ( response.success ) {
							$thisObj.resetReportPopup();
							$thisObj.changeReportButtonStatus( response.data );
							$reportSubmit.removeClass( 'loading' );
							$( '.mfp-close' ).trigger( 'click' );
							if ( response.data.redirect ) {
								location.href = response.data.redirect;
							}
							$( document ).trigger(
								'bb_trigger_toast_message',
								[
									'',
									response.data.toast_message,
									'info',
									null,
									true
								]
							);
						} else {
							$reportSubmit.removeClass( 'loading' );
							$thisObj.handleReportError( response.data.message.errors, e.currentTarget );
						}
					}
				);
			}
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
					var $this = $( this );
					$this.removeAttr( 'data-bp-content-id' );
					$this.removeAttr( 'data-bp-content-type' );
					$this.removeAttr( 'data-bp-nonce' );

					$this.html( data.button.link_text );
					$this.attr( 'class', data.button.button_attr.class );
					$this.attr( 'reported_type', data.button.button_attr.reported_type );
					$this.attr( 'href', data.button.button_attr.href );
					setTimeout(
						function () {
							// Waiting to load dummy image.
							_this.reportedPopup();
						},
						1
					);
				}
			);
		},
		reportedPopup: function () {
			var $reportedContent = $( '.reported-content' );
			if ( $reportedContent.length > 0 ) {
				$reportedContent.magnificPopup(
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
			var message = errors.bp_moderation_missing_data ||
							errors.bp_moderation_already_reported ||
							errors.bp_moderation_missing_error ||
							errors.bp_moderation_invalid_access ||
							errors.bp_moderation_invalid_item_id || '';

			$( target ).closest( '.bb-report-type-wrp' ).find( '.bp-report-form-err' ).html( message );
		},
		togglePassword: function () {
			$( document ).on(
				'click',
				'.bb-toggle-password, .bb-hide-pw',
				function ( e ) {
					e.preventDefault();
					var $this        = $( this ),
						$input       = $this.hasClass( 'bb-hide-pw' ) ? $this.closest( '.password-toggle' ).find( 'input' ) : $this.next( 'input' ),
						$defaultType = $input.data( 'type' ) || 'text';

					$this.toggleClass( 'bb-show-pass' );
					$input.attr( 'type', $this.hasClass( 'bb-show-pass' ) ? $defaultType : 'password' );
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
			if ( ! _.isUndefined( bbRLMedia ) &&
				! _.isUndefined( bbRLMedia.emoji ) &&
				! $targetEl.closest( '.bb-rl-post-emoji' ).length &&
				! $targetEl.is( '.bb-rl-emojioneemoji,.emojibtn' ) &&
				! $targetEl.closest( '.bb-rl-emojionearea-theatre' ).length ) {
				bp.Nouveau.closeEmojiPicker();
			}
		},

		/**
		 * Close emoji picker on Esc press
		 *
		 * @param event
		 */
		closePickersOnEsc: function ( event ) {
			if ( event.key === 'Escape' || event.keyCode === 27 ) {
				if ( ! _.isUndefined( bbRLMedia ) &&
					! _.isUndefined( bbRLMedia.emoji ) ) {
					bp.Nouveau.closeEmojiPicker();
				}
			}
		},
		/**
		 * Lazy Load Images and iframes
		 *
		 * @param lazyTarget
		 */
		lazyLoad: function ( lazyTarget ) {
			var lazy = $( lazyTarget );
			if ( lazy.length ) {
				var lazyLength = lazy.length;
				for ( var i = 0; i < lazyLength; i++ ) {
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
				var imageHeight   = $( e.currentTarget ).closest( '#cover-image-container' ).find( '.header-cover-img' ).height(),
					imageCenter   = ( imageHeight - $( e.currentTarget ).closest( '#header-cover-image' ).height() ) / 2,
					currentTarget = $( e.currentTarget );
				if ( imageHeight <= currentTarget.closest( '#header-cover-image' ).height() ) {
					$( 'body' ).append( '<div id="cover-photo-alert" style="display: block;" class="open-popup"><transition name="modal"><div class="bb-rl-modal-mask bb-white bbm-model-wrap"><div class="bb-rl-modal-wrapper"><div id="bb-rl-media-create-album-popup" class="modal-container bb-rl-has-folderlocationUI"><header class="bb-rl-bb-model-header"><h4>' + bbRLMedia.cover_photo_size_error_header + '</h4><a class="bb-rl-model-close-button" id="bp-media-create-folder-close" href="#"><span class="dashicons dashicons-no-alt"></span></a></header><div class="bb-rl-field-wrap"><p>' + bbRLMedia.cover_photo_size_error_description + '</p></div></div></div></div></transition></div>' );
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
				var saveButton = $( e.currentTarget ),
					coverImage = $( e.currentTarget ).closest( '#cover-image-container' ).find( '.header-cover-img' );
				saveButton.addClass( 'loading' );

				$.post(
					bbRLAjaxUrl,
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
		toggleMoreOption: function ( event ) {
			var $target          = $( event.target ),
				$moreOptions     = $target.closest( '.bb_more_options' ),
				$moreOptionsList = $moreOptions.find( '.bb_more_options_list' ),
				$body            = $( 'body' );

			var isOpen = $moreOptionsList.hasClass( 'is_visible' );

			$( '.bb_more_options' ).removeClass( 'more_option_active' );
			$( '.bb_more_options_list' ).removeClass( 'is_visible open' );
			$body.removeClass( 'user_more_option_open' );

			if ( $target.hasClass( 'bb_more_options_action' ) || $target.parent().hasClass( 'bb_more_options_action' ) ) {
				event.preventDefault();

				if ( !isOpen ) {
					$moreOptions.addClass( 'more_option_active' );
					$moreOptionsList.addClass( 'is_visible open' );
					$body.addClass( 'user_more_option_open' );
				}

			}

			if ( $target.closest( '.bs-dropdown-link' ).length > 0 ) {
				$body.addClass( 'bbpress_more_option_open' );
			} else {
				$body.removeClass( 'bbpress_more_option_open' );
			}
		},

		getVideoThumb: function ( file, target ) {
			// target = '.node'.

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
							var snapImage  = function () {
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
									if ( attempts >= 2 ) {
										$( file.previewElement ).closest( '.dz-preview' ).addClass( 'dz-has-no-thumbnail' );
										clearInterval( timer );
									}
									attempts++;
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
						if ( attempts >= 2 ) {
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
				xhr.onload       = function () {
					if ( 200 === this.status ) {
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
				link   = target.attr( 'href' ),
				parts  = link.split( '#' );
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
		 *
		 * @return {[type]} [description]
		 */
		toggleAccordion: function () {
			var accordion = $( this ).closest( '.bb-accordion' );
			if ( 'true' === accordion.find( '.bb-accordion_trigger' ).attr( 'aria-expanded' ) ) {
				accordion.find( '.bb-accordion_trigger' ).attr( 'aria-expanded', 'false' );
				accordion.find( '.bb-icon-angle-up' ).removeClass( 'bb-icon-angle-up' ).addClass( 'bb-icon-angle-down' );
			} else {
				accordion.find( '.bb-accordion_trigger' ).attr( 'aria-expanded', 'true' );
				accordion.find( '.bb-icon-angle-down' ).removeClass( 'bb-icon-angle-down' ).addClass( 'bb-icon-angle-up' );
			}
			accordion.toggleClass( 'is_closed' );
			accordion.find( '.bb-accordion_panel' ).slideToggle();
		},

		/**
		 *  Make Medium Editor buttons wrap.
		 *
		 *  @param editorWrap The jQuery node.
		 */
		mediumEditorButtonsWarp: function ( editorWrap ) {
			// Pass jQuery $(node).
			if ( editorWrap.hasClass( 'wrappingInitialised' ) ) { // Do not go through if it is initialed already.
				return;
			}
			editorWrap.addClass( 'wrappingInitialised' );
			var buttonsWidth = 0;
			editorWrap.find( '.medium-editor-toolbar-actions > li' ).each(
				function () {
					buttonsWidth += $( this ).outerWidth();
				}
			);
			var editorActionMore = editorWrap.find( '.medium-editor-toolbar-actions .medium-editor-action-more' );
			if ( buttonsWidth > editorWrap.width() - 10 ) { // No need to calculate if space is available.
				editorWrap.data( 'childerWith', buttonsWidth );
				if ( buttonsWidth > editorWrap.width() ) {
					if ( editorActionMore.length === 0 ) {
						editorWrap.find( '.medium-editor-toolbar-actions' ).append( '<li class="medium-editor-action-more"><button class="medium-editor-action medium-editor-action-more-button"><b></b></button><ul></ul></li>' );
					}
					editorWrap.find( '.medium-editor-action-more' ).show();
					buttonsWidth += editorActionMore.outerWidth();
					$( editorWrap.find( '.medium-editor-action' ).get().reverse() ).each(
						function () {
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
				if ( editorActionMore.length ) {
					$( editorWrap.find( '.medium-editor-action-more ul > li' ) ).each(
						function () {
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
				function ( event ) {
					event.preventDefault();
					$( this ).parent( '.medium-editor-action-more' ).toggleClass( 'active' );
				}
			);

			$( editorWrap ).find( '.medium-editor-action-more ul .medium-editor-action' ).on(
				'click',
				function ( event ) {
					event.preventDefault();
					$( this ).closest( '.medium-editor-action-more' ).toggleClass( 'active' );
				}
			);

			$( window ).one(
				'resize',
				function () {
					// Attach event once only.
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
		closeActionPopup: function ( event ) {
			event.preventDefault();
			$( this ).closest( '.bb-action-popup' ).hide();
		},

		/**
		 *  Show/Hide Search reset button
		 *
		 *  @return {function}
		 */
		directorySearchInput: function () {
			// Check if the current value of the input field is equal to the last recorded value,
			// OR if the current value is empty and there is no previously recorded value.
			if ( $( this ).val() === $( this ).data( 'last-value' ) || ( $( this ).val() === '' && $( this ).data( 'last-value' ) === undefined ) ) {
				// Return early to skip unnecessary actions.
				return;
			}

			if ( $( this ).closest( '.header-aside-inner' ).length > 0 ) {
				return;
			}

			$( this ).data( 'last-value', $( this ).val() );

			var $form        = $( this ).closest( '.search-form-has-reset' );
			var $resetButton = $form.find( '.search-form_reset' );

			if ( $( this ).val().length > 0 ) {
				$resetButton.show();
			} else {
				$resetButton.hide();

				// Trigger search event.
				if ( $form.hasClass( 'bp-invites-search-form' ) && BP_SEARCH.enable_ajax_search === '1' ) {
					var searchInputElem = $form.find( 'input[type="search"]' );
					searchInputElem.val( '' );
					searchInputElem.trigger( $.Event( 'search' ) );
				}
			}

			// Forum autocomplete should not trigger search when it's off.
			if ( ! $( this ).hasClass( 'ui-autocomplete-input' ) ) {
				var searchSubmitElem = $form.find( '.search-form_submit' );
				if ( $( this ).closest( '.bs-forums-search' ).length > 0 ) {
					if ( BP_SEARCH.enable_ajax_search === '1' ) {
						searchSubmitElem.trigger( 'click' );
					}
				} else {
					searchSubmitElem.trigger( 'click' );
				}
			}
		},

		/**
		 * Reset search results
		 *
		 * @return {function}
		 * @param e
		 */
		resetDirectorySearch: function ( e ) {
			e.preventDefault();
			var $form           = $( this ).closest( 'form' ),
				searchInputElem = $form.find( 'input[type="search"]' );
			if ( $form.filter( '.bp-messages-search-form, .bp-dir-search-form' ).length > 0 ) {
				searchInputElem.val( '' );
				$form.find( '.search-form_submit' ).trigger( 'click' );
				window.clear_search_trigger = true;
			} else if ( $form.find( '#bb_search_group_members' ).length > 0 ) {
				$form.find( '#bb_search_group_members' ).val( '' ).trigger( 'keyup' );
			} else {
				$form.find( '#bbp_search' ).val( '' );
			}

			$( this ).hide();

			// Trigger search event.
			if ( $form.hasClass( 'bp-invites-search-form' ) ) {
				searchInputElem.val( '' );
				searchInputElem.trigger( $.Event( 'search' ) );
			}
		},

		/**
		 *  Show Action Popup
		 *
		 *  @param  {object} event The event object.
		 *  @return {function}
		 */
		showActionPopup: function ( event ) {
			event.preventDefault();
			$( $( event.currentTarget ).attr( 'href' ) ).show();
		},

		/**
		 * Handle profile notification setting events.
		 */
		profileNotificationSetting: function () {
			var self = this;
			self.profileNotificationSettingInputs( ['.email', '.web', '.app'] );

			// Learn More section hide/show for mobile.
			$( '.notification_info .notification_learn_more' ).click(
				function ( e ) {
					e.preventDefault();

					var $this     = $( this ),
						$span     = $this.find( 'a span' ),
						iconClass = $span.hasClass( 'bb-icon-chevron-down' ) ? 'bb-icon-chevron-up' : 'bb-icon-chevron-down';
					$span.removeClass( 'bb-icon-chevron-down bb-icon-chevron-up' ).addClass( iconClass );
					$( this ).toggleClass( 'show' ).parent().find( '.notification_type' ).toggleClass( 'show' );

				}
			);

			// Notification settings Mobile UI.
			$( '.main-notification-settings' ).each(
				function () {
					self.NotificationMobileDropdown( $( this ).find( 'tr:not( .notification_heading )' ) );
				}
			);

			var $document = $( document );
			$document.on(
				'click',
				'.bb-mobile-setting ul li',
				function ( e ) {
					e.preventDefault();
					var $input      = $( this ).find( 'input' ),
						isChecked   = $input.is( ':checked' ),
						targetInput = $( 'input#' + $( this ).find( 'label' ).attr( 'data-for' ) );
					$input.prop( 'checked', ! isChecked );
					targetInput.trigger( 'click' );
					self.NotificationMobileDropdown( $( this ).closest( 'tr' ) );
				}
			);

			$document.on(
				'click',
				'.bb-mobile-setting .bb-mobile-setting-anchor',
				function () {
					var $parent = $( this ).parent();
					$parent.toggleClass( 'active' );
					$( '.bb-mobile-setting' ).not( $parent ).removeClass( 'active' );
				}
			);

			$document.on(
				'click',
				function ( e ) {
					if ( ! $( e.target ).hasClass( 'bb-mobile-setting-anchor' ) ) {
						$( '.bb-mobile-setting' ).removeClass( 'active' );
					}
				}
			);

		},

		/**
		 *  Add socialnetworks profile field type related class
		 */
		xProfileBlock: function () {
			$( '.profile-fields .field_type_socialnetworks' ).each(
				function () {
					$( this ).closest( '.bp-widget' ).addClass( 'social' );
				}
			);
		},

		/**
		 *  Enable Disable profile notification setting inputs
		 */
		profileNotificationSettingInputs : function ( node ) {
			var $notificationSettings = $( '.main-notification-settings' );

			node.forEach(
				function ( item ) {
					var selector = '.main-notification-settings th' + item + ' input[type="checkbox"]';

					$( document ).on(
						'click',
						selector,
						function () {
							var $checkbox = $( this ),
							targetNode    = $checkbox.closest( 'th' ).index(),
							$targetTd     = $notificationSettings.find( 'td' ).eq( targetNode ),
							$targetLi     = $notificationSettings.find( '.bb-mobile-setting li' ).eq( targetNode );

							if ( $checkbox.is( ':checked' ) ) {
								$targetTd.removeClass( 'disabled' ).find( 'input' ).prop( 'disabled', false );
								$targetLi.removeClass( 'disabled' ).find( 'input' ).prop( 'disabled', false );
							} else {
									$targetTd.addClass( 'disabled' ).find( 'input' ).prop( 'disabled', true );
									$targetLi.addClass( 'disabled' ).find( 'input' ).prop( 'disabled', true );
							}

							bp.Nouveau.NotificationMobileDropdown(
								$checkbox.closest( '#settings-form' ).find( 'tr:not(.notification_heading)' )
							);
						}
					);
				}
			);
		},

		/**
		 *  Notification Mobile UI
		 */
		NotificationMobileDropdown: function ( node ) {
			var $notificationSettings = $( '.main-notification-settings' ),
				textAll               = $notificationSettings.data( 'text-all' ),
				textNone              = $notificationSettings.data( 'text-none' );
			node.each(
				function () {
					var selectedText     = '',
						available_option = '',
						$node            = $( this ),
						nodeSelector     = $node.find( 'td' ).length ? 'td' : 'th',
						allInputsChecked = 0;
					$node.find( nodeSelector + ':not(:first-child)' ).each(
						function () {
							var $this = $( this );
							if ( $node.find( 'input[type="checkbox"]' ).length ) {
								var inputText     = $this.find( 'label' ).text(),
									inputChecked  = $this.find( 'input' ).is( ':checked' ) ? 'checked' : '',
									inputDisabled = $this.hasClass( 'disabled' ) ? ' disabled' : '';
								available_option += '<li class="' + inputText.toLowerCase() + inputDisabled + '"><input type="checkbox" class="bs-styled-checkbox" ' + inputChecked + ' /><label data-for="' + $this.find( 'input[type="checkbox"]' ).attr( 'id' ) + '">' + inputText + '</label></li>';
							}
							if ( $this.hasClass( 'disabled' ) ) {
								return;
							}
							if ( ! $this.find( 'input:checked' ).length ) {
								return;
							}
							selectedText += selectedText === '' ? $this.find( 'input[type="checkbox"] + label' ).text().trim() : ', ' + $this.find( 'input[type="checkbox"] + label' ).text().trim();
							allInputsChecked++;
						}
					);

					var $firstChild = $node.find( nodeSelector + ':first-child' );
					if ( allInputsChecked === $node.find( nodeSelector + ':not(:first-child) input[type="checkbox"]' ).length ) {
						selectedText = ( allInputsChecked === 1 ) ? selectedText : textAll;
					} else {
						selectedText = ( '' === selectedText ) ? textNone : selectedText;
					}
					var $bbMobileSetting = $firstChild.find( '.bb-mobile-setting' );
					if ( $bbMobileSetting.length === 0 ) {
						$firstChild.append( '<div class="bb-mobile-setting"><span class="bb-mobile-setting-anchor">' + selectedText + '</span><ul></ul></div>' );
					} else {
						$bbMobileSetting.find( '.bb-mobile-setting-anchor' ).text( selectedText );
					}
					$firstChild.find( '.bb-mobile-setting ul' ).html( '' );
					$firstChild.find( '.bb-mobile-setting ul' ).append( available_option );
				}
			);
		},

		/**
		 *  Register Dropzone Global Progress UI
		 *
		 *  @param  {object} dropzone The Dropzone object.
		 *  @return {function}
		 */
		dropZoneGlobalProgress: function ( dropzone ) {
			var $dropzoneElement = $( dropzone.element ),
				$globalProgress  = $dropzoneElement.find( '.dz-global-progress' );

			if ( 0 === $globalProgress.length ) {
				$dropzoneElement.append( '<div class="dz-global-progress"><div class="dz-progress-bar-full"><span class="dz-progress"></span></div><p></p><span class="bb-icon-f bb-icon-times dz-remove-all"></span></div>' );
				$dropzoneElement.addClass( 'dz-progress-view' );
				$dropzoneElement.find( '.dz-remove-all' ).click(
					function () {
						$.each(
							dropzone.files,
							function ( index, file ) {
								dropzone.removeFile( file );
							}
						);
					}
				);
			}

			var message, progress, totalProgress;
			if ( dropzone.files.length === 1 ) {
				$dropzoneElement.addClass( 'dz-single-view' );
				message  = bbRLMedia.i18n_strings.uploading + ' <strong>' + dropzone.files[ 0 ].name + '</strong>';
				progress = dropzone.files[ 0 ].upload.progress;
			} else {
				$dropzoneElement.removeClass( 'dz-single-view' );
				totalProgress = 0;
				$.each(
					dropzone.files,
					function ( index, file ) {
						totalProgress += file.upload.progress;
					}
				);
				progress = totalProgress / dropzone.files.length;
				message  = bbRLMedia.i18n_strings.uploading + ' <strong>' + dropzone.files.length + ' files</strong>';
			}

			$dropzoneElement.find( '.dz-global-progress .dz-progress' ).css( 'width', progress + '%' );
			$dropzoneElement.find( '.dz-global-progress > p' ).html( message );
		},

		userPresenceStatus: function () {
			// Active user on page load.
			window.bb_is_user_active = true;
			var idle_interval        = parseInt( BB_Nouveau_Presence.idle_inactive_span ) * 1000;

			// setup the idle time user check.
			bp.Nouveau.userPresenceChecker( idle_interval );

			if ( '' !== BB_Nouveau_Presence.heartbeat_enabled && parseInt( BB_Nouveau_Presence.presence_interval ) <= 60 ) {
				$( document ).on(
					'heartbeat-send',
					function ( event, data ) {
						if (
							'undefined' !== typeof window.bb_is_user_active &&
							true === window.bb_is_user_active
						) {
							var paged_user_id = bp.Nouveau.getPageUserIDs();
							// Add user data to Heartbeat.
							data.presence_users = paged_user_id.join( ',' );
						}

					}
				);

				$( document ).on(
					'heartbeat-tick',
					function ( event, data ) {
						// Check for our data, and use it.
						if ( ! data.users_presence ) {
							return;
						}

						bp.Nouveau.updateUsersPresence( data.users_presence );
					}
				);
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

		getPageUserIDs: function () {
			var user_ids    = [];
			var allPresence = $( document ).find( '.member-status[data-bb-user-id]' );
			if ( allPresence.length > 0 ) {
				allPresence.each(
					function () {
						var user_id = $( this ).attr( 'data-bb-user-id' );
						if ( $.inArray( parseInt( user_id ), user_ids ) === -1 ) {
							user_ids.push( parseInt( user_id ) );
						}
					}
				);
			}

			return user_ids;
		},

		updateUsersPresence: function ( presence_data ) {
			if ( presence_data && presence_data.length > 0 ) {
				$.each(
					presence_data,
					function ( index, user ) {
						bp.Nouveau.updateUserPresence( user.id, user.status );
					}
				);
			}
		},

		updateUserPresence: function ( user_id, status ) {
			$( document )
			.find( '.member-status[data-bb-user-id="' + user_id + '"]' )
			.removeClass( 'offline online' )
			.addClass( status )
			.attr( 'data-bb-user-presence', status );
		},

		userPresenceChecker: function (inactive_timeout) {

			var wait = setTimeout(
				function () {
					window.bb_is_user_active = false;
				},
				inactive_timeout
			);

			document.onmousemove = document.mousedown = document.mouseup = document.onkeydown = document.onkeyup = document.focus = function () {
				clearTimeout( wait );
				wait                     = setTimeout(
					function () {
						window.bb_is_user_active = false;
					},
					inactive_timeout
				);
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
			render: function ( renderOptions ) {
				var self = this;
				// Link Preview Template.
				var tmpl = $( '#tmpl-bb-link-preview' ).html();

				// Compile the template.
				var compiled = _.template( tmpl );

				var html = compiled( renderOptions );

				if ( self.currentPreviewParent ) {
					self.currentPreviewParent.html( html );
				}

				if ( self.options.link_loading === true || self.options.link_swap_image_button === 1 ) {
					return;
				}

				if ( self.controlsAdded === null ) {
					self.registerControls();
				}

				if ( self.options.link_error === true ) {
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

					self.dataInput.val( JSON.stringify( link_preview_data ) ).trigger( 'change' );

				}

			},
			registerControls: function () {
				var self                       = this;
				self.displayNextPrevButtonView = function () {
					$( '.bb-url-scrapper-container #bb-url-prevPicButton' ).show();
					$( '.bb-url-scrapper-container #bb-url-nextPicButton' ).show();
					$( '.bb-url-scrapper-container #bb-link-preview-select-image' ).show();
					$( '.bb-url-scrapper-container #icon-exchange' ).hide();
					$( '.bb-url-scrapper-container #bb-link-preview-remove-image' ).hide();
				};

				$( self.currentPreviewParent ).on(
					'click',
					'#bb-link-preview-remove-image',
					function ( e ) {
						e.preventDefault();
						self.options.link_images           = [];
						self.options.link_image_index      = 0;
						self.options.link_image_index_save = '-1';
						self.render( self.options );
					}
				);

				$( self.currentPreviewParent ).on(
					'click',
					'#bb-close-link-suggestion',
					function ( e ) {
						e.preventDefault();

						// Remove the link preview for the draft too.
						$( '#bb_link_url' ).val( '' );

						// Set default values.
						Object.assign(
							self.options,
							{
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
							}
						);
						self.render( self.options );
					}
				);

				$( self.currentPreviewParent ).on(
					'click',
					'#icon-exchange',
					function ( e ) {
						e.preventDefault();
						self.options.link_swap_image_button = 1;
						self.displayNextPrevButtonView();
					}
				);

				$( self.currentPreviewParent ).on(
					'click',
					'#bb-url-prevPicButton',
					function ( e ) {
						e.preventDefault();
						var imageIndex = self.options.link_image_index;
						if ( imageIndex > 0 ) {
							Object.assign(
								self.options,
								{
									link_image_index : parseInt( imageIndex ) - 1,
									link_swap_image_button : 1
								}
							);
							self.render( self.options );
							self.displayNextPrevButtonView();
						}
					}
				);

				$( self.currentPreviewParent ).on(
					'click',
					'#bb-url-nextPicButton',
					function ( e ) {
						e.preventDefault();
						var imageIndex = self.options.link_image_index;
						var images     = self.options.link_images;
						if ( imageIndex < images.length - 1 ) {
							Object.assign(
								self.options,
								{
									link_image_index : parseInt( imageIndex ) + 1,
									link_swap_image_button : 1
								}
							);
							self.render( self.options );
							self.displayNextPrevButtonView();
						}
					}
				);

				$( self.currentPreviewParent ).on(
					'click',
					'#bb-link-preview-select-image',
					function ( e ) {
						e.preventDefault();
						self.options.link_image_index_save  = self.options.link_image_index;
						self.options.link_swap_image_button = 0;
						$( '.bb-url-scrapper-container #icon-exchange' ).show();
						$( '.bb-url-scrapper-container #activity-link-preview-remove-image' ).show();
						$( '.bb-url-scrapper-container #activity-link-preview-select-image' ).hide();
						$( '.bb-url-scrapper-container #activity-url-prevPicButton' ).hide();
						$( '.bb-url-scrapper-container #activity-url-nextPicButton' ).hide();
						self.render( self.options );
					}
				);

				self.controlsAdded = true;
			},
			scrapURL: function ( urlText, targetPreviewParent, targetDataInput ) {
				var self           = this;
				var urlString      = '';
				var bbLinkUrlInput = '';

				if ( targetPreviewParent ) {
					var formEl = targetPreviewParent.closest( 'form' );
					if ( formEl.find( 'input#bb_link_url' ).length > 0 && formEl.find( 'input#bb_link_url' ).val() !== '' ) {
						var currentValue                   = JSON.parse( formEl.find( 'input#bb_link_url' ).val() );
						self.options.link_url              = currentValue.url ? currentValue.url : '';
						self.options.link_image_index_save = currentValue.link_image_index_save;
						bbLinkUrlInput                     = self.options.link_url;
					}
				}

				if ( ( urlText === null || urlText === '' ) && self.options.link_url === undefined ) {
					return;
				}

				if ( targetPreviewParent ) {

					if ( 0 === targetPreviewParent.children( '.bb-url-scrapper-container' ).length ) {
						targetPreviewParent.prepend( '<div class="bb-url-scrapper-container"><div>' );
					}
					self.currentPreviewParent = targetPreviewParent.find( '.bb-url-scrapper-container' );
				}

				if ( targetDataInput.length > 0 && targetDataInput.prop( 'tagName' ).toLowerCase() === 'input' ) {
					self.dataInput = targetDataInput;
				}

				// Create a DOM parser.
				var parser = new DOMParser();
				var doc    = parser.parseFromString( urlText, 'text/html' );

				// Exclude the mention links from the urlText.
				var anchorElements = doc.querySelectorAll( 'a.bp-suggestions-mention' );
				anchorElements.forEach(
					function ( anchor ) {
						anchor.remove(); }
				);

				// parse html now to get the url.
				urlText = doc.body.innerHTML;

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
					if ( 'undefined' !== typeof bbRLForums.params.excluded_hosts && bbRLForums.params.excluded_hosts.indexOf( hostname ) !== -1 ) {
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
					var urlTextLength = urlText.length;
					for ( var i = startIndex; i < urlTextLength; i++ ) {
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
								if ( urlObj.url === url ) {
									urlResponse = urlObj.response;
									return false;
								}
							}
						);
					}

					if ( self.loadURLAjax != null ) {
						self.loadURLAjax.abort();
					}

					Object.assign(
						self.options,
						{
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
						if ( formEl.find( 'input#bb_link_url' ).length > 0 && formEl.find( 'input#bb_link_url' ).val() !== '' ) {
							var prev_preview_value = JSON.parse( formEl.find( 'input#bb_link_url' ).val() );
							if ( '' !== prev_preview_value.url && prev_preview_value.url !== url ) {

								// Reset older preview data.
								self.options.link_image_index_save = 0;
								formEl.find( 'input#bb_link_url' ).val( '' );
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
							function ( response ) {
								// success callback.
								self.setURLResponse( response, url );
							}
						).always(
							function () {
								// always callback.
							}
						);
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
					var urlImages                 = response.images;
					self.options.link_image_index = 0;
					var urlImagesIndex            = '0';
					if ( '' !== self.options.link_image_index_save && ! _.isUndefined( self.options.link_image_index_save ) ) {
						urlImagesIndex = parseInt( self.options.link_image_index_save );
					}
					if ( self.options.link_image_index_save === '-1' ) {
						urlImagesIndex = '';
						urlImages      = [];
					} else if ( _.isUndefined( self.options.link_image_index_save ) ) {
						self.options.link_image_index_save = 0;
					}
					Object.assign(
						self.options,
						{
							link_success: true,
							link_url: url,
							link_title: ! _.isUndefined( response.title ) ? response.title : '',
							link_description: ! _.isUndefined( response.description ) ? response.description : '',
							link_images: urlImages,
							link_image_index: urlImagesIndex,
							link_embed: ! _.isUndefined( response.wp_embed ) && response.wp_embed
						}
					);

					var whatsNewAttachments = $( '#bb-rl-whats-new-attachments' );
					if ( whatsNewAttachments.hasClass( 'bb-video-preview' ) ) {
						whatsNewAttachments.removeClass( 'bb-video-preview' );
					}

					if ( whatsNewAttachments.hasClass( 'bb-link-preview' ) ) {
						whatsNewAttachments.removeClass( 'bb-link-preview' );
					}

					if ( ( 'undefined' !== typeof response.description && response.description.indexOf( 'iframe' ) > -1 ) || ( ! _.isUndefined( response.wp_embed ) && response.wp_embed ) ) {
						whatsNewAttachments.addClass( 'bb-video-preview' );
					} else {
						whatsNewAttachments.addClass( 'bb-link-preview' );
					}

					self.loadedURLs.push( { 'url': url, 'response': response } );
					self.render( self.options );
				} else {
					Object.assign(
						self.options,
						{
							link_success: false,
							link_error: true,
							link_error_msg: response.error,
							link_loading: false,
							link_images: []
						}
					);
					self.render( self.options );
				}
			},
		},

		/**
		 *  Refresh current activities, used after updating pinned post.
		 */
		refreshActivities: function () {

			var self   = this, object = 'activity', scope = 'all',
				filter = null, objectData, extras = null, search_terms;

			objectData = self.getStorage( 'bp-' + object );

			if ( undefined !== objectData.scope ) {
				scope = objectData.scope;
			}

			if (
				'' === scope ||
				false === scope ||
				(
					'undefined' !== BP_Nouveau.is_send_ajax_request &&
					'' === BP_Nouveau.is_send_ajax_request
				)
			) {
				if ( $( 'body.activity.single-item' ).hasClass( 'groups' ) ) {

					// Groups single activity page.
					scope = 'all';
				} else if ( $( bp.Nouveau.objectNavParent + ' #bb-subnav-filter-show [data-bp-scope].selected' ).length ) {

					// Get the filter selected.
					scope = $( bp.Nouveau.objectNavParent + ' #bb-subnav-filter-show [data-bp-scope].selected' ).data( 'bp-scope' );
				}
			}

			if ( undefined !== objectData.extras ) {
				extras = objectData.extras;
			}

			var $filterElement = $( '#buddypress [data-bp-filter="' + object + '"]' );
			if ( $filterElement.length ) {
				if ( undefined !== objectData.filter ) {
					filter = objectData.filter;
					$filterElement.find( 'option[value="' + filter + '"]' ).prop( 'selected', true );
				} else if ( '-1' !== $filterElement.val() && '0' !== $filterElement.val() ) {
					filter = $filterElement.val();
				}
			}

			var objectNav = $( this.objectNavParent + ' [data-bp-object="' + object + '"]' );
			if ( objectNav.length ) {
				objectNav.each(
					function () {
						$( this ).removeClass( 'selected' );
					}
				);

				$( this.objectNavParent + ' [data-bp-scope="' + object + '"], #object-nav li.current' ).addClass( 'selected' );
			}

			var searchFilter = $( '#buddypress [data-bp-search="' + object + '"] input[type=search]' );
			search_terms     = searchFilter.val();

			// Check the querystring to eventually include the search terms.
			if ( null !== self.querystring ) {
				if ( undefined !== self.querystring[ object + '_search' ] ) {
					search_terms = decodeURI( self.querystring[ object + '_search' ] );
				} else if ( undefined !== self.querystring.s ) {
					search_terms = decodeURI( self.querystring.s );
				}

				if ( search_terms ) {
					searchFilter.val( search_terms );
				}
			}

			if ( $( '#buddypress [data-bp-list="' + object + '"]' ).length ) {
				var queryData = {
					object: object,
					scope: scope,
					filter: filter,
					search_terms: search_terms,
					extras: extras
				};

				// Populate the object list.
				bp.Nouveau.objectRequest( queryData );
			}

			bp.Nouveau.Activity.activityPinHasUpdates = false;
		},

		/**
		 *  Get current Server Time
		 */
		bbServerTime: function () {

			var localTime         = new Date(),
				currentServerTime = new Date( localTime.getTime() + bp.Nouveau.bbServerTimeDiff );

			// Extract date, year, and time components.
			var date = currentServerTime.toLocaleDateString( 'en-US', { month: 'short', day: '2-digit' } ),
				year = currentServerTime.getFullYear(),
				time = currentServerTime.toLocaleTimeString( 'en-US', { hour: '2-digit', minute: '2-digit', hour12: true } );

			return {
				currentServerTime: currentServerTime,
				date: date,
				year: year,
				time: time
			};
		},

		/**
		 * Insert blank space at cursor position to prevent duplicated emoji from windows system emoji picker.
		 */
		mediumFormAction: function ( event ) {
			var element;

			event = event || window.event;

			if ( event.target ) {
				element = event.target;
			} else if ( event.srcElement) {
				element = event.srcElement;
			}

			if ( navigator.userAgent.indexOf( 'Win' ) !== -1 && $( element ).hasClass( 'medium-editor-element' ) && event.metaKey ) {
				var content = element.innerHTML || element.textContent;
				content     = content.trim();
				if ( ! content ) {
					event.preventDefault();
					this.insertBlankSpaceAtCursor();
				}
			}
		},

		insertBlankSpaceAtCursor: function () {
			var selection = window.getSelection();
			if ( ! selection.rangeCount ) {
				return;
			}

			var range           = selection.getRangeAt( 0 ),
				spaceNode       = document.createElement( 'span' );
			spaceNode.innerHTML = '&nbsp;';

			range.insertNode( spaceNode );
			range.setStartAfter( spaceNode );
			range.setEndAfter( spaceNode );

			selection.removeAllRanges();
			selection.addRange( range );
		},

		toggleActivityOption: function ( event ) {
			var target      = $( event.target ),
				body        = $( 'body' ),
				optionsWrap = target.closest( '.bb-activity-more-options-wrap' ),
				options     = $( '.bb-activity-more-options-wrap' ).find( '.bb-activity-more-options' );
			if ( target.hasClass( 'bb-activity-more-options-action' ) || target.parent().hasClass( 'bb-activity-more-options-action' ) ) {

				if ( optionsWrap.find( '.bb-activity-more-options' ).hasClass( 'is_visible open' ) ) {
					options.removeClass( 'is_visible open' );
					body.removeClass( 'more_option_open' );
				} else {
					options.removeClass( 'is_visible open' );
					optionsWrap.find( '.bb-activity-more-options' ).addClass( 'is_visible open' );
					body.addClass( 'more_option_open' );
				}

			} else {
				options.removeClass( 'is_visible open' );
				body.removeClass( 'more_option_open' );
			}
		},

		handleActionButtonState: function ( event, newClass ) {
			event.preventDefault();
			var $body          = $( 'body' ),
				$currentAnchor = $body.find( '[data-current-anchor="true"]' );
			$currentAnchor.removeClass( 'bp-toggle-action-button bp-toggle-action-button-hover' ).addClass( newClass );
			$currentAnchor.trigger( 'click' );
		},

		closePopup: function ( event, options ) {
			var target = $( event.currentTarget ),
				popup  = $( target ).closest( options.popupSelector ),
				$body  = $( 'body' );

			// Find and update the current anchor before resetting it for friendship button.
			var $currentAnchor = $body.find( '[data-current-anchor="true"]' );
			if ( $currentAnchor.length && $currentAnchor.hasClass( 'friendship-button' ) ) {
				var titleValue = $currentAnchor.attr( 'data-title' );
				if ( titleValue ) {
					// Decode HTML entities and update balloon.
					var decodedTitle = $( '<div/>' ).html( titleValue ).text();
					$currentAnchor.attr( 'data-balloon', decodedTitle );
				}

				// For primary hover actions, also update the HTML content.
				if ( $currentAnchor.hasClass( 'bb-rl-primary-hover-action' ) ) {
					var aTagText = $currentAnchor.attr( 'data-title-displayed' );
					if ( aTagText ) {
						$currentAnchor.html( aTagText );
					}
				}
			}

			// Reset data attributes and content.
			$body.find( options.dataAnchorSelector ).attr( 'data-current-anchor', 'false' );
			$body.find( options.dataPopupSelector ).attr( 'data-popup-shown', options.newPopupState );

			// Clear content and hide popup.
			popup.find( options.contentSelector ).html( options.contentPlaceholder );
			popup.hide();
		},

		openPopUp: function ( popupSelector ) {
			var $modal = $( popupSelector );

			if ( $modal.length ) {
				$modal.magnificPopup(
					{
						type            : 'inline',
						preloader       : false,
						fixedBgPos      : true,
						fixedContentPos : true
					}
				);
			}

			this.modalDismiss();
		},

		closeEmojiPicker: function () {
			$( '.bb-rl-post-emoji.active, .emojionearea-button.active' ).removeClass( 'active' );
			if ( $( '.bb-rl-emojionearea-theatre.show' ).length > 0 ) {
				var $emojioneAreaTheatre = $( '.bb-rl-emojionearea-theatre' );
				$emojioneAreaTheatre.removeClass( 'show' ).addClass( 'hide' );
				$emojioneAreaTheatre.find( '.emojionearea-picker' ).addClass( 'hidden' );
			}
		},

		commonQueryFilter: function ( data ) {
			var self         = data.self,
			    object       = data.object,
			    target       = data.target,
			    scope        = data.scope,
			    filter,
			    search_terms = ! _.isUndefined( data.search_terms ) ? data.search_terms : '',
			    extras       = null,
			    queryData,
			    type         = data.type,
			    order        = data.order_by,
			    template     = data.template || null;

			// Filter type.
			if ( 'friends' === object ) {
				object = 'members';
			}

			var objectData = self.getStorage( 'bp-' + object );

			// Notifications always need to start with Newest ones.
			if ( undefined !== objectData.extras && 'notifications' !== object ) {
				extras = objectData.extras;
			}

			filter = $( '#buddypress' ).find( '[data-bp-filter="' + object + '"]' ).first().val();

			if ( type ) { // Group/Member type filter.
				var bbFilterElem = $( '#buddypress [data-bp-filter="' + object + '"]' );
				if ( bbFilterElem.length ) {
					if ( undefined !== objectData.filter ) {
						filter = objectData.filter;
						$( '#buddypress [data-bp-filter="' + object + '"] option[value="' + filter + '"]' ).prop( 'selected', true );
					} else if ( '-1' !== bbFilterElem.val() && '0' !== bbFilterElem.val() ) {
						filter = bbFilterElem.val();
					}
				}
			}

			if ( ! _.isUndefined( data.fetchScope ) && data.fetchScope ) { // Filter type.
				var $selectedObject = $( self.objectNavParent + ' [data-bp-object].selected' );
				if ( $selectedObject.length ) {
					scope = $selectedObject.data( 'bp-scope' );
				}
			}

			if ( '' !== search_terms ) { // Filter, Group/Member, Scope filter.
				var searchFilter = $( '#buddypress [data-bp-search="' + object + '"] input[type=search]' );
				if ( searchFilter.length ) {
					search_terms = searchFilter.val();
				}
			}

			var search_parent = $( event.currentTarget ).closest( '.bb-subnav-filters-search' );
			if( search_parent.length ) {
				search_parent.addClass( 'loading' );
			}

			// For scope - Remove the New count on dynamic tabs.
			if ( ! _.isUndefined( target ) && target.hasClass( 'dynamic' ) ) {
				target.find( 'a span' ).html( '' );
			}

			queryData = {
				object       : object,
				scope        : scope,
				filter       : filter,
				search_terms : search_terms,
				page         : 1,
				extras       : extras,
				template     : template,
				order_by     : order,
				event_element: data.$form ? data.$form : '',
			};

			if ( '' === search_terms ) { // Exclude a type from a search query.
				var memberTypeFilter = $( '#buddypress [data-bp-member-type-filter="' + object + '"]' );
				var groupTypeFilter  = $( '#buddypress [data-bp-group-type-filter="' + object + '"]' );
				if ( memberTypeFilter.length ) {
					queryData.member_type_id = memberTypeFilter.val();
				} else if ( groupTypeFilter.length ) {
					queryData.group_type = groupTypeFilter.val();
				}
			}

			self.objectRequest( queryData );
		},

		groupManageAction: function ( event ) {
			var target = $( event.currentTarget );
			var currentValue = target.val();

			// Reset all other select elements
			$( '.bb-rl-groups-manage-members-list select.member-action-dropdown' ).not( target ).each( function() {
				$( this ).val( '' ).trigger( 'change.select2' );
			} );

			// Disable all action buttons
			$( '.bb-rl-group-member-action-button' ).addClass( 'disabled' );

			// Enable only the button related to the changed select
			var action_button = target.parents( '.members-manage-buttons' ).find( '.bb-rl-group-member-action-button' );
			if ( currentValue ) {
				action_button.removeClass('disabled');
			} else {
				action_button.addClass('disabled');
			}
		},

		groupManageActionClick: function ( event ) {
			var target = $( event.currentTarget );
			var action_url = target.parents( '.members-manage-buttons' ).find( '.member-action-dropdown' ).val();
			if ( action_url ) {
				window.location.href = action_url;
			}

			return false;
		},

		/**
		 * Function to cancel ongoing AJAX request.
		 */
		abortOngoingRequest: function () {
			if ( currentRequest ) {
				currentRequest.abort();
				currentRequest = null;
			}
		},

		/**
		 * Helper function to clear cache for a specific member.
		 */
		clearCacheProfileCard: function ( memberId ) {
			if ( this.cacheProfileCard[memberId] ) {
				delete this.cacheProfileCard[memberId];
			}
		},

		/**
		 * Helper function to reset profile popup cards.
		 */
		resetProfileCard: function () {
			var $profileCard = $( '#profile-card' );

			$profileCard.attr( 'data-bp-item-id', '' ).removeClass( 'show loading' );
			$profileCard.find( '.bb-rl-card-footer, .skeleton-card-footer' ).removeClass( 'bb-rl-card-footer--plain' );
			$profileCard.find( '.bb-card-profile-type' ).removeClass( 'hasMemberType' ).text( '' ).removeAttr( 'style' );
			$profileCard.find( '.card-profile-status' ).removeClass( 'active' );
			$profileCard.find( '.bb-rl-card-heading' ).text( '' );
			$profileCard.find( '.card-meta-joined span, .card-meta-last-active, .card-meta-followers' ).text( '' );
			$profileCard.find( '.bb-rl-card-avatar img' ).attr( 'src', '' );
			$profileCard.find( '.card-button-follow' ).attr( 'data-bp-btn-action', '' ).attr( 'id', '' );
			$profileCard.find( '.follow-button.generic-button' ).removeClass( 'following not_following' ).attr( 'id', '' );
			$profileCard.find( '.send-message' ).attr( 'href', '' );
			$profileCard.find( '.bb-rl-card-action-connect' ).html( '' );
			$profileCard.find( '.bb-rl-card-action-follow' ).html( '' );
		},

		/**
		 * Helper function to update and populate profile popup cards with data.
		 */
		updateProfileCard: function ( data, currentUser ) {
			var $profileCard    = $( '#profile-card' );
			var registeredDate  = new Date( data.registered_date );
			var joinedDate      = new Intl.DateTimeFormat( 'en-US', {
				year : 'numeric',
				month: 'short'
			} ).format( registeredDate );
			var activeStatus    = data.last_activity === 'Active now' ? 'active' : '';
			var memberTypeClass = data.member_types && Array.isArray( data.member_types ) && data.member_types.length > 0 ? 'hasMemberType' : '';
			var memberType      = data.member_types && Array.isArray( data.member_types ) && data.member_types.length > 0 ? data.member_types[0].labels.singular_name : '';
			var memberTypeCSS   = {};
			if ( data.member_types && Array.isArray( data.member_types ) && data.member_types.length > 0 ) {
				var labelColors                   = data.member_types[0].label_colors || {};
				memberTypeCSS.color               = labelColors.color || '';
				memberTypeCSS['background-color'] = labelColors['background-color'] || '';
			}

			$profileCard.addClass( 'show' ).attr( 'data-bp-item-id', data.id );
			$profileCard.find( '.bb-rl-card-avatar img' ).attr( 'src', data.avatar_urls.thumb );
			$profileCard.find( '.card-profile-status' ).addClass( activeStatus );
			$profileCard.find( '.bb-rl-card-heading' ).text( data.profile_name );
			$profileCard.find( '.bb-card-profile-type' ).addClass( memberTypeClass ).text( memberType ).css( memberTypeCSS );
			$profileCard.find( '.card-meta-joined span' ).text( joinedDate );
			$profileCard.find( '.card-meta-last-active' ).text( data.last_activity );
			$profileCard.find( '.card-meta-followers' ).text( data.followers );
			$profileCard.find( '.bb-rl-card-footer .card-button-profile' ).attr( 'href', data.link );

			if ( currentUser ) {
				$profileCard.find( '.bb-rl-card-footer' ).addClass( 'bb-rl-card-footer--plain' );
			}

			var buttonRenderCount = 0;

			var $messageButton = $profileCard.find( '.bb-rl-card-action-message' );
			if ( $messageButton.length ) {
				if ( data.can_send_message && buttonRenderCount < 2 ) {
					$messageButton.find( '.send-message' ).attr( 'href', data.message_url );
					buttonRenderCount++;
					$messageButton.removeClass( 'bp-hide' );
				} else {
					$messageButton.addClass( 'bp-hide' );
				}
			}

			var $followButtonWrapper = $profileCard.find( '.bb-rl-card-action-follow' );
			if ( $followButtonWrapper.length ) {
				if ( data.follow_button_html && buttonRenderCount < 2  ) {
					$followButtonWrapper.html( data.follow_button_html );
					buttonRenderCount++;
					$followButtonWrapper.removeClass( 'bp-hide' );
				} else {
					$followButtonWrapper.addClass( 'bp-hide' );
				}
			}

			var $connectButtonWrapper = $profileCard.find( '.bb-rl-card-action-connect' );
			if ( $connectButtonWrapper.length ) {
				if ( data.friend_button_html && buttonRenderCount < 2 ) {
					$connectButtonWrapper.html( data.friend_button_html );
					buttonRenderCount++;
					$connectButtonWrapper.removeClass( 'bp-hide' );
				} else {
					$connectButtonWrapper.addClass( 'bp-hide' );
				}
			}

			bp.Nouveau.bindPopoverEvents();
		},

		/**
		 * Detects if the current device is a touch device.
		 */
		isTouchDevice: function() {
			return ( 'ontouchstart' in window ) || 
				   ( navigator.maxTouchPoints > 0 ) || 
				   ( navigator.msMaxTouchPoints > 0 );
		},

		/**
		 * Profile popup card for avatars.
		 */
		profilePopupCard: function () {
			// Skip popup card functionality for touch devices to improve user experience.
			if ( bp.Nouveau.isTouchDevice() ) {
				return;
			}

			$( '#buddypress #profile-card, #bbpress-forums #profile-card, #page #profile-card' ).remove();
			var profileCardTemplate = bp.template( 'profile-card-popup' );
			var renderedProfileCard = profileCardTemplate();

			var bbpressForums = $( '#bbpress-forums' ),
			    buddypress    = $( '#buddypress' );
			if ( bbpressForums.length ) {
				bbpressForums.append( renderedProfileCard );
			} else if ( buddypress.length ) {
				buddypress.append( renderedProfileCard );
			} else {
				$( '#page' ).append( renderedProfileCard );
			}

			var $avatar = $( this );

			// Disable popup card for specific locations
			var blockedContainers = '.message-members-list.member-popup, #mass-user-block-list';
			if ( $avatar.closest( blockedContainers ).length ) {
				return;
			}

			if ( ! $avatar.attr( 'data-bb-hp-profile' ) || ! $avatar.attr( 'data-bb-hp-profile' ).length ) {
				return;
			}

			var memberId = $avatar.attr( 'data-bb-hp-profile' );
			if ( ! memberId ) {
				return;
			}

			var currentUserId = 0;
			if ( ! _.isUndefined( BP_Nouveau.activity.params.user_id ) ) {
				currentUserId = BP_Nouveau.activity.params.user_id;
			}

			// Skip showing profile card for current user
			if ( parseInt( currentUserId ) === parseInt( memberId ) ) {
				return;
			}

			var currentUser = parseInt( currentUserId ) === parseInt( memberId );
			var restUrl = BP_Nouveau.rest_url;
			var url = restUrl + '/members/' + memberId + '/info';
			var $profileCard = $( '#profile-card' );

			// Cancel any ongoing request if it's for a different memberId.
			if ( bp.Nouveau.currentRequestMemberId && bp.Nouveau.currentRequestMemberId !== memberId ) {
				bp.Nouveau.abortOngoingRequest();
			}

			// Always update position.
			var position = bp.Nouveau.setPopupPosition( $avatar );
			$profileCard.css( {
				top: position.top + 'px',
				left: position.left + 'px',
				bottom: position.bottom + 'px',
				right: position.right + 'px'
			} );

			// Avoid duplicate AJAX requests for same memberId.
			if ( bp.Nouveau.currentRequestMemberId === memberId ) {
				$profileCard.addClass( 'show' );
				if ( !bp.Nouveau.cacheProfileCard[memberId] ) {
					$profileCard.addClass( 'loading' );
				}
				return;
			}

			// Set current request memberId.
			bp.Nouveau.currentRequestMemberId = memberId;

			// Check cache.
			if ( bp.Nouveau.cacheProfileCard[memberId] ) {
				var cachedProfileData = bp.Nouveau.cacheProfileCard[memberId];
				bp.Nouveau.updateProfileCard( cachedProfileData, currentUser );

				$profileCard.removeClass( 'loading' );
				popupCardLoaded = true;
				bp.Nouveau.currentRequestMemberId = null;
				return;
			}

			// Set up a new AbortController for current request.
			var controller = new AbortController();
			currentRequest = controller;

			if ( popupCardLoaded ) {
				return;
			}

			$.ajax( {
				url       : url,
				method    : 'GET',
				headers   : {
					'X-WP-Nonce': BP_Nouveau.rest_nonce
				},
				signal    : controller.signal, // Attach the signal to the request.
				beforeSend: function () {
					bp.Nouveau.resetProfileCard();

					$profileCard.addClass( 'show loading' );
					if ( currentUser ) {
						$profileCard.find( '.skeleton-card-footer' ).addClass( 'bb-rl-card-footer--plain' );
					}
				},
				success   : function ( data ) {
					// Check if this request was aborted.
					if ( controller.signal.aborted ) {
						return;
					}
					// Cache profile data.
					bp.Nouveau.cacheProfileCard[memberId] = data;

					// Check if hovering over avatar or popup.
					if ( hoverProfileAvatar || hoverProfileCardPopup ) {
						if ( hoverAvatar || hoverCardPopup ) {
							// Get a fresh reference to the profile card
							var $currentProfileCard = $( '#profile-card' );
							$currentProfileCard.removeClass( 'loading' );

							bp.Nouveau.updateProfileCard( data, currentUser );
							popupCardLoaded = true;
						} else {
							bp.Nouveau.hidePopupCard();
						}
					}

					bp.Nouveau.currentRequestMemberId = null;
				},
				error     : function ( xhr, status, error ) {
					console.error( 'Error fetching member info:', error );
					$profileCard.html( '<span>Failed to load data.</span>' );
					bp.Nouveau.currentRequestMemberId = null;
				}
			} );
		},

		setPopupPosition: function ( $element ) {
			var offset = $element.offset();
			var popupTop, popupLeft, popupBottom;
			var rightEdgeDistance = window.innerWidth - ( offset.left + $element.outerWidth() );
			var spaceBelow = window.innerHeight - ( offset.top - window.scrollY + $element.outerHeight() );
			var spaceAbove = offset.top - window.scrollY;
			var useRightPosition = false;
		
			// Handle horizontal position (left or right based on available space)
			if ( window.innerWidth <= 560 ) {
				popupLeft = 5;
			} else {
				popupLeft = offset.left + $element.outerWidth() / 2 - 50;
				if ( rightEdgeDistance < 300 ) {
					useRightPosition = true;
				}
			}
		
			// Determine vertical position
			if ( spaceBelow >= 250 ) {
				// If there's enough space, position below the element
				popupBottom = 'auto';
				popupTop = offset.top + $element.outerHeight() + 5;
			} else if ( spaceAbove >= 250 ) {
				// If there's not enough space, position above the element
				popupTop = 'auto';
				popupBottom = window.innerHeight - offset.top + window.scrollY + 10; // Adjust for scroll
			} else {
				// If no space is available (fallback), position near the bottom
				popupBottom = 10;
				popupTop = 'auto';
			}
		
			// Return positioning info
			if ( useRightPosition ) {
				return {
					top: popupTop - $( window ).scrollTop(),
					left: 'auto',
					right: 10,
					bottom: popupBottom
				};
			} else {
				return {
					top: popupTop - $( window ).scrollTop(),
					left: popupLeft - $( window ).scrollLeft(),
					right: 'auto',
					bottom: popupBottom
				};
			}
		},

		/**
		 * Helper function to clear cache for a specific group.
		 */
		clearCacheGroupCard: function ( groupId ) {
			if ( this.cacheGroupCard[groupId] ) {
				delete this.cacheGroupCard[groupId];
			}
		},

		/**
		 * Helper function to reset group popup cards.
		 */
		resetGroupCard: function () {
			var $groupCard = $( '#group-card' );

			$groupCard.attr( 'data-bp-item-id', '' ).removeClass( 'show loading' );
			$groupCard.find( '.bb-rl-card-heading' ).text( '' );
			$groupCard.find( '.bb-rl-card-footer, .skeleton-card-footer' ).removeClass( 'bb-rl-card-footer--plain' );
			$groupCard.find( '.card-meta-type, .card-meta-status, .card-meta-last-active' ).text( '' );
			$groupCard.find( '.bb-rl-card-avatar img' ).attr( 'src', '' );
			$groupCard.find( '.card-button-group' ).attr( 'href', '' );
			$groupCard.find( '.bs-group-members' ).html( '' );
			$groupCard.find( '.bb-rl-card-action-join' ).html( '' );
		},

		/**
		 * Helper function to update and populate group popup cards with data.
		 */
		updateGroupCard: function ( data ) {
			var $groupCard             = $( '#group-card' );
			var groupMembers           = data.group_members || [];
			var $groupMembersContainer = $groupCard.find( '.bs-group-members' );
			var membersLabel           = ( ( Number( data.members_count ) - 3 ) === 1 ) ? BP_Nouveau.member_label : BP_Nouveau.members_label;

			$groupCard.addClass( 'show' ).attr( 'data-bp-item-id', data.id );
			$groupCard.find( '.bb-rl-card-avatar img' ).attr( 'src', data.avatar_urls.thumb );
			$groupCard.find( '.bb-rl-card-heading' ).text( data.name );
			$groupCard.find( '.card-meta-status' ).text( data.status );
			$groupCard.find( '.card-meta-type' ).text( data.group_type_label );
			// Check if group_type_label is empty
			if ( data.group_type_label && data.group_type_label.trim() !== '' ) {
				$groupCard.find( '.card-meta-type' ).text( data.group_type_label ).removeClass( 'card-meta-type--empty' );
			} else {
				$groupCard.find( '.card-meta-type' ).text( '' ).addClass( 'card-meta-type--empty' );
			}
			$groupCard.find( '.card-meta-last-active' ).text( data.last_activity );
			$groupCard.find( '.bb-rl-card-footer .card-button-group' ).attr( 'href', data.link );

			groupMembers.forEach( function ( member ) {
				var memberHtml =
					'<span class="bs-group-member" data-bp-tooltip-pos="up-left" data-bp-tooltip="' + member.name + '">' +
						'<a href="' + member.link + '">' +
							'<img src="' + member.avatar_urls.thumb + '" alt="' + member.name + '" class="round">' +
						'</a>' +
					'</span>';
				$groupMembersContainer.append( memberHtml );
			} );

			if ( data.members_count > 3 ) {
				var moreIconHtml =
					'<span class="bs-group-member" data-bp-tooltip-pos="up-left" data-bp-tooltip="+ ' + ( Number( data.members_count ) - 3 ) + ' ' + membersLabel + '">' +
						'<a href="' + data.group_members_url + '">' +
							'<span class="bb-icon-f bb-icon-ellipsis-h"></span>' +
						'</a>' +
					'</span>';
				$groupMembersContainer.append( moreIconHtml );
			}

			if ( ! data.can_join ) {
				$groupCard.find( '.bb-rl-card-footer' ).addClass( 'bb-rl-card-footer--plain' );
			}

			var $joinGroupButton = $groupCard.find( '.bb-rl-card-action-join' );
			if ( $joinGroupButton.length && data.can_join ) {
				$joinGroupButton.html( data.join_button );
			}
		},

		/**
		 * Group popup card for avatars.
		 */
		groupPopupCard: function () {
			// Skip popup card functionality for touch devices to improve user experience.
			if ( bp.Nouveau.isTouchDevice() ) {
				return;
			}

			$( '#buddypress #group-card, #bbpress-forums #group-card, #page #group-card' ).remove();
			var groupCardTemplate = bp.template( 'group-card-popup' );
			var renderedGroupCard = groupCardTemplate();

			var bbpressForums = $('#bbpress-forums'),
				buddypress = $('#buddypress');
			if ( bbpressForums.length ) {
				bbpressForums.append( renderedGroupCard );
			} else if ( buddypress.length ) {
				buddypress.append( renderedGroupCard );
			} else {
				$( '#page.site' ).append( renderedGroupCard );
			}

			var $avatar = $( this );

			// Disable popup card for specific locations
			var blockedContainers = '.list-title.groups-title';
			if ( $avatar.closest( blockedContainers ).length ) {
				return;
			}

			var groupId = $avatar.attr( 'data-bb-hp-group' );
			if ( ! groupId ) {
				return;
			}

			var restUrl = BP_Nouveau.rest_url;
			var url = restUrl + '/groups/' + groupId + '/info';
			var $groupCard = $( '#group-card' );

			// Cancel any ongoing request if it's for a different groupId.
			if ( bp.Nouveau.currentRequestGroupId && bp.Nouveau.currentRequestGroupId !== groupId ) {
				bp.Nouveau.abortOngoingRequest();
			}

			// Always update position
			var position = bp.Nouveau.setPopupPosition( $avatar );
			$groupCard.css( {
				top: position.top + 'px',
				left: position.left + 'px',
				bottom: position.bottom + 'px',
				right: position.right + 'px'
			} );

			// Avoid duplicate AJAX requests for same groupId.
			if ( bp.Nouveau.currentRequestGroupId === groupId ) {
				$groupCard.addClass( 'show' );
				if ( !bp.Nouveau.cacheGroupCard[groupId] ) {
					$groupCard.addClass( 'loading' );
				}
				return;
			}

			// Set current request groupId.
			bp.Nouveau.currentRequestGroupId = groupId;

			// Check cache.
			if ( bp.Nouveau.cacheGroupCard[groupId] ) {
				var cachedGroupData = bp.Nouveau.cacheGroupCard[groupId];
				bp.Nouveau.updateGroupCard( cachedGroupData );

				$groupCard.removeClass( 'loading' );
				popupCardLoaded = true;
				bp.Nouveau.currentRequestGroupId = null;
				return;
			}

			// Set up a new AbortController for current request.
			var controller = new AbortController();
			currentRequest = controller;

			if ( popupCardLoaded ) {
				return;
			}

			$.ajax( {
				url       : url,
				method    : 'GET',
				headers   : {
					'X-WP-Nonce': BP_Nouveau.rest_nonce
				},
				signal    : controller.signal, // Attach the signal to the request.
				beforeSend: function () {
					bp.Nouveau.resetGroupCard();

					$groupCard.addClass( 'show loading' );
					$groupCard.find( '.skeleton-card-footer' ).addClass( 'bb-rl-card-footer--plain' );
				},
				success   : function ( data ) {
					// Check if this request was aborted.
					if ( controller.signal.aborted ) {
						return;
					}

					// Cache group data.
					bp.Nouveau.cacheGroupCard[groupId] = data;

					// Check if hovering over avatar or popup.
					if ( hoverGroupAvatar || hoverGroupCardPopup ) {
						if ( hoverAvatar || hoverCardPopup ) {
							// Get a fresh reference to the group card
							var $currentGroupCard = $( '#group-card' );
							$currentGroupCard.removeClass( 'loading' );

							bp.Nouveau.updateGroupCard( data );
							popupCardLoaded = true;
						} else {
							bp.Nouveau.hidePopupCard();
						}
					}

					bp.Nouveau.currentRequestGroupId = null;
				},
				error     : function ( xhr, status, error ) {
					console.error( 'Error fetching group info:', error );
					$groupCard.html( '<span>Failed to load data.</span>' );
					bp.Nouveau.currentRequestGroupId = null;
				}
			} );
		},

		/**
		 * Hide popup card on mouse leave.
		 */
		checkHidePopupCard: function () {
			if ( ! hoverAvatar && ! hoverCardPopup ) {
				hideCardTimeout = setTimeout( function () {
					bp.Nouveau.hidePopupCard();
				}, 100 );
			}
		},

		/**
		 * Hide popup card.
		 */
		hidePopupCard: function () {
			$( '.bb-rl-popup-card' ).removeClass( 'show' );
			bp.Nouveau.resetProfileCard();
			bp.Nouveau.resetGroupCard();
			hideCardTimeout = null;
			popupCardLoaded = false;
		},

		/**
		 * Toggle Profile Search form visibility
		 *
		 * @param  {Object} event The click event
		 * @return {void}
		 */
		toggleProfileSearch: function( event ) {
			event.preventDefault();
			var $toggle = $( event.currentTarget );
			var $searchFormWrapper = $toggle.closest( '.bb-rl-advance-profile-search' );
			var $searchForm = $searchFormWrapper.find( '#bp-profile-search-form-outer' );
			
			if ( $searchForm.length ) {
				$searchFormWrapper.toggleClass( 'active' );
			}
		},

		/**
		 * Close profile search forms
		 * 
		 * @param {Object} event The event object (optional)
		 * @return {void}
		 */
		closeProfileSearch: function( event ) {
			var $target, $searchFormWrapper = $( '.bb-rl-advance-profile-search.active' );
			
			// If event is provided, check if we should proceed with closing
			if ( event ) {
				$target = $( event.target );

				// Close if clicking on the cancel button
				if ( $target.hasClass( 'bb-rl-profile-search-cancel' ) ||
				     $target.closest( '.bb-rl-profile-search-cancel' ).length ) {
					// Close open profile search form
					$searchFormWrapper.removeClass( 'active' );
					return;
				}

				// If this is a click event inside the form or the toggle button, don't close
				if ( event.type === 'click' && (
					$target.closest( '#bp-profile-search-form-outer' ).length ||
					$target.hasClass( 'bb-rl-advance-profile-search-toggle' ) ||
					$target.closest( '.bb-rl-advance-profile-search-toggle' ).length
				) ) {
					// Don't close if clicked on the form or toggle button
					return;
				}
			}

			// Close open profile search form.
			$searchFormWrapper.removeClass( 'active' );
		},

		wrapNavigation: function ( selector, reduceWidth, recalculateWidth ) {
			if( 'undefined' === typeof recalculateWidth ) {
				recalculateWidth = false;
			}

			$( selector ).each( function () {
				//alignMenu( this );
				var elem = this,
					$elem = $( this );
	
				window.addEventListener( 'resize', run_alignMenu );
				window.addEventListener( 'load', run_alignMenu );

				if ( recalculateWidth ) {
					run_alignMenu();
				}
	
				function run_alignMenu() {
					$elem.find( 'li.bb_more_dropdown__title' ).remove();
	
					$elem.append( $( $( $elem.children( 'li.hideshow' ) ).children( 'ul' ) ).html() );
					$elem.children( 'li.hideshow' ).remove();
					alignMenu( elem );
				}
	
				function alignMenu( obj ) {
					var self = $( obj ),
						w = 0,
						i = -1,
						menuhtml = '',
						mw = self.width() - reduceWidth;
	
					$.each( self.children( 'li' ).not( '.bb_more_dropdown__title' ), function () {
						i++;
						w += $( this ).outerWidth( true );
						if ( mw < w ) {
							menuhtml += $( '<div>' ).append( $( this ).clone() ).html();
							$( this ).remove();
						}
					} );
	
					self.append( '<li class="hideshow menu-item-has-children" data-no-dynamic-translation>' +
					  '<a class="more-action-button" href="#">more <i class="bb-icon-l bb-icon-angle-down"></i></a>' +
					  '<ul class="sub-menu bb_nav_more_dropdown" data-no-dynamic-translation>' + menuhtml + '</ul>' +
					  '<div class="bb_more_dropdown_overlay"></div></li>' );
	
					if ( self.find( '.hideshow .bb_nav_more_dropdown .bb_more_dropdown__title' ).length < 1 && $( window ).width() < 981 ) {
						$( self ).find( '.hideshow .bb_nav_more_dropdown' ).append( '<li class="bb_more_dropdown__title">' +
						  '<span class="bb_more_dropdown__title__text">' + BP_Nouveau.more_menu_items + '</span>' +
						  '<span class="bb_more_dropdown__close_button" role="button">' +
						  '<i class="bb-icon-l bb-icon-times"></i></span></li>' );
					}
	
					if ( self.find( 'li.hideshow' ).find( 'li' ).not( '.bb_more_dropdown__title' ).length > 0 ) {
						self.find( 'li.hideshow' ).show();
					} else {
						self.find( 'li.hideshow' ).hide();
					}
				}
	
				//Vertical nav condition
				function checkVerticalMenu() {
	
					if( $( window ).width() > 738 && $elem.parent().hasClass( 'vertical' ) ) {
	
						if( $elem.find( 'li.hideshow' ).length ) {
	
							var verticalmenuhtml = '';
	
							$.each( $elem.find( 'li.hideshow ul' ).children(), function () {
								verticalmenuhtml +=  $( this ).wrap('<p/>').parent().html();
								$( this ).parent().remove();
							} );
	
							$elem.append( verticalmenuhtml );
							$elem.append( $( $( $elem.children( 'li.hideshow' ) ).children( 'ul' ) ).html() );
							$elem.children( 'li.hideshow' ).remove();
	
						} else {
							return;
						}
	
					}
	
				}
	
				window.addEventListener( 'resize', checkVerticalMenu );
				window.addEventListener( 'load', checkVerticalMenu );
	
			} );
		},

		/**
		 * Load more data.
		 *
		 * @param {Object} event The event object
		 * @return {void}
		 */
		loadMoreData: function( event ) {
			event.preventDefault();
			var $this   = $( event.currentTarget );
			var method  = $this.data( 'method' );
			var $target = $this.closest( '.bb-rl-view-more' );
			var page    = $this.attr( 'href' ).split( 'page=' )[1];
			var object  = $target.closest( '[data-bp-list]' ).data( 'bp-list' );

			bp.Nouveau.objectRequest(
				{
					object : object,
					page   : page,
					method : method,
					target : '#buddypress [data-bp-list] ul.bb-rl-list'
				}
			).done(
				function ( response ) {
					if ( true === response.success ) {
						$target.remove();
						// replace fake image with original image by faking scroll event to call bp.Nouveau.lazyLoad.
						jQuery( window ).scroll();
					}
				}
			);
		},

		/**
		 * Update sub nav count.
		 * @param {string} action - The action being performed.
		 */
		updateSubNavCount: function ( action ) {

			var countConfig = {
				invites  : {
					selector        : '.bb-rl-profile-subnav #bb-rl-invites-personal-li',
					decreaseActions : ['accept_invite', 'reject_invite'],
					contentSelector : '.groups-directory-content.bb-rl-groups',
					noneMessage     : bpNouveau.groups.member_invites_none
				},
				myGroups : {
					selector        : '.bb-rl-profile-subnav #bb-rl-groups-my-groups-personal-li',
					increaseActions : ['accept_invite'],
					contentSelector : null,
					noneMessage     : null
				},
				requests : {
					selector        : '.bb-rl-profile-subnav #bb-rl-requests-personal-li',
					decreaseActions : ['accept_friendship', 'reject_friendship'],
					contentSelector : '.bb-rl-members-directory-content.bb-rl-members',
					noneMessage     : bpNouveau.friends.member_requests_none
				},
				friends  : {
					selector        : '.bb-rl-profile-subnav #bb-rl-friends-personal-li',
					increaseActions : ['accept_friendship'],
					contentSelector : '.friends.bb-rl-members',
					noneMessage     : bpNouveau.friends.members_loop_none
				}
			};

			// Process each count type.
			$.each( countConfig, function ( type, config ) {
				var $element = $( config.selector );
				if ( ! $element.length ) {
					return;
				}

				var $withCount    = $element.find( 'a span' );
				var $withoutCount = $element.find( 'a' );
				var $content      = $( config.contentSelector );

				var currentCount = Number( $withCount.html() ) || 0;
				var shouldUpdate = false;

				// Check if action affects this count type.
				if ( config.decreaseActions && -1 !== $.inArray( action, config.decreaseActions ) ) {
					currentCount -= 1;
					shouldUpdate = true;
				} else if ( config.increaseActions && -1 !== $.inArray( action, config.increaseActions ) ) {
					currentCount += 1;
					shouldUpdate = true;
				}

				// Only update if action affects this count.
				if ( shouldUpdate ) {
					if ( currentCount > 0 ) {
						if ( $withCount.length ) {
							$withCount.html( currentCount );
						} else {
							$withoutCount.append( '<span class="count bb-rl-heading-count">' + currentCount + '</span>' );
						}
					} else {
						$withCount.hide();
						$content.html( bp.Nouveau.createFeedbackHtml( config.noneMessage ) );
					}
				}
			} );
		},

		/**
		 * Create feedback HTML from feedback data.
		 *
		 * @param {Object|string} feedbackData - Feedback data object or string message.
		 * @return {string} HTML string for feedback.
		 */
		createFeedbackHtml: function ( feedbackData ) {
			var message, type;

			// Handle both object and string inputs.
			if ( 'object' === typeof feedbackData && null !== feedbackData ) {
				message = feedbackData.message || '';
				type    = feedbackData.type || 'info';
			} else {
				message = feedbackData || '';
				type    = 'info';
			}

			return '<aside class="bp-feedback bp-messages ' + type + '"><span class="bp-icon" aria-hidden="true"></span><p>' + message + '</p></aside>';
		},
	};

	// Launch BP Nouveau.
	bp.Nouveau.start();

} )( bp, jQuery );

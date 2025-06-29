/* global bp, BP_Nouveau */
/* @version 3.0.0 */
window.bp = window.bp || {};

( function( exports, $ ) {

	// Bail if not set
	if ( typeof BP_Nouveau === 'undefined' ) {
		return;
	}

	bp.Nouveau = bp.Nouveau || {};

	/**
	 * [Activity description]
	 * @type {Object}
	 */
	bp.Nouveau.Notifications = {

		/**
		 * [start description]
		 * @return {[type]} [description]
		 */
		start: function() {
			this.setupGlobals();

			// Listen to events ("Add hooks!")
			this.addListeners();

			this.prepareDocument();
		},

		/**
		 * [setupGlobals description]
		 * @return {[type]} [description]
		 */
		setupGlobals: function() {
			// Always reset sort to Newest notifications
			bp.Nouveau.setStorage( 'bp-notifications', 'extras', 'DESC' );
		},

		/**
		 * [addListeners description]
		 */
		addListeners: function() {
			// Change the Order actions visibility once the ajax request is done.
			$( 'body.notifications' ).on( 'bp_ajax_request', this.prepareDocument );

			// Trigger Notifications order request.
			$( 'body.notifications' ).on( 'click', '.bb-sort-by-date', bp.Nouveau, this.sortNotifications );

			// Reset The filter before unload
			$( window ).on( 'unload', this.resetFilter );

			// More Options Dropdown
			$( 'body.notifications' ).on( 'click', '.bb_rl_more_dropdown__action', this.toggleMoreOptionsDropdown );

			$( document ).on( 'click', function( event ) {
				if ( ! $( event.target ).closest( '.bb-rl-more_dropdown-wrap' ).length ) {
					$( '.bb-rl-more_dropdown-wrap' ).removeClass( 'active' );
				}
			} );
		},

		/**
		 * [prepareDocument description]
		 * @return {[type]} [description]
		 */
		prepareDocument: function() {
			var store = bp.Nouveau.getStorage( 'bp-notifications' );

			if ( 'ASC' === store.extras ) {
				$( '[data-bp-notifications-order="DESC"]' ).show();
				$( '[data-bp-notifications-order="ASC"]' ).hide();
			} else {
				$( '[data-bp-notifications-order="ASC"]' ).show();
				$( '[data-bp-notifications-order="DESC"]' ).hide();
			}

			// Make sure a 'Bulk Action' is selected before submitting the form
			$( '#notification-bulk-manage' ).prop( 'disabled', 'disabled' );
		},

		/**
		 * [sortNotifications description]
		 * @param  {[type]} event [description]
		 * @return {[type]}       [description]
		 */
		sortNotifications: function( event ) {
			var store = event.data.getStorage( 'bp-notifications' ),
				scope = store.scope || null, filter = store.filter || null,
				sort = store.extra || null, search_terms = '';

			event.preventDefault();

			sort = $( event.currentTarget ).find( 'a:visible' ).data( 'bp-notifications-order' );
			bp.Nouveau.setStorage( 'bp-notifications', 'extras', sort );

			if ( $( '#buddypress [data-bp-search="notifications"] input[type=search]' ).length ) {
				search_terms = $( '#buddypress [data-bp-search="notifications"] input[type=search]' ).val();
			}

			bp.Nouveau.objectRequest( {
				object              : 'notifications',
				scope               : scope,
				filter              : filter,
				search_terms        : search_terms,
				extras              : sort,
				page                : 1
			} );
		},

		/**
		 * [resetFilter description]
		 * @return {[type]} [description]
		 */
		resetFilter: function() {
			bp.Nouveau.setStorage( 'bp-notifications', 'filter', 0 );
		},

		/**
		 * [toggleMoreOptionsDropdown description]
		 * @return {[type]} [description]
		 */
		toggleMoreOptionsDropdown: function() {
			if( $( this ).closest( '.bb-rl-more_dropdown-wrap' ).hasClass( 'active' ) ) {
				$( '.bb-rl-more_dropdown-wrap' ).removeClass( 'active' );
			} else {
				$( '.bb-rl-more_dropdown-wrap' ).removeClass( 'active' );
				$( this ).closest( '.bb-rl-more_dropdown-wrap' ).addClass( 'active' );
			}
		}
	};

	// Launch BP Nouveau Notifications
	bp.Nouveau.Notifications.start();

} )( bp, jQuery );

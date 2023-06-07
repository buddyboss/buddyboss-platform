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
			$( '#buddypress [data-bp-list="notifications"]' ).on( 'bp_ajax_request', this.prepareDocument );

			// Trigger Notifications order request.
			$( '#buddypress [data-bp-list="notifications"]' ).on( 'click', '[data-bp-notifications-order]', bp.Nouveau, this.sortNotifications );

			// Enable the Apply Button once the bulk action is selected
			$( '#buddypress [data-bp-list="notifications"]' ).on( 'change', '#notification-select', this.enableBulkSubmit );

			// Select all displayed notifications
			$( '#buddypress [data-bp-list="notifications"]' ).on( 'click', '#select-all-notifications', this.selectAll );

			// Check all checkbox checked or not
			$( '#buddypress [data-bp-list="notifications"]' ).on( 'click', '.notification-check', this.checkSelectAllOrNot );

			// Reset The filter before unload
			$( window ).on( 'unload', this.resetFilter );
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

			sort = $( event.currentTarget ).data( 'bp-notifications-order' );
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
		 * [enableBulkSubmit description]
		 * @param  {[type]} event [description]
		 * @return {[type]}       [description]
		 */
		enableBulkSubmit: function( event ) {
			$( '#notification-bulk-manage' ).prop( 'disabled', $( event.currentTarget ).val().length <= 0 );
			
			if ( true === $( '#buddypress [data-bp-list="notifications"]' ).find( '#select-all-notifications' ).prop( 'checked' ) ) {
				$.each( $( '.notification-check' ), function ( cb, checkbox ) {
					if ( 'unread' === $( event.currentTarget ).val() || '' === $( event.currentTarget ).val() ) {
						if ( '' === $( checkbox ).attr( 'data-readonly' ) ) {
							$( checkbox ).prop( 'checked', true );
						} else {
							$( checkbox ).prop( 'checked', false );
						}
					} else {
						$( checkbox ).prop( 'checked', true );
					}
				} );
			}
		},

		/**
		 * [selectAll description]
		 * @param  {[type]} event [description]
		 * @return {[type]}       [description]
		 */
		selectAll: function( event ) {
			var selectedNotificationValue = $( '#notification-select' ).val();
			$.each( $( '.notification-check' ), function ( cb, checkbox ) {
				if ( 'unread' === selectedNotificationValue || '' === selectedNotificationValue ) {
					if ( '' === $( checkbox ).attr( 'data-readonly' ) ) {
						$( checkbox ).prop( 'checked', $( event.currentTarget ).prop( 'checked' ) );
					}
				} else {
					$( checkbox ).prop( 'checked', $( event.currentTarget ).prop( 'checked' ) );
				}
			} );
		},

		/**
		 * [checkSelectAllOrNot description]
		 * @param  {[type]} event [description]
		 * @return {[type]}       [description]
		 */
		checkSelectAllOrNot: function( e ) {
			var unChecked 	= 0;
			if ( '1' === $( this ).attr( 'data-readonly' ) ) {
				$( this ).blur();
				e.preventDefault();
			}
			$.each( $( '.notification-check' ), function ( cb, checkbox ) {
				if ( $( checkbox ).prop( 'checked' ) == false ) {
					unChecked = parseInt( unChecked ) + 1;
				}
			} );

			if ( unChecked == 0 ) {
				$( '#buddypress [data-bp-list="notifications"]' ).find( '#select-all-notifications' ).prop( 'checked',true );
			}else{
				$( '#buddypress [data-bp-list="notifications"]' ).find( '#select-all-notifications' ).prop( 'checked',false );
			}
		},

		/**
		 * [resetFilter description]
		 * @return {[type]} [description]
		 */
		resetFilter: function() {
			bp.Nouveau.setStorage( 'bp-notifications', 'filter', 0 );
		}
	};

	// Launch BP Nouveau Notifications
	bp.Nouveau.Notifications.start();

} )( bp, jQuery );

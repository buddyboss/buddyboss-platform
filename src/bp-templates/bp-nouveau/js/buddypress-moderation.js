/* jshint browser: true */
/* global bp, BP_Nouveau */
/* @version 1.0.0 */
window.bp = window.bp || {};

( function ( exports, $ ) {

	// Bail if not set.
	if ( typeof BP_Nouveau === 'undefined' ) {
		return;
	}

	bp.Nouveau = bp.Nouveau || {};

	/**
	 * [Media description]
	 *
	 * @type {Object}
	 */
	bp.Nouveau.Moderation = {

		/**
		 * [start description]
		 *
		 * @return {[type]} [description]
		 */
		start: function () {
			this.setupGlobals();

			// Listen to events ("Add hooks!").
			this.addListeners();

			this.unblockUser();
		},

		/**
		 * [setupGlobals description]
		 *
		 * @return {[type]} [description]
		 */
		setupGlobals: function () {
			// Init current page.
			this.current_page = 1;
		},

		/**
		 * [addListeners description]
		 */
		addListeners: function () {

			$( document ).on( 'click', '.bp-nouveau [data-bp-list="moderation"] .pager .md-more-container.load-more', this.injectModerations.bind( this ) );
			$('#buddypress [data-bp-list="members"]').on('bp_ajax_request', this.bp_ajax_connection_request);
		},

		injectModerations: function ( event ) {

			var store = bp.Nouveau.getStorage( 'bp-moderation' ),
				scope = store.scope || null, filter = store.filter || null, currentTarget = $( event.currentTarget );

			if ( currentTarget.hasClass( 'load-more' ) ) {
				var next_page = ( Number( this.current_page ) * 1 ) + 1, self = this, search_terms = '';

				// Stop event propagation.
				event.preventDefault();

				currentTarget.find( 'a' ).first().addClass( 'loading' );

				if ( $( '#buddypress .dir-search input[type=search]' ).length ) {
					search_terms = $( '#buddypress .dir-search input[type=search]' ).val();
				}

				var queryData;
				queryData = {
					object: 'moderation',
					scope: scope,
					filter: filter,
					search_terms: search_terms,
					page: next_page,
					method: 'append',
					target: '#buddypress [data-bp-list] table#moderation-list tbody',
				};

				bp.Nouveau.objectRequest(
					queryData
				).done(
					function ( response ) {
						if ( true === response.success ) {
							currentTarget.parent( '.pager' ).remove();

							// Update the current page.
							self.current_page = next_page;

							jQuery( window ).scroll();
						}
					}
				);
			}
		},

		bp_ajax_connection_request: function (event, data) {
			var selector = $( '#friends-personal-li' ).find( '.count' );
			var oldValue = selector.html();
			if ( oldValue !== data.response.count ) {
				selector.html( data.response.count );
			}
		},

		unblockUser: function () {
			$( document ).on( 'click', '.moderation-item-actions .bp-unblock-user', function ( event ) {

				if ( !confirm( BP_Nouveau.moderation.unblock_user_msg ) ) {
					return false;
				}

				$(event.currentTarget).append(' <i class="bb-icon-l bb-icon-spinner animate-spin"></i>');

				var curObj = $( this );
				var id = curObj.attr( 'data-id' );
				var type = curObj.attr( 'data-type' );
				var nonce = curObj.attr( 'data-nonce' );
				var data = {
					action: 'bp_moderation_unblock_user',
					id: id,
					type: type,
					nonce: nonce,
				};

				if (!curObj.parent().closest('.moderation').find('.bp-feedback').hasClass('is_hidden')) {
					curObj.parent().closest('.moderation').find('.bp-feedback').addClass('is_hidden');
				}

				$.post( ajaxurl, data, function ( response ) {
					if (true===response.success) {
						curObj.parent().closest('.moderation-item-wrp').fadeOut('normal', function () {
							curObj.parent().closest('.moderation-item-wrp').remove();
							var TableRow = $('#moderation-list >tbody >tr').length;

							if ( 0 >= TableRow ) {
								$ ( '#moderation-list' ).remove ();
								$ ( '.bp-feedback' ).removeClass ( 'error' ).addClass ( 'info' ).removeClass ( 'is_hidden' );
								$ ( '.bp-feedback p' ).html ( BP_Nouveau.moderation.no_user_msg );
							}
						});
					} else {
						$(event.currentTarget).find('.bb-icon-spinner').remove();
						var msg = '';
						if (response.data.message.errors.bp_moderation_missing_data) {
							msg = response.data.message.errors.bp_moderation_missing_data;
						} else if (response.data.message.errors.bp_moderation_not_exit) {
							msg = response.data.message.errors.bp_moderation_not_exit;
						} else if (response.data.message.errors.bp_rest_invalid_id) {
							msg = response.data.message.errors.bp_rest_invalid_id;
						} else if (response.data.message.errors.bp_moderation_block_error) {
							msg = response.data.message.errors.bp_moderation_block_error;
						}
						curObj.parent().closest('.moderation').find('.bp-feedback').removeClass('is_hidden');
						curObj.parent().closest('.moderation').find('.bp-feedback p').html(msg);
					}
				} );
			} );
		},
	};

	// Launch BP Nouveau Moderation.
	bp.Nouveau.Moderation.start();

} )( bp, jQuery );

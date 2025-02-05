/* jshint browser: true */
/* global bp, bbReadyLaunchGroupsVars, BP_Nouveau */
/* @version 1.0.0 */
window.bp = window.bp || {};

( function ( exports, $ ) {

	/**
	 * [ReadLaunch description]
	 *
	 * @type {Object}
	 */
	bp.Readylaunch.Groups = {
		/**
		 * [start description]
		 *
		 * @return {[type]} [description]
		 */
		start: function () {
			this.addListeners();
		},

		/**
		 * [addListeners description]
		 */
		addListeners: function () {
			var self = this;
			var $document = $( document );
			$document.on( 'click', '.bb-rl-manage-group-container .bp-navs a', self.loadManageSettings );

			$document.on( 'click', '.bb-rl-group-extra-info .bb_more_options .generic-button a.item-button', function () {
				//event.preventDefault();
				var modalId = 'model--' + $( this ).attr( 'id' );
				bp.Readylaunch.Groups.openModal( modalId );
			} );

			$document.on( 'click', '.bb-rl-modal-close-button', function () {
				//event.preventDefault();
				$( this ).closest( '.bb-rl-action-popup' ).removeClass( 'open' );
			} );
		},

		openModal: function ( modalId ) {
			var $modal = $( '#' + modalId );
		
			if ( !$modal.length ) {
				return;
			}

			$modal.addClass( 'open' );

			if ( $modal.hasClass( 'group-manage' ) ) {
				bp.Readylaunch.Groups.initManageGroup( $modal );
			}
		},

		initManageGroup: function ( $modal ) {
			// Handle form submission
			$modal.find( 'form' ).on( 'submit', function ( event ) {
				event.preventDefault();				
			} );
		},

		loadManageSettings: function ( e ) {
			e.preventDefault();

			var current = $( this );

			var $submitButton = current.closest( '.bb-rl-modal-footer' ).find( '.submit-form' );

			var $url = $( this ).attr( 'href' );

			if ( $url ) {

				$submitButton.prop( 'disabled', true );

				$.ajax(
					{
						method: 'GET',
						url: BP_Nouveau.ajaxurl,
						data: {
							action: 'group_manage_content',
							url: $url,
							group_id: bbReadyLaunchGroupsVars.group_id,
						},
						success: function (response) {
							if ( response ) {
								var content = current.closest( '.bb-rl-manage-group-container' ).find( '#group-settings-form' );
								content.replaceWith( response );
							}
						},
						error: function () {
						},
						complete: function () {
							$submitButton.prop( 'disabled', false );
						}
					}
				);
			}

		},
	};

	// Launch members.
	bp.Readylaunch.Groups.start();

} )( bp, jQuery );

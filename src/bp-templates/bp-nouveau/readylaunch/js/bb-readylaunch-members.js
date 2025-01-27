/* jshint browser: true */
/* global bp, bbReadyLaunchFront */
/* @version 1.0.0 */
window.bp = window.bp || {};

( function ( exports, $ ) {

	/**
	 * [ReadLaunch description]
	 *
	 * @type {Object}
	 */
	bp.Readylaunch.Members = {
		/**
		 * [start description]
		 *
		 * @return {[type]} [description]
		 */
		start: function () {
			this.inviteMember();
			this.sentInvitesFormValidate();
		},

		/**
		 * [addListeners description]
		 */
		addListeners: function () {
		},

		inviteMember: function () {
			$( document ).on( 'click', '#bb-rl-invite-button',function ( e ) {
				e.preventDefault();

				var $modal= $( '#bb-rl-invite-modal' );
				
				if ( $modal.length ) {
					$modal.show();
				}
			} );

			$( document ).on( 'click', '.bb-rl-modal-close-button', function ( e ) {
				e.preventDefault();
				
				var $modal = $( this ).closest( '#bb-rl-invite-modal' );
				
				if ( $modal.length ) {
					$modal.hide();
				}
			} );
		},

		sentInvitesFormValidate: function () {

			if ( $( '#bb-rl-invite-form' ).length ) {

				$( '#bb-rl-invite-form' ).submit(
					function ( e ) {

						e.preventDefault();

						var isValid = true;
						var $form = $( this );

						// Reset error classes
						$form.removeClass( 'bb-rl-form-error' );
						$form.find( '.bb-rl-input-field' ).removeClass( 'bb-rl-input-field--error' );

						// Validate Name
						var $nameField = $( '#bb-rl-invite-name' );
						if ( $nameField.val().trim() === '' ) {
							$nameField.addClass( 'bb-rl-input-field--error' );
							isValid = false;
						}

						// Validate Email
						const $emailField = $( '#bb-rl-invite-email' );
						const emailValue = $emailField.val().trim();
						const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

						if ( !emailRegex.test( emailValue ) ) {
							$emailField.addClass( 'bb-rl-input-field--error' );
							isValid = false;
						}

						if ( ! isValid) {
							$form.addClass( 'bb-rl-form-error' );
						} else {
							$form.off( 'submit' ).submit();
						}

					}
				);
			}
		},
	};

	// Launch members.
	bp.Readylaunch.Members.start();

} )( bp, jQuery );
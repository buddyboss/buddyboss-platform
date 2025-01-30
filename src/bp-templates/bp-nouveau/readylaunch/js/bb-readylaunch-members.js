/* jshint browser: true */
/* global bp */
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
			this.inviteMemberPopup();
		},

		/**
		 * [addListeners description]
		 */
		addListeners: function () {
		},

		inviteMemberPopup: function () {
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

			$( document ).on( 'submit', '#bb-rl-invite-form', this.submitInviteMemberPopupForm );
		},

		submitInviteMemberPopupForm: function ( e ) {

			e.preventDefault();

			var isValid = true;
			var $form = $( this );

			// Reset error classes
			$form.removeClass( 'bb-rl-form-error' );
			$form.find( '.bb-rl-input-field' ).removeClass( 'bb-rl-input-field--error' );
			$form.find( '.bb-rl-notice' ).remove();

			// Validate Name
			var $nameField = $( '#bb-rl-invite-name' );
			var $nameWrapper = $nameField.closest( '.bb-rl-form-field-wrapper' );
			if ( $nameField.val().trim() === '' ) {
				$nameField.addClass( 'bb-rl-input-field--error' );
				isValid = false;

				$nameField.addClass( 'bb-rl-input-field--error' );
				$nameWrapper.append( '<div class="bb-rl-notice bb-rl-notice--alt bb-rl-notice--error"><i class="bb-icons-rl-warning-circle"></i>Name is required.</div>' );
				isValid = false;
			}

			// Validate Email
			var $emailField = $( '#bb-rl-invite-email' );
			var $emailWrapper = $emailField.closest( '.bb-rl-form-field-wrapper' );
			var emailValue = $emailField.val().trim();
			var emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

			if ( ! emailRegex.test( emailValue ) ) {
				$emailField.addClass( 'bb-rl-input-field--error' );
				$emailWrapper.append( '<div class="bb-rl-notice bb-rl-notice--alt bb-rl-notice--error"><i class="bb-icons-rl-warning-circle"></i>Please enter a valid email address.</div>' );
				isValid = false;
			}

			if ( ! isValid) {
				$form.addClass( 'bb-rl-form-error' );
			} else {
				// Ajax submit the form.
				var formData = $form.serialize();
				$.ajax(
					{
						method: 'POST',
						url: BP_Nouveau.ajaxurl,
						data: formData,
						success: function (response) {
							if ( response.success ) {
								alert(response.data.message); // Success message
							} else {
								alert( response.data.message ); // Error message
							}
						},
						error: function () {
							console.log('There was an error submitting the form. Please try again.');
						}
					}
				);
			}
		},
	};

	// Launch members.
	bp.Readylaunch.Members.start();

} )( bp, jQuery );
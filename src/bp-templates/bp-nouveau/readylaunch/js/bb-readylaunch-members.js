/* jshint browser: true */
/* global bp, bbReadyLaunchMembersVars */
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
				var $modalMask = $modal.find( '.bb-rl-modal-mask' );

				if ( $modal.length ) {
					$modalMask.show();
					$modal.show();
				}
			} );

			$( document ).on( 'click', '.bb-rl-modal-close-button', function ( e ) {
				e.preventDefault();
				bp.Readylaunch.Members.resetInviteMemberPopupForm.call( this, e );

				var $modal = $( this ).closest( '#bb-rl-invite-modal' );

				if ( $modal.length ) {
					$modal.hide();
				}
			} );

			$( document ).on( 'submit', '#bb-rl-invite-form', this.submitInviteMemberPopupForm );
		},

		showToastMessage: function ( message, type, hideModal ) {
			type = typeof type !== 'undefined' ? type : 'info';
    		hideModal = typeof hideModal !== 'undefined' ? hideModal : false;

			var $modal = $( '#bb-rl-invite-modal' );
			var $modalMask = $modal.find( '.bb-rl-modal-mask' );

			// Remove any existing toast messages
			$( '.bb-rl-toast-message' ).remove();

			var toastClass = 'bb-rl-toast-message';
			var toastIcon = '<span class="bb-rl-spinner"></span>';
			if ( type === 'error' ) {
				toastClass += ' bb-rl-toast-message--error';
				toastIcon += '<i class="bb-icons-rl-warning-circle"></i>';
			} else if ( type === 'success' ) {
				toastClass += ' bb-rl-toast-message--success';
				toastIcon += '<i class="bb-icons-rl-check-circle"></i>';
			}

			var toastHTML = '<div class="' + toastClass + '">' + toastIcon + message + '</div>';
			$modal.append( toastHTML );

			if ( type !== 'error' ) {
				if ( hideModal ) {
					setTimeout( function () {
						$modalMask.fadeOut( 200 );
					}, 500 );
				}
				setTimeout( function () {
					$( '.bb-rl-toast-message' ).fadeOut( 300, function () {
						$( this ).remove();
					} );
				}, 4000 );
				if ( hideModal ) {
					setTimeout( function () {
						$modal.fadeOut( 500 );
					}, 4500 );
				}
			}
		},

		appendMessage: function ( $wrapper, message ) {
			var errorHTML = '<div class="bb-rl-notice bb-rl-notice--alt bb-rl-notice--error">';
			errorHTML += '<i class="bb-icons-rl-warning-circle"></i>' + message + '</div>';
			$wrapper.append( errorHTML );
		},

		submitInviteMemberPopupForm: function ( e ) {
			e.preventDefault();

			var isValid = true;
			var $form = $( this );
			var $submitButton = $form.closest( '#bb-rl-invite-modal' ).find( '#bb-rl-submit-invite' );

			// Disable submit button to prevent multiple submissions
			$submitButton.prop( 'disabled', true );

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
				bp.Readylaunch.Members.appendMessage( $nameWrapper, bbReadyLaunchMembersVars.invite_invalid_name_message );
				isValid = false;
			}

			// Validate Email
			var $emailField = $( '#bb-rl-invite-email' );
			var $emailWrapper = $emailField.closest( '.bb-rl-form-field-wrapper' );
			var emailValue = $emailField.val().trim();
			var emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

			if ( ! emailRegex.test( emailValue ) ) {
				$emailField.addClass( 'bb-rl-input-field--error' );
				bp.Readylaunch.Members.appendMessage( $emailWrapper, bbReadyLaunchMembersVars.invite_valid_email );
				isValid = false;
			}

			if ( !isValid ) {
				$form.addClass( 'bb-rl-form-error' );
				$submitButton.prop( 'disabled', false );
				return;
			}

			bp.Readylaunch.Members.showToastMessage( bbReadyLaunchMembersVars.invite_sending_invite, 'info' );

			var formData = $form.serialize();
			$.ajax(
				{
					method: 'POST',
					url: BP_Nouveau.ajaxurl,
					data: formData,
					success: function (response) {
						if ( response.success ) {
							bp.Readylaunch.Members.showToastMessage( response.data.message, 'success', true );
							$form[ 0 ].reset();
						} else {
							bp.Readylaunch.Members.showToastMessage( response.data.message, 'error', false );
						}
					},
					error: function () {
						bp.Readylaunch.Members.showToastMessage( bbReadyLaunchMembersVars.invite_error_notice, 'error', false );
					},
					complete: function () {
						$submitButton.prop( 'disabled', false );
					}
				}
			);
		},

		resetInviteMemberPopupForm: function () {
			var $modal = $( this ).closest( '#bb-rl-invite-modal' );
			var $form = $modal.find( '#bb-rl-invite-form' );

			// Reset form
			$form.removeClass( 'bb-rl-form-error' );
			$form.find( '.bb-rl-input-field' ).removeClass( 'bb-rl-input-field--error' );
			$form.find( '.bb-rl-notice' ).remove();
			$form[ 0 ].reset();
		},
	};

	// Launch members.
	bp.Readylaunch.Members.start();

} )( bp, jQuery );

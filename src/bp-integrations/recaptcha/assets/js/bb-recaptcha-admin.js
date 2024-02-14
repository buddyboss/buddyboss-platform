/* global bbRecaptcha */
( function ( $ ) {
	var BB_Recaptcha = {

		init: function () {

			this.setupGlobals();

			// Listen to events ("Add hooks!").
			this.addListeners();
		},

		setupGlobals: function () {
			this.selected_version = '';
			this.site_key = '';
			this.secret_key = '';
			this.captcha_response = '';
		},

		addListeners: function () {
			$( '.buddyboss_page_bp-integrations .section-bb_recaptcha_versions' ).on( 'click', '.recaptcha-verification', this.recaptchaVerifications.bind( this ) );
			$( '.buddyboss_page_bp-integrations .section-bb_recaptcha_versions' ).on( 'click', '#recaptcha_submit', this.recaptchaSubmit.bind( this ) );
			$( '.buddyboss_page_bp-integrations .section-bb_recaptcha_versions' ).on( 'click', '#recaptcha_verified', this.recaptchaVerified.bind( this ) );
			$( '.buddyboss_page_bp-integrations .section-bb_recaptcha_versions' ).on( 'click', '#recaptcha_cancel', this.recaptchaVerificationPopupClose.bind( this ) );
			$( '.buddyboss_page_bp-integrations .section-bb_recaptcha_versions' ).on( 'change', 'input[name="bb_recaptcha[recaptcha_version]"]', this.recaptchaVersion.bind( this ) );
			$( '.buddyboss_page_bp-integrations .section-bb_recaptcha_versions' ).on( 'change', 'input[name="bb_recaptcha[v2_option]"]', this.recaptchaType.bind( this ) );
			$( '.buddyboss_page_bp-integrations .section-bb_recaptcha_design' ).on( 'change', 'input[name="bb_recaptcha[theme]"]', this.recaptchaTheme.bind( this ) );

		},

		recaptchaVersion: function ( event ) {
			event.preventDefault();

			if ( 'recaptcha_v2' === event.currentTarget.value ) {
				$( '.recaptcha_v2' ).removeClass( 'bp-hide' );
				$( '.recaptcha_v3' ).addClass( 'bp-hide' );
			} else {
				$( '.recaptcha_v2' ).addClass( 'bp-hide' );
				$( '.recaptcha_v3' ).removeClass( 'bp-hide' );
			}
		},

		recaptchaType: function ( event ) {
			event.preventDefault();

			if ( 'v2_checkbox' === event.currentTarget.value ) {
				$( '.recaptcha_v2_checkbox' ).removeClass( 'bp-hide' );
				$( '.recaptcha_v2_invisible' ).addClass( 'bp-hide' );
			} else {
				$( '.recaptcha_v2_invisible' ).removeClass( 'bp-hide' );
				$( '.recaptcha_v2_checkbox' ).addClass( 'bp-hide' );
			}

		},

		recaptchaTheme: function ( event ) {
			event.preventDefault();

			var current_value = event.currentTarget.value;

			if ( 'dark' === current_value ) {
				$( 'input[name="bb_recaptcha[size]"] + label' ).removeClass( 'opt-size-light' ).addClass( 'opt-size-dark' );
			} else {
				$( 'input[name="bb_recaptcha[size]"] + label' ).removeClass( 'opt-size-dark' ).addClass( 'opt-size-light' );
			}
		},

		recaptchaVerifications: function ( event ) {
			event.preventDefault();

			if ( $( document ).find( '#bp-hello-backdrop' ).length ) {
			} else {
				var finder = $( document ).find( '.bp-hello-recaptcha' );
				$( '<div id="bp-hello-backdrop" style="display: none;"></div>' ).insertBefore( finder );
			}
			var backdrop = document.getElementById( 'bp-hello-backdrop' ),
				modal    = document.getElementById( 'bp-hello-container' );

			if ( null === backdrop ) {
				return;
			}
			document.body.classList.add( 'bp-disable-scroll' );

			// Show modal and overlay.
			backdrop.style.display = '';
			modal.style.display    = '';

			// Focus the "X" so bp_hello_handle_keyboard_events() works.
			var focus_target = modal.querySelectorAll( 'a[href], button' );
			focus_target     = Array.prototype.slice.call( focus_target );
			focus_target[0].focus();

			self.selected_version = $( 'input[name="bb_recaptcha[recaptcha_version]"]:checked' ).val();
			self.site_key = $( '#bb-recaptcha-site-key' ).val();
			self.secret_key = $( '#bb-recaptcha-secret-key' ).val();
			window.bb_recaptcha_script = document.createElement('script');

			if ( 'recaptcha_v3' === self.selected_version ) {
				window.bb_recaptcha_script.src = 'https://www.google.com/recaptcha/api.js?onload=bb_recaptcha_v3_verify&render=' + self.site_key;
			}
			if ( 'recaptcha_v2' === self.selected_version ) {
				window.bb_recaptcha_script.src = 'https://www.google.com/recaptcha/api.js?onload=wpcaptcha_captchav2_test&render=explicit';
			}

			window.bb_recaptcha_script.onerror = function () {
				console.log('error');
			};

			window.bb_recaptcha_v3_verify = function () {
				grecaptcha.execute( self.site_key, { action: 'submit' } ).then( function ( token ) {
					$( '#bp-hello-recaptcha-content' ).html();
					self.captcha_response = token;
					$( '#bp-hello-recaptcha-content' ).html( 'reCAPTCHA token is ready, click Submit to verify');
				} );
			};

			document.head.appendChild( window.bb_recaptcha_script );
		},

		recaptchaSubmit: function ( event ) {
			$.ajax(
				{
					type: 'POST',
					url: bbRecaptcha.ajax_url,
					data: {
						action: 'bb_recaptcha_verification',
						nonce: bbRecaptcha.nonce,
						selected_version: self.selected_version,
						site_key: self.site_key,
						secret_key: self.secret_key,
						captcha_response: self.captcha_response,
					},
					success: function ( response ) {
						if ( response.success && typeof response.data !== 'undefined' ) {
							$( '#bp-hello-recaptcha-content' ).html( response.data );
							$( '.bb-popup-buttons' ).html( '<a href="javascript:void(0);" id="recaptcha_verified" class="button">OK</a>' );
							document.head.removeChild( window.bb_recaptcha_script );
							window.bb_recaptcha_script = null;
							window.bb_recaptcha_v3_verify = null;
						} else {
							$( '#bp-hello-recaptcha-content' ).html( response.data );
							$( '.bb-popup-buttons' ).html( '<a href="javascript:void(0);" id="recaptcha_cancel" class="button">Cancel</a>' );
						}
					}
				}
			);
		},

		recaptchaVerified: function ( event ) {
			event.preventDefault();
			window.location.reload();
		},

		recaptchaVerificationPopupClose: function ( event ) {
			event.preventDefault();
			window.location.reload();
		}
	};

	$(
		function () {
			BB_Recaptcha.init();
		}
	);
} )( jQuery );

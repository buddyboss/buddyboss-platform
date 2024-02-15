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
			this.v2_option = '';
			this.site_key = '';
			this.secret_key = '';
			this.captcha_response = '';
		},

		addListeners: function () {
			$( '.buddyboss_page_bp-integrations .section-bb_recaptcha_versions' ).on( 'keyup', '#bb-recaptcha-site-key, #bb-recaptcha-secret-key', this.enableVerifyButton.bind( this ) );
			$( '.buddyboss_page_bp-integrations .section-bb_recaptcha_versions' ).on( 'click', '.recaptcha-verification', this.recaptchaVerifications.bind( this ) );
			$( '.buddyboss_page_bp-integrations .section-bb_recaptcha_versions' ).on( 'click', '#recaptcha_submit', this.recaptchaSubmit.bind( this ) );
			$( '.buddyboss_page_bp-integrations .section-bb_recaptcha_versions' ).on( 'click', '#recaptcha_verified', this.recaptchaVerified.bind( this ) );
			$( '.buddyboss_page_bp-integrations .section-bb_recaptcha_versions' ).on( 'click', '#recaptcha_cancel', this.recaptchaVerificationPopupClose.bind( this ) );
			$( '.buddyboss_page_bp-integrations .section-bb_recaptcha_versions' ).on( 'change', 'input[name="bb_recaptcha[recaptcha_version]"]', this.recaptchaVersion.bind( this ) );
			$( '.buddyboss_page_bp-integrations .section-bb_recaptcha_versions' ).on( 'change', 'input[name="bb_recaptcha[v2_option]"]', this.recaptchaType.bind( this ) );
			$( '.buddyboss_page_bp-integrations .section-bb_recaptcha_design' ).on( 'change', 'input[name="bb_recaptcha[theme]"]', this.recaptchaTheme.bind( this ) );

		},

		enableVerifyButton: function ( event ) {
			if (
				'' !== $( '#bb-recaptcha-site-key' ).val().trim() &&
				'' !== $( '#bb-recaptcha-secret-key' ).val().trim()
			) {
				$( '.recaptcha-verification' ).removeAttr( 'disabled' );
			} else {
				$( '.recaptcha-verification' ).attr( 'disabled', 'disabled' );
			}
		},

		recaptchaVersion: function ( event ) {
			event.preventDefault();

			var v2_option = $( 'input[name="bb_recaptcha[v2_option]"]:checked' ).val();

			if ( 'recaptcha_v3' === event.currentTarget.value || 'v2_invisible_badge' === v2_option ) {
				$( '.recaptcha_v3' ).removeClass( 'bp-hide' );
				$( '.recaptcha_v2' ).addClass( 'bp-hide' );
				$( '#bp-hello-content-recaptcha_v2' ).addClass( 'bp-hide' );
				$( '#bp-hello-content-recaptcha_v3' ).removeClass( 'bp-hide' );
			} else {
				$( '.recaptcha_v2' ).removeClass( 'bp-hide' );
				$( '.recaptcha_v3' ).addClass( 'bp-hide' );
				$( '#bp-hello-content-recaptcha_v2' ).removeClass( 'bp-hide' );
				$( '#bp-hello-content-recaptcha_v3' ).addClass( 'bp-hide' );
			}
		},

		recaptchaType: function ( event ) {
			event.preventDefault();

			$( '.recaptcha-v2-fields p.description' ).addClass( 'bp-hide' );
			$( '.' + event.currentTarget.value + '_description' ).removeClass( 'bp-hide' );

			if ( 'v2_checkbox' === event.currentTarget.value ) {
				$( '.recaptcha_v2_checkbox' ).removeClass( 'bp-hide' );
				$( '.recaptcha_v2_invisible' ).addClass( 'bp-hide' );
				$( '#bp-hello-content-recaptcha_v2' ).removeClass( 'bp-hide' );
				$( '#bp-hello-content-recaptcha_v3' ).addClass( 'bp-hide' );
			} else {
				$( '.recaptcha_v2_invisible' ).removeClass( 'bp-hide' );
				$( '.recaptcha_v2_checkbox' ).addClass( 'bp-hide' );
				$( '#bp-hello-content-recaptcha_v2' ).addClass( 'bp-hide' );
				$( '#bp-hello-content-recaptcha_v3' ).removeClass( 'bp-hide' );
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

			self.selected_version = $( 'input[name="bb_recaptcha[recaptcha_version]"]:checked' ).val();
			self.site_key = $( '#bb-recaptcha-site-key' ).val();
			self.secret_key = $( '#bb-recaptcha-secret-key' ).val();
			window.bb_recaptcha_script = document.createElement('script');

			if ( $( 'input[name="bb_recaptcha[v2_option]"]' ).length ) {
				self.v2_option = $( 'input[name="bb_recaptcha[v2_option]"]:checked' ).val();
			}

			var selector = this.fetchSelector();

			if ( 'recaptcha_v3' === self.selected_version ) {
				window.bb_recaptcha_script.src = 'https://www.google.com/recaptcha/api.js?onload=bb_recaptcha_v3_verify&render=' + self.site_key;
			}
			if ( 'recaptcha_v2' === self.selected_version && self.v2_option ) {
				if ( 'v2_checkbox' === self.v2_option ) {
					window.bb_recaptcha_script.src = 'https://www.google.com/recaptcha/api.js?onload=bb_recaptcha_v2_verify&render=explicit';
				}
				if ( 'v2_invisible_badge' === self.v2_option ) {
					$( '#bp-hello-content-recaptcha_v3 .verifying_token' ).html('');
					window.bb_recaptcha_script.src = 'https://www.google.com/recaptcha/api.js?onload=bb_recaptcha_v2_verify_invisible&render=explicit';
				}
			}

			window.bb_recaptcha_script.onerror = function () {
				console.log('error');
			};

			window.bb_recaptcha_v3_verify = function () {
				if ( typeof grecaptcha === 'object' ) {
					grecaptcha.ready( function () {
						grecaptcha.execute( self.site_key, { action: 'submit' } ).then( function ( token ) {
							self.captcha_response = token;
							$( '#' + selector + ' .verifying_token' ).hide();
							$( '#' + selector + ' .verified_token' ).show();
						} );
					} );
				}
			};

			window.bb_recaptcha_v2_verify = function () {
				window.bb_recaptcha_box = grecaptcha.render( 'verifying_token', {
					sitekey: self.site_key,
					theme: 'light',
					callback: () => {
						self.captcha_response = grecaptcha.getResponse( window.bb_recaptcha_box );
					},
				} );
			};

			window.bb_recaptcha_v2_verify_invisible = function () {
				window.bb_recaptcha_invisible = grecaptcha.render( 'bp-hello-content-v2_invisible_badge', {
					sitekey: self.site_key,
					tabindex : 9999,
					size: 'invisible',
					callback: ( token ) => {
						self.captcha_response = token;
						$( '#' + selector + ' .verifying_token' ).hide();
						$( '#' + selector + ' .verified_token' ).show();
					},
				} );
				grecaptcha.execute();
			};

			$( '#recaptcha_submit' ).removeAttr( 'disabled' );

			document.head.appendChild( window.bb_recaptcha_script );
		},

		recaptchaSubmit: function ( event ) {
			event.preventDefault();

			$( event.currentTarget ).attr( 'disabled', 'disabled' );
			var data = {
				action: 'bb_recaptcha_verification',
				nonce: bbRecaptcha.nonce,
				selected_version: self.selected_version,
				site_key: self.site_key,
				secret_key: self.secret_key,
				captcha_response: self.captcha_response,
			};
			if ( self.v2_option ) {
				data[ 'v2_option' ] = self.v2_option;
			}

			var selector = this.fetchSelector();
			$.ajax(
				{
					type: 'POST',
					url: bbRecaptcha.ajax_url,
					data: data,
					success: function ( response ) {
						$( event.currentTarget ).removeAttr( 'disabled' );
						if ( response.success && typeof response.data !== 'undefined' ) {
							$( '#' + selector ).html( response.data );
							$( '.bb-popup-buttons' ).html( '<button id="recaptcha_verified" class="button">OK</button>' );
							document.head.removeChild( window.bb_recaptcha_script );
							window.bb_recaptcha_script = null;
							window.bb_recaptcha_v3_verify = null;
							window.bb_recaptcha_box = null;
							window.bb_recaptcha_v2_verify = null;
							window.bb_recaptcha_invisible = null;
							window.bb_recaptcha_v2_verify_invisible = null;
						} else {
							$( '#' + selector ).html( response.data );
							$( '.bb-popup-buttons' ).html( '<button id="recaptcha_cancel" class="button">Cancel</button>' );
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
		},

		fetchSelector: function () {
			if (
				'recaptcha_v3' === self.selected_version ||
				(
					'recaptcha_v2' === self.selected_version &&
					self.v2_option &&
					'v2_invisible_badge' === self.v2_option
				)
			) {
				selector = 'bp-hello-content-recaptcha_v3'
			} else {
				selector = 'bp-hello-content-recaptcha_v2'
			}

			return selector;
		}
	};

	$(
		function () {
			BB_Recaptcha.init();
		}
	);
} )( jQuery );

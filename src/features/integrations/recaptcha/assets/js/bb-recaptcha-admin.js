/* global bbRecaptchaAdmin, grecaptcha */
( function ( $ ) {
	var BB_Recaptcha_Admin = {

		init: function () {
			this.setupGlobals();

			// Listen to events ("Add hooks!").
			this.addListeners();
		},

		setupGlobals: function () {
			this.selected_version = $( 'input[name="bb_recaptcha[recaptcha_version]"]:checked' ).val();
			this.v2_option        = $( '.recaptcha_v2:visible input[name="bb_recaptcha[v2_option]"]:checked' ).val();
			this.site_key         = '';
			this.secret_key       = '';
			this.captcha_response = '';

			// Grey out other settings when recaptcha not connected.
			if ( $( '.bb-recaptcha-settings form table.bb-inactive-field' ).length ) {
				$( '.bb-recaptcha-settings form #bb_recaptcha_settings table' ).removeClass( 'bb-inactive-field' );
				$( '.bb-recaptcha-settings form #bb_recaptcha_design table' ).removeClass( 'bb-inactive-field' );
			}
			if ( $( '.bb-recaptcha-settings .section-bb_recaptcha_versions .bb-recaptcha-status .not-connected' ).length ) {
				$( '.bb-recaptcha-settings form #bb_recaptcha_settings table' ).addClass( 'bb-inactive-field' );
				$( '.bb-recaptcha-settings form #bb_recaptcha_design table' ).addClass( 'bb-inactive-field' );

			}

			var urlParams = new URLSearchParams( window.location.search );
			if ( urlParams.has( 'bb_verified' ) ) {
				urlParams.delete( 'bb_verified' );

				var newUrl = window.location.pathname + '?' + urlParams.toString() + window.location.hash;
				window.history.replaceState( {}, document.title, newUrl );
			}
		},

		addListeners: function () {
			var recaptcha_versions = $( '.buddyboss_page_bp-integrations .section-bb_recaptcha_versions' );
			recaptcha_versions.on( 'keyup', '#bb-recaptcha-site-key, #bb-recaptcha-secret-key', this.enableVerifyButton.bind( this ) );
			recaptcha_versions.on( 'click', '.recaptcha-verification', this.recaptchaVerifications.bind( this ) );
			recaptcha_versions.on( 'click', '#recaptcha_submit', this.recaptchaSubmit.bind( this ) );
			recaptcha_versions.on( 'click', '#recaptcha_verified', this.recaptchaVerified.bind( this ) );
			recaptcha_versions.on( 'click', '.close-modal, #recaptcha_cancel', this.recaptchaVerificationPopupClose.bind( this ) );
			recaptcha_versions.on( 'change', 'input[name="bb_recaptcha[recaptcha_version]"]', this.recaptchaVersion.bind( this ) );
			recaptcha_versions.on( 'change', 'input[name="bb_recaptcha[v2_option]"]', this.recaptchaType.bind( this ) );

			$( '.buddyboss_page_bp-integrations .section-bb_recaptcha_design' ).on( 'change', 'input[name="bb_recaptcha[theme]"]', this.recaptchaTheme.bind( this ) );

			var recaptcha_settings = $( '.buddyboss_page_bp-integrations .section-bb_recaptcha_settings' );
			recaptcha_settings.on( 'change', '#recaptcha_bb_login', this.allowByPass.bind( this ) );
			recaptcha_settings.on( 'change', '#bb_recaptcha_allow_bypass', this.enableBypassInputAndToggle.bind( this ) );
			recaptcha_settings.on( 'keyup', 'input[name="bb_recaptcha[bypass_text]"]', this.updateByPassUrl.bind( this ) );
			recaptcha_settings.on( 'click', '.bb_login_require .bb-recaptcha-copy-button', this.copyByPassUrl.bind( this ) );
		},

		enableVerifyButton: function ( event ) {
			event.preventDefault();
			// Enable/disable verify button and submit button.
			var site_key     = $( '#bb-recaptcha-site-key' ).val();
			var old_site_key = $( '#bb-recaptcha-site-key' ).attr( 'data-old-value' );

			var secret_key     = $( '#bb-recaptcha-secret-key' ).val();
			var old_secret_key = $( '#bb-recaptcha-secret-key' ).attr( 'data-old-value' );

			if ( '' !== site_key && '' !== secret_key ) {
				$( '.verify-row' ).removeClass( 'bp-hide' );
				$( '.recaptcha-verification' ).removeAttr( 'disabled' );
				$( '.bb-recaptcha-settings form .submit input' ).attr( 'disabled', 'disabled' );
				if (
					'' !== $( 'input[name="bb_recaptcha[recaptcha_version]"]:checked' ).val() &&
					(
						site_key !== old_site_key ||
						secret_key !== old_secret_key
					)
				) {
					$( '.verify-row' ).removeClass( 'bp-hide' );
					$( '.recaptcha-verification' ).removeAttr( 'disabled' );
					$( '.bb-recaptcha-settings form .submit input' ).attr( 'disabled', 'disabled' );
				} else {
					$( '.verify-row' ).addClass( 'bp-hide' );
					$( '.recaptcha-verification' ).attr( 'disabled', 'disabled' );
					$( '.bb-recaptcha-settings form .submit input' ).removeAttr( 'disabled' );
				}
			} else {
				$( '.verify-row' ).removeClass( 'bp-hide' );
				$( '.recaptcha-verification' ).attr( 'disabled', 'disabled' );
				$( '.bb-recaptcha-settings form .submit input' ).removeAttr( 'disabled' );
			}
		},

		recaptchaVersion: function ( event ) {
			event.preventDefault();

			if ( 'recaptcha_v3' === event.currentTarget.value ) {
				$( '.recaptcha_v3' ).removeClass( 'bp-hide' );
				$( '.recaptcha_v2' ).addClass( 'bp-hide' );
				$( '#bp-hello-content-recaptcha_v2' ).addClass( 'bp-hide' );
				$( '#bp-hello-content-recaptcha_v3' ).removeClass( 'bp-hide' );
			} else {
				$( '.recaptcha_v2' ).removeClass( 'bp-hide' );
				$( '.recaptcha_v3' ).addClass( 'bp-hide' );

				var existing_v2_option = $( '.recaptcha_v2 input[name="bb_recaptcha[v2_option]"]:checked' ).val();
				if ( 'undefined' !== typeof existing_v2_option && 'v2_invisible_badge' === existing_v2_option ) {
					$( '#bp-hello-content-recaptcha_v2' ).addClass( 'bp-hide' );
					$( '#bp-hello-content-recaptcha_v3' ).removeClass( 'bp-hide' );
				} else {
					$( '#bp-hello-content-recaptcha_v2' ).removeClass( 'bp-hide' );
					$( '#bp-hello-content-recaptcha_v3' ).addClass( 'bp-hide' );
				}
			}

			// Enable/disable verify button and submit button.
			// Need to get v2 selected value after v2 option is visible - $( '.recaptcha_v2:visible input[name="bb_recaptcha[v2_option]"]:checked' ).val() - .
			if (
				this.selected_version !== event.currentTarget.value ||
				(
					'recaptcha_v2' === event.currentTarget.value &&
					this.v2_option !== $( '.recaptcha_v2:visible input[name="bb_recaptcha[v2_option]"]:checked' ).val()
				)
			) {
				$( '.verify-row' ).removeClass( 'bp-hide' );
				$( '.recaptcha-verification' ).removeAttr( 'disabled' );
				$( '.bb-recaptcha-settings form .submit input' ).attr( 'disabled', 'disabled' );
			} else {
				$( '.verify-row' ).addClass( 'bp-hide' );
				$( '.recaptcha-verification' ).attr( 'disabled', 'disabled' );
				$( '.bb-recaptcha-settings form .submit input' ).removeAttr( 'disabled' );
			}
		},

		recaptchaType: function ( event ) {
			event.preventDefault();

			// Enable/disable verify button and submit button.
			if ( this.v2_option !== event.currentTarget.value ) {
				$( '.verify-row' ).removeClass( 'bp-hide' );
				$( '.recaptcha-verification' ).removeAttr( 'disabled' );
				$( '.bb-recaptcha-settings form .submit input' ).attr( 'disabled', 'disabled' );
			} else {
				$( '.verify-row' ).addClass( 'bp-hide' );
				$( '.recaptcha-verification' ).attr( 'disabled', 'disabled' );
				$( '.bb-recaptcha-settings form .submit input' ).removeAttr( 'disabled' );
			}

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
			var self = this;

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
			self.site_key         = $( '#bb-recaptcha-site-key' ).val();
			self.secret_key       = $( '#bb-recaptcha-secret-key' ).val();

			window.bb_recaptcha_script = document.createElement( 'script' );

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
					window.bb_recaptcha_script.src = 'https://www.google.com/recaptcha/api.js?onload=bb_recaptcha_v2_verify_invisible&render=explicit';
					var content                    = '<div id="v2_invisible_footer"></div>';
					$( 'body' ).append( content );
				}
			}

			window.bb_recaptcha_script.onerror = function () {
				console.log( 'error' );
			};

			window.bb_recaptcha_v3_verify = function () {
				if ( typeof grecaptcha === 'object' ) {
					grecaptcha.ready(
						function () {
							grecaptcha.execute( self.site_key, { action: 'bb_recaptcha_admin_verify' } ).then(
								function ( token ) {
									self.captcha_response = token;
									$( '#' + selector + ' .verifying_token' ).hide();
									$( '#' + selector + ' .verified_token' ).show();
								}
							);
						}
					);
				}
			};

			window.bb_recaptcha_v2_verify = function () {
				window.bb_recaptcha_box = grecaptcha.render(
					'verifying_token',
					{
						sitekey: self.site_key,
						theme: 'light',
						callback: function () {
							self.captcha_response = grecaptcha.getResponse( window.bb_recaptcha_box );
						},
					}
				);
			};

			window.bb_recaptcha_v2_verify_invisible = function () {
				window.bb_recaptcha_invisible = grecaptcha.render(
					'v2_invisible_footer',
					{
						sitekey: self.site_key,
						tabindex: 9999,
						size: 'invisible',
						callback: function ( token ) {
							self.captcha_response = token;
							$( '#' + selector + ' .verifying_token' ).hide();
							$( '#' + selector + ' .verified_token' ).show();
						},
					}
				);
				grecaptcha.execute();
			};

			$( '#recaptcha_submit' ).removeAttr( 'disabled' );

			document.head.appendChild( window.bb_recaptcha_script );
		},

		recaptchaSubmit: function ( event ) {
			event.preventDefault();
			var self = this;

			$( event.currentTarget ).attr( 'disabled', 'disabled' );
			var data = {
				action: 'bb_recaptcha_verification_admin_settings',
				nonce: bbRecaptchaAdmin.nonce,
				selected_version: self.selected_version,
				site_key: self.site_key,
				secret_key: self.secret_key,
				captcha_response: self.captcha_response,
			};
			if ( self.v2_option ) {
				data.v2_option = self.v2_option;
			}

			var selector = this.fetchSelector();
			$.ajax(
				{
					type: 'POST',
					url: bbRecaptchaAdmin.ajax_url,
					data: data,
					success: function ( response ) {
						$( event.currentTarget ).removeAttr( 'disabled' );
						if ( response.success && typeof response.data !== 'undefined' ) {
							$( '#' + selector ).html( response.data );
							$( '.bb-popup-buttons' ).html( '<button id="recaptcha_verified" class="button">' + bbRecaptchaAdmin.bb_recaptcha_ok + '</button>' );
							document.head.removeChild( window.bb_recaptcha_script );
							window.bb_recaptcha_script              = null;
							window.bb_recaptcha_v3_verify           = null;
							window.bb_recaptcha_box                 = null;
							window.bb_recaptcha_v2_verify           = null;
							window.bb_recaptcha_invisible           = null;
							window.bb_recaptcha_v2_verify_invisible = null;
						} else {
							$( '#' + selector ).html( response.data );
							$( '.bb-popup-buttons' ).html( '<button id="recaptcha_cancel" class="button">' + bbRecaptchaAdmin.bb_recaptcha_ok + '</button>' );
						}
					},
				}
			);
		},

		recaptchaVerified: function ( event ) {
			event.preventDefault();
			var newUrl = window.location.href + ( window.location.search ? '&' : '?' ) + 'bb_verified';
			window.location.href = newUrl;
		},

		recaptchaVerificationPopupClose: function ( event ) {
			event.preventDefault();
			var newUrl = window.location.href + ( window.location.search ? '&' : '?' ) + 'bb_verified';
			window.location.href = newUrl;
		},

		fetchSelector: function () {
			var self     = this;
			var selector = '';
			if (
				'recaptcha_v3' === self.selected_version ||
				(
					'recaptcha_v2' === self.selected_version &&
					self.v2_option &&
					'v2_invisible_badge' === self.v2_option
				)
			) {
				selector = 'bp-hello-content-recaptcha_v3';
			} else {
				selector = 'bp-hello-content-recaptcha_v2';
			}

			return selector;
		},

		allowByPass: function ( event ) {
			var isChecked = event.currentTarget.checked;
			if ( isChecked ) {
				$( '.bb_login_require' ).removeClass( 'bp-hide' );
			} else {
				$( '.bb_login_require' ).addClass( 'bp-hide' );
			}
		},

		enableBypassInputAndToggle: function ( event ) {
			var isChecked = event.currentTarget.checked;
			if ( isChecked ) {
				$( '.bb_login_require input[name="bb_recaptcha[bypass_text]"]' ).removeAttr( 'disabled' );
				$( '.bb_login_require .copy-toggle' ).removeClass( 'bp-hide' );
			} else {
				$( '.bb_login_require input[name="bb_recaptcha[bypass_text]"]' ).attr( 'disabled', 'disabled' );
				$( '.bb_login_require .copy-toggle' ).addClass( 'bp-hide' );
			}
		},

		updateByPassUrl: function ( event ) {
			event.preventDefault();
			var bypassString = 'xxUNIQUE_STRINGXS';
			if ( event.currentTarget.value ) {
				bypassString = event.currentTarget.value;
			}
			if ( bypassString.length < 6 ) {
				$( '.bb_login_require .copy-toggle' ).addClass( 'bb-url-not-allowed' );
				$( '.bb_login_require .bb-recaptcha-copy-button' ).addClass( 'bp-hide' );
			} else {
				$( '.bb_login_require .copy-toggle' ).removeClass( 'bb-url-not-allowed' );
				$( '.bb_login_require .bb-recaptcha-copy-button' ).removeClass( 'bp-hide' );
			}
			var url = $( '.copy-toggle-text' ).data( 'domain' ) + bypassString;
			$( '.copy-toggle-text' ).attr( 'href', url );
			$( '.copy-toggle-text' ).html( url );
		},

		copyByPassUrl: function ( event ) {
			event.preventDefault();
			var clickedButtonText = $( event.currentTarget ).html();
			var urlToCopy         = $( '.copy-toggle-text' ).attr( 'href' );
			var tempInput         = $( '<input>' );
			$( 'body' ).append( tempInput );
			tempInput.val( urlToCopy ).select();
			document.execCommand( 'copy' );
			tempInput.remove();
			$( event.currentTarget ).html( $( event.currentTarget ).html() );
			var initText = $( event.currentTarget ).data( 'balloon' );
			var copiedText = $( event.currentTarget ).data( 'copied-text' );
			$( event.currentTarget ).attr( 'data-balloon', copiedText );
			setTimeout(
				function () {
					$( event.currentTarget ).html( clickedButtonText );
					$( event.currentTarget ).attr( 'data-balloon', initText );
				},
				2000
			);
		},
	};

	$(
		function () {
			BB_Recaptcha_Admin.init();
		}
	);
} )( jQuery );

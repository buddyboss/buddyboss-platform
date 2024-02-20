/* global bbRecaptcha */
( function ( $ ) {
	var BB_Recaptcha = {

		init: function () {
			this.bbrecaptchaData   = 'undefined' !== bbRecaptcha.data ? bbRecaptcha.data : '';
			this.bbrecaptchaAction = this.bbrecaptchaData && 'undefined' !== this.bbrecaptchaData.actions ? this.bbrecaptchaData.actions : '';
			this.setupGlobals();

			// Listen to events ("Add hooks!").
			this.addListeners();
		},

		setupGlobals: function () {
			var loginAction = this.bbrecaptchaAction &&
												'undefined' !== this.bbrecaptchaAction.bb_login &&
												'undefined' !== this.bbrecaptchaAction.bb_login.enabled ? this.bbrecaptchaAction.bb_login.enabled : false;
			if ( true === loginAction ) {
				if ( 'recaptcha_v3' === this.bbrecaptchaData.selected_version ) {
					grecaptcha.ready( function () {
						grecaptcha.execute( bbRecaptcha.data.site_key, { action: 'bb_login' } ).then( function ( token ) {
							$( '#bb_recaptcha_response_id' ).val( token );
						} );
					} );
				}
				if (
					'recaptcha_v2' === this.bbrecaptchaData.selected_version &&
					'undefined' !== this.bbrecaptchaData.v2_option
				) {
					if ( 'v2_checkbox' === this.bbrecaptchaData.v2_option ) {
						grecaptcha.ready( function () {
							var params = {
								'sitekey': bbRecaptcha.data.site_key,
								'theme': bbRecaptcha.data.v2_theme,
								'size': bbRecaptcha.data.v2_size
							};

							grecaptcha.render( 'bb_recaptcha_login_v2', params );
						} );
					}
					if ( 'v2_invisible_badge' === this.bbrecaptchaData.v2_option ) {
						grecaptcha.ready( function () {
							var params = {
								'sitekey': bbRecaptcha.data.site_key,
								'tabindex': 9999,
								'badge': bbRecaptcha.data.v2_badge_position,
								'size': 'invisible',
								'callback': ( token ) => {
									$( '#g-recaptcha-response' ).val( token );
									document.getElementById( 'loginform' ).submit();
								},
							};
							var loginV2 = grecaptcha.render( 'bb_recaptcha_login_v2', params );
							$( '#loginform' ).on( 'submit', function ( e ) {
								e.preventDefault();
								grecaptcha.execute( loginV2 );
							} );
						} );
					}
				}
			}
		},

		addListeners: function () {

		},
	};

	$(
		function () {
			BB_Recaptcha.init();
		}
	);
} )( jQuery );

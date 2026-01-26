/* global bbRecaptcha, grecaptcha */
( function ( $ ) {
	var BB_Recaptcha = {

		init: function () {
			this.bbrecaptchaData    = 'undefined' !== typeof bbRecaptcha && 'undefined' !== typeof bbRecaptcha.data ? bbRecaptcha.data : '';
			this.bbrecaptchaAction  = this.bbrecaptchaData && 'undefined' !== typeof this.bbrecaptchaData.action ? this.bbrecaptchaData.action : '';
			this.bbrecaptchaVersion = this.bbrecaptchaData && 'undefined' !== typeof this.bbrecaptchaData.selected_version ? this.bbrecaptchaData.selected_version : '';
			this.setupGlobals();
		},

		setupGlobals: function () {
			if ( this.bbrecaptchaAction ) {
				var action    = this.bbrecaptchaAction;
				var container = false;
				if ( 'bb_login' === action ) {
					container = 'loginform';
				} else if ( 'bb_lost_password' === action ) {
					container = 'lostpasswordform';
				} else if ( 'bb_register' === action ) {
					container = 'signup-form';
				} else if ( 'bb_activate' === action ) {
					container = 'activation-form';
				}
				if ( 'recaptcha_v3' === this.bbrecaptchaVersion ) {
					grecaptcha.ready(
						function () {
							grecaptcha.execute( bbRecaptcha.data.site_key, { action: action } ).then(
								function ( token ) {
									$( '#bb_recaptcha_response_id' ).val( token );
								}
							);
						}
					);
				}
				if (
					'recaptcha_v2' === this.bbrecaptchaVersion &&
					'undefined' !== typeof this.bbrecaptchaData.v2_option
				) {
					if ( 'v2_checkbox' === this.bbrecaptchaData.v2_option ) {
						grecaptcha.ready(
							function () {
								var params = {
									'sitekey': bbRecaptcha.data.site_key,
									'theme': bbRecaptcha.data.v2_theme,
									'size': bbRecaptcha.data.v2_size
								};

								grecaptcha.render( 'bb_recaptcha_v2_element', params );
							}
						);
					}
					if ( 'v2_invisible_badge' === this.bbrecaptchaData.v2_option ) {
						grecaptcha.ready( function () {
							var form = $( '#' + container );
							var params = {
								'sitekey': bbRecaptcha.data.site_key,
								'tabindex': 9999,
								'badge': bbRecaptcha.data.v2_badge_position,
								'size': 'invisible',
								'callback': function ( token ) {
									$( '#g-recaptcha-response' ).val( token );
									if ( container ) {
										form.find( 'input[data-click]' ).trigger( 'click' );
									}
								},
							};

							var loginV2 = grecaptcha.render( 'bb_recaptcha_v2_element', params );
							if ( form.length ) {
								form.on( 'submit', function ( e ) {
									if ( '' == form.find( '.g-recaptcha-response' ).val() ) {
										e.preventDefault();
										e.stopImmediatePropagation();
										grecaptcha.execute( loginV2 );
									}
								} ).find( 'input:submit, button' ).on( 'click', function ( e ) {
									if ( '' == form.find( '.g-recaptcha-response' ).val() ) {
										form.find( 'input:submit' ).attr( 'data-click', 'bb_recaptcha_submit' );
										e.preventDefault();
										e.stopImmediatePropagation();
										grecaptcha.execute( loginV2 );
									}
								} );
							}
						} );
					}
				}
			}
		},
	};

	$(
		function () {
			BB_Recaptcha.init();
		}
	);
} )( jQuery );

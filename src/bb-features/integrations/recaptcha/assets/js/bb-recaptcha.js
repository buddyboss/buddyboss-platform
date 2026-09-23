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
					this.setupV3( action, container );
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
							// Bind to the form that actually contains the widget so
							// embedded login forms with a custom form_id (via
							// wp_login_form) still get the submit-intercept. Fall back
							// to the known container id for the core forms.
							var widget = $( '#bb_recaptcha_v2_element' );
							var form   = widget.length ? widget.closest( 'form' ) : $();
							if ( ! form.length && container ) {
								form = $( '#' + container );
							}
							var params = {
								'sitekey': bbRecaptcha.data.site_key,
								'tabindex': 9999,
								'badge': bbRecaptcha.data.v2_badge_position,
								'size': 'invisible',
								'callback': function ( token ) {
									$( '#g-recaptcha-response' ).val( token );
									if ( form.length ) {
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

		/**
		 * Wire reCAPTCHA v3 to the form that carries the hidden response field.
		 *
		 * A v3 token expires two minutes after Google issues it, and the on-load
		 * token is not available until Google's script has finished loading. The
		 * token is therefore refreshed when the visitor actually submits, mirroring
		 * the v2 invisible flow: intercept the submit, mint a fresh token for the
		 * configured action, store it, then re-submit the form through
		 * requestSubmit() with the original control as submitter, so the browser
		 * performs a normal submission (including the control's own name/value,
		 * which the registration screen requires) without re-running other
		 * scripts' click handlers.
		 *
		 * When Google's api.js never loaded there is no way to obtain a token, so
		 * the submission is blocked with a notice instead of being sent to the
		 * server to fail as a counted login attempt.
		 *
		 * @since BuddyBoss [BBVERSION]
		 *
		 * @param {string}         action    reCAPTCHA action name (bb_login, bb_register, ...).
		 * @param {string|boolean} container Known form id for this action, or false.
		 */
		setupV3: function ( action, container ) {
			var self     = this;
			var field    = $( '#bb_recaptcha_response_id' );
			var form     = field.length ? field.closest( 'form' ) : $();
			var mintedAt = 0;

			if ( ! form.length && container ) {
				form = $( '#' + container );
			}
			if ( ! field.length && form.length ) {
				field = form.find( 'input[name="g-recaptcha-response"]' );
			}

			// Keep minting a token on load. It stays the fallback for submissions
			// that never raise a submit event (programmatic form.submit()).
			if ( 'undefined' !== typeof grecaptcha ) {
				grecaptcha.ready(
					function () {
						grecaptcha.execute( bbRecaptcha.data.site_key, { action: action } ).then(
							function ( token ) {
								field.val( token );
								mintedAt = Date.now();
							}
						);
					}
				);
			}

			if ( ! form.length ) {
				return;
			}

			var resubmitting = false;
			var inFlight     = false;

			form.on(
				'submit',
				function ( e ) {
					// Second pass: the token was refreshed a moment ago, let it through.
					if ( resubmitting ) {
						return;
					}

					// Let the browser report its own constraint validation first.
					if ( form[ 0 ].checkValidity && ! form[ 0 ].checkValidity() ) {
						return;
					}

					e.preventDefault();
					e.stopImmediatePropagation();

					// Checked live, not from a DOM-ready snapshot: a deferred api.js
					// may have arrived since the page loaded.
					if ( 'undefined' === typeof grecaptcha ) {
						self.v3Notice( form, field, self.v3String( 'script_failed' ) );
						return;
					}

					if ( inFlight ) {
						return;
					}
					inFlight = true;
					self.v3RemoveNotice( form );

					var submitter = e.originalEvent && e.originalEvent.submitter ? $( e.originalEvent.submitter ) : $();
					if ( ! submitter.length ) {
						submitter = form.find( 'input[type="submit"], button[type="submit"], button:not([type])' ).first();
					}

					// Show the wait: the control is greyed out until Google answers.
					submitter.prop( 'disabled', true );

					self.v3Token(
						action,
						function ( token ) {
							inFlight = false;
							submitter.prop( 'disabled', false );

							if ( token ) {
								field.val( token );
								mintedAt = Date.now();
							} else if ( ! field.val() || ( Date.now() - mintedAt ) > 110000 ) {
								// Nothing fresh, and the on-load token is missing or older
								// than Google's two-minute lifetime: do not consume an attempt.
								self.v3Notice( form, field, self.v3String( 'token_failed' ) );
								submitter.trigger( 'focus' );
								return;
							}

							// Second pass. The flag must be cleared even if another
							// script's submit handler throws, or the intercept would stay
							// disabled for the rest of the page.
							resubmitting = true;
							try {
								self.v3Submit( form, submitter );
							} finally {
								resubmitting = false;
							}
						}
					);
				}
			);
		},

		/**
		 * Submit the form the way the browser would for the given control.
		 *
		 * requestSubmit() fires a real submit event (so the intercept sees the
		 * second pass and other submit listeners still run) and posts the
		 * control's own name/value, which the registration screen requires.
		 * Unlike re-triggering a click it does not re-run other scripts' click
		 * handlers. Browsers without requestSubmit() fall back to submit() with
		 * the control's name/value copied into a hidden input. Both methods are
		 * called from HTMLFormElement.prototype: a form control named "submit"
		 * (the activation form's button) shadows form.submit itself.
		 *
		 * @since BuddyBoss [BBVERSION]
		 *
		 * @param {jQuery} form      The form.
		 * @param {jQuery} submitter The submit control that started the submission, or empty.
		 */
		v3Submit: function ( form, submitter ) {
			var el      = form[ 0 ];
			var control = submitter.length && submitter[ 0 ].form === el ? submitter[ 0 ] : null;
			var proto   = window.HTMLFormElement && window.HTMLFormElement.prototype ? window.HTMLFormElement.prototype : null;

			if ( proto && 'function' === typeof proto.requestSubmit ) {
				if ( control ) {
					proto.requestSubmit.call( el, control );
				} else {
					proto.requestSubmit.call( el );
				}
				return;
			}

			if ( control && control.name ) {
				$( '<input type="hidden" />' ).attr( 'name', control.name ).val( control.value ).appendTo( form );
			}
			if ( proto && 'function' === typeof proto.submit ) {
				proto.submit.call( el );
			} else {
				el.submit();
			}
		},

		/**
		 * Request a fresh reCAPTCHA v3 token for the given action.
		 *
		 * Resolves with an empty string when Google rejects the request, throws,
		 * or does not answer within thirty seconds (for example when api.js loaded
		 * but its dependent script did not). The window is generous on purpose: on
		 * a slow mobile connection Google's own script can take well over ten
		 * seconds to arrive, and a premature failure here would block a visitor
		 * who would otherwise have logged in.
		 *
		 * @since BuddyBoss [BBVERSION]
		 *
		 * @param {string}   action   reCAPTCHA action name.
		 * @param {Function} callback Receives the token, or '' on failure.
		 */
		v3Token: function ( action, callback ) {
			var done  = false;
			var timer = null;

			var finish = function ( token ) {
				if ( done ) {
					return;
				}
				done = true;
				window.clearTimeout( timer );
				callback( 'string' === typeof token ? token : '' );
			};

			timer = window.setTimeout(
				function () {
					finish( '' );
				},
				30000
			);

			try {
				grecaptcha.ready(
					function () {
						grecaptcha.execute( bbRecaptcha.data.site_key, { action: action } ).then(
							function ( token ) {
								finish( token );
							},
							function () {
								finish( '' );
							}
						);
					}
				);
			} catch ( err ) {
				finish( '' );
			}
		},

		/**
		 * Read a localized string passed from bb_recaptcha_display().
		 *
		 * @since BuddyBoss [BBVERSION]
		 *
		 * @param {string} key String key.
		 *
		 * @return {string} Localized string or ''.
		 */
		v3String: function ( key ) {
			var i18n = this.bbrecaptchaData && this.bbrecaptchaData.i18n ? this.bbrecaptchaData.i18n : {};
			return 'string' === typeof i18n[ key ] ? i18n[ key ] : '';
		},

		/**
		 * Show a notice next to the form using the surface's own error markup.
		 *
		 * wp-login.php screens get WordPress core's `.notice.notice-error` box above
		 * the form; BuddyBoss templates (register, activate, embedded login forms)
		 * get the `.bp-messages.bp-feedback.error` block used by their own
		 * validation, placed where the reCAPTCHA field sits.
		 *
		 * @since BuddyBoss [BBVERSION]
		 *
		 * @param {jQuery} form    The form.
		 * @param {jQuery} field   The hidden g-recaptcha-response input.
		 * @param {string} message Localized message.
		 */
		v3Notice: function ( form, field, message ) {
			var notice;

			this.v3RemoveNotice( form );

			if ( ! message ) {
				return;
			}

			if ( form.closest( '#login' ).length ) {
				notice = $( '<div class="notice notice-error bb-recaptcha-notice" role="alert"><p></p></div>' );
				notice.find( 'p' ).text( message );
				form.before( notice );
			} else {
				notice = $( '<div class="bp-messages bp-feedback error bb-recaptcha-notice" role="alert"><span class="bp-icon" aria-hidden="true"></span><p></p></div>' );
				notice.find( 'p' ).text( message );
				if ( field.length ) {
					field.before( notice );
				} else {
					form.prepend( notice );
				}
			}
		},

		/**
		 * Remove any notice previously added by v3Notice().
		 *
		 * @since BuddyBoss [BBVERSION]
		 *
		 * @param {jQuery} form The form.
		 */
		v3RemoveNotice: function ( form ) {
			form.find( '.bb-recaptcha-notice' ).remove();
			form.prevAll( '.bb-recaptcha-notice' ).remove();
		},
	};

	$(
		function () {
			BB_Recaptcha.init();
		}
	);
} )( jQuery );

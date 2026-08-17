/**
 * BuddyBoss Admin Settings 2.0 - reCAPTCHA verify hooks
 *
 * Extends the shared bb_verify_popup field type (VerifyPopupField.js) with
 * reCAPTCHA-specific behavior:
 *
 * - Skips auto-submit so the Google reCAPTCHA widget can render and produce
 *   a token before the verify request is sent.
 * - Renders the Google widget into the modal body via the modal-content
 *   filter (custom JSX replaces the default loading/ready states).
 * - Captures the token; v3 / v2 invisible auto-submit, v2 checkbox waits
 *   for the user to click Submit.
 * - Appends the captcha_response token to the verify AJAX FormData.
 * - Cleans up the script + globals when the modal closes.
 *
 * Disconnect is handled entirely by the shared component — empty values
 * are sent to the verify endpoint and the server clears stored credentials.
 *
 * @package BuddyBoss\Core\Administration
 * @since BuddyBoss [BBVERSION]
 */

import { createElement } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

var FIELD_NAME      = 'bb_recaptcha_verify';
var V2_CONTAINER_ID = 'bb-recaptcha-modal-v2-widget';
var NAMESPACE       = 'buddyboss/recaptcha-verify';

// Module state: the latest captcha token and the modal callbacks for the
// active VerifyPopupField instance. Replaced on each modal_content render,
// so they always reflect the current modal session.
var captchaToken    = '';
var modalCallbacks  = null;

/**
 * Load the Google reCAPTCHA script and render the appropriate widget.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param {string}   siteKey       reCAPTCHA site key.
 * @param {string}   version       Combined version key.
 * @param {Function} onAutoToken   Callback for v3 / v2 invisible (auto token).
 * @param {Function} onV2Token     Callback for v2 checkbox (user-driven token).
 */
function loadRecaptchaWidget( siteKey, version, onAutoToken, onV2Token ) {
	var existingScript = document.getElementById( 'bb-recaptcha-admin-script' );
	if ( existingScript ) {
		existingScript.remove();
	}

	var existingBadge = document.querySelector( '.grecaptcha-badge' );
	if ( existingBadge && existingBadge.parentNode ) {
		existingBadge.parentNode.remove();
	}

	var existingInvisible = document.getElementById( 'bb-recaptcha-invisible-container' );
	if ( existingInvisible ) {
		existingInvisible.remove();
	}

	if ( window.grecaptcha ) {
		try {
			delete window.grecaptcha;
		} catch ( e ) {
			window.grecaptcha = undefined;
		}
	}

	delete window.bb_recaptcha_v3_verify;
	delete window.bb_recaptcha_v2_verify;
	delete window.bb_recaptcha_v2_verify_invisible;

	var script = document.createElement( 'script' );
	script.id  = 'bb-recaptcha-admin-script';
	var apiUrl = 'https://www.google.com/recaptcha/api.js';

	if ( 'recaptcha_v3' === version ) {
		window.bb_recaptcha_v3_verify = function() {
			if ( window.grecaptcha ) {
				window.grecaptcha.ready( function() {
					window.grecaptcha.execute( siteKey, { action: 'bb_recaptcha_admin_verify' } )
						.then( function( token ) {
							onAutoToken( token );
						} );
				} );
			}
		};
		apiUrl += '?onload=bb_recaptcha_v3_verify&render=' + encodeURIComponent( siteKey );
	} else if ( 'recaptcha_v2_checkbox' === version ) {
		window.bb_recaptcha_v2_verify = function() {
			window.bb_recaptcha_box = window.grecaptcha.render(
				V2_CONTAINER_ID,
				{
					sitekey: siteKey,
					theme:   'light',
					callback: function( token ) {
						onV2Token( token );
					},
				}
			);
		};
		apiUrl += '?onload=bb_recaptcha_v2_verify&render=explicit';
	} else if ( 'recaptcha_v2_invisible' === version ) {
		var invisibleDiv = document.createElement( 'div' );
		invisibleDiv.id  = 'bb-recaptcha-invisible-container';
		document.body.appendChild( invisibleDiv );

		window.bb_recaptcha_v2_verify_invisible = function() {
			var widgetId = window.grecaptcha.render(
				'bb-recaptcha-invisible-container',
				{
					sitekey: siteKey,
					size:    'invisible',
					callback: function( token ) {
						onAutoToken( token );
					},
				}
			);
			window.grecaptcha.execute( widgetId );
		};
		apiUrl += '?onload=bb_recaptcha_v2_verify_invisible&render=explicit';
	}

	script.src   = apiUrl;
	script.async = true;
	script.defer = true;
	document.head.appendChild( script );
}

/**
 * Remove the loaded reCAPTCHA script and any global callbacks/widgets.
 *
 * @since BuddyBoss [BBVERSION]
 */
function cleanupRecaptcha() {
	var script = document.getElementById( 'bb-recaptcha-admin-script' );
	if ( script ) {
		script.remove();
	}
	var invisibleContainer = document.getElementById( 'bb-recaptcha-invisible-container' );
	if ( invisibleContainer ) {
		invisibleContainer.remove();
	}
	delete window.bb_recaptcha_v3_verify;
	delete window.bb_recaptcha_v2_verify;
	delete window.bb_recaptcha_v2_verify_invisible;
	delete window.bb_recaptcha_box;
}

// 1. Skip auto-submit for reCAPTCHA — wait for the widget to produce a token.
wp.hooks.addFilter(
	'bb_admin_verify_field_should_auto_submit',
	NAMESPACE,
	function( autoSubmit, field ) {
		if ( field && FIELD_NAME === field.name ) {
			return false;
		}
		return autoSubmit;
	}
);

// 2. On modal phase change: load the widget when entering 'loading',
//    clean up on 'idle' / 'success' / 'error' / 'disconnecting'.
wp.hooks.addAction(
	'bb_admin_verify_field_phase_change',
	NAMESPACE,
	function( field, phase, values ) {
		if ( ! field || FIELD_NAME !== field.name ) {
			return;
		}

		if ( 'loading' === phase ) {
			captchaToken  = '';
			var siteKey   = ( values && values.bb_recaptcha_site_key ) || '';
			var version   = ( values && values.bb_recaptcha_version ) || 'recaptcha_v3';

			if ( ! siteKey ) {
				return;
			}

			// Small delay so the modal DOM (including the v2 container) is mounted.
			setTimeout( function() {
				loadRecaptchaWidget(
					siteKey,
					version,
					function onAutoToken( token ) {
						captchaToken = token;
						if ( modalCallbacks && modalCallbacks.submit ) {
							modalCallbacks.submit();
						}
					},
					function onV2Token( token ) {
						captchaToken = token;
						if ( modalCallbacks && modalCallbacks.setPhase ) {
							modalCallbacks.setPhase( 'ready' );
						}
					}
				);
			}, 100 );
		} else if ( 'idle' === phase || 'success' === phase || 'error' === phase || 'disconnecting' === phase ) {
			cleanupRecaptcha();
			captchaToken = '';
		}
	}
);

// 3. Inject the Google widget container / status message into the modal body.
wp.hooks.addFilter(
	'bb_admin_verify_field_modal_content',
	NAMESPACE,
	function( content, field, phase, values, callbacks ) {
		if ( ! field || FIELD_NAME !== field.name ) {
			return content;
		}

		// Stash callbacks for the async widget callback to use.
		modalCallbacks = callbacks;

		var version      = ( values && values.bb_recaptcha_version ) || 'recaptcha_v3';
		var isV2Checkbox = 'recaptcha_v2_checkbox' === version;

		if ( 'loading' === phase ) {
			if ( isV2Checkbox ) {
				return createElement(
					'div',
					{ className: 'bb-recaptcha-modal__v2-container' },
					createElement( 'div', { id: V2_CONTAINER_ID } )
				);
			}
			return createElement(
				'div',
				{ className: 'bb-admin-verify-modal__status' },
				createElement( 'p', null, __( 'Verifying reCAPTCHA token…', 'buddyboss-platform' ) )
			);
		}

		if ( 'ready' === phase ) {
			if ( isV2Checkbox ) {
				return createElement(
					'div',
					{ className: 'bb-recaptcha-modal__v2-container' },
					createElement( 'div', { id: V2_CONTAINER_ID } )
				);
			}
			return createElement(
				'div',
				{ className: 'bb-admin-verify-modal__status' },
				createElement( 'p', null, __( 'reCAPTCHA token is ready, click Submit to verify.', 'buddyboss-platform' ) )
			);
		}

		// For submitting/success/error, fall through to the default UI.
		return content;
	}
);

// 4. Append the captured captcha_response token + the current version to
//    the verify AJAX FormData. Version is auto-saved as a normal field, so
//    it is not in related_fields — but the verify handler still needs it
//    in the request payload to split the combined v2/v3 selection.
wp.hooks.addFilter(
	'bb_admin_verify_field_before_ajax',
	NAMESPACE,
	function( formData, field, values ) {
		if ( field && FIELD_NAME === field.name ) {
			formData.append( 'captcha_response', captchaToken );
			formData.append(
				'bb_recaptcha_version',
				( values && values.bb_recaptcha_version ) || 'recaptcha_v3'
			);
		}
		return formData;
	}
);

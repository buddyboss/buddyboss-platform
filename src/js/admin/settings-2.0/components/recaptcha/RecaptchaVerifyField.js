/**
 * BuddyBoss Admin Settings 2.0 - reCAPTCHA Verify Field Component
 *
 * Custom field component for the reCAPTCHA verification flow.
 * Opens a modal popup that loads the Google reCAPTCHA widget,
 * captures the token, and sends it to the server for verification.
 * Matches the legacy integration page popup behavior.
 *
 * @package BuddyBoss\Core\Administration
 * @since BuddyBoss [BBVERSION]
 */

import { useState, useRef, useEffect } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { BB_EVENTS } from '../../utils/constants';
import { invalidateFeatureCache } from '../../utils/featureCache';

/**
 * Load the Google reCAPTCHA script dynamically.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param {string} siteKey The reCAPTCHA site key.
 * @param {string} version The combined version.
 * @param {Function} onV3Token Callback for v3 token.
 * @param {string} v2ContainerId DOM ID for v2 checkbox rendering.
 * @param {Function} onV2Token Callback for v2 token.
 */
function loadRecaptchaWidget( siteKey, version, onV3Token, v2ContainerId, onV2Token ) {
	// Remove any previously loaded recaptcha script.
	var existingScript = document.getElementById( 'bb-recaptcha-admin-script' );
	if ( existingScript ) {
		existingScript.remove();
	}

	// Remove existing grecaptcha badge.
	var existingBadge = document.querySelector( '.grecaptcha-badge' );
	if ( existingBadge && existingBadge.parentNode ) {
		existingBadge.parentNode.remove();
	}

	// Clean up invisible container if present.
	var existingInvisible = document.getElementById( 'bb-recaptcha-invisible-container' );
	if ( existingInvisible ) {
		existingInvisible.remove();
	}

	// Reset grecaptcha global.
	if ( window.grecaptcha ) {
		try {
			delete window.grecaptcha;
		} catch ( e ) {
			window.grecaptcha = undefined;
		}
	}

	// Clean up old callbacks.
	delete window.bb_recaptcha_v3_verify;
	delete window.bb_recaptcha_v2_verify;
	delete window.bb_recaptcha_v2_verify_invisible;

	var script = document.createElement( 'script' );
	script.id = 'bb-recaptcha-admin-script';
	var apiUrl = 'https://www.google.com/recaptcha/api.js';

	if ( 'recaptcha_v3' === version ) {
		// v3: Register onload callback, render with site key.
		window.bb_recaptcha_v3_verify = function() {
			if ( window.grecaptcha ) {
				window.grecaptcha.ready( function() {
					window.grecaptcha.execute( siteKey, { action: 'bb_recaptcha_admin_verify' } )
						.then( function( token ) {
							onV3Token( token );
						} );
				} );
			}
		};
		apiUrl += '?onload=bb_recaptcha_v3_verify&render=' + encodeURIComponent( siteKey );
	} else if ( 'recaptcha_v2_checkbox' === version ) {
		// v2 checkbox: Render explicit widget in the modal container.
		window.bb_recaptcha_v2_verify = function() {
			window.bb_recaptcha_box = window.grecaptcha.render(
				v2ContainerId,
				{
					sitekey: siteKey,
					theme: 'light',
					callback: function( token ) {
						onV2Token( token );
					},
				}
			);
		};
		apiUrl += '?onload=bb_recaptcha_v2_verify&render=explicit';
	} else if ( 'recaptcha_v2_invisible' === version ) {
		// v2 invisible: Render invisible badge and auto-execute.
		var invisibleDiv = document.createElement( 'div' );
		invisibleDiv.id = 'bb-recaptcha-invisible-container';
		document.body.appendChild( invisibleDiv );

		window.bb_recaptcha_v2_verify_invisible = function() {
			var widgetId = window.grecaptcha.render(
				'bb-recaptcha-invisible-container',
				{
					sitekey: siteKey,
					size: 'invisible',
					callback: function( token ) {
						onV3Token( token ); // Same flow as v3 — auto token.
					},
				}
			);
			window.grecaptcha.execute( widgetId );
		};
		apiUrl += '?onload=bb_recaptcha_v2_verify_invisible&render=explicit';
	}

	script.src = apiUrl;
	script.async = true;
	script.defer = true;
	document.head.appendChild( script );
}

/**
 * Clean up reCAPTCHA script and globals.
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

/**
 * RecaptchaVerifyField Component
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param {Object}   props          Component props.
 * @param {Object}   props.field    Field configuration object.
 * @param {Object}   props.values   All current form values.
 * @param {boolean}  props.disabled Whether the field is disabled.
 * @returns {JSX.Element} Recaptcha verify field component.
 */
export function RecaptchaVerifyField( props ) {
	var field = props.field;
	var values = props.values || {};
	var disabled = props.disabled;

	var isConnectedInit = field.is_connected || false;

	var connectedState = useState( isConnectedInit );
	var connected = connectedState[ 0 ];
	var setConnected = connectedState[ 1 ];

	var modalOpenState = useState( false );
	var isModalOpen = modalOpenState[ 0 ];
	var setIsModalOpen = modalOpenState[ 1 ];

	// Modal internal states.
	var modalPhaseState = useState( 'loading' ); // 'loading' | 'ready' | 'submitting' | 'success' | 'error'
	var modalPhase = modalPhaseState[ 0 ];
	var setModalPhase = modalPhaseState[ 1 ];

	var modalMessageState = useState( '' );
	var modalMessage = modalMessageState[ 0 ];
	var setModalMessage = modalMessageState[ 1 ];

	var captchaTokenState = useState( '' );
	var captchaToken = captchaTokenState[ 0 ];
	var setCaptchaToken = captchaTokenState[ 1 ];
	var abortRef = useRef( null );
	var v2WidgetContainerId = 'bb-recaptcha-modal-v2-widget';

	// Track initial values of related fields to detect changes.
	var initialValuesRef = useRef( {
		version: values.bb_recaptcha_version || '',
		siteKey: values.bb_recaptcha_site_key || '',
		secretKey: values.bb_recaptcha_secret_key || '',
	} );

	// Sync connected state when field config changes.
	useEffect( function() {
		setConnected( field.is_connected || false );
	}, [ field.is_connected ] );

	// Reset connected state when version, site key, or secret key changes.
	useEffect( function() {
		var currentVersion = values.bb_recaptcha_version || '';
		var currentSiteKey = values.bb_recaptcha_site_key || '';
		var currentSecretKey = values.bb_recaptcha_secret_key || '';
		var initial = initialValuesRef.current;

		if (
			connected &&
			(
				currentVersion !== initial.version ||
				currentSiteKey !== initial.siteKey ||
				currentSecretKey !== initial.secretKey
			)
		) {
			setConnected( false );

			// Sync section header status badge to "Not Connected".
			var resetEvent = new CustomEvent( BB_EVENTS.SECTION_STATUS_UPDATE, {
				detail: {
					fieldName: field.name,
					status: {
						type: 'warning',
						text: __( 'Not Connected', 'buddyboss' ),
					},
				},
			} );
			window.dispatchEvent( resetEvent );
		}
	}, [ values.bb_recaptcha_version, values.bb_recaptcha_site_key, values.bb_recaptcha_secret_key ] );

	// Cleanup on unmount.
	useEffect( function() {
		return function() {
			if ( abortRef.current ) {
				abortRef.current.abort();
			}
			cleanupRecaptcha();
		};
	}, [] );

	/**
	 * Open the verification modal and load the reCAPTCHA widget.
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	function openModal() {
		var siteKey = values.bb_recaptcha_site_key || '';
		var secretKey = values.bb_recaptcha_secret_key || '';

		if ( ! siteKey || ! secretKey ) {
			return;
		}

		setCaptchaToken( '' );
		setModalPhase( 'loading' );
		setModalMessage( '' );
		setIsModalOpen( true );

		var version = values.bb_recaptcha_version || 'recaptcha_v3';

		// For v3 and v2_invisible, token arrives automatically.
		// For v2_checkbox, user must interact with the widget first.
		var onAutoToken = function( token ) {
			setCaptchaToken( token );
			setModalPhase( 'ready' );
		};

		var onV2Token = function( token ) {
			setCaptchaToken( token );
			// Don't auto-advance — user clicks Submit after solving.
		};

		// Small delay to ensure the modal DOM is rendered before rendering widget.
		setTimeout( function() {
			loadRecaptchaWidget( siteKey, version, onAutoToken, v2WidgetContainerId, onV2Token );

			// For v2 checkbox, switch to ready immediately (widget renders in container).
			if ( 'recaptcha_v2_checkbox' === version ) {
				// Wait for grecaptcha to load and render.
				setTimeout( function() {
					setModalPhase( 'ready' );
				}, 1500 );
			}
		}, 100 );
	}

	/**
	 * Handle Submit button click in the modal.
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	function handleSubmit() {
		var token = captchaToken;
		if ( ! token ) {
			setModalPhase( 'error' );
			setModalMessage( __( 'reCAPTCHA verification failed, please try again.', 'buddyboss' ) );
			return;
		}

		setModalPhase( 'submitting' );

		if ( abortRef.current ) {
			abortRef.current.abort();
		}
		var controller = new AbortController();
		abortRef.current = controller;

		var siteKey = values.bb_recaptcha_site_key || '';
		var secretKey = values.bb_recaptcha_secret_key || '';
		var version = values.bb_recaptcha_version || 'recaptcha_v3';

		var formData = new FormData();
		formData.append( 'action', 'bb_recaptcha_verify_settings_2' );
		formData.append( 'nonce', window.bbAdminData.ajaxNonce );
		formData.append( 'bb_recaptcha_site_key', siteKey );
		formData.append( 'bb_recaptcha_secret_key', secretKey );
		formData.append( 'bb_recaptcha_version', version );
		formData.append( 'captcha_response', token );

		fetch( window.bbAdminData.ajaxUrl, {
			method: 'POST',
			credentials: 'same-origin',
			body: formData,
			signal: controller.signal,
		} )
			.then( function( response ) { return response.json(); } )
			.then( function( result ) {
				cleanupRecaptcha();

				if ( result.success ) {
					setModalPhase( 'success' );
					setModalMessage( result.data.message || __( 'reCAPTCHA verification was successful.', 'buddyboss' ) );

					setConnected( true );
					initialValuesRef.current = {
						version: values.bb_recaptcha_version || '',
						siteKey: values.bb_recaptcha_site_key || '',
						secretKey: values.bb_recaptcha_secret_key || '',
					};

					invalidateFeatureCache();

					if ( result.data.status ) {
						var statusEvent = new CustomEvent( BB_EVENTS.SECTION_STATUS_UPDATE, {
							detail: {
								fieldName: field.name,
								status: result.data.status,
							},
						} );
						window.dispatchEvent( statusEvent );
					}

					if ( result.data.updated_fields ) {
						var updateEvent = new CustomEvent( BB_EVENTS.FIELD_VALUE_UPDATE, {
							detail: {
								fields: result.data.updated_fields,
								is_connected: true,
							},
						} );
						window.dispatchEvent( updateEvent );
					}
				} else {
					setModalPhase( 'error' );
					setModalMessage( ( result.data && result.data.message ) || __( 'reCAPTCHA verification failed, please try again.', 'buddyboss' ) );
				}
			} )
			.catch( function( err ) {
				if ( err && 'AbortError' === err.name ) {
					return;
				}
				cleanupRecaptcha();
				setModalPhase( 'error' );
				setModalMessage( err.message || __( 'Verification failed. Please try again.', 'buddyboss' ) );
			} );
	}

	/**
	 * Close the modal and clean up.
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	function closeModal() {
		setIsModalOpen( false );
		cleanupRecaptcha();
		setCaptchaToken( '' );
	}

	// Button label and state — hidden when connected (matches legacy bp-hide behavior).
	// Visible as "Verify" only when not connected and keys are entered.
	var siteKey = values.bb_recaptcha_site_key || '';
	var secretKey = values.bb_recaptcha_secret_key || '';
	var showVerifyButton = ! connected && siteKey && secretKey;
	var isButtonDisabled = disabled;

	var version = values.bb_recaptcha_version || 'recaptcha_v3';
	var isV2Checkbox = 'recaptcha_v2_checkbox' === version;

	// Modal content based on phase.
	var isV3OrInvisible = 'recaptcha_v3' === version || 'recaptcha_v2_invisible' === version;

	return (
		<div className="bb-admin-settings-field__recaptcha-verify">
			{ showVerifyButton && (
				<div className="bb-admin-settings-field__recaptcha-verify-row">
					<button
						type="button"
						className="bb-admin-settings-field__recaptcha-verify-btn"
						onClick={ openModal }
						disabled={ isButtonDisabled }
					>
						{ __( 'Verify', 'buddyboss' ) }
					</button>
				</div>
			) }

			{ /* Verification Modal */ }
			{ isModalOpen && (
				<div className="bb-recaptcha-modal">
					<div className="bb-recaptcha-modal__backdrop" onClick={ closeModal } />
					<div className="bb-recaptcha-modal__container" role="dialog" aria-labelledby="bb-recaptcha-modal-title">
						<div className="bb-recaptcha-modal__header">
							<h2 id="bb-recaptcha-modal-title" className="bb-recaptcha-modal__title">
								{ __( 'Verify reCAPTCHA', 'buddyboss' ) }
							</h2>
							<button
								type="button"
								className="bb-recaptcha-modal__close"
								onClick={ closeModal }
								aria-label={ __( 'Close', 'buddyboss' ) }
							>
								<i className="bb-icon-f bb-icon-times" />
							</button>
						</div>
						<div className="bb-recaptcha-modal__content">
							{ /* v3 / invisible: loading → ready → result */ }
							{ isV3OrInvisible && ( 'loading' === modalPhase || 'ready' === modalPhase ) && (
								<div className="bb-recaptcha-modal__status">
									{ 'loading' === modalPhase && (
										<>
											<div className="bb-recaptcha-modal__icon bb-recaptcha-modal__icon--loading" />
											<p>{ __( 'Verifying reCAPTCHA token', 'buddyboss' ) }</p>
										</>
									) }
									{ 'ready' === modalPhase && (
										<p>{ __( 'reCAPTCHA token is ready, click submit to verify.', 'buddyboss' ) }</p>
									) }
								</div>
							) }

							{ /* v2 checkbox: show widget container */ }
							{ isV2Checkbox && ( 'loading' === modalPhase || 'ready' === modalPhase ) && (
								<div className="bb-recaptcha-modal__v2-container">
									<div id={ v2WidgetContainerId } />
								</div>
							) }

							{ /* Submitting */ }
							{ 'submitting' === modalPhase && (
								<div className="bb-recaptcha-modal__status">
									<div className="bb-recaptcha-modal__icon bb-recaptcha-modal__icon--loading" />
									<p>{ __( 'Verifying...', 'buddyboss' ) }</p>
								</div>
							) }

							{ /* Success result */ }
							{ 'success' === modalPhase && (
								<div className="bb-recaptcha-modal__status bb-recaptcha-modal__status--success">
									<div className="bb-recaptcha-modal__icon bb-recaptcha-modal__icon--success" />
									<p>{ modalMessage }</p>
								</div>
							) }

							{ /* Error result */ }
							{ 'error' === modalPhase && (
								<div className="bb-recaptcha-modal__status bb-recaptcha-modal__status--error">
									<div className="bb-recaptcha-modal__icon bb-recaptcha-modal__icon--error" />
									<p>{ modalMessage }</p>
								</div>
							) }
						</div>
						<div className="bb-recaptcha-modal__footer">
							{ ( 'loading' === modalPhase || 'ready' === modalPhase ) && (
								<>
									<button
										type="button"
										className="bb-recaptcha-modal__btn bb-recaptcha-modal__btn--primary"
										onClick={ handleSubmit }
										disabled={ 'loading' === modalPhase || ! captchaToken }
									>
										{ __( 'Submit', 'buddyboss' ) }
									</button>
									<button
										type="button"
										className="bb-recaptcha-modal__btn"
										onClick={ closeModal }
									>
										{ __( 'Cancel', 'buddyboss' ) }
									</button>
								</>
							) }
							{ 'submitting' === modalPhase && (
								<button
									type="button"
									className="bb-recaptcha-modal__btn bb-recaptcha-modal__btn--primary"
									disabled
								>
									{ __( 'Submitting...', 'buddyboss' ) }
								</button>
							) }
							{ ( 'success' === modalPhase || 'error' === modalPhase ) && (
								<button
									type="button"
									className="bb-recaptcha-modal__btn"
									onClick={ closeModal }
								>
									{ __( 'OK', 'buddyboss' ) }
								</button>
							) }
						</div>
					</div>
				</div>
			) }
		</div>
	);
}

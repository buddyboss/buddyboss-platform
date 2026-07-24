/**
 * BuddyBoss Admin Settings 2.0 - Generic Verify Field Component
 *
 * Reusable field type for credential verification flows. Renders a
 * Verify/Update button that opens a modal dialog with loading → success/error
 * states. Handles AJAX with related field values and updates section badges.
 *
 * Designed for extensibility — plugins can customize modal content, add extra
 * AJAX payload, control submit behavior, and react to phase changes via
 * wp.hooks filters and actions.
 *
 * @internal Landing with no in-tree consumers. Designed for the upcoming Pro
 * Zoom / OneSignal / SSO integrations. Any related-field lookup is done by
 * HTML `name` attribute — related fields MUST be registered with the same
 * `name` that is forwarded to the underlying control (text inputs get `name`
 * from PasswordField, selects via SelectControl's name passthrough).
 *
 * Extension hooks:
 * - bb_admin_verify_field_before_ajax (filter): Modify FormData before AJAX.
 * - bb_admin_verify_field_modal_content (filter): Inject custom modal content.
 * - bb_admin_verify_field_should_auto_submit (filter): Control auto-submit on open.
 * - bb_admin_verify_field_phase_change (action): React to phase transitions.
 * - bb_admin_verify_field_button_visible (filter): Control button visibility.
 * - bb_admin_verify_field_button_disabled (filter): Control button disabled state.
 * - bb_admin_verify_field_button_label (filter): Override button label.
 * - bb_admin_verify_field_modal_title (filter): Override modal title.
 * - bb_admin_verify_field_success (action): Fires on successful verification.
 * - bb_admin_verify_field_error (action): Fires on failed verification.
 *
 * PHP registration:
 *   bb_register_feature_field( $feature, $panel, $section, array(
 *       'name'           => '_my_verify',
 *       'type'           => 'bb_verify_popup',
 *       'label'          => '',
 *       'button_label'   => 'Update',
 *       'ajax_action'    => 'my_verify_action',
 *       'related_fields' => array( 'field-a', 'field-b' ),
 *       'is_connected'   => $is_connected,
 *       'verify_config'  => array(
 *           'modal_title'     => 'Verify Settings',
 *           'loading_message' => 'Verifying...',
 *           'loading_icon'    => 'bb-icon-f bb-icon-cloud',
 *           'success_icon'    => 'bb-icon-f bb-icon-check',
 *           'error_icon'      => 'bb-icon-f bb-icon-exclamation',
 *       ),
 *   ) );
 *
 * AJAX response format (success):
 *   { success: true, data: { message, is_connected, status: {type, text}, updated_fields: {} } }
 *
 * AJAX response format (error):
 *   { success: false, data: { message } }
 *
 * @package BuddyBoss\Core\Administration
 * @since BuddyBoss [BBVERSION]
 */

import { useState, useRef, useEffect, useCallback } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { Spinner } from '@wordpress/components';
import { BB_EVENTS } from '../../utils/constants';
import { invalidateFeatureCache } from '../../utils/featureCache';

/**
 * VerifyField Component
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param {Object}   props          Component props.
 * @param {Object}   props.field    Field configuration object.
 * @param {Object}   props.values   All current form values.
 * @param {boolean}  props.disabled Whether the field is disabled.
 * @returns {JSX.Element} Verify field component.
 */
export function VerifyPopupField( props ) {
	const field    = props.field;
	const values   = props.values || {};
	const disabled = props.disabled;

	const isConnectedInit = field.is_connected || false;
	const relatedFields   = field.related_fields || [];
	const ajaxAction      = field.ajax_action || '';
	const verifyConfig    = field.verify_config || {};

	// Config with defaults.
	const modalTitle     = verifyConfig.modal_title || __( 'Verify Settings', 'buddyboss-platform' );
	const loadingMessage = verifyConfig.loading_message || __( 'Verifying credentials...', 'buddyboss-platform' );
	const loadingIcon    = verifyConfig.loading_icon || 'bb-icons-rl-cloud';
	const successIcon    = verifyConfig.success_icon || 'bb-icons-rl-check-circle';
	const errorIcon      = verifyConfig.error_icon || 'bb-icons-rl-warning-circle';

	// State.
	const [ connected, setConnected ]     = useState( isConnectedInit );
	const [ isModalOpen, setIsModalOpen ] = useState( false );
	// Phases: 'idle' | 'loading' | 'ready' | 'submitting' | 'success' | 'error'.
	const [ modalPhase, setModalPhase ]     = useState( 'idle' );
	const [ modalMessage, setModalMessage ] = useState( '' );

	const abortRef        = useRef( null );
	// Override values for AJAX submission (used by disconnect to force empty payload).
	const submitValuesRef = useRef( null );

	// Track the last server-confirmed connected state. Updated only after
	// successful connect/disconnect AJAX, never by intermediate edits.
	const savedConnectedRef = useRef( isConnectedInit );

	// Track saved/original values of related fields (from server).
	// Used to detect whether the user has actually changed anything.
	// Updated after successful connect/disconnect AJAX.
	const savedValuesRef = useRef( null );
	if ( null === savedValuesRef.current && relatedFields.length > 0 ) {
		const snapshot = {};
		relatedFields.forEach( function ( rf ) {
			snapshot[ rf ] = values[ rf ] || '';
		} );
		savedValuesRef.current = snapshot;
	}

	// Sync when field config changes externally (e.g. page reload with new data).
	useEffect( function () {
		setConnected( field.is_connected || false );
		savedConnectedRef.current = field.is_connected || false;
	}, [ field.is_connected ] );

	// Cleanup on unmount.
	useEffect( function () {
		return function () {
			if ( abortRef.current ) {
				abortRef.current.abort();
			}
		};
	}, [] );

	/**
	 * Submit AJAX verification request.
	 *
	 * Declared first so handleVerify/handleDisconnect can depend on it
	 * without forward-reference issues.
	 */
	const submitVerification = useCallback( function () {
		setModalPhase( 'submitting' );
		wp.hooks.doAction( 'bb_admin_verify_field_phase_change', field, 'submitting', values );

		if ( abortRef.current ) {
			abortRef.current.abort();
		}
		const controller = new AbortController();
		abortRef.current = controller;

		const ajaxUrl = window.bbAdminData ? window.bbAdminData.ajaxUrl : '';
		const nonce   = window.bbAdminData ? window.bbAdminData.ajaxNonce : '';

		let formData = new FormData();
		formData.append( 'action', ajaxAction );
		formData.append( 'nonce', nonce );

		// Send related field values. Use submitValuesRef if set (for disconnect action).
		// For non-disconnect, read from DOM by `name` if React state is stale.
		const fieldsToSubmit = submitValuesRef.current || values;
		relatedFields.forEach( function ( rf ) {
			let val = fieldsToSubmit[ rf ] || '';

			// When submitting live values (not disconnect), fall back to the
			// DOM element matching the related field `name`. Related fields
			// MUST be registered with a `name` — no fuzzy lookup.
			if ( ! submitValuesRef.current && ! val ) {
				const domEl = document.querySelector(
					'input[name="' + rf + '"], select[name="' + rf + '"], textarea[name="' + rf + '"]'
				);
				if ( domEl && domEl.value ) {
					val = domEl.value;
				}
			}

			formData.append( rf, val );
		} );

		/**
		 * Filter: Modify FormData before AJAX request.
		 *
		 * Plugins can add extra fields (e.g. captcha_response for reCAPTCHA).
		 *
		 * @param {FormData} formData The request FormData.
		 * @param {Object}   field    Field configuration.
		 * @param {Object}   values   Current form values (or override values for disconnect).
		 */
		formData = wp.hooks.applyFilters(
			'bb_admin_verify_field_before_ajax',
			formData,
			field,
			fieldsToSubmit
		);

		fetch( ajaxUrl, {
			method: 'POST',
			credentials: 'same-origin',
			body: formData,
			signal: controller.signal,
		} )
			.then( function ( response ) { return response.json(); } )
			.then( function ( result ) {
				if ( result.success ) {
					const data = result.data || {};
					setModalPhase( 'success' );
					setModalMessage( data.message || __( 'Verified successfully.', 'buddyboss-platform' ) );

					// Update connected state based on response (for disconnect, is_connected = false).
					const responseConnected = data.is_connected || false;
					setConnected( responseConnected );
					savedConnectedRef.current = responseConnected;

					// Update saved values snapshot to the submitted values.
					// This becomes the new baseline for change detection.
					const newSnapshot    = {};
					const snapshotValues = submitValuesRef.current || values;
					relatedFields.forEach( function ( rf ) {
						newSnapshot[ rf ] = snapshotValues[ rf ] || '';
					} );
					savedValuesRef.current = newSnapshot;

					// Clear submitValuesRef since we've now submitted and updated the snapshot.
					submitValuesRef.current = null;

					invalidateFeatureCache();

					// Update section status badge.
					if ( data.status ) {
						window.dispatchEvent( new CustomEvent( BB_EVENTS.SECTION_STATUS_UPDATE, {
							detail: { fieldName: field.name, status: data.status },
						} ) );
					}

					// Update hidden fields (e.g. _is_connected) with response value.
					// Also support field_options for dynamically refreshing select field options
					// (e.g. bucket dropdowns after credential verification).
					if ( data.updated_fields || data.field_options ) {
						window.dispatchEvent( new CustomEvent( BB_EVENTS.FIELD_VALUE_UPDATE, {
							detail: {
								fields:        data.updated_fields || {},
								field_options: data.field_options || null,
								is_connected:  responseConnected,
							},
						} ) );
					}

					wp.hooks.doAction( 'bb_admin_verify_field_phase_change', field, 'success', data );

					/**
					 * Action: Fires on successful verification.
					 *
					 * @param {Object} field  Field configuration.
					 * @param {Object} data   Response data.
					 * @param {Object} values Form values at time of verification.
					 */
					wp.hooks.doAction( 'bb_admin_verify_field_success', field, data, values );
				} else {
					const errorData = result.data || {};
					const errorMsg  = errorData.message || __( 'Verification failed.', 'buddyboss-platform' );
					setModalPhase( 'error' );
					setModalMessage( errorMsg );

					// Update section status badge on error.
					if ( errorData.status ) {
						window.dispatchEvent( new CustomEvent( BB_EVENTS.SECTION_STATUS_UPDATE, {
							detail: { fieldName: field.name, status: errorData.status },
						} ) );
					}

					// Update hidden fields on error (e.g. _is_connected = 0).
					if ( errorData.updated_fields ) {
						window.dispatchEvent( new CustomEvent( BB_EVENTS.FIELD_VALUE_UPDATE, {
							detail: { fields: errorData.updated_fields, is_connected: false },
						} ) );
					}

					wp.hooks.doAction( 'bb_admin_verify_field_phase_change', field, 'error', errorData );

					/**
					 * Action: Fires on failed verification.
					 *
					 * @param {Object} field    Field configuration.
					 * @param {Object} data     Error response data.
					 * @param {Object} values   Form values at time of verification.
					 */
					wp.hooks.doAction( 'bb_admin_verify_field_error', field, errorData, values );
				}
			} )
			.catch( function ( err ) {
				if ( err && 'AbortError' === err.name ) {
					return;
				}
				const catchMsg = __( 'Connection failed. Please try again.', 'buddyboss-platform' );
				setModalPhase( 'error' );
				setModalMessage( catchMsg );
				wp.hooks.doAction( 'bb_admin_verify_field_phase_change', field, 'error', { message: catchMsg } );
			} );
	}, [ ajaxAction, field, values, relatedFields ] );

	/**
	 * Handle disconnect action: clear all related field values and submit.
	 */
	const handleDisconnect = useCallback( function () {
		// Create a payload with empty values for all related fields.
		const emptyValues = {};
		relatedFields.forEach( function ( rf ) {
			emptyValues[ rf ] = '';
		} );

		// Store empty values in ref so submitVerification() uses them instead of props.values.
		// This is critical because props.values won't update in time before submitVerification is called.
		submitValuesRef.current = emptyValues;

		// Dispatch field value update to clear form fields.
		window.dispatchEvent( new CustomEvent( BB_EVENTS.FIELD_VALUE_UPDATE, {
			detail: { fields: emptyValues },
		} ) );

		// NOTE: Do NOT update savedValuesRef here. This allows the component to track
		// changes against the originally connected state. If the user re-enters the same
		// values, the button will change back to Disconnect automatically.
		// savedValuesRef will only update after the server confirms the disconnect.

		// Open modal and submit with empty values.
		setModalPhase( 'loading' );
		setModalMessage( '' );
		setIsModalOpen( true );

		wp.hooks.doAction( 'bb_admin_verify_field_phase_change', field, 'disconnecting', values );

		// Submit immediately with empty values (ref is already set above).
		submitVerification();
	}, [ field, values, relatedFields, submitVerification ] );

	/**
	 * Open modal and start verification.
	 */
	const handleVerify = useCallback( function () {
		setModalPhase( 'loading' );
		setModalMessage( '' );
		setIsModalOpen( true );

		wp.hooks.doAction( 'bb_admin_verify_field_phase_change', field, 'loading', values );

		/**
		 * Filter: Should the verify field auto-submit on modal open?
		 *
		 * Return false to prevent auto-submit (e.g. reCAPTCHA waits for widget).
		 * When false, the modal shows in 'loading' phase and the plugin must
		 * call handleSubmit manually via the phase_change action.
		 *
		 * @param {boolean} autoSubmit Whether to auto-submit.
		 * @param {Object}  field      Field configuration.
		 * @param {Object}  values     Current form values.
		 */
		const autoSubmit = wp.hooks.applyFilters(
			'bb_admin_verify_field_should_auto_submit',
			true,
			field,
			values
		);

		if ( autoSubmit ) {
			submitVerification();
		}
	}, [ field, values, submitVerification ] );

	/**
	 * Close modal.
	 */
	const closeModal = useCallback( function () {
		setIsModalOpen( false );
		setModalPhase( 'idle' );
		wp.hooks.doAction( 'bb_admin_verify_field_phase_change', field, 'idle', {} );
	}, [ field ] );

	// --- Button visibility and state ---

	// Compare current values against saved/server values (not intermediate state).
	// This is the single source of truth for whether the user has changed anything.
	const hasChanges = savedValuesRef.current && relatedFields.some( function ( rf ) {
		return ( values[ rf ] || '' ) !== ( savedValuesRef.current[ rf ] || '' );
	} );

	// Derive button state from saved connected state + value comparison.
	// No dependency on React state — purely from refs and current prop values.
	// - 'disconnect': was connected + values unchanged → show Disconnect button
	// - 'connect': was not connected OR values changed → show Update/Connect button
	// - 'hidden': not connected + no fields → hidden
	let buttonState = 'hidden';
	if ( savedConnectedRef.current && ! hasChanges ) {
		buttonState = 'disconnect';
	} else if ( ! savedConnectedRef.current || hasChanges ) {
		buttonState = 'connect';
	}

	/**
	 * Filter: Control button visibility.
	 *
	 * Default: show when not connected OR when related values changed.
	 *
	 * @param {boolean} visible Whether the button is visible.
	 * @param {Object}  field   Field configuration.
	 * @param {boolean} connected Current connected state.
	 * @param {boolean} hasChanges Whether related field values changed.
	 * @param {Object}  values    Current form values.
	 */
	const showButton = wp.hooks.applyFilters(
		'bb_admin_verify_field_button_visible',
		'hidden' !== buttonState,
		field,
		connected,
		hasChanges,
		values
	);

	// Check if all related fields have values. Look up by `name` attribute only
	// — related fields MUST be registered with a matching `name`. If the DOM
	// element cannot be found by name, fall back to the parent `values` prop.
	const allFilled = relatedFields.every( function ( rf ) {
		const domEl = document.querySelector(
			'input[name="' + rf + '"], select[name="' + rf + '"], textarea[name="' + rf + '"]'
		);
		let domValue = domEl ? ( domEl.value || '' ) : '';

		// For SELECT fields: trust the DOM only — React state may carry a
		// default value even when the user has not actually selected anything.
		if ( domEl && 'SELECT' === domEl.tagName ) {
			domValue = ( domValue || '' ).toString().trim();
		} else if ( ! domValue ) {
			// Non-select (or element not found): fall back to parent form state.
			domValue = values[ rf ] || '';
		}

		return !! String( domValue ).trim();
	} );

	/**
	 * Filter: Control button disabled state.
	 *
	 * Default behavior:
	 * - For "disconnect" button: never disabled (allow users to clear any time)
	 * - For "connect" button: disabled until all related fields are filled
	 * - Ignores parent `disabled` prop since this button manages critical connections
	 *
	 * @param {boolean} isDisabled Whether the button is disabled.
	 * @param {Object}  field      Field configuration.
	 * @param {boolean} allFilled  Whether all related fields have values.
	 * @param {Object}  values     Current form values.
	 */
	const isButtonDisabled = wp.hooks.applyFilters(
		'bb_admin_verify_field_button_disabled',
		'connect' === buttonState && ! allFilled,
		field,
		allFilled,
		values
	);

	/**
	 * Filter: Override button label.
	 *
	 * @param {string} label Button label text.
	 * @param {Object} field Field configuration.
	 * @param {boolean} connected Whether currently connected.
	 */
	const connectLabel = wp.hooks.applyFilters(
		'bb_admin_verify_field_button_label',
		field.button_label || __( 'Verify', 'buddyboss-platform' ),
		field,
		connected
	);

	const disconnectLabel = field.disconnect_label || __( 'Disconnect', 'buddyboss-platform' );
	const buttonLabel     = 'disconnect' === buttonState ? disconnectLabel : connectLabel;

	/**
	 * Filter: Override modal title.
	 *
	 * @param {string} title Modal title text.
	 * @param {Object} field Field configuration.
	 */
	const filteredModalTitle = wp.hooks.applyFilters(
		'bb_admin_verify_field_modal_title',
		modalTitle,
		field
	);

	/**
	 * Filter: Inject custom content into the modal body.
	 *
	 * When a non-null value is returned, it replaces the default modal content
	 * for the current phase. Useful for rendering widgets (e.g. reCAPTCHA).
	 *
	 * @param {*}      content    Custom content (null = use default).
	 * @param {Object} field      Field configuration.
	 * @param {string} phase      Current modal phase.
	 * @param {Object} values     Current form values.
	 * @param {Object} callbacks  Submit/close callbacks for plugin use.
	 */
	const customModalContent = wp.hooks.applyFilters(
		'bb_admin_verify_field_modal_content',
		null,
		field,
		modalPhase,
		values,
		{
			submit: submitVerification,
			close: closeModal,
			setPhase: setModalPhase,
			setMessage: setModalMessage,
		}
	);

	return (
		<div className="bb-admin-verify-field">
			{ showButton && 'disconnect' === buttonState && (
				<button
					type="button"
					className="bb-admin-verify-field__btn bb-admin-verify-field__btn--secondary"
					onClick={ handleDisconnect }
					disabled={ isButtonDisabled }
				>
					{ buttonLabel }
				</button>
			) }

			{ showButton && 'connect' === buttonState && (
				<button
					type="button"
					className="bb-admin-verify-field__btn bb-admin-verify-field__btn--primary"
					onClick={ handleVerify }
					disabled={ isButtonDisabled }
				>
					{ buttonLabel }
				</button>
			) }

			{ isModalOpen && (
				<div className="bb-admin-verify-modal">
					<div className="bb-admin-verify-modal__backdrop" onClick={ closeModal } role="presentation" />
					<div
						className="bb-admin-verify-modal__container"
						onClick={ function ( e ) { e.stopPropagation(); } }
						role="dialog"
						aria-labelledby="bb-admin-verify-modal-title"
					>
						<div className="bb-admin-verify-modal__header">
							<h2 id="bb-admin-verify-modal-title" className="bb-admin-verify-modal__title">
								{ filteredModalTitle }
							</h2>
							<button
								type="button"
								className="bb-admin-verify-modal__close"
								onClick={ closeModal }
								aria-label={ __( 'Close', 'buddyboss-platform' ) }
							>
								<i className="bb-icons-rl-x" />
							</button>
						</div>

						<div className="bb-admin-verify-modal__content">
							{ customModalContent ? customModalContent : (
								<>
									{ ( 'loading' === modalPhase || 'submitting' === modalPhase ) && (
										<div className="bb-admin-verify-modal__status">
											<i className={ loadingIcon } />
											<p>{ loadingMessage } <Spinner /></p>
										</div>
									) }

									{ 'success' === modalPhase && (
										<div className="bb-admin-verify-modal__status bb-admin-verify-modal__status--success">
											<i className={ successIcon } />
											<p>{ modalMessage }</p>
										</div>
									) }

									{ 'error' === modalPhase && (
										<div className="bb-admin-verify-modal__status bb-admin-verify-modal__status--error">
											<i className={ errorIcon } />
											<p>{ modalMessage }</p>
										</div>
									) }
								</>
							) }
						</div>

						<div className="bb-admin-verify-modal__footer">
							{ ( 'loading' === modalPhase || 'ready' === modalPhase ) && (
								<>
									<button
										type="button"
										className="bb-admin-verify-modal__btn bb-admin-verify-modal__btn--primary"
										onClick={ submitVerification }
										disabled={ 'loading' === modalPhase }
									>
										{ __( 'Submit', 'buddyboss-platform' ) }
									</button>
									<button
										type="button"
										className="bb-admin-verify-modal__btn"
										onClick={ closeModal }
									>
										{ __( 'Cancel', 'buddyboss-platform' ) }
									</button>
								</>
							) }

							{ 'submitting' === modalPhase && (
								<button
									type="button"
									className="bb-admin-verify-modal__btn bb-admin-verify-modal__btn--primary"
									disabled
								>
									{ __( 'Verifying...', 'buddyboss-platform' ) }
								</button>
							) }

							{ ( 'success' === modalPhase || 'error' === modalPhase ) && (
								<button
									type="button"
									className="bb-admin-verify-modal__btn bb-admin-verify-modal__btn--primary"
									onClick={ closeModal }
								>
									{ __( 'OK', 'buddyboss-platform' ) }
								</button>
							) }
						</div>
					</div>
				</div>
			) }
		</div>
	);
}

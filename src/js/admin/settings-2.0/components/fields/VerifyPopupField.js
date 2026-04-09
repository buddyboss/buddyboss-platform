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
 *       'type'           => 'verify',
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
	var field    = props.field;
	var values   = props.values || {};
	var disabled = props.disabled;

	var isConnectedInit = field.is_connected || false;
	var relatedFields   = field.related_fields || [];
	var ajaxAction      = field.ajax_action || '';
	var verifyConfig    = field.verify_config || {};

	// Config with defaults.
	var modalTitle     = verifyConfig.modal_title || __( 'Verify Settings', 'buddyboss' );
	var loadingMessage = verifyConfig.loading_message || __( 'Verifying credentials...', 'buddyboss' );
	var loadingIcon    = verifyConfig.loading_icon || 'bb-icon-f bb-icon-cloud';
	var successIcon    = verifyConfig.success_icon || 'bb-icon-f bb-icon-check';
	var errorIcon      = verifyConfig.error_icon || 'bb-icon-f bb-icon-exclamation';

	// State.
	var connectedState    = useState( isConnectedInit );
	var connected         = connectedState[ 0 ];
	var setConnected      = connectedState[ 1 ];

	var modalOpenState    = useState( false );
	var isModalOpen       = modalOpenState[ 0 ];
	var setIsModalOpen    = modalOpenState[ 1 ];

	var modalPhaseState   = useState( 'idle' ); // 'idle' | 'loading' | 'ready' | 'submitting' | 'success' | 'error'
	var modalPhase        = modalPhaseState[ 0 ];
	var setModalPhase     = modalPhaseState[ 1 ];

	var modalMessageState = useState( '' );
	var modalMessage      = modalMessageState[ 0 ];
	var setModalMessage   = modalMessageState[ 1 ];

	var abortRef = useRef( null );

	// Track initial values of related fields to detect changes.
	var initialValuesRef = useRef( null );
	if ( null === initialValuesRef.current && relatedFields.length > 0 ) {
		var snapshot = {};
		relatedFields.forEach( function( rf ) {
			snapshot[ rf ] = values[ rf ] || '';
		} );
		initialValuesRef.current = snapshot;
	}

	// Sync connected state when field config changes (e.g. page reload).
	useEffect( function() {
		setConnected( field.is_connected || false );
	}, [ field.is_connected ] );

	// Reset internal connected state when any related field value changes.
	// This controls button visibility (show Update when fields changed) but does NOT
	// update the section status badge — badge only changes after clicking Update.
	useEffect( function() {
		if ( ! connected || ! initialValuesRef.current ) {
			return;
		}

		var hasChanges = relatedFields.some( function( rf ) {
			return ( values[ rf ] || '' ) !== ( initialValuesRef.current[ rf ] || '' );
		} );

		if ( hasChanges ) {
			setConnected( false );

			/**
			 * Action: Fires when connected state resets due to value changes.
			 *
			 * @param {Object} field  Field configuration.
			 * @param {Object} values Current form values.
			 */
			wp.hooks.doAction( 'bb_admin_verify_field_phase_change', field, 'disconnected', values );
		}
	}, relatedFields.map( function( rf ) { return values[ rf ]; } ) );

	// Cleanup on unmount.
	useEffect( function() {
		return function() {
			if ( abortRef.current ) {
				abortRef.current.abort();
			}
		};
	}, [] );

	/**
	 * Open modal and start verification.
	 */
	var handleVerify = useCallback( function() {
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
		var autoSubmit = wp.hooks.applyFilters(
			'bb_admin_verify_field_should_auto_submit',
			true,
			field,
			values
		);

		if ( autoSubmit ) {
			submitVerification();
		}
	}, [ field, values, ajaxAction, submitVerification ] );

	/**
	 * Submit AJAX verification request.
	 */
	var submitVerification = useCallback( function() {
		setModalPhase( 'submitting' );
		wp.hooks.doAction( 'bb_admin_verify_field_phase_change', field, 'submitting', values );

		if ( abortRef.current ) {
			abortRef.current.abort();
		}
		var controller = new AbortController();
		abortRef.current = controller;

		var ajaxUrl = window.bbAdminData ? window.bbAdminData.ajaxUrl : '';
		var nonce   = window.bbAdminData ? window.bbAdminData.ajaxNonce : '';

		var formData = new FormData();
		formData.append( 'action', ajaxAction );
		formData.append( 'nonce', nonce );

		// Send related field values.
		relatedFields.forEach( function( rf ) {
			formData.append( rf, values[ rf ] || '' );
		} );

		/**
		 * Filter: Modify FormData before AJAX request.
		 *
		 * Plugins can add extra fields (e.g. captcha_response for reCAPTCHA).
		 *
		 * @param {FormData} formData The request FormData.
		 * @param {Object}   field    Field configuration.
		 * @param {Object}   values   Current form values.
		 */
		formData = wp.hooks.applyFilters(
			'bb_admin_verify_field_before_ajax',
			formData,
			field,
			values
		);

		fetch( ajaxUrl, {
			method: 'POST',
			credentials: 'same-origin',
			body: formData,
			signal: controller.signal,
		} )
			.then( function( response ) { return response.json(); } )
			.then( function( result ) {
				if ( result.success ) {
					var data = result.data || {};
					setModalPhase( 'success' );
					setModalMessage( data.message || __( 'Verified successfully.', 'buddyboss' ) );
					setConnected( true );

					// Update initial values snapshot.
					var newSnapshot = {};
					relatedFields.forEach( function( rf ) {
						newSnapshot[ rf ] = values[ rf ] || '';
					} );
					initialValuesRef.current = newSnapshot;

					invalidateFeatureCache();

					// Update section status badge.
					if ( data.status ) {
						window.dispatchEvent( new CustomEvent( BB_EVENTS.SECTION_STATUS_UPDATE, {
							detail: { fieldName: field.name, status: data.status },
						} ) );
					}

					// Update hidden fields (e.g. _is_connected).
					if ( data.updated_fields ) {
						window.dispatchEvent( new CustomEvent( BB_EVENTS.FIELD_VALUE_UPDATE, {
							detail: { fields: data.updated_fields, is_connected: true },
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
					var errorData = result.data || {};
					var errorMsg = errorData.message || __( 'Verification failed.', 'buddyboss' );
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
			.catch( function( err ) {
				if ( err && 'AbortError' === err.name ) {
					return;
				}
				var catchMsg = __( 'Connection failed. Please try again.', 'buddyboss' );
				setModalPhase( 'error' );
				setModalMessage( catchMsg );
				wp.hooks.doAction( 'bb_admin_verify_field_phase_change', field, 'error', { message: catchMsg } );
			} );
	}, [ ajaxAction, field, values ] );

	/**
	 * Close modal.
	 */
	var closeModal = useCallback( function() {
		setIsModalOpen( false );
		setModalPhase( 'idle' );
		wp.hooks.doAction( 'bb_admin_verify_field_phase_change', field, 'idle', {} );
	}, [ field ] );

	// --- Button visibility ---

	var hasChanges = initialValuesRef.current && relatedFields.some( function( rf ) {
		return ( values[ rf ] || '' ) !== ( initialValuesRef.current[ rf ] || '' );
	} );

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
	var showButton = wp.hooks.applyFilters(
		'bb_admin_verify_field_button_visible',
		! connected || hasChanges,
		field,
		connected,
		hasChanges,
		values
	);

	// All related fields must have values.
	var allFilled = relatedFields.every( function( rf ) {
		return !! ( values[ rf ] || '' ).toString().trim();
	} );

	/**
	 * Filter: Control button disabled state.
	 *
	 * @param {boolean} isDisabled Whether the button is disabled.
	 * @param {Object}  field      Field configuration.
	 * @param {boolean} allFilled  Whether all related fields have values.
	 * @param {Object}  values     Current form values.
	 */
	var isButtonDisabled = wp.hooks.applyFilters(
		'bb_admin_verify_field_button_disabled',
		disabled || ! allFilled,
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
	var buttonLabel = wp.hooks.applyFilters(
		'bb_admin_verify_field_button_label',
		field.button_label || __( 'Verify', 'buddyboss' ),
		field,
		connected
	);

	/**
	 * Filter: Override modal title.
	 *
	 * @param {string} title Modal title text.
	 * @param {Object} field Field configuration.
	 */
	var filteredModalTitle = wp.hooks.applyFilters(
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
	var customModalContent = wp.hooks.applyFilters(
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
			{ showButton && (
				<button
					type="button"
					className="bb-admin-verify-field__btn"
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
						onClick={ function( e ) { e.stopPropagation(); } }
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
								aria-label={ __( 'Close', 'buddyboss' ) }
							>
								<i className="bb-icon-f bb-icon-times" />
							</button>
						</div>

						<div className="bb-admin-verify-modal__content">
							{ customModalContent ? customModalContent : (
								<>
									{ ( 'loading' === modalPhase || 'submitting' === modalPhase ) && (
										<div className="bb-admin-verify-modal__status">
											<div className="bb-admin-verify-modal__icon bb-admin-verify-modal__icon--loading" />
											<p>{ loadingMessage }</p>
										</div>
									) }

									{ 'success' === modalPhase && (
										<div className="bb-admin-verify-modal__status bb-admin-verify-modal__status--success">
											<div className="bb-admin-verify-modal__icon bb-admin-verify-modal__icon--success" />
											<p>{ modalMessage }</p>
										</div>
									) }

									{ 'error' === modalPhase && (
										<div className="bb-admin-verify-modal__status bb-admin-verify-modal__status--error">
											<div className="bb-admin-verify-modal__icon bb-admin-verify-modal__icon--error" />
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
										{ __( 'Submit', 'buddyboss' ) }
									</button>
									<button
										type="button"
										className="bb-admin-verify-modal__btn"
										onClick={ closeModal }
									>
										{ __( 'Cancel', 'buddyboss' ) }
									</button>
								</>
							) }

							{ 'submitting' === modalPhase && (
								<button
									type="button"
									className="bb-admin-verify-modal__btn bb-admin-verify-modal__btn--primary"
									disabled
								>
									{ __( 'Verifying...', 'buddyboss' ) }
								</button>
							) }

							{ ( 'success' === modalPhase || 'error' === modalPhase ) && (
								<button
									type="button"
									className="bb-admin-verify-modal__btn bb-admin-verify-modal__btn--primary"
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

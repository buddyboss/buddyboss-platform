/**
 * BuddyBoss Admin Settings 2.0 - Input Button Field Component
 *
 * Renders a text input with an action button (e.g., Connect/Disconnect for API keys).
 * Supports connection status tracking and updates section status badges via custom events.
 *
 * When `field.button_only` is true, renders only the action button without a text input.
 * When `field.related_fields` is set, collects those field values from the form and
 * includes them in the AJAX payload.
 *
 * @package BuddyBoss\Core\Administration
 * @since BuddyBoss [BBVERSION]
 */

import { useState, useRef, useEffect } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { BB_EVENTS } from '../../utils/constants';
import { invalidateFeatureCache } from '../../utils/featureCache';

/**
 * Input Button Field Component
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param {Object}   props          Component props.
 * @param {Object}   props.field    Field configuration object.
 * @param {string}   props.value    Current field value.
 * @param {Function} props.onChange Change handler.
 * @param {boolean}  props.disabled Whether the field is disabled.
 * @param {Object}   props.values   All current form values (for related_fields lookup).
 * @returns {JSX.Element} Input button field component.
 */
export function InputButtonField( { field, value, onChange, disabled, values } ) {
	var isConnected = field.is_connected || false;
	var initialButtonLabel = field.button_label || __( 'Connect', 'buddyboss' );
	var isButtonOnly = field.button_only || false;

	var inputRef = useRef( null );
	var abortRef = useRef( null );
	var inputValueState = useState( value || '' );
	var inputValue = inputValueState[ 0 ];
	var setInputValue = inputValueState[ 1 ];

	var buttonLabelState = useState( initialButtonLabel );
	var buttonLabel = buttonLabelState[ 0 ];
	var setButtonLabel = buttonLabelState[ 1 ];

	var connectedState = useState( isConnected );
	var connected = connectedState[ 0 ];
	var setConnected = connectedState[ 1 ];

	var loadingState = useState( false );
	var isLoading = loadingState[ 0 ];
	var setIsLoading = loadingState[ 1 ];

	var errorState = useState( '' );
	var errorMessage = errorState[ 0 ];
	var setErrorMessage = errorState[ 1 ];

	var warningState = useState( '' );
	var warningMessage = warningState[ 0 ];
	var setWarningMessage = warningState[ 1 ];

	// Sync local state when value prop changes (e.g. settings reload).
	useEffect( function() {
		setInputValue( value || '' );
	}, [ value ] );

	// Sync connected state when field.is_connected changes (e.g. after page reload).
	useEffect( function() {
		setConnected( field.is_connected || false );
		setButtonLabel( field.button_label || __( 'Connect', 'buddyboss' ) );
	}, [ field.is_connected, field.button_label ] );

	// Abort in-flight request on unmount.
	useEffect( function() {
		return function() {
			if ( abortRef.current ) {
				abortRef.current.abort();
			}
		};
	}, [] );

	/**
	 * Handle connect/disconnect button click.
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	function handleButtonClick() {
		if ( isLoading ) {
			return;
		}

		// Abort any previous in-flight request.
		if ( abortRef.current ) {
			abortRef.current.abort();
		}

		var controller = new AbortController();
		abortRef.current = controller;

		setIsLoading( true );
		setErrorMessage( '' );
		setWarningMessage( '' );

		var ajaxAction = field.ajax_action || 'bb_media_giphy_connect';
		var formData = new FormData();
		formData.append( 'action', ajaxAction );
		formData.append( 'nonce', window.bbAdminData.ajaxNonce );

		if ( isButtonOnly && Array.isArray( field.related_fields ) && values ) {
			// Button-only mode with related fields: send sibling field values.
			field.related_fields.forEach( function( relatedFieldName ) {
				if ( undefined !== values[ relatedFieldName ] ) {
					formData.append( relatedFieldName, values[ relatedFieldName ] );
				}
			} );
		} else {
			// Standard mode: send connect_action and the input's own value.
			formData.append( 'connect_action', connected ? 'disconnect' : 'connect' );
			formData.append( 'api_key', inputValue );
		}

		fetch( window.bbAdminData.ajaxUrl, {
			method: 'POST',
			credentials: 'same-origin',
			body: formData,
			signal: controller.signal,
		} )
			.then( function( response ) {
				return response.json();
			} )
			.then( function( result ) {
				setIsLoading( false );

				if ( result.success ) {
					var data = result.data;
					setConnected( data.is_connected );

					if ( data.button_label ) {
						setButtonLabel( data.button_label );
					}

					// Invalidate feature cache so navigating away and back fetches fresh data.
					invalidateFeatureCache();

					if ( ! isButtonOnly && ! data.is_connected ) {
						// Disconnected: clear the input value.
						setInputValue( '' );
						onChange( field.name, '' );
					}

					// Dispatch custom event to update section status badge.
					if ( data.status ) {
						var statusEvent = new CustomEvent( BB_EVENTS.SECTION_STATUS_UPDATE, {
							detail: {
								fieldName: field.name,
								status: data.status,
							},
						} );
						window.dispatchEvent( statusEvent );
					}

					// Dispatch event to update related notice fields (e.g. connection_status).
					if ( data.updated_fields ) {
						var updateEvent = new CustomEvent( BB_EVENTS.FIELD_VALUE_UPDATE, {
							detail: {
								fields: data.updated_fields,
								is_connected: data.is_connected,
							},
						} );
						window.dispatchEvent( updateEvent );
					}

					// Show warning message if key saved but validation had issues.
					if ( data.message && data.has_warning ) {
						setWarningMessage( data.message );
					}
				} else {
					setErrorMessage( ( result.data && result.data.message ) || __( 'Connection failed.', 'buddyboss' ) );
				}
			} )
			.catch( function( err ) {
				// Ignore abort errors.
				if ( err && 'AbortError' === err.name ) {
					return;
				}
				setIsLoading( false );
				setErrorMessage( __( 'Connection failed. Please try again.', 'buddyboss' ) );
			} );
	}

	// Determine if button should be disabled.
	var isButtonDisabled = disabled || isLoading;
	if ( ! isButtonOnly && ! connected && ! inputValue ) {
		isButtonDisabled = true;
	}

	return (
		<div className="bb-admin-settings-field__input-button">
			<div className="bb-admin-settings-field__input-button-row">
				{ ! isButtonOnly && (
					<div className="bb-admin-settings-field__input-button-input">
						<input
							ref={ inputRef }
							type="text"
							value={ inputValue }
							placeholder={ field.placeholder || '' }
							onChange={ function( e ) {
								setInputValue( e.target.value );
								setErrorMessage( '' );
								setWarningMessage( '' );
							} }
							disabled={ disabled || connected }
							className="bb-admin-settings-field__input-button-text"
						/>
					</div>
				) }
				<button
					type="button"
					className={ 'bb-admin-settings-field__input-button-btn' + ( connected ? ' bb-admin-settings-field__input-button-btn--connected' : '' ) }
					onClick={ handleButtonClick }
					disabled={ isButtonDisabled }
				>
					{ isLoading ? __( 'Saving...', 'buddyboss' ) : buttonLabel }
				</button>
			</div>
			{ errorMessage && (
				<p className="bb-admin-settings-field__input-button-error">{ errorMessage }</p>
			) }
			{ warningMessage && (
				<p className="bb-admin-settings-field__input-button-warning">{ warningMessage }</p>
			) }
		</div>
	);
}

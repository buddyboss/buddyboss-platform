/**
 * BuddyBoss Admin Settings 2.0 - Input Button Field Component
 *
 * Renders a text input with an action button (e.g., Connect/Disconnect for API keys).
 * Supports connection status tracking and updates section status badges via custom events.
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
 * @returns {JSX.Element} Input button field component.
 */
export function InputButtonField( { field, value, onChange, disabled } ) {
	var isConnected = field.is_connected || false;
	var initialButtonLabel = field.button_label || __( 'Connect', 'buddyboss' );

	var inputRef = useRef( null );
	var abortRef = useRef( null );
	var [ inputValue, setInputValue ] = useState( value || '' );
	var [ buttonLabel, setButtonLabel ] = useState( initialButtonLabel );
	var [ connected, setConnected ] = useState( isConnected );
	var [ isLoading, setIsLoading ] = useState( false );
	var [ errorMessage, setErrorMessage ] = useState( '' );
	var [ warningMessage, setWarningMessage ] = useState( '' );

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

		var formData = new FormData();
		formData.append( 'action', 'bb_media_giphy_connect' );
		formData.append( 'nonce', window.bbAdminData.ajaxNonce );
		formData.append( 'connect_action', connected ? 'disconnect' : 'connect' );
		formData.append( 'api_key', inputValue );

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
					setButtonLabel( data.button_label );

					// Invalidate feature cache so navigating away and back fetches fresh data.
					invalidateFeatureCache();

					if ( ! data.is_connected ) {
						// Disconnected: clear the input value.
						setInputValue( '' );
						onChange( field.name, '' );
					}

					// Dispatch custom event to update section status badge.
					if ( data.status ) {
						var event = new CustomEvent( BB_EVENTS.SECTION_STATUS_UPDATE, {
							detail: {
								fieldName: field.name,
								status: data.status,
							},
						} );
						window.dispatchEvent( event );
					}

					// Show warning message if key saved but validation had issues.
					if ( data.message && data.has_warning ) {
						setWarningMessage( data.message );
					}
				} else {
					setErrorMessage( result.data?.message || __( 'Connection failed.', 'buddyboss' ) );
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

	return (
		<div className="bb-admin-settings-field__input-button">
			<div className="bb-admin-settings-field__input-button-row">
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
				<button
					type="button"
					className={ 'bb-admin-settings-field__input-button-btn' + ( connected ? ' bb-admin-settings-field__input-button-btn--disconnect' : '' ) }
					onClick={ handleButtonClick }
					disabled={ disabled || isLoading || ( ! connected && ! inputValue ) }
				>
					{ isLoading ? __( 'Connecting...', 'buddyboss' ) : buttonLabel }
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

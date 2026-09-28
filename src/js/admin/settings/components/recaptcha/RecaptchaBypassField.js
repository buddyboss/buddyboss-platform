/**
 * BuddyBoss Admin Settings 2.0 - reCAPTCHA Bypass Field Component
 *
 * Renders a checkbox + inline text input + bypass URL with copy button.
 * Matches the legacy reCAPTCHA bypass field behavior.
 *
 * @package BuddyBoss\Core\Administration
 * @since BuddyBoss [BBVERSION]
 */

import { useState, useEffect, useRef } from '@wordpress/element';
import { ToggleControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { safeUrl } from '../../utils/sanitize';

/**
 * RecaptchaBypassField Component
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param {Object}   props          Component props.
 * @param {Object}   props.field    Field configuration object.
 * @param {*}        props.value    Current checkbox value (0 or 1).
 * @param {Object}   props.values   All current form values.
 * @param {Function} props.onChange Change handler (fieldName, newValue).
 * @param {boolean}  props.disabled Whether the field is disabled.
 * @returns {JSX.Element} Bypass field component.
 */
export function RecaptchaBypassField( props ) {
	var field = props.field;
	var value = props.value;
	var values = props.values || {};
	var onChange = props.onChange;
	var disabled = props.disabled;

	var isChecked = !! value && value !== '0' && value !== 0;

	var bypassText = values.bb_recaptcha_bypass_text || '';
	var bypassTextState = useState( bypassText );
	var localBypassText = bypassTextState[ 0 ];
	var setLocalBypassText = bypassTextState[ 1 ];

	var copiedState = useState( false );
	var isCopied = copiedState[ 0 ];
	var setIsCopied = copiedState[ 1 ];

	var copiedTimerRef = useRef( null );

	// Sync bypass text from parent values.
	useEffect( function() {
		setLocalBypassText( values.bb_recaptcha_bypass_text || '' );
	}, [ values.bb_recaptcha_bypass_text ] );

	// Cleanup timer on unmount.
	useEffect( function() {
		return function() {
			if ( copiedTimerRef.current ) {
				clearTimeout( copiedTimerRef.current );
			}
		};
	}, [] );

	var loginUrl = ( window.bbAdminData && window.bbAdminData.loginUrl )
		? window.bbAdminData.loginUrl
		: ( window.location.origin + '/wp-login.php' );
	var bypassDomain = loginUrl + '?bypass_captcha=';
	var displayText = localBypassText || 'xxUNIQUE_STRINGXS';
	var bypassUrl = bypassDomain + displayText;
	var isValidLength = localBypassText.length >= 6 && localBypassText.length <= 10;

	function handleCheckboxChange() {
		var newValue = isChecked ? 0 : 1;
		onChange( field.name, newValue );
	}

	function handleTextChange( e ) {
		var newText = e.target.value;
		setLocalBypassText( newText );
		onChange( 'bb_recaptcha_bypass_text', newText );
	}

	function handleCopy() {
		if ( ! isValidLength ) {
			return;
		}
		if ( navigator.clipboard && navigator.clipboard.writeText ) {
			navigator.clipboard.writeText( bypassUrl );
		}
		setIsCopied( true );
		if ( copiedTimerRef.current ) {
			clearTimeout( copiedTimerRef.current );
		}
		copiedTimerRef.current = setTimeout( function() {
			setIsCopied( false );
		}, 2000 );
	}

	return (
		<div className="bb-admin-settings-field__recaptcha-bypass">
			<div className="bb-admin-settings-field__recaptcha-bypass-row">
				<ToggleControl
					label={ __( 'Allow bypass, enter a 6 to 10-character string to customize your URL', 'buddyboss' ) }
					checked={ isChecked }
					onChange={ function() { handleCheckboxChange(); } }
					disabled={ disabled }
					__nextHasNoMarginBottom
				/>
				<input
					type="text"
					value={ localBypassText }
					onChange={ handleTextChange }
					placeholder="stringxs"
					minLength={ 6 }
					maxLength={ 10 }
					disabled={ disabled || ! isChecked }
					className="bb-admin-settings-field__recaptcha-bypass-text"
				/>
			</div>
			<p className="bb-admin-settings-field__recaptcha-bypass-description">
				{ __( 'The bypass URL enables you to bypass reCAPTCHA in case of issues. We recommend keeping the link below securely stored for accessing your site.', 'buddyboss' ) }
			</p>
			{ isChecked && (
				<div className={ 'bb-admin-settings-field__recaptcha-bypass-url' + ( ! isValidLength ? ' bb-admin-settings-field__recaptcha-bypass-url--invalid' : '' ) }>
					<a
						href={ safeUrl( bypassUrl ) }
						className="bb-admin-settings-field__recaptcha-bypass-link"
						target="_blank"
						rel="noopener noreferrer"
					>
						{ bypassUrl }
					</a>
					{ isValidLength && (
						<button
							type="button"
							className="bb-admin-settings-field__recaptcha-bypass-copy"
							onClick={ handleCopy }
							title={ isCopied ? __( 'Copied', 'buddyboss' ) : __( 'Copy', 'buddyboss' ) }
						>
							<i className={ isCopied ? 'bb-icon-l bb-icon-check' : 'bb-icon-l bb-icon-copy' } />
						</button>
					) }
				</div>
			) }
		</div>
	);
}

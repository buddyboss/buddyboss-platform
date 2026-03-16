/**
 * BuddyBoss Admin Settings 2.0 - Password Field Component
 *
 * Renders a text input with a show/hide eye toggle for sensitive values
 * like API keys and credentials.
 *
 * @package BuddyBoss\Core\Administration
 * @since BuddyBoss [BBVERSION]
 */

import { useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

/**
 * Password Field Component
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param {Object}   props          Component props.
 * @param {Object}   props.field    Field configuration object.
 * @param {string}   props.value    Current field value.
 * @param {Function} props.onChange Change handler.
 * @param {boolean}  props.disabled Whether the field is disabled.
 * @returns {JSX.Element} Password field component.
 */
export function PasswordField( props ) {
	var field = props.field;
	var value = props.value;
	var onChange = props.onChange;
	var disabled = props.disabled;

	var visibleState = useState( false );
	var isVisible = visibleState[ 0 ];
	var setIsVisible = visibleState[ 1 ];

	return (
		<div className="bb-admin-settings-field__password">
			<input
				type={ isVisible ? 'text' : 'password' }
				value={ value || '' }
				placeholder={ field.placeholder || '' }
				aria-label={ field.label || field.name }
				onChange={ function( e ) {
					onChange( field.name, e.target.value );
				} }
				disabled={ disabled }
				className="bb-admin-settings-field__password-input"
			/>
			<button
				type="button"
				className={ 'bb-admin-settings-field__password-toggle' + ( isVisible ? ' bb-admin-settings-field__password-toggle--visible' : '' ) }
				onClick={ function() {
					setIsVisible( ! isVisible );
				} }
				aria-label={ isVisible ? __( 'Hide value', 'buddyboss' ) : __( 'Show value', 'buddyboss' ) }
				tabIndex={ 0 }
				disabled={ disabled }
			>
				{ isVisible ? (
					<svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
						<path d="M17.94 17.94A10.07 10.07 0 0112 20c-7 0-11-8-11-8a18.45 18.45 0 015.06-5.94M9.9 4.24A9.12 9.12 0 0112 4c7 0 11 8 11 8a18.5 18.5 0 01-2.16 3.19m-6.72-1.07a3 3 0 11-4.24-4.24" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"/>
						<line x1="1" y1="1" x2="23" y2="23" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"/>
					</svg>
				) : (
					<svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
						<path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8S1 12 1 12z" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"/>
						<circle cx="12" cy="12" r="3" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"/>
					</svg>
				) }
			</button>
		</div>
	);
}

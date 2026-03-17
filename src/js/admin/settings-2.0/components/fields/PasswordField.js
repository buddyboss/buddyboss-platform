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
					<i className="bb-icons-rl-eye-slash"></i>
				) : (
					<i className="bb-icons-rl-eye"></i>
				) }
			</button>
		</div>
	);
}

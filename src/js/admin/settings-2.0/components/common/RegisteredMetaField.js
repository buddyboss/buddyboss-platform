/**
 * BuddyBoss Admin Settings 2.0 - Registered Meta Field
 *
 * Shared component that renders a single registry field based on its type.
 * Supports: text, number, url, select, radio, richtext, readonly.
 *
 * @package BuddyBoss\Core\Administration
 * @since BuddyBoss [BBVERSION]
 */

import {
	TextControl,
	SelectControl,
	RadioControl,
} from '@wordpress/components';

import { RichTextEditor } from './RichTextEditor';

/**
 * Render a single registered field based on its type.
 *
 * @param {Object}   props            Component props.
 * @param {Object}   props.field      Field data from the registry.
 * @param {*}        props.value      Current value.
 * @param {Function} props.onChange    Change handler.
 * @param {number}   props.activityId Activity ID (used for richtext editor key).
 * @returns {JSX.Element|null} Field component or null.
 */
export function RegisteredMetaField( { field, value, onChange, activityId } ) {
	if ( ! field.visible ) {
		return null;
	}

	// Read-only field (e.g. Activity History).
	if ( 'readonly' === field.type ) {
		// History-style: value is an object with time_since + message.
		if ( value && 'object' === typeof value && value.time_since ) {
			return (
				<div className="bb-admin-meta-field__history">
					<h4 className="bb-admin-meta-field__history-title">
						{ field.label }
					</h4>
					<div className="bb-admin-meta-field__history-entry">
						<span className="bb-admin-meta-field__history-time">{ value.time_since }</span>
						{ ' – ' }
						<span className="bb-admin-meta-field__history-message">{ value.message }</span>
					</div>
				</div>
			);
		}

		// Generic read-only: simple string display.
		if ( value ) {
			return (
				<div className="bb-admin-meta-field__readonly-field">
					<label className="bb-admin-meta-field__label">{ field.label }</label>
					<span className="bb-admin-meta-field__readonly-value">{ String( value ) }</span>
				</div>
			);
		}

		return null;
	}

	// Rich text field (TinyMCE).
	if ( 'richtext' === field.type ) {
		return (
			<RichTextEditor
				key={ field.id + '-' + activityId }
				id={ 'bb-admin-edit-' + field.id + '-' + activityId }
				label={ field.label }
				value={ null != value ? String( value ) : '' }
				onChange={ onChange }
			/>
		);
	}

	// Select field.
	if ( 'select' === field.type ) {
		var options = field.options && Array.isArray( field.options ) ? field.options : [];
		if ( 0 === options.length ) {
			return null;
		}

		return (
			<SelectControl
				label={ field.label }
				value={ String( null != value ? value : '' ) }
				options={ options }
				onChange={ onChange }
				__nextHasNoMarginBottom
			/>
		);
	}

	// Radio field.
	if ( 'radio' === field.type ) {
		var radioOptions = field.options && Array.isArray( field.options ) ? field.options : [];
		if ( 0 === radioOptions.length ) {
			return null;
		}

		return (
			<RadioControl
				label={ field.label }
				selected={ String( null != value ? value : '' ) }
				options={ radioOptions }
				onChange={ onChange }
				__nextHasNoMarginBottom
			/>
		);
	}

	// Text / number / url fields.
	var inputType = 'text';
	if ( 'number' === field.type ) {
		inputType = 'number';
	} else if ( 'url' === field.type ) {
		inputType = 'url';
	}

	return (
		<TextControl
			label={ field.label }
			value={ null != value ? String( value ) : '' }
			onChange={ onChange }
			type={ inputType }
			__nextHasNoMarginBottom
		/>
	);
}

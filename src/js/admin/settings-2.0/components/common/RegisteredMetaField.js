/**
 * BuddyBoss Admin Settings 2.0 - Registered Meta Field
 *
 * Shared component that renders a single registry field based on its type.
 * Supports: text, number, url, select, richtext, readonly.
 *
 * @package BuddyBoss\Core\Administration
 * @since BuddyBoss [BBVERSION]
 */

import {
	TextControl,
	TextareaControl,
	SelectControl,
} from '@wordpress/components';

import { RichTextEditor } from './RichTextEditor';
import { safeUrl } from '../../utils/sanitize';

/**
 * Render a single registered field based on its type.
 *
 * @param {Object}   props            Component props.
 * @param {Object}   props.field      Field data from the registry.
 * @param {*}        props.value      Current value.
 * @param {Function} props.onChange    Change handler.
 * @param {number}   props.activityId Activity ID (used for richtext editor key).
 * @param {number}   props.itemId     Generic item ID (used when activityId is not applicable).
 * @returns {JSX.Element|null} Field component or null.
 */
export function RegisteredMetaField( { field, value, onChange, activityId, itemId } ) {
	var editorItemId = activityId || itemId || 0;
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
				key={ field.id + '-' + editorItemId }
				id={ 'bb-admin-edit-' + field.id + '-' + editorItemId }
				label={ field.label }
				value={ null != value ? String( value ) : '' }
				onChange={ onChange }
			/>
		);
	}

	// Textarea field.
	if ( 'textarea' === field.type ) {
		return (
			<div>
				<TextareaControl
					label={ field.label }
					value={ null != value ? String( value ) : '' }
					onChange={ onChange }
					rows={ 4 }
					placeholder={ field.placeholder || '' }
					__nextHasNoMarginBottom
				/>
				{ field.description && (
					<p className="bb-admin-meta-field__description">{ field.description }</p>
				) }
			</div>
		);
	}

	// Permalink field (slug with URL preview below input).
	if ( 'permalink' === field.type ) {
		var baseUrl = ( field.extra_data && field.extra_data.base_url ) ? field.extra_data.base_url : '';
		var slugValue = null != value ? String( value ) : '';

		return (
			<div className="bb-admin-meta-field__permalink-field">
				<label className="bb-admin-meta-field__label">{ field.label }</label>
				<TextControl
					value={ slugValue }
					onChange={ onChange }
					placeholder={ field.placeholder || '' }
					__nextHasNoMarginBottom
				/>
				{ baseUrl && (
					<div className="bb-admin-meta-field__permalink-preview">
						<a
							href={ safeUrl( baseUrl + slugValue + '/' ) }
							target="_blank"
							rel="noopener noreferrer"
						>
							{ baseUrl }
							<strong>{ slugValue }</strong>
							{ '/' }
						</a>
					</div>
				) }
			</div>
		);
	}

	// Checkbox toggle field (e.g. "Allow this group to have a discussion forum").
	if ( 'checkbox' === field.type ) {
		var isChecked = !! value && '0' !== String( value ) && 0 !== value;

		return (
			<div className="bb-admin-meta-field__checkbox-field">
				<label className="bb-admin-meta-field__checkbox-option" htmlFor={ field.id + '-' + editorItemId }>
					<input
						type="checkbox"
						id={ field.id + '-' + editorItemId }
						checked={ isChecked }
						onChange={ function ( e ) {
							onChange( e.target.checked ? '1' : '0' );
						} }
					/>
					<span className="bb-admin-meta-field__checkbox-label">{ field.label }</span>
				</label>
				{ field.description && (
					<p className="bb-admin-meta-field__description">{ field.description }</p>
				) }
			</div>
		);
	}

	// Radio field.
	if ( 'radio' === field.type ) {
		var radioOptions = field.options && Array.isArray( field.options ) ? field.options : [];
		if ( 0 === radioOptions.length ) {
			return null;
		}

		return (
			<div className="bb-admin-meta-field__radio-field">
				<label className="bb-admin-meta-field__label">{ field.label }</label>
				<div className="bb-admin-meta-field__radio-options">
					{ radioOptions.map( function ( option ) {
						var radioId = field.id + '-' + option.value + '-' + editorItemId;
						return (
							<label key={ option.value } className="bb-admin-meta-field__radio-option" htmlFor={ radioId }>
								<input
									type="radio"
									id={ radioId }
									name={ field.id + '-' + editorItemId }
									value={ option.value }
									checked={ String( value ) === String( option.value ) }
									onChange={ function () {
										onChange( option.value );
									} }
								/>
								<span className="bb-admin-meta-field__radio-label">{ option.label }</span>
								{ option.description && (
									<span className="bb-admin-meta-field__radio-description">{ option.description }</span>
								) }
							</label>
						);
					} ) }
				</div>
				{ field.description && (
					<p className="bb-admin-meta-field__description">{ field.description }</p>
				) }
			</div>
		);
	}

	// Select field.
	if ( 'select' === field.type ) {
		var options = field.options && Array.isArray( field.options ) ? field.options : [];
		if ( 0 === options.length ) {
			return null;
		}

		return (
			<div className="bb-admin-meta-field__select-field">
				<SelectControl
					label={ field.label }
					value={ String( null != value ? value : '' ) }
					options={ options }
					onChange={ onChange }
					__nextHasNoMarginBottom
				/>
				{ field.description && (
					<p className="bb-admin-meta-field__description">{ field.description }</p>
				) }
			</div>
		);
	}

	// Text / number / url fields.
	var inputType = 'text';
	if ( 'number' === field.type ) {
		inputType = 'number';
	} else if ( 'url' === field.type ) {
		inputType = 'url';
	}

	var showDescription = field.description && 'half' !== field.layout;

	return (
		<div>
			<TextControl
				label={ field.label }
				value={ null != value ? String( value ) : '' }
				onChange={ onChange }
				type={ inputType }
				placeholder={ field.placeholder || '' }
				__nextHasNoMarginBottom
			/>
			{ showDescription && (
				<p className="bb-admin-meta-field__description">{ field.description }</p>
			) }
		</div>
	);
}

/**
 * BuddyBoss Admin Settings 2.0 - ImageRadioField Component
 *
 * Visual radio cards (like Default Group Cover Image or Avatar).
 *
 * @package BuddyBoss\Core\Administration
 * @since BuddyBoss [BBVERSION]
 */

import { useState, useEffect } from '@wordpress/element';
import { ImageUploadField } from './ImageUploadField';

/**
 * Shared layout preview for card and header style pickers.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param {Object} props           Component props.
 * @param {string} props.type      Preview type: 'card' or 'header'.
 * @param {string} props.alignment Layout alignment: 'left' or 'centered'.
 * @returns {JSX.Element} Layout preview component.
 */
function LayoutPreview( { type, alignment } ) {
	var base = 'bb-admin-settings-field__' + type + '-preview';
	return (
		<div className={ base + ' ' + base + '--' + alignment }>
			<div className={ base + '-cover' }></div>
			<div className={ base + '-content' }>
				<div className={ base + '-avatar' }>
					<span className="dashicons dashicons-groups"></span>
				</div>
				<div className={ base + '-lines' }>
					<div className={ base + '-line ' + base + '-line--short' }></div>
					<div className={ base + '-line ' + base + '-line--long' }></div>
				</div>
			</div>
		</div>
	);
}

/**
 * Image preview renderers keyed by image identifier.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @type {Object<string, Function>}
 */
var IMAGE_PREVIEWS = {
	'cover-buddyboss': function () {
		return (
			<div className="bb-admin-settings-field__image-radio-icon bb-admin-settings-field__image-radio-icon--buddyboss">
				<span className="dashicons dashicons-format-image"></span>
			</div>
		);
	},
	'cover-none': function () {
		return (
			<div className="bb-admin-settings-field__image-radio-icon bb-admin-settings-field__image-radio-icon--none">
				<span className="dashicons dashicons-no-alt"></span>
			</div>
		);
	},
	'cover-custom': function () {
		return (
			<div className="bb-admin-settings-field__image-radio-icon bb-admin-settings-field__image-radio-icon--custom">
				<span className="dashicons dashicons-admin-generic"></span>
			</div>
		);
	},
	'avatar-buddyboss': function () {
		return (
			<div className="bb-admin-settings-field__image-radio-icon bb-admin-settings-field__image-radio-icon--avatar-group">
				<span className="dashicons dashicons-groups"></span>
			</div>
		);
	},
	'avatar-name': function () {
		return (
			<div className="bb-admin-settings-field__image-radio-icon bb-admin-settings-field__image-radio-icon--avatar-name">
				<span className="bb-admin-settings-field__avatar-initials">BB</span>
			</div>
		);
	},
	'avatar-custom': function () {
		return (
			<div className="bb-admin-settings-field__image-radio-icon bb-admin-settings-field__image-radio-icon--custom">
				<span className="dashicons dashicons-admin-generic"></span>
			</div>
		);
	},
	'card-left-group': function () {
		return <LayoutPreview type="card" alignment="left" />;
	},
	'card-centered-group': function () {
		return <LayoutPreview type="card" alignment="centered" />;
	},
	'header-left-group': function () {
		return <LayoutPreview type="header" alignment="left" />;
	},
	'header-centered-group': function () {
		return <LayoutPreview type="header" alignment="centered" />;
	},
};

/**
 * ImageRadioField component.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param {Object}   props          Component props.
 * @param {Object}   props.field    Field definition.
 * @param {*}        props.value    Current field value.
 * @param {Function} props.onChange Change handler (fieldName, newValue).
 * @param {boolean}  props.disabled Whether the field is disabled.
 * @returns {JSX.Element} ImageRadioField component.
 */
export function ImageRadioField( { field, value, onChange, disabled } ) {
	var [ selected, setSelected ] = useState( value );
	var [ uploadUrl, setUploadUrl ] = useState( field.upload_url || '' );

	// Sync local state when parent value changes (e.g., after save or initial load).
	useEffect( function () {
		setSelected( value );
	}, [ value ] );

	var handleClick = function ( optionValue ) {
		setSelected( optionValue );
		onChange( field.name, optionValue );
	};

	var uploadConditional = field.upload_config && field.upload_config.conditional;
	var showUpload = uploadConditional && selected === uploadConditional.value;

	return (
		<div className="bb-admin-settings-field__image-radio-wrapper">
		<div className="bb-admin-settings-field__image-radio">
			{ ( field.options || [] ).map( function ( option ) {
				return (
					<button
						key={ option.value }
						type="button"
						className={ 'bb-admin-settings-field__image-radio-option' + ( selected === option.value ? ' bb-admin-settings-field__image-radio-option--selected' : '' ) }
						onClick={ function () { handleClick( option.value ); } }
						disabled={ disabled }
					>
						<div className="bb-admin-settings-field__image-radio-preview">
							{ IMAGE_PREVIEWS[ option.image ] && IMAGE_PREVIEWS[ option.image ]() }
						</div>
						<span className="bb-admin-settings-field__image-radio-label">{ option.label }</span>
					</button>
				);
			} ) }
		</div>
		{ showUpload && (
			<ImageUploadField
				uploadConfig={ field.upload_config }
				uploadUrl={ uploadUrl }
				onUpload={ function ( newUrl ) {
					setUploadUrl( newUrl );
				} }
				onRemove={ function () {
					setUploadUrl( '' );
				} }
				disabled={ disabled }
			/>
		) }
		</div>
	);
}

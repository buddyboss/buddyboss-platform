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
// Module-level cache for upload URLs — survives component unmount/remount cycles
// caused by conditional toggling (e.g., disable then re-enable Group Avatars).
var uploadUrlCache = {};

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
				<svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 48 48" fill="none">
					<path d="M24 24C27.746 24 31.14 25.24 33.63 26.974C35.996 28.624 38 31.076 38 33.714C38 35.162 37.382 36.362 36.408 37.254C35.492 38.096 34.296 38.642 33.064 39.014C30.602 39.76 27.36 40 24 40C20.64 40 17.398 39.76 14.936 39.014C13.704 38.642 12.508 38.096 11.59 37.254C10.62 36.364 10 35.164 10 33.716C10 31.078 12.004 28.626 14.37 26.976C16.86 25.24 20.254 24 24 24ZM38 26C40.088 26 41.984 26.69 43.386 27.666C44.666 28.56 46 30.046 46 31.858C46 32.892 45.55 33.75 44.88 34.364C44.268 34.926 43.512 35.256 42.822 35.464C41.882 35.748 40.772 35.894 39.62 35.958C39.864 35.268 40 34.518 40 33.714C40 30.644 38.082 28.036 35.936 26.226C36.6138 26.0763 37.3059 26.0005 38 26ZM10 26C10.716 26.0027 11.404 26.078 12.064 26.226C9.92 28.036 8 30.644 8 33.714C8 34.518 8.136 35.268 8.38 35.958C7.228 35.894 6.12 35.748 5.178 35.464C4.488 35.256 3.732 34.926 3.118 34.364C2.76605 34.0487 2.48457 33.6627 2.29192 33.2312C2.09928 32.7998 1.9998 32.3325 2 31.86C2 30.05 3.332 28.562 4.614 27.668C6.19972 26.5809 8.07743 25.9994 10 26ZM37 14C38.3261 14 39.5979 14.5268 40.5355 15.4645C41.4732 16.4021 42 17.6739 42 19C42 20.3261 41.4732 21.5979 40.5355 22.5355C39.5979 23.4732 38.3261 24 37 24C35.6739 24 34.4021 23.4732 33.4645 22.5355C32.5268 21.5979 32 20.3261 32 19C32 17.6739 32.5268 16.4021 33.4645 15.4645C34.4021 14.5268 35.6739 14 37 14ZM11 14C12.3261 14 13.5979 14.5268 14.5355 15.4645C15.4732 16.4021 16 17.6739 16 19C16 20.3261 15.4732 21.5979 14.5355 22.5355C13.5979 23.4732 12.3261 24 11 24C9.67392 24 8.40215 23.4732 7.46447 22.5355C6.52678 21.5979 6 20.3261 6 19C6 17.6739 6.52678 16.4021 7.46447 15.4645C8.40215 14.5268 9.67392 14 11 14ZM24 6C26.1217 6 28.1566 6.84285 29.6569 8.34315C31.1571 9.84344 32 11.8783 32 14C32 16.1217 31.1571 18.1566 29.6569 19.6569C28.1566 21.1571 26.1217 22 24 22C21.8783 22 19.8434 21.1571 18.3431 19.6569C16.8429 18.1566 16 16.1217 16 14C16 11.8783 16.8429 9.84344 18.3431 8.34315C19.8434 6.84285 21.8783 6 24 6Z" fill="#999999"></path>
				</svg>
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

	// Use cached URL if available (survives unmount/remount), otherwise fall back to server value.
	var initialUrl = uploadUrlCache[ field.name ] !== undefined
		? uploadUrlCache[ field.name ]
		: ( field.upload_url || '' );
	var [ uploadUrl, setUploadUrl ] = useState( initialUrl );

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
					uploadUrlCache[ field.name ] = newUrl;
					setUploadUrl( newUrl );
				} }
				onRemove={ function () {
					uploadUrlCache[ field.name ] = '';
					setUploadUrl( '' );
				} }
				disabled={ disabled }
			/>
		) }
		</div>
	);
}

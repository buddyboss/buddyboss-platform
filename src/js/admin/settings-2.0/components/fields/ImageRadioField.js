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
import coverBuddybossImage from '../../images/cover-image.png';

/**
 * Shared layout preview for card and header style pickers.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param {Object} props           Component props.
 * @param {string} props.alignment Layout alignment: 'left' or 'centered'.
 * @returns {JSX.Element} Layout preview component.
 */
function LayoutPreview( { alignment } ) {
	var base = 'bb-admin-settings-field__header-preview';
	return (
		<div className={ base + ' ' + base + '--' + alignment }>
			<div className={ base + '-cover' }></div>
			<div className={ base + '-content' }>
				<div className={ base + '-avatar' }>
					<svg xmlns="http://www.w3.org/2000/svg" width="22" height="17" viewBox="0 0 22 17" fill="none"><path d="M11 9C12.873 9 14.57 9.62 15.815 10.487C16.998 11.312 18 12.538 18 13.857C18 14.581 17.691 15.181 17.204 15.627C16.746 16.048 16.148 16.321 15.532 16.507C14.301 16.88 12.68 17 11 17C9.32 17 7.699 16.88 6.468 16.507C5.852 16.321 5.254 16.048 4.795 15.627C4.31 15.182 4 14.582 4 13.858C4 12.539 5.002 11.313 6.185 10.488C7.43 9.62 9.127 9 11 9ZM18 10C19.044 10 19.992 10.345 20.693 10.833C21.333 11.28 22 12.023 22 12.929C22 13.446 21.775 13.875 21.44 14.182C21.134 14.463 20.756 14.628 20.411 14.732C19.941 14.874 19.386 14.947 18.81 14.979C18.932 14.634 19 14.259 19 13.857C19 12.322 18.041 11.018 16.968 10.113C17.3069 10.0381 17.6529 10.0002 18 10ZM4 10C4.358 10.0013 4.702 10.039 5.032 10.113C3.96 11.018 3 12.322 3 13.857C3 14.259 3.068 14.634 3.19 14.979C2.614 14.947 2.06 14.874 1.589 14.732C1.244 14.628 0.866 14.463 0.559 14.182C0.383027 14.0244 0.242284 13.8314 0.145961 13.6156C0.0496383 13.3999 -9.78689e-05 13.1663 1.44582e-07 12.93C1.44582e-07 12.025 0.666 11.281 1.307 10.834C2.09986 10.2905 3.03871 9.9997 4 10ZM17.5 4C18.163 4 18.7989 4.26339 19.2678 4.73223C19.7366 5.20107 20 5.83696 20 6.5C20 7.16304 19.7366 7.79893 19.2678 8.26777C18.7989 8.73661 18.163 9 17.5 9C16.837 9 16.2011 8.73661 15.7322 8.26777C15.2634 7.79893 15 7.16304 15 6.5C15 5.83696 15.2634 5.20107 15.7322 4.73223C16.2011 4.26339 16.837 4 17.5 4ZM4.5 4C5.16304 4 5.79893 4.26339 6.26777 4.73223C6.73661 5.20107 7 5.83696 7 6.5C7 7.16304 6.73661 7.79893 6.26777 8.26777C5.79893 8.73661 5.16304 9 4.5 9C3.83696 9 3.20107 8.73661 2.73223 8.26777C2.26339 7.79893 2 7.16304 2 6.5C2 5.83696 2.26339 5.20107 2.73223 4.73223C3.20107 4.26339 3.83696 4 4.5 4ZM11 0C12.0609 0 13.0783 0.421427 13.8284 1.17157C14.5786 1.92172 15 2.93913 15 4C15 5.06087 14.5786 6.07828 13.8284 6.82843C13.0783 7.57857 12.0609 8 11 8C9.93913 8 8.92172 7.57857 8.17157 6.82843C7.42143 6.07828 7 5.06087 7 4C7 2.93913 7.42143 1.92172 8.17157 1.17157C8.92172 0.421427 9.93913 0 11 0Z" fill="#999999"/></svg>
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
				<img src={ coverBuddybossImage } alt="" />
			</div>
		);
	},
	'cover-none': function () {
		return (
			<div className="bb-admin-settings-field__image-radio-icon bb-admin-settings-field__image-radio-icon--none">
				<svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 48 48" fill="none"><path d="M39.0918 35.908C39.5145 36.3307 39.7519 36.9039 39.7519 37.5017C39.7519 38.0995 39.5145 38.6728 39.0918 39.0955C38.6691 39.5182 38.0958 39.7556 37.498 39.7556C36.9003 39.7556 36.327 39.5182 35.9043 39.0955L23.9999 27.1873L12.0918 39.0917C11.6691 39.5144 11.0958 39.7519 10.498 39.7519C9.90027 39.7519 9.32698 39.5144 8.90429 39.0917C8.4816 38.669 8.24414 38.0957 8.24414 37.498C8.24414 36.9002 8.48161 36.3269 8.90429 35.9042L20.8124 23.9998L8.90804 12.0917C8.48535 11.669 8.24789 11.0957 8.24789 10.498C8.24789 9.90019 8.48535 9.3269 8.90804 8.90422C9.33073 8.48153 9.90402 8.24406 10.5018 8.24406C11.0996 8.24406 11.6729 8.48153 12.0955 8.90422L23.9999 20.8123L35.908 8.90234C36.3307 8.47965 36.904 8.24219 37.5018 8.24219C38.0996 8.24219 38.6729 8.47965 39.0955 8.90234C39.5182 9.32503 39.7557 9.89832 39.7557 10.4961C39.7557 11.0939 39.5182 11.6672 39.0955 12.0898L27.1874 23.9998L39.0918 35.908Z" fill="#999999"/></svg>
			</div>
		);
	},
	'cover-custom': function () {
		return (
			<div className="bb-admin-settings-field__image-radio-icon bb-admin-settings-field__image-radio-icon--custom">
				<span className="bb-icons-rl-gear-six"></span>
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
				<span className="bb-icons-rl-gear-six"></span>
			</div>
		);
	},
	'header-left-group': function () {
		return <LayoutPreview alignment="left" />;
	},
	'header-centered-group': function () {
		return <LayoutPreview alignment="centered" />;
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
		<div className={ 'bb-admin-settings-field__image-radio' + ( showUpload ? ' bb-admin-settings-field__image-radio--with-divider' : '' ) }>
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

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
/**
 * Notification position preview for on-screen notifications.
 *
 * Shows a screen mockup with a notification bar at bottom-left or bottom-right.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param {Object} props           Component props.
 * @param {string} props.position  Position: 'left' or 'right'.
 * @returns {JSX.Element} Notification position preview.
 */
function NotificationPositionPreview( { position } ) {
	var base = 'bb-admin-settings-field__notification-position';
	return (
		<div className={ base + ' ' + base + '--' + position }>
			<div className={ base + '-bar' }></div>
		</div>
	);
}

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
		// Single-person silhouette per Figma's avatar tile mock — matches
		// the small-centred visual weight of the sibling tiles (Display
		// Name's "BB" initials and Custom's bb-icons-rl-gear-six). Path is
		// designed for the full 48×48 viewBox so the silhouette fills the
		// SVG element without internal whitespace:
		//
		//   - Head: circle radius 10, centred at (24, 16) → y=6 to y=26
		//   - Shoulder bell: from y=30 to y=44, x=4 to x=44 (~40 wide)
		//
		// 4px breathing room on every side keeps the icon balanced against
		// the gear's 48px font-glyph rendering in the same flex-centred
		// __image-radio-icon wrap.
		return (
			<div className="bb-admin-settings-field__image-radio-icon bb-admin-settings-field__image-radio-icon--avatar-group">
				<svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 48 48" fill="none" aria-hidden="true">
					<path d="M24 26C29.5228 26 34 21.5228 34 16C34 10.4772 29.5228 6 24 6C18.4772 6 14 10.4772 14 16C14 21.5228 18.4772 26 24 26ZM24 30C13.954 30 4 34.5294 4 41V44H44V41C44 34.5294 34.046 30 24 30Z" fill="#999999"/>
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
	'header-left-profile': function () {
		return <LayoutPreview alignment="left" />;
	},
	'header-centered-profile': function () {
		return <LayoutPreview alignment="centered" />;
	},
	'notification-position-left': function () {
		return <NotificationPositionPreview position="left" />;
	},
	'notification-position-right': function () {
		return <NotificationPositionPreview position="right" />;
	},
};

/**
 * ImageRadioField component.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param {Object}   props                 Component props.
 * @param {Object}   props.field            Field definition.
 * @param {*}        props.value            Current field value.
 * @param {Function} props.onChange         Change handler (fieldName, newValue).
 * @param {boolean}  props.disabled         Whether the field is disabled.
 * @param {string}   props.descriptionHtml  Sanitized HTML description to render between options and upload.
 * @returns {JSX.Element} ImageRadioField component.
 */
export function ImageRadioField( { field, value, onChange, disabled, descriptionHtml } ) {
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
		<div className={ 'bb-admin-settings-field__image-radio-wrapper' + ( field.name ? ' bb-admin-settings-field__image-radio-wrapper--' + field.name : '' ) }>
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
							{ IMAGE_PREVIEWS[ option.image ]
								? IMAGE_PREVIEWS[ option.image ]()
								: option.image && ( option.image.indexOf( 'http' ) === 0 || option.image.indexOf( '/' ) === 0 )
									? <img src={ option.image } alt={ option.label || '' } className="bb-admin-settings-field__image-radio-img" />
									: null
							}
						</div>
						<span className="bb-admin-settings-field__image-radio-label">{ option.label }</span>
					</button>
				);
			} ) }
		</div>
		{ descriptionHtml && (
			<p
				className={ 'bb-admin-settings-form__field-description bb-admin-settings-form__field-description--image-radio' + ( showUpload ? ' bb-admin-settings-field__image-radio--with-divider' : '' ) }
				dangerouslySetInnerHTML={{ __html: descriptionHtml }}
			/>
		) }
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

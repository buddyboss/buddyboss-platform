/**
 * BuddyBoss Admin Settings 2.0 - Profile Type Modal
 *
 * Modal for creating and editing profile/member types.
 *
 * @package BuddyBoss\Core\Administration
 * @since BuddyBoss [BBVERSION]
 */

import { useState, useEffect, useCallback, useMemo, useRef } from '@wordpress/element';
import {
	TextControl,
	CheckboxControl,
	SelectControl,
	RadioControl,
	Button,
	Modal,
	ToggleControl,
} from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { decodeEntities } from '@wordpress/html-entities';
import { createMemberType, updateMemberType } from '../../utils/ajax';
import { safeUrl } from '../../utils/sanitize';
import { AsyncSelectField } from '../fields/AsyncSelectField';

// Pinned options for the After Login / After Logout async selects. Listed in
// the same order as the legacy <select> (Default → Custom URL → ...pages) so
// the dedupe in AsyncSelectField produces a render order that matches the
// legacy admin pixel-for-pixel. Defined at module scope so the array reference
// is stable across renders — AsyncSelectField's mount-time resolve effect
// depends on it, and a fresh array each render would re-trigger the resolve.
// `var` to match the surrounding file's ES5-style declarations (DEFAULT_FORM_DATA
// etc.) per the project's WP JS coding-standards convention.
var REDIRECT_STATIC_OPTIONS = [
	{ value: '', label: __( 'Default', 'buddyboss-platform' ) },
	{ value: '0', label: __( 'Custom URL', 'buddyboss-platform' ) },
];

/**
 * Strip leading '#' from a hex color value.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param {string} hex Hex color with or without '#'.
 * @returns {string} Hex value without '#'.
 */
function stripHash( hex ) {
	return ( hex || '' ).replace( /^#/, '' );
}

/**
 * Ensure a hex color string has the '#' prefix.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param {string} hex Hex value with or without '#'.
 * @returns {string} Hex value with '#' prefix.
 */
function ensureHash( hex ) {
	if ( ! hex ) {
		return '';
	}
	return '#' === hex.charAt( 0 ) ? hex : '#' + hex;
}

/**
 * Default form data.
 *
 * @since BuddyBoss [BBVERSION]
 */
var DEFAULT_FORM_DATA = {
	name: '',
	singular_label: '',
	plural_label: '',
	enable_filter: 0,
	enable_remove: 0,
	enable_search_remove: 0,
	allow_messaging_without_connection: 0,
	enable_profile_field: 0,
	// Match legacy default: an unsaved/empty `_bp_member_type_enabled_group_type_create`
	// meta means "no restriction — all group types selectable" (see
	// `bp-core-admin-functions.php:1934-1938` in PROD-8676). The 'all' mode
	// in the save path writes an empty string back, which PHP stores as ''
	// → reads as array() → matches legacy "leave all unchecked" semantic.
	// Defaulting to 'none' here would silently hide the create-group type
	// picker on the frontend for every new profile type — a regression.
	group_type_create_mode: 'all',
	group_type_create: [],
	group_type_auto_join: [],
	wp_roles: [],
	login_redirection: '',
	custom_login_redirection: '',
	logout_redirection: '',
	custom_logout_redirection: '',
	visibility: 'publish',
	post_password: '',
	invite_member_types: [],
	label_color: {
		type: 'default',
		background_color: '',
		text_color: '',
	},
};

/**
 * Determine group type create mode from meta value.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param {*} value Meta value from PHP.
 * @returns {string} 'none', 'all', or 'specific'.
 */
function getGroupTypeCreateMode( value ) {
	// Legacy storage shape (see legacy `bp-core-admin-functions.php` and
	// the frontend gate in `groups/single/admin/group-settings.php`):
	//   ''  / array()     → no restriction, all group types selectable
	//   array( 'none' )   → hide the "What type of group is this?" picker
	//                       on the frontend create-group form entirely
	//   array( 'key1', … ) → restrict to those specific group types
	// React's three radio modes map onto these states 1:1.
	if ( Array.isArray( value ) && value.length > 0 ) {
		if ( -1 !== value.indexOf( 'none' ) ) {
			return 'none';
		}
		return 'specific';
	}
	return 'all';
}

/**
 * Profile Type Modal Component
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param {Object}   props                   Component props.
 * @param {boolean}  props.isOpen            Whether modal is open.
 * @param {Function} props.onClose           Close handler.
 * @param {Function} props.onSave            Save handler.
 * @param {Object}   props.memberType        Member type to edit (null for create).
 * @param {Array}    props.allMemberTypes     All member types (for Email Invites checkboxes).
 * @param {Array}    props.groupTypes         Available group types.
 * @param {Array}    props.wpRoles            Available WordPress roles.
 * @param {Array}    props.publishedPages     Published pages (for redirect dropdowns).
 * @returns {JSX.Element|null} Modal element or null.
 */
export function ProfileTypeModal( { isOpen, onClose, onSave, memberType, allMemberTypes, groupTypes, wpRoles, publishedPages } ) {
	var formDataState = useState( DEFAULT_FORM_DATA );
	var formData = formDataState[ 0 ];
	var setFormData = formDataState[ 1 ];

	var isSavingState = useState( false );
	var isSaving = isSavingState[ 0 ];
	var setIsSaving = isSavingState[ 1 ];

	var errorState = useState( '' );
	var error = errorState[ 0 ];
	var setError = errorState[ 1 ];

	var availableGroupTypes = groupTypes || [];
	var availableWpRoles = wpRoles || [];
	// Login/logout redirection key counters to force AsyncSelectField re-mount on modal open.
	var loginKeyState = useState( 0 );
	var loginKey = loginKeyState[ 0 ];
	var logoutKeyState = useState( 0 );
	var logoutKey = logoutKeyState[ 0 ];

	// Populate form data when editing.
	useEffect( function () {
		if ( ! isOpen ) {
			return;
		}

		if ( memberType ) {
			var labelColor = memberType.label_color || {};
			if ( 'object' !== typeof labelColor || Array.isArray( labelColor ) ) {
				labelColor = {};
			}

			var gtCreate = memberType.group_type_create || [];
			var gtAutoJoin = memberType.group_type_auto_join || [];
			var existingWpRoles = memberType.wp_roles || [];
			var existingInviteTypes = memberType.invite_member_types || [];

			setFormData( {
				name: decodeEntities( memberType.post_title || '' ),
				singular_label: decodeEntities( memberType.singular_label || '' ),
				plural_label: decodeEntities( memberType.plural_label || '' ),
				enable_filter: memberType.enable_filter || 0,
				enable_remove: memberType.enable_remove || 0,
				enable_search_remove: memberType.enable_search_remove || 0,
				allow_messaging_without_connection: memberType.allow_messaging_without_connection || 0,
				enable_profile_field: memberType.enable_profile_field || 0,
				group_type_create_mode: getGroupTypeCreateMode( gtCreate ),
				group_type_create: Array.isArray( gtCreate ) ? gtCreate.map( String ) : [],
				group_type_auto_join: Array.isArray( gtAutoJoin ) ? gtAutoJoin.map( String ) : [],
				invite_member_types: Array.isArray( existingInviteTypes ) ? existingInviteTypes.map( String ) : [],
				wp_roles: Array.isArray( existingWpRoles ) ? existingWpRoles : [],
				login_redirection: memberType.login_redirection || '',
				custom_login_redirection: memberType.custom_login_redirection || '',
				logout_redirection: memberType.logout_redirection || '',
				custom_logout_redirection: memberType.custom_logout_redirection || '',
				visibility: memberType.visibility || 'publish',
				has_password: !! memberType.has_password,
				post_password: '',
				label_color: {
					type: ( labelColor.type ) || 'default',
					background_color: ( labelColor.background_color ) || '',
					text_color: ( labelColor.text_color ) || '',
				},
			} );
		} else {
			setFormData( JSON.parse( JSON.stringify( DEFAULT_FORM_DATA ) ) );
		}

		setError( '' );
	}, [ isOpen, memberType ] );

	// Force AsyncSelectField re-mount when modal opens with new data.
	useEffect( function () {
		if ( isOpen ) {
			loginKeyState[ 1 ]( function ( prev ) { return prev + 1; } );
			logoutKeyState[ 1 ]( function ( prev ) { return prev + 1; } );
		}
	}, [ isOpen, memberType ] ); // eslint-disable-line react-hooks/exhaustive-deps

	// Update a field in form data.
	var updateField = useCallback( function ( field, value ) {
		setFormData( function ( prev ) {
			var updated = Object.assign( {}, prev );
			updated[ field ] = value;
			return updated;
		} );
	}, [] );

	// Update label color.
	var updateLabelColor = useCallback( function ( field, value ) {
		setFormData( function ( prev ) {
			var updated = Object.assign( {}, prev );
			var newColor = Object.assign( {}, prev.label_color );
			newColor[ field ] = value;
			updated.label_color = newColor;
			return updated;
		} );
	}, [] );

	// Toggle an item in a checkbox list.
	var toggleListItem = useCallback( function ( field, itemId ) {
		setFormData( function ( prev ) {
			var updated = Object.assign( {}, prev );
			var currentList = prev[ field ] || [];
			var idStr = String( itemId );
			var index = currentList.indexOf( idStr );

			if ( -1 === index ) {
				updated[ field ] = currentList.concat( [ idStr ] );
			} else {
				updated[ field ] = currentList.filter( function ( id ) {
					return id !== idStr;
				} );
			}

			return updated;
		} );
	}, [] );

	// Handle save.
	var handleSave = useCallback( function () {
		if ( ! formData.name.trim() ) {
			setError( __( 'Profile type name is required.', 'buddyboss-platform' ) );
			return;
		}

		setIsSaving( true );
		setError( '' );

		var data = {
			name: formData.name,
			singular_label: formData.singular_label,
			plural_label: formData.plural_label,
			enable_filter: formData.enable_filter,
			enable_remove: formData.enable_remove,
			enable_search_remove: formData.enable_search_remove,
			allow_messaging_without_connection: formData.allow_messaging_without_connection,
			enable_profile_field: formData.enable_profile_field,
			visibility: formData.visibility,
			login_redirection: formData.login_redirection,
			custom_login_redirection: safeUrl( formData.custom_login_redirection ),
			logout_redirection: formData.logout_redirection,
			custom_logout_redirection: safeUrl( formData.custom_logout_redirection ),
			'label_color[type]': formData.label_color.type,
		};

		if ( 'password_protected' === formData.visibility && formData.post_password ) {
			data.post_password = formData.post_password;
		}

		if ( 'custom' === formData.label_color.type ) {
			data[ 'label_color[background_color]' ] = formData.label_color.background_color;
			data[ 'label_color[text_color]' ] = formData.label_color.text_color;
		}

		// Group type create permissions. Match the legacy storage shape:
		//   'all'      → write empty so frontend sees "no restriction" and
		//                the create-group picker shows every type.
		//   'none'     → write array( 'none' ); frontend gate at
		//                `groups/single/admin/group-settings.php` reads
		//                this sentinel and hides the "What type of group
		//                is this?" picker entirely.
		//   'specific' → write the selected group-type keys; frontend
		//                filters the picker options by membership.
		// The empty-string marker (instead of omitting the key) is also
		// what PHP needs to write the meta — `update_member_type` gates
		// the write on `isset( $_POST['group_type_create'] )` and would
		// otherwise leave a stale value behind.
		if ( 'none' === formData.group_type_create_mode ) {
			data[ 'group_type_create[0]' ] = 'none';
		} else if ( 'specific' === formData.group_type_create_mode && formData.group_type_create.length > 0 ) {
			formData.group_type_create.forEach( function ( id, idx ) {
				data[ 'group_type_create[' + idx + ']' ] = id;
			} );
		} else {
			data[ 'group_type_create' ] = '';
		}

		// Group type auto join. Same empty-marker reason as group_type_create
		// above — PHP gates the meta write on `isset( $_POST[...] )`, so
		// unchecking every auto-join group type without this marker would
		// silently keep the prior selection.
		if ( formData.group_type_auto_join.length > 0 ) {
			formData.group_type_auto_join.forEach( function ( id, idx ) {
				data[ 'group_type_auto_join[' + idx + ']' ] = id;
			} );
		} else {
			data[ 'group_type_auto_join' ] = '';
		}

		// Email Invites — allowed member types.
		if ( formData.invite_member_types.length > 0 ) {
			formData.invite_member_types.forEach( function ( id, idx ) {
				data[ 'invite_member_types[' + idx + ']' ] = id;
			} );
		} else {
			// Send empty array so PHP knows the field was submitted (for conditional save).
			data[ 'invite_member_types' ] = '';
		}

		// WP roles. Same empty-marker reason as group_type_create above —
		// PHP gates the meta write on `isset( $_POST['wp_roles'] )` (see
		// `class-bb-admin-member-types-ajax.php` ~line 669), so picking
		// "None" without this marker would silently leave the prior role
		// assigned because the wire payload wouldn't carry the key at all.
		if ( formData.wp_roles.length > 0 ) {
			formData.wp_roles.forEach( function ( role, idx ) {
				data[ 'wp_roles[' + idx + ']' ] = role;
			} );
		} else {
			data[ 'wp_roles' ] = '';
		}

		var savePromise;
		if ( memberType && memberType.id ) {
			savePromise = updateMemberType( memberType.id, data );
		} else {
			savePromise = createMemberType( data );
		}

		savePromise
			.then( function ( response ) {
				setIsSaving( false );
				if ( response.success ) {
					if ( 'function' === typeof onSave ) {
						onSave();
					}
				} else {
					setError( ( response.data && response.data.message ) || __( 'Failed to save profile type.', 'buddyboss-platform' ) );
				}
			} )
			.catch( function () {
				setIsSaving( false );
				setError( __( 'Failed to save profile type.', 'buddyboss-platform' ) );
			} );
	}, [ formData, memberType, onSave ] );

	if ( ! isOpen ) {
		return null;
	}

	var isEditing = !! ( memberType && memberType.id );
	var modalTitle = isEditing
		? __( 'Edit Profile Type', 'buddyboss-platform' )
		: __( 'Add New Profile Type', 'buddyboss-platform' );

	return (
		<Modal
			title={ modalTitle }
			onRequestClose={ onClose }
			className="bb-admin-profile-type-modal bb-admin-settings-modal"
			shouldCloseOnClickOutside={ false }
		>
			<div className="bb-admin-profile-type-modal__body bb-admin-settings-modal__body">
				{ error && (
					<div className="bb-admin-settings-modal__error">
						{ error }
					</div>
				) }

				{/* Name */}
				<div className="bb-admin-profile-type-modal__section">
					<TextControl
						label={ __( 'Name', 'buddyboss-platform' ) }
						value={ formData.name }
						onChange={ function ( val ) { updateField( 'name', val ); } }
						required
					/>
				</div>

				{/* Labels — Singular first, Plural second (matching Figma) */}
				<div className="bb-admin-profile-type-modal__section">
					<TextControl
						label={ __( 'Singular Label', 'buddyboss-platform' ) }
						value={ formData.singular_label }
						onChange={ function ( val ) { updateField( 'singular_label', val ); } }
					/>
					<TextControl
						label={ __( 'Plural Label', 'buddyboss-platform' ) }
						value={ formData.plural_label }
						onChange={ function ( val ) { updateField( 'plural_label', val ); } }
					/>
				</div>

				{/* Members Directory Permissions */}
				<div className="bb-admin-profile-type-modal__section">
					<h4 className="bb-admin-profile-type-modal__section-title">
						{ __( 'Members Directory Permissions', 'buddyboss-platform' ) }
					</h4>
					<div className="bb-admin-profile-type-modal__checkbox-group">
						<CheckboxControl
							label={ __( 'Display this profile type in "Types" filter in Members Directory', 'buddyboss-platform' ) }
							checked={ !! formData.enable_filter }
							onChange={ function ( val ) { updateField( 'enable_filter', val ? 1 : 0 ); } }
						/>
						<CheckboxControl
							label={ __( 'Hide all members of this type from Members Directory', 'buddyboss-platform' ) }
							checked={ !! formData.enable_remove }
							onChange={ function ( val ) { updateField( 'enable_remove', val ? 1 : 0 ); } }
						/>
						{ !! ( window.bbAdminData && window.bbAdminData.isSearchActive ) && (
							<CheckboxControl
								label={ __( 'Hide all members of this type from Network Search results', 'buddyboss-platform' ) }
								checked={ !! formData.enable_search_remove }
								onChange={ function ( val ) { updateField( 'enable_search_remove', val ? 1 : 0 ); } }
							/>
						) }
					</div>
				</div>

				{/* Messaging — only when messages + friends active and force-friendship-to-message enabled */}
				{ !! ( window.bbAdminData && window.bbAdminData.showMessagingWithoutConnectionFlag ) && (
					<div className="bb-admin-profile-type-modal__section">
						<h4 className="bb-admin-profile-type-modal__section-title">
							{ __( 'Messaging', 'buddyboss-platform' ) }
						</h4>
						<CheckboxControl
							label={ __( 'Allow this profile type to send and receive messages without being connected', 'buddyboss-platform' ) }
							checked={ !! formData.allow_messaging_without_connection }
							onChange={ function ( val ) { updateField( 'allow_messaging_without_connection', val ? 1 : 0 ); } }
						/>
					</div>
				) }

				{/* Profile Field */}
				<div className="bb-admin-profile-type-modal__section">
					<h4 className="bb-admin-profile-type-modal__section-title">
						{ __( 'Profile Field', 'buddyboss-platform' ) }
					</h4>
					<CheckboxControl
						label={ __( 'Allow users to select this profile type from the "Profile Type" dropdown', 'buddyboss-platform' ) }
						checked={ !! formData.enable_profile_field }
						onChange={ function ( val ) { updateField( 'enable_profile_field', val ? 1 : 0 ); } }
					/>
				</div>

				{/* Group Creation Permissions — only when Groups active, creation allowed, group types enabled */}
				{ availableGroupTypes.length > 0 && !! ( window.bbAdminData && window.bbAdminData.isGroupCreationAllowed ) && !! ( window.bbAdminData && window.bbAdminData.isGroupTypeCreationEnabled ) && (
					<div className="bb-admin-profile-type-modal__section">
						<h4 className="bb-admin-profile-type-modal__section-title">
							{ __( 'Group Creation Permissions', 'buddyboss-platform' ) }
						</h4>
						<RadioControl
							selected={ formData.group_type_create_mode }
							options={ [
								{ label: __( 'None', 'buddyboss-platform' ), value: 'none' },
								{ label: __( 'All Group Types', 'buddyboss-platform' ), value: 'all' },
								{ label: __( 'Specific Group Types', 'buddyboss-platform' ), value: 'specific' },
							] }
							onChange={ function ( val ) { updateField( 'group_type_create_mode', val ); } }
						/>
						{ 'specific' === formData.group_type_create_mode && (
							<div className="bb-admin-profile-type-modal__checkbox-grid">
								<p className="bb-admin-profile-type-modal__checkbox-desc">{ __('Select the group types this profile type can create.', 'buddyboss-platform') }</p>
								{ availableGroupTypes.map( function ( gt ) {
									var isChecked = -1 !== formData.group_type_create.indexOf( String( gt.id ) );
									return (
										<ToggleControl
											key={ gt.id }
											label={ decodeEntities( gt.name ) }
											checked={ isChecked }
											onChange={ function () {
												toggleListItem( 'group_type_create', gt.id );
											} }
										/>
									);
								} ) }
							</div>
						) }
					</div>
				) }

				{/* Group Type Membership Approval — only when Groups active, group types enabled, auto-join enabled */}
				{ availableGroupTypes.length > 0 && !! ( window.bbAdminData && window.bbAdminData.isGroupAutoJoinEnabled ) && (
					<div className="bb-admin-profile-type-modal__section">
						<h4 className="bb-admin-profile-type-modal__section-title">
							{ __( 'Group Type Membership Approval', 'buddyboss-platform' ) }
						</h4>
						<div className="bb-admin-profile-type-modal__checkbox-grid">
							{ availableGroupTypes.map( function ( gt ) {
								var isChecked = -1 !== formData.group_type_auto_join.indexOf( String( gt.id ) );
								return (
									<CheckboxControl
										key={ gt.id }
										label={ decodeEntities( gt.name ) }
										checked={ isChecked }
										onChange={ function () {
											toggleListItem( 'group_type_auto_join', gt.id );
										} }
									/>
								);
							} ) }
						</div>
						<p className="bb-admin-profile-type-modal__section-description" style={ { marginTop: 16, marginBottom: 0 } }>
							{ __( 'Automatically add members of this profile type to these group types after account activation. Hidden groups are excluded.', 'buddyboss-platform' ) }
						</p>
					</div>
				) }

				{/* Email Invites — only when Invites active and member type invites enabled */}
				{ ( allMemberTypes || [] ).length > 0 && !! ( window.bbAdminData && window.bbAdminData.isEmailInviteEnabled ) && (
					<div className="bb-admin-profile-type-modal__section">
						<h4 className="bb-admin-profile-type-modal__section-title">
							{ __( 'Email Invites', 'buddyboss-platform' ) }
						</h4>
						<div className="bb-admin-profile-type-modal__checkbox-grid">
							{ ( allMemberTypes || [] ).map( function ( mt ) {
								var isChecked = -1 !== formData.invite_member_types.indexOf( String( mt.id ) );
								return (
									<CheckboxControl
										key={ mt.id }
										label={ decodeEntities( mt.post_title ) }
										checked={ isChecked }
										onChange={ function () {
											toggleListItem( 'invite_member_types', mt.id );
										} }
									/>
								);
							} ) }
						</div>
						<p className="bb-admin-profile-type-modal__section-description" style={ { marginTop: 16, marginBottom: 0 } }>
							{ __( 'Select which profile types can be assigned when members choose a profile type for invited users.', 'buddyboss-platform' ) }
						</p>
					</div>
				) }

				{/* WordPress Role */}
				<div className="bb-admin-profile-type-modal__section">
					<h4 className="bb-admin-profile-type-modal__section-title">
						{ __( 'WordPress Role', 'buddyboss-platform' ) }
					</h4>
					<div className="bb-admin-profile-type-modal__radio-grid">
						<RadioControl
							selected={ formData.wp_roles.length ? formData.wp_roles[ 0 ] : 'subscriber' }
							options={ [ { value: 'none', label: __( 'None', 'buddyboss-platform' ) } ].concat( availableWpRoles ).map( function ( role ) {
								return {
									value: role.value,
									label: decodeEntities( role.label ),
								};
							} ) }
							onChange={ function ( value ) {
								// Store the selected radio value literally — including
								// the 'none' sentinel — so the stored meta matches the
								// legacy "(None)" save shape (array('none')). Earlier
								// code converted "None" to an empty array, which legacy
								// reads as "no value" and falls back to displaying
								// Subscriber checked (see legacy
								// `bp-core-admin-functions.php:2189`), causing a
								// cross-UI mismatch where React showed "None" while
								// legacy showed "Subscriber".
								updateField( 'wp_roles', [ value ] );
							} }
						/>
					</div>
					<p className="bb-admin-profile-type-modal__section-description" style={ { marginTop: 16, marginBottom: 0 } }>
						{ __( 'Auto-assign these WordPress roles to this profile type (includes existing users).', 'buddyboss-platform' ) }
					</p>
				</div>

				{/* After Login Redirection */}
				<div className="bb-admin-profile-type-modal__section bb-admin-profile-type-modal__section--no-border">
					<label className="components-base-control__label">
						{ __( 'After Login Redirection', 'buddyboss-platform' ) }
					</label>
					<AsyncSelectField
						key={ 'login-redirect-' + loginKey }
						value={ formData.login_redirection || '' }
						onChange={ function ( val ) { updateField( 'login_redirection', val ); } }
						asyncAction="bb_admin_search_published_pages"
						placeholder={ __( 'Select a page', 'buddyboss-platform' ) }
						// Pin "Custom URL" (legacy value '0') at the top of the
						// dropdown so users can switch into custom-URL mode the
						// same way the legacy <select> allowed. The component
						// also uses this list to resolve '0' to its label, so we
						// no longer need a separate initialLabel hint.
						staticOptions={ REDIRECT_STATIC_OPTIONS }
					/>
					{ '0' === formData.login_redirection && (
						<TextControl
							// hideLabelFromVision keeps the visual layout legacy-flat
							// (no sub-label between dropdown and input) while still
							// announcing "Custom URL" to assistive tech — the parent
							// <label> has no htmlFor binding so screen readers would
							// otherwise read this input as just "url".
							label={ __( 'Custom URL', 'buddyboss-platform' ) }
							hideLabelFromVision
							value={ formData.custom_login_redirection }
							onChange={ function ( val ) { updateField( 'custom_login_redirection', val ); } }
							placeholder={ __( 'Paste URL', 'buddyboss-platform' ) }
							type="url"
							__nextHasNoMarginBottom
						/>
					) }
				</div>

				<div className="bb-admin-profile-type-modal__section">
					{/* After Logout Redirection */}
					<label className="components-base-control__label">
						{ __( 'After Logout Redirection', 'buddyboss-platform' ) }
					</label>
					<AsyncSelectField
						key={ 'logout-redirect-' + logoutKey }
						value={ formData.logout_redirection || '' }
						onChange={ function ( val ) { updateField( 'logout_redirection', val ); } }
						asyncAction="bb_admin_search_published_pages"
						placeholder={ __( 'Select a page', 'buddyboss-platform' ) }
						staticOptions={ REDIRECT_STATIC_OPTIONS }
					/>
					{ '0' === formData.logout_redirection && (
						<TextControl
							// hideLabelFromVision keeps the visual layout legacy-flat
							// (no sub-label between dropdown and input) while still
							// announcing "Custom URL" to assistive tech — the parent
							// <label> has no htmlFor binding so screen readers would
							// otherwise read this input as just "url".
							label={ __( 'Custom URL', 'buddyboss-platform' ) }
							hideLabelFromVision
							value={ formData.custom_logout_redirection }
							onChange={ function ( val ) { updateField( 'custom_logout_redirection', val ); } }
							placeholder={ __( 'Paste URL', 'buddyboss-platform' ) }
							type="url"
							__nextHasNoMarginBottom
						/>
					) }
					<p className="bb-admin-profile-type-modal__section-description">
						{ __( 'Redirect this profile type. When you change the redirection settings in a profile type this will then take priority and override redirection global settings in', 'buddyboss-platform' ) }
						{ ' ' }
						<a href={ safeUrl( ( window.bbAdminData && window.bbAdminData.adminUrl ? window.bbAdminData.adminUrl : 'admin.php' ) + '?page=bb-settings&tab=registration&panel=login_redirects' ) } target="_blank" rel="noopener noreferrer">
							{ __( 'Settings - Login & Registration - Login Redirects', 'buddyboss-platform' ) }
						</a>
						{ '.' }
					</p>
				</div>

				{/* Visibility */}
				<div className="bb-admin-profile-type-modal__section">
					<SelectControl
						label={ __( 'Visibility', 'buddyboss-platform' ) }
						value={ formData.visibility }
						options={ [
							{ label: __( 'Public', 'buddyboss-platform' ), value: 'publish' },
							{ label: __( 'Private', 'buddyboss-platform' ), value: 'private' },
							{ label: __( 'Draft', 'buddyboss-platform' ), value: 'draft' },
							{ label: __( 'Password Protected', 'buddyboss-platform' ), value: 'password_protected' },
						] }
						onChange={ function ( val ) { updateField( 'visibility', val ); } }
					/>
					{ 'password_protected' === formData.visibility && (
						<TextControl
							label={ __( 'Password', 'buddyboss-platform' ) }
							value={ formData.post_password }
							onChange={ function ( val ) { updateField( 'post_password', val ); } }
							type="text"
							placeholder={ formData.has_password ? __( 'Leave blank to keep current password', 'buddyboss-platform' ) : '' }
						/>
					) }
				</div>

				{/* Label Color */}
				<div className="bb-admin-profile-type-modal__section">
					<SelectControl
						label={ __( 'Label Color', 'buddyboss-platform' ) }
						value={ formData.label_color.type }
						options={ [
							{ label: __( 'Default', 'buddyboss-platform' ), value: 'default' },
							{ label: __( 'Custom', 'buddyboss-platform' ), value: 'custom' },
						] }
						onChange={ function ( val ) { updateLabelColor( 'type', val ); } }
					/>

					{ 'custom' === formData.label_color.type && (
						<div className="bb-admin-profile-type-modal__color-pickers">
							<div className="bb-admin-profile-type-modal__color-field">
								<label className="bb-admin-profile-type-modal__color-label">
									{ __( 'Background Color', 'buddyboss-platform' ) }
								</label>
								<div className="bb-admin-profile-type-modal__color-input-row">
									<input
										type="color"
										value={ formData.label_color.background_color || '#000000' }
										onChange={ function ( e ) { updateLabelColor( 'background_color', e.target.value ); } }
										className="bb-admin-profile-type-modal__color-swatch"
									/>
									<input
										type="text"
										value={ stripHash( formData.label_color.background_color || '000000' ) }
										onChange={ function ( e ) {
											var val = e.target.value.replace( /[^0-9a-fA-F]/g, '' ).substring( 0, 6 );
											updateLabelColor( 'background_color', ensureHash( val ) );
										} }
										className="bb-admin-profile-type-modal__color-hex"
										maxLength="6"
									/>
								</div>
							</div>
							<div className="bb-admin-profile-type-modal__color-field">
								<label className="bb-admin-profile-type-modal__color-label">
									{ __( 'Text Color', 'buddyboss-platform' ) }
								</label>
								<div className="bb-admin-profile-type-modal__color-input-row">
									<input
										type="color"
										value={ formData.label_color.text_color || '#ffffff' }
										onChange={ function ( e ) { updateLabelColor( 'text_color', e.target.value ); } }
										className="bb-admin-profile-type-modal__color-swatch"
									/>
									<input
										type="text"
										value={ stripHash( formData.label_color.text_color || 'FFFFFF' ) }
										onChange={ function ( e ) {
											var val = e.target.value.replace( /[^0-9a-fA-F]/g, '' ).substring( 0, 6 );
											updateLabelColor( 'text_color', ensureHash( val ) );
										} }
										className="bb-admin-profile-type-modal__color-hex"
										maxLength="6"
									/>
								</div>
							</div>
						</div>
					) }
				</div>

				{/* Shortcode (edit mode only) */}
				{ isEditing && memberType && memberType.key && (
					<div className="bb-admin-profile-type-modal__section">
						<h4 className="bb-admin-profile-type-modal__section-title">
							{ __( 'Shortcode', 'buddyboss-platform' ) }
						</h4>
						<div className="bb-admin-profile-type-modal__shortcode-row">
							<input
								type="text"
								readOnly
								value={ '[profile type="' + memberType.key + '"]' }
								className="bb-admin-profile-type-modal__shortcode-input"
								onClick={ function ( e ) { e.target.select(); } }
							/>
							<button
								type="button"
								className="bb-admin-profile-type-modal__shortcode-copy"
								onClick={ function () {
									if ( navigator.clipboard ) {
										navigator.clipboard.writeText( '[profile type="' + memberType.key + '"]' );
									}
								} }
								aria-label={ __( 'Copy shortcode', 'buddyboss-platform' ) }
							>
								<i className="bb-icons-rl bb-icons-rl-copy"></i>
							</button>
						</div>
						<p className="bb-admin-profile-type-modal__section-description">
							{ __( 'To list all users of this profile type, add the shortcode below to any WordPress page.', 'buddyboss-platform' ) }
						</p>
					</div>
				) }
			</div>

			<div className="bb-admin-settings-modal__footer bb-admin-profile-type-modal__footer">
				<Button
					variant="secondary"
					onClick={ onClose }
					disabled={ isSaving }
				>
					{ __( 'Cancel', 'buddyboss-platform' ) }
				</Button>
				<Button
					variant="primary"
					onClick={ handleSave }
					isBusy={ isSaving }
					disabled={ isSaving || ! formData.name.trim() }
				>
					{ isSaving ? __( 'Saving...', 'buddyboss-platform' ) : ( isEditing ? __( 'Update', 'buddyboss-platform' ) : __( 'Save', 'buddyboss-platform' ) ) }
				</Button>
			</div>
		</Modal>
	);
}

export default ProfileTypeModal;

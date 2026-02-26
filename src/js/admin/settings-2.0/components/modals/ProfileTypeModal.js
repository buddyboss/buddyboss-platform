/**
 * BuddyBoss Admin Settings 2.0 - Profile Type Modal
 *
 * Modal for creating and editing profile/member types.
 *
 * @package BuddyBoss\Core\Administration
 * @since BuddyBoss [BBVERSION]
 */

import { useState, useEffect, useCallback } from '@wordpress/element';
import {
	TextControl,
	CheckboxControl,
	SelectControl,
	RadioControl,
	Button,
	Modal,
} from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { decodeEntities } from '@wordpress/html-entities';
import { createMemberType, updateMemberType } from '../../utils/ajax';

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
	enable_profile_field: 0,
	group_type_create_mode: 'none',
	group_type_create: [],
	group_type_auto_join: [],
	wp_roles: [],
	login_redirection: '',
	custom_login_redirection: '',
	logout_redirection: '',
	custom_logout_redirection: '',
	visibility: 'publish',
	post_password: '',
	label_color: {
		type: 'default',
		background_color: '',
		text_color: '',
	},
	enable_invite: 0,
	allowed_member_type_invite: [],
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
	if ( Array.isArray( value ) && value.length > 0 ) {
		// Check if "all" is in the array.
		if ( -1 !== value.indexOf( 'all' ) ) {
			return 'all';
		}
		return 'specific';
	}
	return 'none';
}

/**
 * Profile Type Modal Component
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param {Object}   props                Component props.
 * @param {boolean}  props.isOpen         Whether modal is open.
 * @param {Function} props.onClose        Close handler.
 * @param {Function} props.onSave         Save handler.
 * @param {Object}   props.memberType     Member type to edit (null for create).
 * @param {Array}    props.groupTypes     Available group types.
 * @param {Array}    props.wpRoles        Available WordPress roles.
 * @param {Array}    props.allMemberTypes All member types (for invite field).
 * @returns {JSX.Element|null} Modal element or null.
 */
export function ProfileTypeModal( { isOpen, onClose, onSave, memberType, groupTypes, wpRoles, allMemberTypes } ) {
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
	var availableMemberTypes = allMemberTypes || [];

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
			var allowedInvite = memberType.allowed_member_type_invite || [];

			setFormData( {
				name: decodeEntities( memberType.post_title || '' ),
				singular_label: decodeEntities( memberType.singular_label || '' ),
				plural_label: decodeEntities( memberType.plural_label || '' ),
				enable_filter: memberType.enable_filter || 0,
				enable_remove: memberType.enable_remove || 0,
				enable_search_remove: memberType.enable_search_remove || 0,
				enable_profile_field: memberType.enable_profile_field || 0,
				group_type_create_mode: getGroupTypeCreateMode( gtCreate ),
				group_type_create: Array.isArray( gtCreate ) ? gtCreate.map( String ) : [],
				group_type_auto_join: Array.isArray( gtAutoJoin ) ? gtAutoJoin.map( String ) : [],
				wp_roles: Array.isArray( existingWpRoles ) ? existingWpRoles : [],
				login_redirection: memberType.login_redirection || '',
				custom_login_redirection: memberType.custom_login_redirection || '',
				logout_redirection: memberType.logout_redirection || '',
				custom_logout_redirection: memberType.custom_logout_redirection || '',
				visibility: memberType.visibility || 'publish',
				post_password: memberType.post_password || '',
				label_color: {
					type: ( labelColor.type ) || 'default',
					background_color: ( labelColor.background_color ) || '',
					text_color: ( labelColor.text_color ) || '',
				},
				enable_invite: memberType.enable_invite || 0,
				allowed_member_type_invite: Array.isArray( allowedInvite ) ? allowedInvite.map( String ) : [],
			} );
		} else {
			setFormData( JSON.parse( JSON.stringify( DEFAULT_FORM_DATA ) ) );
		}

		setError( '' );
	}, [ isOpen, memberType ] );

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
			setError( __( 'Profile type name is required.', 'buddyboss' ) );
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
			enable_profile_field: formData.enable_profile_field,
			visibility: formData.visibility,
			login_redirection: formData.login_redirection,
			custom_login_redirection: formData.custom_login_redirection,
			logout_redirection: formData.logout_redirection,
			custom_logout_redirection: formData.custom_logout_redirection,
			enable_invite: formData.enable_invite,
			'label_color[type]': formData.label_color.type,
		};

		if ( 'password_protected' === formData.visibility ) {
			data.post_password = formData.post_password;
		}

		if ( 'custom' === formData.label_color.type ) {
			data[ 'label_color[background_color]' ] = formData.label_color.background_color;
			data[ 'label_color[text_color]' ] = formData.label_color.text_color;
		}

		// Group type create permissions.
		if ( 'all' === formData.group_type_create_mode ) {
			data[ 'group_type_create[0]' ] = 'all';
		} else if ( 'specific' === formData.group_type_create_mode && formData.group_type_create.length > 0 ) {
			formData.group_type_create.forEach( function ( id, idx ) {
				data[ 'group_type_create[' + idx + ']' ] = id;
			} );
		}

		// Group type auto join.
		if ( formData.group_type_auto_join.length > 0 ) {
			formData.group_type_auto_join.forEach( function ( id, idx ) {
				data[ 'group_type_auto_join[' + idx + ']' ] = id;
			} );
		}

		// WP roles.
		if ( formData.wp_roles.length > 0 ) {
			formData.wp_roles.forEach( function ( role, idx ) {
				data[ 'wp_roles[' + idx + ']' ] = role;
			} );
		}

		// Allowed member type invite.
		if ( formData.allowed_member_type_invite.length > 0 ) {
			formData.allowed_member_type_invite.forEach( function ( id, idx ) {
				data[ 'allowed_member_type_invite[' + idx + ']' ] = id;
			} );
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
					setError( response.data?.message || __( 'Failed to save profile type.', 'buddyboss' ) );
				}
			} )
			.catch( function () {
				setIsSaving( false );
				setError( __( 'Failed to save profile type.', 'buddyboss' ) );
			} );
	}, [ formData, memberType, onSave ] );

	if ( ! isOpen ) {
		return null;
	}

	var isEditing = !! ( memberType && memberType.id );
	var modalTitle = isEditing
		? __( 'Edit Profile Type', 'buddyboss' )
		: __( 'Add New Profile Type', 'buddyboss' );

	return (
		<Modal
			title={ modalTitle }
			onRequestClose={ onClose }
			className="bb-admin-profile-type-modal bb-admin-settings-modal"
			shouldCloseOnClickOutside={ false }
		>
			<div className="bb-admin-profile-type-modal__body">
				{ error && (
					<div className="bb-admin-profile-type-modal__error">
						{ error }
					</div>
				) }

				{/* Name */}
				<div className="bb-admin-profile-type-modal__section">
					<TextControl
						label={ __( 'Name', 'buddyboss' ) }
						value={ formData.name }
						onChange={ function ( val ) { updateField( 'name', val ); } }
						required
					/>
				</div>

				{/* Labels — Singular first, Plural second (matching Figma) */}
				<div className="bb-admin-profile-type-modal__section">
					<div className="bb-admin-profile-type-modal__row">
						<TextControl
							label={ __( 'Singular Label', 'buddyboss' ) }
							value={ formData.singular_label }
							onChange={ function ( val ) { updateField( 'singular_label', val ); } }
						/>
						<TextControl
							label={ __( 'Plural Label', 'buddyboss' ) }
							value={ formData.plural_label }
							onChange={ function ( val ) { updateField( 'plural_label', val ); } }
						/>
					</div>
				</div>

				{/* Members Directory Permissions */}
				<div className="bb-admin-profile-type-modal__section">
					<h4 className="bb-admin-profile-type-modal__section-title">
						{ __( 'Members Directory Permissions', 'buddyboss' ) }
					</h4>
					<CheckboxControl
						label={ __( 'Display this profile type in "Types" filter in Members Directory', 'buddyboss' ) }
						checked={ !! formData.enable_filter }
						onChange={ function ( val ) { updateField( 'enable_filter', val ? 1 : 0 ); } }
					/>
					<CheckboxControl
						label={ __( 'Hide all members of this type from Members Directory', 'buddyboss' ) }
						checked={ !! formData.enable_remove }
						onChange={ function ( val ) { updateField( 'enable_remove', val ? 1 : 0 ); } }
					/>
					<CheckboxControl
						label={ __( 'Hide all members of this type from Network Search results', 'buddyboss' ) }
						checked={ !! formData.enable_search_remove }
						onChange={ function ( val ) { updateField( 'enable_search_remove', val ? 1 : 0 ); } }
					/>
				</div>

				{/* Profile Field */}
				<div className="bb-admin-profile-type-modal__section">
					<h4 className="bb-admin-profile-type-modal__section-title">
						{ __( 'Profile Field', 'buddyboss' ) }
					</h4>
					<CheckboxControl
						label={ __( 'Allow users to select this profile type from the "Profile Type" dropdown', 'buddyboss' ) }
						checked={ !! formData.enable_profile_field }
						onChange={ function ( val ) { updateField( 'enable_profile_field', val ? 1 : 0 ); } }
					/>
				</div>

				{/* Group Creation Permissions — only when Groups component is active */}
				{ availableGroupTypes.length > 0 && (
					<div className="bb-admin-profile-type-modal__section">
						<h4 className="bb-admin-profile-type-modal__section-title">
							{ __( 'Group Creation Permissions', 'buddyboss' ) }
						</h4>
						<RadioControl
							selected={ formData.group_type_create_mode }
							options={ [
								{ label: __( 'None', 'buddyboss' ), value: 'none' },
								{ label: __( 'All Group Types', 'buddyboss' ), value: 'all' },
								{ label: __( 'Specific Group Types', 'buddyboss' ), value: 'specific' },
							] }
							onChange={ function ( val ) { updateField( 'group_type_create_mode', val ); } }
						/>
						{ 'specific' === formData.group_type_create_mode && (
							<div className="bb-admin-profile-type-modal__checkbox-grid">
								{ availableGroupTypes.map( function ( gt ) {
									var isChecked = -1 !== formData.group_type_create.indexOf( String( gt.id ) );
									return (
										<CheckboxControl
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

				{/* Group Type Membership Approval — only when Groups component is active */}
				{ availableGroupTypes.length > 0 && (
					<div className="bb-admin-profile-type-modal__section">
						<h4 className="bb-admin-profile-type-modal__section-title">
							{ __( 'Group Type Membership Approval', 'buddyboss' ) }
						</h4>
						<p className="bb-admin-profile-type-modal__section-description" style={ { marginTop: 0, marginBottom: 16 } }>
							{ __( 'Allow members of this profile type to auto-join groups of the selected group types without approval.', 'buddyboss' ) }
						</p>
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
					</div>
				) }

				{/* WordPress Role */}
				<div className="bb-admin-profile-type-modal__section">
					<h4 className="bb-admin-profile-type-modal__section-title">
						{ __( 'WordPress Role', 'buddyboss' ) }
					</h4>
					<p className="bb-admin-profile-type-modal__section-description" style={ { marginTop: 0, marginBottom: 16 } }>
						{ __( 'Select the WordPress role to assign to members of this profile type. Changing this will update the role for all existing members of this type.', 'buddyboss' ) }
					</p>
					<div className="bb-admin-profile-type-modal__checkbox-grid">
						{ [ { value: 'none', label: __( 'None', 'buddyboss' ) } ].concat( availableWpRoles ).map( function ( role ) {
							var isChecked = -1 !== formData.wp_roles.indexOf( role.value );
							return (
								<CheckboxControl
									key={ role.value }
									label={ decodeEntities( role.label ) }
									checked={ isChecked }
									onChange={ function () {
										// Single select behavior — replace array.
										if ( isChecked ) {
											updateField( 'wp_roles', [] );
										} else {
											updateField( 'wp_roles', [ role.value ] );
										}
									} }
								/>
							);
						} ) }
					</div>
				</div>

				{/* After Login Redirection */}
				<div className="bb-admin-profile-type-modal__section">
					<SelectControl
						label={ __( 'After Login Redirection', 'buddyboss' ) }
						value={ formData.login_redirection }
						options={ [
							{ label: __( 'Default', 'buddyboss' ), value: '' },
							{ label: __( 'Custom URL', 'buddyboss' ), value: 'custom' },
							{ label: __( 'Account', 'buddyboss' ), value: 'account' },
							{ label: __( 'Activate', 'buddyboss' ), value: 'activate' },
						] }
						onChange={ function ( val ) { updateField( 'login_redirection', val ); } }
					/>
					{ 'custom' === formData.login_redirection && (
						<TextControl
							label={ __( 'Custom URL', 'buddyboss' ) }
							value={ formData.custom_login_redirection }
							onChange={ function ( val ) { updateField( 'custom_login_redirection', val ); } }
							type="url"
						/>
					) }
				</div>

				{/* After Logout Redirection */}
				<div className="bb-admin-profile-type-modal__section">
					<SelectControl
						label={ __( 'After Logout Redirection', 'buddyboss' ) }
						value={ formData.logout_redirection }
						options={ [
							{ label: __( 'Default', 'buddyboss' ), value: '' },
							{ label: __( 'Custom URL', 'buddyboss' ), value: 'custom' },
							{ label: __( 'Account', 'buddyboss' ), value: 'account' },
							{ label: __( 'Activate', 'buddyboss' ), value: 'activate' },
						] }
						onChange={ function ( val ) { updateField( 'logout_redirection', val ); } }
					/>
					{ 'custom' === formData.logout_redirection && (
						<TextControl
							label={ __( 'Custom URL', 'buddyboss' ) }
							value={ formData.custom_logout_redirection }
							onChange={ function ( val ) { updateField( 'custom_logout_redirection', val ); } }
							type="url"
						/>
					) }
				</div>

				{/* Visibility */}
				<div className="bb-admin-profile-type-modal__section">
					<SelectControl
						label={ __( 'Visibility', 'buddyboss' ) }
						value={ formData.visibility }
						options={ [
							{ label: __( 'Public', 'buddyboss' ), value: 'publish' },
							{ label: __( 'Private', 'buddyboss' ), value: 'private' },
							{ label: __( 'Draft', 'buddyboss' ), value: 'draft' },
							{ label: __( 'Password Protected', 'buddyboss' ), value: 'password_protected' },
						] }
						onChange={ function ( val ) { updateField( 'visibility', val ); } }
					/>
					{ 'password_protected' === formData.visibility && (
						<TextControl
							label={ __( 'Password', 'buddyboss' ) }
							value={ formData.post_password }
							onChange={ function ( val ) { updateField( 'post_password', val ); } }
							type="text"
						/>
					) }
				</div>

				{/* Label Color */}
				<div className="bb-admin-profile-type-modal__section">
					<SelectControl
						label={ __( 'Label Color', 'buddyboss' ) }
						value={ formData.label_color.type }
						options={ [
							{ label: __( 'Default', 'buddyboss' ), value: 'default' },
							{ label: __( 'Custom', 'buddyboss' ), value: 'custom' },
						] }
						onChange={ function ( val ) { updateLabelColor( 'type', val ); } }
					/>

					{ 'custom' === formData.label_color.type && (
						<div className="bb-admin-profile-type-modal__color-pickers">
							<div className="bb-admin-profile-type-modal__color-field">
								<label className="bb-admin-profile-type-modal__color-label">
									{ __( 'Background Color', 'buddyboss' ) }
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
									{ __( 'Text Color', 'buddyboss' ) }
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

				{/* Email Invites */}
				<div className="bb-admin-profile-type-modal__section">
					<h4 className="bb-admin-profile-type-modal__section-title">
						{ __( 'Email Invites', 'buddyboss' ) }
					</h4>
					<CheckboxControl
						label={ __( 'Allow members to select the profile type that the invited recipient will be automatically assigned to on registration', 'buddyboss' ) }
						checked={ !! formData.enable_invite }
						onChange={ function ( val ) { updateField( 'enable_invite', val ? 1 : 0 ); } }
					/>
					{ !! formData.enable_invite && availableMemberTypes.length > 0 && (
						<div className="bb-admin-profile-type-modal__checkbox-grid">
							{ availableMemberTypes.filter( function ( mt ) {
								// Exclude current type from invite list.
								return ! memberType || mt.id !== memberType.id;
							} ).map( function ( mt ) {
								var isChecked = -1 !== formData.allowed_member_type_invite.indexOf( String( mt.id ) );
								return (
									<CheckboxControl
										key={ mt.id }
										label={ decodeEntities( mt.post_title ) }
										checked={ isChecked }
										onChange={ function () {
											toggleListItem( 'allowed_member_type_invite', mt.id );
										} }
									/>
								);
							} ) }
						</div>
					) }
				</div>

				{/* Shortcode (edit mode only) */}
				{ isEditing && memberType && memberType.key && (
					<div className="bb-admin-profile-type-modal__section">
						<h4 className="bb-admin-profile-type-modal__section-title">
							{ __( 'Shortcode', 'buddyboss' ) }
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
								aria-label={ __( 'Copy shortcode', 'buddyboss' ) }
							>
								<i className="bb-icons-rl bb-icons-rl-copy"></i>
							</button>
						</div>
						<p className="bb-admin-profile-type-modal__section-description">
							{ __( 'Add this shortcode to any WordPress page to display all members of this type on a dedicated page.', 'buddyboss' ) }
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
					{ __( 'Cancel', 'buddyboss' ) }
				</Button>
				<Button
					variant="primary"
					onClick={ handleSave }
					isBusy={ isSaving }
					disabled={ isSaving || ! formData.name.trim() }
				>
					{ isSaving ? __( 'Saving...', 'buddyboss' ) : ( isEditing ? __( 'Update', 'buddyboss' ) : __( 'Save', 'buddyboss' ) ) }
				</Button>
			</div>
		</Modal>
	);
}

export default ProfileTypeModal;

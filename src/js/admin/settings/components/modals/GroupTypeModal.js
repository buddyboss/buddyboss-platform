/**
 * BuddyBoss Admin Settings 2.0 - Group Type Modal
 *
 * Modal for creating and editing group types.
 *
 * @package BuddyBoss\Core\Administration
 * @since BuddyBoss [BBVERSION]
 */

import { useState, useEffect, useCallback } from '@wordpress/element';
import {
	TextControl,
	CheckboxControl,
	SelectControl,
	Button,
	Spinner,
	Modal,
} from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { decodeEntities } from '@wordpress/html-entities';
import { createGroupType, updateGroupType } from '../../utils/ajax';
import { sanitizeHtml } from '../../utils/sanitize';

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
	role_labels: {
		organizer: { plural: '', singular: '' },
		moderator: { plural: '', singular: '' },
		member: { plural: '', singular: '' },
	},
	enable_filter: 0,
	enable_remove: 0,
	restrict_invites: 0,
	member_type_invites_mode: 'none',
	member_type_invites: [],
	member_type_join_mode: 'none',
	member_type_join: [],
	visibility: 'public',
	label_color: {
		type: 'default',
		background_color: '',
		text_color: '',
	},
};

/**
 * Determine the mode (all/selected/none) from a member type meta value.
 *
 * Storage shapes produced by the React save flow:
 *   ''                          → "None" mode    (legacy "leave blank" — the
 *                                                  restriction is NOT applied
 *                                                  at the frontend; matches
 *                                                  legacy "Leave blank for
 *                                                  unrestricted invites")
 *   array( every_available_id ) → "All" mode     (restriction IS applied and
 *                                                  every registered profile
 *                                                  type is listed; frontend
 *                                                  filter passes for every
 *                                                  type, including auto-join)
 *   array( subset_of_ids )      → "Selected"     (restriction applied to a
 *                                                  proper subset of types)
 *
 * Note: storing the full type list for "All" means existing profile types
 * added AFTER the group type was saved won't be auto-included — admin
 * must re-save the group type to refresh. This is the tradeoff for being
 * able to express "auto-approve everyone" / "filter applied to all" as
 * a stored state legacy can also read via `in_array()` semantics.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param {*}     value          Meta value from PHP.
 * @param {Array} availableTypes Available member-type objects (each with `id`).
 * @returns {string} 'none', 'all', or 'selected'.
 */
function getMemberTypeMode( value, availableTypes ) {
	if ( ! Array.isArray( value ) || 0 === value.length ) {
		return 'none';
	}
	var availableIds = ( availableTypes || [] ).map( function ( t ) {
		return String( t.id );
	} );
	if ( 0 === availableIds.length ) {
		return 'selected';
	}
	var stored = value.map( String );
	var allPresent = availableIds.every( function ( id ) {
		return -1 !== stored.indexOf( id );
	} );
	if ( allPresent && stored.length === availableIds.length ) {
		return 'all';
	}
	return 'selected';
}

/**
 * Normalize member type meta value to an array of strings.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param {*} value Meta value from PHP.
 * @returns {Array} Array of member type ID strings.
 */
function normalizeMemberTypes( value ) {
	if ( Array.isArray( value ) ) {
		return value.map( function ( v ) {
			return String( v );
		} );
	}
	return [];
}

/**
 * Group Type Modal Component
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param {Object}   props             Component props.
 * @param {boolean}  props.isOpen      Whether modal is open.
 * @param {Function} props.onClose     Close handler.
 * @param {Function} props.onSave      Save handler.
 * @param {Object}   props.groupType   Group type to edit (null for create).
 * @param {Array}    props.memberTypes Available member/profile types.
 * @returns {JSX.Element|null} Modal element or null.
 */
/**
 * Module-level constants for role label rendering.
 *
 * @since BuddyBoss [BBVERSION]
 */
var ROLE_LABELS_MAP = {
	organizer: __( 'Organizers', 'buddyboss' ),
	moderator: __( 'Moderators', 'buddyboss' ),
	member: __( 'Members', 'buddyboss' ),
};

var SINGULAR_PLACEHOLDERS = {
	organizer: __( 'e.g. Organizer', 'buddyboss' ),
	moderator: __( 'e.g. Moderator', 'buddyboss' ),
	member: __( 'e.g. Member', 'buddyboss' ),
};

var PLURAL_PLACEHOLDERS = {
	organizer: __( 'e.g. Organizers', 'buddyboss' ),
	moderator: __( 'e.g. Moderators', 'buddyboss' ),
	member: __( 'e.g. Members', 'buddyboss' ),
};

export function GroupTypeModal( { isOpen, onClose, onSave, groupType, memberTypes } ) {
	var formDataState = useState( DEFAULT_FORM_DATA );
	var formData = formDataState[ 0 ];
	var setFormData = formDataState[ 1 ];

	var isSavingState = useState( false );
	var isSaving = isSavingState[ 0 ];
	var setIsSaving = isSavingState[ 1 ];

	var errorState = useState( '' );
	var error = errorState[ 0 ];
	var setError = errorState[ 1 ];

	var availableMemberTypes = memberTypes || [];

	// Populate form data when editing.
	useEffect( function () {
		if ( ! isOpen ) {
			return;
		}

		if ( groupType ) {
			var roleLabels = groupType.role_labels || {};
			if ( 'object' !== typeof roleLabels || Array.isArray( roleLabels ) ) {
				roleLabels = {};
			}

			var labelColor = groupType.label_color || {};
			if ( 'object' !== typeof labelColor || Array.isArray( labelColor ) ) {
				labelColor = {};
			}

			setFormData( {
				name: decodeEntities( groupType.post_title || '' ),
				singular_label: decodeEntities( groupType.singular_label || '' ),
				plural_label: decodeEntities( groupType.plural_label || '' ),
				role_labels: {
					organizer: {
						plural: ( roleLabels.organizer && roleLabels.organizer.plural ) || '',
						singular: ( roleLabels.organizer && roleLabels.organizer.singular ) || '',
					},
					moderator: {
						plural: ( roleLabels.moderator && roleLabels.moderator.plural ) || '',
						singular: ( roleLabels.moderator && roleLabels.moderator.singular ) || '',
					},
					member: {
						plural: ( roleLabels.member && roleLabels.member.plural ) || '',
						singular: ( roleLabels.member && roleLabels.member.singular ) || '',
					},
				},
				enable_filter: groupType.enable_filter || 0,
				enable_remove: groupType.enable_remove || 0,
				restrict_invites: groupType.restrict_invites || 0,
				member_type_invites_mode: getMemberTypeMode( groupType.member_type_invites, memberTypes ),
				member_type_invites: normalizeMemberTypes( groupType.member_type_invites ),
				member_type_join_mode: getMemberTypeMode( groupType.member_type_join, memberTypes ),
				member_type_join: normalizeMemberTypes( groupType.member_type_join ),
				visibility: groupType.visibility || 'public',
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
	}, [ isOpen, groupType ] );

	// Update a field in form data.
	var updateField = useCallback( function ( field, value ) {
		setFormData( function ( prev ) {
			var updated = Object.assign( {}, prev );
			updated[ field ] = value;
			return updated;
		} );
	}, [] );

	// Update a role label.
	var updateRoleLabel = useCallback( function ( role, labelType, value ) {
		setFormData( function ( prev ) {
			var updated = Object.assign( {}, prev );
			var newRoles = {};
			Object.keys( prev.role_labels ).forEach( function ( rk ) {
				newRoles[ rk ] = Object.assign( {}, prev.role_labels[ rk ] );
			} );
			newRoles[ role ][ labelType ] = value;
			updated.role_labels = newRoles;
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

	// Toggle a member type in the selected list.
	var toggleMemberType = useCallback( function ( field, typeId ) {
		setFormData( function ( prev ) {
			var updated = Object.assign( {}, prev );
			var currentList = prev[ field ] || [];
			var typeIdStr = String( typeId );
			var index = currentList.indexOf( typeIdStr );

			if ( -1 === index ) {
				updated[ field ] = currentList.concat( [ typeIdStr ] );
			} else {
				updated[ field ] = currentList.filter( function ( id ) {
					return id !== typeIdStr;
				} );
			}

			return updated;
		} );
	}, [] );

	// Handle save.
	var handleSave = useCallback( function () {
		if ( isSaving ) {
			return;
		}

		if ( ! formData.name.trim() ) {
			setError( __( 'Group type name is required.', 'buddyboss' ) );
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
			restrict_invites: formData.restrict_invites,
			visibility: formData.visibility,
			'role_labels[organizer][plural]': formData.role_labels.organizer.plural,
			'role_labels[organizer][singular]': formData.role_labels.organizer.singular,
			'role_labels[moderator][plural]': formData.role_labels.moderator.plural,
			'role_labels[moderator][singular]': formData.role_labels.moderator.singular,
			'role_labels[member][plural]': formData.role_labels.member.plural,
			'role_labels[member][singular]': formData.role_labels.member.singular,
			'label_color[type]': formData.label_color.type,
		};

		if ( 'custom' === formData.label_color.type ) {
			data[ 'label_color[background_color]' ] = formData.label_color.background_color;
			data[ 'label_color[text_color]' ] = formData.label_color.text_color;
		}

		// Profile Type Invites — emit the storage shape that matches the
		// legacy `_bp_group_type_enabled_member_type_group_invites` meta
		// semantics so the frontend invite-picker filter and REST consumers
		// behave correctly:
		//   None     → '' (the restriction is NOT applied — same as legacy
		//                 "Leave blank for unrestricted invites")
		//   All      → array of every available member-type id (the
		//                 restriction IS applied and matches every type —
		//                 functionally equivalent to "all allowed," but
		//                 stored explicitly so the round-trip preserves
		//                 admin intent and so the auto-join consumer
		//                 (`bp-groups-functions.php:2616`) can match every
		//                 user type via `in_array()`)
		//   Selected → array of the toggled ids
		var availableMemberTypeIds = ( memberTypes || [] ).map( function ( mt ) {
			return String( mt.id );
		} );
		if ( 'all' === formData.member_type_invites_mode && availableMemberTypeIds.length > 0 ) {
			availableMemberTypeIds.forEach( function ( id, idx ) {
				data[ 'member_type_invites[' + idx + ']' ] = id;
			} );
		} else if ( 'selected' === formData.member_type_invites_mode && formData.member_type_invites.length > 0 ) {
			formData.member_type_invites.forEach( function ( id, idx ) {
				data[ 'member_type_invites[' + idx + ']' ] = id;
			} );
		} else {
			// 'none' or 'selected' with zero items → empty marker so PHP
			// `isset( $_POST['member_type_invites'] )` fires and meta is
			// overwritten with '' instead of being left untouched.
			data.member_type_invites = '';
		}

		// Profile Type Joining — same storage semantics as the invites field
		// above; the `_bp_group_type_enabled_member_type_join` consumer at
		// `bp-groups-functions.php:2616` uses `in_array($user_type, $meta)`
		// to decide whether to auto-approve, so "All" needs the full type
		// list listed explicitly to actually auto-approve every user; '' /
		// "None" falls through to the normal admin-approval flow.
		if ( 'all' === formData.member_type_join_mode && availableMemberTypeIds.length > 0 ) {
			availableMemberTypeIds.forEach( function ( id, idx ) {
				data[ 'member_type_join[' + idx + ']' ] = id;
			} );
		} else if ( 'selected' === formData.member_type_join_mode && formData.member_type_join.length > 0 ) {
			formData.member_type_join.forEach( function ( id, idx ) {
				data[ 'member_type_join[' + idx + ']' ] = id;
			} );
		} else {
			data.member_type_join = '';
		}

		var savePromise;
		if ( groupType && groupType.id ) {
			savePromise = updateGroupType( groupType.id, data );
		} else {
			savePromise = createGroupType( data );
		}

		savePromise
			.then( function ( response ) {
				setIsSaving( false );
				if ( response.success ) {
					if ( 'function' === typeof onSave ) {
						onSave();
					}
				} else {
					setError( ( response.data && response.data.message ) || __( 'Failed to save group type.', 'buddyboss' ) );
				}
			} )
			.catch( function () {
				setIsSaving( false );
				setError( __( 'Failed to save group type.', 'buddyboss' ) );
			} );
	}, [ formData, groupType, onSave, isSaving ] );

	if ( ! isOpen ) {
		return null;
	}

	var isEditing = !! ( groupType && groupType.id );
	var modalTitle = isEditing
		? __( 'Edit Group Type', 'buddyboss' )
		: __( 'Add New Group Type', 'buddyboss' );

	return (
		<Modal
			title={ modalTitle }
			onRequestClose={ function () {
				if ( ! isSaving ) {
					onClose();
				}
			} }
			className="bb-admin-group-type-modal bb-admin-settings-modal"
			shouldCloseOnClickOutside={ false }
		>
			<div className="bb-admin-group-type-modal__body">
					{ error && (
						<div className="bb-admin-group-type-modal__error">
							{ error }
						</div>
					) }

					{/* Name */}
					<div className="bb-admin-group-type-modal__section">
						<TextControl
							label={ __( 'Name', 'buddyboss' ) }
							value={ formData.name }
							onChange={ function ( val ) { updateField( 'name', val ); } }
							required
						/>
					</div>

					{/* Labels — Singular first, Plural second (matching Figma) */}
					<div className="bb-admin-group-type-modal__section">
						<div className="bb-admin-group-type-modal__row">
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

					{/* Group Roles (Optional) */}
					<div className="bb-admin-group-type-modal__section">
						<h4 className="bb-admin-group-type-modal__section-title">
							{ __( 'Group Roles (Optional)', 'buddyboss' ) }
						</h4>

						{ [ 'organizer', 'moderator', 'member' ].map( function ( role ) {
							return (
								<div key={ role } className="bb-admin-group-type-modal__role-row">
									<span className="bb-admin-group-type-modal__role-label">{ ROLE_LABELS_MAP[ role ] }</span>
									<div className="bb-admin-group-type-modal__row">
										<TextControl
											label={ __( 'Singular Label', 'buddyboss' ) }
											value={ formData.role_labels[ role ].singular }
											onChange={ function ( val ) { updateRoleLabel( role, 'singular', val ); } }
											placeholder={ SINGULAR_PLACEHOLDERS[ role ] }
										/>
										<TextControl
											label={ __( 'Plural Label', 'buddyboss' ) }
											value={ formData.role_labels[ role ].plural }
											onChange={ function ( val ) { updateRoleLabel( role, 'plural', val ); } }
											placeholder={ PLURAL_PLACEHOLDERS[ role ] }
										/>
									</div>
								</div>
							);
						} ) }
					</div>

					{/* Groups Directory Permissions */}
					<div className="bb-admin-group-type-modal__section">
						<h4 className="bb-admin-group-type-modal__section-title">
							{ __( 'Groups Directory Permissions', 'buddyboss' ) }
						</h4>
						<CheckboxControl
							label={ __( 'Display this group type in "Types" filter in Groups Directory', 'buddyboss' ) }
							checked={ !! formData.enable_filter }
							onChange={ function ( val ) { updateField( 'enable_filter', val ? 1 : 0 ); } }
						/>
						<CheckboxControl
							label={ __( 'Hide all groups of this type from Groups Directory', 'buddyboss' ) }
							checked={ !! formData.enable_remove }
							onChange={ function ( val ) { updateField( 'enable_remove', val ? 1 : 0 ); } }
						/>
					</div>

					{/* Group Type Invites */}
					<div className="bb-admin-group-type-modal__section">
						<h4 className="bb-admin-group-type-modal__section-title">
							{ __( 'Group Type Invites', 'buddyboss' ) }
						</h4>
						<CheckboxControl
							label={ __( "Members already in a group of this type can't be invited to another", 'buddyboss' ) }
							checked={ !! formData.restrict_invites }
							onChange={ function ( val ) { updateField( 'restrict_invites', val ? 1 : 0 ); } }
						/>
					</div>

					{/* Profile Type Invites */}
					{ availableMemberTypes.length > 0 && (
						<div className="bb-admin-group-type-modal__section">
							<h4 className="bb-admin-group-type-modal__section-title">
								{ __( 'Profile Type Invites', 'buddyboss' ) }
							</h4>
							<SelectControl
								value={ formData.member_type_invites_mode }
								options={ [
									{ label: __( 'All Profile Types', 'buddyboss' ), value: 'all' },
									{ label: __( 'Selected Profile Types', 'buddyboss' ), value: 'selected' },
									{ label: __( 'None', 'buddyboss' ), value: 'none' },
								] }
								onChange={ function ( val ) { updateField( 'member_type_invites_mode', val ); } }
							/>
							<p
								className="bb-admin-group-type-modal__section-description"
								dangerouslySetInnerHTML={ { __html: sanitizeHtml( __( 'Select which profile types are allowed to send a request to join this group. Members restricted by <a href="admin.php?page=bb-settings&tab=groups&panel=access_controls">Group Access</a> settings cannot send a request, even if their profile type is allowed above.', 'buddyboss' ) ) } }
							/>
							{ 'selected' === formData.member_type_invites_mode && (
								<div className="bb-admin-group-type-modal__member-types-grid">
									{ availableMemberTypes.map( function ( mt ) {
										var isChecked = -1 !== formData.member_type_invites.indexOf( String( mt.id ) );
										return (
											<CheckboxControl
												key={ mt.id }
												label={ decodeEntities( mt.name ) }
												checked={ isChecked }
												onChange={ function () {
													toggleMemberType( 'member_type_invites', mt.id );
												} }
											/>
										);
									} ) }
								</div>
							) }
						</div>
					) }

					{/* Profile Type Joining */}
					{ availableMemberTypes.length > 0 && (
						<div className="bb-admin-group-type-modal__section">
							<h4 className="bb-admin-group-type-modal__section-title">
								{ __( 'Profile Type Joining', 'buddyboss' ) }
							</h4>
							<SelectControl
								value={ formData.member_type_join_mode }
								options={ [
									{ label: __( 'All Profile Types', 'buddyboss' ), value: 'all' },
									{ label: __( 'Selected Profile Types', 'buddyboss' ), value: 'selected' },
									{ label: __( 'None', 'buddyboss' ), value: 'none' },
								] }
								onChange={ function ( val ) { updateField( 'member_type_join_mode', val ); } }
							/>
							<p
								className="bb-admin-group-type-modal__section-description"
								dangerouslySetInnerHTML={ { __html: sanitizeHtml( __( 'Select which profile types can join private groups with this group type without approval. Members restricted by <a href="admin.php?page=bb-settings&tab=groups&panel=access_controls">Group Access</a> settings cannot join, even if their profile type is allowed above.', 'buddyboss' ) ) } }
							/>
							{ 'selected' === formData.member_type_join_mode && (
								<div className="bb-admin-group-type-modal__member-types-grid">
									{ availableMemberTypes.map( function ( mt ) {
										var isChecked = -1 !== formData.member_type_join.indexOf( String( mt.id ) );
										return (
											<CheckboxControl
												key={ mt.id }
												label={ decodeEntities( mt.name ) }
												checked={ isChecked }
												onChange={ function () {
													toggleMemberType( 'member_type_join', mt.id );
												} }
											/>
										);
									} ) }
								</div>
							) }
						</div>
					) }

					{/* Visibility */}
					<div className="bb-admin-group-type-modal__section">
						<SelectControl
							label={ __( 'Visibility', 'buddyboss' ) }
							value={ formData.visibility }
							options={ [
								{ label: __( 'Public', 'buddyboss' ), value: 'public' },
								{ label: __( 'Private', 'buddyboss' ), value: 'private' },
							] }
							onChange={ function ( val ) { updateField( 'visibility', val ); } }
						/>
					</div>

					{/* Label Color */}
					<div className="bb-admin-group-type-modal__section">
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
							<div className="bb-admin-group-type-modal__color-pickers">
								<div className="bb-admin-group-type-modal__color-field">
									<label className="bb-admin-group-type-modal__color-label">
										{ __( 'Background Color', 'buddyboss' ) }
									</label>
									<div className="bb-admin-group-type-modal__color-input-row">
										<input
											type="color"
											value={ formData.label_color.background_color || '#000000' }
											onChange={ function ( e ) { updateLabelColor( 'background_color', e.target.value ); } }
											className="bb-admin-group-type-modal__color-swatch"
											aria-label={ __( 'Background Color', 'buddyboss' ) }
										/>
										<input
											type="text"
											value={ stripHash( formData.label_color.background_color || '000000' ) }
											onChange={ function ( e ) {
												var val = e.target.value.replace( /[^0-9a-fA-F]/g, '' ).substring( 0, 6 );
												updateLabelColor( 'background_color', ensureHash( val ) );
											} }
											className="bb-admin-group-type-modal__color-hex"
											maxLength="6"
											aria-label={ __( 'Background Color hex value', 'buddyboss' ) }
										/>
									</div>
								</div>
								<div className="bb-admin-group-type-modal__color-field">
									<label className="bb-admin-group-type-modal__color-label">
										{ __( 'Text Color', 'buddyboss' ) }
									</label>
									<div className="bb-admin-group-type-modal__color-input-row">
										<input
											type="color"
											value={ formData.label_color.text_color || '#ffffff' }
											onChange={ function ( e ) { updateLabelColor( 'text_color', e.target.value ); } }
											className="bb-admin-group-type-modal__color-swatch"
											aria-label={ __( 'Text Color', 'buddyboss' ) }
										/>
										<input
											type="text"
											value={ stripHash( formData.label_color.text_color || 'FFFFFF' ) }
											onChange={ function ( e ) {
												var val = e.target.value.replace( /[^0-9a-fA-F]/g, '' ).substring( 0, 6 );
												updateLabelColor( 'text_color', ensureHash( val ) );
											} }
											className="bb-admin-group-type-modal__color-hex"
											maxLength="6"
											aria-label={ __( 'Text Color hex value', 'buddyboss' ) }
										/>
									</div>
								</div>
							</div>
						) }
					</div>

					{/* Shortcode (edit mode only) */}
					{ isEditing && groupType && groupType.id && (
						<div className="bb-admin-group-type-modal__section">
							<h4 className="bb-admin-group-type-modal__section-title">
								{ __( 'Shortcode', 'buddyboss' ) }
							</h4>
							<div className="bb-admin-group-type-modal__shortcode-row">
								<input
									type="text"
									readOnly
									value={ '[group type="' + groupType.id + '"]' }
									className="bb-admin-group-type-modal__shortcode-input"
									onClick={ function ( e ) { e.target.select(); } }
								/>
								<button
									type="button"
									className="bb-admin-group-type-modal__shortcode-copy"
									onClick={ function () {
										var shortcode = '[group type="' + groupType.id + '"]';
										if ( ! navigator.clipboard ) {
											return;
										}
										navigator.clipboard.writeText( shortcode ).then( function () {
											window.dispatchEvent( new CustomEvent( 'bb-settings-toast', {
												detail: { status: 'success', message: __( 'Copied to clipboard.', 'buddyboss' ) },
											} ) );
										} ).catch( function () {
											window.dispatchEvent( new CustomEvent( 'bb-settings-toast', {
												detail: { status: 'error', message: __( 'Failed to copy to clipboard.', 'buddyboss' ) },
											} ) );
										} );
									} }
									aria-label={ __( 'Copy shortcode', 'buddyboss' ) }
								>
									<i className="bb-icons-rl bb-icons-rl-copy"></i>
								</button>
							</div>
							<p className="bb-admin-group-type-modal__section-description">
								{ __( 'Add this shortcode to any WordPress page to display all groups of this type on a dedicated page.', 'buddyboss' ) }
							</p>
						</div>
					) }
				</div>

				<div className="bb-admin-settings-modal__footer bb-admin-group-type-modal__footer">
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

export default GroupTypeModal;

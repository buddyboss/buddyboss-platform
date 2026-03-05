/**
 * BuddyBoss Admin Settings 2.0 - Profile Field Modal
 *
 * Modal for creating and editing profile fields. Handles type-specific
 * fields, options, visibility, and member type restrictions.
 *
 * @package BuddyBoss\Core\Administration
 * @since BuddyBoss [BBVERSION]
 */

import { useState, useEffect, useCallback, useMemo } from '@wordpress/element';
import {
	TextControl,
	TextareaControl,
	SelectControl,
	CheckboxControl,
	RadioControl,
	Button,
	Spinner,
	Modal,
} from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { decodeEntities } from '@wordpress/html-entities';
import { saveProfileField } from '../../utils/ajax';

/**
 * Field types that support options (dropdown, checkboxes, radio, etc.).
 *
 * @since BuddyBoss [BBVERSION]
 */
var OPTION_TYPES = [ 'selectbox', 'multiselectbox', 'checkbox', 'radio', 'gender' ];

/**
 * Singleton field types (only one instance allowed across all groups).
 *
 * @since BuddyBoss [BBVERSION]
 */
var SINGLETON_TYPES = [ 'gender', 'socialnetworks', 'membertypes' ];

/**
 * Default gender options.
 *
 * @since BuddyBoss [BBVERSION]
 */
var DEFAULT_GENDER_OPTIONS = [
	{ name: 'Male', is_default: false },
	{ name: 'Female', is_default: false },
	{ name: 'Other', is_default: false },
];

/**
 * Profile Field Modal Component
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param {Object}   props                  Component props.
 * @param {Object}   props.field            Field data (null for new).
 * @param {number}   props.groupId          Field group ID.
 * @param {string}   props.groupName        Field group name (for modal title).
 * @param {Object}   props.fieldTypes       Available field types.
 * @param {Array}    props.memberTypes       Available member types.
 * @param {Array}    props.visibilityLevels  Available visibility levels.
 * @param {Array}    props.socialProviders   Available social network providers.
 * @param {Array}    props.allFieldGroups    All field groups (for singleton check).
 * @param {Function} props.onClose          Close callback.
 * @param {Function} props.onSave           Save success callback.
 * @param {Function} props.setToast         Toast setter.
 * @returns {JSX.Element} Profile field modal.
 */
export function ProfileFieldModal( {
	field,
	groupId,
	groupName,
	fieldTypes,
	memberTypes,
	visibilityLevels,
	socialProviders,
	allFieldGroups,
	onClose,
	onSave,
	setToast,
} ) {

	var isEditing = field && field.id;

	// Form state.
	var nameState = useState( isEditing ? field.name : '' );
	var name = nameState[ 0 ];
	var setName = nameState[ 1 ];

	var typeState = useState( isEditing ? field.type : 'textbox' );
	var type = typeState[ 0 ];
	var setType = typeState[ 1 ];

	var alternateNameState = useState( isEditing ? ( field.alternate_name || '' ) : '' );
	var alternateName = alternateNameState[ 0 ];
	var setAlternateName = alternateNameState[ 1 ];

	var placeholderState = useState( isEditing ? ( field.placeholder || '' ) : '' );
	var placeholder = placeholderState[ 0 ];
	var setPlaceholder = placeholderState[ 1 ];

	var descriptionState = useState( isEditing ? ( field.description || '' ) : '' );
	var description = descriptionState[ 0 ];
	var setDescription = descriptionState[ 1 ];

	var isRequiredState = useState( isEditing ? field.is_required : false );
	var isRequired = isRequiredState[ 0 ];
	var setIsRequired = isRequiredState[ 1 ];

	var isSignupState = useState( isEditing ? !! field.is_signup : false );
	var isSignup = isSignupState[ 0 ];
	var setIsSignup = isSignupState[ 1 ];

	var visibilityState = useState( isEditing ? ( field.visibility || 'public' ) : 'public' );
	var visibility = visibilityState[ 0 ];
	var setVisibility = visibilityState[ 1 ];

	var allowCustomVisibilityState = useState( isEditing ? ( field.allow_custom_visibility || 'allowed' ) : 'allowed' );
	var allowCustomVisibility = allowCustomVisibilityState[ 0 ];
	var setAllowCustomVisibility = allowCustomVisibilityState[ 1 ];

	// Member types.
	var memberTypeModeState = useState( function () {
		if ( isEditing && field.member_types && field.member_types.length > 0 ) {
			return 'selected';
		}
		return 'all';
	} );
	var memberTypeMode = memberTypeModeState[ 0 ];
	var setMemberTypeMode = memberTypeModeState[ 1 ];

	var selectedMemberTypesState = useState( isEditing ? ( field.member_types || [] ) : [] );
	var selectedMemberTypes = selectedMemberTypesState[ 0 ];
	var setSelectedMemberTypes = selectedMemberTypesState[ 1 ];

	// Options (for multi-option types).
	var optionsState = useState( function () {
		if ( isEditing && field.options && field.options.length > 0 ) {
			return field.options.map( function ( opt ) {
				return { name: opt.name, is_default: opt.is_default };
			} );
		}
		return [ { name: '', is_default: false } ];
	} );
	var options = optionsState[ 0 ];
	var setOptions = optionsState[ 1 ];

	// Social networks (selected provider values).
	var socialNetworksState = useState( function () {
		if ( isEditing && 'socialnetworks' === field.type && field.options && field.options.length > 0 ) {
			return field.options.map( function ( opt ) {
				return opt.name;
			} );
		}
		// Default: facebook, twitter, linkedIn.
		return [ 'facebook', 'twitter', 'linkedIn' ];
	} );
	var selectedSocialNetworks = socialNetworksState[ 0 ];
	var setSelectedSocialNetworks = socialNetworksState[ 1 ];

	var isSavingState = useState( false );
	var isSaving = isSavingState[ 0 ];
	var setIsSaving = isSavingState[ 1 ];

	// Reset options when type changes to a type that needs options.
	useEffect( function () {
		if ( isEditing ) {
			return;
		}
		if ( 'gender' === type ) {
			setOptions( DEFAULT_GENDER_OPTIONS.slice() );
		} else if ( OPTION_TYPES.indexOf( type ) >= 0 ) {
			setOptions( [ { name: '', is_default: false } ] );
		}
	}, [ type, isEditing ] );

	/**
	 * Check if a singleton type already exists in any field group.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param {string} fieldType Field type to check.
	 * @returns {boolean} True if the type already exists.
	 */
	function isSingletonExists( fieldType ) {
		if ( SINGLETON_TYPES.indexOf( fieldType ) < 0 ) {
			return false;
		}

		var exists = false;
		allFieldGroups.forEach( function ( group ) {
			if ( group.fields ) {
				group.fields.forEach( function ( f ) {
					// Skip the current field being edited.
					if ( isEditing && f.id === field.id ) {
						return;
					}
					if ( f.type === fieldType ) {
						exists = true;
					}
				} );
			}
		} );
		return exists;
	}

	// Build the type select options with optgroups (memoized since fieldTypes rarely change).
	var typeOptions = useMemo( function () {
		var opts = [];

		if ( fieldTypes.multi_fields && fieldTypes.multi_fields.length > 0 ) {
			opts.push( {
				label: __( '--- Multi Fields ---', 'buddyboss' ),
				value: '',
				disabled: true,
			} );
			fieldTypes.multi_fields.forEach( function ( ft ) {
				opts.push( {
					label: decodeEntities( ft.label ),
					value: ft.value,
				} );
			} );
		}

		if ( fieldTypes.single_fields && fieldTypes.single_fields.length > 0 ) {
			opts.push( {
				label: __( '--- Single Fields ---', 'buddyboss' ),
				value: '',
				disabled: true,
			} );
			fieldTypes.single_fields.forEach( function ( ft ) {
				opts.push( {
					label: decodeEntities( ft.label ),
					value: ft.value,
				} );
			} );
		}

		return opts;
	}, [ fieldTypes ] );

	/**
	 * Add an option to the options list.
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	function addOption() {
		setOptions( options.concat( [ { name: '', is_default: false } ] ) );
	}

	/**
	 * Remove an option from the list.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param {number} index Option index to remove.
	 */
	function removeOption( index ) {
		if ( options.length <= 1 ) {
			return;
		}
		var newOptions = options.slice();
		newOptions.splice( index, 1 );
		setOptions( newOptions );
	}

	/**
	 * Update an option's name.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param {number} index Option index.
	 * @param {string} value New name value.
	 */
	function updateOptionName( index, value ) {
		var newOptions = options.slice();
		newOptions[ index ] = Object.assign( {}, newOptions[ index ], { name: value } );
		setOptions( newOptions );
	}

	/**
	 * Toggle default option.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param {number}  index     Option index.
	 * @param {boolean} allowMulti Whether multiple defaults are allowed.
	 */
	function toggleDefaultOption( index, allowMulti ) {
		var newOptions = options.map( function ( opt, i ) {
			if ( i === index ) {
				return Object.assign( {}, opt, { is_default: ! opt.is_default } );
			}
			if ( ! allowMulti ) {
				return Object.assign( {}, opt, { is_default: false } );
			}
			return opt;
		} );
		setOptions( newOptions );
	}

	/**
	 * Toggle a social network selection.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param {string} providerValue Social network provider value.
	 */
	function toggleSocialNetwork( providerValue ) {
		var newNetworks;
		if ( selectedSocialNetworks.indexOf( providerValue ) >= 0 ) {
			newNetworks = selectedSocialNetworks.filter( function ( v ) {
				return v !== providerValue;
			} );
		} else {
			newNetworks = selectedSocialNetworks.concat( [ providerValue ] );
		}
		setSelectedSocialNetworks( newNetworks );
	}

	/**
	 * Toggle a member type selection.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param {string} typeId Member type ID.
	 */
	function toggleMemberType( typeId ) {
		var newTypes;
		if ( selectedMemberTypes.indexOf( typeId ) >= 0 ) {
			newTypes = selectedMemberTypes.filter( function ( t ) {
				return t !== typeId;
			} );
		} else {
			newTypes = selectedMemberTypes.concat( [ typeId ] );
		}
		setSelectedMemberTypes( newTypes );
	}

	/**
	 * Handle save.
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	function handleSave() {
		if ( ! name.trim() ) {
			setToast( { status: 'error', message: __( 'Field name is required.', 'buddyboss' ) } );
			return;
		}

		// Singleton check (applies to both new and type-changed fields).
		if ( isSingletonExists( type ) ) {
			var singletonMessages = {
				gender: __( 'You can only have one instance of the "Gender" profile field.', 'buddyboss' ),
				socialnetworks: __( 'You can only have one instance of the "Social Network" profile field.', 'buddyboss' ),
				membertypes: __( 'You can only have one instance of the "Profile Type" profile field.', 'buddyboss' ),
			};
			setToast( { status: 'error', message: singletonMessages[ type ] || __( 'This field type can only have one instance.', 'buddyboss' ) } );
			return;
		}

		setIsSaving( true );

		var data = {
			field_id: isEditing ? field.id : 0,
			group_id: groupId,
			name: name.trim(),
			type: type,
			description: description.trim(),
			is_required: isRequired ? 1 : 0,
			alternate_name: alternateName.trim(),
			placeholder: placeholder.trim(),
			visibility: visibility,
			allow_custom_visibility: allowCustomVisibility,
			signup_position: isSignup ? 1 : 0,
		};

		// Member types.
		if ( memberTypes.length > 0 ) {
			data.has_member_types = 'selected' === memberTypeMode ? 1 : 0;
			if ( 'selected' === memberTypeMode ) {
				data.member_types = selectedMemberTypes;
			}
		}

		// Options for multi-option types.
		if ( OPTION_TYPES.indexOf( type ) >= 0 ) {
			var validOptions = options.filter( function ( opt ) {
				return opt.name.trim() !== '';
			} );
			if ( validOptions.length > 0 ) {
				data.options = validOptions;
			}
		}

		// Social networks: send selected providers as options.
		if ( 'socialnetworks' === type && selectedSocialNetworks.length > 0 ) {
			data.options = selectedSocialNetworks.map( function ( providerValue ) {
				return { name: providerValue, is_default: false };
			} );
		}

		saveProfileField( data )
			.then( function ( response ) {
				setIsSaving( false );
				if ( response.success ) {
					setToast( {
						status: 'success',
						message: response.data.message || __( 'Field saved.', 'buddyboss' ),
					} );
					onSave();
				} else {
					setToast( {
						status: 'error',
						message: ( response.data && response.data.message ) || __( 'Failed to save field.', 'buddyboss' ),
					} );
				}
			} )
			.catch( function ( error ) {
				setIsSaving( false );
				setToast( { status: 'error', message: error.message || __( 'Failed to save field.', 'buddyboss' ) } );
			} );
	}

	var showOptions = OPTION_TYPES.indexOf( type ) >= 0;
	var showSocialTrack = 'socialnetworks' === type && socialProviders && socialProviders.length > 0;
	var showPlaceholder = 'textbox' === type || 'textarea' === type || 'number' === type || 'telephone' === type || 'url' === type;
	var allowMultiDefault = 'checkbox' === type || 'multiselectbox' === type;

	return (
		<Modal
			title={ isEditing
				? __( 'Edit Field', 'buddyboss' )
				: ( groupName
					? wp.i18n.sprintf( __( 'Add New Field - %s', 'buddyboss' ), decodeEntities( groupName ) )
					: __( 'Add New Field', 'buddyboss' ) )
			}
			onRequestClose={ onClose }
			className="bb-pf-field-modal bb-admin-settings-modal"
			shouldCloseOnClickOutside={ false }
		>
			<div className="bb-admin-settings-modal__body">
				<div className="bb-admin-settings--divided-section">
					{ /* Name */ }
					<TextControl
						label={ __( 'Name', 'buddyboss' ) }
						value={ name }
						onChange={ setName }
						placeholder={ __( 'Enter field name', 'buddyboss' ) }
						required
					/>
				</div>

				<div className="bb-admin-settings--divided-section">
					{ /* Type */ }
					<SelectControl
						label={ __( 'Type', 'buddyboss' ) }
						value={ type }
						options={ typeOptions }
						onChange={ function ( val ) {
							if ( val ) {
								setType( val );
							}
						} }
						disabled={ isEditing && field && ! field.can_delete }
						help={ __( 'Select the input field type members will use to enter information.', 'buddyboss' ) }
					/>

					{ /* Options (for multi-option types) */ }
					{ showOptions && (
						<div className="bb-pf-field-options">
							{ options.map( function ( option, index ) {
								return (
									<div key={ index } className="bb-pf-option-item">
										<div className="bb-pf-option-item__left">
											<i className="bb-icons-rl bb-icons-rl-list" aria-hidden="true"></i>
											<input
												type="text"
												value={ option.name }
												onChange={ function ( e ) { updateOptionName( index, e.target.value ); } }
												placeholder={ __( 'Option label', 'buddyboss' ) }
												className="bb-pf-option-item__input"
											/>
										</div>
										<div className="bb-pf-option-item__right">
											<button
												type="button"
												className={ 'bb-pf-option-item__default' + ( option.is_default ? ' bb-pf-option-item__default--selected' : '' ) }
												onClick={ function () { toggleDefaultOption( index, allowMultiDefault ); } }
												aria-label={ __( 'Set as default value', 'buddyboss' ) }
											>
												<span className="bb-pf-option-item__radio"></span>
												<span className="bb-pf-option-item__default-label">
													{ __( 'Default Value', 'buddyboss' ) }
												</span>
											</button>
											{ options.length > 1 && (
												<button
													type="button"
													className="bb-pf-option-item__remove"
													onClick={ function () { removeOption( index ); } }
													aria-label={ __( 'Remove option', 'buddyboss' ) }
												>
													<i className="bb-icons-rl bb-icons-rl-trash" aria-hidden="true"></i>
												</button>
											) }
										</div>
									</div>
								);
							} ) }
							<Button
								variant="secondary"
								className="bb-pf-add-option-btn"
								onClick={ addOption }
							>
								<i className="bb-icons-rl bb-icons-rl-plus" aria-hidden="true"></i>
								{ __( 'Add Another Option', 'buddyboss' ) }
							</Button>
						</div>
					) }
				</div>

				{ /* Social Track (for socialnetworks type) */ }
				{ showSocialTrack && (
					<div className="bb-pf-field-social-track bb-admin-settings--divided-section">
						<p className="bb-pf-field-social-track__description">
							{ __( 'Please select the social networks to allow. If entered, they will display as icons in the user\'s profile.', 'buddyboss' ) }
						</p>
						<div className="bb-pf-field-social-track__list">
							{ socialProviders.map( function ( provider ) {
								return (
									<CheckboxControl
										key={ provider.value }
										label={ provider.name }
										checked={ selectedSocialNetworks.indexOf( provider.value ) >= 0 }
										onChange={ function () { toggleSocialNetwork( provider.value ); } }
									/>
								);
							} ) }
						</div>
					</div>
				) }

				{ /* Alternate Title */ }
				<TextControl
					label={ __( 'Alternate Title', 'buddyboss' ) }
					value={ alternateName }
					onChange={ setAlternateName }
					help={ __( 'An alternate title for this field that can be used in specific contexts.', 'buddyboss' ) }
				/>

				{ /* Placeholder (for text-like types) */ }
				{ showPlaceholder && (
					<TextControl
						label={ __( 'Placeholder Text', 'buddyboss' ) }
						value={ placeholder }
						onChange={ setPlaceholder }
						help={ __( 'Placeholder text displayed inside the field when empty.', 'buddyboss' ) }
					/>
				) }

				{ /* Instructions (description) */ }
				<TextareaControl
					label={ __( 'Instructions', 'buddyboss' ) }
					placeholder={ __('Enter instructions text', 'buddyboss') }
					value={ description }
					onChange={ setDescription }
					help={ __( 'Help text shown below the field to guide users.', 'buddyboss' ) }
				/>

				{ /* Member Types */ }
				{ memberTypes.length > 0 && (
					<div className="bb-pf-field-member-types">
						<RadioControl
							label={ __( 'Profile Types', 'buddyboss' ) }
							selected={ memberTypeMode }
							options={ [
								{ label: __( 'All profile types', 'buddyboss' ), value: 'all' },
								{ label: __( 'Selected profile types', 'buddyboss' ), value: 'selected' },
							] }
							onChange={ setMemberTypeMode }
						/>
						{ 'selected' === memberTypeMode && (
							<div className="bb-pf-member-type-checkboxes">
								{ memberTypes.map( function ( mt ) {
									return (
										<CheckboxControl
											key={ mt.id }
											label={ decodeEntities( mt.name ) }
											checked={ selectedMemberTypes.indexOf( mt.id ) >= 0 }
											onChange={ function () { toggleMemberType( mt.id ); } }
										/>
									);
								} ) }
							</div>
						) }
					</div>
				) }

				{ /* Visibility */ }
				{ visibilityLevels.length > 0 && (
					<div className="bb-pf-field-visibility bb-admin-settings--divided-section">
						<SelectControl
							label={ __( 'Default Visibility', 'buddyboss' ) }
							value={ visibility }
							options={ visibilityLevels.map( function ( level ) {
								return { label: decodeEntities( level.label ), value: level.id };
							} ) }
							onChange={ setVisibility }
						/>
						<RadioControl
							label={ __( 'Visibility Override', 'buddyboss' ) }
							selected={ allowCustomVisibility }
							options={ [
								{ label: __( 'Allow members to override', 'buddyboss' ), value: 'allowed' },
								{ label: __( 'Enforce default visibility', 'buddyboss' ), value: 'disabled' },
							] }
							onChange={ setAllowCustomVisibility }
						/>
					</div>
				) }

				{ /* Required */ }
				<CheckboxControl
					label={ __( 'Make this field required', 'buddyboss' ) }
					checked={ isRequired }
					onChange={ setIsRequired }
				/>

				{ /* Show on Signup Form */ }
				<CheckboxControl
					label={ __( 'Show this field on the registration form', 'buddyboss' ) }
					checked={ isSignup }
					onChange={ setIsSignup }
				/>
			</div>

			{ /* Footer */ }
			<div className="bb-admin-settings-modal__footer">
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
					disabled={ isSaving || ! name.trim() }
				>
					{ isEditing ? __( 'Save Changes', 'buddyboss' ) : __( 'Add Field', 'buddyboss') }
				</Button>
			</div>
		</Modal>
	);
}

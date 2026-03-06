/**
 * BuddyBoss Admin Settings 2.0 - Profile Field Modal
 *
 * Modal for creating and editing profile fields. Handles type-specific
 * fields, options, visibility, and member type restrictions.
 *
 * @package BuddyBoss\Core\Administration
 * @since BuddyBoss [BBVERSION]
 */

import { useState, useEffect, useCallback, useMemo, useRef } from '@wordpress/element';
import {
	TextControl,
	TextareaControl,
	CheckboxControl,
	RadioControl,
	Button,
	Spinner,
	Modal,
	SelectControl,
} from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { decodeEntities } from '@wordpress/html-entities';
import { DragDropContext, Droppable, Draggable } from 'react-beautiful-dnd';
import { saveProfileField } from '../../utils/ajax';
import { CustomSelectControl } from '../common/CustomSelectControl';

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
 * Current year at module load time, used for datebox range defaults.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @type {number}
 */
var CURRENT_YEAR = new Date().getFullYear();

/**
 * Standard date format options for the datebox field.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @type {Array}
 */
var DATE_FORMAT_OPTIONS = [
	{ value: 'F j, Y', label: 'F j, Y' },
	{ value: 'Y-m-d', label: 'Y-m-d' },
	{ value: 'm/d/Y', label: 'm/d/Y' },
	{ value: 'd/m/Y', label: 'd/m/Y' },
];

/**
 * Generate an example date string from a PHP date format.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param {string} phpFormat PHP date format string.
 * @return {string} Formatted example date.
 */
function formatDateExample( phpFormat ) {
	var now = new Date();
	var month = now.getMonth() + 1;
	var day = now.getDate();
	var year = now.getFullYear();
	var pad = function ( n ) {
		return n < 10 ? '0' + n : String( n );
	};
	var monthNames = [
		'January', 'February', 'March', 'April', 'May', 'June',
		'July', 'August', 'September', 'October', 'November', 'December',
	];
	var map = {
		'F j, Y': monthNames[ now.getMonth() ] + ' ' + day + ', ' + year,
		'Y-m-d': year + '-' + pad( month ) + '-' + pad( day ),
		'm/d/Y': pad( month ) + '/' + pad( day ) + '/' + year,
		'd/m/Y': pad( day ) + '/' + pad( month ) + '/' + year,
	};
	return map[ phpFormat ] || phpFormat;
}

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

	var visibilityState = useState( isEditing ? ( field.visibility || 'public' ) : 'public' );
	var visibility = visibilityState[ 0 ];
	var setVisibility = visibilityState[ 1 ];

	var allowCustomVisibilityState = useState( isEditing ? ( field.allow_custom_visibility || 'allowed' ) : 'allowed' );
	var allowCustomVisibility = allowCustomVisibilityState[ 0 ];
	var setAllowCustomVisibility = allowCustomVisibilityState[ 1 ];

	// Member types.
	var memberTypeModeState = useState( function () {
		if ( isEditing && 'none' === field.member_type_mode ) {
			return 'none';
		}
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

	// Stable uid counter for draggable option keys.
	var optionUidRef = useRef( 0 );
	function nextOptionUid() {
		optionUidRef.current += 1;
		return 'opt-' + optionUidRef.current;
	}

	// Options (for multi-option types).
	var optionsState = useState( function () {
		if ( isEditing && field.options && field.options.length > 0 ) {
			return field.options.map( function ( opt, i ) {
				optionUidRef.current = i + 1;
				return { uid: 'opt-' + ( i + 1 ), name: opt.name, is_default: opt.is_default };
			} );
		}
		optionUidRef.current = 1;
		return [ { uid: 'opt-1', name: '', is_default: false } ];
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

	// Datebox settings.
	var dateFormatState = useState( isEditing && field.field_settings ? ( field.field_settings.date_format || 'Y-m-d' ) : 'Y-m-d' );
	var dateFormat = dateFormatState[ 0 ];
	var setDateFormat = dateFormatState[ 1 ];

	var dateFormatCustomState = useState( isEditing && field.field_settings ? ( field.field_settings.date_format_custom || '' ) : '' );
	var dateFormatCustom = dateFormatCustomState[ 0 ];
	var setDateFormatCustom = dateFormatCustomState[ 1 ];

	var rangeTypeState = useState( isEditing && field.field_settings ? ( field.field_settings.range_type || 'absolute' ) : 'absolute' );
	var rangeType = rangeTypeState[ 0 ];
	var setRangeType = rangeTypeState[ 1 ];

	var rangeAbsoluteStartState = useState( isEditing && field.field_settings ? ( field.field_settings.range_absolute_start || String( CURRENT_YEAR - 60 ) ) : String( CURRENT_YEAR - 60 ) );
	var rangeAbsoluteStart = rangeAbsoluteStartState[ 0 ];
	var setRangeAbsoluteStart = rangeAbsoluteStartState[ 1 ];

	var rangeAbsoluteEndState = useState( isEditing && field.field_settings ? ( field.field_settings.range_absolute_end || String( CURRENT_YEAR + 10 ) ) : String( CURRENT_YEAR + 10 ) );
	var rangeAbsoluteEnd = rangeAbsoluteEndState[ 0 ];
	var setRangeAbsoluteEnd = rangeAbsoluteEndState[ 1 ];

	var rangeRelativeStartState = useState( function () {
		if ( isEditing && field.field_settings && field.field_settings.range_relative_start !== undefined ) {
			return String( Math.abs( parseInt( field.field_settings.range_relative_start, 10 ) || 10 ) );
		}
		return '10';
	} );
	var rangeRelativeStart = rangeRelativeStartState[ 0 ];
	var setRangeRelativeStart = rangeRelativeStartState[ 1 ];

	var rangeRelativeStartTypeState = useState( function () {
		if ( isEditing && field.field_settings && field.field_settings.range_relative_start !== undefined ) {
			return parseInt( field.field_settings.range_relative_start, 10 ) <= 0 ? 'past' : 'future';
		}
		return 'past';
	} );
	var rangeRelativeStartType = rangeRelativeStartTypeState[ 0 ];
	var setRangeRelativeStartType = rangeRelativeStartTypeState[ 1 ];

	var rangeRelativeEndState = useState( function () {
		if ( isEditing && field.field_settings && field.field_settings.range_relative_end !== undefined ) {
			return String( Math.abs( parseInt( field.field_settings.range_relative_end, 10 ) || 20 ) );
		}
		return '20';
	} );
	var rangeRelativeEnd = rangeRelativeEndState[ 0 ];
	var setRangeRelativeEnd = rangeRelativeEndState[ 1 ];

	var rangeRelativeEndTypeState = useState( function () {
		if ( isEditing && field.field_settings && field.field_settings.range_relative_end !== undefined ) {
			return parseInt( field.field_settings.range_relative_end, 10 ) <= 0 ? 'past' : 'future';
		}
		return 'future';
	} );
	var rangeRelativeEndType = rangeRelativeEndTypeState[ 0 ];
	var setRangeRelativeEndType = rangeRelativeEndTypeState[ 1 ];

	// Telephone settings.
	var phoneFormatState = useState( isEditing && field.field_settings ? ( field.field_settings.phone_format || 'international' ) : 'international' );
	var phoneFormat = phoneFormatState[ 0 ];
	var setPhoneFormat = phoneFormatState[ 1 ];

	var isSavingState = useState( false );
	var isSaving = isSavingState[ 0 ];
	var setIsSaving = isSavingState[ 1 ];

	// Reset options when type changes to a type that needs options.
	useEffect( function () {
		if ( isEditing ) {
			return;
		}
		if ( 'gender' === type ) {
			optionUidRef.current = 0;
			setOptions( DEFAULT_GENDER_OPTIONS.map( function ( opt ) {
				return Object.assign( { uid: nextOptionUid() }, opt );
			} ) );
		} else if ( OPTION_TYPES.indexOf( type ) >= 0 ) {
			optionUidRef.current = 0;
			setOptions( [ { uid: nextOptionUid(), name: '', is_default: false } ] );
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

	/**
	 * Icon mapping for profile field types.
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	var FIELD_TYPE_ICONS = {
		checkbox: 'check-circle',
		selectbox: 'caret-circle-down',
		radio: 'radio-button',
		multiselectbox: 'checks',
		gender: 'gender-intersex',
		membertypes: 'users',
		socialnetworks: 'fediverse-logo',
		datebox: 'calendar-heart',
		number: 'number-circle-one',
		textarea: 'paragraph',
		telephone: 'phone',
		textbox: 'text-t',
		url: 'globe',
	};

	// Build grouped options for CustomSelectControl (memoized since fieldTypes rarely change).
	var typeGroups = useMemo( function () {
		var result = [];

		if ( fieldTypes.multi_fields && fieldTypes.multi_fields.length > 0 ) {
			result.push( {
				label: __( 'Multi Fields', 'buddyboss' ),
				options: fieldTypes.multi_fields.map( function ( ft ) {
					return {
						label: decodeEntities( ft.label ),
						value: ft.value,
						icon: FIELD_TYPE_ICONS[ ft.value ] || '',
					};
				} ),
			} );
		}

		if ( fieldTypes.single_fields && fieldTypes.single_fields.length > 0 ) {
			result.push( {
				label: __( 'Single Fields', 'buddyboss' ),
				options: fieldTypes.single_fields.map( function ( ft ) {
					return {
						label: decodeEntities( ft.label ),
						value: ft.value,
						icon: FIELD_TYPE_ICONS[ ft.value ] || '',
					};
				} ),
			} );
		}

		return result;
	}, [ fieldTypes ] );

	/**
	 * Add an option to the options list.
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	function addOption() {
		setOptions( options.concat( [ { uid: nextOptionUid(), name: '', is_default: false } ] ) );
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
		// Gender: Male (0) and Female (1) are never removable.
		if ( 'gender' === type && index < 2 ) {
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
	 * Handle drag end for field options reordering.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param {Object} result Drag result from react-beautiful-dnd.
	 */
	function handleOptionDragEnd( result ) {
		if ( ! result.destination || result.destination.index === result.source.index ) {
			return;
		}
		var items = Array.from( options );
		var moved = items.splice( result.source.index, 1 )[ 0 ];
		items.splice( result.destination.index, 0, moved );
		setOptions( items );
	}

	/**
	 * Handle drag end for social networks reordering.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param {Object} result Drag result from react-beautiful-dnd.
	 */
	function handleSocialDragEnd( result ) {
		if ( ! result.destination || result.destination.index === result.source.index ) {
			return;
		}
		var items = Array.from( selectedSocialNetworks );
		var moved = items.splice( result.source.index, 1 )[ 0 ];
		items.splice( result.destination.index, 0, moved );
		setSelectedSocialNetworks( items );
	}

	/**
	 * Change a social network selection at a given index.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param {number} index Index to update.
	 * @param {string} newValue New provider value.
	 */
	function changeSocialNetwork( index, newValue ) {
		// Prevent duplicate selections.
		if ( selectedSocialNetworks.indexOf( newValue ) >= 0 && selectedSocialNetworks[ index ] !== newValue ) {
			return;
		}
		var updated = selectedSocialNetworks.slice();
		updated[ index ] = newValue;
		setSelectedSocialNetworks( updated );
	}

	/**
	 * Remove a social network at a given index.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param {number} index Index to remove.
	 */
	function removeSocialNetwork( index ) {
		var updated = selectedSocialNetworks.filter( function ( _v, i ) {
			return i !== index;
		} );
		setSelectedSocialNetworks( updated );
	}

	/**
	 * Add a new social network row with the first available (unselected) provider.
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	function addSocialNetwork() {
		// Find first provider not already selected.
		var available = socialProviders.filter( function ( p ) {
			return selectedSocialNetworks.indexOf( p.value ) < 0;
		} );
		if ( available.length > 0 ) {
			setSelectedSocialNetworks( selectedSocialNetworks.concat( [ available[ 0 ].value ] ) );
		}
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
		};

		// Member types.
		if ( memberTypes.length > 0 ) {
			if ( 'selected' === memberTypeMode ) {
				data.has_member_types = 1;
				data.member_types = selectedMemberTypes;
			} else if ( 'none' === memberTypeMode ) {
				data.has_member_types = 1;
				data.member_types = [ 'null' ];
			} else {
				data.has_member_types = 0;
			}
		}

		// Options for multi-option types.
		if ( OPTION_TYPES.indexOf( type ) >= 0 ) {
			var validOptions = options.filter( function ( opt ) {
				return opt.name.trim() !== '';
			} );
			if ( validOptions.length > 0 ) {
				data.options = validOptions.map( function ( opt ) {
					return { name: opt.name, is_default: opt.is_default };
				} );
			}

			// Gender option order (legacy compatibility).
			if ( 'gender' === type && validOptions.length > 0 ) {
				data.gender_option_order = validOptions.map( function ( opt ) {
					return opt.name;
				} ).join( ',' );
			}
		}

		// Social networks: send selected providers as options.
		if ( 'socialnetworks' === type && selectedSocialNetworks.length > 0 ) {
			data.options = selectedSocialNetworks.map( function ( providerValue ) {
				return { name: providerValue, is_default: false };
			} );
		}

		// Date field settings.
		if ( 'datebox' === type ) {
			// Validate range: start must be before end.
			if ( 'absolute' === rangeType && parseInt( rangeAbsoluteStart, 10 ) >= parseInt( rangeAbsoluteEnd, 10 ) ) {
				setToast( { status: 'error', message: __( 'Start year must be before end year.', 'buddyboss' ) } );
				setIsSaving( false );
				return;
			}

			if ( 'relative' === rangeType ) {
				var resolvedStart = CURRENT_YEAR + ( 'past' === rangeRelativeStartType ? -1 : 1 ) * parseInt( rangeRelativeStart, 10 );
				var resolvedEnd = CURRENT_YEAR + ( 'past' === rangeRelativeEndType ? -1 : 1 ) * parseInt( rangeRelativeEnd, 10 );
				if ( resolvedStart >= resolvedEnd ) {
					setToast( { status: 'error', message: __( 'Start year must be before end year.', 'buddyboss' ) } );
					setIsSaving( false );
					return;
				}
			}

			data.field_settings = {
				date_format: dateFormat,
				date_format_custom: 'custom' === dateFormat ? dateFormatCustom : '',
				range_type: rangeType,
				range_absolute_start: rangeAbsoluteStart,
				range_absolute_end: rangeAbsoluteEnd,
				range_relative_start: rangeRelativeStart,
				range_relative_start_type: rangeRelativeStartType,
				range_relative_end: rangeRelativeEnd,
				range_relative_end_type: rangeRelativeEndType,
			};
		}

		// Phone field settings.
		if ( 'telephone' === type ) {
			data.field_settings = {
				phone_format: phoneFormat,
			};
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
				? ( groupName
					? wp.i18n.sprintf( __( 'Edit Field - %s', 'buddyboss' ), decodeEntities( groupName ) )
					: __( 'Edit Field', 'buddyboss' ) )
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
					<CustomSelectControl
						label={ __( 'Type', 'buddyboss' ) }
						value={ type }
						groups={ typeGroups }
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
							<DragDropContext onDragEnd={ handleOptionDragEnd }>
								<Droppable droppableId="field-options">
									{ function ( provided ) {
										return (
											<div
												ref={ provided.innerRef }
												{ ...provided.droppableProps }
												className="bb-pf-field-options__list"
											>
												{ options.map( function ( option, index ) {
													return (
														<Draggable key={ option.uid } draggableId={ option.uid } index={ index }>
															{ function ( providedDrag, snapshot ) {
																return (
																	<div
																		ref={ providedDrag.innerRef }
																		{ ...providedDrag.draggableProps }
																		className={ 'bb-pf-option-item' + ( snapshot.isDragging ? ' is-dragging' : '' ) }
																	>
																		<div className="bb-pf-option-item__left">
																			<i
																				className="bb-icons-rl bb-icons-rl-list"
																				{ ...providedDrag.dragHandleProps }
																				aria-label={ __( 'Reorder option', 'buddyboss' ) }
																			></i>
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
																				<span className={ allowMultiDefault ? "bb-pf-option-item__checkbox" : "bb-pf-option-item__radio" }></span>
																				<span className="bb-pf-option-item__default-label">
																					{ __( 'Default Value', 'buddyboss' ) }
																				</span>
																			</button>
																			{ ( 'gender' === type ? index >= 2 : options.length > 1 ) && (
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
															} }
														</Draggable>
													);
												} ) }
												{ provided.placeholder }
											</div>
										);
									} }
								</Droppable>
							</DragDropContext>
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
						<h4 className="bb-pf-field-social-track__label">
							{ __( 'Social Type', 'buddyboss' ) }
						</h4>
						<p className="bb-pf-field-social-track__description">
							{ __( 'Please select the social networks to allow. If entered, they will display as icons in the user\'s profile.', 'buddyboss' ) }
						</p>
						<div className="bb-pf-field-options">
							<DragDropContext onDragEnd={ handleSocialDragEnd }>
								<Droppable droppableId="social-options">
									{ function ( provided ) {
										return (
											<div
												ref={ provided.innerRef }
												{ ...provided.droppableProps }
												className="bb-pf-field-options__list"
											>
												{ selectedSocialNetworks.map( function ( networkValue, index ) {
													return (
														<Draggable key={ networkValue } draggableId={ networkValue } index={ index }>
															{ function ( providedDrag, snapshot ) {
																return (
																	<div
																		ref={ providedDrag.innerRef }
																		{ ...providedDrag.draggableProps }
																		className={ 'bb-pf-option-item' + ( snapshot.isDragging ? ' is-dragging' : '' ) }
																	>
																		<div className="bb-pf-option-item__left">
																			<i
																				className="bb-icons-rl bb-icons-rl-list"
																				{ ...providedDrag.dragHandleProps }
																				aria-label={ __( 'Reorder option', 'buddyboss' ) }
																			></i>
																			<select
																				className="bb-pf-option-item__select"
																				value={ networkValue }
																				onChange={ function ( e ) { changeSocialNetwork( index, e.target.value ); } }
																				aria-label={ __( 'Social network provider', 'buddyboss' ) }
																			>
																				{ socialProviders.filter( function ( provider ) {
																					return provider.value === networkValue || selectedSocialNetworks.indexOf( provider.value ) < 0;
																				} ).map( function ( provider ) {
																					return (
																						<option
																							key={ provider.value }
																							value={ provider.value }
																						>
																							{ decodeEntities( provider.name ) }
																						</option>
																					);
																				} ) }
																			</select>
																		</div>
																		<div className="bb-pf-option-item__right">
																			{ index > 0 && (
																				<button
																					type="button"
																					className="bb-pf-option-item__remove"
																					onClick={ function () { removeSocialNetwork( index ); } }
																					aria-label={ __( 'Remove option', 'buddyboss' ) }
																				>
																					<i className="bb-icons-rl bb-icons-rl-trash" aria-hidden="true"></i>
																				</button>
																			) }
																		</div>
																	</div>
																);
															} }
														</Draggable>
													);
												} ) }
												{ provided.placeholder }
											</div>
										);
									} }
								</Droppable>
							</DragDropContext>
							{ selectedSocialNetworks.length < socialProviders.length && (
								<Button
									variant="secondary"
									className="bb-pf-add-option-btn"
									onClick={ addSocialNetwork }
								>
									<i className="bb-icons-rl bb-icons-rl-plus" aria-hidden="true"></i>
									{ __( 'Add Another Option', 'buddyboss' ) }
								</Button>
							) }
						</div>
					</div>
				) }

				{ /* Date Selector Settings (datebox type) */ }
				{ 'datebox' === type && (
					<div className="bb-pf-field-datebox-settings bb-admin-settings--divided-section">
						<fieldset className="bb-pf-datebox-format">
							<legend className="bb-pf-datebox-format__legend">
								{ __( 'Date format', 'buddyboss' ) }
							</legend>
							<div className="bb-pf-datebox-format__options">
								{ DATE_FORMAT_OPTIONS.map( function ( opt ) {
									return (
										<div className="bb-pf-datebox-format__option" key={ opt.value }>
											<label>
												<input
													type="radio"
													name="bb-pf-date-format"
													value={ opt.value }
													checked={ opt.value === dateFormat }
													onChange={ function () { setDateFormat( opt.value ); } }
												/>
												<span className="bb-pf-datebox-format__example">
													{ formatDateExample( opt.value ) }
												</span>
												<code className="bb-pf-datebox-format__code">
													{ opt.label }
												</code>
											</label>
										</div>
									);
								} ) }
								<div className="bb-pf-datebox-format__option" key="elapsed">
									<label>
										<input
											type="radio"
											name="bb-pf-date-format"
											value="elapsed"
											checked={ 'elapsed' === dateFormat }
											onChange={ function () { setDateFormat( 'elapsed' ); } }
										/>
										<span className="bb-pf-datebox-format__example">
											{ __( 'Time elapsed', 'buddyboss' ) }
										</span>
										<code className="bb-pf-datebox-format__code">
											{ __( '4 years ago', 'buddyboss' ) }
										</code>
										{ ', ' }
										<code className="bb-pf-datebox-format__code">
											{ __( '4 years from now', 'buddyboss' ) }
										</code>
									</label>
								</div>
								<div className="bb-pf-datebox-format__option" key="custom">
									<label>
										<input
											type="radio"
											name="bb-pf-date-format"
											value="custom"
											checked={ 'custom' === dateFormat }
											onChange={ function () { setDateFormat( 'custom' ); } }
										/>
										<span className="bb-pf-datebox-format__example">
											{ __( 'Custom:', 'buddyboss' ) }
										</span>
										<input
											type="text"
											className="bb-pf-datebox-format__custom-input"
											value={ dateFormatCustom }
											onChange={ function ( e ) { setDateFormatCustom( e.target.value ); } }
											disabled={ 'custom' !== dateFormat }
										/>
									</label>
								</div>
							</div>
							<a
								className="bb-pf-datebox-format__doc-link"
								href="https://wordpress.org/support/article/formatting-date-and-time/"
								target="_blank"
								rel="noopener noreferrer"
							>
								{ __( 'Documentation on date and time formatting.', 'buddyboss' ) }
							</a>
						</fieldset>

						<div className="bb-pf-datebox-range">
							<RadioControl
								label={ __( 'Range', 'buddyboss' ) }
								selected={ rangeType }
								options={ [
									{ label: __( 'Absolute', 'buddyboss' ), value: 'absolute' },
									{ label: __( 'Relative', 'buddyboss' ), value: 'relative' },
								] }
								onChange={ setRangeType }
							/>

							{ 'absolute' === rangeType && (
								<div className="bb-pf-datebox-range__values">
									<TextControl
										label={ __( 'Start', 'buddyboss' ) }
										type="number"
										value={ rangeAbsoluteStart }
										onChange={ setRangeAbsoluteStart }
									/>
									<TextControl
										label={ __( 'End', 'buddyboss' ) }
										type="number"
										value={ rangeAbsoluteEnd }
										onChange={ setRangeAbsoluteEnd }
									/>
								</div>
							) }

							{ 'relative' === rangeType && (
								<div className="bb-pf-datebox-range__values">
									<div className="bb-pf-datebox-range__relative-row">
										<TextControl
											label={ __( 'Start', 'buddyboss' ) }
											type="number"
											value={ rangeRelativeStart }
											onChange={ setRangeRelativeStart }
										/>
										<SelectControl
											label={ __( 'Direction', 'buddyboss' ) }
											value={ rangeRelativeStartType }
											options={ [
												{ label: __( 'years ago', 'buddyboss' ), value: 'past' },
												{ label: __( 'years from now', 'buddyboss' ), value: 'future' },
											] }
											onChange={ setRangeRelativeStartType }
										/>
									</div>
									<div className="bb-pf-datebox-range__relative-row">
										<TextControl
											label={ __( 'End', 'buddyboss' ) }
											type="number"
											value={ rangeRelativeEnd }
											onChange={ setRangeRelativeEnd }
										/>
										<SelectControl
											label={ __( 'Direction', 'buddyboss' ) }
											value={ rangeRelativeEndType }
											options={ [
												{ label: __( 'years ago', 'buddyboss' ), value: 'past' },
												{ label: __( 'years from now', 'buddyboss' ), value: 'future' },
											] }
											onChange={ setRangeRelativeEndType }
										/>
									</div>
								</div>
							) }
						</div>
					</div>
				) }

				{ /* Telephone Settings (telephone type) */ }
				{ 'telephone' === type && (
					<div className="bb-pf-field-telephone-settings bb-admin-settings--divided-section">
						<SelectControl
							label={ __( 'Phone Format', 'buddyboss' ) }
							value={ phoneFormat }
							options={ [
								{ label: __( 'International', 'buddyboss' ), value: 'international' },
								{ label: __( 'Standard - (###) ###-####', 'buddyboss' ), value: 'standard' },
							] }
							onChange={ setPhoneFormat }
							help={ __( 'Select the format for phone number input.', 'buddyboss' ) }
						/>
					</div>
				) }

				{ /* Alternate Title */ }
				<TextControl
					label={ __( 'Alternate Title (Optional)', 'buddyboss' ) }
					value={ alternateName }
					onChange={ setAlternateName }
					placeholder={ __( 'Enter alternate text', 'buddyboss' ) }
					help={ __( 'Appears as the input title. If left blank, the field name will be used instead.', 'buddyboss' ) }
				/>

				{ /* Placeholder (for text-like types) */ }
				{ showPlaceholder && (
					<TextControl
						label={ __( 'Placeholder Text (Optional)', 'buddyboss' ) }
						value={ placeholder }
						onChange={ setPlaceholder }
						placeholder={ __( 'Enter placeholder text', 'buddyboss' ) }
						help={ __( 'Appears inside the input field when no input is entered.', 'buddyboss' ) }
					/>
				) }

				{ /* Instructions (description) */ }
				<TextareaControl
					label={ __( 'Instructions (Optional)', 'buddyboss' ) }
					placeholder={ __( 'Enter instructions text', 'buddyboss' ) }
					value={ description }
					onChange={ setDescription }
					help={ __( 'Appears below the input. Provide instructions or examples for how users should respond.', 'buddyboss' ) }
				/>

				{ /* Member Types */ }
				{ memberTypes.length > 0 && (
					<div className="bb-pf-field-member-types">
						<SelectControl
							label={ __( 'Profile Types', 'buddyboss' ) }
							help={ __( 'Select which profile types this field should be available to.', 'buddyboss' ) }
							value={ memberTypeMode }
							options={ [
								{ label: __( 'All Profile Types', 'buddyboss' ), value: 'all' },
								{ label: __( 'Selected Profile Types', 'buddyboss' ), value: 'selected' },
								{ label: __( 'No Profile Type Users', 'buddyboss' ), value: 'none' },
							] }
							onChange={ setMemberTypeMode }
						/>
						{ 'selected' === memberTypeMode && (
							<div className="bb-pf-member-type-checkboxes bb-pf-member-types-grid">
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
							label={ __( 'Visibility', 'buddyboss' ) }
							value={ visibility }
							options={ visibilityLevels.map( function ( level ) {
								return { label: decodeEntities( level.label ), value: level.id };
							} ) }
							onChange={ setVisibility }
						/>
						<RadioControl
							selected={ allowCustomVisibility }
							options={ [
								{ label: __( 'Allow members to override', 'buddyboss' ), value: 'allowed' },
								{ label: __( 'Enforce Visibility', 'buddyboss' ), value: 'disabled' },
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
					{ __( 'Save', 'buddyboss' ) }
				</Button>
			</div>
		</Modal>
	);
}

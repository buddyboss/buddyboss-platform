/**
 * BuddyBoss Admin Settings 2.0 - Profile Types Screen
 *
 * Custom panel screen for managing profile/member types and settings.
 *
 * @package BuddyBoss\Core\Administration
 * @since BuddyBoss [BBVERSION]
 */

import { useState, useEffect, useCallback, useRef } from '@wordpress/element';
import { ToggleControl, SelectControl, Spinner } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { decodeEntities } from '@wordpress/html-entities';
import { getMemberTypes, deleteMemberType, getPlatformSettings, savePlatformSetting } from '../utils/ajax';
import { sanitizeHtml } from '../utils/sanitize';
import { Toast } from '../components/Toast';
import { HelpIcon } from '../components/HelpIcon';
import { ProfileTypeModal } from '../components/modals/ProfileTypeModal';

/**
 * Profile Types Screen Component
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param {Object}   props             Component props.
 * @param {Function} props.onNavigate  Navigation handler.
 * @param {string}   props.helpUrl     Help URL for this panel.
 * @param {Function} props.onHelpClick Help icon click handler.
 * @param {Object}   props.feature     Feature data from FeatureSettingsScreen (includes field definitions).
 * @param {string}   props.activePanelId Active panel ID.
 * @returns {JSX.Element} Profile types screen.
 */
export function ProfileTypeScreen( { onNavigate, helpUrl, onHelpClick, feature, activePanelId } ) {

	/**
	 * Get a field's description from the PHP-registered feature data.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param {string} fieldName The field option name.
	 * @returns {string} The field description HTML or empty string.
	 */
	function getFieldDescription( fieldName ) {
		if ( ! feature || ! feature.side_panels ) {
			return '';
		}
		var panel = feature.side_panels.find( function ( p ) {
			return p.id === activePanelId;
		} );
		if ( ! panel || ! panel.sections ) {
			return '';
		}
		for ( var i = 0; i < panel.sections.length; i++ ) {
			var fields = panel.sections[ i ].fields || [];
			for ( var j = 0; j < fields.length; j++ ) {
				if ( fields[ j ].name === fieldName ) {
					return fields[ j ].description || '';
				}
			}
		}
		return '';
	}

	var memberTypesState = useState( [] );
	var memberTypes = memberTypesState[ 0 ];
	var setMemberTypes = memberTypesState[ 1 ];

	var isLoadingState = useState( true );
	var isLoading = isLoadingState[ 0 ];
	var setIsLoading = isLoadingState[ 1 ];

	var enableProfileTypesState = useState( false );
	var enableProfileTypes = enableProfileTypesState[ 0 ];
	var setEnableProfileTypes = enableProfileTypesState[ 1 ];

	var displayOnProfileState = useState( false );
	var displayOnProfile = displayOnProfileState[ 0 ];
	var setDisplayOnProfile = displayOnProfileState[ 1 ];

	var defaultProfileTypeState = useState( '' );
	var defaultProfileType = defaultProfileTypeState[ 0 ];
	var setDefaultProfileType = defaultProfileTypeState[ 1 ];

	var settingsLoadingState = useState( true );
	var settingsLoading = settingsLoadingState[ 0 ];
	var setSettingsLoading = settingsLoadingState[ 1 ];

	var isModalOpenState = useState( false );
	var isModalOpen = isModalOpenState[ 0 ];
	var setIsModalOpen = isModalOpenState[ 1 ];

	var editingTypeState = useState( null );
	var editingType = editingTypeState[ 0 ];
	var setEditingType = editingTypeState[ 1 ];

	var openMenuIdState = useState( null );
	var openMenuId = openMenuIdState[ 0 ];
	var setOpenMenuId = openMenuIdState[ 1 ];

	var groupTypesState = useState( [] );
	var groupTypes = groupTypesState[ 0 ];
	var setGroupTypes = groupTypesState[ 1 ];

	var wpRolesState = useState( [] );
	var wpRoles = wpRolesState[ 0 ];
	var setWpRoles = wpRolesState[ 1 ];


	var toastState = useState( null );
	var toast = toastState[ 0 ];
	var setToast = toastState[ 1 ];

	// Load platform settings.
	useEffect( function () {
		var controller = new AbortController();
		setSettingsLoading( true );
		getPlatformSettings( 'bp-member-type-enable-disable,bp-member-type-display-on-profile,bp-member-type-default-on-registration', { signal: controller.signal } )
			.then( function ( response ) {
				if ( response.success && response.data ) {
					setEnableProfileTypes( !! parseInt( response.data[ 'bp-member-type-enable-disable' ] ) );
					setDisplayOnProfile( !! parseInt( response.data[ 'bp-member-type-display-on-profile' ] ) );
					setDefaultProfileType( response.data[ 'bp-member-type-default-on-registration' ] || '' );
				}
				setSettingsLoading( false );
			} )
			.catch( function ( err ) {
				if ( 'AbortError' !== err.name ) {
					setSettingsLoading( false );
				}
			} );

		return function () { controller.abort(); };
	}, [] );

	// Load member types.
	var loadMemberTypes = useCallback( function () {
		setIsLoading( true );
		getMemberTypes()
			.then( function ( response ) {
				if ( response.success && response.data ) {
					setMemberTypes( response.data.member_types || [] );
					setGroupTypes( response.data.group_types || [] );
					setWpRoles( response.data.wp_roles || [] );
				}
				setIsLoading( false );
			} )
			.catch( function () {
				setIsLoading( false );
				setToast( { status: 'error', message: __( 'Failed to load profile types.', 'buddyboss' ) } );
			} );
	}, [] );

	useEffect( function () {
		loadMemberTypes();
	}, [ loadMemberTypes ] );

	// Close menu on outside click.
	useEffect( function () {
		if ( null === openMenuId ) {
			return;
		}

		function handleMouseDown( e ) {
			if ( ! e.target.closest( '.bb-admin-profile-types__menu-wrapper' ) ) {
				setOpenMenuId( null );
			}
		}

		document.addEventListener( 'mousedown', handleMouseDown );
		return function () {
			document.removeEventListener( 'mousedown', handleMouseDown );
		};
	}, [ openMenuId ] );

	// Handle settings toggle.
	var handleSettingChange = useCallback( function ( optionName, newValue ) {
		var prevProfileTypes = enableProfileTypes;
		var prevDisplayOnProfile = displayOnProfile;

		// Optimistic update.
		if ( 'bp-member-type-enable-disable' === optionName ) {
			setEnableProfileTypes( newValue );
		} else if ( 'bp-member-type-display-on-profile' === optionName ) {
			setDisplayOnProfile( newValue );
		}

		savePlatformSetting( optionName, newValue ? 1 : 0 )
			.then( function ( response ) {
				if ( response.success ) {
					setToast( { status: 'success', message: __( 'Setting saved.', 'buddyboss' ) } );
				} else {
					// Rollback.
					setEnableProfileTypes( prevProfileTypes );
					setDisplayOnProfile( prevDisplayOnProfile );
					setToast( { status: 'error', message: __( 'Failed to save setting.', 'buddyboss' ) } );
				}
			} )
			.catch( function () {
				// Rollback.
				setEnableProfileTypes( prevProfileTypes );
				setDisplayOnProfile( prevDisplayOnProfile );
				setToast( { status: 'error', message: __( 'Failed to save setting.', 'buddyboss' ) } );
			} );
	}, [ enableProfileTypes, displayOnProfile ] );

	// Handle default profile type select change.
	var handleDefaultTypeChange = useCallback( function ( newValue ) {
		var prevDefault = defaultProfileType;
		setDefaultProfileType( newValue );

		savePlatformSetting( 'bp-member-type-default-on-registration', newValue )
			.then( function ( response ) {
				if ( response.success ) {
					setToast( { status: 'success', message: __( 'Setting saved.', 'buddyboss' ) } );
				} else {
					setDefaultProfileType( prevDefault );
					setToast( { status: 'error', message: __( 'Failed to save setting.', 'buddyboss' ) } );
				}
			} )
			.catch( function () {
				setDefaultProfileType( prevDefault );
				setToast( { status: 'error', message: __( 'Failed to save setting.', 'buddyboss' ) } );
			} );
	}, [ defaultProfileType ] );

	// Handle delete.
	var handleDelete = useCallback( function ( typeId ) {
		if ( ! window.confirm( __( 'Are you sure you want to delete this profile type?', 'buddyboss' ) ) ) {
			return;
		}

		setOpenMenuId( null );

		deleteMemberType( typeId )
			.then( function ( response ) {
				if ( response.success ) {
					setMemberTypes( function ( prev ) {
						return prev.filter( function ( t ) {
							return t.id !== typeId;
						} );
					} );
					setToast( { status: 'success', message: __( 'Profile type deleted.', 'buddyboss' ) } );
				} else {
					setToast( { status: 'error', message: response.data?.message || __( 'Failed to delete profile type.', 'buddyboss' ) } );
				}
			} )
			.catch( function () {
				setToast( { status: 'error', message: __( 'Failed to delete profile type.', 'buddyboss' ) } );
			} );
	}, [] );

	// Handle edit.
	var handleEdit = useCallback( function ( memberType ) {
		setOpenMenuId( null );
		setEditingType( memberType );
		setIsModalOpen( true );
	}, [] );

	// Handle add new.
	var handleAddNewType = useCallback( function () {
		setEditingType( null );
		setIsModalOpen( true );
	}, [] );

	// Handle modal save.
	var handleModalSave = useCallback( function () {
		setIsModalOpen( false );
		setEditingType( null );
		loadMemberTypes();
	}, [ loadMemberTypes ] );

	// Handle modal close.
	var handleModalClose = useCallback( function () {
		setIsModalOpen( false );
		setEditingType( null );
	}, [] );

	// Build default type options for the select.
	var defaultTypeOptions = [ { label: __( '----', 'buddyboss' ), value: '' } ];
	memberTypes.forEach( function ( type ) {
		if ( type.key ) {
			defaultTypeOptions.push( {
				label: decodeEntities( type.plural_label || type.post_title ),
				value: type.key,
			} );
		}
	} );

	/**
	 * Get visibility badge info for a member type.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param {Object} type Member type object.
	 * @returns {Object} Object with icon, label, and modifier class.
	 */
	function getVisibilityBadge( type ) {
		if ( 'private' === type.visibility ) {
			return { icon: 'bb-icons-rl-lock', label: __( 'Private', 'buddyboss' ), modifier: ' bb-admin-profile-types__list-item-visibility-badge--private' };
		}
		if ( 'draft' === type.visibility ) {
			return { icon: 'bb-icons-rl-file-text', label: __( 'Draft', 'buddyboss' ), modifier: ' bb-admin-profile-types__list-item-visibility-badge--draft' };
		}
		if ( 'password_protected' === type.visibility ) {
			return { icon: 'bb-icons-rl-lock-key', label: __( 'Password Protected', 'buddyboss' ), modifier: ' bb-admin-profile-types__list-item-visibility-badge--password' };
		}
		return { icon: 'bb-icons-rl-globe-simple', label: __( 'Public', 'buddyboss' ), modifier: '' };
	}

	return (
		<div className="bb-admin-profile-types">
			{/* Card 1: Profile Type Settings */}
			<div className="bb-admin-profile-types__card">
				<div className="bb-admin-profile-types__card-header">
					<h3 className="bb-admin-profile-types__card-title">
						{ __( 'Profile Type Settings', 'buddyboss' ) }
					</h3>
					{ helpUrl && (
						<HelpIcon
							onClick={ onHelpClick }
							contentId={ helpUrl }
						/>
					) }
				</div>
				<div className="bb-admin-profile-types__card-body">
					{ settingsLoading ? (
						<div className="bb-admin-loading"><Spinner /></div>
					) : (
						<>
							<div className="bb-admin-profile-types__setting-row">
								<span className="bb-admin-profile-types__setting-label">
									{ __( 'Profile Types', 'buddyboss' ) }
								</span>
								<div className="bb-admin-profile-types__setting-control">
									<ToggleControl
										label={ __( 'Enable profile types', 'buddyboss' ) }
										checked={ enableProfileTypes }
										onChange={ function ( val ) {
											handleSettingChange( 'bp-member-type-enable-disable', val );
										} }
									/>
									{ getFieldDescription( 'bp-member-type-enable-disable' ) && (
										<span
											className="bb-admin-profile-types__setting-description"
											dangerouslySetInnerHTML={ { __html: sanitizeHtml( getFieldDescription( 'bp-member-type-enable-disable' ) ) } }
										/>
									) }
								</div>
							</div>
							{ enableProfileTypes && (
								<>
									<div className="bb-admin-profile-types__setting-row">
										<span className="bb-admin-profile-types__setting-label">
											{ __( 'Display Profile Types', 'buddyboss' ) }
										</span>
										<div className="bb-admin-profile-types__setting-control">
											<ToggleControl
												label={ __( 'Display profile type on member profiles', 'buddyboss' ) }
												checked={ displayOnProfile }
												onChange={ function ( val ) {
													handleSettingChange( 'bp-member-type-display-on-profile', val );
												} }
											/>
										</div>
									</div>
									<div className="bb-admin-profile-types__setting-row">
										<span className="bb-admin-profile-types__setting-label">
											{ __( 'Default Profile Type', 'buddyboss' ) }
										</span>
										<div className="bb-admin-profile-types__setting-control">
											<SelectControl
												value={ defaultProfileType }
												options={ defaultTypeOptions }
												onChange={ handleDefaultTypeChange }
											/>
											{ getFieldDescription( 'bp-member-type-default-on-registration' ) && (
												<span
													className="bb-admin-profile-types__setting-description"
													dangerouslySetInnerHTML={ { __html: sanitizeHtml( getFieldDescription( 'bp-member-type-default-on-registration' ) ) } }
												/>
											) }
										</div>
									</div>
								</>
							) }
						</>
					) }
				</div>
			</div>

			{/* Card 2: Profile Types List (visible only when enabled) */}
			{ enableProfileTypes && <div className="bb-admin-profile-types__card">
				<div className="bb-admin-profile-types__card-header">
					<h3 className="bb-admin-profile-types__card-title">
						{ __( 'Profile Types', 'buddyboss' ) }
					</h3>
					<button
						className="bb-admin-profile-types__add-btn"
						onClick={ handleAddNewType }
					>
						<i className="bb-icons-rl bb-icons-rl-plus"></i>
						{ __( 'Add New Profile Type', 'buddyboss' ) }
					</button>
				</div>
				<div className="bb-admin-profile-types__card-body">
					{ isLoading ? (
						<div className="bb-admin-loading"><Spinner /></div>
					) : memberTypes.length > 0 ? (
						<ul className="bb-admin-profile-types__list">
							{ memberTypes.map( function ( type ) {
								var badge = getVisibilityBadge( type );
								var countText = type.members_count + ' ' + ( 1 === type.members_count ? __( 'member', 'buddyboss' ) : __( 'members', 'buddyboss' ) );

								return (
									<li key={ type.id } className="bb-admin-profile-types__list-item">
										<div className="bb-admin-profile-types__list-item-name-col">
											<span className="bb-admin-profile-types__list-item-icon bb-icons-rl bb-icons-rl-tag"></span>
											<span className="bb-admin-profile-types__list-item-name">
												{ decodeEntities( type.post_title ) }
											</span>
										</div>
										<div className="bb-admin-profile-types__list-item-label-col">
											{ type.singular_label && (
												<span className="bb-admin-profile-types__list-item-badge">
													{ decodeEntities( type.singular_label ) }
												</span>
											) }
										</div>
										<div className="bb-admin-profile-types__list-item-count-col">
											<span className="bb-admin-profile-types__list-item-count-icon bb-icons-rl bb-icons-rl-users"></span>
											<span className="bb-admin-profile-types__list-item-count">
												{ countText }
											</span>
										</div>
										<div className="bb-admin-profile-types__list-item-visibility-col">
											<span className={ 'bb-admin-profile-types__list-item-visibility-badge' + badge.modifier }>
												<span className={ 'bb-icons-rl ' + badge.icon }></span>
												{ badge.label }
											</span>
										</div>
										<div className="bb-admin-profile-types__list-item-actions-col">
											<div className="bb-admin-profile-types__menu-wrapper">
												<button
													className="bb-admin-profile-types__menu-trigger"
													onClick={ function () {
														setOpenMenuId( openMenuId === type.id ? null : type.id );
													} }
													aria-label={ __( 'Actions', 'buddyboss' ) }
												>
													<span className="bb-icons-rl bb-icons-rl-dots-three"></span>
												</button>
												{ openMenuId === type.id && (
													<div className="bb-admin-profile-types__menu-dropdown">
														<button
															className="bb-admin-profile-types__menu-item"
															onClick={ function () {
																handleEdit( type );
															} }
														>
															<i className="bb-icons-rl bb-icons-rl-pencil-simple"></i>
															{ __( 'Edit', 'buddyboss' ) }
														</button>
														<button
															className="bb-admin-profile-types__menu-item bb-admin-profile-types__menu-item--danger"
															onClick={ function () {
																handleDelete( type.id );
															} }
														>
															<i className="bb-icons-rl bb-icons-rl-trash"></i>
															{ __( 'Delete', 'buddyboss' ) }
														</button>
													</div>
												) }
											</div>
										</div>
									</li>
								);
							} ) }
						</ul>
					) : (
						<div className="bb-admin-profile-types__empty">
							<p>{ __( 'No profile types found. Click "Add New Profile Type" to create one.', 'buddyboss' ) }</p>
						</div>
					) }
				</div>
			</div> }

			{/* Profile Type Modal */}
			<ProfileTypeModal
				isOpen={ isModalOpen }
				onClose={ handleModalClose }
				onSave={ handleModalSave }
				memberType={ editingType }
				groupTypes={ groupTypes }
				wpRoles={ wpRoles }
				allMemberTypes={ memberTypes }
			/>

			{/* Toast */}
			{ toast && (
				<div className="bb-toast-container">
					<Toast
						status={ toast.status }
						message={ toast.message }
						onDismiss={ function () { setToast( null ); } }
					/>
				</div>
			) }
		</div>
	);
}

export default ProfileTypeScreen;

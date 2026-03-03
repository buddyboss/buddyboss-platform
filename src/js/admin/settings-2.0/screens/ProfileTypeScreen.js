/**
 * BuddyBoss Admin Settings 2.0 - Profile Types Screen
 *
 * Custom panel screen for managing profile/member types and settings.
 *
 * @package BuddyBoss\Core\Administration
 * @since BuddyBoss [BBVERSION]
 */

import { useState, useEffect, useCallback, useRef, useMemo } from '@wordpress/element';
import { ToggleControl, SelectControl, Spinner } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { decodeEntities } from '@wordpress/html-entities';
import { getMemberTypes, deleteMemberType, getPlatformSettings, savePlatformSetting } from '../utils/ajax';
import { sanitizeHtml } from '../utils/sanitize';
import { Toast } from '../components/Toast';
import { HelpIcon } from '../components/HelpIcon';
import { ProfileTypeModal } from '../components/modals/ProfileTypeModal';
import { ConfirmToggleModal } from '../components/modals/ConfirmToggleModal';
import { getSectionTitle, getFieldLabel, getFieldDescription, getFieldHelpText } from '../utils/feature';

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

	var publishedPagesState = useState( [] );
	var publishedPages = publishedPagesState[ 0 ];
	var setPublishedPages = publishedPagesState[ 1 ];

	var toastState = useState( null );
	var toast = toastState[ 0 ];
	var setToast = toastState[ 1 ];

	var deleteConfirmState = useState( null );
	var deleteConfirmId = deleteConfirmState[ 0 ];
	var setDeleteConfirmId = deleteConfirmState[ 1 ];

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
	var loadMemberTypes = useCallback( function ( options ) {
		setIsLoading( true );
		getMemberTypes( options )
			.then( function ( response ) {
				if ( response.success && response.data ) {
					setMemberTypes( response.data.member_types || [] );
					setGroupTypes( response.data.group_types || [] );
					setWpRoles( response.data.wp_roles || [] );
					setPublishedPages( response.data.published_pages || [] );
				}
				setIsLoading( false );
			} )
			.catch( function ( err ) {
				if ( 'AbortError' !== err.name ) {
					setIsLoading( false );
					setToast( { status: 'error', message: __( 'Failed to load profile types.', 'buddyboss' ) } );
				}
			} );
	}, [] );

	useEffect( function () {
		var controller = new AbortController();
		loadMemberTypes( { signal: controller.signal } );
		return function () { controller.abort(); };
	}, [ loadMemberTypes ] );

	// Close menu on outside click or Escape key.
	useEffect( function () {
		if ( null === openMenuId ) {
			return;
		}

		function handleMouseDown( e ) {
			if ( ! e.target.closest( '.bb-admin-profile-types__menu-wrapper' ) ) {
				setOpenMenuId( null );
			}
		}

		function handleKeyDown( e ) {
			if ( 'Escape' === e.key ) {
				setOpenMenuId( null );
			}
		}

		document.addEventListener( 'mousedown', handleMouseDown );
		document.addEventListener( 'keydown', handleKeyDown );
		return function () {
			document.removeEventListener( 'mousedown', handleMouseDown );
			document.removeEventListener( 'keydown', handleKeyDown );
		};
	}, [ openMenuId ] );

	// Handle settings toggle — rollback only the specific setting that failed.
	var handleSettingChange = useCallback( function ( optionName, newValue ) {
		var isProfileTypeSetting = 'bp-member-type-enable-disable' === optionName;
		var prevValue = isProfileTypeSetting ? enableProfileTypes : displayOnProfile;

		// Optimistic update.
		if ( isProfileTypeSetting ) {
			setEnableProfileTypes( newValue );
		} else if ( 'bp-member-type-display-on-profile' === optionName ) {
			setDisplayOnProfile( newValue );
		}

		savePlatformSetting( optionName, newValue ? 1 : 0 )
			.then( function ( response ) {
				if ( response.success ) {
					setToast( { status: 'success', message: __( 'Setting saved.', 'buddyboss' ) } );
				} else {
					// Rollback only the setting that failed.
					if ( isProfileTypeSetting ) {
						setEnableProfileTypes( prevValue );
					} else {
						setDisplayOnProfile( prevValue );
					}
					setToast( { status: 'error', message: __( 'Failed to save setting.', 'buddyboss' ) } );
				}
			} )
			.catch( function () {
				// Rollback only the setting that failed.
				if ( isProfileTypeSetting ) {
					setEnableProfileTypes( prevValue );
				} else {
					setDisplayOnProfile( prevValue );
				}
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

	// Handle delete — open confirmation modal.
	var handleDelete = useCallback( function ( typeId ) {
		setOpenMenuId( null );
		setDeleteConfirmId( typeId );
	}, [] );

	// Perform delete after confirmation.
	var performDelete = useCallback( function () {
		var typeId = deleteConfirmId;
		setDeleteConfirmId( null );

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
					setToast( { status: 'error', message: ( response.data && response.data.message ) || __( 'Failed to delete profile type.', 'buddyboss' ) } );
				}
			} )
			.catch( function () {
				setToast( { status: 'error', message: __( 'Failed to delete profile type.', 'buddyboss' ) } );
			} );
	}, [ deleteConfirmId ] );

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

	// Build default type options for the select (memoized to avoid rebuilding on unrelated state changes).
	var defaultTypeOptions = useMemo( function () {
		var options = [ { label: __( '----', 'buddyboss' ), value: '' } ];
		memberTypes.forEach( function ( type ) {
			if ( type.key ) {
				options.push( {
					label: decodeEntities( type.plural_label || type.post_title ),
					value: type.key,
				} );
			}
		} );
		return options;
	}, [ memberTypes ] );

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
						{ getSectionTitle( feature, activePanelId, 'profile_types_settings' ) || __( 'Profile Type Settings', 'buddyboss' ) }
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
									{ getFieldLabel( feature, activePanelId, 'bp-member-type-enable-disable' ) || __( 'Profile Types', 'buddyboss' ) }
								</span>
								<div className="bb-admin-profile-types__setting-control">
									<ToggleControl
										label={ getFieldDescription( feature, activePanelId, 'bp-member-type-enable-disable' ) || __( 'Enable profile types', 'buddyboss' ) }
										checked={ enableProfileTypes }
										onChange={ function ( val ) {
											handleSettingChange( 'bp-member-type-enable-disable', val );
										} }
									/>
									{ getFieldHelpText( feature, activePanelId, 'bp-member-type-enable-disable' ) && (
										<span
											className="bb-admin-profile-types__setting-help-text"
											dangerouslySetInnerHTML={ { __html: sanitizeHtml( getFieldHelpText( feature, activePanelId, 'bp-member-type-enable-disable' ) ) } }
										/>
									) }
								</div>
							</div>
							{ enableProfileTypes && (
								<>
									<div className="bb-admin-profile-types__setting-row">
										<span className="bb-admin-profile-types__setting-label">
											{ getFieldLabel( feature, activePanelId, 'bp-member-type-display-on-profile' ) || __( 'Display Profile Types', 'buddyboss' ) }
										</span>
										<div className="bb-admin-profile-types__setting-control">
											<ToggleControl
												label={ getFieldDescription( feature, activePanelId, 'bp-member-type-display-on-profile' ) || __( 'Display profile type on member profiles', 'buddyboss' ) }
												checked={ displayOnProfile }
												onChange={ function ( val ) {
													handleSettingChange( 'bp-member-type-display-on-profile', val );
												} }
											/>
											{ getFieldHelpText( feature, activePanelId, 'bp-member-type-display-on-profile' ) && (
												<span
													className="bb-admin-profile-types__setting-help-text"
													dangerouslySetInnerHTML={ { __html: sanitizeHtml( getFieldHelpText( feature, activePanelId, 'bp-member-type-display-on-profile' ) ) } }
												/>
											) }
										</div>
									</div>
									<div className="bb-admin-profile-types__setting-row">
										<span className="bb-admin-profile-types__setting-label">
											{ getFieldLabel( feature, activePanelId, 'bp-member-type-default-on-registration' ) || __( 'Default Profile Type', 'buddyboss' ) }
										</span>
										<div className="bb-admin-profile-types__setting-control">
											<SelectControl
												value={ defaultProfileType }
												options={ defaultTypeOptions }
												onChange={ handleDefaultTypeChange }
											/>
											{ getFieldHelpText( feature, activePanelId, 'bp-member-type-default-on-registration' ) && (
												<span
													className="bb-admin-profile-types__setting-help-text"
													dangerouslySetInnerHTML={ { __html: sanitizeHtml( getFieldHelpText( feature, activePanelId, 'bp-member-type-default-on-registration' ) ) } }
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
													aria-haspopup="true"
													aria-expanded={ type.id === openMenuId ? 'true' : 'false' }
												>
													<span className="bb-icons-rl bb-icons-rl-dots-three"></span>
												</button>
												{ openMenuId === type.id && (
													<div className="bb-admin-profile-types__menu-dropdown" role="menu">
														<button
															className="bb-admin-profile-types__menu-item"
															role="menuitem"
															onClick={ function () {
																handleEdit( type );
															} }
														>
															<i className="bb-icons-rl bb-icons-rl-pencil-simple"></i>
															{ __( 'Edit', 'buddyboss' ) }
														</button>
														<button
															className="bb-admin-profile-types__menu-item bb-admin-profile-types__menu-item--danger"
															role="menuitem"
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
				publishedPages={ publishedPages }
			/>

			{/* Delete Confirmation Modal */}
			<ConfirmToggleModal
				isOpen={ null !== deleteConfirmId }
				title={ __( 'Delete Profile Type', 'buddyboss' ) }
				message={ __( 'Are you sure you want to delete this profile type?', 'buddyboss' ) }
				confirmLabel={ __( 'Delete', 'buddyboss' ) }
				cancelLabel={ __( 'Cancel', 'buddyboss' ) }
				isDestructive={ true }
				onConfirm={ performDelete }
				onCancel={ function () {
					setDeleteConfirmId( null );
				} }
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

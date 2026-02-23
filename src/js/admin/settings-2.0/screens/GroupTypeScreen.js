/**
 * BuddyBoss Admin Settings 2.0 - Group Types Screen
 *
 * Custom panel screen for managing group types and group type settings.
 *
 * @package BuddyBoss\Core\Administration
 * @since BuddyBoss [BBVERSION]
 */

import { useState, useEffect, useCallback, useRef } from '@wordpress/element';
import { ToggleControl, Spinner } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { decodeEntities } from '@wordpress/html-entities';
import { ajaxFetch, getGroupTypes, deleteGroupType, getPlatformSettings, savePlatformSetting } from '../utils/ajax';
import { getCachedFeatureData, setCachedFeatureData } from '../utils/featureCache';
import { Toast } from '../components/Toast';
import { GroupTypeModal } from '../components/modals/GroupTypeModal';

/**
 * Group Types Screen Component
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param {Object}   props            Component props.
 * @param {Function} props.onNavigate Navigation handler.
 * @returns {JSX.Element} Group types screen.
 */
export function GroupTypeScreen( { onNavigate } ) {
	var groupTypesState = useState( [] );
	var groupTypes = groupTypesState[ 0 ];
	var setGroupTypes = groupTypesState[ 1 ];

	var isLoadingState = useState( true );
	var isLoading = isLoadingState[ 0 ];
	var setIsLoading = isLoadingState[ 1 ];

	var enableGroupTypesState = useState( false );
	var enableGroupTypes = enableGroupTypesState[ 0 ];
	var setEnableGroupTypes = enableGroupTypesState[ 1 ];

	var autoMembershipApprovalState = useState( false );
	var autoMembershipApproval = autoMembershipApprovalState[ 0 ];
	var setAutoMembershipApproval = autoMembershipApprovalState[ 1 ];

	var settingsLoadingState = useState( true );
	var settingsLoading = settingsLoadingState[ 0 ];
	var setSettingsLoading = settingsLoadingState[ 1 ];

	var isModalOpenState = useState( false );
	var isModalOpen = isModalOpenState[ 0 ];
	var setIsModalOpen = isModalOpenState[ 1 ];

	var editingGroupTypeState = useState( null );
	var editingGroupType = editingGroupTypeState[ 0 ];
	var setEditingGroupType = editingGroupTypeState[ 1 ];

	var openMenuIdState = useState( null );
	var openMenuId = openMenuIdState[ 0 ];
	var setOpenMenuId = openMenuIdState[ 1 ];

	var memberTypesState = useState( [] );
	var memberTypes = memberTypesState[ 0 ];
	var setMemberTypes = memberTypesState[ 1 ];

	var toastState = useState( null );
	var toast = toastState[ 0 ];
	var setToast = toastState[ 1 ];

	// Sidebar state.
	var sidebarState = useState( { sidePanels: [], navItems: [] } );
	var sidebar = sidebarState[ 0 ];
	var setSidebar = sidebarState[ 1 ];

	// Load sidebar data.
	useEffect( function () {
		var controller = new AbortController();
		var cachedData = getCachedFeatureData( 'groups' );

		if ( cachedData ) {
			setSidebar( {
				sidePanels: cachedData.side_panels || [],
				navItems: cachedData.navigation || [],
			} );
			return function () { controller.abort(); };
		}

		ajaxFetch( 'bb_admin_get_feature_settings', { feature_id: 'groups' }, { signal: controller.signal } )
			.then( function ( response ) {
				if ( response.success && response.data ) {
					setCachedFeatureData( 'groups', response.data );
					setSidebar( {
						sidePanels: response.data.side_panels || [],
						navItems: response.data.navigation || [],
					} );
				}
			} )
			.catch( function ( err ) {
				if ( 'AbortError' !== err.name ) {
					// Sidebar is non-critical; fail silently but log for debugging.
					// eslint-disable-next-line no-console
					console.warn( 'Failed to load sidebar data.', err );
				}
			} );

		return function () { controller.abort(); };
	}, [] );

	// Load platform settings.
	useEffect( function () {
		var controller = new AbortController();
		setSettingsLoading( true );
		getPlatformSettings( 'bp-disable-group-type-creation,bp-enable-group-auto-join', { signal: controller.signal } )
			.then( function ( response ) {
				if ( response.success && response.data ) {
					// bp-disable-group-type-creation: 1 = enabled (inverted naming).
					setEnableGroupTypes( !! parseInt( response.data[ 'bp-disable-group-type-creation' ] ) );
					setAutoMembershipApproval( !! parseInt( response.data[ 'bp-enable-group-auto-join' ] ) );
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

	// Load group types.
	var loadGroupTypes = useCallback( function () {
		setIsLoading( true );
		getGroupTypes()
			.then( function ( response ) {
				if ( response.success && response.data ) {
					setGroupTypes( response.data.group_types || [] );
					setMemberTypes( response.data.member_types || [] );
				}
				setIsLoading( false );
			} )
			.catch( function () {
				setIsLoading( false );
				setToast( { status: 'error', message: __( 'Failed to load group types.', 'buddyboss' ) } );
			} );
	}, [] );

	useEffect( function () {
		loadGroupTypes();
	}, [ loadGroupTypes ] );

	// Close menu on outside click.
	useEffect( function () {
		if ( null === openMenuId ) {
			return;
		}

		function handleMouseDown( e ) {
			if ( ! e.target.closest( '.bb-admin-group-types__menu-wrapper' ) ) {
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
		var prevGroupTypes = enableGroupTypes;
		var prevAutoMembership = autoMembershipApproval;

		// Optimistic update.
		if ( 'bp-disable-group-type-creation' === optionName ) {
			setEnableGroupTypes( newValue );
		} else if ( 'bp-enable-group-auto-join' === optionName ) {
			setAutoMembershipApproval( newValue );
		}

		savePlatformSetting( optionName, newValue ? 1 : 0 )
			.then( function ( response ) {
				if ( response.success ) {
					setToast( { status: 'success', message: __( 'Setting saved.', 'buddyboss' ) } );
				} else {
					// Rollback.
					setEnableGroupTypes( prevGroupTypes );
					setAutoMembershipApproval( prevAutoMembership );
					setToast( { status: 'error', message: __( 'Failed to save setting.', 'buddyboss' ) } );
				}
			} )
			.catch( function () {
				// Rollback.
				setEnableGroupTypes( prevGroupTypes );
				setAutoMembershipApproval( prevAutoMembership );
				setToast( { status: 'error', message: __( 'Failed to save setting.', 'buddyboss' ) } );
			} );
	}, [ enableGroupTypes, autoMembershipApproval ] );

	// Handle delete.
	var handleDelete = useCallback( function ( typeId ) {
		if ( ! window.confirm( __( 'Are you sure you want to delete this group type?', 'buddyboss' ) ) ) {
			return;
		}

		setOpenMenuId( null );

		deleteGroupType( typeId )
			.then( function ( response ) {
				if ( response.success ) {
					setGroupTypes( function ( prev ) {
						return prev.filter( function ( t ) {
							return t.id !== typeId;
						} );
					} );
					setToast( { status: 'success', message: __( 'Group type deleted.', 'buddyboss' ) } );
				} else {
					setToast( { status: 'error', message: response.data?.message || __( 'Failed to delete group type.', 'buddyboss' ) } );
				}
			} )
			.catch( function () {
				setToast( { status: 'error', message: __( 'Failed to delete group type.', 'buddyboss' ) } );
			} );
	}, [] );

	// Handle edit.
	var handleEdit = useCallback( function ( groupType ) {
		setOpenMenuId( null );
		setEditingGroupType( groupType );
		setIsModalOpen( true );
	}, [] );

	// Handle add new.
	var handleAddNewType = useCallback( function () {
		setEditingGroupType( null );
		setIsModalOpen( true );
	}, [] );

	// Handle modal save.
	var handleModalSave = useCallback( function () {
		setIsModalOpen( false );
		setEditingGroupType( null );
		loadGroupTypes();
	}, [ loadGroupTypes ] );

	// Handle modal close.
	var handleModalClose = useCallback( function () {
		setIsModalOpen( false );
		setEditingGroupType( null );
	}, [] );

	// Handle back.
	var handleBack = function () {
		if ( 'function' === typeof onNavigate ) {
			onNavigate( '/settings' );
		}
	};

	return (
		<div className="bb-admin-group-types">
			{/* Card 1: Group Type Settings */}
			<div className="bb-admin-group-types__card">
				<div className="bb-admin-group-types__card-header">
					<h3 className="bb-admin-group-types__card-title">
						{ __( 'Group Type Settings', 'buddyboss' ) }
					</h3>
				</div>
				<div className="bb-admin-group-types__card-body">
					{ settingsLoading ? (
						<div className="bb-admin-loading"><Spinner /></div>
					) : (
						<>
							<div className="bb-admin-group-types__setting-row">
								<div className="bb-admin-group-types__setting-info">
									<span className="bb-admin-group-types__setting-label">
										{ __( 'Group Types', 'buddyboss' ) }
									</span>
									<span className="bb-admin-group-types__setting-description">
										{ __( 'Enable custom group types to categorize groups.', 'buddyboss' ) }
									</span>
								</div>
								<ToggleControl
									checked={ enableGroupTypes }
									onChange={ function ( val ) {
										handleSettingChange( 'bp-disable-group-type-creation', val );
									} }
								/>
							</div>
							<div className="bb-admin-group-types__setting-row">
								<div className="bb-admin-group-types__setting-info">
									<span className="bb-admin-group-types__setting-label">
										{ __( 'Auto Membership Approval', 'buddyboss' ) }
									</span>
									<span className="bb-admin-group-types__setting-description">
										{ __( 'Allow users to join groups without requiring approval.', 'buddyboss' ) }
									</span>
								</div>
								<ToggleControl
									checked={ autoMembershipApproval }
									onChange={ function ( val ) {
										handleSettingChange( 'bp-enable-group-auto-join', val );
									} }
								/>
							</div>
						</>
					) }
				</div>
			</div>

			{/* Card 2: Group Types List */}
			<div className="bb-admin-group-types__card">
				<div className="bb-admin-group-types__card-header">
					<h3 className="bb-admin-group-types__card-title">
						{ __( 'Group Types', 'buddyboss' ) }
					</h3>
					<button
						className="bb-admin-group-types__add-btn"
						onClick={ handleAddNewType }
					>
						{ __( '+ Add New Group Type', 'buddyboss' ) }
					</button>
				</div>
				<div className="bb-admin-group-types__card-body">
					{ isLoading ? (
						<div className="bb-admin-loading"><Spinner /></div>
					) : groupTypes.length > 0 ? (
						<ul className="bb-admin-group-types__list">
							{ groupTypes.map( function ( type ) {
								var isPublic = 'private' !== type.visibility;
								var countText = type.groups_count + ' ' + ( 1 === type.groups_count ? __( 'group', 'buddyboss' ) : __( 'groups', 'buddyboss' ) );

								return (
									<li key={ type.id } className="bb-admin-group-types__list-item">
										<div className="bb-admin-group-types__list-item-name-col">
											<span className="bb-admin-group-types__list-item-icon bb-icons-rl bb-icons-rl-tag"></span>
											<span className="bb-admin-group-types__list-item-name">
												{ decodeEntities( type.post_title ) }
											</span>
										</div>
										<div className="bb-admin-group-types__list-item-label-col">
											{ type.singular_label && (
												<span className="bb-admin-group-types__list-item-badge">
													{ decodeEntities( type.singular_label ) }
												</span>
											) }
										</div>
										<div className="bb-admin-group-types__list-item-count-col">
											<span className="bb-admin-group-types__list-item-count-icon bb-icons-rl bb-icons-rl-users"></span>
											<span className="bb-admin-group-types__list-item-count">
												{ countText }
											</span>
										</div>
										<div className="bb-admin-group-types__list-item-visibility-col">
											<span className={ 'bb-admin-group-types__list-item-visibility-badge' + ( isPublic ? '' : ' bb-admin-group-types__list-item-visibility-badge--private' ) }>
												<span className={ 'bb-icons-rl ' + ( isPublic ? 'bb-icons-rl-globe-simple' : 'bb-icons-rl-lock' ) }></span>
												{ isPublic ? __( 'Public', 'buddyboss' ) : __( 'Private', 'buddyboss' ) }
											</span>
										</div>
										<div className="bb-admin-group-types__list-item-actions-col">
											<div className="bb-admin-group-types__menu-wrapper">
												<button
													className="bb-admin-group-types__menu-trigger"
													onClick={ function () {
														setOpenMenuId( openMenuId === type.id ? null : type.id );
													} }
													aria-label={ __( 'Actions', 'buddyboss' ) }
												>
													<span className="bb-icons-rl bb-icons-rl-dots-three"></span>
												</button>
												{ openMenuId === type.id && (
													<div className="bb-admin-group-types__menu-dropdown">
														<button
															className="bb-admin-group-types__menu-item"
															onClick={ function () {
																handleEdit( type );
															} }
														>
															{ __( 'Edit', 'buddyboss' ) }
														</button>
														<button
															className="bb-admin-group-types__menu-item bb-admin-group-types__menu-item--danger"
															onClick={ function () {
																handleDelete( type.id );
															} }
														>
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
						<div className="bb-admin-group-types__empty">
							<p>{ __( 'No group types found. Click "Add New Group Type" to create one.', 'buddyboss' ) }</p>
						</div>
					) }
				</div>
			</div>

			{/* Group Type Modal */}
			<GroupTypeModal
				isOpen={ isModalOpen }
				onClose={ handleModalClose }
				onSave={ handleModalSave }
				groupType={ editingGroupType }
				memberTypes={ memberTypes }
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

export default GroupTypeScreen;

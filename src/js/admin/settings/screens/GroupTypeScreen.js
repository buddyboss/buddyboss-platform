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
import { __, _n, sprintf } from '@wordpress/i18n';
import { decodeEntities } from '@wordpress/html-entities';
import { getGroupTypes, deleteGroupType, getPlatformSettings, savePlatformSetting } from '../utils/ajax';
import { Toast } from '../components/Toast';
import { HelpIcon } from '../components/HelpIcon';
import { GroupTypeModal } from '../components/modals/GroupTypeModal';
import { getSectionTitle, getFieldLabel, getFieldDescription, getFieldHelpText } from '../utils/feature';
import { sanitizeHtml, safeUrl } from '../utils/sanitize';
import { ConfirmToggleModal } from '../components/modals/ConfirmToggleModal';

/**
 * Group Types Screen Component
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param {Object}   props             Component props.
 * @param {Function} props.onNavigate  Navigation handler.
 * @param {string}   props.helpUrl     Help URL for this panel.
 * @param {Function} props.onHelpClick Help icon click handler.
 * @param {Object}   props.feature     Feature data from FeatureSettingsScreen (includes field definitions).
 * @param {string}   props.activePanelId Active panel ID.
 * @returns {JSX.Element} Group types screen.
 */
export function GroupTypeScreen( { onNavigate, helpUrl, onHelpClick, feature, activePanelId } ) {
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

	var deleteConfirmState = useState( null );
	var deleteConfirmId = deleteConfirmState[ 0 ];
	var setDeleteConfirmId = deleteConfirmState[ 1 ];

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
				if ( err && 'AbortError' === err.name ) {
					return;
				}
				setSettingsLoading( false );
			} );

		return function () { controller.abort(); };
	}, [] );

	// Load group types.
	var groupTypesAbortRef = useRef( null );

	var loadGroupTypes = useCallback( function () {
		if ( groupTypesAbortRef.current ) {
			groupTypesAbortRef.current.abort();
		}
		groupTypesAbortRef.current = new AbortController();

		setIsLoading( true );
		getGroupTypes( { signal: groupTypesAbortRef.current.signal } )
			.then( function ( response ) {
				if ( response.success && response.data ) {
					setGroupTypes( response.data.group_types || [] );
					setMemberTypes( response.data.member_types || [] );
				}
				setIsLoading( false );
			} )
			.catch( function ( err ) {
				if ( ! err || 'AbortError' !== err.name ) {
					setIsLoading( false );
					setToast( { status: 'error', message: __( 'Failed to load group types.', 'buddyboss' ) } );
				}
			} );
	}, [] );

	useEffect( function () {
		loadGroupTypes();

		return function () {
			if ( groupTypesAbortRef.current ) {
				groupTypesAbortRef.current.abort();
			}
		};
	}, [ loadGroupTypes ] );

	// Close menu on outside click or Escape key.
	useEffect( function () {
		if ( null === openMenuId ) {
			return;
		}

		function handleMouseDown( e ) {
			if ( ! e.target.closest( '.bb-admin-group-types__menu-wrapper' ) ) {
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
		var isGroupTypeSetting = 'bp-disable-group-type-creation' === optionName;
		var prevValue = isGroupTypeSetting ? enableGroupTypes : autoMembershipApproval;

		// Optimistic update.
		if ( isGroupTypeSetting ) {
			setEnableGroupTypes( newValue );
			// Show spinner immediately when re-enabling to avoid flash of empty state.
			if ( newValue ) {
				setIsLoading( true );
			}
		} else if ( 'bp-enable-group-auto-join' === optionName ) {
			setAutoMembershipApproval( newValue );
		}

		savePlatformSetting( optionName, newValue ? 1 : 0 )
			.then( function ( response ) {
				if ( response.success ) {
					setToast( { status: 'success', message: __( 'Setting saved.', 'buddyboss' ) } );

					// Refetch types when re-enabling — the list may be stale/empty
					// from when the feature was disabled and the component remounted.
					if ( isGroupTypeSetting && newValue ) {
						loadGroupTypes();
					}
				} else {
					// Rollback only the setting that failed.
					if ( isGroupTypeSetting ) {
						setEnableGroupTypes( prevValue );
					} else {
						setAutoMembershipApproval( prevValue );
					}
					setToast( { status: 'error', message: __( 'Failed to save setting.', 'buddyboss' ) } );
				}
			} )
			.catch( function () {
				// Rollback only the setting that failed.
				if ( isGroupTypeSetting ) {
					setEnableGroupTypes( prevValue );
				} else {
					setAutoMembershipApproval( prevValue );
				}
				setToast( { status: 'error', message: __( 'Failed to save setting.', 'buddyboss' ) } );
			} );
	}, [ enableGroupTypes, autoMembershipApproval, loadGroupTypes ] );

	// Handle delete — open confirmation modal.
	var handleDelete = useCallback( function ( typeId ) {
		setOpenMenuId( null );
		setDeleteConfirmId( typeId );
	}, [] );

	// Perform delete after confirmation.
	var performDelete = useCallback( function () {
		var typeId = deleteConfirmId;
		setDeleteConfirmId( null );

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
					setToast( { status: 'error', message: ( response.data && response.data.message ) || __( 'Failed to delete group type.', 'buddyboss' ) } );
				}
			} )
			.catch( function () {
				setToast( { status: 'error', message: __( 'Failed to delete group type.', 'buddyboss' ) } );
			} );
	}, [ deleteConfirmId ] );

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
			<div className="bb-admin-feature-settings__section bb-admin-group-types__card">
				<div className="bb-admin-feature-settings__section-header">
					<h3 className="bb-admin-feature-settings__section-title">
						{ getSectionTitle( feature, activePanelId, 'group_type_settings' ) || __( 'Group Type Settings', 'buddyboss' ) }
					</h3>
					{ helpUrl && (
						<HelpIcon
							onClick={ onHelpClick }
							contentId={ helpUrl }
						/>
					) }
				</div>
				<div className="bb-admin-feature-settings__section-body">
					{ settingsLoading ? (
						<div className="bb-admin-loading"><Spinner /></div>
					) : (
						<>
							<div className="bb-admin-group-types__setting-row">
								<span className="bb-admin-group-types__setting-label">
									{ getFieldLabel( feature, activePanelId, 'bp-disable-group-type-creation' ) || __( 'Group Types', 'buddyboss' ) }
								</span>
								<div className="bb-admin-group-types__setting-control">
									<ToggleControl
										label={ getFieldDescription( feature, activePanelId, 'bp-disable-group-type-creation' ) || __( 'Enable group types', 'buddyboss' ) }
										checked={ enableGroupTypes }
										onChange={ function ( val ) {
											handleSettingChange( 'bp-disable-group-type-creation', val );
										} }
									/>
									{ getFieldHelpText( feature, activePanelId, 'bp-disable-group-type-creation' ) && (
										<span
											className="bb-admin-group-types__setting-help-text"
											dangerouslySetInnerHTML={ { __html: sanitizeHtml( getFieldHelpText( feature, activePanelId, 'bp-disable-group-type-creation' ) ) } }
										/>
									) }
								</div>
							</div>
							<div className="bb-admin-group-types__setting-row">
								<span className="bb-admin-group-types__setting-label">
									{ getFieldLabel( feature, activePanelId, 'bp-enable-group-auto-join' ) || __( 'Auto Membership Approval', 'buddyboss' ) }
								</span>
								<div className="bb-admin-group-types__setting-control">
									<ToggleControl
										label={ getFieldDescription( feature, activePanelId, 'bp-enable-group-auto-join' ) || __( 'Allow selected profile types to automatically join groups', 'buddyboss' ) }
										checked={ autoMembershipApproval }
										onChange={ function ( val ) {
											handleSettingChange( 'bp-enable-group-auto-join', val );
										} }
									/>
									{ getFieldHelpText( feature, activePanelId, 'bp-enable-group-auto-join' ) && (
										<span
											className="bb-admin-group-types__setting-help-text"
											dangerouslySetInnerHTML={ { __html: sanitizeHtml( getFieldHelpText( feature, activePanelId, 'bp-enable-group-auto-join' ) ) } }
										/>
									) }
								</div>
							</div>
						</>
					) }
				</div>
			</div>

			{/* Card 2: Group Types List (visible only when group types enabled) */}
			{ enableGroupTypes && <div className="bb-admin-feature-settings__section">
				<div className="bb-admin-feature-settings__section-header">
					<h3 className="bb-admin-feature-settings__section-title">
						{ __( 'Group Types', 'buddyboss' ) }
					</h3>
					<button
						className="bb-admin-group-types__add-btn"
						onClick={ handleAddNewType }
					>
						<i className="bb-icons-rl bb-icons-rl-plus"></i>
						{ __( 'Add New Group Type', 'buddyboss' ) }
					</button>
				</div>
				<div className="bb-admin-feature-settings__section-body bb-admin-group-types__list-body">
					{ isLoading ? (
						<div className="bb-admin-loading"><Spinner /></div>
					) : groupTypes.length > 0 ? (
						<ul className="bb-admin-group-types__list">
							{ groupTypes.map( function ( type ) {
								var isPublic = 'private' !== type.visibility;
								var countText = sprintf( _n( '%s group', '%s groups', type.groups_count, 'buddyboss' ), type.groups_count );

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
											{ type.groups_count > 0 ? (
												<a
													href={ safeUrl( window.location.pathname + '?page=bb-settings&tab=groups&panel=all_groups&group_type=' + encodeURIComponent( type.name ) ) }
													className="bb-admin-group-types__list-item-count-link"
												>
													{ countText }
												</a>
											) : (
												<span className="bb-admin-group-types__list-item-count">
													{ countText }
												</span>
											) }
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
														setOpenMenuId( type.id === openMenuId ? null : type.id );
													} }
													aria-label={ __( 'Actions', 'buddyboss' ) }
													aria-haspopup="true"
													aria-expanded={ type.id === openMenuId ? 'true' : 'false' }
												>
													<span className="bb-icons-rl bb-icons-rl-dots-three"></span>
												</button>
												{ type.id === openMenuId && (
													<div className="bb-admin-group-types__menu-dropdown" role="menu">
														<button
															className="bb-admin-group-types__menu-item"
															role="menuitem"
															onClick={ function () {
																handleEdit( type );
															} }
														>
															<i className="bb-icons-rl bb-icons-rl-pencil-simple"></i>
															{ __( 'Edit', 'buddyboss' ) }
														</button>
														<button
															className="bb-admin-group-types__menu-item bb-admin-group-types__menu-item--danger"
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
						<div className="bb-admin-group-types__empty">
							<p>{ __( 'No group types found. Click "Add New Group Type" to create one.', 'buddyboss' ) }</p>
						</div>
					) }
				</div>
			</div> }

			{/* Group Type Modal */}
			<GroupTypeModal
				isOpen={ isModalOpen }
				onClose={ handleModalClose }
				onSave={ handleModalSave }
				groupType={ editingGroupType }
				memberTypes={ memberTypes }
			/>

			{/* Delete Confirmation Modal */}
			<ConfirmToggleModal
				isOpen={ null !== deleteConfirmId }
				title={ __( 'Delete Group Type', 'buddyboss' ) }
				message={ __( 'Are you sure you want to delete this group type?', 'buddyboss' ) }
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

export default GroupTypeScreen;

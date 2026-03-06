/**
 * BuddyBoss Admin Settings 2.0 - Profile Fields Screen
 *
 * Custom panel screen for managing profile field groups (field sets)
 * and their fields with drag-and-drop reordering.
 *
 * @package BuddyBoss\Core\Administration
 * @since BuddyBoss [BBVERSION]
 */

import { useState, useEffect, useCallback, useRef } from '@wordpress/element';
import { Button, Spinner } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { decodeEntities } from '@wordpress/html-entities';
import {
	getProfileFieldGroups,
	deleteProfileField,
	reorderProfileFields,
} from '../utils/ajax';
import { Toast } from '../components/Toast';
import { FieldSetModal } from '../components/modals/FieldSetModal';
import { DeleteFieldSetModal } from '../components/modals/DeleteFieldSetModal';
import { ProfileFieldModal } from '../components/modals/ProfileFieldModal';
import { ConfirmToggleModal } from '../components/modals/ConfirmToggleModal';
import { getFieldTypeIcon } from '../utils/fieldTypeIcons';

/**
 * Profile Fields Screen Component
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param {Object}   props             Component props.
 * @param {Function} props.onNavigate  Navigation handler.
 * @param {string}   props.helpUrl     Help URL for this panel.
 * @param {Function} props.onHelpClick Help icon click handler.
 * @param {Object}   props.feature     Feature data from FeatureSettingsScreen.
 * @param {string}   props.activePanelId Active panel ID.
 * @returns {JSX.Element} Profile fields screen.
 */
export default function ProfileFieldsScreen( { onNavigate, helpUrl, onHelpClick, feature, activePanelId } ) {

	var fieldGroupsState = useState( [] );
	var fieldGroups = fieldGroupsState[ 0 ];
	var setFieldGroups = fieldGroupsState[ 1 ];

	var isLoadingState = useState( true );
	var isLoading = isLoadingState[ 0 ];
	var setIsLoading = isLoadingState[ 1 ];

	var fieldTypesState = useState( { multi_fields: [], single_fields: [] } );
	var fieldTypes = fieldTypesState[ 0 ];
	var setFieldTypes = fieldTypesState[ 1 ];

	var memberTypesState = useState( [] );
	var memberTypes = memberTypesState[ 0 ];
	var setMemberTypes = memberTypesState[ 1 ];

	var visibilityLevelsState = useState( [] );
	var visibilityLevels = visibilityLevelsState[ 0 ];
	var setVisibilityLevels = visibilityLevelsState[ 1 ];

	var socialProvidersState = useState( [] );
	var socialProviders = socialProvidersState[ 0 ];
	var setSocialProviders = socialProvidersState[ 1 ];

	var toastState = useState( null );
	var toast = toastState[ 0 ];
	var setToast = toastState[ 1 ];

	// Modal states.
	var editFieldSetState = useState( null );
	var editFieldSet = editFieldSetState[ 0 ];
	var setEditFieldSet = editFieldSetState[ 1 ];

	var deleteFieldSetState = useState( null );
	var deleteFieldSetData = deleteFieldSetState[ 0 ];
	var setDeleteFieldSetData = deleteFieldSetState[ 1 ];

	var editFieldState = useState( null );
	var editField = editFieldState[ 0 ];
	var setEditField = editFieldState[ 1 ];

	var deleteFieldState = useState( null );
	var deleteFieldData = deleteFieldState[ 0 ];
	var setDeleteFieldData = deleteFieldState[ 1 ];

	// Collapsed state for field set cards.
	var collapsedState = useState( {} );
	var collapsed = collapsedState[ 0 ];
	var setCollapsed = collapsedState[ 1 ];

	// Open ellipsis menu.
	var openMenuState = useState( null );
	var openMenuId = openMenuState[ 0 ];
	var setOpenMenuId = openMenuState[ 1 ];

	// Drag state.
	var dragItemState = useState( null );
	var dragItem = dragItemState[ 0 ];
	var setDragItem = dragItemState[ 1 ];

	var dragOverItemState = useState( null );
	var dragOverItem = dragOverItemState[ 0 ];
	var setDragOverItem = dragOverItemState[ 1 ];

	var dragTypeState = useState( null );
	var dragType = dragTypeState[ 0 ];
	var setDragType = dragTypeState[ 1 ];

	// AbortController ref.
	var abortRef = useRef( null );

	// AbortController ref for reorder requests.
	var reorderAbortRef = useRef( null );

	/**
	 * Load field groups data.
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	var loadFieldGroups = useCallback( function () {
		if ( abortRef.current ) {
			abortRef.current.abort();
		}
		abortRef.current = new AbortController();

		setIsLoading( true );
		getProfileFieldGroups( { signal: abortRef.current.signal } )
			.then( function ( response ) {
				if ( response.success && response.data ) {
					setFieldGroups( response.data.field_groups || [] );
					setFieldTypes( response.data.field_types || { multi_fields: [], single_fields: [] } );
					setMemberTypes( response.data.member_types || [] );
					setVisibilityLevels( response.data.visibility_levels || [] );
					setSocialProviders( response.data.social_providers || [] );
				}
				setIsLoading( false );
			} )
			.catch( function ( error ) {
				if ( 'AbortError' !== error.name ) {
					setIsLoading( false );
					setToast( { status: 'error', message: error.message || __( 'Failed to load profile fields.', 'buddyboss' ) } );
				}
			} );
	}, [] );

	// Load on mount.
	useEffect( function () {
		loadFieldGroups();
		return function () {
			if ( abortRef.current ) {
				abortRef.current.abort();
			}
			if ( reorderAbortRef.current ) {
				reorderAbortRef.current.abort();
			}
		};
	}, [ loadFieldGroups ] );

	// Close ellipsis menu on outside click or Escape key.
	useEffect( function () {
		if ( null === openMenuId ) {
			return;
		}

		function handleMouseDown( e ) {
			if ( ! e.target.closest( '.bb-pf-field-actions' ) ) {
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

	/**
	 * Toggle collapse state for a field set.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param {number} groupId Group ID.
	 */
	function toggleCollapse( groupId ) {
		setCollapsed( function ( prev ) {
			var next = Object.assign( {}, prev );
			next[ groupId ] = ! prev[ groupId ];
			return next;
		} );
	}

	/**
	 * Handle field delete.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param {number} fieldId Field ID.
	 */
	function handleDeleteField( fieldId ) {
		setDeleteFieldData( null );

		deleteProfileField( fieldId )
			.then( function ( response ) {
				if ( response.success ) {
					setToast( { status: 'success', message: response.data.message || __( 'Field deleted.', 'buddyboss' ) } );
					loadFieldGroups();
				} else {
					setToast( { status: 'error', message: ( response.data && response.data.message ) || __( 'Failed to delete field.', 'buddyboss' ) } );
				}
			} )
			.catch( function ( error ) {
				setToast( { status: 'error', message: error.message || __( 'Failed to delete field.', 'buddyboss' ) } );
			} );
	}

	/**
	 * Handle group drag start.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param {Event}  e       Drag event.
	 * @param {number} index   Group index.
	 */
	function handleGroupDragStart( e, index ) {
		setDragItem( index );
		setDragType( 'group' );
		e.dataTransfer.effectAllowed = 'move';
	}

	/**
	 * Handle group drag over.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param {Event}  e     Drag event.
	 * @param {number} index Group index.
	 */
	function handleGroupDragOver( e, index ) {
		e.preventDefault();
		if ( 'group' === dragType ) {
			setDragOverItem( index );
		}
	}

	/**
	 * Handle group drop.
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	function handleGroupDrop() {
		if ( 'group' !== dragType || null === dragItem || null === dragOverItem || dragItem === dragOverItem ) {
			setDragItem( null );
			setDragOverItem( null );
			setDragType( null );
			return;
		}

		var newGroups = fieldGroups.slice();
		var draggedGroup = newGroups.splice( dragItem, 1 )[ 0 ];
		newGroups.splice( dragOverItem, 0, draggedGroup );
		setFieldGroups( newGroups );

		// Save order — cancel any stale reorder request first.
		if ( reorderAbortRef.current ) {
			reorderAbortRef.current.abort();
		}
		reorderAbortRef.current = new AbortController();

		var groupOrder = {};
		newGroups.forEach( function ( group, index ) {
			groupOrder[ index ] = group.id;
		} );

		reorderProfileFields( { group_order: groupOrder }, { signal: reorderAbortRef.current.signal } )
			.then( function ( response ) {
				if ( response.success ) {
					setToast( { status: 'success', message: __( 'Order updated.', 'buddyboss' ) } );
				}
			} )
			.catch( function ( error ) {
				if ( error && 'AbortError' === error.name ) {
					return;
				}
				setToast( { status: 'error', message: __( 'Failed to save order.', 'buddyboss' ) } );
				loadFieldGroups();
			} );

		setDragItem( null );
		setDragOverItem( null );
		setDragType( null );
	}

	/**
	 * Handle field drag start.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param {Event}  e        Drag event.
	 * @param {number} groupId  Group ID.
	 * @param {number} fieldIdx Field index within group.
	 */
	function handleFieldDragStart( e, groupId, fieldIdx ) {
		e.stopPropagation();
		setDragItem( { groupId: groupId, fieldIdx: fieldIdx } );
		setDragType( 'field' );
		e.dataTransfer.effectAllowed = 'move';
	}

	/**
	 * Handle field drag over.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param {Event}  e        Drag event.
	 * @param {number} groupId  Group ID.
	 * @param {number} fieldIdx Field index within group.
	 */
	function handleFieldDragOver( e, groupId, fieldIdx ) {
		e.preventDefault();
		e.stopPropagation();
		if ( 'field' === dragType ) {
			setDragOverItem( { groupId: groupId, fieldIdx: fieldIdx } );
		}
	}

	/**
	 * Handle field drop.
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	function handleFieldDrop() {
		if ( 'field' !== dragType || ! dragItem || ! dragOverItem ) {
			setDragItem( null );
			setDragOverItem( null );
			setDragType( null );
			return;
		}

		// Only allow reorder within the same group.
		if ( dragItem.groupId !== dragOverItem.groupId ) {
			setDragItem( null );
			setDragOverItem( null );
			setDragType( null );
			return;
		}

		if ( dragItem.fieldIdx === dragOverItem.fieldIdx ) {
			setDragItem( null );
			setDragOverItem( null );
			setDragType( null );
			return;
		}

		var targetGroupId = dragItem.groupId;
		var newGroups = fieldGroups.map( function ( group ) {
			if ( group.id !== targetGroupId ) {
				return group;
			}
			var newFields = group.fields.slice();
			var draggedField = newFields.splice( dragItem.fieldIdx, 1 )[ 0 ];
			newFields.splice( dragOverItem.fieldIdx, 0, draggedField );
			return Object.assign( {}, group, { fields: newFields } );
		} );
		setFieldGroups( newGroups );

		// Build field order for the affected group.
		var fieldOrder = {};
		var targetGroup = newGroups.find( function ( g ) {
			return g.id === targetGroupId;
		} );
		if ( targetGroup ) {
			fieldOrder[ targetGroupId ] = {};
			targetGroup.fields.forEach( function ( field, index ) {
				fieldOrder[ targetGroupId ][ index ] = field.id;
			} );
		}

		// Cancel any stale reorder request first.
		if ( reorderAbortRef.current ) {
			reorderAbortRef.current.abort();
		}
		reorderAbortRef.current = new AbortController();

		reorderProfileFields( { field_order: fieldOrder }, { signal: reorderAbortRef.current.signal } )
			.then( function ( response ) {
				if ( response.success ) {
					setToast( { status: 'success', message: __( 'Order updated.', 'buddyboss' ) } );
				}
			} )
			.catch( function ( error ) {
				if ( error && 'AbortError' === error.name ) {
					return;
				}
				setToast( { status: 'error', message: __( 'Failed to save order.', 'buddyboss' ) } );
				loadFieldGroups();
			} );

		setDragItem( null );
		setDragOverItem( null );
		setDragType( null );
	}

	/**
	 * Get field type label from the types data.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param {string} typeKey Field type key.
	 * @returns {string} Field type label.
	 */
	function getFieldTypeLabel( typeKey ) {
		var allTypes = ( fieldTypes.multi_fields || [] ).concat( fieldTypes.single_fields || [] );
		var found = allTypes.find( function ( t ) {
			return t.value === typeKey;
		} );
		return found ? found.label : typeKey;
	}

	/**
	 * Render required badge text for a field.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param {Object} field Field data.
	 * @returns {JSX.Element|null} Badge element or null.
	 */
	function renderFieldBadgeText( field ) {
		if ( field.is_required ) {
			return <span className="bb-pf-badge-text">{ __( 'required', 'buddyboss' ) }</span>;
		}
		return null;
	}

	/**
	 * Render badge pills for a field.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param {Object} field Field data.
	 * @returns {Array|null} Badge elements or null.
	 */
	function renderFieldBadgePills( field ) {
		var badges = [];
		if ( field.is_signup ) {
			badges.push(
				<span key="signup" className="bb-pf-badge bb-pf-badge--signup">{ __( 'Signup', 'buddyboss' ) }</span>
			);
		}
		if ( 'none' === field.member_type_mode ) {
			badges.push(
				<span key="member-types" className="bb-pf-badge bb-pf-badge--member-type">{ __( 'No Profile Type', 'buddyboss' ) }</span>
			);
		} else if ( field.member_types && field.member_types.length > 0 ) {
			var typeLabels = field.member_types.map( function ( typeKey ) {
				var found = memberTypes.find( function ( mt ) {
					return mt.id === typeKey;
				} );
				return found ? found.name : typeKey;
			} );
			badges.push(
				<span key="member-types" className="bb-pf-badge bb-pf-badge--member-type">{ typeLabels.join( ', ' ) }</span>
			);
		}
		return badges.length > 0 ? badges : null;
	}

	// Loading state.
	if ( isLoading ) {
		return (
			<div className="bb-settings-panel-content bb-pf-loading">
				<Spinner />
				<p>{ __( 'Loading profile fields...', 'buddyboss' ) }</p>
			</div>
		);
	}

	return (
		<div className="bb-settings-panel-content bb-profile-fields-screen">

			{/* Toast notification. */}
			{ toast && (
				<div className="bb-toast-container">
					<Toast
						status={ toast.status }
						message={ toast.message }
						onDismiss={ function () { setToast( null ); } }
					/>
				</div>
			) }

			{/* Top banner. */}
			<div className="bb-pf-banner">
				<p>{ __( 'Select the fields you wish to display on your registration page.', 'buddyboss' ) }</p>
				<a
					href="#signup-fields"
					className="bb-pf-banner__select-link"
					onClick={ function ( e ) {
						e.preventDefault();
						// @todo: Implement signup field selection mode.
					} }
				>
					{ __( 'Select', 'buddyboss' ) }
					<i className="bb-icons-rl bb-icons-rl-caret-right" aria-hidden="true"></i>
				</a>
			</div>

			{/* Field set cards. */}
			{ fieldGroups.map( function ( group, groupIndex ) {
				var isCollapsed = collapsed[ group.id ];
				var isDragOver = 'group' === dragType && dragOverItem === groupIndex;

				return (
					<div
						key={ group.id }
						className={ 'bb-pf-fieldset-card' + ( isDragOver ? ' bb-pf-drag-over' : '' ) }
						draggable={ true }
						onDragStart={ function ( e ) { handleGroupDragStart( e, groupIndex ); } }
						onDragOver={ function ( e ) { handleGroupDragOver( e, groupIndex ); } }
						onDrop={ handleGroupDrop }
						onDragEnd={ function () {
							setDragItem( null );
							setDragOverItem( null );
							setDragType( null );
						} }
					>

						{/* Card header. */}
						<div className="bb-pf-fieldset-header">
							<div className="bb-pf-fieldset-header-left">
								<span
									className="bb-pf-drag-handle"
									aria-label={ __( 'Drag to reorder field set', 'buddyboss' ) }
								>
									<i className="bb-icons-rl-list" />
								</span>
								<button
									className="bb-pf-fieldset-toggle"
									onClick={ function () { toggleCollapse( group.id ); } }
									aria-expanded={ ! isCollapsed }
								>
									<i className={ isCollapsed ? 'bb-icons-rl-caret-right' : 'bb-icons-rl-caret-down' } />
									<h3>{ decodeEntities( group.name ) }</h3>
									{ group.is_repeater && (
										<span className="bb-pf-badge bb-pf-badge--repeater">
											{ __( 'Repeater', 'buddyboss' ) }
										</span>
									) }
								</button>
							</div>
							<div className="bb-pf-fieldset-header-right">
								<Button
									variant="primary"
									isSmall={ true }
									onClick={ function () {
										setEditFieldSet( group );
									} }
								>
									<i className="bb-icons-rl bb-icons-rl-note-pencil" />
									{ ' ' }
									{ __( 'Edit Field Set', 'buddyboss' ) }
								</Button>
							</div>
						</div>

						{/* Card body (fields list). */}
						{ ! isCollapsed && (
							<div className="bb-pf-fieldset-body">
								{ ( group.fields && group.fields.length > 0 )
									? group.fields.map( function ( field, fieldIndex ) {
										var isFieldDragOver = 'field' === dragType && dragOverItem && dragOverItem.groupId === group.id && dragOverItem.fieldIdx === fieldIndex;

										return (
											<div
												key={ field.id }
												className={ 'bb-pf-field-row' + ( isFieldDragOver ? ' bb-pf-drag-over' : '' ) }
												draggable={ true }
												onDragStart={ function ( e ) { handleFieldDragStart( e, group.id, fieldIndex ); } }
												onDragOver={ function ( e ) { handleFieldDragOver( e, group.id, fieldIndex ); } }
												onDrop={ function ( e ) {
													e.stopPropagation();
													handleFieldDrop();
												} }
												onDragEnd={ function () {
													setDragItem( null );
													setDragOverItem( null );
													setDragType( null );
												} }
											>
												<div className="bb-pf-field-left">
													<span
														className="bb-pf-drag-handle"
														aria-label={ __( 'Drag to reorder field', 'buddyboss' ) }
													>
														<i className="bb-icons-rl-list" />
													</span>
													<span className="bb-pf-field-type-icon">
														<i className={ getFieldTypeIcon( field.type ) } />
													</span>
													<span className="bb-pf-field-name">
														{ decodeEntities( field.name ) }
													</span>
													{ renderFieldBadgeText( field ) }
												</div>
												<span className="bb-pf-field-badges">
													{ renderFieldBadgePills( field ) }
												</span>
												<div className="bb-pf-field-actions">
													<button
														className="bb-pf-ellipsis-btn"
														onClick={ function ( e ) {
															e.stopPropagation();
															setOpenMenuId( openMenuId === field.id ? null : field.id );
														} }
														aria-label={ __( 'Actions', 'buddyboss' ) }
														aria-haspopup="true"
														aria-expanded={ field.id === openMenuId ? 'true' : 'false' }
													>
														<i className="bb-icons-rl-dots-three" />
													</button>
													{ openMenuId === field.id && (
														<div className="bb-pf-dropdown-menu bb_dropdown_menu_group components-menu-group" role="menu">
															<button
																className="bb-pf-dropdown-edit components-menu-item__button"
																role="menuitem"
																onClick={ function () {
																	setOpenMenuId( null );
																	setEditField( { field: field, groupId: group.id, groupName: group.name } );
																} }
															>
																<span className="components-menu-item__item">
																	<i className="bb-icons-rl bb-icons-rl-note-pencil" />
																	{ ' ' + __( 'Edit', 'buddyboss' ) }
																</span>
															</button>
															{ field.can_delete && (
																<button
																	className="bb-pf-dropdown-delete components-menu-item__button"
																	role="menuitem"
																	onClick={ function () {
																		setOpenMenuId( null );
																		setDeleteFieldData( field );
																	} }
																>
																	<span className="components-menu-item__item">
																		<i className="bb-icons-rl bb-icons-rl-trash" />
																		{ ' ' + __( 'Delete', 'buddyboss' ) }
																	</span>
																</button>
															) }
														</div>
													) }
												</div>
											</div>
										);
									} )
									: (
										<p className="bb-pf-no-fields">
											{ __( 'No fields in this field set.', 'buddyboss' ) }
										</p>
									)
								}

								{/* Add New Field button. */}
								<Button
									variant="secondary"
									className="bb-pf-add-field-btn"
									onClick={ function () {
										setEditField( { field: null, groupId: group.id, groupName: group.name } );
									} }
								>
									<i className="bb-icons-rl bb-icons-rl-plus" />
									{ ' ' + __( 'Add New Field', 'buddyboss' ) }
								</Button>
							</div>
						) }
					</div>
				);
			} ) }

			{/* Add New Field Set button. */}
			<Button
				variant="primary"
				className="bb-pf-add-fieldset-btn"
				onClick={ function () {
					setEditFieldSet( {} );
				} }
			>
				<i className="bb-icons-rl bb-icons-rl-plus" />
				{ ' ' + __( 'Add New Field Set', 'buddyboss' ) }
			</Button>

			{/* Field Set Modal (Add/Edit). */}
			{ null !== editFieldSet && (
				<FieldSetModal
					fieldSet={ editFieldSet }
					onClose={ function () { setEditFieldSet( null ); } }
					onSave={ function () {
						setEditFieldSet( null );
						loadFieldGroups();
					} }
					onDelete={ function () {
						setEditFieldSet( null );
						setDeleteFieldSetData( editFieldSet );
					} }
					setToast={ setToast }
				/>
			) }

			{/* Delete Field Set Modal. */}
			{ null !== deleteFieldSetData && (
				<DeleteFieldSetModal
					fieldSet={ deleteFieldSetData }
					onClose={ function () { setDeleteFieldSetData( null ); } }
					onDeleted={ function () {
						setDeleteFieldSetData( null );
						loadFieldGroups();
					} }
					setToast={ setToast }
				/>
			) }

			{/* Profile Field Modal (Add/Edit). */}
			{ null !== editField && (
				<ProfileFieldModal
					field={ editField.field }
					groupId={ editField.groupId }
					groupName={ editField.groupName }
					fieldTypes={ fieldTypes }
					memberTypes={ memberTypes }
					visibilityLevels={ visibilityLevels }
					socialProviders={ socialProviders }
					allFieldGroups={ fieldGroups }
					onClose={ function () { setEditField( null ); } }
					onSave={ function () {
						setEditField( null );
						loadFieldGroups();
					} }
					setToast={ setToast }
				/>
			) }

			{/* Delete field confirmation. */}
			<ConfirmToggleModal
				isOpen={ null !== deleteFieldData }
				title={ __( 'Delete Field', 'buddyboss' ) }
				message={ deleteFieldData
					? wp.i18n.sprintf( __( 'Are you sure you want to delete the field "%s"? This action cannot be undone.', 'buddyboss' ), decodeEntities( deleteFieldData.name ) )
					: ''
				}
				confirmLabel={ __( 'Delete', 'buddyboss' ) }
				cancelLabel={ __( 'Cancel', 'buddyboss' ) }
				isDestructive={ true }
				onConfirm={ function () { handleDeleteField( deleteFieldData.id ); } }
				onCancel={ function () { setDeleteFieldData( null ); } }
			/>
		</div>
	);
}

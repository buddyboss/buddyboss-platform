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
import { sanitizeHtml } from '../utils/sanitize';
import { Toast } from '../components/Toast';
import { FieldSetModal } from '../components/modals/FieldSetModal';
import { DeleteFieldSetModal } from '../components/modals/DeleteFieldSetModal';
import { ProfileFieldModal } from '../components/modals/ProfileFieldModal';

/**
 * Field type icon class mapping.
 *
 * @since BuddyBoss [BBVERSION]
 */
var FIELD_TYPE_ICONS = {
	textbox: 'bb-icons-rl-text-t',
	textarea: 'bb-icons-rl-text-align-left',
	selectbox: 'bb-icons-rl-list',
	multiselectbox: 'bb-icons-rl-list-checks',
	checkbox: 'bb-icons-rl-check-square',
	radio: 'bb-icons-rl-radio-button',
	datebox: 'bb-icons-rl-calendar',
	number: 'bb-icons-rl-hash',
	telephone: 'bb-icons-rl-phone',
	url: 'bb-icons-rl-link',
	gender: 'bb-icons-rl-gender-intersex',
	socialnetworks: 'bb-icons-rl-share-network',
	membertypes: 'bb-icons-rl-tag',
};

/**
 * Get the icon class for a field type.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param {string} type Field type key.
 * @returns {string} Icon class name.
 */
function getFieldTypeIcon( type ) {
	return FIELD_TYPE_ICONS[ type ] || 'bb-icons-rl-text-t';
}

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
		};
	}, [ loadFieldGroups ] );

	// Close ellipsis menu on outside click.
	useEffect( function () {
		if ( null === openMenuId ) {
			return;
		}
		function handleClick() {
			setOpenMenuId( null );
		}
		document.addEventListener( 'click', handleClick );
		return function () {
			document.removeEventListener( 'click', handleClick );
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
		deleteProfileField( fieldId )
			.then( function ( response ) {
				if ( response.success ) {
					setToast( { status: 'success', message: response.data.message || __( 'Field deleted.', 'buddyboss' ) } );
					loadFieldGroups();
				} else {
					setToast( { status: 'error', message: response.data?.message || __( 'Failed to delete field.', 'buddyboss' ) } );
				}
			} )
			.catch( function ( error ) {
				setToast( { status: 'error', message: error.message || __( 'Failed to delete field.', 'buddyboss' ) } );
			} );
		setDeleteFieldData( null );
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

		// Save order.
		var groupOrder = {};
		newGroups.forEach( function ( group, index ) {
			groupOrder[ index ] = group.id;
		} );

		reorderProfileFields( { group_order: groupOrder } )
			.then( function ( response ) {
				if ( response.success ) {
					setToast( { status: 'success', message: __( 'Order updated.', 'buddyboss' ) } );
				}
			} )
			.catch( function () {
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

		reorderProfileFields( { field_order: fieldOrder } )
			.then( function ( response ) {
				if ( response.success ) {
					setToast( { status: 'success', message: __( 'Order updated.', 'buddyboss' ) } );
				}
			} )
			.catch( function () {
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
	 * Render badges for a field.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param {Object} field Field data.
	 * @returns {Array} Badge elements.
	 */
	function renderFieldBadges( field ) {
		var badges = [];
		if ( field.is_required ) {
			badges.push(
				wp.element.createElement( 'span', { key: 'required', className: 'bb-pf-badge-text' }, __( 'Required', 'buddyboss' ) )
			);
		}
		if ( field.is_signup ) {
			badges.push(
				wp.element.createElement( 'span', { key: 'signup', className: 'bb-pf-badge bb-pf-badge--signup' }, __( 'Signup', 'buddyboss' ) )
			);
		}
		if ( field.member_types && field.member_types.length > 0 ) {
			var typeLabels = field.member_types.map( function ( typeKey ) {
				var found = memberTypes.find( function ( mt ) {
					return mt.id === typeKey;
				} );
				return found ? found.name : typeKey;
			} );
			badges.push(
				wp.element.createElement( 'span', { key: 'member-types', className: 'bb-pf-badge bb-pf-badge--member-type' }, typeLabels.join( ', ' ) )
			);
		}
		return badges;
	}

	// Loading state.
	if ( isLoading ) {
		return wp.element.createElement(
			'div',
			{ className: 'bb-settings-panel-content bb-pf-loading' },
			wp.element.createElement( Spinner, null ),
			wp.element.createElement( 'p', null, __( 'Loading profile fields...', 'buddyboss' ) )
		);
	}

	return wp.element.createElement(
		'div',
		{ className: 'bb-settings-panel-content bb-profile-fields-screen' },

		// Toast notification.
		toast && wp.element.createElement( Toast, {
			status: toast.status,
			message: toast.message,
			onDismiss: function () { setToast( null ); },
		} ),

		// Top banner.
		wp.element.createElement(
			'div',
			{ className: 'bb-pf-banner' },
			wp.element.createElement(
				'p',
				null,
				__( 'Manage the profile field sets and fields that appear on user profiles and registration.', 'buddyboss' )
			)
		),

		// Field set cards.
		fieldGroups.map( function ( group, groupIndex ) {
			var isCollapsed = collapsed[ group.id ];
			var isDragOver = 'group' === dragType && dragOverItem === groupIndex;

			return wp.element.createElement(
				'div',
				{
					key: group.id,
					className: 'bb-pf-fieldset-card' + ( isDragOver ? ' bb-pf-drag-over' : '' ),
					draggable: true,
					onDragStart: function ( e ) { handleGroupDragStart( e, groupIndex ); },
					onDragOver: function ( e ) { handleGroupDragOver( e, groupIndex ); },
					onDrop: handleGroupDrop,
					onDragEnd: function () {
						setDragItem( null );
						setDragOverItem( null );
						setDragType( null );
					},
				},

				// Card header.
				wp.element.createElement(
					'div',
					{ className: 'bb-pf-fieldset-header' },
					wp.element.createElement(
						'div',
						{ className: 'bb-pf-fieldset-header-left' },
						wp.element.createElement(
							'span',
							{ className: 'bb-pf-drag-handle' },
							wp.element.createElement( 'i', { className: 'bb-icons-rl-list' } )
						),
						wp.element.createElement(
							'button',
							{
								className: 'bb-pf-fieldset-toggle',
								onClick: function () { toggleCollapse( group.id ); },
								'aria-expanded': ! isCollapsed,
							},
							wp.element.createElement( 'i', {
								className: isCollapsed ? 'bb-icons-rl-caret-right' : 'bb-icons-rl-caret-down',
							} ),
							wp.element.createElement( 'h3', null, decodeEntities( group.name ) ),
							group.is_repeater && wp.element.createElement(
								'span',
								{ className: 'bb-pf-badge bb-pf-badge--repeater' },
								__( 'Repeater', 'buddyboss' )
							)
						)
					),
					wp.element.createElement(
						'div',
						{ className: 'bb-pf-fieldset-header-right' },
						wp.element.createElement(
							Button,
							{
								variant: 'primary',
								isSmall: true,
								onClick: function () {
									setEditFieldSet( group );
								},
							},
							wp.element.createElement( 'i', { className: 'bb-icons-rl bb-icons-rl-note-pencil' } ),
							' ',
							__( 'Edit Field Set', 'buddyboss' )
						)
					)
				),

				// Card body (fields list).
				! isCollapsed && wp.element.createElement(
					'div',
					{ className: 'bb-pf-fieldset-body' },
					( group.fields && group.fields.length > 0 )
						? group.fields.map( function ( field, fieldIndex ) {
							var isFieldDragOver = 'field' === dragType && dragOverItem && dragOverItem.groupId === group.id && dragOverItem.fieldIdx === fieldIndex;

							return wp.element.createElement(
								'div',
								{
									key: field.id,
									className: 'bb-pf-field-row' + ( isFieldDragOver ? ' bb-pf-drag-over' : '' ),
									draggable: true,
									onDragStart: function ( e ) { handleFieldDragStart( e, group.id, fieldIndex ); },
									onDragOver: function ( e ) { handleFieldDragOver( e, group.id, fieldIndex ); },
									onDrop: function ( e ) {
										e.stopPropagation();
										handleFieldDrop();
									},
									onDragEnd: function () {
										setDragItem( null );
										setDragOverItem( null );
										setDragType( null );
									},
								},
								wp.element.createElement(
									'span',
									{ className: 'bb-pf-drag-handle' },
									wp.element.createElement( 'i', { className: 'bb-icons-rl-list' } )
								),
								wp.element.createElement(
									'span',
									{ className: 'bb-pf-field-type-icon' },
									wp.element.createElement( 'i', { className: getFieldTypeIcon( field.type ) } )
								),
								wp.element.createElement(
									'span',
									{ className: 'bb-pf-field-name' },
									decodeEntities( field.name )
								),
								wp.element.createElement(
									'span',
									{ className: 'bb-pf-field-badges' },
									renderFieldBadges( field )
								),
								wp.element.createElement(
									'div',
									{ className: 'bb-pf-field-actions' },
									wp.element.createElement(
										'button',
										{
											className: 'bb-pf-ellipsis-btn',
											onClick: function ( e ) {
												e.stopPropagation();
												setOpenMenuId( openMenuId === field.id ? null : field.id );
											},
										},
										wp.element.createElement( 'i', { className: 'bb-icons-rl-dots-three-outline-vertical' } )
									),
									openMenuId === field.id && wp.element.createElement(
										'div',
										{ className: 'bb-pf-dropdown-menu' },
										wp.element.createElement(
											'button',
											{
												onClick: function () {
													setOpenMenuId( null );
													setEditField( { field: field, groupId: group.id } );
												},
											},
											__( 'Edit', 'buddyboss' )
										),
										field.can_delete && wp.element.createElement(
											'button',
											{
												className: 'bb-pf-dropdown-delete',
												onClick: function () {
													setOpenMenuId( null );
													setDeleteFieldData( field );
												},
											},
											__( 'Delete', 'buddyboss' )
										)
									)
								)
							);
						} )
						: wp.element.createElement(
							'p',
							{ className: 'bb-pf-no-fields' },
							__( 'No fields in this field set.', 'buddyboss' )
						),

					// Add New Field button.
					wp.element.createElement(
						Button,
						{
							variant: 'link',
							className: 'bb-pf-add-field-btn',
							onClick: function () {
								setEditField( { field: null, groupId: group.id } );
							},
						},
						'+ ' + __( 'Add New Field', 'buddyboss' )
					)
				)
			);
		} ),

		// Add New Field Set button.
		wp.element.createElement(
			Button,
			{
				variant: 'secondary',
				className: 'bb-pf-add-fieldset-btn',
				onClick: function () {
					setEditFieldSet( {} );
				},
			},
			'+ ' + __( 'Add New Field Set', 'buddyboss' )
		),

		// Field Set Modal (Add/Edit).
		null !== editFieldSet && wp.element.createElement( FieldSetModal, {
			fieldSet: editFieldSet,
			onClose: function () { setEditFieldSet( null ); },
			onSave: function () {
				setEditFieldSet( null );
				loadFieldGroups();
			},
			onDelete: function () {
				setEditFieldSet( null );
				// Open delete confirmation.
				setDeleteFieldSetData( editFieldSet );
			},
			setToast: setToast,
		} ),

		// Delete Field Set Modal.
		null !== deleteFieldSetData && wp.element.createElement( DeleteFieldSetModal, {
			fieldSet: deleteFieldSetData,
			onClose: function () { setDeleteFieldSetData( null ); },
			onDeleted: function () {
				setDeleteFieldSetData( null );
				loadFieldGroups();
			},
			setToast: setToast,
		} ),

		// Profile Field Modal (Add/Edit).
		null !== editField && wp.element.createElement( ProfileFieldModal, {
			field: editField.field,
			groupId: editField.groupId,
			fieldTypes: fieldTypes,
			memberTypes: memberTypes,
			visibilityLevels: visibilityLevels,
			allFieldGroups: fieldGroups,
			onClose: function () { setEditField( null ); },
			onSave: function () {
				setEditField( null );
				loadFieldGroups();
			},
			setToast: setToast,
		} ),

		// Delete field confirmation.
		null !== deleteFieldData && wp.element.createElement(
			'div',
			{ className: 'bb-pf-confirm-overlay' },
			wp.element.createElement(
				'div',
				{ className: 'bb-pf-confirm-dialog' },
				wp.element.createElement( 'p', null,
					/* translators: %s: field name */
					wp.element.sprintf( __( 'Are you sure you want to delete the field "%s"? This action cannot be undone.', 'buddyboss' ), decodeEntities( deleteFieldData.name ) )
				),
				wp.element.createElement(
					'div',
					{ className: 'bb-pf-confirm-actions' },
					wp.element.createElement(
						Button,
						{
							variant: 'secondary',
							onClick: function () { setDeleteFieldData( null ); },
						},
						__( 'Cancel', 'buddyboss' )
					),
					wp.element.createElement(
						Button,
						{
							variant: 'primary',
							isDestructive: true,
							onClick: function () { handleDeleteField( deleteFieldData.id ); },
						},
						__( 'Delete', 'buddyboss' )
					)
				)
			)
		)
	);
}

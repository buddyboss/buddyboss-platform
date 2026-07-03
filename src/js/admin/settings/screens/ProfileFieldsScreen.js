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
import { __, sprintf } from '@wordpress/i18n';
import { decodeEntities } from '@wordpress/html-entities';
import {
	getProfileFieldGroups,
	deleteProfileField,
	reorderProfileFields,
} from '../utils/ajax';
import { Toast, useAutoDismissToast } from '../components/Toast';
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
	useAutoDismissToast( toast, setToast );

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

	// AbortController ref for delete requests.
	var deleteAbortRef = useRef( null );

	// Reason the most recently hovered cross-set drop target is refused, or
	// null when the current target is droppable. Captured during dragOver (the
	// only point that knows both the dragged field and the hovered set) and
	// consumed on drop so a refused release can explain itself instead of
	// bouncing silently.
	var dragBlockReasonRef = useRef( null );

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
					setToast( { status: 'error', message: error.message || __( 'Failed to load profile fields.', 'buddyboss-platform' ) } );
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
			if ( deleteAbortRef.current ) {
				deleteAbortRef.current.abort();
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

		// Cancel any stale delete request.
		if ( deleteAbortRef.current ) {
			deleteAbortRef.current.abort();
		}
		deleteAbortRef.current = new AbortController();

		deleteProfileField( fieldId, { signal: deleteAbortRef.current.signal } )
			.then( function ( response ) {
				if ( response.success ) {
					setToast( { status: 'success', message: response.data.message || __( 'Field deleted.', 'buddyboss-platform' ) } );
					loadFieldGroups();
				} else {
					setToast( { status: 'error', message: ( response.data && response.data.message ) || __( 'Failed to delete field.', 'buddyboss-platform' ) } );
				}
			} )
			.catch( function ( error ) {
				if ( 'AbortError' === error.name ) {
					return;
				}
				setToast( { status: 'error', message: error.message || __( 'Failed to delete field.', 'buddyboss-platform' ) } );
			} );
	}

	/**
	 * Reset all drag-related state.
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	function resetDrag() {
		setDragItem( null );
		setDragOverItem( null );
		setDragType( null );
		dragBlockReasonRef.current = null;
	}

	/**
	 * End-of-drag handler.
	 *
	 * `dragend` always fires, even when the browser suppresses the `drop`
	 * event for a refused target (dropEffect 'none'). Surface any reason
	 * captured during the hover here so a blocked release is explained, then
	 * reset. Idempotent with the drop path: whichever runs first clears the
	 * ref, so the user sees a single toast. During a group reorder the ref is
	 * never set, so this is a plain reset.
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	function handleDragEnd() {
		if ( dragBlockReasonRef.current ) {
			setToast( { status: 'error', message: dragBlockReasonRef.current } );
		}
		resetDrag();
	}

	/**
	 * Persist a reorder payload, cancelling any in-flight reorder request.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param {Object} payload Reorder payload ({ group_order } or { field_order }).
	 */
	function saveReorder( payload ) {
		if ( reorderAbortRef.current ) {
			reorderAbortRef.current.abort();
		}
		reorderAbortRef.current = new AbortController();

		// Show a sticky "saving" toast while the request is in flight; it's
		// replaced by the success or error toast once the request settles.
		setToast( { status: 'saving', message: __( 'Saving order…', 'buddyboss' ) } );

		reorderProfileFields( payload, { signal: reorderAbortRef.current.signal } )
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
	}

	/**
	 * Check whether a field may be moved into a different field set.
	 *
	 * Mirrors the server-side guardrails so the UI can block the drop and
	 * explain why before a request is ever sent. Returns null when the move is
	 * allowed, otherwise a human-readable reason string.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param {number} sourceGroupId Group the field currently belongs to.
	 * @param {number} fieldIdx      Field index within the source group.
	 * @param {number} targetGroupId Group the field is being moved into.
	 * @returns {string|null} Block reason, or null when the move is permitted.
	 */
	function getCrossGroupBlockReason( sourceGroupId, fieldIdx, targetGroupId ) {
		if ( sourceGroupId === targetGroupId ) {
			return null;
		}

		var sourceGroup = fieldGroups.find( function ( g ) { return g.id === sourceGroupId; } );
		var targetGroup = fieldGroups.find( function ( g ) { return g.id === targetGroupId; } );
		var field = sourceGroup && sourceGroup.fields ? sourceGroup.fields[ fieldIdx ] : null;

		if ( ! sourceGroup || ! targetGroup || ! field ) {
			return __( 'This field can’t be moved here.', 'buddyboss' );
		}
		if ( ! field.can_delete || field.is_default_field ) {
			return __( 'This field is required by the platform and can’t be moved to another field set.', 'buddyboss' );
		}
		if ( sourceGroup.is_repeater || targetGroup.is_repeater ) {
			return __( 'Fields can’t be moved into or out of a repeater field set.', 'buddyboss' );
		}
		return null;
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
		} else if ( 'field' === dragType && dragItem ) {
			// Hovering a field set card (outside any field row) targets the end
			// of that set, which is also the only way to drop into an empty set.
			var group = fieldGroups[ index ];
			if ( ! group ) {
				return;
			}

			// Mirror the row-level behavior: block a disallowed cross-set drop
			// with the not-allowed cursor and clear any stale drop target so a
			// release on the blocked card can't commit a prior hover position.
			// The reason is stashed so the drop handler can surface it.
			var cardBlockReason = getCrossGroupBlockReason( dragItem.groupId, dragItem.fieldIdx, group.id );
			if ( cardBlockReason ) {
				dragBlockReasonRef.current = cardBlockReason;
				e.dataTransfer.dropEffect = 'none';
				if ( null !== dragOverItem ) {
					setDragOverItem( null );
				}
				return;
			}

			dragBlockReasonRef.current = null;
			e.dataTransfer.dropEffect = 'move';
			setDragOverItem( { groupId: group.id, fieldIdx: group.fields ? group.fields.length : 0 } );
		}
	}

	/**
	 * Handle group drop.
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	function handleGroupDrop() {
		// A field dropped onto the card body (not a specific row) is a field
		// move into this set — delegate to the shared field-move handler.
		if ( 'field' === dragType ) {
			commitFieldMove();
			return;
		}

		if ( 'group' !== dragType || null === dragItem || null === dragOverItem || dragItem === dragOverItem ) {
			resetDrag();
			return;
		}

		var newGroups = fieldGroups.slice();
		var draggedGroup = newGroups.splice( dragItem, 1 )[ 0 ];
		newGroups.splice( dragOverItem, 0, draggedGroup );
		setFieldGroups( newGroups );

		var groupOrder = {};
		newGroups.forEach( function ( group, index ) {
			groupOrder[ index ] = group.id;
		} );

		saveReorder( { group_order: groupOrder } );
		resetDrag();
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
		if ( 'field' !== dragType || ! dragItem ) {
			return;
		}

		// Signal a disallowed cross-set move with the not-allowed cursor and
		// clear any stale drop target so a release in the blocked region can't
		// commit a prior (in-source-group) hover position. The reason is
		// stashed so the drop handler can surface it instead of bouncing
		// silently.
		var rowBlockReason = getCrossGroupBlockReason( dragItem.groupId, dragItem.fieldIdx, groupId );
		if ( rowBlockReason ) {
			dragBlockReasonRef.current = rowBlockReason;
			e.dataTransfer.dropEffect = 'none';
			if ( null !== dragOverItem ) {
				setDragOverItem( null );
			}
			return;
		}

		dragBlockReasonRef.current = null;
		e.dataTransfer.dropEffect = 'move';
		setDragOverItem( { groupId: groupId, fieldIdx: fieldIdx } );
	}

	/**
	 * Commit a field move (reorder within a set, or move across sets).
	 *
	 * Shared by both the field-row drop target and the field-set card drop
	 * target (the latter handles drops into an empty set or at the end of a
	 * set). Cross-set moves are validated against the same guardrails the
	 * server enforces. A refused move never commits (and the field reverts to
	 * its source position); when a reason was captured during the hover it is
	 * surfaced as an error toast so the user understands why.
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	function commitFieldMove() {
		// A refused cross-set hover clears dragOverItem but stashes the reason.
		// Read it before resetDrag() wipes the ref so the release can explain
		// itself rather than bouncing silently.
		var stashedBlockReason = dragBlockReasonRef.current;
		if ( 'field' !== dragType || ! dragItem || ! dragOverItem ) {
			if ( stashedBlockReason ) {
				setToast( { status: 'error', message: stashedBlockReason } );
			}
			resetDrag();
			return;
		}

		var sourceGroupId = dragItem.groupId;
		var targetGroupId = dragOverItem.groupId;

		// No-op: dropped on its own position within the same set.
		if ( sourceGroupId === targetGroupId && dragItem.fieldIdx === dragOverItem.fieldIdx ) {
			resetDrag();
			return;
		}

		// Enforce cross-set guardrails (mirrors the server). With the dragOver
		// handlers clearing dragOverItem on blocked hovers, a blocked target
		// should already have been filtered out before we reach here — this is
		// a belt-and-braces guard for programmatic drops. Surface the reason so
		// the refused drop is explained rather than silently dropped.
		if ( sourceGroupId !== targetGroupId ) {
			var crossSetBlockReason = getCrossGroupBlockReason( sourceGroupId, dragItem.fieldIdx, targetGroupId );
			if ( crossSetBlockReason ) {
				setToast( { status: 'error', message: crossSetBlockReason } );
				resetDrag();
				return;
			}
		}

		// Work on shallow clones so the move can span two sets at once.
		var newGroups = fieldGroups.map( function ( group ) {
			return Object.assign( {}, group, { fields: group.fields ? group.fields.slice() : [] } );
		} );
		var sourceGroup = newGroups.find( function ( g ) { return g.id === sourceGroupId; } );
		var targetGroup = newGroups.find( function ( g ) { return g.id === targetGroupId; } );
		if ( ! sourceGroup || ! targetGroup ) {
			resetDrag();
			return;
		}

		var movedField = sourceGroup.fields.splice( dragItem.fieldIdx, 1 )[ 0 ];
		if ( ! movedField ) {
			resetDrag();
			return;
		}
		// Insert at the hovered row's index. For a same-set reorder, source and
		// target are the same array, so splicing out then in at the raw index
		// reproduces the original within-set behavior exactly. For a cross-set
		// move the source removal doesn't shift the target array, so the raw
		// index is already correct.
		targetGroup.fields.splice( dragOverItem.fieldIdx, 0, movedField );
		setFieldGroups( newGroups );

		// Build the field order payload for every affected set (one for an
		// in-set reorder, two for a cross-set move).
		var fieldOrder = {};
		var affectedIds = sourceGroupId === targetGroupId ? [ targetGroupId ] : [ sourceGroupId, targetGroupId ];
		affectedIds.forEach( function ( gid ) {
			var group = newGroups.find( function ( g ) { return g.id === gid; } );
			if ( group ) {
				fieldOrder[ gid ] = {};
				group.fields.forEach( function ( field, index ) {
					fieldOrder[ gid ][ index ] = field.id;
				} );
			}
		} );

		saveReorder( { field_order: fieldOrder } );
		resetDrag();
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
			return <span className="bb-pf-badge-text">{ __( 'required', 'buddyboss-platform' ) }</span>;
		}
		return null;
	}

	/**
	 * Render badge pills for a field.
	 *
	 * Profile-type assignments render one pill per type. When the list
	 * exceeds MAX_VISIBLE_MEMBER_TYPE_PILLS the remainder collapses into
	 * a `+N` overflow pill whose title attribute lists the hidden names
	 * so the full set is still discoverable on hover.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param {Object} field Field data.
	 * @returns {Array|null} Badge elements or null.
	 */
	function renderFieldBadgePills( field ) {
		var MAX_VISIBLE_MEMBER_TYPE_PILLS = 3;
		var badges = [];
		if ( field.is_signup ) {
			badges.push(
				<span key="signup" className="bb-pf-badge bb-pf-badge--signup">{ __( 'Signup', 'buddyboss-platform' ) }</span>
			);
		}
		if ( 'none' === field.member_type_mode ) {
			// Match the dropdown option label verbatim so the list view
			// reads the same string the modal does. The previous short
			// "No Profile Type" was ambiguous — could be read as "no
			// profile type assigned yet" instead of its actual meaning
			// ("only users who don't have a profile type").
			badges.push(
				<span key="member-types-none" className="bb-pf-badge bb-pf-badge--member-type">{ __( 'No Profile Type Users', 'buddyboss-platform' ) }</span>
			);
		} else if ( 'all' === field.member_type_mode ) {
			// Explicit "All Profile Types" badge so the list communicates
			// the mode at a glance. Without it the row would look identical
			// to a freshly-created field that's never had its visibility
			// configured — the badge removes that ambiguity.
			badges.push(
				<span key="member-types-all" className="bb-pf-badge bb-pf-badge--member-type">{ __( 'All Profile Types', 'buddyboss-platform' ) }</span>
			);
		} else if ( field.member_types && field.member_types.length > 0 ) {
			var typeLabels = field.member_types.map( function ( typeKey ) {
				var found = memberTypes.find( function ( mt ) {
					return mt.id === typeKey;
				} );
				return found ? decodeEntities( found.name ) : typeKey;
			} );

			var visibleLabels = typeLabels.slice( 0, MAX_VISIBLE_MEMBER_TYPE_PILLS );
			var overflowLabels = typeLabels.slice( MAX_VISIBLE_MEMBER_TYPE_PILLS );

			visibleLabels.forEach( function ( label, idx ) {
				badges.push(
					<span
						key={ 'member-type-' + idx }
						className="bb-pf-badge bb-pf-badge--member-type"
						title={ label }
					>
						{ label }
					</span>
				);
			} );

			if ( overflowLabels.length > 0 ) {
				badges.push(
					<span
						key="member-type-overflow"
						className="bb-pf-badge bb-pf-badge--member-type bb-pf-badge--overflow"
						title={ overflowLabels.join( ', ' ) }
					>
						{ sprintf( '+%d', overflowLabels.length ) }
					</span>
				);
			}
		}
		return badges.length > 0 ? badges : null;
	}

	// Loading state.
	if ( isLoading ) {
		return (
			<div className="bb-settings-panel-content bb-pf-loading">
				<Spinner />
				<p>{ __( 'Loading profile fields...', 'buddyboss-platform' ) }</p>
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
				<p>{ __( 'Select the fields you wish to display on your registration page.', 'buddyboss-platform' ) }</p>
				<a
					href="#signup-fields"
					className="bb-pf-banner__select-link"
					onClick={ function ( e ) {
						e.preventDefault();
						var target = document.getElementById( 'signup-fields' );
						if ( target ) {
							target.scrollIntoView( { behavior: 'smooth', block: 'start' } );
						}
					} }
				>
					{ __( 'Select', 'buddyboss-platform' ) }
					<i className="bb-icons-rl bb-icons-rl-arrow-right" aria-hidden="true"></i>
				</a>
			</div>

			{/* Field set cards. */}
			<div id="signup-fields">
			{ fieldGroups.map( function ( group, groupIndex ) {
				var isCollapsed = collapsed[ group.id ];
				var isDragOver = 'group' === dragType && dragOverItem === groupIndex;
				// Highlight the whole card while a field from another set is
				// dragged over it (only when the move is permitted).
				var isFieldDropTarget = 'field' === dragType && dragItem && dragOverItem &&
					dragOverItem.groupId === group.id && dragItem.groupId !== group.id;

				return (
					<div
						key={ group.id }
						className={ 'bb-pf-fieldset-card' + ( ( isDragOver || isFieldDropTarget ) ? ' bb-pf-drag-over' : '' ) + ( isCollapsed ? ' bb-pf-fieldset-card--collapsed' : '' ) }
						draggable={ true }
						onDragStart={ function ( e ) { handleGroupDragStart( e, groupIndex ); } }
						onDragOver={ function ( e ) { handleGroupDragOver( e, groupIndex ); } }
						onDrop={ handleGroupDrop }
						onDragEnd={ handleDragEnd }
					>

						{/* Card header. */}
						<div className="bb-pf-fieldset-header">
							<div className="bb-pf-fieldset-header-left">
								<span
									className="bb-pf-drag-handle"
									aria-label={ __( 'Drag to reorder field set', 'buddyboss-platform' ) }
								>
									<i className="bb-icons-rl-list" />
								</span>
								<h3>{ decodeEntities( group.name ) }</h3>
								{ group.is_repeater && (
									<span className="bb-pf-badge bb-pf-badge--repeater">
										{ __( 'Repeater', 'buddyboss-platform' ) }
									</span>
								) }
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
									{ __( 'Edit Field Set', 'buddyboss-platform' ) }
								</Button>

								<button
									className="bb-pf-fieldset-toggle"
									onClick={ function () { toggleCollapse( group.id ); } }
									aria-expanded={ ! isCollapsed }
								>
									<i className={ isCollapsed ? 'bb-icons-rl-caret-right' : 'bb-icons-rl-caret-down' } />
								</button>
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
													commitFieldMove();
												} }
												onDragEnd={ handleDragEnd }
											>
												<div className="bb-pf-field-left">
													<span
														className="bb-pf-drag-handle"
														aria-label={ __( 'Drag to reorder field', 'buddyboss-platform' ) }
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
														aria-label={ __( 'Actions', 'buddyboss-platform' ) }
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
																	{ ' ' + __( 'Edit', 'buddyboss-platform' ) }
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
																		{ ' ' + __( 'Delete', 'buddyboss-platform' ) }
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
											{ __( 'No fields in this field set.', 'buddyboss-platform' ) }
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
									{ ' ' + __( 'Add New Field', 'buddyboss-platform' ) }
								</Button>
							</div>
						) }
					</div>
				);
			} ) }
			</div>

			{/* Add New Field Set button. */}
			<Button
				variant="primary"
				className="bb-pf-add-fieldset-btn"
				onClick={ function () {
					setEditFieldSet( {} );
				} }
			>
				<i className="bb-icons-rl bb-icons-rl-plus" />
				{ ' ' + __( 'Add New Field Set', 'buddyboss-platform' ) }
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
				title={ __( 'Delete Field', 'buddyboss-platform' ) }
				message={ deleteFieldData
					? sprintf( __( 'Are you sure you want to delete the field "%s"? This action cannot be undone.', 'buddyboss-platform' ), decodeEntities( deleteFieldData.name ) )
					: ''
				}
				confirmLabel={ __( 'Delete', 'buddyboss-platform' ) }
				cancelLabel={ __( 'Cancel', 'buddyboss-platform' ) }
				isDestructive={ true }
				onConfirm={ function () { handleDeleteField( deleteFieldData.id ); } }
				onCancel={ function () { setDeleteFieldData( null ); } }
			/>
		</div>
	);
}

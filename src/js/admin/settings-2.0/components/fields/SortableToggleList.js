/**
 * BuddyBoss Admin Settings 2.0 — SortableToggleList Component
 *
 * Drag-sortable list of predefined items where each item has an `enabled` toggle
 * and an icon. Used by Appearance → Menus → bb_rl_side_menu, and reusable for any
 * future field that needs to reorder + enable/disable a fixed set of items
 * (e.g., footer menu, mobile tab order).
 *
 * Items are NOT user-addable (use `EditableLinkList` for that pattern).
 *
 * Field config keys (from PHP `bb_register_feature_field()`):
 *   - `available_items` (array) — predefined items: [{ id, label, icon }, ...]
 *
 * Value shape (stored in DB option, also returned to onChange):
 *   { itemId: { enabled: bool, order: int, icon: string }, ... }
 *
 * @package BuddyBoss\Core\Administration
 * @since BuddyBoss [BBVERSION]
 */

import { useMemo } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { ToggleControl } from '@wordpress/components';
import { DragDropContext, Droppable, Draggable } from 'react-beautiful-dnd';

/**
 * Merge predefined item config with stored value into a single ordered render list.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param {Array}  availableItems Predefined items from PHP: [{ id, label, icon }, ...].
 * @param {Object} value          Stored map: { id: { enabled, order, icon }, ... }.
 * @returns {Array} Sorted render list: [{ id, label, icon, enabled }, ...].
 */
function buildRenderList( availableItems, value ) {
	var stored = value && typeof value === 'object' ? value : {};

	var items = availableItems.map( function ( item, index ) {
		var entry = stored[ item.id ] || {};
		return {
			id:      item.id,
			label:   item.label,
			icon:    entry.icon || item.icon || '',
			enabled: typeof entry.enabled === 'boolean' ? entry.enabled : true,
			order:   typeof entry.order === 'number' ? entry.order : index,
		};
	} );

	items.sort( function ( a, b ) {
		return a.order - b.order;
	} );

	return items;
}

/**
 * Convert the render list back to the stored map shape.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param {Array} renderList Items in their current order.
 * @returns {Object} Map shape for storage.
 */
function listToMap( renderList ) {
	var map = {};
	renderList.forEach( function ( item, index ) {
		map[ item.id ] = {
			enabled: !! item.enabled,
			order:   index,
			icon:    item.icon || '',
		};
	} );
	return map;
}

/**
 * SortableToggleList component.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param {Object}   props                Component props.
 * @param {Object}   props.value          Current map: { id: { enabled, order, icon } }.
 * @param {Function} props.onChange       Callback invoked with new map on reorder/toggle.
 * @param {Array}    props.availableItems Predefined items from PHP.
 * @param {boolean}  props.disabled       Whether the field is disabled.
 * @returns {JSX.Element} SortableToggleList component.
 */
export function SortableToggleList( { value, onChange, availableItems, disabled } ) {
	var items = useMemo( function () {
		return buildRenderList( availableItems || [], value );
	}, [ availableItems, value ] );

	function handleDragEnd( result ) {
		if ( ! result.destination ) {
			return;
		}
		if ( result.destination.index === result.source.index ) {
			return;
		}

		var reordered = Array.from( items );
		var moved     = reordered.splice( result.source.index, 1 )[0];
		reordered.splice( result.destination.index, 0, moved );

		onChange( listToMap( reordered ) );
	}

	function handleToggle( itemId, nextEnabled ) {
		var next = items.map( function ( item ) {
			if ( item.id !== itemId ) {
				return item;
			}
			return Object.assign( {}, item, { enabled: nextEnabled } );
		} );

		onChange( listToMap( next ) );
	}

	if ( ! items.length ) {
		return (
			<p className="bb-admin-sortable-toggle-list__empty">
				{ __( 'No items available.', 'buddyboss' ) }
			</p>
		);
	}

	return (
		<DragDropContext onDragEnd={ handleDragEnd }>
			<Droppable droppableId="bb-admin-sortable-toggle-list">
				{ function ( providedDroppable ) {
					return (
						<ul
							className="bb-admin-sortable-toggle-list"
							ref={ providedDroppable.innerRef }
							{ ...providedDroppable.droppableProps }
						>
							{ items.map( function ( item, index ) {
								return (
									<Draggable
										key={ item.id }
										draggableId={ item.id }
										index={ index }
										isDragDisabled={ !! disabled }
									>
										{ function ( providedDrag, snapshot ) {
											return (
												<li
													ref={ providedDrag.innerRef }
													{ ...providedDrag.draggableProps }
													className={ 'bb-admin-sortable-toggle-list__item' + ( snapshot.isDragging ? ' is-dragging' : '' ) }
												>
													<span
														className="bb-admin-sortable-toggle-list__handle"
														{ ...providedDrag.dragHandleProps }
														aria-label={ __( 'Drag to reorder', 'buddyboss' ) }
													>
														<i className="bb-icons-rl bb-icons-rl-dots-six-vertical" aria-hidden="true"></i>
													</span>

													<span className="bb-admin-sortable-toggle-list__toggle">
														<ToggleControl
															checked={ !! item.enabled }
															onChange={ function ( next ) {
																handleToggle( item.id, next );
															} }
															disabled={ disabled }
															__nextHasNoMarginBottom
														/>
													</span>

													{ item.icon && (
														<span className="bb-admin-sortable-toggle-list__icon" aria-hidden="true">
															<i className={ 'bb-icons-rl bb-icons-rl-' + item.icon }></i>
														</span>
													) }

													<span className="bb-admin-sortable-toggle-list__label">
														{ item.label }
													</span>
												</li>
											);
										} }
									</Draggable>
								);
							} ) }
							{ providedDroppable.placeholder }
						</ul>
					);
				} }
			</Droppable>
		</DragDropContext>
	);
}

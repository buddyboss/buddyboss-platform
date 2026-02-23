/**
 * BuddyBoss Admin Settings 2.0 - CheckboxListField Component
 *
 * Checkbox list with drag-and-drop reordering (e.g., Activity Feed Filters).
 *
 * @package BuddyBoss\Core\Administration
 * @since BuddyBoss [BBVERSION]
 */

import { ToggleControl } from '@wordpress/components';
import { DragDropContext, Droppable, Draggable } from 'react-beautiful-dnd';

/**
 * CheckboxListField component.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param {Object}   props                    Component props.
 * @param {Object}   props.field              Field definition.
 * @param {*}        props.value              Current field value.
 * @param {Function} props.onChange            Change handler (fieldName, newValue).
 * @param {boolean}  props.disabled           Whether the field is disabled.
 * @param {string}   props.sanitizedDescription Pre-sanitized HTML description.
 * @returns {JSX.Element} CheckboxListField component.
 */
export function CheckboxListField( { field, value, onChange, disabled, sanitizedDescription } ) {
	// Value can be either:
	// - An object like {"just-me": 1, "favorites": 0, ...} (from AJAX)
	// - An array like ["just-me", "favorites", ...] (legacy)
	var isObjectValue = value && typeof value === 'object' && !Array.isArray( value );
	var checkboxValue = isObjectValue ? value : {};

	// Helper to check if option is selected.
	var isOptionChecked = function ( optionKey ) {
		if ( isObjectValue ) {
			return !!checkboxValue[ optionKey ] && checkboxValue[ optionKey ] !== '0' && checkboxValue[ optionKey ] !== 0;
		}
		return Array.isArray( value ) && value.includes( optionKey );
	};

	// Build sorted options: use value key order first, then append remaining options.
	var optionMap = {};
	( field.options || [] ).forEach( function ( opt ) {
		optionMap[ opt.value ] = opt;
	} );
	var valueKeys = Object.keys( checkboxValue );
	var orderedOptions = [];

	valueKeys.forEach( function ( key ) {
		if ( optionMap[ key ] ) {
			orderedOptions.push( optionMap[ key ] );
		}
	} );
	( field.options || [] ).forEach( function ( opt ) {
		if ( ! valueKeys.includes( opt.value ) ) {
			orderedOptions.push( opt );
		}
	} );

	// Handle drag end: reorder items and rebuild value object with new key order.
	var handleCheckboxListDragEnd = function ( result ) {
		if ( ! result.destination ) {
			return;
		}
		if ( result.destination.index === result.source.index ) {
			return;
		}

		var items = Array.from( orderedOptions );
		var moved = items.splice( result.source.index, 1 )[ 0 ];
		items.splice( result.destination.index, 0, moved );

		var newValue = {};
		items.forEach( function ( item ) {
			newValue[ item.value ] = checkboxValue[ item.value ] !== undefined
				? ( typeof checkboxValue[ item.value ] === 'string' ? parseInt( checkboxValue[ item.value ], 10 ) : checkboxValue[ item.value ] )
				: 0;
		} );

		onChange( field.name, newValue );
	};

	return (
		<DragDropContext onDragEnd={ handleCheckboxListDragEnd }>
			{ field.description && (
				<p
					className="bb-admin-settings-form__field-head-description"
					dangerouslySetInnerHTML={ { __html: sanitizedDescription || '' } }
				/>
			) }
			<Droppable droppableId={ field.name }>
				{ ( provided ) => (
					<div
						ref={ provided.innerRef }
						{ ...provided.droppableProps }
						className="bb-admin-settings-field__checkbox-list"
					>
						{ orderedOptions.map( ( option, index ) => (
							<Draggable key={ option.value } draggableId={ option.value } index={ index }>
								{ ( providedDraggable, snapshot ) => (
									<div
										ref={ providedDraggable.innerRef }
										{ ...providedDraggable.draggableProps }
										{ ...providedDraggable.dragHandleProps }
										className={ 'bb-admin-settings-field__checkbox-list-item' + ( snapshot.isDragging ? ' is-dragging' : '' ) }
									>
										<i className="bb-icons-rl bb-icons-rl-list" />
										<ToggleControl
											label={ option.label }
											checked={ isOptionChecked( option.value ) }
											onChange={ ( checked ) => {
												// Preserve key order by rebuilding the object.
												var newValue = {};
												orderedOptions.forEach( function ( opt ) {
													if ( opt.value === option.value ) {
														newValue[ opt.value ] = checked ? 1 : 0;
													} else {
														newValue[ opt.value ] = checkboxValue[ opt.value ] !== undefined
															? ( typeof checkboxValue[ opt.value ] === 'string' ? parseInt( checkboxValue[ opt.value ], 10 ) : checkboxValue[ opt.value ] )
															: 0;
													}
												} );
												onChange( field.name, newValue );
											} }
											disabled={ disabled || !!option.disabled }
											__nextHasNoMarginBottom
										/>
									</div>
								) }
							</Draggable>
						) ) }
						{ provided.placeholder }
					</div>
				) }
			</Droppable>
		</DragDropContext>
	);
}

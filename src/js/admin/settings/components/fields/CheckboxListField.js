/**
 * BuddyBoss Admin Settings 2.0 - CheckboxListField Component
 *
 * Checkbox list with drag-and-drop reordering (e.g., Activity Feed Filters).
 *
 * @package BuddyBoss\Core\Administration
 * @since BuddyBoss [BBVERSION]
 */

import { ToggleControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
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

	// Scope the "Hidden" inline status tag to the navigation-order fields
	// only — `bb_group_nav_order` (Group Navigation Order) and
	// `bb_user_nav_order` (Profile Navigation Order). Other `checkbox_list`
	// consumers (Activity Feed Filters, etc.) keep the previous behaviour
	// where disabled options just render with the toggle off and no inline
	// indicator.
	//
	// A JS-side allowlist is intentional here vs. a per-field server flag:
	// the alternative would add a new key to the AJAX payload of EVERY
	// field on every panel just to opt-in two fields, which bloats the
	// per-page localized data with no benefit to the other ~200 fields.
	// If a third field later needs the same treatment, add its `name`
	// to the array below — one line.
	var NAV_ORDER_FIELD_NAMES = [ 'bb_group_nav_order', 'bb_user_nav_order' ];
	var showHiddenTagForDisabled = NAV_ORDER_FIELD_NAMES.indexOf( field.name ) >= 0;

	/**
	 * Build label with optional badge(s) for toggle items.
	 *
	 * Two badge sources can render next to the label, separately or together:
	 *   - `option.badge_label` — static badge from PHP registration (e.g. "Pro",
	 *     "Coming Soon"). Uses `__checkbox-list-badge` styling (darker pill).
	 *   - `! checked && showHiddenTagForDisabled` — dynamic "Hidden" status tag
	 *     rendered when the option's toggle is off, on the navigation-order
	 *     fields allow-listed via `NAV_ORDER_FIELD_NAMES` above (no PHP flag —
	 *     scoped client-side to avoid widening the AJAX payload across all
	 *     `checkbox_list` fields). Matches Figma listItem `statusTag` slot
	 *     (Backend-Settings-2.0 node 2611-123377) on the Group / Profile
	 *     Navigation Order screens — surfaces the disabled state inline so
	 *     admins can see at a glance which nav items won't render on the
	 *     frontend. Uses `__checkbox-list-status-tag` (lighter pill, distinct
	 *     from the static badge above).
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param {Object}  option  The option item.
	 * @param {boolean} checked Whether the option's toggle is currently on.
	 * @returns {JSX.Element|string} Label element or string.
	 */
	var buildOptionLabel = function ( option, checked ) {
		var hasStaticBadge = !! option.badge_label;
		var showHiddenTag  = showHiddenTagForDisabled && ! checked;

		if ( ! hasStaticBadge && ! showHiddenTag ) {
			return option.label;
		}

		return (
			<span className="bb-admin-settings-field__checkbox-list-label">
				{ option.label }
				{ hasStaticBadge && (
					<span className="bb-admin-settings-field__checkbox-list-badge">
						{ option.badge_label }
					</span>
				) }
				{ showHiddenTag && (
					// aria-hidden: the underlying ToggleControl already
					// announces the off state to screen readers, so the
					// visual "Hidden" pill would just double-announce.
					// Keep the text visible for sighted users, hide it
					// from the a11y tree.
					<span
						className="bb-admin-settings-field__checkbox-list-status-tag"
						aria-hidden="true"
					>
						{ __( 'Hidden', 'buddyboss' ) }
					</span>
				) }
			</span>
		);
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
											label={ buildOptionLabel( option, isOptionChecked( option.value ) ) }
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

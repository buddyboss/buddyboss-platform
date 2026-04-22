/**
 * BuddyBoss Admin Settings 2.0 — EditableLinkList Component
 *
 * Drag-sortable list of user-defined link items with add/edit/delete via modal.
 * Used by Appearance → Menus → bb_rl_custom_links, and reusable for any future
 * field that needs to manage a list of arbitrary `{ title, url }` items
 * (e.g., footer links, social profiles).
 *
 * Field config keys (from PHP):
 *   - `add_label`         (string) Optional label for the "Add" button (default: "Add New Link").
 *   - `modal_title_add`   (string) Modal title in add mode.
 *   - `modal_title_edit`  (string) Modal title in edit mode.
 *
 * Value shape (stored in DB option, also returned to onChange):
 *   [{ id: string, title: string, url: string, isEditing: false }, ...]
 *
 * @package BuddyBoss\Core\Administration
 * @since BuddyBoss [BBVERSION]
 */

import { useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { Button, Modal, TextControl } from '@wordpress/components';
import { DragDropContext, Droppable, Draggable } from 'react-beautiful-dnd';
import { safeUrl } from '../../utils/sanitize';

/**
 * Generate a stable-ish unique ID for a new link.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @returns {string} Unique ID.
 */
function newLinkId() {
	return 'link_' + Date.now() + '_' + Math.random().toString( 36 ).slice( 2, 7 );
}

/**
 * EditableLinkList component.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param {Object}   props          Component props.
 * @param {Array}    props.value    Current links array.
 * @param {Function} props.onChange Callback invoked with new array on add/edit/delete/reorder.
 * @param {boolean}  props.disabled Whether the field is disabled.
 * @param {Object}   [props.config] Field-level config (add_label, modal_title_add/_edit).
 * @returns {JSX.Element} EditableLinkList component.
 */
export function EditableLinkList( { value, onChange, disabled, config } ) {
	var safeValue        = Array.isArray( value ) ? value : [];
	var mergedConfig     = config || {};
	var addLabel         = mergedConfig.add_label || __( 'Add New Link', 'buddyboss' );
	var modalTitleAdd    = mergedConfig.modal_title_add || __( 'Add Link', 'buddyboss' );
	var modalTitleEdit   = mergedConfig.modal_title_edit || __( 'Edit Link', 'buddyboss' );

	var modalState = useState( null ); // null | { mode: 'add'|'edit', id, title, url }
	var modal      = modalState[0];
	var setModal   = modalState[1];

	function openAddModal() {
		setModal( { mode: 'add', id: newLinkId(), title: '', url: '' } );
	}

	function openEditModal( link ) {
		setModal( { mode: 'edit', id: link.id, title: link.title || '', url: link.url || '' } );
	}

	function closeModal() {
		setModal( null );
	}

	function handleSave() {
		if ( ! modal ) {
			return;
		}
		var trimmedTitle = ( modal.title || '' ).trim();
		var trimmedUrl   = ( modal.url || '' ).trim();
		if ( ! trimmedTitle || ! trimmedUrl ) {
			return;
		}

		var nextLinks;
		if ( 'add' === modal.mode ) {
			nextLinks = safeValue.concat( {
				id:        modal.id,
				title:     trimmedTitle,
				url:       trimmedUrl,
				isEditing: false,
			} );
		} else {
			nextLinks = safeValue.map( function ( link ) {
				if ( link.id !== modal.id ) {
					return link;
				}
				return Object.assign( {}, link, {
					title:     trimmedTitle,
					url:       trimmedUrl,
					isEditing: false,
				} );
			} );
		}
		onChange( nextLinks );
		closeModal();
	}

	function handleDelete( linkId ) {
		var nextLinks = safeValue.filter( function ( link ) {
			return link.id !== linkId;
		} );
		onChange( nextLinks );
	}

	function handleDragEnd( result ) {
		if ( ! result.destination ) {
			return;
		}
		if ( result.destination.index === result.source.index ) {
			return;
		}

		var reordered = Array.from( safeValue );
		var moved     = reordered.splice( result.source.index, 1 )[0];
		reordered.splice( result.destination.index, 0, moved );

		onChange( reordered );
	}

	// Boolean-coerce so `disabled={ ! canSubmit }` never receives the raw
	// string that String.prototype.trim() returns on mixed content.
	var canSubmit = !! ( modal && ( modal.title || '' ).trim() && ( modal.url || '' ).trim() );

	return (
		<div className="bb-admin-editable-link-list">
			{ safeValue.length > 0 && (
				<DragDropContext onDragEnd={ handleDragEnd }>
					<Droppable droppableId="bb-admin-editable-link-list">
						{ function ( providedDroppable ) {
							return (
								<ul
									className="bb-admin-editable-link-list__list"
									ref={ providedDroppable.innerRef }
									{ ...providedDroppable.droppableProps }
								>
									{ safeValue.map( function ( link, index ) {
										return (
											<Draggable
												key={ link.id }
												draggableId={ link.id }
												index={ index }
												isDragDisabled={ !! disabled }
											>
												{ function ( providedDrag, snapshot ) {
													return (
														<li
															ref={ providedDrag.innerRef }
															{ ...providedDrag.draggableProps }
															className={ 'bb-admin-editable-link-list__item' + ( snapshot.isDragging ? ' is-dragging' : '' ) }
														>
															<span
																className="bb-admin-editable-link-list__handle"
																{ ...providedDrag.dragHandleProps }
																aria-label={ __( 'Drag to reorder', 'buddyboss' ) }
															>
																<i className="bb-icons-rl bb-icons-rl-dots-six-vertical" aria-hidden="true"></i>
															</span>

															<span className="bb-admin-editable-link-list__body">
																<span className="bb-admin-editable-link-list__title-row">
																	<i className="bb-icons-rl bb-icons-rl-link bb-admin-editable-link-list__title-icon" aria-hidden="true"></i>
																	<span className="bb-admin-editable-link-list__title">
																		{ link.title }
																	</span>
																</span>
																<a
																	className="bb-admin-editable-link-list__url"
																	href={ safeUrl( link.url ) }
																	target="_blank"
																	rel="noopener noreferrer"
																>
																	{ link.url }
																</a>
															</span>

															<span className="bb-admin-editable-link-list__actions">
																<button
																	type="button"
																	className="bb-admin-editable-link-list__action-btn"
																	onClick={ function () {
																		openEditModal( link );
																	} }
																	disabled={ disabled }
																	aria-label={ __( 'Edit link', 'buddyboss' ) }
																>
																	<i className="bb-icons-rl bb-icons-rl-pencil-simple" aria-hidden="true"></i>
																</button>
																<button
																	type="button"
																	className="bb-admin-editable-link-list__action-btn bb-admin-editable-link-list__action-btn--delete"
																	onClick={ function () {
																		handleDelete( link.id );
																	} }
																	disabled={ disabled }
																	aria-label={ __( 'Delete link', 'buddyboss' ) }
																>
																	<i className="bb-icons-rl bb-icons-rl-trash" aria-hidden="true"></i>
																</button>
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
			) }

			<Button
				variant="primary"
				className="bb-admin-editable-link-list__add"
				onClick={ openAddModal }
				disabled={ disabled }
			>
				<i className="bb-icons-rl bb-icons-rl-plus" aria-hidden="true"></i>
				{ addLabel }
			</Button>

			{ modal && (
				<Modal
					title={ 'add' === modal.mode ? modalTitleAdd : modalTitleEdit }
					onRequestClose={ closeModal }
					className="bb-admin-editable-link-list__modal"
				>
					<TextControl
						label={ __( 'Title', 'buddyboss' ) }
						value={ modal.title }
						onChange={ function ( newTitle ) {
							setModal( Object.assign( {}, modal, { title: newTitle } ) );
						} }
						placeholder={ __( 'Enter title', 'buddyboss' ) }
						__nextHasNoMarginBottom
					/>
					<TextControl
						label={ __( 'URL', 'buddyboss' ) }
						type="url"
						value={ modal.url }
						onChange={ function ( newUrl ) {
							setModal( Object.assign( {}, modal, { url: newUrl } ) );
						} }
						placeholder={ __( 'Enter URL', 'buddyboss' ) }
						__nextHasNoMarginBottom
					/>
					<div className="bb-admin-editable-link-list__modal-actions">
						<Button variant="tertiary" onClick={ closeModal }>
							{ __( 'Cancel', 'buddyboss' ) }
						</Button>
						<Button variant="primary" onClick={ handleSave } disabled={ ! canSubmit }>
							{ 'add' === modal.mode ? __( 'Add Link', 'buddyboss' ) : __( 'Save', 'buddyboss' ) }
						</Button>
					</div>
				</Modal>
			) }
		</div>
	);
}

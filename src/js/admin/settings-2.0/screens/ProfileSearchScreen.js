/**
 * BuddyBoss Admin Settings 2.0 - Profile Search Screen
 *
 * Custom panel screen for managing profile search form fields
 * with drag-and-drop reordering.
 *
 * @package BuddyBoss\Core\Administration
 * @since BuddyBoss [BBVERSION]
 */

import { useState, useEffect, useCallback, useRef } from '@wordpress/element';
import { ToggleControl, Button, Spinner } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { decodeEntities } from '@wordpress/html-entities';
import {
	getProfileSearchFields,
	deleteProfileSearchField,
	reorderProfileSearchFields,
	getPlatformSettings,
	savePlatformSetting,
} from '../utils/ajax';
import { sanitizeHtml } from '../utils/sanitize';
import { Toast } from '../components/Toast';
import { HelpIcon } from '../components/HelpIcon';
import { ProfileSearchFieldModal } from '../components/modals/ProfileSearchFieldModal';
import { getSectionTitle, getFieldLabel, getFieldDescription, getFieldHelpText } from '../utils/feature';
import { getFieldTypeIcon } from '../utils/fieldTypeIcons';

/**
 * Profile Search Screen Component
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param {Object}   props             Component props.
 * @param {Function} props.onNavigate  Navigation handler.
 * @param {string}   props.helpUrl     Help URL for this panel.
 * @param {Function} props.onHelpClick Help icon click handler.
 * @param {Object}   props.feature     Feature data from FeatureSettingsScreen.
 * @param {string}   props.activePanelId Active panel ID.
 * @returns {JSX.Element} Profile search screen.
 */
export default function ProfileSearchScreen( { onNavigate, helpUrl, onHelpClick, feature, activePanelId } ) {

	var searchFieldsState = useState( [] );
	var searchFields = searchFieldsState[ 0 ];
	var setSearchFields = searchFieldsState[ 1 ];

	var availableFieldsState = useState( [] );
	var availableFields = availableFieldsState[ 0 ];
	var setAvailableFields = availableFieldsState[ 1 ];

	var isLoadingState = useState( true );
	var isLoading = isLoadingState[ 0 ];
	var setIsLoading = isLoadingState[ 1 ];

	var enableProfileSearchState = useState( false );
	var enableProfileSearch = enableProfileSearchState[ 0 ];
	var setEnableProfileSearch = enableProfileSearchState[ 1 ];

	var settingsLoadingState = useState( true );
	var settingsLoading = settingsLoadingState[ 0 ];
	var setSettingsLoading = settingsLoadingState[ 1 ];

	var toastState = useState( null );
	var toast = toastState[ 0 ];
	var setToast = toastState[ 1 ];

	// Modal state: null = closed, { field: null } = add new, { field: obj } = edit.
	var editFieldState = useState( null );
	var editField = editFieldState[ 0 ];
	var setEditField = editFieldState[ 1 ];

	// Delete confirmation state.
	var deleteFieldState = useState( null );
	var deleteFieldData = deleteFieldState[ 0 ];
	var setDeleteFieldData = deleteFieldState[ 1 ];

	// Ellipsis menu state.
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

	// AbortController refs.
	var abortRef = useRef( null );
	var reorderAbortRef = useRef( null );

	// Load platform settings (toggle).
	useEffect( function () {
		var controller = new AbortController();
		setSettingsLoading( true );
		getPlatformSettings( 'bp-enable-profile-search', { signal: controller.signal } )
			.then( function ( response ) {
				if ( response.success && response.data ) {
					setEnableProfileSearch( !! parseInt( response.data[ 'bp-enable-profile-search' ] ) );
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

	/**
	 * Load search fields data.
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	var loadSearchFields = useCallback( function () {
		if ( abortRef.current ) {
			abortRef.current.abort();
		}
		abortRef.current = new AbortController();

		setIsLoading( true );
		getProfileSearchFields( { signal: abortRef.current.signal } )
			.then( function ( response ) {
				if ( response.success && response.data ) {
					setSearchFields( response.data.fields || [] );
					setAvailableFields( response.data.available_fields || [] );
				}
				setIsLoading( false );
			} )
			.catch( function ( error ) {
				if ( 'AbortError' !== error.name ) {
					setIsLoading( false );
					setToast( { status: 'error', message: error.message || __( 'Failed to load search fields.', 'buddyboss' ) } );
				}
			} );
	}, [] );

	// Load on mount.
	useEffect( function () {
		loadSearchFields();
		return function () {
			if ( abortRef.current ) {
				abortRef.current.abort();
			}
			if ( reorderAbortRef.current ) {
				reorderAbortRef.current.abort();
			}
		};
	}, [ loadSearchFields ] );

	// Close ellipsis menu on outside click or Escape key.
	useEffect( function () {
		if ( null === openMenuId ) {
			return;
		}

		function handleMouseDown( e ) {
			if ( ! e.target.closest( '.bb-ps-field-actions' ) ) {
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
	 * Handle toggle change for profile search.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param {boolean} newValue New toggle value.
	 */
	var handleToggleChange = useCallback( function ( newValue ) {
		var prevValue = enableProfileSearch;
		setEnableProfileSearch( newValue );

		savePlatformSetting( 'bp-enable-profile-search', newValue ? 1 : 0 )
			.then( function ( response ) {
				if ( response.success ) {
					setToast( { status: 'success', message: __( 'Setting saved.', 'buddyboss' ) } );
				} else {
					setEnableProfileSearch( prevValue );
					setToast( { status: 'error', message: __( 'Failed to save setting.', 'buddyboss' ) } );
				}
			} )
			.catch( function () {
				setEnableProfileSearch( prevValue );
				setToast( { status: 'error', message: __( 'Failed to save setting.', 'buddyboss' ) } );
			} );
	}, [ enableProfileSearch ] );

	/**
	 * Handle field delete.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param {number} fieldIndex Field index to delete.
	 */
	function handleDeleteField( fieldIndex ) {
		setDeleteFieldData( null );

		deleteProfileSearchField( { field_index: fieldIndex } )
			.then( function ( response ) {
				if ( response.success ) {
					setToast( { status: 'success', message: response.data.message || __( 'Field removed.', 'buddyboss' ) } );
					loadSearchFields();
				} else {
					setToast( { status: 'error', message: ( response.data && response.data.message ) || __( 'Failed to remove field.', 'buddyboss' ) } );
				}
			} )
			.catch( function ( error ) {
				setToast( { status: 'error', message: error.message || __( 'Failed to remove field.', 'buddyboss' ) } );
			} );
	}

	/**
	 * Handle drag start.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param {Event}  e     Drag event.
	 * @param {number} index Field index.
	 */
	function handleDragStart( e, index ) {
		setDragItem( index );
		e.dataTransfer.effectAllowed = 'move';
	}

	/**
	 * Handle drag over.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param {Event}  e     Drag event.
	 * @param {number} index Field index.
	 */
	function handleDragOver( e, index ) {
		e.preventDefault();
		setDragOverItem( index );
	}

	/**
	 * Handle drop — reorder fields.
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	function handleDrop() {
		if ( null === dragItem || null === dragOverItem || dragItem === dragOverItem ) {
			setDragItem( null );
			setDragOverItem( null );
			return;
		}

		// Optimistic reorder.
		var newFields = searchFields.slice();
		var draggedField = newFields.splice( dragItem, 1 )[ 0 ];
		newFields.splice( dragOverItem, 0, draggedField );
		setSearchFields( newFields );

		// Build field_order (array of old indices in new order).
		var fieldOrder = newFields.map( function ( field ) {
			return field.id;
		} );

		// Cancel any stale reorder request.
		if ( reorderAbortRef.current ) {
			reorderAbortRef.current.abort();
		}
		reorderAbortRef.current = new AbortController();

		reorderProfileSearchFields( { field_order: fieldOrder }, { signal: reorderAbortRef.current.signal } )
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
				loadSearchFields();
			} );

		setDragItem( null );
		setDragOverItem( null );
	}


	return (
		<div className="bb-admin-profile-types bb-profile-search-screen">

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

			{/* Card 1: Profile Search Settings. */}
			<div className="bb-admin-feature-settings__section">
				<div className="bb-admin-feature-settings__section-header">
					<div className="bb-admin-feature-settings__section-header-left">
						<h3 className="bb-admin-feature-settings__section-title">
							{ getSectionTitle( feature, activePanelId, 'profile_search' ) || __( 'Profile Search', 'buddyboss' ) }
						</h3>
					</div>
					<div className="bb-admin-feature-settings__section-header-right">
						{ helpUrl && (
							<HelpIcon
								onClick={ onHelpClick }
								contentId={ helpUrl }
							/>
						) }
					</div>
				</div>
				<div className="bb-admin-feature-settings__section-body">
					{ settingsLoading
						? (
							<div className="bb-admin-loading">
								<Spinner />
							</div>
						)
						: (
							<div className="bb-admin-settings-form">
								<div className="bb-admin-settings-form__field">
									<div className="bb-admin-settings-form__field-label">
										{ getFieldLabel( feature, activePanelId, 'bp-enable-profile-search' ) || __( 'Profile Search', 'buddyboss' ) }
									</div>
									<div className="bb-admin-settings-form__field-content bb-admin-settings-form__field-content--inline">
										<div className="bb-admin-settings-form__field-input-wrapper">
											<div className="bb-admin-settings-form__toggle-wrapper">
												<ToggleControl
													label={ getFieldDescription( feature, activePanelId, 'bp-enable-profile-search' ) || __( 'Enable advanced profile search on the Members page', 'buddyboss' ) }
													checked={ enableProfileSearch }
													onChange={ handleToggleChange }
												/>
												{ getFieldHelpText( feature, activePanelId, 'bp-enable-profile-search' ) && (
													<span
														className="bb-admin-profile-types__setting-help-text"
														dangerouslySetInnerHTML={ { __html: sanitizeHtml( getFieldHelpText( feature, activePanelId, 'bp-enable-profile-search' ) ) } }
													/>
												) }
											</div>
										</div>
									</div>
								</div>
							</div>
						)
					}
				</div>
			</div>

			{/* Card 2: Form Fields (only when toggle is ON). */}
			{ enableProfileSearch && (
				<div className="bb-admin-feature-settings__section">
					<div className="bb-admin-feature-settings__section-header">
						<div className="bb-admin-feature-settings__section-header-left">
							<h3 className="bb-admin-feature-settings__section-title">
								{ __( 'Form Fields', 'buddyboss' ) }
							</h3>
						</div>
					</div>
					<div className="bb-admin-feature-settings__section-body">
						<p className="bb-admin-feature-settings__section-description">
							{ __( 'Profile search fields match your profile fields. Ensure options are named and assigned correctly, then select them here for search.', 'buddyboss' ) }
						</p>
						{ isLoading
							? (
								<div className="bb-admin-loading">
									<Spinner />
								</div>
							)
							: searchFields.length > 0
								? (
									<div className="bb-ps-field-list">
										{ searchFields.map( function ( field, index ) {
											var isDragOver = dragOverItem === index;

											return (
												<div
													key={ field.code + '-' + index }
													className={ 'bb-pf-field-row' + ( isDragOver ? ' bb-pf-drag-over' : '' ) }
													draggable={ true }
													onDragStart={ function ( e ) { handleDragStart( e, index ); } }
													onDragOver={ function ( e ) { handleDragOver( e, index ); } }
													onDrop={ handleDrop }
													onDragEnd={ function () {
														setDragItem( null );
														setDragOverItem( null );
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
													</div>
													<div className="bb-ps-field-actions bb-pf-field-actions">
														<button
															className="bb-pf-ellipsis-btn"
															onClick={ function ( e ) {
																e.stopPropagation();
																setOpenMenuId( openMenuId === index ? null : index );
															} }
															aria-label={ __( 'Actions', 'buddyboss' ) }
															aria-haspopup="true"
															aria-expanded={ index === openMenuId ? 'true' : 'false' }
														>
															<i className="bb-icons-rl-dots-three" />
														</button>
														{ openMenuId === index && (
															<div className="bb-pf-dropdown-menu bb_dropdown_menu_group components-menu-group" role="menu">
																<button
																	className="bb-pf-dropdown-edit components-menu-item__button"
																	role="menuitem"
																	onClick={ function () {
																		setOpenMenuId( null );
																		setEditField( { field: field } );
																	} }
																>
																	<span className="components-menu-item__item">
																		<i className="bb-icons-rl bb-icons-rl-note-pencil" />
																		{ ' ' + __( 'Edit', 'buddyboss' ) }
																	</span>
																</button>
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
															</div>
														) }
													</div>
												</div>
											);
										} ) }
									</div>
								)
								: (
									<div className="bb-admin-profile-types__empty">
										<p>{ __( 'No form fields configured. Click "Add Field" to add a search field.', 'buddyboss' ) }</p>
									</div>
								)
						}
						<button
							className="bb-admin-profile-fields__add-btn"
							onClick={ function () {
								setEditField( { field: null } );
							} }
						>
							<i className="bb-icons-rl bb-icons-rl-plus" />
							{ __( 'Add Field', 'buddyboss' ) }
						</button>
					</div>
				</div>
			) }

			{/* Profile Search Field Modal (Add/Edit). */}
			{ null !== editField && (
				<ProfileSearchFieldModal
					field={ editField.field }
					availableFields={ availableFields }
					existingFields={ searchFields }
					onClose={ function () { setEditField( null ); } }
					onSave={ function () {
						setEditField( null );
						loadSearchFields();
					} }
					setToast={ setToast }
				/>
			) }

			{/* Delete field confirmation. */}
			{ null !== deleteFieldData && (
				<div className="bb-pf-confirm-overlay">
					<div className="bb-pf-confirm-dialog">
						<p>
							{
								/* translators: %s: field name */
								wp.i18n.sprintf( __( 'Are you sure you want to remove the field "%s"? This action cannot be undone.', 'buddyboss' ), decodeEntities( deleteFieldData.label || deleteFieldData.name ) )
							}
						</p>
						<div className="bb-pf-confirm-actions">
							<Button
								variant="secondary"
								onClick={ function () { setDeleteFieldData( null ); } }
							>
								{ __( 'Cancel', 'buddyboss' ) }
							</Button>
							<Button
								variant="primary"
								isDestructive={ true }
								onClick={ function () { handleDeleteField( deleteFieldData.id ); } }
							>
								{ __( 'Remove', 'buddyboss' ) }
							</Button>
						</div>
					</div>
				</div>
			) }

		</div>
	);
}

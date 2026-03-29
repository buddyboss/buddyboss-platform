/**
 * BuddyBoss Admin Settings 2.0 - Email Template Add/Edit Modal
 *
 * Modal for creating or editing an email template (bp-email CPT).
 * Uses BB_Admin_Meta_Field_Registry for field definitions (same pattern
 * as GroupEditModal and ActivityEditModal).
 *
 * Custom Fields repeater is handled separately (not via registry).
 *
 * @package BuddyBoss\Core\Administration
 * @since BuddyBoss [BBVERSION]
 */

import { useState, useEffect, useRef, useCallback, useMemo } from '@wordpress/element';
import {
	Modal,
	Button,
	TextControl,
	TabPanel,
	Spinner,
	Popover,
} from '@wordpress/components';
import { __, sprintf } from '@wordpress/i18n';
import { decodeEntities } from '@wordpress/html-entities';
import { getEmailTemplate, saveEmailTemplate, getEmailSituations, getEmailMetaKeys } from '../../utils/ajax';
import { RegisteredMetaField } from '../common/RegisteredMetaField';
import { forceRemoveEditor } from '../common/RichTextEditor';

/**
 * Group registered fields by layout for half-width pairing.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param {Array} fields Registered fields array.
 * @returns {Array} Grouped items: { type: 'single', field } or { type: 'row', fields: [f1, f2] }.
 */
function groupFieldsWithLayout( fields ) {
	var result = [];
	var halfBuffer = null;

	fields.forEach( function ( field ) {
		if ( ! field.visible ) {
			return;
		}

		if ( 'half' === field.layout ) {
			if ( halfBuffer ) {
				result.push( { type: 'row', fields: [ halfBuffer, field ] } );
				halfBuffer = null;
			} else {
				halfBuffer = field;
			}
		} else {
			// Flush any unpaired half field.
			if ( halfBuffer ) {
				result.push( { type: 'single', field: halfBuffer } );
				halfBuffer = null;
			}
			result.push( { type: 'single', field: field } );
		}
	} );

	// Flush trailing half field.
	if ( halfBuffer ) {
		result.push( { type: 'single', field: halfBuffer } );
	}

	return result;
}

/**
 * Email Template Add/Edit Modal Component
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param {Object}   props              Component props.
 * @param {boolean}  props.isOpen       Whether the modal is open.
 * @param {number}   props.emailId      Email template ID (0 for add, >0 for edit).
 * @param {Array}    props.createFields Registered field definitions for add mode (from list screen).
 * @param {Function} props.onClose      Close handler.
 * @param {Function} props.onSaved      Success handler.
 * @returns {JSX.Element|null} Modal or null.
 */
export function EmailTemplateModal( { isOpen, emailId, createFields, onClose, onSaved } ) {
	// Registered field values keyed by field ID.
	var registeredValuesState = useState( {} );
	var registeredValues = registeredValuesState[0];
	var setRegisteredValues = registeredValuesState[1];

	// Registered field definitions from server.
	var registeredFieldsState = useState( [] );
	var registeredFields = registeredFieldsState[0];
	var setRegisteredFields = registeredFieldsState[1];

	// Custom fields (arbitrary post meta key/value pairs — not via registry).
	var customMetaState = useState( [] );
	var customMeta = customMetaState[0];
	var setCustomMeta = customMetaState[1];

	// Track which meta key dropdown is open (by index, -1 = none).
	var openDropdownState = useState( -1 );
	var openDropdown = openDropdownState[0];
	var setOpenDropdown = openDropdownState[1];

	var isSavingState = useState( false );
	var isSaving = isSavingState[0];
	var setIsSaving = isSavingState[1];

	var isLoadingState = useState( false );
	var isLoading = isLoadingState[0];
	var setIsLoading = isLoadingState[1];

	var errorState = useState( '' );
	var error = errorState[0];
	var setError = errorState[1];

	// Situations (tabbed radio data — fetched once, cached).
	var situationsState = useState( null );
	var situations = situationsState[0];
	var setSituations = situationsState[1];

	// Meta key suggestions for custom field name autocomplete.
	var metaKeysState = useState( [] );
	var metaKeys = metaKeysState[0];
	var setMetaKeys = metaKeysState[1];

	var isMountedRef = useRef( true );
	var situationsCacheRef = useRef( null );

	useEffect( function () {
		isMountedRef.current = true;
		return function () {
			isMountedRef.current = false;
		};
	}, [] );

	// Close meta key dropdown on click outside.
	useEffect( function () {
		if ( openDropdown < 0 ) {
			return;
		}

		var handleClickOutside = function ( e ) {
			if ( ! e.target.closest( '.bb-email-template-modal__meta-key-autocomplete' ) ) {
				setOpenDropdown( -1 );
			}
		};

		document.addEventListener( 'mousedown', handleClickOutside );
		return function () {
			document.removeEventListener( 'mousedown', handleClickOutside );
		};
	}, [ openDropdown ] );

	// Fetch situations (cached per session).
	useEffect( function () {
		if ( ! isOpen ) {
			return;
		}

		if ( situationsCacheRef.current ) {
			setSituations( situationsCacheRef.current );
		} else {
			getEmailSituations().then( function ( response ) {
				if ( ! isMountedRef.current ) {
					return;
				}
				if ( response.success && response.data ) {
					situationsCacheRef.current = response.data;
					setSituations( response.data );
				}
			} );
		}

		// Fetch meta key suggestions for custom field name autocomplete.
		if ( 0 === metaKeys.length ) {
			getEmailMetaKeys().then( function ( response ) {
				if ( ! isMountedRef.current ) {
					return;
				}
				if ( response.success && response.data ) {
					setMetaKeys( response.data );
				}
			} );
		}
	}, [ isOpen ] );

	// Load fields: add mode uses createFields prop, edit mode fetches from server.
	useEffect( function () {
		if ( ! isOpen ) {
			return;
		}

		setError( '' );

		if ( ! emailId ) {
			// Add mode — use createFields from list screen (already fetched).
			var fields = createFields || [];
			setRegisteredFields( fields );

			var initialValues = {};
			fields.forEach( function ( field ) {
				initialValues[ field.id ] = field.value !== null && field.value !== undefined ? field.value : '';
			} );
			setRegisteredValues( initialValues );
			setCustomMeta( [] );
			setIsLoading( false );
		} else {
			// Edit mode — fetch from server.
			setIsLoading( true );
			getEmailTemplate( { email_id: emailId } ).then( function ( response ) {
				if ( ! isMountedRef.current ) {
					return;
				}
				setIsLoading( false );
				if ( response.success && response.data ) {
					var editFields = response.data.registered_fields || [];
					setRegisteredFields( editFields );

					var editValues = {};
					editFields.forEach( function ( field ) {
						editValues[ field.id ] = field.value !== null && field.value !== undefined ? field.value : '';
					} );
					setRegisteredValues( editValues );
					setCustomMeta( response.data.custom_meta || [] );
				} else {
					setError( ( response.data && response.data.message ) || __( 'Failed to load template.', 'buddyboss' ) );
				}
			} ).catch( function () {
				if ( isMountedRef.current ) {
					setIsLoading( false );
					setError( __( 'An error occurred loading the template.', 'buddyboss' ) );
				}
			} );
		}
	}, [ isOpen, emailId, createFields ] );

	// Separate registered fields by tab — must be before early return to maintain hook order.
	var detailsFields = useMemo( function () {
		return registeredFields.filter( function ( f ) {
			// Skip email_type — rendered as custom Situation TabPanel below.
			if ( 'email_type' === f.id ) {
				return false;
			}
			return 'details' === f.tab || ! f.tab;
		} );
	}, [ registeredFields ] );

	var publishFields = useMemo( function () {
		return registeredFields.filter( function ( f ) {
			return 'publish' === f.tab;
		} );
	}, [ registeredFields ] );

	var groupedPublishFields = useMemo( function () {
		return groupFieldsWithLayout( publishFields );
	}, [ publishFields ] );

	if ( ! isOpen ) {
		return null;
	}

	/**
	 * Handle change for a registered field.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param {string} fieldId Field ID.
	 * @param {*}      val     New value.
	 */
	var handleRegisteredFieldChange = function ( fieldId, val ) {
		setRegisteredValues( function ( prev ) {
			var next = {};
			Object.keys( prev ).forEach( function ( k ) {
				next[ k ] = prev[ k ];
			} );
			next[ fieldId ] = val;
			return next;
		} );
	};

	/**
	 * Reset form to defaults.
	 */
	var resetForm = function () {
		setRegisteredValues( {} );
		setRegisteredFields( [] );
		setCustomMeta( [] );
		setError( '' );
	};

	/**
	 * Clean up TinyMCE editors.
	 */
	var cleanupEditors = function () {
		registeredFields.forEach( function ( field ) {
			if ( 'richtext' === field.type ) {
				var editorId = emailId
					? 'bb-admin-edit-' + field.id + '-' + emailId
					: 'bb-admin-create-email-' + field.id;
				forceRemoveEditor( editorId );
			}
		} );
	};

	/**
	 * Handle modal close.
	 */
	var handleClose = function () {
		cleanupEditors();
		resetForm();
		onClose();
	};

	/**
	 * Handle save.
	 */
	var handleSave = function () {
		setError( '' );
		setIsSaving( true );

		// Build payload from registered fields (same pattern as GroupEditModal).
		var payload = {
			email_id: emailId || 0,
			custom_meta: customMeta,
		};

		registeredFields.forEach( function ( field ) {
			if ( field.readonly ) {
				return;
			}

			var val = registeredValues[ field.id ];

			// For richtext fields, pull latest content from TinyMCE.
			if ( 'richtext' === field.type && window.tinymce ) {
				var editorId = emailId
					? 'bb-admin-edit-' + field.id + '-' + emailId
					: 'bb-admin-create-email-' + field.id;
				var editorInstance = window.tinymce.get( editorId );
				if ( editorInstance ) {
					val = editorInstance.getContent();
				}
			}

			payload[ 'registered_field_' + field.id ] = null !== val && undefined !== val ? val : '';
		} );

		saveEmailTemplate( payload ).then( function ( response ) {
			if ( ! isMountedRef.current ) {
				return;
			}
			setIsSaving( false );
			if ( response.success ) {
				cleanupEditors();
				resetForm();
				if ( onSaved ) {
					onSaved( response.data );
				}
			} else {
				setError( ( response.data && response.data.message ) || __( 'Failed to save template.', 'buddyboss' ) );
			}
		} ).catch( function () {
			if ( ! isMountedRef.current ) {
				return;
			}
			setIsSaving( false );
			setError( __( 'An error occurred. Please try again.', 'buddyboss' ) );
		} );
	};

	// Custom fields handlers.
	var handleAddCustomField = function () {
		setCustomMeta( function ( prev ) {
			return prev.concat( [ { key: '', value: '' } ] );
		} );
	};

	var handleUpdateCustomField = function ( index, field, val ) {
		setCustomMeta( function ( prev ) {
			var updated = prev.slice();
			updated[ index ] = Object.assign( {}, updated[ index ] );
			updated[ index ][ field ] = val;
			return updated;
		} );
	};

	var handleRemoveCustomField = function ( index ) {
		setCustomMeta( function ( prev ) {
			return prev.filter( function ( _, i ) {
				return i !== index;
			} );
		} );
	};

	// Check conditional field visibility.
	var isFieldVisible = function ( field ) {
		if ( ! field.visible ) {
			return false;
		}
		if ( field.conditional ) {
			var depValue = registeredValues[ field.conditional.field ];
			return depValue === field.conditional.value;
		}
		return true;
	};

	var modalTitle = emailId
		? __( 'Edit Email Template', 'buddyboss' )
		: __( 'Add New Email', 'buddyboss' );

	var itemId = emailId || 'new';

	return (
		<Modal
			title={ modalTitle }
			onRequestClose={ handleClose }
			className="bb-admin-settings-modal bb-email-template-modal"
			shouldCloseOnClickOutside={ false }
		>
			<div className="bb-admin-settings-modal__body bb-email-template-modal__body">
				{ isLoading ? (
					<div className="bb-email-template-modal__loading">
						<Spinner />
					</div>
				) : (
					<>
						{ error && (
							<p className="bb-admin-settings-modal__error">{ error }</p>
						) }

						{/* Details tab fields — rendered via RegisteredMetaField */}
						{ detailsFields.map( function ( field ) {
							if ( ! isFieldVisible( field ) ) {
								return null;
							}
							return (
								<div key={ field.id } className="bb-email-template-modal__field">
									<RegisteredMetaField
										field={ field }
										value={ registeredValues[ field.id ] }
										onChange={ function ( val ) {
											handleRegisteredFieldChange( field.id, val );
										} }
										itemId={ itemId }
									/>
								</div>
							);
						} ) }

						{/* Custom Fields repeater — ABOVE Situation per Figma */}
						<div className="bb-email-template-modal__field bb-email-template-modal__custom-fields">
							<label className="bb-email-template-modal__field-label">
								{ __( 'Custom Fields', 'buddyboss' ) }
							</label>
							<div className="bb-email-template-modal__custom-fields-list">
								{ customMeta.map( function ( meta, index ) {
									return (
										<div key={ index } className="bb-email-template-modal__custom-field-row">
											<div className={ 'bb-email-template-modal__meta-key-autocomplete' + ( openDropdown === index ? ' is-open' : '' ) }>
												<TextControl
													label={ __( 'Name', 'buddyboss' ) }
													value={ meta.key }
													onChange={ function ( val ) {
														handleUpdateCustomField( index, 'key', val );
														if ( openDropdown !== index ) {
															setOpenDropdown( index );
														}
													} }
													onFocus={ function () {
														setOpenDropdown( index );
													} }
													placeholder={ __( 'Select or type name', 'buddyboss' ) }
													autoComplete="off"
													__nextHasNoMarginBottom
												/>
												{ openDropdown === index && (
													<Popover
														className="bb-email-template-modal__meta-key-popover"
														placement="bottom-start"
														noArrow
														focusOnMount={ false }
														onClose={ function () {
															setOpenDropdown( -1 );
														} }
													>
														<div className="bb-email-template-modal__meta-key-dropdown">
															<div className="bb-email-template-modal__meta-key-search">
																<input
																	type="text"
																	placeholder={ __( 'Search', 'buddyboss' ) }
																	onMouseDown={ function ( e ) {
																		e.stopPropagation();
																	} }
																	onInput={ function ( e ) {
																		var searchVal = e.target.value.toLowerCase();
																		var list = e.target.closest( '.bb-email-template-modal__meta-key-dropdown' ).querySelector( '.bb-email-template-modal__meta-key-list' );
																		if ( list ) {
																			var items = list.querySelectorAll( '.bb-email-template-modal__meta-key-option' );
																			items.forEach( function ( item ) {
																				item.style.display = item.textContent.toLowerCase().indexOf( searchVal ) !== -1 ? '' : 'none';
																			} );
																		}
																	} }
																/>
															</div>
															<div className="bb-email-template-modal__meta-key-list">
																{ metaKeys.length > 0 ? metaKeys.filter( function ( mk ) {
																	if ( ! meta.key ) {
																		return true;
																	}
																	return mk.toLowerCase().indexOf( meta.key.toLowerCase() ) !== -1;
																} ).map( function ( mk ) {
																	return (
																		<button
																			key={ mk }
																			type="button"
																			className="bb-email-template-modal__meta-key-option"
																			onMouseDown={ function ( e ) {
																				e.preventDefault();
																				e.stopPropagation();
																				handleUpdateCustomField( index, 'key', mk );
																				setOpenDropdown( -1 );
																			} }
																		>
																			{ mk }
																		</button>
																	);
																} ) : (
																	<div className="bb-email-template-modal__meta-key-empty">
																		{ __( 'No suggestions found.', 'buddyboss' ) }
																	</div>
																) }
															</div>
														</div>
													</Popover>
												) }
											</div>
											<TextControl
												label={ __( 'Value', 'buddyboss' ) }
												value={ meta.value }
												onChange={ function ( val ) {
													handleUpdateCustomField( index, 'value', val );
												} }
												placeholder={ __( 'Enter value', 'buddyboss' ) }
												__nextHasNoMarginBottom
											/>
											<Button
												icon={ <i className="bb-icons-rl bb-icons-rl-trash" /> }
												onClick={ function () {
													handleRemoveCustomField( index );
												} }
												isDestructive
												className="bb-email-template-modal__custom-field-delete"
											/>
										</div>
									);
								} ) }
							</div>
							<Button
								onClick={ handleAddCustomField }
								className="bb-email-template-modal__add-custom-field"
							>
								{ __( '+ Add Custom Field', 'buddyboss' ) }
							</Button>
							<p className="bb-email-template-modal__field-help">
								{ __( 'Custom fields can be used to add extra metadata to a post that you can use in your theme.', 'buddyboss' ) }
							</p>
						</div>

						{/* Situation — Tabbed checkboxes (acting as radio, single-select) */}
						{ situations && Object.keys( situations ).length > 0 && (
							<div className="bb-email-template-modal__field bb-email-template-modal__situation">
								<label className="bb-email-template-modal__field-label">
									{ __( 'Situation', 'buddyboss' ) }
								</label>
								<TabPanel
									className="bb-email-template-modal__situation-tabs"
									tabs={ Object.keys( situations ).map( function ( catKey ) {
										return {
											name: catKey,
											title: situations[ catKey ].label,
											className: 'bb-email-template-modal__situation-tab',
										};
									} ) }
								>
									{ function ( tab ) {
										var catTerms = situations[ tab.name ] ? situations[ tab.name ].terms : [];
										return (
											<div className="bb-email-template-modal__situation-list">
												{ catTerms.map( function ( term ) {
													var isSelected = registeredValues.email_type === term.slug;
													return (
														<label key={ term.slug } className={ 'bb-email-template-modal__situation-item' + ( isSelected ? ' bb-email-template-modal__situation-item--selected' : '' ) }>
															<input
																type="checkbox"
																checked={ isSelected }
																onChange={ function () {
																	handleRegisteredFieldChange( 'email_type', isSelected ? '' : term.slug );
																} }
															/>
															<span>{ decodeEntities( term.description || term.slug ) }</span>
														</label>
													);
												} ) }
												{ 0 === catTerms.length && (
													<p className="bb-email-template-modal__situation-empty">
														{ __( 'No situations in this category.', 'buddyboss' ) }
													</p>
												) }
											</div>
										);
									} }
								</TabPanel>
								<p className="bb-email-template-modal__field-help">
									{ __( 'Choose when this email will be sent.', 'buddyboss' ) }
								</p>
							</div>
						) }

						{/* Publish tab fields — Status, Visibility, Password, Publish, Schedule Date */}
						{ groupedPublishFields.length > 0 && (
							<div className="bb-email-template-modal__field bb-email-template-modal__publish-fields">
								{ groupedPublishFields.map( function ( item, idx ) {
									if ( 'row' === item.type ) {
										// Check visibility for both fields in the row.
										var visibleFields = item.fields.filter( isFieldVisible );
										if ( 0 === visibleFields.length ) {
											return null;
										}
										return (
											<div key={ 'pub-row-' + idx } className="bb-admin-meta-field__row bb-email-template-modal__publish-row">
												{ visibleFields.map( function ( field ) {
													return (
														<RegisteredMetaField
															key={ field.id }
															field={ field }
															value={ registeredValues[ field.id ] }
															onChange={ function ( val ) {
																handleRegisteredFieldChange( field.id, val );
															} }
															itemId={ itemId }
														/>
													);
												} ) }
											</div>
										);
									}

									if ( ! isFieldVisible( item.field ) ) {
										return null;
									}
									return (
										<RegisteredMetaField
											key={ item.field.id }
											field={ item.field }
											value={ registeredValues[ item.field.id ] }
											onChange={ function ( val ) {
												handleRegisteredFieldChange( item.field.id, val );
											} }
											itemId={ itemId }
										/>
									);
								} ) }
							</div>
						) }
					</>
				) }
			</div>

			<div className="bb-admin-settings-modal__footer bb-email-template-modal__footer">
				<Button
					variant="secondary"
					onClick={ handleClose }
					disabled={ isSaving }
				>
					{ __( 'Cancel', 'buddyboss' ) }
				</Button>
				<Button
					variant="primary"
					onClick={ handleSave }
					isBusy={ isSaving }
					disabled={ isSaving || isLoading }
				>
					{ isSaving ? __( 'Saving...', 'buddyboss' ) : __( 'Save', 'buddyboss' ) }
				</Button>
			</div>
		</Modal>
	);
}

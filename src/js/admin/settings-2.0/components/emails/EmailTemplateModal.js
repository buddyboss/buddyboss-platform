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

import { useState, useEffect, useRef, useMemo } from '@wordpress/element';
import {
	Modal,
	Button,
	TextControl,
	Spinner,
	Popover,
} from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { decodeEntities } from '@wordpress/html-entities';
import { getEmailTemplate, saveEmailTemplate, getEmailSituations, getEmailMetaKeys } from '../../utils/ajax';
import { groupFieldsWithLayout, buildRegisteredFieldPayload, getVisibleFields, needsSeparator } from '../../utils/format';
import { RegisteredMetaField } from '../common/RegisteredMetaField';
import { forceRemoveEditor } from '../common/RichTextEditor';

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
	var registeredFieldsRef = useRef( registeredFields );
	registeredFieldsRef.current = registeredFields;
	var emailIdRef = useRef( emailId );
	emailIdRef.current = emailId;
	var situationsCacheRef = useRef( null );

	useEffect( function () {
		isMountedRef.current = true;
		return function () {
			isMountedRef.current = false;
			// Clean up TinyMCE editors on unmount (defensive — normally handled by handleClose).
			registeredFieldsRef.current.forEach( function ( field ) {
				if ( 'richtext' === field.type ) {
					forceRemoveEditor( 'bb-admin-edit-' + field.id + '-' + ( emailIdRef.current || 0 ) );
				}
			} );
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

	// Separate registered fields by context (same pattern as ActivityEditModal).
	// Filter out email_type (rendered as custom Situation TabPanel).
	// 'normal' (default) fields render first, 'after' fields render after Custom Fields + Situation.
	var normalFields = useMemo( function () {
		var fields = [];
		registeredFields.forEach( function ( f ) {
			if ( 'email_type' !== f.id && 'after' !== f.context ) {
				fields.push( f );
			}
		} );
		return fields;
	}, [ registeredFields ] );

	var afterFields = useMemo( function () {
		var fields = [];
		registeredFields.forEach( function ( f ) {
			if ( 'after' === f.context ) {
				fields.push( f );
			}
		} );
		return fields;
	}, [ registeredFields ] );

	var visibleNormalFields = getVisibleFields( normalFields, registeredValues );
	var groupedNormalFields = groupFieldsWithLayout( visibleNormalFields );

	var visibleAfterFields = getVisibleFields( afterFields, registeredValues );
	var groupedAfterFields = groupFieldsWithLayout( visibleAfterFields );

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
				var editorId = 'bb-admin-edit-' + field.id + '-' + ( emailId || 0 );
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
		if ( isSaving ) {
			return;
		}
		setError( '' );
		setIsSaving( true );

		// Build payload using shared utility (same as ForumCreateModal).
		var itemId = emailId || 0;
		var payload = buildRegisteredFieldPayload( registeredFields, registeredValues, itemId );
		payload.email_id    = itemId;
		payload.custom_meta = customMeta;

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

	var modalTitle = emailId
		? __( 'Edit Email Template', 'buddyboss' )
		: __( 'Add New Email', 'buddyboss' );

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

						{/* Normal fields (Title, Description, Plain Text) */}
						{ groupedNormalFields.map( function ( item, idx ) {
							var hasSeparator = needsSeparator( item, groupedNormalFields[ idx + 1 ] );

							if ( 'row' === item.type ) {
								return (
									<div key={ 'det-row-' + idx } className={ 'bb-admin-meta-field__row bb-admin-settings-modal__row' + ( hasSeparator ? ' bb-admin-settings-modal__row--separator' : '' ) }>
										{ item.fields.map( function ( field ) {
											return (
												<RegisteredMetaField
													key={ field.id }
													field={ field }
													value={ registeredValues[ field.id ] }
													onChange={ function ( val ) {
														handleRegisteredFieldChange( field.id, val );
													} }
													itemId={ emailId || 0 }
												/>
											);
										} ) }
									</div>
								);
							}
							return (
								<div key={ item.field.id } className={ 'components-base-control' + ( hasSeparator ? ' bb-admin-settings-modal__row--separator' : '' ) }>
									<RegisteredMetaField
										field={ item.field }
										value={ registeredValues[ item.field.id ] }
										onChange={ function ( val ) {
											handleRegisteredFieldChange( item.field.id, val );
										} }
										itemId={ emailId || 0 }
									/>
								</div>
							);
						} ) }

						{/* Custom Fields repeater — between details and publish per Figma */}
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
								<Button
									onClick={ handleAddCustomField }
									className="bb-email-template-modal__add-custom-field"
									variant="tertiary"
								>
									<i className="bb-icons-rl-plus"></i>
									{ __( 'Add Custom Field', 'buddyboss' ) }
								</Button>
								<p className="bb-email-template-modal__field-help">
									{ __( 'Custom fields can be used to add extra metadata to a post that you can', 'buddyboss' ) }
									{ ' ' }
									<a
										href="https://wordpress.org/documentation/article/assign-custom-fields/"
										target="_blank"
										rel="noopener noreferrer"
									>
										{ __( 'use in your theme', 'buddyboss' ) }
									</a>
									{ '.' }
								</p>
							</div>
						</div>

						{/* Situation — Grouped scrollable list (single-select) */}
						{ situations && Object.keys( situations ).length > 0 && (
							<div className="bb-email-template-modal__field bb-email-template-modal__situation">
								<label className="bb-email-template-modal__field-label">
									{ __( 'Situation', 'buddyboss' ) }
								</label>
								<div className="bb-email-template-modal__situation-list">
									{ Object.keys( situations ).map( function ( catKey ) {
										var catData  = situations[ catKey ];
										var catTerms = catData.terms;
										if ( ! catTerms || 0 === catTerms.length ) {
											return null;
										}
										return (
											<div key={ catKey } className="bb-email-template-modal__situation-group">
												<span className="bb-email-template-modal__situation-group-label">
													{ decodeEntities( catData.label ) }
												</span>
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
											</div>
										);
									} ) }
								</div>
								<p className="bb-email-template-modal__field-help">
									{ __( 'Choose when this email will be sent.', 'buddyboss' ) }
								</p>
							</div>
						) }

						{/* After fields (Status, Visibility, Password, Publish, Date, Time) */}
						{ groupedAfterFields.length > 0 && (
							<div className="bb-email-template-modal__publish-fields">
								{ groupedAfterFields.map( function ( item, idx ) {
									var hasSeparator = needsSeparator( item, groupedAfterFields[ idx + 1 ] );

									if ( 'row' === item.type ) {
										return (
											<div key={ 'pub-row-' + idx } className={ 'bb-admin-meta-field__row bb-admin-settings-modal__row bb-admin-settings-modal__row--separator'}>
												{ item.fields.map( function ( field ) {
													return (
														<RegisteredMetaField
															key={ field.id }
															field={ field }
															value={ registeredValues[ field.id ] }
															onChange={ function ( val ) {
																handleRegisteredFieldChange( field.id, val );
															} }
															itemId={ emailId || 0 }
														/>
													);
												} ) }
											</div>
										);
									}
									return (
										<div key={ item.field.id } className={ 'components-base-control' + ( hasSeparator ? ' bb-admin-settings-modal__row--separator' : '' ) }>
											<RegisteredMetaField
												field={ item.field }
												value={ registeredValues[ item.field.id ] }
												onChange={ function ( val ) {
													handleRegisteredFieldChange( item.field.id, val );
												} }
												itemId={ emailId || 0 }
											/>
										</div>
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

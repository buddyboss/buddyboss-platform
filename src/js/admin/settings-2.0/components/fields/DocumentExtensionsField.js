/**
 * BuddyBoss Admin Settings 2.0 - Document Extensions Field
 *
 * Renders a "Manage" button that opens a modal listing all document extensions
 * with checkboxes, file-type icons, descriptions, and three-dot menus.
 * From within that modal, an "Add Extension" button opens a nested modal
 * for adding custom extensions (with MIME Checker support).
 *
 * @package BuddyBoss\Core\Administration
 * @since BuddyBoss [BBVERSION]
 */

import { useState, useEffect, useRef, createPortal } from '@wordpress/element';
import { Modal, Button, TextControl, TextareaControl, DropdownMenu, CheckboxControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { useMimeChecker } from '../../utils/mimeChecker';
import { MimeCheckerPanel } from './MimeCheckerPanel';

/**
 * Whether ReadyLaunch mode is enabled.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @type {boolean}
 */
var isReadyLaunch = !! ( window.bbAdminData && window.bbAdminData.isReadyLaunch );

/**
 * Get the default icon class for an unknown file extension.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @return {string} Default icon CSS class.
 */
function getDefaultIconClass() {
	return isReadyLaunch ? 'bb-icons-rl bb-icons-rl-file' : 'bb-icon-l bb-icon-file';
}

/**
 * Map a file extension to an icon class name.
 *
 * Returns ReadyLaunch (`bb-icons-rl-*`) or legacy (`bb-icon-*`) icon classes
 * depending on whether ReadyLaunch mode is active.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param {string} extension File extension (e.g., '.pdf', '.doc').
 * @return {string} Icon CSS class.
 */
function getExtensionIconClass( extension ) {
	var ext = ( extension || '' ).replace( '.', '' ).toLowerCase();

	if ( isReadyLaunch ) {
		var rlMap = {
			pdf: 'bb-icons-rl-file-pdf',
			doc: 'bb-icons-rl-file-doc',
			docx: 'bb-icons-rl-file-doc',
			xls: 'bb-icons-rl-file-xls',
			xlsx: 'bb-icons-rl-file-xls',
			ppt: 'bb-icons-rl-file-ppt',
			pptx: 'bb-icons-rl-file-ppt',
			csv: 'bb-icons-rl-file-csv',
			css: 'bb-icons-rl-file-css',
			html: 'bb-icons-rl-file-html',
			htm: 'bb-icons-rl-file-html',
			jpg: 'bb-icons-rl-file-jpg',
			jpeg: 'bb-icons-rl-file-jpg',
			png: 'bb-icons-rl-file-png',
			gif: 'bb-icons-rl-file-image',
			svg: 'bb-icons-rl-file-svg',
			zip: 'bb-icons-rl-file-archive',
			rar: 'bb-icons-rl-file-archive',
			gz: 'bb-icons-rl-file-archive',
			tar: 'bb-icons-rl-file-archive',
			'7z': 'bb-icons-rl-file-archive',
			mp3: 'bb-icons-rl-file-audio',
			wav: 'bb-icons-rl-file-audio',
			mp4: 'bb-icons-rl-file-video',
			avi: 'bb-icons-rl-file-video',
			txt: 'bb-icons-rl-file-text',
			js: 'bb-icons-rl-file-code',
			json: 'bb-icons-rl-file-code',
			xml: 'bb-icons-rl-file-code',
			php: 'bb-icons-rl-file-code',
			py: 'bb-icons-rl-file-code',
			cpp: 'bb-icons-rl-file-cpp',
			c: 'bb-icons-rl-file-c',
		};

		return 'bb-icons-rl ' + ( rlMap[ ext ] || 'bb-icons-rl-file' );
	}

	var legacyMap = {
		pdf: 'bb-icon-file-pdf',
		doc: 'bb-icon-file-doc',
		docx: 'bb-icon-file-docx',
		xls: 'bb-icon-file-xlsx',
		xlsx: 'bb-icon-file-xlsx',
		ppt: 'bb-icon-file-pptx',
		pptx: 'bb-icon-file-pptx',
		csv: 'bb-icon-file-csv',
		css: 'bb-icon-file-css',
		html: 'bb-icon-file-html',
		htm: 'bb-icon-file-html',
		jpg: 'bb-icon-file-png',
		jpeg: 'bb-icon-file-png',
		png: 'bb-icon-file-png',
		gif: 'bb-icon-file-image',
		svg: 'bb-icon-file-svg',
		zip: 'bb-icon-file-zip',
		rar: 'bb-icon-file-zip',
		gz: 'bb-icon-file-zip',
		tar: 'bb-icon-file-tar',
		'7z': 'bb-icon-file-zip',
		mp3: 'bb-icon-file-mp3',
		wav: 'bb-icon-file-audio',
		mp4: 'bb-icon-file-video',
		avi: 'bb-icon-file-video',
		txt: 'bb-icon-file-txt',
		js: 'bb-icon-file-code',
		json: 'bb-icon-file-code',
		xml: 'bb-icon-file-code',
		php: 'bb-icon-file-code',
		py: 'bb-icon-file-code',
		cpp: 'bb-icon-file-code',
		c: 'bb-icon-file-code',
	};

	return 'bb-icon-l ' + ( legacyMap[ ext ] || 'bb-icon-file' );
}

/**
 * Document Extensions Field Component
 *
 * Shows a "Manage" button on the settings page. When clicked, opens a modal
 * listing all document extensions with checkboxes. An "Add Extension" button
 * inside the modal opens a nested "Add New Extension" modal with MIME checker.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param {Object}   props          Component props.
 * @param {Object}   props.field    Field definition with extension_data, manage_label, manage_icon.
 * @param {Object}   props.value    Current toggle values { bb_doc_0: 1, bb_doc_1: 0, ... }.
 * @param {Function} props.onChange Change handler (fieldName, newValue).
 * @param {boolean}  props.disabled Whether the field is disabled.
 *
 * @returns {JSX.Element} Document extensions manage button with modals.
 */
export function DocumentExtensionsField( { field, value, onChange, disabled } ) {
	// Normalize value to toggle format { key: 0|1 }.
	// After add/edit/remove, value may contain full extension objects.
	var managedValue = {};
	if ( typeof value === 'object' && value !== null ) {
		Object.keys( value ).forEach( function( key ) {
			var v = value[ key ];
			if ( typeof v === 'object' && v !== null && v.is_active !== undefined ) {
				managedValue[ key ] = v.is_active ? 1 : 0;
			} else {
				managedValue[ key ] = v ? 1 : 0;
			}
		} );
	}

	var [ isManageOpen, setIsManageOpen ] = useState( false );
	var [ localExtensionData, setLocalExtensionData ] = useState( function() {
		return field.extension_data || {};
	} );

	// Sync local extension data when prop changes (e.g., after settings reload).
	useEffect( function() {
		if ( field.extension_data ) {
			setLocalExtensionData( field.extension_data );
		}
	}, [ field.extension_data ] );

	// Local working copy of toggle values inside manage modal.
	var [ workingValues, setWorkingValues ] = useState( function() {
		return Object.assign( {}, managedValue );
	} );

	// Add extension modal state.
	var [ isAddOpen, setIsAddOpen ] = useState( false );
	var [ newExtension, setNewExtension ] = useState( '' );
	var [ newDescription, setNewDescription ] = useState( '' );
	var [ newMimeType, setNewMimeType ] = useState( '' );
	var [ newIcon, setNewIcon ] = useState( 'bb-icon-file' );
	var [ modalError, setModalError ] = useState( '' );

	// Edit extension popup state.
	var [ isEditOpen, setIsEditOpen ] = useState( false );
	var [ editingKey, setEditingKey ] = useState( null );
	var [ editExtension, setEditExtension ] = useState( '' );
	var [ editDescription, setEditDescription ] = useState( '' );
	var [ editMimeType, setEditMimeType ] = useState( '' );
	var [ editIcon, setEditIcon ] = useState( 'bb-icon-file' );

	// Icon options from PHP (via field registration).
	var iconOptions = field.icon_options || [];

	// Custom icon dropdown state (Add and Edit modals).
	var [ isIconDropdownOpen, setIsIconDropdownOpen ] = useState( false );
	var [ isEditIconDropdownOpen, setIsEditIconDropdownOpen ] = useState( false );
	var iconDropdownRef = useRef( null );
	var editIconDropdownRef = useRef( null );

	// MIME Checker (shared hook).
	var mimeChecker = useMimeChecker();

	// Track if there are unsaved changes.
	var [ hasChanges, setHasChanges ] = useState( false );

	// Close nested modals (Add/Edit) on Escape before the parent Manage modal.
	// The nested modals are custom portals (not WP Modal), so they don't have
	// built-in Escape handling. We intercept the keydown in the capture phase
	// to stop propagation before the parent WP Modal's handler fires.
	useEffect( function() {
		if ( ! isAddOpen && ! isEditOpen ) {
			return;
		}

		function handleEscapeCapture( e ) {
			if ( 'Escape' !== e.key ) {
				return;
			}

			e.stopPropagation();
			e.preventDefault();

			if ( isAddOpen ) {
				setIsAddOpen( false );
			} else if ( isEditOpen ) {
				setIsEditOpen( false );
				setEditingKey( null );
			}
		}

		document.addEventListener( 'keydown', handleEscapeCapture, true );
		return function() {
			document.removeEventListener( 'keydown', handleEscapeCapture, true );
		};
	}, [ isAddOpen, isEditOpen ] );

	// Close icon dropdown on outside click.
	useEffect( function() {
		function handleClickOutside( e ) {
			if ( iconDropdownRef.current && ! iconDropdownRef.current.contains( e.target ) ) {
				setIsIconDropdownOpen( false );
			}
			if ( editIconDropdownRef.current && ! editIconDropdownRef.current.contains( e.target ) ) {
				setIsEditIconDropdownOpen( false );
			}
		}

		document.addEventListener( 'mousedown', handleClickOutside );
		return function() {
			document.removeEventListener( 'mousedown', handleClickOutside );
		};
	}, [] );

	/**
	 * Sync working values when manage modal opens.
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	var handleOpenManage = function() {
		setWorkingValues( Object.assign( {}, managedValue ) );
		setHasChanges( false );
		setIsManageOpen( true );
	};

	/**
	 * Toggle a single extension checkbox in the working copy.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param {string}  key     Extension key (e.g., bb_doc_1).
	 * @param {boolean} checked New checked state.
	 */
	var handleCheckboxChange = function( key, checked ) {
		setWorkingValues( function( prev ) {
			var updated = Object.assign( {}, prev );
			updated[ key ] = checked ? 1 : 0;
			return updated;
		} );
		setHasChanges( true );
	};

	/**
	 * Save changes from manage modal and close.
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	var handleSaveManage = function() {
		onChange( field.name, workingValues );
		setIsManageOpen( false );
		setHasChanges( false );
	};

	/**
	 * Cancel manage modal without saving.
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	var handleCancelManage = function() {
		setIsManageOpen( false );
		setHasChanges( false );
	};

	/**
	 * Reset add extension modal state.
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	var resetAddState = function() {
		setNewExtension( '' );
		setNewDescription( '' );
		setNewMimeType( '' );
		setNewIcon( 'bb-icon-file' );
		setModalError( '' );
		setIsIconDropdownOpen( false );
		mimeChecker.resetMimeState();
		setIsAddOpen( false );
	};

	/**
	 * Copy the detected MIME type into the active field and close checker.
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	var handleUseMimeType = function() {
		if ( isEditOpen ) {
			setEditMimeType( mimeChecker.mimeCheckerResult );
		} else {
			setNewMimeType( mimeChecker.mimeCheckerResult );
		}
		mimeChecker.handleCloseMimeChecker();
	};

	/**
	 * Save a new custom extension.
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	var handleSaveExtension = function() {
		var extension = newExtension.trim();

		if ( ! extension ) {
			return;
		}

		// Ensure extension starts with a dot.
		if ( '.' !== extension.charAt( 0 ) ) {
			extension = '.' + extension;
		}

		// Check for duplicate extension.
		var isDuplicate = Object.keys( localExtensionData ).some( function( key ) {
			return localExtensionData[ key ].extension &&
				localExtensionData[ key ].extension.toLowerCase() === extension.toLowerCase();
		} );

		if ( isDuplicate ) {
			setModalError( __( 'This extension already exists.', 'buddyboss' ) );
			return;
		}

		// Generate next key based on existing keys.
		var maxIndex = 0;
		Object.keys( localExtensionData ).forEach( function( key ) {
			var match = key.match( /bb_doc_(\d+)/ );
			if ( match ) {
				var idx = parseInt( match[1], 10 );
				if ( idx >= maxIndex ) {
					maxIndex = idx + 1;
				}
			}
		} );

		var newKey = 'bb_doc_' + maxIndex;
		var description = newDescription.trim();
		var mimeType = newMimeType.trim();

		// MIME type is required.
		if ( ! mimeType ) {
			setModalError( __( 'MIME type is required.', 'buddyboss' ) );
			return;
		}

		// Build full extension data with the new entry.
		var fullData = {};
		Object.keys( localExtensionData ).forEach( function( key ) {
			var ext = localExtensionData[ key ];
			fullData[ key ] = {
				extension: ext.extension,
				mime_type: ext.mime_type,
				description: ext.description,
				is_default: ext.is_default,
				is_active: workingValues[ key ] !== undefined ? workingValues[ key ] : ext.is_active,
				icon: ext.icon || '',
			};
		} );

		// Add the new extension.
		fullData[ newKey ] = {
			extension: extension,
			mime_type: mimeType,
			description: description,
			is_default: 0,
			is_active: 1,
			icon: newIcon || 'bb-icon-file',
		};

		// Update local extension data.
		setLocalExtensionData( fullData );

		// Update working values with new extension enabled.
		setWorkingValues( function( prev ) {
			var updated = Object.assign( {}, prev );
			updated[ newKey ] = 1;
			return updated;
		} );

		// Send full data to save immediately (adding extensions saves right away).
		onChange( field.name, fullData );

		setHasChanges( true );
		resetAddState();
	};

	/**
	 * Remove a custom (non-default) extension.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param {string} key Extension key to remove.
	 */
	var handleRemoveExtension = function( key ) {
		var fullData = {};
		Object.keys( localExtensionData ).forEach( function( k ) {
			if ( k === key ) {
				return;
			}
			var ext = localExtensionData[ k ];
			fullData[ k ] = {
				extension: ext.extension,
				mime_type: ext.mime_type,
				description: ext.description,
				is_default: ext.is_default,
				is_active: workingValues[ k ] !== undefined ? workingValues[ k ] : ext.is_active,
				icon: ext.icon || '',
			};
		} );

		setLocalExtensionData( fullData );

		// Remove from working values.
		setWorkingValues( function( prev ) {
			var updated = Object.assign( {}, prev );
			delete updated[ key ];
			return updated;
		} );

		// Send full data to save immediately.
		onChange( field.name, fullData );
		setHasChanges( true );
	};

	/**
	 * Open edit mode for a custom extension.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param {string} key Extension key to edit.
	 */
	var handleStartEdit = function( key ) {
		var ext = localExtensionData[ key ];
		if ( ! ext ) {
			return;
		}

		// Determine the icon value: use stored icon, or infer from extension name.
		var iconValue = ext.icon || '';
		if ( ! iconValue && ext.extension && iconOptions.length > 0 ) {
			var rlIconClass = getExtensionIconClass( ext.extension );
			var matchedOption = iconOptions.find( function( opt ) {
				return opt.icon_class === rlIconClass;
			} );
			if ( matchedOption ) {
				iconValue = matchedOption.value;
			}
		}

		setEditingKey( key );
		setEditExtension( ext.extension || '' );
		setEditDescription( ext.description || '' );
		setEditMimeType( ext.mime_type || '' );
		setEditIcon( iconValue || 'bb-icon-file' );
		setIsEditOpen( true );
	};

	/**
	 * Save edited extension.
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	var handleSaveEdit = function() {
		if ( ! editingKey ) {
			return;
		}

		var extension = editExtension.trim();
		if ( ! extension ) {
			return;
		}

		if ( '.' !== extension.charAt( 0 ) ) {
			extension = '.' + extension;
		}

		var fullData = {};
		Object.keys( localExtensionData ).forEach( function( key ) {
			var ext = localExtensionData[ key ];
			if ( key === editingKey ) {
				fullData[ key ] = {
					extension: extension,
					mime_type: editMimeType.trim() || ext.mime_type,
					description: editDescription.trim(),
					is_default: ext.is_default,
					is_active: workingValues[ key ] !== undefined ? workingValues[ key ] : ext.is_active,
					icon: editIcon || 'bb-icon-file',
				};
			} else {
				fullData[ key ] = {
					extension: ext.extension,
					mime_type: ext.mime_type,
					description: ext.description,
					is_default: ext.is_default,
					is_active: workingValues[ key ] !== undefined ? workingValues[ key ] : ext.is_active,
					icon: ext.icon || '',
				};
			}
		} );

		setLocalExtensionData( fullData );
		onChange( field.name, fullData );
		setHasChanges( true );
		setIsEditOpen( false );
		setEditingKey( null );
		setEditExtension( '' );
		setEditDescription( '' );
		setEditMimeType( '' );
		setEditIcon( 'bb-icon-file' );
		setIsEditIconDropdownOpen( false );
	};

	/**
	 * Cancel editing.
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	var handleCancelEdit = function() {
		setIsEditOpen( false );
		setEditingKey( null );
		setEditExtension( '' );
		setEditDescription( '' );
		setEditMimeType( '' );
		setEditIcon( 'bb-icon-file' );
		setIsEditIconDropdownOpen( false );
	};

	// Build extension list for display: default extensions first, custom at bottom.
	var extensionKeys = Object.keys( localExtensionData );
	var defaultEntries = [];
	var customEntries = [];
	extensionKeys.forEach( function( key ) {
		var ext = localExtensionData[ key ];
		var entry = {
			key: key,
			extension: ext.extension,
			description: ext.description,
			mime_type: ext.mime_type || '',
			is_default: ext.is_default,
			icon: ext.icon,
		};
		if ( ext.is_default ) {
			defaultEntries.push( entry );
		} else {
			customEntries.push( entry );
		}
	} );
	var sortedEntries = defaultEntries.concat( customEntries );

	return (
		<div className="bb-doc-extensions">
			{ /* Manage Button */ }
			<button
				type="button"
				className="bb-admin-settings-field__manage-btn"
				onClick={ handleOpenManage }
				disabled={ disabled }
			>
				{ field.manage_icon && (
					<i className={ field.manage_icon } />
				) }
				<span>{ field.manage_label || __( 'Manage', 'buddyboss' ) }</span>
			</button>

			{ /* Manage File Extensions Modal */ }
			{ isManageOpen && (
				<Modal
					title={ __( 'Manage File Extensions', 'buddyboss' ) }
					onRequestClose={ handleCancelManage }
					className="bb-doc-extensions-modal bb-admin-settings-modal"
					overlayClassName="bb-extension-modal-overlay"
					shouldCloseOnClickOutside={ false }
				>
					<div className="bb-doc-extensions-modal__body">
						<div className="bb-doc-extensions-modal__list">
							{ sortedEntries.map( function( entry ) {
								var key = entry.key;
								var isChecked = workingValues[ key ] === 1 || ( workingValues[ key ] === undefined && localExtensionData[ key ] && localExtensionData[ key ].is_active );
								var isDefault = entry.is_default === 1;

								return (
									<div
										key={ key }
										className={ 'bb-doc-extensions-modal__item' + ( isChecked ? '' : ' bb-doc-extensions-modal__item--disabled' ) }
									>
										<div className="bb-doc-extensions-modal__checkbox">
											<CheckboxControl
												checked={ isChecked }
												onChange={ function( checked ) {
													handleCheckboxChange( key, checked );
												} }
											/>
										</div>
										<span className="bb-doc-extensions-modal__ext-name">
											{ entry.extension }
										</span>
										<i className={ 'bb-doc-extensions-modal__ext-icon ' + getExtensionIconClass( entry.extension ) } />
										<span className="bb-doc-extensions-modal__ext-desc">
											{ entry.description }
										</span>
										{ ! isDefault && (
											<div className="bb-doc-extensions-modal__ext-actions">
												<DropdownMenu
													className="bb-doc-extensions__dropdown"
													icon={ <i className="bb-icons-rl-dots-three" /> }
													label={ __( 'More options', 'buddyboss' ) }
													controls={ [
														{
															icon: <i className="bb-icons-rl-note-pencil"></i>,
															title: __( 'Edit', 'buddyboss' ),
															onClick: function() {
																handleStartEdit( key );
															},
														},
														{
															icon: <i className="bb-icons-rl-trash"></i>,
															title: __( 'Delete', 'buddyboss' ),
															onClick: function() {
																handleRemoveExtension( key );
															},
														},
													] }
												/>
											</div>
										) }
									</div>
								);
							} ) }
						</div>

						{ /* Add Extension Button */ }
						<div className="bb-doc-extensions-modal__add-btn-wrap">
							<button
								type="button"
								className="bb-doc-extensions-modal__add-btn"
								onClick={ function() {
									setIsAddOpen( true );
								} }
								disabled={ disabled }
							>
								<i className="bb-icons-rl bb-icons-rl-plus" />
								<span>{ __( 'Add Extension', 'buddyboss' ) }</span>
							</button>
						</div>
					</div>

					<div className="bb-admin-settings-modal__footer">
						<Button
							variant="secondary"
							onClick={ handleCancelManage }
						>
							{ __( 'Cancel', 'buddyboss' ) }
						</Button>
						<Button
							variant="primary"
							onClick={ handleSaveManage }
						>
							{ __( 'Save', 'buddyboss' ) }
						</Button>
					</div>
			</Modal>
		) }

		{ /* Add New Extension modal — portaled to body so it renders over the Manage modal */ }
		{ isManageOpen && isAddOpen && createPortal(
			<div className="bb-extension-modal-overlay bb-extension-modal-overlay--nested">
				<div className="bb-extension-modal--nested" role="dialog" aria-modal="true" aria-label={ __( 'Add New Extension', 'buddyboss' ) }>
					<div className="bb-extension-modal--nested__header">
						<h1>{ __( 'Add New Extension', 'buddyboss' ) }</h1>
						<button
							type="button"
							className="bb-extension-modal--nested__close"
							onClick={ resetAddState }
							aria-label={ __( 'Close', 'buddyboss' ) }
						>
							<i className="bb-icons-rl bb-icons-rl-x" />
						</button>
					</div>
					<div className="bb-extension-modal__body">
						{ modalError && (
							<div className="bb-extension-modal__error">
								{ modalError }
							</div>
						) }
						<div className="bb-extension-modal__field">
							<label className="bb-extension-modal__label">
								{ __( 'Extension', 'buddyboss' ) }
							</label>
							<TextControl
								value={ newExtension }
								onChange={ function( val ) {
									setNewExtension( val );
									setModalError( '' );
								} }
								placeholder={ __( 'Enter an extension (e.g., .extension)', 'buddyboss' ) }
								__nextHasNoMarginBottom
							/>
						</div>
						<div className="bb-extension-modal__field">
							<label className="bb-extension-modal__label">
								{ __( 'Description', 'buddyboss' ) }
							</label>
							<TextareaControl
								value={ newDescription }
								onChange={ setNewDescription }
								placeholder={ __( 'Enter a short description', 'buddyboss' ) }
								__nextHasNoMarginBottom
							/>
						</div>
						<div className="bb-extension-modal__field">
							<label className="bb-extension-modal__label">
								{ __( 'Icon', 'buddyboss' ) }
							</label>
							<div className="bb-extension-modal__icon-select" ref={ iconDropdownRef }>
								<div
									className={ "bb-extension-modal__icon-dropdown" + ( isIconDropdownOpen ? " is-open" : "" ) }
									onClick={ function() {
										setIsIconDropdownOpen( ! isIconDropdownOpen );
									} }
									role="button"
									tabIndex={ 0 }
									onKeyDown={ function( e ) {
										if ( 'Enter' === e.key || ' ' === e.key ) {
											e.preventDefault();
											setIsIconDropdownOpen( ! isIconDropdownOpen );
										}
									} }
								>
									<i className={
										( iconOptions.find( function( o ) { return o.value === newIcon; } ) || {} ).icon_class || getDefaultIconClass()
									} />
									<span className="bb-extension-modal__icon-dropdown-label">
										{ ( iconOptions.find( function( o ) { return o.value === newIcon; } ) || {} ).label || __( 'Default', 'buddyboss' ) }
									</span>
									<i className="bb-icons-rl bb-icons-rl-caret-down bb-extension-modal__icon-dropdown-chevron" />
								</div>
								{ isIconDropdownOpen && (
									<div className="bb-extension-modal__icon-dropdown-list">
										{ iconOptions.map( function( opt ) {
											return (
												<div
													key={ opt.value }
													className={ "bb-extension-modal__icon-dropdown-item" + ( opt.value === newIcon ? " is-selected" : "" ) }
													onClick={ function() {
														setNewIcon( opt.value );
														setIsIconDropdownOpen( false );
													} }
												>
													<i className={ opt.icon_class } />
													<span>{ opt.label }</span>
												</div>
											);
										} ) }
									</div>
								) }
							</div>
						</div>
						<div className="bb-extension-modal__field">
							<label className="bb-extension-modal__label">
								{ __( 'MIME Type', 'buddyboss' ) }
								<span className="bb-extension-modal__required">*</span>
							</label>
							<div className="bb-extension-modal__mime-row">
								<TextControl
									value={ newMimeType }
									onChange={ function( val ) {
										setNewMimeType( val );
										setModalError( '' );
									} }
									placeholder={ __( 'Enter MIME type', 'buddyboss' ) }
									__nextHasNoMarginBottom
								/>
								<Button
									variant="tertiary"
									className="bb-extension-modal__mime-checker-toggle"
									onClick={ function() {
										mimeChecker.setIsMimeCheckerOpen( ! mimeChecker.isMimeCheckerOpen );
										mimeChecker.setMimeCheckerResult( '' );
									} }
								>
									{ __( 'MIME Checker', 'buddyboss' ) }
								</Button>
							</div>
							{ ! mimeChecker.isMimeCheckerOpen && ! newMimeType.trim() && (
								<p className="bb-extension-modal__field-hint">
									{ __( 'Not sure? Click "MIME Checker" to detect the correct type from a sample file.', 'buddyboss' ) }
								</p>
							) }
						</div>

						{ mimeChecker.isMimeCheckerOpen && (
							<MimeCheckerPanel
								mimeChecker={ mimeChecker }
								onUseMimeType={ handleUseMimeType }
							/>
						) }
					</div>
					<div className="bb-admin-settings-modal__footer bb-extension-modal__footer">
						<Button
							variant="secondary"
							onClick={ resetAddState }
						>
							{ __( 'Cancel', 'buddyboss' ) }
						</Button>
						<Button
							variant="primary"
							onClick={ handleSaveExtension }
							disabled={ ! newExtension.trim() || ! newMimeType.trim() }
						>
							{ __( 'Save', 'buddyboss' ) }
						</Button>
					</div>
				</div>
			</div>,
			document.body
		) }

		{ /* Edit Extension modal — portaled to body so it renders over the Manage modal */ }
		{ isManageOpen && isEditOpen && editingKey && createPortal(
			<div className="bb-extension-modal-overlay bb-extension-modal-overlay--nested">
				<div className="bb-extension-modal--nested" role="dialog" aria-modal="true" aria-label={ __( 'Edit Extension', 'buddyboss' ) }>
					<div className="bb-extension-modal--nested__header">
						<h1>{ __( 'Edit Extension', 'buddyboss' ) }</h1>
						<button
							type="button"
							className="bb-extension-modal--nested__close"
							onClick={ handleCancelEdit }
							aria-label={ __( 'Close', 'buddyboss' ) }
						>
							<i className="bb-icons-rl bb-icons-rl-x" />
						</button>
					</div>
					<div className="bb-extension-modal__body">
						<div className="bb-extension-modal__field">
							<label className="bb-extension-modal__label">
								{ __( 'Extension', 'buddyboss' ) }
							</label>
							<TextControl
								value={ editExtension }
								onChange={ setEditExtension }
								__nextHasNoMarginBottom
							/>
						</div>
						<div className="bb-extension-modal__field">
							<label className="bb-extension-modal__label">
								{ __( 'Description', 'buddyboss' ) }
							</label>
							<TextControl
								value={ editDescription }
								onChange={ setEditDescription }
								__nextHasNoMarginBottom
							/>
						</div>
						<div className="bb-extension-modal__field">
							<label className="bb-extension-modal__label">
								{ __( 'Icon', 'buddyboss' ) }
							</label>
							<div className="bb-extension-modal__icon-select" ref={ editIconDropdownRef }>
								<div
									className={ "bb-extension-modal__icon-dropdown" + ( isEditIconDropdownOpen ? " is-open" : "" ) }
									onClick={ function() {
										setIsEditIconDropdownOpen( ! isEditIconDropdownOpen );
									} }
									role="button"
									tabIndex={ 0 }
									onKeyDown={ function( e ) {
										if ( 'Enter' === e.key || ' ' === e.key ) {
											e.preventDefault();
											setIsEditIconDropdownOpen( ! isEditIconDropdownOpen );
										}
									} }
								>
									<i className={
										( iconOptions.find( function( o ) { return o.value === editIcon; } ) || {} ).icon_class || getDefaultIconClass()
									} />
									<span className="bb-extension-modal__icon-dropdown-label">
										{ ( iconOptions.find( function( o ) { return o.value === editIcon; } ) || {} ).label || __( 'Default', 'buddyboss' ) }
									</span>
									<i className="bb-icons-rl bb-icons-rl-caret-down bb-extension-modal__icon-dropdown-chevron" />
								</div>
								{ isEditIconDropdownOpen && (
									<div className="bb-extension-modal__icon-dropdown-list">
										{ iconOptions.map( function( opt ) {
											return (
												<div
													key={ opt.value }
													className={ "bb-extension-modal__icon-dropdown-item" + ( opt.value === editIcon ? " is-selected" : "" ) }
													onClick={ function() {
														setEditIcon( opt.value );
														setIsEditIconDropdownOpen( false );
													} }
												>
													<i className={ opt.icon_class } />
													<span>{ opt.label }</span>
												</div>
											);
										} ) }
									</div>
								) }
							</div>
						</div>
						<div className="bb-extension-modal__field">
							<label className="bb-extension-modal__label">
								{ __( 'MIME Type', 'buddyboss' ) }
								<span className="bb-extension-modal__required">*</span>
							</label>
							<div className="bb-extension-modal__mime-row">
								<TextControl
									value={ editMimeType }
									onChange={ setEditMimeType }
									placeholder={ __( 'Enter MIME type', 'buddyboss' ) }
									__nextHasNoMarginBottom
								/>
								<Button
									variant="tertiary"
									className="bb-extension-modal__mime-checker-toggle"
									onClick={ function() {
										mimeChecker.setIsMimeCheckerOpen( ! mimeChecker.isMimeCheckerOpen );
										mimeChecker.setMimeCheckerResult( '' );
									} }
								>
									{ __( 'MIME Checker', 'buddyboss' ) }
								</Button>
							</div>
							{ ! mimeChecker.isMimeCheckerOpen && ! editMimeType.trim() && (
								<p className="bb-extension-modal__field-hint">
									{ __( 'Not sure? Click "MIME Checker" to detect the correct type from a sample file.', 'buddyboss' ) }
								</p>
							) }
						</div>

						{ mimeChecker.isMimeCheckerOpen && (
							<MimeCheckerPanel
								mimeChecker={ mimeChecker }
								onUseMimeType={ function() {
									setEditMimeType( mimeChecker.mimeCheckerResult );
									mimeChecker.handleCloseMimeChecker();
								} }
							/>
						) }
					</div>
					<div className="bb-admin-settings-modal__footer bb-extension-modal__footer">
						<Button
							variant="secondary"
							onClick={ handleCancelEdit }
						>
							{ __( 'Cancel', 'buddyboss' ) }
						</Button>
						<Button
							variant="primary"
							onClick={ handleSaveEdit }
							disabled={ ! editExtension.trim() || ! editMimeType.trim() }
						>
							{ __( 'Save', 'buddyboss' ) }
						</Button>
					</div>
				</div>
			</div>,
			document.body
		) }
	</div>
	);
}

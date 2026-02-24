/**
 * BuddyBoss Admin Settings 2.0 - Extension List Field
 *
 * Renders a toggle list of file extensions with an "Add Extension" button
 * and modal for adding custom extensions.
 *
 * @package BuddyBoss\Core\Administration
 * @since BuddyBoss [BBVERSION]
 */

import { useState, useRef } from '@wordpress/element';
import { ToggleControl, Modal, Button, TextControl, TextareaControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

/**
 * Extension List Field Component
 *
 * Manages a list of file extensions with toggle switches for enabling/disabling,
 * an "Add Extension" button for adding custom extensions via a modal, and
 * remove buttons for non-default (custom) extensions.
 *
 * The modal includes 3 fields matching the legacy admin: Extension, Description,
 * and MIME Type with a "MIME Checker" that uploads a sample file to detect its
 * real MIME type via PHP's finfo.
 *
 * @param {Object}   props                    Component props.
 * @param {Object}   props.field              Field definition with options, extension_data, allow_add.
 * @param {Object}   props.value              Current toggle values { bb_vid_0: 1, bb_vid_1: 0, ... }.
 * @param {Function} props.onChange            Change handler (fieldName, newValue).
 * @param {boolean}  props.disabled            Whether the field is disabled.
 * @param {string}   props.sanitizedDescription Pre-sanitized HTML description.
 *
 * @returns {JSX.Element} Extension list with optional add modal.
 */
export function ExtensionListField( { field, value, onChange, disabled, sanitizedDescription } ) {
	const [ isModalOpen, setIsModalOpen ] = useState( false );
	const [ newExtension, setNewExtension ] = useState( '' );
	const [ newDescription, setNewDescription ] = useState( '' );
	const [ newMimeType, setNewMimeType ] = useState( '' );

	// MIME Checker state.
	const [ isMimeCheckerOpen, setIsMimeCheckerOpen ] = useState( false );
	const [ mimeCheckerResult, setMimeCheckerResult ] = useState( '' );
	const [ isMimeChecking, setIsMimeChecking ] = useState( false );
	const fileInputRef = useRef( null );

	// Track local extension data so additions/removals render immediately.
	const [ localExtensionData, setLocalExtensionData ] = useState( function() {
		return field.extension_data || {};
	} );

	// Track local options list derived from extension data.
	const [ localOptions, setLocalOptions ] = useState( function() {
		return field.options || [];
	} );

	const listValue = typeof value === 'object' && value !== null ? value : {};

	/**
	 * Handle toggle change for an extension.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param {string}  optionValue The extension key (e.g., bb_vid_0).
	 * @param {boolean} checked     Whether the toggle is on.
	 */
	var handleToggleChange = function( optionValue, checked ) {
		var newValue = Object.assign( {}, listValue );
		newValue[ optionValue ] = checked ? 1 : 0;
		onChange( field.name, newValue );
	};

	/**
	 * Handle saving a new custom extension.
	 *
	 * Generates a new key, builds the full extension data structure,
	 * updates local state for immediate UI update, and triggers a save.
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

		// Generate next key based on existing keys.
		var maxIndex = 0;
		Object.keys( localExtensionData ).forEach( function( key ) {
			var match = key.match( /bb_vid_(\d+)/ );
			if ( match ) {
				var idx = parseInt( match[1], 10 );
				if ( idx >= maxIndex ) {
					maxIndex = idx + 1;
				}
			}
		} );

		var newKey = 'bb_vid_' + maxIndex;
		var description = newDescription.trim();
		var mimeType = newMimeType.trim();

		// Fallback MIME type if not provided.
		if ( ! mimeType ) {
			mimeType = 'video/' + extension.replace( '.', '' );
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
				is_active: listValue[ key ] !== undefined ? listValue[ key ] : ext.is_active,
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
			icon: '',
		};

		// Update local extension data for immediate rendering.
		setLocalExtensionData( fullData );

		// Update local options list.
		var newOption = {
			label: description ? extension + ' (' + description + ')' : extension,
			value: newKey,
			is_default: 0,
		};
		setLocalOptions( function( prev ) {
			return prev.concat( [ newOption ] );
		} );

		// Send the full data structure to save.
		onChange( field.name, fullData );

		// Reset modal state and close.
		resetModalState();
	};

	/**
	 * Handle removing a custom (non-default) extension.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param {string} optionValue The extension key to remove.
	 */
	var handleRemoveExtension = function( optionValue ) {
		// Build full data without the removed extension.
		var fullData = {};
		Object.keys( localExtensionData ).forEach( function( key ) {
			if ( key === optionValue ) {
				return;
			}
			var ext = localExtensionData[ key ];
			fullData[ key ] = {
				extension: ext.extension,
				mime_type: ext.mime_type,
				description: ext.description,
				is_default: ext.is_default,
				is_active: listValue[ key ] !== undefined ? listValue[ key ] : ext.is_active,
				icon: ext.icon || '',
			};
		} );

		// Update local state.
		setLocalExtensionData( fullData );
		setLocalOptions( function( prev ) {
			return prev.filter( function( opt ) {
				return opt.value !== optionValue;
			} );
		} );

		// Send the full data structure to save.
		onChange( field.name, fullData );
	};

	/**
	 * Reset all modal form state.
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	var resetModalState = function() {
		setNewExtension( '' );
		setNewDescription( '' );
		setNewMimeType( '' );
		setMimeCheckerResult( '' );
		setIsMimeCheckerOpen( false );
		setIsMimeChecking( false );
		setIsModalOpen( false );
		if ( fileInputRef.current ) {
			fileInputRef.current.value = '';
		}
	};

	/**
	 * Handle closing the modal and resetting form state.
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	var handleCloseModal = function() {
		resetModalState();
	};

	/**
	 * Upload a file to detect its MIME type via the server.
	 *
	 * Uses the existing wp_ajax_bp_document_check_file_mime_type AJAX handler
	 * which uses PHP's finfo_file() for accurate MIME type detection.
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	var handleGetMimeType = function() {
		if ( ! fileInputRef.current || ! fileInputRef.current.files || ! fileInputRef.current.files[0] ) {
			return;
		}

		var ajaxUrl = window.bbAdminData?.ajaxUrl || '/wp-admin/admin-ajax.php';
		var formData = new FormData();
		formData.append( 'file', fileInputRef.current.files[0] );
		formData.append( 'action', 'bp_document_check_file_mime_type' );

		setIsMimeChecking( true );
		setMimeCheckerResult( '' );

		fetch( ajaxUrl, {
			method: 'POST',
			credentials: 'same-origin',
			body: formData,
		} )
			.then( function( response ) {
				return response.json();
			} )
			.then( function( result ) {
				if ( result.success && result.data && result.data.type ) {
					setMimeCheckerResult( result.data.type );
				}
				setIsMimeChecking( false );
			} )
			.catch( function() {
				setIsMimeChecking( false );
			} );
	};

	/**
	 * Copy the detected MIME type into the MIME Type field and close checker.
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	var handleUseMimeType = function() {
		setNewMimeType( mimeCheckerResult );
		setMimeCheckerResult( '' );
		setIsMimeCheckerOpen( false );
		if ( fileInputRef.current ) {
			fileInputRef.current.value = '';
		}
	};

	/**
	 * Close the MIME checker panel and reset its state.
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	var handleCloseMimeChecker = function() {
		setMimeCheckerResult( '' );
		setIsMimeCheckerOpen( false );
		setIsMimeChecking( false );
		if ( fileInputRef.current ) {
			fileInputRef.current.value = '';
		}
	};

	return (
		<div className="bb-extension-list">
			<div className="bb-extension-list__items">
				{ localOptions.map( function( option ) {
					var isDefault = option.is_default === 1;

					return (
						<div key={ option.value } className="bb-extension-list__item">
							<div className="bb-extension-list__item-toggle">
								<ToggleControl
									label={ option.label }
									checked={ !! listValue[ option.value ] }
									onChange={ function( checked ) {
										handleToggleChange( option.value, checked );
									} }
									disabled={ disabled }
									__nextHasNoMarginBottom
								/>
							</div>
							{ ! isDefault && (
								<button
									type="button"
									className="bb-extension-list__item-remove"
									onClick={ function() {
										handleRemoveExtension( option.value );
									} }
									disabled={ disabled }
									aria-label={ __( 'Remove extension', 'buddyboss' ) }
								>
									<i className="bb-icons-rl bb-icons-rl-times" />
								</button>
							) }
						</div>
					);
				} ) }
			</div>

			{ sanitizedDescription && (
				<p
					className="bb-admin-settings-form__field-description"
					dangerouslySetInnerHTML={{ __html: sanitizedDescription }}
				/>
			) }

			{ field.allow_add && (
				<button
					type="button"
					className="bb-extension-list__add-btn"
					onClick={ function() {
						setIsModalOpen( true );
					} }
					disabled={ disabled }
				>
					<i className="bb-icons-rl bb-icons-rl-plus" />
					<span>{ field.add_button_label || __( 'Add Extension', 'buddyboss' ) }</span>
				</button>
			) }

			{ isModalOpen && (
				<Modal
					title={ __( 'Add New Extension', 'buddyboss' ) }
					onRequestClose={ handleCloseModal }
					className="bb-extension-modal"
					overlayClassName="bb-extension-modal-overlay"
					shouldCloseOnClickOutside={ false }
				>
					<div className="bb-extension-modal__body">
						<div className="bb-extension-modal__field">
							<label className="bb-extension-modal__label">
								{ __( 'Extension', 'buddyboss' ) }
							</label>
							<TextControl
								value={ newExtension }
								onChange={ setNewExtension }
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
								{ __( 'MIME Type', 'buddyboss' ) }
							</label>
							<div className="bb-extension-modal__mime-row">
								<TextControl
									value={ newMimeType }
									onChange={ setNewMimeType }
									placeholder={ __( 'e.g., video/mp4', 'buddyboss' ) }
									__nextHasNoMarginBottom
								/>
								<button
									type="button"
									className="bb-extension-modal__mime-checker-link"
									onClick={ function() {
										setIsMimeCheckerOpen( ! isMimeCheckerOpen );
										setMimeCheckerResult( '' );
									} }
								>
									{ __( 'MIME Checker', 'buddyboss' ) }
								</button>
							</div>
						</div>

						{ isMimeCheckerOpen && (
							<div className="bb-extension-modal__mime-checker">
								<div className="bb-extension-modal__mime-checker-header">
									<h4>{ __( 'Upload a file to check its MIME type', 'buddyboss' ) }</h4>
									<button
										type="button"
										className="bb-extension-modal__mime-checker-close"
										onClick={ handleCloseMimeChecker }
										aria-label={ __( 'Close MIME checker', 'buddyboss' ) }
									>
										<i className="bb-icons-rl bb-icons-rl-times" />
									</button>
								</div>
								<p className="bb-extension-modal__mime-checker-desc">
									{ __( 'Upload a sample file and click "Get MIME Type" to detect the correct MIME type for your extension.', 'buddyboss' ) }
								</p>
								<div className="bb-extension-modal__mime-checker-upload">
									<input
										type="file"
										ref={ fileInputRef }
										className="bb-extension-modal__mime-checker-file"
									/>
									<Button
										variant="secondary"
										onClick={ handleGetMimeType }
										disabled={ isMimeChecking }
										className="bb-extension-modal__mime-checker-btn"
									>
										{ isMimeChecking ? __( 'Checking...', 'buddyboss' ) : __( 'Get MIME Type', 'buddyboss' ) }
									</Button>
								</div>
								{ mimeCheckerResult && (
									<div className="bb-extension-modal__mime-checker-result">
										<span className="bb-extension-modal__mime-checker-result-label">
											{ __( 'Detected MIME type:', 'buddyboss' ) }
										</span>
										<code className="bb-extension-modal__mime-checker-result-value">
											{ mimeCheckerResult }
										</code>
										<Button
											variant="primary"
											onClick={ handleUseMimeType }
											className="bb-extension-modal__mime-checker-use-btn"
										>
											{ __( 'Use this MIME type', 'buddyboss' ) }
										</Button>
									</div>
								) }
							</div>
						) }
					</div>
					<div className="bb-extension-modal__footer">
						<Button
							variant="secondary"
							onClick={ handleCloseModal }
							className="bb-extension-modal__cancel-btn"
						>
							{ __( 'Cancel', 'buddyboss' ) }
						</Button>
						<Button
							variant="primary"
							onClick={ handleSaveExtension }
							disabled={ ! newExtension.trim() }
							className="bb-extension-modal__save-btn"
						>
							{ __( 'Save', 'buddyboss' ) }
						</Button>
					</div>
				</Modal>
			) }
		</div>
	);
}

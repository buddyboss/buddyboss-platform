/**
 * BuddyBoss Admin Settings 2.0 - ImageUploadField Component
 *
 * Upload, crop (avatar), preview, and remove custom images for image_radio fields.
 * Uses existing AJAX handlers — no new PHP endpoints needed.
 *
 * State machine: idle -> uploading -> (avatar: cropping -> saving | cover: done) -> preview
 *                preview -> removing -> idle
 *
 * @package BuddyBoss\Core\Administration
 * @since BuddyBoss [BBVERSION]
 */

import { useState, useRef, useEffect, useCallback } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { invalidateFeatureCache } from '../../utils/featureCache';
import { AvatarCropModal } from './AvatarCropModal';

// Maximum file size: 10 MB (matches WordPress default).
var MAX_FILE_SIZE = 10 * 1024 * 1024;

// Allowed upload config types for className sanitization.
var ALLOWED_TYPES = { avatar: true, cover: true };

/**
 * Send FormData to an AJAX action.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param {string}      ajaxUrl  AJAX endpoint URL.
 * @param {string}      action   AJAX action name.
 * @param {FormData}    formData Form data to send.
 * @param {AbortSignal} [signal] Optional AbortController signal.
 * @returns {Promise} Promise resolving to JSON response.
 */
function sendAjax( ajaxUrl, action, formData, signal ) {
	formData.append( 'action', action );
	var fetchOptions = {
		method: 'POST',
		credentials: 'same-origin',
		body: formData,
	};
	if ( signal ) {
		fetchOptions.signal = signal;
	}
	return fetch( ajaxUrl, fetchOptions ).then( function ( response ) {
		if ( ! response.ok ) {
			throw new Error( 'HTTP ' + response.status );
		}
		return response.json();
	} );
}

/**
 * ImageUploadField component.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param {Object}   props              Component props.
 * @param {Object}   props.uploadConfig Upload configuration from field registration.
 * @param {string}   props.uploadUrl    Current uploaded image URL (if any).
 * @param {Function} props.onUpload     Callback after successful upload (receives new URL).
 * @param {Function} props.onRemove     Callback after successful removal.
 * @param {boolean}  props.disabled     Whether the field is disabled.
 * @returns {JSX.Element} ImageUploadField component.
 */
export function ImageUploadField( { uploadConfig, uploadUrl, onUpload, onRemove, disabled } ) {
	var [ status, setStatus ] = useState( uploadUrl ? 'preview' : 'idle' );
	var [ previewUrl, setPreviewUrl ] = useState( uploadUrl || '' );
	var [ error, setError ] = useState( '' );
	var [ cropData, setCropData ] = useState( null );
	var fileInputRef = useRef( null );
	var abortRef = useRef( null );

	// Sync preview when uploadUrl changes externally.
	useEffect( function () {
		if ( uploadUrl ) {
			setPreviewUrl( uploadUrl );
			setStatus( 'preview' );
		} else {
			setPreviewUrl( '' );
			setStatus( 'idle' );
		}
	}, [ uploadUrl ] );

	// Abort any in-flight request on unmount.
	useEffect( function () {
		return function () {
			if ( abortRef.current ) {
				abortRef.current.abort();
			}
		};
	}, [] );

	var isAvatar = 'avatar' === uploadConfig.type;
	var nonces = window.bbAdminData?.uploadNonces || {};
	var ajaxUrl = window.bbAdminData?.ajaxUrl || '/wp-admin/admin-ajax.php';

	// Sanitize type for className — only allow known types.
	var typeClass = ALLOWED_TYPES[ uploadConfig.type ] ? uploadConfig.type : 'unknown';

	/**
	 * Handle file selection.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param {Event} e Change event from file input.
	 */
	var handleFileSelect = function ( e ) {
		var file = e.target.files && e.target.files[0];
		if ( ! file ) {
			return;
		}

		// Validate file type.
		if ( ! /^image\/(jpe?g|png)$/i.test( file.type ) ) {
			setError( __( 'Please select a JPG or PNG image.', 'buddyboss' ) );
			return;
		}

		// Validate file size.
		if ( file.size > MAX_FILE_SIZE ) {
			setError( __( 'File size must be less than 10 MB.', 'buddyboss' ) );
			return;
		}

		setError( '' );
		setStatus( 'uploading' );

		if ( isAvatar ) {
			handleAvatarUpload( file );
		} else {
			handleCoverUpload( file );
		}

		// Reset file input so the same file can be re-selected.
		if ( fileInputRef.current ) {
			fileInputRef.current.value = '';
		}
	};

	/**
	 * Upload avatar (step 1: temp upload, then show crop).
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param {File} file The image file.
	 */
	var handleAvatarUpload = function ( file ) {
		// Abort any previous in-flight request.
		if ( abortRef.current ) {
			abortRef.current.abort();
		}
		abortRef.current = new AbortController();

		var formData = new FormData();
		formData.append( 'file', file, file.name );
		// check_admin_referer('bp-uploader') expects `_wpnonce`.
		formData.append( '_wpnonce', nonces.uploader || '' );
		// Handler reads params from $_POST['bp_params'] array.
		formData.append( 'bp_params[object]', uploadConfig.object );
		formData.append( 'bp_params[item_id]', uploadConfig.item_id );
		formData.append( 'bp_params[item_type]', uploadConfig.item_type || '' );

		sendAjax( ajaxUrl, 'bp_avatar_upload', formData, abortRef.current.signal ).then( function ( response ) {
			if ( response.success && response.data ) {
				// Use the server's uploaded image URL for crop preview so that
				// canvas dimensions match the file the server will crop.
				var serverUrl = response.data.url || '';
				setCropData( {
					imageUrl: serverUrl,
					originalFile: serverUrl,
				} );
				setStatus( 'cropping' );
			} else {
				var msg = ( response.data && response.data.message ) || __( 'Upload failed.', 'buddyboss' );
				setError( msg );
				setStatus( 'idle' );
			}
		} ).catch( function ( err ) {
			if ( 'AbortError' === err.name ) {
				return;
			}
			setError( err.message || __( 'Upload failed.', 'buddyboss' ) );
			setStatus( 'idle' );
		} );
	};

	/**
	 * Upload cover image (single step — no crop needed).
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param {File} file The image file.
	 */
	var handleCoverUpload = function ( file ) {
		// Abort any previous in-flight request.
		if ( abortRef.current ) {
			abortRef.current.abort();
		}
		abortRef.current = new AbortController();

		var formData = new FormData();
		formData.append( 'file', file, file.name );
		// check_admin_referer('bp-uploader') expects `_wpnonce`.
		formData.append( '_wpnonce', nonces.uploader || '' );
		// Handler reads params from $_POST['bp_params'] array.
		formData.append( 'bp_params[object]', uploadConfig.object );
		formData.append( 'bp_params[item_id]', uploadConfig.item_id );
		formData.append( 'bp_params[item_type]', uploadConfig.item_type || '' );

		sendAjax( ajaxUrl, 'bp_cover_image_upload', formData, abortRef.current.signal ).then( function ( response ) {
			if ( response.success && response.data ) {
				var newUrl = response.data.url || response.data;
				setPreviewUrl( newUrl );
				setStatus( 'preview' );
				invalidateFeatureCache( 'groups' );
				if ( onUpload ) {
					onUpload( newUrl );
				}
			} else {
				var msg = ( response.data && response.data.message ) || __( 'Upload failed.', 'buddyboss' );
				setError( msg );
				setStatus( 'idle' );
			}
		} ).catch( function ( err ) {
			if ( 'AbortError' === err.name ) {
				return;
			}
			setError( err.message || __( 'Upload failed.', 'buddyboss' ) );
			setStatus( 'idle' );
		} );
	};

	/**
	 * Handle remove (avatar or cover).
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	var handleRemove = function () {
		setStatus( 'removing' );
		setError( '' );

		// Abort any previous in-flight request.
		if ( abortRef.current ) {
			abortRef.current.abort();
		}
		abortRef.current = new AbortController();

		var formData = new FormData();
		formData.append( 'object', uploadConfig.object );
		formData.append( 'item_id', uploadConfig.item_id );
		formData.append( 'item_type', uploadConfig.item_type || '' );

		var action;
		if ( isAvatar ) {
			formData.append( 'nonce', nonces.avatarDelete || '' );
			action = 'bp_avatar_delete';
		} else {
			formData.append( 'nonce', nonces.coverDelete || '' );
			action = 'bp_cover_image_delete';
		}

		sendAjax( ajaxUrl, action, formData, abortRef.current.signal ).then( function ( response ) {
			if ( response.success ) {
				setPreviewUrl( '' );
				setStatus( 'idle' );
				invalidateFeatureCache( 'groups' );
				if ( onRemove ) {
					onRemove();
				}
			} else {
				var msg = ( response.data && response.data.message ) || __( 'Remove failed.', 'buddyboss' );
				setError( msg );
				setStatus( 'preview' );
			}
		} ).catch( function ( err ) {
			if ( 'AbortError' === err.name ) {
				return;
			}
			setError( err.message || __( 'Remove failed.', 'buddyboss' ) );
			setStatus( 'preview' );
		} );
	};

	/**
	 * Trigger file input click.
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	var triggerFileInput = function () {
		if ( fileInputRef.current ) {
			fileInputRef.current.click();
		}
	};

	// Stable callback for crop cancel — prevents event listener churn in AvatarCropModal.
	var handleCropCancel = useCallback( function () {
		setCropData( null );
		setStatus( 'idle' );
	}, [] );

	// Stable callback for crop save.
	var handleCropSave = useCallback( function ( newUrl ) {
		setCropData( null );
		setPreviewUrl( newUrl );
		setStatus( 'preview' );
		invalidateFeatureCache( 'groups' );
		if ( onUpload ) {
			onUpload( newUrl );
		}
	}, [ onUpload ] );

	var isLoading = 'uploading' === status || 'saving' === status || 'removing' === status;
	var helpText = uploadConfig.help_text || '';
	var uploadLabel = uploadConfig.label || '';

	return (
		<div className={ 'bb-admin-image-upload bb-admin-image-upload--' + typeClass }>
			{ /* Upload label (e.g. "Upload Custom Avatar") */ }
			{ uploadLabel && (
				<p className="bb-admin-image-upload__label">{ uploadLabel }</p>
			) }

			{ /* Idle / Placeholder */ }
			{ ( 'idle' === status || 'uploading' === status ) && (
				<div className="bb-admin-image-upload__placeholder-area">
					<div className="bb-admin-image-upload__placeholder">
						{ isLoading
							? <span className="bb-admin-image-upload__spinner"></span>
							: <span className="dashicons dashicons-format-image"></span>
						}
					</div>
					<div className="bb-admin-image-upload__actions">
						<button
							type="button"
							className="bb-admin-image-upload__btn bb-admin-image-upload__btn--upload"
							onClick={ triggerFileInput }
							disabled={ disabled || isLoading }
						>
							{ isLoading ? __( 'Uploading...', 'buddyboss' ) : __( 'Upload', 'buddyboss' ) }
						</button>
					</div>
				</div>
			) }

			{ /* Preview */ }
			{ 'preview' === status && previewUrl && (
				<div className="bb-admin-image-upload__preview-area">
					<div className="bb-admin-image-upload__preview">
						<img src={ previewUrl } alt={ __( 'Uploaded image', 'buddyboss' ) } />
					</div>
					<div className="bb-admin-image-upload__actions">
						<button
							type="button"
							className="bb-admin-image-upload__btn bb-admin-image-upload__btn--upload"
							onClick={ triggerFileInput }
							disabled={ disabled }
						>
							{ __( 'Upload', 'buddyboss' ) }
						</button>
						<button
							type="button"
							className="bb-admin-image-upload__btn bb-admin-image-upload__btn--remove"
							onClick={ handleRemove }
							disabled={ disabled }
						>
							{ __( 'Remove', 'buddyboss' ) }
						</button>
					</div>
				</div>
			) }

			{ /* Removing state */ }
			{ 'removing' === status && (
				<div className="bb-admin-image-upload__placeholder-area">
					<div className="bb-admin-image-upload__placeholder">
						<span className="bb-admin-image-upload__spinner"></span>
					</div>
					<div className="bb-admin-image-upload__actions">
						<button type="button" className="bb-admin-image-upload__btn bb-admin-image-upload__btn--upload" disabled>
							{ __( 'Removing...', 'buddyboss' ) }
						</button>
					</div>
				</div>
			) }

			{ /* Crop Modal (avatar only) */ }
			{ 'cropping' === status && cropData && (
				<AvatarCropModal
					imageUrl={ cropData.imageUrl }
					originalFile={ cropData.originalFile }
					nonces={ nonces }
					uploadConfig={ uploadConfig }
					ajaxUrl={ ajaxUrl }
					onSave={ handleCropSave }
					onCancel={ handleCropCancel }
				/>
			) }

			{ /* Hidden file input */ }
			<input
				ref={ fileInputRef }
				type="file"
				accept="image/jpeg,image/png"
				className="bb-admin-image-upload__file-input"
				style={ { display: 'none' } }
				onChange={ handleFileSelect }
				tabIndex={ -1 }
				aria-hidden="true"
			/>

			{ /* Help text */ }
			{ helpText && (
				<p className="bb-admin-image-upload__help">{ helpText }</p>
			) }

			{ /* Error */ }
			{ error && (
				<p className="bb-admin-image-upload__error" role="alert">{ error }</p>
			) }
		</div>
	);
}

/**
 * BuddyBoss Admin Settings 2.0 - ImageUploadField Component
 *
 * Upload, crop (avatar), preview, and remove custom images for image_radio fields.
 * Uses existing AJAX handlers — no new PHP endpoints needed.
 *
 * State machine: idle → uploading → (avatar: cropping → saving | cover: done) → preview
 *                preview → removing → idle
 *
 * @package BuddyBoss\Core\Administration
 * @since BuddyBoss [BBVERSION]
 */

import { useState, useRef, useEffect, useCallback } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { invalidateFeatureCache } from '../../utils/featureCache';

// Maximum file size: 10 MB (matches WordPress default).
var MAX_FILE_SIZE = 10 * 1024 * 1024;

// Allowed upload config types for className sanitization.
var ALLOWED_TYPES = { avatar: true, cover: true };

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

	var isAvatar = 'avatar' === uploadConfig.type;
	var nonces = window.bbAdminData?.uploadNonces || {};
	var ajaxUrl = window.bbAdminData?.ajaxUrl || '/wp-admin/admin-ajax.php';

	// Sanitize type for className — only allow known types.
	var typeClass = ALLOWED_TYPES[ uploadConfig.type ] ? uploadConfig.type : 'unknown';

	/**
	 * Send FormData to an AJAX action.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param {string}   action   AJAX action name.
	 * @param {FormData} formData Form data to send.
	 * @returns {Promise} Promise resolving to JSON response.
	 */
	var sendAjax = function ( action, formData ) {
		formData.append( 'action', action );
		return fetch( ajaxUrl, {
			method: 'POST',
			credentials: 'same-origin',
			body: formData,
		} ).then( function ( response ) {
			if ( ! response.ok ) {
				throw new Error( 'HTTP ' + response.status );
			}
			return response.json();
		} );
	};

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
		var formData = new FormData();
		formData.append( 'file', file, file.name );
		// check_admin_referer('bp-uploader') expects `_wpnonce`.
		formData.append( '_wpnonce', nonces.uploader || '' );
		// Handler reads params from $_POST['bp_params'] array.
		formData.append( 'bp_params[object]', uploadConfig.object );
		formData.append( 'bp_params[item_id]', uploadConfig.item_id );
		formData.append( 'bp_params[item_type]', uploadConfig.item_type || '' );

		sendAjax( 'bp_avatar_upload', formData ).then( function ( response ) {
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
		var formData = new FormData();
		formData.append( 'file', file, file.name );
		// check_admin_referer('bp-uploader') expects `_wpnonce`.
		formData.append( '_wpnonce', nonces.uploader || '' );
		// Handler reads params from $_POST['bp_params'] array.
		formData.append( 'bp_params[object]', uploadConfig.object );
		formData.append( 'bp_params[item_id]', uploadConfig.item_id );
		formData.append( 'bp_params[item_type]', uploadConfig.item_type || '' );

		sendAjax( 'bp_cover_image_upload', formData ).then( function ( response ) {
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

		sendAjax( action, formData ).then( function ( response ) {
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

	var isLoading = 'uploading' === status || 'saving' === status || 'removing' === status;
	var helpText = uploadConfig.help_text || '';

	return (
		<div className={ 'bb-admin-image-upload bb-admin-image-upload--' + typeClass }>
			{ /* Idle / Placeholder */ }
			{ ( 'idle' === status || 'uploading' === status ) && (
				<div className="bb-admin-image-upload__placeholder-area">
					<div className={ 'bb-admin-image-upload__placeholder' + ( isAvatar ? ' bb-admin-image-upload__placeholder--avatar' : '' ) }>
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
					<div className={ 'bb-admin-image-upload__preview' + ( isAvatar ? ' bb-admin-image-upload__preview--avatar' : '' ) }>
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
					<div className={ 'bb-admin-image-upload__placeholder' + ( isAvatar ? ' bb-admin-image-upload__placeholder--avatar' : '' ) }>
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
					onSave={ function ( newUrl ) {
						setCropData( null );
						setPreviewUrl( newUrl );
						setStatus( 'preview' );
						invalidateFeatureCache( 'groups' );
						if ( onUpload ) {
							onUpload( newUrl );
						}
					} }
					onCancel={ function () {
						setCropData( null );
						setStatus( 'idle' );
					} }
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

/**
 * AvatarCropModal - Canvas-based crop UI for avatar images.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param {Object}   props               Component props.
 * @param {string}   props.imageUrl      Server-uploaded image URL for crop preview.
 * @param {string}   props.originalFile  Server-side temp file URL from upload response.
 * @param {Object}   props.nonces        Upload nonces.
 * @param {Object}   props.uploadConfig  Upload configuration.
 * @param {string}   props.ajaxUrl       AJAX URL.
 * @param {Function} props.onSave        Callback after successful crop+save.
 * @param {Function} props.onCancel      Callback on cancel.
 * @returns {JSX.Element} Crop modal.
 */
function AvatarCropModal( { imageUrl, originalFile, nonces, uploadConfig, ajaxUrl, onSave, onCancel } ) {
	var canvasRef = useRef( null );
	var imageRef = useRef( null );
	var [ saving, setSaving ] = useState( false );
	var [ error, setError ] = useState( '' );
	var [ imgLoaded, setImgLoaded ] = useState( false );
	var [ cropBox, setCropBox ] = useState( { x: 0, y: 0, size: 150 } );

	// Use refs for drag state to avoid stale closures in event listeners.
	var draggingRef = useRef( false );
	var dragStartRef = useRef( { x: 0, y: 0, cropX: 0, cropY: 0 } );
	var cropBoxRef = useRef( cropBox );

	// Keep cropBoxRef in sync with state.
	useEffect( function () {
		cropBoxRef.current = cropBox;
	}, [ cropBox ] );

	// Canvas display size.
	var canvasSize = 400;

	/**
	 * Draw the image and crop overlay on the canvas.
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	var drawCanvas = useCallback( function () {
		var canvas = canvasRef.current;
		var img = imageRef.current;
		if ( ! canvas || ! img || ! imgLoaded ) {
			return;
		}

		var ctx = canvas.getContext( '2d' );

		// Draw image scaled to fit canvas.
		var scale = Math.min( canvasSize / img.naturalWidth, canvasSize / img.naturalHeight );
		var drawW = img.naturalWidth * scale;
		var drawH = img.naturalHeight * scale;
		var offsetX = ( canvasSize - drawW ) / 2;
		var offsetY = ( canvasSize - drawH ) / 2;

		// 1. Draw full image.
		ctx.clearRect( 0, 0, canvasSize, canvasSize );
		ctx.drawImage( img, offsetX, offsetY, drawW, drawH );

		// 2. Dark overlay over entire canvas.
		ctx.fillStyle = 'rgba(0, 0, 0, 0.5)';
		ctx.fillRect( 0, 0, canvasSize, canvasSize );

		// 3. Clear crop area and redraw image portion (reveals crop selection).
		ctx.clearRect( cropBox.x, cropBox.y, cropBox.size, cropBox.size );
		ctx.save();
		ctx.beginPath();
		ctx.rect( cropBox.x, cropBox.y, cropBox.size, cropBox.size );
		ctx.clip();
		ctx.drawImage( img, offsetX, offsetY, drawW, drawH );
		ctx.restore();

		// 4. White border around crop box.
		ctx.strokeStyle = '#ffffff';
		ctx.lineWidth = 2;
		ctx.strokeRect( cropBox.x, cropBox.y, cropBox.size, cropBox.size );
	}, [ cropBox, imgLoaded ] );

	useEffect( function () {
		drawCanvas();
	}, [ drawCanvas ] );

	/**
	 * Handle image load.
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	var handleImageLoad = function () {
		var img = imageRef.current;
		if ( ! img ) {
			return;
		}
		setImgLoaded( true );

		// Set initial crop box centered, 60% of the visible image area.
		var scale = Math.min( canvasSize / img.naturalWidth, canvasSize / img.naturalHeight );
		var drawW = img.naturalWidth * scale;
		var drawH = img.naturalHeight * scale;
		var minDim = Math.min( drawW, drawH );
		var size = Math.round( minDim * 0.6 );
		var offsetX = ( canvasSize - drawW ) / 2;
		var offsetY = ( canvasSize - drawH ) / 2;

		setCropBox( {
			x: Math.round( offsetX + ( drawW - size ) / 2 ),
			y: Math.round( offsetY + ( drawH - size ) / 2 ),
			size: size,
		} );
	};

	/**
	 * Handle mouse down on canvas (start dragging crop box).
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param {MouseEvent} e Mouse event.
	 */
	var handleMouseDown = function ( e ) {
		var canvas = canvasRef.current;
		if ( ! canvas ) {
			return;
		}
		var rect = canvas.getBoundingClientRect();
		var scaleX = canvasSize / rect.width;
		var scaleY = canvasSize / rect.height;
		var mouseX = ( e.clientX - rect.left ) * scaleX;
		var mouseY = ( e.clientY - rect.top ) * scaleY;
		var box = cropBoxRef.current;

		// Check if click is inside crop box.
		if (
			mouseX >= box.x &&
			mouseX <= box.x + box.size &&
			mouseY >= box.y &&
			mouseY <= box.y + box.size
		) {
			draggingRef.current = true;
			dragStartRef.current = {
				x: mouseX,
				y: mouseY,
				cropX: box.x,
				cropY: box.y,
			};
			e.preventDefault();
		}
	};

	// Attach global mouse events for dragging using refs to avoid stale closures.
	useEffect( function () {
		var onMouseMove = function ( e ) {
			if ( ! draggingRef.current ) {
				return;
			}
			var canvas = canvasRef.current;
			if ( ! canvas ) {
				return;
			}
			var rect = canvas.getBoundingClientRect();
			var scaleX = canvasSize / rect.width;
			var scaleY = canvasSize / rect.height;
			var mouseX = ( e.clientX - rect.left ) * scaleX;
			var mouseY = ( e.clientY - rect.top ) * scaleY;
			var dx = mouseX - dragStartRef.current.x;
			var dy = mouseY - dragStartRef.current.y;
			var box = cropBoxRef.current;
			var newX = Math.max( 0, Math.min( canvasSize - box.size, dragStartRef.current.cropX + dx ) );
			var newY = Math.max( 0, Math.min( canvasSize - box.size, dragStartRef.current.cropY + dy ) );
			setCropBox( { x: newX, y: newY, size: box.size } );
		};

		var onMouseUp = function () {
			draggingRef.current = false;
		};

		document.addEventListener( 'mousemove', onMouseMove );
		document.addEventListener( 'mouseup', onMouseUp );
		return function () {
			document.removeEventListener( 'mousemove', onMouseMove );
			document.removeEventListener( 'mouseup', onMouseUp );
		};
	}, [] ); // Empty deps — uses refs for all mutable state.

	/**
	 * Crop and save the avatar.
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	var handleCropSave = function () {
		var img = imageRef.current;
		if ( ! img ) {
			return;
		}

		setSaving( true );
		setError( '' );

		// Convert canvas crop coordinates to original image coordinates.
		var scale = Math.min( canvasSize / img.naturalWidth, canvasSize / img.naturalHeight );
		var offsetX = ( canvasSize - img.naturalWidth * scale ) / 2;
		var offsetY = ( canvasSize - img.naturalHeight * scale ) / 2;

		var cropX = Math.max( 0, Math.round( ( cropBox.x - offsetX ) / scale ) );
		var cropY = Math.max( 0, Math.round( ( cropBox.y - offsetY ) / scale ) );
		var cropW = Math.round( cropBox.size / scale );
		var cropH = cropW; // Square crop for avatar.

		var formData = new FormData();
		formData.append( 'nonce', nonces.avatarCropstore || '' );
		formData.append( 'object', uploadConfig.object );
		formData.append( 'item_id', uploadConfig.item_id );
		formData.append( 'item_type', uploadConfig.item_type || '' );
		formData.append( 'original_file', originalFile );
		formData.append( 'type', 'crop' );
		formData.append( 'crop_x', cropX );
		formData.append( 'crop_y', cropY );
		formData.append( 'crop_w', cropW );
		formData.append( 'crop_h', cropH );

		// Use sendAjax helper for consistent error handling.
		formData.append( 'action', 'bp_avatar_set' );
		fetch( ajaxUrl, {
			method: 'POST',
			credentials: 'same-origin',
			body: formData,
		} )
			.then( function ( response ) {
				if ( ! response.ok ) {
					throw new Error( 'HTTP ' + response.status );
				}
				return response.json();
			} )
			.then( function ( response ) {
				if ( response.success && response.data ) {
					var newUrl = response.data.avatar || response.data.url || '';
					onSave( newUrl );
				} else {
					var msg = ( response.data && response.data.message ) || __( 'Crop failed.', 'buddyboss' );
					setError( msg );
					setSaving( false );
				}
			} )
			.catch( function ( err ) {
				setError( err.message || __( 'Crop failed.', 'buddyboss' ) );
				setSaving( false );
			} );
	};

	/**
	 * Handle Escape key to close the crop modal.
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	useEffect( function () {
		var onKeyDown = function ( e ) {
			if ( 'Escape' === e.key && ! saving ) {
				onCancel();
			}
		};
		document.addEventListener( 'keydown', onKeyDown );
		return function () {
			document.removeEventListener( 'keydown', onKeyDown );
		};
	}, [ saving, onCancel ] );

	return (
		<div
			className="bb-admin-image-upload__crop-overlay"
			role="dialog"
			aria-modal="true"
			aria-label={ __( 'Crop Avatar', 'buddyboss' ) }
		>
			<div className="bb-admin-image-upload__crop-modal">
				<h3 className="bb-admin-image-upload__crop-title">
					{ __( 'Crop Avatar', 'buddyboss' ) }
				</h3>

				<div className="bb-admin-image-upload__crop-canvas-wrap">
					{ /* Hidden image for loading — crossOrigin for same-origin canvas access */ }
					<img
						ref={ imageRef }
						src={ imageUrl }
						crossOrigin="anonymous"
						style={ { display: 'none' } }
						onLoad={ handleImageLoad }
						alt=""
					/>
					<canvas
						ref={ canvasRef }
						width={ canvasSize }
						height={ canvasSize }
						className="bb-admin-image-upload__crop-canvas"
						onMouseDown={ handleMouseDown }
					/>
				</div>

				{ error && (
					<p className="bb-admin-image-upload__error" role="alert">{ error }</p>
				) }

				<div className="bb-admin-image-upload__crop-actions">
					<button
						type="button"
						className="bb-admin-image-upload__btn bb-admin-image-upload__btn--cancel"
						onClick={ onCancel }
						disabled={ saving }
					>
						{ __( 'Cancel', 'buddyboss' ) }
					</button>
					<button
						type="button"
						className="bb-admin-image-upload__btn bb-admin-image-upload__btn--upload"
						onClick={ handleCropSave }
						disabled={ saving || ! imgLoaded }
					>
						{ saving ? __( 'Saving...', 'buddyboss' ) : __( 'Crop & Save', 'buddyboss' ) }
					</button>
				</div>
			</div>
		</div>
	);
}

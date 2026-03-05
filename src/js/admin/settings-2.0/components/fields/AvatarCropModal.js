/**
 * BuddyBoss Admin Settings 2.0 - AvatarCropModal Component
 *
 * Canvas-based crop UI for avatar images. Allows dragging a square crop box
 * over the uploaded image before saving.
 *
 * @package BuddyBoss\Core\Administration
 * @since BuddyBoss [BBVERSION]
 */

import { useState, useRef, useEffect, useCallback } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

/**
 * Send FormData to an AJAX action.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param {string}   ajaxUrl  AJAX endpoint URL.
 * @param {string}   action   AJAX action name.
 * @param {FormData} formData Form data to send.
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
export function AvatarCropModal( { imageUrl, originalFile, nonces, uploadConfig, ajaxUrl, onSave, onCancel } ) {
	var canvasRef = useRef( null );
	var imageRef = useRef( null );
	var abortRef = useRef( null );
	var [ saving, setSaving ] = useState( false );
	var [ error, setError ] = useState( '' );
	var [ imgLoaded, setImgLoaded ] = useState( false );
	var [ cropBox, setCropBox ] = useState( { x: 0, y: 0, size: 150 } );
	var [ sizeRange, setSizeRange ] = useState( { min: 50, max: 400 } );

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

	// Abort any in-flight request on unmount.
	useEffect( function () {
		return function () {
			if ( abortRef.current ) {
				abortRef.current.abort();
			}
		};
	}, [] );

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

		// Set min/max for the resize slider based on image dimensions.
		var minSize = Math.max( 50, Math.round( minDim * 0.15 ) );
		var maxSize = Math.round( minDim );
		setSizeRange( { min: minSize, max: maxSize } );

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
	 * Handle resize slider change.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param {Event} e Input range change event.
	 */
	var handleResize = function ( e ) {
		var newSize = parseInt( e.target.value, 10 );
		var box = cropBoxRef.current;
		var img = imageRef.current;
		if ( ! img ) {
			return;
		}

		// Calculate visible image bounds on canvas.
		var scale = Math.min( canvasSize / img.naturalWidth, canvasSize / img.naturalHeight );
		var drawW = img.naturalWidth * scale;
		var drawH = img.naturalHeight * scale;
		var imgLeft = ( canvasSize - drawW ) / 2;
		var imgTop = ( canvasSize - drawH ) / 2;

		// Keep crop box centered on its current center, clamped within image bounds.
		var centerX = box.x + box.size / 2;
		var centerY = box.y + box.size / 2;
		var half = newSize / 2;
		var newX = Math.max( imgLeft, Math.min( imgLeft + drawW - newSize, centerX - half ) );
		var newY = Math.max( imgTop, Math.min( imgTop + drawH - newSize, centerY - half ) );

		setCropBox( { x: Math.round( newX ), y: Math.round( newY ), size: newSize } );
	};

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

		// Abort any previous in-flight request.
		if ( abortRef.current ) {
			abortRef.current.abort();
		}
		abortRef.current = new AbortController();

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

		sendAjax( ajaxUrl, 'bp_avatar_set', formData, abortRef.current.signal )
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
				if ( 'AbortError' === err.name ) {
					return;
				}
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

				{ imgLoaded && (
					<div className="bb-admin-image-upload__crop-resize">
						<i className="bb-icons-rl bb-icons-rl-minus"></i>
						<input
							type="range"
							className="bb-admin-image-upload__crop-slider"
							min={ sizeRange.min }
							max={ sizeRange.max }
							value={ cropBox.size }
							onChange={ handleResize }
						/>
						<i className="bb-icons-rl bb-icons-rl-plus"></i>
					</div>
				) }

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

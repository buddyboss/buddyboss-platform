/**
 * BuddyBoss Admin Settings 2.0 - CoverCropModal Component
 *
 * Canvas-based crop UI for cover images. Mirrors the avatar crop modal but
 * uses a rectangular crop box matching the configured cover dimensions
 * (default 1950×450 → 13:3 ratio). The React modal is the FIRST step where
 * the admin can choose which slice of their uploaded image becomes the
 * cover band; without it, the existing single-step `bp_cover_image_upload`
 * flow auto-fits the image and the admin had no control over framing.
 *
 * Wire diagram (admin two-step):
 *   1. ImageUploadField uploads file → `bb_admin_cover_image_upload_temp`
 *      → server stages a `tmp-XXX` copy at the default cover dir, returns
 *        `{url, basename, width, height}` of the unfit original.
 *   2. ImageUploadField sets status='cropping' and renders this modal.
 *   3. Admin drags/resizes the crop box, clicks Crop & Save.
 *   4. Modal POSTs to `bb_admin_cover_image_set` with crop coords + basename.
 *      Server applies user crop, runs the existing fit-to-feature-dimensions
 *      step, fires `xprofile_cover_image_uploaded` / `groups_cover_image_uploaded`
 *      so the option-storage hook updates `bp-default-custom-{profile,group}-cover`.
 *      Returns the final URL.
 *
 * The crop box is rectangular and locked to the cover aspect ratio. The
 * resize slider scales the box width; height is recomputed from the ratio.
 *
 * @package BuddyBoss\Core\Administration
 * @since BuddyBoss [BBVERSION]
 */

import { useState, useRef, useEffect, useCallback, useMemo } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

// Canvas display size — wider than tall to give the rectangular crop box
// reasonable horizontal real estate without squashing it into a few pixels
// of height. Image is letterboxed inside this canvas to preserve aspect.
var CANVAS_WIDTH  = 700;
var CANVAS_HEIGHT = 400;

// Fallback aspect ratio (1950 / 450) when uploadConfig.dimensions is missing.
// Covers cases where the field's PHP registration was deployed without the
// `dimensions` key — the modal still works, it just defaults to the standard
// BuddyBoss cover aspect rather than a site-overridden one.
var FALLBACK_RATIO = 1950 / 450;

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
// Shared with ImageUploadField — but defined locally to keep CoverCropModal
// independently buildable (one component per file is the project convention).
// See ImageUploadField.js for the content-type guard rationale.
function sendAjax( ajaxUrl, action, formData, signal ) {
	formData.append( 'action', action );
	var fetchOptions = {
		method:      'POST',
		credentials: 'same-origin',
		body:        formData,
	};
	if ( signal ) {
		fetchOptions.signal = signal;
	}
	return fetch( ajaxUrl, fetchOptions ).then( function ( response ) {
		if ( ! response.ok ) {
			throw new Error( 'HTTP ' + response.status );
		}
		// 200-status non-JSON guard: security plugin challenge pages or a
		// fatal in a downstream listener that flushed HTML before
		// wp_send_json_* completed would otherwise reach `.json()` and
		// throw a cryptic `Unexpected token '<'`.
		var contentType = response.headers.get( 'content-type' ) || '';
		if ( -1 === contentType.indexOf( 'application/json' ) ) {
			throw new Error( __( 'Unexpected server response.', 'buddyboss' ) );
		}
		return response.json();
	} );
}

/**
 * CoverCropModal - Canvas-based crop UI for cover images.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param {Object}   props              Component props.
 * @param {string}   props.imageUrl     Server-staged cover image URL (from `bb_admin_cover_image_upload_temp`).
 * @param {string}   props.basename     Server-side temp file basename (returned alongside imageUrl).
 * @param {Object}   props.nonces       Upload nonces (uses `coverCropstore`).
 * @param {Object}   props.uploadConfig Upload configuration — must include `object` ('user'|'group').
 *                                      Optional `dimensions` ({width, height}) drives the crop aspect ratio.
 * @param {string}   props.ajaxUrl      AJAX URL.
 * @param {Function} props.onSave       Callback after successful crop+save (receives final URL).
 * @param {Function} props.onCancel     Callback on cancel.
 * @returns {JSX.Element} Crop modal.
 */
export function CoverCropModal( { imageUrl, basename, originalName, nonces, uploadConfig, ajaxUrl, onSave, onCancel } ) {
	var canvasRef = useRef( null );
	var imageRef  = useRef( null );
	var abortRef  = useRef( null );

	var [ saving, setSaving ]       = useState( false );
	var [ error, setError ]         = useState( '' );
	var [ imgLoaded, setImgLoaded ] = useState( false );

	// Crop box: rectangular, aspect-locked. `width` is the slider-controlled
	// dimension; `height` is recomputed from the aspect ratio whenever width
	// changes. State is kept as { x, y, width, height } so the canvas draw
	// path doesn't have to recompute height every frame.
	var [ cropBox, setCropBox ]     = useState( { x: 0, y: 0, width: 200, height: 46 } );
	var [ widthRange, setWidthRange ] = useState( { min: 100, max: CANVAS_WIDTH } );

	// Drag state lives in refs so the global mousemove listener doesn't
	// capture stale React state.
	var draggingRef  = useRef( false );
	var dragStartRef = useRef( { x: 0, y: 0, cropX: 0, cropY: 0 } );
	var cropBoxRef   = useRef( cropBox );

	useEffect( function () {
		cropBoxRef.current = cropBox;
	}, [ cropBox ] );

	// Cover aspect ratio derived from PHP-side dimensions when available;
	// falls back to the BuddyBoss default 1950×450. Memoized so the value is
	// identity-stable across renders (matters for the onLoad handler closures).
	var aspectRatio = useMemo( function () {
		var d = uploadConfig && uploadConfig.dimensions;
		if ( d && d.width && d.height ) {
			return d.width / d.height;
		}
		return FALLBACK_RATIO;
	}, [ uploadConfig ] );

	// Abort any in-flight request on unmount so a slow network doesn't try to
	// flip status on a dead component.
	useEffect( function () {
		return function () {
			if ( abortRef.current ) {
				abortRef.current.abort();
			}
		};
	}, [] );

	/**
	 * Compute the visible image's drawn rectangle on the canvas — the image is
	 * scaled to fit while preserving aspect, then centered. Returns the same
	 * shape we use for clamping the crop box.
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	var getDrawRect = useCallback( function () {
		var img = imageRef.current;
		if ( ! img || ! img.naturalWidth || ! img.naturalHeight ) {
			return { left: 0, top: 0, width: CANVAS_WIDTH, height: CANVAS_HEIGHT, scale: 1 };
		}
		var scale   = Math.min( CANVAS_WIDTH / img.naturalWidth, CANVAS_HEIGHT / img.naturalHeight );
		var drawW   = img.naturalWidth * scale;
		var drawH   = img.naturalHeight * scale;
		var offsetX = ( CANVAS_WIDTH - drawW ) / 2;
		var offsetY = ( CANVAS_HEIGHT - drawH ) / 2;
		return { left: offsetX, top: offsetY, width: drawW, height: drawH, scale: scale };
	}, [] );

	/**
	 * Draw the image and crop overlay on the canvas.
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	var drawCanvas = useCallback( function () {
		var canvas = canvasRef.current;
		var img    = imageRef.current;
		if ( ! canvas || ! img || ! imgLoaded ) {
			return;
		}

		var ctx = canvas.getContext( '2d' );
		var r   = getDrawRect();

		// 1. Clear and draw full image scaled to fit.
		ctx.clearRect( 0, 0, CANVAS_WIDTH, CANVAS_HEIGHT );
		ctx.drawImage( img, r.left, r.top, r.width, r.height );

		// 2. Dark overlay over the entire canvas.
		ctx.fillStyle = 'rgba(0, 0, 0, 0.5)';
		ctx.fillRect( 0, 0, CANVAS_WIDTH, CANVAS_HEIGHT );

		// 3. Punch out the crop area and redraw the image portion underneath
		//    so the user sees their selection in clear focus.
		ctx.clearRect( cropBox.x, cropBox.y, cropBox.width, cropBox.height );
		ctx.save();
		ctx.beginPath();
		ctx.rect( cropBox.x, cropBox.y, cropBox.width, cropBox.height );
		ctx.clip();
		ctx.drawImage( img, r.left, r.top, r.width, r.height );
		ctx.restore();

		// 4. White border around the crop box.
		ctx.strokeStyle = '#ffffff';
		ctx.lineWidth   = 2;
		ctx.strokeRect( cropBox.x, cropBox.y, cropBox.width, cropBox.height );
	}, [ cropBox, imgLoaded, getDrawRect ] );

	useEffect( function () {
		drawCanvas();
	}, [ drawCanvas ] );

	/**
	 * On image load, size and center the crop box at the cover aspect ratio,
	 * anchored to the image area. The default size is whichever is smaller —
	 * 80% of the visible image width, OR a width that keeps the matching
	 * height inside the visible image. Avoids the slider starting outside its
	 * own range.
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	var handleImageLoad = function () {
		var img = imageRef.current;
		if ( ! img ) {
			return;
		}
		setImgLoaded( true );

		var r = getDrawRect();

		// Initial crop width: 80% of visible image, but never wider than what
		// keeps the matching height inside the visible image.
		var maxWByHeight = Math.floor( r.height * aspectRatio );
		var initialW     = Math.min( Math.floor( r.width * 0.8 ), maxWByHeight );
		var minW         = Math.max( 60, Math.floor( Math.min( r.width, maxWByHeight ) * 0.2 ) );
		var maxW         = Math.floor( Math.min( r.width, maxWByHeight ) );
		// Guard for pathological inputs (image smaller than min) — collapse
		// the slider range to one value so the user can still save.
		if ( maxW < minW ) {
			maxW = minW;
		}
		if ( initialW < minW ) {
			initialW = minW;
		}
		if ( initialW > maxW ) {
			initialW = maxW;
		}
		var initialH = Math.round( initialW / aspectRatio );

		setWidthRange( { min: minW, max: maxW } );
		setCropBox( {
			x:      Math.round( r.left + ( r.width - initialW ) / 2 ),
			y:      Math.round( r.top + ( r.height - initialH ) / 2 ),
			width:  initialW,
			height: initialH,
		} );
	};

	/**
	 * Handle mouse down — start dragging the crop box if the click landed
	 * inside it. Drag is canvas-coordinate based; mouse coords are scaled to
	 * canvas size to handle CSS-resized canvases.
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
		var rect   = canvas.getBoundingClientRect();
		var scaleX = CANVAS_WIDTH / rect.width;
		var scaleY = CANVAS_HEIGHT / rect.height;
		var mouseX = ( e.clientX - rect.left ) * scaleX;
		var mouseY = ( e.clientY - rect.top ) * scaleY;
		var box    = cropBoxRef.current;

		if (
			mouseX >= box.x &&
			mouseX <= box.x + box.width &&
			mouseY >= box.y &&
			mouseY <= box.y + box.height
		) {
			draggingRef.current  = true;
			dragStartRef.current = {
				x:     mouseX,
				y:     mouseY,
				cropX: box.x,
				cropY: box.y,
			};
			e.preventDefault();
		}
	};

	// Global mouse move/up — uses refs to read latest state without re-binding
	// listeners, matching the AvatarCropModal pattern.
	//
	// `setCropBox` calls are coalesced into a single `requestAnimationFrame`
	// callback per browser frame. Raw `mousemove` fires at 60-1000Hz depending
	// on the input device (high-poll-rate mice); driving `setCropBox` at that
	// rate causes a React commit + full canvas redraw per event. With rAF the
	// state update fires at most once per display refresh — visually
	// indistinguishable but with a 5-10× reduction in render+redraw work
	// during a drag.
	var rafRef = useRef( null );
	useEffect( function () {
		var onMouseMove = function ( e ) {
			if ( ! draggingRef.current ) {
				return;
			}
			var canvas = canvasRef.current;
			if ( ! canvas ) {
				return;
			}
			var rect   = canvas.getBoundingClientRect();
			var scaleX = CANVAS_WIDTH / rect.width;
			var scaleY = CANVAS_HEIGHT / rect.height;
			var mouseX = ( e.clientX - rect.left ) * scaleX;
			var mouseY = ( e.clientY - rect.top ) * scaleY;
			var dx     = mouseX - dragStartRef.current.x;
			var dy     = mouseY - dragStartRef.current.y;
			var box    = cropBoxRef.current;
			var r      = getDrawRect();

			// Clamp the crop box inside the visible image rectangle, not the
			// canvas. Letting the user drag into the dark letterbox bands
			// would crop empty pixels outside the actual image.
			var minX = r.left;
			var minY = r.top;
			var maxX = r.left + r.width  - box.width;
			var maxY = r.top  + r.height - box.height;
			var newX = Math.max( minX, Math.min( maxX, dragStartRef.current.cropX + dx ) );
			var newY = Math.max( minY, Math.min( maxY, dragStartRef.current.cropY + dy ) );

			// Coalesce: cancel any pending frame, schedule a fresh one. The
			// next mousemove will replace it; on rAF firing we commit the
			// final cropBox state for that frame.
			if ( null !== rafRef.current ) {
				cancelAnimationFrame( rafRef.current );
			}
			rafRef.current = requestAnimationFrame( function () {
				rafRef.current = null;
				setCropBox( {
					x:      newX,
					y:      newY,
					width:  box.width,
					height: box.height,
				} );
			} );
		};

		var onMouseUp = function () {
			draggingRef.current = false;
		};

		document.addEventListener( 'mousemove', onMouseMove );
		document.addEventListener( 'mouseup', onMouseUp );
		return function () {
			document.removeEventListener( 'mousemove', onMouseMove );
			document.removeEventListener( 'mouseup', onMouseUp );
			// Cancel any still-pending frame on unmount so a late
			// `setCropBox` doesn't fire against an unmounted component.
			if ( null !== rafRef.current ) {
				cancelAnimationFrame( rafRef.current );
				rafRef.current = null;
			}
		};
	}, [ getDrawRect ] );

	/**
	 * Resize slider — drives the crop-box width; height is recomputed from
	 * the aspect ratio. Box is re-centered around its prior center, then
	 * clamped inside the visible image bounds.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param {Event} e Slider input event.
	 */
	var handleResize = function ( e ) {
		var newW = parseInt( e.target.value, 10 );
		if ( isNaN( newW ) || newW <= 0 ) {
			return;
		}
		var box = cropBoxRef.current;
		var r   = getDrawRect();

		var newH    = Math.round( newW / aspectRatio );
		var centerX = box.x + box.width / 2;
		var centerY = box.y + box.height / 2;
		var newX    = Math.max( r.left, Math.min( r.left + r.width  - newW, centerX - newW / 2 ) );
		var newY    = Math.max( r.top,  Math.min( r.top  + r.height - newH, centerY - newH / 2 ) );

		setCropBox( {
			x:      Math.round( newX ),
			y:      Math.round( newY ),
			width:  newW,
			height: newH,
		} );
	};

	/**
	 * Crop and save. Converts canvas crop coordinates to original-image
	 * coordinates (using the inverse of the image-fit scale) and POSTs them
	 * to `bb_admin_cover_image_set`, which applies the crop and runs the
	 * existing feature-dimension fit step on the result.
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	var handleCropSave = function () {
		var img = imageRef.current;
		if ( ! img ) {
			return;
		}

		// Client-side guard against a missing/malformed `uploadConfig.object`.
		// Without this the FormData posts `"undefined"` as a string, the
		// server validation responds `Invalid object.`, and the admin sees
		// what looks like a server bug for a misconfigured field registration.
		if ( 'user' !== uploadConfig.object && 'group' !== uploadConfig.object ) {
			setError( __( 'Upload configuration is invalid (missing object).', 'buddyboss' ) );
			return;
		}

		setSaving( true );
		setError( '' );

		if ( abortRef.current ) {
			abortRef.current.abort();
		}
		abortRef.current = new AbortController();

		// Canvas → original-image coordinate conversion. Same math as the
		// AvatarCropModal but using box.width/box.height directly (not size).
		var r       = getDrawRect();
		var scale   = r.scale;
		var cropXSrc = Math.max( 0, Math.round( ( cropBox.x - r.left ) / scale ) );
		var cropYSrc = Math.max( 0, Math.round( ( cropBox.y - r.top  ) / scale ) );
		var cropWSrc = Math.round( cropBox.width  / scale );
		var cropHSrc = Math.round( cropBox.height / scale );

		// Belt-and-braces: never let crop coords run past the image bounds.
		// Server-side wp_get_image_editor would error, but trim here so the
		// user gets a fast clientside check rather than a 500.
		if ( cropXSrc + cropWSrc > img.naturalWidth ) {
			cropWSrc = img.naturalWidth - cropXSrc;
		}
		if ( cropYSrc + cropHSrc > img.naturalHeight ) {
			cropHSrc = img.naturalHeight - cropYSrc;
		}

		var formData = new FormData();
		formData.append( 'nonce', ( nonces && nonces.coverCropstore ) || '' );
		formData.append( 'object', uploadConfig.object );
		formData.append( 'basename', basename );
		formData.append( 'crop_x', cropXSrc );
		formData.append( 'crop_y', cropYSrc );
		formData.append( 'crop_w', cropWSrc );
		formData.append( 'crop_h', cropHSrc );
		// Forward the original filename captured in phase 1 so the
		// `*_cover_image_uploaded` action's `$name` arg holds the admin's
		// actual file name rather than the random `tmp-XXXX` stem. Server
		// falls back to the temp basename's stem if this is missing.
		if ( originalName ) {
			formData.append( 'original_name', originalName );
		}

		sendAjax( ajaxUrl, 'bb_admin_cover_image_set', formData, abortRef.current.signal )
			.then( function ( response ) {
				if ( response.success && response.data && response.data.url ) {
					onSave( response.data.url );
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

	// Escape closes the modal.
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
			aria-label={ __( 'Upload Custom Cover', 'buddyboss' ) }
		>
			<div className="bb-admin-image-upload__crop-modal">
				{ /* Header: title left, close (✕) right, divider underneath (border-bottom on .__crop-header). */ }
				<div className="bb-admin-image-upload__crop-header">
					<h3 className="bb-admin-image-upload__crop-title">
						{ __( 'Upload Custom Cover', 'buddyboss' ) }
					</h3>
					<button
						type="button"
						className="bb-admin-image-upload__crop-close"
						onClick={ onCancel }
						disabled={ saving }
						aria-label={ __( 'Close', 'buddyboss' ) }
					>
						<i className="bb-icons-rl bb-icons-rl-x" aria-hidden="true"></i>
					</button>
				</div>

				{ /* Body: scrollable region holding the crop canvas + resize slider + error. */ }
				<div className="bb-admin-image-upload__crop-body">
					<div className="bb-admin-image-upload__crop-canvas-wrap">
						{ /* Hidden image for canvas drawing. crossOrigin so canvas pixel reads stay tainted-free if a CDN serves the URL. */ }
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
							width={ CANVAS_WIDTH }
							height={ CANVAS_HEIGHT }
							className="bb-admin-image-upload__crop-canvas"
							onMouseDown={ handleMouseDown }
						/>
					</div>

					{ imgLoaded && widthRange.max > widthRange.min && (
						<div className="bb-admin-image-upload__crop-resize">
							<i className="bb-icons-rl bb-icons-rl-minus"></i>
							<input
								type="range"
								className="bb-admin-image-upload__crop-slider"
								min={ widthRange.min }
								max={ widthRange.max }
								value={ cropBox.width }
								onChange={ handleResize }
							/>
							<i className="bb-icons-rl bb-icons-rl-plus"></i>
						</div>
					) }

					{ error && (
						<p className="bb-admin-image-upload__error" role="alert">{ error }</p>
					) }
				</div>

				{ /* Footer: right-aligned button group with a 1px top rule. */ }
				<div className="bb-admin-image-upload__crop-footer">
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
						{ saving ? __( 'Uploading…', 'buddyboss' ) : __( 'Crop & Upload', 'buddyboss' ) }
					</button>
				</div>
			</div>
		</div>
	);
}

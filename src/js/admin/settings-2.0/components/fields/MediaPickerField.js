/**
 * BuddyBoss Admin Settings 2.0 — MediaPickerField Component
 *
 * Generic WordPress Media Library picker. Opens wp.media() and returns the
 * selected attachment(s) as `{ id, url, alt, title }` objects (or an array of
 * such objects when `multiple` is true).
 *
 * Distinct from `ImageUploadField`, which is tightly coupled to BuddyPress
 * avatar/cover AJAX endpoints and returns a bare URL string.
 *
 * Field config keys consumed (from PHP `bb_register_feature_field()`):
 *   - `library_type`     (string)  Defaults to 'image'. Pass to `library.type`.
 *   - `multiple`         (bool)    Defaults to false.
 *   - `frame_title`      (string)  Optional override for media frame title.
 *   - `frame_button_text`(string)  Optional override for the frame's select button.
 *   - `placeholder_icon` (string)  Icon class for the empty-state button (default 'plus').
 *
 * Used by:
 *   - Appearance → Branding → bb_rl_light_logo / bb_rl_dark_logo
 *   - (Phase 4) Appearance → Site SEO → OG Image
 *
 * @package BuddyBoss\Core\Administration
 * @since BuddyBoss [BBVERSION]
 */

import { useEffect, useRef } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { safeUrl } from '../../utils/sanitize';

/**
 * Convert a wp.media attachment JSON into the canonical media object.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param {Object} attachment Attachment JSON from wp.media selection.
 * @returns {Object} { id, url, alt, title }
 */
function attachmentToObject( attachment ) {
	return {
		id:    attachment.id,
		url:   attachment.url,
		alt:   attachment.alt || '',
		title: attachment.title || '',
	};
}

/**
 * MediaPickerField component.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param {Object}              props          Component props.
 * @param {Object|Array|null}   props.value    Current value: object, array of objects, or empty.
 * @param {Function}            props.onChange Callback invoked with the new value (object/array/null).
 * @param {boolean}             props.disabled Whether the field is disabled.
 * @param {Object}              [props.config] Field-level config (library_type, multiple, frame_title, ...).
 * @returns {JSX.Element} MediaPickerField component.
 */
export function MediaPickerField( { value, onChange, disabled, config } ) {
	var frameRef       = useRef( null );
	var mergedConfig   = config || {};
	var libraryType    = mergedConfig.library_type || 'image';
	var allowMultiple  = !! mergedConfig.multiple;
	var frameTitle     = mergedConfig.frame_title || __( 'Select or Upload Media', 'buddyboss' );
	var frameButton    = mergedConfig.frame_button_text || __( 'Use this media', 'buddyboss' );
	var placeholderIcn = mergedConfig.placeholder_icon || 'plus';
	// 'compact' (default) renders a small +-tile placeholder. 'large' renders a
	// square picture placeholder plus a separate "Upload" button — used by OG image.
	var placeholderVariant = mergedConfig.placeholder_variant || 'compact';

	// Tear down the wp.media frame on unmount to avoid leaking event handlers.
	useEffect( function () {
		return function () {
			if ( frameRef.current && typeof frameRef.current.off === 'function' ) {
				frameRef.current.off();
				frameRef.current = null;
			}
		};
	}, [] );

	function openMediaLibrary() {
		if ( typeof window.wp === 'undefined' || ! window.wp.media ) {
			window.alert( __( 'WordPress Media API is not available.', 'buddyboss' ) );
			return;
		}

		// Reuse a single frame instance across opens for performance.
		if ( ! frameRef.current ) {
			frameRef.current = window.wp.media( {
				title:    frameTitle,
				button:   { text: frameButton },
				multiple: allowMultiple,
				library:  { type: libraryType },
			} );

			frameRef.current.on( 'select', function () {
				var selection = frameRef.current.state().get( 'selection' );

				if ( allowMultiple ) {
					var values = [];
					selection.each( function ( model ) {
						values.push( attachmentToObject( model.toJSON() ) );
					} );
					onChange( values );
				} else {
					var attachment = selection.first().toJSON();
					onChange( attachmentToObject( attachment ) );
				}
			} );
		}

		frameRef.current.open();
	}

	function handleRemove() {
		onChange( allowMultiple ? [] : null );
	}

	// Some fields (e.g. `buddyboss_og_image`) persist the picked attachment as
	// a bare URL string to preserve legacy `<meta og:image>` output. Normalize
	// to the canonical `{ url, alt, title }` shape so the preview renders the
	// same whether the stored value is an object or a string.
	var normalizedSingle = null;
	if ( ! allowMultiple && value ) {
		if ( typeof value === 'string' && value ) {
			normalizedSingle = { url: value, alt: '', title: '' };
		} else if ( typeof value === 'object' && value.url ) {
			normalizedSingle = value;
		}
	}

	// Single-value rendering helpers.
	var hasSingleImage = null !== normalizedSingle;
	var hasMultiImages = allowMultiple && Array.isArray( value ) && value.length > 0;
	var hasAny         = hasSingleImage || hasMultiImages;

	var isLargeVariant = 'large' === placeholderVariant;
	var mediaPickerClass = 'bb-admin-media-picker' + ( isLargeVariant ? ' bb-admin-media-picker--large' : '' );

	return (
		<div className={ mediaPickerClass }>
			{ hasSingleImage && (
				<div className="bb-admin-media-picker__preview-area">
					<div className="bb-admin-media-picker__preview">
						<img
							src={ safeUrl( normalizedSingle.url ) }
							alt={ normalizedSingle.alt || '' }
							className="bb-admin-media-picker__preview-image"
						/>
					</div>
					<div className="bb-admin-media-picker__actions">
						<button
							type="button"
							className="bb-admin-media-picker__btn bb-admin-media-picker__btn--replace"
							onClick={ openMediaLibrary }
							disabled={ disabled }
						>
							<i className="bb-icons-rl bb-icons-rl-upload-simple"></i>
							{ __( 'Replace', 'buddyboss' ) }
						</button>
						<button
							type="button"
							className="bb-admin-media-picker__btn bb-admin-media-picker__btn--remove"
							onClick={ handleRemove }
							disabled={ disabled }
						>
							<i className="bb-icons-rl bb-icons-rl-x"></i>
							{ __( 'Remove', 'buddyboss' ) }
						</button>
					</div>
				</div>
			) }

			{ hasMultiImages && (
				<div className="bb-admin-media-picker__multi-area">
					<ul className="bb-admin-media-picker__multi-list">
						{ value.map( function ( item ) {
							return (
								<li key={ item.id } className="bb-admin-media-picker__multi-item">
									<img src={ safeUrl( item.url ) } alt={ item.alt || '' } />
								</li>
							);
						} ) }
					</ul>
					<div className="bb-admin-media-picker__actions">
						<button
							type="button"
							className="bb-admin-media-picker__btn bb-admin-media-picker__btn--replace"
							onClick={ openMediaLibrary }
							disabled={ disabled }
						>
							<i className="bb-icons-rl bb-icons-rl-upload-simple"></i>
							{ __( 'Replace selection', 'buddyboss' ) }
						</button>
						<button
							type="button"
							className="bb-admin-media-picker__btn bb-admin-media-picker__btn--remove"
							onClick={ handleRemove }
							disabled={ disabled }
						>
							<i className="bb-icons-rl bb-icons-rl-x"></i>
							{ __( 'Clear', 'buddyboss' ) }
						</button>
					</div>
				</div>
			) }

			{ ! hasAny && ! isLargeVariant && (
				<div className="bb-admin-media-picker__placeholder-area">
					<button
						type="button"
						className="bb-admin-media-picker__placeholder"
						onClick={ openMediaLibrary }
						disabled={ disabled }
						aria-label={ __( 'Select media', 'buddyboss' ) }
					>
						<i className={ 'bb-icons-rl bb-icons-rl-' + placeholderIcn } aria-hidden="true"></i>
					</button>
				</div>
			) }

			{ ! hasAny && isLargeVariant && (
				<div className="bb-admin-media-picker__placeholder-area bb-admin-media-picker__placeholder-area--large">
					<span className="bb-admin-media-picker__placeholder bb-admin-media-picker__placeholder--large" aria-hidden="true">
						<i className="bb-icons-rl bb-icons-rl-image"></i>
					</span>
					<button
						type="button"
						className="bb-admin-media-picker__upload-btn"
						onClick={ openMediaLibrary }
						disabled={ disabled }
					>
						<i className="bb-icons-rl bb-icons-rl-upload-simple"></i>
						{ __( 'Upload', 'buddyboss' ) }
					</button>
				</div>
			) }
		</div>
	);
}

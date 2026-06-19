/**
 * BuddyBoss Admin Settings 2.0 - Media Frame Hook
 *
 * Centralizes the WordPress Media Library (wp.media) frame logic so any admin
 * React component can open a "select or upload" picker with a single call,
 * without duplicating frame creation, the select handler, and unmount teardown.
 *
 * Mirrors WordPress's native "Featured image" frame by default: standard WP
 * title/button labels and a `filterable` library so the "Filter by type"
 * dropdown shows (defaulting to Images).
 *
 * Used by the Forums create/edit modals for the forum feature image. Designed
 * to be reused by any future media-selection UI (e.g. profile avatar, group
 * cover image) that keeps its own custom trigger/preview markup but wants the
 * native WordPress picker behind it.
 *
 * Distinct from `MediaPickerField`, which bundles its own preview/placeholder
 * markup; this hook is markup-agnostic — the caller owns the UI and only needs
 * the selected attachment(s).
 *
 * @package BuddyBoss\Core\Administration
 * @since BuddyBoss [BBVERSION]
 */

import { useRef, useEffect, useCallback } from '@wordpress/element';

/**
 * Open a WordPress media frame and receive the selection.
 *
 * The frame is created lazily on first open, reused across subsequent opens,
 * and disposed on unmount (releases event handlers + the modal DOM).
 *
 * Note: `config` is evaluated only when the frame is first created (on the
 * first open). The frame is then reused, so later `config` changes are ignored
 * for the lifetime of the component. Pass a stable config; if it must change,
 * remount the component (or extend this hook to rebuild on structural change,
 * as `MediaPickerField` does). The returned opener has a stable identity
 * (memoized) so it is safe to pass as a prop.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param {Object}  [config]             Frame configuration.
 * @param {string}  [config.title]       Frame title. Defaults to WP's "Featured image".
 * @param {string}  [config.buttonText]  Select-button label. Defaults to WP's "Set featured image".
 * @param {string}  [config.libraryType] Library `type` filter (e.g. 'image'). Default 'image'.
 * @param {boolean} [config.multiple]    Allow selecting multiple items. Default false.
 * @param {string}  [config.filterable]  AttachmentFilters mode (e.g. 'all'). Default 'all'.
 * @returns {Function} openMediaFrame( onSelect ) — opens the frame and invokes
 *                      `onSelect` with the chosen attachment object (or an array
 *                      of objects when `multiple` is true). Returns `true` when
 *                      the frame opened, or `false` (a no-op) when the WordPress
 *                      Media API is unavailable so the caller can surface its
 *                      own message.
 */
export function useMediaFrame( config ) {
	var settings    = config || {};
	var frameRef    = useRef( null );
	// Latest `onSelect` per open. The frame's `select` handler is bound ONCE
	// (the frame is reused across opens), so without this ref it would close
	// over the first open's callback. Refreshed on every open below.
	var onSelectRef = useRef( null );

	// Dispose the frame on unmount to avoid leaking event handlers and the
	// modal DOM (off() alone leaves the `.media-modal` node attached).
	useEffect( function () {
		return function () {
			if ( frameRef.current ) {
				frameRef.current.off();
				frameRef.current.dispose();
				frameRef.current = null;
			}
		};
	}, [] );

	// Stable identity across renders so the opener can be passed as a prop
	// without forcing child re-renders. `config` is intentionally read once (on
	// first open — see the note above), so empty deps are correct here.
	// eslint-disable-next-line react-hooks/exhaustive-deps
	return useCallback( function openMediaFrame( onSelect ) {
		if ( typeof window.wp === 'undefined' || ! window.wp.media ) {
			return false;
		}

		onSelectRef.current = onSelect;

		if ( ! frameRef.current ) {
			var l10n       = ( window.wp.media.view && window.wp.media.view.l10n ) || {};
			var title      = settings.title || l10n.setFeaturedImageTitle;
			var buttonText = settings.buttonText || l10n.setFeaturedImage;
			var multiple   = !! settings.multiple;

			frameRef.current = window.wp.media( {
				title:    title,
				button:   { text: buttonText },
				multiple: multiple,
				states:   [
					new window.wp.media.controller.Library( {
						title:      title,
						library:    window.wp.media.query( { type: settings.libraryType || 'image' } ),
						multiple:   multiple,
						filterable: settings.filterable || 'all',
						priority:   20,
					} ),
				],
			} );

			frameRef.current.on( 'select', function () {
				if ( ! onSelectRef.current ) {
					return;
				}

				var selection = frameRef.current.state().get( 'selection' );

				if ( multiple ) {
					var items = [];
					selection.each( function ( model ) {
						items.push( model.toJSON() );
					} );
					onSelectRef.current( items );
				} else {
					// `first()` is undefined on an empty selection. WP disables the
					// select button when nothing is chosen, so this is defensive
					// against custom frame states firing `select` while empty.
					var first = selection.first();
					if ( first ) {
						onSelectRef.current( first.toJSON() );
					}
				}
			} );
		}

		frameRef.current.open();
		return true;
	}, [] );
}

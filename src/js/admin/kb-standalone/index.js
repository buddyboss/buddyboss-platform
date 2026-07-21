/**
 * Standalone Knowledge Base mount.
 *
 * Mounts the shared KB modal (from @bb/admin-common) into its own React root
 * on any admin page, and exposes window.bbKb.open(options) / .close(). Reused
 * across products (Membership, Courses, …) — per-product scope travels through
 * open({ rootCategory }); nothing product-specific is stored here.
 *
 * @package BuddyBoss\Core\Administration
 * @since BuddyBoss [BBVERSION]
 */

import { createRoot, useEffect } from '@wordpress/element';
import { KbProvider, KnowledgeBaseModal, useKb } from '@bb/admin-common';

/**
 * Bridge that publishes the provider's open/close onto window.bbKb, then
 * renders the modal. Kept inside <KbProvider> so useKb() resolves.
 *
 * @since BuddyBoss [BBVERSION]
 * @return {React.Element} The modal.
 */
function StandaloneBridge() {
	const { open, close } = useKb();
	useEffect( () => {
		window.bbKb = window.bbKb || {};
		window.bbKb.open  = ( options = {} ) => open( { resetToLanding: true, ...options } );
		window.bbKb.close = close;

		// Drain a request queued before this effect ran. A page (e.g. the
		// Membership Support view) may call open on DOMContentLoaded, which can
		// fire before React flushes this passive effect — in that case the page
		// stashes the request on window.bbKbPendingOpen instead of no-opping.
		if ( window.bbKbPendingOpen ) {
			const pending = window.bbKbPendingOpen;
			window.bbKbPendingOpen = null;
			window.bbKb.open( pending );
		}
	}, [ open, close ] );
	return <KnowledgeBaseModal />;
}

// Single root — never mount twice (M3).
( function mountOnce() {
	if ( window.bbKbMounted ) {
		return;
	}
	window.bbKbMounted = true;
	const host = document.createElement( 'div' );
	host.id = 'bb-kb-standalone-root';
	// The shared KB modal styles are scoped under `.bb-admin-app` (see
	// styles/scss/_knowledge_base.scss). Settings 2.0 / Integrations mount
	// inside that shell; the standalone host must carry the class itself or
	// the modal renders unstyled (position: static — no fixed overlay).
	host.className = 'bb-admin-app';
	document.body.appendChild( host );
	createRoot( host ).render(
		<KbProvider>
			<StandaloneBridge />
		</KbProvider>
	);
}() );

<?php
/**
 * Pusher integration helpers
 *
 * @since   BuddyBoss 2.1.4
 *
 * @package BuddyBoss\Pusher
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Returns Pusher Integration url.
 *
 * @since BuddyBoss 2.1.4
 *
 * @param string $path Path to pusher integration.
 */
function bboss_pusher_integration_url( $path = '' ) {
	return trailingslashit( bboss()->integration_url ) . 'pusher/' . trim( $path, '/\\' );
}

// @bbwt-com-start compat-layer
// bbwt-rename:compat-aliases — the pre-rename names of this file's symbols (decision 0013): declared
// here, guarded on the renamed declaration, so each old name exists exactly when the new one does.
if ( function_exists( 'bboss_pusher_integration_url' ) && ! function_exists( 'bb_pusher_integration_url' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_pusher_integration_url()}.
	 *
	 * @deprecated Use bboss_pusher_integration_url() instead.
	 */
	function bb_pusher_integration_url( $path = '' ) {
		return bboss_pusher_integration_url( $path );
	}
}
// @bbwt-com-end compat-layer

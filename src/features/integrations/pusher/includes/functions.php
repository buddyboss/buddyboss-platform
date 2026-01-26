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
function bb_pusher_integration_url( $path = '' ) {
	// Get URL from integration object if available.
	if ( isset( buddypress()->integrations['pusher'] ) && isset( buddypress()->integrations['pusher']->url ) ) {
		return trailingslashit( buddypress()->integrations['pusher']->url ) . trim( $path, '/\\' );
	}

	// Fallback to features path.
	return trailingslashit( buddypress()->plugin_url ) . 'features/integrations/pusher/' . trim( $path, '/\\' );
}

<?php
/**
 * Pusher integration helpers
 *
 * @since   BuddyBoss [BBVERSION]
 *
 * @package BuddyBoss\Pusher
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Returns Pusher Integration url.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param string $path Path to pusher integration.
 */
function bb_pusher_integration_url( $path = '' ) {
	return trailingslashit( buddypress()->integration_url ) . 'pusher/' . trim( $path, '/\\' );
}

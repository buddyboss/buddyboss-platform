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

<?php
/**
 * Deprecated functions.
 *
 * @deprecated BuddyBoss 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Used by the Activity component's @mentions to print a JSON list of the current user's friends.
 *
 * This is intended to speed up @mentions lookups for a majority of use cases.
 *
 * @since BuddyPress 2.1.0
 * @deprecated BuddyBoss [BBVERSION]
 *
 * @see bp_activity_mentions_script()
 */
function bp_friends_prime_mentions_results() {
	_deprecated_function( __FUNCTION__, '1.8.6', 'bp_friends_prime_mentions_results' );

	return bb_core_prime_mentions_results();
}
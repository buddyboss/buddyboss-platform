<?php
/**
 * Deprecated functions.
 *
 * @since BuddyBoss [BBVERSION]
 * @deprecated BuddyBoss [BBVERSION]
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'bb_enable_group_subscriptions' ) ) {
	/**
	 * Is group subscription turned off?
	 *
	 * Group subscriptions moved to BuddyBoss Add-ons, which calls this function too.
	 * This copy loads first, so it keeps working for both.
	 *
	 * @since BuddyBoss 2.2.8
	 * @deprecated BuddyBoss [BBVERSION] Group subscriptions moved to BuddyBoss Add-ons.
	 *
	 * @param bool $default Optional. Fallback value if not found in the database.
	 *                      Default: true.
	 *
	 * @return bool True if group subscription is enabled, otherwise false.
	 */
	function bb_enable_group_subscriptions( $default = true ) {
		// Only a call without the Add-ons module is a deprecated one.
		if ( ! function_exists( 'bb_addons_group_subscription_init' ) ) {
			_deprecated_function( __FUNCTION__, 'BuddyBoss [BBVERSION]', 'BuddyBoss Add-ons' );
		}

		/**
		 * Filters whether group subscription is turned off.
		 *
		 * @since BuddyBoss 2.2.8
		 *
		 * @param bool $value Whether group subscription is turned off.
		 */
		return (bool) apply_filters( 'bb_enable_group_subscriptions', (bool) bp_get_option( 'bb_enable_group_subscriptions', $default ) );
	}
}

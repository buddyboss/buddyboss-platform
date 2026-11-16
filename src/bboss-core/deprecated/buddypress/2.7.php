<?php
/**
 * Deprecated functions.
 *
 * @deprecated BuddyPress 2.7.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Get the DB schema to use for BuddyPress components.
 *
 * @since BuddyPress 1.1.0
 * @deprecated BuddyPress 2.7.0
 *
 * @return string The default database character-set, if set.
 */
function bboss_core_set_charset() {
	global $wpdb;

	_deprecated_function( __FUNCTION__, '2.7', 'wpdb::get_charset_collate()' );

	require_once ABSPATH . 'wp-admin/includes/upgrade.php';
	return ! empty( $wpdb->charset ) ? "DEFAULT CHARACTER SET {$wpdb->charset}" : '';
}

// @bbwt-com-start compat-layer
// bbwt-rename:compat-aliases — the pre-rename names of this file's symbols (decision 0013): declared
// here, guarded on the renamed declaration, so each old name exists exactly when the new one does.
if ( function_exists( 'bboss_core_set_charset' ) && ! function_exists( 'bp_core_set_charset' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_core_set_charset()}.
	 *
	 * @deprecated Use bboss_core_set_charset() instead.
	 */
	function bp_core_set_charset() {
		return bboss_core_set_charset();
	}
}
// @bbwt-com-end compat-layer

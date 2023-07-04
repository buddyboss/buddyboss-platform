<?php
/**
 * Deprecated functions.
 *
 * @deprecated BuddyBoss [BBVERSION]
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Print moment.js config in page footer.
 *
 * Will be removed once we set our minimum version of WP 4.5.
 *
 * @since BuddyPress 2.7.0
 *
 * @access private
 */
function _bp_core_moment_js_config_footer() {
	_deprecated_function( __FUNCTION__, '[BBVERSION]' );
	if ( ! wp_script_is( 'bp-moment-locale' ) ) {
		return;
	}

	printf( '<script>%s</script>', bp_core_moment_js_config() );
}

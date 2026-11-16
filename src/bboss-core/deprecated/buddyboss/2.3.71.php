<?php
/**
 * Deprecated functions.
 *
 * @deprecated BuddyBoss 2.3.90
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
function bboss__bp_core_moment_js_config_footer() {
	_deprecated_function( __FUNCTION__, '2.3.90' );
	if ( ! wp_script_is( 'bp-moment-locale' ) ) {
		return;
	}

	$inline_js = bboss_core_moment_js_config();

	if ( function_exists( 'wp_print_inline_script_tag' ) ) {
		wp_print_inline_script_tag( $inline_js );
	} else {
		echo '<script>' . $inline_js . '</script>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- JSON configuration from wp_json_encode(). WordPress < 5.7 only.
	}
}
// @bbwt-com-start compat-layer
// bbwt-rename:compat-aliases — the pre-rename name of the function above (decision 0013): declared right after it, guarded on it, so the old name exists exactly when the new one does.
if ( function_exists( 'bboss__bp_core_moment_js_config_footer' ) && ! function_exists( '_bp_core_moment_js_config_footer' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss__bp_core_moment_js_config_footer()}.
	 *
	 * @deprecated Use bboss__bp_core_moment_js_config_footer() instead.
	 */
	function _bp_core_moment_js_config_footer() {
		return bboss__bp_core_moment_js_config_footer();
	}
}
// @bbwt-com-end compat-layer


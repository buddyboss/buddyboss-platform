<?php
/**
 * Deprecated functions.
 *
 * @deprecated BuddyBoss 2.4.50
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Return moment.js config.
 *
 * @since             BuddyPress 2.7.0
 * @deprecated        2.3.90 Softly deprecated as we're keeping the function into this file
 *                    to avoid fatal errors if deprecated code is ignored.
 *
 * @return string
 */
function bboss_core_moment_js_config() {
	_deprecated_function( __FUNCTION__, '2.3.90' );

	return '';
}
// @bbwt-com-start compat-layer
// bbwt-rename:compat-aliases — the pre-rename name of the function above (decision 0013): declared right after it, guarded on it, so the old name exists exactly when the new one does.
if ( function_exists( 'bboss_core_moment_js_config' ) && ! function_exists( 'bp_core_moment_js_config' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_core_moment_js_config()}.
	 *
	 * @deprecated Use bboss_core_moment_js_config() instead.
	 */
	function bp_core_moment_js_config() {
		return bboss_core_moment_js_config();
	}
}
// @bbwt-com-end compat-layer


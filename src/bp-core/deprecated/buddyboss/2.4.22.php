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
function bp_core_moment_js_config() {
	_deprecated_function( __FUNCTION__, '2.3.90' );

	return '';
}

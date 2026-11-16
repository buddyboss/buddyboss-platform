<?php
/**
 * Deprecated functions.
 *
 * @deprecated BuddyPress 2.3.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Return the referrer URL without the http(s)://
 *
 * @deprecated BuddyPress 2.3.0
 *
 * @return string The referrer URL.
 */
function bboss_core_referrer() {
	_deprecated_function( __FUNCTION__, '2.3.0', 'bp_get_referer_path()' );
	$referer = explode( '/', wp_get_referer() );
	unset( $referer[0], $referer[1], $referer[2] );
	return implode( '/', $referer );
}

// @bbwt-com-start compat-layer
// bbwt-rename:compat-aliases — the pre-rename names of this file's symbols (decision 0013): declared
// here, guarded on the renamed declaration, so each old name exists exactly when the new one does.
if ( function_exists( 'bboss_core_referrer' ) && ! function_exists( 'bp_core_referrer' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_core_referrer()}.
	 *
	 * @deprecated Use bboss_core_referrer() instead.
	 */
	function bp_core_referrer() {
		return bboss_core_referrer();
	}
}
// @bbwt-com-end compat-layer

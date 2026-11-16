<?php
/**
 * BuddyBoss Media Loader.
 *
 * A media component, Allow your users to upload photos and create albums.
 *
 * @package BuddyBoss\Media\Loader
 * @since BuddyBoss 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Set up the bp-media component.
 *
 * @since BuddyBoss 1.0.0
 */
function bboss_setup_media() {
	bboss()->media = new BBoss_Media_Component();
}
// @bbwt-com-start compat-layer
// bbwt-rename:compat-aliases — the pre-rename name of the function above (decision 0013): declared right after it, guarded on it, so the old name exists exactly when the new one does.
if ( function_exists( 'bboss_setup_media' ) && ! function_exists( 'bp_setup_media' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_setup_media()}.
	 *
	 * @deprecated Use bboss_setup_media() instead.
	 */
	function bp_setup_media() {
		return bboss_setup_media();
	}
}
// @bbwt-com-end compat-layer

add_action( 'bp_setup_components', 'bp_setup_media', 5 );

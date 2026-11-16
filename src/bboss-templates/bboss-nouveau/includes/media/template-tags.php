<?php
/**
 * Media Template tags
 *
 * @since BuddyBoss 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Before Media's directory content legacy do_action hooks wrapper
 *
 * @since BuddyBoss 1.0.0
 */
function bboss_nouveau_before_media_directory_content() {
	/**
	 * Fires at the begining of the templates BP injected content.
	 *
	 * @since BuddyBoss 1.0.0
	 */
	do_action( 'bp_before_directory_media' );

	/**
	 * Fires before the media directory display content.
	 *
	 * @since BuddyBoss 1.0.0
	 */
	do_action( 'bp_before_directory_media_content' );
}
// @bbwt-com-start compat-layer
// bbwt-rename:compat-aliases — the pre-rename name of the function above (decision 0013): declared right after it, guarded on it, so the old name exists exactly when the new one does.
if ( function_exists( 'bboss_nouveau_before_media_directory_content' ) && ! function_exists( 'bp_nouveau_before_media_directory_content' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_nouveau_before_media_directory_content()}.
	 *
	 * @deprecated Use bboss_nouveau_before_media_directory_content() instead.
	 */
	function bp_nouveau_before_media_directory_content() {
		return bboss_nouveau_before_media_directory_content();
	}
}
// @bbwt-com-end compat-layer


/**
 * After Media's directory content legacy do_action hooks wrapper
 *
 * @since BuddyBoss 1.0.0
 */
function bboss_nouveau_after_media_directory_content() {
	/**
	 * Fires after the display of the media list.
	 *
	 * @since BuddyBoss 1.0.0
	 */
	do_action( 'bp_after_directory_media_list' );

	/**
	 * Fires inside and displays the media directory display content.
	 */
	do_action( 'bp_directory_media_content' );

	/**
	 * Fires after the media directory display content.
	 *
	 * @since BuddyBoss 1.0.0
	 */
	do_action( 'bp_after_directory_media_content' );

	/**
	 * Fires after the media directory listing.
	 *
	 * @since BuddyBoss 1.0.0
	 */
	do_action( 'bp_after_directory_media' );

	//bp_get_template_part( 'common/js-templates/media/comments' );
}
// @bbwt-com-start compat-layer
// bbwt-rename:compat-aliases — the pre-rename name of the function above (decision 0013): declared right after it, guarded on it, so the old name exists exactly when the new one does.
if ( function_exists( 'bboss_nouveau_after_media_directory_content' ) && ! function_exists( 'bp_nouveau_after_media_directory_content' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_nouveau_after_media_directory_content()}.
	 *
	 * @deprecated Use bboss_nouveau_after_media_directory_content() instead.
	 */
	function bp_nouveau_after_media_directory_content() {
		return bboss_nouveau_after_media_directory_content();
	}
}
// @bbwt-com-end compat-layer


/**
 * Fire specific hooks into the media entry template
 *
 * @since BuddyBoss 1.0.0
 *
 * @param string $when   Optional. Either 'before' or 'after'.
 * @param string $suffix Optional. Use it to add terms at the end of the hook name.
 */
function bboss_nouveau_media_hook( $when = '', $suffix = '' ) {
	$hook = array( 'bp' );

	if ( $when ) {
		$hook[] = $when;
	}

	// It's an media entry hook
	$hook[] = 'media';

	if ( $suffix ) {
		$hook[] = $suffix;
	}

	bboss_nouveau_hook( $hook );
}
// @bbwt-com-start compat-layer
// bbwt-rename:compat-aliases — the pre-rename name of the function above (decision 0013): declared right after it, guarded on it, so the old name exists exactly when the new one does.
if ( function_exists( 'bboss_nouveau_media_hook' ) && ! function_exists( 'bp_nouveau_media_hook' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_nouveau_media_hook()}.
	 *
	 * @deprecated Use bboss_nouveau_media_hook() instead.
	 */
	function bp_nouveau_media_hook( $when = '', $suffix = '' ) {
		return bboss_nouveau_media_hook( $when, $suffix );
	}
}
// @bbwt-com-end compat-layer


/**
 * Output the Media timestamp into the bp-timestamp attribute.
 *
 * @since BuddyBoss 1.0.0
 */
function bboss_nouveau_media_timestamp() {
	echo esc_attr( bboss_nouveau_get_media_timestamp() );
}
// @bbwt-com-start compat-layer
// bbwt-rename:compat-aliases — the pre-rename name of the function above (decision 0013): declared right after it, guarded on it, so the old name exists exactly when the new one does.
if ( function_exists( 'bboss_nouveau_media_timestamp' ) && ! function_exists( 'bp_nouveau_media_timestamp' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_nouveau_media_timestamp()}.
	 *
	 * @deprecated Use bboss_nouveau_media_timestamp() instead.
	 */
	function bp_nouveau_media_timestamp() {
		return bboss_nouveau_media_timestamp();
	}
}
// @bbwt-com-end compat-layer


	/**
	 * Get the Media timestamp.
	 *
	 * @since BuddyBoss 1.0.0
	 *
	 * @return integer The Media timestamp.
	 */
	function bboss_nouveau_get_media_timestamp() {
		/**
		 * Filter here to edit the media timestamp.
		 *
		 * @since BuddyBoss 1.0.0
		 *
		 * @param integer $value The Media timestamp.
		 */
		return apply_filters( 'bp_nouveau_get_media_timestamp', strtotime( bboss_get_media_date_created() ) );
	}
	// @bbwt-com-start compat-layer
	// bbwt-rename:compat-aliases — the pre-rename name of the function above (decision 0013): declared right after it, guarded on it, so the old name exists exactly when the new one does.
	if ( function_exists( 'bboss_nouveau_get_media_timestamp' ) && ! function_exists( 'bp_nouveau_get_media_timestamp' ) ) {
		/**
		 * Backward-compatible alias of {@see bboss_nouveau_get_media_timestamp()}.
		 *
		 * @deprecated Use bboss_nouveau_get_media_timestamp() instead.
		 */
		function bp_nouveau_get_media_timestamp() {
			return bboss_nouveau_get_media_timestamp();
		}
	}
	// @bbwt-com-end compat-layer


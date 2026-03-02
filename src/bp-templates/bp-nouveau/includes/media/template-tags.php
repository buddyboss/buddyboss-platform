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
function bp_nouveau_before_media_directory_content() {
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

/**
 * After Media's directory content legacy do_action hooks wrapper
 *
 * @since BuddyBoss 1.0.0
 */
function bp_nouveau_after_media_directory_content() {
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

/**
 * Fire specific hooks into the media entry template
 *
 * @since BuddyBoss 1.0.0
 *
 * @param string $when   Optional. Either 'before' or 'after'.
 * @param string $suffix Optional. Use it to add terms at the end of the hook name.
 */
function bp_nouveau_media_hook( $when = '', $suffix = '' ) {
	$hook = array( 'bp' );

	if ( $when ) {
		$hook[] = $when;
	}

	// It's an media entry hook
	$hook[] = 'media';

	if ( $suffix ) {
		$hook[] = $suffix;
	}

	bp_nouveau_hook( $hook );
}

/**
 * Output the Media timestamp into the bp-timestamp attribute.
 *
 * @since BuddyBoss 1.0.0
 */
function bp_nouveau_media_timestamp() {
	echo esc_attr( bp_nouveau_get_media_timestamp() );
}

	/**
	 * Get the Media timestamp.
	 *
	 * @since BuddyBoss 1.0.0
	 *
	 * @return integer The Media timestamp.
	 */
	function bp_nouveau_get_media_timestamp() {
		/**
		 * Filter here to edit the media timestamp.
		 *
		 * @since BuddyBoss 1.0.0
		 *
		 * @param integer $value The Media timestamp.
		 */
		return apply_filters( 'bp_nouveau_get_media_timestamp', strtotime( bp_get_media_date_created() ) );
	}

<?php
/**
 * Document Template tags
 *
 * @since BuddyBoss 1.4.0
 * @package BuddyBoss\Core
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Before Document's directory content legacy do_action hooks wrapper
 *
 * @since BuddyBoss 1.4.0
 */
function bp_nouveau_before_document_directory_content() {
	/**
	 * Fires at the beginning of the templates BP injected content.
	 *
	 * @since BuddyBoss 1.4.0
	 */
	do_action( 'bp_before_directory_document' );

	/**
	 * Fires before the document directory display content.
	 *
	 * @since BuddyBoss 1.4.0
	 */
	do_action( 'bp_before_directory_document_content' );
}

/**
 * After Document's directory content legacy do_action hooks wrapper
 *
 * @since BuddyBoss 1.4.0
 */
function bp_nouveau_after_document_directory_content() {
	/**
	 * Fires after the display of the document list.
	 *
	 * @since BuddyBoss 1.4.0
	 */
	do_action( 'bp_after_directory_document_list' );

	/**
	 * Fires inside and displays the document directory display content.
	 */
	do_action( 'bp_directory_document_content' );

	/**
	 * Fires after the document directory display content.
	 *
	 * @since BuddyBoss 1.4.0
	 */
	do_action( 'bp_after_directory_document_content' );

	/**
	 * Fires after the document directory listing.
	 *
	 * @since BuddyBoss 1.4.0
	 */
	do_action( 'bp_after_directory_document' );

}

/**
 * Fire specific hooks into the document entry template
 *
 * @since BuddyBoss 1.4.0
 *
 * @param string $when   Optional. Either 'before' or 'after'.
 * @param string $suffix Optional. Use it to add terms at the end of the hook name.
 */
function bp_nouveau_document_hook( $when = '', $suffix = '' ) {
	$hook = array( 'bp' );

	if ( $when ) {
		$hook[] = $when;
	}

	// It's an media entry hook.
	$hook[] = 'document';

	if ( $suffix ) {
		$hook[] = $suffix;
	}

	bp_nouveau_hook( $hook );
}

/**
 * Output the Media timestamp into the bp-timestamp attribute.
 *
 * @since BuddyBoss 1.4.0
 */
function bp_nouveau_document_timestamp() {
	echo esc_attr( bp_nouveau_get_document_timestamp() );
}

/**
 * Get the Document timestamp.
 *
 * @since BuddyBoss 1.4.0
 *
 * @return integer The Document timestamp.
 */
function bp_nouveau_get_document_timestamp() {
	/**
	 * Filter here to edit the document timestamp.
	 *
	 * @since BuddyBoss 1.4.0
	 *
	 * @param integer $value The Document timestamp.
	 */
	return apply_filters( 'bp_nouveau_get_document_timestamp', strtotime( bp_get_document_date_created() ) );
}


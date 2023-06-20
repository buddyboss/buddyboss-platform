<?php
/**
 * Deprecated functions.
 *
 * @deprecated BuddyBoss 1.5.2
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Filter {@link get_avatar_url()} to use the BuddyPress user avatar URL.
 *
 * @since BuddyPress 2.9.0
 *
 * @param  string $retval      The URL of the avatar.
 * @param  mixed  $id_or_email The Gravatar to retrieve. Accepts a user_id, gravatar md5 hash,
 *                             user email, WP_User object, WP_Post object, or WP_Comment object.
 * @param  array  $args        Arguments passed to get_avatar_data(), after processing.
 * @return string
 */
function bp_core_get_avatar_data_url_filter( $retval, $id_or_email, $args ) {
	$user = null;

	_deprecated_function( __FUNCTION__, '1.5.2' );

	// Added this check for the display proper images in /wp-admin/options-discussion.php page Default Avatar page.
	global $pagenow;
	if ( 'options-discussion.php' === $pagenow ) {
		if ( true === $args['force_default'] ) {
			return $retval;
		}
	}

	// Ugh, hate duplicating code; process the user identifier.
	if ( is_numeric( $id_or_email ) ) {
		$user = get_user_by( 'id', absint( $id_or_email ) );
	} elseif ( $id_or_email instanceof WP_User ) {
		// User Object
		$user = $id_or_email;
	} elseif ( $id_or_email instanceof WP_Post ) {
		// Post Object
		$user = get_user_by( 'id', (int) $id_or_email->post_author );
	} elseif ( $id_or_email instanceof WP_Comment ) {
		if ( ! empty( $id_or_email->user_id ) ) {
			$user = get_user_by( 'id', (int) $id_or_email->user_id );
		}
	} elseif ( is_email( $id_or_email ) ) {
		$user = get_user_by( 'email', $id_or_email );
	}

	// No user, so bail.
	if ( false === $user instanceof WP_User ) {
		return $retval;
	}

	// Set BuddyPress-specific avatar args.
	$args['item_id'] = $user->ID;
	$args['html']    = false;

	// Use the 'full' type if size is larger than BP's thumb width.
	if ( (int) $args['size'] > bp_core_avatar_thumb_width() ) {
		$args['type'] = 'full';
	}

	// Get the BuddyPress avatar URL.
	if ( $bp_avatar = bp_core_fetch_avatar( $args ) ) {
		return $bp_avatar;
	}

	return $retval;
}


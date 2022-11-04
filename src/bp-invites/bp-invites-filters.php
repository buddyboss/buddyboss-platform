<?php
/**
 * BuddyBoss Invites Filters.
 *
 * @package BuddyBoss\Invites\Filters
 * @since BuddyPress 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

add_filter( 'bp_sent_invite_email_avatar', 'bb_sent_invite_email_avatar_default_avatar', 10 );


/**
 * Set default avatar when sent invite.
 *
 * @since BuddyBoss 1.8.6
 *
 * @param string $avatar Default avatar.
 * @return string The default avatar URL based on backend setting.
 */
function bb_sent_invite_email_avatar_default_avatar( $avatar = '' ) {

	if ( empty( $avatar ) ) {
		$show_avatar                 = bp_get_option( 'show_avatars' );
		$profile_avatar_type         = bb_get_profile_avatar_type();
		$default_profile_avatar_type = bb_get_default_profile_avatar_type();

		if ( $show_avatar && 'WordPress' === $profile_avatar_type && 'blank' !== bp_get_option( 'avatar_default', 'mystery' ) ) {
			$avatar = get_avatar_url(
				'',
				array(
					'size' => 300,
				)
			);
		}
	}

	return $avatar;
}

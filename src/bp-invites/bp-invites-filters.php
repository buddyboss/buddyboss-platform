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

/**
 * Clear cache after send invites/revoke invites.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param int $user_id User ID.
 * @param int $post_id Post ID.
 */
function bb_member_invitation_count_clear_cache( $user_id, $post_id ) {
	wp_cache_delete( 'bb_get_total_invitation_count_' . $user_id, 'bp_invites' );
}
add_action( 'bp_member_invite_submit', 'bb_member_invitation_count_clear_cache', 10, 2 );

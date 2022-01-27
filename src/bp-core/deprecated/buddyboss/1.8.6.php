<?php
/**
 * Deprecated functions.
 *
 * @deprecated BuddyBoss 1.8.6
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Adds CSS to remove the Default Avatar settings from /wp-admin/options-discussion.php page.
 * These settings cannot be used with BuddyBoss, as we load custom avatars instead of gravatar.
 *
 * @since BuddyBoss 1.0.0
 */
function bp_remove_avatar_settings_from_options_discussion_page() {

	_deprecated_function( __FUNCTION__, '1.8.6' );

	global $pagenow;

	if ( 'options-discussion.php' === $pagenow ) {

		?>
		<style>
			body.options-discussion-php #wpbody-content .wrap form table:nth-last-child(2) tbody tr:last-child {
				display: none !important;
			}

			body.options-discussion-php #wpbody-content .wrap h2.title, body.options-discussion-php #wpbody-content .wrap h2.title + p, body.options-discussion-php #wpbody-content .wrap h2.title + p + table {
				display: none !important;
			}
		</style>
		<?php

	}

}

/**
 * Use the mystery group avatar for groups.
 *
 * @since BuddyPress 2.6.0
 *
 * @param string $avatar Current avatar src.
 * @param array  $params Avatar params.
 * @return string
 */
function bp_groups_default_avatar( $avatar, $params ) {

	_deprecated_function( __FUNCTION__, '1.8.6', 'bb_attachments_get_default_profile_group_avatar_image' );

	$params['object'] = 'group';
	return bb_attachments_get_default_profile_group_avatar_image( $params );
}

/**
 * Used by the Activity component's @mentions to print a JSON list of the current user's friends.
 *
 * This is intended to speed up @mentions lookups for a majority of use cases.
 *
 * @since BuddyPress 2.1.0
 * @deprecated BuddyBoss 1.8.6
 *
 * @see bp_activity_mentions_script()
 */
function bp_friends_prime_mentions_results() {
	_deprecated_function( __FUNCTION__, '1.8.6', 'bp_friends_prime_mentions_results' );

	return bb_core_prime_mentions_results();
}

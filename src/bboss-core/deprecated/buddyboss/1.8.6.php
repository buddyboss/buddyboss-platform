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
function bboss_remove_avatar_settings_from_options_discussion_page() {

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
function bboss_groups_default_avatar( $avatar, $params ) {

	_deprecated_function( __FUNCTION__, '1.8.6', 'bboss_attachments_get_default_profile_group_avatar_image' );

	$params['object'] = 'group';
	return bboss_attachments_get_default_profile_group_avatar_image( $params );
}

/**
 * Used by the Activity component's @mentions to print a JSON list of the current user's friends.
 *
 * This is intended to speed up @mentions lookups for a majority of use cases.
 *
 * @since BuddyPress 2.1.0
 * @deprecated BuddyBoss 1.8.6
 *
 * @see bboss_activity_mentions_script()
 */
function bboss_friends_prime_mentions_results() {
	_deprecated_function( __FUNCTION__, '1.8.6', 'bboss_friends_prime_mentions_results' );

	return bboss_core_prime_mentions_results();
}

// @bbwt-com-start compat-layer
// bbwt-rename:compat-aliases — the pre-rename names of this file's symbols (decision 0013): declared
// here, guarded on the renamed declaration, so each old name exists exactly when the new one does.
if ( function_exists( 'bboss_friends_prime_mentions_results' ) && ! function_exists( 'bp_friends_prime_mentions_results' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_friends_prime_mentions_results()}.
	 *
	 * @deprecated Use bboss_friends_prime_mentions_results() instead.
	 */
	function bp_friends_prime_mentions_results() {
		return bboss_friends_prime_mentions_results();
	}
}
if ( function_exists( 'bboss_groups_default_avatar' ) && ! function_exists( 'bp_groups_default_avatar' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_groups_default_avatar()}.
	 *
	 * @deprecated Use bboss_groups_default_avatar() instead.
	 */
	function bp_groups_default_avatar( $avatar, $params ) {
		return bboss_groups_default_avatar( $avatar, $params );
	}
}
if ( function_exists( 'bboss_remove_avatar_settings_from_options_discussion_page' ) && ! function_exists( 'bp_remove_avatar_settings_from_options_discussion_page' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_remove_avatar_settings_from_options_discussion_page()}.
	 *
	 * @deprecated Use bboss_remove_avatar_settings_from_options_discussion_page() instead.
	 */
	function bp_remove_avatar_settings_from_options_discussion_page() {
		return bboss_remove_avatar_settings_from_options_discussion_page();
	}
}
// @bbwt-com-end compat-layer

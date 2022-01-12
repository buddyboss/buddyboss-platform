<?php
/**
 * Deprecated functions.
 *
 * @deprecated BuddyBoss [BBVERSION]
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

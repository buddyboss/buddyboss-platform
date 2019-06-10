<?php

/**
 * Forums Localization
 *
 * @package BuddyBoss\Localization
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * Translates role name.
 *
 * Since the role names are in the database and not in the source there
 * are dummy gettext calls to get them into the POT file and this function
 * properly translates them back.
 *
 * The before_last_bar() call is needed, because older installs keep the roles
 * using the old context format: 'Role name|User role' and just skipping the
 * content after the last bar is easier than fixing them in the DB. New installs
 * won't suffer from that problem.
 *
 * @see translate_user_role()
 *
 * @since BuddyPress 2.6.0 Forums
 *
 * @param string $name The role name.
 * @return string Translated role name on success, original name on failure.
 */
function bbp_translate_user_role( $name ) {
	return translate_with_gettext_context( before_last_bar( $name ), 'User role', 'buddyboss' );
}

/**
 * Dummy gettext calls to get strings in the catalog.
 *
 * @since BuddyPress 2.6.0 Forums
 */
function bbp_dummy_role_names() {

	/* translators: user role */
	__( 'Organizer', 'buddyboss' );

	/* translators: user role */
	__( 'Moderator', 'buddyboss' );

	/* translators: user role */
	__( 'Participant', 'buddyboss' );

	/* translators: user role */
	__( 'Spectator', 'buddyboss' );

	/* translators: user role */
	__( 'Blocked', 'buddyboss' );
}

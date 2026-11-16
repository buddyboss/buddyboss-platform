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
function bboss_bbp_translate_user_role( $name ) {
	return translate_with_gettext_context( before_last_bar( $name ), 'User role', 'buddyboss' ); // phpcs:ignore WordPress.WP.I18n.LowLevelTranslationFunction,WordPress.WP.I18n.NonSingularStringLiteralText -- mirrors core's translate_user_role() for the plugin's own role names (registered as translatable strings where the roles are defined).
}

/**
 * Dummy gettext calls to get strings in the catalog.
 *
 * @since BuddyPress 2.6.0 Forums
 */
function bboss_bbp_dummy_role_names() {

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

// @bbwt-com-start compat-layer
// bbwt-rename:compat-aliases — the pre-rename names of this file's symbols (decision 0013): declared
// here, guarded on the renamed declaration, so each old name exists exactly when the new one does.
if ( function_exists( 'bboss_bbp_dummy_role_names' ) && ! function_exists( 'bbp_dummy_role_names' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_bbp_dummy_role_names()}.
	 *
	 * @deprecated Use bboss_bbp_dummy_role_names() instead.
	 */
	function bbp_dummy_role_names() {
		return bboss_bbp_dummy_role_names();
	}
}
if ( function_exists( 'bboss_bbp_translate_user_role' ) && ! function_exists( 'bbp_translate_user_role' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_bbp_translate_user_role()}.
	 *
	 * @deprecated Use bboss_bbp_translate_user_role() instead.
	 */
	function bbp_translate_user_role( $name ) {
		return bboss_bbp_translate_user_role( $name );
	}
}
// @bbwt-com-end compat-layer

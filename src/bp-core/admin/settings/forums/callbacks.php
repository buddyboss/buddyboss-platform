<?php
/**
 * BuddyBoss Admin Settings - Forums Callbacks.
 *
 * Post-save callback functions for Forums feature settings.
 *
 * @package BuddyBoss\Core\Administration
 * @since BuddyBoss [BBVERSION]
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Flush rewrite rules after forum settings are saved.
 *
 * Legacy settings_save() always calls flush_rewrite_rules() because
 * permalink slugs may have changed. This preserves that behavior.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param string $feature_id Feature ID.
 * @param array  $settings   Full submitted settings.
 * @param array  $saved      Keys and values saved by core.
 */
function bb_forums_after_save_settings( $feature_id, $settings, $saved ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
	if ( 'forums' !== $feature_id ) {
		return;
	}

	// Flush rewrite rules — critical for permalink slug changes.
	flush_rewrite_rules();

	/**
	 * Fires after forum settings are saved via Settings 2.0.
	 *
	 * Used internally to trigger deprecated legacy hooks
	 * (`bp_admin_tab_setting_save`, `bp_admin_tab_setting_saved`)
	 * via wrappers in the deprecation file.
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	do_action( 'bb_forums_after_save_settings' );
}

add_action( 'bb_admin_save_feature_settings_after', 'bb_forums_after_save_settings', 10, 3 );

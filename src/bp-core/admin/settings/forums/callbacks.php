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
 * Flush rewrite rules and fire deprecated hook after forum settings are saved.
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
	 * Fires after forum settings are saved (legacy backward compatibility).
	 *
	 * Previously fired as `bp_admin_setting_forums_register_fields` on the
	 * legacy BP_Admin_Setting_Forums class. Third-party plugins may hook here.
	 *
	 * @since BuddyBoss 1.2.6
	 * @deprecated BuddyBoss [BBVERSION] Use {@see 'bb_admin_save_feature_settings_after'} instead.
	 */
	do_action_deprecated(
		'bp_admin_setting_forums_register_fields',
		array( null ),
		'BuddyBoss [BBVERSION]',
		'bb_admin_save_feature_settings_after'
	);
}

add_action( 'bb_admin_save_feature_settings_after', 'bb_forums_after_save_settings', 10, 3 );

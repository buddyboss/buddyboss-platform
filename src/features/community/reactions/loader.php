<?php
/**
 * Reactions Feature Loader.
 *
 * Loads the Reactions feature when it is active.
 *
 * @package BuddyBoss\Features\Community\Reactions
 * @since BuddyBoss [BBVERSION]
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Initialize the Reactions feature UI components.
 *
 * Note: Core reaction functions are loaded in feature-config.php to ensure
 * they're always available when activity is active (required by BP_Activity_Template).
 * This function only loads additional UI components when the feature toggle is on.
 *
 * @since BuddyBoss [BBVERSION]
 */
function bb_reactions_feature_init() {
	// Reactions require activity component.
	if ( ! bp_is_active( 'activity' ) ) {
		return;
	}

	// Core files are already loaded in feature-config.php.
	// No additional loading needed here for now.
	// In the future, feature-specific UI components can be loaded here.

	// Register admin settings (admin only).
	if ( is_admin() ) {
		add_action( 'bp_admin_init', 'bb_reactions_register_admin_settings', 20 );
	}
}
add_action( 'bp_loaded', 'bb_reactions_feature_init', 5 );

/**
 * Register Reactions admin settings.
 *
 * @since BuddyBoss [BBVERSION]
 */
function bb_reactions_register_admin_settings() {
	// Configuration is loaded directly in feature-config.php
	// to ensure settings are available even when feature is inactive.

	// Only load legacy settings tab if Settings 2.0 is NOT active.
	$is_settings_2_0_active = function_exists( 'bb_feature_registry' );

	if ( ! $is_settings_2_0_active ) {
		// Load legacy admin settings class for backward compatibility.
		require_once __DIR__ . '/admin/settings.php';

		// Register the legacy settings tab.
		new BB_Admin_Setting_Reactions( 'bp-reactions' );
	}
}

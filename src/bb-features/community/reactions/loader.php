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
 * Note: Core reaction functions are loaded in bb-feature-config.php to ensure
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

	// Core files are already loaded in bb-feature-config.php.
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
	// Settings 2.0 configuration (side panels, sections, fields) is loaded
	// in bb-feature-config.php to ensure settings are registered even when
	// the feature is inactive (needed to show the settings page).
	// No additional admin settings loading needed here.
}

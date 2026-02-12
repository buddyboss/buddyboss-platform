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
 * Note: Core reaction classes and functions are loaded in bb-feature-config.php
 * to ensure they're always available when activity is active (required by
 * BP_Activity_Template). This function only loads additional UI components
 * when the feature toggle is on.
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
}
add_action( 'bp_loaded', 'bb_reactions_feature_init', 5 );

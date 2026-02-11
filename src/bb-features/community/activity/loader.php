<?php
/**
 * Activity Feature Loader.
 *
 * Loads the Activity feature when it is active.
 *
 * @package BuddyBoss\Features\Community\Activity
 * @since BuddyBoss [BBVERSION]
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Initialize the Activity feature UI components.
 *
 * Core activity functions are loaded separately via bp-activity component.
 * This loader only loads additional UI/admin components when the feature toggle is on.
 *
 * @since BuddyBoss [BBVERSION]
 */
function bb_activity_feature_init() {
	// Activity requires the activity component.
	if ( ! bp_is_active( 'activity' ) ) {
		return;
	}

	// Core files are already loaded via bp-activity component.
	// No additional loading needed here for now.
	// In the future, feature-specific UI components can be loaded here.
}
add_action( 'bp_loaded', 'bb_activity_feature_init', 5 );

<?php
/**
 * BuddyBoss LearnDash Loader.
 *
 * @package BuddyBoss\LearnDash
 * @since BuddyBoss 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Set up the bp learndash integration.
 *
 * @since BuddyBoss 1.0.0
 */
function bp_register_learndash_integration() {
	require_once dirname( __FILE__ ) . '/bp-learndash-integration.php';
	buddypress()->integrations['learndash'] = new BP_Learndash_Integration();
}
add_action( 'bp_setup_integrations', 'bp_register_learndash_integration' );

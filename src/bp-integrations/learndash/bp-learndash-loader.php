<?php
/**
 * BuddyBoss Learndash Loader.
 *
 * @package BuddyBoss
 * @subpackage Learndash
 * @since BuddyBoss 3.1.1
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Set up the bp learndash integration.
 *
 * @since Buddyboss 3.1.1
 */
function bp_register_learndash_integration() {
	require_once dirname( __FILE__ ) . '/classes/bp-learndash-integration.php';
	buddypress()->integrations['learndash'] = new BP_Learndash_Integration;
}
add_action( 'bp_setup_integrations', 'bp_register_learndash_integration' );

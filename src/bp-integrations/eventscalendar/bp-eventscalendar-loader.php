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
 * Set up the bp eventscalendar integration.
 *
 * @since Buddyboss 3.1.1
 */
function bp_register_eventscalendar_integration() {
	require_once dirname( __FILE__ ) . '/admin/bp-admin-eventscalendar-tab.php';
}
add_action( 'bp_register_admin_integrations', 'bp_register_eventscalendar_integration' );

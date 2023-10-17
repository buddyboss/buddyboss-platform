<?php
/**
 * BuddyBoss TutorLMS Loader.
 *
 * @package BuddyBoss\TutorLMS
 * @since BuddyBoss 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Set up the bp TutorLMS integration.
 *
 * @since BuddyBoss [BBVERSION]
 */
function bb_register_tutorlms_integration() {
	require_once dirname( __FILE__ ) . '/bp-tutorlms-integration.php';
	buddypress()->integrations['tutorlms'] = new BB_TutorLMS_Integration();
}
add_action( 'bp_setup_integrations', 'bb_register_tutorlms_integration' );

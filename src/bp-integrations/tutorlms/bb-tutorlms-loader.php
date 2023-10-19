<?php
/**
 * BuddyBoss TutorLMS Integration Loader.
 *
 * @package BuddyBoss\TutorLMS
 *
 * @since BuddyBoss [BBVERSION]
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Set up the BB TutorLMS integration.
 *
 * @since BuddyBoss [BBVERSION]
 */
function bb_register_tutorlms_integration() {
	require_once dirname( __FILE__ ) . '/bb-tutorlms-integration.php';
	buddypress()->integrations['tutorlms'] = new BB_TutorLMS_Integration();
}
add_action( 'bp_setup_integrations', 'bb_register_tutorlms_integration', 20 );

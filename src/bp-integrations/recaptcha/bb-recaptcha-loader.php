<?php
/**
 * BuddyBoss Recaptcha Integration Loader.
 *
 * @package BuddyBoss\Recaptcha
 *
 * @since BuddyBoss [BBVERSION]
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Set up the BB Recaptcha integration.
 *
 * @since BuddyBoss [BBVERSION]
 */
function bb_register_recaptcha_integration() {
	require_once dirname( __FILE__ ) . '/bb-recaptcha-integration.php';
	buddypress()->integrations['recaptcha'] = new BB_Recaptcha_Integration();
}
add_action( 'bp_setup_integrations', 'bb_register_recaptcha_integration', 20 );
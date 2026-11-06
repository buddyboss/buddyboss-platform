<?php
/**
 * BuddyBoss Recaptcha Integration Loader.
 *
 * @package BuddyBoss\Recaptcha
 *
 * @since BuddyBoss 2.5.60
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Set up the BB Recaptcha integration.
 *
 * @since BuddyBoss 2.5.60
 */
function bboss_register_recaptcha_integration() {
	require_once dirname( __FILE__ ) . '/classes/class-bb-recaptcha-integration.php';
	bboss()->integrations['recaptcha'] = new BBoss_Recaptcha_Integration();
}
add_action( 'bp_setup_integrations', 'bboss_register_recaptcha_integration', 20 );

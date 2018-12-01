<?php
/**
 * BuddyPress Tokens for email.
 *
 * @package BuddyBoss
 * @subpackage Core
 * @since BuddyBoss 3.1.1
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;
/**
 * Set up the bp-core-email-tokens component.
 *
 * @since BuddyBoss 3.1.1
 */
function bp_setup_core_email_tokens() {
	new BP_Email_Tokens();
}
add_action( 'bp_init', 'bp_setup_core_email_tokens', 0 );

/**
 * All generic email notifications for the WP
 */
function bp_email_set_content_type(){
	return "text/html";
}
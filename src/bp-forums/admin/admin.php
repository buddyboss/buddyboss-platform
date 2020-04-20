<?php

/**
 * Main Forums Admin Class
 *
 * @package BuddyBoss\Administration
 * @since bbPress (r2464)
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'BBP_Admin' ) ) :
	/**
	 * Loads Forums plugin admin area
	 *
	 * @since bbPress (r2464)
	 */
	class BBP_Admin {
			}
endif; // class_exists check

/**
 * Setup Forums Admin
 *
 * @since bbPress (r2596)
 *
 * @uses BBP_Admin
 */
function bbp_admin() {
	bbpress()->admin = new BBP_Admin();

	bbpress()->admin->converter = new BBP_Converter();
}

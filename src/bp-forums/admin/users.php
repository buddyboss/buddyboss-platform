<?php

/**
 * Forums Users Admin Class
 *
 * @package BuddyBoss
 * @subpackage Administration
 */

// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

if ( !class_exists( 'BBP_Users_Admin' ) ) :
/**
 * Loads Forums users admin area
 *
 * @package BuddyBoss
 * @subpackage Administration
 * @since bbPress (r2464)
 */
class BBP_Users_Admin {

	/**
	 * The Forums users admin loader
	 *
	 * @since bbPress (r2515)
	 *
	 * @uses BBP_Users_Admin::setup_globals() Setup the globals needed
	 * @uses BBP_Users_Admin::setup_actions() Setup the hooks and actions
	 */
	public function __construct() {
		$this->setup_actions();
	}

	/**
	 * Setup the admin hooks, actions and filters
	 *
	 * @since bbPress (r2646)
	 * @access private
	 *
	 * @uses add_action() To add various actions
	 */
	function setup_actions() {

		// Bail if in network admin
		if ( is_network_admin() )
			return;

		// Remove bbp secondary role
		add_filter( 'get_role_list', array( $this, 'remove_forum_roles' ), 15, 2 );
	}

	/**
	 * Remove bbp's dynamic roles from user role list
	 *
	 * @since BuddyBoss 1.0.0
	 */
	public function remove_forum_roles( $role_list, $user_object ) {
		$bbp_roles = array_map( '__return_zero', bbp_get_dynamic_roles() );

		return array_diff_key( $role_list, $bbp_roles );
	}
}
new BBP_Users_Admin();
endif; // class exists

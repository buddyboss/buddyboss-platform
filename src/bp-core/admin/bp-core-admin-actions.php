<?php
/**
 * BuddyPress Admin Actions.
 *
 * This file contains the actions that are used through-out BuddyPress Admin. They
 * are consolidated here to make searching for them easier, and to help developers
 * understand at a glance the order in which things occur.
 *
 * There are a few common places that additional actions can currently be found.
 *
 *  - BuddyPress: In {@link BuddyPress::setup_actions()} in BuddyPress.php
 *  - Admin: More in {@link bp_Admin::setup_actions()} in admin.php
 *
 * @package BuddyBoss\Admin
 * @since BuddyPress 2.3.0
 * @see bp-core-actions.php
 * @see bp-core-filters.php
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Attach BuddyPress to WordPress.
 *
 * BuddyPress uses its own internal actions to help aid in third-party plugin
 * development, and to limit the amount of potential future code changes when
 * updates to WordPress core occur.
 *
 * These actions exist to create the concept of 'plugin dependencies'. They
 * provide a safe way for plugins to execute code *only* when BuddyPress is
 * installed and activated, without needing to do complicated guesswork.
 *
 * For more information on how this works, see the 'Plugin Dependency' section
 * near the bottom of this file.
 *
 *          v--WordPress Actions       v--BuddyPress Sub-actions
 */
add_action( 'admin_menu', 'bp_admin_menu' );
add_action( 'admin_init', 'bp_admin_init' );
add_action( 'admin_head', 'bp_admin_head' );
add_action( 'admin_notices', 'bp_admin_notices' );
add_action( 'admin_enqueue_scripts', 'bp_admin_enqueue_scripts' );
add_action( 'customize_controls_enqueue_scripts', 'bp_admin_enqueue_scripts', 8 );
add_action( 'network_admin_menu', 'bp_admin_menu' );
add_action( 'custom_menu_order', 'bp_admin_custom_menu_order' );
add_action( 'menu_order', 'bp_admin_menu_order' );
add_action( 'wpmu_new_blog', 'bp_new_site', 10, 6 );

// Hook on to admin_init.
add_action( 'bp_admin_init', 'bp_setup_updater', 1000 );
add_action( 'bp_admin_init', 'bp_core_activation_notice', 1010 );
add_action( 'bp_admin_init', 'bp_register_importers' );
add_action( 'bp_admin_init', 'bp_register_admin_style' );
add_action( 'bp_admin_init', 'bp_register_admin_settings' );
add_action( 'bp_admin_init', 'bp_register_admin_integrations' );
add_action( 'bp_admin_init', 'bp_do_activation_redirect', 1 );
add_action( 'bp_admin_init', 'bp_check_for_legacy_theme' );

// Show notice when Profile Avatars is BuddyBoss.
add_action( 'bp_admin_head', 'bb_discussion_page_show_notice_in_avatar_section' );

// Add a new separator.
add_action( 'bp_admin_menu', 'bp_admin_separator' );

// Check user nickname on backend user edit page.
add_action( 'user_profile_update_errors', 'bb_check_user_nickname', 10, 3 );

// Validate if email address is allowed or blacklisted.
add_action( 'user_profile_update_errors', 'bb_validate_restricted_email_on_registration', PHP_INT_MAX, 3 );
add_action( 'personal_options_update', 'bb_validate_restricted_email_on_profile_update', 1 ); // Edit the login user profile from backend.
add_action( 'edit_user_profile_update', 'bb_validate_restricted_email_on_profile_update', 1 ); // Edit other users profile from backend.

/**
 * When a new site is created in a multisite installation, run the activation
 * routine on that site.
 *
 * @since BuddyPress 1.7.0
 *
 * @param int    $blog_id ID of the blog being installed to.
 * @param int    $user_id ID of the user the install is for.
 * @param string $domain  Domain to use with the install.
 * @param string $path    Path to use with the install.
 * @param int    $site_id ID of the site being installed to.
 * @param array  $meta    Metadata to use with the site creation.
 */
function bp_new_site( $blog_id, $user_id, $domain, $path, $site_id, $meta ) {

	// Bail if plugin is not network activated.
	if ( ! is_plugin_active_for_network( buddypress()->basename ) ) {
		return;
	}

	// Switch to the new blog.
	switch_to_blog( $blog_id );

	/**
	 * Fires the activation routine for a new site created in a multisite installation.
	 *
	 * @since BuddyPress 1.7.0
	 *
	 * @param int    $blog_id ID of the blog being installed to.
	 * @param int    $user_id ID of the user the install is for.
	 * @param string $domain  Domain to use with the install.
	 * @param string $path    Path to use with the install.
	 * @param int    $site_id ID of the site being installed to.
	 * @param array  $meta    Metadata to use with the site creation.
	 */
	do_action( 'bp_new_site', $blog_id, $user_id, $domain, $path, $site_id, $meta );

	// Restore original blog.
	restore_current_blog();
}

/** Sub-Actions ***************************************************************/

/**
 * Piggy back admin_init action.
 *
 * @since BuddyPress 1.7.0
 */
function bp_admin_init() {

	/**
	 * Fires inside the bp_admin_init function.
	 *
	 * @since BuddyPress 1.6.0
	 */
	do_action( 'bp_admin_init' );
}

/**
 * Piggy back admin_menu action.
 *
 * @since BuddyPress 1.7.0
 */
function bp_admin_menu() {

	/**
	 * Fires inside the bp_admin_menu function.
	 *
	 * @since BuddyPress 1.7.0
	 */
	do_action( 'bp_admin_menu' );
}

/**
 * Piggy back admin_head action.
 *
 * @since BuddyPress 1.7.0
 */
function bp_admin_head() {

	/**
	 * Fires inside the bp_admin_head function.
	 *
	 * @since BuddyPress 1.6.0
	 */
	do_action( 'bp_admin_head' );
}

/**
 * Piggy back admin_notices action.
 *
 * @since BuddyPress 1.7.0
 */
function bp_admin_notices() {

	/**
	 * Fires inside the bp_admin_notices function.
	 *
	 * @since BuddyPress 1.5.0
	 */
	do_action( 'bp_admin_notices' );
}

/**
 * Piggy back admin_enqueue_scripts action.
 *
 * @since BuddyPress 1.7.0
 *
 * @param string $hook_suffix The current admin page, passed to
 *                            'admin_enqueue_scripts'.
 */
function bp_admin_enqueue_scripts( $hook_suffix = '' ) {

	/**
	 * Fires inside the bp_admin_enqueue_scripts function.
	 *
	 * @since BuddyPress 1.7.0
	 *
	 * @param string $hook_suffix The current admin page, passed to admin_enqueue_scripts.
	 */
	do_action( 'bp_admin_enqueue_scripts', $hook_suffix );
}

/**
 * Dedicated action to register BuddyPress importers.
 *
 * @since BuddyPress 1.7.0
 */
function bp_register_importers() {

	/**
	 * Fires inside the bp_register_importers function.
	 *
	 * Used to register a BuddyPress importer.
	 *
	 * @since BuddyPress 1.7.0
	 */
	do_action( 'bp_register_importers' );
}

/**
 * Dedicated action to register admin styles.
 *
 * @since BuddyPress 1.7.0
 */
function bp_register_admin_style() {

	/**
	 * Fires inside the bp_register_admin_style function.
	 *
	 * @since BuddyPress 1.7.0
	 */
	do_action( 'bp_register_admin_style' );
}

/**
 * Dedicated action to register admin settings.
 *
 * @since BuddyPress 1.7.0
 */
function bp_register_admin_settings() {

	/**
	 * Fires inside the bp_register_admin_settings function.
	 *
	 * @since BuddyPress 1.6.0
	 */
	do_action( 'bp_register_admin_settings' );
}

/**
 * Dedicated action to register admin integrations.
 *
 * @since BuddyBoss 1.0.0
 */
function bp_register_admin_integrations() {

	/**
	 * Fires inside the bp_register_admin_integrations function.
	 *
	 * @since BuddyPress 1.6.0
	 */
	do_action( 'bp_register_admin_integrations' );
}

/**
 * Check user nickname is already taken or not.
 *
 * @since BuddyBoss 1.6.0
 *
 * @param object $errors error object.
 * @param bool   $update updating user or adding user.
 * @param object $user   user data.
 */
function bb_check_user_nickname( &$errors, $update, &$user ) {
	global $wpdb;

	// Check user unique identifier exist.
	$check_exists = $wpdb->get_var( // phpcs:ignore
		$wpdb->prepare(
			"SELECT count(*) FROM {$wpdb->usermeta} WHERE meta_key = %s AND meta_value = %s",
			'bb_profile_slug',
			$user->user_login
		)
	);

	if ( $check_exists > 0 ) {
		return $errors->add( 'invalid_nickname', __( 'Invalid Nickname', 'buddyboss' ), array( 'form-field' => 'nickname' ) );
	}

	$un_name = ( ! empty( $user->nickname ) ) ? $user->nickname : $user->user_login;

	$where = array(
		'meta_key = "nickname"',
		'meta_value = "' . $un_name . '"',
	);

	if ( ! empty( $user->ID ) ) {
		$where[] = 'user_id != ' . $user->ID;
	}

	$sql = sprintf(
		'SELECT count(*) FROM %s WHERE %s',
		$wpdb->usermeta,
		implode( ' AND ', $where )
	);

	if ( $wpdb->get_var( $sql ) > 0 ) {
		$errors->add( 'nickname_exists', __( '<strong>Error</strong>: Nickname already has been taken. Please try again.', 'buddyboss' ), array( 'form-field' => 'nickname' ) );
	}
}

/**
 * Wrapper function to check GIPHY key is valid or not.
 *
 * @since BuddyBoss 2.1.2
 */
function bb_admin_check_valid_giphy_key() {
	$response = array(
		'code'    => 403,
		'message' => esc_html__( 'There was a problem performing this action. Please try again.', 'buddyboss' ),
	);

	$key = filter_input( INPUT_POST, 'key', FILTER_DEFAULT );

	if ( empty( $key ) ) {
		wp_send_json_error( $response );
	}

	// Bail if not a POST action.
	if ( ! bp_is_post_request() ) {
		wp_send_json_error( $response );
	}

	if ( ! bp_is_active( 'media' )  ) {
		wp_send_json_error( $response );
	}

	if ( empty( $_POST['nonce'] ) ) {
		wp_send_json_error( $response );
	}

	// Use default nonce.
	$nonce = filter_input( INPUT_POST, 'nonce', FILTER_DEFAULT );
	$check = 'bb-giphy-connect';

	// Nonce check!
	if ( empty( $nonce ) || ! wp_verify_nonce( $nonce, $check ) ) {
		wp_send_json_error( $response );
	}

	$result = bb_check_valid_giphy_api_key( $key, true );

	if ( $result ) {
		wp_send_json_success( $result['response'] );
	}

	wp_send_json_error( $response );

}
add_action( 'wp_ajax_bb_admin_check_valid_giphy_key', 'bb_admin_check_valid_giphy_key' );

/**
 * Validate the email address allowed to register as per the registration restriction settings.
 *
 * @since BuddyBoss 2.4.11
 *
 * @param object $errors error object.
 * @param bool   $update updating user or adding user.
 * @param object $user   user data.
 *
 * @return object
 */
function bb_validate_restricted_email_on_registration( $errors, $update, $user ) {

	// Check if email address allowed.
	if ( ! bb_is_allowed_register_email_address( $user->user_email ) ) {
		if ( $update ) {
			$old_user_data = get_userdata( $user->ID );

			// Allow already saved emails.
			if ( $old_user_data->user_email === $user->user_email ) {
				return $errors;
			}
		}
		$errors->add( 'bb_restricted_email', __( 'This email address or domain has been blacklisted. If you think you are seeing this in error, please contact the site administrator.', 'buddyboss' ), array( 'form-field' => 'email' ) );
	}

	return $errors;
}

/**
 * Validate & prevent email update and related email.
 *
 * @since BuddyBoss 2.4.11
 *
 * @param int $user_id User ID.
 */
function bb_validate_restricted_email_on_profile_update( $user_id ) {

	if (
		! empty( $_REQUEST['email'] ) && // phpcs:ignore
		! empty( $_REQUEST['action'] ) && // phpcs:ignore
		'update' === $_REQUEST['action'] // phpcs:ignore
	) {
		$email = $_REQUEST['email']; // phpcs:ignore
		$old_user_data = get_userdata( $user_id );
		if (
			$old_user_data->user_email !== $email &&
			! bb_is_allowed_register_email_address( $email )
		) {

			// Prevent email updates and related email.
			remove_action( 'personal_options_update', 'send_confirmation_on_profile_email' );
			add_filter( 'send_email_change_email', '__return_false', 0 );
		}
	}
}

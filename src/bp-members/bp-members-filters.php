<?php
/**
 * BuddyPress Members Filters.
 *
 * Filters specific to the Members component.
 *
 * @package BuddyBoss\Members\Filters
 * @since BuddyPress 1.5.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Escape commonly used fullname output functions.
 */
add_filter( 'bp_displayed_user_fullname', 'esc_html' );
add_filter( 'bp_get_loggedin_user_fullname', 'esc_html' );

// Filter the user registration URL to point to BuddyPress's registration page.
add_filter( 'register_url', 'bp_get_signup_page' );

/**
 * Load additional sign-up sanitization filters on bp_loaded.
 *
 * These are used to prevent XSS in the BuddyPress sign-up process. You can
 * unhook these to allow for customization of your registration fields;
 * however, it is highly recommended that you leave these in place for the
 * safety of your network.
 *
 * @since BuddyPress 1.5.0
 */
function bp_members_signup_sanitization() {

	// Filters on sign-up fields.
	$fields = array(
		'bp_get_signup_username_value',
		'bp_get_signup_email_value',
		'bp_get_signup_with_blog_value',
		'bp_get_signup_blog_url_value',
		'bp_get_signup_blog_title_value',
		'bp_get_signup_blog_privacy_value',
		'bp_get_signup_avatar_dir_value',
	);

	// Add the filters to each field.
	foreach ( $fields as $filter ) {
		add_filter( $filter, 'esc_html', 1 );
		add_filter( $filter, 'wp_filter_kses', 2 );
		add_filter( $filter, 'stripslashes', 3 );
	}

	// Sanitize email.
	add_filter( 'bp_get_signup_email_value', 'sanitize_email' );
}
add_action( 'bp_loaded', 'bp_members_signup_sanitization' );

/**
 * Make sure the username is not the blog slug in case of root profile & subdirectory blog.
 *
 * If BP_ENABLE_ROOT_PROFILES is defined & multisite config is set to subdirectories,
 * then there is a chance site.url/username == site.url/blogslug. If so, user's profile
 * is not reachable, instead the blog is displayed. This filter makes sure the signup username
 * is not the same than the blog slug for this particular config.
 *
 * @since BuddyPress 2.1.0
 *
 * @param array $illegal_names Array of illiegal names.
 * @return array $illegal_names
 */
function bp_members_signup_with_subdirectory_blog( $illegal_names = array() ) {
	if ( ! bp_core_enable_root_profiles() ) {
		return $illegal_names;
	}

	if ( is_network_admin() && isset( $_POST['blog'] ) ) {
		$blog   = $_POST['blog'];
		$domain = '';

		if ( preg_match( '|^([a-zA-Z0-9-])$|', $blog['domain'] ) ) {
			$domain = strtolower( $blog['domain'] );
		}

		if ( username_exists( $domain ) ) {
			$illegal_names[] = $domain;
		}
	} else {
		$illegal_names[] = buddypress()->signup->username;
	}

	return $illegal_names;
}
add_filter( 'subdirectory_reserved_names', 'bp_members_signup_with_subdirectory_blog', 10, 1 );

/**
 * Filter the user profile URL to point to BuddyPress profile edit.
 *
 * @since BuddyPress 1.6.0
 *
 * @param string $url     WP profile edit URL.
 * @param int    $user_id ID of the user.
 * @param string $scheme  Scheme to use.
 * @return string
 */
function bp_members_edit_profile_url( $url, $user_id, $scheme = 'admin' ) {

	// If xprofile is active, use profile domain link.
	if ( ! is_admin() && bp_is_active( 'xprofile' ) ) {
		$profile_link = trailingslashit( bp_core_get_user_domain( $user_id ) . bp_get_profile_slug() . '/edit' );

	} else {
		// Default to $url.
		$profile_link = $url;
	}

	/**
	 * Filters the user profile URL to point to BuddyPress profile edit.
	 *
	 * @since BuddyPress 1.5.2
	 *
	 * @param string $url WP profile edit URL.
	 * @param int    $user_id ID of the user.
	 * @param string $scheme Scheme to use.
	 */
	return apply_filters( 'bp_members_edit_profile_url', $profile_link, $url, $user_id, $scheme );
}
add_filter( 'edit_profile_url', 'bp_members_edit_profile_url', 10, 3 );

/**
 * Overwrites login form email field label.
 *
 * @since BuddyBoss 1.0.0
 */
function bp_overwrite_login_form_email_field_label( $defaults ) {
	$defaults['label_username'] = __( 'Email Address', 'buddyboss' );

	return $defaults;
}
add_filter( 'login_form_defaults', 'bp_overwrite_login_form_email_field_label' );

/**
 * Overwrites login email field label.
 *
 * @since BuddyBoss 1.0.0
 */
function bp_overwrite_login_email_field_label( $translated_text, $text, $domain ) {
	if ( 'Username or Email Address' == $text && 'default' == $domain ) {
		remove_filter( 'gettext', 'bp_overwrite_login_email_field_label' );
		return __( 'Email Address', 'buddyboss' );
	}

	return $translated_text;
}

/**
 * Overwrites login form email field label hook.
 *
 * @since BuddyBoss 1.0.0
 */
function bp_overwrite_login_email_field_label_hook() {
	add_filter( 'gettext', 'bp_overwrite_login_email_field_label', 10, 3 );
}
add_action( 'login_form_retrievepassword', 'bp_overwrite_login_email_field_label_hook' );
add_action( 'login_form_lostpassword', 'bp_overwrite_login_email_field_label_hook' );
add_action( 'login_form_login', 'bp_overwrite_login_email_field_label_hook' );

/**
 * Set up media arguments for use with the 'personal' scope.
 *
 * @since BuddyBoss 1.1.9
 *
 * @param array $retval Empty array by default.
 * @param array $filter Current activity arguments.
 * @return array
 */
function bp_members_filter_media_personal_scope( $retval = array(), $filter = array() ) {

	// Determine the user_id.
	if ( ! empty( $filter['user_id'] ) ) {
		$user_id = $filter['user_id'];
	} else {
		$user_id = bp_displayed_user_id()
			? bp_displayed_user_id()
			: bp_loggedin_user_id();
	}

	$retval = array(
		'relation' => 'AND',
		array(
			'column' => 'user_id',
			'value'  => $user_id,
		),
		array(
			'column' => 'privacy',
			'value'  => 'onlyme',
		),
	);

	return $retval;
}
add_filter( 'bp_media_set_personal_scope_args', 'bp_members_filter_media_personal_scope', 10, 2 );

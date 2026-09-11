<?php
/**
 * Two-Factor integration actions.
 *
 * Loaded from the integration's includes() on bp_include @8, only when the Two
 * Factor plugin is active and the feature is enabled.
 *
 * @since   BuddyBoss [BBVERSION]
 * @package BuddyBoss\Features\Integrations\TwoFactor
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Register the Security tab under Account.
 *
 * Fired by BP_Component::setup_nav() after the Settings component has built its
 * own items. Own profile only - bp_core_can_edit_settings() is not used because it
 * is true for admins viewing other members, and a switched session carries no
 * two-factor validation of its own.
 *
 * @since BuddyBoss [BBVERSION]
 */
function bb_two_factor_setup_nav() {
	if ( ! bb_two_factor_is_active() ) {
		return;
	}

	if ( bp_displayed_user_domain() ) {
		$user_domain = bp_displayed_user_domain();
	} elseif ( bp_loggedin_user_domain() ) {
		$user_domain = bp_loggedin_user_domain();
	} else {
		return;
	}

	$slug = bp_get_settings_slug();

	bp_core_new_subnav_item(
		array(
			'name'            => __( 'Security', 'buddyboss' ),
			'slug'            => 'security',
			'parent_url'      => trailingslashit( $user_domain . $slug ),
			'parent_slug'     => $slug,
			'screen_function' => 'bb_two_factor_screen_security',
			'item_css_id'     => 'security',
			'position'        => 15,
			'user_has_access' => bp_is_my_profile() && ! bp_current_member_switched(),
		),
		'members'
	);
}
add_action( 'bp_settings_setup_nav', 'bb_two_factor_setup_nav' );

/**
 * Screen handler for the Security tab.
 *
 * @since BuddyBoss [BBVERSION]
 */
function bb_two_factor_screen_security() {
	if ( bp_action_variables() ) {
		bp_do_404();
		return;
	}

	// Fallback for dispatchers with no 'security' case: a theme override of
	// members/single/settings.php falls through to members/single/plugins.php.
	add_action( 'bp_template_title', 'bb_two_factor_template_title' );
	add_action( 'bp_template_content', 'bb_two_factor_render_section' );

	/**
	 * Filters the template loaded for the Security tab.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param string $template Template part slug.
	 */
	bp_core_load_template( apply_filters( 'bb_member_security_template', 'members/single/settings/security' ) );
}

/**
 * Save the Security tab.
 *
 * Validates before handing off to the plugin's own saver, then drains its private
 * error store into BuddyBoss feedback.
 *
 * @since BuddyBoss [BBVERSION]
 */
function bb_two_factor_settings_save() {
	if ( ! bp_is_post_request() || ! bp_is_settings_component() || ! bp_is_current_action( 'security' ) || ! isset( $_POST['bb-two-factor-submit'] ) ) {
		return;
	}

	// The plugin can be deactivated, or the feature switched off, between the
	// render and this request.
	if ( ! bb_two_factor_is_active() ) {
		return;
	}

	$redirect = bb_two_factor_get_settings_url();

	if ( ! bp_is_my_profile() || bp_current_member_switched() ) {
		bp_core_add_message( __( 'You cannot manage two-factor authentication for another account.', 'buddyboss' ), 'error' );
		bp_core_redirect( $redirect );
	}

	// Our nonce, emitted by bp_nouveau_submit_button().
	$nonce = isset( $_POST['_wpnonce'] ) ? sanitize_text_field( wp_unslash( $_POST['_wpnonce'] ) ) : '';

	if ( ! wp_verify_nonce( $nonce, 'bb_two_factor_settings' ) ) {
		bp_core_add_message( __( 'There was a problem saving your settings. Please try again.', 'buddyboss' ), 'error' );
		bp_core_redirect( $redirect );
	}

	// The plugin's nonce, pre-verified so its check_admin_referer() can never wp_die() mid-page.
	$plugin_nonce = isset( $_POST['_nonce_user_two_factor_options'] ) ? sanitize_text_field( wp_unslash( $_POST['_nonce_user_two_factor_options'] ) ) : '';

	if ( ! wp_verify_nonce( $plugin_nonce, 'user_two_factor_options' ) ) {
		bp_core_add_message( __( 'Your session expired before the change could be saved. Please try again.', 'buddyboss' ), 'error' );
		bp_core_redirect( $redirect );
	}

	// The plugin returns silently outside the revalidation window; tell the member instead.
	if ( ! bb_two_factor_current_user_can_manage( 'save' ) ) {
		bp_core_add_message( __( 'For your security, confirm it is you before changing these settings.', 'buddyboss' ), 'error' );
		bp_core_redirect( $redirect );
	}

	$user_id = bp_loggedin_user_id();

	// The plugin's saver reaches a wp_die() for a configuration that no longer resolves.
	$broken = bb_two_factor_get_broken_config( $user_id );

	if ( $broken ) {
		bp_core_add_message( wp_strip_all_tags( $broken->get_error_message() ), 'error' );
		bp_core_redirect( $redirect );
	}

	Two_Factor_Core::user_two_factor_options_update( $user_id );

	$errors = bb_two_factor_drain_errors();

	if ( $errors->has_errors() ) {
		foreach ( $errors->get_error_messages() as $message ) {
			bp_core_add_message( wp_strip_all_tags( $message ), 'error' );
		}
	} else {
		bp_core_add_message( __( 'Your security settings have been saved.', 'buddyboss' ) );
	}

	bp_core_redirect( $redirect );
}
add_action( 'bp_actions', 'bb_two_factor_settings_save' );

/**
 * Heading for the Security tab when rendered through members/single/plugins.php.
 *
 * @since BuddyBoss [BBVERSION]
 */
function bb_two_factor_template_title() {
	esc_html_e( 'Security', 'buddyboss' );
}

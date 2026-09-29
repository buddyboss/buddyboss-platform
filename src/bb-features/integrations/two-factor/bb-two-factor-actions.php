<?php
/**
 * Two-Factor integration actions.
 *
 * Loaded from the integration's includes() on bp_include @8, only when the Two
 * Factor plugin is active and the feature is enabled. The bb_two_factor_is_active()
 * guards below are therefore belt-and-braces, for a caller that ever reaches one
 * of these functions from somewhere that does not load behind that gate.
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
 * own items. Access is bp_core_can_edit_settings(), the same gate every other
 * Account tab uses: own profile only - it returns false for an admin viewing
 * another member - and true under View As, where the switched admin is the
 * member, so the tab behaves like the rest of the Account screen.
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
			'user_has_access' => bp_core_can_edit_settings(),
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

	if ( ! bb_two_factor_is_active() ) {
		return;
	}

	$redirect = bb_two_factor_get_settings_url();

	if ( ! bp_core_can_edit_settings() ) {
		bp_core_add_message( __( 'You cannot manage two-factor authentication for another account.', 'buddyboss' ), 'error' );
		bp_core_redirect( $redirect );
	}

	// Our nonce, emitted by bp_nouveau_submit_button().
	$nonce = isset( $_POST['_wpnonce'] ) ? sanitize_text_field( wp_unslash( $_POST['_wpnonce'] ) ) : '';

	if ( ! wp_verify_nonce( $nonce, 'bb_two_factor_settings' ) ) {
		bp_core_add_message( __( 'There was a problem saving your settings. Please try again.', 'buddyboss' ), 'error' );
		bp_core_redirect( $redirect );
	}

	// The plugin returns silently outside the revalidation window; tell the member instead.
	// Checked before the plugin's nonce: a locked form disables the fieldset that
	// carries that nonce, so the nonce check would otherwise answer first with a
	// misleading "session expired" for a member who only needs to revalidate.
	if ( ! bb_two_factor_current_user_can_manage( 'save' ) ) {
		bp_core_add_message( __( 'For your security, confirm it is you before changing these settings.', 'buddyboss' ), 'error' );
		bp_core_redirect( $redirect );
	}

	// The plugin's nonce, pre-verified so its check_admin_referer() can never wp_die() mid-page.
	$plugin_nonce = isset( $_POST['_nonce_user_two_factor_options'] ) ? sanitize_text_field( wp_unslash( $_POST['_nonce_user_two_factor_options'] ) ) : '';

	if ( ! wp_verify_nonce( $plugin_nonce, 'user_two_factor_options' ) ) {
		bp_core_add_message( __( 'Your session expired before the change could be saved. Please try again.', 'buddyboss' ), 'error' );
		bp_core_redirect( $redirect );
	}

	$user_id = bp_loggedin_user_id();

	// The plugin's saver reaches a wp_die() for a configuration that no longer resolves.
	$broken = bb_two_factor_get_broken_config( $user_id );

	if ( $broken ) {
		bp_core_add_message( wp_strip_all_tags( $broken->get_error_message() ), 'error' );
		bp_core_redirect( $redirect );
	}

	// A member without wp-admin has no other way back in, so a primary method needs a recovery method beside it.
	$recovery = bb_two_factor_validate_recovery_method( $user_id );

	if ( is_wp_error( $recovery ) ) {
		bp_core_add_message( $recovery->get_error_message(), 'error' );
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

/*
 * Social Login (SSO) needs no handling here.
 *
 * The Social Login addon issues the auth cookie and then fires `wp_login`. The
 * Two Factor plugin collects the token of every cookie it sees on
 * `set_auth_cookie` / `set_logged_in_cookie` and, in its own `wp_login` handler,
 * destroys that session and renders the second-factor challenge. A member with
 * two-factor enabled is therefore challenged on a social sign-in by the plugin
 * itself. Do not remove that handler or write `two-factor-login` into the
 * session from Platform: doing so signs the member in on the social account
 * alone and marks the session as verified, which lets recovery codes be
 * generated or two-factor disabled without a second factor.
 */

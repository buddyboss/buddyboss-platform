<?php
/**
 * Two-Factor integration actions.
 *
 * Loaded from the integration's includes() on bp_include @8, only when the Two
 * Factor plugin is active and the feature is enabled. The bboss_two_factor_is_active()
 * guards below are therefore belt-and-braces, for a caller that ever reaches one
 * of these functions from somewhere that does not load behind that gate.
 *
 * @since   BuddyBoss 3.6.0
 * @package BuddyBoss\Features\Integrations\TwoFactor
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Register the Security tab under Account.
 *
 * Fired by BBoss_Component::setup_nav() after the Settings component has built its
 * own items. Access is bboss_is_my_profile(): the tab can only ever manage the
 * logged-in member's own two-factor, so it is shown on their own profile only.
 * bboss_core_can_edit_settings() is not used because it also lets admins and
 * moderators into another member's settings, where this tab would show and save
 * the admin's own two-factor instead. Under View As the switched session is the
 * member, so bboss_is_my_profile() is true and the tab works for them.
 *
 * @since BuddyBoss 3.6.0
 */
function bboss_two_factor_setup_nav() {
	if ( ! bboss_two_factor_is_active() ) {
		return;
	}

	if ( bboss_displayed_user_domain() ) {
		$user_domain = bboss_displayed_user_domain();
	} elseif ( bboss_loggedin_user_domain() ) {
		$user_domain = bboss_loggedin_user_domain();
	} else {
		return;
	}

	$slug = bboss_get_settings_slug();

	bboss_core_new_subnav_item(
		array(
			'name'            => __( 'Security', 'buddyboss' ),
			'slug'            => 'security',
			'parent_url'      => trailingslashit( $user_domain . $slug ),
			'parent_slug'     => $slug,
			'screen_function' => 'bboss_two_factor_screen_security',
			'item_css_id'     => 'security',
			'position'        => 15,
			'user_has_access' => bboss_is_my_profile(),
		),
		'members'
	);
}
add_action( 'bp_settings_setup_nav', 'bb_two_factor_setup_nav' );

/**
 * Screen handler for the Security tab.
 *
 * @since BuddyBoss 3.6.0
 */
function bboss_two_factor_screen_security() {
	if ( bboss_action_variables() ) {
		bboss_do_404();
		return;
	}

	// Fallback for dispatchers with no 'security' case: a theme override of
	// members/single/settings.php falls through to members/single/plugins.php.
	add_action( 'bp_template_title', 'bb_two_factor_template_title' );
	add_action( 'bp_template_content', 'bb_two_factor_render_section' );

	/**
	 * Filters the template loaded for the Security tab.
	 *
	 * @since BuddyBoss 3.6.0
	 *
	 * @param string $template Template part slug.
	 */
	bboss_core_load_template( apply_filters( 'bb_member_security_template', 'members/single/settings/security' ) );
}

/**
 * Save the Security tab.
 *
 * Validates before handing off to the plugin's own saver, then drains its private
 * error store into BuddyBoss feedback.
 *
 * @since BuddyBoss 3.6.0
 */
function bboss_two_factor_settings_save() {
	if ( ! bboss_is_post_request() || ! bboss_is_settings_component() || ! bboss_is_current_action( 'security' ) || ! isset( $_POST['bb-two-factor-submit'] ) ) {
		return;
	}

	if ( ! bboss_two_factor_is_active() ) {
		return;
	}

	$redirect = bboss_two_factor_get_settings_url();

	// Own profile only: the save always writes the logged-in member's two-factor.
	if ( ! bboss_is_my_profile() ) {
		bboss_core_add_message( __( 'You cannot manage two-factor authentication for another account.', 'buddyboss' ), 'error' );
		bboss_core_redirect( $redirect );
	}

	// Our nonce, emitted by bp_nouveau_submit_button().
	$nonce = isset( $_POST['_wpnonce'] ) ? sanitize_text_field( wp_unslash( $_POST['_wpnonce'] ) ) : '';

	if ( ! wp_verify_nonce( $nonce, 'bb_two_factor_settings' ) ) {
		bboss_core_add_message( __( 'There was a problem saving your settings. Please try again.', 'buddyboss' ), 'error' );
		bboss_core_redirect( $redirect );
	}

	// The plugin returns silently outside the revalidation window; tell the member instead.
	// Checked before the plugin's nonce: a locked form disables the fieldset that
	// carries that nonce, so the nonce check would otherwise answer first with a
	// misleading "session expired" for a member who only needs to revalidate.
	if ( ! bboss_two_factor_current_user_can_manage( 'save' ) ) {
		bboss_core_add_message( __( 'For your security, confirm it is you before changing these settings.', 'buddyboss' ), 'error' );
		bboss_core_redirect( $redirect );
	}

	// The plugin's nonce, pre-verified so its check_admin_referer() can never wp_die() mid-page.
	$plugin_nonce = isset( $_POST['_nonce_user_two_factor_options'] ) ? sanitize_text_field( wp_unslash( $_POST['_nonce_user_two_factor_options'] ) ) : '';

	if ( ! wp_verify_nonce( $plugin_nonce, 'user_two_factor_options' ) ) {
		bboss_core_add_message( __( 'Your session expired before the change could be saved. Please try again.', 'buddyboss' ), 'error' );
		bboss_core_redirect( $redirect );
	}

	$user_id = bboss_loggedin_user_id();

	// The plugin's saver reaches a wp_die() for a configuration that no longer resolves.
	$broken = bboss_two_factor_get_broken_config( $user_id );

	if ( $broken ) {
		bboss_core_add_message( wp_strip_all_tags( $broken->get_error_message() ), 'error' );
		bboss_core_redirect( $redirect );
	}

	// A member without wp-admin has no other way back in, so a primary method needs a recovery method beside it.
	$recovery = bboss_two_factor_validate_recovery_method( $user_id );

	if ( is_wp_error( $recovery ) ) {
		bboss_core_add_message( $recovery->get_error_message(), 'error' );
		bboss_core_redirect( $redirect );
	}

	Two_Factor_Core::user_two_factor_options_update( $user_id );

	$errors = bboss_two_factor_drain_errors();

	if ( $errors->has_errors() ) {
		foreach ( $errors->get_error_messages() as $message ) {
			bboss_core_add_message( wp_strip_all_tags( $message ), 'error' );
		}
	} else {
		bboss_core_add_message( __( 'Your security settings have been saved.', 'buddyboss' ) );
	}

	bboss_core_redirect( $redirect );
}
add_action( 'bp_actions', 'bb_two_factor_settings_save' );

/**
 * Heading for the Security tab when rendered through members/single/plugins.php.
 *
 * @since BuddyBoss 3.6.0
 */
function bboss_two_factor_template_title() {
	esc_html_e( 'Security', 'buddyboss' );
}

/**
 * Keep a revalidation round trip pointed at the Security tab.
 *
 * The plugin passes the tab URL through the `login_redirect` filter before it
 * redirects, and Platform's bboss_bb_login_redirect() replaces that destination with
 * the site's Login Redirect or profile-type redirect for every non-admin. That
 * is right for a sign-in and wrong for a member who was mid-way through their
 * security settings, so the override is lifted for this request only, and only
 * when the destination really is the member's own Security tab.
 *
 * @since BuddyBoss 3.6.0
 *
 * @param WP_User $user The revalidated member.
 */
function bboss_two_factor_keep_revalidation_return( $user ) {
	if ( ! ( $user instanceof WP_User ) ) {
		return;
	}

	// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- The plugin verified its login nonce before firing this action; the value is only compared.
	$redirect_to = isset( $_REQUEST['redirect_to'] ) ? esc_url_raw( wp_unslash( $_REQUEST['redirect_to'] ) ) : '';

	if ( '' === $redirect_to ) {
		return;
	}

	$security_url = bboss_two_factor_get_settings_url( $user->ID );

	if ( '' === $security_url || untrailingslashit( $redirect_to ) !== untrailingslashit( $security_url ) ) {
		return;
	}

	remove_filter( 'bp_login_redirect', 'bb_login_redirect', PHP_INT_MAX );
}
add_action( 'two_factor_user_revalidated', 'bb_two_factor_keep_revalidation_return' );

/**
 * Stop the authenticator setup from switching two-factor on without a recovery method.
 *
 * The plugin's authenticator setup script ("Verify") posts `enable_provider: true`
 * to its own REST route, which turns the method on straight away and never
 * reaches the Security tab save, so the recovery-method rule would not run.
 * When turning it on now would leave the member without a usable recovery
 * method, the request still verifies and stores the secret but leaves the method
 * off. The setup script then shows it as configured and ticked, and the Save
 * button turns it on together with a recovery method, where the rule runs.
 *
 * Applies only to a member changing their own account. An admin setting up
 * another user in wp-admin is left to the plugin.
 *
 * @since BuddyBoss 3.6.0
 *
 * @param WP_REST_Response|WP_HTTP_Response|WP_Error|mixed $response Result to send, or null to run the endpoint.
 * @param array                                            $handler  Route handler.
 * @param WP_REST_Request                                  $request  Request.
 * @return WP_REST_Response|WP_HTTP_Response|WP_Error|mixed Unchanged.
 */
function bboss_two_factor_guard_totp_enable( $response, $handler, $request ) {
	if ( null !== $response || ! ( $request instanceof WP_REST_Request ) ) {
		return $response;
	}

	if ( 'POST' !== $request->get_method() || '/' . Two_Factor_Core::REST_NAMESPACE . '/totp' !== untrailingslashit( $request->get_route() ) ) {
		return $response;
	}

	if ( ! $request->get_param( 'enable_provider' ) || ! bboss_two_factor_is_active() ) {
		return $response;
	}

	$user_id = (int) $request->get_param( 'user_id' );

	if ( ! $user_id || get_current_user_id() !== $user_id ) {
		return $response;
	}

	$enabled   = (array) Two_Factor_Core::get_enabled_providers_for_user( $user_id );
	$enabled[] = 'Two_Factor_Totp';

	if ( true !== bboss_two_factor_check_recovery_method( $user_id, array_unique( $enabled ) ) ) {
		$request->set_param( 'enable_provider', false );
	}

	return $response;
}
add_filter( 'rest_request_before_callbacks', 'bb_two_factor_guard_totp_enable', 10, 3 );

/**
 * Do not ask for a second factor on a BuddyBoss Social Login sign-in.
 *
 * Product decision: signing in through Social Login (any supported provider)
 * does not require two-factor. The Social Login addon issues the auth cookie and
 * fires `bb_sso_before_wp_login` immediately before `wp_login`; the Two Factor
 * plugin's `wp_login` handler would destroy that session and render its
 * challenge, so it is detached for the rest of this request.
 *
 * The session is deliberately NOT marked as having passed two-factor (the
 * plugin's `two-factor-login` session key is not written). A member who uses
 * two-factor is signed in, but the Security tab stays locked behind the plugin's
 * "Revalidate now" step, so someone holding only the social account cannot
 * generate recovery codes or turn two-factor off without the second factor.
 *
 * Only the web sign-in fires this action; the addon's REST/App sign-in does not.
 *
 * @since BuddyBoss 3.6.0
 */
function bboss_two_factor_skip_social_login_challenge() {
	if ( ! bboss_two_factor_is_active() ) {
		return;
	}

	/**
	 * Filters whether a BuddyBoss Social Login sign-in skips the two-factor challenge.
	 *
	 * Return false to keep asking for the second factor on social sign-ins.
	 *
	 * @since BuddyBoss 3.6.0
	 *
	 * @param bool $skip    Default true.
	 * @param int  $user_id Member signing in.
	 */
	if ( ! apply_filters( 'bb_two_factor_skip_on_social_login', true, get_current_user_id() ) ) {
		return;
	}

	remove_action( 'wp_login', array( 'Two_Factor_Core', 'wp_login' ), PHP_INT_MAX );
}
add_action( 'bb_sso_before_wp_login', 'bb_two_factor_skip_social_login_challenge' );

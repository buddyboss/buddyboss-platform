<?php
/**
 * Two-Factor integration filters.
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
 * Add the Security entry to the Account admin-bar menu.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param array $wp_admin_nav Admin-bar items for the Settings component.
 * @return array
 */
function bb_two_factor_settings_admin_nav( $wp_admin_nav ) {
	if ( ! bb_two_factor_is_active() || ! is_user_logged_in() ) {
		return $wp_admin_nav;
	}

	$settings_id = buddypress()->settings->id;

	$wp_admin_nav[] = array(
		'parent'   => 'my-account-' . $settings_id,
		'id'       => 'my-account-' . $settings_id . '-security',
		'title'    => __( 'Security', 'buddyboss' ),
		'href'     => bb_two_factor_get_settings_url( bp_loggedin_user_id() ),
		'position' => 15,
	);

	return $wp_admin_nav;
}
add_filter( 'bp_settings_admin_nav', 'bb_two_factor_settings_admin_nav', 15 );

/**
 * Register the Security tab submit button.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param array $buttons Registered submit buttons.
 * @return array
 */
function bb_two_factor_submit_button( $buttons ) {
	$buttons['bb-two-factor-settings'] = array(
		'before'     => 'bb_two_factor_settings_before_submit',
		'after'      => 'bb_two_factor_settings_after_submit',
		'nonce'      => 'bb_two_factor_settings',
		'attributes' => array(
			'name'  => 'bb-two-factor-submit',
			'id'    => 'submit',
			'value' => __( 'Save Changes', 'buddyboss' ),
			'class' => 'auto',
		),
	);

	return $buttons;
}
add_filter( 'bp_nouveau_get_submit_button', 'bb_two_factor_submit_button' );

/**
 * Hide the debug-only Dummy provider from members.
 *
 * The plugin strips Two_Factor_Dummy only when WP_DEBUG is off. Members must never
 * see it whatever the site's debug setting; a real wp-admin screen keeps the
 * plugin's own behaviour. AJAX and REST are member contexts even though
 * is_admin() is true for the former.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param array $providers Provider instances keyed by provider class name.
 * @return array
 */
function bb_two_factor_hide_dummy_provider( $providers ) {
	if ( ! is_array( $providers ) ) {
		return $providers;
	}

	if ( is_admin() && ! wp_doing_ajax() ) {
		return $providers;
	}

	unset( $providers['Two_Factor_Dummy'] );

	return $providers;
}
add_filter( 'two_factor_providers_for_user', 'bb_two_factor_hide_dummy_provider' );

<?php
/**
 * BuddyBoss Admin Settings - Account Settings Feature Registration.
 *
 * Registers the Account Settings feature in the Feature Registry.
 * This is a toggle-only feature card — toggling it on/off directly
 * activates/deactivates the bp-settings component. No settings page
 * is needed because bp-settings manages user-facing account settings
 * (email, password, notifications, profile visibility, export, deletion),
 * not admin-configurable options.
 *
 * @package BuddyBoss\Core\Administration
 * @since BuddyBoss 3.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Register Account Settings feature in Feature Registry.
 *
 * Registers only the feature card for the Settings 2.0 admin grid.
 * No side panels, sections, or fields — the toggle directly controls
 * the bp-settings component active state.
 *
 * @since BuddyBoss 3.0.0
 */
function bb_admin_settings_register_account_settings_feature() {

	bb_register_feature(
		'settings',
		array(
			'label'              => __( 'Account Settings', 'buddyboss' ),
			'description'        => __( 'Allow members to update their account and notification settings directly from their profiles with this account settings page.', 'buddyboss' ),
			'icon'               => array(
				'type'  => 'font',
				'class' => 'bb-icons-rl bb-icons-rl-user-gear',
			),
			'license_tier'       => 'free',
			'category'           => 'community',
			'standalone'         => true,
			'is_active_callback' => function () {
				return bp_is_active( 'settings' );
			},
			'settings_route'     => '',
			'order'              => 30,
		)
	);
}

add_action( 'bb_register_features', 'bb_admin_settings_register_account_settings_feature', 20 );

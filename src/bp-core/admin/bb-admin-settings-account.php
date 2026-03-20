<?php
/**
 * BuddyBoss Admin Settings - Account Settings Feature Registration.
 *
 * Registers the Account Settings feature in the Feature Registry.
 * This feature controls the bp-settings component which allows members
 * to update their account and notification settings from their profiles.
 *
 * @package BuddyBoss\Core\Administration
 * @since BuddyBoss [BBVERSION]
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Register Account Settings feature in Feature Registry.
 *
 * Registers the feature card for the Settings 2.0 admin grid.
 * The bp-settings component handles user-facing account settings
 * (email, password, notifications, profile visibility, export, deletion).
 *
 * @since BuddyBoss [BBVERSION]
 */
function bb_admin_settings_register_account_settings_feature() {

	// =========================================================================
	// REGISTER FEATURE
	// =========================================================================

	bb_register_feature(
		'settings',
		array(
			'label'              => __( 'Account Settings', 'buddyboss' ),
			'description'        => __( 'Allow members to update their account and notification settings directly from their profiles.', 'buddyboss' ),
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
			'settings_route'     => '/settings/settings',
			'order'              => 90,
		)
	);

	// When settings is disabled, only the feature card is needed (so admin can re-enable).
	if ( ! bp_is_active( 'settings' ) ) {
		return;
	}

	// =========================================================================
	// SIDE PANELS
	// =========================================================================

	// Side Panel 1: Settings (default).
	bb_register_side_panel(
		'settings',
		'account_settings',
		array(
			'title'      => __( 'Settings', 'buddyboss' ),
			'icon'       => array(
				'type'  => 'font',
				'class' => 'bb-icons-rl bb-icons-rl-gear-six',
			),
			'order'      => 10,
			'is_default' => true,
		)
	);

	// =========================================================================
	// SECTION: ACCOUNT SETTINGS
	// =========================================================================

	bb_register_feature_section(
		'settings',
		'account_settings',
		'account_settings',
		array(
			'title'       => __( 'Account Settings', 'buddyboss' ),
			'description' => '',
			'order'       => 10,
		)
	);

	// FIELD: Account Deletion.
	// Legacy option name: bp-disable-account-deletion (inverted — DB stores 1=deletion DISABLED).
	bb_register_feature_field(
		'settings',
		'account_settings',
		'account_settings',
		array(
			'name'              => 'bp-disable-account-deletion',
			'label'             => __( 'Account Deletion', 'buddyboss' ),
			'type'              => 'toggle',
			'description'       => __( 'Allow members to delete their profiles', 'buddyboss' ),
			'default'           => absint( ! bp_disable_account_deletion( false ) ),
			'sanitize_callback' => 'absint',
			'invert_value'      => true,
			'order'             => 10,
		)
	);

	/**
	 * Fires after all Account Settings fields are registered.
	 * Allows third-party extensions to add more panels or fields.
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	do_action( 'bb_account_settings_after_register_settings_fields' );
}

add_action( 'bb_register_features', 'bb_admin_settings_register_account_settings_feature', 20 );

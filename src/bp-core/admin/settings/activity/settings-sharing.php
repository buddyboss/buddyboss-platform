<?php
/**
 * BuddyBoss Admin Settings - Activity Sharing Panel.
 *
 * Registers the Activity Sharing side panel with a PRO-locked stub.
 * When the BuddyBoss Sharing plugin is active, it registers its own
 * full fields via the `bb_activity_after_register_settings_fields` hook.
 *
 * @package BuddyBoss\Core\Administration
 * @since BuddyBoss [BBVERSION]
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Register Activity Sharing panel section and PRO-locked stub field.
 *
 * Only registers the section and a single PRO-locked "Enable Sharing" toggle.
 * When the sharing plugin is active, it will override this with full fields.
 *
 * @since BuddyBoss [BBVERSION]
 */
function bb_activity_register_sharing_panel_fields() {

	// Three possible states (mirrors the Site SEO panel in the Appearance
	// feature):
	//   1. NEW Sharing — registers its own Activity Sharing fields via
	//      `bb_admin_settings_before_get_feature`. Platform skips.
	//   2. OLD Sharing — main plugin class exists but predates Settings 2.0.
	//      Show Update Required empty state, no UPGRADE PRO badge.
	//   3. Sharing NOT installed — upgrade-to-get-Sharing empty state.
	//
	// Detection key: `Activity_Settings::bb_sh_register_sharing_settings()`
	// is only declared on Sharing versions that ship the Settings 2.0 path.
	$has_new_sharing = class_exists( '\\BuddyBoss\\Sharing\\Admin\\Activity_Settings' )
		&& method_exists( '\\BuddyBoss\\Sharing\\Admin\\Activity_Settings', 'bb_sh_register_sharing_settings' );
	$has_old_sharing = ! $has_new_sharing && class_exists( 'BuddyBoss_Sharing' );

	// -------------------------------------------------------------------------
	// SECTION: Activity Sharing.
	// -------------------------------------------------------------------------
	bb_register_feature_section(
		'activity',
		'activity_sharing',
		'activity_sharing',
		array(
			'title' => __( 'Activity Sharing', 'buddyboss' ),
			'order' => 10,
		)
	);

	if ( $has_new_sharing ) {
		return;
	}

	// Empty-state card — same pattern as OneSignal "Pro Update Required" /
	// Site SEO panel. `empty_state` field type (see SettingsForm.js:691).
	if ( $has_old_sharing ) {
		$empty_state_args = array(
			'name'                    => 'bb_activity_sharing_update_notice',
			'label'                   => '',
			'type'                    => 'empty_state',
			'icon'                    => 'bb-icons-rl bb-icons-rl-warning-circle',
			'empty_state_title'       => __( 'BuddyBoss Sharing Update Required', 'buddyboss' ),
			'empty_state_description' => __( 'Please update to the latest version of BuddyBoss Sharing to manage activity sharing from Settings 2.0.', 'buddyboss' ),
			'button_label'            => __( 'Update Now', 'buddyboss' ),
			'button_url'              => admin_url( 'update-core.php' ),
			'sanitize_callback'       => '__return_empty_string',
			'order'                   => 10,
		);
	} else {
		$empty_state_args = array(
			'name'                    => 'bb_activity_sharing_install_notice',
			'label'                   => '',
			'type'                    => 'empty_state',
			'icon'                    => 'bb-icons-rl bb-icons-rl-share',
			'empty_state_title'       => __( 'BuddyBoss Sharing Required', 'buddyboss' ),
			'empty_state_description' => __( 'Install BuddyBoss Sharing to let members share activity posts to their groups, friends, direct messages and external networks. Comes with the Pro license.', 'buddyboss' ),
			'button_label'            => __( 'Upgrade to Pro', 'buddyboss' ),
			'button_url'              => 'https://www.buddyboss.com/pricing/',
			'button_target'           => '_blank',
			'sanitize_callback'       => '__return_empty_string',
			'order'                   => 10,
		);
	}

	bb_register_feature_field( 'activity', 'activity_sharing', 'activity_sharing', $empty_state_args );
}

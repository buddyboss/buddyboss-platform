<?php
/**
 * BuddyBoss Admin Settings - Activity Sharing Panel.
 *
 * Registers the Activity Sharing side panel with a PRO-locked stub.
 * When the BuddyBoss Sharing plugin is active, it registers its own
 * full fields via the `bb_activity_after_register_settings_fields` hook.
 *
 * @package BuddyBoss\Features\Community\Activity
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

	// Only show PRO-locked Enable Sharing toggle when sharing plugin is not active.
	if ( ! class_exists( 'BuddyBoss_Sharing' ) ) {
		bb_register_feature_field(
			'activity',
			'activity_sharing',
			'activity_sharing',
			array(
				'name'        => 'buddyboss_enable_activity_sharing',
				'label'       => __( 'Enable Sharing', 'buddyboss' ),
				'type'        => 'toggle',
				'description' => __( 'Allow members to share activity posts', 'buddyboss' ),
				'default'     => false,
				'pro_only'    => true,
				'pro_notice'  => array(
					'show'       => true,
					'badge_text' => __( 'PRO', 'buddyboss' ),
					'badge_icon' => 'bb-icons-rl-crown-simple',
					'link_url'   => 'https://www.buddyboss.com/resources/docs/components/activity/activity-sharing/',
					'link_icon'  => 'bb-icons-rl-play',
				),
				'order'       => 10,
			)
		);
	}
}

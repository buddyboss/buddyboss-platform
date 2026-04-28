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

	// Three possible states (mirrors the Web Push Notifications pattern +
	// the Site SEO panel):
	// 1. NEW Sharing — registers its own Activity Sharing fields via
	// `bb_admin_settings_before_get_feature`. Platform skips.
	// 2. OLD Sharing — main plugin class exists but predates Settings 2.0.
	// Show `empty_state` Update Required card, NO UPGRADE PRO badge.
	// 3. Sharing NOT installed / deactivated — render the full Figma field
	// surface as `pro_only` disabled placeholders with an UPGRADE PRO
	// badge on the section. Mirrors OneSignal's
	// `bb_notifications_register_web_push_pro_placeholder_fields()`.
	//
	// Detection key: `Activity_Settings::bb_sh_register_sharing_settings()`
	// only ships in Sharing versions with the Settings 2.0 path. The class
	// itself has existed since 1.0.0, so `class_exists` alone is not enough.
	$has_new_sharing = class_exists( '\\BuddyBoss\\Sharing\\Admin\\Activity_Settings' )
		&& method_exists( '\\BuddyBoss\\Sharing\\Admin\\Activity_Settings', 'bb_sh_register_sharing_settings' );
	$has_old_sharing = ! $has_new_sharing && class_exists( 'BuddyBoss_Sharing' );

	if ( $has_new_sharing ) {
		// Sharing owns the panel — register an empty section shell so its
		// `bb_admin_settings_before_get_feature` hook can fill it in.
		bb_register_feature_section(
			'activity',
			'activity_sharing',
			'activity_sharing',
			array(
				'title'    => __( 'Activity Sharing', 'buddyboss' ),
				'order'    => 10,
				'help_url' => '62793',
			)
		);
		return;
	}

	if ( $has_old_sharing ) {
		// OLD Sharing — `empty_state` Update Required card, NO UPGRADE PRO
		// badge (plugin is already present, just out of date).
		bb_register_feature_section(
			'activity',
			'activity_sharing',
			'activity_sharing',
			array(
				'title'    => __( 'Activity Sharing', 'buddyboss' ),
				'order'    => 10,
				'help_url' => '62793',
			)
		);
		bb_register_feature_field(
			'activity',
			'activity_sharing',
			'activity_sharing',
			array(
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
			)
		);
		return;
	}

	// Sharing NOT installed / deactivated — render Figma fields as PRO-gated
	// disabled placeholders. Section carries the UPGRADE PRO badge.
	bb_activity_register_sharing_pro_placeholder_fields();
}

/**
 * Register PRO-gated placeholder fields for the Activity Sharing panel.
 *
 * Called when the BuddyBoss Sharing plugin is NOT installed. Mirrors
 * `bb_notifications_register_web_push_pro_placeholder_fields()` — registers
 * the full Figma field surface as `pro_only` disabled placeholders so admins
 * see what they'd unlock by upgrading to Pro + installing Sharing.
 *
 * Field option keys match Sharing's own Settings 2.0 registration so values
 * hand over seamlessly once Sharing is active.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @return void
 */
function bb_activity_register_sharing_pro_placeholder_fields() {

	$feature_id = 'activity';
	$panel_id   = 'activity_sharing';
	$section_id = 'activity_sharing';

	// Per-field PRO badge data. Activity Sharing is gated by the Sharing
	// plugin, not by Platform Pro — so when Sharing is inactive we always
	// want the row-level "PRO" badge to show, regardless of whether Pro is
	// active.
	//
	// `bb_admin_settings_format_field_data` (in `class-bb-admin-settings-ajax.php`)
	// auto-computes `pro_notice` for any `pro_only` field that doesn't already
	// have one set, and that auto-compute (`bb_admin_settings_get_pro_notice`)
	// only returns `show => true` when Pro is inactive or its license is
	// invalid. The OneSignal placeholder relies on that auto-compute because
	// its placeholder only runs when Pro is inactive — so the assumption holds.
	// Activity Sharing is asymmetric: Sharing inactive can coexist with Pro
	// active, so we set `pro_notice` explicitly here to bypass the auto-compute
	// and keep the badge visible in that combination.
	$pro_notice_field = array(
		'show'       => true,
		'badge_text' => __( 'PRO', 'buddyboss' ),
		'badge_icon' => 'bb-icons-rl-crown-simple',
		'link_url'   => 'https://www.buddyboss.com/platform/',
		'link_icon'  => 'bb-icons-rl-play',
	);

	// -------------------------------------------------------------------------
	// SECTION: Activity Sharing (pro-gated placeholder, UPGRADE PRO badge).
	// -------------------------------------------------------------------------
	bb_register_feature_section(
		$feature_id,
		$panel_id,
		$section_id,
		array(
			'title'      => __( 'Activity Sharing', 'buddyboss' ),
			'order'      => 10,
			'help_url'   => '62793',
			'pro_notice' => array(
				'show'       => true,
				'badge_text' => __( 'UPGRADE PRO', 'buddyboss' ),
				'badge_icon' => 'bb-icons-rl-crown-simple',
				'link_url'   => 'https://www.buddyboss.com/pricing/',
			),
		)
	);

	// Figma alignment: each toggle uses `description` for the inline helper
	// text next to the switch (same pattern as the Social Open-graph toggle
	// in the Site SEO panel). `label` holds the left-column title.
	$toggle_fields = array(
		array(
			'name'        => 'buddyboss_enable_activity_sharing',
			'label'       => __( 'Enable Sharing', 'buddyboss' ),
			'description' => __( 'Allow members to share activity posts', 'buddyboss' ),
			'order'       => 10,
		),
		array(
			'name'        => 'buddyboss_activity_sharing_custom_message',
			'label'       => __( 'Custom Message', 'buddyboss' ),
			'description' => __( 'Allow members to add a custom message while sharing', 'buddyboss' ),
			'order'       => 20,
		),
		array(
			'name'        => 'buddyboss_activity_sharing_to_groups',
			'label'       => __( 'Share to Groups', 'buddyboss' ),
			'description' => __( 'Allow members to share public posts in their groups', 'buddyboss' ),
			'order'       => 30,
		),
		array(
			'name'        => 'buddyboss_activity_sharing_to_friends',
			'label'       => __( 'Share to Friends', 'buddyboss' ),
			'description' => __( "Allow members to share public posts to their friends' profiles", 'buddyboss' ),
			'order'       => 40,
		),
		array(
			'name'        => 'buddyboss_activity_sharing_to_message',
			'label'       => __( 'Share to Message', 'buddyboss' ),
			'description' => __( 'Allow members to send via direct message', 'buddyboss' ),
			'order'       => 50,
		),
	);

	foreach ( $toggle_fields as $toggle ) {
		bb_register_feature_field(
			$feature_id,
			$panel_id,
			$section_id,
			array(
				'name'              => $toggle['name'],
				'label'             => $toggle['label'],
				'type'              => 'toggle',
				'description'       => $toggle['description'],
				'default'           => 0,
				'pro_only'          => true,
				'pro_notice'        => $pro_notice_field,
				'sanitize_callback' => '__return_empty_string',
				'order'             => $toggle['order'],
			)
		);
	}

	// Share as Link toggle + its share-platforms picker share the same
	// `group.key` so the row-separator line between them drops (per Figma —
	// the platform cards visually belong to the "Share as Link" row, not
	// a standalone row of their own).
	bb_register_feature_field(
		$feature_id,
		$panel_id,
		$section_id,
		array(
			'name'              => 'buddyboss_activity_sharing_as_link',
			'label'             => __( 'Share as Link', 'buddyboss' ),
			'type'              => 'toggle',
			'description'       => __( 'Allow members to share public posts as link', 'buddyboss' ),
			'default'           => 0,
			'pro_only'          => true,
			'pro_notice'        => $pro_notice_field,
			'sanitize_callback' => '__return_empty_string',
			'group'             => array(
				'key' => 'share_as_link',
			),
			'order'             => 60,
		)
	);

	// Share-platforms picker (Messenger / Facebook / X / LinkedIn / WhatsApp).
	bb_register_feature_field(
		$feature_id,
		$panel_id,
		$section_id,
		array(
			'name'              => 'buddyboss_activity_sharing_link_platforms',
			'label'             => '',
			'type'              => 'share_platforms',
			'default'           => array(),
			'options'           => array(
				array(
					'label' => __( 'Messenger', 'buddyboss' ),
					'value' => 'messenger',
					'icon' => 'bb-icons-rl bb-icons-rl-messenger-logo',
				),
				array(
					'label' => __( 'Facebook', 'buddyboss' ),
					'value' => 'facebook',
					'icon' => 'bb-icons-rl bb-icons-rl-facebook-logo',
				),
				array(
					'label' => __( 'X', 'buddyboss' ),
					'value' => 'twitter',
					'icon' => 'bb-icons-rl bb-icons-rl-x-logo',
				),
				array(
					'label' => __( 'Linkedin', 'buddyboss' ),
					'value' => 'linkedin',
					'icon' => 'bb-icons-rl bb-icons-rl-linkedin-logo',
				),
				array(
					'label' => __( 'Whatsapp', 'buddyboss' ),
					'value' => 'whatsapp',
					'icon' => 'bb-icons-rl bb-icons-rl-whatsapp-logo',
				),
			),
			'pro_only'          => true,
			'pro_notice'        => $pro_notice_field,
			'sanitize_callback' => '__return_empty_string',
			'conditional'       => array(
				'field' => 'buddyboss_activity_sharing_as_link',
				'value' => true,
			),
			'group'             => array(
				'key' => 'share_as_link',
			),
			'order'             => 70,
		)
	);
}

<?php
/**
 * BuddyBoss Admin Settings - Activity Sharing Panel.
 *
 * Registers the Activity Sharing side panel with a PRO-locked stub.
 * When the BuddyBoss Sharing plugin is active, it registers its own
 * full fields via the `bb_activity_after_register_settings_fields` hook.
 *
 * @package BuddyBoss\Core\Administration
 * @since BuddyBoss 3.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Activity Sharing section title.
 *
 * Single source of truth for the section title used across the section
 * registration branches in this file. The side-panel title (in
 * `bb-admin-settings-activity.php`) is intentionally NOT routed through
 * this helper — panel and section titles are conceptually distinct and a
 * future customization might want them to differ.
 *
 * @since BuddyBoss 3.0.0
 *
 * @return string Translated section title.
 */
function bb_activity_sharing_section_title() {
	return __( 'Activity Sharing', 'buddyboss-platform' );
}

/**
 * Activity Sharing help article ID.
 *
 * Single source of truth for the section's help_url. Mirrors
 * `bb_activity_sharing_section_title()` so the help link can be updated
 * in one place.
 *
 * @since BuddyBoss 3.0.0
 *
 * @return string Help article ID.
 */
function bb_activity_sharing_section_help_article() {
	return '637448';
}

/**
 * Base args for the Activity Sharing section.
 *
 * Defaults that apply regardless of which Sharing state (new / old / none) is
 * active. State-specific extensions (e.g. the UPGRADE PRO badge when Sharing
 * is not installed) are layered on top via the
 * `bb_activity_sharing_section_args` filter — see
 * `bb_activity_sharing_get_section_args()` and
 * `bb_activity_sharing_add_pro_badge_when_no_sharing()` below.
 *
 * @since BuddyBoss 3.0.0
 *
 * @return array Section args (title, order, help_url).
 */
function bb_activity_sharing_section_base_args() {
	return array(
		'title'    => bb_activity_sharing_section_title(),
		'order'    => 10,
		'help_url' => bb_activity_sharing_section_help_article(),
	);
}

/**
 * Build the final section args for `activity_sharing`, after extensions filter.
 *
 * Plugins that need to mutate the Activity Sharing section attributes (add a
 * `pro_notice` badge, change `status`, override `description`, etc.) should
 * hook the `bb_activity_sharing_section_args` filter rather than calling
 * `bb_register_feature_section()` a second time. Re-registering the same
 * section ID without `merge => true` would trigger the registry's
 * duplicate-detection auto-suffix path (`activity_sharing_1`) and render
 * two sections in the panel.
 *
 * The filter is the canonical extension point for boot-time state (e.g. "no
 * Sharing installed → show UPGRADE PRO badge"). For runtime state changes
 * that only resolve at AJAX time — Sharing's license-lock state being the
 * only known case, because Sharing's DRM-addon registration races with
 * Platform's panel hook on `plugins_loaded@10` — use a follow-up
 * `bb_register_feature_section()` call with `merge => true`. See
 * `bb_activity_register_sharing_pro_placeholder_fields()`.
 *
 * @since BuddyBoss 3.0.0
 *
 * @return array Section args after filter.
 */
function bb_activity_sharing_get_section_args() {
	/**
	 * Filter the Activity Sharing section args.
	 *
	 * Use this filter to mutate the section's attributes (title, order,
	 * help_url, pro_notice, status, description). Section ID and panel ID
	 * are fixed — this is the section args, not a "register a different
	 * section" hook.
	 *
	 * @since BuddyBoss 3.0.0
	 *
	 * @param array $args {
	 *     Default section args.
	 *
	 *     @type string $title    Section title.
	 *     @type int    $order    Display order within the panel.
	 *     @type string $help_url Help article ID.
	 * }
	 */
	return apply_filters( 'bb_activity_sharing_section_args', bb_activity_sharing_section_base_args() );
}

/**
 * Filter callback: add UPGRADE PRO badge when the BuddyBoss Sharing plugin is
 * not installed at all (state 4).
 *
 * Detection here is reliable at boot — `class_exists` does not depend on DRM
 * registration ordering. The "license-locked" runtime state (state 2) is
 * handled separately by `bb_activity_register_sharing_pro_placeholder_fields()`
 * via a `merge => true` follow-up registration at AJAX time, when license
 * state is settled.
 *
 * @since BuddyBoss 3.0.0
 *
 * @param array $args Default section args.
 * @return array Possibly-modified section args.
 */
function bb_activity_sharing_add_pro_badge_when_no_sharing( $args ) {
	$has_new_sharing = class_exists( '\\BuddyBoss\\Sharing\\Admin\\Activity_Settings' )
		&& method_exists( '\\BuddyBoss\\Sharing\\Admin\\Activity_Settings', 'bb_sh_register_sharing_settings' );
	$has_old_sharing = ! $has_new_sharing && class_exists( 'BuddyBoss_Sharing' );

	// Only add the upgrade badge when Sharing is genuinely absent. New Sharing
	// (state 1/2) and old Sharing (state 3) handle their own messaging.
	if ( $has_new_sharing || $has_old_sharing ) {
		return $args;
	}

	$args['pro_notice'] = array(
		'show'       => true,
		'badge_text' => __( 'UPGRADE PRO', 'buddyboss-platform' ),
		'badge_icon' => 'bb-icons-rl-crown-simple',
		'link_url'   => 'https://www.buddyboss.com/pricing/',
	);

	return $args;
}
add_filter( 'bb_activity_sharing_section_args', 'bb_activity_sharing_add_pro_badge_when_no_sharing' );

/**
 * Register the Activity Sharing panel section + state-appropriate fields.
 *
 * Section is registered once via filtered args (single source of truth — see
 * `bb_activity_sharing_get_section_args()`). Field registration branches on
 * the four possible states:
 *
 *   1. NEW Sharing — Sharing's `bb_admin_settings_before_get_feature` hook
 *      registers the real fields at AJAX time. Platform registers nothing
 *      here beyond the section shell.
 *   2. NEW Sharing + license locked — Same as state 1 at boot. Sharing's
 *      lazy hook calls `bb_activity_register_sharing_pro_placeholder_fields()`
 *      at AJAX time, which adds the UPGRADE PRO badge via merge mode and
 *      registers placeholder fields.
 *   3. OLD Sharing — main plugin class exists but predates Settings 2.0.
 *      Render an `empty_state` Update Required card. No UPGRADE PRO badge.
 *   4. Sharing NOT installed / deactivated — the
 *      `bb_activity_sharing_section_args` filter has already added the
 *      UPGRADE PRO badge to the section. Register the placeholder fields.
 *
 * Detection key: `Activity_Settings::bb_sh_register_sharing_settings()` only
 * ships in Sharing versions with the Settings 2.0 path. The class itself has
 * existed since 1.0.0, so `class_exists` alone is not enough.
 *
 * @since BuddyBoss 3.0.0
 */
function bb_activity_register_sharing_panel_fields() {

	// Single section registration — args go through the
	// `bb_activity_sharing_section_args` filter, which is the canonical
	// extension point for mutating section attributes from this or any
	// other plugin. State-appropriate badges (e.g. UPGRADE PRO when Sharing
	// is absent) are added via filter callbacks, not by re-registering.
	bb_register_feature_section(
		'activity',
		'activity_sharing',
		'activity_sharing',
		bb_activity_sharing_get_section_args()
	);

	$has_new_sharing = class_exists( '\\BuddyBoss\\Sharing\\Admin\\Activity_Settings' )
		&& method_exists( '\\BuddyBoss\\Sharing\\Admin\\Activity_Settings', 'bb_sh_register_sharing_settings' );
	$has_old_sharing = ! $has_new_sharing && class_exists( 'BuddyBoss_Sharing' );

	if ( $has_new_sharing ) {
		// States 1 and 2 — Sharing's lazy AJAX hook handles fields.
		return;
	}

	if ( $has_old_sharing ) {
		// State 3 — OLD Sharing: show Update Required notice.
		bb_register_feature_field(
			'activity',
			'activity_sharing',
			'activity_sharing',
			array(
				'name'                    => 'bb_activity_sharing_update_notice',
				'label'                   => '',
				'type'                    => 'empty_state',
				'icon'                    => 'bb-icons-rl bb-icons-rl-warning-circle',
				'empty_state_title'       => __( 'BuddyBoss Sharing Update Required', 'buddyboss-platform' ),
				'empty_state_description' => __( 'Please update to the latest version of BuddyBoss Sharing to manage activity sharing from Settings 2.0.', 'buddyboss-platform' ),
				'button_label'            => __( 'Update Now', 'buddyboss-platform' ),
				'button_url'              => admin_url( 'update-core.php' ),
				'sanitize_callback'       => '__return_empty_string',
				'order'                   => 10,
			)
		);
		return;
	}

	// State 4 — Sharing NOT installed: section already carries the UPGRADE PRO
	// badge from the filter callback above. Register the placeholder fields.
	bb_activity_register_sharing_pro_placeholder_fields();
}

/**
 * Register PRO-gated placeholder fields for the Activity Sharing panel, and
 * ensure the section carries the UPGRADE PRO badge.
 *
 * Called from two contexts:
 *
 *   - Platform boot, no Sharing installed (state 4) — section already carries
 *     the UPGRADE PRO badge from the boot-time
 *     `bb_activity_sharing_add_pro_badge_when_no_sharing` filter callback.
 *     The merge call below overlays the same `pro_notice` value — no-op.
 *
 *   - Sharing add-on's lazy AJAX hook when its license is locked (state 2).
 *     At boot the filter saw `class_exists` for new Sharing and stepped
 *     aside, so the section was registered without the badge. License-lock
 *     state cannot be checked reliably at boot — Sharing's
 *     `register_with_drm()` is hooked on `plugins_loaded@10`, which fires
 *     AFTER Platform's `bp_loaded` (also dispatched during `plugins_loaded@10`)
 *     because Platform loads first alphabetically. So at the moment Platform's
 *     filter callback runs, `BB_DRM_Registry::should_lock_addon_features('buddyboss-sharing')`
 *     hasn't seen Sharing's addon registration yet and returns false. The
 *     filter therefore can't detect state 2 at boot. The merge call below
 *     adds the badge at AJAX time, where DRM state is fully settled.
 *
 * Why merge is the canonical mechanism here:
 *
 *   The `bb_activity_sharing_section_args` filter handles boot-time state
 *   (no Sharing installed → add badge). Runtime state changes that resolve
 *   only at AJAX time are exactly what the registry's `merge => true`
 *   contract is designed for — see `BB_Feature_Registry::bb_register_section()`
 *   lines 540-595. Without merge the second registration auto-suffixes to
 *   `activity_sharing_1` and renders a phantom second section.
 *
 * Field option keys match Sharing's own Settings 2.0 registration so values
 * hand over seamlessly once Sharing is active and licensed.
 *
 * @since BuddyBoss 3.0.0
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
		'badge_text' => __( 'PRO', 'buddyboss-platform' ),
		'badge_icon' => 'bb-icons-rl-crown-simple',
		'link_url'   => 'https://www.buddyboss.com/platform/',
		'link_icon'  => 'bb-icons-rl-play',
	);

	// Idempotently ensure the section carries the UPGRADE PRO badge.
	//
	// State 4 (no Sharing): the boot-time filter already added the badge.
	// This merge overlays the same value — no-op.
	//
	// State 2 (Sharing license locked at AJAX time): the boot-time filter
	// did NOT add the badge (DRM race — see function docblock above). This
	// merge adds it now, where license state is reliable.
	bb_register_feature_section(
		$feature_id,
		$panel_id,
		$section_id,
		array(
			'merge'      => true,
			'pro_notice' => array(
				'show'       => true,
				'badge_text' => __( 'UPGRADE PRO', 'buddyboss-platform' ),
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
			'label'       => __( 'Enable Sharing', 'buddyboss-platform' ),
			'description' => __( 'Allow members to share activity posts', 'buddyboss-platform' ),
			'order'       => 10,
		),
		array(
			'name'        => 'buddyboss_activity_sharing_custom_message',
			'label'       => __( 'Custom Message', 'buddyboss-platform' ),
			'description' => __( 'Allow members to add a custom message while sharing', 'buddyboss-platform' ),
			'order'       => 20,
		),
		array(
			'name'        => 'buddyboss_activity_sharing_to_groups',
			'label'       => __( 'Share to Groups', 'buddyboss-platform' ),
			'description' => __( 'Allow members to share public posts in their groups', 'buddyboss-platform' ),
			'order'       => 30,
		),
		array(
			'name'        => 'buddyboss_activity_sharing_to_friends',
			'label'       => __( 'Share to Friends', 'buddyboss-platform' ),
			'description' => __( "Allow members to share public posts to their friends' profiles", 'buddyboss-platform' ),
			'order'       => 40,
		),
		array(
			'name'        => 'buddyboss_activity_sharing_to_message',
			'label'       => __( 'Share to Message', 'buddyboss-platform' ),
			'description' => __( 'Allow members to send via direct message', 'buddyboss-platform' ),
			'order'       => 50,
		),
	);

	// All toggles except Enable Sharing itself are dependents of Enable Sharing
	// — they hide when the master toggle is OFF. Mirrors the "real" Sharing
	// add-on path in `Activity_Settings::bb_sh_register_activity_sharing_settings()`
	// where the same dependents carry the same conditional.
	foreach ( $toggle_fields as $toggle ) {
		$args = array(
			'name'              => $toggle['name'],
			'label'             => $toggle['label'],
			'type'              => 'toggle',
			'description'       => $toggle['description'],
			'default'           => 0,
			'pro_only'          => true,
			'pro_notice'        => $pro_notice_field,
			'sanitize_callback' => '__return_empty_string',
			'order'             => $toggle['order'],
		);

		if ( 'buddyboss_enable_activity_sharing' !== $toggle['name'] ) {
			$args['conditional'] = array(
				'field' => 'buddyboss_enable_activity_sharing',
				'value' => true,
			);
		}

		bb_register_feature_field( $feature_id, $panel_id, $section_id, $args );
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
			'label'             => __( 'Share as Link', 'buddyboss-platform' ),
			'type'              => 'toggle',
			'description'       => __( 'Allow members to share public posts as link', 'buddyboss-platform' ),
			'default'           => 0,
			'pro_only'          => true,
			'pro_notice'        => $pro_notice_field,
			'sanitize_callback' => '__return_empty_string',
			'group'             => array(
				'key' => 'share_as_link',
			),
			'order'             => 60,
			'conditional'       => array(
				'field' => 'buddyboss_enable_activity_sharing',
				'value' => true,
			),
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
					'label' => __( 'Messenger', 'buddyboss-platform' ),
					'value' => 'messenger',
					'icon' => 'bb-icons-rl bb-icons-rl-messenger-logo',
				),
				array(
					'label' => __( 'Facebook', 'buddyboss-platform' ),
					'value' => 'facebook',
					'icon' => 'bb-icons-rl bb-icons-rl-facebook-logo',
				),
				array(
					'label' => __( 'X', 'buddyboss-platform' ),
					'value' => 'twitter',
					'icon' => 'bb-icons-rl bb-icons-rl-x-logo',
				),
				array(
					'label' => __( 'Linkedin', 'buddyboss-platform' ),
					'value' => 'linkedin',
					'icon' => 'bb-icons-rl bb-icons-rl-linkedin-logo',
				),
				array(
					'label' => __( 'Whatsapp', 'buddyboss-platform' ),
					'value' => 'whatsapp',
					'icon' => 'bb-icons-rl bb-icons-rl-whatsapp-logo',
				),
			),
			'pro_only'          => true,
			'pro_notice'        => $pro_notice_field,
			'sanitize_callback' => '__return_empty_string',
			// Visible only when both the master toggle (Enable Sharing) and
			// the parent toggle (Share as Link) are ON. AND-ing both is
			// required because the conditional system reads stored values,
			// not the visible-state cascade — so hiding Share as Link via
			// the master toggle does not implicitly hide this picker.
			'conditional'       => array(
				'operator'   => 'AND',
				'conditions' => array(
					array(
						'field' => 'buddyboss_enable_activity_sharing',
						'value' => true,
					),
					array(
						'field' => 'buddyboss_activity_sharing_as_link',
						'value' => true,
					),
				),
			),
			'group'             => array(
				'key' => 'share_as_link',
			),
			'order'             => 70,
		)
	);
}

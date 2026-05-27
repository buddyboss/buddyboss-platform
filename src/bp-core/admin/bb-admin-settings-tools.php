<?php
/**
 * BuddyBoss Admin Settings — Tools Feature Registration.
 *
 * Registers the Tools feature card + 3 side-panel slots (Repair Platform,
 * Sample Data, Migration Tools) + the three Settings 2.0 custom field types
 * that drive the React panels:
 *
 *   - `bb_tools_repair_platform` — rendered by Platform (this plugin) always.
 *   - `bb_tools_sample_data`     — rendered by buddyboss-tools when active;
 *                                  falls back to the Activation Required CTA.
 *   - `bb_tools_migration_tools` — rendered by buddyboss-tools when active;
 *                                  falls back to the Activation Required CTA.
 *
 * The legacy `?page=bp-tools` admin page (Repair Community + Repair Forums +
 * Default Data + Forum Import) is replaced by this feature. The legacy URL
 * 301-redirects via `bb_redirect_bp_settings_before_permission_check()`.
 *
 * @package BuddyBoss\Core\Administration
 * @since BuddyBoss [BBVERSION]
 */

defined( 'ABSPATH' ) || exit;

/**
 * Register the Tools feature, its side panels, sections, and fields.
 *
 * Hooked on `bb_register_features` priority 25 so it runs after component
 * features (priority 20) and alongside other non-component features
 * (advanced @ 25, emails @ 25, forums @ 25).
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @return void
 */
function bb_admin_settings_register_tools_feature() {

	// =========================================================================
	// REGISTER FEATURE
	// =========================================================================

	bb_register_feature(
		'tools',
		array(
			'label'              => __( 'Tools', 'buddyboss' ),
			'description'        => __( 'Repair community data, import sample content, and migrate from other platforms.', 'buddyboss' ),
			'icon'               => array(
				'type'  => 'font',
				'class' => 'bb-icons-rl bb-icons-rl-wrench',
			),
			'license_tier'       => 'free',
			'category'           => 'tools',
			'standalone'         => true,
			'is_active_callback' => '__return_true',
			'settings_route'     => '/settings/tools',
			'order'              => 10,
		)
	);

	// =========================================================================
	// SIDE PANELS
	// =========================================================================

	// Side Panel 1: Repair Platform (always rendered by Platform).
	bb_register_side_panel(
		'tools',
		'repair_platform',
		array(
			'title'      => __( 'Repair Platform', 'buddyboss' ),
			'icon'       => array(
				'type'  => 'font',
				'class' => 'bb-icons-rl bb-icons-rl-wrench',
			),
			'order'      => 10,
			'is_default' => true,
		)
	);

	bb_register_feature_section(
		'tools',
		'repair_platform',
		'repair_platform_section',
		array(
			'title'       => __( 'Repair Platform', 'buddyboss' ),
			'description' => __( 'BuddyBoss keeps track of various relationships between members, groups, and activity items. Occasionally these relationships become out of sync, most often after an import, update, or migration. Use the tools below to manually recalculate these relationships.', 'buddyboss' ),
			'order'       => 10,
		)
	);

	bb_register_feature_field(
		'tools',
		'repair_platform',
		'repair_platform_section',
		array(
			'name'       => 'bb_tools_repair_platform',
			'label'      => '',
			'type'       => 'bb_tools_repair_platform',
			'full_width' => true,
			'order'      => 10,
		)
	);

	// Side Panel 2: Sample Data (Tools-active → React panel; inactive → CTA).
	bb_register_side_panel(
		'tools',
		'sample_data',
		array(
			'title'      => __( 'Sample Data', 'buddyboss' ),
			'icon'       => array(
				'type'  => 'font',
				'class' => 'bb-icons-rl bb-icons-rl-database',
			),
			'order'      => 20,
			'is_default' => false,
		)
	);

	bb_register_feature_section(
		'tools',
		'sample_data',
		'sample_data_section',
		array(
			'title'       => __( 'Sample Data', 'buddyboss' ),
			'description' => __( 'Select the data you want to import. Some of these tools utilize substantial database resources. Avoid running more than 1 import at a time.', 'buddyboss' ),
			'order'       => 10,
		)
	);

	bb_register_feature_field(
		'tools',
		'sample_data',
		'sample_data_section',
		array(
			'name'       => 'bb_tools_sample_data',
			'label'      => '',
			'type'       => 'bb_tools_sample_data',
			'full_width' => true,
			'order'      => 10,
		)
	);

	// Side Panel 3: Migration Tools (Tools-active → React panel; inactive → CTA).
	bb_register_side_panel(
		'tools',
		'migration_tools',
		array(
			'title'      => __( 'Migration Tools', 'buddyboss' ),
			'icon'       => array(
				'type'  => 'font',
				'class' => 'bb-icons-rl bb-icons-rl-upload-simple',
			),
			'order'      => 30,
			'is_default' => false,
		)
	);

	bb_register_feature_section(
		'tools',
		'migration_tools',
		'migration_tools_section',
		array(
			'title'       => __( 'Migration Tools', 'buddyboss' ),
			'description' => __( 'Migrate content from other community, course, or forum platforms into BuddyBoss.', 'buddyboss' ),
			'order'       => 10,
		)
	);

	bb_register_feature_field(
		'tools',
		'migration_tools',
		'migration_tools_section',
		array(
			'name'       => 'bb_tools_migration_tools',
			'label'      => '',
			'type'       => 'bb_tools_migration_tools',
			'full_width' => true,
			'order'      => 10,
		)
	);

	// Load AJAX handlers for the Activation Required CTA install/activate buttons.
	require_once __DIR__ . '/settings/tools/callbacks.php';
}
add_action( 'bb_register_features', 'bb_admin_settings_register_tools_feature', 25 );

/**
 * Localize Repair Platform config for the React component.
 *
 * Provides the merged repair-items list (`bp_admin_repair_list()` +
 * `bbp_admin_repair_list()`) with each item tagged by its dispatching AJAX
 * endpoint, the item-categories taxonomy used to group items in the UI, the
 * multisite context flag, and the network-sites array when applicable.
 *
 * Two AJAX endpoints and two nonces are exposed because legacy code has two
 * separate dispatchers: `bp_admin_repair_tools_wrapper_function` (nonce
 * `bp-do-counts`) for community items, and `bp_admin_forum_repair_tools_wrapper_function`
 * (nonce `bbpress-do-counts`) for forum items. Both are LOCKED BC and remain
 * untouched in Platform.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @return void
 */
function bb_admin_settings_localize_tools_repair_config() {
	if ( ! is_admin() ) {
		return;
	}
	if ( ! function_exists( 'get_current_screen' ) ) {
		return;
	}
	$screen = get_current_screen();
	if ( ! $screen || 'buddyboss_page_bb-settings' !== $screen->id ) {
		return;
	}

	// Only run when actually on the Tools tab — avoids loading the legacy
	// admin-tools files on every Settings 2.0 page load.
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only URL inspection.
	$current_tab = isset( $_GET['tab'] ) ? sanitize_text_field( wp_unslash( $_GET['tab'] ) ) : '';
	if ( 'tools' !== $current_tab ) {
		return;
	}

	// Force-load the legacy admin-tools files because they're normally only
	// loaded when the user is on `?page=bp-tools` / `?page=bbp-repair`. The
	// `bp_admin_repair_list()` and `bbp_admin_repair_list()` functions live
	// inside these files (locked-BC, third-party-extensible).
	$bp_tools_file  = buddypress()->plugin_dir . 'bp-core/admin/bp-core-admin-tools.php';
	$bbp_tools_file = buddypress()->plugin_dir . 'bp-forums/admin/tools.php';
	if ( ! function_exists( 'bp_admin_repair_list' ) && file_exists( $bp_tools_file ) ) {
		require_once $bp_tools_file;
	}
	if ( ! function_exists( 'bbp_admin_repair_list' ) && file_exists( $bbp_tools_file ) ) {
		require_once $bbp_tools_file;
	}

	$repair_items = array();

	if ( function_exists( 'bp_admin_repair_list' ) ) {
		$bp_list = (array) bp_admin_repair_list();
		foreach ( $bp_list as $item ) {
			if ( ! is_array( $item ) || count( $item ) < 2 ) {
				continue;
			}
			$repair_items[] = array(
				'id'       => (string) $item[0],
				'label'    => html_entity_decode( (string) $item[1], ENT_QUOTES, 'UTF-8' ),
				'category' => bb_admin_repair_categorize_item( (string) $item[0] ),
				'endpoint' => 'bp_admin_repair_tools_wrapper_function',
				'nonce'    => 'repair',
			);
		}
	}

	if ( function_exists( 'bbp_admin_repair_list' ) ) {
		$bbp_list = (array) bbp_admin_repair_list();
		foreach ( $bbp_list as $item ) {
			if ( ! is_array( $item ) || count( $item ) < 2 ) {
				continue;
			}
			$repair_items[] = array(
				'id'       => (string) $item[0],
				'label'    => html_entity_decode( (string) $item[1], ENT_QUOTES, 'UTF-8' ),
				'category' => 'forums_discussions',
				'endpoint' => 'bp_admin_forum_repair_tools_wrapper_function',
				'nonce'    => 'forumRepair',
			);
		}
	}

	$item_categories = array(
		array(
			'id'    => 'members_profiles',
			'label' => __( 'Members & Profiles', 'buddyboss' ),
			'order' => 10,
		),
		array(
			'id'    => 'groups',
			'label' => __( 'Groups', 'buddyboss' ),
			'order' => 20,
		),
		array(
			'id'    => 'media',
			'label' => __( 'Media', 'buddyboss' ),
			'order' => 25,
		),
		array(
			'id'    => 'forums_discussions',
			'label' => __( 'Forums & Discussions', 'buddyboss' ),
			'order' => 30,
		),
		array(
			'id'    => 'messages',
			'label' => __( 'Messages', 'buddyboss' ),
			'order' => 35,
		),
		array(
			'id'    => 'moderation',
			'label' => __( 'Moderation', 'buddyboss' ),
			'order' => 38,
		),
		array(
			'id'    => 'connections',
			'label' => __( 'Connections', 'buddyboss' ),
			'order' => 40,
		),
		array(
			'id'    => 'activity_reactions',
			'label' => __( 'Activity & Reactions', 'buddyboss' ),
			'order' => 50,
		),
		array(
			'id'    => 'emails',
			'label' => __( 'Emails', 'buddyboss' ),
			'order' => 60,
		),
	);

	$network_sites = array();
	if ( is_multisite() && is_network_admin() && function_exists( 'bbp_get_network_sites' ) ) {
		$sites = bbp_get_network_sites();
		foreach ( (array) $sites as $site ) {
			$network_sites[] = array(
				'blog_id' => (int) $site->blog_id,
				'domain'  => (string) $site->domain,
				'path'    => (string) $site->path,
			);
		}
	}

	$config = array(
		'repairItems'      => $repair_items,
		'itemCategories'   => $item_categories,
		'isNetworkAdmin'   => is_multisite() && is_network_admin(),
		'networkSites'     => $network_sites,
		'repairNonce'      => wp_create_nonce( 'bp-do-counts' ),
		'forumRepairNonce' => wp_create_nonce( 'bbpress-do-counts' ),
		'ajaxUrl'          => admin_url( 'admin-ajax.php' ),
	);

	// `bb-admin-settings` script is enqueued INSIDE the page render
	// (`bb_admin_settings_page()` line 141), not from `admin_enqueue_scripts`.
	// That means `wp_add_inline_script( 'bb-admin-settings', ... )` from this
	// hook would run before the script is registered. Print directly as inline
	// JS in the admin footer so the global is available before React mounts.
	printf(
		'<script id="bb-tools-repair-config-js">window.bbToolsRepairConfig = %s;</script>',
		wp_json_encode( $config ) // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- wp_json_encode escapes for JS context.
	);
}
add_action( 'admin_print_footer_scripts', 'bb_admin_settings_localize_tools_repair_config' );

/**
 * Map a `bp_admin_repair_*` item id to one of the Figma category buckets.
 *
 * The legacy `bp_admin_repair_list()` returns a flat list — Figma groups the
 * items under five headings. This map decides which heading each item lands
 * under. Items not matched fall through to `connections`.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param string $item_id The legacy repair-item id (e.g. "bp-last-activity").
 * @return string Category id matching the `item_categories` taxonomy above.
 */
function bb_admin_repair_categorize_item( $item_id ) {
	// 50 items total in the legacy lists (28 from bp_admin_repair_list,
	// 22 from bbp_admin_repair_list) — every entry below should map to one
	// of the nine Figma categories defined in $item_categories above. Pro
	// plugin contributions land via the bp_repair_list filter and fall
	// through to `connections` by default; update the maps below when those
	// move to a more specific bucket. See LEGACY-INVENTORY.md for the full
	// id → label catalog.
	$members_profiles   = array(
		'bp-last-activity',
		'bp-total-member-count',
		'bp-xprofile-fields',
		'bp-xprofile-wordpress-resync',
		'bp-wordpress-xprofile-resync',
		'bp-wordpress-update-display-name',
		'bp-sync-profile-completion-widget',
		'bp-xprofile-repeater-field-repair',
		'bb-xprofile-repair-user-nicknames',
		'bb-xprofile-visibility-field-migrate',
		'bb-member-repair-profile-links',
	);
	$moderation         = array(
		'bp-repair-moderation-data',
	);
	$groups             = array(
		'bp-group-count',
		'bp-group-members-count',
		'bp-invitations-table',
		'bb-repair-group-subscription',
		'bp-group-courses-migrate',
	);
	$media              = array(
		'bp-repair-video',
		'bp-repair-media',
		'bp-repair-document',
		'bp-media-message-repair',
		'bp-video-forum-privacy-repair',
		'bp-media-forum-privacy-repair',
	);
	$messages           = array(
		'bp-repair-messages-unread-count',
	);
	$emails             = array(
		'bp-missing-emails',
		'bp-reinstall-emails',
	);
	$connections        = array(
		'bp-user-friends',
		'bp-blog-records',
	);
	$activity_reactions = array(
		'bp-sync-activity-favourite',
	);

	if ( in_array( $item_id, $members_profiles, true ) ) {
		return 'members_profiles';
	}
	if ( in_array( $item_id, $groups, true ) ) {
		return 'groups';
	}
	if ( in_array( $item_id, $media, true ) ) {
		return 'media';
	}
	if ( in_array( $item_id, $messages, true ) ) {
		return 'messages';
	}
	if ( in_array( $item_id, $moderation, true ) ) {
		return 'moderation';
	}
	if ( in_array( $item_id, $emails, true ) ) {
		return 'emails';
	}
	if ( in_array( $item_id, $connections, true ) ) {
		return 'connections';
	}
	if ( in_array( $item_id, $activity_reactions, true ) ) {
		return 'activity_reactions';
	}

	return 'connections';
}

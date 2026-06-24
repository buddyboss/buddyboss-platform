<?php
/**
 * BuddyBoss Admin Settings - Moderation Feature Registration.
 *
 * Registers the Moderation feature in the Feature Registry and loads
 * all Moderation settings (side panels, sections, fields).
 *
 * @package BuddyBoss\Core\Administration
 * @since BuddyBoss 3.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Get administrator user IDs with static caching.
 *
 * Avoids repeated get_users() calls within the same request.
 *
 * @since BuddyBoss 3.0.0
 *
 * @return int[] Array of administrator user IDs.
 */
function bb_moderation_get_admin_user_ids() {
	static $admins = null;

	if ( null === $admins ) {
		$admins = array_map(
			'intval',
			get_users(
				array(
					'role'   => 'administrator',
					'fields' => 'ID',
					'number' => max( 1, absint( apply_filters( 'bb_moderation_admin_user_query_limit', 1000 ) ) ),
				)
			)
		);
	}

	return $admins;
}

/**
 * Temporarily bypass suspension filters so admin can see real user data.
 *
 * The suspension system replaces display names with "Unknown Member" and
 * avatars with a generic placeholder for suspended users. In admin panels,
 * administrators need to see the actual user data.
 *
 * Shared between BB_Admin_Flagged_Members_Ajax and BB_Admin_Reported_Content_Ajax.
 *
 * @since BuddyBoss 3.0.0
 */
function bb_moderation_bypass_suspend_filters() {
	global $moderation_suspend;

	if ( empty( $moderation_suspend['user'] ) || ! $moderation_suspend['user'] instanceof BP_Suspend_Member ) {
		return;
	}

	$suspend = $moderation_suspend['user'];

	// Remove display name filters.
	remove_filter( 'bp_core_get_user_displayname', array( $suspend, 'get_the_author_name' ), 9999 );
	remove_filter( 'get_the_author_display_name', array( $suspend, 'get_the_author_name' ), 9999 );

	// Remove user nicename/login/email filters (nicename affects profile URL slug).
	remove_filter( 'get_the_author_user_nicename', array( $suspend, 'get_the_author_name' ), 9999 );
	remove_filter( 'get_the_author_user_login', array( $suspend, 'get_the_author_name' ), 9999 );
	remove_filter( 'get_the_author_user_email', array( $suspend, 'get_the_author_name' ), 9999 );

	// Remove avatar filters.
	remove_filter( 'get_avatar_url', array( $suspend, 'get_avatar_url' ), 9999 );
	remove_filter( 'bp_core_fetch_avatar_url_check', array( $suspend, 'bp_fetch_avatar_url' ), 1005 );
	remove_filter( 'bp_core_fetch_gravatar_url_check', array( $suspend, 'bp_fetch_avatar_url' ), 1005 );

	// Remove user domain filter.
	remove_filter( 'bp_core_get_user_domain', array( $suspend, 'bp_core_get_user_domain' ), 9999 );
}

/**
 * Restore suspension filters after admin data fetch.
 *
 * Shared between BB_Admin_Flagged_Members_Ajax and BB_Admin_Reported_Content_Ajax.
 *
 * @since BuddyBoss 3.0.0
 */
function bb_moderation_restore_suspend_filters() {
	global $moderation_suspend;

	if ( empty( $moderation_suspend['user'] ) || ! $moderation_suspend['user'] instanceof BP_Suspend_Member ) {
		return;
	}

	$suspend = $moderation_suspend['user'];

	// Restore display name filters.
	add_filter( 'bp_core_get_user_displayname', array( $suspend, 'get_the_author_name' ), 9999, 2 );
	add_filter( 'get_the_author_display_name', array( $suspend, 'get_the_author_name' ), 9999, 2 );

	// Restore user nicename/login/email filters.
	add_filter( 'get_the_author_user_nicename', array( $suspend, 'get_the_author_name' ), 9999, 2 );
	add_filter( 'get_the_author_user_login', array( $suspend, 'get_the_author_name' ), 9999, 2 );
	add_filter( 'get_the_author_user_email', array( $suspend, 'get_the_author_name' ), 9999, 2 );

	// Restore avatar filters.
	add_filter( 'get_avatar_url', array( $suspend, 'get_avatar_url' ), 9999, 3 );
	add_filter( 'bp_core_fetch_avatar_url_check', array( $suspend, 'bp_fetch_avatar_url' ), 1005, 2 );
	add_filter( 'bp_core_fetch_gravatar_url_check', array( $suspend, 'bp_fetch_avatar_url' ), 1005, 2 );

	// Restore user domain filter.
	add_filter( 'bp_core_get_user_domain', array( $suspend, 'bp_core_get_user_domain' ), 9999, 2 );
}

/**
 * Register Moderation feature and settings in Feature Registry.
 *
 * Registers the feature, side panels, and delegates field registration
 * to panel-specific functions.
 *
 * @since BuddyBoss 3.0.0
 */
function bb_admin_settings_register_moderation_feature() {

	// =========================================================================
	// REGISTER FEATURE
	// =========================================================================

	bb_register_feature(
		'moderation',
		array(
			'label'              => __( 'Moderation', 'buddyboss-platform' ),
			'description'        => __( 'Allow members to block one another and report inappropriate content for review by the site admin.', 'buddyboss-platform' ),
			'icon'               => array(
				'type'  => 'font',
				'class' => 'bb-icons-rl bb-icons-rl-flag',
			),
			'license_tier'       => 'free',
			'category'           => 'community',
			'standalone'         => true,
			'is_active_callback' => function () {
				return bp_is_active( 'moderation' );
			},
			'settings_route'     => '/settings/moderation',
			'order'              => 40,
			// Confirmation modal shown when an admin tries to turn the
			// Moderation feature OFF from the features grid. Mirrors the
			// field-level confirm_* convention (see ConfirmToggleModal.js)
			// but uses confirm_off_* prefix so the feature card can stay
			// independent of any per-field confirms — and so future features
			// could add a separate confirm_on_* flow without colliding.
			// Wired up in class-bb-admin-settings-ajax.php (response shape)
			// and SettingsScreen.js (toggle handler intercept).
			// Body mirrors the legacy Moderation deactivation warning shown in
			// the Components admin screen. wp_kses_post() in
			// BB_Admin_Settings_Ajax::bb_format_confirm_off_payload keeps the
			// markup safe through the JSON trip; ConfirmToggleModal then
			// double-sanitises via DOMPurify before rendering when
			// confirm_off_message_is_html is true.
			'confirm_off_message'         => '<p>' . __( 'Please confirm you want to deactivate the Moderation feature.', 'buddyboss-platform' ) . '</p>'
				. '<h4>' . __( 'On Deactivation:', 'buddyboss-platform' ) . '</h4>'
				. '<ul>'
				. '<li>' . __( 'All suspended members will regain permission to login and their content will be unhidden', 'buddyboss-platform' ) . '</li>'
				. '<li>' . __( 'Members on the network will no longer be able to block other members. Any members they have blocked will be unblocked.', 'buddyboss-platform' ) . '</li>'
				. '<li>' . __( 'All hidden content will be unhidden', 'buddyboss-platform' ) . '</li>'
				. '</ul>'
				. '<p>' . __( 'Please note: Data will not be deleted when you deactivate the Moderation feature. On reactivation, members who have previously been suspended or blocked will once again have their access removed or limited. Content that was previously unhidden will be hidden again.', 'buddyboss-platform' ) . '</p>',
			'confirm_off_message_is_html' => true,
			'confirm_off_title'           => __( 'Disable Moderation?', 'buddyboss-platform' ),
			'confirm_off_ok'              => __( 'Disable', 'buddyboss-platform' ),
			'confirm_off_cancel'          => __( 'Cancel', 'buddyboss-platform' ),
			'confirm_off_destructive'     => true,
		)
	);

	// When moderation is disabled, only the feature card is needed (so admin can re-enable).
	// Side panels, sections, and fields depend on moderation functions that aren't loaded.
	if ( ! bp_is_active( 'moderation' ) ) {
		return;
	}

	// Load settings sub-files only when moderation is active.
	require_once __DIR__ . '/settings/moderation/callbacks.php';
	require_once __DIR__ . '/settings/moderation/settings-member-moderation.php';
	require_once __DIR__ . '/settings/moderation/settings-content-reporting.php';

	// =========================================================================
	// SIDE PANELS
	// =========================================================================

	// Side Panel 1: Member Moderation (default).
	bb_register_side_panel(
		'moderation',
		'member_moderation',
		array(
			'title'      => __( 'Member Moderation', 'buddyboss-platform' ),
			'icon'       => array(
				'type'  => 'font',
				'class' => 'bb-icons-rl bb-icons-rl-user-minus',
			),
			'help_url'   => bp_get_admin_url(
				add_query_arg(
					array(
						'page'    => 'bp-help',
						'article' => 62868,
					),
					'admin.php'
				)
			),
			'order'      => 10,
			'is_default' => true,
		)
	);

	// Side Panel 2: Content Reporting.
	bb_register_side_panel(
		'moderation',
		'content_reporting',
		array(
			'title'    => __( 'Content Reporting', 'buddyboss-platform' ),
			'icon'     => array(
				'type'  => 'font',
				'class' => 'bb-icons-rl bb-icons-rl-flag',
			),
			'help_url' => bp_get_admin_url(
				add_query_arg(
					array(
						'page'    => 'bp-help',
						'article' => 62868,
					),
					'admin.php'
				)
			),
			'order'    => 20,
		)
	);

	// Side Panel 3: Reporting Categories.
	bb_register_side_panel(
		'moderation',
		'reporting_categories',
		array(
			'title' => __( 'Reporting Categories', 'buddyboss-platform' ),
			'icon'  => array(
				'type'  => 'font',
				'class' => 'bb-icons-rl bb-icons-rl-squares-four',
			),
			'order' => 30,
		)
	);

	// Side Panel: Flagged Members (custom panel screen).
	bb_register_side_panel(
		'moderation',
		'flagged_members',
		array(
			'title'   => __( 'Flagged Members', 'buddyboss-platform' ),
			'icon'    => array(
				'type'  => 'font',
				'class' => 'bb-icons-rl bb-icons-rl-users',
			),
			'divider' => true,
			'order'   => 100,
		)
	);

	// Side Panel: Reported Content (custom panel screen).
	bb_register_side_panel(
		'moderation',
		'reported_content',
		array(
			'title' => __( 'Reported Content', 'buddyboss-platform' ),
			'icon'  => array(
				'type'  => 'font',
				'class' => 'bb-icons-rl bb-icons-rl-file-text',
			),
			'order' => 110,
		)
	);

	// @todo: Add Blog Posts side panel when the blog posts moderation screen is implemented.
	/*
	bb_register_side_panel(
		'moderation',
		'blog_posts',
		array(
			'title' => __( 'Blog Posts', 'buddyboss' ),
			'icon'  => array(
				'type'  => 'font',
				'class' => 'bb-icons-rl bb-icons-rl-article',
			),
			'link'  => bp_get_admin_url( 'admin.php?page=bp-moderation&tab=blog-posts' ),
			'order' => 120,
		)
	);
	*/

	// =========================================================================
	// PANEL FIELDS
	// =========================================================================

	// Panel 1: Member Moderation.
	bb_moderation_register_member_moderation_fields();

	// Panel 2: Content Reporting.
	bb_moderation_register_content_reporting_fields();

	/**
	 * Fires after all Moderation settings panels are registered.
	 * Allows third-party extensions to add more panels or fields.
	 *
	 * @since BuddyBoss 3.0.0
	 */
	do_action( 'bb_moderation_after_register_settings_fields' );
}

add_action( 'bb_register_features', 'bb_admin_settings_register_moderation_feature', 20 );

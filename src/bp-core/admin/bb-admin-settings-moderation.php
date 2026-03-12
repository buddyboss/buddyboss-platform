<?php
/**
 * BuddyBoss Admin Settings - Moderation Feature Registration.
 *
 * Registers the Moderation feature in the Feature Registry and loads
 * all Moderation settings (side panels, sections, fields).
 *
 * @package BuddyBoss\Core\Administration
 * @since BuddyBoss [BBVERSION]
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Register Moderation feature and settings in Feature Registry.
 *
 * Registers the feature, side panels, and delegates field registration
 * to panel-specific functions.
 *
 * @since BuddyBoss [BBVERSION]
 */
function bb_admin_settings_register_moderation_feature() {

	// =========================================================================
	// REGISTER FEATURE
	// =========================================================================

	bb_register_feature(
		'moderation',
		array(
			'label'              => __( 'Moderation', 'buddyboss' ),
			'description'        => __( 'Allow members to block one another and report inappropriate content for review by the site admin.', 'buddyboss' ),
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
			'order'              => 120,
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
			'title'      => __( 'Member Moderation', 'buddyboss' ),
			'icon'       => array(
				'type'  => 'font',
				'class' => 'bb-icons-rl bb-icons-rl-user-circle-minus',
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
			'title'    => __( 'Content Reporting', 'buddyboss' ),
			'icon'     => array(
				'type'  => 'font',
				'class' => 'bb-icons-rl bb-icons-rl-file-text',
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
			'title' => __( 'Reporting Categories', 'buddyboss' ),
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
			'title'   => __( 'Flagged Members', 'buddyboss' ),
			'icon'    => array(
				'type'  => 'font',
				'class' => 'bb-icons-rl bb-icons-rl-user-circle-minus',
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
			'title' => __( 'Reported Content', 'buddyboss' ),
			'icon'  => array(
				'type'  => 'font',
				'class' => 'bb-icons-rl bb-icons-rl-file-text',
			),
			'order' => 110,
		)
	);

	// Side Panel: Blog Posts (navigation link).
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
	 * @since BuddyBoss [BBVERSION]
	 */
	do_action( 'bb_moderation_after_register_settings_fields' );
}

add_action( 'bb_register_features', 'bb_admin_settings_register_moderation_feature', 20 );

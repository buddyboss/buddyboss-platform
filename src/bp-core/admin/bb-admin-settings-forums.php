<?php
/**
 * BuddyBoss Admin Settings - Forums Feature Registration.
 *
 * Registers the Forums feature in the Feature Registry and loads
 * all Forums settings (side panels, sections, fields).
 *
 * @package BuddyBoss\Core\Administration
 * @since BuddyBoss 3.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Register Forums feature and settings in Feature Registry.
 *
 * Registers the feature, side panels, and delegates field registration
 * to panel-specific functions.
 *
 * @since BuddyBoss 3.0.0
 *
 * @return void
 */
function bb_admin_settings_register_forums_feature() {

	// =========================================================================
	// REGISTER FEATURE
	// =========================================================================

	bb_register_feature(
		'forums',
		array(
			'label'              => __( 'Forum Discussions', 'buddyboss' ),
			'description'        => __( 'Allow members to hold discussions in Q&A-style forums, which can operate independently or be linked to social groups.', 'buddyboss' ),
			'icon'               => array(
				'type'  => 'font',
				'class' => 'bb-icons-rl bb-icons-rl-chats-circle',
			),
			'license_tier'       => 'free',
			'category'           => 'community',
			'standalone'         => true,
			'is_active_callback' => function () {
				return bp_is_active( 'forums' );
			},
			'settings_route'     => '/settings/forums',
			'order'              => 80,
			'components'         => array( 'forums' ),
		)
	);

	// When forums is disabled, only the feature card is needed (so admin can re-enable).
	// Side panels, sections, and fields depend on forum functions that aren't loaded.
	if ( ! bp_is_active( 'forums' ) ) {
		return;
	}

	// Load sanitize callbacks and panel field registrations only when forums is active.
	require_once __DIR__ . '/settings/forums/callbacks.php';
	require_once __DIR__ . '/settings/forums/settings-forum-settings.php';
	require_once __DIR__ . '/settings/forums/settings-forum-features.php';
	require_once __DIR__ . '/settings/forums/settings-forum-directories.php';
	require_once __DIR__ . '/settings/forums/settings-forum-permalinks.php';
	require_once __DIR__ . '/settings/forums/meta-fields.php';
	require_once __DIR__ . '/settings/forums/meta-fields-topics.php';
	require_once __DIR__ . '/settings/forums/meta-fields-replies.php';
	require_once __DIR__ . '/settings/forums/legacy-meta-bridge.php';
	require_once __DIR__ . '/settings/forums/legacy-meta-bridge-topics.php';
	require_once __DIR__ . '/settings/forums/legacy-meta-bridge-replies.php';

	// =========================================================================
	// SIDE PANELS
	// =========================================================================

	// Side Panel 1: Forum Settings (default).
	bb_register_side_panel(
		'forums',
		'forum_settings',
		array(
			'title'      => __( 'Forum Settings', 'buddyboss' ),
			'icon'       => array(
				'type'  => 'font',
				'class' => 'bb-icons-rl bb-icons-rl-gear-six',
			),
			'help_url'   => bp_get_admin_url(
				add_query_arg(
					array(
						'page'    => 'bp-help',
						'article' => 62857,
					),
					'admin.php'
				)
			),
			'order'      => 10,
			'is_default' => true,
		)
	);

	// Side Panel 2: Forum Features.
	bb_register_side_panel(
		'forums',
		'forum_features',
		array(
			'title'      => __( 'Forum Features', 'buddyboss' ),
			'icon'       => array(
				'type'  => 'font',
				'class' => 'bb-icons-rl bb-icons-rl-chats-teardrop',
			),
			'help_url'   => bp_get_admin_url(
				add_query_arg(
					array(
						'page'    => 'bp-help',
						'article' => 62857,
					),
					'admin.php'
				)
			),
			'order'      => 20,
			'is_default' => false,
		)
	);

	// Side Panel 3: Forum Directories.
	bb_register_side_panel(
		'forums',
		'forum_directories',
		array(
			'title'      => __( 'Forum Directories', 'buddyboss' ),
			'icon'       => array(
				'type'  => 'font',
				'class' => 'bb-icons-rl bb-icons-rl-grid-nine',
			),
			'help_url'   => bp_get_admin_url(
				add_query_arg(
					array(
						'page'    => 'bp-help',
						'article' => 62857,
					),
					'admin.php'
				)
			),
			'order'      => 30,
			'is_default' => false,
		)
	);

	// Side Panel 4: Permalinks.
	bb_register_side_panel(
		'forums',
		'forum_permalinks',
		array(
			'title'      => __( 'Permalinks', 'buddyboss' ),
			'icon'       => array(
				'type'  => 'font',
				'class' => 'bb-icons-rl bb-icons-rl-pencil-simple',
			),
			'help_url'   => bp_get_admin_url(
				add_query_arg(
					array(
						'page'    => 'bp-help',
						'article' => 62857,
					),
					'admin.php'
				)
			),
			'order'      => 40,
			'is_default' => false,
		)
	);

	// Side Panel 5: All Forums (custom list screen).
	bb_register_side_panel(
		'forums',
		'all_forums',
		array(
			'title'      => __( 'Forums', 'buddyboss' ),
			'icon'       => array(
				'type'  => 'font',
				'class' => 'bb-icons-rl bb-icons-rl-chat-text',
			),
			'order'      => 100,
			'is_default' => false,
			'divider'    => true,
		)
	);

	// Side Panel 6: Discussions (custom list screen).
	bb_register_side_panel(
		'forums',
		'discussions',
		array(
			'title'      => __( 'Discussions', 'buddyboss' ),
			'icon'       => array(
				'type'  => 'font',
				'class' => 'bb-icons-rl bb-icons-rl-chats',
			),
			'order'      => 110,
			'is_default' => false,
		)
	);

	// Side Panel 7: Discussion Tags (only when topic tags are enabled).
	if ( bbp_allow_topic_tags() ) {
		bb_register_side_panel(
			'forums',
			'discussion_tags',
			array(
				'title'      => __( 'Discussion Tags', 'buddyboss' ),
				'icon'       => array(
					'type'  => 'font',
					'class' => 'bb-icons-rl bb-icons-rl-tag',
				),
				'order'      => 120,
				'is_default' => false,
			)
		);
	}

	// Side Panel 8: Replies.
	bb_register_side_panel(
		'forums',
		'replies',
		array(
			'title'      => __( 'Replies', 'buddyboss' ),
			'icon'       => array(
				'type'  => 'font',
				'class' => 'bb-icons-rl bb-icons-rl-arrow-bend-up-left',
			),
			'order'      => 130,
			'is_default' => false,
		)
	);

	// =========================================================================
	// PANEL FIELDS
	// =========================================================================

	// Panel 1: Forum Settings.
	bb_forums_register_settings_panel_fields();

	// Panel 2: Forum Features.
	bb_forums_register_features_panel_fields();

	// Panel 3: Forum Directories.
	bb_forums_register_directories_panel_fields();

	// Panel 4: Permalinks.
	bb_forums_register_permalinks_panel_fields();

	/**
	 * Fires after all Forums settings panels are registered.
	 * Allows third-party extensions to add more panels or fields.
	 *
	 * @since BuddyBoss 3.0.0
	 */
	do_action( 'bb_forums_after_register_settings_fields' );
}

add_action( 'bb_register_features', 'bb_admin_settings_register_forums_feature', 25 );

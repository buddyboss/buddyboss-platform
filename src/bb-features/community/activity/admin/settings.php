<?php
/**
 * BuddyBoss Admin Settings - Activity Feature Settings Registration.
 *
 * Orchestrates Activity feature settings by registering side panels
 * and loading panel-specific field registrations.
 *
 * @package BuddyBoss\Features\Community\Activity
 * @since BuddyBoss [BBVERSION]
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

// Load sanitize callbacks.
require_once __DIR__ . '/callbacks.php';

// Load panel field registrations.
require_once __DIR__ . '/settings-activity.php';
require_once __DIR__ . '/settings-comments.php';
require_once __DIR__ . '/settings-topics.php';
require_once __DIR__ . '/settings-visibility.php';

/**
 * Register Activity feature settings in Feature Registry.
 *
 * Registers side panels and delegates field registration to panel-specific functions.
 *
 * @since BuddyBoss [BBVERSION]
 */
function bb_admin_settings_register_activity_settings() {

	// =========================================================================
	// SIDE PANELS
	// =========================================================================

	// Side Panel 1: Activity Settings (default).
	bb_register_side_panel(
		'activity',
		'activity_settings',
		array(
			'title'      => __( 'Activity Settings', 'buddyboss' ),
			'icon'       => array(
				'type'  => 'font',
				'class' => 'bb-icons-rl bb-icons-rl-gear-six',
			),
			'help_url'   => bp_get_admin_url(
				add_query_arg(
					array(
						'page'    => 'bp-help',
						'article' => 62793,
					),
					'admin.php'
				)
			),
			'order'      => 10,
			'is_default' => true,
		)
	);

	// Side Panel 2: Activity Comments.
	bb_register_side_panel(
		'activity',
		'activity_comments',
		array(
			'title'      => __( 'Activity Comments', 'buddyboss' ),
			'icon'       => array(
				'type'  => 'font',
				'class' => 'bb-icons-rl bb-icons-rl-chat-circle',
			),
			'help_url'   => bp_get_admin_url(
				add_query_arg(
					array(
						'page'    => 'bp-help',
						'article' => 127431,
					),
					'admin.php'
				)
			),
			'order'      => 20,
			'is_default' => false,
		)
	);

	// Side Panel 3: Activity Topics.
	bb_register_side_panel(
		'activity',
		'activity_topics',
		array(
			'title'      => __( 'Activity Topics', 'buddyboss' ),
			'icon'       => array(
				'type'  => 'font',
				'class' => 'bb-icons-rl bb-icons-rl-squares-four',
			),
			'help_url'   => bp_get_admin_url(
				add_query_arg(
					array(
						'page'    => 'bp-help',
						'article' => 128458,
					),
					'admin.php'
				)
			),
			'order'      => 30,
			'is_default' => false,
		)
	);

	// Side Panel 4: Posts Visibility.
	bb_register_side_panel(
		'activity',
		'posts_visibility',
		array(
			'title'      => __( 'Posts Visibility', 'buddyboss' ),
			'icon'       => array(
				'type'  => 'font',
				'class' => 'bb-icons-rl bb-icons-rl-eye',
			),
			'help_url'   => '',
			'order'      => 40,
			'is_default' => false,
		)
	);

	// Side Panel: All Activities (navigation link).
	bb_register_side_panel(
		'activity',
		'all_activities',
		array(
			'title'      => __( 'All Activities', 'buddyboss' ),
			'icon'       => array(
				'type'  => 'font',
				'class' => 'bb-icons-rl bb-icons-rl-arrow-square-out',
			),
			'link'       => admin_url( 'edit.php?post_type=&page=bp-activity' ),
			'order'      => 100,
			'is_default' => false,
		)
	);

	// =========================================================================
	// PANEL FIELDS
	// =========================================================================

	// Panel 1: Activity Settings.
	bb_activity_register_settings_panel_fields();

	// Panel 2: Activity Comments (shares edit_time_options with Panel 1).
	$edit_time_options = array(
		array(
			'label' => __( 'Forever', 'buddyboss' ),
			'value' => -1,
		),
	);
	foreach ( bp_activity_edit_times() as $time ) {
		$edit_time_options[] = array(
			'label' => $time['label'],
			'value' => $time['value'],
		);
	}
	bb_activity_register_comments_panel_fields( $edit_time_options );

	// Panel 3: Activity Topics.
	bb_activity_register_topics_panel_fields();

	// Panel 4: Posts Visibility.
	bb_activity_register_visibility_panel_fields();

	/**
	 * Fires after all Activity settings panels are registered.
	 * Allows third-party extensions to add more panels or fields.
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	do_action( 'bb_activity_after_register_settings_fields' );
}

add_action( 'bb_register_features', 'bb_admin_settings_register_activity_settings', 20 );

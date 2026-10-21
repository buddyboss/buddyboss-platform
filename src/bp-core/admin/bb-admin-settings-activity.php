<?php
/**
 * BuddyBoss Admin Settings - Activity Feature Registration.
 *
 * Registers the Activity feature in the Feature Registry and loads
 * all Activity settings (side panels, sections, fields).
 *
 * @package BuddyBoss\Core\Administration
 * @since BuddyBoss 3.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Register Activity feature and settings in Feature Registry.
 *
 * Registers the feature, side panels, and delegates field registration
 * to panel-specific functions.
 *
 * @since BuddyBoss 3.0.0
 */
function bb_admin_settings_register_activity_feature() {

	// =========================================================================
	// REGISTER FEATURE
	// =========================================================================

	bb_register_feature(
		'activity',
		array(
			'label'              => __( 'Activity Feeds', 'buddyboss' ),
			'description'        => __( 'Provide global, personal, and group activity feeds that support threaded commenting, direct posting, @mentions, and email notifications.', 'buddyboss' ),
			'icon'               => array(
				'type'  => 'font',
				'class' => 'bb-icons-rl bb-icons-rl-pulse',
			),
			'license_tier'       => 'free',
			'category'           => 'community',
			'standalone'         => true,
			'is_active_callback' => function () {
				return bp_is_active( 'activity' );
			},
			'settings_route'     => '/settings/activity',
			'order'              => 70,
		)
	);

	// When activity is disabled, only the feature card is needed (so admin can re-enable).
	// Side panels, sections, and fields depend on activity functions that aren't loaded.
	if ( ! bp_is_active( 'activity' ) ) {
		return;
	}

	// Load settings sub-files only when activity is active to avoid parsing ~1,000 lines of
	// callbacks, field registrations, and meta-field definitions when the feature is off.
	require_once __DIR__ . '/settings/activity/callbacks.php';
	require_once __DIR__ . '/settings/activity/settings-activity.php';
	require_once __DIR__ . '/settings/activity/settings-comments.php';
	require_once __DIR__ . '/settings/activity/settings-topics.php';
	require_once __DIR__ . '/settings/activity/settings-visibility.php';
	require_once __DIR__ . '/settings/activity/settings-sharing.php';
	require_once __DIR__ . '/settings/activity/settings-access-control.php';
	require_once __DIR__ . '/settings/activity/meta-fields.php';
	require_once __DIR__ . '/settings/activity/legacy-meta-bridge.php';

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
				'class' => 'bb-icons-rl bb-icons-rl-pulse',
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
				'class' => 'bb-icons-rl bb-icons-rl-chat-text',
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

	// Side Panel 5: Activity Sharing.
	bb_register_side_panel(
		'activity',
		'activity_sharing',
		array(
			'title'      => __( 'Activity Sharing', 'buddyboss' ),
			'icon'       => array(
				'type'  => 'font',
				'class' => 'bb-icons-rl bb-icons-rl-share-fat',
			),
			'help_url'   => '637448',
			'order'      => 50,
			'is_default' => false,
		)
	);

	// Side Panel 6: Access Controls.
	bb_register_side_panel(
		'activity',
		'access_controls',
		array(
			'title' => __( 'Access Controls', 'buddyboss' ),
			'icon'  => array(
				'type'  => 'font',
				'class' => 'bb-icons-rl bb-icons-rl-lock-simple',
			),
			'order' => 60,
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
				'class' => 'bb-icons-rl bb-icons-rl-list-dashes',
			),
			'link'       => bp_get_admin_url( 'admin.php?page=bb-settings' ),
			'divider'    => true,
			'order'      => 100,
			'is_default' => false,
		)
	);

	// =========================================================================
	// PANEL FIELDS
	// =========================================================================

	// Build edit time options once; shared by Panel 1 (Edit Activity) and Panel 2 (Edit Comment).
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

	// Panel 1: Activity Settings.
	bb_activity_register_settings_panel_fields( $edit_time_options );

	// Panel 2: Activity Comments.
	bb_activity_register_comments_panel_fields( $edit_time_options );

	// Panel 3: Activity Topics.
	bb_activity_register_topics_panel_fields();

	// Panel 4: Posts Visibility.
	bb_activity_register_visibility_panel_fields();

	// Panel 5: Activity Sharing.
	bb_activity_register_sharing_panel_fields();

	// Panel 6: Access Controls.
	bb_activity_register_access_control_fields();

	/**
	 * Fires after all Activity settings panels are registered.
	 * Allows third-party extensions to add more panels or fields.
	 *
	 * @since BuddyBoss 3.0.0
	 */
	do_action( 'bb_activity_after_register_settings_fields' );

}

add_action( 'bb_register_features', 'bb_admin_settings_register_activity_feature', 20 );

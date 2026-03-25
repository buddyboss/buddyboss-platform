<?php
/**
 * BuddyBoss Admin Settings - Activity Settings Panel.
 *
 * Registers sections and fields for the Activity Settings side panel.
 *
 * @package BuddyBoss\Core\Administration
 * @since BuddyBoss [BBVERSION]
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Register Activity Settings panel sections and fields.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param array $edit_time_options Edit time options array built in bb-admin-settings-activity.php.
 */
function bb_activity_register_settings_panel_fields( $edit_time_options = array() ) {

	// -------------------------------------------------------------------------
	// SECTION: Activity Settings
	// -------------------------------------------------------------------------
	bb_register_feature_section(
		'activity',
		'activity_settings',
		'activity_settings',
		array(
			'title'       => __( 'Activity Settings', 'buddyboss' ),
			'description' => '',
			'order'       => 10,
		)
	);

	bb_register_feature_field(
		'activity',
		'activity_settings',
		'activity_settings',
		array(
			'name'                 => '_bp_enable_activity_edit',
			'label'                => __( 'Edit Activity', 'buddyboss' ),
			'type'                 => 'toggle',
			// translators: %s: Edit time duration select control (e.g. "10 minutes").
			'description'          => __( 'Allow members to edit their activity posts for a duration of %s', 'buddyboss' ),
			'default'              => bp_is_activity_edit_enabled(),
			'sanitize_callback'    => 'absint',
			'description_controls' => array(
				array(
					'type'              => 'select',
					'name'              => '_bp_activity_edit_time',
					'default'           => bp_get_activity_edit_time() ? bp_get_activity_edit_time() : 600,
					'options'           => $edit_time_options,
					'sanitize_callback' => 'bb_activity_sanitize_edit_time',
				),
			),
			'order'                => 10,
		)
	);

	// FIELD: Post Title.
	bb_register_feature_field(
		'activity',
		'activity_settings',
		'activity_settings',
		array(
			'name'              => 'bb_activity_post_title_enabled',
			'label'             => __( 'Post Title', 'buddyboss' ),
			'type'              => 'toggle',
			'description'       => __( 'Make post titles mandatory', 'buddyboss' ),
			'default'           => bb_is_activity_post_title_enabled(),
			'sanitize_callback' => 'absint',
			'order'             => 20,
		)
	);

	// FIELD: Post Feature Image (Pro only).
	bb_register_feature_field(
		'activity',
		'activity_settings',
		'activity_settings',
		array(
			'name'              => 'bb_enable_activity_post_feature_image',
			'label'             => __( 'Post Feature Image', 'buddyboss' ),
			'type'              => 'toggle',
			'description'       => __( 'Allow group owners and moderators to add a featured image to their posts', 'buddyboss' ),
			'default'           => function_exists( 'bb_pro_activity_post_feature_image_instance' ) ? bb_pro_activity_post_feature_image_instance()->bb_is_enabled() : false,
			'sanitize_callback' => 'absint',
			'order'             => 30,
			'pro_only'          => true,
		)
	);

	// FIELD: Activity auto-refresh.
	$heartbeat_disabled = '1' === get_option( 'bp_wp_heartbeat_disabled' );
	$heartbeat_args     = array(
		'name'              => '_bp_enable_heartbeat_refresh',
		'label'             => __( 'Activity auto-refresh', 'buddyboss' ),
		'type'              => 'toggle',
		'description'       => __( 'Automatically check for new activity posts', 'buddyboss' ),
		'default'           => bp_is_activity_heartbeat_active(),
		'sanitize_callback' => 'absint',
		'order'             => 40,
	);

	if ( $heartbeat_disabled ) {
		$heartbeat_args['disabled']  = true;
		$heartbeat_args['help_text'] = __( 'This feature requires the WordPress <a href="https://developer.wordpress.org/plugins/javascript/heartbeat-api/" target="_blank">Heartbeat API</a> to function, which is disabled on your server.', 'buddyboss' );
	}

	bb_register_feature_field(
		'activity',
		'activity_settings',
		'activity_settings',
		$heartbeat_args
	);

	// FIELD: Close Comments.
	bb_register_feature_field(
		'activity',
		'activity_settings',
		'activity_settings',
		array(
			'name'              => '_bb_enable_close_activity_comments',
			'label'             => __( 'Close Comments', 'buddyboss' ),
			'type'              => 'toggle',
			'description'       => __( 'Allow your users to stop users commenting on their posts', 'buddyboss' ),
			'default'           => bb_is_close_activity_comments_enabled(),
			'sanitize_callback' => 'absint',
			'order'             => 50,
		)
	);

	// FIELD: Polls (Pro only).
	bb_register_feature_field(
		'activity',
		'activity_settings',
		'activity_settings',
		array(
			'name'              => '_bb_enable_activity_post_polls',
			'label'             => __( 'Polls', 'buddyboss' ),
			'type'              => 'toggle',
			'description'       => __( 'Allow group owners and moderators to post polls', 'buddyboss' ),
			'default'           => function_exists( 'bb_is_enabled_activity_post_polls' ) ? bb_is_enabled_activity_post_polls( false ) : false,
			'sanitize_callback' => 'absint',
			'order'             => 60,
			'pro_only'          => true,
		)
	);

	// FIELD: Pinned Post.
	bb_register_feature_field(
		'activity',
		'activity_settings',
		'activity_settings',
		array(
			'name'              => '_bb_enable_activity_pinned_posts',
			'label'             => __( 'Pinned Post', 'buddyboss' ),
			'type'              => 'toggle',
			'description'       => __( 'Allow group owners and moderators to pin posts', 'buddyboss' ),
			'default'           => bb_is_active_activity_pinned_posts(),
			'sanitize_callback' => 'absint',
			'order'             => 70,
		)
	);

	// FIELD: Schedule Posts (Pro only).
	bb_register_feature_field(
		'activity',
		'activity_settings',
		'activity_settings',
		array(
			'name'              => '_bb_enable_activity_schedule_posts',
			'label'             => __( 'Schedule Posts', 'buddyboss' ),
			'type'              => 'toggle',
			'description'       => __( 'Allow group owners and moderators to schedule their posts', 'buddyboss' ),
			'default'           => function_exists( 'bb_is_enabled_activity_schedule_posts_filter' ) ? bb_is_enabled_activity_schedule_posts_filter() : false,
			'sanitize_callback' => 'absint',
			'order'             => 80,
			'pro_only'          => true,
		)
	);

	// FIELD: Follow.
	bb_register_feature_field(
		'activity',
		'activity_settings',
		'activity_settings',
		array(
			'name'              => '_bp_enable_activity_follow',
			'label'             => __( 'Follow', 'buddyboss' ),
			'type'              => 'toggle',
			'description'       => __( 'Allow your members to follow the activity of each other on their timeline', 'buddyboss' ),
			'default'           => bp_is_activity_follow_active(),
			'sanitize_callback' => 'absint',
			'order'             => 90,
		)
	);

	// FIELD: Relevant Activity.
	bb_register_feature_field(
		'activity',
		'activity_settings',
		'activity_settings',
		array(
			'name'              => '_bp_enable_relevant_feed',
			'label'             => __( 'Relevant Activity', 'buddyboss' ),
			'type'              => 'toggle',
			'description'       => __( 'Show only relevant posts to the logged-in member in the activity feed.', 'buddyboss' ),
			'help_text'         => __( 'When checked, logged-in members will see activity from their timeline, connections, followed members, joined groups, subscribed forums, and mentions.', 'buddyboss' ),
			'default'           => bp_is_relevant_feed_enabled(),
			'sanitize_callback' => 'absint',
			'order'             => 100,
		)
	);

	/**
	 * Fires after Activity Settings section fields are registered.
	 * Allows third-party extensions to add more fields.
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	do_action( 'bb_activity_settings_after_settings_fields' );

	// -------------------------------------------------------------------------
	// SECTION: Activity Feed
	// -------------------------------------------------------------------------
	bb_register_feature_section(
		'activity',
		'activity_settings',
		'activity_feed',
		array(
			'title'       => __( 'Activity Feed', 'buddyboss' ),
			'description' => '',
			'order'       => 20,
		)
	);

	// FIELD: Activity Search.
	bb_register_feature_field(
		'activity',
		'activity_settings',
		'activity_feed',
		array(
			'name'              => 'bb_enable_activity_search',
			'label'             => __( 'Activity Search', 'buddyboss' ),
			'type'              => 'toggle',
			'description'       => __( 'Allow members to search activity posts', 'buddyboss' ),
			'default'           => bb_is_activity_search_enabled(),
			'sanitize_callback' => 'absint',
			'order'             => 10,
		)
	);

	// Activity Feed Filters: options from core labels (same keys/labels as legacy).
	$activity_feed_filter_options = array();
	if ( function_exists( 'bb_get_activity_filter_options_labels' ) ) {
		foreach ( bb_get_activity_filter_options_labels() as $value => $label_or ) {
			$label  = is_array( $label_or ) && isset( $label_or['default'] ) ? $label_or['default'] : ( is_array( $label_or ) ? '' : $label_or );
			$option = array(
				'label' => $label,
				'value' => $value,
			);

			// "All Updates" must always remain enabled.
			if ( 'all' === $value ) {
				$option['disabled'] = true;
			}

			$activity_feed_filter_options[] = $option;
		}
	}
	// FIELD: Activity Feed Filters (checkbox list with drag-and-drop). Option name and value keys match legacy (bp_get_option( 'bb_activity_filter_options' )).
	bb_register_feature_field(
		'activity',
		'activity_settings',
		'activity_feed',
		array(
			'name'        => 'bb_activity_filter_options',
			'label'       => __( 'Activity Feed Filters', 'buddyboss' ),
			'type'        => 'checkbox_list',
			'description' => __( 'Allow members to filter activity posts by:', 'buddyboss' ),
			'default'           => bb_get_enabled_activity_filter_options(),
			'options'           => $activity_feed_filter_options,
			'sanitize_callback' => 'bb_sanitize_checkbox_list',
			'order'             => 20,
		)
	);

	// Profile Timeline Filters: options from core labels (same keys/labels as legacy).
	$activity_timeline_filter_options = array();
	if ( function_exists( 'bb_get_activity_timeline_filter_options_labels' ) ) {
		foreach ( bb_get_activity_timeline_filter_options_labels() as $value => $label_or ) {
			$label  = is_array( $label_or ) && isset( $label_or['default'] ) ? $label_or['default'] : ( is_array( $label_or ) ? '' : $label_or );
			$option = array(
				'label' => $label,
				'value' => $value,
			);

			// "Personal Posts" must always remain enabled.
			if ( 'just-me' === $value ) {
				$option['disabled'] = true;
			}

			$activity_timeline_filter_options[] = $option;
		}
	}
	// FIELD: Profile Timeline Filters (checkbox list with drag-and-drop). Option name and value keys match legacy (bp_get_option( 'bb_activity_timeline_filter_options' )).
	bb_register_feature_field(
		'activity',
		'activity_settings',
		'activity_feed',
		array(
			'name'        => 'bb_activity_timeline_filter_options',
			'label'       => __( 'Profile Timeline Filters', 'buddyboss' ),
			'type'        => 'checkbox_list',
			'description' => __( 'Allow members to filter activity posts by:', 'buddyboss' ),
			'default'     => array(
				'just-me'   => 1,
				'favorites' => 1,
				'groups'    => 1,
				'friends'   => 1,
				'mentions'  => 1,
				'following' => 1,
			),
			'options'           => $activity_timeline_filter_options,
			'sanitize_callback' => 'bb_sanitize_checkbox_list',
			'order'             => 30,
		)
	);

	// Activity Sorting: options from core labels (New Posts, Recent Activity).
	$activity_sorting_options = array();
	if ( function_exists( 'bb_get_activity_sorting_options_labels' ) ) {
		foreach ( bb_get_activity_sorting_options_labels() as $value => $label_or ) {
			$label  = is_array( $label_or ) && isset( $label_or['default'] ) ? $label_or['default'] : ( is_array( $label_or ) ? '' : $label_or );
			$option = array(
				'label' => $label,
				'value' => $value,
			);

			// "New Posts" must always remain enabled.
			if ( 'date_recorded' === $value ) {
				$option['disabled'] = true;
			}

			$activity_sorting_options[] = $option;
		}
	}
	// FIELD: Activity Sorting (checkbox list with drag-and-drop). Option name and value keys match legacy (bp_get_option( 'bb_activity_sorting_options' )).
	bb_register_feature_field(
		'activity',
		'activity_settings',
		'activity_feed',
		array(
			'name'        => 'bb_activity_sorting_options',
			'label'       => __( 'Activity Sorting', 'buddyboss' ),
			'type'        => 'checkbox_list',
			'description' => __( 'Allow members to sort activity posts by:', 'buddyboss' ),
			'default'     => bb_get_enabled_activity_sorting_options(),
			'options'           => $activity_sorting_options,
			'sanitize_callback' => 'bb_sanitize_checkbox_list',
			'order'             => 40,
		)
	);
}

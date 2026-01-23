<?php
/**
 * BuddyBoss Admin Settings 2.0 - Activity Feature Registration
 *
 * Registers Activity feature with the new hierarchy:
 * Feature → Side Panels → Sections → Fields
 *
 * @package BuddyBoss\Core\Administration
 * @since BuddyBoss 3.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Register Activity feature in Feature Registry.
 *
 * @since BuddyBoss 3.0.0
 */
function bb_admin_settings_2_0_register_activity_feature() {
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
			'category'           => 'community',
			'license_tier'       => 'free',
			'is_active_callback' => function () {
				return bp_is_active( 'activity' );
			},
			'php_loader'         => function () {
				$file = buddypress()->plugin_dir . 'bp-activity/bp-activity-loader.php';
				if ( file_exists( $file ) ) {
					require_once $file;
				}
			},
			'settings_route'     => '/settings/activity',
			'order'              => 40,
		)
	);

	// =========================================================================
	// SIDE PANEL: ACTIVITY SETTINGS
	// =========================================================================
	bb_register_side_panel(
		'activity',
		'activity_settings',
		array(
			'title'      => __( 'Activity Settings', 'buddyboss' ),
			'icon'       => array(
				'type' => 'dashicon',
				'slug' => 'dashicons-admin-settings',
			),
			'help_url'   => 'https://www.buddyboss.com/resources/docs/components/activity/activity-settings/',
			'order'      => 10,
			'is_default' => true,
		)
	);

	// Section: Activity Settings
	bb_register_feature_section(
		'activity',
		'activity_settings',
		'main',
		array(
			'title'       => __( 'Activity Settings', 'buddyboss' ),
			'description' => '',
			'order'       => 10,
		)
	);

	// Field: Edit Activity
	bb_register_feature_field(
		'activity',
		'activity_settings',
		'main',
		array(
			'name'              => '_bp_enable_activity_edit',
			'label'             => __( 'Edit Activity', 'buddyboss' ),
			'type'              => 'toggle',
			'description'       => __( 'Allow members to edit their activity posts for a duration of time.', 'buddyboss' ),
			'default'           => bp_get_option( '_bp_enable_activity_edit', 0 ),
			'sanitize_callback' => 'intval',
			'order'             => 10,
		)
	);

	// Field: Post Title
	bb_register_feature_field(
		'activity',
		'activity_settings',
		'main',
		array(
			'name'              => 'bb_activity_post_title_enabled',
			'label'             => __( 'Post Title', 'buddyboss' ),
			'type'              => 'toggle',
			'description'       => __( 'Allow members to add a title to their activity posts.', 'buddyboss' ),
			'default'           => bp_get_option( 'bb_activity_post_title_enabled', 0 ),
			'sanitize_callback' => 'intval',
			'order'             => 20,
		)
	);

	// Field: Post Feature Image
	bb_register_feature_field(
		'activity',
		'activity_settings',
		'main',
		array(
			'name'              => 'bb_enable_activity_post_feature_image',
			'label'             => __( 'Post Feature Image', 'buddyboss' ),
			'type'              => 'toggle',
			'description'       => __( 'Allow members to add a feature image to their activity posts.', 'buddyboss' ),
			'default'           => bp_get_option( 'bb_enable_activity_post_feature_image', 0 ),
			'sanitize_callback' => 'intval',
			'pro_only'          => true,
			'license_tier'      => 'pro',
			'order'             => 30,
		)
	);

	// Field: Activity auto-refresh
	bb_register_feature_field(
		'activity',
		'activity_settings',
		'main',
		array(
			'name'              => '_bp_enable_heartbeat_refresh',
			'label'             => __( 'Activity auto-refresh', 'buddyboss' ),
			'type'              => 'toggle',
			'description'       => __( 'Automatically refresh the activity feed to show new posts.', 'buddyboss' ),
			'default'           => bp_get_option( '_bp_enable_heartbeat_refresh', 0 ),
			'sanitize_callback' => 'intval',
			'order'             => 40,
		)
	);

	// Field: Close Comments
	bb_register_feature_field(
		'activity',
		'activity_settings',
		'main',
		array(
			'name'              => '_bb_enable_close_activity_comments',
			'label'             => __( 'Close Comments', 'buddyboss' ),
			'type'              => 'toggle',
			'description'       => __( 'Allow members to close comments on their activity posts.', 'buddyboss' ),
			'default'           => bp_get_option( '_bb_enable_close_activity_comments', 0 ),
			'sanitize_callback' => 'intval',
			'order'             => 50,
		)
	);

	// Field: Polls
	bb_register_feature_field(
		'activity',
		'activity_settings',
		'main',
		array(
			'name'              => '_bb_enable_activity_post_polls',
			'label'             => __( 'Polls', 'buddyboss' ),
			'type'              => 'toggle',
			'description'       => __( 'Allow members to create polls in their activity posts.', 'buddyboss' ),
			'default'           => bp_get_option( '_bb_enable_activity_post_polls', 0 ),
			'sanitize_callback' => 'intval',
			'pro_only'          => true,
			'license_tier'      => 'pro',
			'order'             => 60,
		)
	);

	// Field: Pinned Post
	bb_register_feature_field(
		'activity',
		'activity_settings',
		'main',
		array(
			'name'              => '_bb_enable_activity_pinned_posts',
			'label'             => __( 'Pinned Post', 'buddyboss' ),
			'type'              => 'toggle',
			'description'       => __( 'Allow pinning activity posts to the top of the feed.', 'buddyboss' ),
			'default'           => bp_get_option( '_bb_enable_activity_pinned_posts', 0 ),
			'sanitize_callback' => 'intval',
			'order'             => 70,
		)
	);

	// Field: Schedule Posts
	bb_register_feature_field(
		'activity',
		'activity_settings',
		'main',
		array(
			'name'              => '_bb_enable_activity_schedule_posts',
			'label'             => __( 'Schedule Posts', 'buddyboss' ),
			'type'              => 'toggle',
			'description'       => __( 'Allow members to schedule their activity posts for later.', 'buddyboss' ),
			'default'           => bp_get_option( '_bb_enable_activity_schedule_posts', 0 ),
			'sanitize_callback' => 'intval',
			'pro_only'          => true,
			'license_tier'      => 'pro',
			'order'             => 80,
		)
	);

	// Field: Follow
	bb_register_feature_field(
		'activity',
		'activity_settings',
		'main',
		array(
			'name'              => '_bp_enable_activity_follow',
			'label'             => __( 'Follow', 'buddyboss' ),
			'type'              => 'toggle',
			'description'       => __( 'Allow members to follow other members and see their activity.', 'buddyboss' ),
			'default'           => bp_get_option( '_bp_enable_activity_follow', 0 ),
			'sanitize_callback' => 'intval',
			'order'             => 90,
		)
	);

	// Field: Relevant Activity
	bb_register_feature_field(
		'activity',
		'activity_settings',
		'main',
		array(
			'name'              => '_bp_enable_relevant_feed',
			'label'             => __( 'Relevant Activity', 'buddyboss' ),
			'type'              => 'toggle',
			'description'       => __( 'Show relevant activity posts based on member connections and interests.', 'buddyboss' ),
			'default'           => bp_get_option( '_bp_enable_relevant_feed', 0 ),
			'sanitize_callback' => 'intval',
			'order'             => 100,
		)
	);

	// =========================================================================
	// SECTION: ACTIVITY FEED (under activity_settings side panel)
	// =========================================================================
	// Section: Activity Feed - use unique section_id 'activity_feed'
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

	// Field: Activity Search
	bb_register_feature_field(
		'activity',
		'activity_settings',
		'activity_feed',
		array(
			'name'              => 'bb_enable_activity_search',
			'label'             => __( 'Activity Search', 'buddyboss' ),
			'type'              => 'toggle',
			'description'       => __( 'Enable search functionality in the activity feed.', 'buddyboss' ),
			'default'           => bp_get_option( 'bb_enable_activity_search', 0 ),
			'sanitize_callback' => 'intval',
			'order'             => 10,
		)
	);

	// Field: Activity Feed Filters
	// Note: Keys must match bb_get_activity_filter_options_labels() in bp-core-options.php
	bb_register_feature_field(
		'activity',
		'activity_settings',
		'activity_feed',
		array(
			'name'              => 'bb_activity_filter_options',
			'label'             => __( 'Activity Feed Filters', 'buddyboss' ),
			'type'              => 'checkbox_list',
			'description'       => __( 'Select which filters to display on the activity feed.', 'buddyboss' ),
			'default'           => bb_get_enabled_activity_filter_options(),
			'options'           => array(
				array(
					'label' => __( 'All updates', 'buddyboss' ),
					'value' => 'all',
				),
				array(
					'label' => __( 'Created by me', 'buddyboss' ),
					'value' => 'just-me',
				),
				array(
					'label' => __( "I've reacted to", 'buddyboss' ),
					'value' => 'favorites',
				),
				array(
					'label' => __( 'From my groups', 'buddyboss' ),
					'value' => 'groups',
				),
				array(
					'label' => __( 'From my connections', 'buddyboss' ),
					'value' => 'friends',
				),
				array(
					'label' => __( "I'm mentioned in", 'buddyboss' ),
					'value' => 'mentions',
				),
				array(
					'label' => __( "I'm following", 'buddyboss' ),
					'value' => 'following',
				),
				array(
					'label' => __( 'Unanswered', 'buddyboss' ),
					'value' => 'unanswered',
				),
			),
			'sanitize_callback' => 'bb_sanitize_checkbox_list',
			'order'             => 20,
		)
	);

	// Field: Profile Timeline Filters
	// Note: Stored as associative array { "key": "1", "key2": "0" }
	bb_register_feature_field(
		'activity',
		'activity_settings',
		'activity_feed',
		array(
			'name'              => 'bb_activity_timeline_filter_options',
			'label'             => __( 'Profile Timeline Filters', 'buddyboss' ),
			'type'              => 'checkbox_list',
			'description'       => __( 'Select which filters to display on member profile timelines.', 'buddyboss' ),
			'default'           => array(
				'just-me'   => 1,
				'favorites' => 1,
				'groups'    => 1,
				'friends'   => 1,
				'mentions'  => 1,
				'following' => 1,
			),
			'options'           => array(
				array(
					'label' => __( 'Personal posts', 'buddyboss' ),
					'value' => 'just-me',
				),
				array(
					'label' => __( 'Reacted to', 'buddyboss' ),
					'value' => 'favorites',
				),
				array(
					'label' => __( 'From groups', 'buddyboss' ),
					'value' => 'groups',
				),
				array(
					'label' => __( 'From connections', 'buddyboss' ),
					'value' => 'friends',
				),
				array(
					'label' => __( 'Mentioned in', 'buddyboss' ),
					'value' => 'mentions',
				),
				array(
					'label' => __( 'Following', 'buddyboss' ),
					'value' => 'following',
				),
			),
			'sanitize_callback' => 'bb_sanitize_checkbox_list',
			'order'             => 30,
		)
	);

	// Field: Activity Sorting.
	bb_register_feature_field(
		'activity',
		'activity_settings',
		'activity_feed',
		array(
			'name'              => 'bb_activity_sorting_options',
			'label'             => __( 'Activity Sorting', 'buddyboss' ),
			'type'              => 'checkbox_list',
			'description'       => __( 'Select which sorting options to display on the activity feed.', 'buddyboss' ),
			'default'           => bb_get_enabled_activity_sorting_options(),
			'options'           => array(
				array(
					'label' => __( 'Newest', 'buddyboss' ),
					'value' => 'date_recorded',
				),
				array(
					'label' => __( 'Popular', 'buddyboss' ),
					'value' => 'date_updated',
				),
			),
			'sanitize_callback' => 'bb_sanitize_checkbox_list',
			'order'             => 40,
		)
	);

	// =========================================================================
	// SIDE PANEL: ACTIVITY COMMENTS
	// =========================================================================
	bb_register_side_panel(
		'activity',
		'activity_comments',
		array(
			'title'    => __( 'Activity Comments', 'buddyboss' ),
			'icon'     => array(
				'type' => 'dashicon',
				'slug' => 'dashicons-admin-comments',
			),
			'help_url' => 'https://www.buddyboss.com/resources/docs/components/activity/activity-comments/',
			'order'    => 30,
		)
	);

	// Section: Activity Comments
	bb_register_feature_section(
		'activity',
		'activity_comments',
		'main',
		array(
			'title'       => __( 'Activity Comments', 'buddyboss' ),
			'description' => sprintf(
				wp_kses_post(
					__( 'WordPress post and custom post types will inherit from your WordPress %s settings.', 'buddyboss' )
				),
				'<a href="options-discussion.php" target="_blank">' . esc_html__( 'Discussion', 'buddyboss' ) . '</a>'
			),
			'order'       => 10,
		)
	);

	// Field: Activity Comments toggle
	bb_register_feature_field(
		'activity',
		'activity_comments',
		'main',
		array(
			'name'              => '_bb_enable_activity_comments',
			'label'             => __( 'Activity Comments', 'buddyboss' ),
			'type'              => 'toggle',
			'description'       => __( 'Allow members to post comments on activity posts. Disabling this will hide the comments for all posts, even if there are existing comments.', 'buddyboss' ),
			'default'           => bp_get_option( '_bb_enable_activity_comments', 1 ),
			'sanitize_callback' => 'intval',
			'order'             => 10,
		)
	);

	// Field: Comment Edit toggle
	bb_register_feature_field(
		'activity',
		'activity_comments',
		'main',
		array(
			'name'              => '_bb_enable_activity_comment_edit',
			'label'             => __( 'Comment Edit', 'buddyboss' ),
			'type'              => 'toggle',
			'description'       => __( 'Allow members to edit their activity comments for a short period of time after posting.', 'buddyboss' ),
			'default'           => bp_get_option( '_bb_enable_activity_comment_edit', 0 ),
			'sanitize_callback' => 'intval',
			'order'             => 20,
		)
	);

	// Field: Edit time limit (child of Comment Edit)
	bb_register_feature_field(
		'activity',
		'activity_comments',
		'main',
		array(
			'name'              => '_bb_activity_comment_edit_time',
			'label'             => __( 'Edit time limit', 'buddyboss' ),
			'type'              => 'number',
			'description'       => '',
			'default'           => bp_get_option( '_bb_activity_comment_edit_time', 10 ),
			'sanitize_callback' => 'intval',
			'order'             => 25,
			'parent_field'      => '_bb_enable_activity_comment_edit', // Show only when Comment Edit is enabled
			'suffix'            => __( 'Minutes', 'buddyboss' ),
		)
	);

	// Field: Comment Threading toggle
	bb_register_feature_field(
		'activity',
		'activity_comments',
		'main',
		array(
			'name'              => '_bb_enable_activity_comment_threading',
			'label'             => __( 'Comment Threading', 'buddyboss' ),
			'type'              => 'toggle',
			'description'       => __( 'Allow activity comment threading so members can reply to individual comments.', 'buddyboss' ),
			'default'           => bp_get_option( '_bb_enable_activity_comment_threading', 0 ),
			'sanitize_callback' => 'intval',
			'order'             => 30,
		)
	);

	// Field: Threading levels (child of Comment Threading)
	bb_register_feature_field(
		'activity',
		'activity_comments',
		'main',
		array(
			'name'              => '_bb_activity_comment_threading_depth',
			'label'             => __( 'Threading levels', 'buddyboss' ),
			'type'              => 'number',
			'description'       => '',
			'default'           => bp_get_option( '_bb_activity_comment_threading_depth', 3 ),
			'sanitize_callback' => 'intval',
			'order'             => 35,
			'parent_field'      => '_bb_enable_activity_comment_threading', // Show only when Threading is enabled
			'suffix'            => __( 'Levels', 'buddyboss' ),
		)
	);

	// Field: Comment Visibility toggle
	bb_register_feature_field(
		'activity',
		'activity_comments',
		'main',
		array(
			'name'              => '_bb_activity_comment_visibility',
			'label'             => __( 'Comment Visibility', 'buddyboss' ),
			'type'              => 'toggle',
			'description'       => __( 'Control which comments to show per activity post.', 'buddyboss' ),
			'default'           => bp_get_option( '_bb_activity_comment_visibility', 0 ),
			'sanitize_callback' => 'intval',
			'order'             => 60,
		)
	);

	// Field: Comments Loading dropdown
	bb_register_feature_field(
		'activity',
		'activity_comments',
		'main',
		array(
			'name'              => '_bb_activity_comment_loading',
			'label'             => __( 'Comments Loading', 'buddyboss' ),
			'type'              => 'select',
			'description'       => __( 'Select which comments are loaded when the page loads.', 'buddyboss' ),
			'default'           => bp_get_option( '_bb_activity_comment_loading', 'all' ),
			'options'           => array(
				array(
					'label' => __( 'All comments', 'buddyboss' ),
					'value' => 'all',
				),
				array(
					'label' => __( 'Latest comments', 'buddyboss' ),
					'value' => 'latest',
				),
				array(
					'label' => __( 'Oldest comments', 'buddyboss' ),
					'value' => 'oldest',
				),
			),
			'sanitize_callback' => 'sanitize_text_field',
			'order'             => 70,
		)
	);

	// =========================================================================
	// SIDE PANEL: ACTIVITY TOPICS
	// =========================================================================
	bb_register_side_panel(
		'activity',
		'activity_topics',
		array(
			'title'    => __( 'Activity Topics', 'buddyboss' ),
			'icon'     => array(
				'type' => 'dashicon',
				'slug' => 'dashicons-tag',
			),
			'help_url' => 'https://www.buddyboss.com/resources/docs/components/activity/activity-topics/',
			'order'    => 40,
		)
	);

	// Section: Activity Topics
	bb_register_feature_section(
		'activity',
		'activity_topics',
		'main',
		array(
			'title'       => __( 'Activity Topics', 'buddyboss' ),
			'description' => '',
			'order'       => 10,
		)
	);

	// Field: Enable Topics
	bb_register_feature_field(
		'activity',
		'activity_topics',
		'main',
		array(
			'name'              => 'bb_enable_activity_topics',
			'label'             => __( 'Enable Topics', 'buddyboss' ),
			'type'              => 'toggle',
			'description'       => __( 'Allow members to add topics/tags to their activity posts.', 'buddyboss' ),
			'default'           => bp_get_option( 'bb_enable_activity_topics', 0 ),
			'sanitize_callback' => 'intval',
			'order'             => 10,
		)
	);

	// =========================================================================
	// SIDE PANEL: POSTS VISIBILITY
	// =========================================================================
	bb_register_side_panel(
		'activity',
		'posts_visibility',
		array(
			'title'    => __( 'Posts Visibility', 'buddyboss' ),
			'icon'     => array(
				'type' => 'dashicon',
				'slug' => 'dashicons-visibility',
			),
			'help_url' => 'https://www.buddyboss.com/resources/docs/components/activity/posts-visibility/',
			'order'    => 50,
		)
	);

	// Section: Posts Visibility
	bb_register_feature_section(
		'activity',
		'posts_visibility',
		'main',
		array(
			'title'       => __( 'Posts Visibility', 'buddyboss' ),
			'description' => '',
			'order'       => 10,
		)
	);

	// Field: Default visibility
	bb_register_feature_field(
		'activity',
		'posts_visibility',
		'main',
		array(
			'name'              => '_bp_activity_visibility',
			'label'             => __( 'Default Visibility', 'buddyboss' ),
			'type'              => 'select',
			'description'       => __( 'Default visibility for activity posts.', 'buddyboss' ),
			'default'           => bp_get_option( '_bp_activity_visibility', 'public' ),
			'options'           => array(
				array(
					'label' => __( 'Public', 'buddyboss' ),
					'value' => 'public',
				),
				array(
					'label' => __( 'Logged-in Members', 'buddyboss' ),
					'value' => 'loggedin',
				),
				array(
					'label' => __( 'My Connections', 'buddyboss' ),
					'value' => 'friends',
				),
				array(
					'label' => __( 'Only Me', 'buddyboss' ),
					'value' => 'onlyme',
				),
			),
			'sanitize_callback' => 'sanitize_text_field',
			'order'             => 10,
		)
	);

	// Field: Allow members to control
	bb_register_feature_field(
		'activity',
		'posts_visibility',
		'main',
		array(
			'name'              => '_bp_enable_activity_visibility_control',
			'label'             => __( 'Allow Members to Control', 'buddyboss' ),
			'type'              => 'toggle',
			'description'       => __( 'Allow members to control visibility of their activity posts.', 'buddyboss' ),
			'default'           => bp_get_option( '_bp_enable_activity_visibility_control', 1 ),
			'sanitize_callback' => 'intval',
			'order'             => 20,
		)
	);

	// =========================================================================
	// SIDE PANEL: ACTIVITY SHARING
	// =========================================================================
	bb_register_side_panel(
		'activity',
		'activity_sharing',
		array(
			'title'    => __( 'Activity Sharing', 'buddyboss' ),
			'icon'     => array(
				'type' => 'dashicon',
				'slug' => 'dashicons-share',
			),
			'help_url' => 'https://www.buddyboss.com/resources/docs/components/activity/activity-sharing/',
			'order'    => 60,
		)
	);

	// Section: Activity Sharing
	bb_register_feature_section(
		'activity',
		'activity_sharing',
		'main',
		array(
			'title'       => __( 'Activity Sharing', 'buddyboss' ),
			'description' => '',
			'order'       => 10,
		)
	);

	// Field: Enable sharing
	bb_register_feature_field(
		'activity',
		'activity_sharing',
		'main',
		array(
			'name'              => '_bb_enable_activity_share',
			'label'             => __( 'Enable Sharing', 'buddyboss' ),
			'type'              => 'toggle',
			'description'       => __( 'Allow members to share activity posts.', 'buddyboss' ),
			'default'           => bp_get_option( '_bb_enable_activity_share', 0 ),
			'sanitize_callback' => 'intval',
			'order'             => 10,
		)
	);

	// =========================================================================
	// SIDE PANEL: ACCESS CONTROLS
	// =========================================================================
	bb_register_side_panel(
		'activity',
		'access_controls',
		array(
			'title'    => __( 'Access Controls', 'buddyboss' ),
			'icon'     => array(
				'type' => 'dashicon',
				'slug' => 'dashicons-lock',
			),
			'help_url' => 'https://www.buddyboss.com/resources/docs/components/activity/access-controls/',
			'order'    => 70,
		)
	);

	// Section: Access Controls
	bb_register_feature_section(
		'activity',
		'access_controls',
		'main',
		array(
			'title'       => __( 'Access Controls', 'buddyboss' ),
			'description' => '',
			'order'       => 10,
		)
	);

	// Field: Who can post
	bb_register_feature_field(
		'activity',
		'access_controls',
		'main',
		array(
			'name'              => '_bb_activity_post_access',
			'label'             => __( 'Who Can Post', 'buddyboss' ),
			'type'              => 'select',
			'description'       => __( 'Select who can post activity updates.', 'buddyboss' ),
			'default'           => bp_get_option( '_bb_activity_post_access', 'all' ),
			'options'           => array(
				array(
					'label' => __( 'All Members', 'buddyboss' ),
					'value' => 'all',
				),
				array(
					'label' => __( 'Administrators Only', 'buddyboss' ),
					'value' => 'admins',
				),
				array(
					'label' => __( 'Specific Roles', 'buddyboss' ),
					'value' => 'roles',
				),
			),
			'sanitize_callback' => 'sanitize_text_field',
			'order'             => 10,
		)
	);

	// Field: Who can comment
	bb_register_feature_field(
		'activity',
		'access_controls',
		'main',
		array(
			'name'              => '_bb_activity_comment_access',
			'label'             => __( 'Who Can Comment', 'buddyboss' ),
			'type'              => 'select',
			'description'       => __( 'Select who can comment on activity posts.', 'buddyboss' ),
			'default'           => bp_get_option( '_bb_activity_comment_access', 'all' ),
			'options'           => array(
				array(
					'label' => __( 'All Members', 'buddyboss' ),
					'value' => 'all',
				),
				array(
					'label' => __( 'Connections Only', 'buddyboss' ),
					'value' => 'friends',
				),
				array(
					'label' => __( 'Administrators Only', 'buddyboss' ),
					'value' => 'admins',
				),
			),
			'sanitize_callback' => 'sanitize_text_field',
			'order'             => 20,
		)
	);

	// =========================================================================
	// NAVIGATION ITEM: ALL ACTIVITIES
	// =========================================================================
	bb_register_feature_nav_item(
		'activity',
		array(
			'id'    => 'all_activity',
			'label' => __( 'All Activities', 'buddyboss' ),
			'route' => '/activity/all',
			'icon'  => array(
				'type'  => 'font',
				'class' => 'bb-icons-rl bb-icons-rl-list',
			),
			'order' => 100,
		)
	);
}
add_action( 'bb_register_features', 'bb_admin_settings_2_0_register_activity_feature', 10 );

/**
 * Sanitize checkbox list field.
 *
 * @since BuddyBoss 3.0.0
 *
 * @param mixed $value The value to sanitize.
 * @return array Sanitized array of values.
 */
if ( ! function_exists( 'bb_sanitize_checkbox_list' ) ) {
	function bb_sanitize_checkbox_list( $value ) {
		if ( ! is_array( $value ) ) {
			return array();
		}
		return array_map( 'sanitize_text_field', $value );
	}
}

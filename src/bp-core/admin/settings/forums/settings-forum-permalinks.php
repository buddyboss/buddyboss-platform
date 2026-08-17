<?php
/**
 * BuddyBoss Admin Settings - Forum Permalinks Panel.
 *
 * Registers sections and fields for the Forum Permalinks side panel.
 *
 * @package BuddyBoss\Core\Administration
 * @since BuddyBoss 3.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Register Forum Permalinks panel sections and fields.
 *
 * @since BuddyBoss 3.0.0
 *
 * @return void
 */
function bb_forums_register_permalinks_panel_fields() {

	// -------------------------------------------------------------------------
	// SECTION: Forum Permalinks
	// -------------------------------------------------------------------------
	bb_register_feature_section(
		'forums',
		'forum_permalinks',
		'forum_permalinks_section',
		array(
			'title'       => __( 'Forum Permalinks', 'buddyboss-platform' ),
			'description' => __( 'Custom URL slugs for Forum content. Slugs should be all lowercase and contain only letters, numbers, and hyphens.', 'buddyboss-platform' ),
			'order'       => 10,
			'help_url'    => '636172',
		)
	);

	// FIELD: Forum slug.
	bb_register_feature_field(
		'forums',
		'forum_permalinks',
		'forum_permalinks_section',
		array(
			'name'              => '_bbp_forum_slug',
			'label'             => __( 'Forum', 'buddyboss-platform' ),
			'type'              => 'text',
			'default'           => get_option( '_bbp_forum_slug', 'forum' ),
			'sanitize_callback' => 'bbp_sanitize_slug',
			'order'             => 10,
		)
	);

	// FIELD: Discussion slug.
	bb_register_feature_field(
		'forums',
		'forum_permalinks',
		'forum_permalinks_section',
		array(
			'name'              => '_bbp_topic_slug',
			'label'             => __( 'Discussion', 'buddyboss-platform' ),
			'type'              => 'text',
			'default'           => get_option( '_bbp_topic_slug', 'discussion' ),
			'sanitize_callback' => 'bbp_sanitize_slug',
			'order'             => 20,
		)
	);

	// FIELD: Discussion Tag slug.
	bb_register_feature_field(
		'forums',
		'forum_permalinks',
		'forum_permalinks_section',
		array(
			'name'              => '_bbp_topic_tag_slug',
			'label'             => __( 'Discussion Tag', 'buddyboss-platform' ),
			'type'              => 'text',
			'default'           => get_option( '_bbp_topic_tag_slug', 'discussion-tag' ),
			'sanitize_callback' => 'bbp_sanitize_slug',
			'order'             => 30,
		)
	);

	// FIELD: Discussion View slug.
	bb_register_feature_field(
		'forums',
		'forum_permalinks',
		'forum_permalinks_section',
		array(
			'name'              => '_bbp_view_slug',
			'label'             => __( 'Discussion View', 'buddyboss-platform' ),
			'type'              => 'text',
			'default'           => get_option( '_bbp_view_slug', 'view' ),
			'sanitize_callback' => 'bbp_sanitize_slug',
			'order'             => 40,
		)
	);

	// FIELD: Reply slug.
	bb_register_feature_field(
		'forums',
		'forum_permalinks',
		'forum_permalinks_section',
		array(
			'name'              => '_bbp_reply_slug',
			'label'             => __( 'Reply', 'buddyboss-platform' ),
			'type'              => 'text',
			'default'           => get_option( '_bbp_reply_slug', 'reply' ),
			'sanitize_callback' => 'bbp_sanitize_slug',
			'order'             => 50,
		)
	);

	// FIELD: Search slug.
	bb_register_feature_field(
		'forums',
		'forum_permalinks',
		'forum_permalinks_section',
		array(
			'name'              => '_bbp_search_slug',
			'label'             => __( 'Search', 'buddyboss-platform' ),
			'type'              => 'text',
			'default'           => get_option( '_bbp_search_slug', 'search' ),
			'sanitize_callback' => 'bbp_sanitize_slug',
			'order'             => 60,
		)
	);

	// -------------------------------------------------------------------------
	// SECTION: Forum Profile Permalinks
	// -------------------------------------------------------------------------
	bb_register_feature_section(
		'forums',
		'forum_permalinks',
		'forum_profile_permalinks_section',
		array(
			'title'       => __( 'Forum Profile Permalinks', 'buddyboss-platform' ),
			'description' => __( 'Custom URL slugs for the Forums tab in member profiles. Slugs should be all lowercase and contain only letters, numbers, and hyphens.', 'buddyboss-platform' ),
			'order'       => 20,
			'help_url'    => '636174',
		)
	);

	// FIELD: Replies Created slug.
	bb_register_feature_field(
		'forums',
		'forum_permalinks',
		'forum_profile_permalinks_section',
		array(
			'name'              => '_bbp_reply_archive_slug',
			'label'             => __( 'Replies Created', 'buddyboss-platform' ),
			'type'              => 'text',
			'default'           => bbp_get_reply_archive_slug(),
			'sanitize_callback' => 'bbp_sanitize_slug',
			'order'             => 10,
		)
	);

	// FIELD: Favorite Discussions slug.
	bb_register_feature_field(
		'forums',
		'forum_permalinks',
		'forum_profile_permalinks_section',
		array(
			'name'              => '_bbp_user_favs_slug',
			'label'             => __( 'Favorite Discussions', 'buddyboss-platform' ),
			'type'              => 'text',
			'default'           => bbp_get_user_favorites_slug(),
			'sanitize_callback' => 'bbp_sanitize_slug',
			'order'             => 20,
		)
	);

	// FIELD: Subscriptions slug.
	bb_register_feature_field(
		'forums',
		'forum_permalinks',
		'forum_profile_permalinks_section',
		array(
			'name'              => '_bbp_user_subs_slug',
			'label'             => __( 'Subscriptions', 'buddyboss-platform' ),
			'type'              => 'text',
			'default'           => bbp_get_user_subscriptions_slug(),
			'sanitize_callback' => 'bbp_sanitize_slug',
			'order'             => 30,
		)
	);
}

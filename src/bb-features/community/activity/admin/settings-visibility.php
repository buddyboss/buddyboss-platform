<?php
/**
 * BuddyBoss Admin Settings - Posts Visibility Panel.
 *
 * Registers sections and fields for the Posts Visibility side panel.
 *
 * @package BuddyBoss\Features\Community\Activity
 * @since BuddyBoss [BBVERSION]
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Register Posts Visibility panel sections and fields.
 *
 * @since BuddyBoss [BBVERSION]
 */
function bb_activity_register_visibility_panel_fields() {

	// -------------------------------------------------------------------------
	// SECTION: Posts Visibility in Activity Feed
	// -------------------------------------------------------------------------
	bb_register_feature_section(
		'activity',
		'posts_visibility',
		'posts_visibility',
		array(
			'title'       => __( 'Posts Visibility in Activity Feed', 'buddyboss' ),
			'description' => '',
			'order'       => 10,
		)
	);

	// FIELD: BuddyBoss Platform activity types (toggle list).
	$platform_activity_types = function_exists( 'bp_platform_default_activity_types' ) ? bp_platform_default_activity_types() : array();
	$platform_type_options   = array();

	foreach ( $platform_activity_types as $type ) {
		$option_name            = 'bp-feed-platform-' . $type['activity_name'];
		$platform_type_options[] = array(
			'label'   => $type['activity_label'],
			'value'   => $type['activity_name'],
			'enabled' => function_exists( 'bp_platform_is_feed_enable' ) ? bp_platform_is_feed_enable( $option_name, true ) : true,
		);
	}

	bb_register_feature_field(
		'activity',
		'posts_visibility',
		'posts_visibility',
		array(
			'name'              => 'bp_platform_activity_types',
			'label'             => __( 'BuddyBoss Platform', 'buddyboss' ),
			'type'              => 'toggle_list',
			'description'       => '',
			'options'           => $platform_type_options,
			'default'           => array(),
			'sanitize_callback' => 'bb_activity_sanitize_platform_activity_types',
			'order'             => 10,
		)
	);

	// FIELD: WordPress post type (toggle + comments checkbox).
	$all_feed_post_types    = function_exists( 'bb_feed_post_types' ) ? bb_feed_post_types() : array();
	$wordpress_post_options = array();
	$custom_post_options    = array();

	foreach ( $all_feed_post_types as $post_type ) {
		$post_type_obj       = get_post_type_object( $post_type );
		$post_type_label     = $post_type_obj ? $post_type_obj->labels->name : $post_type;
		$post_option_name    = bb_post_type_feed_option_name( $post_type );
		$comment_option_name = bb_post_type_feed_comment_option_name( $post_type );

		$option_data = array(
			'label'            => $post_type_label,
			'value'            => $post_type,
			'enabled'          => function_exists( 'bp_is_post_type_feed_enable' ) ? bp_is_post_type_feed_enable( $post_type, false ) : false,
			'comments_enabled' => function_exists( 'bb_is_post_type_feed_comment_enable' ) ? bb_is_post_type_feed_comment_enable( $post_type, false ) : false,
			'option_name'      => $post_option_name,
			'comment_option'   => $comment_option_name,
		);

		if ( 'post' === $post_type ) {
			$wordpress_post_options[] = $option_data;
		} else {
			$custom_post_options[] = $option_data;
		}
	}

	if ( ! empty( $wordpress_post_options ) ) {
		bb_register_feature_field(
			'activity',
			'posts_visibility',
			'posts_visibility',
			array(
				'name'              => 'bb_wordpress_post_types',
				'label'             => __( 'WordPress', 'buddyboss' ),
				'type'              => 'toggle_with_checkbox',
				'description'       => '',
				'options'           => $wordpress_post_options,
				'default'           => array(),
				'sanitize_callback' => 'bb_activity_sanitize_post_type_feed',
				'order'             => 20,
			)
		);
	}

	if ( ! empty( $custom_post_options ) ) {
		bb_register_feature_field(
			'activity',
			'posts_visibility',
			'posts_visibility',
			array(
				'name'              => 'bb_custom_post_types',
				'label'             => __( 'Custom Post Types', 'buddyboss' ),
				'type'              => 'toggle_with_checkbox',
				'description'       => __( 'Display custom post type activity in the activity feed when members create, or comment on, custom post types.', 'buddyboss' ),
				'options'           => $custom_post_options,
				'default'           => array(),
				'sanitize_callback' => 'bb_activity_sanitize_post_type_feed',
				'order'             => 30,
			)
		);
	}
}

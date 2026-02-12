<?php
/**
 * BuddyBoss Admin Settings - Posts Visibility Panel.
 *
 * Registers sections and fields for the Posts Visibility side panel.
 *
 * Uses the `group` property to visually group related fields (e.g., a toggle
 * and its dependent checkbox) without borders/spacing between them.
 * Child fields use `conditional` to show/hide based on parent field values,
 * supporting unlimited depth via chaining (A -> B -> C).
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

	// -------------------------------------------------------------------------
	// WordPress post type fields.
	// Toggle + checkbox grouped together. Checkbox depends on toggle via conditional.
	// Option names: bp-feed-custom-post-type-post / bp-feed-custom-post-type-post-comments
	// -------------------------------------------------------------------------
	$all_feed_post_types = function_exists( 'bb_feed_post_types' ) ? bb_feed_post_types() : array();
	$field_order         = 20;

	if ( in_array( 'post', $all_feed_post_types, true ) ) {
		$wp_post_option_name    = bb_post_type_feed_option_name( 'post' );
		$wp_comment_option_name = bb_post_type_feed_comment_option_name( 'post' );

		// Toggle: Show WordPress Posts.
		bb_register_feature_field(
			'activity',
			'posts_visibility',
			'posts_visibility',
			array(
				'name'              => $wp_post_option_name,
				'label'             => __( 'WordPress', 'buddyboss' ),
				'type'              => 'toggle',
				'description'       => __( 'Show WordPress Posts', 'buddyboss' ),
				'help_text'         => __( 'When checked, new blog posts published by members will appear in the activity feed.', 'buddyboss' ),
				'default'           => function_exists( 'bp_is_post_type_feed_enable' ) ? bp_is_post_type_feed_enable( 'post', false ) : false,
				'sanitize_callback' => 'intval',
				'order'             => $field_order,
				'group'             => 'wordpress_feed',
			)
		);

		// Checkbox: Enable WordPress Post comments in the activity feed.
		bb_register_feature_field(
			'activity',
			'posts_visibility',
			'posts_visibility',
			array(
				'name'              => $wp_comment_option_name,
				'label'             => '',
				'type'              => 'checkbox',
				'description'       => __( 'Enable WordPress Post comments in the activity feed', 'buddyboss' ),
				'help_text'         => __( 'Allow members to view and create comments to blog posts in the activity feed.', 'buddyboss' ),
				'default'           => function_exists( 'bb_is_post_type_feed_comment_enable' ) ? bb_is_post_type_feed_comment_enable( 'post', false ) : false,
				'sanitize_callback' => 'intval',
				'order'             => $field_order + 1,
				'group'             => 'wordpress_feed',
				'conditional'       => array(
					'field' => $wp_post_option_name,
					'value' => true,
				),
			)
		);

		$field_order += 10;
	}

	// -------------------------------------------------------------------------
	// Custom Post Types.
	// Each CPT gets a toggle + checkbox pair in its own group.
	// -------------------------------------------------------------------------
	$custom_post_types = array_diff( $all_feed_post_types, array( 'post' ) );

	if ( ! empty( $custom_post_types ) ) {
		$cpt_index = 0;

		foreach ( $custom_post_types as $post_type ) {
			$post_type_obj       = get_post_type_object( $post_type );
			$post_type_label     = $post_type_obj ? $post_type_obj->labels->name : $post_type;
			$post_option_name    = bb_post_type_feed_option_name( $post_type );
			$comment_option_name = bb_post_type_feed_comment_option_name( $post_type );
			$group_id            = 'cpt_feed_' . sanitize_key( $post_type );

			// Toggle: Post type name.
			// Only first CPT gets the "Custom Post Types" label.
			bb_register_feature_field(
				'activity',
				'posts_visibility',
				'posts_visibility',
				array(
					'name'              => $post_option_name,
					'label'             => 0 === $cpt_index ? __( 'Custom Post Types', 'buddyboss' ) : '',
					'type'              => 'toggle',
					'description'       => $post_type_label,
					'default'           => function_exists( 'bp_is_post_type_feed_enable' ) ? bp_is_post_type_feed_enable( $post_type, false ) : false,
					'sanitize_callback' => 'intval',
					'order'             => $field_order,
					'group'             => $group_id,
				)
			);

			// Checkbox: Enable comments.
			bb_register_feature_field(
				'activity',
				'posts_visibility',
				'posts_visibility',
				array(
					'name'              => $comment_option_name,
					'label'             => '',
					'type'              => 'checkbox',
					'description'       => __( 'Enable comments', 'buddyboss' ),
					'default'           => function_exists( 'bb_is_post_type_feed_comment_enable' ) ? bb_is_post_type_feed_comment_enable( $post_type, false ) : false,
					'sanitize_callback' => 'intval',
					'order'             => $field_order + 1,
					'group'             => $group_id,
					'conditional'       => array(
						'field' => $post_option_name,
						'value' => true,
					),
				)
			);

			$field_order += 10;
			$cpt_index++;
		}

		// Help text for Custom Post Types section.
		bb_register_feature_field(
			'activity',
			'posts_visibility',
			'posts_visibility',
			array(
				'name'        => 'bb_custom_post_types_info',
				'label'       => '',
				'type'        => 'notice',
				'notice_type' => 'info',
				'description' => __( 'Select the custom post types to display in the activity feed when members publish them. For each type, you can also choose whether to include comments in the activity posts (if comments are supported).', 'buddyboss' ),
				'order'       => $field_order,
			)
		);
	}
}

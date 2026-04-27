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
 * Post type fields (WordPress + CPTs) are registered via the late-binding
 * `bb_admin_settings_before_get_feature` hook because custom post types from
 * third-party plugins (e.g., LearnDash) are not available at the early
 * `bb_register_features` time.
 *
 * @package BuddyBoss\Core\Administration
 * @since BuddyBoss [BBVERSION]
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Register Posts Visibility panel sections and fields.
 *
 * Only registers the section and BuddyBoss Platform activity types field,
 * which are available at early registration time.
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
	// Legacy stores each type in a separate option (bp-feed-platform-{activity_name}). Core AJAX
	// reads/writes per-option when option_prefix is set (see class-bb-admin-settings-ajax.php).
	$platform_activity_types = function_exists( 'bp_platform_default_activity_types' ) ? bp_platform_default_activity_types() : array();
	$platform_type_options   = array();

	foreach ( $platform_activity_types as $type ) {
		$platform_type_options[] = array(
			'label' => $type['activity_label'],
			'value' => $type['activity_name'],
		);
	}

	bb_register_feature_field(
		'activity',
		'posts_visibility',
		'posts_visibility',
		array(
			'name'                 => 'bp_platform_activity_types',
			'label'                => __( 'BuddyBoss Platform', 'buddyboss' ),
			'type'                 => 'toggle_list',
			'description'          => '',
			'options'              => $platform_type_options,
			'default'              => array(),
			'sanitize_callback'    => 'bb_activity_sanitize_platform_activity_types',
			'order'                => 10,
			'option_prefix'        => 'bp-feed-platform-',
			'option_value_truthy'  => array( 1, '1', true ),
		)
	);
}

/**
 * Register post type fields for Posts Visibility panel (late-binding).
 *
 * Hooked to `bb_admin_settings_before_get_feature` which fires during the
 * AJAX request, when all custom post types from third-party plugins are
 * already registered.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param string $feature_id The feature being loaded.
 */
function bb_activity_register_visibility_post_type_fields( $feature_id ) {
	// Only run for the activity feature.
	if ( 'activity' !== $feature_id ) {
		return;
	}

	$all_feed_post_types = function_exists( 'bb_feed_post_types' ) ? bb_feed_post_types() : array();
	$field_order         = 20;

	// -------------------------------------------------------------------------
	// WordPress post type fields.
	// Toggle + checkbox grouped together. Checkbox depends on toggle via conditional.
	// -------------------------------------------------------------------------
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
				'sanitize_callback' => 'absint',
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
				'sanitize_callback' => 'absint',
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
		$group_id  = '';

		// Compute once outside the loop — the result is the same for every iteration.
		$no_comment_post_types = function_exists( 'bb_feed_not_allowed_comment_post_types' ) ? bb_feed_not_allowed_comment_post_types() : array();

		foreach ( $custom_post_types as $post_type ) {
			$post_type_obj       = get_post_type_object( $post_type );
			$post_type_label     = $post_type_obj ? $post_type_obj->labels->name : $post_type;
			$post_option_name    = bb_post_type_feed_option_name( $post_type );
			$comment_option_name = bb_post_type_feed_comment_option_name( $post_type );
			$group_id            = 'cpt_feed_' . sanitize_key( $post_type );

			// Check if the post type supports comments (same logic as legacy).
			// Also checks bb_activity_is_enabled_cpt_global_comment() so that LearnDash/TutorLMS
			// CPTs with comments disabled in their own settings are treated as unsupported.
			$comments_not_supported = in_array( $post_type, $no_comment_post_types, true )
				|| ( function_exists( 'bb_activity_is_enabled_cpt_global_comment' ) && ! bb_activity_is_enabled_cpt_global_comment( $post_type ) );

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
					'sanitize_callback' => 'absint',
					'order'             => $field_order,
					'group'             => $group_id,
				)
			);

			if ( $comments_not_supported ) {
				// Notice: Comments not supported (shown only when toggle is ON).
				bb_register_feature_field(
					'activity',
					'posts_visibility',
					'posts_visibility',
					array(
						'name'        => 'bb_cpt_no_comments_' . sanitize_key( $post_type ),
						'label'       => '',
						'type'        => 'notice',
						'notice_type' => 'info',
						'description' => sprintf(
							/* translators: %s: Post type name (e.g. "Quizzes"). */
							__( 'Comments are not supported for %s.', 'buddyboss' ),
							$post_type_label
						),
						'order'       => $field_order + 1,
						'group'       => $group_id,
						'conditional' => array(
							'field' => $post_option_name,
							'value' => true,
						),
					)
				);
			} else {
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
						'sanitize_callback' => 'absint',
						'order'             => $field_order + 1,
						'group'             => $group_id,
						'conditional'       => array(
							'field' => $post_option_name,
							'value' => true,
						),
					)
				);
			}

			$field_order += 10;
			++$cpt_index;
		}

		// Help text for Custom Post Types section.
		// Use the last CPT's group_id so the notice is visually inside the CPT section.
		// notice_type 'plain' renders as standalone helper text (no boxed background,
		// no icon) per Figma — used for section-level help where a full notice card
		// would be too heavy.
		bb_register_feature_field(
			'activity',
			'posts_visibility',
			'posts_visibility',
			array(
				'name'        => 'bb_custom_post_types_info',
				'label'       => '',
				'type'        => 'notice',
				'notice_type' => 'plain',
				'description' => __( 'Select the custom post types to display in the activity feed when members publish them. For each type, you can also choose whether to include comments in the activity posts (if comments are supported).', 'buddyboss' ),
				'order'       => $field_order,
				'group'       => $group_id,
			)
		);
	}
}

add_action( 'bb_admin_settings_before_get_feature', 'bb_activity_register_visibility_post_type_fields' );

<?php
/**
 * BuddyBoss Admin Settings - Activity Comments Panel.
 *
 * Registers sections and fields for the Activity Comments side panel.
 *
 * @package BuddyBoss\Core\Administration
 * @since BuddyBoss 3.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Register Activity Comments panel sections and fields.
 *
 * @since BuddyBoss 3.0.0
 *
 * @param array $edit_time_options Edit time options built from bp_activity_edit_times().
 */
function bb_activity_register_comments_panel_fields( $edit_time_options ) {

	// -------------------------------------------------------------------------
	// SECTION: Activity Comments
	// -------------------------------------------------------------------------
	bb_register_feature_section(
		'activity',
		'activity_comments',
		'activity_comments',
		array(
			'title'       => __( 'Activity Comments', 'buddyboss' ),
			'description' => '',
			'order'       => 10,
			'help_url'    => '636115',
		)
	);

	// FIELD: Enable Comments.
	bb_register_feature_field(
		'activity',
		'activity_comments',
		'activity_comments',
		array(
			'name'              => '_bb_enable_activity_comments',
			'label'             => __( 'Enable Comments', 'buddyboss' ),
			'type'              => 'toggle',
			'description'       => __( 'Allow members to comment on activity posts', 'buddyboss' ),
			'help_text'         => __( 'Comments on an individual activity post can be closed or disabled all together by site admins.', 'buddyboss' ),
			'default'           => bb_is_activity_comments_enabled(),
			'sanitize_callback' => 'absint',
			'order'             => 10,
		)
	);

	// FIELD: Edit Activity Comments (toggle with inline select in description).
	bb_register_feature_field(
		'activity',
		'activity_comments',
		'activity_comments',
		array(
			'name'                 => '_bb_enable_activity_comment_edit',
			'label'                => __( 'Edit Activity Comments', 'buddyboss' ),
			'type'                 => 'toggle',
			/* translators: %s: edit time duration select control. */
			'description'          => __( 'Allow members to edit their comment for a duration of %s', 'buddyboss' ),
			'default'              => bb_is_activity_comment_edit_enabled(),
			'sanitize_callback'    => 'absint',
			'description_controls' => array(
				array(
					'type'              => 'select',
					'name'              => '_bb_activity_comment_edit_time',
					'default'           => bb_get_activity_comment_edit_time() ? bb_get_activity_comment_edit_time() : 600,
					'options'           => $edit_time_options,
					'sanitize_callback' => 'bb_activity_sanitize_edit_time',
				),
			),
			'order'                => 20,
		)
	);

	// FIELD: Comment Visibility (inline select in description via description_controls).
	bb_register_feature_field(
		'activity',
		'activity_comments',
		'activity_comments',
		array(
			'name'                 => '_bb_activity_comment_visibility',
			'label'                => __( 'Comment Visibility', 'buddyboss' ),
			'type'                 => 'hidden',
			/* translators: %s: comment count select control. */
			'description'          => __( 'Display a maximum %s comments per post in activity feeds', 'buddyboss' ),
			'help_text'            => __( 'Load more via "View more comments." High comment counts may slow scrolling. Applies to platform only, not app.', 'buddyboss' ),
			'default'              => bb_get_activity_comment_visibility(),
			'options'              => array(
				array(
					'label' => __( 'None', 'buddyboss' ),
					'value' => 0,
				),
				array(
					'label' => '1',
					'value' => 1,
				),
				array(
					'label' => '2',
					'value' => 2,
				),
				array(
					'label' => '3',
					'value' => 3,
				),
				array(
					'label' => '4',
					'value' => 4,
				),
				array(
					'label' => '5',
					'value' => 5,
				),
			),
			'sanitize_callback'    => 'bb_activity_sanitize_comment_visibility',
			'description_controls' => array(
				array(
					'type' => 'self',
				),
			),
			'order'                => 30,
		)
	);

	// FIELD: Comment Threading (toggle with inline select in description).
	bb_register_feature_field(
		'activity',
		'activity_comments',
		'activity_comments',
		array(
			'name'                 => '_bb_enable_activity_comment_threading',
			'label'                => __( 'Comment Threading', 'buddyboss' ),
			'type'                 => 'toggle',
			/* translators: %s: thread depth select control. */
			'description'          => __( 'Organize replies into threads %s levels deep', 'buddyboss' ),
			'help_text'            => __( 'Replies to activity comments appear in threads, except for replies at the deepest level. Applies to platform only, not app.', 'buddyboss' ),
			'default'              => bb_is_activity_comment_threading_enabled(),
			'sanitize_callback'    => 'absint',
			'description_controls' => array(
				array(
					'type'              => 'select',
					'name'              => '_bb_activity_comment_threading_depth',
					'default'           => bb_get_activity_comment_threading_depth(),
					'options'           => array(
						array(
							'label' => '1',
							'value' => 1,
						),
						array(
							'label' => '2',
							'value' => 2,
						),
						array(
							'label' => '3',
							'value' => 3,
						),
						array(
							'label' => '4',
							'value' => 4,
						),
					),
					'sanitize_callback' => 'bb_activity_sanitize_comment_threading_depth',
				),
			),
			'order'                => 40,
		)
	);

	// FIELD: Comments Loading (inline select in description via description_controls).
	bb_register_feature_field(
		'activity',
		'activity_comments',
		'activity_comments',
		array(
			'name'                 => '_bb_activity_comment_loading',
			'label'                => __( 'Comments Loading', 'buddyboss' ),
			'type'                 => 'hidden',
			/* translators: %s: additional comments count select control. */
			'description'          => __( 'Load %s Additional comments on each request', 'buddyboss' ),
			'help_text'            => __( 'Increasing the number of comments retrieved in each request may negatively impact site performance.', 'buddyboss' ),
			'default'              => bb_get_activity_comment_loading(),
			'options'              => array_map(
				function ( $n ) {
					return array(
						'label' => (string) $n,
						'value' => $n,
					);
				},
				/**
				 * Filters the allowed values for the comment loading setting.
				 * Same filter as legacy bp-admin-setting-activity.php.
				 *
				 * @since BuddyBoss 2.5.80
				 *
				 * @param array $allowed Allowed integer values.
				 */
				apply_filters( 'bb_activity_comment_loading_options', array( 5, 10, 15, 20, 25, 30 ) )
			),
			'sanitize_callback'    => 'bb_activity_sanitize_comment_loading',
			'description_controls' => array(
				array(
					'type' => 'self',
				),
			),
			'order'                => 50,
		)
	);

	// FIELD: Notice about WordPress Discussion settings.
	bb_register_feature_field(
		'activity',
		'activity_comments',
		'activity_comments',
		array(
			'name'        => 'bb_activity_comments_info',
			'label'       => __( 'Notice', 'buddyboss' ),
			'type'        => 'notice',
			'notice_type' => 'info',
			'description' => __( 'Comments on WordPress Posts and Custom Post Types will inherit from your WordPress Discussion settings.', 'buddyboss' ),
			'order'       => 60,
		)
	);
}

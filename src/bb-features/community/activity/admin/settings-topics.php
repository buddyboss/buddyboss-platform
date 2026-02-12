<?php
/**
 * BuddyBoss Admin Settings - Activity Topics Panel.
 *
 * Registers sections and fields for the Activity Topics side panel.
 *
 * @package BuddyBoss\Features\Community\Activity
 * @since BuddyBoss [BBVERSION]
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Register Activity Topics panel sections and fields.
 *
 * @since BuddyBoss [BBVERSION]
 */
function bb_activity_register_topics_panel_fields() {

	// -------------------------------------------------------------------------
	// SECTION: Activity Topics
	// -------------------------------------------------------------------------
	bb_register_feature_section(
		'activity',
		'activity_topics',
		'activity_topics',
		array(
			'title'       => __( 'Activity Topics', 'buddyboss' ),
			'description' => '',
			'order'       => 10,
		)
	);

	// FIELD: Enable Topics.
	bb_register_feature_field(
		'activity',
		'activity_topics',
		'activity_topics',
		array(
			'name'              => 'bb_enable_activity_topics',
			'label'             => __( 'Activity Topics', 'buddyboss' ),
			'type'              => 'toggle',
			'description'       => __( 'Enable topics in activity feed', 'buddyboss' ),
			'default'           => bb_is_enabled_activity_topics(),
			'sanitize_callback' => 'intval',
			'order'             => 10,
			'group'             => 'activity_topics_group',
		)
	);

	// FIELD: Topic Required (sub-toggle, grouped with Enable Topics).
	bb_register_feature_field(
		'activity',
		'activity_topics',
		'activity_topics',
		array(
			'name'              => 'bb_activity_topic_required',
			'label'             => '',
			'type'              => 'toggle',
			'description'       => __( 'Require users to select a topic before posting in activity feed', 'buddyboss' ),
			'default'           => bb_is_activity_topic_required(),
			'sanitize_callback' => 'intval',
			'order'             => 20,
			'group'             => 'activity_topics_group',
			'conditional'       => array(
				'field' => 'bb_enable_activity_topics',
				'value' => true,
			),
		)
	);

	// FIELD: Topics List (custom topic_list type).
	bb_register_feature_field(
		'activity',
		'activity_topics',
		'activity_topics',
		array(
			'name'        => 'bb_activity_topics',
			'label'       => __( 'Topics', 'buddyboss' ),
			'type'        => 'topic_list',
			'description' => __( 'You can add up to a maximum of 20 topics', 'buddyboss' ),
			'default'     => array(),
			'order'       => 30,
			'conditional' => array(
				'field' => 'bb_enable_activity_topics',
				'value' => true,
			),
		)
	);

	// -------------------------------------------------------------------------
	// SECTION: Group Topics (Pro hooks in here)
	// -------------------------------------------------------------------------
	if ( bp_is_active( 'groups' ) ) {
		bb_register_feature_section(
			'activity',
			'activity_topics',
			'group_topics',
			array(
				'title'       => __( 'Group Topics', 'buddyboss' ),
				'description' => '',
				'order'       => 20,
			)
		);

		// FIELD: Group Topics (Pro only).
		bb_register_feature_field(
			'activity',
			'activity_topics',
			'group_topics',
			array(
				'name'              => 'bb-enable-group-activity-topics',
				'label'             => __( 'Group Topics', 'buddyboss' ),
				'type'              => 'toggle',
				'description'       => __( 'Enable topics for groups', 'buddyboss' ),
				'help_text'         => __( 'Allow group organizers to set topics for members to use in group posts.', 'buddyboss' ),
				'default'           => function_exists( 'bb_is_enabled_group_activity_topics' ) && bb_is_enabled_group_activity_topics(),
				'sanitize_callback' => 'intval',
				'order'             => 10,
				'pro_only'          => true,
				'conditional'       => array(
					'field' => 'bb_enable_activity_topics',
					'value' => true,
				),
			)
		);
	}

	/**
	 * Fires after Activity Topics section fields are registered.
	 * Allows Pro plugin to register Group Topics options and other fields.
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	do_action( 'bb_activity_settings_after_topics_fields' );
}

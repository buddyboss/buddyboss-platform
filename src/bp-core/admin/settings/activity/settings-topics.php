<?php
/**
 * BuddyBoss Admin Settings - Activity Topics Panel.
 *
 * Registers sections and fields for the Activity Topics side panel.
 *
 * @package BuddyBoss\Core\Administration
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
			'sanitize_callback' => 'absint',
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
			'sanitize_callback' => 'absint',
			'order'             => 20,
			'group'             => 'activity_topics_group',
			'conditional'       => array(
				'field' => 'bb_enable_activity_topics',
				'value' => true,
			),
		)
	);

	// FIELD: Topics List (custom topic_list type).
	// Topics are managed via dedicated AJAX (bb_add_topic, bb_delete_topic, etc.)
	// so the sanitize_callback prevents auto-save from overwriting topic data.
	bb_register_feature_field(
		'activity',
		'activity_topics',
		'activity_topics',
		array(
			'name'              => 'bb_activity_topics',
			'label'             => __( 'Topics', 'buddyboss' ),
			'type'              => 'topic_list',
			'description'       => __( 'Maximum of 20 topics can be added.', 'buddyboss' ),
			'default'           => array(),
			'sanitize_callback' => 'bb_sanitize_topic_list_noop',
			'order'             => 30,
			'conditional'       => array(
				'field' => 'bb_enable_activity_topics',
				'value' => true,
			),
		)
	);

	// -------------------------------------------------------------------------
	// SECTION: Group Topics (Pro hooks in here)
	// -------------------------------------------------------------------------
	if ( bp_is_active( 'groups' ) ) {
		$group_topics_section_args = array(
			'title'       => __( 'Group Topics', 'buddyboss' ),
			'description' => '',
			'order'       => 20,
		);

		// When PRO is active, hide section when Activity Topics is disabled.
		// When PRO is not active, always show for upsell visibility.
		if ( function_exists( 'bb_is_enabled_group_activity_topics' ) ) {
			$group_topics_section_args['conditional'] = array(
				'field'  => 'bb_enable_activity_topics',
				'value'  => true,
				'action' => 'hide',
			);
		}

		bb_register_feature_section(
			'activity',
			'activity_topics',
			'group_topics',
			$group_topics_section_args
		);

		// FIELD: Group Topics (Pro only).
		$group_topics_field_args = array(
			'name'              => 'bb-enable-group-activity-topics',
			'label'             => __( 'Group Topics', 'buddyboss' ),
			'type'              => 'toggle',
			'description'       => __( 'Enable topics for groups', 'buddyboss' ),
			'help_text'         => __( 'Allow group organizers to set topics for members to use in group posts.', 'buddyboss' ),
			'default'           => function_exists( 'bb_is_enabled_group_activity_topics' ) && bb_is_enabled_group_activity_topics(),
			'sanitize_callback' => 'absint',
			'order'             => 10,
			'pro_only'          => true,
		);

		// When PRO is active, hide field when Activity Topics is disabled.
		// When PRO is not active, always show for upsell visibility.
		if ( function_exists( 'bb_is_enabled_group_activity_topics' ) ) {
			$group_topics_field_args['conditional'] = array(
				'field' => 'bb_enable_activity_topics',
				'value' => true,
			);
		}

		bb_register_feature_field(
			'activity',
			'activity_topics',
			'group_topics',
			$group_topics_field_args
		);
	}

	/**
	 * Fires after Activity Topics section fields are registered.
	 * Allows Pro plugin to register Group Topics options and other fields.
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	do_action( 'bb_activity_settings_after_topics_fields' );

	/**
	 * Deprecated: bb_admin_setting_activity_topic_register_fields.
	 *
	 * Legacy hook used by third-party plugins to register additional topic
	 * settings fields. Replaced by bb_activity_settings_after_topics_fields.
	 *
	 * @since BuddyBoss 1.7.0
	 * @deprecated BuddyBoss [BBVERSION] Use bb_activity_settings_after_topics_fields instead.
	 */
	do_action_deprecated(
		'bb_admin_setting_activity_topic_register_fields',
		array(),
		'[BBVERSION]',
		'bb_activity_settings_after_topics_fields'
	);
}

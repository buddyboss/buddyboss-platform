<?php
/**
 * BuddyBoss Admin Settings - Forum Settings Panel.
 *
 * Registers sections and fields for the Forum Settings side panel.
 *
 * @package BuddyBoss\Core\Administration
 * @since BuddyBoss [BBVERSION]
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Register Forum Settings panel sections and fields.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @return void
 */
function bb_forums_register_settings_panel_fields() {

	// -------------------------------------------------------------------------
	// SECTION: Forum Settings
	// -------------------------------------------------------------------------
	bb_register_feature_section(
		'forums',
		'forum_settings',
		'forum_settings_section',
		array(
			'title'       => __( 'Forum Settings', 'buddyboss' ),
			'description' => '',
			'order'       => 10,
		)
	);

	// FIELD: Disallow Editing After.
	bb_register_feature_field(
		'forums',
		'forum_settings',
		'forum_settings_section',
		array(
			'name'              => '_bbp_edit_lock',
			'label'             => __( 'Disallow Editing After', 'buddyboss' ),
			'type'              => 'number',
			'default'           => (int) get_option( '_bbp_edit_lock', 5 ),
			'sanitize_callback' => 'intval',
			'min'               => 0,
			'step'              => 1,
			'suffix'            => __( 'minutes', 'buddyboss' ),
			'order'             => 10,
		)
	);

	// FIELD: Throttle Posting Every.
	bb_register_feature_field(
		'forums',
		'forum_settings',
		'forum_settings_section',
		array(
			'name'              => '_bbp_throttle_time',
			'label'             => __( 'Throttle Posting Every', 'buddyboss' ),
			'type'              => 'number',
			'default'           => (int) get_option( '_bbp_throttle_time', 10 ),
			'sanitize_callback' => 'intval',
			'min'               => 0,
			'step'              => 1,
			'suffix'            => __( 'seconds', 'buddyboss' ),
			'order'             => 20,
		)
	);

	// Build depth options for reply threading (2–max).
	$thread_replies_depth_max = (int) apply_filters( 'bbp_thread_replies_depth_max', 10 );
	$depth_options            = array();
	for ( $i = 2; $i <= $thread_replies_depth_max; ++$i ) {
		$depth_options[] = array(
			'label' => (string) $i,
			'value' => $i,
		);
	}

	// FIELD: Reply Threading (toggle with depth select in description_controls).
	bb_register_feature_field(
		'forums',
		'forum_settings',
		'forum_settings_section',
		array(
			'name'                 => '_bbp_allow_threaded_replies',
			'label'                => __( 'Reply Threading', 'buddyboss' ),
			'type'                 => 'toggle',
			/* translators: %s: Thread depth select control. */
			'description'          => __( 'Enable threaded (nested) replies %s levels deep', 'buddyboss' ),
			'default'              => bbp_allow_threaded_replies(),
			'sanitize_callback'    => 'intval',
			'description_controls' => array(
				array(
					'type'              => 'select',
					'name'              => '_bbp_thread_replies_depth',
					'default'           => bbp_thread_replies_depth(),
					'options'           => $depth_options,
					'sanitize_callback' => 'intval',
				),
			),
			'order'                => 30,
		)
	);

	// -------------------------------------------------------------------------
	// SECTION: Discussions and Replies Per Page
	// -------------------------------------------------------------------------
	bb_register_feature_section(
		'forums',
		'forum_settings',
		'forum_per_page_section',
		array(
			'title'       => __( 'Discussions and Replies Per Page', 'buddyboss' ),
			'description' => '',
			'order'       => 20,
		)
	);

	// FIELD: Forums Per Page.
	bb_register_feature_field(
		'forums',
		'forum_settings',
		'forum_per_page_section',
		array(
			'name'              => '_bbp_forums_per_page',
			'label'             => __( 'Forums', 'buddyboss' ),
			'type'              => 'number',
			'default'           => bbp_get_forums_per_page(),
			'sanitize_callback' => 'absint',
			'min'               => 1,
			'step'              => 1,
			'suffix'            => __( 'per page', 'buddyboss' ),
			'order'             => 10,
		)
	);

	// FIELD: Discussions Per Page.
	bb_register_feature_field(
		'forums',
		'forum_settings',
		'forum_per_page_section',
		array(
			'name'              => '_bbp_topics_per_page',
			'label'             => __( 'Discussions', 'buddyboss' ),
			'type'              => 'number',
			'default'           => bbp_get_topics_per_page(),
			'sanitize_callback' => 'absint',
			'min'               => 1,
			'step'              => 1,
			'suffix'            => __( 'per page', 'buddyboss' ),
			'order'             => 20,
		)
	);

	// FIELD: Replies Per Page.
	bb_register_feature_field(
		'forums',
		'forum_settings',
		'forum_per_page_section',
		array(
			'name'              => '_bbp_replies_per_page',
			'label'             => __( 'Replies', 'buddyboss' ),
			'type'              => 'number',
			'default'           => bbp_get_replies_per_page(),
			'sanitize_callback' => 'absint',
			'min'               => 1,
			'step'              => 1,
			'suffix'            => __( 'per page', 'buddyboss' ),
			'order'             => 30,
		)
	);

	// -------------------------------------------------------------------------
	// SECTION: Discussions and Replies Per RSS Page
	// -------------------------------------------------------------------------
	bb_register_feature_section(
		'forums',
		'forum_settings',
		'forum_per_rss_page_section',
		array(
			'title'       => __( 'Discussions and Replies Per RSS Page', 'buddyboss' ),
			'description' => '',
			'order'       => 30,
		)
	);

	// FIELD: Discussions Per RSS Page.
	bb_register_feature_field(
		'forums',
		'forum_settings',
		'forum_per_rss_page_section',
		array(
			'name'              => '_bbp_topics_per_rss_page',
			'label'             => __( 'Discussions', 'buddyboss' ),
			'type'              => 'number',
			'default'           => bbp_get_topics_per_rss_page(),
			'sanitize_callback' => 'absint',
			'min'               => 1,
			'step'              => 1,
			'suffix'            => __( 'per page', 'buddyboss' ),
			'order'             => 10,
		)
	);

	// FIELD: Replies Per RSS Page.
	bb_register_feature_field(
		'forums',
		'forum_settings',
		'forum_per_rss_page_section',
		array(
			'name'              => '_bbp_replies_per_rss_page',
			'label'             => __( 'Replies', 'buddyboss' ),
			'type'              => 'number',
			'default'           => bbp_get_replies_per_rss_page(),
			'sanitize_callback' => 'absint',
			'min'               => 1,
			'step'              => 1,
			'suffix'            => __( 'per page', 'buddyboss' ),
			'order'             => 20,
		)
	);
}

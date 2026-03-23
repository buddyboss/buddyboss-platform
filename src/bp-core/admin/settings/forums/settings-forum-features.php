<?php
/**
 * BuddyBoss Admin Settings - Forum Features Panel.
 *
 * Registers sections and fields for the Forum Features side panel.
 *
 * @package BuddyBoss\Core\Administration
 * @since BuddyBoss [BBVERSION]
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Register Forum Features panel sections and fields.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @return void
 */
function bb_forums_register_features_panel_fields() {

	// -------------------------------------------------------------------------
	// SECTION: Forum Features
	// -------------------------------------------------------------------------
	bb_register_feature_section(
		'forums',
		'forum_features',
		'forum_features_section',
		array(
			'title'       => __( 'Forum Features', 'buddyboss' ),
			'description' => '',
			'order'       => 10,
		)
	);

	// FIELD: Revisions.
	bb_register_feature_field(
		'forums',
		'forum_features',
		'forum_features_section',
		array(
			'name'              => '_bbp_allow_revisions',
			'label'             => __( 'Revisions', 'buddyboss' ),
			'type'              => 'toggle',
			'description'       => __( 'Allow discussion and reply revision logging', 'buddyboss' ),
			'default'           => bbp_allow_revisions(),
			'sanitize_callback' => 'absint',
			'order'             => 10,
		)
	);

	// FIELD: Favorites.
	bb_register_feature_field(
		'forums',
		'forum_features',
		'forum_features_section',
		array(
			'name'              => '_bbp_enable_favorites',
			'label'             => __( 'Favorites', 'buddyboss' ),
			'type'              => 'toggle',
			'description'       => __( 'Allow members to mark discussions as favorites', 'buddyboss' ),
			'default'           => bbp_is_favorites_active(),
			'sanitize_callback' => 'absint',
			'order'             => 20,
		)
	);

	// FIELD: Subscriptions.
	bb_register_feature_field(
		'forums',
		'forum_features',
		'forum_features_section',
		array(
			'name'              => '_bbp_enable_subscriptions',
			'label'             => __( 'Subscriptions', 'buddyboss' ),
			'type'              => 'toggle',
			'description'       => __( 'Allow members to subscribe to discussions and standalone forums', 'buddyboss' ),
			'default'           => bbp_is_subscriptions_active(),
			'sanitize_callback' => 'absint',
			'order'             => 30,
		)
	);

	// FIELD: Discussion Tags.
	bb_register_feature_field(
		'forums',
		'forum_features',
		'forum_features_section',
		array(
			'name'              => '_bbp_allow_topic_tags',
			'label'             => __( 'Discussion Tags', 'buddyboss' ),
			'type'              => 'toggle',
			'description'       => __( 'Allow discussions to have tags', 'buddyboss' ),
			'default'           => bbp_allow_topic_tags(),
			'sanitize_callback' => 'absint',
			'refresh_panels'    => true,
			'order'             => 40,
		)
	);

	// FIELD: Search.
	bb_register_feature_field(
		'forums',
		'forum_features',
		'forum_features_section',
		array(
			'name'              => '_bbp_allow_search',
			'label'             => __( 'Search', 'buddyboss' ),
			'type'              => 'toggle',
			'description'       => __( 'Allow forum-wide search', 'buddyboss' ),
			'default'           => bbp_allow_search(),
			'sanitize_callback' => 'absint',
			'order'             => 50,
		)
	);

	// FIELD: Post Formatting.
	bb_register_feature_field(
		'forums',
		'forum_features',
		'forum_features_section',
		array(
			'name'              => '_bbp_use_wp_editor',
			'label'             => __( 'Post Formatting', 'buddyboss' ),
			'type'              => 'toggle',
			'description'       => __( 'Add toolbar & buttons to text areas to help with HTML formatting', 'buddyboss' ),
			'default'           => bbp_use_wp_editor(),
			'sanitize_callback' => 'absint',
			'order'             => 60,
		)
	);

	// FIELD: Link Previews.
	bb_register_feature_field(
		'forums',
		'forum_features',
		'forum_features_section',
		array(
			'name'              => '_bbp_use_autoembed',
			'label'             => __( 'Link Previews', 'buddyboss' ),
			'type'              => 'toggle',
			'description'       => __( 'Embed media (YouTube, Twitter, Vimeo, etc...) directly into discussions and replies', 'buddyboss' ),
			'default'           => bbp_use_autoembed(),
			'sanitize_callback' => 'absint',
			'order'             => 70,
		)
	);

	// FIELD: Anonymous Posting.
	bb_register_feature_field(
		'forums',
		'forum_features',
		'forum_features_section',
		array(
			'name'              => '_bbp_allow_anonymous',
			'label'             => __( 'Anonymous Posting', 'buddyboss' ),
			'type'              => 'toggle',
			'description'       => __( 'Allow guest users without accounts to create discussions and replies', 'buddyboss' ),
			'default'           => bbp_allow_anonymous(),
			'sanitize_callback' => 'absint',
			'order'             => 80,
		)
	);

	// FIELD: Akismet Spam Protection (conditional on Akismet plugin being active).
	if ( class_exists( 'Akismet' ) ) {
		bb_register_feature_field(
			'forums',
			'forum_features',
			'forum_features_section',
			array(
				'name'              => '_bbp_enable_akismet',
				'label'             => __( 'Akismet Spam Protection', 'buddyboss' ),
				'type'              => 'toggle',
				'description'       => __( 'Allow Akismet spam filtering to actively prevent forum spam.', 'buddyboss' ),
				'help_text'         => sprintf(
					/* translators: %s: Akismet link. */
					__( 'Learn more about %s.', 'buddyboss' ),
					'<a href="https://akismet.com/" target="_blank" rel="noopener noreferrer">' . esc_html__( 'Akismet', 'buddyboss' ) . '</a>'
				),
				'default'           => bbp_is_akismet_active(),
				'sanitize_callback' => 'absint',
				'order'             => 90,
			)
		);
	}

	/**
	 * Fires after Forum Features section fields are registered.
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	do_action( 'bb_forums_settings_after_features_fields' );

	// -------------------------------------------------------------------------
	// SECTION: Group Forums (conditional on groups active)
	// -------------------------------------------------------------------------
	if ( bp_is_active( 'groups' ) ) {

		bb_register_feature_section(
			'forums',
			'forum_features',
			'group_forums_section',
			array(
				'title'       => __( 'Group Forums', 'buddyboss' ),
				'description' => '',
				'order'       => 20,
			)
		);

		// FIELD: Group Forums.
		bb_register_feature_field(
			'forums',
			'forum_features',
			'group_forums_section',
			array(
				'name'              => '_bbp_enable_group_forums',
				'label'             => __( 'Group Forums', 'buddyboss' ),
				'type'              => 'toggle',
				'description'       => __( 'Allow social groups to have their own forums', 'buddyboss' ),
				'default'           => bbp_is_group_forums_active(),
				'sanitize_callback' => 'absint',
				'order'             => 10,
			)
		);
		/**
		 * Fires after Group Forums section fields are registered.
		 *
		 * @since BuddyBoss [BBVERSION]
		 */
		do_action( 'bb_forums_settings_after_group_forums_fields' );
	}
}

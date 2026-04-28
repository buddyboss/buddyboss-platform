<?php
/**
 * BuddyBoss Admin Settings - Forum Directories Panel.
 *
 * Registers sections and fields for the Forum Directories side panel.
 *
 * @package BuddyBoss\Core\Administration
 * @since BuddyBoss [BBVERSION]
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Register Forum Directories panel sections and fields.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @return void
 */
function bb_forums_register_directories_panel_fields() {

	// -------------------------------------------------------------------------
	// SECTION: Forum Directories
	// -------------------------------------------------------------------------
	// Build Shortcodes help link.
	$shortcodes_url = esc_url(
		bp_get_admin_url(
			add_query_arg(
				array(
					'page'    => 'bp-help',
					'article' => 83108,
				),
				'admin.php'
			)
		)
	);

	bb_register_feature_section(
		'forums',
		'forum_directories',
		'forum_directories_section',
		array(
			'title'       => __( 'Forum Directories', 'buddyboss' ),
			/* translators: %s: Shortcodes link. */
			'description' => sprintf( __( 'Customize your Forums directory. Use <a href="%s" target="_blank">Shortcodes</a> for more flexibility.', 'buddyboss' ), $shortcodes_url ),
			'order'       => 10,
		)
	);

	// Build Forums page link for help text.
	$forums_page_url = esc_url( bb_get_feature_settings_url( 'appearance', 'pages' ) );

	// FIELD: Forums Prefix.
	bb_register_feature_field(
		'forums',
		'forum_directories',
		'forum_directories_section',
		array(
			'name'              => '_bbp_include_root',
			'label'             => __( 'Forums Prefix', 'buddyboss' ),
			'type'              => 'toggle',
			'description'       => __( 'Prefix all forum content with the Forums page slug (Recommended)', 'buddyboss' ),
			'default'           => bbp_include_root_slug(),
			'sanitize_callback' => 'absint',
			/* translators: %s: Forums page link. */
			'help_text'         => sprintf( __( 'When enabled, it adds the <a href="%s">Forums page</a> slug to all forum URLs for cleaner, more organized links.', 'buddyboss' ), $forums_page_url ),
			'order'             => 10,
		)
	);

	// FIELD: Forums Directory Shows.
	bb_register_feature_field(
		'forums',
		'forum_directories',
		'forum_directories_section',
		array(
			'name'              => '_bbp_show_on_root',
			'label'             => __( 'Forums Directory Shows', 'buddyboss' ),
			'type'              => 'radio',
			'default'           => bbp_show_on_root(),
			'sanitize_callback' => 'sanitize_text_field',
			'options'           => array(
				array(
					'label' => __( 'Forum Index', 'buddyboss' ),
					'value' => 'forums',
				),
				array(
					'label' => __( 'Discussions by Last Post', 'buddyboss' ),
					'value' => 'topics',
				),
			),
			'order'             => 20,
		)
	);
}

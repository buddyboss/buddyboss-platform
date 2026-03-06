<?php
/**
 * BuddyBoss Admin Settings - Search Pages & Posts Panel.
 *
 * Registers the Pages and Posts Search side panel sections and fields for the
 * Search feature in the Settings 2.0 registry.
 *
 * All post types (standard and custom) are registered within a single section
 * so the React UI renders them in one continuous card — matching the Figma design.
 *
 * Standard post types (post, page, attachment) are registered eagerly at
 * bb_register_features time. Custom post types from third-party plugins
 * (e.g., GamiPress, LearnDash) are registered lazily via
 * bb_admin_settings_before_get_feature because those plugins register their
 * CPTs on `init`, which fires after `bb_register_features`.
 *
 * @package BuddyBoss\Core\Administration
 * @since   BuddyBoss [BBVERSION]
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Standard post types to register eagerly.
 *
 * These are always available at bb_register_features time.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @var array
 */
define( 'BB_SEARCH_STANDARD_POST_TYPES', array( 'post', 'page', 'attachment' ) );

/**
 * Post types to skip entirely (handled by Network Search panel).
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @var array
 */
define( 'BB_SEARCH_EXCLUDED_POST_TYPES', array( 'forum', 'topic', 'reply' ) );

/**
 * Register Pages & Posts Search panel sections and fields.
 *
 * Called from bb-admin-settings-search.php after side panels are registered.
 * All standard post types are registered as fields within a single section
 * so the React UI renders them in one continuous card — matching the Figma design.
 * Custom post types are deferred to bb_search_lazy_register_cpt_fields().
 *
 * @since BuddyBoss [BBVERSION]
 */
function bb_search_register_pages_posts_fields() {

	// Single section for the entire Pages & Posts panel (one card in React UI).
	bb_register_feature_section(
		'search',
		'pages_posts',
		'pages_posts_content',
		array(
			'title'       => __( 'Pages & Posts Search', 'buddyboss' ),
			'description' => __( 'All listed WordPress content and custom post types are searchable. The switches indicate which ones are included in searches.', 'buddyboss' ),
			'order'       => 10,
		)
	);

	$order = 10;

	foreach ( BB_SEARCH_STANDARD_POST_TYPES as $post_type ) {
		$post_type_obj = get_post_type_object( $post_type );

		if ( ! $post_type_obj ) {
			continue;
		}

		// Determine display label and toggle description.
		$label = $post_type_obj->labels->name;
		if ( 'post' === $post_type ) {
			$label = __( 'Blog Posts', 'buddyboss' );
		}

		$toggle_descriptions = array(
			'post'       => __( 'Allow blog posts searching', 'buddyboss' ),
			'page'       => __( 'Allow Pages searching', 'buddyboss' ),
			'attachment' => __( 'Allow media searching', 'buddyboss' ),
		);

		$toggle_description = isset( $toggle_descriptions[ $post_type ] )
			? $toggle_descriptions[ $post_type ]
			: '';

		// Determine default value (post and page default to 1, others to 0).
		$default = in_array( $post_type, array( 'post', 'page' ), true ) ? 1 : 0;

		// FIELD: Post type toggle (parent).
		bb_register_feature_field(
			'search',
			'pages_posts',
			'pages_posts_content',
			array(
				'name'              => 'bp_search_post_type_' . $post_type,
				'label'             => $label,
				'description'       => $toggle_description,
				'type'              => 'toggle',
				'default'           => (bool) bp_get_option( 'bp_search_post_type_' . $post_type, $default ),
				'sanitize_callback' => 'absint',
				'order'             => $order,
			)
		);

		// Taxonomy child fields.
		bb_search_register_taxonomy_fields( $post_type, $order );

		// Meta Data child field (post & page only).
		if ( in_array( $post_type, array( 'post', 'page' ), true ) ) {
			bb_search_register_meta_field( $post_type, $order );
		}

		$order += 100;
	}
}

/**
 * Register taxonomy child fields for a post type.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param string $post_type  Post type slug.
 * @param int    $base_order Base order of the parent toggle field.
 */
function bb_search_register_taxonomy_fields( $post_type, $base_order = 10 ) {

	/**
	 * Filter to add or remove the Taxonomy from Post Type.
	 *
	 * @since 1.1.9
	 *
	 * @param array  $taxonomies The names of the taxonomies registered for the post type.
	 * @param string $post_type  Post type.
	 *
	 * @return array $taxonomies Filtered taxonomy names.
	 */
	$taxonomies  = (array) apply_filters( 'bp_search_settings_post_type_taxonomies', get_object_taxonomies( $post_type ), $post_type );
	$child_order = $base_order + 10;

	foreach ( $taxonomies as $taxonomy ) {
		$taxonomy_obj = get_taxonomy( $taxonomy );

		if ( ! $taxonomy_obj ) {
			continue;
		}

		$option_name = 'bp_search_' . $post_type . '_tax_' . $taxonomy;

		bb_register_feature_field(
			'search',
			'pages_posts',
			'pages_posts_content',
			array(
				'name'              => $option_name,
				'label'             => $taxonomy_obj->labels->name,
				'type'              => 'checkbox',
				'default'           => (bool) bp_get_option( $option_name, 0 ),
				'sanitize_callback' => 'absint',
				'parent_field'      => 'bp_search_post_type_' . $post_type,
				'order'             => $child_order,
			)
		);

		$child_order += 10;
	}
}

/**
 * Register Meta Data child field for a post type.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param string $post_type  Post type slug.
 * @param int    $base_order Base order of the parent toggle field.
 */
function bb_search_register_meta_field( $post_type, $base_order = 10 ) {

	// Count existing taxonomy fields to determine the next order.
	$taxonomies  = (array) apply_filters( 'bp_search_settings_post_type_taxonomies', get_object_taxonomies( $post_type ), $post_type );
	$child_order = $base_order + 10 + ( count( $taxonomies ) * 10 );

	bb_register_feature_field(
		'search',
		'pages_posts',
		'pages_posts_content',
		array(
			'name'              => 'bp_search_post_type_meta_' . $post_type,
			'label'             => __( 'Meta Data', 'buddyboss' ),
			'type'              => 'checkbox',
			'default'           => (bool) bp_get_option( 'bp_search_post_type_meta_' . $post_type, 0 ),
			'sanitize_callback' => 'absint',
			'parent_field'      => 'bp_search_post_type_' . $post_type,
			'order'             => $child_order,
		)
	);
}

/**
 * Lazily register Custom Post Type fields for Pages & Posts panel.
 *
 * Hooked to `bb_admin_settings_before_get_feature` which fires during the
 * AJAX request, when all custom post types from third-party plugins are
 * already registered. Registers CPT toggles within the same single section
 * so they appear in the same card as the standard post types.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param string $feature_id The feature being loaded.
 */
function bb_search_lazy_register_cpt_fields( $feature_id ) {
	// Only run for the search feature.
	if ( 'search' !== $feature_id ) {
		return;
	}

	$post_types = get_post_types( array( 'public' => true ) );

	// Collect CPTs (exclude standard + excluded types).
	$cpt_list = array();
	foreach ( $post_types as $post_type ) {
		if ( in_array( $post_type, BB_SEARCH_EXCLUDED_POST_TYPES, true ) ) {
			continue;
		}
		if ( in_array( $post_type, BB_SEARCH_STANDARD_POST_TYPES, true ) ) {
			continue;
		}
		$post_type_obj = get_post_type_object( $post_type );
		if ( ! $post_type_obj ) {
			continue;
		}
		$cpt_list[ $post_type ] = $post_type_obj;
	}

	// Nothing to register if no CPTs found.
	if ( empty( $cpt_list ) ) {
		return;
	}

	// Start CPT order after standard post types (each uses 100 increment).
	$parent_order = 10 + ( count( BB_SEARCH_STANDARD_POST_TYPES ) * 100 );

	// Hidden parent field — renders as a label-only row ("Custom Post Types")
	// with CPT toggles as its children in the React UI.
	bb_register_feature_field(
		'search',
		'pages_posts',
		'pages_posts_content',
		array(
			'name'  => 'bp_search_custom_post_types',
			'label' => __( 'Custom Post Types', 'buddyboss' ),
			'type'  => 'hidden',
			'order' => $parent_order,
		)
	);

	$child_order = $parent_order + 10;

	foreach ( $cpt_list as $post_type => $post_type_obj ) {
		$label = $post_type_obj->labels->name;

		// Toggle field per CPT — child of the hidden "Custom Post Types" parent.
		bb_register_feature_field(
			'search',
			'pages_posts',
			'pages_posts_content',
			array(
				'name'              => 'bp_search_post_type_' . $post_type,
				'label'             => $label,
				'type'              => 'toggle',
				'default'           => (bool) bp_get_option( 'bp_search_post_type_' . $post_type, 0 ),
				'sanitize_callback' => 'absint',
				'parent_field'      => 'bp_search_custom_post_types',
				'order'             => $child_order,
			)
		);

		$child_order += 10;
	}
}
add_action( 'bb_admin_settings_before_get_feature', 'bb_search_lazy_register_cpt_fields' );

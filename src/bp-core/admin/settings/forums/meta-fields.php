<?php
/**
 * BuddyBoss Admin Settings - Forums Meta Fields Registration.
 *
 * Registers core forum fields for the Forum Create/Edit modals
 * in the Settings 2.0 admin interface via BB_Admin_Meta_Field_Registry.
 *
 * @package BuddyBoss\Core\Administration
 * @since BuddyBoss 3.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Register core forum meta fields for the create/edit modals.
 *
 * Hooks into `bb_register_forums_meta_fields` at priority 1 so Platform fields
 * come first. Pro extensions and third-party plugins register at priority 10+.
 *
 * @since BuddyBoss 3.0.0
 *
 * @param BB_Admin_Meta_Field_Registry $registry  The registry instance.
 * @param string                       $component The component identifier.
 */
function bb_forums_register_core_meta_fields( $registry, $component ) {

	// =========================================================================
	// TAB: Details (order 10–90).
	// =========================================================================

	// Name.
	$registry->register(
		$component,
		'name',
		array(
			'label'             => __( 'Forum Name', 'buddyboss' ),
			'type'              => 'text',
			'tab'               => 'details',
			'order'             => 10,
			'save_phase'        => 'before',
			'get_value'         => function ( $forum ) {
				return $forum->post_title;
			},
			'save_value'        => function ( $forum, $value ) {
				$forum->post_title = $value;
			},
			'sanitize_callback' => 'sanitize_text_field',
		)
	);

	// Slug (Permalink).
	$registry->register(
		$component,
		'slug',
		array(
			'label'             => __( 'Permalink', 'buddyboss' ),
			'type'              => 'permalink',
			'tab'               => 'details',
			'order'             => 20,
			'save_phase'        => 'before',
			'get_value'         => function ( $forum ) {
				return $forum->post_name;
			},
			'get_extra_data'    => function ( $forum ) {
				$site_url       = trailingslashit( get_site_url() );
				$forum_slug     = get_option( '_bbp_forum_slug', 'forum' );
				$is_child_forum = ! empty( $forum->ID ) && function_exists( 'bb_get_child_forum_group_ids' ) && ! empty( bb_get_child_forum_group_ids( $forum->ID ) );

				// For child group forums, show the full permalink instead of base URL.
				if ( $is_child_forum ) {
					return array(
						'base_url'       => bbp_get_forum_permalink( $forum->ID ),
						'is_child_forum' => true,
					);
				}

				return array(
					'base_url' => $site_url . $forum_slug . '/',
				);
			},
			'save_value'        => function ( $forum, $value ) {
				$forum->post_name = sanitize_title( $value );
			},
			'sanitize_callback' => 'sanitize_title',
		)
	);

	// Description.
	$registry->register(
		$component,
		'description',
		array(
			'label'             => __( 'Forum Description (Optional)', 'buddyboss' ),
			'type'              => 'richtext',
			'tab'               => 'details',
			'order'             => 30,
			'save_phase'        => 'before',
			'get_value'         => function ( $forum ) {
				return $forum->post_content;
			},
			'save_value'        => function ( $forum, $value ) {
				$forum->post_content = $value;
			},
			'sanitize_callback' => 'wp_kses_post',
		)
	);

	// Type (Forum/Category).
	$registry->register(
		$component,
		'forum_type',
		array(
			'label'             => __( 'Type', 'buddyboss' ),
			'type'              => 'select',
			'tab'               => 'details',
			'order'             => 40,
			'layout'            => 'third',
			'save_phase'        => 'after',
			'get_value'         => function ( $forum ) {
				if ( empty( $forum->ID ) ) {
					return 'forum';
				}
				return bbp_get_forum_type( $forum->ID );
			},
			'get_options'       => function ( $forum ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found -- Callback signature required by registry.
				return array(
					array(
						'value' => 'forum',
						'label' => __( 'Forum', 'buddyboss' ),
					),
					array(
						'value' => 'category',
						'label' => __( 'Category', 'buddyboss' ),
					),
				);
			},
			// No-op: type is saved via $_POST['bbp_forum_type'] for bbp_save_forum_extras().
			'save_value'        => function ( $forum, $value ) {}, // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found, Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
			'sanitize_callback' => 'sanitize_key',
		)
	);

	// Status (Open/Closed).
	$registry->register(
		$component,
		'forum_status',
		array(
			'label'             => __( 'Status', 'buddyboss' ),
			'type'              => 'select',
			'tab'               => 'details',
			'order'             => 42,
			'layout'            => 'third',
			'save_phase'        => 'after',
			'get_value'         => function ( $forum ) {
				if ( empty( $forum->ID ) ) {
					return 'open';
				}
				return bbp_get_forum_status( $forum->ID );
			},
			'get_options'       => function ( $forum ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found -- Callback signature required by registry.
				return array(
					array(
						'value' => 'open',
						'label' => __( 'Open', 'buddyboss' ),
					),
					array(
						'value' => 'closed',
						'label' => __( 'Closed', 'buddyboss' ),
					),
				);
			},
			// No-op: forum status is saved directly by create_forum()/save_forum()
			// AJAX handlers via bbp_close_forum()/bbp_open_forum(). Defining
			// save_value so the field is not marked readonly in the JSON response.
			'save_value'        => function ( $forum, $value ) {}, // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found, Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
			'sanitize_callback' => 'sanitize_key',
		)
	);

	// Visibility (Public/Private/Hidden).
	$registry->register(
		$component,
		'visibility',
		array(
			'label'             => __( 'Visibility', 'buddyboss' ),
			'type'              => 'select',
			'tab'               => 'details',
			'order'             => 44,
			'layout'            => 'third',
			'save_phase'        => 'before',
			'get_value'         => function ( $forum ) {
				if ( empty( $forum->ID ) ) {
					return 'publish';
				}
				return get_post_status( $forum->ID );
			},
			'get_options'       => function ( $forum ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found -- Callback signature required by registry.
				return array(
					array(
						'value' => 'publish',
						'label' => __( 'Public', 'buddyboss' ),
					),
					array(
						'value' => 'private',
						'label' => __( 'Private', 'buddyboss' ),
					),
					array(
						'value' => 'hidden',
						'label' => __( 'Hidden', 'buddyboss' ),
					),
				);
			},
			'save_value'        => function ( $forum, $value ) {
				$allowed = array( 'publish', 'private', 'hidden' );
				if ( in_array( $value, $allowed, true ) ) {
					$forum->post_status = $value;
				}
			},
			'sanitize_callback' => 'sanitize_key',
		)
	);

	// Parent Forum.
	$registry->register(
		$component,
		'parent_id',
		array(
			'label'             => __( 'Parent Forum', 'buddyboss' ),
			'type'              => 'async_select',
			'async_action'      => 'bb_admin_forum_autocomplete',
			'placeholder'       => __( 'None', 'buddyboss' ),
			'tab'               => 'details',
			'order'             => 60,
			'layout'            => 'half',
			'save_phase'        => 'before',
			'get_value'         => function ( $forum ) {
				return (int) $forum->post_parent;
			},
			'save_value'        => function ( $forum, $value ) {
				$new_parent = absint( $value );

				// Prevent self-parenting.
				if ( ! empty( $forum->ID ) && $new_parent === $forum->ID ) {
					$new_parent = 0;
				}

				$forum->post_parent = $new_parent;
			},
			'sanitize_callback' => 'absint',
		)
	);

	// Order.
	$registry->register(
		$component,
		'order',
		array(
			'label'             => __( 'Order', 'buddyboss' ),
			'type'              => 'number',
			'tab'               => 'details',
			'order'             => 70,
			'layout'            => 'half',
			'save_phase'        => 'before',
			'get_value'         => function ( $forum ) {
				return (int) $forum->menu_order;
			},
			'save_value'        => function ( $forum, $value ) {
				$forum->menu_order = absint( $value );
			},
			'sanitize_callback' => 'absint',
		)
	);
}
add_action( 'bb_register_forums_meta_fields', 'bb_forums_register_core_meta_fields', 1, 2 );

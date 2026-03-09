<?php
/**
 * BuddyBoss Admin Settings - Search Network Search Panel.
 *
 * Registers the Network Search side panel sections and fields for the
 * Search feature in the Settings 2.0 registry.
 *
 * @package BuddyBoss\Core\Administration
 * @since   BuddyBoss [BBVERSION]
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Register Network Search panel sections and fields.
 *
 * Called from bb-admin-settings-search.php after side panels are registered.
 *
 * All component toggles (Members, Forums, Groups, etc.) are registered as
 * fields within a single section so the React UI renders them in one
 * continuous card — matching the Figma design.
 *
 * Note: xProfile fields are registered lazily via bb_admin_settings_before_get_feature
 * because bp_xprofile_get_groups() returns empty results at bb_register_features time
 * (the xProfile DB layer isn't fully initialized yet at bp_loaded priority 5).
 *
 * @since BuddyBoss [BBVERSION]
 */
function bb_search_register_network_search_fields() {

	// Single section for the entire Network Search panel.
	bb_register_feature_section(
		'search',
		'network_search',
		'network_search_components',
		array(
			'title'       => __( 'Network Search', 'buddyboss' ),
			'description' => __( 'All listed BuddyBoss components are searchable. The switches indicate which ones are included in searches.', 'buddyboss' ),
			'order'       => 10,
		)
	);

	// =========================================================================
	// Members.
	// =========================================================================
	bb_register_feature_field(
		'search',
		'network_search',
		'network_search_components',
		array(
			'name'              => 'bp_search_members',
			'label'             => __( 'Members', 'buddyboss' ),
			'description'       => __( 'Allow member searching', 'buddyboss' ),
			'type'              => 'toggle',
			'default'           => (bool) bp_get_option( 'bp_search_members', 1 ),
			'sanitize_callback' => 'absint',
			'order'             => 10,
		)
	);

	// User account fields — use bp_get_search_user_fields() when available,
	// otherwise inline fallback values since the function is defined in
	// bp-search-functions.php which loads at 'init' (after bb_register_features).
	$user_fields = function_exists( 'bp_get_search_user_fields' )
		? bp_get_search_user_fields()
		: array(
			'user_meta'    => __( 'User Meta', 'buddyboss' ),
			'display_name' => __( 'Display Name', 'buddyboss' ),
			'user_email'   => __( 'User Email', 'buddyboss' ),
			'user_login'   => __( 'Username', 'buddyboss' ),
		);

	$child_order = 20;
	foreach ( $user_fields as $field_key => $field_label ) {
		$option_name = 'bp_search_user_field_' . $field_key;
		bb_register_feature_field(
			'search',
			'network_search',
			'network_search_components',
			array(
				'name'              => $option_name,
				'label'             => $field_label,
				'type'              => 'checkbox',
				'default'           => (bool) bp_get_option( $option_name, 0 ),
				'sanitize_callback' => 'absint',
				'parent_field'      => 'bp_search_members',
				'child_group_label' => __( 'Account', 'buddyboss' ),
				'order'             => $child_order,
			)
		);
		$child_order += 10;
	}

	// xProfile fields are registered lazily — see bb_search_lazy_register_xprofile_fields().

	// =========================================================================
	// Forums (conditional).
	// =========================================================================
	if ( bp_is_active( 'forums' ) ) {
		bb_register_feature_field(
			'search',
			'network_search',
			'network_search_components',
			array(
				'name'              => 'bp_search_post_type_forum',
				'label'             => __( 'Forums', 'buddyboss' ),
				'description'       => __( 'Allow forum searching', 'buddyboss' ),
				'type'              => 'toggle',
				'default'           => (bool) bp_get_option( 'bp_search_post_type_forum', 1 ),
				'sanitize_callback' => 'absint',
				'order'             => 100,
			)
		);

		// FIELD: Discussion (child).
		bb_register_feature_field(
			'search',
			'network_search',
			'network_search_components',
			array(
				'name'              => 'bp_search_post_type_topic',
				'label'             => __( 'Discussion', 'buddyboss' ),
				'type'              => 'checkbox',
				'default'           => (bool) bp_get_option( 'bp_search_post_type_topic', 1 ),
				'sanitize_callback' => 'absint',
				'parent_field'      => 'bp_search_post_type_forum',
				'order'             => 110,
			)
		);

		// FIELD: Discussion Tags (child).
		bb_register_feature_field(
			'search',
			'network_search',
			'network_search_components',
			array(
				'name'              => 'bp_search_topic_tax_topic-tag',
				'label'             => __( 'Discussion Tags', 'buddyboss' ),
				'type'              => 'checkbox',
				'default'           => (bool) bp_get_option( 'bp_search_topic_tax_topic-tag', 0 ),
				'sanitize_callback' => 'absint',
				'parent_field'      => 'bp_search_post_type_forum',
				'order'             => 120,
			)
		);

		// FIELD: Replies (child).
		bb_register_feature_field(
			'search',
			'network_search',
			'network_search_components',
			array(
				'name'              => 'bp_search_post_type_reply',
				'label'             => __( 'Replies', 'buddyboss' ),
				'type'              => 'checkbox',
				'default'           => (bool) bp_get_option( 'bp_search_post_type_reply', 1 ),
				'sanitize_callback' => 'absint',
				'parent_field'      => 'bp_search_post_type_forum',
				'order'             => 130,
			)
		);
	}

	// =========================================================================
	// Groups (conditional).
	// =========================================================================
	if ( bp_is_active( 'groups' ) ) {
		bb_register_feature_field(
			'search',
			'network_search',
			'network_search_components',
			array(
				'name'              => 'bp_search_groups',
				'label'             => __( 'Groups', 'buddyboss' ),
				'description'       => __( 'Allow group searching', 'buddyboss' ),
				'type'              => 'toggle',
				'default'           => (bool) bp_get_option( 'bp_search_groups', 1 ),
				'sanitize_callback' => 'absint',
				'order'             => 200,
			)
		);
	}

	// =========================================================================
	// Photos (conditional).
	// =========================================================================
	if ( bp_is_active( 'media' ) ) {
		bb_register_feature_field(
			'search',
			'network_search',
			'network_search_components',
			array(
				'name'              => 'bp_search_photos',
				'label'             => __( 'Photos', 'buddyboss' ),
				'description'       => __( 'Allow photo searching', 'buddyboss' ),
				'type'              => 'toggle',
				'default'           => (bool) bp_get_option( 'bp_search_photos', 0 ),
				'sanitize_callback' => 'absint',
				'order'             => 300,
			)
		);

		// FIELD: Albums (child of Photos).
		bb_register_feature_field(
			'search',
			'network_search',
			'network_search_components',
			array(
				'name'              => 'bp_search_albums',
				'label'             => __( 'Albums', 'buddyboss' ),
				'type'              => 'checkbox',
				'default'           => (bool) bp_get_option( 'bp_search_albums', 0 ),
				'sanitize_callback' => 'absint',
				'parent_field'      => 'bp_search_photos',
				'order'             => 310,
			)
		);

		// =====================================================================
		// Videos.
		// =====================================================================
		bb_register_feature_field(
			'search',
			'network_search',
			'network_search_components',
			array(
				'name'              => 'bp_search_videos',
				'label'             => __( 'Videos', 'buddyboss' ),
				'description'       => __( 'Allow video searching', 'buddyboss' ),
				'type'              => 'toggle',
				'default'           => (bool) bp_get_option( 'bp_search_videos', 0 ),
				'sanitize_callback' => 'absint',
				'order'             => 400,
			)
		);
	}

	// =========================================================================
	// Documents (conditional).
	// =========================================================================
	if ( bp_is_active( 'media' ) && function_exists( 'bp_is_group_document_support_enabled' ) && ( bp_is_group_document_support_enabled() || bp_is_profile_document_support_enabled() ) ) {
		bb_register_feature_field(
			'search',
			'network_search',
			'network_search_components',
			array(
				'name'              => 'bp_search_documents',
				'label'             => __( 'Documents', 'buddyboss' ),
				'description'       => __( 'Allow document searching', 'buddyboss' ),
				'type'              => 'toggle',
				'default'           => (bool) bp_get_option( 'bp_search_documents', 0 ),
				'sanitize_callback' => 'absint',
				'order'             => 500,
			)
		);

		// FIELD: Folders (child of Documents).
		bb_register_feature_field(
			'search',
			'network_search',
			'network_search_components',
			array(
				'name'              => 'bp_search_folders',
				'label'             => __( 'Folders', 'buddyboss' ),
				'type'              => 'checkbox',
				'default'           => (bool) bp_get_option( 'bp_search_folders', 0 ),
				'sanitize_callback' => 'absint',
				'parent_field'      => 'bp_search_documents',
				'order'             => 510,
			)
		);
	}

	// =========================================================================
	// Activity (conditional).
	// =========================================================================
	if ( bp_is_active( 'activity' ) ) {
		bb_register_feature_field(
			'search',
			'network_search',
			'network_search_components',
			array(
				'name'              => 'bp_search_activity',
				'label'             => __( 'Activity', 'buddyboss' ),
				'description'       => __( 'Allow activity searching', 'buddyboss' ),
				'type'              => 'toggle',
				'default'           => (bool) bp_get_option( 'bp_search_activity', 1 ),
				'sanitize_callback' => 'absint',
				'order'             => 600,
			)
		);

		// FIELD: Activity comments (child).
		bb_register_feature_field(
			'search',
			'network_search',
			'network_search_components',
			array(
				'name'              => 'bp_search_activity_comments',
				'label'             => __( 'Activity comments', 'buddyboss' ),
				'type'              => 'checkbox',
				'default'           => (bool) bp_get_option( 'bp_search_activity_comments', 1 ),
				'sanitize_callback' => 'absint',
				'parent_field'      => 'bp_search_activity',
				'order'             => 610,
			)
		);
	}
}

/**
 * Lazy-register xProfile fields for the Search Network Search panel.
 *
 * Deferred to the AJAX request because bp_xprofile_get_groups() returns
 * empty at bb_register_features time (bp_loaded priority 5) — the xProfile
 * DB layer isn't fully initialized yet. This hook fires when the admin
 * loads the Search settings page, at which point xProfile data is available.
 *
 * Uses a static flag to prevent duplicate registration across multiple
 * AJAX calls in the same request.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param string $feature_id The feature being loaded.
 */
function bb_search_lazy_register_xprofile_fields( $feature_id ) {

	if ( 'search' !== $feature_id ) {
		return;
	}

	// Prevent duplicate registration.
	static $registered = false;
	if ( $registered ) {
		return;
	}
	$registered = true;

	if ( ! bp_is_active( 'xprofile' ) || ! function_exists( 'bp_xprofile_get_groups' ) ) {
		return;
	}

	$groups = bp_xprofile_get_groups(
		array(
			'fetch_fields' => true,
		)
	);

	if ( empty( $groups ) ) {
		return;
	}

	// Start after the 4 hardcoded user fields (orders 20, 30, 40, 50).
	$child_order = 60;

	foreach ( $groups as $group ) {
		if ( ! is_object( $group ) || empty( $group->fields ) ) {
			continue;
		}

		$group_label = ! empty( $group->name ) ? $group->name : '';

		foreach ( $group->fields as $field ) {
			if ( ! is_object( $field ) || empty( $field->id ) ) {
				continue;
			}

			if ( true === bp_core_hide_display_name_field( $field->id ) ) {
				continue;
			}

			$option_name = 'bp_search_xprofile_' . $field->id;
			bb_register_feature_field(
				'search',
				'network_search',
				'network_search_components',
				array(
					'name'              => $option_name,
					'label'             => $field->name,
					'type'              => 'checkbox',
					'default'           => (bool) bp_get_option( $option_name, 0 ),
					'sanitize_callback' => 'absint',
					'parent_field'      => 'bp_search_members',
					'child_group_label' => $group_label,
					'order'             => $child_order,
				)
			);
			$child_order += 10;
		}
	}
}
add_action( 'bb_admin_settings_before_get_feature', 'bb_search_lazy_register_xprofile_fields' );

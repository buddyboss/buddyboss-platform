<?php
/**
 * BuddyBoss Admin Settings 2.0 Migration
 *
 * Migrates existing settings from BP_Admin_Setting_tab classes to Feature Registry.
 * Reads from WordPress Settings API globals after existing classes register their fields.
 *
 * @package BuddyBoss\Core\Administration
 * @since BuddyBoss 3.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Migrate existing settings to Feature Registry.
 *
 * This function reads from WordPress Settings API globals ($wp_settings_sections, $wp_settings_fields)
 * after existing BP_Admin_Setting_* classes have registered their fields.
 *
 * @since BuddyBoss 3.0.0
 */
function bb_admin_settings_2_0_migrate_existing_settings() {
	global $wp_settings_sections, $wp_settings_fields;

	// Only migrate if we have existing settings registered.
	if ( empty( $wp_settings_sections ) || empty( $wp_settings_fields ) ) {
		return;
	}

	$registry = bb_feature_registry();

	// Map of tab names to feature IDs.
	$tab_to_feature_map = array(
		'bp-activity' => 'activity',
		'bp-groups'   => 'groups',
		'bp-members'  => 'members',
		'bp-messages' => 'messages',
		'bp-media'    => 'media',
		'bp-video'    => 'video',
		'bp-document' => 'document',
		'bp-forums'   => 'forums',
		'bp-friends'  => 'friends',
		'bp-notifications' => 'notifications',
		'bp-invites'  => 'invites',
		'bp-moderation' => 'moderation',
		'bp-search'   => 'search',
		'bp-xprofile' => 'xprofile',
		'bp-registration' => 'registration',
		'bp-performance' => 'performance',
		'bp-general'  => 'general',
		'bp-credit'   => 'credit',
		'bp-labs'     => 'labs',
	);

	// Process each settings page (tab).
	foreach ( $wp_settings_sections as $tab_name => $sections ) {
		// Skip if not a BuddyBoss settings tab.
		if ( ! isset( $tab_to_feature_map[ $tab_name ] ) ) {
			continue;
		}

		$feature_id = $tab_to_feature_map[ $tab_name ];

		// Register feature if not already registered.
		if ( ! $registry->get_feature( $feature_id ) ) {
			// Get feature label from tab.
			$feature_label = ucfirst( str_replace( array( 'bp-', '-' ), array( '', ' ' ), $tab_name ) );
			
			// Try to get from existing admin tab class.
			$tab_class_name = 'BP_Admin_Setting_' . str_replace( ' ', '_', ucwords( str_replace( '-', ' ', str_replace( 'bp-', '', $tab_name ) ) ) );
			if ( class_exists( $tab_class_name ) ) {
				$tab_instance = new $tab_class_name();
				if ( isset( $tab_instance->tab_label ) ) {
					$feature_label = $tab_instance->tab_label;
				}
			}

			// Register feature.
			bb_register_feature(
				$feature_id,
				array(
					'label'              => $feature_label,
					'description'        => sprintf(
						/* translators: %s: feature label */
						__( 'Configure %s settings.', 'buddyboss' ),
						$feature_label
					),
					'icon'               => 'dashicons-admin-generic',
					'category'           => 'community',
					'license_tier'       => 'free',
					'is_active_callback' => function() use ( $feature_id ) {
						// Check if component is active.
						return bp_is_active( $feature_id );
					},
					'settings_route'     => '/settings/' . $feature_id,
					'order'              => 100,
				)
			);
		}

		// Process sections.
		foreach ( $sections as $section_id => $section ) {
			// Register section.
			$section_title = isset( $section['title'] ) ? $section['title'] : $section_id;
			$section_description = isset( $section['notice'] ) ? $section['notice'] : '';

			// Determine nav group from section ID or title.
			$nav_group = 'General';
			if ( strpos( $section_id, 'avatar' ) !== false || strpos( $section_id, 'image' ) !== false ) {
				$nav_group = 'Images';
			} elseif ( strpos( $section_id, 'header' ) !== false ) {
				$nav_group = 'Headers';
			} elseif ( strpos( $section_id, 'directory' ) !== false ) {
				$nav_group = 'Directory';
			} elseif ( strpos( $section_id, 'access' ) !== false || strpos( $section_id, 'control' ) !== false ) {
				$nav_group = 'Access Control';
			} elseif ( strpos( $section_id, 'comment' ) !== false ) {
				$nav_group = 'Comments';
			} elseif ( strpos( $section_id, 'topic' ) !== false ) {
				$nav_group = 'Topics';
			}

			bb_register_feature_section(
				$feature_id,
				$section_id,
				array(
					'title'       => $section_title,
					'description' => $section_description,
					'nav_group'   => $nav_group,
					'order'       => 100,
				)
			);

			// Process fields in this section.
			if ( isset( $wp_settings_fields[ $tab_name ][ $section_id ] ) ) {
				foreach ( $wp_settings_fields[ $tab_name ][ $section_id ] as $field_name => $field ) {
					// Get field type from callback or args.
					$field_type = 'text';
					$sanitize_callback = 'sanitize_text_field';

					// Determine field type from callback name or args.
					if ( isset( $field['args'] ) && is_array( $field['args'] ) ) {
						if ( isset( $field['args']['type'] ) ) {
							$field_type = $field['args']['type'];
						}
					}

					// Check callback name for hints.
					$callback = isset( $field['callback'] ) ? $field['callback'] : '';
					if ( is_string( $callback ) ) {
						if ( strpos( $callback, 'checkbox' ) !== false || strpos( $callback, 'toggle' ) !== false ) {
							$field_type = 'toggle';
							$sanitize_callback = 'intval';
						} elseif ( strpos( $callback, 'select' ) !== false ) {
							$field_type = 'select';
						} elseif ( strpos( $callback, 'radio' ) !== false ) {
							$field_type = 'radio';
						} elseif ( strpos( $callback, 'textarea' ) !== false ) {
							$field_type = 'textarea';
							$sanitize_callback = 'sanitize_textarea_field';
						}
					}

					// Get sanitize callback from registered setting.
					if ( isset( $field['args'] ) && is_string( $field['args'] ) ) {
						$sanitize_callback = $field['args'];
					} elseif ( isset( $field['args'] ) && is_array( $field['args'] ) && isset( $field['args']['sanitize_callback'] ) ) {
						$sanitize_callback = $field['args']['sanitize_callback'];
					}

					// Get default value.
					$default_value = get_option( $field_name, '' );

					// Register field.
					bb_register_feature_field(
						$feature_id,
						$section_id,
						array(
							'name'             => $field_name,
							'label'            => isset( $field['title'] ) ? $field['title'] : $field_name,
							'type'             => $field_type,
							'description'      => isset( $field['args']['description'] ) ? $field['args']['description'] : '',
							'default'          => $default_value,
							'sanitize_callback' => $sanitize_callback,
							'order'            => 100,
						)
					);
				}
			}
		}
	}
}

/**
 * Initialize migration after existing settings are registered.
 *
 * @since BuddyBoss 3.0.0
 */
function bb_admin_settings_2_0_init_migration() {
	// Wait for existing settings to be registered.
	// Existing BP_Admin_Setting_* classes register on 'bp_admin_init' or similar.
	add_action( 'bp_admin_init', 'bb_admin_settings_2_0_migrate_existing_settings', 100 );
}
add_action( 'bb_after_register_features', 'bb_admin_settings_2_0_init_migration', 5 );

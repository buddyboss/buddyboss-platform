<?php
/**
 * BuddyBoss Admin Settings - Member Directory Panel.
 *
 * Registers sections and fields for the Member Directory side panel.
 *
 * @package BuddyBoss\Core\Administration
 * @since BuddyBoss [BBVERSION]
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Register Member Directory panel sections and fields.
 *
 * @since BuddyBoss [BBVERSION]
 */
function bb_members_register_member_directory_panel_fields() {

	// -------------------------------------------------------------------------
	// SECTION: Member Directory
	// -------------------------------------------------------------------------
	bb_register_feature_section(
		'members',
		'member_directory',
		'member_directory',
		array(
			'title'       => __( 'Member Directory', 'buddyboss' ),
			'description' => '',
			'order'       => 10,
		)
	);

	// FIELD: Enabled View(s).
	bb_register_feature_field(
		'members',
		'member_directory',
		'member_directory',
		array(
			'name'              => 'bp-profile-layout-format',
			'label'             => __( 'Enabled View(s)', 'buddyboss' ),
			'type'              => 'select',
			'description'       => __( 'Display member directories in grid view, list view, or allow toggling between both views.', 'buddyboss' ),
			'default'           => bp_get_option( 'bp-profile-layout-format', 'list_grid' ),
			'sanitize_callback' => 'bb_members_sanitize_layout_format',
			'options'           => array(
				array(
					'label' => __( 'Grid and List', 'buddyboss' ),
					'value' => 'list_grid',
				),
				array(
					'label' => __( 'Grid', 'buddyboss' ),
					'value' => 'grid',
				),
				array(
					'label' => __( 'List', 'buddyboss' ),
					'value' => 'list',
				),
			),
			'order'             => 10,
		)
	);

	// FIELD: Default View (conditional — only when both grid and list are enabled).
	bb_register_feature_field(
		'members',
		'member_directory',
		'member_directory',
		array(
			'name'              => 'bp-profile-layout-default-format',
			'label'             => __( 'Default View', 'buddyboss' ),
			'type'              => 'radio',
			'description'       => '',
			'default'           => bp_profile_layout_default_format( 'grid' ),
			'sanitize_callback' => 'bb_members_sanitize_default_format',
			'options'           => array(
				array(
					'label' => __( 'Grid', 'buddyboss' ),
					'value' => 'grid',
				),
				array(
					'label' => __( 'List', 'buddyboss' ),
					'value' => 'list',
				),
			),
			'conditional'       => array(
				'field' => 'bp-profile-layout-format',
				'value' => 'list_grid',
			),
			'order'             => 20,
		)
	);

	// Build member directory element options from the existing function.
	$directory_elements = function_exists( 'bb_get_member_directory_elements' ) ? bb_get_member_directory_elements() : array();
	$element_options    = array();

	foreach ( $directory_elements as $element ) {
		$option = array(
			'label' => $element['element_label'],
			'value' => $element['element_name'],
		);

		// Disable elements that depend on inactive features.
		if ( ! empty( $element['element_class'] ) && false !== strpos( $element['element_class'], 'bp-hide' ) ) {
			$option['disabled'] = true;
		}

		$element_options[] = $option;
	}

	// FIELD: Elements (Pro only, toggle_list).
	if ( ! empty( $element_options ) ) {
		bb_register_feature_field(
			'members',
			'member_directory',
			'member_directory',
			array(
				'name'              => 'bb-member-directory-elements',
				'label'             => __( 'Elements', 'buddyboss' ),
				'type'              => 'toggle_list',
				'description'       => __( 'Select which elements to show in your member directories.', 'buddyboss' ),
				'default'           => array(),
				'sanitize_callback' => 'bb_members_sanitize_toggle_list',
				'options'           => $element_options,
				'pro_only'          => true,
				'order'             => 30,
			)
		);
	}

	// Build profile action options from the existing function.
	$profile_actions    = function_exists( 'bb_get_member_directory_profile_actions' ) ? bb_get_member_directory_profile_actions() : array();
	$action_options     = array();
	$has_visible_action = false;

	foreach ( $profile_actions as $action ) {
		$option = array(
			'label' => $action['element_label'],
			'value' => $action['element_name'],
		);

		$is_hidden = ! empty( $action['element_class'] ) && false !== strpos( $action['element_class'], 'bp-hide' );

		if ( $is_hidden ) {
			$option['disabled'] = true;
		} else {
			$has_visible_action = true;
		}

		$action_options[] = $option;
	}

	// FIELD: Profile Actions (Pro only, toggle_list).
	if ( ! empty( $action_options ) && $has_visible_action ) {
		bb_register_feature_field(
			'members',
			'member_directory',
			'member_directory',
			array(
				'name'              => 'bb-member-profile-actions',
				'label'             => __( 'Profile Actions', 'buddyboss' ),
				'type'              => 'toggle_list',
				'description'       => __( 'Select which profile actions to enable in your member directories', 'buddyboss' ),
				'default'           => array(),
				'sanitize_callback' => 'bb_members_sanitize_toggle_list',
				'options'           => $action_options,
				'pro_only'          => true,
				'order'             => 40,
			)
		);

		// FIELD: Primary Action (Pro only, select — depends on at least one action enabled).
		$selected_actions = function_exists( 'bb_get_enabled_member_directory_profile_actions' ) ? bb_get_enabled_member_directory_profile_actions() : array();

		// Build primary action options from visible (non-hidden) actions.
		$primary_action_options = array(
			array(
				'label' => __( 'None', 'buddyboss' ),
				'value' => '',
			),
		);

		foreach ( $profile_actions as $action ) {
			$is_hidden = ! empty( $action['element_class'] ) && false !== strpos( $action['element_class'], 'bp-hide' );
			if ( ! $is_hidden ) {
				$primary_action_options[] = array(
					'label' => $action['element_label'],
					'value' => $action['element_name'],
				);
			}
		}

		if ( ! empty( $selected_actions ) ) {
			bb_register_feature_field(
				'members',
				'member_directory',
				'member_directory',
				array(
					'name'              => 'bb-member-profile-primary-action',
					'label'             => __( 'Primary Action', 'buddyboss' ),
					'type'              => 'select',
					'description'       => __( 'Select which profile action to show as a primary button. The remaining enabled profile actions will be shown as secondary buttons underneath.', 'buddyboss' ),
					'default'           => function_exists( 'bb_get_member_directory_primary_action' ) ? bb_get_member_directory_primary_action() : '',
					'sanitize_callback' => 'sanitize_text_field',
					'options'           => $primary_action_options,
					'pro_only'          => true,
					'order'             => 50,
				)
			);
		}
	}

	/**
	 * Fires after Member Directory section fields are registered.
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	do_action( 'bb_members_settings_after_directory_fields' );
}

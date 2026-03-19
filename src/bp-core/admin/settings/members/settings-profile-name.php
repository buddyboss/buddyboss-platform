<?php
/**
 * BuddyBoss Admin Settings - Profile Name Panel.
 *
 * Registers sections and fields for the Profile Name side panel.
 *
 * @package BuddyBoss\Core\Administration
 * @since BuddyBoss [BBVERSION]
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Register Profile Name panel sections and fields.
 *
 * @since BuddyBoss [BBVERSION]
 */
function bb_members_register_profile_name_panel_fields() {

	// -------------------------------------------------------------------------
	// SECTION: Profile Name
	// -------------------------------------------------------------------------
	bb_register_feature_section(
		'members',
		'profile_name',
		'profile_name',
		array(
			'title'       => __( 'Profile Name', 'buddyboss' ),
			'description' => '',
			'order'       => 10,
		)
	);

	// FIELD: Display Name Format.
	bb_register_feature_field(
		'members',
		'profile_name',
		'profile_name',
		array(
			'name'              => 'bp-display-name-format',
			'label'             => __( 'Display Name Format', 'buddyboss' ),
			'type'              => 'select',
			'description'       => sprintf(
				/* translators: %s: Repair Community link. */
				__( 'After the format has been updated, remember to run <a href="%s">Repair Community</a> tools to update all the users.', 'buddyboss' ),
				esc_url(
					add_query_arg(
						array(
							'page' => 'bp-repair-community',
							'tab'  => 'bp-repair-community',
							'tool' => 'bp-wordpress-update-display-name',
						),
						admin_url( 'admin.php' )
					)
				)
			),
			'default'           => bp_core_display_name_format(),
			'sanitize_callback' => 'bb_members_sanitize_display_name_format',
			'options'           => array(
				array(
					'label' => __( 'First Name', 'buddyboss' ),
					'value' => 'first_name',
				),
				array(
					'label' => __( 'First Name & Last Name', 'buddyboss' ),
					'value' => 'first_last_name',
				),
				array(
					'label' => __( 'Nickname', 'buddyboss' ),
					'value' => 'nickname',
				),
			),
			'order'             => 10,
		)
	);

	// ---- Display Name Fields — "First Name" mode ----
	// Shows 3 toggles: First Name (disabled), Last Name (can be disabled), Nickname (disabled).
	// Legacy: bp_admin_setting_display_name_first_name() — checkbox with css class 'first-name-options'.

	// FIELD: First Name (disabled, always on).
	bb_register_feature_field(
		'members',
		'profile_name',
		'profile_name',
		array(
			'name'              => 'bp-display-name-fn-first-name',
			'label'             => __( 'Display Name Fields', 'buddyboss' ),
			'type'              => 'toggle',
			'description'       => __( 'First Name', 'buddyboss' ),
			'default'           => 1,
			'disabled'          => true,
			'sanitize_callback' => 'absint',
			'conditional'       => array(
				'field' => 'bp-display-name-format',
				'value' => 'first_name',
			),
			'group'             => 'display_name_fields_first_name',
			'order'             => 20,
		)
	);

	// FIELD: Last Name (editable toggle).
	// Legacy DB: bp-hide-last-name = 1 means field is visible (confusing name, but 1 = show).
	bb_register_feature_field(
		'members',
		'profile_name',
		'profile_name',
		array(
			'name'              => 'bp-hide-last-name',
			'label'             => '',
			'type'              => 'toggle',
			'description'       => __( 'Last Name', 'buddyboss' ),
			'default'           => absint( bp_hide_last_name() ),
			'sanitize_callback' => 'absint',
			'conditional'       => array(
				'field' => 'bp-display-name-format',
				'value' => 'first_name',
			),
			'group'             => 'display_name_fields_first_name',
			'order'             => 21,
		)
	);

	// FIELD: Nickname (disabled, always on).
	bb_register_feature_field(
		'members',
		'profile_name',
		'profile_name',
		array(
			'name'              => 'bp-display-name-fn-nickname',
			'label'             => '',
			'type'              => 'toggle',
			'description'       => __( 'Nickname', 'buddyboss' ),
			'default'           => 1,
			'disabled'          => true,
			'sanitize_callback' => 'absint',
			'help_text'         => __( 'If you disable "Last Name" field, it will not appear anywhere in the network.', 'buddyboss' ),
			'conditional'       => array(
				'field' => 'bp-display-name-format',
				'value' => 'first_name',
			),
			'group'             => 'display_name_fields_first_name',
			'order'             => 22,
		)
	);

	// ---- Display Name Fields — "First Name & Last Name" mode ----
	// Shows 3 toggles: all disabled (all fields required).
	// Legacy: bp_admin_setting_display_name_first_last_name() — all checkboxes disabled.

	// FIELD: First Name (disabled, always on).
	bb_register_feature_field(
		'members',
		'profile_name',
		'profile_name',
		array(
			'name'              => 'bp-display-name-fln-first-name',
			'label'             => __( 'Display Name Fields', 'buddyboss' ),
			'type'              => 'toggle',
			'description'       => __( 'First Name', 'buddyboss' ),
			'default'           => 1,
			'disabled'          => true,
			'sanitize_callback' => 'absint',
			'conditional'       => array(
				'field' => 'bp-display-name-format',
				'value' => 'first_last_name',
			),
			'group'             => 'display_name_fields_first_last_name',
			'order'             => 30,
		)
	);

	// FIELD: Last Name (disabled, always on).
	bb_register_feature_field(
		'members',
		'profile_name',
		'profile_name',
		array(
			'name'              => 'bp-display-name-fln-last-name',
			'label'             => '',
			'type'              => 'toggle',
			'description'       => __( 'Last Name', 'buddyboss' ),
			'default'           => 1,
			'disabled'          => true,
			'sanitize_callback' => 'absint',
			'conditional'       => array(
				'field' => 'bp-display-name-format',
				'value' => 'first_last_name',
			),
			'group'             => 'display_name_fields_first_last_name',
			'order'             => 31,
		)
	);

	// FIELD: Nickname (disabled, always on).
	bb_register_feature_field(
		'members',
		'profile_name',
		'profile_name',
		array(
			'name'              => 'bp-display-name-fln-nickname',
			'label'             => '',
			'type'              => 'toggle',
			'description'       => __( 'Nickname', 'buddyboss' ),
			'default'           => 1,
			'disabled'          => true,
			'sanitize_callback' => 'absint',
			'help_text'         => __( 'All name fields are required with this format. Best used for professional networks.', 'buddyboss' ),
			'conditional'       => array(
				'field' => 'bp-display-name-format',
				'value' => 'first_last_name',
			),
			'group'             => 'display_name_fields_first_last_name',
			'order'             => 32,
		)
	);

	// ---- Display Name Fields — "Nickname" mode ----
	// Shows 3 toggles: First Name (can be disabled), Last Name (can be disabled), Nickname (disabled).
	// Legacy: bp_admin_setting_callback_nickname_hide_first_name() + bp_admin_setting_callback_nickname_hide_last_name().

	// FIELD: First Name (editable toggle).
	// Legacy DB: bp-hide-nickname-first-name = 1 means field is visible (confusing name, but 1 = show).
	bb_register_feature_field(
		'members',
		'profile_name',
		'profile_name',
		array(
			'name'              => 'bp-hide-nickname-first-name',
			'label'             => __( 'Display Name Fields', 'buddyboss' ),
			'type'              => 'toggle',
			'description'       => __( 'First Name', 'buddyboss' ),
			'default'           => absint( bp_hide_nickname_first_name() ),
			'sanitize_callback' => 'absint',
			'conditional'       => array(
				'field' => 'bp-display-name-format',
				'value' => 'nickname',
			),
			'group'             => 'display_name_fields_nickname',
			'order'             => 40,
		)
	);

	// FIELD: Last Name (editable toggle).
	// Legacy DB: bp-hide-nickname-last-name = 1 means field is visible (confusing name, but 1 = show).
	bb_register_feature_field(
		'members',
		'profile_name',
		'profile_name',
		array(
			'name'              => 'bp-hide-nickname-last-name',
			'label'             => '',
			'type'              => 'toggle',
			'description'       => __( 'Last Name', 'buddyboss' ),
			'default'           => absint( bp_hide_nickname_last_name() ),
			'sanitize_callback' => 'absint',
			'conditional'       => array(
				'field' => 'bp-display-name-format',
				'value' => 'nickname',
			),
			'group'             => 'display_name_fields_nickname',
			'order'             => 41,
		)
	);

	// FIELD: Nickname (disabled, always on).
	bb_register_feature_field(
		'members',
		'profile_name',
		'profile_name',
		array(
			'name'              => 'bp-display-name-nn-nickname',
			'label'             => '',
			'type'              => 'toggle',
			'description'       => __( 'Nickname', 'buddyboss' ),
			'default'           => 1,
			'disabled'          => true,
			'sanitize_callback' => 'absint',
			'help_text'         => __( 'If you disable "First Name" and "Last Name" fields, they will not appear anywhere in the network. This allows your members to be fully anonymous (if they use a pseudonym for their nickname).', 'buddyboss' ),
			'conditional'       => array(
				'field' => 'bp-display-name-format',
				'value' => 'nickname',
			),
			'group'             => 'display_name_fields_nickname',
			'order'             => 42,
		)
	);

	/**
	 * Fires after Profile Name section fields are registered.
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	do_action( 'bb_members_settings_after_profile_name_fields' );

	// -------------------------------------------------------------------------
	// SECTION: Profile Link
	// -------------------------------------------------------------------------
	bb_register_feature_section(
		'members',
		'profile_name',
		'profile_link',
		array(
			'title'       => __( 'Profile Link', 'buddyboss' ),
			'description' => '',
			'order'       => 20,
		)
	);

	// FIELD: Link Format.
	bb_register_feature_field(
		'members',
		'profile_name',
		'profile_link',
		array(
			'name'              => 'bb_profile_slug_format',
			'label'             => __( 'Link Format', 'buddyboss' ),
			'type'              => 'radio',
			'description'       => __( 'Select the format for members\' profile links (e.g., /members/username). Both formats work, so switching won\'t break existing links.', 'buddyboss' ),
			'default'           => bb_get_profile_slug_format(),
			'sanitize_callback' => 'bb_members_sanitize_slug_format',
			'options'           => array(
				array(
					'label' => __( 'Username', 'buddyboss' ),
					'value' => 'username',
				),
				array(
					'label' => __( 'Unique Identifier', 'buddyboss' ),
					'value' => 'unique_identifier',
				),
			),
			'order'             => 10,
		)
	);

	/**
	 * Fires after Profile Link section fields are registered.
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	do_action( 'bb_members_settings_after_profile_link_fields' );
}

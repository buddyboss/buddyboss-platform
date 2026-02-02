<?php
/**
 * BuddyBoss Admin Settings - Reactions Feature Settings Registration.
 *
 * Registers Reactions feature settings with the new hierarchy:
 * Feature → Side Panels → Sections → Fields
 *
 * @package BuddyBoss\Features\Community\Reactions
 * @since BuddyBoss [BBVERSION]
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

require_once __DIR__ . '/callbacks.php';

/**
 * Register Reactions feature settings in Feature Registry.
 *
 * @since BuddyBoss [BBVERSION]
 */
function bb_admin_settings_register_reactions_settings() {

	// =========================================================================
	// SIDE PANEL: DISPLAY SETTINGS
	// =========================================================================
	bb_register_side_panel(
		'reactions',
		'display_settings',
		array(
			'title'      => __( 'Display Settings', 'buddyboss' ),
			'icon'       => array(
				'type'  => 'font',
				'class' => 'bb-icons-rl bb-icons-rl-gear-six',
			),
			'help_url'   => bp_get_admin_url(
				add_query_arg(
					array(
						'page'    => 'bp-help',
						'article' => 127197,
					),
					'admin.php'
				)
			),
			'order'      => 10,
			'is_default' => true,
		)
	);

	// =========================================================================
	// SIDE PANEL: REACTIONS (navigation item)
	// =========================================================================
	bb_register_side_panel(
		'reactions',
		'reactions_nav',
		array(
			'title'      => __( 'Reactions', 'buddyboss' ),
			'icon'       => array(
				'type'  => 'font',
				'class' => 'bb-icons-rl bb-icons-rl-smiley',
			),
			'help_url'   => '',
			'order'      => 20,
			'is_default' => false,
		)
	);

	// =========================================================================
	// SECTION: ENABLE REACTIONS
	// Card Title: "Display Settings" (from side panel)
	// Section Title: "Enable Reactions"
	// =========================================================================
	bb_register_feature_section(
		'reactions',
		'display_settings',
		'enable_reactions',
		array(
			'title'       => __( 'Display Settings', 'buddyboss' ),
			'description' => '',
			'order'       => 10,
		)
	);

	// -------------------------------------------------------------------------
	// FIELD: Enable Reactions (Toggle list for content types)
	// -------------------------------------------------------------------------
	bb_register_feature_field(
		'reactions',
		'display_settings',
		'enable_reactions',
		array(
			'name'              => 'bb_all_reactions',
			'label'             => __( 'Enable Reactions', 'buddyboss' ),
			'type'              => 'toggle_list',
			'description'       => __( 'Select the types of content that members are allowed to react to.', 'buddyboss' ),
			'options'           => array(
				array(
					'label' => __( 'Activity Posts', 'buddyboss' ),
					'value' => 'activity',
				),
				array(
					'label' => __( 'Activity Comments', 'buddyboss' ),
					'value' => 'activity_comment',
				),
			),
			'default'           => array(
				'activity'         => 1,
				'activity_comment' => 1,
			),
			'sanitize_callback' => 'bb_reactions_sanitize_content_types',
			'order'             => 10,
		)
	);

	// =========================================================================
	// SECTION: REACTIONS (under reactions_nav side panel)
	// For Reactions Mode and Button settings
	// =========================================================================
	bb_register_feature_section(
		'reactions',
		'reactions_nav',
		'reactions_settings',
		array(
			'title'       => __( 'Reactions', 'buddyboss' ),
			'description' => '',
			'order'       => 10,
		)
	);

	// -------------------------------------------------------------------------
	// FIELD: Conversion Warning Notice (shown when there are unconverted likes)
	// This notice will be registered by BuddyBoss Pro if there are unconverted likes
	// -------------------------------------------------------------------------

	/**
	 * Fires to allow Pro plugin to register conversion notice.
	 * Pro plugin checks for unconverted likes and registers the notice if needed.
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	do_action( 'bb_reactions_register_conversion_notice' );

	// -------------------------------------------------------------------------
	// FIELD: Reaction Mode (Radio: Like vs Emotions)
	// -------------------------------------------------------------------------
	$pro_class = function_exists( 'bb_get_pro_fields_class' ) ? bb_get_pro_fields_class( 'reaction' ) : '';
	$pro_label = function_exists( 'bb_get_pro_label_notice' ) ? bb_get_pro_label_notice( 'reaction' ) : '';

	bb_register_feature_field(
		'reactions',
		'reactions_nav',
		'reactions_settings',
		array(
			'name'              => 'bb_reaction_mode',
			'label'             => __( 'Reaction Mode', 'buddyboss' ),
			'type'              => 'reaction_mode',
			'description'       => '',
			'options'           => array(
				array(
					'label'       => __( 'Like', 'buddyboss' ),
					'value'       => 'likes',
					'description' => __( 'A simple \"Like\" button will show for members to express their appreciation or acknowledgement.', 'buddyboss' ),
				),
				array(
					'label'       => __( 'Emotions', 'buddyboss' ),
					'value'       => 'emotions',
					'description' => __( 'Members express their thoughts or feelings by selecting an emotion from a list of options. Maximum of only 6 emotions can be used.', 'buddyboss' ),
				),
			),
			'default'           => function_exists( 'bb_get_reaction_mode' ) ? bb_get_reaction_mode() : 'likes',
			'sanitize_callback' => 'sanitize_text_field',
			'order'             => 10,
			'pro_only'          => true,
			'pro_label'         => $pro_label,
		)
	);

	// -------------------------------------------------------------------------
	// FIELD: Reaction Emotions (Placeholder for Pro extension)
	// -------------------------------------------------------------------------
	// This field is intentionally empty in core and gets extended by Pro plugin
	// Pro plugin will register emotion management fields here

	/**
	 * Fires after reactions core settings fields are registered.
	 * Pro plugin uses this to add emotion management fields.
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	do_action( 'bb_reactions_register_emotion_fields' );

	// -------------------------------------------------------------------------
	// FIELD: Reaction Button (Icon + Text)
	// -------------------------------------------------------------------------
	$button_settings  = function_exists( 'bb_reaction_button_options' ) ? bb_reaction_button_options() : array();
	$button_icon      = isset( $button_settings['icon'] ) ? $button_settings['icon'] : 'thumbs-up';
	$button_text      = isset( $button_settings['text'] ) ? trim( $button_settings['text'] ) : __( 'Like', 'buddyboss' );
	$button_pro_label = function_exists( 'bb_get_pro_label_notice' ) ? bb_get_pro_label_notice( 'reaction' ) : '';

	bb_register_feature_field(
		'reactions',
		'reactions_nav',
		'reactions_settings',
		array(
			'name'              => 'bb_reactions_button',
			'label'             => __( 'Reaction Button', 'buddyboss' ),
			'type'              => 'reaction_button',
			'description'       => __( 'This label and icon indicate the default reaction before any reaction is selected. In \'Emotions\' mode, clicking the button applies the first reaction in the configured list.', 'buddyboss' ),
			'icon'              => $button_icon,
			'text'              => $button_text,
			'sanitize_callback' => 'bb_reactions_sanitize_button_settings',
			'default'           => array(
				'icon' => $button_icon,
				'text' => $button_text,
			),
			'order'             => 20,
			'pro_only'          => true,
			'pro_label'         => $button_pro_label,
		)
	);

	// -------------------------------------------------------------------------
	// FIELD: Migration Notice
	// Footer text about migration wizard
	// -------------------------------------------------------------------------
	bb_register_feature_field(
		'reactions',
		'reactions_nav',
		'reactions_settings',
		array(
			'name'        => 'bb_reactions_migration_notice',
			'label'       => '',
			'type'        => 'notice',
			'notice_type' => 'info',
			'description' => sprintf(
				/* translators: %s: link to migration wizard */
				__( 'When switching reactions mode, use our %s to map existing reactions to the new options.', 'buddyboss' ),
				'<a href="' . esc_url( admin_url( 'admin.php?page=bp-tools&tab=bb-reactions-migration' ) ) . '">' . __( 'migration wizard', 'buddyboss' ) . '</a>'
			),
			'order'       => 30,
		)
	);

	/**
	 * Fires after reactions settings fields are registered.
	 * Allows third-party extensions to add more fields.
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	do_action( 'bb_reactions_after_register_settings_fields' );
}

add_action( 'bb_register_features', 'bb_admin_settings_register_reactions_settings', 20 );

/**
 * Format reactions field data.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param array $field_data Formatted field data.
 * @param array $field      Field data.
 *
 * @return array Formatted field data.
 */
function bb_admin_settings_format_reactions_field_data( $field_data, $field ) {

	if ( 'reaction_mode' === ( $field['type'] ?? '' ) ) {

		$reactions_modes = array(
			'likes'    => array(
				'label'    => esc_html__( 'Likes', 'buddyboss' ),
				'name'     => 'bb_reaction_mode',
				'value'    => 'likes',
				'id'       => 'bb_reaction_mode_likes',
				'notice'   => __( 'A simple "Like" button will show for members to express their appreciation or acknowledgement.', 'buddyboss' ),
				'disabled' => false,
			),
			'emotions' => array(
				'label'    => esc_html__( 'Emotions', 'buddyboss' ),
				'name'     => 'bb_reaction_mode',
				'value'    => 'emotions',
				'id'       => 'bb_reaction_mode_emotions',
				'notice'   => esc_html__( 'Members express their thoughts or feelings by selecting an emotion from a list of options. Maximum of only 6 emotions can be used.', 'buddyboss' ),
				'disabled' => (
					! class_exists( 'BB_Reactions' ) ||
					! function_exists( 'bbp_pro_is_license_valid' ) ||
					! bbp_pro_is_license_valid()
				),
			),
		);

		/**
		 * Reuse the same filter so third-party code that modifies the
		 * legacy settings page also applies to the React UI.
		 *
		 * @since BuddyBoss [BBVERSION]
		 *
		 * @param array $reactions_modes Reaction mode options.
		 */
		$reactions_modes = apply_filters( 'bb_setting_reaction_mode_args', $reactions_modes );

		// Map to the option format React expects (label, value, id, notice, disabled).
		$field_data['options'] = array_values(
			array_map(
				function ( $mode ) {
					return array(
						'label'       => $mode['label'],
						'value'       => $mode['value'],
						'id'          => $mode['id'],
						'notice'      => $mode['notice'] ?? '',
						'disabled'    => ! empty( $mode['disabled'] ),
					);
				},
				$reactions_modes
			)
		);

		$pro_notice = bb_admin_settings_get_pro_notice( 'reaction' );
		if ( ! empty( $pro_notice['show'] ) ) {
			$field_data['pro_notice'] = $pro_notice;
		}
	}

	if ( 'reaction_button' === ( $field['type'] ?? '' ) ) {
		$button_settings = function_exists( 'bb_reaction_button_options' ) ? bb_reaction_button_options() : array();
		$button_icon     = isset( $button_settings['icon'] ) ? $button_settings['icon'] : 'thumbs-up';
		$button_text     = isset( $button_settings['text'] ) ? trim( $button_settings['text'] ) : '';

		$field_data['icon']      = $button_icon;
		$field_data['text']      = $button_text;
		$field_data['maxlength'] = 12;

		// Ensure value is set for React (icon + text).
		$current_value       = isset( $field_data['value'] ) && is_array( $field_data['value'] ) ? $field_data['value'] : array();
		$field_data['value'] = array(
			'icon' => isset( $current_value['icon'] ) ? $current_value['icon'] : $button_icon,
			'text' => isset( $current_value['text'] ) ? $current_value['text'] : $button_text,
		);

		$pro_notice = bb_admin_settings_get_pro_notice( 'reaction' );
		if ( ! empty( $pro_notice['show'] ) ) {
			$field_data['pro_notice'] = $pro_notice;
		}
	}

	return $field_data;
}

add_filter( 'bb_admin_settings_format_field_data', 'bb_admin_settings_format_reactions_field_data', 10, 2 );

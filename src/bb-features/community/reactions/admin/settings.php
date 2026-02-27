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

	// Static caches: these call bp_get_option() which is cheap with object cache, but avoids
	// redundant DB hits on the rare case this function is invoked more than once per request.
	static $cached_reaction_mode   = null;
	static $cached_button_settings = null;

	if ( null === $cached_reaction_mode ) {
		$cached_reaction_mode = function_exists( 'bb_get_reaction_mode' ) ? bb_get_reaction_mode() : 'likes';
	}

	if ( null === $cached_button_settings ) {
		$cached_button_settings = function_exists( 'bb_reaction_button_options' ) ? bb_reaction_button_options() : array();
	}

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
		'reactions',
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

	// Use titles (and optional sanitize_callback) from bb_reactions_get_settings_fields() so the filter applies to Settings 2.0.
	$reactions_legacy_fields = bb_reactions_get_settings_fields();
	$reaction_titles         = isset( $reactions_legacy_fields['bp_reaction_settings_section'] ) ? $reactions_legacy_fields['bp_reaction_settings_section'] : array();

	// -------------------------------------------------------------------------
	// FIELD: Enable Reactions (Toggle list for content types)
	// -------------------------------------------------------------------------
	$field_all_reactions = isset( $reaction_titles['bb_all_reactions'] ) ? $reaction_titles['bb_all_reactions'] : array();
	bb_register_feature_field(
		'reactions',
		'display_settings',
		'enable_reactions',
		array(
			'name'              => 'bb_all_reactions',
			'label'             => isset( $field_all_reactions['title'] ) ? $field_all_reactions['title'] : __( 'Enable Reactions', 'buddyboss' ),
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
	// SECTION: REACTIONS (under reactions side panel)
	// For Reactions Mode and Button settings
	// =========================================================================
	bb_register_feature_section(
		'reactions',
		'reactions',
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
	 * Fires to allow the Pro plugin to register conversion notice.
	 * Pro plugin checks for unconverted likes and registers the notice if needed.
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	do_action( 'bb_reactions_register_conversion_notice' );

	// -------------------------------------------------------------------------
	// FIELD: Reaction Mode (Radio: Like vs Emotions)
	// -------------------------------------------------------------------------

	// Build reaction mode options with id, notice, and disabled state.
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
	$reaction_mode_options = array_values(
		array_map(
			function ( $mode ) {
				return array(
					'label'    => $mode['label'],
					'value'    => $mode['value'],
					'id'       => $mode['id'],
					'notice'   => $mode['notice'] ?? '',
					'disabled' => ! empty( $mode['disabled'] ),
				);
			},
			$reactions_modes
		)
	);

	$field_reaction_mode = isset( $reaction_titles['bb_reaction_mode'] ) ? $reaction_titles['bb_reaction_mode'] : array();
	bb_register_feature_field(
		'reactions',
		'reactions',
		'reactions_settings',
		array(
			'name'              => 'bb_reaction_mode',
			'label'             => isset( $field_reaction_mode['title'] ) ? $field_reaction_mode['title'] : __( 'Reactions Mode', 'buddyboss' ),
			'type'              => 'reaction_mode',
			'description'       => '',
			'options'           => $reaction_mode_options,
			'default'           => $cached_reaction_mode,
			'sanitize_callback' => ! empty( $field_reaction_mode['sanitize_callback'] ) && is_callable( $field_reaction_mode['sanitize_callback'] ) ? $field_reaction_mode['sanitize_callback'] : 'bb_reactions_sanitize_mode',
			'order'             => 10,
			'pro_only'          => true,
		)
	);

	// -------------------------------------------------------------------------
	// FIELD: Reaction Button (icon + text customization, Pro-only)
	// -------------------------------------------------------------------------
	$button_settings        = $cached_button_settings;
	$button_icon            = isset( $button_settings['icon'] ) ? $button_settings['icon'] : 'thumbs-up';
	$button_text            = isset( $button_settings['text'] ) ? trim( $button_settings['text'] ) : __( 'Like', 'buddyboss' );
	$field_reactions_button = isset( $reaction_titles['bb_reactions_button'] ) ? $reaction_titles['bb_reactions_button'] : array();

	$reactions_button_field_args = array(
		'name'              => 'bb_reactions_button',
		'label'             => isset( $field_reactions_button['title'] ) ? $field_reactions_button['title'] : __( 'Reaction Button', 'buddyboss' ),
		'type'              => 'reaction_button',
		'description'       => __( 'This label and icon indicate the default reaction before any reaction is selected. In \'Emotions\' mode, clicking the button applies the first reaction in the configured list.', 'buddyboss' ),
		'icon'              => $button_icon,
		'text'              => $button_text,
		'maxlength'         => 12,
		'default'           => array(
			'icon' => $button_icon,
			'text' => $button_text,
		),
		'sanitize_callback' => 'bb_reactions_sanitize_button_settings',
		'order'             => 20,
		'pro_only'          => true,
	);

	// When PRO is active, hide field when Reaction Mode is not Emotions.
	// When PRO is not active, always show for upsell visibility.
	if ( class_exists( 'BB_Reactions' ) ) {
		$reactions_button_field_args['conditional'] = array(
			'field' => 'bb_reaction_mode',
			'value' => 'emotions',
		);
	}

	bb_register_feature_field(
		'reactions',
		'reactions',
		'reactions_settings',
		$reactions_button_field_args
	);

	/**
	 * Fires after reaction settings fields are registered.
	 * Allows third-party extensions to add more fields.
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	do_action( 'bb_reactions_after_register_settings_fields' );
}

add_action( 'bb_register_features', 'bb_admin_settings_register_reactions_settings', 20 );

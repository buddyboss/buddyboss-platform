<?php
/**
 * BuddyBoss Admin Settings 2.0 - Reactions Feature Registration
 *
 * Registers Reactions feature settings with the new hierarchy:
 * Feature → Side Panels → Sections → Fields
 *
 * @package BuddyBoss\Features\Community\Reactions
 * @since BuddyBoss 3.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Register Reactions feature settings in Feature Registry.
 *
 * @since BuddyBoss 3.0.0
 */
function bb_admin_settings_2_0_register_reactions_settings() {

	// =========================================================================
	// SIDE PANEL: DISPLAY SETTINGS
	// Based on Figma: https://www.figma.com/design/XS2Hf0smlEnhWfoKyks7ku/Backend-Settings-2.0?node-id=2112-38944
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
				'class' => 'bb-icons-rl bb-icons-rl-thumbs-up',
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
	// Based on Figma design showing toggles for: Activity Posts, Activity Comments, Blogs, Private Message
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
	// Based on Figma: https://www.figma.com/design/XS2Hf0smlEnhWfoKyks7ku/Backend-Settings-2.0?node-id=2112-46484
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
	// Based on Figma: Yellow warning bar with "Start Conversion" button
	// This notice will be registered by BuddyBoss Pro if there are unconverted likes
	// -------------------------------------------------------------------------

	/**
	 * Fires to allow Pro plugin to register conversion notice.
	 * Pro plugin checks for unconverted likes and registers the notice if needed.
	 *
	 * @since BuddyBoss 3.0.0
	 */
	do_action( 'bb_reactions_register_conversion_notice' );

	// -------------------------------------------------------------------------
	// FIELD: Reaction Mode (Radio: Like vs Emotions)
	// Based on Figma showing "Like" and "Emotions" radio options
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
			'description'       => __( 'Allow members to express their thoughts by selecting from a list of up to six emotions.', 'buddyboss' ),
			'options'           => array(
				array(
					'label' => __( 'Like', 'buddyboss' ),
					'value' => 'likes',
				),
				array(
					'label' => __( 'Emotions', 'buddyboss' ),
					'value' => 'emotions',
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
	 * @since BuddyBoss 3.0.0
	 */
	do_action( 'bb_reactions_register_emotion_fields' );

	// -------------------------------------------------------------------------
	// FIELD: Reaction Button (Icon + Text)
	// Based on Figma showing card with icon and label + description
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
	 * @since BuddyBoss 3.0.0
	 */
	do_action( 'bb_reactions_after_register_settings_fields' );
}
add_action( 'bb_register_features', 'bb_admin_settings_2_0_register_reactions_settings', 20 );

// =============================================================================
// HELPER FUNCTIONS FOR FIELD RENDERING AND SANITIZATION
// =============================================================================

/**
 * Sanitize reaction content types.
 *
 * Handles the toggle_list field type for bb_all_reactions option.
 * Accepts associative array like: { activity: 1, activity_comment: 1, blogs: 0, private_message: 0 }
 *
 * @since BuddyBoss 3.0.0
 *
 * @param mixed $value The value to sanitize.
 *
 * @return array
 */
function bb_reactions_sanitize_content_types( $value ) {
	if ( ! is_array( $value ) ) {
		return array();
	}

	$allowed_keys = array( 'activity', 'activity_comment' );
	$sanitized    = array();

	foreach ( $allowed_keys as $key ) {
		$sanitized[ $key ] = isset( $value[ $key ] ) ? (bool) $value[ $key ] : false;
	}

	return $sanitized;
}

/**
 * Render custom reactions button field.
 *
 * Based on Figma showing a card with icon and label "Like"
 *
 * @since BuddyBoss 3.0.0
 *
 * @param array $field Field configuration.
 *
 * @return string
 */
function bb_reactions_render_button_field( $field ) {
	$button_settings = bb_reaction_button_options();
	$button_icon     = isset( $button_settings['icon'] ) ? $button_settings['icon'] : 'thumbs-up';
	$button_text     = isset( $button_settings['text'] ) ? trim( $button_settings['text'] ) : __( 'Like', 'buddyboss' );

	ob_start();
	?>
	<div class="bb-reaction-button-card">
		<div class="bb-reaction-button-card__preview">
			<div class="bb-reaction-button-card__icon-wrapper">
				<button type="button" class="bb-reaction-button-card__icon-btn" id="bb-reaction-button-chooser">
					<i class="bb-icon-rf bb-icon-<?php echo esc_attr( $button_icon ); ?>"></i>
				</button>
			</div>
			<div class="bb-reaction-button-card__footer">
				<input
					name="bb_reactions_button[text]"
					id="bb-reaction-button-text"
					type="text"
					maxlength="12"
					value="<?php echo esc_attr( $button_text ); ?>"
					placeholder="<?php esc_attr_e( 'Like', 'buddyboss' ); ?>"
					class="bb-reaction-button-card__text-input"
				/>
				<button type="button" class="bb-reaction-button-card__menu-btn" aria-label="<?php esc_attr_e( 'More options', 'buddyboss' ); ?>">
					<i class="bb-icon-rf bb-icon-ellipsis-h"></i>
				</button>
			</div>
		</div>
		<input
			type="hidden"
			name="bb_reactions_button[icon]"
			id="bb-reaction-button-hidden-field"
			value="<?php echo esc_attr( $button_icon ); ?>"
		/>
	</div>
	<?php
	return ob_get_clean();
}

/**
 * Sanitize reactions button settings.
 *
 * @since BuddyBoss 3.0.0
 *
 * @param mixed $value The value to sanitize.
 *
 * @return array
 */
function bb_reactions_sanitize_button_settings( $value ) {
	if ( ! is_array( $value ) ) {
		return array();
	}

	$sanitized = array();

	if ( isset( $value['icon'] ) ) {
		$sanitized['icon'] = sanitize_text_field( $value['icon'] );
	}

	if ( isset( $value['text'] ) ) {
		$text                = trim( stripslashes( sanitize_text_field( $value['text'] ) ) );
		$sanitized['text']   = strlen( $text ) > 12 ? substr( $text, 0, 12 ) : $text;
	}

	return $sanitized;
}

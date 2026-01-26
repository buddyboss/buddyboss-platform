<?php
/**
 * Reactions feature helper functions
 *
 * @package BuddyBoss\Features\Community\Reactions
 * @since BuddyBoss 3.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Load the Reactions singleton instance.
 *
 * Note: This function is defined in bp-core/bp-core-functions.php as a
 * backward compatibility wrapper. It's kept there to ensure it's always
 * available for third-party code.
 *
 * @since BuddyBoss 2.4.30
 *
 * @return BB_Reaction|null
 */
// Function exists in bp-core/bp-core-functions.php - not redeclared here to avoid conflicts.

/**
 * Get the Reactions settings sections.
 *
 * @since BuddyBoss 2.5.20
 *
 * @return array
 */
function bb_reactions_get_settings_sections() {

	$settings = array(
		'bp_reaction_settings_section' => array(
			'page'              => 'reaction',
			'title'             => esc_html__( 'Reactions', 'buddyboss' ),
			'tutorial_callback' => 'bp_admin_reaction_setting_tutorial',
		),
	);

	return (array) apply_filters( 'bb_reactions_get_settings_sections', $settings );
}

/**
 * Link to Reaction tutorial.
 *
 * @since BuddyBoss 2.5.20
 */
function bp_admin_reaction_setting_tutorial() {
	?>
	<p>
		<a class="button" target="_blank" href="
		<?php
		echo esc_url(
			bp_get_admin_url(
				add_query_arg(
					array(
						'page'    => 'bp-help',
						'article' => 127197,
					),
					'admin.php'
				)
			)
		);
		?>
		"><?php esc_html_e( 'View Tutorial', 'buddyboss' ); ?></a>
	</p>
	<?php
}

/**
 * Get reaction settings fields by section.
 *
 * @since BuddyBoss 2.5.20
 *
 * @param string $section_id Section ID.
 *
 * @return mixed False if section is invalid, array of fields otherwise.
 */
function bb_reactions_get_settings_fields_for_section( $section_id = '' ) {

	// Bail if section is empty.
	if ( empty( $section_id ) ) {
		return false;
	}

	$fields = bb_reactions_get_settings_fields();
	$retval = $fields[ $section_id ] ?? false;

	return (array) apply_filters( 'bb_reactions_get_settings_fields_for_section', $retval, $section_id );
}

/**
 * Get all of the reactions settings fields.
 *
 * @since BuddyBoss 2.5.20
 *
 * @return array
 */
function bb_reactions_get_settings_fields() {

	$fields    = array();
	$pro_class = bb_get_pro_fields_class( 'reaction' );

	$reaction_btn_class = 'bb_reaction_button_row ' . $pro_class;
	if ( function_exists( 'bb_get_reaction_mode' ) && 'emotions' !== bb_get_reaction_mode() ) {
		$reaction_btn_class .= ' bp-hide';
	}

	$fields['bp_reaction_settings_section'] = array(
		'bb_all_reactions' => array(
			'title'    => esc_html__( 'Enable Reactions', 'buddyboss' ),
			'callback' => 'bb_reactions_settings_callback_all_reactions',
			'args'     => array(),
		),

		'bb_reaction_mode' => array(
			'title'             => esc_html__( 'Reactions Mode', 'buddyboss' ) . bb_get_pro_label_notice( 'reaction' ),
			'callback'          => 'bb_reactions_settings_callback_reaction_mode',
			'sanitize_callback' => 'sanitize_text_field',
			'args'              => array(
				'class' => $pro_class
			),
		),

		'bb_reaction_emotions' => array(),

		'bb_reactions_button' => array(
			'title'    => esc_html__( 'Reactions Button', 'buddyboss' ) . bb_get_pro_label_notice( 'reaction' ),
			'callback' => 'bb_reactions_settings_callback_reactions_button',
			'args'     => array(
				'class' => $reaction_btn_class
			),
		),
	);

	return (array) apply_filters( 'bb_reactions_get_settings_fields', $fields );
}

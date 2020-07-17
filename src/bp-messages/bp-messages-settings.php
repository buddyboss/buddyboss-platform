<?php
/**
 * Messages Settings
 *
 * @package BuddyBoss\Message
 * @since BuddyBoss 1.0.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Get the Messages settings sections.
 *
 * @return array
 * @since BuddyBoss 1.0.0
 */
function bp_messages_get_settings_sections() {

	$settings = array(
		'bp_message_settings_test'      => array(
			'page'  => 'messages',
			'title' => __( 'Test', 'buddyboss' ),
		),
	);

	return (array) apply_filters( 'bp_messages_get_settings_sections', $settings );
}

/**
 * Get all of the settings fields.
 *
 * @return array
 * @since BuddyBoss 1.0.0
 */
function bp_messages_get_settings_fields() {

	$fields = array();

	$fields['bp_message_settings_test'] = array(

		'bp_messages_test'          => array(
			'title'             => __( 'Test', 'buddyboss' ),
			'callback'          => 'bp_messages_settings_callback_test',
			'sanitize_callback' => 'absint',
			'args'              => array(),
		),

	);

	$fields['bp_message_settings_test']['bp_messages_test_tutorial'] = array(
		'title'    => __( '&#160;', 'buddyboss' ),
		'callback' => 'bp_messages_test_tutorial',
	);

	return (array) apply_filters( 'bp_messages_get_settings_fields', $fields );
}

/** General Section **************************************************************/

/**
 * Get settings fields by section.
 *
 * @param string $section_id
 *
 * @return mixed False if section is invalid, array of fields otherwise.
 * @since BuddyBoss 1.0.0
 */
function bp_messages_get_settings_fields_for_section( $section_id = '' ) {

	// Bail if section is empty
	if ( empty( $section_id ) ) {
		return false;
	}

	$fields = bp_messages_get_settings_fields();
	$retval = isset( $fields[ $section_id ] ) ? $fields[ $section_id ] : false;

	return (array) apply_filters( 'bp_messages_get_settings_fields_for_section', $retval, $section_id );
}

/**
 * Output settings API option
 *
 * @param string $option
 * @param string $default
 * @param bool   $slug
 *
 * @since BuddyBoss 1.0.0
 */
function bp_messages_form_option( $option, $default = '', $slug = false ) {
	echo bp_messages_get_form_option( $option, $default, $slug );
}

/**
 * Return settings API option
 *
 * @param string $option
 * @param string $default
 * @param bool   $slug
 *
 * @return mixed
 * @since BuddyBoss 1.0.0
 *
 * @uses get_option()
 * @uses esc_attr()
 * @uses apply_filters()
 */
function bp_messages_get_form_option( $option, $default = '', $slug = false ) {

	// Get the option and sanitize it
	$value = get_option( $option, $default );

	// Slug?
	if ( true === $slug ) {
		$value = esc_attr( apply_filters( 'editable_slug', $value ) );

		// Not a slug
	} else {
		$value = esc_attr( $value );
	}

	// Fallback to default
	if ( empty( $value ) ) {
		$value = $default;
	}

	// Allow plugins to further filter the output
	return apply_filters( 'bp_messages_get_form_option', $value, $option );
}

/**
 * Setting > Messages > Test
 *
 * @since BuddyBoss 1.0.0
 */
function bp_messages_settings_callback_test() {
	?>
	<input name="test"
		   id="tesr"
		   type="checkbox"
		   value="1"
	/>
	<label for="bp_media_profile_media_support">
		<?php
		_e( 'Test', 'buddyboss' );
		?>
	</label>
	<?php
}

/**
 * Link to messages tutorial
 *
 * @since BuddyBoss 1.0.0
 */
function bp_messages_test_tutorial() {
	?>

	<p>
		<a class="button" href="
		<?php
		echo bp_get_admin_url(
			add_query_arg(
				array(
					'page'    => 'bp-help',
					'article' => 62829,
				),
				'admin.php'
			)
		);
		?>
		"><?php _e( 'View Tutorial', 'buddyboss' ); ?></a>
	</p>

	<?php
}


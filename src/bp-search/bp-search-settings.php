<?php
/**
 * Search Settings
 *
 * @package BuddyBoss
 * @subpackage Search
 * @since BuddyBoss 3.1.1
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Get the Search settings sections.
 *
 * @since BuddyBoss 3.1.1
 * @return array
 */
function bp_search_get_settings_sections() {
	return (array) apply_filters( 'bp_search_get_settings_sections', array(
		'bp_search_settings_community'  => array(
			'page' => 'search'
		),
		'bp_search_settings_post_types' => array(
			'page' => 'search'
		),
		'bp_search_settings_general'    => array(
			'page' => 'search'
		),
	) );
}

/**
 * Get all of the settings fields.
 *
 * @since bbPress (r4001)
 * @return array
 */
function bp_search_get_settings_fields() {

	$fields = [];

	/** General Section ******************************************************/
	$fields['bp_search_settings_general'] = [

		'bp_search_autocomplete' => [
			'title'             => __( 'Enable Autocomplete', 'buddyboss' ),
			'callback'          => 'bp_search_settings_callback_autocomplete',
			'sanitize_callback' => 'intval',
			'args'              => []
		],

		'bp_search_number_of_results' => [
			'title'             => __( 'Number of Results', 'buddyboss' ),
			'callback'          => 'bp_search_settings_callback_number_of_results',
			'sanitize_callback' => 'intval',
			'args'              => []
		],
	];

	$fields['bp_search_settings_community'] = [
		'bp_search_members' => [
			'title'             => __( 'Community Network', 'buddyboss' ),
			'callback'          => 'bp_search_settings_callback_members',
			'sanitize_callback' => 'intval',
			'args'              => []
		],
	];

	if ( bp_is_search_autotcomplete_enable() ) {

	}

	return (array) apply_filters( 'bp_search_get_settings_fields', $fields );
}

/** General Section **************************************************************/

/**
 * Get settings fields by section.
 *
 * @since bbPress (r4001)
 *
 * @param string $section_id
 *
 * @return mixed False if section is invalid, array of fields otherwise.
 */
function bp_search_get_settings_fields_for_section( $section_id = '' ) {

	// Bail if section is empty
	if ( empty( $section_id ) ) {
		return false;
	}

	$fields = bp_search_get_settings_fields();
	$retval = isset( $fields[ $section_id ] ) ? $fields[ $section_id ] : false;

	return (array) apply_filters( 'bp_search_get_settings_fields_for_section', $retval, $section_id );
}

/**
 * Output settings API option
 *
 * @since bbPress (r3203)
 *
 * @uses bbp_get_bbp_form_option()
 *
 * @param string $option
 * @param string $default
 * @param bool $slug
 */
function bp_search_form_option( $option, $default = '', $slug = false ) {
	echo bp_search_get_form_option( $option, $default, $slug );
}

/**
 * Return settings API option
 *
 * @since BuddyBoss 3.1.1
 *
 * @uses get_option()
 * @uses esc_attr()
 * @uses apply_filters()
 *
 * @param string $option
 * @param string $default
 * @param bool $slug
 *
 * @return mixed
 */
function bp_search_get_form_option( $option, $default = '', $slug = false ) {

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
	return apply_filters( 'bp_search_get_form_option', $value, $option );
}

/**
 * Search autocomplete setting field
 *
 * @since BuddyBoss 3.1.1
 *
 * @uses checked() To display the checked attribute
 */
function bp_search_settings_callback_autocomplete() {
	?>

	<input name="bp_search_autocomplete" id="bp_search_autocomplete" type="checkbox" value="1"
		<?php checked( bp_is_search_autotcomplete_enable( true ) ) ?> />
	<label
		for="bp_search_autocomplete"><?php esc_html_e( 'Enable autocomplete dropdown when typing into search inputs.', 'buddyboss' ); ?></label>

	<?php
}

/**
 * Checks if search autocomplete feature is enabled.
 *
 * @since BuddyBoss 3.1.1
 *
 * @param $default bool Optional.Default value true
 *
 * @uses get_option() To get the bp_search_autocomplete option
 * @return bool Is search autocomplete enabled or not
 */
function bp_is_search_autotcomplete_enable( $default = 1 ) {
	return (bool) apply_filters( 'bp_is_search_autotcomplete_enable', (bool) get_option( 'bp_search_autocomplete', $default ) );
}

/**
 * Number of results setting field
 *
 * @since BuddyBoss 3.1.1
 */
function bp_search_settings_callback_number_of_results() {
	?>

	<input name="bp_search_number_of_results" id="bp_search_number_of_results" type="number" min="1" step="1"
	       value="<?php bp_search_form_option( 'bp_search_number_of_results', '5' ); ?>" class="small-text"/>
	<label for="bp_search_number_of_results"><?php esc_html_e( 'results', 'buddyboss' ); ?></label>

	<?php
}

/**
 * Allow Members search setting field
 *
 * @since BuddyBoss 3.1.1
 *
 * @uses checked() To display the checked attribute
 */
function bp_search_settings_callback_members() {
	?>

	<input name="bp_search_members" id="bp_search_members" type="checkbox" value="1"
		<?php checked( bp_is_search_members_enable( true ) ) ?> />
	<label
		for="bp_search_members"><?php esc_html_e( 'Members', 'buddyboss' ); ?></label>

	<?php
}

/**
 * Checks if members search feature is enabled.
 *
 * @since BuddyBoss 3.1.1
 *
 * @param $default bool Optional.Default value true
 *
 * @uses get_option() To get the bp_search_members option
 * @return bool Is members search enabled or not
 */
function bp_is_search_members_enable( $default = 1 ) {
	return (bool) apply_filters( 'bp_is_search_members_enable', (bool) get_option( 'bp_search_members', $default ) );
}

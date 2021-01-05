<?php
/**
 * Performance Settings
 *
 * @package BuddyBoss\Performance
 * @since   BuddyBoss 1.6.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Get the Performance settings sections.
 *
 * @since BuddyBoss 1.6.0
 * @return array
 */
function bp_performance_get_settings_sections() {

	$settings = array(
		'bp_performance_settings' => array(
			'page'  => 'performance',
			'title' => __( 'API Caching', 'buddyboss' ),
		),
	);

	return (array) apply_filters( 'bp_performance_get_settings_sections', $settings );
}

/**
 * Get all of the settings fields.
 *
 * @since BuddyBoss 1.6.0
 * @return array
 */
function bp_performance_get_settings_fields() {

	$fields = array();

	$fields['bp_performance_settings'] = array();

	if ( bp_is_active( 'activity' ) ) {
		$fields['bp_performance_settings']['bp_is_activity_rest_cache_enabled'] = array(
			'title'             => __( 'Activity Feeds', 'buddyboss' ),
			'callback'          => 'bp_performance_settings_callback_activity_rest_cache',
			'sanitize_callback' => 'intval',
			'args'              => array(),
		);
	}

	return (array) apply_filters( 'bp_performance_get_settings_fields', $fields );
}

/**
 * Get settings fields by section.
 *
 * @since BuddyBoss 1.6.0
 *
 * @param string $section_id Section id.
 *
 * @return mixed False if section is invalid, array of fields otherwise.
 */
function bp_performance_get_settings_fields_for_section( $section_id = '' ) {

	// Bail if section is empty.
	if ( empty( $section_id ) ) {
		return false;
	}

	$fields = bp_performance_get_settings_fields();
	$retval = isset( $fields[ $section_id ] ) ? $fields[ $section_id ] : false;

	return (array) apply_filters( 'bp_performance_get_settings_fields_for_section', $retval, $section_id );
}

/**
 * Setting > Performance > Activity Rest Cache.
 *
 * @since BuddyBoss 1.6.0
 */
function bp_performance_settings_callback_activity_rest_cache() {
	?>
	<input name="bp_activity_rest_cache_enabled" id="bp_activity_rest_cache_enabled" type="checkbox" value="1" <?php checked( bp_is_activity_rest_cache_enabled() ); ?> />
	<label for="bp_activity_rest_cache_enabled">
		<?php
		esc_html_e( 'Cache Activity Feeds', 'buddyboss' );
		?>
	</label>
	<p class="description"><?php esc_html_e( 'Plugins that interact with Activity Feeds may not be compatible with API caching.', 'buddyboss' ); ?></p>
	<?php
}

/**
 * Checks if Activity REST cache enabled or not.
 *
 * @param integer $default Default false.
 *
 * @return bool Is Activity REST cache enabled or not
 *
 * @since BuddyBoss 1.6.0
 */
function bp_is_activity_rest_cache_enabled( $default = 0 ) {
	return (bool) apply_filters( 'bp_is_activity_rest_cache_enabled', (bool) get_option( 'bp_activity_rest_cache_enabled', $default ) );
}

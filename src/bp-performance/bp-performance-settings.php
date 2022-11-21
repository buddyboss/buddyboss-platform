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
 * @since BuddyBoss 1.5.7
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
 * @since BuddyBoss 1.5.7
 * @return array
 */
function bp_performance_get_settings_fields() {

	$fields = array();


	return (array) apply_filters( 'bp_performance_get_settings_fields', $fields );
}

/**
 * Get settings fields by section.
 *
 * @since BuddyBoss 1.5.7
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

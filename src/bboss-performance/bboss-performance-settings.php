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
function bboss_performance_get_settings_sections() {

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
function bboss_performance_get_settings_fields() {

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
function bboss_performance_get_settings_fields_for_section( $section_id = '' ) {

	// Bail if section is empty.
	if ( empty( $section_id ) ) {
		return false;
	}

	$fields = bboss_performance_get_settings_fields();
	$retval = isset( $fields[ $section_id ] ) ? $fields[ $section_id ] : false;

	return (array) apply_filters( 'bp_performance_get_settings_fields_for_section', $retval, $section_id );
}

// @bbwt-com-start compat-layer
// bbwt-rename:compat-aliases — the pre-rename names of this file's symbols (decision 0013): declared
// here, guarded on the renamed declaration, so each old name exists exactly when the new one does.
if ( function_exists( 'bboss_performance_get_settings_fields' ) && ! function_exists( 'bp_performance_get_settings_fields' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_performance_get_settings_fields()}.
	 *
	 * @deprecated Use bboss_performance_get_settings_fields() instead.
	 */
	function bp_performance_get_settings_fields() {
		return bboss_performance_get_settings_fields();
	}
}
if ( function_exists( 'bboss_performance_get_settings_fields_for_section' ) && ! function_exists( 'bp_performance_get_settings_fields_for_section' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_performance_get_settings_fields_for_section()}.
	 *
	 * @deprecated Use bboss_performance_get_settings_fields_for_section() instead.
	 */
	function bp_performance_get_settings_fields_for_section( $section_id = '' ) {
		return bboss_performance_get_settings_fields_for_section( $section_id );
	}
}
if ( function_exists( 'bboss_performance_get_settings_sections' ) && ! function_exists( 'bp_performance_get_settings_sections' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_performance_get_settings_sections()}.
	 *
	 * @deprecated Use bboss_performance_get_settings_sections() instead.
	 */
	function bp_performance_get_settings_sections() {
		return bboss_performance_get_settings_sections();
	}
}
// @bbwt-com-end compat-layer

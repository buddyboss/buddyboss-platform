<?php
/**
 * Moderation Settings
 *
 * @package BuddyBoss\Moderation
 * @since BuddyBoss 1.5.4
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Get the Moderation settings sections.
 *
 * @return array
 * @since BuddyBoss 1.5.4
 */
function bp_moderation_get_settings_sections() {

	$settings = array(
		'bp_moderation_settings_reporting'    => array(
			'page'  => 'moderation',
			'title' => __( 'Reporting', 'buddyboss' ),
		),
	);

	return (array) apply_filters( 'bp_moderation_get_settings_sections', $settings );
}

/**
 * Get all of the settings fields.
 *
 * @return array
 * @since BuddyBoss 1.5.4
 */
function bp_moderation_get_settings_fields() {

	$fields = array();

	$fields['bp_moderation_settings_reporting'] = array();

	return (array) apply_filters( 'bp_moderation_get_settings_fields', $fields );
}

/**
 * Get settings fields by section.
 *
 * @param string $section_id
 *
 * @return mixed False if section is invalid, array of fields otherwise.
 * @since BuddyBoss 1.5.4
 */
function bp_moderation_get_settings_fields_for_section( $section_id = '' ) {

	// Bail if section is empty
	if ( empty( $section_id ) ) {
		return false;
	}

	$fields = bp_moderation_get_settings_fields();
	$retval = isset( $fields[ $section_id ] ) ? $fields[ $section_id ] : false;

	return (array) apply_filters( 'bp_moderation_get_settings_fields_for_section', $retval, $section_id );
}

<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Loads a single instance of LearnDash_BuddyPress_Groups_Reports.
 *
 * This follows the PHP singleton design pattern.
 *
 * Use this function like you would a global variable, except without needing
 * to declare the global.
 *
 * @example <?php $learndash_bbudypress_groups_reports = learndash_bbudypress_groups_reports(); ?>
 *
 * @see     LearnDash_BuddyPress_Groups_Reports::get_instance()
 *
 * @return object LearnDash_BuddyPress_Groups_Reports Returns an instance of the  class
 */
function learndash_bbudypress_groups_reports() {
	return LearnDash_BuddyPress_Groups_Reports::get_instance();
}

/**
 * default setting value
 *
 * @return array
 */
function ld_bp_groups_reports_default_value() {
	return $default_value = array(
		'enable_group_reports' => false,
		'report_access'        => array(
			'admin',
			'moderator'
		),
	);
}

/**
 * get the default value from setting
 *
 * @param null $key
 * @param array $default
 * @param bool $get_default_value
 *
 * @return array|bool
 */
function ld_bp_groups_reports_get_settings( $key = null, $get_default_value = true, $default = array() ) {

	$default_value = ld_bp_groups_reports_default_value();

	$options = get_option( 'learndash_settings_buddypress_groups_reports', $default_value );

	if ( ! $key ) {
		return $options;
	}

	return isset( $options[ $key ] ) ? $options[ $key ] : ( $get_default_value && isset( $default_value[ $key ] ) ? $default_value[ $key ] : $default );
}

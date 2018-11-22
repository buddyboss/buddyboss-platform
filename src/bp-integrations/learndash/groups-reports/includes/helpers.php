<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
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
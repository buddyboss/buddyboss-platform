<?php
/**
 * BuddyBoss Admin Settings - Registration Callbacks.
 *
 * Sanitize callback functions for Login & Registration feature settings.
 *
 * @package BuddyBoss\Core\Administration
 * @since BuddyBoss [BBVERSION]
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Sanitize domain restrictions setting.
 *
 * Validates submitted domain restrictions array: removes placeholder index,
 * re-indexes by priority, sanitizes domain/tld/condition per row.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param mixed $value The submitted value.
 *
 * @return array Sanitized domain restrictions.
 */
function bb_registration_sanitize_domain_restrictions( $value ) {
	if ( ! is_array( $value ) ) {
		return array();
	}

	// Remove placeholder clone row (same as legacy BP_Admin_Tab::settings_save).
	unset( $value['placeholder_priority_index'] );

	$allowed_conditions = array( '', 'always_allow', 'never_allow', 'only_allow' );
	$sanitized          = array();

	foreach ( $value as $row ) {
		if ( ! is_array( $row ) ) {
			continue;
		}

		$domain    = isset( $row['domain'] ) ? sanitize_text_field( $row['domain'] ) : '';
		$tld       = isset( $row['tld'] ) ? sanitize_text_field( $row['tld'] ) : '';
		$condition = isset( $row['condition'] ) ? sanitize_text_field( $row['condition'] ) : '';

		if ( ! in_array( $condition, $allowed_conditions, true ) ) {
			$condition = '';
		}

		// Skip completely empty rows.
		if ( '' === $domain && '' === $tld && '' === $condition ) {
			continue;
		}

		$sanitized[] = array(
			'domain'    => $domain,
			'tld'       => $tld,
			'condition' => $condition,
		);
	}

	// Re-index by priority (array_values ensures 0-based sequential keys).
	return array_values( $sanitized );
}

/**
 * Sanitize email restrictions setting.
 *
 * Validates submitted email restrictions array: removes placeholder index,
 * sanitizes address/condition per row.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param mixed $value The submitted value.
 *
 * @return array Sanitized email restrictions.
 */
function bb_registration_sanitize_email_restrictions( $value ) {
	if ( ! is_array( $value ) ) {
		return array();
	}

	// Remove placeholder clone row.
	unset( $value['placeholder_priority_index'] );

	$allowed_conditions = array( '', 'always_allow', 'never_allow' );
	$sanitized          = array();

	foreach ( $value as $row ) {
		if ( ! is_array( $row ) ) {
			continue;
		}

		$address   = isset( $row['address'] ) ? sanitize_text_field( $row['address'] ) : '';
		$condition = isset( $row['condition'] ) ? sanitize_text_field( $row['condition'] ) : '';

		if ( ! in_array( $condition, $allowed_conditions, true ) ) {
			$condition = '';
		}

		// Skip completely empty rows.
		if ( '' === $address && '' === $condition ) {
			continue;
		}

		$sanitized[] = array(
			'address'   => $address,
			'condition' => $condition,
		);
	}

	return array_values( $sanitized );
}

/**
 * Sanitize registration form type (select: 0 or 1).
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param mixed $value The submitted value.
 *
 * @return int 0 for BuddyBoss Registration, 1 for Custom URL.
 */
function bb_registration_sanitize_form_type( $value ) {
	return in_array( (int) $value, array( 0, 1 ), true ) ? (int) $value : 0;
}

/**
 * Sanitize login/logout redirection select value.
 *
 * Accepts empty string (Default), '0' (Custom URL), or a page ID string.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param mixed $value The submitted value.
 *
 * @return string Sanitized redirection value.
 */
function bb_registration_sanitize_redirection( $value ) {
	$value = sanitize_text_field( $value );

	// Allow empty (Default) or '0' (Custom URL) or numeric page ID.
	if ( '' === $value || '0' === $value || is_numeric( $value ) ) {
		return $value;
	}

	return '';
}

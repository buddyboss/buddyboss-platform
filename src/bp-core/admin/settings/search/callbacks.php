<?php
/**
 * BuddyBoss Admin Settings - Search Callbacks.
 *
 * Sanitize and validate callbacks for Search settings fields.
 *
 * @package BuddyBoss\Core\Administration
 * @since   BuddyBoss [BBVERSION]
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Sanitize the number of autocomplete results.
 *
 * Ensures value is a positive integer with a minimum of 1.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param mixed $value The raw input value.
 *
 * @return int Sanitized positive integer (minimum 1).
 */
function bb_search_sanitize_number_of_results( $value ) {
	$value = absint( $value );

	if ( $value < 1 ) {
		return 5;
	}

	return $value;
}

<?php
/**
 * BuddyBoss Admin Settings - Search Callbacks.
 *
 * Sanitize and validate callbacks for Search settings fields.
 *
 * @package BuddyBoss\Core\Administration
 * @since   BuddyBoss 3.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Sanitize the number of autocomplete results.
 *
 * Ensures value is a positive integer with a minimum of 1.
 *
 * @since BuddyBoss 3.0.0
 *
 * @param mixed $value The raw input value.
 *
 * @return int Sanitized positive integer (minimum 1).
 */
function bboss_search_sanitize_number_of_results( $value ) {
	$value = absint( $value );

	if ( $value < 1 ) {
		return 5;
	}

	return $value;
}
// @bbwt-com-start compat-layer
// bbwt-rename:compat-aliases — the pre-rename name of the function above (decision 0013): declared right after it, guarded on it, so the old name exists exactly when the new one does.
if ( function_exists( 'bboss_search_sanitize_number_of_results' ) && ! function_exists( 'bb_search_sanitize_number_of_results' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_search_sanitize_number_of_results()}.
	 *
	 * @deprecated Use bboss_search_sanitize_number_of_results() instead.
	 */
	function bb_search_sanitize_number_of_results( $value ) {
		return bboss_search_sanitize_number_of_results( $value );
	}
}
// @bbwt-com-end compat-layer


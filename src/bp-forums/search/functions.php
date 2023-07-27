<?php

/**
 * Forums Search Functions
 *
 * @package BuddyBoss\Functions
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/** Query *********************************************************************/

/**
 * Run the search query
 *
 * @since bbPress (r4579)
 *
 * @param mixed $new_args New arguments
 * @uses bbp_get_search_query_args() To get the search query args
 * @uses bbp_parse_args() To parse the args
 * @uses bbp_has_search_results() To make the search query
 * @return bool False if no results, otherwise if search results are there
 */
function bbp_search_query( $new_args = array() ) {

	// Existing arguments
	$query_args = bbp_get_search_query_args();

	// Merge arguments
	if ( ! empty( $new_args ) ) {
		$new_args   = bbp_parse_args( $new_args, array(), 'search_query' );
		$query_args = array_merge( $query_args, $new_args );
	}

	return bbp_has_search_results( $query_args );
}

/**
 * Return the search's query args
 *
 * @since bbPress (r4579)
 *
 * @uses bbp_get_search_terms() To get the search terms
 * @return array Query arguments
 */
function bbp_get_search_query_args() {

	// Get search terms
	$search_terms = bbp_get_search_terms();
	$retval       = ! empty( $search_terms ) ? array( 's' => $search_terms ) : array();

	return apply_filters( 'bbp_get_search_query_args', $retval );
}

/**
 * Redirect to search results page if needed
 *
 * @since bbPress (r4928)
 * @return If a redirect is not needed
 */
function bbp_search_results_redirect() {

	// Bail if not a search request action
	if ( empty( $_GET['action'] ) || ( 'bbp-search-request' !== $_GET['action'] ) ) {
		return;
	}

	// Bail if not using pretty permalinks
	if ( ! bbp_use_pretty_urls() ) {
		return;
	}

	// Get the redirect URL
	$redirect_to = bbp_get_search_results_url();
	if ( empty( $redirect_to ) ) {
		return;
	}

	// Redirect and bail
	bbp_redirect( $redirect_to );
}


/**
 * Return an array of search types
 *
 * @since 2.6.0 bbPress (r6903)
 *
 * @return array
 */
function bbp_get_search_type_ids() {
	return apply_filters( 'bbp_get_search_types', array( 's', 'fs', 'ts', 'rs' ) );
}

/**
 * Sanitize a query argument used to pass some search terms.
 *
 * Accepts a single parameter to be used for forums, topics, or replies.
 *
 * @since 2.6.0 bbPress (r6903)
 *
 * @param string $query_arg s|fs|ts|rs
 *
 * @return mixed
 */
function bbp_sanitize_search_request( $query_arg = 's' ) {

	// Define allowed keys
	$allowed = bbp_get_search_type_ids();

	// Bail if not an allowed query string key
	if ( ! in_array( $query_arg, $allowed, true ) ) {
		return false;
	}

	// Get search terms if requested
	$terms = ! empty( $_REQUEST[ $query_arg ] )
		? $_REQUEST[ $query_arg ]
		: false;

	// Bail if query argument does not exist
	if ( empty( $terms ) ) {
		return false;
	}

	// Maybe implode if an array
	if ( is_array( $terms ) ) {
		$terms = implode( ' ', $terms );
	}

	// Sanitize
	$retval = sanitize_title( trim( $terms ) );

	// Filter & return
	return apply_filters( 'bbp_sanitize_search_request', $retval, $query_arg );
}

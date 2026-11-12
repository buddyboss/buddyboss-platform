<?php

/**
 * Forums Search Template Tags
 *
 * @package BuddyBoss\Template\Tags
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/** Search Loop Functions *****************************************************/

/**
 * The main search loop. WordPress does the heavy lifting.
 *
 * @since bbPress (r4579)
 *
 * @param mixed $args All the arguments supported by {@link WP_Query}
 * @uses bboss_bbp_get_view_all() Are we showing all results?
 * @uses bboss_bbp_get_public_status_id() To get the public status id
 * @uses bboss_bbp_get_closed_status_id() To get the closed status id
 * @uses bboss_bbp_get_spam_status_id() To get the spam status id
 * @uses bboss_bbp_get_trash_status_id() To get the trash status id
 * @uses bboss_bbp_get_forum_post_type() To get the forum post type
 * @uses bboss_bbp_get_topic_post_type() To get the topic post type
 * @uses bboss_bbp_get_reply_post_type() To get the reply post type
 * @uses bboss_bbp_get_replies_per_page() To get the replies per page option
 * @uses bboss_bbp_get_paged() To get the current page value
 * @uses bboss_bbp_get_search_terms() To get the search terms
 * @uses WP_Query To make query and get the search results
 * @uses bboss_bbp_use_pretty_urls() To check if the site is using pretty URLs
 * @uses bboss_bbp_get_search_url() To get the forum search url
 * @uses paginate_links() To paginate search results
 * @uses apply_filters() Calls 'bbp_has_search_results' with
 *                        bbPress::search_query::have_posts()
 *                        and bbPress::reply_query
 * @return object Multidimensional array of search information
 */
function bboss_bbp_has_search_results( $args = '' ) {

	/** Defaults */
	$default_search_terms = bboss_bbp_get_search_terms();
	$default_post_types   = bboss_bbp_get_post_types();

	// Default query args
	$default = array(
		'post_type'                => $default_post_types,        // Forums, topics, and replies
		'posts_per_page'           => bboss_bbp_get_replies_per_page(), // This many
		'paged'                    => bboss_bbp_get_paged(),            // On this page
		'orderby'                  => 'date ID',                     // Sorted by date
		'order'                    => 'DESC',                     // Most recent first
		'ignore_sticky_posts'      => true,                       // Stickies not supported,
		'update_post_family_cache' => true,                       // Conditionally prime the cache for related posts

		// Conditionally prime the cache for last active posts.
		'update_post_family_cache' => true
	);

	// Only set 's' if search terms exist
	// https://bbpress.trac.wordpress.org/ticket/2607
	if ( false !== $default_search_terms ) {
		$default['s'] = $default_search_terms;
	}

	// What are the default allowed statuses (based on user caps)
	if ( bboss_bbp_get_view_all() ) {

		// Default view=all statuses
		$post_statuses = array_keys( bboss_bbp_get_topic_statuses() );

		// Add support for private status
		if ( current_user_can( 'read_private_topics' ) ) {
			$post_statuses[] = bboss_bbp_get_private_status_id();
		}

		// Join post statuses together
		$default['post_status'] = $post_statuses;

		// Lean on the 'perm' query var value of 'readable' to provide statuses
	} else {
		$default['perm'] = 'readable';
	}

	/** Setup */

	// Parse arguments against default values
	$r = bboss_bbp_parse_args( $args, $default, 'has_search_results' );

	// Get Forums
	$bbp = bboss_forums();

	// Call the query
	if ( ! empty( $r['s'] ) ) {
		$bbp->search_query = new WP_Query( $r );
	}

	// Maybe prime last active posts.
	if ( ! empty( $r['update_post_family_cache'] ) ) {
		bboss_bbp_update_post_family_caches( $bbp->search_query->posts );
	}

	// Add pagination values to query object
	$bbp->search_query->posts_per_page = (int) $r['posts_per_page'];
	$bbp->search_query->paged          = (int) $r['paged'];

	// Never home, regardless of what parse_query says
	$bbp->search_query->is_home = false;

	// Only add pagination is query returned results
	if ( ! empty( $bbp->search_query->found_posts ) && ! empty( $bbp->search_query->posts_per_page ) ) {

		// Array of arguments to add after pagination links.
		$add_args = array();

		// Add args.
		if ( bboss_bbp_get_view_all() ) {
			$add_args['view'] = 'all';
		}

		// Add pagination to query object.
		$bbp->search_query->pagination_links = paginate_links(
			apply_filters(
				'bbp_search_results_pagination',
				array(
					'base'      => bboss_bbp_get_search_pagination_base(),
					'format'    => '',
					'total'     => ceil( (int) $bbp->search_query->found_posts / (int) $r['posts_per_page'] ),
					'current'   => (int) $bbp->search_query->paged,
					'prev_text' => is_rtl() ? '&rarr;' : '&larr;',
					'next_text' => is_rtl() ? '&larr;' : '&rarr;',
					'mid_size'  => 1,
					'add_args'  => $add_args,
				)
			)
		);

		// Remove first page from pagination
		if ( bboss_bbp_get_paged_slug() ) {
			$bbp->search_query->pagination_links = str_replace( bboss_bbp_get_paged_slug() . '/1/', '', $bbp->search_query->pagination_links );
		} else {
			$bbp->search_query->pagination_links = str_replace( '&#038;paged=1', '', $bbp->search_query->pagination_links );
		}
	}

	// Return object
	return apply_filters( 'bbp_has_search_results', $bbp->search_query->have_posts(), $bbp->search_query );
}

/**
 * Whether there are more search results available in the loop
 *
 * @since bbPress (r4579)
 *
 * @uses WP_Query bbPress::search_query::have_posts() To check if there are more
 *                                                     search results available
 * @return object Search information
 */
function bboss_bbp_search_results() {

	// Put into variable to check against next
	$have_posts = bboss_forums()->search_query->have_posts();

	// Reset the post data when finished
	if ( empty( $have_posts ) ) {
		wp_reset_postdata();
	}

	return $have_posts;
}

/**
 * Loads up the current search result in the loop
 *
 * @since bbPress (r4579)
 *
 * @uses WP_Query bbPress::search_query::the_post() To get the current search result
 * @return object Search information
 */
function bboss_bbp_the_search_result() {
	$search_result = bboss_forums()->search_query->the_post();

	// Reset each current (forum|topic|reply) id
	bboss_forums()->current_forum_id = bboss_bbp_get_forum_id();
	bboss_forums()->current_topic_id = bboss_bbp_get_topic_id();
	bboss_forums()->current_reply_id = bboss_bbp_get_reply_id();

	return $search_result;
}

/**
 * Output the search page title
 *
 * @since bbPress (r4579)
 *
 * @uses bboss_bbp_get_search_title()
 */
function bboss_bbp_search_title() {
	echo esc_html( bboss_bbp_get_search_title() );
}

/**
 * Get the search page title
 *
 * @since bbPress (r4579)
 *
 * @uses bboss_bbp_get_search_terms()
 */
function bboss_bbp_get_search_title() {

	// Get search terms
	$search_terms = bboss_bbp_get_search_terms();

	// No search terms specified
	if ( empty( $search_terms ) ) {
		$title = esc_html__( 'Search', 'buddyboss-platform' );

		// Include search terms in title
	} else {
		/* translators: %s: search terms. */
		$title = sprintf( esc_html__( "Search Results for '%s'", 'buddyboss-platform' ), esc_attr( $search_terms ) );
	}

	return apply_filters( 'bbp_get_search_title', $title, $search_terms );
}

/**
 * Output the search url
 *
 * @since bbPress (r4579)
 *
 * @uses bboss_bbp_get_search_url() To get the search url
 */
function bboss_bbp_search_url() {
	echo esc_url( bboss_bbp_get_search_url() );
}

/**
 * Return the search url
 *
 * @since bbPress (r4579)
 *
 * @uses user_trailingslashit() To fix slashes
 * @uses trailingslashit() To fix slashes
 * @uses bboss_bbp_get_forums_url() To get the root forums url
 * @uses bboss_bbp_get_search_slug() To get the search slug
 * @uses add_query_arg() To help make unpretty permalinks
 * @return string Search url
 */
function bboss_bbp_get_search_url( $default = true ) {

	// Pretty permalinks
	if ( bboss_bbp_use_pretty_urls() ) {
		$url = bboss_bbp_get_root_url() . bboss_bbp_get_search_slug();
		$url = home_url( user_trailingslashit( $url ) );

		// Unpretty permalinks
	} else {
		$url = add_query_arg( array( bboss_bbp_get_search_rewrite_id() => '' ), home_url( '/' ) );
	}

	if ( true === $default ) {
		return apply_filters( 'bbp_get_search_url', add_query_arg( 'bp_search', 1, $url ) );
	} else {
		return apply_filters( 'bbp_get_search_url', $url );
	}

}

/**
 * Output the search results url
 *
 * @since bbPress (r4928)
 *
 * @uses bboss_bbp_get_search_url() To get the search url
 */
function bboss_bbp_search_results_url() {
	echo esc_url( bboss_bbp_get_search_results_url() );
}

/**
 * Return the search url
 *
 * @since bbPress (r4928)
 *
 * @uses user_trailingslashit() To fix slashes
 * @uses trailingslashit() To fix slashes
 * @uses bboss_bbp_get_forums_url() To get the root forums url
 * @uses bboss_bbp_get_search_slug() To get the search slug
 * @uses add_query_arg() To help make unpretty permalinks
 * @return string Search url
 */
function bboss_bbp_get_search_results_url() {

	// Get the search terms
	$search_terms = bboss_bbp_get_search_terms();

	// Pretty permalinks
	if ( bboss_bbp_use_pretty_urls() ) {

		// Root search URL
		$url = bboss_bbp_get_root_url() . bboss_bbp_get_search_slug();

		// Append search terms
		if ( ! empty( $search_terms ) ) {
			$url = trailingslashit( $url ) . urlencode( $search_terms );
		}

		// Run through home_url()
		$url = home_url( user_trailingslashit( $url ) );

		// Unpretty permalinks
	} else {
		$url = add_query_arg( array( bboss_bbp_get_search_rewrite_id() => urlencode( $search_terms ) ), home_url( '/' ) );
	}

	return apply_filters( 'bbp_get_search_results_url', $url );
}

/**
 * Output the search terms
 *
 * @since bbPress (r4579)
 *
 * @param string $search_terms Optional. Search terms
 * @uses bboss_bbp_get_search_terms() To get the search terms
 */
function bboss_bbp_search_terms( $search_terms = '' ) {
	echo esc_attr( bboss_bbp_get_search_terms( $search_terms ) );
}

/**
 * Get the search terms
 *
 * @since bbPress (r4579)
 *
 * If search terms are supplied, those are used. Otherwise check the
 * search rewrite id query var.
 *
 * @param string $passed_terms Optional. Search terms
 * @uses sanitize_title() To sanitize the search terms
 * @uses get_query_var() To get the search terms from query variable
 * @return bool|string Search terms on success, false on failure
 */
function bboss_bbp_get_search_terms( $passed_terms = '' ) {

	// Sanitize terms if they were passed in
	if ( ! empty( $passed_terms ) ) {
		$search_terms = sanitize_title( $passed_terms );

		// Use query variable if not
	} else {

		// Global
		$search_terms = get_query_var( bboss_bbp_get_search_rewrite_id(), null );

		// Searching globally
		if ( ! is_null( $search_terms ) ) {
			$search_terms = wp_unslash( $search_terms );

			// Other searches
		} else {

			// Get known search type IDs
			$types = bboss_bbp_get_search_type_ids();

			// Filterable, so make sure types exist
			if ( ! empty( $types ) ) {

				// Loop through types
				foreach ( $types as $type ) {

					// Look for search terms
					$terms = bboss_bbp_sanitize_search_request( $type );

					// Skip if no terms
					if ( empty( $terms ) ) {
						continue;
					}

					// Set terms if not empty
					$search_terms = $terms;
				}
			}
		}
	}

	// Trim whitespace & decode if non-empty string, or set to false
	$search_terms = ! empty( $search_terms ) && is_string( $search_terms )
		? urldecode( trim( $search_terms ) )
		: false;

	// Filter & return
	return apply_filters( 'bbp_get_search_terms', $search_terms, $passed_terms );
}

/**
 * Return the base URL used inside of pagination links.
 *
 * @since 2.6.0 bbPress (r6679)
 * @since BuddyBoss 2.4.00
 *
 * @return string
 */
function bboss_bbp_get_search_pagination_base() {

	// If pretty permalinks are enabled, make our pagination pretty.
	if ( bboss_bbp_use_pretty_urls() ) {

		// Any single post (for shortcodes).
		if ( is_singular() ) {
			$base = get_permalink();

			// Default search location.
		} else {
			$base = bboss_bbp_get_search_results_url();
		}

		// Add pagination base.
		$base = trailingslashit( $base ) . user_trailingslashit( bboss_bbp_get_paged_slug() . '/%#%/' );

		// Unpretty permalinks.
	} else {
		$base = add_query_arg( 'paged', '%#%' );
	}

	// Filter & return.
	return apply_filters( 'bbp_get_search_pagination_base', $base );
}

/**
 * Output the search result pagination count
 *
 * @since bbPress (r4579)
 *
 * @uses bboss_bbp_get_search_pagination_count() To get the search result pagination count
 */
function bboss_bbp_search_pagination_count() {
	echo esc_html( bboss_bbp_get_search_pagination_count() );
}

/**
 * Return the search results pagination count
 *
 * @since bbPress (r4579)
 *
 * @uses bboss_bbp_number_format() To format the number value
 * @uses apply_filters() Calls 'bbp_get_search_pagination_count' with the
 *                        pagination count
 * @return string Search pagination count
 */
function bboss_bbp_get_search_pagination_count() {
	$bbp = bboss_forums();

	// Define local variable(s)
	$retstr = '';

	// Set pagination values
	$start_num = intval( ( $bbp->search_query->paged - 1 ) * $bbp->search_query->posts_per_page ) + 1;
	$from_num  = bboss_bbp_number_format( $start_num );
	$to_num    = bboss_bbp_number_format( ( $start_num + ( $bbp->search_query->posts_per_page - 1 ) > $bbp->search_query->found_posts ) ? $bbp->search_query->found_posts : $start_num + ( $bbp->search_query->posts_per_page - 1 ) );
	$total_int = (int) $bbp->search_query->found_posts;
	$total     = bboss_bbp_number_format( $total_int );

	// Single page of results
	if ( empty( $to_num ) ) {
		/* translators: 1: total number of results. */
		$retstr = sprintf( _n( 'Viewing %1$s result', 'Viewing %1$s results', $total_int, 'buddyboss-platform' ), $total );

		// Several pages of results
	} else {
		/* translators: 2: number shown on this page, 3: last item number on this page, 4: total number of results. */
		$retstr = sprintf( _n( 'Viewing %2$s of %4$s results', 'Viewing %2$s - %3$s of %4$s results', $bbp->search_query->post_count, 'buddyboss-platform' ), $bbp->search_query->post_count, $from_num, $to_num, $total ); // phpcs:ignore WordPress.WP.I18n.MissingSingularPlaceholder -- Singular keeps its literal form to preserve existing translations (msgid unchanged); upstream BuddyPress pagination string.

	}

	// Filter and return
	return apply_filters( 'bbp_get_search_pagination_count', esc_html( $retstr ) );
}

/**
 * Output search pagination links
 *
 * @since bbPress (r4579)
 *
 * @uses bboss_bbp_get_search_pagination_links() To get the search pagination links
 */
function bboss_bbp_search_pagination_links() {
	echo wp_kses_post( bboss_bbp_get_search_pagination_links() );
}

/**
 * Return search pagination links
 *
 * @since bbPress (r4579)
 *
 * @uses apply_filters() Calls 'bbp_get_search_pagination_links' with the
 *                        pagination links
 * @return string Search pagination links
 */
function bboss_bbp_get_search_pagination_links() {
	$bbp = bboss_forums();

	if ( ! isset( $bbp->search_query->pagination_links ) || empty( $bbp->search_query->pagination_links ) ) {
		return false;
	}

	return apply_filters( 'bbp_get_search_pagination_links', $bbp->search_query->pagination_links );
}

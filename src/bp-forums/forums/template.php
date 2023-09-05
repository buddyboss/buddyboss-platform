<?php

/**
 * Forums Forum Template Tags
 *
 * @package BuddyBoss\Template\Tags
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/** Post Type *****************************************************************/

/**
 * Output the unique id of the custom post type for forums
 *
 * @since bbPress (r2857)
 * @uses  bbp_get_forum_post_type() To get the forum post type
 */
function bbp_forum_post_type() {
	echo bbp_get_forum_post_type();
}

/**
 * Return the unique id of the custom post type for forums
 *
 * @since                 bbPress (r2857)
 *
 * @return string The unique forum post type id
 * @uses                  apply_filters() Calls 'bbp_get_forum_post_type' with the forum
 *                        post type id
 */
function bbp_get_forum_post_type() {
	return apply_filters( 'bbp_get_forum_post_type', bbpress()->forum_post_type );
}


/**
 * Return array of labels used by the forum post type
 *
 * @since bbPress (r5129)
 *
 * @return array
 */
function bbp_get_forum_post_type_labels() {
	return apply_filters(
		'bbp_get_forum_post_type_labels',
		array(
			'name'               => __( 'Forums', 'buddyboss' ),
			'menu_name'          => __( 'All Forums', 'buddyboss' ),
			'singular_name'      => __( 'Forum', 'buddyboss' ),
			'all_items'          => __( 'All Forums', 'buddyboss' ),
			'add_new'            => __( 'New Forum', 'buddyboss' ),
			'add_new_item'       => __( 'Create New Forum', 'buddyboss' ),
			'edit'               => __( 'Edit', 'buddyboss' ),
			'edit_item'          => __( 'Edit Forum', 'buddyboss' ),
			'new_item'           => __( 'New Forum', 'buddyboss' ),
			'view'               => __( 'View Forum', 'buddyboss' ),
			'view_item'          => __( 'View Forum', 'buddyboss' ),
			'search_items'       => __( 'Search Forums', 'buddyboss' ),
			'not_found'          => __( 'No forums found', 'buddyboss' ),
			'not_found_in_trash' => __( 'No forums found in trash', 'buddyboss' ),
			'parent_item_colon'  => __( 'Parent Forum:', 'buddyboss' ),
		)
	);
}

/**
 * Return array of forum post type rewrite settings
 *
 * @since bbPress (r5129)
 *
 * @return array
 */
function bbp_get_forum_post_type_rewrite() {
	return apply_filters(
		'bbp_get_forum_post_type_rewrite',
		array(
			'slug'       => bbp_get_forum_slug(),
			'with_front' => false,
		)
	);
}

/**
 * Return array of features the forum post type supports
 *
 * @since bbPress (r5129)
 *
 * @return array
 */
function bbp_get_forum_post_type_supports() {
	return apply_filters(
		'bbp_get_forum_post_type_supports',
		array(
			'title',
			'editor',
			'revisions',
		)
	);
}

/** Forum Loop ****************************************************************/

/**
 * The main forum loop.
 *
 * WordPress makes this easy for us.
 *
 * @since                    bbPress (r2464)
 *
 * @param mixed $args All the arguments supported by {@link WP_Query}
 *
 * @return object Multidimensional array of forum information
 * @uses                     bbp_get_forum_post_type() To get the forum post type id
 * @uses                     bbp_get_forum_id() To get the forum id
 * @uses                     get_option() To get the forums per page option
 * @uses                     current_user_can() To check if the current user is capable of editing
 *                           others' forums
 * @uses                     apply_filters() Calls 'bbp_has_forums' with
 *                           bbPres::forum_query::have_posts()
 *                           and bbPres::forum_query
 * @uses                     WP_Query To make query and get the forums
 */
function bbp_has_forums( $args = '' ) {
	static $bbp_forum_query_cache = array();
	global $wp_rewrite;

	// Forum archive only shows root
	if ( bbp_is_forum_archive() ) {
		$default_post_parent = 0;

		// User subscriptions shows any
	} elseif ( bbp_is_subscriptions() ) {
		$default_post_parent = 'any';

		// Could be anything, so look for possible parent ID
	} else {
		$default_post_parent = bbp_get_forum_id();
	}

	$default_forum_search = bbp_sanitize_search_request( 'fs' );

	// Parse arguments with default forum query for most circumstances
	$bbp_f = bbp_parse_args(
		$args,
		array(
			'post_type'                => bbp_get_forum_post_type(),
			'post_parent'              => $default_post_parent,
			'post_status'              => bbp_get_public_status_id(),
			'posts_per_page'           => bbp_get_forums_per_page(),
			'ignore_sticky_posts'      => true,
			'orderby'                  => 'menu_order title',
			'order'                    => 'ASC',
			'paged'                    => bbp_get_paged(),           // Page Number.
			'update_post_family_cache' => true,                      // Conditionally prime the cache for related posts.
		),
		'has_forums'
	);

	// Only add 's' arg if searching for forums.
	if ( ! empty( $default_forum_search ) ) {
		$bbp_f['s'] = $default_forum_search;
	}

	if ( ! empty( $default_post_parent ) ) {
		$bbp_f['paged'] = bb_get_forum_paged();
	}

	// Run the query.
	$bbp       = bbpress();
	$cache_key = 'bbp_has_forums_' . md5( maybe_serialize( $bbp_f ) );
	if ( ! isset( $bbp_forum_query_cache[ $cache_key ] ) ) {
		$bbp->forum_query = new WP_Query( $bbp_f );

		$bbp_forum_query_cache[ $cache_key ] = $bbp->forum_query;

		// Maybe prime last active posts.
		if ( ! empty( $bbp_f['update_post_family_cache'] ) ) {
			bbp_update_post_family_caches( $bbp->forum_query->posts );
		}
	} else {
		$bbp->forum_query = $bbp_forum_query_cache[ $cache_key ];
	}

	// Add pagination values to query object.
	$bbp->forum_query->posts_per_page = $bbp_f['posts_per_page'];
	$bbp->forum_query->paged          = $bbp_f['paged'];

	// Only add pagination if query returned results.
	if ( ( (int) $bbp->forum_query->post_count || (int) $bbp->forum_query->found_posts ) && (int) $bbp->forum_query->posts_per_page ) {

		// Limit the number of forums shown based on maximum allowed pages.
		if ( ( ! empty( $bbp_f['max_num_pages'] ) ) && $bbp->forum_query->found_posts > $bbp->forum_query->max_num_pages * $bbp->forum_query->post_count ) {
			$bbp->forum_query->found_posts = $bbp->forum_query->max_num_pages * $bbp->forum_query->post_count;
		}

		if ( ! empty( $default_post_parent ) ) {

			if ( bbp_get_paged() !== 1 ) {
				$base = trailingslashit( bbp_get_forum_permalink( $default_post_parent ) ) . user_trailingslashit( bbp_get_paged_slug() . '/' . bbp_get_paged() . '/' );
			} else {
				$base = bbp_get_forum_permalink( $default_post_parent );
			}

			$base = add_query_arg( 'forum-paged', '%#%',  $base ); // If a forum has subforums, this is the URL to their first page
		} else {
			$base = add_query_arg( 'paged', '%#%', bbp_get_forums_url() );
		}

		// Pagination settings with filter.
		$bbp_topic_pagination = apply_filters(
			'bbp_forum_pagination',
			array(
				'base'      => $base,
				'format'    => '',
				'total'     => $bbp_f['posts_per_page'] === $bbp->forum_query->found_posts ? 1 : ceil( (int) $bbp->forum_query->found_posts / (int) $bbp_f['posts_per_page'] ),
				'current'   => (int) $bbp->forum_query->paged,
				'prev_text' => is_rtl() ? '&rarr;' : '&larr;',
				'next_text' => is_rtl() ? '&larr;' : '&rarr;',
				'mid_size'  => 1,
			)
		);

		// Add pagination to query object.
		$bbp->forum_query->pagination_links = paginate_links( $bbp_topic_pagination );

		if ( ! empty( $bbp->forum_query->pagination_links ) ) {
			// Remove first page from pagination.
			$bbp->forum_query->pagination_links = str_replace( $wp_rewrite->pagination_base . "/1/'", "'", $bbp->forum_query->pagination_links );
		}
	}

	return apply_filters( 'bbp_has_forums', $bbp->forum_query->have_posts(), $bbp->forum_query );
}

/**
 * Whether there are more forums available in the loop
 *
 * @since                                   bbPress (r2464)
 *
 * @return object Forum information
 * @uses                                    bbPress:forum_query::have_posts() To check if there are more forums
 *                                          available
 */
function bbp_forums() {

	// Put into variable to check against next
	$have_posts = bbpress()->forum_query->have_posts();

	// Reset the post data when finished
	if ( empty( $have_posts ) ) {
		wp_reset_postdata();
	}

	return $have_posts;
}

/**
 * Loads up the current forum in the loop
 *
 * @since bbPress (r2464)
 *
 * @return object Forum information
 * @uses  bbPress:forum_query::the_post() To get the current forum
 */
function bbp_the_forum() {
	return bbpress()->forum_query->the_post();
}

/** Forum *********************************************************************/

/**
 * Output forum id
 *
 * @since bbPress (r2464)
 *
 * @param $forum_id Optional. Used to check emptiness
 *
 * @uses  bbp_get_forum_id() To get the forum id
 */
function bbp_forum_id( $forum_id = 0 ) {
	echo bbp_get_forum_id( $forum_id );
}

/**
 * Return the forum id
 *
 * @since                 bbPress (r2464)
 *
 * @param $forum_id Optional. Used to check emptiness
 *
 * @return int The forum id
 * @uses                  bbPress::forum_query::in_the_loop To check if we're in the loop
 * @uses                  bbPress::forum_query::post::ID To get the forum id
 * @uses                  WP_Query::post::ID To get the forum id
 * @uses                  bbp_is_forum() To check if the search result is a forum
 * @uses                  bbp_is_single_forum() To check if it's a forum page
 * @uses                  bbp_is_single_topic() To check if it's a topic page
 * @uses                  bbp_get_topic_forum_id() To get the topic forum id
 * @uses                  get_post_field() To get the post's post type
 * @uses                  apply_filters() Calls 'bbp_get_forum_id' with the forum id and
 *                        supplied forum id
 */
function bbp_get_forum_id( $forum_id = 0 ) {
	global $wp_query;

	$bbp = bbpress();

	// Easy empty checking
	if ( ! empty( $forum_id ) && is_numeric( $forum_id ) ) {
		$bbp_forum_id = $forum_id;

		// Currently inside a forum loop
	} elseif ( ! empty( $bbp->forum_query->in_the_loop ) && isset( $bbp->forum_query->post->ID ) ) {
		$bbp_forum_id = $bbp->forum_query->post->ID;

		// Currently inside a search loop
	} elseif ( ! empty( $bbp->search_query->in_the_loop ) && isset( $bbp->search_query->post->ID ) && bbp_is_forum( $bbp->search_query->post->ID ) ) {
		$bbp_forum_id = $bbp->search_query->post->ID;

		// Currently viewing a forum
	} elseif ( ( bbp_is_single_forum() || bbp_is_forum_edit() ) && ! empty( $bbp->current_forum_id ) ) {
		$bbp_forum_id = $bbp->current_forum_id;

		// Currently viewing a forum
	} elseif ( ( bbp_is_single_forum() || bbp_is_forum_edit() ) && isset( $wp_query->post->ID ) ) {
		$bbp_forum_id = $wp_query->post->ID;

		// Currently viewing a topic
	} elseif ( bbp_is_single_topic() ) {
		$bbp_forum_id = bbp_get_topic_forum_id();

		// Fallback
	} else {
		$bbp_forum_id = 0;
	}

	return (int) apply_filters( 'bbp_get_forum_id', (int) $bbp_forum_id, $forum_id );
}

/**
 * Gets a forum
 *
 * @since                 bbPress (r2787)
 *
 * @param int|object $forum  forum id or forum object
 * @param string     $output Optional. OBJECT, ARRAY_A, or ARRAY_N. Default = OBJECT
 * @param string     $filter Optional Sanitation filter. See {@link sanitize_post()}
 *
 * @return mixed Null if error or forum (in specified form) if success
 * @uses                  apply_filters() Calls 'bbp_get_forum' with the forum, output type and
 *                        sanitation filter
 * @uses                  get_post() To get the forum
 */
function bbp_get_forum( $forum, $output = OBJECT, $filter = 'raw' ) {

	// Use forum ID.
	if ( empty( $forum ) || is_numeric( $forum ) ) {
		$forum = bbp_get_forum_id( $forum );
	}

	// Attempt to load the forum.
	$forum = get_post( $forum, OBJECT, $filter );
	if ( empty( $forum ) ) {
		return $forum;
	}

	// Bail if post_type is not a forum.
	if ( bbp_get_forum_post_type() !== $forum->post_type ) {
		return null;
	}

	// Tweak the data type to return.
	if ( ARRAY_A === $output ) {
		$forum = get_object_vars( $forum );

	} elseif ( ARRAY_N === $output ) {
		$forum = array_values( get_object_vars( $forum ) );
	}

	return apply_filters( 'bbp_get_forum', $forum, $output, $filter );
}

/**
 * Output the link to the forum
 *
 * @since bbPress (r2464)
 *
 * @param int $forum_id Optional. Forum id
 *
 * @uses  bbp_get_forum_permalink() To get the permalink
 */
function bbp_forum_permalink( $forum_id = 0 ) {
	echo esc_url( bbp_get_forum_permalink( $forum_id ) );
}

/**
 * Return the link to the forum
 *
 * @since                 bbPress (r2464)
 *
 * @param int $forum_id         Optional. Forum id
 * @param     $string           $redirect_to Optional. Pass a redirect value for use with
 *                              shortcodes and other fun things.
 *
 * @return string Permanent link to forum
 * @uses                  get_permalink() Get the permalink of the forum
 * @uses                  apply_filters() Calls 'bbp_get_forum_permalink' with the forum
 *                        link
 * @uses                  bbp_get_forum_id() To get the forum id
 */
function bbp_get_forum_permalink( $forum_id = 0, $redirect_to = '' ) {
	$forum_id = bbp_get_forum_id( $forum_id );

	// Use the redirect address
	if ( ! empty( $redirect_to ) ) {
		$forum_permalink = esc_url_raw( $redirect_to );

		// Use the topic permalink
	} else {
		$forum_permalink = get_permalink( $forum_id );
	}

	return apply_filters( 'bbp_get_forum_permalink', $forum_permalink, $forum_id );
}

/**
 * Output the title of the forum
 *
 * @since bbPress (r2464)
 *
 * @param int $forum_id Optional. Forum id
 *
 * @uses  bbp_get_forum_title() To get the forum title
 */
function bbp_forum_title( $forum_id = 0 ) {
	echo bbp_get_forum_title( $forum_id );
}

/**
 * Return the title of the forum
 *
 * @since bbPress (r2464)
 *
 * @param int $forum_id Optional. Forum id
 *
 * @return string Title of forum
 * @uses  get_the_title() To get the forum title
 * @uses  apply_filters() Calls 'bbp_get_forum_title' with the title
 * @uses  bbp_get_forum_id() To get the forum id
 */
function bbp_get_forum_title( $forum_id = 0 ) {
	$forum_id = bbp_get_forum_id( $forum_id );
	$title    = get_the_title( $forum_id );

	return apply_filters( 'bbp_get_forum_title', $title, $forum_id );
}

/**
 * Output the forum archive title
 *
 * @since bbPress (r3249)
 *
 * @param string $title Default text to use as title
 */
function bbp_forum_archive_title( $title = '' ) {
	echo bbp_get_forum_archive_title( $title );
}

/**
 * Return the forum archive title
 *
 * @since bbPress (r3249)
 *
 * @param string $title Default text to use as title
 *
 * @return string The forum archive title
 * @uses  get_the_title() Use the page title at the root path
 * @uses  get_post_type_object() Load the post type object
 * @uses  bbp_get_forum_post_type() Get the forum post type ID
 * @uses  get_post_type_labels() Get labels for forum post type
 * @uses  apply_filters() Allow output to be manipulated
 *
 * @uses  bbp_get_page_by_path() Check if page exists at root path
 */
function bbp_get_forum_archive_title( $title = '' ) {

	// If no title was passed
	if ( empty( $title ) ) {

		// Set root text to page title
		$page = bbp_get_page_by_path( bbp_get_root_slug() );
		if ( ! empty( $page ) ) {
			$title = get_the_title( $page->ID );

			// Default to forum post type name label
		} else {
			$fto   = get_post_type_object( bbp_get_forum_post_type() );
			$title = $fto->labels->name;
		}
	}

	return apply_filters( 'bbp_get_forum_archive_title', $title );
}

/**
 * Output the content of the forum
 *
 * @since bbPress (r2780)
 *
 * @param int $forum_id Optional. Topic id
 *
 * @uses  bbp_get_forum_content() To get the forum content
 */
function bbp_forum_content( $forum_id = 0 ) {
	echo bbp_get_forum_content( $forum_id );
}

/**
 * Return the content of the forum
 *
 * @since                 bbPress (r2780)
 *
 * @param int $forum_id Optional. Topic id
 *
 * @return string Content of the forum
 * @uses                  post_password_required() To check if the forum requires pass
 * @uses                  get_the_password_form() To get the password form
 * @uses                  get_post_field() To get the content post field
 * @uses                  apply_filters() Calls 'bbp_get_forum_content' with the content
 *                        and forum id
 * @uses                  bbp_get_forum_id() To get the forum id
 */
function bbp_get_forum_content( $forum_id = 0 ) {
	$forum_id = bbp_get_forum_id( $forum_id );

	// Check if password is required
	if ( post_password_required( $forum_id ) ) {
		return get_the_password_form();
	}

	$content = get_post_field( 'post_content', $forum_id );

	return apply_filters( 'bbp_get_forum_content', $content, $forum_id );
}

/**
 * Allow forum rows to have adminstrative actions
 *
 * @since bbPress (r3653)
 * @uses  do_action()
 * @todo  Links and filter
 */
function bbp_forum_row_actions() {
	do_action( 'bbp_forum_row_actions' );
}

/**
 * Output the forums last active ID
 *
 * @since bbPress (r2860)
 *
 * @param int $forum_id Optional. Forum id
 *
 * @uses  bbp_get_forum_last_active_id() To get the forum's last active id
 */
function bbp_forum_last_active_id( $forum_id = 0 ) {
	echo bbp_get_forum_last_active_id( $forum_id );
}

/**
 * Return the forums last active ID
 *
 * @since                 bbPress (r2860)
 *
 * @param int $forum_id Optional. Forum id
 *
 * @return int Forum's last active id
 * @uses                  get_post_meta() To get the forum's last active id
 * @uses                  apply_filters() Calls 'bbp_get_forum_last_active_id' with
 *                        the last active id and forum id
 * @uses                  bbp_get_forum_id() To get the forum id
 */
function bbp_get_forum_last_active_id( $forum_id = 0 ) {
	$forum_id  = bbp_get_forum_id( $forum_id );
	$active_id = get_post_meta( $forum_id, '_bbp_last_active_id', true );

	return (int) apply_filters( 'bbp_get_forum_last_active_id', (int) $active_id, $forum_id );
}

/**
 * Output the forums last update date/time (aka freshness)
 *
 * @since bbPress (r2464)
 *
 * @param int $forum_id Optional. Forum id
 *
 * @uses  bbp_get_forum_last_active_time() To get the forum freshness
 */
function bbp_forum_last_active_time( $forum_id = 0 ) {
	echo bbp_get_forum_last_active_time( $forum_id );
}

/**
 * Return the forums last update date/time (aka freshness)
 *
 * @since                             bbPress (r2464)
 *
 * @param int $forum_id Optional. Forum id
 *
 * @return string Forum last update date/time (freshness)
 * @uses                              bbp_get_forum_id() To get the forum id
 * @uses                              get_post_meta() To retrieve forum last active meta
 * @uses                              bbp_get_forum_last_reply_id() To get forum's last reply id
 * @uses                              get_post_field() To get the post date of the reply
 * @uses                              bbp_get_forum_last_topic_id() To get forum's last topic id
 * @uses                              bbp_get_topic_last_active_time() To get time when the topic was
 *                                    last active
 * @uses                              bbp_convert_date() To convert the date
 * @uses                              bbp_get_time_since() To get time in since format
 * @uses                              apply_filters() Calls 'bbp_get_forum_last_active' with last
 *                                    active time and forum id
 */
function bbp_get_forum_last_active_time( $forum_id = 0 ) {

	// Verify forum and get last active meta
	$forum_id    = bbp_get_forum_id( $forum_id );
	$last_active = get_post_meta( $forum_id, '_bbp_last_active_time', true );

	if ( empty( $last_active ) ) {
		$reply_id = bbp_get_forum_last_reply_id( $forum_id );
		if ( ! empty( $reply_id ) ) {
			$last_active = get_post_field( 'post_date', $reply_id );
		} else {
			$topic_id = bbp_get_forum_last_topic_id( $forum_id );
			if ( ! empty( $topic_id ) ) {
				$last_active = bbp_get_topic_last_active_time( $topic_id );
			}
		}
	}

	$active_time = ! empty( $last_active ) ? bbp_get_time_since( bbp_convert_date( $last_active ) ) : '';

	return apply_filters( 'bbp_get_forum_last_active', $active_time, $forum_id );
}

/**
 * Output link to the most recent activity inside a forum.
 *
 * Outputs a complete link with attributes and content.
 *
 * @since bbPress (r2625)
 *
 * @param int $forum_id Optional. Forum id
 *
 * @uses  bbp_get_forum_freshness_link() To get the forum freshness link
 */
function bbp_forum_freshness_link( $forum_id = 0 ) {
	echo bbp_get_forum_freshness_link( $forum_id );
}

/**
 * Returns link to the most recent activity inside a forum.
 *
 * Returns a complete link with attributes and content.
 *
 * @since bbPress (r2625)
 *
 * @param int $forum_id Optional. Forum id
 *
 * @uses  bbp_get_forum_id() To get the forum id
 * @uses  bbp_get_forum_last_active_id() To get the forum last active id
 * @uses  bbp_get_forum_last_reply_id() To get the forum last reply id
 * @uses  bbp_get_forum_last_topic_id() To get the forum last topic id
 * @uses  bbp_get_forum_last_reply_url() To get the forum last reply url
 * @uses  bbp_get_forum_last_reply_title() To get the forum last reply
 *                                         title
 * @uses  bbp_get_forum_last_topic_permalink() To get the forum last
 *                                             topic permalink
 * @uses  bbp_get_forum_last_topic_title() To get the forum last topic
 *                                         title
 * @uses  bbp_get_forum_last_active_time() To get the time when the forum
 *                                         was last active
 * @uses  apply_filters() Calls 'bbp_get_forum_freshness_link' with the
 *                        link and forum id
 */
function bbp_get_forum_freshness_link( $forum_id = 0 ) {
	$forum_id  = bbp_get_forum_id( $forum_id );
	$active_id = bbp_get_forum_last_active_id( $forum_id );
	$link_url  = $title = '';

	if ( empty( $active_id ) ) {
		$active_id = bbp_get_forum_last_reply_id( $forum_id );
	}

	if ( empty( $active_id ) ) {
		$active_id = bbp_get_forum_last_topic_id( $forum_id );
	}

	if ( bbp_is_topic( $active_id ) ) {
		$link_url = bbp_get_forum_last_topic_permalink( $forum_id );
		$title    = bbp_get_forum_last_topic_title( $forum_id );
	} elseif ( bbp_is_reply( $active_id ) ) {
		$link_url = bbp_get_forum_last_reply_url( $forum_id );
		$title    = bbp_get_forum_last_reply_title( $forum_id );
	}

	$time_since = bbp_get_forum_last_active_time( $forum_id );

	if ( ! empty( $time_since ) && ! empty( $link_url ) ) {
		$anchor = '<a href="' . esc_url( $link_url ) . '" title="' . esc_attr( $title ) . '">' . esc_html( $time_since ) . '</a>';
	} else {
		$anchor = esc_html__( 'No Discussions', 'buddyboss' );
	}

	return apply_filters( 'bbp_get_forum_freshness_link', $anchor, $forum_id, $time_since, $link_url, $title, $active_id );
}

/**
 * Output parent ID of a forum, if exists
 *
 * @since bbPress (r3675)
 *
 * @param int $forum_id Forum ID
 *
 * @uses  bbp_get_forum_parent_id() To get the forum's parent ID
 */
function bbp_forum_parent_id( $forum_id = 0 ) {
	echo bbp_get_forum_parent_id( $forum_id );
}

/**
 * Return ID of forum parent, if exists
 *
 * @since bbPress (r3675)
 *
 * @param int $forum_id Optional. Forum id
 *
 * @return int Forum parent
 * @uses  get_post_field() To get the forum parent
 * @uses  apply_filters() Calls 'bbp_get_forum_parent' with the parent & forum id
 * @uses  bbp_get_forum_id() To get the forum id
 */
function bbp_get_forum_parent_id( $forum_id = 0 ) {
	$forum_id  = bbp_get_forum_id( $forum_id );
	$parent_id = get_post_field( 'post_parent', $forum_id );

	return (int) apply_filters( 'bbp_get_forum_parent_id', (int) $parent_id, $forum_id );
}

/**
 * Return array of parent forums
 *
 * @since                 bbPress (r2625)
 *
 * @param int $forum_id Optional. Forum id
 *
 * @return array Forum ancestors
 * @uses                  bbp_get_forum() To get the forum
 * @uses                  apply_filters() Calls 'bbp_get_forum_ancestors' with the ancestors
 *                        and forum id
 * @uses                  bbp_get_forum_id() To get the forum id
 */
function bbp_get_forum_ancestors( $forum_id = 0 ) {
	$forum_id  = bbp_get_forum_id( $forum_id );
	$ancestors = array();
	$forum     = bbp_get_forum( $forum_id );

	if ( ! empty( $forum ) ) {
		while ( 0 !== (int) $forum->post_parent ) {
			$ancestors[] = $forum->post_parent;
			$forum       = bbp_get_forum( $forum->post_parent );
		}
	}

	return apply_filters( 'bbp_get_forum_ancestors', $ancestors, $forum_id );
}

/**
 * Return subforums of given forum
 *
 * @since                    bbPress (r2747)
 *
 * @param mixed $args All the arguments supported by {@link WP_Query}
 *
 * @return mixed false if none, array of subs if yes
 * @uses                     current_user_can() To check if the current user is capable of
 *                           reading private forums
 * @uses                     get_posts() To get the subforums
 * @uses                     apply_filters() Calls 'bbp_forum_get_subforums' with the subforums
 *                           and the args
 * @uses                     bbp_get_forum_id() To get the forum id
 */
function bbp_forum_get_subforums( $args = '' ) {

	// Use passed integer as post_parent
	if ( is_numeric( $args ) ) {
		$args = array( 'post_parent' => $args );
	}

	// Setup possible post__not_in array
	$post_stati = array( bbp_get_public_status_id() );

	// Super admin get whitelisted post statuses
	if ( bbp_is_user_keymaster() ) {
		$post_stati = array( bbp_get_public_status_id(), bbp_get_private_status_id(), bbp_get_hidden_status_id() );

		// Not a keymaster, so check caps
	} else {

		// Check if user can read private forums
		if ( current_user_can( 'read_private_forums' ) ) {
			$post_stati[] = bbp_get_private_status_id();
		}

		// Check if user can read hidden forums
		if ( current_user_can( 'read_hidden_forums' ) ) {
			$post_stati[] = bbp_get_hidden_status_id();
		}
	}

	// Parse arguments against default values
	$r                = bbp_parse_args(
		$args,
		array(
			'post_parent'         => 0,
			'post_type'           => bbp_get_forum_post_type(),
			'post_status'         => implode( ',', $post_stati ),
			'posts_per_page'      => bbp_get_forums_per_page(),
			'orderby'             => 'menu_order title',
			'order'               => 'ASC',
			'ignore_sticky_posts' => true,
			'no_found_rows'       => true,
		),
		'forum_get_subforums'
	);
	$r['post_parent'] = bbp_get_forum_id( $r['post_parent'] );

	// Create a new query for the subforums
	$get_posts = new WP_Query();

	// No forum passed
	$sub_forums = ! empty( $r['post_parent'] ) ? $get_posts->query( $r ) : array();

	return (array) apply_filters( 'bbp_forum_get_subforums', $sub_forums, $r );
}

/**
 * Output a list of forums (can be used to list subforums)
 *
 * @param mixed $args The function supports these args:
 *                    - before: To put before the output. Defaults to '<ul class="bbp-forums">'
 *                    - after: To put after the output. Defaults to '</ul>'
 *                    - link_before: To put before every link. Defaults to '<li class="bbp-forum">'
 *                    - link_after: To put after every link. Defaults to '</li>'
 *                    - separator: Separator. Defaults to ', '
 *                    - forum_id: Forum id. Defaults to ''
 *                    - show_topic_count - To show forum topic count or not. Defaults to true
 *                    - show_reply_count - To show forum reply count or not. Defaults to true
 *
 * @uses bbp_forum_get_subforums() To check if the forum has subforums or not
 * @uses bbp_get_forum_permalink() To get forum permalink
 * @uses bbp_get_forum_title() To get forum title
 * @uses bbp_is_forum_category() To check if a forum is a category
 * @uses bbp_get_forum_topic_count() To get forum topic count
 * @uses bbp_get_forum_reply_count() To get forum reply count
 */
function bbp_list_forums( $args = '' ) {

	// Define used variables
	$output = $sub_forums = $topic_count = $reply_count = $counts = '';
	$i      = 0;
	$count  = array();

	// Parse arguments against default values
	$r = bbp_parse_args(
		$args,
		array(
			'before'           => '<ul class="bbp-forums-list">',
			'after'            => '</ul>',
			'link_before'      => '<li class="bbp-forum">',
			'link_after'       => '</li>',
			'count_before'     => ' (',
			'count_after'      => ')',
			'count_sep'        => ', ',
			'separator'        => ', ',
			'forum_id'         => '',
			'show_topic_count' => true,
			'show_reply_count' => true,
		),
		'list_forums'
	);

	// Loop through forums and create a list
	$sub_forums = bbp_forum_get_subforums( $r['forum_id'] );
	if ( ! empty( $sub_forums ) ) {

		// Total count (for separator)
		$total_subs = count( $sub_forums );
		foreach ( $sub_forums as $sub_forum ) {
			$i ++; // Separator count

			// Get forum details
			$count     = array();
			$show_sep  = $total_subs > $i ? $r['separator'] : '';
			$permalink = bbp_get_forum_permalink( $sub_forum->ID );
			$title     = bbp_get_forum_title( $sub_forum->ID );

			// Show topic count
			if ( ! empty( $r['show_topic_count'] ) && ! bbp_is_forum_category( $sub_forum->ID ) ) {
				$count['topic'] = bbp_get_forum_topic_count( $sub_forum->ID );
			}

			// Show reply count
			if ( ! empty( $r['show_reply_count'] ) && ! bbp_is_forum_category( $sub_forum->ID ) ) {
				$count['reply'] = bbp_get_forum_reply_count( $sub_forum->ID );
			}

			// Counts to show
			if ( ! empty( $count ) ) {
				$counts = $r['count_before'] . implode( $r['count_sep'], $count ) . $r['count_after'];
			}

			// Build this sub forums link
			$output .= $r['link_before'] . '<a href="' . esc_url( $permalink ) . '" class="bbp-forum-link">' . $title . $counts . '</a>' . $show_sep . $r['link_after'];
		}

		// Output the list
		echo apply_filters( 'bbp_list_forums', $r['before'] . $output . $r['after'], $r );
	}
}

/**
 * Output a list of forums recursively.
 *
 * @since BuddyBoss 2.4.20
 *
 * @param mixed $args The function supports these args:
 *                    - before: To put before the output. Defaults to '<ul class="bbp-forums">'
 *                    - after: To put after the output. Defaults to '</ul>'
 *                    - link_before: To put before every link. Defaults to '<li class="bbp-forum">'
 *                    - link_after: To put after every link. Defaults to '</li>'
 *                    - separator: Separator. Defaults to ', '
 *                    - forum_id: Forum id. Defaults to ''
 *                    - show_topic_count - To show forum topic count or not. Defaults to true
 *                    - show_reply_count - To show forum reply count or not. Defaults to true
 *
 * @uses bbp_forum_get_subforums() To check if the forum has subforums or not
 * @uses bbp_get_forum_permalink() To get forum permalink
 * @uses bbp_get_forum_title() To get forum title
 * @uses bbp_is_forum_category() To check if a forum is a category
 * @uses bbp_get_forum_topic_count() To get forum topic count
 * @uses bbp_get_forum_reply_count() To get forum reply count
 *
 * @return mixed
 */
function bb_get_list_forums_recursively( $args = array() ) {

	$r = bbp_parse_args(
		$args,
		array(
			'before'           => '<ul class="bbp-forums-list">',
			'after'            => '</ul>',
			'link_before'      => '<li class="bbp-forum">',
			'link_after'       => '</li>',
			'count_before'     => ' (',
			'count_after'      => ')',
			'count_sep'        => ', ',
			'separator'        => ', ',
			'forum_id'         => '',
			'show_topic_count' => true,
			'show_reply_count' => true,
		),
		'list_forums'
	);

	// Get subforums for the current forum.
	$sub_forums = bbp_forum_get_subforums( $r['forum_id'] );
	$output     = '';

	if ( ! empty( $sub_forums ) ) {
		$total_subs = count( $sub_forums );
		$counts     = '';
		$i          = 0;

		foreach ( $sub_forums as $sub_forum ) {
			$i ++; // Separator count

			$count    = array();
			$show_sep = $total_subs > $i ? $r['separator'] : '';

			// Show topic count
			if ( ! empty( $r['show_topic_count'] ) && ! bbp_is_forum_category( $sub_forum->ID ) ) {
				$count['topic'] = bbp_get_forum_topic_count( $sub_forum->ID );
			}

			// Show reply count
			if ( ! empty( $r['show_reply_count'] ) && ! bbp_is_forum_category( $sub_forum->ID ) ) {
				$count['reply'] = bbp_get_forum_reply_count( $sub_forum->ID );
			}

			// Counts to show
			if ( ! empty( $count ) ) {
				$counts = $r['count_before'] . implode( $r['count_sep'], $count ) . $r['count_after'];
			}

			$output .= sprintf(
				'%s <a href="%s" class="bbp-forum-link">%s %s</a> %s %s %s',
				$r['link_before'],
				esc_url( bbp_get_forum_permalink( $sub_forum->ID ) ),
				esc_html( bbp_get_forum_title( $sub_forum->ID ) ),
				$counts,
				$show_sep,
				bb_get_list_forums_recursively( array( 'forum_id' => $sub_forum->ID ) ),
				$r['link_after'],
			);
		}

		/**
		 * Modify the output of a list of forums recursively using filters.
		 *
		 * @since BuddyBoss 2.4.20
		 *
		 * @param string $output The processed output of the list of forums.
		 * @param array  $r      An array of parameters and settings related to the output.
		 */
		$output = apply_filters( 'bb_get_list_forums_recursively', $r['before'] . $output . $r['after'], $r );
	}

	return $output;
}

/** Forum Pagination **********************************************************/

function bbp_forum_index_pagination_count() {
	echo bbp_get_forum_index_pagination_count();
}

/**
 * Return the forum index pagination count
 *
 * @since                 bbPress (r2519)
 *
 * @return string Forum Pagintion count
 * @uses                  apply_filters() Calls 'bbp_get_forum_index_pagination_count' with the
 *                        pagination count
 * @uses                  bbp_number_format() To format the number value
 */
function bbp_get_forum_index_pagination_count() {
	$bbp = bbpress();

	if ( empty( $bbp->forum_query ) ) {
		return false;
	}

	// Set pagination values
	$start_num = intval( ( $bbp->forum_query->paged - 1 ) * $bbp->forum_query->posts_per_page ) + 1;
	$from_num  = bbp_number_format( $start_num );
	$to_num    = bbp_number_format( ( $start_num + ( $bbp->forum_query->posts_per_page - 1 ) > $bbp->forum_query->found_posts ) ? $bbp->forum_query->found_posts : $start_num + ( $bbp->forum_query->posts_per_page - 1 ) );
	$total_int = (int) ! empty( $bbp->forum_query->found_posts ) ? $bbp->forum_query->found_posts : $bbp->forum_query->post_count;
	$total     = bbp_number_format( $total_int );

	// Several forums in a forum index with a single page
	if ( empty( $to_num ) ) {
		$retstr = sprintf( _n( 'Viewing %1$s forum', 'Viewing %1$s forums', $total_int, 'buddyboss' ), $total );

		// Several forums in a forum index with several pages
	} else {
		$retstr = sprintf( _n( 'Viewing %2$s of %4$s forums', 'Viewing %2$s - %3$s of %4$s forums', $total_int, 'buddyboss' ), $bbp->forum_query->post_count, $from_num, $to_num, $total );
	}

	// Filter and return
	return apply_filters( 'bbp_get_forum_index_pagination_count', esc_html( $retstr ) );
}

/**
 * Output forum pagination links
 *
 * @since BuddyBoss 1.0.0
 *
 * @uses  bbp_get_forum_index_pagination_links() To get the forum index pagination links
 */
function bbp_forum_index_pagination_links() {
	echo bbp_get_forum_index_pagination_links();
}

/**
 * Return forum pagination links
 *
 * @since                 BuddyBoss 1.0.0
 *
 * @return string forum pagination links
 * @uses                  apply_filters() Calls 'bbp_get_forum_index_pagination_links' with the
 *                        pagination links
 */
function bbp_get_forum_index_pagination_links() {
	$bbp = bbpress();

	if ( ! isset( $bbp->forum_query->pagination_links ) || empty( $bbp->forum_query->pagination_links ) ) {
		return false;
	}

	return apply_filters( 'bbp_get_forum_index_pagination_links', $bbp->forum_query->pagination_links );
}

/** Forum Subscriptions *******************************************************/

/**
 * Output the forum subscription link
 *
 * @since bbPress (r5156)
 *
 * @uses  bbp_get_forum_subscription_link()
 */
function bbp_forum_subscription_link( $args = array() ) {
	echo bbp_get_forum_subscription_link( $args );
}

/**
 * Get the forum subscription link
 *
 * A custom wrapper for bbp_get_user_subscribe_link()
 *
 * @since bbPress (r5156)
 *
 * @uses  bbp_parse_args()
 * @uses  bbp_get_user_subscribe_link()
 * @uses  apply_filters() Calls 'bbp_get_forum_subscribe_link'
 */
function bbp_get_forum_subscription_link( $args = array() ) {

	// No link
	$retval = false;

	// Parse the arguments
	$r = bbp_parse_args(
		$args,
		array(
			'forum_id'    => 0,
			'user_id'     => 0,
			'before'      => '',
			'after'       => '',
			'subscribe'   => __( 'Subscribe', 'buddyboss' ),
			'unsubscribe' => __( 'Unsubscribe', 'buddyboss' ),
		),
		'get_forum_subscribe_link'
	);

	// No link for categories until we support subscription hierarchy
	// @see http://bbpress.trac.wordpress.org/ticket/2475
	if ( ! bbp_is_forum_category() ) {
		$retval = bbp_get_user_subscribe_link( $r );
	}

	return apply_filters( 'bbp_get_forum_subscribe_link', $retval, $r );
}

/** Forum Report **********************************************************/

/**
 * Output the forum report link
 *
 * @since BuddyBoss 1.5.6
 *
 * @uses  bbp_get_forum_report_link()
 */
function bbp_forum_report_link( $args = array() ) {
	echo bbp_get_forum_report_link( $args );
}

/**
 * Get the forum report link
 *
 * @since BuddyBoss 1.5.6
 *
 * @uses  bbp_parse_args()
 * @uses  apply_filters() Calls 'bbp_get_forum_report_link'
 */
function bbp_get_forum_report_link( $args = array() ) {

	if ( ! bp_is_active( 'moderation' ) || ! is_user_logged_in() ) {
		return false;
	}

	$retval = bp_moderation_get_report_button( array(
			'id'                => 'forum_report',
			'component'         => 'moderation',
			'must_be_logged_in' => true,
			'button_attr'       => array(
				'data-bp-content-id'   => ! empty( $args['id'] ) ? $args['id'] : 0,
				'data-bp-content-type' => BP_Moderation_Forums::$moderation_type,
			),
		),
		true
	);

	if ( empty( $retval ) ) {
		$retval = false;
	}

	return apply_filters( 'bbp_get_forum_report_link', $retval, $args );
}

/** Forum Last Topic **********************************************************/

/**
 * Output the forum's last topic id
 *
 * @since bbPress (r2464)
 *
 * @param int $forum_id Optional. Forum id
 *
 * @uses  bbp_get_forum_last_topic_id() To get the forum's last topic id
 */
function bbp_forum_last_topic_id( $forum_id = 0 ) {
	echo bbp_get_forum_last_topic_id( $forum_id );
}

/**
 * Return the forum's last topic id
 *
 * @since                 bbPress (r2464)
 *
 * @param int $forum_id Optional. Forum id
 *
 * @return int Forum's last topic id
 * @uses                  get_post_meta() To get the forum's last topic id
 * @uses                  apply_filters() Calls 'bbp_get_forum_last_topic_id' with the
 *                        forum and topic id
 * @uses                  bbp_get_forum_id() To get the forum id
 */
function bbp_get_forum_last_topic_id( $forum_id = 0 ) {
	$forum_id = bbp_get_forum_id( $forum_id );
	$topic_id = get_post_meta( $forum_id, '_bbp_last_topic_id', true );

	return (int) apply_filters( 'bbp_get_forum_last_topic_id', (int) $topic_id, $forum_id );
}

/**
 * Output the title of the last topic inside a forum
 *
 * @since bbPress (r2625)
 *
 * @param int $forum_id Optional. Forum id
 *
 * @uses  bbp_get_forum_last_topic_title() To get the forum's last topic's title
 */
function bbp_forum_last_topic_title( $forum_id = 0 ) {
	echo bbp_get_forum_last_topic_title( $forum_id );
}

/**
 * Return the title of the last topic inside a forum
 *
 * @since                 bbPress (r2625)
 *
 * @param int $forum_id Optional. Forum id
 *
 * @return string Forum's last topic's title
 * @uses                  bbp_get_forum_last_topic_id() To get the forum's last topic id
 * @uses                  bbp_get_topic_title() To get the topic's title
 * @uses                  apply_filters() Calls 'bbp_get_forum_last_topic_title' with the
 *                        topic title and forum id
 * @uses                  bbp_get_forum_id() To get the forum id
 */
function bbp_get_forum_last_topic_title( $forum_id = 0 ) {
	$forum_id = bbp_get_forum_id( $forum_id );
	$topic_id = bbp_get_forum_last_topic_id( $forum_id );
	$title    = ! empty( $topic_id ) ? bbp_get_topic_title( $topic_id ) : '';

	return apply_filters( 'bbp_get_forum_last_topic_title', $title, $forum_id );
}

/**
 * Output the link to the last topic in a forum
 *
 * @since bbPress (r2464)
 *
 * @param int $forum_id Optional. Forum id
 *
 * @uses  bbp_get_forum_last_topic_permalink() To get the forum's last topic's
 *                                             permanent link
 */
function bbp_forum_last_topic_permalink( $forum_id = 0 ) {
	echo esc_url( bbp_get_forum_last_topic_permalink( $forum_id ) );
}

/**
 * Return the link to the last topic in a forum
 *
 * @since                 bbPress (r2464)
 *
 * @param int $forum_id Optional. Forum id
 *
 * @return string Permanent link to topic
 * @uses                  bbp_get_forum_last_topic_id() To get the forum's last topic id
 * @uses                  bbp_get_topic_permalink() To get the topic's permalink
 * @uses                  apply_filters() Calls 'bbp_get_forum_last_topic_permalink' with
 *                        the topic link and forum id
 * @uses                  bbp_get_forum_id() To get the forum id
 */
function bbp_get_forum_last_topic_permalink( $forum_id = 0 ) {
	$forum_id = bbp_get_forum_id( $forum_id );

	return apply_filters( 'bbp_get_forum_last_topic_permalink', bbp_get_topic_permalink( bbp_get_forum_last_topic_id( $forum_id ) ), $forum_id );
}

/**
 * Return the author ID of the last topic of a forum
 *
 * @since                 bbPress (r2625)
 *
 * @param int $forum_id Optional. Forum id
 *
 * @return int Forum's last topic's author id
 * @uses                  bbp_get_forum_last_topic_id() To get the forum's last topic id
 * @uses                  bbp_get_topic_author_id() To get the topic's author id
 * @uses                  apply_filters() Calls 'bbp_get_forum_last_topic_author' with the author
 *                        id and forum id
 * @uses                  bbp_get_forum_id() To get the forum id
 */
function bbp_get_forum_last_topic_author_id( $forum_id = 0 ) {
	$forum_id  = bbp_get_forum_id( $forum_id );
	$author_id = bbp_get_topic_author_id( bbp_get_forum_last_topic_id( $forum_id ) );

	return (int) apply_filters( 'bbp_get_forum_last_topic_author_id', (int) $author_id, $forum_id );
}

/**
 * Output link to author of last topic of forum
 *
 * @since bbPress (r2625)
 *
 * @param int $forum_id Optional. Forum id
 *
 * @uses  bbp_get_forum_last_topic_author_link() To get the forum's last topic's
 *                                               author link
 */
function bbp_forum_last_topic_author_link( $forum_id = 0 ) {
	echo bbp_get_forum_last_topic_author_link( $forum_id );
}

/**
 * Return link to author of last topic of forum
 *
 * @since                                      bbPress (r2625)
 *
 * @param int $forum_id Optional. Forum id
 *
 * @return string Forum's last topic's author link
 * @uses                                       bbp_get_forum_last_topic_author_id() To get the forum's last
 *                                             topic's author id
 * @uses                                       bbp_get_user_profile_link() To get the author's profile link
 * @uses                                       apply_filters() Calls 'bbp_get_forum_last_topic_author_link'
 *                                             with the author link and forum id
 * @uses                                       bbp_get_forum_id() To get the forum id
 */
function bbp_get_forum_last_topic_author_link( $forum_id = 0 ) {
	$forum_id    = bbp_get_forum_id( $forum_id );
	$author_id   = bbp_get_forum_last_topic_author_id( $forum_id );
	$author_link = bbp_get_user_profile_link( $author_id );

	return apply_filters( 'bbp_get_forum_last_topic_author_link', $author_link, $forum_id );
}

/** Forum Last Reply **********************************************************/

/**
 * Output the forums last reply id
 *
 * @since bbPress (r2464)
 *
 * @param int $forum_id Optional. Forum id
 *
 * @uses  bbp_get_forum_last_reply_id() To get the forum's last reply id
 */
function bbp_forum_last_reply_id( $forum_id = 0 ) {
	echo bbp_get_forum_last_reply_id( $forum_id );
}

/**
 * Return the forums last reply id
 *
 * @since                 bbPress (r2464)
 *
 * @param int $forum_id Optional. Forum id
 *
 * @return int Forum's last reply id
 * @uses                  get_post_meta() To get the forum's last reply id
 * @uses                  bbp_get_forum_last_topic_id() To get the forum's last topic id
 * @uses                  apply_filters() Calls 'bbp_get_forum_last_reply_id' with
 *                        the last reply id and forum id
 * @uses                  bbp_get_forum_id() To get the forum id
 */
function bbp_get_forum_last_reply_id( $forum_id = 0 ) {
	$forum_id = bbp_get_forum_id( $forum_id );
	$reply_id = get_post_meta( $forum_id, '_bbp_last_reply_id', true );

	if ( empty( $reply_id ) ) {
		$reply_id = bbp_get_forum_last_topic_id( $forum_id );
	}

	return (int) apply_filters( 'bbp_get_forum_last_reply_id', (int) $reply_id, $forum_id );
}

/**
 * Output the title of the last reply inside a forum
 *
 * @param int $forum_id Optional. Forum id
 *
 * @uses bbp_get_forum_last_reply_title() To get the forum's last reply's title
 */
function bbp_forum_last_reply_title( $forum_id = 0 ) {
	echo bbp_get_forum_last_reply_title( $forum_id );
}

/**
 * Return the title of the last reply inside a forum
 *
 * @param int $forum_id Optional. Forum id
 *
 * @return string
 * @uses                  bbp_get_forum_last_reply_id() To get the forum's last reply id
 * @uses                  bbp_get_reply_title() To get the reply title
 * @uses                  apply_filters() Calls 'bbp_get_forum_last_reply_title' with the
 *                        reply title and forum id
 * @uses                  bbp_get_forum_id() To get the forum id
 */
function bbp_get_forum_last_reply_title( $forum_id = 0 ) {
	$forum_id = bbp_get_forum_id( $forum_id );

	return apply_filters( 'bbp_get_forum_last_reply_title', bbp_get_reply_title( bbp_get_forum_last_reply_id( $forum_id ) ), $forum_id );
}

/**
 * Output the link to the last reply in a forum
 *
 * @since bbPress (r2464)
 *
 * @param int $forum_id Optional. Forum id
 *
 * @uses  bbp_get_forum_last_reply_permalink() To get the forum last reply link
 */
function bbp_forum_last_reply_permalink( $forum_id = 0 ) {
	echo esc_url( bbp_get_forum_last_reply_permalink( $forum_id ) );
}

/**
 * Return the link to the last reply in a forum
 *
 * @since                 bbPress (r2464)
 *
 * @param int $forum_id Optional. Forum id
 *
 * @return string Permanent link to the forum's last reply
 * @uses                  bbp_get_forum_last_reply_id() To get the forum's last reply id
 * @uses                  bbp_get_reply_permalink() To get the reply permalink
 * @uses                  apply_filters() Calls 'bbp_get_forum_last_reply_permalink' with
 *                        the reply link and forum id
 * @uses                  bbp_get_forum_id() To get the forum id
 */
function bbp_get_forum_last_reply_permalink( $forum_id = 0 ) {
	$forum_id = bbp_get_forum_id( $forum_id );

	return apply_filters( 'bbp_get_forum_last_reply_permalink', bbp_get_reply_permalink( bbp_get_forum_last_reply_id( $forum_id ) ), $forum_id );
}

/**
 * Output the url to the last reply in a forum
 *
 * @since bbPress (r2683)
 *
 * @param int $forum_id Optional. Forum id
 *
 * @uses  bbp_get_forum_last_reply_url() To get the forum last reply url
 */
function bbp_forum_last_reply_url( $forum_id = 0 ) {
	echo esc_url( bbp_get_forum_last_reply_url( $forum_id ) );
}

/**
 * Return the url to the last reply in a forum
 *
 * @since                                      bbPress (r2683)
 *
 * @param int $forum_id Optional. Forum id
 *
 * @return string Paginated URL to latest reply
 * @uses                                       bbp_get_forum_last_reply_id() To get the forum's last reply id
 * @uses                                       bbp_get_reply_url() To get the reply url
 * @uses                                       bbp_get_forum_last_topic_permalink() To get the forum's last
 *                                             topic's permalink
 * @uses                                       apply_filters() Calls 'bbp_get_forum_last_reply_url' with the
 *                                             reply url and forum id
 * @uses                                       bbp_get_forum_id() To get the forum id
 */
function bbp_get_forum_last_reply_url( $forum_id = 0 ) {
	$forum_id = bbp_get_forum_id( $forum_id );

	// If forum has replies, get the last reply and use its url
	$reply_id = bbp_get_forum_last_reply_id( $forum_id );
	if ( ! empty( $reply_id ) ) {
		$reply_url = bbp_get_reply_url( $reply_id );

		// No replies, so look for topics and use last permalink
	} else {
		$reply_url = bbp_get_forum_last_topic_permalink( $forum_id );

		// No topics either, so set $reply_url as empty string
		if ( empty( $reply_url ) ) {
			$reply_url = '';
		}
	}

	// Filter and return
	return apply_filters( 'bbp_get_forum_last_reply_url', $reply_url, $forum_id );
}

/**
 * Output author ID of last reply of forum
 *
 * @since bbPress (r2625)
 *
 * @param int $forum_id Optional. Forum id
 *
 * @uses  bbp_get_forum_last_reply_author_id() To get the forum's last reply
 *                                             author id
 */
function bbp_forum_last_reply_author_id( $forum_id = 0 ) {
	echo bbp_get_forum_last_reply_author_id( $forum_id );
}

/**
 * Return author ID of last reply of forum
 *
 * @since                                      bbPress (r2625)
 *
 * @param int $forum_id Optional. Forum id
 *
 * @return int Forum's last reply author id
 * @uses                                       bbp_get_forum_last_reply_author_id() To get the forum's last
 *                                             reply's author id
 * @uses                                       bbp_get_reply_author_id() To get the reply's author id
 * @uses                                       apply_filters() Calls 'bbp_get_forum_last_reply_author_id' with
 *                                             the author id and forum id
 * @uses                                       bbp_get_forum_id() To get the forum id
 */
function bbp_get_forum_last_reply_author_id( $forum_id = 0 ) {
	$forum_id  = bbp_get_forum_id( $forum_id );
	$author_id = bbp_get_reply_author_id( bbp_get_forum_last_reply_id( $forum_id ) );

	return apply_filters( 'bbp_get_forum_last_reply_author_id', $author_id, $forum_id );
}

/**
 * Output link to author of last reply of forum
 *
 * @since bbPress (r2625)
 *
 * @param int $forum_id Optional. Forum id
 *
 * @uses  bbp_get_forum_last_reply_author_link() To get the forum's last reply's
 *                                               author link
 */
function bbp_forum_last_reply_author_link( $forum_id = 0 ) {
	echo bbp_get_forum_last_reply_author_link( $forum_id );
}

/**
 * Return link to author of last reply of forum
 *
 * @since                                      bbPress (r2625)
 *
 * @param int $forum_id Optional. Forum id
 *
 * @return string Link to author of last reply of forum
 * @uses                                       bbp_get_forum_last_reply_author_id() To get the forum's last
 *                                             reply's author id
 * @uses                                       bbp_get_user_profile_link() To get the reply's author's profile
 *                                             link
 * @uses                                       apply_filters() Calls 'bbp_get_forum_last_reply_author_link'
 *                                             with the author link and forum id
 * @uses                                       bbp_get_forum_id() To get the forum id
 */
function bbp_get_forum_last_reply_author_link( $forum_id = 0 ) {
	$forum_id    = bbp_get_forum_id( $forum_id );
	$author_id   = bbp_get_forum_last_reply_author_id( $forum_id );
	$author_link = bbp_get_user_profile_link( $author_id );

	return apply_filters( 'bbp_get_forum_last_reply_author_link', $author_link, $forum_id );
}

/** Forum Counts **************************************************************/

/**
 * Output the topics link of the forum
 *
 * @since bbPress (r2883)
 *
 * @param int $forum_id Optional. Topic id
 *
 * @uses  bbp_get_forum_topics_link() To get the forum topics link
 */
function bbp_forum_topics_link( $forum_id = 0 ) {
	echo bbp_get_forum_topics_link( $forum_id );
}

/**
 * Return the topics link of the forum
 *
 * @since bbPress (r2883)
 *
 * @param int $forum_id Optional. Topic id
 *
 * @uses  bbp_get_forum_id() To get the forum id
 * @uses  bbp_get_forum() To get the forum
 * @uses  bbp_get_forum_topic_count() To get the forum topic count
 * @uses  bbp_get_forum_permalink() To get the forum permalink
 * @uses  bbp_get_forum_topic_count_hidden() To get the forum hidden
 *                                           topic count
 * @uses  current_user_can() To check if the current user can edit others
 *                           topics
 * @uses  add_query_arg() To add custom args to the url
 * @uses  apply_filters() Calls 'bbp_get_forum_topics_link' with the
 *                        topics link and forum id
 */
function bbp_get_forum_topics_link( $forum_id = 0 ) {
	$forum    = bbp_get_forum( $forum_id );
	$forum_id = $forum->ID;
	$topics   = sprintf( _n( '%s discussion', '%s discussions', bbp_get_forum_topic_count( $forum_id, true, false ), 'buddyboss' ), bbp_get_forum_topic_count( $forum_id ) );
	$retval   = '';
	$link     = bbp_get_forum_permalink( $forum_id );

	// First link never has view=all
	if ( bbp_get_view_all( 'edit_others_topics' ) ) {
		$retval .= "<a href='" . esc_url( bbp_remove_view_all( $link ) ) . "'>" . esc_html( $topics ) . '</a>';
	} else {
		$retval .= esc_html( $topics );
	}

	// Get deleted topics
	$deleted_int = bbp_get_forum_topic_count_hidden( $forum_id, false, true );

	// This forum has hidden topics
	if ( ! empty( $deleted_int ) && current_user_can( 'edit_others_topics' ) ) {

		// Hidden text.
		$deleted_num = bbp_get_forum_topic_count_hidden( $forum_id, false, false );
		$extra       = ' ' . sprintf( _n( '(+%s hidden)', '(+%s hidden)', $deleted_int, 'buddyboss' ), $deleted_num );

		// Hidden link.
		$retval .= ! bbp_get_view_all( 'edit_others_topics' )
			? " <a href='" . esc_url( bbp_add_view_all( $link, true ) ) . "'>" . esc_html( $extra ) . "</a>"
			: " {$extra}";
	}

	return apply_filters( 'bbp_get_forum_topics_link', $retval, $forum_id );
}

/**
 * Output total sub-forum count of a forum
 *
 * @since bbPress (r2464)
 *
 * @param int     $forum_id Optional. Forum id to check
 * @param boolean $integer  Optional. Whether or not to format the result
 *
 * @uses  bbp_get_forum_subforum_count() To get the forum's subforum count
 */
function bbp_forum_subforum_count( $forum_id = 0, $integer = false ) {
	echo bbp_get_forum_subforum_count( $forum_id, $integer );
}

/**
 * Return total subforum count of a forum
 *
 * @since                 bbPress (r2464)
 *
 * @param int     $forum_id Optional. Forum id
 * @param boolean $integer  Optional. Whether or not to format the result
 *
 * @return int Forum's subforum count
 * @uses                  get_post_meta() To get the subforum count
 * @uses                  apply_filters() Calls 'bbp_get_forum_subforum_count' with the
 *                        subforum count and forum id
 * @uses                  bbp_get_forum_id() To get the forum id
 */
function bbp_get_forum_subforum_count( $forum_id = 0, $integer = false ) {
	$forum_id    = bbp_get_forum_id( $forum_id );
	$forum_count = (int) get_post_meta( $forum_id, '_bbp_forum_subforum_count', true );
	$filter      = ( true === $integer ) ? 'bbp_get_forum_subforum_count_int' : 'bbp_get_forum_subforum_count';

	return apply_filters( $filter, $forum_count, $forum_id );
}

/**
 * Output total topic count of a forum
 *
 * @since bbPress (r2464)
 *
 * @param int     $forum_id    Optional. Forum id
 * @param bool    $total_count Optional. To get the total count or normal count?
 * @param boolean $integer     Optional. Whether or not to format the result
 *
 * @uses  bbp_get_forum_topic_count() To get the forum topic count
 */
function bbp_forum_topic_count( $forum_id = 0, $total_count = true, $integer = false ) {
	echo bbp_get_forum_topic_count( $forum_id, $total_count, $integer );
}

/**
 * Return total topic count of a forum
 *
 * @since                 bbPress (r2464)
 *
 * @param int     $forum_id     Optional. Forum id
 * @param bool    $total_count  Optional. To get the total count or normal
 *                              count? Defaults to total.
 * @param boolean $integer      Optional. Whether or not to format the result
 *
 * @return int Forum topic count
 * @uses                  get_post_meta() To get the forum topic count
 * @uses                  apply_filters() Calls 'bbp_get_forum_topic_count' with the
 *                        topic count and forum id
 * @uses                  bbp_get_forum_id() To get the forum id
 */
function bbp_get_forum_topic_count( $forum_id = 0, $total_count = true, $integer = false ) {
	$forum_id = bbp_get_forum_id( $forum_id );
	$meta_key = empty( $total_count ) ? '_bbp_topic_count' : '_bbp_total_topic_count';
	$topics   = (int) get_post_meta( $forum_id, $meta_key, true );
	$filter   = ( true === $integer ) ? 'bbp_get_forum_topic_count_int' : 'bbp_get_forum_topic_count';

	return apply_filters( $filter, $topics, $forum_id );
}

/**
 * Output total reply count of a forum
 *
 * @since bbPress (r2464)
 *
 * @param int     $forum_id    Optional. Forum id
 * @param bool    $total_count Optional. To get the total count or normal count?
 * @param boolean $integer     Optional. Whether or not to format the result
 *
 * @uses  bbp_get_forum_reply_count() To get the forum reply count
 */
function bbp_forum_reply_count( $forum_id = 0, $total_count = true, $integer = false ) {
	echo bbp_get_forum_reply_count( $forum_id, $total_count, $integer );
}

/**
 * Return total post count of a forum
 *
 * @since                 bbPress (r2464)
 *
 * @param int     $forum_id     Optional. Forum id
 * @param bool    $total_count  Optional. To get the total count or normal
 *                              count?
 * @param boolean $integer      Optional. Whether or not to format the result
 *
 * @return int Forum reply count
 * @uses                  get_post_meta() To get the forum reply count
 * @uses                  apply_filters() Calls 'bbp_get_forum_reply_count' with the
 *                        reply count and forum id
 * @uses                  bbp_get_forum_id() To get the forum id
 */
function bbp_get_forum_reply_count( $forum_id = 0, $total_count = true, $integer = false ) {
	$forum_id = bbp_get_forum_id( $forum_id );
	$meta_key = empty( $total_count ) ? '_bbp_reply_count' : '_bbp_total_reply_count';
	$replies  = (int) get_post_meta( $forum_id, $meta_key, true );
	$filter   = ( true === $integer ) ? 'bbp_get_forum_reply_count_int' : 'bbp_get_forum_reply_count';

	return apply_filters( $filter, $replies, $forum_id );
}

/**
 * Output total post count of a forum
 *
 * @since bbPress (r2954)
 *
 * @param int     $forum_id    Optional. Forum id
 * @param bool    $total_count Optional. To get the total count or normal count?
 * @param boolean $integer     Optional. Whether or not to format the result
 *
 * @uses  bbp_get_forum_post_count() To get the forum post count
 */
function bbp_forum_post_count( $forum_id = 0, $total_count = true, $integer = false ) {
	echo bbp_get_forum_post_count( $forum_id, $total_count, $integer );
}

/**
 * Return total post count of a forum
 *
 * @since                 bbPress (r2954)
 *
 * @param int     $forum_id     Optional. Forum id
 * @param bool    $total_count  Optional. To get the total count or normal
 *                              count?
 * @param boolean $integer      Optional. Whether or not to format the result
 *
 * @return int Forum post count
 * @uses get_post_meta() To get the forum post count
 * @uses apply_filters() Calls 'bbp_get_forum_post_count' with the post count and forum id
 * @uses bbp_get_forum_id() To get the forum id
 * @uses bbp_get_forum_topic_count() To get the reply count
 * @uses bbp_get_forum_reply_count() To get the reply count
 */
function bbp_get_forum_post_count( $forum_id = 0, $total_count = true, $integer = false ) {
	$forum_id = bbp_get_forum_id( $forum_id );
	$topics   = bbp_get_forum_topic_count( $forum_id, $total_count, true );
	$replies  = bbp_get_forum_reply_count( $forum_id, $total_count, true );
	$retval   = $replies + $topics;
	$filter   = ( true === $integer ) ? 'bbp_get_forum_post_count_int' : 'bbp_get_forum_post_count';

	return apply_filters( $filter, $retval, $forum_id );
}

/**
 * Output total hidden topic count of a forum (hidden includes trashed and
 * spammed topics)
 *
 * @since bbPress (r2883)
 *
 * @param int     $forum_id Optional. Topic id
 * @param boolean $integer  Optional. Whether or not to format the result
 *
 * @uses  bbp_get_forum_topic_count_hidden() To get the forum hidden topic count
 */
function bbp_forum_topic_count_hidden( $forum_id = 0, $integer = false ) {
	echo bbp_get_forum_topic_count_hidden( $forum_id, $integer );
}

/**
 * Return total hidden topic count of a forum (hidden includes trashed
 * and spammed topics)
 *
 * @since                 bbPress (r2883)
 *
 * @param int     $forum_id Optional. Topic id
 * @param boolean $integer  Optional. Whether or not to format the result
 *
 * @return int Topic hidden topic count
 * @uses                  get_post_meta() To get the hidden topic count
 * @uses                  apply_filters() Calls 'bbp_get_forum_topic_count_hidden' with
 *                        the hidden topic count and forum id
 * @uses                  bbp_get_forum_id() To get the forum id
 */
function bbp_get_forum_topic_count_hidden( $forum_id = 0, $integer = false ) {
	$forum_id = bbp_get_forum_id( $forum_id );
	$topics   = (int) get_post_meta( $forum_id, '_bbp_topic_count_hidden', true );
	$filter   = ( true === $integer ) ? 'bbp_get_forum_topic_count_hidden_int' : 'bbp_get_forum_topic_count_hidden';

	return apply_filters( $filter, $topics, $forum_id );
}

/**
 * Output the status of the forum
 *
 * @since bbPress (r2667)
 *
 * @param int $forum_id Optional. Forum id
 *
 * @uses  bbp_get_forum_status() To get the forum status
 */
function bbp_forum_status( $forum_id = 0 ) {
	echo bbp_get_forum_status( $forum_id );
}

/**
 * Return the status of the forum
 *
 * @since                 bbPress (r2667)
 *
 * @param int $forum_id Optional. Forum id
 *
 * @return string Status of forum
 * @uses                  get_post_status() To get the forum's status
 * @uses                  apply_filters() Calls 'bbp_get_forum_status' with the status
 *                        and forum id
 * @uses                  bbp_get_forum_id() To get the forum id
 */
function bbp_get_forum_status( $forum_id = 0 ) {
	$forum_id = bbp_get_forum_id( $forum_id );
	$status   = get_post_meta( $forum_id, '_bbp_status', true );
	if ( empty( $status ) ) {
		$status = 'open';
	}

	return apply_filters( 'bbp_get_forum_status', $status, $forum_id );
}

/**
 * Output the visibility of the forum
 *
 * @since bbPress (r2997)
 *
 * @param int $forum_id Optional. Forum id
 *
 * @uses  bbp_get_forum_visibility() To get the forum visibility
 */
function bbp_forum_visibility( $forum_id = 0 ) {
	echo bbp_get_forum_visibility( $forum_id );
}

/**
 * Return the visibility of the forum
 *
 * @since                 bbPress (r2997)
 *
 * @param int $forum_id Optional. Forum id
 *
 * @return string Status of forum
 * @uses                  get_post_visibility() To get the forum's visibility
 * @uses                  apply_filters() Calls 'bbp_get_forum_visibility' with the visibility
 *                        and forum id
 * @uses                  bbp_get_forum_id() To get the forum id
 */
function bbp_get_forum_visibility( $forum_id = 0 ) {
	$forum_id = bbp_get_forum_id( $forum_id );

	return apply_filters( 'bbp_get_forum_visibility', get_post_status( $forum_id ), $forum_id );
}

/**
 * Output the type of the forum
 *
 * @since bbPress (r3563)
 *
 * @param int $forum_id Optional. Forum id
 *
 * @uses  bbp_get_forum_type() To get the forum type
 */
function bbp_forum_type( $forum_id = 0 ) {
	echo bbp_get_forum_type( $forum_id );
}

/**
 * Return the type of forum (category/forum/etc...)
 *
 * @since bbPress (r3563)
 *
 * @param int $forum_id Optional. Forum id
 *
 * @return bool Whether the forum is a category or not
 * @uses  get_post_meta() To get the forum category meta
 */
function bbp_get_forum_type( $forum_id = 0 ) {
	$forum_id = bbp_get_forum_id( $forum_id );
	$retval   = get_post_meta( $forum_id, '_bbp_forum_type', true );
	if ( empty( $retval ) ) {
		$retval = 'forum';
	}

	return apply_filters( 'bbp_get_forum_type', $retval, $forum_id );
}

/**
 * Is the forum a category?
 *
 * @since bbPress (r2746)
 *
 * @param int $forum_id Optional. Forum id
 *
 * @return bool Whether the forum is a category or not
 * @uses  bbp_get_forum_type() To get the forum type
 */
function bbp_is_forum_category( $forum_id = 0 ) {
	$forum_id = bbp_get_forum_id( $forum_id );
	$type     = bbp_get_forum_type( $forum_id );
	$retval   = ( ! empty( $type ) && 'category' === $type );

	return (bool) apply_filters( 'bbp_is_forum_category', (bool) $retval, $forum_id );
}

/**
 * Is the forum open?
 *
 * @since bbPress (r2746)
 *
 * @param int $forum_id Optional. Forum id
 *
 * @param int $forum_id Optional. Forum id
 *
 * @return bool Whether the forum is open or not
 * @uses  bbp_is_forum_closed() To check if the forum is closed or not
 */
function bbp_is_forum_open( $forum_id = 0 ) {
	return ! bbp_is_forum_closed( $forum_id );
}

/**
 * Is the forum closed?
 *
 * @since bbPress (r2746)
 *
 * @param int  $forum_id         Optional. Forum id
 * @param bool $check_ancestors  Check if the ancestors are closed (only
 *                               if they're a category)
 *
 * @return bool True if closed, false if not
 * @uses  bbp_get_forum_ancestors() To get the forum ancestors
 * @uses  bbp_is_forum_category() To check if the forum is a category
 * @uses  bbp_is_forum_closed() To check if the forum is closed
 * @uses  bbp_get_forum_status() To get the forum status
 */
function bbp_is_forum_closed( $forum_id = 0, $check_ancestors = true ) {

	$forum_id = bbp_get_forum_id( $forum_id );
	$retval   = ( bbp_get_closed_status_id() === bbp_get_forum_status( $forum_id ) );

	if ( ! empty( $check_ancestors ) ) {
		$ancestors = bbp_get_forum_ancestors( $forum_id );

		foreach ( (array) $ancestors as $ancestor ) {
			if ( bbp_is_forum_category( $ancestor, false ) && bbp_is_forum_closed( $ancestor, false ) ) {
				$retval = true;
			}
		}
	}

	return (bool) apply_filters( 'bbp_is_forum_closed', (bool) $retval, $forum_id, $check_ancestors );
}

/**
 * Is the forum public?
 *
 * @since bbPress (r2997)
 *
 * @param int  $forum_id         Optional. Forum id
 * @param bool $check_ancestors  Check if the ancestors are public (only if
 *                               they're a category)
 *
 * @return bool True if closed, false if not
 * @uses  bbp_get_forum_ancestors() To get the forum ancestors
 * @uses  bbp_is_forum_category() To check if the forum is a category
 * @uses  bbp_is_forum_closed() To check if the forum is closed
 * @uses  get_post_meta() To get the forum public meta
 */
function bbp_is_forum_public( $forum_id = 0, $check_ancestors = true ) {

	$forum_id   = bbp_get_forum_id( $forum_id );
	$visibility = bbp_get_forum_visibility( $forum_id );

	// If post status is public, return true
	$retval = ( bbp_get_public_status_id() === $visibility );

	// Check ancestors and inherit their privacy setting for display
	if ( ! empty( $check_ancestors ) ) {
		$ancestors = bbp_get_forum_ancestors( $forum_id );

		foreach ( (array) $ancestors as $ancestor ) {
			if ( bbp_is_forum( $ancestor ) && bbp_is_forum_public( $ancestor, false ) ) {
				$retval = true;
			}
		}
	}

	return (bool) apply_filters( 'bbp_is_forum_public', (bool) $retval, $forum_id, $check_ancestors );
}

/**
 * Is the forum private?
 *
 * @since bbPress (r2746)
 *
 * @param int  $forum_id         Optional. Forum id
 * @param bool $check_ancestors  Check if the ancestors are private (only if
 *                               they're a category)
 *
 * @return bool True if closed, false if not
 * @uses  bbp_get_forum_ancestors() To get the forum ancestors
 * @uses  bbp_is_forum_category() To check if the forum is a category
 * @uses  bbp_is_forum_closed() To check if the forum is closed
 * @uses  get_post_meta() To get the forum private meta
 */
function bbp_is_forum_private( $forum_id = 0, $check_ancestors = true ) {

	$forum_id   = bbp_get_forum_id( $forum_id );
	$visibility = bbp_get_forum_visibility( $forum_id );

	// If post status is private, return true
	$retval = ( bbp_get_private_status_id() === $visibility );

	// Check ancestors and inherit their privacy setting for display
	if ( ! empty( $check_ancestors ) ) {
		$ancestors = bbp_get_forum_ancestors( $forum_id );

		foreach ( (array) $ancestors as $ancestor ) {
			if ( bbp_is_forum( $ancestor ) && bbp_is_forum_private( $ancestor, false ) ) {
				$retval = true;
			}
		}
	}

	return (bool) apply_filters( 'bbp_is_forum_private', (bool) $retval, $forum_id, $check_ancestors );
}

/**
 * Is the forum hidden?
 *
 * @since bbPress (r2997)
 *
 * @param int  $forum_id         Optional. Forum id
 * @param bool $check_ancestors  Check if the ancestors are private (only if
 *                               they're a category)
 *
 * @return bool True if closed, false if not
 * @uses  bbp_get_forum_ancestors() To get the forum ancestors
 * @uses  bbp_is_forum_category() To check if the forum is a category
 * @uses  bbp_is_forum_closed() To check if the forum is closed
 * @uses  get_post_meta() To get the forum private meta
 */
function bbp_is_forum_hidden( $forum_id = 0, $check_ancestors = true ) {

	$forum_id   = bbp_get_forum_id( $forum_id );
	$visibility = bbp_get_forum_visibility( $forum_id );

	// If post status is private, return true
	$retval = ( bbp_get_hidden_status_id() === $visibility );

	// Check ancestors and inherit their privacy setting for display
	if ( ! empty( $check_ancestors ) ) {
		$ancestors = bbp_get_forum_ancestors( $forum_id );

		foreach ( (array) $ancestors as $ancestor ) {
			if ( bbp_is_forum( $ancestor ) && bbp_is_forum_hidden( $ancestor, false ) ) {
				$retval = true;
			}
		}
	}

	return (bool) apply_filters( 'bbp_is_forum_hidden', (bool) $retval, $forum_id, $check_ancestors );
}

/**
 * Output the author of the forum
 *
 * @since bbPress (r3675)
 *
 * @param int $forum_id Optional. Forum id
 *
 * @uses  bbp_get_forum_author() To get the forum author
 */
function bbp_forum_author_display_name( $forum_id = 0 ) {
	echo bbp_get_forum_author_display_name( $forum_id );
}

/**
 * Return the author of the forum
 *
 * @since                 bbPress (r3675)
 *
 * @param int $forum_id Optional. Forum id
 *
 * @return string Author of forum
 * @uses                  bbp_get_forum_author_id() To get the forum author id
 * @uses                  get_the_author_meta() To get the display name of the author
 * @uses                  apply_filters() Calls 'bbp_get_forum_author' with the author
 *                        and forum id
 * @uses                  bbp_get_forum_id() To get the forum id
 */
function bbp_get_forum_author_display_name( $forum_id = 0 ) {
	$forum_id = bbp_get_forum_id( $forum_id );
	$author   = get_the_author_meta( 'display_name', bbp_get_forum_author_id( $forum_id ) );

	return apply_filters( 'bbp_get_forum_author_display_name', $author, $forum_id );
}

/**
 * Output the author ID of the forum
 *
 * @since bbPress (r3675)
 *
 * @param int $forum_id Optional. Forum id
 *
 * @uses  bbp_get_forum_author_id() To get the forum author id
 */
function bbp_forum_author_id( $forum_id = 0 ) {
	echo bbp_get_forum_author_id( $forum_id );
}

/**
 * Return the author ID of the forum
 *
 * @since                 bbPress (r3675)
 *
 * @param int $forum_id Optional. Forum id
 *
 * @return string Author of forum
 * @uses                  get_post_field() To get the forum author id
 * @uses                  apply_filters() Calls 'bbp_get_forum_author_id' with the author
 *                        id and forum id
 * @uses                  bbp_get_forum_id() To get the forum id
 */
function bbp_get_forum_author_id( $forum_id = 0 ) {
	$forum_id  = bbp_get_forum_id( $forum_id );
	$author_id = get_post_field( 'post_author', $forum_id );

	return (int) apply_filters( 'bbp_get_forum_author_id', (int) $author_id, $forum_id );
}

/**
 * Replace forum meta details for users that cannot view them.
 *
 * @since bbPress (r3162)
 *
 * @param string $retval
 * @param int    $forum_id
 *
 * @return string
 * @uses  current_user_can()
 *
 * @uses  bbp_is_forum_private()
 */
function bbp_suppress_private_forum_meta( $retval, $forum_id ) {
	if ( bbp_is_forum_private( $forum_id, false ) && ! current_user_can( 'read_private_forums' ) ) {
		$retval = '-';
	}

	return apply_filters( 'bbp_suppress_private_forum_meta', $retval );
}

/**
 * Replace forum author details for users that cannot view them.
 *
 * @since bbPress (r3162)
 *
 * @param string $retval
 * @param int    $forum_id
 *
 * @return string
 * @uses  bbp_is_forum_private()
 * @uses  get_post_field()
 * @uses  bbp_get_topic_post_type()
 * @uses  bbp_is_forum_private()
 * @uses  bbp_get_topic_forum_id()
 * @uses  bbp_get_reply_post_type()
 * @uses  bbp_get_reply_forum_id()
 *
 */
function bbp_suppress_private_author_link( $author_link, $args ) {

	// Assume the author link is the return value
	$retval = $author_link;

	// Show the normal author link
	if ( ! empty( $args['post_id'] ) && ! current_user_can( 'read_private_forums' ) ) {

		// What post type are we looking at?
		$post_type = get_post_field( 'post_type', $args['post_id'] );

		switch ( $post_type ) {

			// Topic
			case bbp_get_topic_post_type():
				if ( bbp_is_forum_private( bbp_get_topic_forum_id( $args['post_id'] ) ) ) {
					$retval = '';
				}

				break;

			// Reply
			case bbp_get_reply_post_type():
				if ( bbp_is_forum_private( bbp_get_reply_forum_id( $args['post_id'] ) ) ) {
					$retval = '';
				}

				break;

			// Post
			default:
				if ( bbp_is_forum_private( $args['post_id'] ) ) {
					$retval = '';
				}

				break;
		}
	}

	return apply_filters( 'bbp_suppress_private_author_link', $retval );
}

/**
 * Output the row class of a forum
 *
 * @since bbPress (r2667)
 *
 * @param int $forum_id Optional. Forum ID.
 * @param array Extra classes you can pass when calling this function
 *
 * @uses  bbp_get_forum_class() To get the row class of the forum
 */
function bbp_forum_class( $forum_id = 0, $classes = array() ) {
	echo bbp_get_forum_class( $forum_id, $classes );
}

/**
 * Return the row class of a forum
 *
 * @since bbPress (r2667)
 *
 * @param int $forum_id Optional. Forum ID
 * @param array Extra classes you can pass when calling this function
 *
 * @return string Row class of the forum
 * @uses  bbp_get_forum_id() To validate the forum id
 * @uses  bbp_is_forum_category() To see if forum is a category
 * @uses  bbp_get_forum_status() To get the forum status
 * @uses  bbp_get_forum_visibility() To get the forum visibility
 * @uses  bbp_get_forum_parent_id() To get the forum parent id
 * @uses  get_post_class() To get all the classes including ours
 * @uses  apply_filters() Calls 'bbp_get_forum_class' with the classes
 */
function bbp_get_forum_class( $forum_id = 0, $classes = array() ) {
	$bbp      = bbpress();
	$forum_id = bbp_get_forum_id( $forum_id );
	$count    = isset( $bbp->forum_query->current_post ) ? $bbp->forum_query->current_post : 1;
	$classes  = (array) $classes;

	// Get some classes
	$classes[] = 'loop-item-' . $count;
	$classes[] = ( (int) $count % 2 ) ? 'even' : 'odd';
	$classes[] = bbp_is_forum_category( $forum_id ) ? 'status-category' : '';
	$classes[] = bbp_get_forum_subforum_count( $forum_id ) ? 'bbp-has-subforums' : '';
	$classes[] = bbp_get_forum_parent_id( $forum_id ) ? 'bbp-parent-forum-' . bbp_get_forum_parent_id( $forum_id ) : '';
	$classes[] = 'bbp-forum-status-' . bbp_get_forum_status( $forum_id );
	$classes[] = 'bbp-forum-visibility-' . bbp_get_forum_visibility( $forum_id );

	// Ditch the empties
	$classes = array_filter( $classes );
	$classes = get_post_class( $classes, $forum_id );

	// Filter the results
	$classes = apply_filters( 'bbp_get_forum_class', $classes, $forum_id );
	$retval  = 'class="' . implode( ' ', $classes ) . '"';

	return $retval;
}

/** Forms *********************************************************************/

/**
 * Output the value of forum title field
 *
 * @since bbPress (r3551)
 *
 * @uses  bbp_get_form_forum_title() To get the value of forum title field
 */
function bbp_form_forum_title() {
	echo bbp_get_form_forum_title();
}

/**
 * Return the value of forum title field
 *
 * @since bbPress (r3551)
 *
 * @return string Value of forum title field
 * @uses  apply_filters() Calls 'bbp_get_form_forum_title' with the title
 * @uses  bbp_is_forum_edit() To check if it's forum edit page
 */
function bbp_get_form_forum_title() {

	// Get _POST data
	if ( bbp_is_post_request() && isset( $_POST['bbp_forum_title'] ) ) {
		$forum_title = $_POST['bbp_forum_title'];

		// Get edit data
	} elseif ( bbp_is_forum_edit() ) {
		$forum_title = bbp_get_global_post_field( 'post_title', 'raw' );

		// No data
	} else {
		$forum_title = '';
	}

	return apply_filters( 'bbp_get_form_forum_title', esc_attr( $forum_title ) );
}

/**
 * Output the value of forum content field
 *
 * @since bbPress (r3551)
 *
 * @uses  bbp_get_form_forum_content() To get value of forum content field
 */
function bbp_form_forum_content() {
	echo bbp_get_form_forum_content();
}

/**
 * Return the value of forum content field
 *
 * @since bbPress (r3551)
 *
 * @return string Value of forum content field
 * @uses  apply_filters() Calls 'bbp_get_form_forum_content' with the content
 * @uses  bbp_is_forum_edit() To check if it's the forum edit page
 */
function bbp_get_form_forum_content() {

	// Get _POST data
	if ( bbp_is_post_request() && isset( $_POST['bbp_forum_content'] ) ) {
		$forum_content = stripslashes( $_POST['bbp_forum_content'] );

		// Get edit data
	} elseif ( bbp_is_forum_edit() ) {
		$forum_content = bbp_get_global_post_field( 'post_content', 'raw' );

		// No data
	} else {
		$forum_content = '';
	}

	return apply_filters( 'bbp_get_form_forum_content', $forum_content );
}

/**
 * Output value of forum parent
 *
 * @since bbPress (r3551)
 *
 * @uses  bbp_get_form_forum_parent() To get the topic's forum id
 */
function bbp_form_forum_parent() {
	echo bbp_get_form_forum_parent();
}

/**
 * Return value of forum parent
 *
 * @since bbPress (r3551)
 *
 * @return string Value of topic content field
 * @uses  bbp_get_forum_parent_id() To get the topic forum id
 * @uses  apply_filters() Calls 'bbp_get_form_forum_parent' with the forum
 * @uses  bbp_is_topic_edit() To check if it's the topic edit page
 */
function bbp_get_form_forum_parent() {

	// Get _POST data
	if ( bbp_is_post_request() && isset( $_POST['bbp_forum_id'] ) ) {
		$forum_parent = $_POST['bbp_forum_id'];

		// Get edit data
	} elseif ( bbp_is_forum_edit() ) {
		$forum_parent = bbp_get_forum_parent_id();

		// No data
	} else {
		$forum_parent = 0;
	}

	return apply_filters( 'bbp_get_form_forum_parent', esc_attr( $forum_parent ) );
}

/**
 * Output value of forum type
 *
 * @since bbPress (r3563)
 *
 * @uses  bbp_get_form_forum_type() To get the topic's forum id
 */
function bbp_form_forum_type() {
	echo bbp_get_form_forum_type();
}

/**
 * Return value of forum type
 *
 * @since bbPress (r3563)
 *
 * @return string Value of topic content field
 * @uses  bbp_get_forum_type_id() To get the topic forum id
 * @uses  apply_filters() Calls 'bbp_get_form_forum_type' with the forum
 * @uses  bbp_is_topic_edit() To check if it's the topic edit page
 */
function bbp_get_form_forum_type() {

	// Get _POST data
	if ( bbp_is_post_request() && isset( $_POST['bbp_forum_type'] ) ) {
		$forum_type = $_POST['bbp_forum_type'];

		// Get edit data
	} elseif ( bbp_is_forum_edit() ) {
		$forum_type = bbp_get_forum_type();

		// No data
	} else {
		$forum_type = 'forum';
	}

	return apply_filters( 'bbp_get_form_forum_type', esc_attr( $forum_type ) );
}

/**
 * Output value of forum visibility
 *
 * @since bbPress (r3563)
 *
 * @uses  bbp_get_form_forum_visibility() To get the topic's forum id
 */
function bbp_form_forum_visibility() {
	echo bbp_get_form_forum_visibility();
}

/**
 * Return value of forum visibility
 *
 * @since bbPress (r3563)
 *
 * @return string Value of topic content field
 * @uses  bbp_get_forum_visibility_id() To get the topic forum id
 * @uses  apply_filters() Calls 'bbp_get_form_forum_visibility' with the forum
 * @uses  bbp_is_topic_edit() To check if it's the topic edit page
 */
function bbp_get_form_forum_visibility() {

	// Get _POST data
	if ( bbp_is_post_request() && isset( $_POST['bbp_forum_visibility'] ) ) {
		$forum_visibility = $_POST['bbp_forum_visibility'];

		// Get edit data
	} elseif ( bbp_is_forum_edit() ) {
		$forum_visibility = bbp_get_forum_visibility();

		// No data
	} else {
		$forum_visibility = bbpress()->public_status_id;
	}

	return apply_filters( 'bbp_get_form_forum_visibility', esc_attr( $forum_visibility ) );
}

/**
 * Output checked value of forum subscription
 *
 * @since bbPress (r5156)
 *
 * @uses  bbp_get_form_forum_subscribed() To get the subscribed checkbox value
 */
function bbp_form_forum_subscribed() {
	echo bbp_get_form_forum_subscribed();
}

/**
 * Return checked value of forum subscription
 *
 * @since                                   bbPress (r5156)
 *
 * @return string Checked value of forum subscription
 * @uses                                    bbp_get_global_post_field() To get current post author
 * @uses                                    bbp_get_current_user_id() To get the current user id
 * @uses                                    bbp_is_user_subscribed_to_forum() To check if the user is
 *                                          subscribed to the forum
 * @uses                                    apply_filters() Calls 'bbp_get_form_forum_subscribed' with the
 *                                          option
 * @uses                                    bbp_is_forum_edit() To check if it's the forum edit page
 */
function bbp_get_form_forum_subscribed() {

	// Default value.
	$forum_subscribed = false;

	// Get _POST data.
	if ( bbp_is_post_request() && isset( $_POST['bbp_forum_subscription'] ) ) {
		$forum_subscribed = (bool) $_POST['bbp_forum_subscription'];

		// Get edit data.
	} elseif ( bbp_is_forum_edit() || bbp_is_reply_edit() ) {

		// Get current posts author.
		$post_author      = (int) bbp_get_global_post_field( 'post_author', 'raw' );
		$forum_subscribed = bbp_is_user_subscribed( $post_author, bbp_get_forum_id() );

		// Get current status.
	} elseif ( bbp_is_single_forum() ) {
		$forum_subscribed = bbp_is_user_subscribed( bbp_get_current_user_id(), bbp_get_forum_id() );
	}

	// Get checked output.
	$checked = checked( $forum_subscribed, true, false );

	return apply_filters( 'bbp_get_form_forum_subscribed', $checked, $forum_subscribed );
}

/** Form Dropdowns ************************************************************/

/**
 * Output value forum type dropdown
 *
 * @since bbPress (r3563)
 *
 * @param int $forum_id The forum id to use
 *
 * @uses  bbp_get_form_forum_type() To get the topic's forum id
 */
function bbp_form_forum_type_dropdown( $args = '' ) {
	echo bbp_get_form_forum_type_dropdown( $args );
}

/**
 * Return the forum type dropdown
 *
 * @since bbPress (r3563)
 *
 * @param int $forum_id The forum id to use
 *
 * @return string HTML select list for selecting forum type
 * @uses  bbp_get_forum_type() To get the forum type
 * @uses  apply_filters()
 * @uses  bbp_is_topic_edit() To check if it's the topic edit page
 */
function bbp_get_form_forum_type_dropdown( $args = '' ) {

	// Backpat for handling passing of a forum ID as integer
	if ( is_int( $args ) ) {
		$forum_id = (int) $args;
		$args     = array();
	} else {
		$forum_id = 0;
	}

	// Parse arguments against default values
	$r = bbp_parse_args(
		$args,
		array(
			'select_id' => 'bbp_forum_type',
			'tab'       => bbp_get_tab_index(),
			'forum_id'  => $forum_id,
			'selected'  => false,
		),
		'forum_type_select'
	);

	// No specific selected value passed
	if ( empty( $r['selected'] ) ) {

		// Post value is passed
		if ( bbp_is_post_request() && isset( $_POST[ $r['select_id'] ] ) ) {
			$r['selected'] = $_POST[ $r['select_id'] ];

			// No Post value was passed
		} else {

			// Edit topic
			if ( bbp_is_forum_edit() ) {
				$r['forum_id'] = bbp_get_forum_id( $r['forum_id'] );
				$r['selected'] = bbp_get_forum_type( $r['forum_id'] );

				// New topic
			} else {
				$r['selected'] = bbp_get_public_status_id();
			}
		}
	}

	// Used variables
	$tab        = ! empty( $r['tab'] ) ? ' tabindex="' . (int) $r['tab'] . '"' : '';
	$group_ids  = bbp_get_forum_group_ids( $r['forum_id'] );
	$can_update = empty( $group_ids ) ? true : false;

	// Start an output buffer, we'll finish it after the select loop
	ob_start(); ?>

	<select name="<?php echo esc_attr( $r['select_id'] ); ?>" id="<?php echo esc_attr( $r['select_id'] ); ?>_select"<?php echo esc_attr( $tab ); ?> <?php echo $can_update === false ? esc_attr( 'disabled="disabled"' ) : ''; ?>>

		<?php foreach ( bbp_get_forum_types( $r['forum_id'] ) as $key => $label ) : ?>
			<option value="<?php echo esc_attr( $key ); ?>"<?php selected( $key, $r['selected'] ); ?>><?php echo esc_html( $label ); ?></option>
		<?php endforeach; ?>

	</select>

	<?php

	// Return the results
	return apply_filters( 'bbp_get_form_forum_type_dropdown', ob_get_clean(), $r );
}

/**
 * Output value forum status dropdown
 *
 * @since bbPress (r3563)
 *
 * @param int $forum_id The forum id to use
 *
 * @uses  bbp_get_form_forum_status() To get the topic's forum id
 */
function bbp_form_forum_status_dropdown( $args = '' ) {
	echo bbp_get_form_forum_status_dropdown( $args );
}

/**
 * Return the forum status dropdown
 *
 * @since bbPress (r3563)
 *
 * @param int $forum_id The forum id to use
 *
 * @return string HTML select list for selecting forum status
 * @uses  bbp_get_forum_status() To get the forum status
 * @uses  apply_filters()
 * @uses  bbp_is_topic_edit() To check if it's the topic edit page
 */
function bbp_get_form_forum_status_dropdown( $args = '' ) {

	// Backpat for handling passing of a forum ID
	if ( is_int( $args ) ) {
		$forum_id = (int) $args;
		$args     = array();
	} else {
		$forum_id = 0;
	}

	// Parse arguments against default values
	$r = bbp_parse_args(
		$args,
		array(
			'select_id' => 'bbp_forum_status',
			'tab'       => bbp_get_tab_index(),
			'forum_id'  => $forum_id,
			'selected'  => false,
		),
		'forum_status_select'
	);

	// No specific selected value passed
	if ( empty( $r['selected'] ) ) {

		// Post value is passed
		if ( bbp_is_post_request() && isset( $_POST[ $r['select_id'] ] ) ) {
			$r['selected'] = $_POST[ $r['select_id'] ];

			// No Post value was passed
		} else {

			// Edit topic
			if ( bbp_is_forum_edit() ) {
				$r['forum_id'] = bbp_get_forum_id( $r['forum_id'] );
				$r['selected'] = bbp_get_forum_status( $r['forum_id'] );

				// New topic
			} else {
				$r['selected'] = bbp_get_public_status_id();
			}
		}
	}

	// Used variables
	$tab = ! empty( $r['tab'] ) ? ' tabindex="' . (int) $r['tab'] . '"' : '';

	// Start an output buffer, we'll finish it after the select loop
	ob_start();
	?>

	<select name="<?php echo esc_attr( $r['select_id'] ); ?>" id="<?php echo esc_attr( $r['select_id'] ); ?>_select"<?php echo $tab; ?>>

		<?php foreach ( bbp_get_forum_statuses( $r['forum_id'] ) as $key => $label ) : ?>
			<option value="<?php echo esc_attr( $key ); ?>"<?php selected( $key, $r['selected'] ); ?>><?php echo esc_html( $label ); ?></option>
		<?php endforeach; ?>

	</select>

	<?php

	// Return the results
	return apply_filters( 'bbp_get_form_forum_status_dropdown', ob_get_clean(), $r );
}

/**
 * Output value forum visibility dropdown
 *
 * @since bbPress (r3563)
 *
 * @param int $forum_id The forum id to use
 *
 * @uses  bbp_get_form_forum_visibility() To get the topic's forum id
 */
function bbp_form_forum_visibility_dropdown( $args = '' ) {
	echo bbp_get_form_forum_visibility_dropdown( $args );
}

/**
 * Return the forum visibility dropdown
 *
 * @since bbPress (r3563)
 *
 * @param int $forum_id The forum id to use
 *
 * @return string HTML select list for selecting forum visibility
 * @uses  bbp_get_forum_visibility() To get the forum visibility
 * @uses  apply_filters()
 * @uses  bbp_is_topic_edit() To check if it's the topic edit page
 */
function bbp_get_form_forum_visibility_dropdown( $args = '' ) {

	// Backpat for handling passing of a forum ID
	if ( is_int( $args ) ) {
		$forum_id = (int) $args;
		$args     = array();
	} else {
		$forum_id = 0;
	}

	// Parse arguments against default values
	$r = bbp_parse_args(
		$args,
		array(
			'select_id' => 'bbp_forum_visibility',
			'tab'       => bbp_get_tab_index(),
			'forum_id'  => $forum_id,
			'selected'  => false,
		),
		'forum_type_select'
	);

	// No specific selected value passed
	if ( empty( $r['selected'] ) ) {

		// Post value is passed
		if ( bbp_is_post_request() && isset( $_POST[ $r['select_id'] ] ) ) {
			$r['selected'] = $_POST[ $r['select_id'] ];

			// No Post value was passed
		} else {

			// Edit topic
			if ( bbp_is_forum_edit() ) {
				$r['forum_id'] = bbp_get_forum_id( $r['forum_id'] );
				$r['selected'] = bbp_get_forum_visibility( $r['forum_id'] );

				// New topic
			} else {
				$r['selected'] = bbp_get_public_status_id();
			}
		}
	}

	// Used variables
	$tab = ! empty( $r['tab'] ) ? ' tabindex="' . (int) $r['tab'] . '"' : '';

	// Get forum visibility update status.
	$disabled = bb_get_child_forum_group_ids( $r['forum_id'] );

	// Start an output buffer, we'll finish it after the select loop
	ob_start();
	?>

	<select name="<?php echo esc_attr( $r['select_id'] ); ?>" id="<?php echo esc_attr( $r['select_id'] ); ?>_select"<?php echo esc_attr( $tab ); ?> <?php echo $disabled ? esc_attr( 'disabled="disabled"' ) : ''; ?>>

		<?php foreach ( bbp_get_forum_visibilities( $r['forum_id'] ) as $key => $label ) : ?>
			<option value="<?php echo esc_attr( $key ); ?>"<?php selected( $key, $r['selected'] ); ?>><?php echo esc_html( $label ); ?></option>
		<?php endforeach; ?>

	</select>

	<?php

	// Return the results
	return apply_filters( 'bbp_get_form_forum_type_dropdown', ob_get_clean(), $r );
}

/** Feeds *********************************************************************/

/**
 * Output the link for the forum feed
 *
 * @since bbPress (r3172)
 *
 * @param type $forum_id Optional. Forum ID.
 *
 * @uses  bbp_get_forum_topics_feed_link()
 */
function bbp_forum_topics_feed_link( $forum_id = 0 ) {
	echo bbp_get_forum_topics_feed_link( $forum_id );
}

/**
 * Retrieve the link for the forum feed
 *
 * @since bbPress (r3172)
 *
 * @param int $forum_id Optional. Forum ID.
 *
 * @return string
 * @uses  bbp_get_forum_id()
 * @uses  get_option()
 * @uses  trailingslashit()
 * @uses  bbp_get_forum_permalink()
 * @uses  user_trailingslashit()
 * @uses  bbp_get_forum_post_type()
 * @uses  get_post_field()
 * @uses  apply_filters()
 *
 */
function bbp_get_forum_topics_feed_link( $forum_id = 0 ) {

	// Validate forum id
	$forum_id = bbp_get_forum_id( $forum_id );

	// Forum is valid
	if ( ! empty( $forum_id ) ) {

		// Define local variable(s)
		$link = '';

		// Pretty permalinks
		if ( get_option( 'permalink_structure' ) ) {

			// Forum link
			$url = trailingslashit( bbp_get_forum_permalink( $forum_id ) ) . 'feed';
			$url = user_trailingslashit( $url, 'single_feed' );

			// Unpretty permalinks
		} else {
			$url = home_url(
				add_query_arg(
					array(
						'feed'                    => 'rss2',
						bbp_get_forum_post_type() => get_post_field( 'post_name', $forum_id ),
					)
				)
			);
		}

		$link = '<a href="' . esc_url( $url ) . '" class="bbp-forum-rss-link topics"><span>' . esc_attr__( 'Discussions', 'buddyboss' ) . '</span></a>';
	}

	return apply_filters( 'bbp_get_forum_topics_feed_link', $link, $url, $forum_id );
}

/**
 * Output the link for the forum replies feed
 *
 * @since bbPress (r3172)
 *
 * @param type $forum_id Optional. Forum ID.
 *
 * @uses  bbp_get_forum_replies_feed_link()
 */
function bbp_forum_replies_feed_link( $forum_id = 0 ) {
	echo bbp_get_forum_replies_feed_link( $forum_id );
}

/**
 * Retrieve the link for the forum replies feed
 *
 * @since bbPress (r3172)
 *
 * @param int $forum_id Optional. Forum ID.
 *
 * @return string
 * @uses  bbp_get_forum_id()
 * @uses  get_option()
 * @uses  trailingslashit()
 * @uses  bbp_get_forum_permalink()
 * @uses  user_trailingslashit()
 * @uses  bbp_get_forum_post_type()
 * @uses  get_post_field()
 * @uses  apply_filters()
 *
 */
function bbp_get_forum_replies_feed_link( $forum_id = 0 ) {

	// Validate forum id
	$forum_id = bbp_get_forum_id( $forum_id );

	// Forum is valid
	if ( ! empty( $forum_id ) ) {

		// Define local variable(s)
		$link = '';

		// Pretty permalinks
		if ( get_option( 'permalink_structure' ) ) {

			// Forum link
			$url = trailingslashit( bbp_get_forum_permalink( $forum_id ) ) . 'feed';
			$url = user_trailingslashit( $url, 'single_feed' );
			$url = add_query_arg( array( 'type' => 'reply' ), $url );

			// Unpretty permalinks
		} else {
			$url = home_url(
				add_query_arg(
					array(
						'type'                    => 'reply',
						'feed'                    => 'rss2',
						bbp_get_forum_post_type() => get_post_field( 'post_name', $forum_id ),
					)
				)
			);
		}

		$link = '<a href="' . esc_url( $url ) . '" class="bbp-forum-rss-link replies"><span>' . esc_html__( 'Replies', 'buddyboss' ) . '</span></a>';
	}

	return apply_filters( 'bbp_get_forum_replies_feed_link', $link, $url, $forum_id );
}

/**
 * Get group ID's for a child forum.
 *
 * @since BuddyBoss 1.7.8
 *
 * @param int $forum_id Forum id.
 *
 * @uses bbp_get_forum() Get forum.
 *
 * @return array
 */
function bb_get_child_forum_group_ids( $forum_id ) {
	if ( empty( $forum_id ) ) {
		return array();
	}

	$parents = get_post_ancestors( $forum_id );

	// Set the parameter forum_id in the parents array as its first element.
	array_unshift( $parents, $forum_id );

	if ( ! empty( $parents ) ) {
		foreach ( $parents as $parent ) {
			$group_ids = bbp_get_forum_group_ids( $parent );

			if ( ! empty( $group_ids ) ) {
				return $group_ids;
			}
		}
	}

	return array();
}

/**
 * Return the content of the forum
 *
 * @since BuddyBoss 2.2.1
 *
 * @param int $forum_id Optional. Topic id.
 *
 * @return string Content of the forum with more link.
 */
function bbp_get_forum_content_excerpt_view_more( $forum_id = 0 ) {
	global $template_forum_ids;
	$forum_id      = bbp_get_forum_id( $forum_id );
	$forum_content = bbp_get_forum_content( $forum_id );

	$template_forum_ids[] = $forum_id;

	$forum_link = '... <br/> <a href="#single-forum-description-popup-' . esc_attr( $forum_id ) . '" class="bb-more-link show-action-popup button outline">' . esc_html__( 'View more', 'buddyboss' ) . '</a>';

	return bp_create_excerpt( $forum_content, 250, array( 'ending' => $forum_link ) );
}

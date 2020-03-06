<?php
/**
 * BuddyBoss Document Template Functions.
 *
 * @package BuddyBoss\Document\Templates
 * @since BuddyBoss 1.2.5
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Output the document component slug.
 *
 * @since BuddyBoss 1.2.5
 */
function bp_document_slug() {
	echo bp_get_document_slug();
}
/**
 * Return the document component slug.
 *
 * @since BuddyBoss 1.2.5
 *
 * @return string
 */
function bp_get_document_slug() {

	/**
	 * Filters the document component slug.
	 *
	 * @since BuddyBoss 1.2.5
	 *
	 * @param string $slug Document component slug.
	 */
	return apply_filters( 'bp_get_document_slug', buddypress()->document->slug );
}

/**
 * Output the document component root slug.
 *
 * @since BuddyBoss 1.2.5
 */
function bp_document_root_slug() {
	echo bp_get_document_root_slug();
}
/**
 * Return the document component root slug.
 *
 * @since BuddyBoss 1.2.5
 *
 * @return string
 */
function bp_get_document_root_slug() {

	/**
	 * Filters the Document component root slug.
	 *
	 * @since BuddyBoss 1.2.5
	 *
	 * @param string $slug Document component root slug.
	 */
	return apply_filters( 'bp_get_document_root_slug', buddypress()->document->root_slug );
}

/**
 * Initialize the document loop.
 *
 * Based on the $args passed, bp_has_document() populates the
 * $document_template global, enabling the use of BuddyPress templates and
 * template functions to display a list of document items.
 *
 * @since BuddyBoss 1.2.5

 * @global object $document_template {@link BP_Document_Template}
 *
 * @param array|string $args {
 *     Arguments for limiting the contents of the document loop. Most arguments
 *     are in the same format as {@link BP_Document::get()}. However,
 *     because the format of the arguments accepted here differs in a number of
 *     ways, and because bp_has_document() determines some default arguments in
 *     a dynamic fashion, we list all accepted arguments here as well.
 *
 *     Arguments can be passed as an associative array, or as a URL querystring
 *     (eg, 'user_id=4&fields=all').
 *
 *     @type int               $page             Which page of results to fetch. Using page=1 without per_page will result
 *                                               in no pagination. Default: 1.
 *     @type int|bool          $per_page         Number of results per page. Default: 20.
 *     @type string            $page_arg         String used as a query parameter in pagination links. Default: 'acpage'.
 *     @type int|bool          $max              Maximum number of results to return. Default: false (unlimited).
 *     @type string            $fields           Document fields to retrieve. 'all' to fetch entire document objects,
 *                                               'ids' to get only the document IDs. Default 'all'.
 *     @type string|bool       $count_total      If true, an additional DB query is run to count the total document items
 *                                               for the query. Default: false.
 *     @type string            $sort             'ASC' or 'DESC'. Default: 'DESC'.
 *     @type array|bool        $exclude          Array of document IDs to exclude. Default: false.
 *     @type array|bool        $include          Array of exact document IDs to query. Providing an 'include' array will
 *                                               override all other filters passed in the argument array. When viewing the
 *                                               permalink page for a single document item, this value defaults to the ID of
 *                                               that item. Otherwise the default is false.
 *     @type string            $search_terms     Limit results by a search term. Default: false.
 *     @type string            $scope            Use a BuddyPress pre-built filter.
 *                                                 - 'friends' retrieves items belonging to the friends of a user.
 *                                                 - 'groups' retrieves items belonging to groups to which a user belongs to.
 *                                               defaults to false.
 *     @type int|array|bool    $user_id          The ID(s) of user(s) whose document should be fetched. Pass a single ID or
 *                                               an array of IDs. When viewing a user profile page, 'user_id' defaults to
 *                                               the ID of the displayed user. Otherwise the default is false.
 *     @type int|array|bool    $folder_id         The ID(s) of folder(s) whose document should be fetched. Pass a single ID or
 *                                               an array of IDs. When viewing a single folder page, 'folder_id' defaults to
 *                                               the ID of the displayed folder. Otherwise the default is false.
 *     @type int|array|bool    $group_id         The ID(s) of group(s) whose document should be fetched. Pass a single ID or
 *                                               an array of IDs. When viewing a single group page, 'group_id' defaults to
 *                                               the ID of the displayed group. Otherwise the default is false.
 *     @type array             $privacy          Limit results by privacy. Default: public | grouponly.
 * }
 * @return bool Returns true when document found, otherwise false.
 */
function bp_has_document( $args = '' ) {
	global $document_template;

	/*
	 * Smart Defaults.
	 */

	// User filtering.
	$user_id = bp_displayed_user_id() ? bp_displayed_user_id() : false;

	$search_terms_default = false;
	$search_query_arg     = bp_core_get_component_search_query_arg( 'document' );
	if ( ! empty( $_REQUEST[ $search_query_arg ] ) ) {
		$search_terms_default = stripslashes( $_REQUEST[ $search_query_arg ] );
	}

	// folder filtering
	if ( ! isset( $args['folder_id'] ) ) {
		$folder_id = bp_is_single_folder() ? (int) bp_action_variable( 0 ) : false;
		if ( bp_is_group_single() && bp_is_group_folders() ) {
			$folder_id = (int) bp_action_variable( 1 );
		}
	} else {
		$folder_id = $args['folder_id'];
	}

	$privacy = array( 'public' );
	if ( is_user_logged_in() ) {
		$privacy[] = 'loggedin';
		if ( bp_is_active( 'friends' ) ) {

			// get the login user id.
			$current_user_id = get_current_user_id();

			// check if the login user is friends of the display user
			$is_friend = friends_check_friendship( $current_user_id, $user_id );

			/**
			 * check if the login user is friends of the display user
			 * OR check if the login user and the display user is the same
			 */
			if ( $is_friend || ! empty( $current_user_id ) && $current_user_id == $user_id ) {
				$privacy[] = 'friends';
			}
		}

		if ( bp_is_my_profile() ) {
			$privacy[] = 'onlyme';
		}
	}

	$group_id = false;
	if ( bp_is_active( 'groups' ) && bp_is_group() ) {
		$privacy  = array( 'grouponly' );
		$group_id = bp_get_current_group_id();
		$user_id  = false;
	}

	// The default scope should recognize custom slugs.
	$scope = array();
	if ( bp_is_document_directory() ) {
		if ( bp_is_active( 'friends' ) ) {
			$scope[] = 'friends';
		}

		if ( bp_is_active( 'groups' ) ) {
			$scope[] = 'groups';
		}

		if ( is_user_logged_in() ) {
			$scope[] = 'personal';
		}
	}

	/*
	 * Parse Args.
	 */

	// Note: any params used for filtering can be a single value, or multiple
	// values comma separated.
	$r = bp_parse_args(
		$args,
		array(
			'include'      => false,           // Pass an document_id or string of IDs comma-separated.
			'exclude'      => false,           // Pass an activity_id or string of IDs comma-separated.
			'sort'         => 'DESC',          // Sort DESC or ASC.
			'order_by'     => false,           // Order by. Default: date_created
			'page'         => 1,               // Which page to load.
			'per_page'     => 20,              // Number of items per page.
			'page_arg'     => 'acpage',        // See https://buddypress.trac.wordpress.org/ticket/3679.
			'max'          => false,           // Max number to return.
			'fields'       => 'all',
			'count_total'  => false,

			// Scope - pre-built document filters for a user (friends/groups).
			'scope'        => $scope,

			// Filtering
			'user_id'      => $user_id,        // user_id to filter on.
			'folder_id'     => $folder_id,       // folder_id to filter on.
			'group_id'     => $group_id,       // group_id to filter on.
			'privacy'      => $privacy,        // privacy to filter on - public, onlyme, loggedin, friends, grouponly, message.
			'folder'        => true,        // privacy to filter on - public, onlyme, loggedin, friends, grouponly, message.
			'user_directory' => true,        // privacy to filter on - public, onlyme, loggedin, friends, grouponly, message.

		// Searching.
			'search_terms' => $search_terms_default,
		),
		'has_document'
	);

	/*
	 * Smart Overrides.
	 */

	// Search terms.
	if ( ! empty( $_REQUEST['s'] ) && empty( $r['search_terms'] ) ) {
		$r['search_terms'] = $_REQUEST['s'];
	}

	// Do not exceed the maximum per page.
	if ( ! empty( $r['max'] ) && ( (int) $r['per_page'] > (int) $r['max'] ) ) {
		$r['per_page'] = $r['max'];
	}

	/*
	 * Query
	 */

	$document_template = new BP_Document_Template( $r );

	/**
	 * Filters whether or not there are document items to display.
	 *
	 * @since BuddyBoss 1.2.5
	 *
	 * @param bool   $value               Whether or not there are document items to display.
	 * @param string $document_template      Current document template being used.
	 * @param array  $r                   Array of arguments passed into the BP_Document_Template class.
	 */
	return apply_filters( 'bp_has_document', $document_template->has_document(), $document_template, $r );
}

/**
 * Determine if there are still document left in the loop.
 *
 * @since BuddyBoss 1.2.5
 *
 * @global object $document_template {@link BP_Document_Template}
 *
 * @return bool Returns true when document are found.
 */
function bp_document() {
	global $document_template;
	return $document_template->user_documents();
}

/**
 * Get the current document object in the loop.
 *
 * @since BuddyBoss 1.2.5
 *
 * @global object $document_template {@link BP_Document_Template}
 *
 * @return object The current document within the loop.
 */
function bp_the_document() {
	global $document_template;
	return $document_template->the_document();
}

/**
 * Output the URL for the Load More link.
 *
 * @since BuddyPress 2.1.0
 */
function bp_document_load_more_link() {
	echo esc_url( bp_get_document_load_more_link() );
}
/**
 * Get the URL for the Load More link.
 *
 * @since BuddyPress 2.1.0
 *
 * @return string $link
 */
function bp_get_document_load_more_link() {
	global $document_template;

	$url  = bp_get_requested_url();
	$link = add_query_arg( $document_template->pag_arg, $document_template->pag_page + 1, $url );

	/**
	 * Filters the Load More link URL.
	 *
	 * @since BuddyPress 2.1.0
	 *
	 * @param string $link                The "Load More" link URL with appropriate query args.
	 * @param string $url                 The original URL.
	 * @param object $document_template The document template loop global.
	 */
	return apply_filters( 'bp_get_document_load_more_link', $link, $url, $document_template );
}

/**
 * Output the document pagination count.
 *
 * @since BuddyBoss 1.2.5
 *
 * @global object $document_template {@link BP_Document_Template}
 */
function bp_document_pagination_count() {
	echo bp_get_document_pagination_count();
}

/**
 * Return the document pagination count.
 *
 * @since BuddyBoss 1.2.5
 *
 * @global object $document_template {@link BP_Document_Template}
 *
 * @return string The pagination text.
 */
function bp_get_document_pagination_count() {
	global $document_template;

	$start_num = intval( ( $document_template->pag_page - 1 ) * $document_template->pag_num ) + 1;
	$from_num  = bp_core_number_format( $start_num );
	$to_num    = bp_core_number_format( ( $start_num + ( $document_template->pag_num - 1 ) > $document_template->total_document_count ) ? $document_template->total_document_count : $start_num + ( $document_template->pag_num - 1 ) );
	$total     = bp_core_number_format( $document_template->total_document_count );

	$message = sprintf( _n( 'Viewing 1 item', 'Viewing %1$s - %2$s of %3$s items', $document_template->total_document_count, 'buddyboss' ), $from_num, $to_num, $total );

	return $message;
}

/**
 * Output the document pagination links.
 *
 * @since BuddyBoss 1.2.5
 */
function bp_document_pagination_links() {
	echo bp_get_document_pagination_links();
}

/**
 * Return the document pagination links.
 *
 * @since BuddyBoss 1.2.5
 *
 * @global object $document_template {@link BP_Document_Template}
 *
 * @return string The pagination links.
 */
function bp_get_document_pagination_links() {
	global $document_template;

	/**
	 * Filters the document pagination link output.
	 *
	 * @since BuddyBoss 1.2.5
	 *
	 * @param string $pag_links Output for the document pagination links.
	 */
	return apply_filters( 'bp_get_document_pagination_links', $document_template->pag_links );
}

/**
 * Return true when there are more document items to be shown than currently appear.
 *
 * @since BuddyBoss 1.2.5
 *
 * @global object $document_template {@link BP_Document_Template}
 *
 * @return bool $has_more_items True if more items, false if not.
 */
function bp_document_has_more_items() {
	global $document_template;

	if ( ! empty( $document_template->has_more_items ) ) {
		$has_more_items = true;
	} else {
		$remaining_pages = 0;

		if ( ! empty( $document_template->pag_page ) ) {
			$remaining_pages = floor( ( $document_template->total_document_count - 1 ) / ( $document_template->pag_num * $document_template->pag_page ) );
		}

		$has_more_items = (int) $remaining_pages > 0;
	}

	/**
	 * Filters whether there are more document items to display.
	 *
	 * @since BuddyBoss 1.2.5
	 *
	 * @param bool $has_more_items Whether or not there are more document items to display.
	 */
	return apply_filters( 'bp_document_has_more_items', $has_more_items );
}

/**
 * Output the document count.
 *
 * @since BuddyBoss 1.2.5
 */
function bp_document_count() {
	echo bp_get_document_count();
}

/**
 * Return the document count.
 *
 * @since BuddyBoss 1.2.5
 *
 * @global object $document_template {@link BP_Document_Template}
 *
 * @return int The document count.
 */
function bp_get_document_count() {
	global $document_template;

	/**
	 * Filters the document count for the document template.
	 *
	 * @since BuddyBoss 1.2.5
	 *
	 * @param int $document_count The count for total document.
	 */
	return apply_filters( 'bp_get_document_count', (int) $document_template->document_count );
}

/**
 * Output the number of document per page.
 *
 * @since BuddyBoss 1.2.5
 */
function bp_document_per_page() {
	echo bp_get_document_per_page();
}

/**
 * Return the number of document per page.
 *
 * @since BuddyBoss 1.2.5
 *
 * @global object $document_template {@link BP_Document_Template}
 *
 * @return int The document per page.
 */
function bp_get_document_per_page() {
	global $document_template;

	/**
	 * Filters the document posts per page value.
	 *
	 * @since BuddyBoss 1.2.5
	 *
	 * @param int $pag_num How many post should be displayed for pagination.
	 */
	return apply_filters( 'bp_get_document_per_page', (int) $document_template->pag_num );
}

/**
 * Output the document ID.
 *
 * @since BuddyBoss 1.2.5
 */
function bp_document_id() {
	echo bp_get_document_id();
}

/**
 * Return the document ID.
 *
 * @since BuddyBoss 1.2.5
 *
 * @global object $document_template {@link BP_Document_Template}
 *
 * @return int The document ID.
 */
function bp_get_document_id() {
	global $document_template;

	/**
	 * Filters the document ID being displayed.
	 *
	 * @since BuddyBoss 1.2.5
	 *
	 * @param int $id The document ID.
	 */
	return apply_filters( 'bp_get_document_id', $document_template->document->id );
}

/**
 * Output the document blog id.
 *
 * @since BuddyBoss 1.2.5
 */
function bp_document_blog_id() {
	echo bp_get_document_blog_id();
}

/**
 * Return the document blog ID.
 *
 * @since BuddyBoss 1.2.5
 *
 * @global object $document_template {@link BP_Document_Template}
 *
 * @return int The document blog ID.
 */
function bp_get_document_blog_id() {
	global $document_template;

	/**
	 * Filters the document ID being displayed.
	 *
	 * @since BuddyBoss 1.2.5
	 *
	 * @param int $id The document blog ID.
	 */
	return apply_filters( 'bp_get_document_blog_id', $document_template->document->blog_id );
}

/**
 * Output the document user ID.
 *
 * @since BuddyBoss 1.2.5
 */
function bp_document_user_id() {
	echo bp_get_document_user_id();
}

/**
 * Return the document user ID.
 *
 * @since BuddyBoss 1.2.5
 *
 * @global object $document_template {@link BP_Document_Template}
 *
 * @return int The document user ID.
 */
function bp_get_document_user_id() {
	global $document_template;

	/**
	 * Filters the document ID being displayed.
	 *
	 * @since BuddyBoss 1.2.5
	 *
	 * @param int $id The document user ID.
	 */
	return apply_filters( 'bp_get_document_user_id', $document_template->document->user_id );
}

/**
 * Output the document attachment ID.
 *
 * @since BuddyBoss 1.2.5
 */
function bp_document_attachment_id() {
	echo bp_get_document_attachment_id();
}

/**
 * Return the document attachment ID.
 *
 * @since BuddyBoss 1.2.5
 *
 * @global object $document_template {@link BP_Document_Template}
 *
 * @return int The document attachment ID.
 */
function bp_get_document_attachment_id() {
	global $document_template;

	// Will get false if it's a folder.
	$attachment_id = false;

	/**
	 * Filters the document ID being displayed.
	 *
	 * @since BuddyBoss 1.2.5
	 *
	 * @param int $id The document attachment ID.
	 */

	if ( isset( $document_template->document->attachment_id ) ) {
		$attachment_id = $document_template->document->attachment_id;
	}

	return apply_filters( 'bp_get_document_attachment_id', $attachment_id );
}

/**
 * Output the document title.
 *
 * @since BuddyBoss 1.2.5
 */
function bp_document_title() {
	echo bp_get_document_title();
}

/**
 * Return the document title.
 *
 * @since BuddyBoss 1.2.5
 *
 * @global object $document_template {@link BP_Document_Template}
 *
 * @return int The document title.
 */
function bp_get_document_title() {
	global $document_template;

	/**
	 * Filters the document title being displayed.
	 *
	 * @since BuddyBoss 1.2.5
	 *
	 * @param int $id The document title.
	 */
	return apply_filters( 'bp_get_document_title', $document_template->document->title );
}

/**
 * Determine if the current user can delete an document item.
 *
 * @since BuddyBoss 1.2.5
 *
 * @param int|BP_Document $document BP_Document object or ID of the document
 * @return bool True if can delete, false otherwise.
 */
function bp_document_user_can_delete( $document = false ) {

	// Assume the user cannot delete the document item.
	$can_delete = false;

	if ( empty( $document ) ) {
		return $can_delete;
	}

	if ( ! is_object( $document ) ) {
		$document = new BP_Document( $document );
	}

	if ( empty( $document ) ) {
		return $can_delete;
	}

	// Only logged in users can delete document.
	if ( is_user_logged_in() ) {

		// Community moderators can always delete document (at least for now).
		if ( bp_current_user_can( 'bp_moderate' ) ) {
			$can_delete = true;
		}

		// Users are allowed to delete their own document.
		if ( isset( $document->user_id ) && ( $document->user_id === bp_loggedin_user_id() ) ) {
			$can_delete = true;
		}
	}

	/**
	 * Filters whether the current user can delete an document item.
	 *
	 * @since BuddyBoss 1.2.5
	 *
	 * @param bool   $can_delete Whether the user can delete the item.
	 * @param object $document   Current document item object.
	 */
	return (bool) apply_filters( 'bp_document_user_can_delete', $can_delete, $document );
}

/**
 * Output the document folder ID.
 *
 * @since BuddyBoss 1.2.5
 */
function bp_document_folder_id() {
	echo bp_get_document_folder_id();
}

/**
 * Return the document folder ID.
 *
 * @since BuddyBoss 1.2.5
 *
 * @global object $document_template {@link BP_Document_Template}
 *
 * @return int The document folder ID.
 */
function bp_get_document_folder_id() {
	global $document_template;

	/**
	 * Filters the document folder ID being displayed.
	 *
	 * @since BuddyBoss 1.2.5
	 *
	 * @param int $id The document folder ID.
	 */
	return apply_filters( 'bp_get_document_folder_id', $document_template->document->album_id );
}

/**
 * Output the document activity ID.
 *
 * @since BuddyBoss 1.2.5
 */
function bp_document_activity_id() {
	echo bp_get_document_activity_id();
}

/**
 * Return the document activity ID.
 *
 * @since BuddyBoss 1.2.5
 *
 * @global object $document_template {@link BP_Document_Template}
 *
 * @return int The document activity ID.
 */
function bp_get_document_activity_id() {
	global $document_template;

	/**
	 * Filters the document activity ID being displayed.
	 *
	 * @since BuddyBoss 1.2.5
	 *
	 * @param int $id The document activity ID.
	 */
	if ( isset( $document_template->document->activity_id ) ) {
		return apply_filters( 'bp_get_document_activity_id', $document_template->document->activity_id );
	} else {
		return apply_filters( 'bp_get_document_activity_id', '' );
	}
}

/**
 * Output the document date created.
 *
 * @since BuddyBoss 1.2.5
 */
function bp_document_date_created() {
	echo bp_get_document_date_created();
}

/**
 * Return the document date created.
 *
 * @since BuddyBoss 1.2.5
 *
 * @global object $document_template {@link BP_Document_Template}
 *
 * @return string The document date created.
 */
function bp_get_document_date_created() {
	global $document_template;

	$date = date_i18n( bp_get_option( 'date_format' ), strtotime( $document_template->document->date_created ) );

	/**
	 * Filters the document date created being displayed.
	 *
	 * @since BuddyBoss 1.2.5
	 *
	 * @param string The date created.
	 */
	return apply_filters( 'bp_get_document_date_created', $date );
}

/**
 * Output the document attachment thumbnail.
 *
 * @since BuddyBoss 1.2.5
 */
function bp_document_attachment_image_thumbnail() {
	echo bp_get_document_attachment_image_thumbnail();
}

/**
 * Return the document attachment thumbnail.
 *
 * @since BuddyBoss 1.2.5
 *
 * @global object $document_template {@link BP_Document_Template}
 *
 * @return string The document attachment thumbnail url.
 */
function bp_get_document_attachment_image_thumbnail() {
	global $document_template;

	/**
	 * Filters the document thumbnail being displayed.
	 *
	 * @since BuddyBoss 1.2.5
	 *
	 * @param string The document thumbnail.
	 */
	if ( isset( $document_template->document->attachment_data->thumb ) ) {
		return apply_filters( 'bp_get_document_activity_id', $document_template->document->attachment_data->thumb );
	} else {
		return apply_filters( 'bp_get_document_activity_id', '' );
	}
}

/**
 * Output the document attachment activity thumbnail.
 *
 * @since BuddyBoss 1.2.5
 */
function bp_document_attachment_image_activity_thumbnail() {
	echo bp_get_document_attachment_image_activity_thumbnail();
}

/**
 * Return the document attachment activity thumbnail.
 *
 * @since BuddyBoss 1.2.5
 *
 * @global object $document_template {@link BP_Document_Template}
 *
 * @return string The document attachment thumbnail url.
 */
function bp_get_document_attachment_image_activity_thumbnail() {
	global $document_template;

	/**
	 * Filters the document activity thumbnail being displayed.
	 *
	 * @since BuddyBoss 1.2.5
	 *
	 * @param string The document activity thumbnail.
	 */
	return apply_filters( 'bp_get_document_attachment_image', $document_template->document->attachment_data->activity_thumb );
}

/**
 * Output the document attachment.
 *
 * @since BuddyBoss 1.2.5
 */
function bp_document_attachment_image() {
	echo bp_get_document_attachment_image();
}

/**
 * Return the document attachment.
 *
 * @since BuddyBoss 1.2.5
 *
 * @global object $document_template {@link BP_Document_Template}
 *
 * @return string The document attachment url.
 */
function bp_get_document_attachment_image() {
	global $document_template;

	/**
	 * Filters the document image being displayed.
	 *
	 * @since BuddyBoss 1.2.5
	 *
	 * @param string The full image.
	 */
	if ( isset( $document_template->document->attachment_data->full ) ) {
		return apply_filters( 'bp_get_document_attachment_image', $document_template->document->attachment_data->full );
	} else {
		return apply_filters( 'bp_get_document_attachment_image', '' );
	}

}

/**
 * Output document directory permalink.
 *
 * @since BuddyBoss 1.2.5
 */
function bp_document_directory_permalink() {
	echo esc_url( bp_get_document_directory_permalink() );
}
/**
 * Return document directory permalink.
 *
 * @since BuddyBoss 1.2.5
 *
 * @return string
 */
function bp_get_document_directory_permalink() {

	/**
	 * Filters the document directory permalink.
	 *
	 * @since BuddyBoss 1.2.5
	 *
	 * @param string $value document directory permalink.
	 */
	return apply_filters( 'bp_get_document_directory_permalink', trailingslashit( bp_get_root_domain() . '/' . bp_get_document_root_slug() ) );
}

// ****************************** Document Document *********************************//

/**
 * Initialize the folder loop.
 *
 * Based on the $args passed, bp_has_folders() populates the
 * $document_folder_template global, enabling the use of BuddyPress templates and
 * template functions to display a list of document folder items.
 *
 * @since BuddyBoss 1.2.5

 * @global object $document_folder_template {@link BP_Document_Folder_Template}
 *
 * @param array|string $args {
 *     Arguments for limiting the contents of the document loop. Most arguments
 *     are in the same format as {@link BP_Document_Folder::get()}. However,
 *     because the format of the arguments accepted here differs in a number of
 *     ways, and because bp_has_document() determines some default arguments in
 *     a dynamic fashion, we list all accepted arguments here as well.
 *
 *     Arguments can be passed as an associative array, or as a URL querystring
 *     (eg, 'author_id=4&privacy=public').
 *
 *     @type int               $page             Which page of results to fetch. Using page=1 without per_page will result
 *                                               in no pagination. Default: 1.
 *     @type int|bool          $per_page         Number of results per page. Default: 20.
 *     @type string            $page_arg         String used as a query parameter in pagination links. Default: 'acpage'.
 *     @type int|bool          $max              Maximum number of results to return. Default: false (unlimited).
 *     @type string            $fields           Activity fields to retrieve. 'all' to fetch entire document objects,
 *                                               'ids' to get only the document IDs. Default 'all'.
 *     @type string|bool       $count_total      If true, an additional DB query is run to count the total document items
 *                                               for the query. Default: false.
 *     @type string            $sort             'ASC' or 'DESC'. Default: 'DESC'.
 *     @type array|bool        $exclude          Array of document IDs to exclude. Default: false.
 *     @type array|bool        $include          Array of exact document IDs to query. Providing an 'include' array will
 *                                               override all other filters passed in the argument array. When viewing the
 *                                               permalink page for a single document item, this value defaults to the ID of
 *                                               that item. Otherwise the default is false.
 *     @type string            $search_terms     Limit results by a search term. Default: false.
 *     @type int|array|bool    $user_id          The ID(s) of user(s) whose document should be fetched. Pass a single ID or
 *                                               an array of IDs. When viewing a user profile page, 'user_id' defaults to
 *                                               the ID of the displayed user. Otherwise the default is false.
 *     @type int|array|bool    $group_id         The ID(s) of group(s) whose document should be fetched. Pass a single ID or
 *                                               an array of IDs. When viewing a group page, 'group_id' defaults to
 *                                               the ID of the displayed group. Otherwise the default is false.
 *     @type array             $privacy          Limit results by a privacy. Default: public | grouponly.
 * }
 * @return bool Returns true when document found, otherwise false.
 */
function bp_has_folders( $args = '' ) {
	global $document_folder_template;

	/*
	 * Smart Defaults.
	 */

	// User filtering.
	$user_id = bp_displayed_user_id() ? bp_displayed_user_id() : false;

	$search_terms_default = false;
	$search_query_arg     = bp_core_get_component_search_query_arg( 'folder' );
	if ( ! empty( $_REQUEST[ $search_query_arg ] ) ) {
		$search_terms_default = stripslashes( $_REQUEST[ $search_query_arg ] );
	}

	$privacy = array( 'public' );
	if ( is_user_logged_in() ) {
		$privacy[] = 'loggedin';
		if ( bp_is_active( 'friends' ) ) {

			// get the login user id.
			$current_user_id = get_current_user_id();

			// check if the login user is friends of the display user
			$is_friend = friends_check_friendship( $current_user_id, $user_id );

			/**
			 * check if the login user is friends of the display user
			 * OR check if the login user and the display user is the same
			 */
			if ( $is_friend || ! empty( $current_user_id ) && $current_user_id == $user_id ) {
				$privacy[] = 'friends';
			}
		}

		if ( bp_is_my_profile() ) {
			$privacy[] = 'onlyme';
		}
	}

	$group_id = false;
	if ( bp_is_group() ) {
		$group_id = bp_get_current_group_id();
		$user_id  = false;
		$privacy  = array( 'grouponly' );
	}

	/*
	 * Parse Args.
	 */

	// Note: any params used for filtering can be a single value, or multiple
	// values comma separated.
	$r = bp_parse_args(
		$args,
		array(
			'include'      => false,        // Pass an folder_id or string of IDs comma-separated.
			'exclude'      => false,        // Pass an activity_id or string of IDs comma-separated.
			'sort'         => 'DESC',       // Sort DESC or ASC.
			'page'         => 1,            // Which page to load.
			'per_page'     => 20,           // Number of items per page.
			'page_arg'     => 'acpage',     // See https://buddypress.trac.wordpress.org/ticket/3679.
			'max'          => false,        // Max number to return.
			'fields'       => 'all',
			'count_total'  => false,

			// Filtering
			'user_id'      => $user_id,     // user_id to filter on.
			'group_id'     => $group_id,    // group_id to filter on.
			'privacy'      => $privacy,     // privacy to filter on - public, onlyme, loggedin, friends, grouponly.

		// Searching.
			'search_terms' => $search_terms_default,
		),
		'has_documents'
	);

	if ( bp_is_group_single() && bp_is_group_folders() && false === $r['include'] ) {
		$r['include'] = (int) bp_action_variable( 1 );
	}

	/*
	 * Smart Overrides.
	 */

	// Search terms.
	if ( ! empty( $_REQUEST['s'] ) && empty( $r['search_terms'] ) ) {
		$r['search_terms'] = $_REQUEST['s'];
	}

	// Do not exceed the maximum per page.
	if ( ! empty( $r['max'] ) && ( (int) $r['per_page'] > (int) $r['max'] ) ) {
		$r['per_page'] = $r['max'];
	}

	/*
	 * Query
	 */

	$document_folder_template = new BP_Document_Folder_Template( $r );

	/**
	 * Filters whether or not there are document documents to display.
	 *
	 * @since BuddyBoss 1.2.5
	 *
	 * @param bool   $value                     Whether or not there are document items to display.
	 * @param string $document_folder_template      Current document folder template being used.
	 * @param array  $r                         Array of arguments passed into the BP_Document_Folder_Template class.
	 */
	return apply_filters( 'bp_has_folder', $document_folder_template->has_folders(), $document_folder_template, $r );
}

/**
 * Determine if there are still folder left in the loop.
 *
 * @since BuddyBoss 1.2.5
 *
 * @global object $document_folder_template {@link BP_Document_Folder_Template}
 *
 * @return bool Returns true when document are found.
 */
function bp_folder() {
	global $document_folder_template;
	return $document_folder_template->user_folders();
}

/**
 * Get the current folder object in the loop.
 *
 * @since BuddyBoss 1.2.5
 *
 * @global object $document_folder_template {@link BP_Document_Folder_Template}
 *
 * @return object The current document within the loop.
 */
function bp_the_folder() {
	global $document_folder_template;
	return $document_folder_template->the_folder();
}

/**
 * Output the URL for the Load More link.
 *
 * @since BuddyBoss 1.2.5
 */
function bp_folder_load_more_link() {
	echo esc_url( bp_get_folder_load_more_link() );
}
/**
 * Get the URL for the Load More link.
 *
 * @since BuddyBoss 1.2.5
 *
 * @return string $link
 */
function bp_get_folder_load_more_link() {
	global $document_folder_template;

	$url  = bp_get_requested_url();
	$link = add_query_arg( $document_folder_template->pag_arg, $document_folder_template->pag_page + 1, $url );

	/**
	 * Filters the Load More link URL.
	 *
	 * @since BuddyBoss 1.2.5
	 *
	 * @param string $link                  The "Load More" link URL with appropriate query args.
	 * @param string $url                   The original URL.
	 * @param object $document_folder_template  The document folder template loop global.
	 */
	return apply_filters( 'bp_get_folder_load_more_link', $link, $url, $document_folder_template );
}

/**
 * Output the folder pagination count.
 *
 * @since BuddyBoss 1.2.5
 *
 * @global object $document_folder_template {@link BP_Document_Folder_Template}
 */
function bp_folder_pagination_count() {
	echo bp_get_folder_pagination_count();
}

/**
 * Return the folder pagination count.
 *
 * @since BuddyBoss 1.2.5
 *
 * @global object $document_folder_template {@link BP_Document_Folder_Template}
 *
 * @return string The pagination text.
 */
function bp_get_folder_pagination_count() {
	global $document_folder_template;

	$start_num = intval( ( $document_folder_template->pag_page - 1 ) * $document_folder_template->pag_num ) + 1;
	$from_num  = bp_core_number_format( $start_num );
	$to_num    = bp_core_number_format( ( $start_num + ( $document_folder_template->pag_num - 1 ) > $document_folder_template->total_folder_count ) ? $document_folder_template->total_folder_count : $start_num + ( $document_folder_template->pag_num - 1 ) );
	$total     = bp_core_number_format( $document_folder_template->total_folder_count );

	$message = sprintf( _n( 'Viewing 1 item', 'Viewing %1$s - %2$s of %3$s items', $document_folder_template->total_document_count, 'buddyboss' ), $from_num, $to_num, $total );

	return $message;
}

/**
 * Output the folder pagination links.
 *
 * @since BuddyBoss 1.2.5
 */
function bp_folder_pagination_links() {
	echo bp_get_folder_pagination_links();
}

/**
 * Return the folder pagination links.
 *
 * @since BuddyBoss 1.2.5
 *
 * @global object $document_folder_template {@link BP_Document_Folder_Template}
 *
 * @return string The pagination links.
 */
function bp_get_folder_pagination_links() {
	global $document_folder_template;

	/**
	 * Filters the folder pagination link output.
	 *
	 * @since BuddyBoss 1.2.5
	 *
	 * @param string $pag_links Output for the document folder pagination links.
	 */
	return apply_filters( 'bp_get_folder_pagination_links', $document_folder_template->pag_links );
}

/**
 * Return true when there are more folder items to be shown than currently appear.
 *
 * @since BuddyBoss 1.2.5
 *
 * @global object $document_folder_template {@link BP_Document_Folder_Template}
 *
 * @return bool $has_more_items True if more items, false if not.
 */
function bp_folder_has_more_items() {
	global $document_folder_template;

	if ( ! empty( $document_folder_template->has_more_items ) ) {
		$has_more_items = true;
	} else {
		$remaining_pages = 0;

		if ( ! empty( $document_folder_template->pag_page ) ) {
			$remaining_pages = floor( ( $document_folder_template->total_folder_count - 1 ) / ( $document_folder_template->pag_num * $document_folder_template->pag_page ) );
		}

		$has_more_items = (int) $remaining_pages > 0;
	}

	/**
	 * Filters whether there are more folder items to display.
	 *
	 * @since BuddyBoss 1.2.5
	 *
	 * @param bool $has_more_items Whether or not there are more folder items to display.
	 */
	return apply_filters( 'bp_folder_has_more_items', $has_more_items );
}

/**
 * Output the folder count.
 *
 * @since BuddyBoss 1.2.5
 */
function bp_folder_count() {
	echo bp_get_folder_count();
}

/**
 * Return the folder count.
 *
 * @since BuddyBoss 1.2.5
 *
 * @global object $document_folder_template {@link BP_Document_Folder_Template}
 *
 * @return int The folder count.
 */
function bp_get_folder_count() {
	global $document_folder_template;

	/**
	 * Filters the folder count for the document folder template.
	 *
	 * @since BuddyBoss 1.2.5
	 *
	 * @param int $folder_count The count for total folder.
	 */
	return apply_filters( 'bp_get_folder_count', (int) $document_folder_template->folder_count );
}

/**
 * Output the number of document folder per page.
 *
 * @since BuddyBoss 1.2.5
 */
function bp_folder_per_page() {
	echo bp_get_folder_per_page();
}

/**
 * Return the number of document folder per page.
 *
 * @since BuddyBoss 1.2.5
 *
 * @global object $document_folder_template {@link BP_Document_Folder_Template}
 *
 * @return int The document folder per page.
 */
function bp_get_folder_per_page() {
	global $document_folder_template;

	/**
	 * Filters the document folder posts per page value.
	 *
	 * @since BuddyBoss 1.2.5
	 *
	 * @param int $pag_num How many post should be displayed for pagination.
	 */
	return apply_filters( 'bp_get_folder_per_page', (int) $document_folder_template->pag_num );
}

/**
 * Output the document folder ID.
 *
 * @since BuddyBoss 1.2.5
 */
function bp_folder_id() {
	echo bp_get_folder_id();
}

/**
 * Return the folder ID.
 *
 * @since BuddyBoss 1.2.5
 *
 * @global object $document_folder_template {@link BP_Document_Folder_Template}
 *
 * @return int The document folder ID.
 */
function bp_get_folder_id() {
	global $document_template;

	/**
	 * Filters the document ID being displayed.
	 *
	 * @since BuddyBoss 1.2.5
	 *
	 * @param int $id The document folder ID.
	 */
	return apply_filters( 'bp_get_folder_id', $document_template->document->id );
}

/**
 * Output the document folder title.
 *
 * @since BuddyBoss 1.2.5
 */
function bp_folder_title() {
	echo bp_get_folder_title();
}

/**
 * Return the folder title.
 *
 * @since BuddyBoss 1.2.5
 *
 * @global object $document_folder_template {@link BP_Document_Folder_Template}
 *
 * @return string The document folder title.
 */
function bp_get_folder_title() {
	global $document_folder_template;

	/**
	 * Filters the folder title being displayed.
	 *
	 * @since BuddyBoss 1.2.5
	 *
	 * @param int $id The document folder title.
	 */
	return apply_filters( 'bp_get_folder_title', $document_folder_template->folder->title );
}

/**
 * Return the folder privacy.
 *
 * @since BuddyBoss 1.2.5
 *
 * @global object $document_folder_template {@link BP_Document_Folder_Template}
 *
 * @return string The document folder privacy.
 */
function bp_get_folder_privacy() {
	global $document_folder_template;

	/**
	 * Filters the folder privacy being displayed.
	 *
	 * @since BuddyBoss 1.2.5
	 *
	 * @param int $id The document folder privacy.
	 */
	return apply_filters( 'bp_get_folder_privacy', $document_folder_template->folder->privacy );
}

/**
 * Output the document folder ID.
 *
 * @since BuddyBoss 1.2.5
 */
function bp_folder_link() {
	echo bp_get_folder_link();
}

/**
 * Return the folder description.
 *
 * @since BuddyBoss 1.2.5
 *
 * @global object $document_folder_template {@link BP_Document_Folder_Template}
 *
 * @return string The document folder description.
 */
function bp_get_folder_link() {
	global $document_template;

	if ( bp_is_group() || $document_template->document->group_id > 0 ) {
		$group_link = bp_get_group_permalink( buddypress()->groups->current_group );
		if ( '' === $group_link ) {
			$group_link = bp_get_group_permalink( $document_template->document->group_id );
		}
		$url        = trailingslashit( $group_link . 'documents/folders/' . bp_get_folder_id() );
	} else {
		$url = trailingslashit( bp_displayed_user_domain() . 'document/folders/' . bp_get_folder_id() );
	}

	/**
	 * Filters the folder description being displayed.
	 *
	 * @since BuddyBoss 1.2.5
	 *
	 * @param int $id The document folder description.
	 */
	return apply_filters( 'bp_get_folder_link', $url );
}

/**
 * Determine if the current user can delete an folder item.
 *
 * @since BuddyBoss 1.2.5
 *
 * @param int|BP_Document_Folder $folder BP_Document_Folder object or ID of the folder
 * @return bool True if can delete, false otherwise.
 */
function bp_folder_user_can_delete( $folder = false ) {

	// Assume the user cannot delete the folder item.
	$can_delete = false;

	if ( empty( $folder ) ) {
		return $can_delete;
	}

	if ( ! is_object( $folder ) ) {
		$folder = new BP_Document_Folder( $folder );
	}

	if ( empty( $folder ) ) {
		return $can_delete;
	}

	// Only logged in users can delete folder.
	if ( is_user_logged_in() ) {

		// Groups documents have their own access
		if ( ! empty( $folder->group_id ) && groups_can_user_manage_folders( bp_loggedin_user_id(), $folder->group_id ) ) {
			$can_delete = true;

			// Users are allowed to delete their own folder.
		} else if ( isset( $folder->user_id ) && bp_loggedin_user_id() === $folder->user_id ) {
			$can_delete = true;
		}

		// Community moderators can always delete folder (at least for now).
		if ( bp_current_user_can( 'bp_moderate' ) ) {
			$can_delete = true;
		}
	}

	/**
	 * Filters whether the current user can delete an folder item.
	 *
	 * @since BuddyBoss 1.2.5
	 *
	 * @param bool   $can_delete Whether the user can delete the item.
	 * @param object $folder   Current folder item object.
	 */
	return (bool) apply_filters( 'bp_folder_user_can_delete', $can_delete, $folder );
}

/**
 * Output the document name.
 *
 * @since BuddyBoss 1.2.5
 */
function bp_document_name() {
	echo bp_get_document_name();
}

/**
 * Return the document name.
 *
 * @since BuddyBoss 1.2.5
 *
 * @global object $document_template {@link BP_Document_Template}
 *
 * @return int The document name.
 */
function bp_get_document_name() {
	global $document_template;

	if ( isset($document_template->document ) && isset( $document_template->document->attachment_id ) && $document_template->document->attachment_id > 0 ) {
		$filename = basename( get_attached_file( $document_template->document->attachment_id ) );
	} else {
		$filename = $document_template->document->title;
	}

	/**
	 * Filters the document name being displayed.
	 *
	 * @since BuddyBoss 1.2.5
	 *
	 * @param int $id The document name.
	 */
	return apply_filters( 'bp_get_document_name', $filename );
}

/**
 * Output the document name.
 *
 * @since BuddyBoss 1.2.5
 */
function bp_document_author() {
	echo bp_get_document_author();
}

/**
 * Return the document name.
 *
 * @since BuddyBoss 1.2.5
 *
 * @global object $document_template {@link BP_Document_Template}
 *
 * @return int The document name.
 */
function bp_get_document_author() {
	global $document_template;

	$author = bp_core_get_user_displayname( $document_template->document->user_id );

	/**
	 * Filters the document name being displayed.
	 *
	 * @since BuddyBoss 1.2.5
	 *
	 * @param int $id The document name.
	 */
	return apply_filters( 'bp_get_document_author', $author );
}

/**
 * Output the document preview attachment id.
 *
 * @since BuddyBoss 1.2.5
 */
function bp_document_preview_attachment_id() {
	echo bp_get_document_preview_attachment_id();
}

/**
 * Return the document preview attachment id.
 *
 * @since BuddyBoss 1.2.5
 *
 * @global object $document_template {@link BP_Document_Template}
 *
 * @return int The document preview attachment id.
 */
function bp_get_document_preview_attachment_id() {
	global $document_template;

	/**
	 * Filters the document preview attachment id being displayed.
	 *
	 * @since BuddyBoss 1.2.5
	 *
	 * @param int $id The document preview attachment id.
	 */
	return apply_filters( 'bp_get_document_preview_attachment_id', $document_template->document->preview_attachment_id );
}

/**
 * Output the document group ID.
 *
 * @since BuddyBoss 1.2.5
 */
function bp_document_group_id() {
	echo bp_get_document_group_id();
}

/**
 * Return the document group ID.
 *
 * @since BuddyBoss 1.2.5
 *
 * @global object $document_template {@link BP_Document_Template}
 *
 * @return int The document group ID.
 */
function bp_get_document_group_id() {
	global $document_template;

	/**
	 * Filters the document group ID being displayed.
	 *
	 * @since BuddyBoss 1.2.5
	 *
	 * @param int $id The document group ID.
	 */
	return apply_filters( 'bp_get_document_group_id', $document_template->document->group_id );
}

/**
 * Output the document date modified.
 *
 * @since BuddyBoss 1.2.5
 */
function bp_document_date_modified() {
	echo bp_get_document_date_modified();
}

/**
 * Return the document date created.
 *
 * @since BuddyBoss 1.2.5
 *
 * @global object $document_template {@link BP_Document_Template}
 *
 * @return string The document date created.
 */
function bp_get_document_date_modified() {
	global $document_template;

	$date = date_i18n( bp_get_option( 'date_format' ), strtotime( $document_template->document->date_modified ) );

	/**
	 * Filters the document date modified being displayed.
	 *
	 * @since BuddyBoss 1.2.5
	 *
	 * @param string The date modified.
	 */
	return apply_filters( 'bp_get_document_date_modified', $date );
}

/**
 * Output the document date modified.
 *
 * @since BuddyBoss 1.2.5
 */
function bp_document_date() {
	echo bp_get_document_date();
}

/**
 * Return the document date created.
 *
 * @since BuddyBoss 1.2.5
 *
 * @global object $document_template {@link BP_Document_Template}
 *
 * @return string The document date created.
 */
function bp_get_document_date() {
	global $document_template;

	if ( (int) strtotime( $document_template->document->date_modified ) > 0 ) {
		$date = $document_template->document->date_modified;
	} else {
		$date = $document_template->document->date_created;
	}

	$date = date_i18n( bp_get_option( 'date_format' ), strtotime( $date ) );

	/**
	 * Filters the document date modified being displayed.
	 *
	 * @since BuddyBoss 1.2.5
	 *
	 * @param string The date modified.
	 */
	return apply_filters( 'bp_get_document_date', $date );
}

/**
 * Output the document date modified.
 *
 * @since BuddyBoss 1.2.5
 */
function bp_document_privacy() {
	echo bp_get_document_privacy();
}

/**
 * Return the document privacy.
 *
 * @since BuddyBoss 1.2.5
 *
 * @global object $document_template {@link BP_Document_Template}
 *
 * @return string The document privacy.
 */
function bp_get_document_privacy() {
	global $document_template;

	$document_privacy = bp_document_get_visibility_levels();
	$document_privacy = $document_privacy[ $document_template->document->privacy ];

	/**
	 * Filters the document privacy being displayed.
	 *
	 * @since BuddyBoss 1.2.5
	 *
	 * @param string The privacy.
	 */
	return apply_filters( 'bp_get_document_privacy', $document_privacy, $document_template->document->privacy, $document_privacy );
}

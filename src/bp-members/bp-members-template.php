<?php
/**
 * BuddyPress Member Template Tags.
 *
 * Functions that are safe to use inside your template files and themes.
 *
 * @package BuddyBoss\Members
 * @since BuddyPress 1.5.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Output the profile component slug.
 *
 * @since BuddyPress 2.4.0
 */
function bp_profile_slug() {
	echo bp_get_profile_slug();
}
	/**
	 * Return the profile component slug.
	 *
	 * @since BuddyPress 2.4.0
	 *
	 * @return string
	 */
function bp_get_profile_slug() {

	/**
	 * Filters the profile component slug.
	 *
	 * @since BuddyPress 2.4.0
	 *
	 * @param string $slug Profile component slug.
	 */
	return apply_filters( 'bp_get_profile_slug', buddypress()->profile->slug );
}

/**
 * Output the members component slug.
 *
 * @since BuddyPress 1.5.0
 */
function bp_members_slug() {
	echo bp_get_members_slug();
}
	/**
	 * Return the members component slug.
	 *
	 * @since BuddyPress 1.5.0
	 *
	 * @return string
	 */
function bp_get_members_slug() {

	/**
	 * Filters the Members component slug.
	 *
	 * @since BuddyPress 1.5.0
	 *
	 * @param string $slug Members component slug.
	 */
	return apply_filters( 'bp_get_members_slug', buddypress()->members->slug );
}

/**
 * Output the members component root slug.
 *
 * @since BuddyPress 1.5.0
 */
function bp_members_root_slug() {
	echo bp_get_members_root_slug();
}
	/**
	 * Return the members component root slug.
	 *
	 * @since BuddyPress 1.5.0
	 *
	 * @return string
	 */
function bp_get_members_root_slug() {

	/**
	 * Filters the Members component root slug.
	 *
	 * @since BuddyPress 1.5.0
	 *
	 * @param string $slug Members component root slug.
	 */
	return apply_filters( 'bp_get_members_root_slug', buddypress()->members->root_slug );
}

/**
 * Output the profile type base slug.
 *
 * @since BuddyPress 2.5.0
 */
function bp_members_member_type_base() {
	echo esc_url( bp_get_members_member_type_base() );
}
	/**
	 * Get the profile type base slug.
	 *
	 * The base slug is the string used as the base prefix when generating profile type directory URLs.
	 * For example, in example.com/members/type/foo/, 'foo' is the profile type and 'type' is the
	 * base slug.
	 *
	 * @since BuddyPress 2.5.0
	 *
	 * @return string
	 */
function bp_get_members_member_type_base() {
	/**
	 * Filters the profile type URL base.
	 *
	 * @since BuddyPress 2.3.0
	 *
	 * @param string $base
	 * @todo if this is changed what will it break? Should we allow this?
	 */
	return apply_filters( 'bp_members_member_type_base', _x( 'type', 'profile type URL base', 'buddyboss' ) );
}

/**
 * Output member directory permalink.
 *
 * @since BuddyPress 1.5.0
 */
function bp_members_directory_permalink() {
	echo esc_url( bp_get_members_directory_permalink() );
}
	/**
	 * Return member directory permalink.
	 *
	 * @since BuddyPress 1.5.0
	 *
	 * @return string
	 */
function bp_get_members_directory_permalink() {

	/**
	 * Filters the member directory permalink.
	 *
	 * @since BuddyPress 1.5.0
	 *
	 * @param string $value Members directory permalink.
	 */
	return apply_filters( 'bp_get_members_directory_permalink', trailingslashit( bp_get_root_domain() . '/' . bp_get_members_root_slug() ) );
}

/**
 * Output profile type directory permalink.
 *
 * @since BuddyPress 2.5.0
 *
 * @param string $member_type Optional. profile type.
 */
function bp_member_type_directory_permalink( $member_type = '' ) {
	echo esc_url( bp_get_member_type_directory_permalink( $member_type ) );
}
	/**
	 * Return profile type directory permalink.
	 *
	 * @since BuddyPress 2.5.0
	 *
	 * @param string $member_type Optional. profile type. Defaults to current profile type.
	 * @return string profile type directory URL on success, an empty string on failure.
	 */
function bp_get_member_type_directory_permalink( $member_type = '' ) {

	if ( $member_type ) {
		$_member_type = $member_type;
	} else {
		// Fall back on the current profile type.
		$_member_type = bp_get_current_member_type();
	}

	$type = bp_get_member_type_object( $_member_type );

	// Bail when profile type is not found or has no directory.
	if ( ! $type || ! $type->has_directory ) {
		return '';
	}

	/**
	 * Filters the profile type directory permalink.
	 *
	 * @since BuddyPress 2.5.0
	 *
	 * @param string $value       profile type directory permalink.
	 * @param object $type        profile type object.
	 * @param string $member_type profile type name, as passed to the function.
	 */
	return apply_filters( 'bp_get_member_type_directory_permalink', trailingslashit( bp_get_members_directory_permalink() . bp_get_members_member_type_base() . '/' . $type->directory_slug ), $type, $member_type );
}

/**
 * Output the sign-up slug.
 *
 * @since BuddyPress 1.5.0
 */
function bp_signup_slug() {
	echo bp_get_signup_slug();
}
	/**
	 * Return the sign-up slug.
	 *
	 * @since BuddyPress 1.5.0
	 *
	 * @return string
	 */
function bp_get_signup_slug() {
	$bp = buddypress();

	if ( ! empty( $bp->pages->register->slug ) ) {
		$slug = $bp->pages->register->slug;
	} elseif ( defined( 'BP_REGISTER_SLUG' ) ) {
		$slug = BP_REGISTER_SLUG;
	} else {
		$slug = 'register';
	}

	/**
	 * Filters the sign-up slug.
	 *
	 * @since BuddyPress 1.5.0
	 *
	 * @param string $slug Sign-up slug.
	 */
	return apply_filters( 'bp_get_signup_slug', $slug );
}

/**
 * Output the activation slug.
 *
 * @since BuddyPress 1.5.0
 */
function bp_activate_slug() {
	echo bp_get_activate_slug();
}
	/**
	 * Return the activation slug.
	 *
	 * @since BuddyPress 1.5.0
	 *
	 * @return string
	 */
function bp_get_activate_slug() {
	$bp = buddypress();

	if ( ! empty( $bp->pages->activate->slug ) ) {
		$slug = $bp->pages->activate->slug;
	} elseif ( defined( 'BP_ACTIVATION_SLUG' ) ) {
		$slug = BP_ACTIVATION_SLUG;
	} else {
		$slug = 'activate';
	}

	/**
	 * Filters the activation slug.
	 *
	 * @since BuddyPress 1.5.0
	 *
	 * @param string $slug Activation slug.
	 */
	return apply_filters( 'bp_get_activate_slug', $slug );
}

/**
 * Initialize the members loop.
 *
 * Based on the $args passed, bp_has_members() populates the $members_template
 * global, enabling the use of BuddyPress templates and template functions to
 * display a list of members.
 *
 * @since BuddyPress 1.2.0
 * @since BuddyPress 7.0.0 Added `xprofile_query` and `user_ids` parameters.
 * @since BuddyPress 10.0.0 Added `$date_query` parameter.
 * @since BuddyBoss 2.3.90 Added `xprofile_query`, `user_ids`, `date_query` parameters.
 *
 * @global object $members_template {@link BP_Members_Template}
 *
 * @param array|string $args {
 *     Arguments for limiting the contents of the members loop. Most arguments
 *     are in the same format as {@link BP_User_Query}. However, because
 *     the format of the arguments accepted here differs in a number of ways,
 *     and because bp_has_members() determines some default arguments in a
 *     dynamic fashion, we list all accepted arguments here as well.
 *
 *     Arguments can be passed as an associative array, or as a URL query
 *     string (eg, 'user_id=4&per_page=3').
 *
 *     @type int                   $type                Sort order. Accepts 'active', 'random', 'newest', 'popular',
 *                                                      'online', 'alphabetical'. Default: 'active'.
 *     @type int|bool              $page                Page of results to display. Default: 1.
 *     @type int|bool              $per_page            Number of results per page. Default: 20.
 *     @type int|bool              $max                 Maximum number of results to return. Default: false (unlimited).
 *     @type string                $page_arg            The string used as a query parameter in pagination links.
 *                                                      Default: 'bpage'.
 *     @type array|int|string|bool $include             Limit results by a list of user IDs. Accepts an array, a
 *                                                      single integer, a comma-separated list of IDs, or false (to
 *                                                      disable this limiting). Accepts 'active', 'alphabetical',
 *                                                      'newest', or 'random'. Default: false.
 *     @type array|int|string|bool $exclude             Exclude users from results by ID. Accepts an array, a single
 *                                                      integer, a comma-separated list of IDs, or false (to disable
 *                                                      this limiting). Default: false.
 *     @type array|string|bool     $user_ids            An array or comma-separated list of IDs, or false (to
 *                                                      disable this limiting). Default: false.
 *     @type int                   $user_id             If provided, results are limited to the friends of the specified
 *                                                      user. When on a user's Connections page, defaults to the ID of the
 *                                                      displayed user. Otherwise defaults to 0.
 *     @type string|array          $member_type         Array or comma-separated list of profile types to limit
 *                                                      results to.
 *     @type string|array          $member_type__in     Array or comma-separated list of profile types to limit
 *                                                      results to.
 *     @type string|array          $member_type__not_in Array or comma-separated list of profile types to exclude
 *                                                      from results.
 *     @type string                $search_terms        Limit results by a search term. Default: value of
 *                                                      `$_REQUEST['members_search']` or `$_REQUEST['s']`, if present.
 *                                                      Otherwise false.
 *     @type string                $meta_key            Limit results by the presence of a usermeta key.
 *                                                      Default: false.
 *     @type mixed                 $meta_value          When used with meta_key, limits results by the a matching
 *                                                      usermeta value. Default: false.
 *     @type array                 $xprofile_query      Filter results by xprofile data. Requires the xprofile
 *                                                      component. See {@see BP_XProfile_Query} for details.
 *     @type array                 $date_query          Filter results by member last activity date. See first parameter of
 *                                                      {@link WP_Date_Query::__construct()} for syntax. Only applicable if
 *                                                      $type is either 'active', 'random', 'newest', or 'online'.
 *     @type bool                  $populate_extras     Whether to fetch optional data, such as friend counts.
 *                                                      Default: true.
 * }
 * @return bool Returns true when blogs are found, otherwise false.
 */
function bp_has_members( $args = array() ) {
	global $members_template;

	// Default user ID.
	$user_id = 0;

	// User filtering.
	if ( bp_is_user_friends() && ! bp_is_user_friend_requests() && ! bp_is_user_mutual_friends() ) {
		$user_id = bp_displayed_user_id();
	}

	$include = false;
	$type    = 'active';

	// Mutual User filtering.
	/**
	 * Removed below from the IF condition becuase of mutual tab showing the all the user instead of mutual connections
	 * isset( $args['type'] ) && 'online' != $args['type'] &&
	 */
	if ( bp_is_user_friends() && bp_is_user_mutual_friends() ) {
		$include = bp_get_mutual_friendships();
		$type    = 'alphabetical';
	}

	$member_type = '';

	if ( isset( $_REQUEST['member_type'] ) && ! empty( $_REQUEST['member_type'] ) ) {
		if ( is_array( $_REQUEST['member_type'] ) ) {
			$member_type = $_REQUEST['member_type'];
		} else {
			// Can be a comma-separated list.
			$member_type = explode( ',', $_REQUEST['member_type'] );
		}
	}

	if ( empty( $member_type ) ) {
		$member_type = bp_get_current_member_type();
	} elseif ( is_array( $member_type ) ) {
		$member_type = array_merge( $member_type, bp_get_current_member_type() );
	} elseif ( '' !== $member_type ) {
		$member_type = explode( ',', $member_type );
		$member_type = array_merge( $member_type, bp_get_current_member_type() );
	}

	$search_terms_default = null;
	$search_query_arg     = bp_core_get_component_search_query_arg( 'members' );
	if ( ! empty( $_REQUEST[ $search_query_arg ] ) ) {
		$search_terms_default = stripslashes( $_REQUEST[ $search_query_arg ] );
	}

	$member_type__not_in = array();

	$args = bp_parse_args( $args, array() );
	// Exclude Member Types.
	if ( ( empty( $args['scope'] ) || 'all' === $args['scope'] ) && ( ! bp_is_user() && empty( $member_type ) && empty( $args['member_type'] ) ) ) {
		// get all excluded member types.
		$bp_member_type_ids = bp_get_removed_member_types();
		if ( isset( $bp_member_type_ids ) && ! empty( $bp_member_type_ids ) ) {
			foreach ( $bp_member_type_ids as $single ) {
				$member_type__not_in[] = $single['name'];
			}
		}
	}

	// Type: active ( default ) | random | newest | popular | online | alphabetical.
	$r = bp_parse_args(
		$args,
		array(
			'type'                => $type,
			'page'                => 1,
			'per_page'            => 20,
			'max'                 => false,

			'page_arg'            => 'upage',  // See https://buddypress.trac.wordpress.org/ticket/3679.

			'include'             => $include,    // Pass a user_id or a list (comma-separated or array) of user_ids to only show these users.
			'exclude'             => false,    // Pass a user_id or a list (comma-separated or array) of user_ids to exclude these users.
			'user_ids'            => false,

			'user_id'             => $user_id, // Pass a user_id to only show friends of this user.
			'member_type'         => $member_type,
			'member_type__in'     => '',
			'member_type__not_in' => $member_type__not_in,
			'search_terms'        => $search_terms_default,

			'meta_key'            => false,    // Only return users with this usermeta.
			'meta_value'          => false,    // Only return users where the usermeta value matches. Requires meta_key.

			'populate_extras'     => true,     // Fetch usermeta? Connection count, last active etc.
			'xprofile_query'      => false,
			'date_query'          => false,    // Filter members by last activity.
		),
		'has_members'
	);

	// Pass a filter if ?s= is set.
	if ( is_null( $r['search_terms'] ) ) {
		if ( ! empty( $_REQUEST['s'] ) ) {
			$r['search_terms'] = $_REQUEST['s'];
		} else {
			$r['search_terms'] = false;
		}
	}

	// Set per_page to max if max is larger than per_page.
	if ( ! empty( $r['max'] ) && ( $r['per_page'] > $r['max'] ) ) {
		$r['per_page'] = $r['max'];
	}

	// Query for members and populate $members_template global.
	$members_template = new BP_Core_Members_Template( $r );

	/**
	 * Filters whether or not BuddyPress has members to iterate over.
	 *
	 * @since BuddyPress 1.2.4
	 * @since BuddyPress 2.6.0 Added the `$r` parameter
	 *
	 * @param bool  $value            Whether or not there are members to iterate over.
	 * @param array $members_template Populated $members_template global.
	 * @param array $r                Array of arguments passed into the BP_Core_Members_Template class.
	 */
	return apply_filters( 'bp_has_members', $members_template->has_members(), $members_template, $r );
}

/**
 * Set up the current member inside the loop.
 *
 * @since BuddyPress 1.2.0
 *
 * @return object
 */
function bp_the_member() {
	global $members_template;
	return $members_template->the_member();
}

/**
 * Check whether there are more members to iterate over.
 *
 * @since BuddyPress 1.2.0
 *
 * @return bool
 */
function bp_members() {
	global $members_template;
	return $members_template->members();
}

/**
 * Output the members pagination count.
 *
 * @since BuddyPress 1.2.0
 */
function bp_members_pagination_count() {
	echo bp_get_members_pagination_count();
}
	/**
	 * Generate the members pagination count.
	 *
	 * @since BuddyPress 1.5.0
	 *
	 * @return string
	 */
function bp_get_members_pagination_count() {
	global $members_template;

	if ( empty( $members_template->type ) ) {
		$members_template->type = '';
	}

	$start_num = intval( ( $members_template->pag_page - 1 ) * $members_template->pag_num ) + 1;
	$from_num  = bp_core_number_format( $start_num );
	$to_num    = bp_core_number_format( ( $start_num + ( $members_template->pag_num - 1 ) > $members_template->total_member_count ) ? $members_template->total_member_count : $start_num + ( $members_template->pag_num - 1 ) );
	$total     = bp_core_number_format( $members_template->total_member_count );

	if ( 'active' == $members_template->type ) {
		$pag = sprintf( _n( 'Viewing 1 member', 'Viewing %1$s - %2$s of %3$s members', $members_template->total_member_count, 'buddyboss' ), $from_num, $to_num, $total );
	} elseif ( 'popular' == $members_template->type ) {
		$pag = sprintf( _n( 'Viewing 1 member with connections', 'Viewing %1$s - %2$s of %3$s members with connections', $members_template->total_member_count, 'buddyboss' ), $from_num, $to_num, $total );
	} elseif ( 'online' == $members_template->type ) {
		$pag = sprintf( _n( 'Viewing 1 online member', 'Viewing %1$s - %2$s of %3$s online members', $members_template->total_member_count, 'buddyboss' ), $from_num, $to_num, $total );
	} else {
		$pag = sprintf( _n( 'Viewing 1 member', 'Viewing %1$s - %2$s of %3$s members', $members_template->total_member_count, 'buddyboss' ), $from_num, $to_num, $total );

	}

	/**
	 * Filters the members pagination count.
	 *
	 * @since BuddyPress 1.5.0
	 *
	 * @param string $pag Pagination count string.
	 */
	return apply_filters( 'bp_members_pagination_count', $pag );
}

/**
 * Output the members pagination links.
 *
 * @since BuddyPress 1.2.0
 */
function bp_members_pagination_links() {
	echo bp_get_members_pagination_links();
}
	/**
	 * Fetch the members pagination links.
	 *
	 * @since BuddyPress 1.2.0
	 *
	 * @return string
	 */
function bp_get_members_pagination_links() {
	global $members_template;

	/**
	 * Filters the members pagination link.
	 *
	 * @since BuddyPress 1.2.0
	 *
	 * @param string $pag_links HTML markup for pagination links.
	 */
	return apply_filters( 'bp_get_members_pagination_links', $members_template->pag_links );
}

/**
 * Output the ID of the current member in the loop.
 *
 * @since BuddyPress 1.2.0
 */
function bp_member_user_id() {
	echo bp_get_member_user_id();
}
	/**
	 * Get the ID of the current member in the loop.
	 *
	 * @since BuddyPress 1.2.0
	 *
	 * @return string Member ID.
	 */
function bp_get_member_user_id() {
	global $members_template;
	$member_id = isset( $members_template->member->id ) ? (int) $members_template->member->id : false;

	/**
	 * Filters the ID of the current member in the loop.
	 *
	 * @since BuddyPress 1.2.0
	 *
	 * @param int $member_id ID of the member being iterated over.
	 */
	return apply_filters( 'bp_get_member_user_id', $member_id );
}

/**
 * Output the row class of the current member in the loop.
 *
 * @since BuddyPress 1.7.0
 *
 * @param array $classes Array of custom classes.
 */
function bp_member_class( $classes = array() ) {
	echo bp_get_member_class( $classes );
}
	/**
	 * Return the row class of the current member in the loop.
	 *
	 * @since BuddyPress 1.7.0
	 *
	 * @param array $classes Array of custom classes.
	 * @return string Row class of the member
	 */
function bp_get_member_class( $classes = array() ) {
	global $members_template;

	// Add even/odd classes, but only if there's more than 1 member.
	if ( $members_template->member_count > 1 ) {
		$pos_in_loop = (int) $members_template->current_member;
		$classes[]   = ( $pos_in_loop % 2 ) ? 'even' : 'odd';

		// If we've only one member in the loop, don't bother with odd and even.
	} else {
		$classes[] = 'bp-single-member';
	}

	// Maybe add 'is-online' class.
	if ( ! empty( $members_template->member->last_activity ) ) {

		// Calculate some times.
		$current_time  = bp_core_current_time( true, 'timestamp' );
		$last_activity = strtotime( $members_template->member->last_activity );
		$still_online  = strtotime( '+5 minutes', $last_activity );

		// Has the user been active recently?
		if ( $current_time <= $still_online ) {
			$classes[] = 'is-online';
		}
	}

	// Add current user class.
	if ( bp_loggedin_user_id() === (int) $members_template->member->id ) {
		$classes[] = 'is-current-user';
	}

	// Add current user profile types.
	if ( $member_types = bp_get_member_type( $members_template->member->id, false ) ) {
		foreach ( $member_types as $member_type ) {
			$classes[] = sprintf( 'member-type-%s', esc_attr( $member_type ) );
		}
	}

	/**
	 * Filters the determined classes to add to the HTML element.
	 *
	 * @since BuddyPress 1.7.0
	 *
	 * @param string $classes Classes to be added to the HTML element.
	 */
	$classes = apply_filters( 'bp_get_member_class', $classes );
	$classes = array_merge( $classes, array() );
	$retval  = 'class="' . join( ' ', $classes ) . '"';

	return $retval;
}

/**
 * Output nicename of current member in the loop.
 *
 * @since BuddyPress 1.2.5
 */
function bp_member_user_nicename() {
	echo bp_get_member_user_nicename();
}
	/**
	 * Get the nicename of the current member in the loop.
	 *
	 * @since BuddyPress 1.2.5
	 *
	 * @return string Members nicename.
	 */
function bp_get_member_user_nicename() {
	global $members_template;

	/**
	 * Filters the nicename of the current member in the loop.
	 *
	 * @since BuddyPress 1.2.5
	 *
	 * @param string $user_nicename Nicename for the current member.
	 */
	return apply_filters( 'bp_get_member_user_nicename', $members_template->member->user_nicename );
}

/**
 * Output login for current member in the loop.
 *
 * @since BuddyPress 1.2.5
 */
function bp_member_user_login() {
	echo bp_get_member_user_login();
}
	/**
	 * Get the login of the current member in the loop.
	 *
	 * @since BuddyPress 1.2.5
	 *
	 * @return string Member's login.
	 */
function bp_get_member_user_login() {
	global $members_template;

	/**
	 * Filters the login of the current member in the loop.
	 *
	 * @since BuddyPress 1.2.5
	 *
	 * @param string $user_login Login for the current member.
	 */
	return apply_filters( 'bp_get_member_user_login', $members_template->member->user_login );
}

/**
 * Output the email address for the current member in the loop.
 *
 * @since BuddyPress 1.2.5
 */
function bp_member_user_email() {
	echo bp_get_member_user_email();
}
	/**
	 * Get the email address of the current member in the loop.
	 *
	 * @since BuddyPress 1.2.5
	 *
	 * @return string Member's email address.
	 */
function bp_get_member_user_email() {
	global $members_template;

	/**
	 * Filters the email address of the current member in the loop.
	 *
	 * @since BuddyPress 1.2.5
	 *
	 * @param string $user_email Email address for the current member.
	 */
	return apply_filters( 'bp_get_member_user_email', $members_template->member->user_email );
}

/**
 * Check whether the current member in the loop is the logged-in user.
 *
 * @since BuddyPress 1.2.5
 *
 * @return bool
 */
function bp_member_is_loggedin_user() {
	global $members_template;

	/**
	 * Filters whether the current member in the loop is the logged-in user.
	 *
	 * @since BuddyPress 1.2.5
	 *
	 * @param bool $value Whether current member in the loop is logged in.
	 */
	return apply_filters( 'bp_member_is_loggedin_user', bp_loggedin_user_id() == $members_template->member->id ? true : false );
}

/**
 * Output a member's avatar.
 *
 * @since BuddyPress 1.2.0
 *
 * @see bp_get_member_avatar() for description of arguments.
 *
 * @param array|string $args See {@link bp_get_member_avatar()}.
 */
function bp_member_avatar( $args = '' ) {

	/**
	 * Filters a members avatar.
	 *
	 * @since BuddyPress 1.2.0
	 * @since BuddyPress 2.6.0 Added the `$args` parameter.
	 *
	 * @param string       $value Formatted HTML <img> element, or raw avatar URL based on $html arg.
	 * @param array|string $args  See {@link bp_get_member_avatar()}.
	 */
	echo apply_filters( 'bp_member_avatar', bp_get_member_avatar( $args ), $args );
}
	/**
	 * Get a member's avatar.
	 *
	 * @since BuddyPress 1.2.0
	 *
	 * @see bp_core_fetch_avatar() For a description of arguments and
	 *      return values.
	 *
	 * @param array|string $args  {
	 *     Arguments are listed here with an explanation of their defaults.
	 *     For more information about the arguments, see
	 *     {@link bp_core_fetch_avatar()}.
	 *     @type string   $alt     Default: 'Profile picture of [user name]'.
	 *     @type string   $class   Default: 'avatar'.
	 *     @type string   $type    Default: 'thumb'.
	 *     @type int|bool $width   Default: false.
	 *     @type int|bool $height  Default: false.
	 *     @type bool     $id      Currently unused.
	 *     @type bool     $no_grav Default: false.
	 * }
	 * @return string User avatar string.
	 */
function bp_get_member_avatar( $args = '' ) {
	global $members_template;

	$fullname = ! empty( $members_template->member->fullname ) ? $members_template->member->fullname : $members_template->member->display_name;

	$defaults = array(
		'type'   => 'thumb',
		'width'  => false,
		'height' => false,
		'class'  => 'avatar',
		'id'     => false,
		'alt'    => sprintf( __( 'Profile photo of %s', 'buddyboss' ), $fullname ),
	);

	$r = bp_parse_args( $args, $defaults );
	extract( $r, EXTR_SKIP );

	/**
	 * Filters a members avatar.
	 *
	 * @since BuddyPress 1.2.0
	 * @since BuddyPress 2.6.0 Added the `$r` parameter.
	 *
	 * @param string $value Formatted HTML <img> element, or raw avatar URL based on $html arg.
	 * @param array  $r     Array of parsed arguments. See {@link bp_get_member_avatar()}.
	 */
	return apply_filters(
		'bp_get_member_avatar',
		bp_core_fetch_avatar(
			array(
				'item_id' => $members_template->member->id,
				'type'    => $type,
				'alt'     => $alt,
				'css_id'  => $id,
				'class'   => $class,
				'width'   => $width,
				'height'  => $height,
				'email'   => $members_template->member->user_email,
			)
		),
		$r
	);
}

/**
 * Output the permalink for the current member in the loop.
 *
 * @since BuddyPress 1.2.0
 */
function bp_member_permalink() {
	echo esc_url( bp_get_member_permalink() );
}
	/**
	 * Get the permalink for the current member in the loop.
	 *
	 * @since BuddyPress 1.2.0
	 *
	 * @return string
	 */
function bp_get_member_permalink() {
	global $members_template;

	/**
	 * Filters the permalink for the current member in the loop.
	 *
	 * @since BuddyPress 1.2.0
	 *
	 * @param string $value Permalink for the current member in the loop.
	 */
	return apply_filters( 'bp_get_member_permalink', bp_core_get_user_domain( $members_template->member->id, $members_template->member->user_nicename, $members_template->member->user_login ) );
}

	/**
	 * Alias of {@link bp_member_permalink()}.
	 *
	 * @since BuddyPress 1.2.0
	 */
function bp_member_link() {
	echo esc_url( bp_get_member_permalink() ); }

	/**
	 * Alias of {@link bp_get_member_permalink()}.
	 *
	 * @since BuddyPress 1.2.0
	 */
function bp_get_member_link() {
	return bp_get_member_permalink(); }

/**
 * Output display name of current member in the loop.
 *
 * @since BuddyPress 1.2.0
 */
function bp_member_name() {

	/**
	 * Filters the display name of current member in the loop.
	 *
	 * @since BuddyPress 1.2.0
	 *
	 * @param string $value Display name for current member.
	 */
	echo apply_filters( 'bp_member_name', bp_get_member_name() );
}
	/**
	 * Get the display name of the current member in the loop.
	 *
	 * Full name is, by default, pulled from xprofile's Full Name field.
	 * When this field is empty, we try to get an alternative name from the
	 * WP users table, in the following order of preference: display_name,
	 * user_nicename, user_login.
	 *
	 * @since BuddyPress 1.2.0
	 *
	 * @return string The user's fullname for display.
	 */
function bp_get_member_name() {
	global $members_template;

	// Generally, this only fires when xprofile is disabled.
	if ( empty( $members_template->member->fullname ) ) {
		// Our order of preference for alternative fullnames.
		$name_stack = array(
			'display_name',
			'user_nicename',
			'user_login',
		);

		foreach ( $name_stack as $source ) {
			if ( ! empty( $members_template->member->{$source} ) ) {
				// When a value is found, set it as fullname and be done with it.
				$members_template->member->fullname = $members_template->member->{$source};
				break;
			}
		}
	}

	$list_fields = bp_xprofile_get_hidden_fields_for_user( $members_template->member->ID, bp_loggedin_user_id() );
	if ( empty( $list_fields ) ) {
		$full_name = $members_template->member->fullname;
	} else {
		$last_name_field_id = bp_xprofile_lastname_field_id();
		if ( in_array( $last_name_field_id, $list_fields ) ) {
			$last_name = $members_template->member->fullname;
			$full_name = str_replace( ' ' . $last_name, '', $members_template->member->fullname );
		} else {
			$full_name = $members_template->member->fullname;
		}
	}

	/**
	 * Filters the display name of current member in the loop.
	 *
	 * @since BuddyPress 1.2.0
	 *
	 * @param string $fullname Display name for current member.
	 */
	return apply_filters( 'bp_get_member_name', trim( $full_name ) );
}
	add_filter( 'bp_get_member_name', 'wp_filter_kses' );
	add_filter( 'bp_get_member_name', 'stripslashes' );
	add_filter( 'bp_get_member_name', 'strip_tags' );
	add_filter( 'bp_get_member_name', 'esc_html' );

/**
 * Output the current member's last active time.
 *
 * @since BuddyPress 1.2.0
 *
 * @param array $args {@see bp_get_member_last_active()}.
 */
function bp_member_last_active( $args = array() ) {
	echo bp_get_member_last_active( $args );
}
	/**
	 * Return the current member's last active time.
	 *
	 * @since BuddyPress 1.2.0
	 * @since BuddyPress 2.7.0 Added 'relative' as a parameter to $args.
	 *
	 * @param array $args {
	 *     Array of optional arguments.
	 *     @type mixed $active_format If true, formatted "active 5 minutes ago". If false, formatted "5 minutes
	 *                                ago". If string, should be sprintf'able like 'last seen %s ago'.
	 *     @type bool  $relative      If true, will return relative time "5 minutes ago". If false, will return
	 *                                date from database. Default: true.
	 * }
	 * @return string
	 */
function bp_get_member_last_active( $args = array() ) {
	global $members_template;

	// Parse the activity format.
	$r = bp_parse_args(
		$args,
		array(
			'active_format' => true,
			'relative'      => true,
		)
	);

	// Backwards compatibility for anyone forcing a 'true' active_format.
	if ( true === $r['active_format'] ) {
		$r['active_format'] = __( 'active %s', 'buddyboss' );
	}

	// Member has logged in at least one time.
	if ( isset( $members_template->member->last_activity ) ) {
		// We do not want relative time, so return now.
		// @todo Should the 'bp_member_last_active' filter be applied here?
		if ( ! $r['relative'] ) {
			return esc_attr( $members_template->member->last_activity );
		}

		// Backwards compatibility for pre 1.5 'ago' strings.
		$last_activity = ! empty( $r['active_format'] )
			? bp_core_get_last_activity( $members_template->member->last_activity, $r['active_format'] )
			: bp_core_time_since( $members_template->member->last_activity );

		// Member has never logged in or been active.
	} else {
		$last_activity = __( 'Never active', 'buddyboss' );
	}

	/**
	 * Filters the current members last active time.
	 *
	 * @since BuddyPress 1.2.0
	 *
	 * @param string $last_activity Formatted time since last activity.
	 * @param array  $r             Array of parsed arguments for query.
	 */
	return apply_filters( 'bp_member_last_active', $last_activity, $r );
}

/**
 * Output a piece of user profile data.
 *
 * @since BuddyPress 1.2.0
 *
 * @see bp_get_member_profile_data() for a description of params.
 *
 * @param array|string $args See {@link bp_get_member_profile_data()}.
 */
function bp_member_profile_data( $args = '' ) {
	echo bp_get_member_profile_data( $args );
}
	/**
	 * Get a piece of user profile data.
	 *
	 * When used in a bp_has_members() loop, this function will attempt
	 * to fetch profile data cached in the template global. It is also safe
	 * to use outside of the loop.
	 *
	 * @since BuddyPress 1.2.0
	 *
	 * @param array|string $args {
	 *     Array of config parameters.
	 *     @type string $field   Name of the profile field.
	 *     @type int    $user_id ID of the user whose data is being fetched.
	 *                           Defaults to the current member in the loop, or if not
	 *                           present, to the currently displayed user.
	 * }
	 * @return string|bool Profile data if found, otherwise false.
	 */
function bp_get_member_profile_data( $args = '' ) {
	global $members_template;

	if ( ! bp_is_active( 'xprofile' ) ) {
		return false;
	}

	// Declare local variables.
	$data = false;

	// Guess at default $user_id.
	$default_user_id = 0;
	if ( ! empty( $members_template->member->id ) ) {
		$default_user_id = $members_template->member->id;
	} elseif ( bp_displayed_user_id() ) {
		$default_user_id = bp_displayed_user_id();
	}

	$defaults = array(
		'field'   => false,
		'user_id' => $default_user_id,
	);

	$r = bp_parse_args( $args, $defaults );

	// If we're in a members loop, get the data from the global.
	if ( ! empty( $members_template->member->profile_data ) ) {
		$profile_data = $members_template->member->profile_data;
	}

	// Otherwise query for the data.
	if ( empty( $profile_data ) && method_exists( 'BP_XProfile_ProfileData', 'get_all_for_user' ) ) {
		$profile_data = BP_XProfile_ProfileData::get_all_for_user( $r['user_id'] );
	}

	// If we're in the members loop, but the profile data has not
	// been loaded into the global, cache it there for later use.
	if ( ! empty( $members_template->member ) && empty( $members_template->member->profile_data ) ) {
		$members_template->member->profile_data = $profile_data;
	}

	// Get the data for the specific field requested.
	if ( ! empty( $profile_data ) && ! empty( $profile_data[ $r['field'] ]['field_type'] ) && ! empty( $profile_data[ $r['field'] ]['field_data'] ) ) {
		$data = xprofile_format_profile_field( $profile_data[ $r['field'] ]['field_type'], $profile_data[ $r['field'] ]['field_data'] );
	}

	/**
	 * Filters resulting piece of member profile data.
	 *
	 * @since BuddyPress 1.2.0
	 * @since BuddyPress 2.6.0 Added the `$r` parameter.
	 *
	 * @param string|bool $data Profile data if found, otherwise false.
	 * @param array       $r    Array of parsed arguments.
	 */
	$data = apply_filters( 'bp_get_member_profile_data', $data, $r );

	/**
	 * Filters the resulting piece of member profile data by field type.
	 *
	 * This is a dynamic filter based on field type of the current field requested.
	 *
	 * @since BuddyPress 2.7.0
	 *
	 * @param string|bool $data Profile data if found, otherwise false.
	 * @param array       $r    Array of parsed arguments.
	 */
	if ( ! empty( $profile_data[ $r['field'] ]['field_type'] ) ) {
		$data = apply_filters( 'bp_get_member_profile_data_' . $profile_data[ $r['field'] ]['field_type'], $data, $r );
	}

	return $data;
}

/**
 * Output the 'registered [x days ago]' string for the current member.
 *
 * @since BuddyPress 1.2.0
 * @since BuddyPress 2.7.0 Added $args as a parameter.
 *
 * @param array $args Optional. {@see bp_get_member_registered()}
 */
function bp_member_registered( $args = array() ) {
	echo bp_get_member_registered( $args );
}
	/**
	 * Get the 'registered [x days ago]' string for the current member.
	 *
	 * @since BuddyPress 1.2.0
	 * @since BuddyPress 2.7.0 Added $args as a parameter.
	 *
	 * @param array $args {
	 *     Array of optional parameters.
	 *
	 *     @type bool $relative Optional. If true, returns relative registered date. eg. registered 5 months ago.
	 *                          If false, returns registered date value from database.
	 * }
	 *
	 * @return string
	 */
function bp_get_member_registered( $args = array() ) {
	global $members_template;

	$r = bp_parse_args(
		$args,
		array(
			'relative' => true,
		)
	);

	// We do not want relative time, so return now.
	// @todo Should the 'bp_member_registered' filter be applied here?
	if ( ! $r['relative'] ) {
		return esc_attr( $members_template->member->user_registered );
	}

	$registered = esc_attr( bp_core_get_last_activity( $members_template->member->user_registered, _x( 'registered %s', 'Records the timestamp that the user registered into the activity feed', 'buddyboss' ) ) );

	/**
	 * Filters the 'registered [x days ago]' string for the current member.
	 *
	 * @since BuddyPress 2.1.0
	 *
	 * @param string $registered The 'registered [x days ago]' string.
	 */
	return apply_filters( 'bp_member_registered', $registered );
}

/**
 * Output a random piece of profile data for the current member in the loop.
 *
 * @since BuddyPress 1.2.0
 */
function bp_member_random_profile_data() {
	global $members_template;

	if ( bp_is_active( 'xprofile' ) ) { ?>
		<?php $random_data = xprofile_get_random_profile_data( $members_template->member->id, true ); ?>
			<strong><?php echo wp_filter_kses( $random_data[0]->name ); ?></strong>
			<?php echo wp_filter_kses( $random_data[0]->value ); ?>
		<?php
	}
}

/**
 * Output hidden input for preserving member search params on form submit.
 *
 * @since BuddyPress 1.2.0
 */
function bp_member_hidden_fields() {
	$query_arg = bp_core_get_component_search_query_arg( 'members' );

	if ( isset( $_REQUEST[ $query_arg ] ) ) {
		echo '<input type="hidden" id="search_terms" value="' . esc_attr( $_REQUEST[ $query_arg ] ) . '" name="search_terms" />';
	}

	if ( isset( $_REQUEST['letter'] ) ) {
		echo '<input type="hidden" id="selected_letter" value="' . esc_attr( $_REQUEST['letter'] ) . '" name="selected_letter" />';
	}

	if ( isset( $_REQUEST['members_search'] ) ) {
		echo '<input type="hidden" id="search_terms" value="' . esc_attr( $_REQUEST['members_search'] ) . '" name="search_terms" />';
	}
}

/**
 * Output the Members directory search form.
 *
 * @since BuddyPress 1.0.0
 */
function bp_directory_members_search_form() {

	$query_arg = bp_core_get_component_search_query_arg( 'members' );

	if ( ! empty( $_REQUEST[ $query_arg ] ) ) {
		$search_value = stripslashes( $_REQUEST[ $query_arg ] );
	} else {
		$search_value = bp_get_search_default_text( 'members' );
	}

	$search_form_html = '<form action="" method="get" id="search-members-form">
		<label for="members_search"><input type="text" name="' . esc_attr( $query_arg ) . '" id="members_search" placeholder="' . esc_attr( $search_value ) . '" /></label>
		<input type="submit" id="members_search_submit" name="members_search_submit" value="' . __( 'Search', 'buddyboss' ) . '" />
	</form>';

	/**
	 * Filters the Members component search form.
	 *
	 * @since BuddyPress 1.9.0
	 *
	 * @param string $search_form_html HTML markup for the member search form.
	 */
	echo apply_filters( 'bp_directory_members_search_form', $search_form_html );
}

/**
 * Output the total member count.
 *
 * @since BuddyPress 1.2.0
 */
function bp_total_site_member_count() {
	echo bp_get_total_site_member_count();
}
	/**
	 * Get the total site member count.
	 *
	 * @since BuddyPress 1.2.0
	 *
	 * @return int
	 */
function bp_get_total_site_member_count() {

	/**
	 * Filters the total site member count.
	 *
	 * @since BuddyPress 1.2.0
	 *
	 * @param int $value Number-formatted total site member count.
	 */
	return apply_filters( 'bp_get_total_site_member_count', bp_core_number_format( bp_core_get_total_member_count() ) );
}

/** Navigation and other misc template tags ***********************************/

/**
 * Render the navigation markup for the logged-in user.
 *
 * Each component adds to this navigation array within its own
 * [component_name]setup_nav() function.
 *
 * This navigation array is the top level navigation, so it contains items such as:
 *      [Blog, Profile, Messages, Groups, Connections] ...
 *
 * The function will also analyze the current component the user is in, to
 * determine whether or not to highlight a particular nav item.
 *
 * @since BuddyPress 1.1.0
 *
 * @todo Move to a back-compat file?
 * @deprecated Does not seem to be called anywhere in BP core.
 */
function bp_get_loggedin_user_nav() {
	$bp = buddypress();

	// Loop through each navigation item.
	foreach ( (array) $bp->members->nav->get_primary() as $nav_item ) {

		$selected = '';

		// If the current component matches the nav item id, then add a highlight CSS class.
		if ( ! bp_is_directory() && ! empty( $bp->active_components[ bp_current_component() ] ) && $bp->active_components[ bp_current_component() ] == $nav_item->css_id ) {
			$selected = ' class="current selected"';
		}

		// If we are viewing another person (current_userid does not equal
		// loggedin_user->id then check to see if the two users are friends.
		// if they are, add a highlight CSS class to the friends nav item
		// if it exists.
		if ( ! bp_is_my_profile() && bp_displayed_user_id() ) {
			$selected = '';

			if ( bp_is_active( 'friends' ) ) {
				if ( $nav_item->css_id == $bp->friends->id ) {
					if ( friends_check_friendship( bp_loggedin_user_id(), bp_displayed_user_id() ) ) {
						$selected = ' class="current selected"';
					}
				}
			}
		}

		// Echo out the final list item.
		echo apply_filters_ref_array( 'bp_get_loggedin_user_nav_' . $nav_item->css_id, array( '<li id="li-nav-' . $nav_item->css_id . '" ' . $selected . '><a id="my-' . $nav_item->css_id . '" href="' . $nav_item->link . '">' . $nav_item->name . '</a></li>', &$nav_item ) );
	}

	// Always add a log out list item to the end of the navigation.
	$logout_link = '<li><a id="wp-logout" href="' . wp_logout_url( bp_get_root_domain() ) . '">' . __( 'Log Out', 'buddyboss' ) . '</a></li>';

	echo apply_filters( 'bp_logout_nav_link', $logout_link );
}

/**
 * Output the contents of the current user's home page.
 *
 * @since BuddyPress 2.6.0
 */
function bp_displayed_user_front_template_part() {
	$located = bp_displayed_user_get_front_template();

	if ( false !== $located ) {
		$slug = str_replace( '.php', '', $located );
		$name = null;

		/**
		 * Let plugins adding an action to bp_get_template_part get it from here
		 *
		 * @param string $slug Template part slug requested.
		 * @param string $name Template part name requested.
		 */
		do_action( 'get_template_part_' . $slug, $slug, $name );

		load_template( $located, true );
	}

	return $located;
}

/**
 * Locate a custom user front template if it exists.
 *
 * @since BuddyPress 2.6.0
 *
 * @param  object|null $displayed_user Optional. Falls back to current user if not passed.
 * @return string|bool                 Path to front template on success; boolean false on failure.
 */
function bp_displayed_user_get_front_template( $displayed_user = null ) {
	if ( ! is_object( $displayed_user ) || empty( $displayed_user->id ) ) {
		$displayed_user = bp_get_displayed_user();
	}

	if ( ! isset( $displayed_user->id ) ) {
		return false;
	}

	if ( isset( $displayed_user->front_template ) ) {
		return $displayed_user->front_template;
	}

	// Init the hierarchy
	$template_names = array(
		'members/single/front-id-' . (int) $displayed_user->id . '.php',
		'members/single/front-nicename-' . sanitize_file_name( $displayed_user->userdata->user_nicename ) . '.php',
	);

	/**
	 * Check for profile types and add it to the hierarchy
	 *
	 * Make sure to register your member
	 * type using the hook 'bp_register_member_types'
	 */
	if ( bp_get_member_types() ) {
		$displayed_user_member_type = bp_get_member_type( $displayed_user->id );
		if ( ! $displayed_user_member_type ) {
			$displayed_user_member_type = 'none';
		}

		$template_names[] = 'members/single/front-member-type-' . sanitize_file_name( $displayed_user_member_type ) . '.php';
	}

	// Add The generic template to the end of the hierarchy
	$template_names[] = 'members/single/front.php';

	/**
	 * Filters the hierarchy of user front templates corresponding to a specific user.
	 *
	 * @since BuddyPress 2.6.0
	 *
	 * @param array  $template_names Array of template paths.
	 */
	return bp_locate_template( apply_filters( 'bp_displayed_user_get_front_template', $template_names ), false, true );
}

/**
 * Check if the displayed user has a custom front template.
 *
 * @since BuddyPress 2.6.0
 */
function bp_displayed_user_has_front_template() {
	$displayed_user = bp_get_displayed_user();

	return ! empty( $displayed_user->front_template );
}

/**
 * Render the navigation markup for the displayed user.
 *
 * @since BuddyPress 1.1.0
 */
function bp_get_displayed_user_nav() {
	$bp = buddypress();

	foreach ( $bp->members->nav->get_primary() as $user_nav_item ) {
		if ( empty( $user_nav_item->show_for_displayed_user ) && ! bp_is_my_profile() ) {
			continue;
		}

		$selected = '';
		if ( bp_is_current_component( $user_nav_item->slug ) ) {
			$selected = ' class="current selected"';
		}

		if ( bp_loggedin_user_domain() ) {
			$link = str_replace( bp_loggedin_user_domain(), bp_displayed_user_domain(), $user_nav_item->link );
		} else {
			$link = trailingslashit( bp_displayed_user_domain() . $user_nav_item->link );
		}

		/**
		 * Filters the navigation markup for the displayed user.
		 *
		 * This is a dynamic filter that is dependent on the navigation tab component being rendered.
		 *
		 * @since BuddyPress 1.1.0
		 *
		 * @param string $value         Markup for the tab list item including link.
		 * @param array  $user_nav_item Array holding parts used to construct tab list item.
		 *                              Passed by reference.
		 */
		echo apply_filters_ref_array( 'bp_get_displayed_user_nav_' . $user_nav_item->css_id, array( '<li id="' . $user_nav_item->css_id . '-personal-li" ' . $selected . '><a id="user-' . $user_nav_item->css_id . '" href="' . $link . '">' . $user_nav_item->name . '</a></li>', &$user_nav_item ) );
	}
}

/** cover photo ***************************************************************/

/**
 * Should we use the cover photo header
 *
 * @since BuddyPress 2.4.0
 *
 * @return bool True if the displayed user has a cover photo,
 *              False otherwise
 */
function bp_displayed_user_use_cover_image_header() {
	return (bool) bp_is_active( 'xprofile', 'cover_image' ) && ! bp_disable_cover_image_uploads() && bp_attachments_is_wp_version_supported();
}

/** Avatars *******************************************************************/

/**
 * Output the logged-in user's avatar.
 *
 * @since BuddyPress 1.1.0
 *
 * @see bp_get_loggedin_user_avatar() for a description of params.
 *
 * @param array|string $args {@see bp_get_loggedin_user_avatar()}.
 */
function bp_loggedin_user_avatar( $args = '' ) {
	echo bp_get_loggedin_user_avatar( $args );
}
	/**
	 * Get the logged-in user's avatar.
	 *
	 * @since BuddyPress 1.1.0
	 *
	 * @see bp_core_fetch_avatar() For a description of arguments and
	 *      return values.
	 *
	 * @param array|string $args  {
	 *     Arguments are listed here with an explanation of their defaults.
	 *     For more information about the arguments, see
	 *     {@link bp_core_fetch_avatar()}.
	 *     @type string   $alt    Default: 'Profile picture of [user name]'.
	 *     @type bool     $html   Default: true.
	 *     @type string   $type   Default: 'thumb'.
	 *     @type int|bool $width  Default: false.
	 *     @type int|bool $height Default: false.
	 * }
	 * @return string User avatar string.
	 */
function bp_get_loggedin_user_avatar( $args = '' ) {

	$r = bp_parse_args(
		$args,
		array(
			'item_id' => bp_loggedin_user_id(),
			'type'    => 'thumb',
			'width'   => false,
			'height'  => false,
			'html'    => true,
			'alt'     => sprintf( __( 'Profile photo of %s', 'buddyboss' ), bp_get_loggedin_user_fullname() ),
		)
	);

	/**
	 * Filters the logged in user's avatar.
	 *
	 * @since BuddyPress 1.1.0
	 *
	 * @param string $value User avatar string.
	 * @param array  $r     Array of parsed arguments.
	 * @param array  $args  Array of initial arguments.
	 */
	return apply_filters( 'bp_get_loggedin_user_avatar', bp_core_fetch_avatar( $r ), $r, $args );
}

/**
 * Output the displayed user's avatar.
 *
 * @since BuddyPress 1.1.0
 *
 * @see bp_get_displayed_user_avatar() for a description of params.
 *
 * @param array|string $args {@see bp_get_displayed_user_avatar()}.
 */
function bp_displayed_user_avatar( $args = '' ) {
	echo bp_get_displayed_user_avatar( $args );
}
	/**
	 * Get the displayed user's avatar.
	 *
	 * @since BuddyPress 1.1.0
	 *
	 * @see bp_core_fetch_avatar() For a description of arguments and
	 *      return values.
	 *
	 * @param array|string $args  {
	 *     Arguments are listed here with an explanation of their defaults.
	 *     For more information about the arguments, see
	 *     {@link bp_core_fetch_avatar()}.
	 *     @type string   $alt    Default: 'Profile picture of [user name]'.
	 *     @type bool     $html   Default: true.
	 *     @type string   $type   Default: 'thumb'.
	 *     @type int|bool $width  Default: false.
	 *     @type int|bool $height Default: false.
	 * }
	 * @return string User avatar string.
	 */
function bp_get_displayed_user_avatar( $args = '' ) {

	$r = bp_parse_args(
		$args,
		array(
			'item_id' => bp_displayed_user_id(),
			'type'    => 'thumb',
			'width'   => false,
			'height'  => false,
			'html'    => true,
			'alt'     => sprintf( __( 'Profile photo of %s', 'buddyboss' ), bp_get_displayed_user_fullname() ),
		)
	);

	/**
	 * Filters the displayed user's avatar.
	 *
	 * @since BuddyPress 1.1.0
	 *
	 * @param string $value User avatar string.
	 * @param array  $r     Array of parsed arguments.
	 * @param array  $args  Array of initial arguments.
	 */
	return apply_filters( 'bp_get_displayed_user_avatar', bp_core_fetch_avatar( $r ), $r, $args );
}

/**
 * Output the email address of the displayed user.
 *
 * @since BuddyPress 1.5.0
 */
function bp_displayed_user_email() {
	echo bp_get_displayed_user_email();
}
	/**
	 * Get the email address of the displayed user.
	 *
	 * @since BuddyPress 1.5.0
	 *
	 * @return string
	 */
function bp_get_displayed_user_email() {
	$bp = buddypress();

	// If displayed user exists, return email address.
	if ( isset( $bp->displayed_user->userdata->user_email ) ) {
		$retval = $bp->displayed_user->userdata->user_email;
	} else {
		$retval = '';
	}

	/**
	 * Filters the email address of the displayed user.
	 *
	 * @since BuddyPress 1.5.0
	 *
	 * @param string $retval Email address for displayed user.
	 */
	return apply_filters( 'bp_get_displayed_user_email', esc_attr( $retval ) );
}

/**
 * Output the "active [x days ago]" string for a user.
 *
 * @since BuddyPress 1.0.0
 *
 * @see bp_get_last_activity() for a description of parameters.
 *
 * @param int $user_id See {@link bp_get_last_activity()}.
 */
function bp_last_activity( $user_id = 0 ) {

	/**
	 * Filters the 'active [x days ago]' string for a user.
	 *
	 * @since BuddyPress 1.0.0
	 *
	 * @param string $value Formatted 'active [x days ago]' string.
	 */
	echo apply_filters( 'bp_last_activity', bp_get_last_activity( $user_id ) );
}
	/**
	 * Get the "active [x days ago]" string for a user.
	 *
	 * @since BuddyPress 1.5.0
	 *
	 * @param int $user_id ID of the user. Default: displayed user ID.
	 * @return string
	 */
function bp_get_last_activity( $user_id = 0 ) {

	if ( empty( $user_id ) ) {
		$user_id = bp_displayed_user_id();
	}

	$last_activity = bp_core_get_last_activity(
		bp_get_user_last_activity( $user_id ),
		/* translators: %s: The last activity human-readable date. */
		esc_html__( 'Active %s', 'buddyboss' )
	);

	/**
	 * Filters the 'active [x days ago]' string for a user.
	 *
	 * @since BuddyPress 1.5.0
	 * @since BuddyPress 2.6.0 Added the `$user_id` parameter.
	 *
	 * @param string $value   Formatted 'active [x days ago]' string.
	 * @param int    $user_id ID of the user.
	 */
	return apply_filters( 'bp_get_last_activity', $last_activity, $user_id );
}

/**
 * Output the calculated first name of the displayed or logged-in user.
 *
 * @since BuddyPress 1.2.0
 */
function bp_user_firstname() {
	echo bp_get_user_firstname();
}
	/**
	 * Output the first name of a user.
	 *
	 * Simply takes all the characters before the first space in a name.
	 *
	 * @since BuddyPress 1.2.0
	 *
	 * @param string|bool $name Full name to use when generating first name.
	 *                          Defaults to displayed user's first name, or to
	 *                          logged-in user's first name if it's unavailable.
	 * @return string
	 */
function bp_get_user_firstname( $name = false ) {

	// Try to get displayed user.
	if ( empty( $name ) ) {
		$name = bp_get_displayed_user_fullname();
	}

	// Fall back on logged in user.
	if ( empty( $name ) ) {
		$name = bp_get_loggedin_user_fullname();
	}

	$fullname = (array) explode( ' ', $name );

	/**
	 * Filters the first name of a user.
	 *
	 * @since BuddyPress 1.2.0
	 *
	 * @param string $value    First name of user.
	 * @param string $fullname Full name of user.
	 */
	return apply_filters( 'bp_get_user_firstname', $fullname[0], $fullname );
}

/**
 * Output the link for the logged-in user's profile.
 *
 * @since BuddyPress 1.2.4
 */
function bp_loggedin_user_link() {
	echo esc_url( bp_get_loggedin_user_link() );
}
	/**
	 * Get the link for the logged-in user's profile.
	 *
	 * @since BuddyPress 1.0.0
	 *
	 * @return string
	 */
function bp_get_loggedin_user_link() {

	/**
	 * Filters the link for the logged-in user's profile.
	 *
	 * @since BuddyPress 1.2.4
	 *
	 * @param string $value Link for the logged-in user's profile.
	 */
	return apply_filters( 'bp_get_loggedin_user_link', bp_loggedin_user_domain() );
}

/**
 * Output the link for the displayed user's profile.
 *
 * @since BuddyPress 1.2.4
 */
function bp_displayed_user_link() {
	echo esc_url( bp_get_displayed_user_link() );
}
	/**
	 * Get the link for the displayed user's profile.
	 *
	 * @since BuddyPress 1.0.0
	 *
	 * @return string
	 */
function bp_get_displayed_user_link() {

	/**
	 * Filters the link for the displayed user's profile.
	 *
	 * @since BuddyPress 1.2.4
	 *
	 * @param string $value Link for the displayed user's profile.
	 */
	return apply_filters( 'bp_get_displayed_user_link', bp_displayed_user_domain() );
}

	/**
	 * Alias of {@link bp_displayed_user_domain()}.
	 *
	 * @deprecated
	 */
function bp_user_link() {
	bp_displayed_user_domain(); }

/**
 * Alias of {@link bp_displayed_user_id()}.
 *
 * @since BuddyPress 1.0.0
 */
function bp_current_user_id() {
	return bp_displayed_user_id(); }

/**
 * Generate the link for the displayed user's profile.
 *
 * @since BuddyPress 1.0.0
 *
 * @return string
 */
function bp_displayed_user_domain() {
	$bp = buddypress();

	/**
	 * Filters the generated link for the displayed user's profile.
	 *
	 * @since BuddyPress 1.0.0
	 *
	 * @param string $value Generated link for the displayed user's profile.
	 */
	return apply_filters( 'bp_displayed_user_domain', isset( $bp->displayed_user->domain ) ? $bp->displayed_user->domain : '' );
}

/**
 * Generate the link for the logged-in user's profile.
 *
 * @since BuddyPress 1.0.0
 *
 * @return string
 */
function bp_loggedin_user_domain() {
	$bp = buddypress();

	/**
	 * Filters the generated link for the logged-in user's profile.
	 *
	 * @since BuddyPress 1.0.0
	 *
	 * @param string $value Generated link for the logged-in user's profile.
	 */
	return apply_filters( 'bp_loggedin_user_domain', isset( $bp->loggedin_user->domain ) ? $bp->loggedin_user->domain : '' );
}

/**
 * Output the displayed user's display name.
 *
 * @since BuddyPress 1.0.0
 */
function bp_displayed_user_fullname() {
	echo bp_get_displayed_user_fullname();
}
	/**
	 * Get the displayed user's display name.
	 *
	 * @since BuddyPress 1.2.0
	 *
	 * @return string
	 */
function bp_get_displayed_user_fullname() {
	$bp = buddypress();

	/**
	 * Filters the displayed user's display name.
	 *
	 * @since BuddyPress 1.2.0
	 *
	 * @param string $value Displayed user's display name.
	 */
	return apply_filters( 'bp_displayed_user_fullname', isset( $bp->displayed_user->fullname ) ? $bp->displayed_user->fullname : '' );
}

	/**
	 * Alias of {@link bp_get_displayed_user_fullname()}.
	 *
	 * @since BuddyPress 1.0.0
	 */
function bp_user_fullname() {
	echo bp_get_displayed_user_fullname(); }


/**
 * Output the logged-in user's display name.
 *
 * @since BuddyPress 1.0.0
 */
function bp_loggedin_user_fullname() {
	echo bp_get_loggedin_user_fullname();
}
	/**
	 * Get the logged-in user's display name.
	 *
	 * @since BuddyPress 1.0.0
	 *
	 * @return string
	 */
function bp_get_loggedin_user_fullname() {
	$bp = buddypress();

	/**
	 * Filters the logged-in user's display name.
	 *
	 * @since BuddyPress 1.0.0
	 *
	 * @param string $value Logged-in user's display name.
	 */
	return apply_filters( 'bp_get_loggedin_user_fullname', isset( $bp->loggedin_user->fullname ) ? $bp->loggedin_user->fullname : '' );
}

/**
 * Output the username of the displayed user.
 *
 * @since BuddyPress 1.2.0
 */
function bp_displayed_user_username() {
	echo bp_get_displayed_user_username();
}
	/**
	 * Get the username of the displayed user.
	 *
	 * @since BuddyPress 1.2.0
	 *
	 * @return string
	 */
function bp_get_displayed_user_username() {
	$bp = buddypress();

	if ( bp_displayed_user_id() ) {
		$username = bp_core_get_username( bp_displayed_user_id(), $bp->displayed_user->userdata->user_nicename, $bp->displayed_user->userdata->user_login );
	} else {
		$username = '';
	}

	/**
	 * Filters the username of the displayed user.
	 *
	 * @since BuddyPress 1.2.0
	 *
	 * @param string $username Username of the displayed user.
	 */
	return apply_filters( 'bp_get_displayed_user_username', $username );
}

/**
 * Output the username of the logged-in user.
 *
 * @since BuddyPress 1.2.0
 */
function bp_loggedin_user_username() {
	echo bp_get_loggedin_user_username();
}
	/**
	 * Get the username of the logged-in user.
	 *
	 * @since BuddyPress 1.2.0
	 *
	 * @return string
	 */
function bp_get_loggedin_user_username() {
	$bp = buddypress();

	if ( bp_loggedin_user_id() ) {
		$username = bp_core_get_username( bp_loggedin_user_id(), $bp->loggedin_user->userdata->user_nicename, $bp->loggedin_user->userdata->user_login );
	} else {
		$username = '';
	}

	/**
	 * Filters the username of the logged-in user.
	 *
	 * @since BuddyPress 1.2.0
	 *
	 * @param string $username Username of the logged-in user.
	 */
	return apply_filters( 'bp_get_loggedin_user_username', $username );
}

/**
 * Echo the current profile type message.
 *
 * @since BuddyPress 2.3.0
 */
function bp_current_member_type_message() {
	echo bp_get_current_member_type_message();
}
	/**
	 * Generate the current profile type message.
	 *
	 * @since BuddyPress 2.3.0
	 *
	 * @return string
	 */
function bp_get_current_member_type_message() {
	$type_object = bp_get_member_type_object( bp_get_current_member_type() );

	$message = sprintf( __( 'Viewing all members who are %s', 'buddyboss' ), '<strong>' . $type_object->labels['name'] . '</strong>' );

	/**
	 * Filters the current profile type message.
	 *
	 * @since BuddyPress 2.3.0
	 *
	 * @param string $message Message to filter.
	 */
	return apply_filters( 'bp_get_current_member_type_message', $message );
}

/** Signup Form ***************************************************************/

/**
 * Do we have a working custom sign up page?
 *
 * @since BuddyPress 1.5.0
 *
 * @return bool True if page and template exist, false if not.
 */
function bp_has_custom_signup_page() {
	static $has_page = false;

	if ( empty( $has_page ) ) {
		$has_page = bp_get_signup_slug() && bp_locate_template( array( 'registration/register.php', 'members/register.php', 'register.php' ), false );
	}

	return (bool) $has_page;
}

/**
 * Output the URL to the signup page.
 *
 * @since BuddyPress 1.0.0
 */
function bp_signup_page() {
	echo esc_url( bp_get_signup_page() );
}
	/**
	 * Get the URL to the signup page.
	 *
	 * @since BuddyPress 1.1.0
	 *
	 * @return string
	 */
function bp_get_signup_page() {
	if ( bp_has_custom_signup_page() && ! bp_allow_custom_registration() ) {
		$page = trailingslashit( bp_get_root_domain() . '/' . bp_get_signup_slug() );
	} elseif ( bp_has_custom_signup_page() && bp_allow_custom_registration() && '' === bp_custom_register_page_url() ) {
		$page = trailingslashit( bp_get_root_domain() . '/' . bp_get_signup_slug() );
	} elseif ( bp_allow_custom_registration() && '' !== bp_custom_register_page_url() ) {
		$page = bp_custom_register_page_url();
	} else {
		$page = bp_get_root_domain() . '/wp-signup.php';
	}

	/**
	 * Filters the URL to the signup page.
	 *
	 * @since BuddyPress 1.1.0
	 *
	 * @param string $page URL to the signup page.
	 */
	return apply_filters( 'bp_get_signup_page', $page );
}

/**
 * Do we have a working custom activation page?
 *
 * @since BuddyPress 1.5.0
 *
 * @return boolean True if page and template exist, false if not.
 */
function bp_has_custom_activation_page() {
	static $has_page = false;

	if ( empty( $has_page ) ) {
		$has_page = bp_get_activate_slug() && bp_locate_template( array( 'registration/activate.php', 'members/activate.php', 'activate.php' ), false );
	}

	return (bool) $has_page;
}

/**
 * Output the URL of the activation page.
 *
 * @since BuddyPress 1.0.0
 */
function bp_activation_page() {
	echo esc_url( bp_get_activation_page() );
}
	/**
	 * Get the URL of the activation page.
	 *
	 * @since BuddyPress 1.2.0
	 *
	 * @return string
	 */
function bp_get_activation_page() {
	if ( bp_has_custom_activation_page() ) {
		$page = trailingslashit( bp_get_root_domain() . '/' . bp_get_activate_slug() );
	} else {
		$page = trailingslashit( bp_get_root_domain() ) . 'wp-activate.php';
	}

	/**
	 * Filters the URL of the activation page.
	 *
	 * @since BuddyPress 1.2.0
	 *
	 * @param string $page URL to the activation page.
	 */
	return apply_filters( 'bp_get_activation_page', $page );
}

/**
 * Get the activation key from the current request URL.
 *
 * @since BuddyPress 3.0.0
 *
 * @return string
 */
function bp_get_current_activation_key() {
	$key = '';

	if ( bp_is_current_component( 'activate' ) ) {
		if ( isset( $_GET['key'] ) ) {
			$key = wp_unslash( $_GET['key'] );
		} else {
			$key = bp_current_action();
		}
	}

	/**
	 * Filters the activation key from the current request URL.
	 *
	 * @since BuddyPress 3.0.0
	 *
	 * @param string $key Activation key.
	 */
	return apply_filters( 'bp_get_current_activation_key', $key );
}

/**
 * Output the username submitted during signup.
 *
 * @since BuddyPress 1.1.0
 */
function bp_signup_username_value() {
	echo bp_get_signup_username_value();
}
	/**
	 * Get the username submitted during signup.
	 *
	 * @since BuddyPress 1.1.0
	 *
	 * @todo This should be properly escaped.
	 *
	 * @return string
	 */
function bp_get_signup_username_value() {
	$value = '';

	// grab the nickname field first
	$nickname_field = 'field_' . bp_xprofile_nickname_field_id();
	if ( isset( $_POST[ $nickname_field ] ) ) {
		$value = $_POST[ $nickname_field ];
	}
	// fallback to grab the username
	elseif ( isset( $_POST['signup_username'] ) ) {
		$value = $_POST['signup_username'];
	}

	/**
	 * Filters the username submitted during signup.
	 *
	 * @since BuddyPress 1.1.0
	 *
	 * @param string $value Username submitted during signup.
	 */
	return apply_filters( 'bp_get_signup_username_value', $value );
}

/**
 * Output the user email address submitted during signup.
 *
 * @since BuddyPress 1.1.0
 */
function bp_signup_email_value() {
	echo bp_get_signup_email_value();
}
	/**
	 * Get the email address submitted during signup.
	 *
	 * @since BuddyPress 1.1.0
	 *
	 * @todo This should be properly escaped.
	 *
	 * @return string
	 */
function bp_get_signup_email_value() {
	$value = '';
	if ( isset( $_POST['signup_email'] ) ) {
		$value = $_POST['signup_email'];
	}

	/**
	 * Filters the email address submitted during signup.
	 *
	 * @since BuddyPress 1.1.0
	 *
	 * @param string $value Email address submitted during signup.
	 */
	return apply_filters( 'bp_get_signup_email_value', $value );
}

/**
 * Output the 'signup_with_blog' value submitted during signup.
 *
 * @since BuddyPress 1.1.0
 */
function bp_signup_with_blog_value() {
	echo bp_get_signup_with_blog_value();
}
	/**
	 * Get the 'signup_with_blog' value submitted during signup.
	 *
	 * @since BuddyPress 1.1.0
	 *
	 * @return string
	 */
function bp_get_signup_with_blog_value() {
	$value = '';
	if ( isset( $_POST['signup_with_blog'] ) ) {
		$value = $_POST['signup_with_blog'];
	}

	/**
	 * Filters the 'signup_with_blog' value submitted during signup.
	 *
	 * @since BuddyPress 1.1.0
	 *
	 * @param string $value 'signup_with_blog' value submitted during signup.
	 */
	return apply_filters( 'bp_get_signup_with_blog_value', $value );
}

/**
 * Output the 'signup_blog_url' value submitted at signup.
 *
 * @since BuddyPress 1.1.0
 */
function bp_signup_blog_url_value() {
	echo bp_get_signup_blog_url_value();
}
	/**
	 * Get the 'signup_blog_url' value submitted at signup.
	 *
	 * @since BuddyPress 1.1.0
	 *
	 * @todo Should be properly escaped.
	 *
	 * @return string
	 */
function bp_get_signup_blog_url_value() {
	$value = '';
	if ( isset( $_POST['signup_blog_url'] ) ) {
		$value = $_POST['signup_blog_url'];
	}

	/**
	 * Filters the 'signup_blog_url' value submitted during signup.
	 *
	 * @since BuddyPress 1.1.0
	 *
	 * @param string $value 'signup_blog_url' value submitted during signup.
	 */
	return apply_filters( 'bp_get_signup_blog_url_value', $value );
}

/**
 * Output the base URL for subdomain installations of WordPress Multisite.
 *
 * @since BuddyPress 2.1.0
 */
function bp_signup_subdomain_base() {
	echo bp_signup_get_subdomain_base();
}
	/**
	 * Return the base URL for subdomain installations of WordPress Multisite.
	 *
	 * Replaces bp_blogs_get_subdomain_base()
	 *
	 * @since BuddyPress 2.1.0
	 *
	 * @return string The base URL - eg, 'example.com' for site_url() example.com or www.example.com.
	 */
function bp_signup_get_subdomain_base() {
	global $current_site;

	// In case plugins are still using this filter.
	$subdomain_base = apply_filters( 'bp_blogs_subdomain_base', preg_replace( '|^www\.|', '', $current_site->domain ) . $current_site->path );

	/**
	 * Filters the base URL for subdomain installations of WordPress Multisite.
	 *
	 * @since BuddyPress 2.1.0
	 *
	 * @param string $subdomain_base The base URL - eg, 'example.com' for
	 *                               site_url() example.com or www.example.com.
	 */
	return apply_filters( 'bp_signup_subdomain_base', $subdomain_base );
}

/**
 * Output the 'signup_blog_titl' value submitted at signup.
 *
 * @since BuddyPress 1.1.0
 */
function bp_signup_blog_title_value() {
	echo bp_get_signup_blog_title_value();
}
	/**
	 * Get the 'signup_blog_title' value submitted at signup.
	 *
	 * @since BuddyPress 1.1.0
	 *
	 * @todo Should be properly escaped.
	 *
	 * @return string
	 */
function bp_get_signup_blog_title_value() {
	$value = '';
	if ( isset( $_POST['signup_blog_title'] ) ) {
		$value = $_POST['signup_blog_title'];
	}

	/**
	 * Filters the 'signup_blog_title' value submitted during signup.
	 *
	 * @since BuddyPress 1.1.0
	 *
	 * @param string $value 'signup_blog_title' value submitted during signup.
	 */
	return apply_filters( 'bp_get_signup_blog_title_value', $value );
}

/**
 * Output the 'signup_blog_privacy' value submitted at signup.
 *
 * @since BuddyPress 1.1.0
 */
function bp_signup_blog_privacy_value() {
	echo bp_get_signup_blog_privacy_value();
}
	/**
	 * Get the 'signup_blog_privacy' value submitted at signup.
	 *
	 * @since BuddyPress 1.1.0
	 *
	 * @todo Should be properly escaped.
	 *
	 * @return string
	 */
function bp_get_signup_blog_privacy_value() {
	$value = '';
	if ( isset( $_POST['signup_blog_privacy'] ) ) {
		$value = $_POST['signup_blog_privacy'];
	}

	/**
	 * Filters the 'signup_blog_privacy' value submitted during signup.
	 *
	 * @since BuddyPress 1.1.0
	 *
	 * @param string $value 'signup_blog_privacy' value submitted during signup.
	 */
	return apply_filters( 'bp_get_signup_blog_privacy_value', $value );
}

/**
 * Output the avatar dir used during signup.
 *
 * @since BuddyPress 1.1.0
 */
function bp_signup_avatar_dir_value() {
	echo bp_get_signup_avatar_dir_value();
}
	/**
	 * Get the avatar dir used during signup.
	 *
	 * @since BuddyPress 1.1.0
	 *
	 * @return string
	 */
function bp_get_signup_avatar_dir_value() {
	$bp = buddypress();

	// Check if signup_avatar_dir is passed.
	if ( ! empty( $_POST['signup_avatar_dir'] ) ) {
		$signup_avatar_dir = $_POST['signup_avatar_dir'];
	}

	// If not, check if global is set.
	elseif ( ! empty( $bp->signup->avatar_dir ) ) {
		$signup_avatar_dir = $bp->signup->avatar_dir;
	}

	// If not, set false.
	else {
		$signup_avatar_dir = false;
	}

	/**
	 * Filters the avatar dir used during signup.
	 *
	 * @since BuddyPress 1.1.0
	 *
	 * @param string|bool $signup_avatar_dir Avatar dir used during signup or false.
	 */
	return apply_filters( 'bp_get_signup_avatar_dir_value', $signup_avatar_dir );
}

/**
 * Output the current signup step.
 *
 * @since BuddyPress 1.1.0
 */
function bp_current_signup_step() {
	echo bp_get_current_signup_step();
}
	/**
	 * Get the current signup step.
	 *
	 * @since BuddyPress 1.1.0
	 *
	 * @return string
	 */
function bp_get_current_signup_step() {
	return buddypress()->signup->step;
}

/**
 * Output the user avatar during signup.
 *
 * @since BuddyPress 1.1.0
 *
 * @see bp_get_signup_avatar() for description of arguments.
 *
 * @param array|string $args See {@link bp_get_signup_avatar(}.
 */
function bp_signup_avatar( $args = '' ) {
	echo bp_get_signup_avatar( $args );
}
	/**
	 * Get the user avatar during signup.
	 *
	 * @since BuddyPress 1.1.0
	 *
	 * @see bp_core_fetch_avatar() for description of arguments.
	 *
	 * @param array|string $args {
	 *     Array of optional arguments.
	 *     @type int    $size  Height/weight in pixels. Default: value of
	 *                         bp_core_avatar_full_width().
	 *     @type string $class CSS class. Default: 'avatar'.
	 *     @type string $alt   HTML 'alt' attribute. Default: 'Your Avatar'.
	 * }
	 * @return string
	 */
function bp_get_signup_avatar( $args = '' ) {
	$bp = buddypress();

	$defaults = array(
		'size'  => bp_core_avatar_full_width(),
		'class' => 'avatar',
		'alt'   => __( 'Your Profile Photo', 'buddyboss' ),
	);

	$r = bp_parse_args( $args, $defaults );
	extract( $r, EXTR_SKIP );

	// Avatar DIR is found.
	if ( $signup_avatar_dir = bp_get_signup_avatar_dir_value() ) {
		$gravatar_img = bp_core_fetch_avatar(
			array(
				'item_id'    => $signup_avatar_dir,
				'object'     => 'signup',
				'avatar_dir' => 'avatars/signups',
				'type'       => 'full',
				'width'      => $size,
				'height'     => $size,
				'alt'        => $alt,
				'class'      => $class,
			)
		);

		// No avatar DIR was found.
	} else {

		$gravatar_url = bb_attachments_get_default_profile_group_avatar_image( array( 'object' => 'user' ) );

		if ( empty( $gravatar_url ) ) {

			// Set default gravatar type.
			if ( empty( $bp->grav_default->user ) ) {
				$default_grav = 'wavatar';
			}

			$default_grav = $bp->grav_default->user;

			/**
			 * Filters the base Gravatar url used for signup avatars when no avatar dir found.
			 *
			 * @since BuddyPress 1.0.2
			 *
			 * @param string $value Gravatar url to use.
			 */
			$gravatar_url    = apply_filters( 'bp_gravatar_url', 'https://www.gravatar.com/avatar/' );
			$md5_lcase_email = md5( strtolower( bp_get_signup_email_value() ) );
			$gravatar_url    = $gravatar_url . $md5_lcase_email . '?d=' . $default_grav . '&amp;s=' . $size;
		}

		$gravatar_img = '<img src="' . esc_url( $gravatar_url ) . '" width="' . esc_attr( $size ) . '" height="' . esc_attr( $size ) . '" alt="' . esc_attr( $alt ) . '" class="' . esc_attr( $class ) . '" />';
	}

	/**
	 * Filters the user avatar during signup.
	 *
	 * @since BuddyPress 1.1.0
	 *
	 * @param string $gravatar_img Avatar HTML image tag.
	 * @param array  $args         Array of parsed args for avatar query.
	 */
	return apply_filters( 'bp_get_signup_avatar', $gravatar_img, $args );
}

/**
 * Output whether signup is allowed.
 *
 * @since BuddyPress 1.1.0
 *
 * @todo Remove this function. Echoing a bool is pointless.
 */
function bp_signup_allowed() {
	echo bp_get_signup_allowed();
}
	/**
	 * Is user signup allowed?
	 *
	 * @since BuddyPress 1.1.0
	 *
	 * @return bool
	 */
function bp_get_signup_allowed() {
	/**
	 * Filters whether or not new signups are allowed.
	 *
	 * @since BuddyPress 1.5.0
	 *
	 * @param bool $signup_allowed Whether or not new signups are allowed.
	 */
	return apply_filters( 'bp_get_signup_allowed', (bool) bp_get_option( 'users_can_register' ) );
}

/**
 * Hook member activity feed to <head>.
 *
 * @since BuddyPress 1.5.0
 */
function bp_members_activity_feed() {
	if ( ! bp_is_active( 'activity' ) || ! bp_is_user() ) {
		return;
	}
	?>

	<link rel="alternate" type="application/rss+xml" title="<?php bloginfo( 'name' ); ?> | <?php bp_displayed_user_fullname(); ?> | <?php _e( 'Activity RSS Feed', 'buddyboss' ); ?>" href="<?php bp_member_activity_feed_link(); ?>" />

	<?php
}
add_action( 'bp_head', 'bp_members_activity_feed' );

/**
 * Output a link to a members component subpage.
 *
 * @since BuddyPress 1.5.0
 *
 * @see bp_get_members_component_link() for description of parameters.
 *
 * @param string      $component See {@bp_get_members_component_link()}.
 * @param string      $action See {@bp_get_members_component_link()}.
 * @param string      $query_args See {@bp_get_members_component_link()}.
 * @param string|bool $nonce See {@bp_get_members_component_link()}.
 */
function bp_members_component_link( $component, $action = '', $query_args = '', $nonce = false ) {
	echo esc_url( bp_get_members_component_link( $component, $action, $query_args, $nonce ) );
}
	/**
	 * Generate a link to a members component subpage.
	 *
	 * @since BuddyPress 1.5.0
	 *
	 * @param string       $component  ID of the component (eg 'friends').
	 * @param string       $action     Optional. 'action' slug (eg 'invites').
	 * @param array|string $query_args Optional. Array of URL params to add to the
	 *                                 URL. See {@link add_query_arg()} for format.
	 * @param array|bool   $nonce      Optional. If provided, the URL will be passed
	 *                                 through wp_nonce_url() with $nonce as the
	 *                                 action string.
	 * @return string
	 */
function bp_get_members_component_link( $component, $action = '', $query_args = '', $nonce = false ) {

	// Must be displayed user.
	if ( ! bp_displayed_user_id() ) {
		return;
	}

	$bp = buddypress();

	// Append $action to $url if there is no $type.
	if ( ! empty( $action ) ) {
		$url = bp_displayed_user_domain() . $bp->{$component}->slug . '/' . $action;
	} else {
		$url = bp_displayed_user_domain() . $bp->{$component}->slug;
	}

	// Add a slash at the end of our user url.
	$url = trailingslashit( $url );

	// Add possible query arg.
	if ( ! empty( $query_args ) && is_array( $query_args ) ) {
		$url = add_query_arg( $query_args, $url );
	}

	// To nonce, or not to nonce...
	if ( true === $nonce ) {
		$url = wp_nonce_url( $url );
	} elseif ( is_string( $nonce ) ) {
		$url = wp_nonce_url( $url, $nonce );
	}

	// Return the url, if there is one.
	if ( ! empty( $url ) ) {
		return $url;
	}
}


/**
 * Output the Switch button.
 *
 * @since BuddyBoss 1.0.0
 *
 * @param int   $user_id The user ID of the person we want to switch to.
 * @param array $button_args See BP_Button class for more information.
 */
function bp_add_switch_button( $user_id, $button_args = array() ) {
	echo bp_get_add_switch_button( $user_id, $button_args );
}

/**
 * Returns a view as/back to button for a given user depending on the switching status.
 *
 * Checks to see if the admin user is already switched.  If is switched, returns
 * "Back to Admin" button; if not switched, returns "View As" button.
 *
 * @param int   $user_id The user ID of the person we want to switch to.
 * @param array $button_args See BP_Button class for more information.
 *
 * @return mixed String of the button on success.  Boolean false on failure.
 * @uses bp_get_button() Renders a button using the BP Button API
 * @since BuddyBoss 1.0.0
 */
function bp_get_add_switch_button( $user_id, $button_args = array() ) {

	if ( ! $user_id ) {
		return false;
	}
    
	// If user is pending then view as button should not show to admin.
	if ( ! bp_is_user_active( $user_id ) ) {
		return false;
	}

	$old_user = bp_current_member_switched();

	$button_args = bp_parse_args( $button_args, get_class_vars( 'BP_Button' ) );

	$user = get_userdata( $user_id );
	$link = BP_Core_Members_Switching::maybe_switch_url( $user );

	if ( ! $link ) {
		return;
	}

	$link = add_query_arg(
		array(
			'redirect_to' => urlencode( bp_core_get_user_domain( $user->ID ) ),
		),
		$link
	);

	if ( $old_user && ( $old_user->ID === $user->ID || bp_is_my_profile() ) ) {
		$button = bp_parse_args(
			array(
				'id'                => 'member_switch',
				'component'         => 'members',
				'must_be_logged_in' => true,
				'block_self'        => false,
				'wrapper_class'     => 'switch-button back-to-admin',
				'wrapper_id'        => 'switch-button-' . $old_user->ID,
				'link_href'         => esc_url( $link ),
				'link_text'         => __( 'Back to Admin', 'buddyboss' ),
				'link_id'           => 'switch-' . $old_user->ID,
				'link_rel'          => 'stop',
				'link_class'        => 'switch-button back-to-admin stop bp-toggle-action-button outline',
			),
			$button_args
		);
	} else {
		$button = bp_parse_args(
			array(
				'id'                => 'member_switch',
				'component'         => 'members',
				'must_be_logged_in' => true,
				'block_self'        => true,
				'wrapper_class'     => 'switch-button view-as',
				'wrapper_id'        => 'switch-button-' . $user_id,
				'link_href'         => esc_url( $link ),
				'link_text'         => __( 'View As', 'buddyboss' ),
				'link_id'           => 'switch-' . $user_id,
				'link_rel'          => 'start',
				'link_class'        => 'switch-button view-as start outline',
			),
			$button_args
		);
	}

	/**
	 * Filters the HTML for the follow button.
	 *
	 * @since BuddyBoss 1.0.0
	 *
	 * @param string $button HTML markup for follow button.
	 */
	return bp_get_button( apply_filters( 'bp_get_add_switch_button', $button ) );
}

/**
 * Output the user confirm email address submitted during signup.
 *
 * @since BuddyBoss 1.1.6
 */
function bp_signup_confirm_email_value() {
	echo bp_get_signup_confirm_email_value();
}
	/**
	 * Get the confirm email address submitted during signup.
	 *
	 * @since BuddyBoss 1.1.6
	 *
	 * @return string
	 */
function bp_get_signup_confirm_email_value() {
	$value = '';
	if ( isset( $_POST['signup_email_confirm'] ) ) {
		$value = sanitize_email( $_POST['signup_email_confirm'] );
	}

	/**
	 * Filters the confirm email address submitted during signup.
	 *
	 * @since BuddyBoss 1.1.6
	 *
	 * @param string $value Email address submitted during signup.
	 */
	return apply_filters( 'bp_get_signup_confirm_email_value', $value );
}

/**
 * Get user followers count.
 *
 * @param int|null $user_id user id to get followers count. If user id is null then get current logged-in user id.
 *                 Default false.
 *
 * @since BuddyBoss 1.9.1
 *
 * @return string
 */
function bb_get_followers_count( $user_id = false ) {

	if ( ! function_exists( 'bp_is_active' ) && ! function_exists( 'bp_is_activity_follow_active' ) ) {
		return;
	}

	$is_follow_active = bp_is_active( 'activity' ) && bp_is_activity_follow_active();

	if ( false === $user_id ) {
		$user_id = bp_displayed_user_id();
	}

	if ( $is_follow_active && is_user_logged_in() ) {
		$total_followers = 0;
		$follower_ids    = bp_get_follower_ids( array( 'user_id' => $user_id ) );

		if ( ! empty( $follower_ids ) ) {
			$total_followers = count( explode( ',', $follower_ids ) );
		}

		if ( 0 === $total_followers ) {
			$followers = sprintf(
				/* translators: Follower string. */
				'<strong>0</strong> %1$s',
				esc_html__( 'followers', 'buddyboss' ),
			);
		} elseif ( 1 === $total_followers ) {
			$followers = sprintf(
				/* translators: Follower string. */
				'<strong>1</strong> %1$s',
				esc_html__( 'follower', 'buddyboss' ),
			);
		} else {
			$followers = sprintf(
				/* translators: 1: Total followers count. 2: Follower string. */
				'<strong>%1$s</strong> %2$s',
				$total_followers,
				esc_html__( 'followers', 'buddyboss' ),
			);
		}
		?>

		<div class="followers-wrap"><?php echo wp_kses_post( $followers ); ?></div>
		<?php
	}
}

/**
 * Get user following count.
 *
 * @param int|null $user_id user id to get following count. If user id is null then get current logged-in user id.
 *                 Default false.
 *
 * @since BuddyBoss 1.9.1
 *
 * @return string
 */
function bb_get_following_count( $user_id = false ) {

	if ( ! function_exists( 'bp_is_active' ) && ! function_exists( 'bp_is_activity_follow_active' ) ) {
		return;
	}

	$is_follow_active = bp_is_active( 'activity' ) && bp_is_activity_follow_active();

	if ( false === $user_id ) {
		$user_id = bp_displayed_user_id();
	}

	if ( $is_follow_active && is_user_logged_in() ) {
		$total_following = 0;
		$following_ids   = bp_get_following_ids( array( 'user_id' => $user_id ) );

		if ( ! empty( $following_ids ) ) {
			$total_following = count( explode( ',', $following_ids ) );
		}

		if ( 0 === $total_following ) {
			$following = sprintf(
				/* translators: Following string. */
				'<strong>0</strong> %1$s',
				esc_html__( 'following', 'buddyboss' ),
			);
		} elseif ( 1 === $total_following ) {
			$following = sprintf(
				/* translators: Following string. */
				'<strong>1</strong> %1$s',
				esc_html__( 'following', 'buddyboss' ),
			);
		} else {
			$following = sprintf(
				/* translators: 1: Total following count. 2: Following string. */
				'<strong>%1$s</strong> %2$s',
				$total_following,
				esc_html__( 'following', 'buddyboss' ),
			);
		}
		?>

		<div class="following-wrap"><?php echo wp_kses_post( $following ); ?></div>
		<?php
	}
}


/**
 * Get member actions for member directories.
 *
 * @since BuddyBoss 1.9.1
 *
 * @param int    $user_id Member ID.
 * @param string $button_type Which type of buttons need "primary", "secondary" or "both".
 *                            Default false.
 *
 * @return array|string Return the member actions.
 */
function bb_member_directories_get_profile_actions( $user_id, $button_type = false ) {
	$buttons = array();

	// Member directories profile actions.
	$enabled_follow_action  = function_exists( 'bb_enabled_member_directory_profile_action' ) ? bb_enabled_member_directory_profile_action( 'follow' ) : true;
	$enabled_connect_action = function_exists( 'bb_enabled_member_directory_profile_action' ) ? bb_enabled_member_directory_profile_action( 'connect' ) : true;
	$enabled_message_action = function_exists( 'bb_enabled_member_directory_profile_action' ) ? bb_enabled_member_directory_profile_action( 'message' ) : true;

	// Member directories primary actions.
	$primary_action_btn = function_exists( 'bb_get_member_directory_primary_action' ) ? bb_get_member_directory_primary_action() : '';

	// Is follow active or not?
	$is_follow_active = bp_is_active( 'activity' ) && function_exists( 'bp_is_activity_follow_active' ) && bp_is_activity_follow_active() && $enabled_follow_action;

	// Show "Message" button or not?
	$is_message_active = apply_filters( 'bb_member_loop_show_message_button', (bool) $enabled_message_action && bp_is_active( 'messages' ), $user_id, bp_loggedin_user_id() );

	// Is follow active or not?
	$is_friend_active = ( $enabled_connect_action && bp_is_active( 'friends' ) && bp_loggedin_user_id() !== $user_id );

	// Get actions button arguments to add tooltips and "<i>" tag.
	$primary_button_args   = function_exists( 'bb_member_get_profile_action_arguments' ) ? bb_member_get_profile_action_arguments() : array();
	$secondary_button_args = function_exists( 'bb_member_get_profile_action_arguments' ) ? bb_member_get_profile_action_arguments( 'directory', 'secondary' ) : array();

	if ( 'follow' === $primary_action_btn ) {
		// Primary button.
		$buttons['primary'] = $is_follow_active ? bp_get_add_follow_button( $user_id, bp_loggedin_user_id(), $primary_button_args ) : '';

		// Secondary buttons.
		$buttons['secondary'] = $is_friend_active ? bp_get_add_friend_button( $user_id, false, $secondary_button_args ) : '';

		if ( $is_message_active ) {
			add_filter( 'bp_displayed_user_id', 'bb_member_loop_set_member_id' );
			add_filter( 'bp_is_my_profile', 'bb_member_loop_set_my_profile' );
			$buttons['secondary'] .= bp_get_send_message_button( $secondary_button_args );
			remove_filter( 'bp_displayed_user_id', 'bb_member_loop_set_member_id' );
			remove_filter( 'bp_is_my_profile', 'bb_member_loop_set_my_profile' );
		}
	} elseif ( 'connect' === $primary_action_btn ) {
		// Primary button.
		$buttons['primary'] = $is_friend_active ? bp_get_add_friend_button( $user_id, false, $primary_button_args ) : '';

		// Secondary buttons.
		$buttons['secondary'] = $is_follow_active ? bp_get_add_follow_button( $user_id, bp_loggedin_user_id(), $secondary_button_args ) : '';

		if ( $is_message_active ) {
			add_filter( 'bp_displayed_user_id', 'bb_member_loop_set_member_id' );
			add_filter( 'bp_is_my_profile', 'bb_member_loop_set_my_profile' );
			$buttons['secondary'] .= bp_get_send_message_button( $secondary_button_args );
			remove_filter( 'bp_displayed_user_id', 'bb_member_loop_set_member_id' );
			remove_filter( 'bp_is_my_profile', 'bb_member_loop_set_my_profile' );
		}
	} elseif ( 'message' === $primary_action_btn ) {
		// Primary button.
		if ( $is_message_active ) {
			add_filter( 'bp_displayed_user_id', 'bb_member_loop_set_member_id' );
			add_filter( 'bp_is_my_profile', 'bb_member_loop_set_my_profile' );
			$buttons['primary'] = bp_get_send_message_button( $primary_button_args );
			remove_filter( 'bp_displayed_user_id', 'bb_member_loop_set_member_id' );
			remove_filter( 'bp_is_my_profile', 'bb_member_loop_set_my_profile' );
		}

		// Secondary buttons.
		$buttons['secondary']  = $is_follow_active ? bp_get_add_follow_button( $user_id, bp_loggedin_user_id(), $secondary_button_args ) : '';
		$buttons['secondary'] .= $is_friend_active ? bp_get_add_friend_button( $user_id, false, $secondary_button_args ) : '';
	} else {
		// Primary button.
		$buttons['primary'] = '';

		// Secondary buttons.
		$buttons['secondary']  = $is_follow_active ? bp_get_add_follow_button( $user_id, bp_loggedin_user_id(), $secondary_button_args ) : '';
		$buttons['secondary'] .= $is_friend_active ? bp_get_add_friend_button( $user_id, false, $secondary_button_args ) : '';

		if ( $is_message_active ) {
			add_filter( 'bp_displayed_user_id', 'bb_member_loop_set_member_id' );
			add_filter( 'bp_is_my_profile', 'bb_member_loop_set_my_profile' );
			$buttons['secondary'] .= bp_get_send_message_button( $secondary_button_args );
			remove_filter( 'bp_displayed_user_id', 'bb_member_loop_set_member_id' );
			remove_filter( 'bp_is_my_profile', 'bb_member_loop_set_my_profile' );
		}
	}

	/**
	 * Filters the member actions for member directories.
	 *
	 * @since BuddyBoss 1.9.1
	 *
	 * @param array  $buttons     Member profile actions.
	 * @param int    $user_id     Member ID.
	 * @param string $button_type Which type of buttons need "primary", "secondary" or "both".
	 */
	$buttons = apply_filters( 'bb_member_directories_get_profile_actions', $buttons, $user_id, $button_type );

	if ( ! $button_type ) {
		return $buttons;
	} elseif ( 'primary' === $button_type ) {
		return $buttons['primary'];
	} elseif ( 'secondary' === $button_type ) {
		return $buttons['secondary'];
	}
}

/**
 * Get the member last activity time.
 *
 * @since BuddyBoss 2.0.0
 *
 * @param array $args Array of arguments.
 *
 * @return mixed|string|void
 */
function bb_get_member_last_activity_time( $args = array() ) {
	global $members_template;

	// Parse the activity format.
	$r = bp_parse_args(
		$args,
		array(
			'active_format' => true,
			'relative'      => true,
		)
	);

	// Backwards compatibility for anyone forcing a 'true' active_format.
	if ( true === $r['active_format'] ) {
		$r['active_format'] = __( '%s', 'buddyboss' );
	}

	// Member has logged in at least one time.
	if ( isset( $members_template->member->last_activity ) ) {
		// We do not want relative time, so return now.
		// @todo Should the 'bp_member_last_active' filter be applied here?
		if ( ! $r['relative'] ) {
			return esc_attr( $members_template->member->last_activity );
		}

		// Backwards compatibility for pre 1.5 'ago' strings.
		$last_activity = ! empty( $r['active_format'] )
			? bp_core_get_last_activity( $members_template->member->last_activity, $r['active_format'] )
			: bp_core_time_since( $members_template->member->last_activity );

		// Member has never logged in or been active.
	} else {
		$last_activity = esc_html__( 'Never active', 'buddyboss' );
	}

	/**
	 * Filters the current members last active time.
	 *
	 * @since BuddyBoss 2.0.0
	 *
	 * @param string $last_activity Formatted time since last activity.
	 * @param array  $r             Array of parsed arguments for query.
	 */
	return apply_filters( 'bb_get_member_last_activity_time', $last_activity, $r );
}

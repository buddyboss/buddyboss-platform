<?php
/**
 * BuddyPress Member Functions.
 *
 * Functions specific to the members component.
 *
 * @package BuddyBoss\Members\Functions
 * @since BuddyPress 1.5.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Check for the existence of a Members directory page.
 *
 * @since BuddyPress 1.5.0
 *
 * @return bool True if found, otherwise false.
 */
function bp_members_has_directory() {
	$bp = buddypress();

	return (bool) ! empty( $bp->pages->members->id );
}

/**
 * Define the slug constants for the Members component.
 *
 * Handles the three slug constants used in the Members component -
 * BP_MEMBERS_SLUG, BP_REGISTER_SLUG, and BP_ACTIVATION_SLUG. If these
 * constants are not overridden in wp-config.php or bp-custom.php, they are
 * defined here to match the slug of the corresponding WP pages.
 *
 * In general, fallback values are only used during initial BP page creation,
 * when no slugs have been explicitly defined.
 *
 * @since BuddyPress 1.5.0
 */
function bp_core_define_slugs() {
	$bp = buddypress();

	// No custom members slug.
	if ( ! defined( 'BP_MEMBERS_SLUG' ) ) {
		if ( ! empty( $bp->pages->members ) ) {
			define( 'BP_MEMBERS_SLUG', $bp->pages->members->slug );
		} else {
			define( 'BP_MEMBERS_SLUG', 'members' );
		}
	}

	// No custom registration slug.
	if ( ! defined( 'BP_REGISTER_SLUG' ) ) {
		if ( ! empty( $bp->pages->register ) ) {
			define( 'BP_REGISTER_SLUG', $bp->pages->register->slug );
		} else {
			define( 'BP_REGISTER_SLUG', 'register' );
		}
	}

	// No custom activation slug.
	if ( ! defined( 'BP_ACTIVATION_SLUG' ) ) {
		if ( ! empty( $bp->pages->activate ) ) {
			define( 'BP_ACTIVATION_SLUG', $bp->pages->activate->slug );
		} else {
			define( 'BP_ACTIVATION_SLUG', 'activate' );
		}
	}
}
add_action( 'bp_setup_globals', 'bp_core_define_slugs', 11 );

/**
 * Fetch an array of users based on the parameters passed.
 *
 * Since BuddyPress 1.7, bp_core_get_users() uses BP_User_Query. If you
 * need backward compatibility with BP_Core_User::get_users(), filter the
 * bp_use_legacy_user_query value, returning true.
 *
 * @since BuddyPress 1.2.0
 * @since BuddyPress 7.0.0 Added `xprofile_query` parameter. Added `user_ids` parameter.
 * @since BuddyBoss 2.3.90 Added `xprofile_query` parameter. Added `user_ids` parameter.
 *
 * @param array|string $args {
 *     Array of arguments. All are optional. See {@link BP_User_Query} for
 *     a more complete description of arguments.
 *     @type string       $type                Sort order. Default: 'active'.
 *     @type int          $user_id             Limit results to friends of a user. Default: false.
 *     @type mixed        $exclude             IDs to exclude from results. Default: false.
 *     @type string       $search_terms        Limit to users matching search terms. Default: false.
 *     @type string       $meta_key            Limit to users with a meta_key. Default: false.
 *     @type string       $meta_value          Limit to users with a meta_value (with meta_key). Default: false.
 *     @type array|string $member_type         Array or comma-separated string of profile types.
 *     @type array|string $member_type__in     Array or comma-separated string of profile types.
 *                                             `$member_type` takes precedence over this parameter.
 *     @type array|string $member_type__not_in Array or comma-separated string of profile types to be excluded.
 *     @type mixed        $include             Limit results by user IDs. Default: false.
 *     @type mixed        $user_ids            IDs corresponding to the users. Default: false.
 *     @type int          $per_page            Results per page. Default: 20.
 *     @type int          $page                Page of results. Default: 1.
 *     @type bool         $populate_extras     Fetch optional extras. Default: true.
 *     @type string|bool  $count_total         How to do total user count. Default: 'count_query'.
 * }
 * @return array
 */
function bp_core_get_users( $args = '' ) {
	static $bp_core_get_users = array();
	// Parse the user query arguments.
	$r = bp_parse_args(
		$args,
		array(
			'type'                => 'active',      // Active, newest, alphabetical, random or popular.
			'user_id'             => false,         // Pass a user_id to limit to only friend connections for this user.
			'exclude'             => false,         // Users to exclude from results.
			'search_terms'        => false,         // Limit to users that match these search terms.
			'meta_key'            => false,         // Limit to users who have this piece of usermeta.
			'meta_value'          => false,         // With meta_key, limit to users where usermeta matches this value.
			'member_type'         => '',
			'member_type__in'     => '',
			'member_type__not_in' => '',
			'include'             => false,         // Pass comma separated list of user_ids to limit to only these users.
			'user_ids'            => false,
			'per_page'            => 20,            // The number of results to return per page.
			'page'                => 1,             // The page to return if limiting per page.
			'populate_extras'     => true,          // Fetch the last active, where the user is a friend, total friend count, latest update.
			'xprofile_query'      => false,
			'count_total'         => 'count_query', // What kind of total user count to do, if any. 'count_query', 'sql_calc_found_rows', or false.
		),
		'core_get_users'
	);

	// For legacy users. Use of BP_Core_User::get_users() is deprecated.
	if ( apply_filters( 'bp_use_legacy_user_query', false, __FUNCTION__, $r ) ) {
		$retval = BP_Core_User::get_users(
			$r['type'],
			$r['per_page'],
			$r['page'],
			$r['user_id'],
			$r['include'],
			$r['search_terms'],
			$r['populate_extras'],
			$r['exclude'],
			$r['meta_key'],
			$r['meta_value']
		);

		// Default behavior as of BuddyPress 1.7.0.
	} else {

		// Get users like we were asked to do...
		$cache_key = 'bp_core_get_users_' . md5( maybe_serialize( $r ) );
		if ( ! isset( $bp_core_get_users[ $cache_key ] ) ) {
			$users = new BP_User_Query( $r );

			$bp_core_get_users[ $cache_key ] = $users;
		} else {
			$users = $bp_core_get_users[ $cache_key ];
		}

		// ...but reformat the results to match bp_core_get_users() behavior.
		$retval = array(
			'users' => array_values( $users->results ),
			'total' => $users->total_users,
		);
	}

	/**
	 * Filters the results of the user query.
	 *
	 * @since BuddyPress 1.2.0
	 *
	 * @param array $retval Array of users for the current query.
	 * @param array $r      Array of parsed query arguments.
	 */
	return apply_filters( 'bp_core_get_users', $retval, $r );
}

/**
 * Return the domain for the passed user: e.g. http://example.com/members/andy/.
 *
 * @since BuddyPress 1.0.0
 *
 * @param int         $user_id       The ID of the user.
 * @param string|bool $user_nicename Optional. user_nicename of the user.
 * @param string|bool $user_login    Optional. user_login of the user.
 * @return string
 */
function bp_core_get_user_domain( $user_id = 0, $user_nicename = false, $user_login = false ) {

	if ( empty( $user_id ) ) {
		return;
	}

	$username = bp_core_get_username( $user_id, $user_nicename, $user_login );

	if ( 'unique_identifier' === bb_get_profile_slug_format() ) {
		$username = bb_core_get_user_slug( $user_id );
		if ( empty( $username ) ) {
			$username = bb_set_user_profile_slug( $user_id );
		}
	}

	if ( bp_is_username_compatibility_mode() ) {
		$username = rawurlencode( $username );
	}

	$after_domain = bp_core_enable_root_profiles() ? $username : bp_get_members_root_slug() . '/' . $username;
	$domain       = trailingslashit( bp_get_root_domain() . '/' . $after_domain );

	// Don't use this filter.  Subject to removal in a future release.
	// Use the 'bp_core_get_user_domain' filter instead.
	$domain = apply_filters( 'bp_core_get_user_domain_pre_cache', $domain, $user_id, $user_nicename, $user_login );

	$user_data = get_userdata( $user_id );
	if ( empty( $user_data ) ) {
		$domain = '';
	}
	/**
	 * Filters the domain for the passed user.
	 *
	 * @since BuddyPress 1.0.1
	 *
	 * @param string $domain        Domain for the passed user.
	 * @param int    $user_id       ID of the passed user.
	 * @param string $user_nicename User nicename of the passed user.
	 * @param string $user_login    User login of the passed user.
	 */
	return apply_filters( 'bp_core_get_user_domain', $domain, $user_id, $user_nicename, $user_login );
}

/**
 * Fetch everything in the wp_users table for a user, without any usermeta.
 *
 * @since BuddyPress 1.2.0
 *
 * @param  int $user_id The ID of the user.
 * @return array|bool Array of data on success, boolean false on failure.
 */
function bp_core_get_core_userdata( $user_id = 0 ) {
	if ( empty( $user_id ) ) {
		return false;
	}

	// Get core user data.
	$userdata = BP_Core_User::get_core_userdata( $user_id );

	/**
	 * Filters the userdata for a passed user.
	 *
	 * @since BuddyPress 1.2.0
	 *
	 * @param array|bool $userdata Array of user data for a passed user on success, boolean false on failure.
	 */
	return apply_filters( 'bp_core_get_core_userdata', $userdata );
}

/**
 * Return the ID of a user, based on user_login.
 *
 * No longer used.
 *
 * @todo Deprecate.
 *
 * @param string $user_login user_login of the user being queried.
 * @return int
 */
function bp_core_get_displayed_userid( $user_login ) {
	return apply_filters( 'bp_core_get_displayed_userid', bp_core_get_userid( $user_login ) );
}

/**
 * Return the user ID based on a user's user_login.
 *
 * @since BuddyPress 1.0.0
 *
 * @param string $username user_login to check.
 * @return int|null The ID of the matched user on success, null on failure.
 */
function bp_core_get_userid( $username = '' ) {
	if ( empty( $username ) ) {
		return false;
	}

	$user = get_user_by( 'login', $username );

	/**
	 * Filters the ID of a user, based on user_login.
	 *
	 * @since BuddyPress 1.0.1
	 *
	 * @param int|null $value    ID of the user or null.
	 * @param string   $username User login to check.
	 */
	return apply_filters( 'bp_core_get_userid', ! empty( $user->ID ) ? $user->ID : null, $username );
}

/**
 * Return the user ID based on user_nicename.
 *
 * @since BuddyPress 1.2.3
 *
 * @param string $user_nicename user_nicename to check.
 * @return int|null The ID of the matched user on success, null on failure.
 */
function bp_core_get_userid_from_nicename( $user_nicename = '' ) {
	if ( empty( $user_nicename ) ) {
		return false;
	}

	$user = get_user_by( 'slug', $user_nicename );

	/**
	 * Filters the user ID based on user_nicename.
	 *
	 * @since BuddyPress 1.2.3
	 *
	 * @param int|null $value         ID of the user or null.
	 * @param string   $user_nicename User nicename to check.
	 */
	return apply_filters( 'bp_core_get_userid_from_nicename', ! empty( $user->ID ) ? $user->ID : null, $user_nicename );
}

/**
 * Return the user ID based on nickname.
 *
 * @since BuddyBoss 1.0.0
 *
 * @param string $nickname nickname to check.
 * @return int|null The ID of the matched user on success, null on failure.
 */
function bp_core_get_userid_from_nickname( $nickname ) {
	if ( empty( $nickname ) ) {
		return false;
	}

	static $cache = array();

	$cache_key = 'bp_core_get_userid_from_nickname' . $nickname;
	if ( ! isset( $cache[ $cache_key ] ) ) {
		$cache[ $cache_key ] = get_users(
			array(
				'meta_key'    => 'nickname',
				'meta_value'  => $nickname,
				'number'      => 1,
				'count_total' => false,
			)
		);

		$user = $cache[ $cache_key ] ? $cache[ $cache_key ][0] : null;
	} else {
		$user = $cache[ $cache_key ] ? $cache[ $cache_key ][0] : null;
	}

	return apply_filters( 'bp_core_get_userid_from_nickname', ! empty( $user->ID ) ? $user->ID : null, $nickname );
}

/**
 * Retrieve member info based on nickname.
 *
 * @since BuddyBoss 1.0.0
 *
 * @param string $nickname nickname to check.
 * @return WP_User|null WP_User object on success, null on failure.
 */
function bp_get_user_by_nickname( $nickname ) {
	$user_id = bp_core_get_userid_from_nickname( $nickname );
	return $user_id ? get_user_by( 'ID', $user_id ) : null;
}

/**
 * Return the username for a user based on their user id.
 *
 * This function is sensitive to the BP_ENABLE_USERNAME_COMPATIBILITY_MODE,
 * so it will return the user_login or user_nicename as appropriate.
 *
 * @since BuddyPress 1.0.0
 *
 * @param int         $user_id       User ID to check.
 * @param string|bool $user_nicename Optional. user_nicename of user being checked.
 * @param string|bool $user_login    Optional. user_login of user being checked.
 * @return string The username of the matched user or an empty string if no user is found.
 */
function bp_core_get_username( $user_id = 0, $user_nicename = false, $user_login = false ) {

	if ( ! $user_nicename && ! $user_login ) {
		// Pull an audible and maybe use the login over the nicename.
		if ( bp_is_username_compatibility_mode() ) {
			$username = get_the_author_meta( 'login', $user_id );
		} else {
			$username = get_the_author_meta( 'nicename', $user_id );
		}
	} else {
		$username = bp_is_username_compatibility_mode() ? $user_login : $user_nicename;
	}

	/**
	 * Filters the username based on originally provided user ID.
	 *
	 * @since BuddyPress 1.0.1
	 *
	 * @param string $username Username determined by user ID.
	 */
	return apply_filters( 'bp_core_get_username', $username );
}

/**
 * Return the user_nicename for a user based on their user_id.
 *
 * This should be used for linking to user profiles and anywhere else a
 * sanitized and unique slug to a user is needed.
 *
 * @since BuddyPress 1.5.0
 *
 * @param int $user_id User ID to check.
 * @return string The username of the matched user or an empty string if no user is found.
 */
function bp_members_get_user_nicename( $user_id ) {
	/**
	 * Filters the user_nicename based on originally provided user ID.
	 *
	 * @since BuddyPress 1.5.0
	 *
	 * @param string $username User nice name determined by user ID.
	 */
	return apply_filters( 'bp_members_get_user_nicename', get_the_author_meta( 'nicename', $user_id ) );
}

/**
 * Return the email address for the user based on user ID.
 *
 * @since BuddyPress 1.0.0
 *
 * @param int $uid User ID to check.
 * @return string The email for the matched user. Empty string if no user
 *                matches the $user_id.
 */
function bp_core_get_user_email( $user_id ) {
	/**
	 * Filters the user email for user based on user ID.
	 *
	 * @since BuddyPress 1.0.1
	 *
	 * @param string $email Email determined for the user.
	 */
	return apply_filters( 'bp_core_get_user_email', get_the_author_meta( 'email', $user_id ) );
}

/**
 * Return a HTML formatted link for a user with the user's full name as the link text.
 *
 * Eg: <a href="http://andy.example.com/">Andy Peatling</a>
 *
 * Optional parameters will return just the name or just the URL.
 *
 * @since BuddyPress 1.0.0
 *
 * @param int  $user_id   User ID to check.
 * @param bool $no_anchor Disable URL and HTML and just return full name.
 *                        Default: false.
 * @param bool $just_link Disable full name and HTML and just return the URL
 *                        text. Default false.
 * @return string|bool The link text based on passed parameters, or false on
 *                     no match.
 */
function bp_core_get_userlink( $user_id, $no_anchor = false, $just_link = false ) {
	$display_name = bp_core_get_user_displayname( $user_id );

	if ( empty( $display_name ) ) {
		return false;
	}

	if ( ! empty( $no_anchor ) ) {
		return $display_name;
	}

	$url = bp_core_get_user_domain( $user_id );

	if ( ! empty( $just_link ) ) {
		return $url;
	}

	/**
	 * Filters the link text for the passed in user.
	 *
	 * @since BuddyPress 1.2.0
	 *
	 * @param string $value   Link text based on passed parameters.
	 * @param int    $user_id ID of the user to check.
	 */
	return apply_filters( 'bp_core_get_userlink', '<a href="' . $url . '">' . $display_name . '</a>', $user_id );
}

/**
 * Fetch the display name for a group of users.
 *
 * Uses the 'Name' field in xprofile if available. Falls back on WP
 * display_name, and then user_nicename.
 *
 * @since BuddyPress 2.0.0
 *
 * @param array $user_ids Array of user IDs to get display names for.
 * @return array Associative array of the format "id" => "displayname".
 */
function bp_core_get_user_displaynames( $user_ids ) {

	// Sanitize.
	$user_ids = wp_parse_id_list( $user_ids );

	// Remove dupes and empties.
	$user_ids = array_unique( array_filter( $user_ids ) );

	if ( empty( $user_ids ) ) {
		return array();
	}

	// Warm the WP users cache with a targeted bulk update.
	cache_users( $user_ids );

	$retval = array();
	foreach ( $user_ids as $user_id ) {
		$retval[ $user_id ] = bp_core_get_user_displayname( $user_id );
	}

	return $retval;
}

/**
 * Fetch the display name for a user.
 *
 * @since BuddyPress 1.0.1
 *
 * @param int|string|bool $user_id_or_username User ID or username.
 * @return string|bool The display name for the user in question, or false if
 *                     user not found.
 */
function bp_core_get_user_displayname( $user_id_or_username ) {
	if ( empty( $user_id_or_username ) ) {
		return false;
	}

	if ( ! is_numeric( $user_id_or_username ) ) {
		$user_id = bp_core_get_userid( $user_id_or_username );
	} else {
		$user_id = $user_id_or_username;
	}

	if ( empty( $user_id ) ) {
		return false;
	}

	$list_fields = bp_xprofile_get_hidden_fields_for_user( $user_id, bp_loggedin_user_id() );
	if ( empty( $list_fields ) ) {
		$full_name = get_the_author_meta( 'display_name', $user_id );
		if ( empty( $full_name ) ) {
			$full_name = get_the_author_meta( 'nickname', $user_id );
		}
	} else {
		$last_name_field_id = bp_xprofile_lastname_field_id();
		if ( in_array( $last_name_field_id, $list_fields ) ) {
			$last_name = xprofile_get_field_data( $last_name_field_id, $user_id );
			$full_name = str_replace( ' ' . $last_name, '', get_the_author_meta( 'display_name', $user_id ) );
		} else {
			$full_name = get_the_author_meta( 'display_name', $user_id );
		}
	}

	$user_data = get_userdata( $user_id );
	if ( empty( $full_name ) && empty( $user_data ) ) {
		$full_name = __( 'Deleted User', 'buddyboss' );
	}

	/**
	 * Filters the display name for the passed in user.
	 *
	 * @since BuddyPress 1.0.1
	 *
	 * @param string $fullname Display name for the user.
	 * @param int    $user_id  ID of the user to check.
	 */
	return apply_filters( 'bp_core_get_user_displayname', trim( $full_name ), $user_id );
}
add_filter( 'bp_core_get_user_displayname', 'wp_filter_kses' );
add_filter( 'bp_core_get_user_displayname', 'strip_tags', 1 );
add_filter( 'bp_core_get_user_displayname', 'trim' );
add_filter( 'bp_core_get_user_displayname', 'stripslashes' );
add_filter( 'bp_core_get_user_displayname', 'esc_html' );
add_filter( 'bp_core_get_user_displayname', 'wp_specialchars_decode', 16 );

/**
 * Return the user link for the user based on user email address.
 *
 * @since BuddyPress 1.0.0
 *
 * @param string $email The email address for the user.
 * @return string The link to the users home base. False on no match.
 */
function bp_core_get_userlink_by_email( $email ) {
	$user = get_user_by( 'email', $email );

	/**
	 * Filters the user link for the user based on user email address.
	 *
	 * @since BuddyPress 1.0.1
	 *
	 * @param string|bool $value URL for the user if found, otherwise false.
	 */
	return apply_filters( 'bp_core_get_userlink_by_email', bp_core_get_userlink( $user->ID, false, false, true ) );
}

/**
 * Return the user link for the user based on the supplied identifier.
 *
 * @since BuddyPress 1.0.0
 *
 * @param string $username If BP_ENABLE_USERNAME_COMPATIBILITY_MODE is set,
 *                         this should be user_login, otherwise it should
 *                         be user_nicename.
 * @return string|bool The link to the user's domain, false on no match.
 */
function bp_core_get_userlink_by_username( $username ) {
	if ( bp_is_username_compatibility_mode() ) {
		$user_id = bp_core_get_userid( $username );
	} else {
		$user_id = bp_core_get_userid_from_nicename( $username );
	}

	/**
	 * Filters the user link for the user based on username.
	 *
	 * @since BuddyPress 1.0.1
	 *
	 * @param string|bool $value URL for the user if found, otherwise false.
	 */
	return apply_filters( 'bp_core_get_userlink_by_username', bp_core_get_userlink( $user_id, false, false, true ) );
}

/**
 * Return the total number of members for the installation.
 *
 * Note that this is a raw count of non-spam, activated users. It does not
 * account for users who have logged activity (last_active). See
 * {@link bp_core_get_active_member_count()}.
 *
 * @since BuddyPress 1.2.0
 *
 * @return int The total number of members.
 */
function bp_core_get_total_member_count() {
	global $wpdb;

	$count = wp_cache_get( 'bp_total_member_count', 'bp' );

	if ( false === $count ) {
		$status_sql = bp_core_get_status_sql();
		$count      = $wpdb->get_var( "SELECT COUNT(ID) FROM {$wpdb->users} WHERE {$status_sql}" );
		wp_cache_set( 'bp_total_member_count', $count, 'bp' );
	}

	/**
	 * Filters the total number of members for the installation.
	 *
	 * @since BuddyPress 1.2.0
	 *
	 * @param int $count Total number of members.
	 */
	return apply_filters( 'bp_core_get_total_member_count', $count );
}

/**
 * Return the total number of members for the installation.
 *
 * @since BuddyPress 1.2.9.1
 *
 * @return int The total number of members.
 */
function bp_core_get_all_member_count() {
	add_filter( 'bp_ajax_querystring', 'bp_member_object_template_results_members_all_scope', 20, 2 );
	bp_has_members( bp_ajax_querystring( 'members' ) );
	$count = $GLOBALS['members_template']->total_member_count;
	remove_filter( 'bp_ajax_querystring', 'bp_member_object_template_results_members_all_scope', 20, 2 );

	/**
	 * Filters the total number of members for the installation.
	 *
	 * @since BuddyPress 1.2.9.1
	 *
	 * @param int $count Total number of members.
	 */
	return apply_filters( 'bp_core_get_all_member_count', $count );
}

/**
 * Object template results members all scope.
 *
 * @since BuddyBoss 1.2.10
 */
function bp_member_object_template_results_members_all_scope( $querystring, $object ) {
	if ( 'members' !== $object ) {
		return $querystring;
	}

	$querystring = bp_parse_args( $querystring );

	if ( bp_is_active( 'activity' ) && bp_is_activity_follow_active() && isset( $querystring['scope'] ) && 'following' === $querystring['scope'] ) {
		$counts = bp_total_follow_counts();
		if ( ! empty( $counts['following'] ) ) {
			unset( $querystring['include'] );
		}
	}

	$querystring['scope']    = 'all';
	$querystring['page']     = 1;
	$querystring['per_page'] = '1';
	$querystring['user_id']  = 0;
	return http_build_query( $querystring );
}

/**
 * Return the total number of members, limited to those members with last_activity.
 *
 * @since BuddyPress 1.6.0
 *
 * @return int The number of active members.
 */
function bp_core_get_active_member_count() {
	global $wpdb;

	$count = get_transient( 'bp_active_member_count' );
	if ( false === $count ) {
		$bp = buddypress();

		// Avoid a costly join by splitting the lookup.
		if ( is_multisite() ) {
			$sql = "SELECT ID FROM {$wpdb->users} WHERE (user_status != 0 OR deleted != 0 OR user_status != 0)";
		} else {
			$sql = "SELECT ID FROM {$wpdb->users} WHERE user_status != 0";
		}

		$exclude_users     = $wpdb->get_col( $sql );
		$exclude_users_sql = ! empty( $exclude_users ) ? 'AND user_id NOT IN (' . implode( ',', wp_parse_id_list( $exclude_users ) ) . ')' : '';
		$count             = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(user_id) FROM {$bp->members->table_name_last_activity} WHERE component = %s AND type = 'last_activity' {$exclude_users_sql}", $bp->members->id ) );

		set_transient( 'bp_active_member_count', $count );
	}

	/**
	 * Filters the total number of members for the installation limited to those with last_activity.
	 *
	 * @since BuddyPress 1.6.0
	 *
	 * @param int $count Total number of active members.
	 */
	return apply_filters( 'bp_core_get_active_member_count', $count );
}

/**
 * Process a spammed or unspammed user.
 *
 * This function is called from three places:
 *
 * - in bp_settings_action_capabilities() (from the front-end)
 * - by bp_core_mark_user_spam_admin()    (from wp-admin)
 * - bp_core_mark_user_ham_admin()        (from wp-admin)
 *
 * @since BuddyPress 1.6.0
 *
 * @param int    $user_id       The ID of the user being spammed/hammed.
 * @param string $status        'spam' if being marked as spam, 'ham' otherwise.
 * @param bool   $do_wp_cleanup True to force the cleanup of WordPress content
 *                              and status, otherwise false. Generally, this should
 *                              only be false if WordPress is expected to have
 *                              performed this cleanup independently, as when hooked
 *                              to 'make_spam_user'.
 * @return bool True on success, false on failure.
 */
function bp_core_process_spammer_status( $user_id, $status, $do_wp_cleanup = true ) {
	global $wpdb;

	// Bail if no user ID.
	if ( empty( $user_id ) ) {
		return;
	}

	// Bail if user ID is super admin.
	if ( is_super_admin( $user_id ) ) {
		return;
	}

	// Get the functions file.
	if ( is_multisite() ) {
		require_once ABSPATH . 'wp-admin/includes/ms.php';
	}

	$is_spam = ( 'spam' == $status );

	// Only you can prevent infinite loops.
	remove_action( 'make_spam_user', 'bp_core_mark_user_spam_admin' );
	remove_action( 'make_ham_user', 'bp_core_mark_user_ham_admin' );

	// Force the cleanup of WordPress content and status for multisite configs.
	if ( $do_wp_cleanup ) {

		// Get the blogs for the user.
		$blogs = get_blogs_of_user( $user_id, true );

		foreach ( (array) array_values( $blogs ) as $details ) {

			// Do not mark the main or current root blog as spam.
			if ( 1 == $details->userblog_id || bp_get_root_blog_id() == $details->userblog_id ) {
				continue;
			}

			// Update the blog status.
			update_blog_status( $details->userblog_id, 'spam', $is_spam );
		}

		// Finally, mark this user as a spammer.
		if ( is_multisite() ) {
			update_user_status( $user_id, 'spam', $is_spam );
		}
	}

	// Update the user status.
	$wpdb->update( $wpdb->users, array( 'user_status' => $is_spam ), array( 'ID' => $user_id ) );

	// Clean user cache.
	clean_user_cache( $user_id );

	if ( ! is_multisite() ) {
		// Call multisite actions in single site mode for good measure.
		if ( true === $is_spam ) {

			/**
			 * Fires at end of processing spammer in Dashboard if not multisite and user is spam.
			 *
			 * @since BuddyPress 1.5.0
			 *
			 * @param int $value user ID.
			 */
			do_action( 'make_spam_user', $user_id );
		} else {

			/**
			 * Fires at end of processing spammer in Dashboard if not multisite and user is not spam.
			 *
			 * @since BuddyPress 1.5.0
			 *
			 * @param int $value user ID.
			 */
			do_action( 'make_ham_user', $user_id );
		}
	}

	// Hide this user's activity.
	if ( ( true === $is_spam ) && bp_is_active( 'activity' ) ) {
		bp_activity_hide_user_activity( $user_id );
	}

	// We need a special hook for is_spam so that components can delete data at spam time.
	if ( true === $is_spam ) {

		/**
		 * Fires at the end of the process spammer process if the user is spam.
		 *
		 * @since BuddyPress 1.5.0
		 *
		 * @param int $value Displayed user ID.
		 */
		do_action( 'bp_make_spam_user', $user_id );
	} else {

		/**
		 * Fires at the end of the process spammer process if the user is not spam.
		 *
		 * @since BuddyPress 1.5.0
		 *
		 * @param int $value Displayed user ID.
		 */
		do_action( 'bp_make_ham_user', $user_id );
	}

	/**
	 * Fires at the end of the process for hanlding spammer status.
	 *
	 * @since BuddyPress 1.5.5
	 *
	 * @param int  $user_id ID of the processed user.
	 * @param bool $is_spam The determined spam status of processed user.
	 */
	do_action( 'bp_core_process_spammer_status', $user_id, $is_spam );

	// Put things back how we found them.
	add_action( 'make_spam_user', 'bp_core_mark_user_spam_admin' );
	add_action( 'make_ham_user', 'bp_core_mark_user_ham_admin' );

	return true;
}
/**
 * Hook to WP's make_spam_user and run our custom BP spam functions.
 *
 * @since BuddyPress 1.6.0
 *
 * @param int $user_id The user ID passed from the make_spam_user hook.
 */
function bp_core_mark_user_spam_admin( $user_id ) {
	bp_core_process_spammer_status( $user_id, 'spam', false );
}
add_action( 'make_spam_user', 'bp_core_mark_user_spam_admin' );

/**
 * Hook to WP's make_ham_user and run our custom BP spam functions.
 *
 * @since BuddyPress 1.6.0
 *
 * @param int $user_id The user ID passed from the make_ham_user hook.
 */
function bp_core_mark_user_ham_admin( $user_id ) {
	bp_core_process_spammer_status( $user_id, 'ham', false );
}
add_action( 'make_ham_user', 'bp_core_mark_user_ham_admin' );

/**
 * Check whether a user has been marked as a spammer.
 *
 * @since BuddyPress 1.6.0
 *
 * @param int $user_id The ID for the user.
 * @return bool True if spammer, otherwise false.
 */
function bp_is_user_spammer( $user_id = 0 ) {

	// No user to check.
	if ( empty( $user_id ) ) {
		return false;
	}

	$bp = buddypress();

	// Assume user is not spam.
	$is_spammer = false;

	// Setup our user.
	$user = false;

	// Get locally-cached data if available.
	switch ( $user_id ) {
		case bp_loggedin_user_id():
			$user = ! empty( $bp->loggedin_user->userdata ) ? $bp->loggedin_user->userdata : false;
			break;

		case bp_displayed_user_id():
			$user = ! empty( $bp->displayed_user->userdata ) ? $bp->displayed_user->userdata : false;
			break;

		case bp_get_member_user_id():
			global $members_template;
			$user = isset( $members_template ) && isset( $members_template->member ) ? $members_template->member : false;
			break;
	}

	// Manually get userdata if still empty.
	if ( empty( $user ) ) {
		$user = get_userdata( $user_id );
	}

	// No user found.
	if ( empty( $user ) ) {
		$is_spammer = false;

		// User found.
	} else {

		// Check if spam.
		if ( ! empty( $user->spam ) ) {
			$is_spammer = true;
		}

		if ( 1 == $user->user_status ) {
			$is_spammer = true;
		}
	}

	/**
	 * Filters whether a user is marked as a spammer.
	 *
	 * @since BuddyPress 1.6.0
	 *
	 * @param bool $is_spammer Whether or not user is marked as spammer.
	 */
	return apply_filters( 'bp_is_user_spammer', (bool) $is_spammer );
}

/**
 * Check whether a user has been marked as deleted.
 *
 * @since BuddyPress 1.6.0
 *
 * @param int $user_id The ID for the user.
 * @return bool True if deleted, otherwise false.
 */
function bp_is_user_deleted( $user_id = 0 ) {

	// No user to check.
	if ( empty( $user_id ) ) {
		return false;
	}

	$bp = buddypress();

	// Assume user is not deleted.
	$is_deleted = false;

	// Setup our user.
	$user = false;

	// Get locally-cached data if available.
	switch ( $user_id ) {
		case bp_loggedin_user_id():
			$user = ! empty( $bp->loggedin_user->userdata ) ? $bp->loggedin_user->userdata : false;
			break;

		case bp_displayed_user_id():
			$user = ! empty( $bp->displayed_user->userdata ) ? $bp->displayed_user->userdata : false;
			break;
	}

	// Manually get userdata if still empty.
	if ( empty( $user ) ) {
		$user = get_userdata( $user_id );
	}

	// No user found.
	if ( empty( $user ) ) {
		$is_deleted = true;

		// User found.
	} else {

		// Check if deleted.
		if ( ! empty( $user->deleted ) ) {
			$is_deleted = true;
		}

		if ( 2 == $user->user_status ) {
			$is_deleted = true;
		}
	}

	/**
	 * Filters whether a user is marked as deleted.
	 *
	 * @since BuddyPress 1.6.0
	 *
	 * @param bool $is_deleted Whether or not user is marked as deleted.
	 */
	return apply_filters( 'bp_is_user_deleted', (bool) $is_deleted );
}

/**
 * Check whether a user is "active", ie neither deleted nor spammer.
 *
 * @since BuddyPress 1.6.0
 *
 * @param int $user_id The user ID to check.
 * @return bool True if active, otherwise false.
 */
function bp_is_user_active( $user_id = 0 ) {

	// Default to current user.
	if ( empty( $user_id ) && is_user_logged_in() ) {
		$user_id = bp_loggedin_user_id();
	}

	// No user to check.
	if ( empty( $user_id ) ) {
		return false;
	}

	// Check spam.
	if ( bp_is_user_spammer( $user_id ) ) {
		return false;
	}

	// Check deleted.
	if ( bp_is_user_deleted( $user_id ) ) {
		return false;
	}

	// Assume true if not spam or deleted.
	return true;
}

/**
 * Check whether user is not active.
 *
 * @since BuddyPress 1.6.0
 *
 * @todo No need for the user fallback checks, since they're done in
 *       bp_is_user_active().
 *
 * @param int $user_id The user ID to check.
 * @return bool True if inactive, otherwise false.
 */
function bp_is_user_inactive( $user_id = 0 ) {

	// Default to current user.
	if ( empty( $user_id ) && is_user_logged_in() ) {
		$user_id = bp_loggedin_user_id();
	}

	// No user to check.
	if ( empty( $user_id ) ) {
		return false;
	}

	// Return the inverse of active.
	return ! bp_is_user_active( $user_id );
}

/**
 * Update a user's last activity.
 *
 * @since BuddyPress 1.9.0
 *
 * @param int    $user_id ID of the user being updated.
 * @param string $time    Time of last activity, in 'Y-m-d H:i:s' format.
 * @return bool True on success, false on failure.
 */
function bp_update_user_last_activity( $user_id = 0, $time = '' ) {

	// Fall back on current user.
	if ( empty( $user_id ) ) {
		$user_id = bp_loggedin_user_id();
	}

	// Bail if the user id is 0, as there's nothing to update.
	if ( empty( $user_id ) ) {
		return false;
	}

	// Bail if this is switched user.
	$old_user = bp_current_member_switched();
	if ( $old_user instanceof WP_User ) {
		return false;
	}

	// Fall back on current time.
	if ( empty( $time ) ) {
		$time = bp_core_current_time();
	}

	return BP_Core_User::update_last_activity( $user_id, $time );
}

/**
 * Backward compatibility for 'last_activity' usermeta fetching.
 *
 * In BuddyPress 2.0, user last_activity data was moved out of usermeta. For
 * backward compatibility, we continue to mirror the data there. This function
 * serves two purposes: it warns plugin authors of the change, and it returns
 * the data from the proper location.
 *
 * @since BuddyPress 2.0.0
 * @since BuddyPress 2.9.3 Added the `$single` parameter.
 *
 * @access private For internal use only.
 *
 * @param null   $retval Null retval value.
 * @param int    $object_id ID of the user.
 * @param string $meta_key  Meta key being fetched.
 * @param bool   $single    Whether a single key is being fetched (vs an array).
 * @return string|null
 */
function _bp_get_user_meta_last_activity_warning( $retval, $object_id, $meta_key, $single ) {
	static $warned = false;

	if ( 'last_activity' === $meta_key ) {
		// Don't send the warning more than once per pageload.
		if ( false === $warned ) {
			_doing_it_wrong( 'get_user_meta( $user_id, \'last_activity\' )', __( 'User last_activity data is no longer stored in usermeta. Use bp_get_user_last_activity() instead.', 'buddyboss' ), '2.0.0' );
			$warned = true;
		}

		$user_last_activity = bp_get_user_last_activity( $object_id );
		if ( $single ) {
			return $user_last_activity;
		} else {
			return array( $user_last_activity );
		}
	}

	return $retval;
}
add_filter( 'get_user_metadata', '_bp_get_user_meta_last_activity_warning', 10, 4 );

/**
 * Backward compatibility for 'last_activity' usermeta setting.
 *
 * In BuddyPress 2.0, user last_activity data was moved out of usermeta. For
 * backward compatibility, we continue to mirror the data there. This function
 * serves two purposes: it warns plugin authors of the change, and it updates
 * the data in the proper location.
 *
 * @since BuddyPress 2.0.0
 *
 * @access private For internal use only.
 *
 * @param int    $meta_id    ID of the just-set usermeta row.
 * @param int    $object_id  ID of the user.
 * @param string $meta_key   Meta key being fetched.
 * @param string $meta_value Active time.
 */
function _bp_update_user_meta_last_activity_warning( $meta_id, $object_id, $meta_key, $meta_value ) {
	if ( 'last_activity' === $meta_key ) {
		_doing_it_wrong( 'update_user_meta( $user_id, \'last_activity\' )', __( 'User last_activity data is no longer stored in usermeta. Use bp_update_user_last_activity() instead.', 'buddyboss' ), '2.0.0' );
		bp_update_user_last_activity( $object_id, $meta_value );
	}
}
add_filter( 'update_user_metadata', '_bp_update_user_meta_last_activity_warning', 10, 4 );

/**
 * Get the last activity for a given user.
 *
 * @since BuddyPress 1.9.0
 *
 * @param int $user_id The ID of the user.
 * @return string Time of last activity, in 'Y-m-d H:i:s' format, or an empty
 *                string if none is found.
 */
function bp_get_user_last_activity( $user_id = 0 ) {
	$activity = '';

	$last_activity = BB_Presence::bb_get_users_last_activity( $user_id );

	if ( ! empty( $last_activity[ $user_id ] ) ) {
		$activity = $last_activity[ $user_id ]['date_recorded'];
	}

	/**
	 * Filters the last activity for a given user.
	 *
	 * @since BuddyPress 1.9.0
	 *
	 * @param string $activity Time of last activity, in 'Y-m-d H:i:s' format or
	 *                         an empty string if none found.
	 * @param int    $user_id  ID of the user being checked.
	 */
	return apply_filters( 'bp_get_user_last_activity', $activity, $user_id );
}

/**
 * Migrate last_activity data from the usermeta table to the activity table.
 *
 * Generally, this function is only run when BP is upgraded to 2.0. It can also
 * be called directly from the BuddyBoss Tools panel.
 *
 * @since BuddyPress 2.0.0
 *
 * @return bool
 */
function bp_last_activity_migrate() {
	global $wpdb;

	$bp = buddypress();

	// Wipe out existing last_activity data in the activity table -
	// this helps to prevent duplicates when pulling from the usermeta
	// table.
	$wpdb->query( $wpdb->prepare( "DELETE FROM {$bp->members->table_name_last_activity} WHERE component = %s AND type = 'last_activity'", $bp->members->id ) );

	$sql = "INSERT INTO {$bp->members->table_name_last_activity} (`user_id`, `component`, `type`, `action`, `content`, `primary_link`, `item_id`, `date_recorded` ) (
		  SELECT user_id, '{$bp->members->id}' as component, 'last_activity' as type, '' as action, '' as content, '' as primary_link, 0 as item_id, meta_value AS date_recorded
		  FROM {$wpdb->usermeta}
		  WHERE
		    meta_key = 'last_activity'
	);";

	return $wpdb->query( $sql );
}

/**
 * Fetch every post that is authored by the given user for the current blog.
 *
 * No longer used in BuddyPress.
 *
 * @todo Deprecate.
 *
 * @param int $user_id ID of the user being queried.
 * @return array Post IDs.
 */
function bp_core_get_all_posts_for_user( $user_id = 0 ) {
	global $wpdb;

	if ( empty( $user_id ) ) {
		$user_id = bp_displayed_user_id();
	}

	$cache_key = 'bp_get_all_posts_for_user_' . $user_id;
	$result    = wp_cache_get( $cache_key, 'bp_member' );

	if ( false === $result ) {
		$result = apply_filters( 'bp_core_get_all_posts_for_user', $wpdb->get_col( $wpdb->prepare( "SELECT ID FROM {$wpdb->posts} WHERE post_author = %d AND post_status = 'publish' AND post_type = 'post'", $user_id ) ) );
		wp_cache_set( $cache_key, $result, 'bp_member' );
	}

	return $result;
}

/**
 * Process account deletion requests.
 *
 * Primarily used for self-deletions, as requested through Settings.
 *
 * @since BuddyPress 1.0.0
 *
 * @param int $user_id Optional. ID of the user to be deleted. Default: the
 *                     logged-in user.
 * @return bool True on success, false on failure.
 */
function bp_core_delete_account( $user_id = 0 ) {

	// Use logged in user ID if none is passed.
	if ( empty( $user_id ) ) {
		$user_id = bp_loggedin_user_id();
	}

	// Site admins cannot be deleted.
	if ( is_super_admin( $user_id ) ) {
		return false;
	}

	// Extra checks if user is not deleting themselves.
	if ( bp_loggedin_user_id() !== absint( $user_id ) ) {

		// Bail if current user cannot delete any users.
		if ( ! bp_current_user_can( 'delete_users' ) ) {
			return false;
		}

		// Bail if current user cannot delete this user.
		if ( ! current_user_can_for_blog( bp_get_root_blog_id(), 'delete_user', $user_id ) ) {
			return false;
		}
	}

	/**
	 * Fires before the processing of an account deletion.
	 *
	 * @since BuddyPress 1.6.0
	 *
	 * @param int $user_id ID of the user account being deleted.
	 */
	do_action( 'bp_core_pre_delete_account', $user_id );

	// Specifically handle multi-site environment.
	if ( is_multisite() ) {
		require_once ABSPATH . '/wp-admin/includes/ms.php';
		require_once ABSPATH . '/wp-admin/includes/user.php';

		$retval = wpmu_delete_user( $user_id );

		// Single site user deletion.
	} else {
		require_once ABSPATH . '/wp-admin/includes/user.php';
		$retval = wp_delete_user( $user_id );
	}

	/**
	 * Fires after the deletion of an account.
	 *
	 * @since BuddyPress 1.6.0
	 *
	 * @param int $user_id ID of the user account that was deleted.
	 */
	do_action( 'bp_core_deleted_account', $user_id );

	return $retval;
}

/**
 * Delete a user's avatar when the user is deleted.
 *
 * @since BuddyPress 1.9.0
 *
 * @param int $user_id ID of the user who is about to be deleted.
 * @return bool True on success, false on failure.
 */
function bp_core_delete_avatar_on_user_delete( $user_id ) {
	return bp_core_delete_existing_avatar(
		array(
			'item_id' => $user_id,
			'object'  => 'user',
		)
	);
}
add_action( 'wpmu_delete_user', 'bp_core_delete_avatar_on_user_delete' );
add_action( 'delete_user', 'bp_core_delete_avatar_on_user_delete' );

/**
 * Multibyte-safe ucfirst() support.
 *
 * Uses multibyte functions when available on the PHP build.
 *
 * @since BuddyPress 1.0.0
 *
 * @param string $str String to be upper-cased.
 * @return string
 */
function bp_core_ucfirst( $str ) {
	if ( function_exists( 'mb_strtoupper' ) && function_exists( 'mb_substr' ) ) {
		$fc = mb_strtoupper( mb_substr( $str, 0, 1 ) );
		return $fc . mb_substr( $str, 1 );
	} else {
		return ucfirst( $str );
	}
}

/**
 * Prevent spammers from logging in.
 *
 * When a user logs in, check if they have been marked as a spammer. If yes
 * then simply redirect them to the home page and stop them from logging in.
 *
 * @since BuddyPress 1.1.2
 *
 * @param WP_User|WP_Error $user Either the WP_User object or the WP_Error
 *                               object, as passed to the 'authenticate' filter.
 * @return WP_User|WP_Error If the user is not a spammer, return the WP_User
 *                          object. Otherwise a new WP_Error object.
 */
function bp_core_boot_spammer( $user ) {

	// Check to see if the $user has already failed logging in, if so return $user as-is.
	if ( is_wp_error( $user ) || empty( $user ) ) {
		return $user;
	}

	// The user exists; now do a check to see if the user is a spammer
	// if the user is a spammer, stop them in their tracks!
	if ( is_a( $user, 'WP_User' ) && ( ( is_multisite() && (int) $user->spam ) || 1 == $user->user_status ) ) {
		return new WP_Error( 'invalid_username', __( '<strong>ERROR</strong>: Your account has been marked as a spammer.', 'buddyboss' ) );
	}

	// User is good to go!
	return $user;
}
add_filter( 'authenticate', 'bp_core_boot_spammer', 30 );

/**
 * Delete last_activity data for the user when the user is deleted.
 *
 * @since BuddyPress 1.0.0
 *
 * @param int $user_id The user ID for the user to delete usermeta for.
 */
function bp_core_remove_data( $user_id ) {

	// Remove last_activity data.
	BP_Core_User::delete_last_activity( $user_id );

	// Flush the cache to remove the user from all cached objects.
	wp_cache_flush();
}
add_action( 'wpmu_delete_user', 'bp_core_remove_data' );
add_action( 'delete_user', 'bp_core_remove_data' );
add_action( 'bp_make_spam_user', 'bp_core_remove_data' );

/**
 * Check whether the logged-in user can edit settings for the displayed user.
 *
 * @since BuddyPress 1.5.0
 *
 * @return bool True if editing is allowed, otherwise false.
 */
function bp_core_can_edit_settings() {
	$status = false;

	if ( bp_is_my_profile() ) {
		$status = true;
	} elseif ( bp_is_user() && ! bp_is_my_profile() ) {
		$status = false;
	} elseif ( is_super_admin( bp_displayed_user_id() ) && ! is_super_admin() ) {
		$status = false;
	} elseif ( bp_current_user_can( 'bp_moderate' ) || current_user_can( 'edit_users' ) ) {
		$status = true;
	}

	/**
	 * Filters the status of whether the logged-in user can edit settings for the displayed user or not.
	 *
	 * @since BuddyPress 2.8.0
	 *
	 * @param bool True if editing is allowed, otherwise false.
	 */
	return apply_filters( 'bp_core_can_edit_settings', $status );
}

/** Sign-up *******************************************************************/

/**
 * Flush illegal names by getting and setting 'illegal_names' site option.
 *
 * @since BuddyPress 1.2.5
 */
function bp_core_flush_illegal_names() {
	$illegal_names = get_site_option( 'illegal_names' );
	update_site_option( 'illegal_names', $illegal_names );
}

/**
 * Add BuddyPress-specific items to the illegal_names array.
 *
 * @since BuddyPress 1.2.7
 *
 * @param array|string $value    Illegal names as being saved defined in
 *                               Multisite settings.
 * @param array|string $oldvalue The old value of the option.
 * @return array Merged and unique array of illegal names.
 */
function bp_core_get_illegal_names( $value = '', $oldvalue = '' ) {

	// Make sure $value is array.
	if ( empty( $value ) ) {
		$db_illegal_names = array();
	}

	if ( is_array( $value ) ) {
		$db_illegal_names = $value;
	} elseif ( is_string( $value ) ) {
		$db_illegal_names = explode( ' ', $value );
	}

	// Add the core components' slugs to the banned list even if their components aren't active.
	$bp_component_slugs = array(
		'groups',
		'members',
		'forums',
		'blogs',
		'activity',
		'profile',
		'friends',
		'search',
		'settings',
		'notifications',
		'register',
		'activate',
	);

	// Core constants.
	$slug_constants = array(
		'BP_GROUPS_SLUG',
		'BP_MEMBERS_SLUG',
		'BP_FORUMS_SLUG',
		'BP_BLOGS_SLUG',
		'BP_ACTIVITY_SLUG',
		'BP_XPROFILE_SLUG',
		'BP_FRIENDS_SLUG',
		'BP_SEARCH_SLUG',
		'BP_SETTINGS_SLUG',
		'BP_NOTIFICATIONS_SLUG',
		'BP_REGISTER_SLUG',
		'BP_ACTIVATION_SLUG',
	);
	foreach ( $slug_constants as $constant ) {
		if ( defined( $constant ) ) {
			$bp_component_slugs[] = constant( $constant );
		}
	}

	/**
	 * Filters the array of default illegal usernames.
	 *
	 * @since BuddyPress 1.2.2
	 *
	 * @param array $value Merged and unique array of illegal usernames.
	 */
	$filtered_illegal_names = apply_filters( 'bp_core_illegal_usernames', array_merge( array( 'www', 'web', 'root', 'admin', 'main', 'invite', 'administrator' ), $bp_component_slugs ) );

	/**
	 * Filters the list of illegal usernames from WordPress.
	 *
	 * @since BuddyPress 3.0
	 *
	 * @param array Array of illegal usernames.
	 */
	$wp_filtered_illegal_names = apply_filters( 'illegal_user_logins', array() );

	// First merge BuddyPress illegal names.
	$bp_merged_names = array_merge( (array) $filtered_illegal_names, (array) $db_illegal_names );

	// Then merge WordPress and BuddyPress illegal names.
	$merged_names = array_merge( (array) $wp_filtered_illegal_names, (array) $bp_merged_names );

	// Remove duplicates.
	$illegal_names = array_unique( (array) $merged_names );

	/**
	 * Filters the array of default illegal names.
	 *
	 * @since BuddyPress 1.2.5
	 *
	 * @param array $value Merged and unique array of illegal names.
	 */
	return apply_filters( 'bp_core_illegal_names', $illegal_names );
}
add_filter( 'pre_update_site_option_illegal_names', 'bp_core_get_illegal_names', 10, 2 );

/**
 * Check that an email address is valid for use.
 *
 * Performs the following checks:
 *   - Is the email address well-formed?
 *   - Is the email address already used?
 *   - If there's an email domain blacklist, is the current domain on it?
 *   - If there's an email domain whitelest, is the current domain on it?
 *
 * @since BuddyPress 1.6.2
 *
 * @param string $user_email The email being checked.
 * @return bool|array True if the address passes all checks; otherwise an array
 *                    of error codes.
 */
function bp_core_validate_email_address( $user_email ) {
	$errors = array();

	$user_email = sanitize_email( $user_email );

	// Is the email well-formed?
	if ( ! is_email( $user_email ) ) {
		$errors['invalid'] = 1;
	}

	// Is the email on the Banned Email Domains list?
	// Note: This check only works on Multisite.
	if ( function_exists( 'is_email_address_unsafe' ) && is_email_address_unsafe( $user_email ) ) {
		$errors['domain_banned'] = 1;
	}

	// Is the email on the Limited Email Domains list?
	// Note: This check only works on Multisite.
	$limited_email_domains = get_site_option( 'limited_email_domains' );
	if ( is_array( $limited_email_domains ) && empty( $limited_email_domains ) == false ) {
		$emaildomain = substr( $user_email, 1 + strpos( $user_email, '@' ) );
		if ( ! in_array( $emaildomain, $limited_email_domains ) ) {
			$errors['domain_not_allowed'] = 1;
		}
	}

	// Check buddyboss enmail restrictions.
	if ( function_exists( 'bb_is_allowed_register_email_address' ) && ! bb_is_allowed_register_email_address( $user_email ) ) {
		$errors['bb_restricted_email'] = 1;
	}

	// Is the email alreday in use?
	if ( email_exists( $user_email ) ) {
		$errors['in_use'] = 1;
	}

	$retval = ! empty( $errors ) ? $errors : true;

	return $retval;
}

/**
 * Add the appropriate errors to a WP_Error object, given results of a validation test.
 *
 * Functions like bp_core_validate_email_address() return a structured array
 * of error codes. bp_core_add_validation_error_messages() takes this array and
 * parses, adding the appropriate error messages to the WP_Error object.
 *
 * @since BuddyPress 1.7.0
 *
 * @see bp_core_validate_email_address()
 *
 * @param WP_Error $errors             WP_Error object.
 * @param array    $validation_results The return value of a validation function
 *                                     like bp_core_validate_email_address().
 */
function bp_core_add_validation_error_messages( WP_Error $errors, $validation_results ) {
	if ( ! empty( $validation_results['invalid'] ) ) {
		$errors->add( 'user_email', __( 'Please enter a valid email address.', 'buddyboss' ) );
	}

	if ( ! empty( $validation_results['domain_banned'] ) ) {
		$errors->add( 'user_email', __( 'Sorry, that email address is not allowed!', 'buddyboss' ) );
	}

	if ( ! empty( $validation_results['domain_not_allowed'] ) ) {
		$errors->add( 'user_email', __( 'Sorry, that email address is not allowed!', 'buddyboss' ) );
	}

	if ( ! empty( $validation_results['in_use'] ) ) {
		$errors->add( 'user_email', __( 'Sorry, that email address is already used!', 'buddyboss' ) );
	}

	if ( ! empty( $validation_results['bb_restricted_email'] ) ) {
		$errors->add( 'user_email', __( 'This email address or domain has been blacklisted. If you think you are seeing this in error, please contact the site administrator.', 'buddyboss' ) );
	}
}

/**
 * Validate a user name and email address when creating a new user.
 *
 * @since BuddyPress 1.2.2
 *
 * @param string $user_name  Username to validate.
 * @param string $user_email Email address to validate.
 * @return array Results of user validation including errors, if any.
 */
function bp_core_validate_user_signup( $user_name, $user_email ) {

	// Make sure illegal names include BuddyPress slugs and values.
	bp_core_flush_illegal_names();

	// WordPress Multisite has its own validation. Use it, so that we
	// properly mirror restrictions on username, etc.
	if ( function_exists( 'wpmu_validate_user_signup' ) ) {
		$result = wpmu_validate_user_signup( $user_name, $user_email );

		// When not running Multisite, we perform our own validation. What
		// follows reproduces much of the logic of wpmu_validate_user_signup(),
		// minus the multisite-specific restrictions on user_login.
	} else {
		$errors = new WP_Error();

		/**
		 * Filters the username before being validated.
		 *
		 * @since BuddyPress 1.5.5
		 *
		 * @param string $user_name Username to validate.
		 */
		$user_name = apply_filters( 'pre_user_login', $user_name );

		// User name can't be empty.
		if ( empty( $user_name ) ) {
			$errors->add( 'user_name', __( 'Please enter a username', 'buddyboss' ) );
		}

		// User name can't be on the blacklist.
		$illegal_names = get_site_option( 'illegal_names' );
		if ( in_array( $user_name, (array) $illegal_names ) ) {
			$errors->add( 'user_name', __( 'That username is not allowed', 'buddyboss' ) );
		}

		// User name must pass WP's validity check.
		if ( ! validate_username( $user_name ) ) {
			$field_name = xprofile_get_field( bp_xprofile_nickname_field_id() )->name;
			$errors->add( 'user_name', sprintf( __( 'Invalid %s. Only "a-z", "0-9", "-", "_" and "." are allowed.', 'buddyboss' ), $field_name ) );
		}

		// Minimum of 4 characters.
		if ( strlen( $user_name ) < 3 ) {
			$errors->add( 'user_name', __( 'Username must be at least 3 characters', 'buddyboss' ) );
		}

		// Check into signups.
		$signups = BP_Signup::get(
			array(
				'user_login' => $user_name,
			)
		);

		$signup = isset( $signups['signups'] ) && ! empty( $signups['signups'][0] ) ? $signups['signups'][0] : false;

		// Check if the username has been used already.
		if ( username_exists( $user_name ) || ! empty( $signup ) ) {
			$errors->add( 'user_name', __( 'Sorry, that username already exists!', 'buddyboss' ) );
		}

		// Validate the email address and process the validation results into
		// error messages.
		$validate_email = bp_core_validate_email_address( $user_email );
		bp_core_add_validation_error_messages( $errors, $validate_email );

		// Assemble the return array.
		$result = array(
			'user_name'  => $user_name,
			'user_email' => $user_email,
			'errors'     => $errors,
		);

		// Apply WPMU legacy filter.
		$result = apply_filters( 'wpmu_validate_user_signup', $result );
	}

	/**
	 * Filters the result of the user signup validation.
	 *
	 * @since BuddyPress 1.2.2
	 *
	 * @param array $result Results of user validation including errors, if any.
	 */
	return apply_filters( 'bp_core_validate_user_signup', $result );
}

/**
 * Validate blog URL and title provided at signup.
 *
 * @since BuddyPress 1.2.2
 *
 * @todo Why do we have this wrapper?
 *
 * @param string $blog_url   Blog URL requested during registration.
 * @param string $blog_title Blog title requested during registration.
 * @return array
 */
function bp_core_validate_blog_signup( $blog_url, $blog_title ) {
	if ( ! is_multisite() || ! function_exists( 'wpmu_validate_blog_signup' ) ) {
		return false;
	}

	/**
	 * Filters the validated blog url and title provided at signup.
	 *
	 * @since BuddyPress 1.2.2
	 *
	 * @param array $value Array with the new site data and error messages.
	 */
	return apply_filters( 'bp_core_validate_blog_signup', wpmu_validate_blog_signup( $blog_url, $blog_title ) );
}

/**
 * Process data submitted at user registration and convert to a signup object.
 *
 * @since BuddyPress 1.2.0
 *
 * @todo There appears to be a bug in the return value on success.
 *
 * @param string $user_login    Login name requested by the user.
 * @param string $user_password Password requested by the user.
 * @param string $user_email    Email address entered by the user.
 * @param array  $usermeta      Miscellaneous metadata about the user (blog-specific
 *                              signup data, xprofile data, etc).
 * @return int|false True on success, WP_Error on failure.
 */
function bp_core_signup_user( $user_login, $user_password, $user_email, $usermeta ) {
	$bp = buddypress();

	$user_login = strtolower( $user_login );

	// We need to cast $user_id to pass to the filters.
	$user_id = false;

	// Multisite installs have their own install procedure.
	if ( is_multisite() ) {
		wpmu_signup_user( $user_login, $user_email, $usermeta );

	} else {
		// Format data.
		$user_login     = preg_replace( '/\s+/', '', sanitize_user( $user_login, true ) );
		$user_email     = sanitize_email( $user_email );
		$activation_key = wp_generate_password( 32, false );

		/**
		 * WordPress's default behavior is to create user accounts
		 * immediately at registration time. BuddyPress uses a system
		 * borrowed from WordPress Multisite, where signups are stored
		 * separately and accounts are only created at the time of
		 * activation. For backward compatibility with plugins that may
		 * be anticipating WP's default behavior, BP silently creates
		 * accounts for registrations (though it does not use them). If
		 * you know that you are not running any plugins dependent on
		 * these pending accounts, you may want to save a little DB
		 * clutter by defining setting the BP_SIGNUPS_SKIP_USER_CREATION
		 * to true in your wp-config.php file.
		 */
		if ( ! defined( 'BP_SIGNUPS_SKIP_USER_CREATION' ) || ! BP_SIGNUPS_SKIP_USER_CREATION ) {
			$user_id = BP_Signup::add_backcompat( $user_login, $user_password, $user_email, $usermeta );

			if ( is_wp_error( $user_id ) ) {
				return $user_id;
			}

			bp_update_user_meta( $user_id, 'activation_key', $activation_key );
		}

		$args = array(
			'user_login'     => $user_login,
			'user_email'     => $user_email,
			'activation_key' => $activation_key,
			'meta'           => $usermeta,
		);

		BP_Signup::add( $args );

		/**
		 * Filters if BuddyPress should send an activation key for a new signup.
		 *
		 * @since BuddyPress 1.2.3
		 *
		 * @param bool   $value          Whether or not to send the activation key.
		 * @param int    $user_id        User ID to send activation key to.
		 * @param string $user_email     User email to send activation key to.
		 * @param string $activation_key Activation key to be sent.
		 * @param array  $usermeta       Miscellaneous metadata about the user (blog-specific
		 *                               signup data, xprofile data, etc).
		 */
		if ( apply_filters( 'bp_core_signup_send_activation_key', true, $user_id, $user_email, $activation_key, $usermeta ) ) {
			bp_core_signup_send_validation_email( $user_id, $user_email, $activation_key, $user_login );
		}
	}

	$bp->signup->username = $user_login;

	/**
	 * Fires at the end of the process to sign up a user.
	 *
	 * @since BuddyPress 1.2.2
	 *
	 * @param bool|WP_Error   $user_id       True on success, WP_Error on failure.
	 * @param string          $user_login    Login name requested by the user.
	 * @param string          $user_password Password requested by the user.
	 * @param string          $user_email    Email address requested by the user.
	 * @param array           $usermeta      Miscellaneous metadata about the user (blog-specific
	 *                                       signup data, xprofile data, etc).
	 */
	do_action( 'bp_core_signup_user', $user_id, $user_login, $user_password, $user_email, $usermeta );

	return $user_id;
}

/**
 * Create a blog and user based on data supplied at user registration.
 *
 * @since BuddyPress 1.2.2
 *
 * @param string $blog_domain Domain requested by user.
 * @param string $blog_path   Path requested by user.
 * @param string $blog_title  Title as entered by user.
 * @param string $user_name   user_login of requesting user.
 * @param string $user_email  Email address of requesting user.
 * @param string $usermeta    Miscellaneous metadata for the user.
 * @return bool
 */
function bp_core_signup_blog( $blog_domain, $blog_path, $blog_title, $user_name, $user_email, $usermeta ) {
	if ( ! is_multisite() || ! function_exists( 'wpmu_signup_blog' ) ) {
		return false;
	}

	/**
	 * Filters the result of wpmu_signup_blog().
	 *
	 * This filter provides no value and is retained for
	 * backwards compatibility.
	 *
	 * @since BuddyPress 1.2.2
	 *
	 * @param void $value
	 */
	return apply_filters( 'bp_core_signup_blog', wpmu_signup_blog( $blog_domain, $blog_path, $blog_title, $user_name, $user_email, $usermeta ) );
}

/**
 * Activate a signup, as identified by an activation key.
 *
 * @since BuddyPress 1.2.2
 *
 * @param string $key Activation key.
 * @return int|bool User ID on success, false on failure.
 */
function bp_core_activate_signup( $key ) {
	global $wpdb;

	$user = false;

	// Multisite installs have their own activation routine.
	if ( is_multisite() ) {
		$user = wpmu_activate_signup( $key );

		// If there were errors, add a message and redirect.
		if ( ! empty( $user->errors ) ) {
			return $user;
		}

		$user_id = $user['user_id'];

	} else {
		$signups = BP_Signup::get(
			array(
				'exclude_active' => false,
				'activation_key' => $key,
			)
		);

		if ( empty( $signups['signups'] ) ) {
			return new WP_Error( 'invalid_key', __( 'Invalid activation key.', 'buddyboss' ) );
		}

		$signup = $signups['signups'][0];

		if ( $signup->active ) {
			if ( empty( $signup->domain ) ) {
				return new WP_Error( 'already_active', __( 'The user is already active.', 'buddyboss' ), $signup );
			} else {
				return new WP_Error( 'already_active', __( 'The site is already active.', 'buddyboss' ), $signup );
			}
		}

		// Password is hashed again in wp_insert_user.
		$password = wp_generate_password( 12, false );

		$user_id = username_exists( $signup->user_login );

		// Create the user. This should only be necessary if BP_SIGNUPS_SKIP_USER_CREATION is true.
		if ( ! $user_id ) {
			$user_id = wp_create_user( $signup->user_login, $password, $signup->user_email );

			// Otherwise, update the existing user's status.
		} elseif ( $key === bp_get_user_meta( $user_id, 'activation_key', true ) || $key === wp_hash( $user_id ) ) {

			// Change the user's status so they become active.
			if ( ! $wpdb->query( $wpdb->prepare( "UPDATE {$wpdb->users} SET user_status = 0 WHERE ID = %d", $user_id ) ) ) {
				return new WP_Error( 'invalid_key', __( 'Invalid activation key.', 'buddyboss' ) );
			}

			bp_delete_user_meta( $user_id, 'activation_key' );

			$user_already_created = true;

		} else {
			$user_already_exists = true;
		}

		if ( ! $user_id ) {
			return new WP_Error( 'create_user', __( 'Could not create user', 'buddyboss' ), $signup );
		}

		// Fetch the signup so we have the data later on.
		$signups = BP_Signup::get(
			array(
				'activation_key' => $key,
			)
		);

		$signup = isset( $signups['signups'] ) && ! empty( $signups['signups'][0] ) ? $signups['signups'][0] : false;

		// Activate the signup.
		BP_Signup::validate( $key );

		if ( isset( $user_already_exists ) ) {
			return new WP_Error( 'user_already_exists', __( 'That username is already activated.', 'buddyboss' ), $signup );
		}

		// Set up data to pass to the legacy filter.
		$user = array(
			'user_id'  => $user_id,
			'password' => isset( $signup->meta['password'] ) ? $signup->meta['password'] : '',
			'meta'     => $signup->meta,
		);

		/**
		 * Maybe notify the site admin of a new user registration.
		 *
		 * @since BuddyPress 1.2.2
		 *
		 * @param bool $notification Whether to send the notification or not.
		 */
		if ( apply_filters( 'bp_core_send_user_registration_admin_notification', true ) ) {
			wp_new_user_notification( $user_id );
		}

		if ( isset( $user_already_created ) ) {

			/**
			 * Fires if the user has already been created.
			 *
			 * @since BuddyPress 1.2.2
			 *
			 * @param int    $user_id ID of the user being checked.
			 * @param string $key     Activation key.
			 * @param array  $user    Array of user data.
			 */
			do_action( 'bp_core_activated_user', $user_id, $key, $user );
			return $user_id;
		}
	}

	// Set any profile data.
	if ( bp_is_active( 'xprofile' ) ) {
		if ( ! empty( $user['meta']['profile_field_ids'] ) ) {
			$profile_field_ids = explode( ',', $user['meta']['profile_field_ids'] );

			foreach ( (array) $profile_field_ids as $field_id ) {
				$current_field = isset( $user['meta'][ "field_{$field_id}" ] ) ? $user['meta'][ "field_{$field_id}" ] : false;

				if ( ! empty( $current_field ) ) {
					xprofile_set_field_data( $field_id, $user_id, $current_field );
				}

				/*
				 * Save the visibility level.
				 *
				 * Use the field's default visibility if not present, and 'public' if a
				 * default visibility is not defined.
				 */
				$key = "field_{$field_id}_visibility";
				if ( isset( $user['meta'][ $key ] ) ) {
					$visibility_level = $user['meta'][ $key ];
				} else {
					$vfield           = xprofile_get_field( $field_id );
					$visibility_level = isset( $vfield->default_visibility ) ? $vfield->default_visibility : 'public';
				}
				xprofile_set_field_visibility_level( $field_id, $user_id, $visibility_level );
			}
		}
	}

	// Replace the password automatically generated by WordPress by the one the user chose.
	if ( ! empty( $user['meta']['password'] ) ) {
		$wpdb->query( $wpdb->prepare( "UPDATE {$wpdb->users} SET user_pass = %s WHERE ID = %d", $user['meta']['password'], $user_id ) );

		/**
		 * Make sure to clean the user's cache as we've
		 * directly edited the password without using
		 * wp_update_user().
		 *
		 * If we can't use wp_update_user() that's because
		 * we already hashed the password at the signup step.
		 */
		$uc = wp_cache_get( $user_id, 'users' );

		if ( ! empty( $uc->ID ) ) {
			clean_user_cache( $uc->ID );
		}
	}

	/**
	 * Fires at the end of the user activation process.
	 *
	 * @since BuddyPress 1.2.2
	 *
	 * @param int    $user_id ID of the user being checked.
	 * @param string $key     Activation key.
	 * @param array  $user    Array of user data.
	 */
	do_action( 'bp_core_activated_user', $user_id, $key, $user );

	return $user_id;
}

/**
 * Add default WordPress role for new signups on the BP root blog.
 *
 * @since BuddyPress 3.0.0
 *
 * @param int $user_id The user ID to add the default role for.
 */
function bp_members_add_role_after_activation( $user_id ) {
	// Get default role to add.
	$role = bp_get_option( 'default_role' );

	// Multisite.
	if ( is_multisite() && ! is_user_member_of_blog( $user_id, bp_get_root_blog_id() ) ) {
		add_user_to_blog( bp_get_root_blog_id(), $user_id, $role );

		// Single-site.
	} elseif ( ! is_multisite() ) {
		$member = get_userdata( $user_id );
		$member->set_role( $role );
	}
}
add_action( 'bp_core_activated_user', 'bp_members_add_role_after_activation', 1 );

/**
 * Migrate signups from pre-2.0 configuration to wp_signups.
 *
 * @since BuddyPress 2.0.1
 */
function bp_members_migrate_signups() {
	global $wpdb;

	$status_2_ids = $wpdb->get_col( "SELECT ID FROM {$wpdb->users} WHERE user_status = '2'" );

	if ( ! empty( $status_2_ids ) ) {
		$signups = get_users(
			array(
				'fields'  => array(
					'ID',
					'user_login',
					'user_pass',
					'user_registered',
					'user_email',
					'display_name',
				),
				'include' => $status_2_ids,
			)
		);

		// Fetch activation keys separately, to avoid the all_with_meta
		// overhead.
		$status_2_ids_sql = implode( ',', $status_2_ids );
		$ak_data          = $wpdb->get_results( "SELECT user_id, meta_value FROM {$wpdb->usermeta} WHERE meta_key = 'activation_key' AND user_id IN ({$status_2_ids_sql})" );

		// Rekey.
		$activation_keys = array();
		foreach ( $ak_data as $ak_datum ) {
			$activation_keys[ intval( $ak_datum->user_id ) ] = $ak_datum->meta_value;
		}

		unset( $status_2_ids_sql, $status_2_ids, $ak_data );

		// Merge.
		foreach ( $signups as &$signup ) {
			if ( isset( $activation_keys[ $signup->ID ] ) ) {
				$signup->activation_key = $activation_keys[ $signup->ID ];
			}
		}

		// Reset the signup var as we're using it to process the migration.
		unset( $signup );

	} else {
		return;
	}

	foreach ( $signups as $signup ) {
		$meta = array();

		// Rebuild the activation key, if missing.
		if ( empty( $signup->activation_key ) ) {
			$signup->activation_key = wp_generate_password( 32, false );
		}

		if ( bp_is_active( 'xprofile' ) ) {
			$meta['field_1'] = $signup->display_name;
		}

		$meta['password'] = $signup->user_pass;

		$user_login = preg_replace( '/\s+/', '', sanitize_user( $signup->user_login, true ) );
		$user_email = sanitize_email( $signup->user_email );

		BP_Signup::add(
			array(
				'user_login'     => $user_login,
				'user_email'     => $user_email,
				'registered'     => $signup->user_registered,
				'activation_key' => $signup->activation_key,
				'meta'           => $meta,
			)
		);

		// Deleting these options will remove signups from users count.
		delete_user_option( $signup->ID, 'capabilities' );
		delete_user_option( $signup->ID, 'user_level' );
	}
}

/**
 * Map a user's WP display name to the XProfile fullname field, if necessary.
 *
 * This only happens when a user is registered in wp-admin by an administrator;
 * during normal registration, XProfile data is provided directly by the user.
 *
 * @since BuddyPress 1.2.0
 *
 * @param int  $user_id ID of the user.
 * @param bool $by_pass ByPass is admin conditions.
 *
 * @return bool
 */
function bp_core_map_user_registration( $user_id, $by_pass = false ) {

	// Only map data when the site admin is adding users, not on registration.
	if ( ! is_admin() && empty( $by_pass ) ) {
		return false;
	}

	// Add the user's fullname to Xprofile.
	if ( bp_is_active( 'xprofile' ) ) {
		$user      = get_user_by( 'ID', $user_id );
		$firstname = bp_get_user_meta( $user_id, 'first_name', true );
		$lastname  = bp_get_user_meta( $user_id, 'last_name', true );
		$nickname  = $user->nickname;

		xprofile_set_field_data( bp_xprofile_firstname_field_id(), $user_id, $firstname );
		xprofile_set_field_data( bp_xprofile_lastname_field_id(), $user_id, $lastname );
		xprofile_set_field_data( bp_xprofile_nickname_field_id(), $user_id, $nickname );

		bp_xprofile_update_display_name( $user_id );
	}
}
add_action( 'user_register', 'bp_core_map_user_registration' );

/**
 * Get the avatar storage directory for use during registration.
 *
 * @since BuddyPress 1.1.0
 *
 * @return string|bool Directory path on success, false on failure.
 */
function bp_core_signup_avatar_upload_dir() {
	$bp = buddypress();

	if ( empty( $bp->signup->avatar_dir ) ) {
		return false;
	}

	$directory = 'avatars/signups';
	$path      = bp_core_avatar_upload_path() . '/' . $directory . '/' . $bp->signup->avatar_dir;
	$newbdir   = $path;
	$newurl    = bp_core_avatar_url() . '/' . $directory . '/' . $bp->signup->avatar_dir;
	$newburl   = $newurl;
	$newsubdir = '/' . $directory . '/' . $bp->signup->avatar_dir;

	/**
	 * Filters the avatar storage directory for use during registration.
	 *
	 * @since BuddyPress 1.1.1
	 *
	 * @param array $value Array of path and URL values for created storage directory.
	 */
	return apply_filters(
		'bp_core_signup_avatar_upload_dir',
		array(
			'path'    => $path,
			'url'     => $newurl,
			'subdir'  => $newsubdir,
			'basedir' => $newbdir,
			'baseurl' => $newburl,
			'error'   => false,
		)
	);
}

/**
 * Send activation email to a newly registered user.
 *
 * @since BuddyPress 1.2.2
 * @since BuddyPress 2.5.0 Add the $user_login parameter.
 *
 * @param int|bool $user_id    ID of the new user, false if BP_SIGNUPS_SKIP_USER_CREATION is true.
 * @param string   $user_email Email address of the new user.
 * @param string   $key        Activation key.
 * @param string   $user_login Optional. The user login name.
 */
function bp_core_signup_send_validation_email( $user_id, $user_email, $key, $user_login = '' ) {
	$args = array(
		'tokens' => array(
			'activate.url' => esc_url( trailingslashit( bp_get_activation_page() ) . "{$key}/" ),
			'key'          => $key,
			'user.email'   => $user_email,
			'user.id'      => $user_id,
		),
	);

	if ( $user_id ) {
		$to = $user_id;
	} else {
		$to = array( array( $user_email => $user_login ) );
	}

	bp_send_email( 'core-user-registration', $to, $args );
}

/**
 * Display a "resend email" link when an unregistered user attempts to log in.
 *
 * @since BuddyPress 1.2.2
 *
 * @param WP_User|WP_Error|null $user     Either the WP_User or the WP_Error object.
 * @param string                $username The inputted, attempted username.
 * @param string                $password The inputted, attempted password.
 * @return WP_User|WP_Error
 */
function bp_core_signup_disable_inactive( $user = null, $username = '', $password = '' ) {
	// Login form not used.
	if ( empty( $username ) && empty( $password ) ) {
		return $user;
	}

	// An existing WP_User with a user_status of 2 is either a legacy
	// signup, or is a user created for backward compatibility. See
	// {@link bp_core_signup_user()} for more details.
	if ( is_a( $user, 'WP_User' ) && 2 == $user->user_status ) {
		$user_login = $user->user_login;

		// If no WP_User is found corresponding to the username, this
		// is a potential signup.
	} elseif ( is_wp_error( $user ) && 'invalid_username' == $user->get_error_code() ) {
		$user_login = $username;

		// This is an activated user, so bail.
	} else {
		return $user;
	}

	// Look for the unactivated signup corresponding to the login name.
	$signup = BP_Signup::get( array( 'user_login' => sanitize_user( $user_login ) ) );

	// No signup or more than one, something is wrong. Let's bail.
	if ( empty( $signup['signups'][0] ) || $signup['total'] > 1 ) {
		return $user;
	}

	// Unactivated user account found!
	// Set up the feedback message.
	$signup_id = $signup['signups'][0]->signup_id;

	$resend_url_params = array(
		'action' => 'bp-resend-activation',
		'id'     => $signup_id,
	);

	$resend_url = wp_nonce_url(
		add_query_arg( $resend_url_params, wp_login_url() ),
		'bp-resend-activation'
	);

	$resend_string = '<br /><br />' . sprintf( __( 'If you have not received an email yet, <a href="%s">click here to resend it</a>.', 'buddyboss' ), esc_url( $resend_url ) );

	return new WP_Error( 'bp_account_not_activated', __( '<strong>ERROR</strong>: Your account has not been activated. Check your email for the activation link.', 'buddyboss' ) . $resend_string );
}
add_filter( 'authenticate', 'bp_core_signup_disable_inactive', 30, 3 );

/**
 * On the login screen, resends the activation email for a user.
 *
 * @since BuddyPress 2.0.0
 *
 * @see bp_core_signup_disable_inactive()
 */
function bp_members_login_resend_activation_email() {
	global $error;

	if ( empty( $_GET['id'] ) || empty( $_GET['_wpnonce'] ) ) {
		return;
	}

	// Verify nonce.
	if ( ! wp_verify_nonce( $_GET['_wpnonce'], 'bp-resend-activation' ) ) {
		die( 'Security check' );
	}

	$signup_id = (int) $_GET['id'];

	// Resend the activation email.
	// also updates the 'last sent' and '# of emails sent' values.
	$resend = BP_Signup::resend( array( $signup_id ) );

	// Add feedback message.
	if ( ! empty( $resend['errors'] ) ) {
		$error = __( '<strong>ERROR</strong>: Your account has already been activated.', 'buddyboss' );
	} else {
		$error = __( 'Activation email resent! Please check your inbox or spam folder.', 'buddyboss' );
	}
}
add_action( 'login_form_bp-resend-activation', 'bp_members_login_resend_activation_email' );

/**
 * Redirect away from wp-signup.php if BP registration templates are present.
 *
 * @since BuddyPress 1.1.0
 */
function bp_core_wpsignup_redirect() {

	do_action( 'bp_core_before_wpsignup_redirect' );

	// Bail in admin or if custom signup page is broken.
	if ( is_admin() || ! bp_has_custom_signup_page() ) {
		return;
	}

	$action = ! empty( $_GET['action'] ) ? $_GET['action'] : '';

	// Not at the WP core signup page and action is not register.
	if ( ! empty( $_SERVER['SCRIPT_NAME'] ) && false === strpos( 'wp-signup.php', $_SERVER['SCRIPT_NAME'] ) && ( 'register' != $action ) ) {
		return;
	}

	$allow_custom_registration = bp_allow_custom_registration();

	if ( apply_filters( 'bp_core_wpsignup_redirect', true ) && ! $allow_custom_registration ) {
		bp_core_redirect( bp_get_signup_page() );
	} elseif ( apply_filters( 'bp_core_wpsignup_redirect', true ) && $allow_custom_registration && '' === bp_custom_register_page_url() ) {
		bp_core_redirect( bp_get_signup_page() );
	} elseif ( apply_filters( 'bp_core_wpsignup_redirect', true ) && $allow_custom_registration && '' !== bp_custom_register_page_url() ) {
		$bp_custom_register_page_url = bp_custom_register_page_url();

		// Check if custom registration URL is https://site.com/wp-login.php?action=register then we do not need to redirect again.
		if (
			false === strpos( $bp_custom_register_page_url, 'wp-login.php' ) &&
			'register' !== $action
		) {
			bp_core_redirect( $bp_custom_register_page_url );
		}
	}
}
add_action( 'bp_init', 'bp_core_wpsignup_redirect' );

/**
 * Stop a logged-in user who is marked as a spammer.
 *
 * When an admin marks a live user as a spammer, that user can still surf
 * around and cause havoc on the site until that person is logged out.
 *
 * This code checks to see if a logged-in user is marked as a spammer.  If so,
 * we redirect the user back to wp-login.php with the 'reauth' parameter.
 *
 * This clears the logged-in spammer's cookies and will ask the spammer to
 * reauthenticate.
 *
 * Note: A spammer cannot log back in - {@see bp_core_boot_spammer()}.
 *
 * Runs on 'bp_init' at priority 5 so the members component globals are setup
 * before we do our spammer checks.
 *
 * This is important as the $bp->loggedin_user object is setup at priority 4.
 *
 * @since BuddyPress 1.8.0
 */
function bp_stop_live_spammer() {
	// If we're on the login page, stop now to prevent redirect loop.
	$is_login = false;
	if ( isset( $GLOBALS['pagenow'] ) && ( false !== strpos( $GLOBALS['pagenow'], 'wp-login.php' ) ) ) {
		$is_login = true;
	} elseif ( isset( $_SERVER['SCRIPT_NAME'] ) && false !== strpos( $_SERVER['SCRIPT_NAME'], 'wp-login.php' ) ) {
		$is_login = true;
	}

	if ( $is_login ) {
		return;
	}

	// User isn't logged in, so stop!
	if ( ! is_user_logged_in() ) {
		return;
	}

	// If spammer, redirect to wp-login.php and reauthorize.
	if ( bp_is_user_spammer( bp_loggedin_user_id() ) ) {
		// Setup login args.
		$args = array(
			// Custom action used to throw an error message.
			'action' => 'bp-spam',

			// Reauthorize user to login.
			'reauth' => 1,
		);

		/**
		 * Filters the url used for redirection for a logged in user marked as spam.
		 *
		 * @since BuddyPress 1.8.0
		 *
		 * @param string $value URL to redirect user to.
		 */
		$login_url = apply_filters( 'bp_live_spammer_redirect', add_query_arg( $args, wp_login_url() ) );

		// Redirect user to login page.
		wp_safe_redirect( $login_url );
		die();
	}
}
add_action( 'bp_init', 'bp_stop_live_spammer', 5 );

/**
 * Show a custom error message when a logged-in user is marked as a spammer.
 *
 * @since BuddyPress 1.8.0
 */
function bp_live_spammer_login_error() {
	global $error;

	$error = __( '<strong>ERROR</strong>: Your account has been marked as a spammer.', 'buddyboss' );

	// Shake shake shake!
	add_action( 'login_head', 'wp_shake_js', 12 );
}
add_action( 'login_form_bp-spam', 'bp_live_spammer_login_error' );

/**
 * Get the displayed user Object
 *
 * @since BuddyPress 2.6.0
 *
 * @return object The displayed user object, null otherwise.
 */
function bp_get_displayed_user() {
	$bp = buddypress();

	$displayed_user = null;
	if ( ! empty( $bp->displayed_user->id ) ) {
		$displayed_user = $bp->displayed_user;
	}

	/**
	 * Filters the displayed_user object corresponding to the displayed member.
	 *
	 * @since BuddyPress 2.6.0
	 *
	 * @param object $displayed_user The displayed_user object.
	 */
	return apply_filters( 'bp_get_displayed_user', $displayed_user );
}

/** Profile Types *************************************************************/

/**
 * Output the slug of the profile type taxonomy.
 *
 * @since BuddyPress 2.7.0
 */
function bp_member_type_tax_name() {
	echo bp_get_member_type_tax_name();
}

	/**
	 * Return the slug of the profile type taxonomy.
	 *
	 * @since BuddyPress 2.7.0
	 *
	 * @return string The unique member taxonomy slug.
	 */
function bp_get_member_type_tax_name() {
	/**
	 * Filters the slug of the profile type taxonomy.
	 *
	 * @since BuddyPress 2.7.0
	 *
	 * @param string $value profile type taxonomy slug.
	 */
	return apply_filters( 'bp_get_member_type_tax_name', 'bp_member_type' );
}

/**
 * Register a profile type.
 *
 * @since BuddyPress 2.2.0
 *
 * @param string $member_type Unique string identifier for the profile type.
 * @param array  $args {
 *     Array of arguments describing the profile type.
 *
 *     @type array       $labels {
 *         Array of labels to use in various parts of the interface.
 *
 *         @type string $name          Default name. Should typically be plural.
 *         @type string $singular_name Singular name.
 *     }
 *     @type bool|string $has_directory Whether the profile type should have its own type-specific directory.
 *                                      Pass `true` to use the `$member_type` string as the type's slug.
 *                                      Pass a string to customize the slug. Pass `false` to disable.
 *                                      Default: true.
 * }
 * @return object|WP_Error profile type object on success, WP_Error object on failure.
 */
function bp_register_member_type( $member_type, $args = array() ) {
	$bp = buddypress();

	if ( isset( $bp->members->types[ $member_type ] ) ) {
		return new WP_Error( 'bp_member_type_exists', __( 'Profile type already exists.', 'buddyboss' ), $member_type );
	}

	$r = bp_parse_args(
		$args,
		array(
			'labels'        => array(),
			'has_directory' => true,
		),
		'register_member_type'
	);

	$member_type = sanitize_key( $member_type );

	/**
	 * Filters the list of illegal profile type names.
	 *
	 * - 'any' is a special pseudo-type, representing items unassociated with any profile type.
	 * - 'null' is a special pseudo-type, representing users without any type.
	 * - '_none' is used internally to denote an item that should not apply to any profile types.
	 *
	 * @since BuddyPress 2.4.0
	 *
	 * @param array $illegal_names Array of illegal names.
	 */
	$illegal_names = apply_filters( 'bp_member_type_illegal_names', array( 'any', 'null', '_none' ) );
	if ( in_array( $member_type, $illegal_names, true ) ) {
		return new WP_Error( 'bp_member_type_illegal_name', __( 'You may not register a profile type with this name.', 'buddyboss' ), $member_type );
	}

	// Store the post type name as data in the object (not just as the array key).
	$r['name'] = $member_type;

	// Make sure the relevant labels have been filled in.
	$default_name = isset( $r['labels']['name'] ) ? $r['labels']['name'] : ucfirst( $r['name'] );
	$r['labels']  = array_merge(
		array(
			'name'          => $default_name,
			'singular_name' => $default_name,
		),
		$r['labels']
	);

	// Directory slug.
	if ( $r['has_directory'] ) {
		// A string value is intepreted as the directory slug. Otherwise fall back on profile type.
		if ( is_string( $r['has_directory'] ) ) {
			$directory_slug = $r['has_directory'];
		} else {
			$directory_slug = $member_type;
		}

		// Sanitize for use in URLs.
		$r['directory_slug'] = sanitize_title( $directory_slug );
		$r['has_directory']  = true;
	} else {
		$r['directory_slug'] = '';
		$r['has_directory']  = false;
	}

	$bp->members->types[ $member_type ] = $type = (object) $r;

	/**
	 * Fires after a profile type is registered.
	 *
	 * @since BuddyPress 2.2.0
	 *
	 * @param string $member_type profile type identifier.
	 * @param object $type        profile type object.
	 */
	do_action( 'bp_registered_member_type', $member_type, $type );

	return $type;
}

/**
 * Retrieve a profile type object by name.
 *
 * @since BuddyPress 2.2.0
 *
 * @param string $member_type The name of the profile type.
 * @return object A profile type object.
 */
function bp_get_member_type_object( $member_type ) {
	$types = bp_get_member_types( array(), 'objects' );

	if ( empty( $types[ $member_type ] ) ) {
		return null;
	}

	return $types[ $member_type ];
}

/**
 * Get a list of all registered profile type objects.
 *
 * @since BuddyPress 2.2.0
 *
 * @see bp_register_member_type() for accepted arguments.
 *
 * @param array|string $args     Optional. An array of key => value arguments to match against
 *                               the profile type objects. Default empty array.
 * @param string       $output   Optional. The type of output to return. Accepts 'names'
 *                               or 'objects'. Default 'names'.
 * @param string       $operator Optional. The logical operation to perform. 'or' means only one
 *                               element from the array needs to match; 'and' means all elements
 *                               must match. Accepts 'or' or 'and'. Default 'and'.
 * @return array A list of profile type names or objects.
 */
function bp_get_member_types( $args = array(), $output = 'names', $operator = 'and' ) {
	$types = buddypress()->members->types;

	$types = wp_filter_object_list( $types, $args, $operator );

	/**
	 * Filters the array of profile type objects.
	 *
	 * This filter is run before the $output filter has been applied, so that
	 * filtering functions have access to the entire profile type objects.
	 *
	 * @since BuddyPress 2.2.0
	 *
	 * @param array  $types     profile type objects, keyed by name.
	 * @param array  $args      Array of key=>value arguments for filtering.
	 * @param string $operator  'or' to match any of $args, 'and' to require all.
	 */
	$types = apply_filters( 'bp_get_member_types', $types, $args, $operator );

	if ( 'names' === $output ) {
		$types = wp_list_pluck( $types, 'name' );
	}

	return $types;
}

/**
 * Set type for a member.
 *
 * @since BuddyPress 2.2.0
 *
 * @param int    $user_id     ID of the user.
 * @param string $member_type profile type.
 * @param bool   $append      Optional. True to append this to existing types for user,
 *                            false to replace. Default: false.
 * @return false|array $retval See {@see bp_set_object_terms()}.
 */
function bp_set_member_type( $user_id, $member_type, $append = false ) {
	// Pass an empty $member_type to remove a user's type.
	if ( ! empty( $member_type ) && ! bp_get_member_type_object( $member_type ) ) {
		return false;
	}

	$retval = bp_set_object_terms( $user_id, $member_type, bp_get_member_type_tax_name(), $append );

	// Bust the cache if the type has been updated.
	if ( ! is_wp_error( $retval ) ) {
		wp_cache_delete( $user_id, 'bp_member_member_type' );

		/**
		 * Fires just after a user's profile type has been changed.
		 *
		 * @since BuddyPress 2.2.0
		 *
		 * @param int    $user_id     ID of the user whose profile type has been updated.
		 * @param string $member_type profile type.
		 * @param bool   $append      Whether the type is being appended to existing types.
		 */
		do_action( 'bp_set_member_type', $user_id, $member_type, $append );
	}

	return $retval;
}

/**
 * Remove type for a member.
 *
 * @since BuddyPress 2.3.0
 *
 * @param int    $user_id     ID of the user.
 * @param string $member_type profile type.
 * @return bool|WP_Error
 */
function bp_remove_member_type( $user_id, $member_type ) {
	// Bail if no valid profile type was passed.
	if ( empty( $member_type ) || ! bp_get_member_type_object( $member_type ) ) {
		return false;
	}

	// No need to continue if the member doesn't have the type.
	$existing_types = bp_get_member_type( $user_id, false );
	if ( ! in_array( $member_type, $existing_types, true ) ) {
		return false;
	}

	$deleted = bp_remove_object_terms( $user_id, $member_type, bp_get_member_type_tax_name() );

	// Bust the cache if the type has been removed.
	if ( ! is_wp_error( $deleted ) ) {
		wp_cache_delete( $user_id, 'bp_member_member_type' );

		/**
		 * Fires just after a user's profile type has been removed.
		 *
		 * @since BuddyPress 2.3.0
		 *
		 * @param int    $user_id     ID of the user whose profile type has been updated.
		 * @param string $member_type profile type.
		 */
		do_action( 'bp_remove_member_type', $user_id, $member_type );
	}

	return $deleted;
}

/**
 * Get type for a member.
 *
 * @since BuddyPress 2.2.0
 *
 * @param int  $user_id ID of the user.
 * @param bool $single  Optional. Whether to return a single type string. If multiple types are found
 *                      for the user, the oldest one will be returned. Default: true.
 * @return string|array|bool On success, returns a single profile type (if $single is true) or an array of member
 *                           types (if $single is false). Returns false on failure.
 */
function bp_get_member_type( $user_id, $single = true ) {
	$types = wp_cache_get( $user_id, 'bp_member_member_type' );

	if ( empty( $types ) ) {
		$raw_types = bp_get_object_terms( $user_id, bp_get_member_type_tax_name() );

		if ( ! is_wp_error( $raw_types ) ) {
			$types = array();

			// Only include currently registered group types.
			foreach ( $raw_types as $mtype ) {
				if ( bp_get_member_type_object( $mtype->name ) ) {
					$types[] = $mtype->name;
				}
			}

			wp_cache_set( $user_id, $types, 'bp_member_member_type' );
		}
	}

	$type = false;
	if ( ! empty( $types ) ) {
		if ( $single ) {
			$type = array_pop( $types );
		} else {
			$type = $types;
		}
	}

	/**
	 * Filters a user's profile type(s).
	 *
	 * @since BuddyPress 2.2.0
	 *
	 * @param string $type    profile type.
	 * @param int    $user_id ID of the user.
	 * @param bool   $single  Whether to return a single type string, or an array.
	 */
	return apply_filters( 'bp_get_member_type', $type, $user_id, $single );
}

/**
 * Check whether the given user has a certain profile type.
 *
 * @since BuddyPress 2.3.0
 *
 * @param int    $user_id     $user_id ID of the user.
 * @param string $member_type profile type.
 * @return bool Whether the user has the given profile type.
 */
function bp_has_member_type( $user_id, $member_type ) {
	// Bail if no valid profile type was passed.
	if ( empty( $member_type ) || ! bp_get_member_type_object( $member_type ) ) {
		return false;
	}

	// Get all user's profile types.
	$types = bp_get_member_type( $user_id, false );

	if ( ! is_array( $types ) ) {
		return false;
	}

	return in_array( $member_type, $types );
}

/**
 * Delete a user's profile type when the user when the user is deleted.
 *
 * @since BuddyPress 2.2.0
 *
 * @param int $user_id ID of the user.
 * @return false|array $value See {@see bp_set_member_type()}.
 */
function bp_remove_member_type_on_user_delete( $user_id ) {
	return bp_set_member_type( $user_id, '' );
}
add_action( 'wpmu_delete_user', 'bp_remove_member_type_on_user_delete' );
add_action( 'delete_user', 'bp_remove_member_type_on_user_delete' );

/**
 * Get the "current" profile type, if one is provided, in member directories.
 *
 * @since BuddyPress 2.3.0
 *
 * @return string
 */
function bp_get_current_member_type() {

	/**
	 * Filters the "current" profile type, if one is provided, in member directories.
	 *
	 * @since BuddyPress 2.3.0
	 *
	 * @param string $value "Current" profile type.
	 */
	return apply_filters( 'bp_get_current_member_type', buddypress()->current_member_type );
}

/**
 * Enable/disable profile type functionality.
 *
 * @since BuddyBoss 1.0.0
 */
function bp_register_member_type_section() {

	$is_member_type_enabled = bp_member_type_enable_disable();

	if ( false === $is_member_type_enabled ) {

		// action for remove profile type metabox.
		add_action( 'bp_members_admin_user_metaboxes', 'bp_remove_member_type_metabox_globally' );

		return;
	}

	// profile types.
	register_post_type(
		bp_get_member_type_post_type(),
		apply_filters(
			'bp_register_member_type_post_type',
			array(
				'description'        => __( 'BuddyBoss profile type', 'buddyboss' ),
				'labels'             => bp_get_member_type_post_type_labels(),
				'public'             => false,
				'publicly_queryable' => false,
				'query_var'          => false,
				'rewrite'            => false,
				'show_in_admin_bar'  => false,
				'show_in_menu'       => false,
				'map_meta_cap'       => true,
				'show_in_rest'       => true,
				'show_ui'            => bp_current_user_can( 'bp_moderate' ),
				'supports'           => bp_get_member_type_post_type_supports(),
			)
		)
	);

	// set profile type while update user profile.
	// add_action( 'set_user_role', 'bp_update_user_member_type_type_set', 10, 2 );

	// action for remove profile type metabox.
	add_action( 'bp_members_admin_user_metaboxes', 'bp_remove_member_type_metabox' );

	// add column.
	add_filter( 'manage_' . bp_get_member_type_post_type() . '_posts_columns', 'bp_member_type_add_column' );

	// action for adding a sortable column name.
	add_action( 'manage_' . bp_get_member_type_post_type() . '_posts_custom_column', 'bp_member_type_show_data', 10, 2 );

	// sortable columns.
	add_filter( 'manage_edit-' . bp_get_member_type_post_type() . '_sortable_columns', 'bp_member_type_add_sortable_columns' );

	// request filter.
	add_action( 'load-edit.php', 'bp_member_type_add_request_filter' );

	// hide quick edit link on the custom post type list screen.
	add_filter( 'post_row_actions', 'bp_member_type_hide_quickedit', 10, 2 );

	// filter for adding body class where the shortcode added.
	add_filter( 'body_class', 'bp_member_type_shortcode_add_body_class' );

	// Hook for creating a profile type shortcode.
	add_shortcode( 'profile', 'bp_member_type_shortcode_callback' );

	// action for adding the js for the profile type post type.
	add_action( 'admin_enqueue_scripts', 'bp_member_type_changing_listing_label' );

}

// Register enable/disable profile type functionality.
add_action( 'bp_init', 'bp_register_member_type_section' );

// action for registering active profile types.
add_action( 'bp_register_member_types', 'bp_register_active_member_types' );

/**
 * Output the name of the profile type post type.
 *
 * @since BuddyBoss 1.0.0
 *
 * @return string   custom post type of profile type.
 */
function bp_member_type_post_type() {
	echo bp_get_member_type_post_type();
}

/**
 * Returns the name of the profile type post type.
 *
 * @since BuddyBoss 1.0.0
 *
 * @return string The name of the profile type post type.
 */
function bp_get_member_type_post_type() {

	/**
	 * Filters the name of the profile type post type.
	 *
	 * @since BuddyBoss 1.0.0
	 *
	 * @param string $value profile type post type name.
	 */
	return apply_filters( 'bp_get_member_type_post_type', buddypress()->member_type_post_type );
}

/**
 * Return labels used by the profile type post type.
 *
 * @since BuddyBoss 1.0.0
 *
 * @return array
 */
function bp_get_member_type_post_type_labels() {

	/**
	 * Filters profile type post type labels.
	 *
	 * @since BuddyBoss 1.0.0
	 *
	 * @param array $value Associative array (name => label).
	 */
	return apply_filters(
		'bp_get_member_type_post_type_labels',
		array(
			'add_new_item'       => __( 'New Profile Type', 'buddyboss' ),
			'all_items'          => __( 'Profile Types', 'buddyboss' ),
			'edit_item'          => __( 'Edit Profile Type', 'buddyboss' ),
			'menu_name'          => __( 'Users', 'buddyboss' ),
			'name'               => __( 'Profile Types', 'buddyboss' ),
			'new_item'           => __( 'New Profile Type', 'buddyboss' ),
			'not_found'          => __( 'No Profile Types found', 'buddyboss' ),
			'not_found_in_trash' => __( 'No Profile Types found in trash', 'buddyboss' ),
			'search_items'       => __( 'Search Profile Types', 'buddyboss' ),
			'singular_name'      => __( 'Profile Type', 'buddyboss' ),
			'attributes'         => __( 'Dropdown Order', 'buddyboss' ),
		)
	);
}

/**
 * Return array of features that the profile type post type supports.
 *
 * @since BuddyBoss 1.0.0
 *
 * @return array
 */
function bp_get_member_type_post_type_supports() {

	/**
	 * Filters the features that the profile type post type supports.
	 *
	 * @since BuddyBoss 1.0.0
	 *
	 * @param array $value Supported features.
	 */
	return apply_filters(
		'bp_get_member_type_post_type_supports',
		array(
			'page-attributes',
			'title',
		)
	);
}

/**
 * Return profile type key.
 *
 * @since BuddyBoss 1.0.0
 *
 * @param $post_id
 * @return mixed|string
 */
function bp_get_member_type_key( $post_id ) {

	if ( empty( $post_id ) ) {
		return '';
	}

	$key = get_post_meta( $post_id, '_bp_member_type_key', true );

	// Fallback to legacy way of generating profile type key from singular label
	// if Key is not set by admin user.
	if ( empty( $key ) ) {
		$key  = get_post_field( 'post_name', $post_id );
		$term = term_exists( sanitize_key( $key ), bp_get_member_type_tax_name() );
		if ( 0 !== $term && null !== $term ) {
			$digits = 3;
			$unique = rand( pow( 10, $digits - 1 ), pow( 10, $digits ) - 1 );
			$key    = $key . $unique;
		}
		update_post_meta( $post_id, '_bp_member_type_key', sanitize_key( $key ) );
	}

	return apply_filters( 'bp_get_member_type_key', $key );
}

/**
 * Get members by role.
 *
 * @since BuddyBoss 1.0.0
 *
 * @param $role
 *
 * @return array
 */
function bp_get_member_type_by_wp_role( $role ) {
	$bp_member_type_ids = array();
	$post_type          = bp_get_member_type_post_type();

	$bp_member_type_args = array(
		'post_type' => $post_type,
		'nopaging'  => true,
	);

	$bp_member_type_query = new WP_Query( $bp_member_type_args );
	if ( $bp_member_type_query->have_posts() ) :
		while ( $bp_member_type_query->have_posts() ) :
			$bp_member_type_query->the_post();

			$post_id        = get_the_ID();
			$selected_roles = get_post_meta( $post_id, '_bp_member_type_wp_roles', true );
			$selected_roles = (array) $selected_roles;
			$singular_name  = strtolower( get_post_meta( $post_id, '_bp_member_type_label_singular_name', true ) );
			$name           = bp_get_member_type_key( $post_id );
			if ( in_array( $role, $selected_roles ) ) {
				$bp_member_type_ids[] = array(
					'ID'        => $post_id,
					'name'      => $name,
					'nice_name' => $singular_name,
				);
			}
		endwhile;
	endif;
	wp_reset_query();
	wp_reset_postdata();
	return $bp_member_type_ids;
}

/**
 * Removes the role from profile type.
 *
 * @since BuddyBoss 1.0.0
 *
 * @param $wp_roles
 * @param $member_type
 */
function bp_remove_member_type_to_roles( $wp_roles, $member_type ) {
	$users = bp_get_users_by_roles( $wp_roles );
	if ( isset( $users ) && ! empty( $users ) ) {
		foreach ( $users as $single ) {
			bp_remove_member_type( $single, $member_type );
		}
	}
}

/**
 * Sets the profile type to roles.
 *
 * @since BuddyBoss 1.0.0
 *
 * @param $wp_roles
 * @param $member_type
 */
function bp_set_member_type_to_roles( $wp_roles, $member_type ) {
	$users = bp_get_users_by_roles( $wp_roles );
	if ( isset( $users ) && ! empty( $users ) ) {
		foreach ( $users as $single ) {
			bp_set_user_member_type( $single, $member_type );
		}
	}
}

/**
 * Gets a user by their role.
 *
 * @since BuddyBoss 1.0.0
 *
 * @param $roles
 *
 * @return array
 */
function bp_get_users_by_roles( $roles ) {
	$roles = (array) $roles;
	$users = array();

	foreach ( $roles as $role ) :
		$users_query = new WP_User_Query(
			array(
				'fields' => 'ID',
				'role'   => $role,
			)
		);
		$results     = $users_query->get_results();
		if ( $results ) {
			$users = array_merge( $users, $results );
		}
	endforeach;

	return $users;
}

/**
 * Set type for a member profile.
 * Set profile types on save_post
 *
 * @since BuddyBoss 1.0.0
 *
 * @param int    $user_id     ID of the user.
 * @param string $member_type profile type.
 * @param bool   $append      Optional. True to append this to existing types for user,
 *                            false to replace. Default: false.
 * @return See {@see bp_set_object_terms()}.
 */
function bp_set_user_member_type( $user_id, $member_type, $append = false ) {

	$retval = bp_set_object_terms( $user_id, $member_type, 'bp_member_type', $append );

	// Bust the cache if the type has been updated.
	if ( ! is_wp_error( $retval ) ) {
		wp_cache_delete( $user_id, 'bp_member_member_type' );

		/**
		 * Fires just after a user's profile type has been changed.
		 *
		 * @since BuddyPress (2.2.0)
		 *
		 * @param int    $user_id     ID of the user whose profile type has been updated.
		 * @param string $member_type profile type.
		 * @param bool   $append      Whether the type is being appended to existing types.
		 */
		do_action( 'bp_set_user_member_type', $user_id, $member_type, $append );
	}

	return $retval;
}

/**
 * Gets profile type term taxonomy id.
 *
 * @param $type_name
 *
 * @return int
 * @since BuddyBoss 1.0.0
 *
 * @global wpdb $wpdb WordPress database abstraction object.
 */
function bp_member_type_term_taxonomy_id( $member_type_name ) {
	$member_type = get_term_by( 'slug', $member_type_name, 'bp_member_type' );
	if ( ! $member_type ) {
		return 0;
	}

	$term_taxonomy_id = $member_type->term_taxonomy_id;

	return $term_taxonomy_id;
}

/**
 * Get Member post by profile type.
 *
 * @param $member_type
 *
 * @return array
 * @since BuddyBoss 1.0.0
 *
 * @global wpdb $wpdb WordPress database abstraction object.
 */
function bp_member_type_post_by_type( $member_type ) {
	static $member_type_post = array();
	global $wpdb;

	$cache_key = 'bb_member_type_post_by_type_' . sanitize_title( $member_type );

	if ( isset( $member_type_post[ $cache_key ] ) ) {
		return $member_type_post[ $cache_key ];
	}

	$query   = "SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = '%s' AND LOWER(meta_value) = '%s'";
	$query   = $wpdb->prepare( $query, '_bp_member_type_key', $member_type );
	$post_id = $wpdb->get_var( $query );

	// Fallback to legacy way to retrieve profile type from name by using singular label.
	if ( ! $post_id ) {
		$name    = str_replace( array( '-', '-' ), array( ' ', ',' ), $member_type );
		$query   = "SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = '%s' AND LOWER(meta_value) = '%s'";
		$query   = $wpdb->prepare( $query, '_bp_member_type_label_singular_name', $name );
		$post_id = $wpdb->get_var( $query );
	}

	$member_type_post[ $cache_key ] = apply_filters( 'bp_member_type_post_by_type', $post_id );

	return $member_type_post[ $cache_key ];
}

/**
 * Gets member by type id.
 *
 * @param $type_id
 *
 * @return array
 * @since BuddyBoss 1.0.0
 *
 * @global wpdb $wpdb WordPress database abstraction object.
 */
function bp_member_type_by_type( $type_id ) {
	global $wpdb;

	$member_ids = array();

	if ( empty( $type_id ) ) {
		return $member_ids;
	}

	$cache_key  = 'bp_member_type_by_type_' . $type_id;
	$member_ids = wp_cache_get( $cache_key, 'bp_member_member_type' );
	if ( false === $member_ids ) {
		$member_ids = $wpdb->get_col( "SELECT u.ID FROM {$wpdb->users} u INNER JOIN {$wpdb->term_relationships} r ON u.ID = r.object_id WHERE u.user_status = 0 AND r.term_taxonomy_id = " . $type_id );
		wp_cache_set( $cache_key, $member_ids, 'bp_member_member_type' );
	}

	return $member_ids;
}

/**
 * Get all profile types.
 *
 * @since BuddyBoss 1.0.0
 *
 * @param array $args Arguments
 *
 * @return array Member types
 */
function bp_get_active_member_types( $args = array() ) {

	if ( ! bp_member_type_enable_disable() ) {
		return array();
	}

	static $cache           = array();
	$bp_active_member_types = array();

	$args = bp_parse_args(
		$args,
		array(
			'posts_per_page' => - 1,
			'post_type'      => bp_get_member_type_post_type(),
			'post_status'    => 'publish',
			'orderby'        => 'menu_order',
			'order'          => 'ASC',
			'fields'         => 'ids',
		),
		'member_types'
	);

	$cache_key = 'bp_get_active_member_types_' . md5( maybe_serialize( $args ) );

	if ( isset( $cache[ $cache_key ] ) ) {
		return $cache[ $cache_key ];
	}

	$bp_active_member_types_query = new \WP_Query( $args );

	if ( $bp_active_member_types_query->have_posts() ) {
		$bp_active_member_types = $bp_active_member_types_query->posts;
	}
	wp_reset_postdata();

	$bp_active_member_types = apply_filters( 'bp_get_active_member_types', $bp_active_member_types );
	$cache[ $cache_key ]    = $bp_active_member_types;

	return $bp_active_member_types;
}

/**
 * Removed profile type.
 *
 * @since BuddyBoss 1.0.0
 *
 * @return array
 */
function bp_get_removed_member_types() {
	$bp_member_type_ids  = array();
	$post_type           = bp_get_member_type_post_type();
	$bp_member_type_args = array(
		'post_type'  => $post_type,
		'meta_query' => array(
			array(
				'key'     => '_bp_member_type_enable_remove',
				'value'   => 1,
				'compare' => '=',
			),
		),
		'nopaging'   => true,
	);

	$bp_member_type_query = wp_cache_get( 'bp_get_removed_member_types', 'bp_member_member_type' );

	if ( false === $bp_member_type_query ) {
		$bp_member_type_query = new WP_Query( $bp_member_type_args );
		wp_cache_set( 'bp_get_removed_member_types', $bp_member_type_query, 'bp_member_member_type' );
	}
	if ( $bp_member_type_query->have_posts() ) :
		while ( $bp_member_type_query->have_posts() ) :
			$bp_member_type_query->the_post();

			$post_id              = get_the_ID();
			$name                 = bp_get_member_type_key( $post_id );
			$bp_member_type_ids[] = array(
				'ID'   => $post_id,
				'name' => $name,
			);
		endwhile;
	endif;
	wp_reset_query();
	wp_reset_postdata();
	return $bp_member_type_ids;
}

/**
 * Get members removed profile type.
 *
 * @return array
 * @since BuddyBoss 1.0.0
 */
function bp_get_users_of_removed_member_types() {
	$user_ids = array();
	// get removed profile type post ids.
	$bp_member_type_ids = bp_get_removed_member_types();
	// get removed profile type names/slugs.
	$bp_member_type_names = array();
	if ( isset( $bp_member_type_ids ) && ! empty( $bp_member_type_ids ) ) {
		foreach ( $bp_member_type_ids as $single ) {
			$bp_member_type_names[] = $single['name'];
		}
	}

	// get member user ids.
	if ( isset( $bp_member_type_names ) && ! empty( $bp_member_type_names ) ) {
		foreach ( $bp_member_type_names as $type_name ) {
			$type_id           = bp_member_type_term_taxonomy_id( $type_name );
			$member_type_users = bp_member_type_by_type( $type_id );
			if ( isset( $member_type_users ) && ! empty( $member_type_users ) ) {
				foreach ( $member_type_users as $single ) {
					$user_ids[] = $single;
				}
			}
		}
	}

	return $user_ids;
}

/**
 * Register all active profile types.
 *
 * @since BuddyBoss 1.0.0
 */
function bp_register_active_member_types() {
	$member_type_ids = bp_get_active_member_types();

	if ( ! empty( $member_type_ids ) ) {

		foreach ( $member_type_ids as $member_type_id ) {
			$key = bp_get_member_type_key( $member_type_id );

			bp_register_member_type(
				$key,
				array(
					'labels'        => array(
						'name'          => get_post_meta( $member_type_id, '_bp_member_type_label_name', true ),
						'singular_name' => get_post_meta( $member_type_id, '_bp_member_type_label_singular_name', true ),
					),
					'has_directory' => true,
				)
			);
		}
	}
}



/**
 * Set profile type while updating user profile.
 *
 * @since BuddyBoss 1.0.0
 *
 * @param $user_id
 * @param $user_role
 */
function bp_update_user_member_type_type_set( $user_id, $user_role ) {

	$get_member_type = bp_get_member_type_by_wp_role( $user_role );

	if ( isset( $get_member_type[0]['name'] ) && ! empty( $get_member_type[0]['name'] ) ) {
		bp_set_member_type( $user_id, $get_member_type[0]['name'] );
	}
}

/**
 * Displays a user by it's type.
 *
 * @since BuddyBoss 1.0.0
 */
function bp_member_type_directory() {
	$member_types = bp_get_active_member_types();

	foreach ( $member_types as $member_type_id ) {

		if ( ! get_post_meta( $member_type_id, '_bp_member_type_enable_filter', true ) ) {
			continue;
		}

		$type_name        = bp_get_member_type_key( $member_type_id );
		$type_id          = bp_member_type_term_taxonomy_id( $type_name );
		$members_count    = count( bp_member_type_by_type( $type_id ) );
		$member_type_name = get_post_meta( $member_type_id, '_bp_member_type_label_name', true );

		if ( empty( $type_id ) ) {
			$type_id = 0;
		}
		?>
		<li id="members-<?php echo $type_id; ?>">
			<a href="<?php echo bp_member_type_directory_permalink( $type_name ); ?>"><?php printf( '%s <span>%s</span>', $member_type_name, $members_count ); // @todo no variables in the text domain please ?></a>
		</li>
		<?php
	}
}

/**
 * Remove profile type metabox for users who doesn't have permission to change profile types.
 *
 * @since BuddyBoss 1.0.0
 */
function bp_remove_member_type_metabox() {
	if ( ! current_user_can( 'manage_options' ) ) {
		remove_meta_box( 'bp_members_admin_member_type', get_current_screen()->id, 'side' );
	}
}

/**
 * Removes metabox from member profile if profile types are disabled.
 *
 * @since BuddyBoss 1.0.0
 */
function bp_remove_member_type_metabox_globally() {
	remove_meta_box( 'bp_members_admin_member_type', get_current_screen()->id, 'side' );
}

/**
 * Add new columns to the post type list screen.
 *
 * @since BuddyBoss 1.0.0
 *
 * @param type $columns
 * @return type
 */
function bp_member_type_add_column( $columns ) {

	$columns['title']         = __( 'Profile Type', 'buddyboss' );
	$columns['member_type']   = __( 'Label', 'buddyboss' );
	$columns['enable_filter'] = __( 'Members Filter', 'buddyboss' );
	$columns['enable_remove'] = __( 'Members Directory', 'buddyboss' );
	$columns['total_users']   = __( 'Users', 'buddyboss' );

	unset( $columns['date'] );

	return $columns;
}

/**
 * Display data by column and post id.
 *
 * @since BuddyBoss 1.0.0
 *
 * @param $column
 * @param $post_id
 */
function bp_member_type_show_data( $column, $post_id ) {

	switch ( $column ) {

		case 'member_type':
			echo '<code>' . get_post_meta( $post_id, '_bp_member_type_label_singular_name', true ) . '</code>';
			break;

		case 'enable_filter':
			if ( get_post_meta( $post_id, '_bp_member_type_enable_filter', true ) ) {
				_e( 'Show', 'buddyboss' );
			} else {
				_e( 'Hide', 'buddyboss' );
			}

			break;

		case 'enable_remove':
			if ( get_post_meta( $post_id, '_bp_member_type_enable_remove', true ) ) {
				_e( 'Hide', 'buddyboss' );
			} else {
				_e( 'Show', 'buddyboss' );
			}

			break;

		case 'total_users':
			$name    = bp_get_member_type_key( $post_id );
			$type_id = bp_member_type_term_taxonomy_id( $name );

			$member_type_url = admin_url() . 'users.php?bp-member-type=' . $name;
			$count           = count( bp_member_type_by_type( $type_id ) );

			if ( $count > 0 ) {
				// @todo why text domain here and below?
				printf( '<a href="%s">%s</a>', esc_url( $member_type_url ), $count );
			} else {
				echo '0';
			}

			break;

	}

}

/**
 * Sets up a column on admin view on profile type post type.
 *
 * @since BuddyBoss 1.0.0
 *
 * @param $columns
 *
 * @return array
 */
function bp_member_type_add_sortable_columns( $columns ) {

	$columns['total_users']   = 'total_users';
	$columns['enable_filter'] = 'enable_filter';
	$columns['enable_remove'] = 'enable_remove';
	$columns['member_type']   = 'member_type';

	return $columns;
}

/**
 * Adds a filter to profile type sort items.
 *
 * @since BuddyBoss 1.0.0
 */
function bp_member_type_add_request_filter() {

	add_filter( 'request', 'bp_member_type_sort_items' );

}

/**
 * Sort list of profile type post types.
 *
 * @since BuddyBoss 1.0.0
 *
 * @param type $qv
 * @return string
 */
function bp_member_type_sort_items( $qv ) {

	if ( ! isset( $qv['post_type'] ) || $qv['post_type'] != bp_get_member_type_post_type() ) {
		return $qv;
	}

	if ( ! isset( $qv['orderby'] ) ) {
		return $qv;
	}

	switch ( $qv['orderby'] ) {

		case 'member_type':
			$qv['meta_key'] = '_bp_member_type_name';
			$qv['orderby']  = 'meta_value';

			break;

		case 'enable_filter':
			$qv['meta_key'] = '_bp_member_type_enable_filter';
			$qv['orderby']  = 'meta_value_num';

			break;

	}

	return $qv;
}

/**
 * Hide quick edit link.
 *
 * @since BuddyBoss 1.0.0
 *
 * @param type $actions
 * @param type $post
 * @return type
 */
function bp_member_type_hide_quickedit( $actions, $post ) {

	if ( empty( $post ) ) {
		global $post;
	}

	if ( bp_get_member_type_post_type() == $post->post_type ) {
		unset( $actions['inline hide-if-no-js'] );
	}

	return $actions;
}

/**
 * Adds body class where the shortcode is added.
 *
 * @since BuddyBoss 1.0.0
 *
 * @param $class
 *
 * @return array
 */
function bp_member_type_shortcode_add_body_class( $class ) {

	global $post;

	if ( isset( $post->post_content ) && has_shortcode( $post->post_content, 'profile' ) ) {
		$class[] = 'directory';
		$class[] = 'members';
		$class[] = 'buddypress';
		$class[] = 'buddyboss';
		/**
		 *This class commented because this class will add when buddypanel enable
		 *and this condition already in the theme
		 */
		// $class[] = 'bb-buddypanel';
	}
	return $class;
}

/**
 * Displays shortcode data.
 *
 * @since BuddyBoss 1.0.0
 *
 * @param $atts
 *
 * @return false|string
 */
function bp_member_type_shortcode_callback( $atts ) {

	ob_start();

	echo '<div id="buddypress" class="buddypress-wrap bp-dir-hori-nav bp-shortcode-wrap">';
	echo '<div class="members">';
	echo '<div class="subnav-filters filters no-ajax" id="subnav-filters">';
	bp_get_template_part( 'common/filters/grid-filters' );
	echo '</div>';
	echo '<div class="screen-content members-directory-content">';

	echo '<div id="members-dir-list" class="members dir-list">';

	if ( ! empty( $atts['type'] ) ) {

		$name = str_replace( array( ' ', ',' ), array( '-', '-' ), strtolower( $atts['type'] ) );

		// Set the "current" profile type, if one is provided, in member directories.
		buddypress()->current_member_type = $name;
		buddypress()->current_component   = 'members';
		buddypress()->is_directory        = true;
	}

	// Get a BuddyPress members-loop template part for display in a theme.
	bp_get_template_part( 'members/members-loop' );

	echo '</div> <!-- #members-dir-list -->';
	echo '</div><!-- .members-directory-content -->';
	echo '</div><!-- .members -->';
	echo '</div><!-- #buddypress -->';

	return ob_get_clean();

}

/**
 * Adds the JS on profile type post type.
 *
 * @since BuddyBoss 1.0.0
 */
function bp_member_type_changing_listing_label() {
	global $current_screen;

	$url_clip_board = buddypress()->plugin_url . 'bp-core/js/vendor/';
	$url_member     = buddypress()->plugin_url . 'bp-core/js/';

	$bp_member_type_pages = array(
		'edit-bp-member-type',
		'bp-member-type',
		'bp-group-type',
		'edit-bp-group-type',
	);

	// Check to make sure we're on a profile type's admin page.
	if ( isset( $current_screen->id ) && in_array( $current_screen->id, $bp_member_type_pages ) ) {

		wp_enqueue_script( 'bp-clipboard', $url_clip_board . 'clipboard.js', array(), bp_get_version() );
		wp_enqueue_script( 'bp-member-type-admin-screen', $url_member . 'bp-member-type-admin-screen.js', array( 'jquery' ), bp_get_version() );

		$strings = array(
			'warnTrash'       => __( 'You have {total_users} members with this profile type, are you sure you would like to trash it?', 'buddyboss' ),
			'warnDelete'      => __( 'You have {total_users} members with this profile type, are you sure you would like to delete it?', 'buddyboss' ),
			'warnBulkTrash'   => __( 'You have members with these profile types, are you sure you would like to trash it?', 'buddyboss' ),
			'warnBulkDelete'  => __( 'You have members with these profile types, are you sure you would like to delete it?', 'buddyboss' ),
			'copied'          => __( 'Copied', 'buddyboss' ),
			'copytoclipboard' => __( 'Copy to clipboard', 'buddyboss' ),
		);

		wp_localize_script( 'bp-member-type-admin-screen', '_bpmtAdminL10n', $strings );
	}
}

/**
 * Get profile type.
 *
 * @since BuddyBoss 1.0.0
 *
 * @param $user_id
 *
 * @return string
 */
function bp_get_user_member_type( $user_id ) {

	if ( empty( $user_id ) ) {
		$user_id = bp_displayed_user_id();
	}

	$member_type = __( 'Member', 'buddyboss' );

	if ( true === bp_member_type_enable_disable() ) {
		if ( true === bp_member_type_display_on_profile() ) {

			// Get the profile type.
			$type = bp_get_member_type( $user_id );

			// Output the.
			if ( $type_obj = bp_get_member_type_object( $type ) ) {
				$member_type = $type_obj->labels['singular_name'];
			}

			$string = '<span class="bp-member-type bb-current-member-' . esc_attr( $type ) . '">' . $member_type . '</span>';
		} else {
			$string = '<span class="bp-member-type">' . $member_type . '</span>';
		}
	} else {
		$string = '<span class="bp-member-type">' . $member_type . '</span>';
	}

	return apply_filters( 'bp_member_type_name_string', $string, $member_type, $user_id );
}

/**
 * Return "his", "her" or "their" based on member selected gender, used in activity feeds.
 *
 * @since BuddyBoss 1.0.0
 *
 * @param string $user_id
 *
 * @return string
 */
function bp_get_user_gender_pronoun_type( $user_id = '' ) {

	global $wpdb;
	global $bp;

	if ( '' === $user_id ) {
		$gender_pronoun = esc_html__( 'their', 'buddyboss' );
	} else {
		$table         = bp_core_get_table_prefix() . 'bp_xprofile_fields';
		$exists_gender = $wpdb->get_results( "SELECT COUNT(*) as count, id FROM {$table} a WHERE parent_id = 0 AND type = 'gender' " );
		if ( $exists_gender[0]->count > 0 ) {
			$field_id = $exists_gender[0]->id;
			$gender   = xprofile_get_field_data( $field_id, $user_id );
			if ( empty( $gender ) ) {
				$gender_pronoun = esc_html__( 'their', 'buddyboss' );
			} else {
				$split_value = explode( '_', $gender );
				if ( 'his' === $split_value[0] ) {
					$gender_pronoun = esc_html__( 'his', 'buddyboss' );
				} elseif ( 'her' === $split_value[0] ) {
					$gender_pronoun = esc_html__( 'her', 'buddyboss' );
				} else {
					$gender_pronoun = esc_html__( 'their', 'buddyboss' );
				}
			}
		} else {
			$gender_pronoun = esc_html__( 'their', 'buddyboss' );
		}
	}
	return $gender_pronoun;
}

/**
 * Sets authorization cookies containing the originating user information.
 *
 * @since BuddyBoss 1.0.0
 *
 * @param int    $old_user_id The ID of the originating user, usually the current logged in user.
 * @param bool   $pop Optional. Pop the latest user off the auth cookie, instead of appending the new one. Default false.
 * @param string $token Optional. The old user's session token to store for later reuse. Default empty string.
 */
function bp_member_switching_set_olduser_cookie( $old_user_id, $pop = false, $token = '' ) {
	$secure_auth_cookie    = BP_Core_Members_Switching::secure_auth_cookie();
	$secure_olduser_cookie = BP_Core_Members_Switching::secure_olduser_cookie();
	$expiration            = time() + 172800; // 48 hours
	$auth_cookie           = bp_member_switching_get_auth_cookie();
	$olduser_cookie        = wp_generate_auth_cookie( $old_user_id, $expiration, 'logged_in', $token );

	if ( $secure_auth_cookie ) {
		$auth_cookie_name = BP_MEMBER_SWITCHING_SECURE_COOKIE;
		$scheme           = 'secure_auth';
	} else {
		$auth_cookie_name = BP_MEMBER_SWITCHING_COOKIE;
		$scheme           = 'auth';
	}

	if ( $pop ) {
		array_pop( $auth_cookie );
	} else {
		array_push( $auth_cookie, wp_generate_auth_cookie( $old_user_id, $expiration, $scheme, $token ) );
	}

	$auth_cookie = json_encode( $auth_cookie );

	/** This filter is documented in wp-includes/pluggable.php */
	if ( ! apply_filters( 'send_auth_cookies', true ) ) {
		return;
	}

	setcookie( $auth_cookie_name, $auth_cookie, $expiration, SITECOOKIEPATH, COOKIE_DOMAIN, $secure_auth_cookie, true );
	setcookie( BP_MEMBER_SWITCHING_OLDUSER_COOKIE, $olduser_cookie, $expiration, COOKIEPATH, COOKIE_DOMAIN, $secure_olduser_cookie, true );
}


/**
 * Clears the cookies containing the originating user, or pops the latest item off the end if there's more than one.
 *
 * @since BuddyBoss 1.0.0
 *
 * @param bool $clear_all Optional. Whether to clear the cookies (as opposed to just popping the last user off the end). Default true.
 */
function bp_member_switching_clear_olduser_cookie( $clear_all = true ) {
	$auth_cookie = bp_member_switching_get_auth_cookie();
	if ( ! empty( $auth_cookie ) ) {
		array_pop( $auth_cookie );
	}
	if ( $clear_all || empty( $auth_cookie ) ) {

		/** This filter is documented in wp-includes/pluggable.php */
		if ( ! apply_filters( 'send_auth_cookies', true ) ) {
			return;
		}

		$expire = time() - 31536000;
		setcookie( BP_MEMBER_SWITCHING_COOKIE, ' ', $expire, SITECOOKIEPATH, COOKIE_DOMAIN );
		setcookie( BP_MEMBER_SWITCHING_SECURE_COOKIE, ' ', $expire, SITECOOKIEPATH, COOKIE_DOMAIN );
		setcookie( BP_MEMBER_SWITCHING_OLDUSER_COOKIE, ' ', $expire, COOKIEPATH, COOKIE_DOMAIN );
	} else {
		if ( BP_Core_Members_Switching::secure_auth_cookie() ) {
			$scheme = 'secure_auth';
		} else {
			$scheme = 'auth';
		}

		$old_cookie = end( $auth_cookie );

		$old_user_id = wp_validate_auth_cookie( $old_cookie, $scheme );
		if ( $old_user_id ) {
			$parts = wp_parse_auth_cookie( $old_cookie, $scheme );
			bp_member_switching_set_olduser_cookie( $old_user_id, true, $parts['token'] );
		}
	}
}


/**
 * Gets the value of the cookie containing the originating user.
 *
 * @since BuddyBoss 1.0.0
 * @return string|false The old user cookie, or boolean false if there isn't one.
 */
function bp_member_switching_get_olduser_cookie() {
	if ( isset( $_COOKIE[ BP_MEMBER_SWITCHING_OLDUSER_COOKIE ] ) ) {
		return wp_unslash( $_COOKIE[ BP_MEMBER_SWITCHING_OLDUSER_COOKIE ] );
	} else {
		return false;
	}
}


/**
 * Gets the value of the auth cookie containing the list of originating users.
 *
 * @since BuddyBoss 1.0.0
 * @return string[] Array of originating user authentication cookie values. Empty array if there are none.
 */
function bp_member_switching_get_auth_cookie() {
	if ( BP_Core_Members_Switching::secure_auth_cookie() ) {
		$auth_cookie_name = BP_MEMBER_SWITCHING_SECURE_COOKIE;
	} else {
		$auth_cookie_name = BP_MEMBER_SWITCHING_COOKIE;
	}

	if ( isset( $_COOKIE[ $auth_cookie_name ] ) && is_string( $_COOKIE[ $auth_cookie_name ] ) ) {
		$cookie = json_decode( wp_unslash( $_COOKIE[ $auth_cookie_name ] ) );
	}
	if ( ! isset( $cookie ) || ! is_array( $cookie ) ) {
		$cookie = array();
	}

	return $cookie;
}


/**
 * Switches the current logged in user to the specified user.
 *
 * @since BuddyBoss 1.0.0
 *
 * @param  int  $user_id The ID of the user to switch to.
 * @param  bool $remember Optional. Whether to 'remember' the user in the form of a persistent browser cookie. Default false.
 * @param  bool $set_old_user Optional. Whether to set the old user cookie. Default true.
 *
 * @return false|WP_User WP_User object on success, false on failure.
 */
function bp_member_switch_to( $user_id, $remember = false, $set_old_user = true ) {
	$user = get_userdata( $user_id );

	if ( ! $user ) {
		return false;
	}

	$old_user_id  = ( is_user_logged_in() ) ? get_current_user_id() : false;
	$old_token    = function_exists( 'wp_get_session_token' ) ? wp_get_session_token() : '';
	$auth_cookie  = bp_member_switching_get_auth_cookie();
	$cookie_parts = wp_parse_auth_cookie( end( $auth_cookie ) );

	if ( $set_old_user && $old_user_id ) {
		// Switching to another user.
		$new_token = '';
		// We'll not override the old user.
		if ( empty( $auth_cookie ) ) {
			bp_member_switching_set_olduser_cookie( $old_user_id, false, $old_token );
		}
	} else {
		// Switching back, either after being switched off or after being switched to another user.
		$new_token = isset( $cookie_parts['token'] ) ? $cookie_parts['token'] : '';
		bp_member_switching_clear_olduser_cookie( false );
	}

	/**
	 * Attaches the original user ID and session token to the new session when a user switches to another user.
	 *
	 * @param array $session Array of extra data.
	 * @param int $user_id User ID.
	 *
	 * @return array Array of extra data.
	 */
	$session_filter = function ( array $session, $user_id ) use ( $old_user_id, $old_token ) {
		$session['switched_from_id']      = $old_user_id;
		$session['switched_from_session'] = $old_token;

		return $session;
	};

	add_filter( 'attach_session_information', $session_filter, 99, 2 );

	wp_clear_auth_cookie();
	wp_set_auth_cookie( $user_id, $remember, '', $new_token );
	wp_set_current_user( $user_id );

	if ( $old_token && $old_user_id && ! $set_old_user ) {
		// When switching back, destroy the session for the old user.
		$manager = WP_Session_Tokens::get_instance( $old_user_id );
		$manager->destroy( $old_token );
	}

	return $user;
}

/**
 * Returns whether or not the current user switched into their account.
 *
 * @since BuddyBoss 1.0.0
 * @return false|WP_User False if the user isn't logged in or they didn't switch in; old user object (which evaluates to
 *                       true) if the user switched into the current user account.
 */
function bp_current_member_switched() {
	if ( ! is_user_logged_in() ) {
		return false;
	}

	return BP_Core_Members_Switching::get_old_user();
}

/**
 * Add members to Auto Group Membership Approval based on their profile type.
 *
 * @since BuddyBoss 1.0.0
 *
 * @param int $user_id The user ID to add the Auto Group Membership Approval.
 */
function bp_member_add_auto_join_groups( $user_id, $key, $user ) {

	$user_member_type = bp_get_member_type( $user_id );

	// Get post id of selected profile type.
	$post_id = bp_member_type_post_by_type( $user_member_type );

	// Get selected Auto Membership Approval group types.
	$group_types = get_post_meta( $post_id, '_bp_member_type_enabled_group_type_auto_join', true );

	if ( ! empty( $group_types ) && isset( $group_types ) ) {

		foreach ( $group_types as $group_type ) {

			$groups_args = array(
				'object'     => 'groups',
				'per_page'   => 0,
				'group_type' => array( $group_type ),
			);

			if ( bp_has_groups( $groups_args ) ) :

				while ( bp_groups() ) :
					bp_the_group();

					$group_id = bp_get_group_id();

					// check if already member.
					$membership = new BP_Groups_Member( $user_id, $group_id );
					if ( ! isset( $membership->id ) ) {
						// add as member.
						groups_join_group( $group_id, $user_id );
					}

				endwhile;

			endif;
		}
	}
}
add_action( 'bp_core_activated_user', 'bp_member_add_auto_join_groups', 99, 3 );

/**
 * Set default profile type on registration.
 *
 * @since BuddyBoss 1.0.0
 *
 * @param $user_id
 * @param $key
 * @param $user
 */
function bp_assign_default_member_type_to_activate_user( $user_id, $key, $user ) {
	global $bp, $wpdb;

	// Check whether member type is enabled.
	if ( true === bp_member_type_enable_disable() ) {

		// Check Member Type Dropdown added on register page.
		$get_parent_id_of_member_types_field  = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$wpdb->base_prefix}bp_xprofile_fields WHERE type = %s AND parent_id = %d ", 'membertypes', 0 ) );
		$get_selected_member_type_on_register = trim( $wpdb->get_var( $wpdb->prepare( "SELECT value FROM {$wpdb->base_prefix}bp_xprofile_data WHERE user_id = %s AND field_id = %d ", $user_id, $get_parent_id_of_member_types_field ) ) );
		// return to user if default member type is not set.
		$existing_selected = bp_member_type_default_on_registration();

		// Check one of them is added.
		if ( '' !== $existing_selected || '' !== $get_selected_member_type_on_register ) {

			$email = bp_core_get_user_email( $user_id );

			// Check if invites component enabled.
			if ( bp_is_active( 'invites' ) ) {
				$inviters = array();

				$args = array(
					'post_type'      => bp_get_invite_post_type(),
					'posts_per_page' => - 1,
					'meta_query'     => array(
						array(
							'key'     => '_bp_invitee_email',
							'value'   => $email,
							'compare' => '=',
						),
					),
				);

				$bp_get_invitee_email = new WP_Query( $args );

				if ( $bp_get_invitee_email->have_posts() ) {

					$member_type = get_post_meta( get_the_ID(), '_bp_invitee_member_type', true );
					// Check if user is invited for specific member type.
					if ( isset( $member_type ) && ! empty( $member_type ) ) {

						// Assign the invited member type to user.
						bp_set_member_type( $user_id, '' );
						bp_set_member_type( $user_id, $member_type );
						$member_type_id                = bp_member_type_post_by_type( $member_type );
						$selected_member_type_wp_roles = get_post_meta( $member_type_id, '_bp_member_type_wp_roles', true );

						if ( isset( $selected_member_type_wp_roles[0] ) && 'none' !== $selected_member_type_wp_roles[0] ) {
							$bp_user = new WP_User( $user_id );
							foreach ( $bp_user->roles as $role ) {
								// Remove role.
								$bp_user->remove_role( $role );
							}
							// Add role.
							$bp_user->add_role( $selected_member_type_wp_roles[0] );
						}
					} else {

						if ( '' !== $get_selected_member_type_on_register ) {
							// Get selected profile type role.
							$selected_member_type_wp_roles = get_post_meta( $get_selected_member_type_on_register, '_bp_member_type_wp_roles', true );
							$type_name                     = bp_get_member_type_key( $get_selected_member_type_on_register );

							// Assign the default member type to user.
							bp_set_member_type( $user_id, '' );
							bp_set_member_type( $user_id, $type_name );
							if ( isset( $selected_member_type_wp_roles[0] ) && 'none' !== $selected_member_type_wp_roles[0] ) {
								$bp_user = new WP_User( $user_id );
								foreach ( $bp_user->roles as $role ) {
									// Remove role.
									$bp_user->remove_role( $role );
								}
								// Add role.
								$bp_user->add_role( $selected_member_type_wp_roles[0] );
							}
						} else {
							// Assign the default member type to user.
							bp_set_member_type( $user_id, '' );
							bp_set_member_type( $user_id, $existing_selected );
							$member_type_id                = bp_member_type_post_by_type( $existing_selected );
							$selected_member_type_wp_roles = get_post_meta( $member_type_id, '_bp_member_type_wp_roles', true );

							if ( isset( $selected_member_type_wp_roles[0] ) && 'none' !== $selected_member_type_wp_roles[0] ) {
								$bp_user = new WP_User( $user_id );
								foreach ( $bp_user->roles as $role ) {
									// Remove role.
									$bp_user->remove_role( $role );
								}
								// Add role.
								$bp_user->add_role( $selected_member_type_wp_roles[0] );
							}
						}
					}
					// If user is not invited by send invites then assign default member type.
				} else {

					if ( '' !== $get_selected_member_type_on_register ) {
						// Get selected profile type role.
						$selected_member_type_wp_roles = get_post_meta( $get_selected_member_type_on_register, '_bp_member_type_wp_roles', true );
						$type_name                     = bp_get_member_type_key( $get_selected_member_type_on_register );

						// Assign the default member type to user.
						bp_set_member_type( $user_id, '' );
						bp_set_member_type( $user_id, $type_name );

						if ( isset( $selected_member_type_wp_roles[0] ) && 'none' !== $selected_member_type_wp_roles[0] ) {
							$bp_user = new WP_User( $user_id );
							foreach ( $bp_user->roles as $role ) {
								// Remove role.
								$bp_user->remove_role( $role );
							}
							// Add role.
							$bp_user->add_role( $selected_member_type_wp_roles[0] );
						}
					} else {
						// Assign the default member type to user.
						bp_set_member_type( $user_id, '' );
						bp_set_member_type( $user_id, $existing_selected );
						$member_type_id                = bp_member_type_post_by_type( $existing_selected );
						$selected_member_type_wp_roles = get_post_meta( $member_type_id, '_bp_member_type_wp_roles', true );

						if ( isset( $selected_member_type_wp_roles[0] ) && 'none' !== $selected_member_type_wp_roles[0] ) {
							$bp_user = new WP_User( $user_id );
							foreach ( $bp_user->roles as $role ) {
								// Remove role.
								$bp_user->remove_role( $role );
							}
							// Add role.
							$bp_user->add_role( $selected_member_type_wp_roles[0] );
						}
					}
				}
			} else {

				if ( '' !== $get_selected_member_type_on_register ) {
					// Get selected profile type role.
					$selected_member_type_wp_roles = get_post_meta( $get_selected_member_type_on_register, '_bp_member_type_wp_roles', true );
					$type_name                     = bp_get_member_type_key( $get_selected_member_type_on_register );

					// Assign the default member type to user.
					bp_set_member_type( $user_id, '' );
					bp_set_member_type( $user_id, $type_name );

					if ( isset( $selected_member_type_wp_roles[0] ) && 'none' !== $selected_member_type_wp_roles[0] ) {
						$bp_user = new WP_User( $user_id );
						foreach ( $bp_user->roles as $role ) {
							// Remove role.
							$bp_user->remove_role( $role );
						}
						// Add role.
						$bp_user->add_role( $selected_member_type_wp_roles[0] );
					}
				} else {
					// Assign the default member type to user.
					bp_set_member_type( $user_id, '' );
					bp_set_member_type( $user_id, $existing_selected );

					$member_type_id                = bp_member_type_post_by_type( $existing_selected );
					$selected_member_type_wp_roles = get_post_meta( $member_type_id, '_bp_member_type_wp_roles', true );

					if ( isset( $selected_member_type_wp_roles[0] ) && 'none' !== $selected_member_type_wp_roles[0] ) {
						$bp_user = new WP_User( $user_id );
						foreach ( $bp_user->roles as $role ) {
							// Remove role.
							$bp_user->remove_role( $role );
						}
						// Add role.
						$bp_user->add_role( $selected_member_type_wp_roles[0] );
					}
				}
			}
		}
	}

}
add_action( 'bp_core_activated_user', 'bp_assign_default_member_type_to_activate_user', 10, 3 );

/**
 * Set default profile type on registration.
 *
 * @since BuddyBoss 1.0.0
 *
 * @param int $user_id User Id.
 */
function bp_assign_default_member_type_to_activate_user_on_admin( $user_id ) {
	global $bp;

	// Check whether member type is enabled.
	if ( true === bp_member_type_enable_disable() ) {

		// return to user if default member type is not set.
		$existing_selected = bp_member_type_default_on_registration();
		if ( '' === $existing_selected ) {
			return;
		}

		$email = bp_core_get_user_email( $user_id );

		// Check if invites component enabled.
		if ( bp_is_active( 'invites' ) ) {
			$inviters = array();

			$args = array(
				'post_type'      => bp_get_invite_post_type(),
				'posts_per_page' => - 1,
				'meta_query'     => array(
					array(
						'key'     => '_bp_invitee_email',
						'value'   => $email,
						'compare' => '=',
					),
				),
			);

			$bp_get_invitee_email = new WP_Query( $args );

			if ( $bp_get_invitee_email->have_posts() ) {

				$member_type = get_post_meta( get_the_ID(), '_bp_invitee_member_type', true );
				// Check if user is invited for specific member type.
				if ( isset( $member_type ) && ! empty( $member_type ) ) {
					// Assign the invited member type to user.
					bp_set_member_type( $user_id, '' );
					bp_set_member_type( $user_id, $member_type );

					$member_type_id                = bp_member_type_post_by_type( $member_type );
					$selected_member_type_wp_roles = get_post_meta( $member_type_id, '_bp_member_type_wp_roles', true );

					if ( isset( $selected_member_type_wp_roles[0] ) && 'none' !== $selected_member_type_wp_roles[0] ) {
						$bp_user = new WP_User( $user_id );
						foreach ( $bp_user->roles as $role ) {
							// Remove role.
							$bp_user->remove_role( $role );
						}
						// Add role.
						$bp_user->add_role( $selected_member_type_wp_roles[0] );
					}
				} else {
					/**
					 * Assign the default member type to user on Admin.
					 *
					 * @since BuddyBoss 2.3.2
					 *
					 * @param int $user_id ID of user.
					 * @param string $member_type Default selected member type.
					 */
					do_action( 'bb_assign_default_member_type_to_activate_user_on_admin', $user_id, $existing_selected );
				}
				// If user is not invited by send invites then assign default member type.
			} else {
				/**
				 * Assign the default member type to user on Admin.
				 *
				 * @since BuddyBoss 2.3.2
				 *
				 * @param int $user_id ID of user.
				 * @param string $member_type Default selected member type.
				 */
				do_action( 'bb_assign_default_member_type_to_activate_user_on_admin', $user_id, $existing_selected );
			}
		} else {
			/**
			 * Assign the default member type to user on Admin.
			 *
			 * @since BuddyBoss 2.3.2
			 *
			 * @param int $user_id ID of user.
			 * @param string $member_type Default selected member type.
			 */
			do_action( 'bb_assign_default_member_type_to_activate_user_on_admin', $user_id, $existing_selected );
		}
	}

}
add_action( 'user_register', 'bp_assign_default_member_type_to_activate_user_on_admin', 10, 1 );

/**
 * Show/Hide Email Invites tab in user profile navigation if member type enabled and restrict member type via
 * BuddyBoss > Settings > Invites > Allowed Profile Type.
 *
 * @since BuddyBoss 1.0.0
 *
 * @return bool
 */
function bp_allow_user_to_send_invites() {

	// if user not logged in and component not active then return false.
	if ( ! bp_is_active( 'invites' ) && ! is_user_logged_in() ) {
		return false;
	}

	// Get all active member type.
	$member_types = bp_get_active_member_types();
	if ( ! empty( $member_types ) ) {
		$allowed_member_type    = array();
		$disallowed_member_type = array();
		foreach ( $member_types as $member_type_id ) {
			$type_name = bp_get_member_type_key( $member_type_id );
			$set_value = bp_enable_send_invite_member_type( 'bp-enable-send-invite-member-type-' . $type_name );
			if ( true === $set_value ) {
				$allowed_member_type[] = $type_name;
			} else {
				$disallowed_member_type[] = $type_name;
			}
		}

		if ( empty( $allowed_member_type ) ) {
			return true;
		}

		// Get the member type of current logged in user.
		$member_type = bp_get_member_type( bp_loggedin_user_id() );
		if ( ( is_admin() || is_network_admin() ) && current_user_can( 'manage_options' ) ) {
			return true;
		} elseif ( false === $member_type && ! current_user_can( 'manage_options' ) ) {
			return false;
		} elseif ( false === $member_type && current_user_can( 'manage_options' ) ) {
			return true;
		} elseif ( empty( $allowed_member_type ) || count( $allowed_member_type ) === count( $member_types ) ) {
			return true;
		} elseif ( in_array( $member_type, $disallowed_member_type, true ) ) {
			return false;
		} elseif ( in_array( $member_type, $allowed_member_type, true ) ) {
			return true;
		}
	}

	return true;
}

/**
 * Disable the WP Editor buttons not allowed in invites content.
 *
 * @since BuddyBoss 1.0.0
 *
 * @param  array $buttons  The WP Editor buttons list.
 * @return array           The filtered WP Editor buttons list.
 */
function bp_nouveau_btn_invites_mce_buttons( $buttons = array() ) {
	$buttons = array(
		'bold',
		'italic',
		'bullist',
		'numlist',
		'blockquote',
		'link',
	);

	// Provide extensibility.
	return apply_filters( 'bp_nouveau_btn_invites_mce_buttons', $buttons );
}

/**
 * Return the member type xprofile field id.
 *
 * @return string|null
 */
function bp_get_xprofile_member_type_field_id() {
	global $wpdb;

	static $get_parent_id_of_member_types_field = false;

	if ( false === $get_parent_id_of_member_types_field ) {
		$table                               = bp_core_get_table_prefix() . 'bp_xprofile_fields';
		$get_parent_id_of_member_types_field = (int) $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$table} WHERE type = %s AND parent_id = %d ", 'membertypes', 0 ) );
	}

	return (int) $get_parent_id_of_member_types_field;
}

/**
 * Return the gender type xprofile field id.
 *
 * @return string|null
 */
function bp_get_xprofile_gender_type_field_id() {
	static $get_parent_id_of_gender_types_field = false;

	if ( false === $get_parent_id_of_gender_types_field ) {
		global $wpdb;
		$table                               = bp_core_get_table_prefix() . 'bp_xprofile_fields';
		$get_parent_id_of_gender_types_field = (int) $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$table} WHERE type = %s AND parent_id = %d ", 'gender', 0 ) );
	}

	return (int) $get_parent_id_of_gender_types_field;
}

/**
 * Sync the BP data based on the WP data via infusion soft API.
 *
 * @param $user_id
 *
 * @since BuddyBoss 1.1.5
 */
function bp_infusion_soft_sync_bp_data( $user_id ) {

	if ( function_exists( 'iMember360' ) ) {

		$first_name_id = (int) get_option( 'bp-xprofile-firstname-field-id' );
		$nickname_id   = (int) get_option( 'bp-xprofile-nickname-field-id' );
		$last_name_id  = (int) get_option( 'bp-xprofile-lastname-field-id' );

		$xprofile_nick_name  = xprofile_get_field_data( $nickname_id, $user_id );
		$xprofile_first_name = xprofile_get_field_data( $first_name_id, $user_id );
		$xprofile_last_name  = xprofile_get_field_data( $last_name_id, $user_id );

		if ( '' === $xprofile_first_name ) {
			$result_first_name = get_user_meta( $user_id, 'first_name', true );
			if ( empty( $result_first_name ) ) {
				$result_first_name = get_user_meta( $user_id, 'nickname', true );
			}
			xprofile_set_field_data( $first_name_id, $user_id, $result_first_name );
		}

		if ( '' === trim( $xprofile_nick_name ) ) {
			$user = get_userdata( $user_id );
			// make sure nickname is valid.
			$nickname = get_user_meta( $user_id, 'nickname', true );
			$nickname = sanitize_title( $nickname );
			$invalid  = bp_xprofile_validate_nickname_value( '', $nickname_id, $nickname, $user_id );

			// or use the user_nicename.
			if ( ! $nickname || $invalid ) {
				$nickname = $user->user_nicename;
			}
			xprofile_set_field_data( $nickname_id, $user_id, $nickname );
		}

		if ( '' === $xprofile_last_name ) {
			$result_last_name = get_user_meta( $user_id, 'last_name', true );
			xprofile_set_field_data( $last_name_id, $user_id, $result_last_name );
		}
	}

}
add_action( 'user_register', 'bp_infusion_soft_sync_bp_data', 10, 1 );

/**
 * Function to add the content on top of members listing.
 *
 * @since BuddyBoss 1.2.5
 */
function bp_members_directory_page_content() {

	$page_ids = bp_core_get_directory_page_ids();

	if ( ! empty( $page_ids['members'] ) ) {
		$members_page_content = get_post_field( 'post_content', $page_ids['members'] );
		echo apply_filters( 'the_content', $members_page_content );
	}
}
add_action( 'bp_before_directory_members_page', 'bp_members_directory_page_content' );

/**
 * Function to add the content on activate page.
 *
 * @since BuddyBoss 1.2.5
 */
function bp_activate_page_content() {

	$page_ids = bp_core_get_directory_page_ids();

	if ( ! empty( $page_ids['activate'] ) ) {
		$activate_page_content = get_post_field( 'post_content', $page_ids['activate'] );
		echo apply_filters( 'the_content', $activate_page_content );
	}
}
add_action( 'bp_before_activation_page', 'bp_activate_page_content' );

/**
 * Function to add the content on register page
 *
 * @since BuddyBoss 1.2.5
 */
function bp_register_page_content() {

	$page_ids = bp_core_get_directory_page_ids();

	if ( ! empty( $page_ids['register'] ) ) {
		$register_page_content = get_post_field( 'post_content', $page_ids['register'] );
		echo apply_filters( 'the_content', $register_page_content );
	}
}
add_action( 'bp_before_register_page', 'bp_register_page_content' );

/**
 * Return the link to report Member
 *
 * @since BuddyBoss 1.5.6
 *
 * @param array $args Arguments.
 *
 * @return string|false Link for a report a Member.
 */
function bp_member_get_report_link( $args = array() ) {

	// Restricted Report link for admin user.
	$displayed_user_roles = bb_get_member_roles( bp_displayed_user_id() );
	if ( in_array( 'administrator', $displayed_user_roles, true ) ) {
		return false;
	}

	$args = bp_parse_args(
		$args,
		array(
			'id'                => isset( $args['report_user'] ) ? 'member_report' : 'member_block',
			'component'         => 'moderation',
			'position'          => 50,
			'must_be_logged_in' => true,
			'button_attr'       => array(
				'data-bp-content-id'   => bp_displayed_user_id(),
				'data-bp-content-type' => isset( $args['report_user'] ) ? BP_Moderation_Members::$moderation_type_report : BP_Moderation_Members::$moderation_type,
			),
		)
	);

	/**
	 * Filter to update Member report link
	 *
	 * @since BuddyBoss 1.5.6
	 */
	return apply_filters( 'bp_member_get_report_link', bp_moderation_get_report_button( $args, false ), $args );
}

/**
 * Get member roles.
 *
 * @param int $user_id User ID.
 *
 * @return array|false List of user roles or false otherwise.
 */
function bb_get_member_roles( $user_id = 0 ) {

	// Default return value.
	$roles = array();

	// Bail if cannot query the user.
	if ( ! class_exists( 'WP_User' ) || empty( $user_id ) ) {
		return $roles;
	}

	// User ID.
	$user = new WP_User( $user_id );
	if ( isset( $user->roles ) ) {
		$roles = (array) $user->roles;
	}

	// Super admin.
	if ( is_multisite() && is_super_admin( $user_id ) ) {
		$roles[] = 'super_admin';
	}

	return $roles;
}

/**
 * Function to get the hidden profile type.
 *
 * @since BuddyBoss 1.7.9
 *
 * @return array|false
 */
function bp_get_hidden_member_types() {
	$args = array(
		'posts_per_page' => - 1,
		'post_type'      => bp_get_member_type_post_type(),
		'meta_query'     => array(
			array(
				'key'     => '_bp_member_type_enable_search_remove',
				'value'   => 1,
				'compare' => '=',
			),
		),
		'nopaging'       => true,
	);

	$cache_key            = 'bp_get_hidden_member_types_cache';
	$hidden_profile_types = wp_cache_get( $cache_key, 'bp_member_type' );
	if ( false === $hidden_profile_types ) {
		$hidden_profile_types = new WP_Query( $args );
		wp_cache_set( $cache_key, $hidden_profile_types, 'bp_member_type' );
	}

	/**
	 * Filters hidden profile types.
	 *
	 * @since BuddyBoss 1.7.9
	 *
	 * @param array $post_name Hidden profile type names.
	 */
	return apply_filters( 'bp_get_hidden_member_types', isset( $hidden_profile_types->posts ) ? wp_list_pluck( $hidden_profile_types->posts, 'post_name' ) : false );
}

/**
 * Current user online activity time.
 *
 * @since BuddyPress 1.7.0
 *
 * @param int      $user_id User id.
 * @param bool|int $expiry  Given time or whether to check degault timeframe.
 *
 * @return string
 */
function bb_is_online_user( $user_id, $expiry = false ) {

	if ( ! function_exists( 'bp_get_user_last_activity' ) ) {
		return;
	}

	$last_activity = strtotime( bp_get_user_last_activity( $user_id ) );

	if ( empty( $last_activity ) ) {
		return false;
	}

	if ( is_int( $expiry ) && ! empty( $expiry ) ) {
		$timeframe = $expiry;
	} else {
		$timeframe = bb_presence_interval() + bb_presence_time_span();
	}

	$online_time = apply_filters( 'bb_default_online_presence_time', $timeframe );

	return apply_filters( 'bb_is_online_user', ( time() - $last_activity <= $online_time ), $user_id );
}

/**
 * Get profile cover image width.
 *
 * @since BuddyBoss 1.9.1
 *
 * @param string|null $default Optional. Fallback value if not found in the database.
 *                             Default: 'default'.
 *
 * @return string Return profile cover image width.
 */
function bb_get_profile_cover_image_width( $default = 'default' ) {
	return bp_get_option( 'bb-pro-cover-profile-width', $default );
}

/**
 * Get profile cover image height.
 *
 * @since BuddyBoss 1.9.1
 *
 * @param string|null $default Optional. Fallback value if not found in the database.
 *                             Default: 'small'.
 *
 * @return string Return profile cover image height.
 */
function bb_get_profile_cover_image_height( $default = 'small' ) {
	return bp_get_option( 'bb-pro-cover-profile-height', $default );
}

/**
 * Get profile header layout style.
 *
 * @since BuddyBoss 1.9.1
 *
 * @param string|null $default Optional. Fallback value if not found in the database.
 *                             Default: 'left'.
 *
 * @return string Return profile header layout style.
 */
function bb_get_profile_header_layout_style( $default = 'left' ) {
	return function_exists( 'bb_platform_pro_profile_headers_style' ) ? bb_platform_pro_profile_headers_style( $default ) : $default;
}

/**
 * Get profile header layout style.
 *
 * @since BuddyBoss 1.9.1
 *
 * @param string $element Profile header element.
 *                        Default: online-status.
 *
 * @return bool True if profile element is enabled otherwise false.
 */
function bb_enabled_profile_header_layout_element( $element = 'online-status' ) {
	return (bool) function_exists( 'bb_platform_pro_profile_header_element_enable' ) ? bb_platform_pro_profile_header_element_enable( $element ) : true;
}

/**
 * Check the member directory element is enabled or not.
 *
 * @since BuddyBoss 1.9.1
 *
 * @param string $element Member directory element.
 *                        Default: online-status.
 *
 * @return bool True if member directory element is enabled otherwise false.
 */
function bb_enabled_member_directory_element( $element = 'online-status' ) {
	return (bool) function_exists( 'bb_platform_pro_enable_member_directory_element' ) ? bb_platform_pro_enable_member_directory_element( $element ) : true;
}

/**
 * Get enabled the profile actions.
 *
 * @since BuddyBoss 1.9.1
 *
 * @return array Return selected profile actions.
 */
function bb_get_enabled_member_directory_profile_actions() {
	return function_exists( 'bb_platform_pro_get_member_directory_profile_actions' ) ? bb_platform_pro_get_member_directory_profile_actions() : ( function_exists( 'bb_get_member_directory_profile_actions' ) ? array_column( bb_get_member_directory_profile_actions(), 'element_name' ) : array() );
}

/**
 * Check the member profile action is enabled or not.
 *
 * @since BuddyBoss 1.9.1
 *
 * @param string|null $action Member directory profile action.
 *                            Default: null.
 *
 * @return bool True if member profile action is enabled otherwise false.
 */
function bb_enabled_member_directory_profile_action( $action = '' ) {

	if ( empty( $action ) ) {
		return false;
	}

	return (bool) function_exists( 'bb_platform_pro_enable_member_directory_profile_action' ) ? bb_platform_pro_enable_member_directory_profile_action( $action ) : true;
}

/**
 * Get the primary action for member directories.
 *
 * @since BuddyBoss 1.9.1
 *
 * @return string Return the primary action for member directories.
 */
function bb_get_member_directory_primary_action() {
	return function_exists( 'bb_platform_pro_get_member_directory_primary_action' ) ? bb_platform_pro_get_member_directory_primary_action() : bp_get_option( 'bb-member-profile-primary-action' );
}

/**
 * Function which will return the member id if $id > 0 then it will return the original displayed id
 * else it will return the member loop member id.
 *
 * @since BuddyBoss 1.9.1
 *
 * @param int $id Member ID.
 *
 * @return int Member ID.
 */
function bb_member_loop_set_member_id( $id ) {

	if ( $id > 0 ) {

		// This will fix the issues in theme members directory page & members connections tab send message issue.
		if ( is_user_logged_in() && bp_loggedin_user_id() === $id ) {
			if ( 'my-friends' === bp_current_action() && 'friends' === bp_current_component() ) {
				// This will fix the issues in theme members directory page & members connections tab send message issue.
				return bp_get_member_user_id();
			} elseif ( 'requests' === bp_current_action() && 'friends' === bp_current_component() ) {
				// This will fix the issues in theme members directory page & members connections tab send message issue.
				return bp_get_member_user_id();
			} else {
				return $id;
			}
		} else {
			if (
				'friends' === bp_current_component() &&
				( 'my-friends' === bp_current_action() || 'mutual' === bp_current_action() )
			) {
				// This will fix the issues in theme members directory page & members connections tab send message issue.
				return bp_get_member_user_id();
			} else {
				return $id;
			}
		}
	}

	// This will fix the issues in theme members directory page & members connections tab send message issue.
	return bp_get_member_user_id();
}

/**
 * Function which will return the false in even if user is in h/her own profile page in connections members listing.
 *
 * @since BuddyBoss 1.9.1
 *
 * @param bool $my_profile The current page is profile page or not.
 *
 * @return bool
 */
function bb_member_loop_set_my_profile( $my_profile ) {

	if ( 'my-friends' === bp_current_action() && 'friends' === bp_current_component() ) {
		if ( $my_profile && bp_loggedin_user_id() === bp_displayed_user_id() ) {
			return false;
		}
	}
	if ( 'requests' === bp_current_action() && 'friends' === bp_current_component() ) {
		if ( $my_profile && bp_loggedin_user_id() === bp_displayed_user_id() ) {
			return false;
		}
	}
	return $my_profile;
}

/**
 * Get member directories and header page button arguments.
 *
 * @since BuddyBoss 1.9.1
 *
 * @param string $page    The current page is member directories or header page. Default: 'directory'.
 * @param string $clicked The button clicked from primary or secondary button. Default: 'primary'.
 *
 * @return array Return button arguments.
 */
function bb_member_get_profile_action_arguments( $page = 'directory', $clicked = 'primary' ) {
	$button_args = array();
	if ( 'directory' === $page ) {
		$button_args = array(
			'prefix_link_text' => '<i></i>',
			'button_attr'      => array(
				'hover_type' => 'hover',
			),
		);

		if ( 'secondary' === $clicked ) {
			$button_args = array_merge(
				array(
					'is_tooltips'      => true,
					'data-balloon-pos' => 'up',
				),
				$button_args
			);
		}
	} elseif ( 'single' === $page ) {
		if ( 'primary' === $clicked ) {
			$button_args = array(
				'prefix_link_text' => '<i></i>',
				'is_tooltips'      => false,
				'button_attr'      => array(
					'hover_type' => 'hover',
				),
			);
		} elseif ( 'secondary' === $clicked ) {
			$button_args = array(
				'is_tooltips' => false,
				'button_attr' => array(
					'hover_type' => 'static',
				),
			);
		}
	}

	return $button_args;
}

/**
 * Mark Member notification read.
 *
 * @since BuddyBoss 1.9.3
 *
 * @return void
 */
function bb_members_notifications_mark_read() {
	if ( ! is_user_logged_in() ) {
		return;
	}

	// Mark individual notification as read for member following.
	if ( ! empty( $_GET['rid'] ) ) {
		BP_Notifications_Notification::update(
			array(
				'is_new' => false,
			),
			array(
				'user_id'          => bp_loggedin_user_id(),
				'id'               => (int) $_GET['rid'],
				'component_action' => 'bb_following_new',
			)
		);
	}

	if ( ! bp_core_can_edit_settings() || ! bp_current_action() ) {
		return;
	}

	if ( 'general' === bp_current_action() ) {
		$n_id = 0;
		// For replies to a parent update.
		if ( ! empty( $_GET['rid'] ) ) {
			$n_id = (int) $_GET['rid'];
		}

		// Mark individual notification as read.
		if ( ! empty( $n_id ) ) {
			BP_Notifications_Notification::update(
				array(
					'is_new' => false,
				),
				array(
					'user_id' => bp_loggedin_user_id(),
					'id'      => $n_id,
				)
			);
		}
	}
}
add_action( 'template_redirect', 'bb_members_notifications_mark_read' );

/**
 * Determine a user's "mentionname", the name used for that user in @-mentions.
 *
 * @since BuddyBoss 1.9.3
 *
 * @param int|string $user_id ID of the user to get @-mention name for.
 *
 * @return string $mentionname User name appropriate for @-mentions.
 */
function bb_members_get_user_mentionname( $user_id ) {
	$mentionname = '';

	$userdata = bp_core_get_core_userdata( $user_id );

	if ( $userdata ) {
		if ( bp_is_username_compatibility_mode() ) {
			$mentionname = str_replace( ' ', '-', $userdata->user_login );
		} else {
			$mentionname = get_user_meta( $userdata->ID, 'nickname', true );
		}
	}

	return $mentionname;
}

/**
 * Function will return label background and text color's for specific member type.
 *
 * @since BuddyBoss 2.0.0
 *
 * @param $type Type of the member
 *
 * @return array Return array of label color data
 */
function bb_get_member_type_label_colors( $type ) {
	if ( empty( $type ) ) {
		return false;
	}
	$post_id                    = bp_member_type_post_by_type( $type );
	$cache_key                  = 'bb-member-type-label-color-' . $type;
	$bp_member_type_label_color = wp_cache_get( $cache_key, 'bp_member_member_type' );
	if ( false === $bp_member_type_label_color && ! empty( $post_id ) ) {
		$label_colors_meta = get_post_meta( $post_id, '_bp_member_type_label_color', true );
		$label_color_data  = ! empty( $label_colors_meta ) ? maybe_unserialize( $label_colors_meta ) : array();
		$color_type        = isset( $label_color_data['type'] ) ? $label_color_data['type'] : 'default';
		if ( function_exists( 'buddyboss_theme_get_option' ) && 'default' === $color_type ) {
			$background_color = buddyboss_theme_get_option( 'label_background_color' );
			$text_color       = buddyboss_theme_get_option( 'label_text_color' );
		} else {
			$background_color = isset( $label_color_data['background_color'] ) ? $label_color_data['background_color'] : '';
			$text_color       = isset( $label_color_data['text_color'] ) ? $label_color_data['text_color'] : '';
		}
		// Array of label's text and background color data.
		$bp_member_type_label_color = array(
			'color_type'       => $color_type,
			'background-color' => $background_color,
			'color'            => $text_color,
		);
		wp_cache_set( $cache_key, $bp_member_type_label_color, 'bp_member_member_type' );
	}

	return apply_filters( 'bb_get_member_type_label_colors', $bp_member_type_label_color );
}

add_filter( 'gettext', 'bb_profile_drop_down_order_metabox_translate_order_text', 10, 3 );

/**
 * Translate the order text in the Profile Drop Down Order metabox.
 *
 * @since BuddyBoss 2.1.6
 *
 * @param string $translated_text   Translated text.
 * @param string $untranslated_text Untranslated text.
 * @param string $domain            Domain.
 *
 * @return mixed|string|void
 */
function bb_profile_drop_down_order_metabox_translate_order_text( $translated_text, $untranslated_text, $domain ) {

	if ( ! function_exists( 'get_current_screen' ) ) {
		return $translated_text;
	}
	$current_screen = get_current_screen();

	if ( ! is_admin() || empty( $current_screen ) || ! isset( $current_screen->id ) || ! function_exists( 'bp_get_member_type_post_type' ) || bp_get_member_type_post_type() !== $current_screen->id ) {
		return $translated_text;
	}

	if ( 'Order' === $untranslated_text ) {
		return __( 'Number', 'buddyboss' );
	}

	return $translated_text;
}

/**
 * Get the given user ID online/offline status.
 *
 * @since BuddyBoss 2.1.4
 *
 * @param int $user_id User id.
 *
 * @return string
 */
function bb_get_user_presence( $user_id, $expiry = false ) {
	if ( bb_is_online_user( $user_id, $expiry ) ) {
		return 'online';
	} else {
		return 'offline';
	}
}

/**
 * Get online html string.
 *
 * @since BuddyBoss 2.1.4
 *
 * @param int  $user_id User id.
 * @param bool $expiry  Consider expiry time.
 *
 * @return string
 */
function bb_get_user_presence_html( $user_id, $expiry = true ) {
	return sprintf(
		'<span class="member-status %s" data-bb-user-id="%d" data-bb-user-presence="%s"></span>',
		bb_get_user_presence( $user_id, $expiry ),
		$user_id,
		bb_get_user_presence( $user_id, $expiry )
	);
}

/**
 * Get online html string.
 *
 * @since BuddyBoss 2.1.4
 *
 * @param int  $user_id User id.
 * @param bool $expiry  Consider expiry time.
 *
 * @return void
 */
function bb_user_presence_html( $user_id, $expiry = true ) {
	echo bb_get_user_presence_html( $user_id, $expiry ); //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}

/**
 * Generate user profile slug.
 *
 * @since BuddyBoss 2.3.1
 * @since BuddyBoss 2.3.41 The `$force` parameter was added.
 *
 * @param int  $user_id user id.
 * @param bool $force   Optional. If true then will generate new slug forcefully.
 *
 * @return string
 */
function bb_generate_user_profile_slug( int $user_id, bool $force = false ) {
	$unique_identifier = '';

	// If empty user ID.
	if ( empty( $user_id ) ) {
		return $unique_identifier;
	}

	if ( ! $force ) {
		// Get user slug if already exists.
		$user_profile_slug = bb_core_get_user_slug( $user_id );

		// Check the slug and it's not long.
		if (
			! empty( $user_profile_slug ) &&
			bb_is_short_user_unique_identifier( $user_profile_slug )
		) {
			return $user_profile_slug;
		}
	}

	// Get user by ID.
	$user = get_user_by( 'ID', (int) $user_id );

	if ( $user ) {
		$new_unique_identifier = bb_generate_user_random_profile_slugs();
		$unique_identifier     = ! empty( $new_unique_identifier ) ? current( $new_unique_identifier ) : '';
	}

	return $unique_identifier;
}

/**
 * Get the user ID based on the profile hash.
 *
 * @since BuddyBoss 2.3.1
 *
 * @param string $profile_slug profile slug to check.
 *
 * @return int The ID of the matched user on success, null on failure.
 */
function bb_get_user_by_profile_slug( $profile_slug ) {

	// Bail if empty.
	if ( empty( $profile_slug ) ) {
		return false;
	}

	static $cache = array();

	$cache_key = 'bb_profile_slug_' . $profile_slug;

	if ( ! isset( $cache[ $cache_key ] ) ) {
		global $wpdb;
		$bp_prefix = bp_core_get_table_prefix();

		// Backward compatible to check 40 characters long unique slug or new slug as well.
		$user_query = $wpdb->prepare(
			"SELECT user_id FROM `{$wpdb->usermeta}` WHERE `meta_key` IN ( %s, %s )",
			"bb_profile_slug_{$profile_slug}",
			"bb_profile_long_slug_{$profile_slug}"
		);

		// Get the user ID from the created query based on string length.
		$found_users = $wpdb->get_var( $user_query ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

		// Validate the user ID.
		$user = ( ! empty( $found_users ) && ! is_wp_error( $found_users ) ? $found_users : 0 );

		// Set in static cache.
		$cache[ $cache_key ] = $user;
	} else {

		// If already cached then return from the cache.
		$user = isset( $cache[ $cache_key ] ) ? $cache[ $cache_key ] : 0;
	}

	return apply_filters( 'bb_get_user_by_profile_slug', ! empty( $user ) ? $user : 0, $profile_slug );
}

/**
 * Get the profile slug based on the user ID.
 *
 * @since BuddyBoss 2.3.1
 *
 * @param int $user_id User ID to check.
 *
 * @return string
 */
function bb_core_get_user_slug( int $user_id ) {

	if ( empty( $user_id ) ) {
		return '';
	}

	$profile_slug = bp_get_user_meta( $user_id, 'bb_profile_slug', true );

	/**
	 * Filters the profile slug based on originally provided user ID.
	 *
	 * @since BuddyBoss 2.3.1
	 *
	 * @param string $profile_slug User profile slug.
	 * @param int    $user_id User ID.
	 */
	return apply_filters( 'bb_core_get_user_slug', $profile_slug, $user_id );
}

/**
 * Setup the user profile hash to the user meta.
 *
 * @since BuddyBoss 2.3.1
 * @since BuddyBoss 2.3.41 The `$force` parameter was added.
 *
 * @param int  $user_id User ID.
 * @param bool $force   Optional. If true then will generate new slug and update forcefully.
 *
 * @return string
 */
function bb_set_user_profile_slug( int $user_id, bool $force = false ) {

	$unique_identifier = bb_generate_user_profile_slug( $user_id, $force );
	if ( ! empty( $unique_identifier ) ) {

		// Backward compatible to store 40 characters long unique slug.
		$old_unique_identifier = bb_core_get_user_slug( $user_id );
		if ( ! empty( $old_unique_identifier ) ) {

			// Delete the existing meta.
			bp_delete_user_meta( $user_id, 'bb_profile_slug_' . $old_unique_identifier );

			// Backed up 40 characters long unique identifier.
			if ( ! bb_is_short_user_unique_identifier( $old_unique_identifier ) ) {
				bp_update_user_meta( $user_id, 'bb_profile_long_slug_' . $old_unique_identifier, $user_id );
			}
		}

		bp_update_user_meta( $user_id, 'bb_profile_slug', $unique_identifier );
		bp_update_user_meta( $user_id, 'bb_profile_slug_' . $unique_identifier, $user_id );
	}

	return $unique_identifier;
}

/**
 * Setup the user profile hash to the user meta.
 *
 * @since BuddyBoss 2.3.1
 *
 * @param array $user_ids User IDs.
 */
function bb_set_bulk_user_profile_slug( $user_ids ) {

	if ( empty( $user_ids ) ) {
		return;
	}

	foreach ( $user_ids as $user_id ) {
		bb_set_user_profile_slug( (int) $user_id );
	}

	// Flush WP cache.
	wp_cache_flush();

	// Purge all the cache for API.
	if ( class_exists( 'BuddyBoss\Performance\Cache' ) ) {
		BuddyBoss\Performance\Cache::instance()->purge_all();
	}
}

/**
 * Function to generate the unique keys.
 *
 * @since BuddyBoss 2.3.41
 *
 * @param int $max_ids How many unique IDs need to be generated? Default 1.
 *
 * @return array
 */
function bb_generate_user_random_profile_slugs( $max_ids = 1 ) {
	$max_ids       = absint( $max_ids );
	$start         = 0;
	$length        = 8;
	$loop_count    = 1;
	$max_length    = 12;
	$generated_ids = array(); // holds the generated ids.

	/**
	 * Generate the missing ids.
	 */
	$generate_ids_func = function( $generated_ids ) use ( $max_ids, $start, $length ) {
		while ( count( $generated_ids ) < $max_ids ) { // phpcs:ignore Squiz.PHP.DisallowSizeFunctionsInLoops.Found
			$generated_ids[] = strtolower( substr( sha1( wp_generate_password( 40 ) ), $start, $length ) );
		}

		return $generated_ids;
	};

	// Initially generate the UUIDs.
	$generated_ids = $generate_ids_func( $generated_ids );

	// Check the generated UUIDs already exists or not?
	$match_found = bb_is_exists_user_unique_identifier( $generated_ids );

	// Validate the ID's with existing matches in DB.
	while ( ! empty( $match_found ) ) {
		// unset the match which are found.
		foreach ( $match_found as $k ) {
			if ( isset( $generated_ids[ array_search( $k, $generated_ids, true ) ] ) ) {
				unset( $generated_ids[ array_search( $k, $generated_ids, true ) ] );
			}
		}

		// Break the loop if run more than 6 times.
		if ( 6 < $loop_count ) {
			$loop_count = 1;

			if ( $length < $max_length ) {
				$length ++;
			}
		}

		$generated_ids = $generate_ids_func( $generated_ids );
		$match_found   = bb_is_exists_user_unique_identifier( $generated_ids );
		$loop_count ++;
	}

	return array_values( $generated_ids );
}

/**
 * Function to check the newly generated slug is exists or not.
 *
 * @since BuddyBoss 2.3.41
 *
 * @param array|string $unique_identifier Newly generated unique identifier.
 * @param int          $user_id           Optional. ID of user to exclude from the search.
 *
 * @return array
 */
function bb_is_exists_user_unique_identifier( $unique_identifier, $user_id = 0 ) {
	global $wpdb;
	$bp_prefix = bp_core_get_table_prefix();

	if ( is_array( $unique_identifier ) ) {
		$unique_identifier = '"' . implode( '","', $unique_identifier ) . '"';
	}

	// Prepare the statement to check unique identifier.
	$prepare_user_query = "SELECT DISTINCT u.user_nicename, u.user_login FROM `{$wpdb->users}` AS u WHERE ( u.user_login IN ({$unique_identifier}) OR u.user_nicename IN ({$unique_identifier}) )";

	// Exclude the user to check unique identifier.
	if ( ! empty( $user_id ) ) {
		$prepare_user_query = $wpdb->prepare(
			$prepare_user_query . ' AND u.ID != %d', // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			$user_id
		);
	}

	// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	$user_val = $wpdb->get_results( $prepare_user_query );

	$matched_uuids = array();
	if ( ! empty( $user_val ) ) {
		$matched_uuids = array_column( $user_val, 'user_nicename' );
		$matched_uuids = array_merge( $matched_uuids, array_column( $user_val, 'user_login' ) );
	}

	// Prepare the statement to check unique identifier.
	$prepare_meta_query = $wpdb->prepare(
		"SELECT DISTINCT um.meta_value FROM `{$wpdb->usermeta}` AS um WHERE ( um.meta_key = %s AND um.meta_value IN ({$unique_identifier}) ) OR ( um.meta_key = %s AND um.meta_value IN ({$unique_identifier}) )", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		'bb_profile_slug',
		'nickname'
	);

	// Exclude the user to check unique identifier.
	if ( ! empty( $user_id ) ) {
		$prepare_meta_query = $wpdb->prepare(
			$prepare_meta_query . ' AND um.user_id != %d', // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			$user_id
		);
	}

	// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	$meta_val = $wpdb->get_results( $prepare_meta_query );

	if ( ! empty( $meta_val ) ) {
		$matched_uuids = array_merge( $matched_uuids, array_column( $meta_val, 'meta_value' ) );
	}

	return array_filter( array_unique( $matched_uuids ) );
}

/**
 * Function to check the unique identifier slug is short or not.
 *
 * @since BuddyBoss 2.3.41
 *
 * @param string $unique_identifier User unique identifier.
 *
 * @return bool False if unique identifier is 40 characters long otherwise return true.
 */
function bb_is_short_user_unique_identifier( $unique_identifier ) {
	// Get length of provided unique identifier.
	if ( function_exists( 'mb_strlen' ) ) {
		$length = mb_strlen( $unique_identifier );
	} else {
		$length = strlen( $unique_identifier );
	}

	// Check the unique identifier is short then return true.
	if ( $length >= 8 && $length <= 12 ) {
		return true;
	}

	// Return false because unique identifier is 40 characters long.
	return false;
}

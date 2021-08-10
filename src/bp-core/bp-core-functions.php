<?php
/**
 * BuddyPress Common Functions.
 *
 * @package BuddyBoss\Functions
 * @since BuddyPress 1.5.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/** Versions ******************************************************************/

/**
 * Output the BuddyPress version.
 *
 * @since BuddyPress 1.6.0
 */
function bp_version() {
	echo bp_get_version();
}
	/**
	 * Return the BuddyPress version.
	 *
	 * @since BuddyPress 1.6.0
	 *
	 * @return string The BuddyPress version.
	 */
function bp_get_version() {
	return buddypress()->version;
}

/**
 * Output the BuddyPress database version.
 *
 * @since BuddyPress 1.6.0
 */
function bp_db_version() {
	echo bp_get_db_version();
}
	/**
	 * Return the BuddyPress database version.
	 *
	 * @since BuddyPress 1.6.0
	 *
	 * @return string The BuddyPress database version.
	 */
function bp_get_db_version() {
	return buddypress()->db_version;
}

/**
 * Output the BuddyPress database version.
 *
 * @since BuddyPress 1.6.0
 */
function bp_db_version_raw() {
	echo bp_get_db_version_raw();
}
	/**
	 * Return the BuddyPress database version.
	 *
	 * @since BuddyPress 1.6.0
	 *
	 * @return string The BuddyPress version direct from the database.
	 */
function bp_get_db_version_raw() {
	$bp = buddypress();
	return ! empty( $bp->db_version_raw ) ? $bp->db_version_raw : 0;
}

/** Functions *****************************************************************/

/**
 * Get the $wpdb base prefix, run through the 'bp_core_get_table_prefix' filter.
 *
 * The filter is intended primarily for use in multinetwork installations.
 *
 * @since BuddyPress 1.2.6
 *
 * @global wpdb $wpdb WordPress database abstraction object.
 *
 * @return string Filtered database prefix.
 */
function bp_core_get_table_prefix() {
	global $wpdb;

	/**
	 * Filters the $wpdb base prefix.
	 *
	 * Intended primarily for use in multinetwork installations.
	 *
	 * @since BuddyPress 1.2.6
	 *
	 * @param string $base_prefix Base prefix to use.
	 */
	return apply_filters( 'bp_core_get_table_prefix', $wpdb->base_prefix );
}

/**
 * Sort an array of objects or arrays by a specific key/property.
 *
 * The main purpose for this function is so that you can avoid having to create
 * your own awkward callback function for usort().
 *
 * @since BuddyPress 2.2.0
 * @since BuddyPress 2.7.0 Added $preserve_keys parameter.
 *
 * @param array      $items         The items to be sorted. Its constituent items
 *                                  can be either associative arrays or objects.
 * @param string|int $key           The array index or property name to sort by.
 * @param string     $type          Sort type. 'alpha' for alphabetical, 'num'
 *                                  for numeric. Default: 'alpha'.
 * @param bool       $preserve_keys Whether to keep the keys or not.
 *
 * @return array $items The sorted array.
 */
function bp_sort_by_key( $items, $key, $type = 'alpha', $preserve_keys = false ) {
	$callback = function( $a, $b ) use ( $key, $type ) {
		$values = array(
			0 => false,
			1 => false,
		);
		foreach ( func_get_args() as $indexi => $index ) {
			if ( isset( $index->{$key} ) ) {
				$values[ $indexi ] = $index->{$key};
			} elseif ( isset( $index[ $key ] ) ) {
				$values[ $indexi ] = $index[ $key ];
			}
		}

		if ( isset( $values[0], $values[1] ) ) {
			if ( 'num' === $type ) {
				$cmp = $values[0] - $values[1];
			} else {
				$cmp = strcmp( $values[0], $values[1] );
			}

			if ( 0 > $cmp ) {
				$retval = -1;
			} elseif ( 0 < $cmp ) {
				$retval = 1;
			} else {
				$retval = 0;
			}
			return $retval;
		} else {
			return 0;
		}
	};

	if ( true === $preserve_keys ) {
		uasort( $items, $callback );
	} else {
		usort( $items, $callback );
	}

	return $items;
}

/**
 * Sort an array of objects or arrays by alphabetically sorting by a specific key/property.
 *
 * For instance, if you have an array of WordPress post objects, you can sort
 * them by post_name as follows:
 *     $sorted_posts = bp_alpha_sort_by_key( $posts, 'post_name' );
 *
 * @since BuddyPress 1.9.0
 *
 * @param array      $items The items to be sorted. Its constituent items can be either associative arrays or objects.
 * @param string|int $key   The array index or property name to sort by.
 * @return array $items The sorted array.
 */
function bp_alpha_sort_by_key( $items, $key ) {
	return bp_sort_by_key( $items, $key, 'alpha' );
}

/**
 * Format numbers the BuddyPress way.
 *
 * @since BuddyPress 1.2.0
 *
 * @param int  $number   The number to be formatted.
 * @param bool $decimals Whether to use decimals. See {@link number_format_i18n()}.
 * @return string The formatted number.
 */
function bp_core_number_format( $number = 0, $decimals = false ) {

	// Force number to 0 if needed.
	if ( ! is_numeric( $number ) ) {
		$number = 0;
	}

	/**
	 * Filters the BuddyPress formatted number.
	 *
	 * @since BuddyPress 1.2.4
	 *
	 * @param string $value    BuddyPress formatted value.
	 * @param int    $number   The number to be formatted.
	 * @param bool   $decimals Whether or not to use decimals.
	 */
	return apply_filters( 'bp_core_number_format', number_format_i18n( $number, $decimals ), $number, $decimals );
}

/**
 * A utility for parsing individual function arguments into an array.
 *
 * The purpose of this function is to help with backward compatibility in cases where
 *
 *   function foo( $bar = 1, $baz = false, $barry = array(), $blip = false ) { // ...
 *
 * is deprecated in favor of
 *
 *   function foo( $args = array() ) {
 *       $defaults = array(
 *           'bar'  => 1,
 *           'arg2' => false,
 *           'arg3' => array(),
 *           'arg4' => false,
 *       );
 *       $r = wp_parse_args( $args, $defaults ); // ...
 *
 * The first argument, $old_args_keys, is an array that matches the parameter positions (keys) to
 * the new $args keys (values):
 *
 *   $old_args_keys = array(
 *       0 => 'bar', // because $bar was the 0th parameter for foo()
 *       1 => 'baz', // because $baz was the 1st parameter for foo()
 *       2 => 'barry', // etc
 *       3 => 'blip'
 *   );
 *
 * For the second argument, $func_args, you should just pass the value of func_get_args().
 *
 * @since BuddyPress 1.6.0
 *
 * @param array $old_args_keys Old argument indexs, keyed to their positions.
 * @param array $func_args     The parameters passed to the originating function.
 * @return array $new_args The parsed arguments.
 */
function bp_core_parse_args_array( $old_args_keys, $func_args ) {
	$new_args = array();

	foreach ( $old_args_keys as $arg_num => $arg_key ) {
		if ( isset( $func_args[ $arg_num ] ) ) {
			$new_args[ $arg_key ] = $func_args[ $arg_num ];
		}
	}

	return $new_args;
}

/**
 * Merge user defined arguments into defaults array.
 *
 * This function is used throughout BuddyPress to allow for either a string or
 * array to be merged into another array. It is identical to wp_parse_args()
 * except it allows for arguments to be passively or aggressively filtered using
 * the optional $filter_key parameter. If no $filter_key is passed, no filters
 * are applied.
 *
 * @since BuddyPress 2.0.0
 *
 * @param string|array $args       Value to merge with $defaults.
 * @param array        $defaults   Array that serves as the defaults.
 * @param string       $filter_key String to key the filters from.
 * @return array Merged user defined values with defaults.
 */
function bp_parse_args( $args, $defaults = array(), $filter_key = '' ) {

	// Setup a temporary array from $args.
	if ( is_object( $args ) ) {
		$r = get_object_vars( $args );
	} elseif ( is_array( $args ) ) {
		$r =& $args;
	} else {
		wp_parse_str( $args, $r );
	}

	// Passively filter the args before the parse.
	if ( ! empty( $filter_key ) ) {

		/**
		 * Filters the arguments key before parsing if filter key provided.
		 *
		 * This is a dynamic filter dependent on the specified key.
		 *
		 * @since BuddyPress 2.0.0
		 *
		 * @param array $r Array of arguments to use.
		 */
		$r = apply_filters( 'bp_before_' . $filter_key . '_parse_args', $r );
	}

	// Parse.
	if ( is_array( $defaults ) && ! empty( $defaults ) ) {
		$r = array_merge( $defaults, $r );
	}

	// Aggressively filter the args after the parse.
	if ( ! empty( $filter_key ) ) {

		/**
		 * Filters the arguments key after parsing if filter key provided.
		 *
		 * This is a dynamic filter dependent on the specified key.
		 *
		 * @since BuddyPress 2.0.0
		 *
		 * @param array $r Array of parsed arguments.
		 */
		$r = apply_filters( 'bp_after_' . $filter_key . '_parse_args', $r );
	}

	// Return the parsed results.
	return $r;
}

/**
 * Sanitizes a pagination argument based on both the request override and the
 * original value submitted via a query argument, likely to a template class
 * responsible for limiting the resultset of a template loop.
 *
 * @since BuddyPress 2.2.0
 *
 * @param string $page_arg The $_REQUEST argument to look for.
 * @param int    $page     The original page value to fall back to.
 * @return int A sanitized integer value, good for pagination.
 */
function bp_sanitize_pagination_arg( $page_arg = '', $page = 1 ) {

	// Check if request overrides exist.
	if ( isset( $_REQUEST[ $page_arg ] ) ) {

		// Get the absolute integer value of the override.
		$int = absint( $_REQUEST[ $page_arg ] );

		// If override is 0, do not use it. This prevents unlimited result sets.
		// @see https://buddypress.trac.wordpress.org/ticket/5796.
		if ( $int ) {
			$page = $int;
		}
	}

	return intval( $page );
}

/**
 * Sanitize an 'order' parameter for use in building SQL queries.
 *
 * Strings like 'DESC', 'desc', ' desc' will be interpreted into 'DESC'.
 * Everything else becomes 'ASC'.
 *
 * @since BuddyPress 1.8.0
 *
 * @param string $order The 'order' string, as passed to the SQL constructor.
 * @return string The sanitized value 'DESC' or 'ASC'.
 */
function bp_esc_sql_order( $order = '' ) {
	$order = strtoupper( trim( $order ) );
	return 'DESC' === $order ? 'DESC' : 'ASC';
}

/**
 * Escape special characters in a SQL LIKE clause.
 *
 * In WordPress 4.0, like_escape() was deprecated, due to incorrect
 * documentation and improper sanitization leading to a history of misuse. To
 * maintain compatibility with versions of WP before 4.0, we duplicate the
 * logic of the replacement, wpdb::esc_like().
 *
 * @since BuddyPress 2.1.0
 *
 * @see wpdb::esc_like() for more details on proper use.
 *
 * @param string $text The raw text to be escaped.
 * @return string Text in the form of a LIKE phrase. Not SQL safe. Run through
 *                wpdb::prepare() before use.
 */
function bp_esc_like( $text ) {
	global $wpdb;

	if ( method_exists( $wpdb, 'esc_like' ) ) {
		return $wpdb->esc_like( $text );
	} else {
		return addcslashes( $text, '_%\\' );
	}
}

/**
 * Are we running username compatibility mode?
 *
 * @since BuddyPress 1.5.0
 *
 * @todo Move to members component?
 *
 * @return bool False when compatibility mode is disabled, true when enabled.
 *              Default: false.
 */
function bp_is_username_compatibility_mode() {

	/**
	 * Filters whether or not to use username compatibility mode.
	 *
	 * @since BuddyPress 1.5.0
	 *
	 * @param bool $value Whether or not username compatibility mode should be used.
	 */
	return apply_filters( 'bp_is_username_compatibility_mode', defined( 'BP_ENABLE_USERNAME_COMPATIBILITY_MODE' ) && BP_ENABLE_USERNAME_COMPATIBILITY_MODE );
}

/**
 * Should we use the WP Toolbar?
 *
 * The WP Toolbar, introduced in WP 3.1, is fully supported in BuddyPress as
 * of BP 1.5. For BP 1.6, the WP Toolbar is the default.
 *
 * @since BuddyPress 1.5.0
 *
 * @return bool Default: true. False when WP Toolbar support is disabled.
 */
function bp_use_wp_admin_bar() {

	// Default to true (to avoid loading deprecated BuddyBar code).
	$use_admin_bar = true;

	// Has the WP Toolbar constant been explicitly opted into?
	if ( defined( 'BP_USE_WP_ADMIN_BAR' ) ) {
		$use_admin_bar = (bool) BP_USE_WP_ADMIN_BAR;

		// ...or is the old BuddyBar being forced back into use?
	} elseif ( bp_force_buddybar( false ) ) {
		$use_admin_bar = false;
	}

	/**
	 * Filters whether or not to use the admin bar.
	 *
	 * @since BuddyPress 1.5.0
	 *
	 * @param bool $use_admin_bar Whether or not to use the admin bar.
	 */
	return (bool) apply_filters( 'bp_use_wp_admin_bar', $use_admin_bar );
}


/**
 * Return the parent forum ID for the Legacy Forums abstraction layer.
 *
 * @since BuddyPress 1.5.0
 * @since BuddyPress 3.0.0 Supported for compatibility with bbPress 2.
 *
 * @return int Forum ID.
 */
function bp_forums_parent_forum_id() {

	/**
	 * Filters the parent forum ID for the bbPress abstraction layer.
	 *
	 * @since BuddyPress 1.5.0
	 *
	 * @param int BP_FORUMS_PARENT_FORUM_ID The Parent forum ID constant.
	 */
	return apply_filters( 'bp_forums_parent_forum_id', BP_FORUMS_PARENT_FORUM_ID );
}

/** Directory *****************************************************************/

/**
 * Returns an array of core component IDs.
 *
 * @since BuddyPress 2.1.0
 *
 * @return array
 */
function bp_core_get_packaged_component_ids() {
	$components = array(
		'activity',
		'members',
		'groups',
		'blogs',
		'xprofile',
		'friends',
		'media',
		'document',
		'video',
		'messages',
		'settings',
		'notifications',
		'search',
		'moderation',
	);

	return $components;
}

/**
 * Fetch a list of BP directory pages from the appropriate meta table.
 *
 * @since BuddyPress 1.5.0
 *
 * @param string $status 'active' to return only pages associated with active components, 'all' to return all saved
 *                       pages. When running save routines, use 'all' to avoid removing data related to inactive
 *                       components. Default: 'active'.
 * @return array|string An array of page IDs, keyed by component names, or an
 *                      empty string if the list is not found.
 */
function bp_core_get_directory_page_ids( $status = 'active' ) {
	$page_ids = bp_get_option( 'bp-pages', array() );

	// Loop through pages.
	foreach ( $page_ids as $component_name => $page_id ) {

		// Ensure that empty indexes are unset. Should only matter in edge cases.
		if ( empty( $component_name ) || empty( $page_id ) ) {
			unset( $page_ids[ $component_name ] );
		}

		// Trashed pages should never appear in results.
		if ( 'trash' === get_post_status( $page_id ) ) {
			unset( $page_ids[ $component_name ] );
		}

		// 'register', 'activate', 'terms' and 'privacy' do not have components, but should be whitelisted.
		if ( in_array( $component_name, array( 'register', 'activate', 'terms', 'privacy', 'profile_dashboard', 'new_forums_page' ), true ) ) {
			continue;
		}

		// Remove inactive component pages.
		if ( ( 'active' === $status ) && ! bp_is_active( $component_name ) ) {
			unset( $page_ids[ $component_name ] );
		}
	}

	/**
	 * Filters the list of BP directory pages from the appropriate meta table.
	 *
	 * @since BuddyPress 1.5.0
	 * @since BuddyPress 2.9.0 Add $status parameter
	 *
	 * @param array  $page_ids Array of directory pages.
	 * @param string $status   Page status to limit results to
	 */
	return (array) apply_filters( 'bp_core_get_directory_page_ids', $page_ids, $status );
}

/**
 * Get the page ID corresponding to a component directory.
 *
 * @since BuddyPress 2.6.0
 *
 * @param string|null $component The slug representing the component. Defaults to the current component.
 * @return int|false The ID of the directory page associated with the component. False if none is found.
 */
function bp_core_get_directory_page_id( $component = null ) {
	if ( ! $component ) {
		$component = bp_current_component();
	}

	$bp_pages = bp_core_get_directory_page_ids( 'all' );

	$page_id = false;
	if ( $component && isset( $bp_pages[ $component ] ) ) {
		$page_id = (int) $bp_pages[ $component ];
	}

	return $page_id;
}

/**
 * Store the list of BP directory pages in the appropriate meta table.
 *
 * The bp-pages data is stored in site_options (falls back to options on non-MS),
 * in an array keyed by blog_id. This allows you to change your
 * bp_get_root_blog_id() and go through the setup process again.
 *
 * @since BuddyPress 1.5.0
 *
 * @param array $blog_page_ids The IDs of the WP pages corresponding to BP
 *                             component directories.
 */
function bp_core_update_directory_page_ids( $blog_page_ids ) {
	bp_update_option( 'bp-pages', $blog_page_ids );
}

/**
 * Get names and slugs for BuddyPress component directory pages.
 *
 * @since BuddyPress 1.5.0
 *
 * @return object Page names, IDs, and slugs.
 */
function bp_core_get_directory_pages() {
	global $wpdb;

	// Look in cache first.
	$pages = wp_cache_get( 'directory_pages', 'bp_pages' );

	if ( false === $pages ) {

		// Set pages as standard class.
		$pages = new stdClass();

		// Get pages and IDs.
		$page_ids = bp_core_get_directory_page_ids();
		if ( ! empty( $page_ids ) ) {

			// Always get page data from the root blog, except on multiblog mode, when it comes
			// from the current blog.
			$posts_table_name = bp_is_multiblog_mode() ? $wpdb->posts : $wpdb->get_blog_prefix( bp_get_root_blog_id() ) . 'posts';
			$page_ids_sql     = implode( ',', wp_parse_id_list( $page_ids ) );
			$page_names       = $wpdb->get_results( "SELECT ID, post_name, post_parent, post_title FROM {$posts_table_name} WHERE ID IN ({$page_ids_sql}) AND post_status = 'publish' " );

			foreach ( (array) $page_ids as $component_id => $page_id ) {
				foreach ( (array) $page_names as $page_name ) {
					if ( $page_name->ID == $page_id ) {
						if ( ! isset( $pages->{$component_id} ) || ! is_object( $pages->{$component_id} ) ) {
							$pages->{$component_id} = new stdClass();
						}

						$pages->{$component_id}->name  = $page_name->post_name;
						$pages->{$component_id}->id    = $page_name->ID;
						$pages->{$component_id}->title = $page_name->post_title;
						$slug[]                        = $page_name->post_name;

						// Get the slug.
						while ( $page_name->post_parent != 0 ) {
							$parent                 = $wpdb->get_results( $wpdb->prepare( "SELECT post_name, post_parent FROM {$posts_table_name} WHERE ID = %d", $page_name->post_parent ) );
							$slug[]                 = $parent[0]->post_name;
							$page_name->post_parent = $parent[0]->post_parent;
						}

						$pages->{$component_id}->slug = implode( '/', array_reverse( (array) $slug ) );
					}

					unset( $slug );
				}
			}
		}

		wp_cache_set( 'directory_pages', $pages, 'bp_pages' );
	}

	/**
	 * Filters the names and slugs for BuddyPress component directory pages.
	 *
	 * @since BuddyPress 1.5.0
	 *
	 * @param object $pages Object holding page names and slugs.
	 */
	return apply_filters( 'bp_core_get_directory_pages', $pages );
}

/**
 * Creates necessary directory pages.
 *
 * Directory pages are those WordPress pages used by BP components to display
 * content (eg, the 'groups' page created by BP).
 *
 * @since BuddyPress 1.7.0
 *
 * @param array  $components Components to create pages for.
 * @param string $existing   'delete' if you want to delete existing page mappings
 *                           and replace with new ones. Otherwise existing page mappings
 *                           are kept, and the gaps filled in with new pages. Default: 'keep'.
 */
function bp_core_add_page_mappings( $components, $existing = 'keep' ) {

	// If no value is passed, there's nothing to do.
	if ( empty( $components ) ) {
		return;
	}

	// Make sure that the pages are created on the root blog no matter which
	// dashboard the setup is being run on.
	if ( ! bp_is_root_blog() ) {
		switch_to_blog( bp_get_root_blog_id() );
	}

	$pages = bp_core_get_directory_page_ids( 'all' );

	// Delete any existing pages.
	if ( 'delete' === $existing ) {
		foreach ( $pages as $page_id ) {
			wp_delete_post( $page_id, true );
		}

		$pages = array();
	}

	$page_titles = bp_core_get_directory_page_default_titles();

	$pages_to_create = array();
	foreach ( array_keys( $components ) as $component_name ) {
		if ( ! isset( $pages[ $component_name ] ) && isset( $page_titles[ $component_name ] ) ) {
			$pages_to_create[ $component_name ] = $page_titles[ $component_name ];
		}
	}

	// Register and Activate are not components, but need pages when
	// registration is enabled.
	if ( bp_get_signup_allowed() ) {
		foreach ( array( 'register', 'activate' ) as $slug ) {
			if ( ! isset( $pages[ $slug ] ) ) {
				$pages_to_create[ $slug ] = $page_titles[ $slug ];
			}
		}
	}

	// check for privacy page if already exists in WP settings > privacy.
	$policy_page_id = (int) get_option( 'wp_page_for_privacy_policy' );
	$static_pages   = array( 'terms' );

	if ( empty( $policy_page_id ) ) {
		$static_pages[] = 'privacy';
	} else {
		$pages_to_create['privacy'] = $page_titles['privacy'];
	}

	// Create terms and privacy pages.
	foreach ( $static_pages as $slug ) {
		if ( ! isset( $pages[ $slug ] ) ) {
			$pages_to_create[ $slug ] = $page_titles[ $slug ];
		}
	}

	// No need for a Sites directory unless we're on multisite.
	if ( ! is_multisite() && isset( $pages_to_create['blogs'] ) ) {
		unset( $pages_to_create['blogs'] );
	}

	// Members must always have a page, no matter what.
	if ( ! isset( $pages['members'] ) && ! isset( $pages_to_create['members'] ) ) {
		$pages_to_create['members'] = $page_titles['members'];
	}

	// Create the pages.
	foreach ( $pages_to_create as $component_name => $page_name ) {
		$exists     = get_page_by_path( $component_name );
		$page_exist = post_exists( $page_name, '', '', 'page' );

		// If page already exists, use it.
		if ( ! empty( $exists ) ) {
			$pages[ $component_name ] = $exists->ID;
		} elseif ( ! empty( $page_exist ) ) {
			$pages[ $component_name ] = $page_exist;
		} else {
			$pages[ $component_name ] = wp_insert_post(
				array(
					'comment_status' => 'closed',
					'ping_status'    => 'closed',
					'post_status'    => 'publish',
					'post_title'     => $page_name,
					'post_type'      => 'page',
				)
			);
		}
	}

	// Save the page mapping.
	bp_update_option( 'bp-pages', $pages );

	// If we had to switch_to_blog, go back to the original site.
	if ( ! bp_is_root_blog() ) {
		restore_current_blog();
	}
}

/**
 * Get the default page titles for BP directory pages.
 *
 * @since BuddyPress 2.7.0
 *
 * @return array
 * @todo pretty sure these need to be deprecated
 */
function bp_core_get_directory_page_default_titles() {
	$page_default_titles = array(
		'activity'        => __( 'News Feed', 'buddyboss' ),
		'groups'          => __( 'Groups', 'buddyboss' ),
		'blogs'           => __( 'Sites', 'buddyboss' ),
		'members'         => __( 'Members', 'buddyboss' ),
		'media'           => __( 'Photos', 'buddyboss' ),
		'document'        => __( 'Documents', 'buddyboss' ),
		'video'           => __( 'Videos', 'buddyboss' ),
		'activate'        => __( 'Activate', 'buddyboss' ),
		'register'        => __( 'Register', 'buddyboss' ),
		// 'profile_dashboard' => __( 'Dashboard', 'buddyboss' ),
		'new_forums_page' => __( 'Forums', 'buddyboss' ),
		'terms'           => __( 'Terms of Service', 'buddyboss' ),
		'privacy'         => __( 'Privacy Policy', 'buddyboss' ),
		'moderation'      => __( 'Moderation', 'buddyboss' ),
	);

	/**
	 * Filters the default page titles array
	 *
	 * @since BuddyPress 2.7.0
	 *
	 * @param array $page_default_titles the array of default WP (post_title) titles.
	 */
	return apply_filters( 'bp_core_get_directory_page_default_titles', $page_default_titles );
}

/**
 * Remove the entry from bp_pages when the corresponding WP page is deleted.
 *
 * Bails early on multisite installations when not viewing the root site.
 *
 * @link https://buddypress.trac.wordpress.org/ticket/6226
 *
 * @since BuddyPress 2.2.0
 *
 * @param int $post_id Post ID.
 */
function bp_core_on_directory_page_delete( $post_id ) {

	// Stop if we are not on the main BP root blog.
	if ( ! bp_is_root_blog() ) {
		return;
	}

	$page_ids       = bp_core_get_directory_page_ids( 'all' );
	$component_name = array_search( $post_id, $page_ids );

	if ( ! empty( $component_name ) ) {
		unset( $page_ids[ $component_name ] );
	}

	bp_core_update_directory_page_ids( $page_ids );
}
add_action( 'delete_post', 'bp_core_on_directory_page_delete' );

/**
 * Create a default component slug from a WP page root_slug.
 *
 * Since 1.5, BP components get their root_slug (the slug used immediately
 * following the root domain) from the slug of a corresponding WP page.
 *
 * E.g. if your BP installation at example.com has its members page at
 * example.com/community/people, $bp->members->root_slug will be
 * 'community/people'.
 *
 * By default, this function creates a shorter version of the root_slug for
 * use elsewhere in the URL, by returning the content after the final '/'
 * in the root_slug ('people' in the example above).
 *
 * Filter on 'bp_core_component_slug_from_root_slug' to override this method
 * in general, or define a specific component slug constant (e.g.
 * BP_MEMBERS_SLUG) to override specific component slugs.
 *
 * @since BuddyPress 1.5.0
 *
 * @param string $root_slug The root slug, which comes from $bp->pages->[component]->slug.
 * @return string The short slug for use in the middle of URLs.
 */
function bp_core_component_slug_from_root_slug( $root_slug ) {
	$slug_chunks = explode( '/', $root_slug );
	$slug        = array_pop( $slug_chunks );

	/**
	 * Filters the default component slug from a WP page root_slug.
	 *
	 * @since BuddyPress 1.5.0
	 *
	 * @param string $slug      Short slug for use in the middle of URLs.
	 * @param string $root_slug The root slug which comes from $bp->pages-[component]->slug.
	 */
	return apply_filters( 'bp_core_component_slug_from_root_slug', $slug, $root_slug );
}

/**
 * Add support for a top-level ("root") component.
 *
 * This function originally (pre-1.5) let plugins add support for pages in the
 * root of the install. These root level pages are now handled by actual
 * WordPress pages and this function is now a convenience for compatibility
 * with the new method.
 *
 * @since BuddyPress 1.0.0
 *
 * @param string $slug The slug of the component being added to the root list.
 */
function bp_core_add_root_component( $slug ) {
	$bp = buddypress();

	if ( empty( $bp->pages ) ) {
		$bp->pages = bp_core_get_directory_pages();
	}

	$match = false;

	// Check if the slug is registered in the $bp->pages global.
	foreach ( (array) $bp->pages as $key => $page ) {
		if ( $key == $slug || $page->slug == $slug ) {
			$match = true;
		}
	}

	// Maybe create the add_root array.
	if ( empty( $bp->add_root ) ) {
		$bp->add_root = array();
	}

	// If there was no match, add a page for this root component.
	if ( empty( $match ) ) {
		$add_root_items   = $bp->add_root;
		$add_root_items[] = $slug;
		$bp->add_root     = $add_root_items;
	}

	// Make sure that this component is registered as requiring a top-level directory.
	if ( isset( $bp->{$slug} ) ) {
		$bp->loaded_components[ $bp->{$slug}->slug ] = $bp->{$slug}->id;
		$bp->{$slug}->has_directory                  = true;
	}
}

/**
 * Create WordPress pages to be used as BP component directories.
 *
 * @since BuddyPress 1.5.0
 */
function bp_core_create_root_component_page() {

	// Get BuddyPress.
	$bp = buddypress();

	$new_page_ids = array();

	foreach ( (array) $bp->add_root as $slug ) {
		$new_page_ids[ $slug ] = wp_insert_post(
			array(
				'comment_status' => 'closed',
				'ping_status'    => 'closed',
				'post_title'     => ucwords( $slug ),
				'post_status'    => 'publish',
				'post_type'      => 'page',
			)
		);
	}

	$page_ids = array_merge( $new_page_ids, bp_core_get_directory_page_ids( 'all' ) );
	bp_core_update_directory_page_ids( $page_ids );
}

/**
 * Add illegal blog names to WP so that root components will not conflict with blog names on a subdirectory installation.
 *
 * For example, it would stop someone creating a blog with the slug "groups".
 *
 * @since BuddyPress 1.0.0
 *
 * @todo Deprecate?
 */
function bp_core_add_illegal_names() {
	update_site_option( 'illegal_names', get_site_option( 'illegal_names' ), array() );
}

/**
 * Get the 'search' query argument for a given component.
 *
 * @since BuddyPress 2.4.0
 * @since BuddyPress 2.7.0 The `$component` parameter was made optional, with the current component
 *              as the fallback value.
 *
 * @param string|null $component Optional. Component name. Defaults to current component.
 * @return string|bool Query argument on success. False on failure.
 */
function bp_core_get_component_search_query_arg( $component = null ) {
	if ( ! $component ) {
		$component = bp_current_component();
	}

	$query_arg = false;
	if ( isset( buddypress()->{$component}->search_query_arg ) ) {
		$query_arg = sanitize_title( buddypress()->{$component}->search_query_arg );
	}

	/**
	 * Filters the query arg for a component search string.
	 *
	 * @since BuddyPress 2.4.0
	 *
	 * @param string $query_arg Query argument.
	 * @param string $component Component name.
	 */
	return apply_filters( 'bp_core_get_component_search_query_arg', $query_arg, $component );
}

/** URI ***********************************************************************/

/**
 * Return the domain for the root blog.
 *
 * Eg: http://example.com OR https://example.com
 *
 * @since BuddyPress 1.0.0
 *
 * @return string The domain URL for the blog.
 */
function bp_core_get_root_domain() {

	$domain = get_home_url( bp_get_root_blog_id() );

	/**
	 * Filters the domain for the root blog.
	 *
	 * @since BuddyPress 1.0.1
	 *
	 * @param string $domain The domain URL for the blog.
	 */
	return apply_filters( 'bp_core_get_root_domain', $domain );
}

/**
 * Perform a status-safe wp_redirect() that is compatible with BP's URI parser.
 *
 * @since BuddyPress 1.0.0
 *
 * @param string $location The redirect URL.
 * @param int    $status   Optional. The numeric code to give in the redirect
 *                         headers. Default: 302.
 */
function bp_core_redirect( $location = '', $status = 302 ) {

	// On some setups, passing the value of wp_get_referer() may result in an
	// empty value for $location, which results in an error. Ensure that we
	// have a valid URL.
	if ( empty( $location ) ) {
		$location = bp_get_root_domain();
	}

	// Make sure we don't call status_header() in bp_core_do_catch_uri() as this
	// conflicts with wp_redirect() and wp_safe_redirect().
	buddypress()->no_status_set = true;

	wp_safe_redirect( $location, $status );

	// If PHPUnit is running, do not kill execution.
	if ( ! defined( 'BP_TESTS_DIR' ) ) {
		die;
	}
}

/**
 * Return the URL path of the referring page.
 *
 * This is a wrapper for `wp_get_referer()` that sanitizes the referer URL to
 * a webroot-relative path. For example, 'http://example.com/foo/' will be
 * reduced to '/foo/'.
 *
 * @since BuddyPress 2.3.0
 *
 * @return bool|string Returns false on error, a URL path on success.
 */
function bp_get_referer_path() {
	$referer = wp_get_referer();

	if ( false === $referer ) {
		return false;
	}

	// Turn into an absolute path.
	$referer = preg_replace( '|https?\://[^/]+/|', '/', $referer );

	return $referer;
}

/**
 * Get the path of the current site.
 *
 * @since BuddyPress 1.0.0
 *
 * @global object $current_site
 *
 * @return string URL to the current site.
 */
function bp_core_get_site_path() {
	global $current_site;

	if ( is_multisite() ) {
		$site_path = $current_site->path;
	} else {
		$site_path = (array) explode( '/', home_url() );

		if ( count( $site_path ) < 2 ) {
			$site_path = '/';
		} else {
			// Unset the first three segments (http(s)://example.com part).
			unset( $site_path[0] );
			unset( $site_path[1] );
			unset( $site_path[2] );

			if ( ! count( $site_path ) ) {
				$site_path = '/';
			} else {
				$site_path = '/' . implode( '/', $site_path ) . '/';
			}
		}
	}

	/**
	 * Filters the path of the current site.
	 *
	 * @since BuddyPress 1.2.0
	 *
	 * @param string $site_path URL to the current site.
	 */
	return apply_filters( 'bp_core_get_site_path', $site_path );
}

/** Time **********************************************************************/

/**
 * Get the current GMT time to save into the DB.
 *
 * @since BuddyPress 1.2.6
 *
 * @param bool   $gmt  True to use GMT (rather than local) time. Default: true.
 * @param string $type See the 'type' parameter in {@link current_time()}.
 *                     Default: 'mysql'.
 * @return string Current time in 'Y-m-d h:i:s' format.
 */
function bp_core_current_time( $gmt = true, $type = 'mysql' ) {

	/**
	 * Filters the current GMT time to save into the DB.
	 *
	 * @since BuddyPress 1.2.6
	 *
	 * @param string $value Current GMT time.
	 */
	return apply_filters( 'bp_core_current_time', current_time( $type, $gmt ) );
}

/**
 * Get an English-language representation of the time elapsed since a given date.
 *
 * Based on function created by Dunstan Orchard - http://1976design.com
 *
 * This function will return an English representation of the time elapsed
 * since a given date.
 * eg: 2 hours and 50 minutes
 * eg: 4 days
 * eg: 4 weeks and 6 days
 *
 * Note that fractions of minutes are not represented in the return string. So
 * an interval of 3 minutes will be represented by "3 minutes ago", as will an
 * interval of 3 minutes 59 seconds.
 *
 * @since BuddyPress 1.0.0
 *
 * @param int|string $older_date The earlier time from which you're calculating
 *                               the time elapsed. Enter either as an integer Unix timestamp,
 *                               or as a date string of the format 'Y-m-d h:i:s'.
 * @param int|bool   $newer_date Optional. Unix timestamp of date to compare older
 *                               date to. Default: false (current time).
 * @return string String representing the time since the older date, eg
 *         "2 hours and 50 minutes".
 */
function bp_core_time_since( $older_date, $newer_date = false ) {

	/**
	 * Filters whether or not to bypass BuddyPress' time_since calculations.
	 *
	 * @since BuddyPress 1.7.0
	 *
	 * @param bool   $value      Whether or not to bypass.
	 * @param string $older_date Earlier time from which we're calculating time elapsed.
	 * @param string $newer_date Unix timestamp of date to compare older time to.
	 */
	$pre_value = apply_filters( 'bp_core_time_since_pre', false, $older_date, $newer_date );
	if ( false !== $pre_value ) {
		return $pre_value;
	}

	/**
	 * Filters the value to use if the time since is unknown.
	 *
	 * @since BuddyPress 1.5.0
	 *
	 * @param string $value String representing the time since the older date.
	 */
	$unknown_text = apply_filters( 'bp_core_time_since_unknown_text', __( 'sometime', 'buddyboss' ) );

	/**
	 * Filters the value to use if the time since is right now.
	 *
	 * @since BuddyPress 1.5.0
	 *
	 * @param string $value String representing the time since the older date.
	 */
	$right_now_text = apply_filters( 'bp_core_time_since_right_now_text', __( 'right now', 'buddyboss' ) );

	/**
	 * Filters the value to use if the time since is some time ago.
	 *
	 * @since BuddyPress 1.5.0
	 *
	 * @param string $value String representing the time since the older date.
	 */
	$ago_text = apply_filters( 'bp_core_time_since_ago_text', __( '%s ago', 'buddyboss' ) );

	// Array of time period chunks.
	$chunks = array(
		YEAR_IN_SECONDS,
		30 * DAY_IN_SECONDS,
		WEEK_IN_SECONDS,
		DAY_IN_SECONDS,
		HOUR_IN_SECONDS,
		MINUTE_IN_SECONDS,
		1,
	);

	if ( ! empty( $older_date ) && ! is_numeric( $older_date ) ) {
		$time_chunks = explode( ':', str_replace( ' ', ':', $older_date ) );
		$date_chunks = explode( '-', str_replace( ' ', '-', $older_date ) );
		$older_date  = gmmktime( (int) $time_chunks[1], (int) $time_chunks[2], (int) $time_chunks[3], (int) $date_chunks[1], (int) $date_chunks[2], (int) $date_chunks[0] );
	}

	/**
	 * $newer_date will equal false if we want to know the time elapsed between
	 * a date and the current time. $newer_date will have a value if we want to
	 * work out time elapsed between two known dates.
	 */
	$newer_date = ( ! $newer_date ) ? bp_core_current_time( true, 'timestamp' ) : $newer_date;

	// Difference in seconds.
	$since = $newer_date - $older_date;

	// Something went wrong with date calculation and we ended up with a negative date.
	if ( 0 > $since ) {
		$output = $unknown_text;

		/**
		 * We only want to output two chunks of time here, eg:
		 * x years, xx months
		 * x days, xx hours
		 * so there's only two bits of calculation below:
		 */
	} else {

		/**
		 * Initializing the count variable to avoid undefined notice
		 */
		$count  = 0;
		$count2 = 0;

		// Step one: the first chunk.
		for ( $i = 0, $j = count( $chunks ); $i < $j; ++$i ) {
			$seconds = $chunks[ $i ];

			// Finding the biggest chunk (if the chunk fits, break).
			$count = floor( $since / $seconds );
			if ( 0 != $count ) {
				break;
			}
		}

		// If $i iterates all the way to $j, then the event happened 0 seconds ago.
		if ( ! isset( $chunks[ $i ] ) ) {
			$output = $right_now_text;

		} else {

			// Set output var.
			switch ( $seconds ) {
				case YEAR_IN_SECONDS:
					$output = sprintf( _n( '%s year', '%s years', $count, 'buddyboss' ), $count );
					break;
				case 30 * DAY_IN_SECONDS:
					$output = sprintf( _n( '%s month', '%s months', $count, 'buddyboss' ), $count );
					break;
				case WEEK_IN_SECONDS:
					$output = sprintf( _n( '%s week', '%s weeks', $count, 'buddyboss' ), $count );
					break;
				case DAY_IN_SECONDS:
					$output = sprintf( _n( '%s day', '%s days', $count, 'buddyboss' ), $count );
					break;
				case HOUR_IN_SECONDS:
					$output = sprintf( _n( '%s hour', '%s hours', $count, 'buddyboss' ), $count );
					break;
				case MINUTE_IN_SECONDS:
					$output = sprintf( _n( '%s minute', '%s minutes', $count, 'buddyboss' ), $count );
					break;
				default:
					$output = sprintf( _n( '%s second', '%s seconds', $count, 'buddyboss' ), $count );
			}

			// Step two: the second chunk
			// A quirk in the implementation means that this
			// condition fails in the case of minutes and seconds.
			// We've left the quirk in place, since fractions of a
			// minute are not a useful piece of information for our
			// purposes.
			if ( $i + 2 < $j ) {
				$seconds2 = $chunks[ $i + 1 ];
				$count2   = floor( ( $since - ( $seconds * $count ) ) / $seconds2 );

				// Add to output var.
				if ( 0 != $count2 ) {
					$output .= _x( ',', 'Separator in time since', 'buddyboss' ) . ' ';

					switch ( $seconds2 ) {
						case 30 * DAY_IN_SECONDS:
							$output .= sprintf( _n( '%s month', '%s months', $count2, 'buddyboss' ), $count2 );
							break;
						case WEEK_IN_SECONDS:
							$output .= sprintf( _n( '%s week', '%s weeks', $count2, 'buddyboss' ), $count2 );
							break;
						case DAY_IN_SECONDS:
							$output .= sprintf( _n( '%s day', '%s days', $count2, 'buddyboss' ), $count2 );
							break;
						case HOUR_IN_SECONDS:
							$output .= sprintf( _n( '%s hour', '%s hours', $count2, 'buddyboss' ), $count2 );
							break;
						case MINUTE_IN_SECONDS:
							$output .= sprintf( _n( '%s minute', '%s minutes', $count2, 'buddyboss' ), $count2 );
							break;
						default:
							$output .= sprintf( _n( '%s second', '%s seconds', $count2, 'buddyboss' ), $count2 );
					}
				}
			}

			// No output, so happened right now.
			if ( ! (int) $count && ! (int) $count2 ) {
				$output = $right_now_text;
			}
		}
	}

	// Append 'ago' to the end of time-since if not 'right now'.
	if ( $output != $right_now_text ) {
		$output = sprintf( $ago_text, $output );
	}

	/**
	 * Filters the English-language representation of the time elapsed since a given date.
	 *
	 * @since BuddyPress 1.7.0
	 *
	 * @param string $output     Final 'time since' string.
	 * @param string $older_date Earlier time from which we're calculating time elapsed.
	 * @param string $newer_date Unix timestamp of date to compare older time to.
	 */
	return apply_filters( 'bp_core_time_since', $output, $older_date, $newer_date );
}

/**
 * Output an ISO-8601 date from a date string.
 *
 * @since BuddyPress 2.7.0
 *
 * @param string String of date to convert. Timezone should be UTC before using this.
 * @return string|null
 */
function bp_core_iso8601_date( $timestamp = '' ) {
	echo bp_core_get_iso8601_date( $timestamp );
}
	/**
	 * Return an ISO-8601 date from a date string.
	 *
	 * @since BuddyPress 2.7.0
	 *
	 * @param string String of date to convert. Timezone should be UTC before using this.
	 * @return string
	 */
function bp_core_get_iso8601_date( $timestamp = '' ) {
	if ( ! $timestamp ) {
			return '';
	}

	try {
		$date = new DateTime( $timestamp, new DateTimeZone( 'UTC' ) );

		// Not a valid date, so return blank string.
	} catch ( Exception $e ) {
			 return '';
	}

		 return $date->format( DateTime::ISO8601 );
}

/**
 * Return the Default date format
 *
 * @param bool   $date
 * @param bool   $time
 * @param string $symbol
 *
 * @return mixed
 */
function bp_core_date_format( $time = false, $date = true, $symbol = ' @ ' ) {

	$format = $date ? get_option( 'date_format' ) : '';

	if ( $time ) {
		$format .= empty( $format ) ? get_option( 'time_format' ) : $symbol . get_option( 'time_format' );
	}
	return $format;
}

/**
 * Output formatted date from a date string.
 *
 * @since BuddyBoss 1.0.0
 *
 * @param string String of date to convert. Timezone should be UTC before using this.
 * @param string String of format of date.
 * @return string|null
 */
function bp_core_format_date( $date = '', $format = '' ) {
	echo bp_core_get_format_date( $date, $format );
}

/**
 * Return formatted date from a date string.
 *
 * @since BuddyBoss 1.0.0
 *
 * @param string String of date to convert. Timezone should be UTC before using this.
 * @param string String of format of date.
 * @return string
 */
function bp_core_get_format_date( $date = '', $format = '' ) {
	if ( ! $date ) {
		return '';
	}

	$date = strtotime( $date );

	if ( empty( $format ) ) {
		$format = bp_core_date_format();
	}

	return date_i18n( $format, $date );
}


/** Messages ******************************************************************/

/**
 * Add a feedback (error/success) message to the WP cookie so it can be displayed after the page reloads.
 *
 * @since BuddyPress 1.0.0
 *
 * @param string $message Feedback message to be displayed.
 * @param string $type    Message type. 'updated', 'success', 'error', 'warning'.
 *                        Default: 'success'.
 */
function bp_core_add_message( $message, $type = '' ) {

	// Success is the default.
	if ( empty( $type ) ) {
		$type = 'success';
	}

	// Send the values to the cookie for page reload display.
	@setcookie( 'bp-message', rawurlencode( $message ), time() + 60 * 60 * 24, COOKIEPATH, COOKIE_DOMAIN, is_ssl() );
	@setcookie( 'bp-message-type', rawurlencode( $type ), time() + 60 * 60 * 24, COOKIEPATH, COOKIE_DOMAIN, is_ssl() );

	// Get BuddyPress.
	$bp = buddypress();

	/**
	 * Send the values to the $bp global so we can still output messages
	 * without a page reload
	 */
	$bp->template_message      = $message;
	$bp->template_message_type = $type;
}

/**
 * Set up the display of the 'template_notices' feedback message.
 *
 * Checks whether there is a feedback message in the WP cookie and, if so, adds
 * a "template_notices" action so that the message can be parsed into the
 * template and displayed to the user.
 *
 * After the message is displayed, it removes the message vars from the cookie
 * so that the message is not shown to the user multiple times.
 *
 * @since BuddyPress 1.1.0
 */
function bp_core_setup_message() {

	// Get BuddyPress.
	$bp = buddypress();

	if ( empty( $bp->template_message ) && isset( $_COOKIE['bp-message'] ) ) {
		$bp->template_message = stripslashes( rawurldecode( $_COOKIE['bp-message'] ) );
	}

	if ( empty( $bp->template_message_type ) && isset( $_COOKIE['bp-message-type'] ) ) {
		$bp->template_message_type = stripslashes( rawurldecode( $_COOKIE['bp-message-type'] ) );
	}

	add_action( 'template_notices', 'bp_core_render_message' );

	if ( isset( $_COOKIE['bp-message'] ) ) {
		@setcookie( 'bp-message', false, time() - 1000, COOKIEPATH, COOKIE_DOMAIN, is_ssl() );
	}

	if ( isset( $_COOKIE['bp-message-type'] ) ) {
		@setcookie( 'bp-message-type', false, time() - 1000, COOKIEPATH, COOKIE_DOMAIN, is_ssl() );
	}
}
add_action( 'bp_actions', 'bp_core_setup_message', 5 );

/**
 * Render the 'template_notices' feedback message.
 *
 * The hook action 'template_notices' is used to call this function, it is not
 * called directly.
 *
 * @since BuddyPress 1.1.0
 */
function bp_core_render_message() {

	// Get BuddyPress.
	$bp = buddypress();

	if ( ! empty( $bp->template_message ) ) :
		$type = ( 'success' === $bp->template_message_type ) ? 'updated' : 'error';

		/**
		 * Filters the 'template_notices' feedback message content.
		 *
		 * @since BuddyPress 1.5.5
		 *
		 * @param string $template_message Feedback message content.
		 * @param string $type             The type of message being displayed.
		 *                                 Either 'updated' or 'error'.
		 */
		$content = apply_filters( 'bp_core_render_message_content', $bp->template_message, $type ); ?>

		<div id="message" class="bp-template-notice <?php echo esc_attr( $type ); ?>">

			<?php echo $content; ?>

		</div>

		<?php

		/**
		 * Fires after the display of any template_notices feedback messages.
		 *
		 * @since BuddyPress 1.1.0
		 */
		do_action( 'bp_core_render_message' );

	endif;
}

/** Last active ***************************************************************/

/**
 * Listener function for the logged-in user's 'last_activity' metadata.
 *
 * Many functions use a "last active" feature to show the length of time since
 * the user was last active. This function will update that time as a usermeta
 * setting for the user every 5 minutes while the user is actively browsing the
 * site.
 *
 * @since BuddyPress 1.0.0
 *
 *       usermeta table.
 *
 * @return false|null Returns false if there is nothing to do.
 */
function bp_core_record_activity() {

	// Bail if user is not logged in.
	if ( ! is_user_logged_in() ) {
		return false;
	}

	// Get the user ID.
	$user_id = bp_loggedin_user_id();

	// Bail if user is not active.
	if ( bp_is_user_inactive( $user_id ) ) {
		return false;
	}

	// Get the user's last activity.
	$activity = bp_get_user_last_activity( $user_id );

	// Make sure it's numeric.
	if ( ! is_numeric( $activity ) ) {
		$activity = strtotime( $activity );
	}

	// Get current time.
	$current_time = bp_core_current_time( true, 'timestamp' );

	// Use this action to detect the very first activity for a given member.
	if ( empty( $activity ) ) {

		/**
		 * Fires inside the recording of an activity item.
		 *
		 * Use this action to detect the very first activity for a given member.
		 *
		 * @since BuddyPress 1.6.0
		 *
		 * @param int $user_id ID of the user whose activity is recorded.
		 */
		do_action( 'bp_first_activity_for_member', $user_id );
	}

	// If it's been more than 5 minutes, record a newer last-activity time.
	if ( empty( $activity ) || ( $current_time >= strtotime( '+5 minutes', $activity ) ) ) {
		bp_update_user_last_activity( $user_id, date( 'Y-m-d H:i:s', $current_time ) );
	}
}
add_action( 'wp_head', 'bp_core_record_activity' );

/**
 * Format last activity string based on time since date given.
 *
 * @since BuddyPress 1.0.0
 *
 *       representation of the time elapsed.
 *
 * @param int|string $last_activity_date The date of last activity.
 * @param string     $string             A sprintf()-able statement of the form 'active %s'.
 * @return string $last_active A string of the form '3 years ago'.
 */
function bp_core_get_last_activity( $last_activity_date = '', $string = '' ) {

	// Setup a default string if none was passed.
	$string = empty( $string )
		? '%s'     // Gettext placeholder.
		: $string;

	// Use the string if a last activity date was passed.
	$last_active = empty( $last_activity_date )
		? __( 'Not recently active', 'buddyboss' )
		: sprintf( $string, bp_core_time_since( $last_activity_date ) );

	/**
	 * Filters last activity string based on time since date given.
	 *
	 * @since BuddyPress 1.2.0
	 *
	 * @param string $last_active        Last activity string based on time since date given.
	 * @param string $last_activity_date The date of last activity.
	 * @param string $string             A sprintf()-able statement of the form 'active %s'.
	 */
	return apply_filters( 'bp_core_get_last_activity', $last_active, $last_activity_date, $string );
}

/** Meta **********************************************************************/

/**
 * Get the meta_key for a given piece of user metadata
 *
 * BuddyPress stores a number of pieces of userdata in the WordPress central
 * usermeta table. In order to allow plugins to enable multiple instances of
 * BuddyPress on a single WP installation, BP's usermeta keys are filtered
 * through this function, so that they can be altered on the fly.
 *
 * Plugin authors should use BP's _user_meta() functions, which bakes in
 * bp_get_user_meta_key():
 *    $friend_count = bp_get_user_meta( $user_id, 'total_friend_count', true );
 * If you must use WP's _user_meta() functions directly for some reason, you
 * should use this function to determine the $key parameter, eg
 *    $friend_count = get_user_meta( $user_id, bp_get_user_meta_key( 'total_friend_count' ), true );
 * If using the WP functions, do not not hardcode your meta keys.
 *
 * @since BuddyPress 1.5.0
 *
 * @param string|bool $key The usermeta meta_key.
 * @return string $key The usermeta meta_key.
 */
function bp_get_user_meta_key( $key = false ) {

	/**
	 * Filters the meta_key for a given piece of user metadata.
	 *
	 * @since BuddyPress 1.5.0
	 *
	 * @param string $key The usermeta meta key.
	 */
	return apply_filters( 'bp_get_user_meta_key', $key );
}

/**
 * Get a piece of usermeta.
 *
 * This is a wrapper for get_user_meta() that allows for easy use of
 * bp_get_user_meta_key(), thereby increasing compatibility with non-standard
 * BP setups.
 *
 * @since BuddyPress 1.5.0
 *
 * @see get_user_meta() For complete details about parameters and return values.
 *
 * @param int    $user_id The ID of the user whose meta you're fetching.
 * @param string $key     The meta key to retrieve.
 * @param bool   $single  Whether to return a single value.
 * @return mixed Will be an array if $single is false. Will be value of meta data field if $single
 *               is true.
 */
function bp_get_user_meta( $user_id, $key, $single = false ) {
	return get_user_meta( $user_id, bp_get_user_meta_key( $key ), $single );
}

/**
 * Update a piece of usermeta.
 *
 * This is a wrapper for update_user_meta() that allows for easy use of
 * bp_get_user_meta_key(), thereby increasing compatibility with non-standard
 * BP setups.
 *
 * @since BuddyPress 1.5.0
 *
 * @see update_user_meta() For complete details about parameters and return values.
 *
 * @param int    $user_id    The ID of the user whose meta you're setting.
 * @param string $key        The meta key to set.
 * @param mixed  $value      Metadata value.
 * @param mixed  $prev_value Optional. Previous value to check before removing.
 * @return bool False on failure, true on success.
 */
function bp_update_user_meta( $user_id, $key, $value, $prev_value = '' ) {
	return update_user_meta( $user_id, bp_get_user_meta_key( $key ), $value, $prev_value );
}

/**
 * Delete a piece of usermeta.
 *
 * This is a wrapper for delete_user_meta() that allows for easy use of
 * bp_get_user_meta_key(), thereby increasing compatibility with non-standard
 * BP setups.
 *
 * @since BuddyPress 1.5.0
 *
 * @see delete_user_meta() For complete details about parameters and return values.
 *
 * @param int    $user_id The ID of the user whose meta you're deleting.
 * @param string $key     The meta key to delete.
 * @param mixed  $value   Optional. Metadata value.
 * @return bool False for failure. True for success.
 */
function bp_delete_user_meta( $user_id, $key, $value = '' ) {
	return delete_user_meta( $user_id, bp_get_user_meta_key( $key ), $value );
}

/** Embeds ********************************************************************/

/**
 * Initializes {@link BP_Embed} after everything is loaded.
 *
 * @since BuddyPress 1.5.0
 */
function bp_embed_init() {

	// Get BuddyPress.
	$bp = buddypress();

	if ( empty( $bp->embed ) ) {
		$bp->embed = new BP_Embed();
	}
}
add_action( 'bp_init', 'bp_embed_init', 9 );

/**
 * Are oembeds allowed in activity items?
 *
 * @since BuddyPress 1.5.0
 *
 * @return bool False when activity embed support is disabled; true when
 *              enabled. Default: true.
 */
function bp_use_embed_in_activity() {

	/**
	 * Filters whether or not oEmbeds are allowed in activity items.
	 *
	 * @since BuddyPress 1.5.0
	 *
	 * @param bool $value Whether or not oEmbeds are allowed.
	 */
	return apply_filters( 'bp_use_oembed_in_activity', ! defined( 'BP_EMBED_DISABLE_ACTIVITY' ) || ! BP_EMBED_DISABLE_ACTIVITY );
}

/**
 * Are oembeds allowed in activity replies?
 *
 * @since BuddyPress 1.5.0
 *
 * @return bool False when activity replies embed support is disabled; true
 *              when enabled. Default: true.
 */
function bp_use_embed_in_activity_replies() {

	/**
	 * Filters whether or not oEmbeds are allowed in activity replies.
	 *
	 * @since BuddyPress 1.5.0
	 *
	 * @param bool $value Whether or not oEmbeds are allowed.
	 */
	return apply_filters( 'bp_use_embed_in_activity_replies', ! defined( 'BP_EMBED_DISABLE_ACTIVITY_REPLIES' ) || ! BP_EMBED_DISABLE_ACTIVITY_REPLIES );
}

/**
 * Are oembeds allowed in private messages?
 *
 * @since BuddyPress 1.5.0
 *
 * @return bool False when private message embed support is disabled; true when
 *              enabled. Default: true.
 */
function bp_use_embed_in_private_messages() {

	/**
	 * Filters whether or not oEmbeds are allowed in private messages.
	 *
	 * @since BuddyPress 1.5.0
	 *
	 * @param bool $value Whether or not oEmbeds are allowed.
	 */
	return apply_filters( 'bp_use_embed_in_private_messages', ! defined( 'BP_EMBED_DISABLE_PRIVATE_MESSAGES' ) || ! BP_EMBED_DISABLE_PRIVATE_MESSAGES );
}

/**
 * Extracts media metadata from a given content.
 *
 * @since BuddyPress 2.6.0
 *
 * @param string     $content The content to check.
 * @param string|int $type    The type to check. Can also use a bitmask. See the class constants in the
 *                             BP_Media_Extractor class for more info.
 * @return false|array If media exists, will return array of media metadata. Else, boolean false.
 */
function bp_core_extract_media_from_content( $content = '', $type = 'all' ) {
	if ( is_string( $type ) ) {
		$class   = new ReflectionClass( 'BP_Media_Extractor' );
		$bitmask = $class->getConstant( strtoupper( $type ) );
	} else {
		$bitmask = (int) $type;
	}

	// Type isn't valid, so bail.
	if ( empty( $bitmask ) ) {
		return false;
	}

	$x     = new BP_Media_Extractor();
	$media = $x->extract( $content, $bitmask );

	unset( $media['has'] );
	$retval = array_filter( $media );

	return ! empty( $retval ) ? $retval : false;
}

/** Admin *********************************************************************/

/**
 * Output the correct admin URL based on BuddyPress and WordPress configuration.
 *
 * @since BuddyPress 1.5.0
 *
 * @see bp_get_admin_url() For description of parameters.
 *
 * @param string $path   See {@link bp_get_admin_url()}.
 * @param string $scheme See {@link bp_get_admin_url()}.
 */
function bp_admin_url( $path = '', $scheme = 'admin' ) {
	echo esc_url( bp_get_admin_url( $path, $scheme ) );
}
	/**
	 * Return the correct admin URL based on BuddyPress and WordPress configuration.
	 *
	 * @since BuddyPress 1.5.0
	 *
	 * @param string $path   Optional. The sub-path under /wp-admin to be
	 *                       appended to the admin URL.
	 * @param string $scheme The scheme to use. Default is 'admin', which
	 *                       obeys {@link force_ssl_admin()} and {@link is_ssl()}. 'http'
	 *                       or 'https' can be passed to force those schemes.
	 * @return string Admin url link with optional path appended.
	 */
function bp_get_admin_url( $path = '', $scheme = 'admin' ) {

	// Links belong in network admin.
	if ( bp_core_do_network_admin() ) {
		$url = network_admin_url( $path, $scheme );

		// Links belong in site admin.
	} else {
		$url = admin_url( $path, $scheme );
	}

	return $url;
}

/**
 * Should BuddyPress appear in network admin (vs a single site Dashboard)?
 *
 * Because BuddyPress can be installed in multiple ways and with multiple
 * configurations, we need to check a few things to be confident about where
 * to hook into certain areas of WordPress's admin.
 *
 * @since BuddyPress 1.5.0
 *
 * @return bool True if the BP admin screen should appear in the Network Admin,
 *              otherwise false.
 */
function bp_core_do_network_admin() {

	// Default.
	$retval = bp_is_network_activated();

	if ( bp_is_multiblog_mode() ) {
		$retval = false;
	}

	/**
	 * Filters whether or not BuddyPress should appear in network admin.
	 *
	 * @since BuddyPress 1.5.0
	 *
	 * @param bool $retval Whether or not BuddyPress should be in the network admin.
	 */
	return (bool) apply_filters( 'bp_core_do_network_admin', $retval );
}

/**
 * Return the action name that BuddyPress nav setup callbacks should be hooked to.
 *
 * Functions used to set up BP Dashboard pages (wrapping such admin-panel
 * functions as add_submenu_page()) should use bp_core_admin_hook() for the
 * first parameter in add_action(). BuddyPress will then determine
 * automatically whether to load the panels in the Network Admin. Ie:
 *
 *     add_action( bp_core_admin_hook(), 'myplugin_dashboard_panel_setup' );
 *
 * @since BuddyPress 1.5.0
 *
 * @return string $hook The proper hook ('network_admin_menu' or 'admin_menu').
 */
function bp_core_admin_hook() {
	$hook = bp_core_do_network_admin() ? 'network_admin_menu' : 'admin_menu';

	/**
	 * Filters the action name that BuddyPress nav setup callbacks should be hooked to.
	 *
	 * @since BuddyPress 1.5.0
	 *
	 * @param string $hook Action name to be attached to.
	 */
	return apply_filters( 'bp_core_admin_hook', $hook );
}

/** Multisite *****************************************************************/

/**
 * Is this the root blog?
 *
 * @since BuddyPress 1.5.0
 *
 * @param int $blog_id Optional. Default: the ID of the current blog.
 * @return bool $is_root_blog Returns true if this is bp_get_root_blog_id().
 */
function bp_is_root_blog( $blog_id = 0 ) {

	// Assume false.
	$is_root_blog = false;

	// Use current blog if no ID is passed.
	if ( empty( $blog_id ) || ! is_int( $blog_id ) ) {
		$blog_id = get_current_blog_id();
	}

	// Compare to root blog ID.
	if ( bp_get_root_blog_id() === $blog_id ) {
		$is_root_blog = true;
	}

	/**
	 * Filters whether or not we're on the root blog.
	 *
	 * @since BuddyPress 1.5.0
	 *
	 * @param bool $is_root_blog Whether or not we're on the root blog.
	 */
	return (bool) apply_filters( 'bp_is_root_blog', (bool) $is_root_blog );
}

/**
 * Get the ID of the root blog.
 *
 * The "root blog" is the blog on a WordPress network where BuddyPress content
 * appears (where member profile URLs resolve, where a given theme is loaded,
 * etc.).
 *
 * @since BuddyPress 1.5.0
 *
 * @return int The root site ID.
 */
function bp_get_root_blog_id() {

	/**
	 * Filters the ID for the root blog.
	 *
	 * @since BuddyPress 1.5.0
	 *
	 * @param int $root_blog_id ID for the root blog.
	 */
	return (int) apply_filters( 'bp_get_root_blog_id', (int) buddypress()->root_blog_id );
}

/**
 * Are we running multiblog mode?
 *
 * Note that BP_ENABLE_MULTIBLOG is different from (but dependent on) WordPress
 * Multisite. "Multiblog" is BuddyPress setup that allows BuddyPress components
 * to be viewed on every blog on the network, each with their own settings.
 *
 * Thus, instead of having all 'boonebgorges' links go to
 *   http://example.com/members/boonebgorges
 * on the root blog, each blog will have its own version of the same content, eg
 *   http://site2.example.com/members/boonebgorges (for subdomains)
 *   http://example.com/site2/members/boonebgorges (for subdirectories)
 *
 * Multiblog mode is disabled by default, meaning that all BuddyPress content
 * must be viewed on the root blog. It's also recommended not to use the
 * BP_ENABLE_MULTIBLOG constant beyond 1.7, as BuddyPress can now be activated
 * on individual sites.
 *
 * Why would you want to use this? Originally it was intended to allow
 * BuddyPress to live in mu-plugins and be visible on mapped domains. This is
 * a very small use-case with large architectural shortcomings, so do not go
 * down this road unless you specifically need to.
 *
 * @since BuddyPress 1.5.0
 *
 * @return bool False when multiblog mode is disabled; true when enabled.
 *              Default: false.
 */
function bp_is_multiblog_mode() {

	// Setup some default values.
	$retval         = false;
	$is_multisite   = is_multisite();
	$network_active = bp_is_network_activated();
	$is_multiblog   = defined( 'BP_ENABLE_MULTIBLOG' ) && BP_ENABLE_MULTIBLOG;

	// Multisite, Network Activated, and Specifically Multiblog.
	if ( $is_multisite && $network_active && $is_multiblog ) {
		$retval = true;

		// Multisite, but not network activated.
	} elseif ( $is_multisite && ! $network_active ) {
		$retval = true;
	}

	/**
	 * Filters whether or not we're running in multiblog mode.
	 *
	 * @since BuddyPress 1.5.0
	 *
	 * @param bool $retval Whether or not we're running multiblog mode.
	 */
	return apply_filters( 'bp_is_multiblog_mode', $retval );
}

/**
 * Is BuddyPress active at the network level for this network?
 *
 * Used to determine admin menu placement, and where settings and options are
 * stored. If you're being *really* clever and manually pulling BuddyPress in
 * with an mu-plugin or some other method, you'll want to filter
 * 'bp_is_network_activated' and override the auto-determined value.
 *
 * @since BuddyPress 1.7.0
 *
 * @return bool True if BuddyPress is network activated.
 */
function bp_is_network_activated() {

	// Default to is_multisite().
	$retval = is_multisite();

	// Check the sitewide plugins array.
	$base    = buddypress()->basename;
	$plugins = get_site_option( 'active_sitewide_plugins' );

	// Override is_multisite() if not network activated.
	if ( ! is_array( $plugins ) || ! isset( $plugins[ $base ] ) ) {
		$retval = false;
	}

	/**
	 * Filters whether or not we're active at the network level.
	 *
	 * @since BuddyPress 1.7.0
	 *
	 * @param bool $retval Whether or not we're network activated.
	 */
	return (bool) apply_filters( 'bp_is_network_activated', $retval );
}

/** Global Manipulators *******************************************************/

/**
 * Set the "is_directory" global.
 *
 * @since BuddyPress 1.5.0
 *
 * @param bool   $is_directory Optional. Default: false.
 * @param string $component    Optional. Component name. Default: the current
 *                             component.
 */
function bp_update_is_directory( $is_directory = false, $component = '' ) {

	if ( empty( $component ) ) {
		$component = bp_current_component();
	}

	/**
	 * Filters the "is_directory" global value.
	 *
	 * @since BuddyPress 1.5.0
	 *
	 * @param bool   $is_directory Whether or not we're "is_directory".
	 * @param string $component    Component name. Default: the current component.
	 */
	buddypress()->is_directory = apply_filters( 'bp_update_is_directory', $is_directory, $component );
}

/**
 * Set the "is_item_admin" global.
 *
 * @since BuddyPress 1.5.0
 *
 * @param bool   $is_item_admin Optional. Default: false.
 * @param string $component     Optional. Component name. Default: the current
 *                              component.
 */
function bp_update_is_item_admin( $is_item_admin = false, $component = '' ) {

	if ( empty( $component ) ) {
		$component = bp_current_component();
	}

	/**
	 * Filters the "is_item_admin" global value.
	 *
	 * @since BuddyPress 1.5.0
	 *
	 * @param bool   $is_item_admin Whether or not we're "is_item_admin".
	 * @param string $component     Component name. Default: the current component.
	 */
	buddypress()->is_item_admin = apply_filters( 'bp_update_is_item_admin', $is_item_admin, $component );
}

/**
 * Set the "is_item_mod" global.
 *
 * @since BuddyPress 1.5.0
 *
 * @param bool   $is_item_mod Optional. Default: false.
 * @param string $component   Optional. Component name. Default: the current
 *                            component.
 */
function bp_update_is_item_mod( $is_item_mod = false, $component = '' ) {

	if ( empty( $component ) ) {
		$component = bp_current_component();
	}

	/**
	 * Filters the "is_item_mod" global value.
	 *
	 * @since BuddyPress 1.5.0
	 *
	 * @param bool   $is_item_mod Whether or not we're "is_item_mod".
	 * @param string $component   Component name. Default: the current component.
	 */
	buddypress()->is_item_mod = apply_filters( 'bp_update_is_item_mod', $is_item_mod, $component );
}

/**
 * Trigger a 404.
 *
 * @since BuddyPress 1.5.0
 *
 * @global WP_Query $wp_query WordPress query object.
 *
 * @param string $redirect If 'remove_canonical_direct', remove WordPress' "helpful"
 *                         redirect_canonical action. Default: 'remove_canonical_redirect'.
 */
function bp_do_404( $redirect = 'remove_canonical_direct' ) {
	global $wp_query;

	/**
	 * Fires inside the triggering of a 404.
	 *
	 * @since BuddyPress 1.5.0
	 *
	 * @param string $redirect Redirect type used to determine if redirect_canonical
	 *                         function should be be removed.
	 */
	do_action( 'bp_do_404', $redirect );

	$wp_query->set_404();
	status_header( 404 );
	nocache_headers();

	if ( 'remove_canonical_direct' === $redirect ) {
		remove_action( 'template_redirect', 'redirect_canonical' );
	}
}

/** Nonces ********************************************************************/

/**
 * Makes sure the user requested an action from another page on this site.
 *
 * To avoid security exploits within the theme.
 *
 * @since BuddyPress 1.6.0
 *
 * @param string $action    Action nonce.
 * @param string $query_arg Where to look for nonce in $_REQUEST.
 * @return bool True if the nonce is verified, otherwise false.
 */
function bp_verify_nonce_request( $action = '', $query_arg = '_wpnonce' ) {

	/* Home URL **************************************************************/

	// Parse home_url() into pieces to remove query-strings, strange characters,
	// and other funny things that plugins might to do to it.
	$parsed_home = parse_url( home_url( '/', ( is_ssl() ? 'https' : 'http' ) ) );

	// Maybe include the port, if it's included in home_url().
	if ( isset( $parsed_home['port'] ) ) {
		$parsed_host = $parsed_home['host'] . ':' . $parsed_home['port'];
	} else {
		$parsed_host = $parsed_home['host'];
	}

	// Set the home URL for use in comparisons.
	$home_url = trim( strtolower( $parsed_home['scheme'] . '://' . $parsed_host . $parsed_home['path'] ), '/' );

	/* Requested URL *********************************************************/

	// Maybe include the port, if it's included in home_url().
	if ( isset( $parsed_home['port'] ) && false === strpos( $_SERVER['HTTP_HOST'], ':' ) ) {
		$request_host = $_SERVER['HTTP_HOST'] . ':' . $_SERVER['SERVER_PORT'];
	} else {
		$request_host = $_SERVER['HTTP_HOST'];
	}

	// Build the currently requested URL.
	$scheme        = is_ssl() ? 'https://' : 'http://';
	$requested_url = strtolower( $scheme . $request_host . $_SERVER['REQUEST_URI'] );

	/* Look for match ********************************************************/

	/**
	 * Filters the requested URL being nonce-verified.
	 *
	 * Useful for configurations like reverse proxying.
	 *
	 * @since BuddyPress 1.9.0
	 *
	 * @param string $requested_url The requested URL.
	 */
	$matched_url = apply_filters( 'bp_verify_nonce_request_url', $requested_url );

	// Check the nonce.
	$result = isset( $_REQUEST[ $query_arg ] ) ? wp_verify_nonce( $_REQUEST[ $query_arg ], $action ) : false;

	// Nonce check failed.
	if ( empty( $result ) || empty( $action ) || ( strpos( $matched_url, $home_url ) !== 0 ) ) {
		$result = false;
	}

	/**
	 * Fires at the end of the nonce verification check.
	 *
	 * @since BuddyPress 1.6.0
	 *
	 * @param string $action Action nonce.
	 * @param bool   $result Boolean result of nonce verification.
	 */
	do_action( 'bp_verify_nonce_request', $action, $result );

	return $result;
}

/** Requests ******************************************************************/

/**
 * Return true|false if this is a POST request.
 *
 * @since BuddyPress 1.9.0
 *
 * @return bool
 */
function bp_is_post_request() {
	return (bool) ( 'POST' === strtoupper( $_SERVER['REQUEST_METHOD'] ) );
}

/**
 * Return true|false if this is a GET request.
 *
 * @since BuddyPress 1.9.0
 *
 * @return bool
 */
function bp_is_get_request() {
	return (bool) ( 'GET' === strtoupper( $_SERVER['REQUEST_METHOD'] ) );
}


/** Miscellaneous hooks *******************************************************/

/**
 * Load the buddyboss translation file for current language.
 *
 * @since BuddyPress 1.0.2
 *
 * @see load_textdomain() for a description of return values.
 *
 * @return bool True on success, false on failure.
 */
function bp_core_load_buddypress_textdomain() {
	$domain = 'buddyboss';

	/**
	 * Filters the locale to be loaded for the language files.
	 *
	 * @since BuddyPress 1.0.2
	 *
	 * @param string $value Current locale for the install.
	 */
	$mofile_custom = sprintf( '%s-%s.mo', $domain, apply_filters( 'buddypress_locale', get_locale() ) );

	/**
	 * Filters the locations to load language files from.
	 *
	 * @since BuddyPress 2.2.0
	 *
	 * @param array $value Array of directories to check for language files in.
	 */
	$locations = apply_filters(
		'buddypress_locale_locations',
		array(
			trailingslashit( WP_LANG_DIR . '/' . $domain ),
			trailingslashit( WP_LANG_DIR ),
			trailingslashit( BP_PLUGIN_DIR . '/languages' ),
		)
	);

	// Try custom locations in WP_LANG_DIR.
	foreach ( $locations as $location ) {
		if ( load_textdomain( 'buddyboss', $location . $mofile_custom ) ) {
			return true;
		}
	}

	// Default to WP and glotpress.
	return load_plugin_textdomain( $domain );
}
add_action( 'bp_core_loaded', 'bp_core_load_buddypress_textdomain' );

/**
 * A JavaScript-free implementation of the search functions in BuddyPress.
 *
 * @since BuddyPress 1.0.1
 *
 * @param string $slug The slug to redirect to for searching.
 */
function bp_core_action_search_site( $slug = '' ) {

	if ( ! bp_is_current_component( bp_get_search_slug() ) ) {
		return;
	}

	if ( empty( $_POST['search-terms'] ) ) {
		bp_core_redirect( bp_get_root_domain() );
		return;
	}

	$search_terms = stripslashes( $_POST['search-terms'] );
	$search_which = ! empty( $_POST['search-which'] ) ? $_POST['search-which'] : '';
	$query_string = '/?s=';

	if ( empty( $slug ) ) {
		switch ( $search_which ) {
			case 'posts':
				$slug = '';
				$var  = '/?s=';

				// If posts aren't displayed on the front page, find the post page's slug.
				if ( 'page' == get_option( 'show_on_front' ) ) {
					$page = get_post( get_option( 'page_for_posts' ) );

					if ( ! is_wp_error( $page ) && ! empty( $page->post_name ) ) {
						$slug = $page->post_name;
						$var  = '?s=';
					}
				}
				break;

			case 'blogs':
				$slug = bp_is_active( 'blogs' ) ? bp_get_blogs_root_slug() : '';
				break;

			case 'groups':
				$slug = bp_is_active( 'groups' ) ? bp_get_groups_root_slug() : '';
				break;

			case 'members':
			default:
				$slug = bp_get_members_root_slug();
				break;
		}

		if ( empty( $slug ) && 'posts' != $search_which ) {
			bp_core_redirect( bp_get_root_domain() );
			return;
		}
	}

	/**
	 * Filters the constructed url for use with site searching.
	 *
	 * @since BuddyPress 1.0.0
	 *
	 * @param string $value        URL for use with site searching.
	 * @param array  $search_terms Array of search terms.
	 */
	bp_core_redirect( apply_filters( 'bp_core_search_site', home_url( $slug . $query_string . urlencode( $search_terms ) ), $search_terms ) );
}
add_action( 'bp_init', 'bp_core_action_search_site', 7 );

/**
 * Remove "prev" and "next" relational links from <head> on BuddyPress pages.
 *
 * WordPress automatically generates these relational links to the current
 * page.  However, BuddyPress doesn't adhere to these links.  In this
 * function, we remove these links when on a BuddyPress page.  This also
 * prevents additional, unnecessary queries from running.
 *
 * @since BuddyPress 2.1.0
 */
function bp_remove_adjacent_posts_rel_link() {
	if ( ! is_buddypress() ) {
		return;
	}

	remove_action( 'wp_head', 'adjacent_posts_rel_link_wp_head', 10 );
}
add_action( 'bp_init', 'bp_remove_adjacent_posts_rel_link' );

/**
 * Strip the span count of a menu item or of a title part.
 *
 * @since BuddyPress 2.2.2
 *
 * @param string $title_part Title part to clean up.
 * @return string
 */
function _bp_strip_spans_from_title( $title_part = '' ) {
	$title = $title_part;
	$span  = strpos( $title, '<span' );
	if ( false !== $span ) {
		$title = substr( $title, 0, $span - 1 );
	}
	return trim( $title );
}

/**
 * Get the correct filename suffix for minified assets.
 *
 * @since BuddyPress 2.5.0
 *
 * @return string
 */
function bp_core_get_minified_asset_suffix() {
	$ext = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? '' : '.min';
	return $ext;
}

/**
 * Return a list of component information.
 *
 * @since BuddyPress 2.6.0
 *
 * @param string $type Optional; component type to fetch. Default value is 'all', or 'optional', 'required', 'default'.
 * @return array Requested components' data.
 */
function bp_core_get_components( $type = 'all' ) {

	$required_components = array(
		'members'  => array(
			'title'       => __( 'Member Profiles', 'buddyboss' ),
			'settings'    => bp_get_admin_url(
				add_query_arg(
					array(
						'page' => 'bp-settings',
						'tab'  => 'bp-xprofile',
					),
					'admin.php'
				)
			),
			'description' => __( 'Everything in a community website revolves around its members. All website users are given member profiles.', 'buddyboss' ),
		),
		'xprofile' => array(
			'title'       => __( 'Profile Fields', 'buddyboss' ),
			'settings'    => bp_get_admin_url( 'admin.php?page=bp-profile-setup' ),
			'description' => __( 'Customize your community with fully editable profile fields that allow members to share details about themselves.', 'buddyboss' ),
			'default'     => true,
		),
	);

	$optional_components = array(
		'settings'      => array(
			'title'       => __( 'Account Settings', 'buddyboss' ),
			'description' => __( 'Allow members to modify their account and notification settings from within their profile.', 'buddyboss' ),
			'default'     => true,
		),
		'notifications' => array(
			'title'       => __( 'Notifications', 'buddyboss' ),
			'description' => __( 'Notify members of relevant activity with a toolbar bubble and/or via email and allow them to customize their notification settings.', 'buddyboss' ),
			'default'     => true,
		),
		'groups'        => array(
			'title'       => __( 'Social Groups', 'buddyboss' ),
			'settings'    => bp_get_admin_url(
				add_query_arg(
					array(
						'page' => 'bp-settings',
						'tab'  => 'bp-groups',
					),
					'admin.php'
				)
			),
			'description' => __( 'Allow members to organize themselves into public, private or hidden social groups with separate activity feeds and member listings.', 'buddyboss' ),
			'default'     => false,
		),
		'forums'        => array(
			'title'       => __( 'Forum Discussions', 'buddyboss' ),
			'settings'    => bp_get_admin_url(
				add_query_arg(
					array(
						'page' => 'bp-settings',
						'tab'  => 'bp-forums',
					),
					'admin.php'
				)
			),
			'description' => __( 'Allow members to have discussions using Q&A style message boards. Forums can be standalone or connected to social groups.', 'buddyboss' ),
			'default'     => false,
		),
		'activity'      => array(
			'title'       => __( 'Activity Feeds', 'buddyboss' ),
			'settings'    => bp_get_admin_url(
				add_query_arg(
					array(
						'page' => 'bp-settings',
						'tab'  => 'bp-activity',
					),
					'admin.php'
				)
			),
			'description' => __( 'Global, personal, and group activity feeds with threaded commenting, direct posting, and @mentions, with email notification support.', 'buddyboss' ),
			'default'     => false,
		),
		'media'         => array(
			'title'       => __( 'Media Uploading', 'buddyboss' ),
			'settings'    => bp_get_admin_url(
				add_query_arg(
					array(
						'page' => 'bp-settings',
						'tab'  => 'bp-media',
					),
					'admin.php'
				)
			),
			'description' => __( 'Allow members to upload photos, documents, videos, emojis and animated GIFs, and to organize photos and videos into albums and documents into folders.', 'buddyboss' ),
			'default'     => false,
		),
		'document'      => array(
			'title'       => __( 'Document Uploading', 'buddyboss' ),
			'settings'    => bp_get_admin_url(
				add_query_arg(
					array(
						'page' => 'bp-settings',
						'tab'  => 'bp-media',
					),
					'admin.php'
				)
			),
			'description' => __( 'Allow members to upload documents, and to organize documents into folders.', 'buddyboss' ),
			'default'     => false,
		),
		'video'         => array(
			'title'       => __( 'Video Uploading', 'buddyboss' ),
			'settings'    => bp_get_admin_url(
				add_query_arg(
					array(
						'page' => 'bp-settings',
						'tab'  => 'bp-media',
					),
					'admin.php'
				)
			),
			'description' => __( 'Allow members to upload videos, and to organize videos into albums.', 'buddyboss' ),
			'default'     => false,
		),
		'messages'      => array(
			'title'       => __( 'Private Messaging', 'buddyboss' ),
			'description' => __( 'Allow members to send private messages. Messages can be sent to one member or a group of members.', 'buddyboss' ),
			'default'     => false,
		),
		'friends'       => array(
			'title'       => __( 'Member Connections', 'buddyboss' ),
			'settings'    => bp_get_admin_url(
				add_query_arg(
					array(
						'page' => 'bp-settings',
						'tab'  => 'bp-friends',
					),
					'admin.php'
				)
			),
			'description' => __( 'Allow members to make connections with one another and focus on those they care about most.', 'buddyboss' ),
			'default'     => false,
		),
		'invites'       => array(
			'title'       => __( 'Email Invites', 'buddyboss' ),
			'settings'    => bp_get_admin_url(
				add_query_arg(
					array(
						'page' => 'bp-settings',
						'tab'  => 'bp-invites',
					),
					'admin.php'
				)
			),
			'description' => __( 'Allow members to send email invitations to non-members to join the network.', 'buddyboss' ),
			'default'     => false,
		),
		'moderation'    => array(
			'title'                => __( 'Moderation', 'buddyboss' ),
			'description'          => __( 'Allow members to block each other, and report inappropriate content to be reviewed by the site admin.', 'buddyboss' ),
			'settings'             => bp_get_admin_url(
				add_query_arg(
					array(
						'page' => 'bp-settings',
						'tab'  => 'bp-moderation',
					),
					'admin.php'
				)
			),
			'default'              => false,
			'deactivation_confirm' => true,
			'deactivation_message' => '<p>' . __( 'Please confirm you want to deactivate the Moderation component.', 'buddyboss' ) . '</p>' .
										'<h4>' . __( 'On Deactivation:', 'buddyboss' ) . '</h4>' .
										'<ul>' .
											'<li>' . __( 'All suspended members will regain permission to login and their content will be unhidden', 'buddyboss' ) . '</li>' .
											'<li>' . __( 'Members on the network will no longer be able to block other members. Any members they have blocked will be unblocked.', 'buddyboss' ) . '</li>' .
											'<li>' . __( 'All hidden content will be unhidden', 'buddyboss' ) . '</li>' .
										'</ul>' .
										'<p>' . __( 'Please note: Data will not be deleted when you deactivate the Moderation component. On reactivation, members who have previously been suspended or blocked will once again have their access removed or limited. Content that was previously unhidden will be hidden again.', 'buddyboss' ) . '</p>',
		),
		// @todo: used for bp-performance will enable in feature.
		/*
		'performance'       => array(
			'title'       => __( 'API Caching', 'buddyboss' ),
			'settings'    => bp_get_admin_url(
				add_query_arg(
					array(
						'page' => 'bp-settings',
						'tab'  => 'bp-performance',
					),
					'admin.php'
				)
			),
			'description' => __( 'Allow REST API data to be cached to improve performance.', 'buddyboss' ),
			'default'     => false,
		),
		*/
		'search'        => array(
			'title'       => __( 'Network Search', 'buddyboss' ),
			'settings'    => bp_get_admin_url(
				add_query_arg(
					array(
						'page' => 'bp-settings',
						'tab'  => 'bp-search',
					),
					'admin.php'
				)
			),
			'description' => __( 'Allow members to search the entire network, along with custom post types of your choice, all in one unified search bar.', 'buddyboss' ),
			'default'     => false,
		),
		'blogs'         => array(
			'title'       => __( 'Blog Feeds', 'buddyboss' ),
			'description' => __( 'Have new blog posts and comments appear in site activity feeds. Make sure to enable Activity Feeds first.', 'buddyboss' ),
			'default'     => false,
		),
	);

	if ( class_exists( 'BB_Platform_Pro' ) && function_exists( 'is_plugin_active' ) && is_plugin_active( 'buddyboss-platform-pro/buddyboss-platform-pro.php' ) ) {
		$plugin_data    = get_plugin_data( trailingslashit( WP_PLUGIN_DIR ) . 'buddyboss-platform-pro/buddyboss-platform-pro.php' );
		$plugin_version = ! empty( $plugin_data['Version'] ) ? $plugin_data['Version'] : 0;
		if ( $plugin_version && version_compare( $plugin_version, '1.0.9', '>' ) ) {
			$optional_components['messages']['settings'] = bp_get_admin_url(
				add_query_arg(
					array(
						'page' => 'bp-settings',
						'tab'  => 'bp-messages',
					),
					'admin.php'
				)
			);
		}
	}

	// Add blogs tracking if multisite.
	if ( is_multisite() ) {
		$optional_components['blogs']['description'] = __( 'Record activity for new sites, posts, and comments across your network.', 'buddyboss' );
	}

	$default_components = array();
	foreach ( array_merge( $required_components, $optional_components ) as $key => $component ) {
		if ( isset( $component['default'] ) && true === $component['default'] ) {
			$default_components[ $key ] = $component;
		}
	}

	switch ( $type ) {
		case 'required':
			$components = $required_components;
			break;
		case 'optional':
			$components = $optional_components;
			break;
		case 'default':
			$components = $default_components;
			break;
		case 'all':
		default:
			$components = array_merge( $required_components, $optional_components );
			break;
	}

	/**
	 * Filters the list of component information.
	 *
	 * @since BuddyPress 2.6.0
	 *
	 * @param array  $components Array of component information.
	 * @param string $type       Type of component list requested.
	 *                           Possible values are 'all', 'optional', 'required'.
	 */
	return apply_filters( 'bp_core_get_components', $components, $type );
}

/** Nav Menu ******************************************************************/

/**
 * Create fake "post" objects for BP's logged-in nav menu for use in the WordPress "Menus" settings page.
 *
 * WordPress nav menus work by representing post or tax term data as a custom
 * post type, which is then used to populate the checkboxes that appear on
 * Dashboard > Appearance > Menu as well as the menu as rendered on the front
 * end. Most of the items in the BuddyPress set of nav items are neither posts
 * nor tax terms, so we fake a post-like object so as to be compatible with the
 * menu.
 *
 * This technique also allows us to generate links dynamically, so that, for
 * example, "My Profile" will always point to the URL of the profile of the
 * logged-in user.
 *
 * @since BuddyPress 1.9.0
 *
 * @return mixed A URL or an array of dummy pages.
 */
function bp_nav_menu_get_loggedin_pages() {

	// Try to catch the cached version first.
	if ( ! empty( buddypress()->wp_nav_menu_items->loggedin ) ) {
		return buddypress()->wp_nav_menu_items->loggedin;
	}

	// Pull up a list of items registered in BP's primary nav for the member.
	$bp_menu_items = buddypress()->members->nav->get_primary();

	// Some BP nav menu items will not be represented in bp_nav, because
	// they are not real BP components. We add them manually here.
	$bp_menu_items[] = array(
		'name' => __( 'Log Out', 'buddyboss' ),
		'slug' => 'logout',
		'link' => wp_logout_url(),
	);

	// If there's nothing to show, we're done.
	if ( count( $bp_menu_items ) < 1 ) {
		return false;
	}

	$page_args = array();

	foreach ( $bp_menu_items as $bp_item ) {

		$nav_counter = hexdec( uniqid() );

		// Remove <span>number</span>.
		$item_name = _bp_strip_spans_from_title( $bp_item['name'] );

		$page_args[ $bp_item['slug'] ] = (object) array(
			'ID'             => $nav_counter,
			'post_title'     => $item_name,
			'post_author'    => 0,
			'post_date'      => 0,
			'post_excerpt'   => $bp_item['slug'],
			'post_type'      => 'page',
			'post_status'    => 'publish',
			'comment_status' => 'closed',
			'guid'           => $bp_item['link'],
			'post_parent'    => 0,
		);

		$nav_sub = buddypress()->members->nav->get_secondary(
			array(
				'parent_slug' => $bp_item['slug'],
			)
		);

		if ( 'messages' === $bp_item['slug'] && bp_is_active( 'messages' ) ) {
			$page_args['compose-messages'] =
			(object) array(
				'ID'             => hexdec( uniqid() ),
				'post_title'     => __( 'New Messages', 'buddyboss' ),
				'object_id'      => hexdec( uniqid() ),
				'post_author'    => 0,
				'post_date'      => 0,
				'post_excerpt'   => 'compose-messages',
				'post_type'      => 'page',
				'post_status'    => 'publish',
				'comment_status' => 'closed',
				'guid'           => trailingslashit( bp_loggedin_user_domain() . bp_get_messages_slug() ) . 'compose',
				'post_parent'    => $nav_counter,
			);

			if ( bp_current_user_can( 'bp_moderate' ) ) {
				$page_args['site-notice'] = (object) array(
					'ID'             => hexdec( uniqid() ),
					'post_title'     => __( 'Site Notices', 'buddyboss' ),
					'object_id'      => hexdec( uniqid() ),
					'post_author'    => 0,
					'post_date'      => 0,
					'post_excerpt'   => 'site-notice',
					'post_type'      => 'page',
					'post_status'    => 'publish',
					'comment_status' => 'closed',
					'guid'           => admin_url( '/admin.php?page=bp-notices' ),
					'post_parent'    => $nav_counter,
				);
			}
		}

		if ( 'groups' === $bp_item['slug'] && bp_is_active( 'groups' ) && bp_user_can_create_groups() ) {
			$page_args['groups-create'] = (object) array(
				'ID'             => hexdec( uniqid() ),
				'post_title'     => __( 'Create Group', 'buddyboss' ),
				'object_id'      => hexdec( uniqid() ),
				'post_author'    => 0,
				'post_date'      => 0,
				'post_excerpt'   => 'groups-create',
				'post_type'      => 'page',
				'post_status'    => 'publish',
				'comment_status' => 'closed',
				'guid'           => trailingslashit( bp_get_groups_directory_permalink() . 'create' ),
				'post_parent'    => $nav_counter,
			);
		}

		if ( 'activity' === $bp_item['slug'] ) {
			$page_args['activity-posts'] = (object) array(
				'ID'             => hexdec( uniqid() ),
				'post_title'     => __( 'Posts', 'buddyboss' ),
				'object_id'      => hexdec( uniqid() ),
				'post_author'    => 0,
				'post_date'      => 0,
				'post_excerpt'   => 'activity-posts',
				'post_type'      => 'page',
				'post_status'    => 'publish',
				'comment_status' => 'closed',
				'guid'           => trailingslashit( bp_loggedin_user_domain() . bp_get_activity_slug() ),
				'post_parent'    => $nav_counter,
			);
		}

		if ( ! empty( $nav_sub ) ) {
			foreach ( $nav_sub as $s_nav ) {

				$sub_name          = preg_replace( '/^(.*)(<(.*)<\/(.*)>)/', '$1', $s_nav['name'] );
				$sub_name          = trim( $sub_name );
				$nav_counter_child = hexdec( uniqid() );

				$key = $s_nav['slug'];

				if ( in_array( $key, array( 'capabilities', 'delete-account' ), true ) ) {
					continue;
				}

				if ( 'settings' === $bp_item['slug'] && 'notifications' === $key ) {
					$key = 'settings-notifications';
				}

				if ( 'profile' === $key ) {
					$key = 'view';
				} elseif ( 'groups' === $bp_item['slug'] && 'invites' === $key ) {
					$key = 'group-invites';
				}

				if ( 'my-friends' === $s_nav['slug'] ) {
					$sub_name = __( 'My Connections', 'buddyboss' );
				}

				if ( 'my-document' === $s_nav['slug'] ) {
					$sub_name = __( 'My Documents', 'buddyboss' );
				}

				if ( 'my-media' === $s_nav['slug'] ) {
					$sub_name = __( 'My Photos', 'buddyboss' );
				}

				if ( 'my-video' === $s_nav['slug'] ) {
					$sub_name = __( 'My Videos', 'buddyboss' );
				}

				if ( 'my-courses' === $s_nav['slug'] ) {
					$sub_name = sprintf( __( 'My  %s', 'buddyboss' ), LearnDash_Custom_Label::get_label( 'courses' ) );
				}

				if ( 'settings' === $bp_item['slug'] && 'invites' === $s_nav['slug'] ) {
					$key = 'group-invites-settings';
				}

				$link                  = $s_nav['link'];
				$arr_key               = $key . '-sub';
				$page_args[ $arr_key ] =
					(object) array(
						'ID'             => $nav_counter_child,
						'post_title'     => $sub_name,
						'object_id'      => $nav_counter_child,
						'post_author'    => 0,
						'post_date'      => 0,
						'post_excerpt'   => $arr_key,
						'post_type'      => 'page',
						'post_status'    => 'publish',
						'comment_status' => 'closed',
						'guid'           => $link,
						'post_parent'    => $nav_counter,
					);
			}
		}

		if ( 'settings' === $bp_item['slug'] && ! bp_disable_account_deletion() ) {
			$page_args['delete-account'] = (object) array(
				'ID'             => hexdec( uniqid() ),
				'post_title'     => __( 'Delete Account', 'buddyboss' ),
				'object_id'      => hexdec( uniqid() ),
				'post_author'    => 0,
				'post_date'      => 0,
				'post_excerpt'   => 'delete-account',
				'post_type'      => 'page',
				'post_status'    => 'publish',
				'comment_status' => 'closed',
				'guid'           => trailingslashit( bp_loggedin_user_domain() . bp_get_settings_slug() . '/delete-account' ),
				'post_parent'    => $nav_counter,
			);
		}
	}

	if ( empty( buddypress()->wp_nav_menu_items ) ) {
		buddypress()->wp_nav_menu_items = new stdClass();
	}

	buddypress()->wp_nav_menu_items->loggedin = $page_args;

	return $page_args;
}

/**
 * Create fake "post" objects for BP's logged-out nav menu for use in the WordPress "Menus" settings page.
 *
 * WordPress nav menus work by representing post or tax term data as a custom
 * post type, which is then used to populate the checkboxes that appear on
 * Dashboard > Appearance > Menu as well as the menu as rendered on the front
 * end. Most of the items in the BuddyPress set of nav items are neither posts
 * nor tax terms, so we fake a post-like object so as to be compatible with the
 * menu.
 *
 * @since BuddyPress 1.9.0
 *
 * @return mixed A URL or an array of dummy pages.
 */
function bp_nav_menu_get_loggedout_pages() {

	// Try to catch the cached version first.
	if ( ! empty( buddypress()->wp_nav_menu_items->loggedout ) ) {
		return buddypress()->wp_nav_menu_items->loggedout;
	}

	$bp_menu_items = array();

	// Some BP nav menu items will not be represented in bp_nav, because
	// they are not real BP components. We add them manually here.
	$bp_menu_items[] = array(
		'name' => __( 'Log In', 'buddyboss' ),
		'slug' => 'login',
		'link' => wp_login_url(),
	);

	// The Register page will not always be available (ie, when
	// registration is disabled).
	$bp_directory_page_ids = bp_core_get_directory_page_ids();

	if ( ! empty( $bp_directory_page_ids['register'] ) ) {
		$register_page   = get_post( $bp_directory_page_ids['register'] );
		$bp_menu_items[] = array(
			'name' => $register_page->post_title,
			'slug' => 'register',
			'link' => get_permalink( $register_page->ID ),
		);
	}

	// If there's nothing to show, we're done.
	if ( count( $bp_menu_items ) < 1 ) {
		return false;
	}

	$page_args = array();

	foreach ( $bp_menu_items as $bp_item ) {
		$page_args[ $bp_item['slug'] ] = (object) array(
			'ID'             => -1,
			'post_title'     => $bp_item['name'],
			'post_author'    => 0,
			'post_date'      => 0,
			'post_excerpt'   => $bp_item['slug'],
			'post_type'      => 'page',
			'post_status'    => 'publish',
			'comment_status' => 'closed',
			'guid'           => $bp_item['link'],
		);
	}

	if ( empty( buddypress()->wp_nav_menu_items ) ) {
		buddypress()->wp_nav_menu_items = new stdClass();
	}

	buddypress()->wp_nav_menu_items->loggedout = $page_args;

	return $page_args;
}

/**
 * Get the URL for a BuddyPress WP nav menu item, based on slug.
 *
 * BuddyPress-specific WP nav menu items have dynamically generated URLs,
 * based on the identity of the current user. This function lets you fetch the
 * proper URL for a given nav item slug (such as 'login' or 'messages').
 *
 * @since BuddyPress 1.9.0
 *
 * @param string $slug The slug of the nav item: login, register, or one of the
 *                     slugs from the members navigation.
 * @return string $nav_item_url The URL generated for the current user.
 */
function bp_nav_menu_get_item_url( $slug ) {
	$nav_item_url   = '';
	$nav_menu_items = bp_nav_menu_get_loggedin_pages();

	if ( isset( $nav_menu_items[ $slug ] ) ) {
		$nav_item_url = $nav_menu_items[ $slug ]->guid;
	}

	return $nav_item_url;
}

/** Suggestions***************************************************************/

/**
 * BuddyPress Suggestions API for types of at-mentions.
 *
 * This is used to power BuddyPress' at-mentions suggestions, but it is flexible enough to be used
 * for similar kinds of future requirements, or those implemented by third-party developers.
 *
 * @since BuddyPress 2.1.0
 *
 * @param array $args Array of args for the suggestions.
 * @return array|WP_Error Array of results. If there were any problems, returns a WP_Error object.
 */
function bp_core_get_suggestions( $args ) {
	$args = bp_parse_args( $args, array(), 'get_suggestions' );

	if ( ! $args['type'] ) {
		return new WP_Error( 'missing_parameter' );
	}

	// Members @name suggestions.
	if ( 'members' === $args['type'] ) {
		$class = 'BP_Members_Suggestions';

		// Members @name suggestions for users in a specific Group.
		if ( isset( $args['group_id'] ) ) {
			$class = 'BP_Groups_Member_Suggestions';
		}
	} else {

		/**
		 * Filters the default suggestions service to use.
		 *
		 * Use this hook to tell BP the name of your class
		 * if you've built a custom suggestions service.
		 *
		 * @since BuddyPress 2.1.0
		 *
		 * @param string $value Custom class to use. Default: none.
		 * @param array  $args  Array of arguments for sugggestions.
		 */
		$class = apply_filters( 'bp_suggestions_services', '', $args );
	}

	if ( ! $class || ! class_exists( $class ) ) {
		return new WP_Error( 'missing_parameter' );
	}

	// Remove action for remove search against xprofile fields.
	remove_action( 'bp_user_query_uid_clauses', 'bp_xprofile_bp_user_query_search', 10, 2 );

	// Add action only for xprofile fields First, last and nickname.
	add_action( 'bp_user_query_uid_clauses', 'bb_xprofile_search_bp_user_query_search_first_last_nickname', 10, 2 );

	$suggestions = new $class( $args );
	$validation  = $suggestions->validate();

	if ( is_wp_error( $validation ) ) {
		$retval = $validation;
	} else {
		$retval = $suggestions->get_suggestions();
	}

	// Add action again for search against xprofile fields.
	add_action( 'bp_user_query_uid_clauses', 'bp_xprofile_bp_user_query_search', 10, 2 );

	// Removed action only for xprofile fields First, last and nickname.
	remove_action( 'bp_user_query_uid_clauses', 'bb_xprofile_search_bp_user_query_search_first_last_nickname', 10, 2 );
	/**
	 * Filters the available type of at-mentions.
	 *
	 * @since BuddyPress 2.1.0
	 *
	 * @param array|WP_Error $retval Array of results or WP_Error object.
	 * @param array          $args   Array of arguments for suggestions.
	 */
	return apply_filters( 'bp_core_get_suggestions', $retval, $args );
}

/**
 * Set data from the BP root blog's upload directory.
 *
 * Handy for multisite instances because all uploads are made on the BP root
 * blog and we need to query the BP root blog for the upload directory data.
 *
 * This function ensures that we only need to use {@link switch_to_blog()}
 * once to get what we need.
 *
 * @since BuddyPress 2.3.0
 *
 * @return bool|array
 */
function bp_upload_dir() {
	$bp = buddypress();

	if ( empty( $bp->upload_dir ) ) {
		$need_switch = (bool) ( is_multisite() && ! bp_is_root_blog() );

		// Maybe juggle to root blog.
		if ( true === $need_switch ) {
			switch_to_blog( bp_get_root_blog_id() );
		}

		// Get the upload directory (maybe for root blog).
		$wp_upload_dir = wp_upload_dir();

		// Maybe juggle back to current blog.
		if ( true === $need_switch ) {
			restore_current_blog();
		}

		// Bail if an error occurred.
		if ( ! empty( $wp_upload_dir['error'] ) ) {
			return false;
		}

		$bp->upload_dir = $wp_upload_dir;
	}

	return $bp->upload_dir;
}


/** Post Types *****************************************************************/

/**
 * Output the name of the email post type.
 *
 * @since BuddyPress 2.5.0
 */
function bp_email_post_type() {
	echo bp_get_email_post_type();
}
	/**
	 * Returns the name of the email post type.
	 *
	 * @since BuddyPress 2.5.0
	 *
	 * @return string The name of the email post type.
	 */
function bp_get_email_post_type() {

	/**
	 * Filters the name of the email post type.
	 *
	 * @since BuddyPress 2.5.0
	 *
	 * @param string $value Email post type name.
	 */
	return apply_filters( 'bp_get_email_post_type', buddypress()->email_post_type );
}

/**
 * Return labels used by the email post type.
 *
 * @since BuddyPress 2.5.0
 *
 * @return array
 */
function bp_get_email_post_type_labels() {

	/**
	 * Filters email post type labels.
	 *
	 * @since BuddyPress 2.5.0
	 *
	 * @param array $value Associative array (name => label).
	 */
	return apply_filters(
		'bp_get_email_post_type_labels',
		array(
			'add_new'               => __( 'New Email', 'buddyboss' ),
			'add_new_item'          => __( 'Add New Email', 'buddyboss' ),
			'all_items'             => __( 'All Emails', 'buddyboss' ),
			'edit_item'             => __( 'Edit Email', 'buddyboss' ),
			'filter_items_list'     => __( 'Filter email list', 'buddyboss' ),
			'items_list'            => __( 'Email list', 'buddyboss' ),
			'items_list_navigation' => __( 'Email list navigation', 'buddyboss' ),
			'menu_name'             => __( 'Emails', 'buddyboss' ),
			'name'                  => __( 'Email Templates', 'buddyboss' ),
			'new_item'              => __( 'New Email', 'buddyboss' ),
			'not_found'             => __( 'No emails found', 'buddyboss' ),
			'not_found_in_trash'    => __( 'No emails found in trash', 'buddyboss' ),
			'search_items'          => __( 'Search Emails', 'buddyboss' ),
			'singular_name'         => __( 'Email', 'buddyboss' ),
			'uploaded_to_this_item' => __( 'Uploaded to this email', 'buddyboss' ),
			'view_item'             => __( 'View Email', 'buddyboss' ),
		)
	);
}

/**
 * Return array of features that the email post type supports.
 *
 * @since BuddyPress 2.5.0
 *
 * @return array
 */
function bp_get_email_post_type_supports() {

	/**
	 * Filters the features that the email post type supports.
	 *
	 * @since BuddyPress 2.5.0
	 *
	 * @param array $value Supported features.
	 */
	return apply_filters(
		'bp_get_email_post_type_supports',
		array(
			'custom-fields',
			'editor',
			'excerpt',
			'revisions',
			'title',
		)
	);
}


/** Taxonomies *****************************************************************/

/**
 * Output the name of the email type taxonomy.
 *
 * @since BuddyPress 2.5.0
 */
function bp_email_tax_type() {
	echo bp_get_email_tax_type();
}
	/**
	 * Return the name of the email type taxonomy.
	 *
	 * @since BuddyPress 2.5.0
	 *
	 * @return string The unique email taxonomy type ID.
	 */
function bp_get_email_tax_type() {

	/**
	 * Filters the name of the email type taxonomy.
	 *
	 * @since BuddyPress 2.5.0
	 *
	 * @param string $value Email type taxonomy name.
	 */
	return apply_filters( 'bp_get_email_tax_type', buddypress()->email_taxonomy_type );
}

/**
 * Return labels used by the email type taxonomy.
 *
 * @since BuddyPress 2.5.0
 *
 * @return array
 */
function bp_get_email_tax_type_labels() {

	/**
	 * Filters email type taxonomy labels.
	 *
	 * @since BuddyPress 2.5.0
	 *
	 * @param array $value Associative array (name => label).
	 */
	return apply_filters(
		'bp_get_email_tax_type_labels',
		array(
			'add_new_item'          => __( 'New Email Situation', 'buddyboss' ),
			'all_items'             => __( 'All Email Situations', 'buddyboss' ),
			'edit_item'             => __( 'Edit Email Situations', 'buddyboss' ),
			'items_list'            => __( 'Email list', 'buddyboss' ),
			'items_list_navigation' => __( 'Email list navigation', 'buddyboss' ),
			'menu_name'             => __( 'Situations', 'buddyboss' ),
			'name'                  => __( 'Situation', 'buddyboss' ),
			'new_item_name'         => __( 'New email situation name', 'buddyboss' ),
			'not_found'             => __( 'No email situations found', 'buddyboss' ),
			'no_terms'              => __( 'No email situations', 'buddyboss' ),
			'popular_items'         => __( 'Popular Email Situation', 'buddyboss' ),
			'search_items'          => __( 'Search Emails', 'buddyboss' ),
			'singular_name'         => __( 'Email', 'buddyboss' ),
			'update_item'           => __( 'Update Email Situation', 'buddyboss' ),
			'view_item'             => __( 'View Email Situation', 'buddyboss' ),
		)
	);
}


/** Email *****************************************************************/

/**
 * Get an BP_Email object for the specified email type.
 *
 * This function pre-populates the object with the subject, content, and template from the appropriate
 * email post type item. It does not replace placeholder tokens in the content with real values.
 *
 * @since BuddyPress 2.5.0
 *
 * @param string $email_type Unique identifier for a particular type of email.
 * @return BP_Email|WP_Error BP_Email object, or WP_Error if there was a problem.
 */
function bp_get_email( $email_type ) {
	$switched = false;

	// Switch to the root blog, where the email posts live.
	if ( ! bp_is_root_blog() ) {
		switch_to_blog( bp_get_root_blog_id() );
		$switched = true;
	}

	$args = array(
		'no_found_rows'          => true,
		'numberposts'            => 1,
		'post_status'            => 'publish',
		'post_type'              => bp_get_email_post_type(),
		'suppress_filters'       => false,
		'update_post_meta_cache' => false,
		'update_post_term_cache' => false,

		'tax_query'              => array(
			array(
				'field'    => 'slug',
				'taxonomy' => bp_get_email_tax_type(),
				'terms'    => $email_type,
			),
		),
	);

	/**
	 * Filters arguments used to find an email post type object.
	 *
	 * @since BuddyPress 2.5.0
	 *
	 * @param array  $args       Arguments for get_posts() used to fetch a post object.
	 * @param string $email_type Unique identifier for a particular type of email.
	 */
	$args = apply_filters( 'bp_get_email_args', $args, $email_type );
	$post = get_posts( $args );
	if ( ! $post ) {
		if ( $switched ) {
			restore_current_blog();
		}

		return new WP_Error( 'missing_email', __FUNCTION__, array( $email_type, $args ) );
	}

	/**
	 * Filters arguments used to create the BP_Email object.
	 *
	 * @since BuddyPress 2.5.0
	 *
	 * @param WP_Post $post       Post object containing the contents of the email.
	 * @param string  $email_type Unique identifier for a particular type of email.
	 * @param array   $args       Arguments used with get_posts() to fetch a post object.
	 * @param WP_Post $post       All posts retrieved by get_posts( $args ). May only contain $post.
	 */
	$post  = apply_filters( 'bp_get_email_post', $post[0], $email_type, $args, $post );
	$email = new BP_Email( $email_type );

	/*
	 * Set some email properties for convenience.
	 */

	// Post object (sets subject, content, template).
	$email->set_post_object( $post );

	/**
	 * Filters the BP_Email object returned by bp_get_email().
	 *
	 * @since BuddyPress 2.5.0
	 *
	 * @param BP_Email $email      An object representing a single email, ready for mailing.
	 * @param string   $email_type Unique identifier for a particular type of email.
	 * @param array    $args       Arguments used with get_posts() to fetch a post object.
	 * @param WP_Post  $post       All posts retrieved by get_posts( $args ). May only contain $post.
	 */
	$retval = apply_filters( 'bp_get_email', $email, $email_type, $args, $post );

	if ( $switched ) {
		restore_current_blog();
	}

	return $retval;
}

/**
 * Send email, similar to WordPress' wp_mail().
 *
 * A true return value does not automatically mean that the user received the
 * email successfully. It just only means that the method used was able to
 * process the request without any errors.
 *
 * @since BuddyPress 2.5.0
 *
 * @param string                   $email_type Type of email being sent.
 * @param string|array|int|WP_User $to         Either an email address, user ID, WP_User object,
 *                                             or an array containg the address and name.
 * @param array                    $args {
 *     Optional. Array of extra parameters.
 *
 *     @type array $tokens Optional. Assocative arrays of string replacements for the email.
 * }
 * @return bool|WP_Error True if the email was sent successfully. Otherwise, a WP_Error object
 *                       describing why the email failed to send. The contents will vary based
 *                       on the email delivery class you are using.
 */
function bp_send_email( $email_type, $to, $args = array() ) {
	static $is_default_wpmail = null;
	static $wp_html_emails    = null;

	if ( function_exists( 'pmpro_wp_mail_content_type' ) ) {
		/**
		 * Removed Paid Memberships Pro plugin's filter.
		 */
		remove_filter( 'wp_mail_content_type', 'pmpro_wp_mail_content_type' );
	}

	// Has wp_mail() been filtered to send HTML emails?
	if ( is_null( $wp_html_emails ) ) {
		/** This filter is documented in wp-includes/pluggable.php */
		$wp_html_emails = apply_filters( 'wp_mail_content_type', 'text/plain' ) === 'text/html';
	}

	// Since wp_mail() is a pluggable function, has it been re-defined by another plugin?
	if ( is_null( $is_default_wpmail ) ) {
		try {
			$mirror            = new ReflectionFunction( 'wp_mail' );
			$is_default_wpmail = substr( $mirror->getFileName(), -strlen( 'pluggable.php' ) ) === 'pluggable.php';
		} catch ( Exception $e ) {
			$is_default_wpmail = true;
		}
	}

	$args = bp_parse_args(
		$args,
		array(
			'tokens' => array(),
		),
		'send_email'
	);

	/*
	 * Build the email.
	 */

	$email = bp_get_email( $email_type );
	if ( is_wp_error( $email ) ) {
		return $email;
	}

	// From, subject, content are set automatically.
	$email->set_to( $to );
	$email->set_tokens( $args['tokens'] );

	/**
	 * Gives access to an email before it is sent.
	 *
	 * @since BuddyPress 2.8.0
	 *
	 * @param BP_Email                 $email      The email (object) about to be sent.
	 * @param string                   $email_type Type of email being sent.
	 * @param string|array|int|WP_User $to         Either an email address, user ID, WP_User object,
	 *                                             or an array containg the address and name.
	 * @param array                    $args {
	 *     Optional. Array of extra parameters.
	 *
	 *     @type array $tokens Optional. Assocative arrays of string replacements for the email.
	 * }
	 */
	do_action_ref_array( 'bp_send_email', array( &$email, $email_type, $to, $args ) );

	$status = $email->validate();
	if ( is_wp_error( $status ) ) {
		return $status;
	}

	/**
	 * Filter this to skip BP's email handling and instead send everything to wp_mail().
	 *
	 * This is done if wp_mail_content_type() has been configured for HTML,
	 * or if wp_mail() has been redeclared (it's a pluggable function).
	 *
	 * @since BuddyPress 2.5.0
	 *
	 * @param bool $wp_html_emails || ! $is_default_wpmail $is_default_wpmail Whether to fallback to the regular wp_mail() function or not.
	 */
	$must_use_wpmail = apply_filters( 'bp_email_use_wp_mail', $wp_html_emails || ! $is_default_wpmail );

	/**
	 * Filter to forcefully use template
	 *
	 * This is done if wp_mail_content_type() has been configured for HTML,
	 * or if wp_mail() has been redeclared (it's a pluggable function).
	 *
	 * @since BuddyBoss 1.2.9
	 *
	 * @param bool true default fallback will be always true.
	 */
	$force_use_template = apply_filters( 'bp_email_force_use_templates', true );

	if ( $force_use_template ) {
		add_filter( 'wp_mail_content_type', 'bp_email_set_content_type' );
	}

	if ( $must_use_wpmail ) {

		$to = $email->get( 'to' );

		return wp_mail(
			array_shift( $to )->get_address(),
			$email->get( 'subject', 'replace-tokens' ),
			$force_use_template ?
				$email->get_template( 'add-content' ) :
				$email->get( 'content_plaintext', 'replace-tokens' )
		);
	}

	$must_use_bp_mail = apply_filters( 'bp_email_use_bp_mail', false );

	if ( $must_use_bp_mail ) {

		/*
		 * Send the email.
		 */

		/**
		 * Filter the email delivery class.
		 *
		 * Defaults to BP_PHPMailer, which as you can guess, implements PHPMailer.
		 *
		 * @param string $deliver_class The email delivery class name.
		 * @param string $email_type Type of email being sent.
		 * @param array|string $to Array or comma-separated list of email addresses to the email to.
		 * @param array $args {
		 *     Optional. Array of extra parameters.
		 *
		 * @type array $tokens Optional. Assocative arrays of string replacements for the email.
		 * }
		 * @since BuddyPress 2.5.0
		 */
		$delivery_class = apply_filters( 'bp_send_email_delivery_class', 'BP_PHPMailer', $email_type, $to, $args );
		if ( ! class_exists( $delivery_class ) ) {
			return new WP_Error( 'missing_class', 'No class found by that name', $delivery_class );
		}

		$delivery = new $delivery_class();
		$status   = $delivery->bp_email( $email );

		if ( is_wp_error( $status ) ) {

			/**
			 * Fires after BuddyPress has tried - and failed - to send an email.
			 *
			 * @param WP_Error $status A WP_Error object describing why the email failed to send. The contents
			 *                         will vary based on the email delivery class you are using.
			 * @param BP_Email $email The email we tried to send.
			 *
			 * @since BuddyPress 2.5.0
			 */
			do_action( 'bp_send_email_failure', $status, $email );

		} else {

			/**
			 * Fires after BuddyPress has succesfully sent an email.
			 *
			 * @param bool $status True if the email was sent successfully.
			 * @param BP_Email $email The email sent.
			 *
			 * @since BuddyPress 2.5.0
			 */
			do_action( 'bp_send_email_success', $status, $email );
		}

		return $status;

	} else {

		$to = $email->get( 'to' );

		return wp_mail(
			array_shift( $to )->get_address(),
			$email->get( 'subject', 'replace-tokens' ),
			$force_use_template ?
				$email->get_template( 'add-content' ) :
				$email->get( 'content_plaintext', 'replace-tokens' )
		);

	}
}

/**
 * Return email appearance settings.
 *
 * @since BuddyPress 2.5.0
 * @since BuddyPress 3.0.0 Added "direction" parameter for LTR/RTL email support, and
 *              "link_text_color" to override that in the email body.
 *
 * @return array
 */
function bp_email_get_appearance_settings() {
	$default_args = array(
		'body_bg'                   => '#FFFFFF',
		'quote_bg'                  => '#F7FAFE',
		'body_border_color'         => '#E7E9EC',
		'body_text_color'           => '#7F868F',
		'body_secondary_text_color' => '#122B46',
		'body_text_size'            => 16,
		'email_bg'                  => '#FAFBFD',
		'footer_text_color'         => '#7F868F',
		'footer_text_size'          => 12,
		'highlight_color'           => '#007CFF',
		'site_title_logo_size'      => 180,
		'site_title_text_color'     => '#122B46',
		'site_title_text_size'      => 20,
		'recipient_text_color'      => '#7F868F',
		'recipient_text_size'       => 14,
		'direction'                 => is_rtl() ? 'right' : 'left',

		'footer_text'               => sprintf(
			/* translators: email disclaimer, e.g. "© 2016 Site Name". */
			__( '&copy; %1$s %2$s', 'buddyboss' ),
			date_i18n( 'Y' ),
			bp_get_option( 'blogname' )
		),
	);

	$options = bp_parse_args(
		bp_get_option( 'bp_email_options', array() ),
		$default_args,
		'email_appearance_settings'
	);

	// Link text colour defaults to the highlight colour.
	if ( ! isset( $options['link_text_color'] ) ) {
		$options['link_text_color'] = $options['highlight_color'];
	}

	return $options;
}

/**
 * Get the paths to possible templates for the specified email object.
 *
 * @since BuddyPress 2.5.0
 *
 * @param WP_Post $object Post to get email template for.
 * @return array
 */
function bp_email_get_template( WP_Post $object ) {
	$single = "single-{$object->post_type}";

	/**
	 * Filter the possible template paths for the specified email object.
	 *
	 * @since BuddyPress 2.5.0
	 *
	 * @param array   $value  Array of possible template paths.
	 * @param WP_Post $object WP_Post object.
	 */
	return apply_filters(
		'bp_email_get_template',
		array(
			"assets/emails/{$single}-{$object->post_name}.php",
			"{$single}-{$object->post_name}.php",
			"{$single}.php",
			"assets/emails/{$single}.php",
		),
		$object
	);
}

/**
 * Replace all tokens in the input text with appropriate values.
 *
 * Intended for use with the email system introduced in BuddyPress 2.5.0.
 *
 * @since BuddyPress 2.5.0
 *
 * @param string $text   Text to replace tokens in.
 * @param array  $tokens Token names and replacement values for the $text.
 * @return string
 */
function bp_core_replace_tokens_in_text( $text, $tokens ) {
	$unescaped = array();
	$escaped   = array();

	foreach ( $tokens as $token => $value ) {
		if ( ! is_string( $value ) && is_callable( $value ) ) {
			$value = call_user_func( $value );
		}

		// Tokens could be objects or arrays.
		if ( ! is_scalar( $value ) ) {
			continue;
		}

		$unescaped[ '{{{' . $token . '}}}' ] = $value;
		$escaped[ '{{' . $token . '}}' ]     = esc_html( $value );
	}

	$text = strtr( $text, $unescaped );  // Do first.
	$text = strtr( $text, $escaped );

	/**
	 * Filters text that has had tokens replaced.
	 *
	 * @since BuddyPress 2.5.0
	 *
	 * @param string $text
	 * @param array $tokens Token names and replacement values for the $text.
	 */
	return apply_filters( 'bp_core_replace_tokens_in_text', $text, $tokens );
}

/**
 * Get a list of emails for populating the email post type.
 *
 * @since BuddyPress 2.5.1
 *
 * @return array
 */
function bp_email_get_schema() {

	$schema = array(
		'activity-comment'                   => array(
			/* translators: do not remove {} brackets or translate its contents. */
				'post_title'   => __( '[{{{site.name}}}] {{poster.name}} replied to one of your updates', 'buddyboss' ),
			/* translators: do not remove {} brackets or translate its contents. */
				'post_content' => __( "<a href=\"{{{poster.url}}}\">{{poster.name}}</a> replied to one of your updates:\n\n{{{activity_reply}}}", 'buddyboss' ),
			/* translators: do not remove {} brackets or translate its contents. */
				'post_excerpt' => __( "{{poster.name}} replied to one of your updates:\n\n{{{activity_reply}}}\n\nGo to the discussion to reply or catch up on the conversation: {{{thread.url}}}", 'buddyboss' ),
		),
		'activity-comment-author'            => array(
			/* translators: do not remove {} brackets or translate its contents. */
				'post_title'   => __( '[{{{site.name}}}] {{poster.name}} replied to one of your comments', 'buddyboss' ),
			/* translators: do not remove {} brackets or translate its contents. */
				'post_content' => __( "<a href=\"{{{poster.url}}}\">{{poster.name}}</a> replied to one of your comments:\n\n{{{activity_reply}}}", 'buddyboss' ),
			/* translators: do not remove {} brackets or translate its contents. */
				'post_excerpt' => __( "{{poster.name}} replied to one of your comments:\n\n{{{activity_reply}}}\n\nGo to the discussion to reply or catch up on the conversation: {{{thread.url}}}", 'buddyboss' ),
		),
		'activity-at-message'                => array(
			/* translators: do not remove {} brackets or translate its contents. */
				'post_title'   => __( '[{{{site.name}}}] {{poster.name}} mentioned you in a status update', 'buddyboss' ),
			/* translators: do not remove {} brackets or translate its contents. */
				'post_content' => __( "<a href=\"{{{poster.url}}}\">{{poster.name}}</a> mentioned you in a status update:\n\n{{{status_update}}}", 'buddyboss' ),
			/* translators: do not remove {} brackets or translate its contents. */
				'post_excerpt' => __( "{{poster.name}} mentioned you in a status update:\n\n{{{status_update}}}\n\nGo to the discussion to reply or catch up on the conversation: {{{mentioned.url}}}", 'buddyboss' ),
		),
		'groups-at-message'                  => array(
			/* translators: do not remove {} brackets or translate its contents. */
				'post_title'   => __( '[{{{site.name}}}] {{poster.name}} mentioned you in a group update', 'buddyboss' ),
			/* translators: do not remove {} brackets or translate its contents. */
				'post_content' => __( "<a href=\"{{{poster.url}}}\">{{poster.name}}</a> mentioned you in the group \"<a href=\"{{{group.url}}}\">{{group.name}}</a>\":\n\n{{{status_update}}}", 'buddyboss' ),
			/* translators: do not remove {} brackets or translate its contents. */
				'post_excerpt' => __( "{{poster.name}} mentioned you in the group \"{{group.name}}\":\n\n{{{status_update}}}\n\nGo to the discussion to reply or catch up on the conversation: {{{mentioned.url}}}", 'buddyboss' ),
		),
		'core-user-registration'             => array(
			/* translators: do not remove {} brackets or translate its contents. */
				'post_title'   => __( '[{{{site.name}}}] Activate your account', 'buddyboss' ),
			/* translators: do not remove {} brackets or translate its contents. */
				'post_content' => __( "Thanks for registering!\n\nTo complete the activation of your account, go to the following link: <a href=\"{{{activate.url}}}\">{{{activate.url}}}</a>", 'buddyboss' ),
			/* translators: do not remove {} brackets or translate its contents. */
				'post_excerpt' => __( "Thanks for registering!\n\nTo complete the activation of your account, go to the following link: {{{activate.url}}}", 'buddyboss' ),
		),
		'core-user-registration-with-blog'   => array(
			/* translators: do not remove {} brackets or translate its contents. */
				'post_title'   => __( '[{{{site.name}}}] Activate {{{user-site.url}}}', 'buddyboss' ),
			/* translators: do not remove {} brackets or translate its contents. */
				'post_content' => __( "Thanks for registering!\n\nTo complete the activation of your account and site, go to the following link: <a href=\"{{{activate-site.url}}}\">{{{activate-site.url}}}</a>.\n\nAfter you activate, you can visit your site at <a href=\"{{{user-site.url}}}\">{{{user-site.url}}}</a>.", 'buddyboss' ),
			/* translators: do not remove {} brackets or translate its contents. */
				'post_excerpt' => __( "Thanks for registering!\n\nTo complete the activation of your account and site, go to the following link: {{{activate-site.url}}}\n\nAfter you activate, you can visit your site at {{{user-site.url}}}.", 'buddyboss' ),
			'args'             => array(
				'multisite' => true,
			),
		),
		'friends-request'                    => array(
			/* translators: do not remove {} brackets or translate its contents. */
				'post_title'   => __( '[{{{site.name}}}] New request to connect from {{initiator.name}}', 'buddyboss' ),
			/* translators: do not remove {} brackets or translate its contents. */
				'post_content' => __( "<a href=\"{{{initiator.url}}}\">{{initiator.name}}</a> wants to add you as a connection.\n\n{{{member.card}}}\n\n<a href=\"{{{friend-requests.url}}}\">Click here</a> to manage this and all other pending requests.", 'buddyboss' ),
			/* translators: do not remove {} brackets or translate its contents. */
				'post_excerpt' => __( "{{initiator.name}} wants to add you as a connection.\n\nTo accept this request and manage all of your pending requests, visit: {{{friend-requests.url}}}\n\nTo view {{initiator.name}}'s profile, visit: {{{initiator.url}}}", 'buddyboss' ),
		),
		'friends-request-accepted'           => array(
			/* translators: do not remove {} brackets or translate its contents. */
				'post_title'   => __( '[{{{site.name}}}] {{friend.name}} accepted your request to connect', 'buddyboss' ),
			/* translators: do not remove {} brackets or translate its contents. */
				'post_content' => __( "<a href=\"{{{friendship.url}}}\">{{friend.name}}</a> accepted your request to connect.\n\n{{{member.card}}}", 'buddyboss' ),
			/* translators: do not remove {} brackets or translate its contents. */
				'post_excerpt' => __( "{{friend.name}} accepted your friend request.\n\nTo learn more about them, visit their profile: {{{friendship.url}}}", 'buddyboss' ),
		),
		'groups-details-updated'             => array(
			/* translators: do not remove {} brackets or translate its contents. */
				'post_title'   => __( '[{{{site.name}}}] Group details updated', 'buddyboss' ),
			/* translators: do not remove {} brackets or translate its contents. */
				'post_content' => __( "Group details for the group &quot;<a href=\"{{{group.url}}}\">{{group.name}}</a>&quot; were updated.\n\n{{{group.description}}}\n\n{{{group.small_card}}}", 'buddyboss' ),
			/* translators: do not remove {} brackets or translate its contents. */
				'post_excerpt' => __( "Group details for the group \"{{group.name}}\" were updated:\n\n{{changed_text}}\n\nTo view the group, visit: {{{group.url}}}", 'buddyboss' ),
		),
		'groups-invitation'                  => array(
			/* translators: do not remove {} brackets or translate its contents. */
				'post_title'   => __( '[{{{site.name}}}] You have an invitation to the group: "{{group.name}}"', 'buddyboss' ),
			/* translators: do not remove {} brackets or translate its contents. */
				'post_content' => __( "<a href=\"{{{inviter.url}}}\">{{inviter.name}}</a> has invited you to join the group: <a href=\"{{{group.url}}}\">{{group.name}}</a>.\n\n{{{group.invite_message}}}\n\n{{{group.small_card}}}\n\n<a href=\"{{{invites.url}}}\">Click here</a> to manage this and all other pending group invites.", 'buddyboss' ),
			/* translators: do not remove {} brackets or translate its contents. */
				'post_excerpt' => __( "{{inviter.name}} has invited you to join the group: \"{{group.name}}\".\n\n{{{group.invite_message}}}\n\nTo accept your invitation, visit: {{{invites.url}}}\n\nTo learn more about the group, visit: {{{group.url}}}.\nTo view {{inviter.name}}'s profile, visit: {{{inviter.url}}}", 'buddyboss' ),
		),
		'groups-member-promoted'             => array(
			/* translators: do not remove {} brackets or translate its contents. */
				'post_title'   => __( '[{{{site.name}}}] You have been promoted in the group: "{{group.name}}"', 'buddyboss' ),
			/* translators: do not remove {} brackets or translate its contents. */
				'post_content' => __( "You have been promoted to <b>{{promoted_to}}</b> in the group &quot;<a href=\"{{{group.url}}}\">{{group.name}}</a>&quot;.\n\n{{{group.small_card}}}", 'buddyboss' ),
			/* translators: do not remove {} brackets or translate its contents. */
				'post_excerpt' => __( "You have been promoted to {{promoted_to}} in the group: \"{{group.name}}\".\n\nTo visit the group, go to: {{{group.url}}}", 'buddyboss' ),
		),
		'groups-membership-request'          => array(
			/* translators: do not remove {} brackets or translate its contents. */
				'post_title'   => __( '[{{{site.name}}}] Membership request for group: {{group.name}}', 'buddyboss' ),
			/* translators: do not remove {} brackets or translate its contents. */
				'post_content' => __( "<a href=\"{{{profile.url}}}\">{{requesting-user.name}}</a> wants to join the group &quot;<a href=\"{{{group.url}}}\">{{group.name}}</a>&quot;. As you are organizer of this group, you must either accept or reject the membership request.\n\n{{{member.card}}}\n\n<a href=\"{{{group-requests.url}}}\">Click here</a> to manage this and all other pending requests.", 'buddyboss' ),
			/* translators: do not remove {} brackets or translate its contents. */
				'post_excerpt' => __( "{{requesting-user.name}} wants to join the group \"{{group.name}}\". As you are the organizer of this group, you must either accept or reject the membership request.\n\nTo manage this and all other pending requests, visit: {{{group-requests.url}}}\n\nTo view {{requesting-user.name}}'s profile, visit: {{{profile.url}}}", 'buddyboss' ),
		),
		'messages-unread'                    => array(
			/* translators: do not remove {} brackets or translate its contents. */
				'post_title'   => __( '[{{{site.name}}}] New message from {{sender.name}}', 'buddyboss' ),
			/* translators: do not remove {} brackets or translate its contents. */
				'post_content' => __( "{{sender.name}} sent you a new message.\n\n{{{message}}}", 'buddyboss' ),
			/* translators: do not remove {} brackets or translate its contents. */
				'post_excerpt' => __( "{{sender.name}} sent you a new message.\n\n{{{message}}}\"\n\nGo to the discussion to reply or catch up on the conversation: {{{message.url}}}", 'buddyboss' ),
		),
		'settings-verify-email-change'       => array(
			/* translators: do not remove {} brackets or translate its contents. */
				'post_title'   => __( '[{{{site.name}}}] Verify your new email address', 'buddyboss' ),
			/* translators: do not remove {} brackets or translate its contents. */
				'post_content' => __( "You recently changed the email address associated with your account on {{site.name}} to {{user.email}}. If this is correct, <a href=\"{{{verify.url}}}\">click here</a> to confirm the change. \n\nOtherwise, you can safely ignore and delete this email if you have changed your mind, or if you think you have received this email in error.", 'buddyboss' ),
			/* translators: do not remove {} brackets or translate its contents. */
				'post_excerpt' => __( "You recently changed the email address associated with your account on {{site.name}} to {{user.email}}. If this is correct, go to the following link to confirm the change: {{{verify.url}}}\n\nOtherwise, you can safely ignore and delete this email if you have changed your mind, or if you think you have received this email in error.", 'buddyboss' ),
		),
		'groups-membership-request-accepted' => array(
			/* translators: do not remove {} brackets or translate its contents. */
				'post_title'   => __( '[{{{site.name}}}] Membership request for group "{{group.name}}" accepted', 'buddyboss' ),
			/* translators: do not remove {} brackets or translate its contents. */
				'post_content' => __( "Your membership request for the group &quot;<a href=\"{{{group.url}}}\">{{group.name}}</a>&quot; has been accepted.\n\n{{{group.small_card}}}", 'buddyboss' ),
			/* translators: do not remove {} brackets or translate its contents. */
				'post_excerpt' => __( "Your membership request for the group \"{{group.name}}\" has been accepted.\n\nTo view the group, visit: {{{group.url}}}", 'buddyboss' ),
		),
		'groups-membership-request-rejected' => array(
			/* translators: do not remove {} brackets or translate its contents. */
				'post_title'   => __( '[{{{site.name}}}] Membership request for group "{{group.name}}" rejected', 'buddyboss' ),
			/* translators: do not remove {} brackets or translate its contents. */
				'post_content' => __( "Your membership request for the group &quot;<a href=\"{{{group.url}}}\">{{group.name}}</a>&quot; has been rejected.\n\n{{{group.small_card}}}", 'buddyboss' ),
			/* translators: do not remove {} brackets or translate its contents. */
				'post_excerpt' => __( "Your membership request for the group \"{{group.name}}\" has been rejected.\n\nTo request membership again, visit: {{{group.url}}}", 'buddyboss' ),
		),
		'bbp-new-forum-topic'                => array(
			/* translators: do not remove {} brackets or translate its contents. */
				'post_title'   => __( '[{{{site.name}}}] New discussion: {{discussion.title}}', 'buddyboss' ),
			/* translators: do not remove {} brackets or translate its contents. */
				'post_content' => __( "{{poster.name}} started a new discussion <a href=\"{{discussion.url}}\">{{discussion.title}}</a> in the forum <a href=\"{{forum.url}}\">{{forum.title}}</a>:\n\n{{{discussion.content}}}", 'buddyboss' ),
			/* translators: do not remove {} brackets or translate its contents. */
				'post_excerpt' => __( "{{poster.name}} started a new discussion {{discussion.title}} in the forum {{forum.title}}:\n\n{{{discussion.content}}}\n\nDiscussion Link: {{discussion.url}}", 'buddyboss' ),
		),
		'bbp-new-forum-reply'                => array(
			/* translators: do not remove {} brackets or translate its contents. */
				'post_title'   => __( '[{{{site.name}}}] {{poster.name}} replied to one of your forum discussions', 'buddyboss' ),
			/* translators: do not remove {} brackets or translate its contents. */
				'post_content' => __( "{{poster.name}} replied to the discussion <a href=\"{{discussion.url}}\">{{discussion.title}}</a> in the forum <a href=\"{{forum.url}}\">{{forum.title}}</a>:\n\n{{{reply.content}}}", 'buddyboss' ),
			/* translators: do not remove {} brackets or translate its contents. */
				'post_excerpt' => __( "{{poster.name}} replied to the discussion {{discussion.title}} in the forum {{forum.title}}:\n\n{{{reply.content}}}\n\nPost Link: {{reply.url}}", 'buddyboss' ),
		),
		'invites-member-invite'              => array(
			/* translators: do not remove {} brackets or translate its contents. */
				'post_title'   => __( 'An invitation from {{inviter.name}} to join [{{{site.name}}}]', 'buddyboss' ),
			/* translators: do not remove {} brackets or translate its contents. */
				'post_content' => __( 'You have been invited by {{inviter.name}} to join the <a href="{{{site.url}}}">[{{{site.name}}}]</a> community.', 'buddyboss' ),
			/* translators: do not remove {} brackets or translate its contents. */
				'post_excerpt' => __( 'You have been invited by {{inviter.name}} to join the [{{{site.name}}}] community.', 'buddyboss' ),
		),
		'group-message-email'                => array(
			/* translators: do not remove {} brackets or translate its contents. */
				'post_title'   => __( '[{{{site.name}}}] New message from group: "{{group.name}}"', 'buddyboss' ),
			/* translators: do not remove {} brackets or translate its contents. */
				'post_content' => __( "{{sender.name}} from {{group.name}} sent you a new message.\n\n{{{message}}}", 'buddyboss' ),
			/* translators: do not remove {} brackets or translate its contents. */
				'post_excerpt' => __( "{{sender.name}} from {{group.name}} sent you a new message.\n\n{{{message}}}\"\n\nGo to the discussion to reply or catch up on the conversation: {{{message.url}}}", 'buddyboss' ),
		),
		'content-moderation-email'           => array(
			/* translators: do not remove {} brackets or translate its contents. */
			'post_title'   => __( '[{{{site.name}}}] Content has been automatically hidden', 'buddyboss' ),
			/* translators: do not remove {} brackets or translate its contents. */
			'post_content' => __( "<a href='{{{content.link}}}'>{{content.type}}</a> has been automatically hidden from your network as it has been reported {{timesreported}} time(s). \n\n <a href='{{{reportlink}}}' style='color: #007CFF;font-size: 14px;text-decoration: none;border: 1px solid #007CFF;border-radius: 100px;min-width: 64px;text-align: center;height: 16px;line-height: 16px;padding: 8px 12px; display: inline-block;'>View Reports</a>", 'buddyboss' ),
			/* translators: do not remove {} brackets or translate its contents. */
			'post_excerpt' => __( "{{content.type}} [{{content.link}}] has been automatically hidden from your network as it has been reported {{timesreported}} time(s). \n\n View Reports: {{reportlink}}", 'buddyboss' ),
		),
		'user-moderation-email'              => array(
			/* translators: do not remove {} brackets or translate its contents. */
			'post_title'   => __( '[{{{site.name}}}] {{user.name}} has been suspended', 'buddyboss' ),
			/* translators: do not remove {} brackets or translate its contents. */
			'post_content' => __( "<a href='{{{user.link}}}'>{{user.name}}</a> has been automatically suspended from your network as they have been reported {{timesblocked}} time(s). \n\n <a href='{{{reportlink}}}' style='font-size: 14px;color: #007CFF;text-decoration: none;border: 1px solid #007CFF;border-radius: 100px;min-width: 64px;text-align: center;height: 16px;line-height: 16px;padding: 8px 12px;display: inline-block;'>View Reports</a>", 'buddyboss' ),
			/* translators: do not remove {} brackets or translate its contents. */
			'post_excerpt' => __( "{{user.name}} [{{user.link}}] has been automatically suspended from your network as they have been reported {{user.timesblocked}} time(s). \n\n View Reports: {{reportlink}}", 'buddyboss' ),
		),
	);

	/**
	 * Filters registered email schema.
	 *
	 * @param array $schema Email schema.
	 *
	 * @returns array $schema Email schema array.
	 * @since BuddyBoss 1.5.4
	 */
	return apply_filters( 'bp_email_get_schema', $schema );
}

/**
 * Get a list of emails for populating email type taxonomy terms.
 *
 * @since BuddyPress 2.5.1
 * @since BuddyPress 2.7.0 $field argument added.
 *
 * @param string $field Optional; defaults to "description" for backwards compatibility. Other values: "all".
 * @return array {
 *     The array of email types and their schema.
 *
 *     @type string $description The description of the action which causes this to trigger.
 *     @type array  $unsubscribe {
 *         Replacing this with false indicates that a user cannot unsubscribe from this type.
 *
 *         @type string $meta_key The meta_key used to toggle the email setting for this notification.
 *         @type string $message  The message shown when the user has successfully unsubscribed.
 *     }
 * @todo check if these can be deprecated
 */
function bp_email_get_type_schema( $field = 'description' ) {
	$activity_comment = array(
		'description' => __( 'A member has replied to an activity update that the recipient posted.', 'buddyboss' ),
		'unsubscribe' => array(
			'meta_key' => 'notification_activity_new_reply',
			'message'  => __( 'You will no longer receive emails when someone replies to an update or comment you posted.', 'buddyboss' ),
		),
	);

	$activity_comment_author = array(
		'description' => __( 'A member has replied to a comment on an activity update that the recipient posted.', 'buddyboss' ),
		'unsubscribe' => array(
			'meta_key' => 'notification_activity_new_reply',
			'message'  => __( 'You will no longer receive emails when someone replies to an update or comment you posted.', 'buddyboss' ),
		),
	);

	$activity_at_message = array(
		'description' => __( 'Recipient was mentioned in an activity update.', 'buddyboss' ),
		'unsubscribe' => array(
			'meta_key' => 'notification_activity_new_mention',
			'message'  => __( 'You will no longer receive emails when someone mentions you in an update.', 'buddyboss' ),
		),
	);

	$groups_at_message = array(
		'description' => __( 'Recipient was mentioned in a group activity update.', 'buddyboss' ),
		'unsubscribe' => array(
			'meta_key' => 'notification_activity_new_mention',
			'message'  => __( 'You will no longer receive emails when someone mentions you in an update.', 'buddyboss' ),
		),
	);

	$core_user_registration = array(
		'description' => __( 'Recipient has registered for an account.', 'buddyboss' ),
		'unsubscribe' => false,
	);

	$core_user_registration_with_blog = array(
		'description' => __( 'Recipient has registered for an account and site.', 'buddyboss' ),
		'unsubscribe' => false,
	);

	$friends_request = array(
		'description' => __( 'A member has sent an invitation to connect to the recipient.', 'buddyboss' ),
		'unsubscribe' => array(
			'meta_key' => 'notification_friends_friendship_request',
			'message'  => __( 'You will no longer receive emails when someone sends you an invitation to connect.', 'buddyboss' ),
		),
	);

	$friends_request_accepted = array(
		'description' => __( 'Recipient has had an invitation to connect accepted by a member.', 'buddyboss' ),
		'unsubscribe' => array(
			'meta_key' => 'notification_friends_friendship_accepted',
			'message'  => __( 'You will no longer receive emails when someone accepts your invitation to connect.', 'buddyboss' ),
		),
	);

	$groups_details_updated = array(
		'description' => __( "A group's details were updated.", 'buddyboss' ),
		'unsubscribe' => array(
			'meta_key' => 'notification_groups_group_updated',
			'message'  => __( 'You will no longer receive emails when one of your groups is updated.', 'buddyboss' ),
		),
	);

	$groups_invitation = array(
		'description' => __( 'A member has sent a group invitation to the recipient.', 'buddyboss' ),
		'unsubscribe' => array(
			'meta_key' => 'notification_groups_invite',
			'message'  => __( 'You will no longer receive emails when you are invited to join a group.', 'buddyboss' ),
		),
	);

	$groups_member_promoted = array(
		'description' => __( "Recipient's status within a group has changed.", 'buddyboss' ),
		'unsubscribe' => array(
			'meta_key' => 'notification_groups_admin_promotion',
			'message'  => __( 'You will no longer receive emails when you have been promoted in a group.', 'buddyboss' ),
		),
	);

	$groups_membership_request = array(
		'description' => __( 'A member has requested permission to join a group.', 'buddyboss' ),
		'unsubscribe' => array(
			'meta_key' => 'notification_groups_membership_request',
			'message'  => __( 'You will no longer receive emails when someone requests to be a member of your group.', 'buddyboss' ),
		),
	);

	$messages_unread = array(
		'description' => __( 'Recipient has received a private message.', 'buddyboss' ),
		'unsubscribe' => array(
			'meta_key' => 'notification_messages_new_message',
			'message'  => __( 'You will no longer receive emails when someone sends you a message.', 'buddyboss' ),
		),
	);

	$settings_verify_email_change = array(
		'description' => __( 'Recipient has changed their email address.', 'buddyboss' ),
		'unsubscribe' => false,
	);

	$groups_membership_request_accepted = array(
		'description' => __( 'Recipient had requested to join a group, which was accepted.', 'buddyboss' ),
		'unsubscribe' => array(
			'meta_key' => 'notification_membership_request_completed',
			'message'  => __( 'You will no longer receive emails when your request to join a group has been accepted or denied.', 'buddyboss' ),
		),
	);

	$groups_membership_request_rejected = array(
		'description' => __( 'Recipient had requested to join a group, which was rejected.', 'buddyboss' ),
		'unsubscribe' => array(
			'meta_key' => 'notification_membership_request_completed',
			'message'  => __( 'You will no longer receive emails when your request to join a group has been accepted or denied.', 'buddyboss' ),
		),
	);

	$bbp_new_forum_topic = array(
		'description' => __( 'A member has created a new forum discussion.', 'buddyboss' ),
		'unsubscribe' => array(
			'meta_key' => 'notification_bbp_new_forum_topic',
			'message'  => __( 'You will no longer receive emails when a member will create a new forum discussion.', 'buddyboss' ),
		),
	);

	$bbp_new_forum_reply = array(
		'description' => __( 'A member replies to a discussion you are subscribed to.', 'buddyboss' ),
		'unsubscribe' => array(
			'meta_key' => 'notification_bbp_new_forum_reply',
			'message'  => __( 'You will no longer receive emails when a member will reply to one of your forum discussions.', 'buddyboss' ),
		),
	);

	$invites_member_invite = array(
		'description' => __( 'Recipient has been invited by a member to join the website.', 'buddyboss' ),
		'unsubscribe' => false,
	);

	$group_message_email = array(
		'description' => __( 'Recipient has received a group message.', 'buddyboss' ),
		'unsubscribe' => array(
			'meta_key' => 'notification_group_messages_new_message',
			'message'  => __( 'You will no longer receive emails when someone sends you a group message.', 'buddyboss' ),
		),
	);

	$content_moderation_email = array(
		'description' => __( 'When content is automatically hidden due to reaching the reporting threshold.', 'buddyboss' ), // Todo: Add proper description of email.
		'unsubscribe' => false,
	);

	$user_moderation_email = array(
		'description' => __( 'When a member has been automatically suspended due to reaching the reporting threshold.', 'buddyboss' ), // Todo: Add proper description of email.
		'unsubscribe' => false,
	);

	$types = array(
		'activity-comment'                   => $activity_comment,
		'activity-comment-author'            => $activity_comment_author,
		'activity-at-message'                => $activity_at_message,
		'groups-at-message'                  => $groups_at_message,
		'core-user-registration'             => $core_user_registration,
		'core-user-registration-with-blog'   => $core_user_registration_with_blog,
		'friends-request'                    => $friends_request,
		'friends-request-accepted'           => $friends_request_accepted,
		'groups-details-updated'             => $groups_details_updated,
		'groups-invitation'                  => $groups_invitation,
		'groups-member-promoted'             => $groups_member_promoted,
		'groups-membership-request'          => $groups_membership_request,
		'messages-unread'                    => $messages_unread,
		'settings-verify-email-change'       => $settings_verify_email_change,
		'groups-membership-request-accepted' => $groups_membership_request_accepted,
		'groups-membership-request-rejected' => $groups_membership_request_rejected,
		'bbp-new-forum-topic'                => $bbp_new_forum_topic,
		'bbp-new-forum-reply'                => $bbp_new_forum_reply,
		'invites-member-invite'              => $invites_member_invite,
		'group-message-email'                => $group_message_email,
		'content-moderation-email'           => $content_moderation_email,
		'user-moderation-email'              => $user_moderation_email,
	);

	/**
	 * Filters Email type schema
	 *
	 * @param array $types Types array.
	 *
	 * @since BuddyBoss 1.5.4
	 */
	$types = apply_filters( 'bp_email_get_type_schema', $types );

	if ( $field !== 'all' ) {
		return wp_list_pluck( $types, $field );
	} else {
		return $types;
	}
}

/**
 * Handles unsubscribing user from notification emails.
 *
 * @since BuddyPress 2.7.0
 */
function bp_email_unsubscribe_handler() {
	$emails         = bp_email_get_unsubscribe_type_schema();
	$raw_email_type = ! empty( $_GET['nt'] ) ? $_GET['nt'] : '';
	$raw_hash       = ! empty( $_GET['nh'] ) ? $_GET['nh'] : '';
	$raw_user_id    = ! empty( $_GET['uid'] ) ? absint( $_GET['uid'] ) : 0;
	$new_hash       = hash_hmac( 'sha1', "{$raw_email_type}:{$raw_user_id}", bp_email_get_salt() );

	// Check required values.
	if ( ! $raw_user_id || ! $raw_email_type || ! $raw_hash || ! array_key_exists( $raw_email_type, $emails ) ) {
		$redirect_to = wp_login_url();
		$result_msg  = __( 'Something has gone wrong.', 'buddyboss' );
		$unsub_msg   = __( 'Please log in and go to your settings to unsubscribe from notification emails.', 'buddyboss' );

		// Check valid hash.
	} elseif ( ! hash_equals( $new_hash, $raw_hash ) ) {
		$redirect_to = wp_login_url();
		$result_msg  = __( 'Something has gone wrong.', 'buddyboss' );
		$unsub_msg   = __( 'Please log in and go to your settings to unsubscribe from notification emails.', 'buddyboss' );

		// Don't let authenticated users unsubscribe other users' email notifications.
	} elseif ( is_user_logged_in() && get_current_user_id() !== $raw_user_id ) {
		$result_msg = __( 'Something has gone wrong.', 'buddyboss' );
		$unsub_msg  = __( 'Please go to your notifications settings to unsubscribe from emails.', 'buddyboss' );

		if ( bp_is_active( 'settings' ) ) {
			$redirect_to = sprintf(
				'%s%s/notifications/',
				bp_core_get_user_domain( get_current_user_id() ),
				bp_get_settings_slug()
			);
		} else {
			$redirect_to = bp_core_get_user_domain( get_current_user_id() );
		}
	} else {
		if ( bp_is_active( 'settings' ) ) {
			$redirect_to = sprintf(
				'%s%s/notifications/',
				bp_core_get_user_domain( $raw_user_id ),
				bp_get_settings_slug()
			);
		} else {
			$redirect_to = bp_core_get_user_domain( $raw_user_id );
		}

		// Unsubscribe.
		$meta_key = $emails[ $raw_email_type ]['unsubscribe']['meta_key'];
		bp_update_user_meta( $raw_user_id, $meta_key, 'no' );

		$result_msg = $emails[ $raw_email_type ]['unsubscribe']['message'];
		$unsub_msg  = __( 'You can change this or any other email notification preferences in your email settings.', 'buddyboss' );
	}

	$message = sprintf(
		'%1$s <a href="%2$s">%3$s</a>',
		$result_msg,
		esc_url( $redirect_to ),
		esc_html( $unsub_msg )
	);

	bp_core_add_message( $message );
	bp_core_redirect( bp_core_get_user_domain( $raw_user_id ) );

	exit;
}

/**
 * Creates unsubscribe link for notification emails.
 *
 * @since BuddyPress 2.7.0
 *
 * @param string $redirect_to The URL to which the unsubscribe query string is appended.
 * @param array  $args {
 *     Used to build unsubscribe query string.
 *
 *    @type string $notification_type Which notification type is being sent.
 *    @type string $user_id           The ID of the user to whom the notification is sent.
 *    @type string $redirect_to       Optional. The url to which the user will be redirected. Default is the activity directory.
 * }
 * @return string The unsubscribe link.
 */
function bp_email_get_unsubscribe_link( $args ) {
	$emails = bp_email_get_unsubscribe_type_schema();

	if ( empty( $args['notification_type'] ) || ! array_key_exists( $args['notification_type'], $emails ) ) {
		return wp_login_url();
	}

	$email_type  = $args['notification_type'];
	$redirect_to = ! empty( $args['redirect_to'] ) ? $args['redirect_to'] : site_url();
	$user_id     = (int) $args['user_id'];

	// Bail out if the activity type is not un-unsubscribable.
	if ( empty( $emails[ $email_type ]['unsubscribe'] ) ) {
		return '';
	}

	$link = add_query_arg(
		array(
			'action' => 'unsubscribe',
			'nh'     => hash_hmac( 'sha1', "{$email_type}:{$user_id}", bp_email_get_salt() ),
			'nt'     => $args['notification_type'],
			'uid'    => $user_id,
		),
		$redirect_to
	);

	/**
	 * Filters the unsubscribe link.
	 *
	 * @since BuddyPress 2.7.0
	 */
	return apply_filters( 'bp_email_get_link', $link, $redirect_to, $args );
}

/**
 * Get a persistent salt for email unsubscribe links.
 *
 * @since BuddyPress 2.7.0
 *
 * @return string|null Returns null if value isn't set, otherwise string.
 */
function bp_email_get_salt() {
	return bp_get_option( 'bp-emails-unsubscribe-salt', null );
}

/**
 * Get a list of emails for use in our unsubscribe functions.
 *
 * @since BuddyPress 2.8.0
 *
 * @see https://buddypress.trac.wordpress.org/ticket/7431
 *
 * @return array The array of email types and their schema.
 */
function bp_email_get_unsubscribe_type_schema() {
	$emails = bp_email_get_type_schema( 'all' );

	/**
	 * Filters the return of `bp_email_get_type_schema( 'all' )` for use with
	 * our unsubscribe functionality.
	 *
	 * @since BuddyPress 2.8.0
	 *
	 * @param array $emails The array of email types and their schema.
	 */
	return (array) apply_filters( 'bp_email_get_unsubscribe_type_schema', $emails );
}

/**
 * Get BuddyPress content allowed tags.
 *
 * @since BuddyPress  3.0.0
 *
 * @global array $allowedtags KSES allowed HTML elements.
 * @return array              BuddyPress content allowed tags.
 */
function bp_get_allowedtags() {
	global $allowedtags;

	return array_merge_recursive(
		$allowedtags,
		array(
			'a'       => array(
				'aria-label'      => array(),
				'class'           => array(),
				'data-bp-tooltip' => array(),
				'id'              => array(),
				'rel'             => array(),
			),
			'img'     => array(
				'src'    => array(),
				'alt'    => array(),
				'width'  => array(),
				'height' => array(),
				'class'  => array(),
				'id'     => array(),
			),
			'span'    => array(
				'class'          => array(),
				'data-livestamp' => array(),
				'id'             => array(),
			),
			'ul'      => array(),
			'ol'      => array(),
			'li'      => array(),
			'p'       => array(
				'class' => array(),
				'id'    => array(),
				'style' => array(),
			),
			'abbr'    => array( 'title' => true ),
			'acronym' => array( 'title' => true ),
			'b'       => array(),
			'u'       => array(),
			'i'       => array(),
			'br'      => array(),
			'pre'     => array(),

		)
	);
}

/**
 * Remove script and style tags from a string.
 *
 * @since BuddyPress 3.0.1
 *
 * @param  string $string The string to strip tags from.
 * @return string         The stripped tags string.
 */
function bp_strip_script_and_style_tags( $string ) {
	return preg_replace( '@<(script|style)[^>]*?>.*?</\\1>@si', '', $string );
}

/**
 * Check if logged in member type is allowed to send invites
 *
 * @since BuddyBoss 1.0.0
 */
function bp_check_member_send_invites_tab_member_type_allowed() {

	// default allowed false
	$allowed = false;

	// Check BuddyBoss > Settings > Profiles > Enable profile types to give members unique profile fields and permission.
	if ( true === bp_member_type_enable_disable() ) {
		// Check BuddyBoss > Settings > Email Invites > Allow users to select profile type of invitee.
		if ( true === bp_disable_invite_member_type() ) {
			$current_user = bp_loggedin_user_id();
			$member_type  = bp_get_member_type( $current_user );
			// If current user don't have any profile type then we are not allow.
			if ( false === $member_type ) {
				$allowed = false;
			} else {
				$member_type_post_id = bp_member_type_post_by_type( $member_type );
				$meta                = get_post_custom( $member_type_post_id );
				$enable_invite       = isset( $meta['_bp_member_type_enable_invite'] ) ? intval( $meta['_bp_member_type_enable_invite'][0] ) : 1; // enabled by default
				if ( 1 === $enable_invite ) {
					$get_all_registered_member_types = bp_get_active_member_types();
					if ( isset( $get_all_registered_member_types ) && ! empty( $get_all_registered_member_types ) ) {
						$allowed = true;
					}
				}
			}
		}
	}
	return $allowed;
}

/**
 * Checks whether the current installation is "large".
 *
 * By default, an installation counts as "large" if there are 10000 users or more.
 * Filter 'bp_is_large_install' to adjust.
 *
 * @since BuddyPress 4.1.0
 *
 * @return bool
 */
function bp_is_large_install() {
	// Use the Multisite function if available.
	if ( function_exists( 'wp_is_large_network' ) ) {
		$is_large = wp_is_large_network( 'users' );
	} else {
		$is_large = bp_core_get_total_member_count() > 10000;
	}

	/**
	 * Filters whether the current installation is "large".
	 *
	 * @since BuddyPress 4.1.0
	 *
	 * @param bool $is_large True if the network is "large".
	 */
	return (bool) apply_filters( 'bp_is_large_install', $is_large );
}

/**
 * Returns the upper limit on the "max" item count, for widgets that support it.
 *
 * @since BuddyPress 5.0.0
 *
 * @param string $widget_class Optional. Class name of the calling widget.
 * @return int
 */
function bp_get_widget_max_count_limit( $widget_class = '' ) {
	/**
	 * Filters the upper limit on the "max" item count, for widgets that support it.
	 *
	 * @since BuddyPress 5.0.0
	 *
	 * @param int    $count        Defaults to 50.
	 * @param string $widget_class Class name of the calling widget.
	 */
	return apply_filters( 'bp_get_widget_max_count_limit', 50, $widget_class );
}

/**
 * Returns the active custom post type activity feed CPT array.
 *
 * @since BuddyBoss 1.0.0
 *
 * @return array
 */
function bp_core_get_active_custom_post_type_feed() {

	// Get site-wide all the CPT array.
	$custom_post_types = bp_get_option( 'bp_core_admin_get_active_custom_post_type_feed', array() );

	$cpt_arr = array();
	foreach ( $custom_post_types as $single_post ) {

		// check custom post type feed is enabled from the BuddyBoss > Settings > Activity > Custom Post Types metabox settings.
		$enabled = bp_is_post_type_feed_enable( $single_post );

		// If enabled put in $cpt_arr
		if ( $enabled ) {
			$cpt_arr[] = $single_post;
		}
	}

	return $cpt_arr;
}

/**
 * Return all the default activity of platform.
 *
 * @since BuddyBoss 1.0.0
 *
 * @return array
 */
function bp_platform_default_activity_types() {

	$activity_type = apply_filters(
		'bp_platform_default_activity_types',
		array(
			'0' => array(
				'activity_name'  => 'new_avatar',
				'activity_label' => __( 'Member changes their profile photo', 'buddyboss' ),
			),
			'1' => array(
				'activity_name'  => 'updated_profile',
				'activity_label' => __( 'Member updates their profile details', 'buddyboss' ),
			),
			'2' => array(
				'activity_name'  => 'new_member',
				'activity_label' => __( 'Member registers to the site', 'buddyboss' ),
			),
			'3' => array(
				'activity_name'  => 'friendship_created',
				'activity_label' => __( 'Two members become connected', 'buddyboss' ),
			),
			'4' => array(
				'activity_name'  => 'created_group',
				'activity_label' => __( 'Member creates a group', 'buddyboss' ),
			),
			'5' => array(
				'activity_name'  => 'joined_group',
				'activity_label' => __( 'Member joins a group', 'buddyboss' ),
			),
			'6' => array(
				'activity_name'  => 'group_details_updated',
				'activity_label' => __( 'Group details are updated', 'buddyboss' ),
			),
			'7' => array(
				'activity_name'  => 'bbp_topic_create',
				'activity_label' => __( 'Member creates a forum discussion', 'buddyboss' ),
			),
			'8' => array(
				'activity_name'  => 'bbp_reply_create',
				'activity_label' => __( 'Member replies to a forum discussion', 'buddyboss' ),
			),
		)
	);

	return $activity_type;
}

if ( ! function_exists( 'bp_core_get_post_id_by_slug' ) ) {
	/**
	 * Get Post id by Post SLUG
	 *
	 * @param $slug
	 *
	 * @since BuddyBoss 1.0.0
	 *
	 * @return array
	 */
	function bp_core_get_post_id_by_slug( $slug ) {
		$post_id = array();
		$args    = array(
			'posts_per_page'         => 1,
			'post_type'              => 'docs',
			'name'                   => $slug,
			'post_parent'            => 0,
			'suppress_filters'       => false,
			'update_post_meta_cache' => false,
			'update_post_term_cache' => false,
		);
		$docs    = get_posts( $args );
		if ( ! empty( $docs ) ) {
			foreach ( $docs as $doc ) {
				$post_id[] = $doc->ID;
			}
		}

		return $post_id;
	}
}

/**
 * Generate post slug by files name
 *
 * @since BuddyBoss 1.0.0
 *
 * @param $dir_index_file
 *
 * @return string
 */
function bp_core_get_post_slug_by_index( $dir_index_file ) {
	$dir_file_array = explode( '/', $dir_index_file );
	$index_file     = bp_core_help_remove_file_extension_from_slug( end( $dir_file_array ) );

	return bp_core_help_remove_file_number_from_slug( $index_file );
}

/**
 * Remove H1 tag from Content
 *
 * @since BuddyBoss 1.0.0
 *
 * @param $content
 *
 * @return mixed|null|string|string[]
 */
function bp_core_strip_header_tags( $content ) {
	$content = preg_replace( '/<h1[^>]*>([\s\S]*?)<\/h1[^>]*>/', '', $content );

	return $content;
}

/**
 * AJAX endpoint for Suggestions API lookups.
 *
 * @since BuddyPress 2.1.0
 *
 * @info Moved from /bp-activity/bp-activity-functions.php
 */
function bp_ajax_get_suggestions() {
	if ( ! bp_is_user_active() || empty( $_GET['term'] ) || empty( $_GET['type'] ) ) {
		wp_send_json_error( 'missing_parameter' );
		exit;
	}

	$args = array(
		'term' => sanitize_text_field( $_GET['term'] ),
		'type' => sanitize_text_field( $_GET['type'] ),
	);

	if ( ! empty( $_GET['only_friends'] ) ) {
		$args['only_friends'] = absint( $_GET['only_friends'] );
	}

	// Support per-Group suggestions.
	if ( ! empty( $_GET['group-id'] ) ) {
		$args['group_id'] = absint( $_GET['group-id'] );
	}

	$results = bp_core_get_suggestions( $args );

	if ( is_wp_error( $results ) ) {
		wp_send_json_error( $results->get_error_message() );
		exit;
	}

	wp_send_json_success( $results );
}
add_action( 'wp_ajax_bp_get_suggestions', 'bp_ajax_get_suggestions' );

/**
 * Locate usernames in an content string, as designated by an @ sign.
 *
 * @since BuddyBoss 1.2.8
 *
 * @param  array  $mentioned_users Associative array with user IDs as keys and usernames as values.
 * @param string $content Content
 * @return array|bool Associative array with user ID as key and username as
 *                    value. Boolean false if no mentions found.
 */
function bp_find_mentions_by_at_sign( $mentioned_users, $content ) {
	$pattern = '/[@]+([A-Za-z0-9-_\.@]+)\b/';
	preg_match_all( $pattern, $content, $usernames );

	// Make sure there's only one instance of each username.
	$usernames = array_unique( $usernames[1] );

	// Bail if no usernames.
	if ( empty( $usernames ) ) {
		return $mentioned_users;
	}

	// We've found some mentions! Check to see if users exist.
	foreach ( (array) array_values( $usernames ) as $username ) {
		$user_id = bp_get_userid_from_mentionname( $username );

		// The user ID exists, so let's add it to our array.
		if ( ! empty( $user_id ) ) {
			$mentioned_users[ $user_id ] = $username;
		}
	}

	if ( empty( $mentioned_users ) ) {
		return $mentioned_users;
	}

	return $mentioned_users;
}

/**
 * Get a user ID from a "mentionname", the name used for a user in @-mentions.
 *
 * @since BuddyBoss 1.2.8
 *
 * @param string $mentionname Username of user in @-mentions.
 * @return int|bool ID of the user, if one is found. Otherwise false.
 */
function bp_get_userid_from_mentionname( $mentionname ) {
	$user_id = false;

	/*
	 * In username compatibility mode, hyphens are ambiguous between
	 * actual hyphens and converted spaces.
	 *
	 * @todo There is the potential for username clashes between 'foo bar'
	 * and 'foo-bar' in compatibility mode. Come up with a system for
	 * unique mentionnames.
	 */
	if ( bp_is_username_compatibility_mode() ) {
		// First, try the raw username.
		$userdata = get_user_by( 'login', $mentionname );

		// Doing a direct query to use proper regex. Necessary to
		// account for hyphens + spaces in the same user_login.
		if ( empty( $userdata ) || ! is_a( $userdata, 'WP_User' ) ) {
			global $wpdb;
			$regex   = esc_sql( str_replace( '-', '[ \-]', $mentionname ) );
			$user_id = $wpdb->get_var( "SELECT ID FROM {$wpdb->users} WHERE user_login REGEXP '{$regex}'" );
		} else {
			$user_id = $userdata->ID;
		}

		// When username compatibility mode is disabled, the mentionname is
		// the same as the nicename.
	} else {
		$user_id = bp_core_get_userid_from_nickname( $mentionname );
	}

	return $user_id;
}

/**
 * Get unique ID.
 *
 * This is a PHP implementation of Underscore's uniqueId method. A static variable
 * contains an integer that is incremented with each call. This number is returned
 * with the optional prefix. As such the returned value is not universally unique,
 * but it is unique across the life of the PHP process.
 *
 * @since 1.2.10
 *
 * @staticvar int $id_counter
 *
 * @param string $prefix Prefix for the returned ID.
 * @return string Unique ID.
 */
function bp_unique_id( $prefix = '' ) {
	static $id_counter = 0;
	return $prefix . (string) ++$id_counter;
}

function bp_array_flatten( $array ) {
	if ( ! is_array( $array ) ) {
		return false;
	}
	$result = array();
	foreach ( $array as $key => $value ) {
		if ( is_array( $value ) ) {
			$result = array_merge( $result, bp_array_flatten( $value ) );
		} else {
			$result[ $key ] = $value;
		}
	}
	return $result;
}
/**
 * Get Group avatar.
 *
 * This function will give you the group avatar if previously group is created and
 * group is not deleted but if admin disabled group component then in Messages
 * section if previously group thread created then will show the actual group avatar
 * in messages view.
 *
 * @since BuddyBoss 1.3.0
 *
 * @param $avatar_size
 * @param $avatar_folder_dir
 * @param $avatar_folder_url
 *
 * @return mixed
 */
function bp_core_get_group_avatar( $legacy_user_avatar_name, $legacy_group_avatar_name, $avatar_size, $avatar_folder_dir, $avatar_folder_url ) {

	$group_avatar = '';

	if ( file_exists( $avatar_folder_dir ) ) {

		// Open directory.
		if ( $av_dir = opendir( $avatar_folder_dir ) ) {

			// Stash files in an array once to check for one that matches.
			$avatar_files = array();
			while ( false !== ( $avatar_file = readdir( $av_dir ) ) ) {
				// Only add files to the array (skip directories).
				if ( 2 < strlen( $avatar_file ) ) {
					$avatar_files[] = $avatar_file;
				}
			}

			// Check for array.
			if ( 0 < count( $avatar_files ) ) {

				// Check for current avatar.
				foreach ( $avatar_files as $key => $value ) {
					if ( strpos( $value, $avatar_size ) !== false ) {
						$group_avatar = $avatar_folder_url . '/' . $avatar_files[ $key ];
					}
				}

				// Legacy avatar check.
				if ( ! isset( $group_avatar ) ) {
					foreach ( $avatar_files as $key => $value ) {
						if ( strpos( $value, $legacy_user_avatar_name ) !== false ) {
							$group_avatar = $avatar_folder_url . '/' . $avatar_files[ $key ];
						}
					}

					// Legacy group avatar check.
					if ( ! isset( $group_avatar ) ) {
						foreach ( $avatar_files as $key => $value ) {
							if ( strpos( $value, $legacy_group_avatar_name ) !== false ) {
								$group_avatar = $avatar_folder_url . '/' . $avatar_files[ $key ];
							}
						}
					}
				}
			}
		}
		// Close the avatar directory.
		closedir( $av_dir );
	}

	return $group_avatar;
}

/**
 * Parse url and get data about URL.
 *
 * @param string $url URL to parse data.
 *
 * @return array Parsed URL data.
 * @since BuddyBoss 1.3.2
 */
function bp_core_parse_url( $url ) {
	$cache_key = 'bp_activity_oembed_' . md5( serialize( $url ) );

	// get transient data for url.
	$parsed_url_data = get_transient( $cache_key );
	if ( ! empty( $parsed_url_data ) ) {
		return $parsed_url_data;
	}

	$parsed_url_data = array();

	if ( strstr( $url, site_url() ) && ( strstr( $url, 'download_document_file' ) || strstr( $url, 'download_media_file' ) || strstr( $url, 'download_video_file' ) ) ) {
		return array();
	}

	// Fetch the oembed code for URL.
	$embed_code = wp_oembed_get( $url, array( 'discover' => false ) );
	if ( ! empty( $embed_code ) ) {
		$parsed_url_data['title']       = ' ';
		$parsed_url_data['description'] = $embed_code;
		$parsed_url_data['images']      = '';
		$parsed_url_data['error']       = '';
		$parsed_url_data['wp_embed']    = true;
	} else {

		// safely get URL and response body.
		$response = wp_safe_remote_get(
			$url,
			array(
				'user-agent' => '', // Default value being blocked by Cloudflare
			)
		);
		$body     = wp_remote_retrieve_body( $response );

		// if response is not empty
		if ( ! is_wp_error( $body ) && ! empty( $body ) ) {

			// Load HTML to DOM Object
			$dom = new DOMDocument();
			@$dom->loadHTML( mb_convert_encoding( $body, 'HTML-ENTITIES', 'UTF-8' ) );

			$meta_tags   = array();
			$images      = array();
			$description = '';
			$title       = '';

			$xpath       = new DOMXPath( $dom );
			$query       = '//*/meta[starts-with(@property, \'og:\')]';
			$metas_query = $xpath->query( $query );
			foreach ( $metas_query as $meta ) {
				$property    = $meta->getAttribute( 'property' );
				$content     = $meta->getAttribute( 'content' );
				$meta_tags[] = array( $property, $content );
			}

			if ( is_array( $meta_tags ) && ! empty( $meta_tags ) ) {
				foreach ( $meta_tags as $tag ) {
					if ( is_array( $tag ) && ! empty( $tag ) ) {
						if ( $tag[0] == 'og:title' ) {
							$title = $tag[1];
						}
						if ( $tag[0] == 'og:description' || 'description' === strtolower( $tag[0] ) ) {
							$description = html_entity_decode( $tag[1], ENT_QUOTES, 'utf-8' );
						}
						if ( $tag[0] == 'og:image' ) {
							$images[] = $tag[1];
						}
					}
				}
			}

			// Parse DOM to get Title
			if ( empty( $title ) ) {
				$nodes = $dom->getElementsByTagName( 'title' );
				$title = $nodes->item( 0 )->nodeValue;
			}

			// Parse DOM to get Meta Description
			if ( empty( $description ) ) {
				$metas = $dom->getElementsByTagName( 'meta' );
				for ( $i = 0; $i < $metas->length; $i ++ ) {
					$meta = $metas->item( $i );
					if ( 'description' === $meta->getAttribute( 'name' ) ) {
						$description = $meta->getAttribute( 'content' );
						break;
					}
				}
			}

			// Parse DOM to get Images
			$image_elements = $dom->getElementsByTagName( 'img' );
			for ( $i = 0; $i < $image_elements->length; $i ++ ) {
				$image = $image_elements->item( $i );
				$src   = $image->getAttribute( 'src' );

				if ( filter_var( $src, FILTER_VALIDATE_URL ) ) {
					$images[] = $src;
				}
			}

			if ( ! empty( $description ) && '' === trim( $title ) ) {
				$title = $description;
			}

			if ( ! empty( $title ) && '' === trim( $description ) ) {
				$description = $title;
			}

			if ( ! empty( $title ) ) {
				$parsed_url_data['title'] = $title;
			}

			if ( ! empty( $description ) ) {
				$parsed_url_data['description'] = $description;
			}

			if ( ! empty( $images ) ) {
				$parsed_url_data['images'] = $images;
			}

			if ( ! empty( $title ) || ! empty( $description ) || ! empty( $images ) ) {
				$parsed_url_data['error'] = '';
			}
		}
	}

	if ( ! empty( $parsed_url_data ) ) {
		// set the transient.
		set_transient( $cache_key, $parsed_url_data, DAY_IN_SECONDS );
	}

	/**
	 * Filters parsed URL data.
	 *
	 * @since BuddyBoss 1.3.2
	 * @param array $parsed_url_data Parse URL data.
	 */
	return apply_filters( 'bp_core_parse_url', $parsed_url_data );
}

/**
 * Format file size units
 *
 * @param int    $bytes
 * @param bool   $unit_label
 * @param string $type
 *
 * @return string
 * @since BuddyBoss 1.3.5
 */
function bp_core_format_size_units( $bytes, $unit_label = false, $type = '' ) {

	if ( $bytes > 0 && ! $unit_label ) {
		if ( 'GB' === $type ) {
			return $bytes / 1073741824;
		} elseif ( 'MB' === $type ) {
			return $bytes / 1048576;
		} elseif ( 'KB' === $type ) {
			return $bytes / 1024;
		} else {
			return $bytes;
		}
	}

	if ( empty( $type ) ) {
		if ( $bytes >= 1073741824 ) {
			$bytes = number_format( ( $bytes / 1073741824 ), 2, '.', '' ) . ' GB';
		} elseif ( $bytes >= 1048576 ) {
			$bytes = number_format( ( $bytes / 1048576 ), 2, '.', '' ) . ' MB';
		} elseif ( $bytes >= 1024 ) {
			$bytes = number_format( ( $bytes / 1024 ), 2, '.', '' ) . ' KB';
		} elseif ( $bytes > 1 ) {
			$bytes = $bytes . ' bytes';
		} elseif ( $bytes == 1 ) {
			$bytes = $bytes . ' byte';
		} else {
			$bytes = '0' . ' bytes';
		}
	} else {
		if ( 'GB' === $type ) {
			$bytes = number_format( ( $bytes / 1073741824 ), 2, '.', '' ) . ' GB';
		} elseif ( 'MB' === $type ) {
			$bytes = number_format( ( $bytes / 1048576 ), 2, '.', '' ) . ' MB';
		} elseif ( 'KB' === $type ) {
			$bytes = number_format( ( $bytes / 1024 ), 2, '.', '' ) . ' KB';
		} elseif ( 'bytes' === $type ) {
			$bytes = $bytes . ' bytes';
		} elseif ( 1 === $bytes ) {
			$bytes = $bytes . ' byte';
		} else {
			$bytes = '0' . ' bytes';
		}
	}

	return $bytes;
}

/**
 * Whether or not profile field is hidden.
 *
 * @since BuddyBoss 1.4.7
 *
 * @param int $field_id ID for the profile field.
 *
 * @return bool Whether or not profile field is hidden.
 */
function bp_core_hide_display_name_field( $field_id = 0 ) {
	if (
		! function_exists( 'bp_is_active' )
		|| ! bp_is_active( 'xprofile' )
		|| empty( $field_id )
	) {
		return false;
	}

	$retval = false;

	// Get the current display settings from BuddyBoss > Settings > Profiles > Display Name Format.
	$current_value = bp_get_option( 'bp-display-name-format' );

	// If First Name selected then do not add last name field.
	if ( 'first_name' === $current_value && $field_id === bp_xprofile_lastname_field_id() ) {
		if ( function_exists( 'bp_hide_last_name' ) && false === bp_hide_last_name() ) {
			$retval = true;
		}
		// If Nick Name selected then do not add first & last name field.
	} elseif ( 'nickname' === $current_value && $field_id === bp_xprofile_lastname_field_id() ) {
		if ( function_exists( 'bp_hide_nickname_last_name' ) && false === bp_hide_nickname_last_name() ) {
			$retval = true;
		}
	} elseif ( 'nickname' === $current_value && $field_id === bp_xprofile_firstname_field_id() ) {
		if ( function_exists( 'bp_hide_nickname_first_name' ) && false === bp_hide_nickname_first_name() ) {
			$retval = true;
		}
	}

	/**
	 * Filters Hide Display name field.
	 *
	 * @since BuddyBoss 1.4.7
	 *
	 * @param bool $retval   Return value.
	 * @param int  $field_id ID for the profile field.
	 */
	return (bool) apply_filters( 'bp_core_hide_display_name_field', $retval, $field_id );
}

/**
 * Return the file upload max size in bytes.
 *
 * @return mixed|void
 *
 * @since BuddyBoss 1.4.8
 */
function bp_core_upload_max_size() {

	static $max_size = - 1;

	if ( $max_size < 0 ) {
		// Start with post_max_size.
		$size = @ini_get( 'post_max_size' );
		$unit = preg_replace( '/[^bkmgtpezy]/i', '', $size ); // Remove the non-unit characters from the size.
		$size = preg_replace( '/[^0-9\.]/', '', $size ); // Remove the non-numeric characters from the size.
		if ( $unit ) {
			$post_max_size = round( $size * pow( 1024, stripos( 'bkmgtpezy', $unit[0] ) ) );
		} else {
			$post_max_size = round( $size );
		}

		if ( $post_max_size > 0 ) {
			$max_size = $post_max_size;
		}

		// If upload_max_size is less, then reduce. Except if upload_max_size is
		// zero, which indicates no limit.
		$size = @ini_get( 'upload_max_filesize' );
		$unit = preg_replace( '/[^bkmgtpezy]/i', '', $size ); // Remove the non-unit characters from the size.
		$size = preg_replace( '/[^0-9\.]/', '', $size ); // Remove the non-numeric characters from the size.
		if ( $unit ) {
			$upload_max = round( $size * pow( 1024, stripos( 'bkmgtpezy', $unit[0] ) ) );
		} else {
			$upload_max = round( $size );
		}
		if ( $upload_max > 0 && $upload_max < $max_size ) {
			$max_size = $upload_max;
		}
	}

	/**
	 * Filters file upload max limit.
	 *
	 * @param mixed $max_size file upload max limit.
	 *
	 * @since BuddyBoss 1.4.8
	 */
	return apply_filters( 'bp_core_upload_max_size', $max_size );

}

/**
 * Function will return the default fields groups and avatar/cover is enabled or not.
 *
 * @since BuddyBoss 1.5.4
 */
function bp_core_profile_completion_steps_options() {

	/* Profile Groups and Profile Cover Photo VARS. */
	$options                              = array();
	$options['profile_groups']            = bp_xprofile_get_groups();
	$options['is_profile_photo_disabled'] = bp_disable_avatar_uploads();
	$options['is_cover_photo_disabled']   = bp_disable_cover_image_uploads();

	/**
	 * Filters will return the default fields groups and avatar/cover is enabled or not.
	 *
	 * @param array $options of default Profile Groups and Profile Cover/Photo enabled.
	 *
	 * @since BuddyBoss 1.5.4
	 */
	return apply_filters( 'bp_core_profile_completion_steps_options', $options );
}

/**
 * Function trigger when profile updated. Profile field added/updated/deleted.
 * Deletes Profile Completion Transient here.
 *
 * @since BuddyBoss 1.4.9
 */
function bp_core_xprofile_update_profile_completion_user_progress() {

	$user_id            = get_current_user_id();
	$steps_options      = bp_core_profile_completion_steps_options();
	$profile_groups     = wp_list_pluck( $steps_options['profile_groups'], 'id' );
	$profile_photo_type = array();

	if ( ! $steps_options['is_profile_photo_disabled'] ) {
		$profile_photo_type[] = 'profile_photo';
	}
	if ( ! $steps_options['is_cover_photo_disabled'] ) {
		$profile_photo_type[] = 'cover_photo';
	}

	// Get logged in user Progress.
	$user_progress_arr = bp_xprofile_get_user_progress( $profile_groups, $profile_photo_type );
	bp_update_user_meta( $user_id, 'bp_profile_completion_widgets', $user_progress_arr );

}

/**
 * Function will return the user progress based on the settings you provided.
 *
 * @param array $settings set of fieldset selected to show in progress & profile or cover photo selected to show in
 *                        progress.
 *
 * @return array $response user progress based on widget settings.
 */
function bp_xprofile_get_selected_options_user_progress( $settings ) {

	$profile_groups     = $settings['profile_groups'];
	$profile_photo_type = $settings['profile_photo_type'];

	// Get logged in user Progress.
	$get_user_data = bp_get_user_meta( get_current_user_id(), 'bp_profile_completion_widgets', true );
	if ( ! $get_user_data ) {
		bp_core_xprofile_update_profile_completion_user_progress();
		$get_user_data = bp_get_user_meta( get_current_user_id(), 'bp_profile_completion_widgets', true );
	}

	$response                     = array();
	$response['photo_type']       = array();
	$response['groups']           = array();
	$response['total_fields']     = 0;
	$response['completed_fields'] = 0;
	$total_count                  = 0;
	$total_completed_count        = 0;

	if ( ! empty( $profile_photo_type ) ) {
		foreach ( $profile_photo_type as $option ) {
			if ( 'profile_photo' === $option && isset( $get_user_data['photo_type'] ) && isset( $get_user_data['photo_type']['profile_photo'] ) ) {
				$response['photo_type']['profile_photo'] = $get_user_data['photo_type']['profile_photo'];
				$total_count                             = ++ $total_count;
				if ( isset( $get_user_data['photo_type']['profile_photo']['is_uploaded'] ) && 1 === (int) $get_user_data['photo_type']['profile_photo']['is_uploaded'] ) {
					$total_completed_count = ++ $total_completed_count;
				}
			} elseif ( 'cover_photo' === $option && isset( $get_user_data['photo_type'] ) && isset( $get_user_data['photo_type']['cover_photo'] ) ) {
				$response['photo_type']['cover_photo'] = $get_user_data['photo_type']['cover_photo'];
				$total_count                           = ++ $total_count;
				if ( isset( $get_user_data['photo_type']['cover_photo']['is_uploaded'] ) && 1 === (int) $get_user_data['photo_type']['cover_photo']['is_uploaded'] ) {
					$total_completed_count = ++ $total_completed_count;
				}
			}
		}
	}

	if ( ! empty( $profile_groups ) ) {
		foreach ( $profile_groups as $group ) {
			if ( isset( $get_user_data['groups'][ $group ] ) ) {
				$response['groups'][ $group ] = $get_user_data['groups'][ $group ];
				$total_count                  = $total_count + (int) $get_user_data['groups'][ $group ]['group_total_fields'];
				if ( isset( $get_user_data['groups'][ $group ]['group_completed_fields'] ) && (int) $get_user_data['groups'][ $group ]['group_completed_fields'] > 0 ) {
					$total_completed_count = $total_completed_count + (int) $get_user_data['groups'][ $group ]['group_completed_fields'];
				}
			}
		}
	}

	if ( $total_count > 0 ) {
		$response['total_fields'] = $total_count;
	}

	if ( $total_completed_count > 0 ) {
		$response['completed_fields'] = $total_completed_count;
	}

	/**
	 * Filters will return the user progress based on the settings you provided.
	 *
	 * @param array $response           user progress array.
	 * @param array $profile_groups     user profile groups.
	 * @param array $profile_photo_type user profile photo/cover data.
	 * @param array $get_user_data      user profile cached data.
	 *
	 * @since BuddyBoss 1.5.4
	 */
	return apply_filters( 'bp_xprofile_get_selected_options_user_progress', $response, $profile_groups, $profile_photo_type, $get_user_data );

}

/**
 * Function trigger when admin make an profile field or settings changes on backend.
 *
 * @since BuddyBoss 1.5.4
 */
function bp_core_xprofile_clear_all_user_progress_cache() {

	delete_metadata(
		'user',        // the meta type.
		0,             // this doesn't actually matter in this call.
		'bp_profile_completion_widgets', // the meta key to be removed everywhere.
		'',            // this also doesn't actually matter in this call.
		true           // tells the function "yes, please remove them all".
	);

}

/**
 * When search_terms are passed to BP_User_Query, search against xprofile fields.
 *
 * @since BuddyBoss 1.6.3
 *
 * @param array         $sql   Clauses in the user_id SQL query.
 * @param BP_User_Query $query User query object.
 * @return array
 */
function bb_xprofile_search_bp_user_query_search_first_last_nickname( $sql, BP_User_Query $query ) {

	global $wpdb;
	if ( empty( $query->query_vars['search_terms'] ) || empty( $sql['where']['search'] ) ) {
		return $sql;
	}

	$bp                 = buddypress();
	$search_terms_clean = bp_esc_like( wp_kses_normalize_entities( $query->query_vars['search_terms'] ) );
	if ( 'left' === $query->query_vars['search_wildcard'] ) {
		$search_terms_nospace = '%' . $search_terms_clean;
		$search_terms_space   = '%' . $search_terms_clean . ' %';
	} elseif ( 'right' === $query->query_vars['search_wildcard'] ) {
		$search_terms_nospace = $search_terms_clean . '%';
		$search_terms_space   = '% ' . $search_terms_clean . '%';
	} else {
		$search_terms_nospace = '%' . $search_terms_clean . '%';
		$search_terms_space   = '%' . $search_terms_clean . '%';
	}

	// Get the firstname,last and nickname field id.
	$firstname_field_id = bp_xprofile_firstname_field_id();
	$last_field_id      = bp_xprofile_lastname_field_id();
	$nickname_field_id  = bp_xprofile_nickname_field_id();

	// Get the current display settings from BuddyBoss > Settings > Profiles > Display Name Format.
	$current_value  = bp_get_option( 'bp-display-name-format' );
	$enabled_fields = array();

	// If First Name selected then do not add last name field.
	if ( 'first_name' === $current_value ) {
		$enabled_fields['first_name'] = bp_xprofile_firstname_field_id();
		$enabled_fields['nickname']   = bp_xprofile_nickname_field_id();
		if ( ! empty( bp_hide_last_name() ) ) {
			$enabled_fields['lastname'] = bp_xprofile_lastname_field_id();
		}
		// If Nick Name selected then do not add first & last name field.
	} elseif ( 'nickname' === $current_value ) {
		$enabled_fields['nickname'] = bp_xprofile_nickname_field_id();
		if ( ! empty( bp_hide_nickname_first_name() ) ) {
			$enabled_fields['first_name'] = bp_xprofile_firstname_field_id();
		}
		if ( ! empty( bp_hide_last_name() ) ) {
			$enabled_fields['lastname'] = bp_xprofile_lastname_field_id();
		}
	} else {
		$enabled_fields['first_name'] = bp_xprofile_firstname_field_id();
		$enabled_fields['lastname']   = bp_xprofile_lastname_field_id();
		$enabled_fields['nickname']   = bp_xprofile_nickname_field_id();
	}

	$where_condition = array();
	if ( ! empty( $enabled_fields ) ) {
		foreach ( $enabled_fields as $field_name => $field_id ) {
			$where_condition[] = ' ( ( field_id = ' . $field_id . " ) AND ( value LIKE '" . $search_terms_nospace . "' OR value LIKE '" . $search_terms_space . "' ) )";
		}
	}
	// Combine the core search (against wp_users) into a single OR clause with the xprofile_data search.
	$matched_user_ids = $wpdb->get_col( "SELECT DISTINCT user_id FROM {$bp->profile->table_name_data} WHERE " . implode( ' OR ', $where_condition ) );

	// Checked profile fields based on privacy settings of particular user while searching.
	if ( ! empty( $matched_user_ids ) ) {
		$matched_user_data = $wpdb->get_results( "SELECT * FROM {$bp->profile->table_name_data} WHERE " . implode( ' OR ', $where_condition ) );

		if ( ! empty( $matched_user_data ) ) {
			foreach ( $matched_user_data as $k => $user ) {
				$field_visibility = xprofile_get_field_visibility_level( $user->field_id, $user->user_id );
				if ( 'adminsonly' === $field_visibility && ! current_user_can( 'administrator' ) ) {
					$key = array_search( $user->user_id, $matched_user_ids, true );
					if ( false !== $key ) {
						unset( $matched_user_ids[ $key ] );
					}
				}
				if ( 'friends' === $field_visibility && ! current_user_can( 'administrator' ) && false === friends_check_friendship( intval( $user->user_id ), bp_loggedin_user_id() ) ) {
					$key = array_search( $user->user_id, $matched_user_ids, true );
					if ( false !== $key ) {
						unset( $matched_user_ids[ $key ] );
					}
				}
			}
		}
	}

	if ( ! empty( $matched_user_ids ) ) {
		$search_core            = $sql['where']['search'];
		$search_combined        = " ( u.{$query->uid_name} IN (" . implode( ',', $matched_user_ids ) . ") OR {$search_core} )";
		$sql['where']['search'] = $search_combined;
	}

	return $sql;
}

/**
 * Check given directory is empty or not.
 *
 * @param string $dir The directory path.
 * @return bool True OR False whether directory is empty or not.
 *
 * @since BuddyBoss 1.7.0
 */
function bp_core_is_empty_directory( $dir ) {
	$handle = opendir( $dir );
	while ( ( $entry  = readdir( $handle ) ) !== $entry ) {
		if ( '.' !== $entry && '..' !== $entry ) {
			closedir( $handle );

			return false;
		}
	}
	closedir( $handle );

	return true;
}

/**
 * Regenerate attachment thumbnails
 *
 * @param int $attachment_id Attachment ID.
 *
 * @since BuddyBoss 1.7.0
 */
function bp_core_regenerate_attachment_thumbnails( $attachment_id ) {
	if ( function_exists( 'wp_get_original_image_path' ) ) {
		$fullsizepath = wp_get_original_image_path( $attachment_id );
	} else {
		$fullsizepath = get_attached_file( $attachment_id );
	}

	if ( ! function_exists( 'media_handle_upload' ) ) {
		require_once ABSPATH . 'wp-admin/includes/admin.php';
	}
	$new_metadata = wp_generate_attachment_metadata( $attachment_id, $fullsizepath );
	wp_update_attachment_metadata( $attachment_id, $new_metadata );
}

/**
 * Function which remove the temporary created directory.
 *
 * @param string $directory Directory to remove.
 *
 * @since BuddyBoss 1.7.0
 */
function bp_core_remove_temp_directory( $directory = '' ) {
	if ( is_dir( $directory ) ) {
		$objects = scandir( $directory );
		foreach ( $objects as $object ) {
			if ( '.' !== $object && '..' !== $object ) {
				if ( 'dir' === filetype( $directory . '/' . $object ) ) {
					bp_core_remove_temp_directory( $directory . '/' . $object );
				} else {
					unlink( $directory . '/' . $object );
				}
			}
		}
		reset( $objects );
		rmdir( $directory );
	}
}

/**
 * Symlink Generator
 *
 * @since BuddyBoss 1.7.0
 *
 * @param string $type            Item Type ( media, document, video ).
 * @param object $item            Item Object( Media, Document, Video ).
 * @param string $size            Image Size.
 * @param array  $file            Array of file relative path, width, and height.
 * @param string $output_file_src Absolute path of the site.
 * @param string $attachment_path Symbolising path to generate.
 */
function bb_core_symlink_generator( $type, $item, $size, $file, $output_file_src, $attachment_path ) {
	if ( empty( $type ) || empty( $item ) ) {
		return;
	}

	if ( ! bp_is_active( 'media') ) {
	    return;
    }

	$key           = '';
	$sym_path      = '';
	$filename      = '';
	$attachment_id = $item->attachment_id;

	switch ( $type ) {
		case 'media':
			$key      = 'bb_media_symlink_type';
			$sym_path = bp_media_symlink_path();
			$filename = md5( $item->id . $attachment_id . $item->privacy . $size );
			if ( $item->group_id > 0 && bp_is_active( 'groups' ) ) {
				$group_object = groups_get_group( $item->group_id );
				$group_status = bp_get_group_status( $group_object );
				$filename     = md5( $item->id . $attachment_id . $group_status . $item->privacy . $size );
			}
			break;
		case 'document':
			$key      = 'bb_document_symlink_type';
			$sym_path = bp_document_symlink_path();
			$filename = md5( $item->id . $attachment_id . $item->privacy . $size );
			if ( $item->group_id > 0 && bp_is_active( 'groups' ) ) {
				$group_object = groups_get_group( $item->group_id );
				$group_status = bp_get_group_status( $group_object );
				$filename     = md5( $item->id . $attachment_id . $group_status . $item->privacy . $size );
			}
			break;
		case 'document_video':
			$key      = 'bb_document_video_symlink_type';
			$sym_path = bp_document_symlink_path();
			$filename = md5( $item->id . $attachment_id . $item->privacy );
			if ( $item->group_id > 0 && bp_is_active( 'groups' ) ) {
				$group_object = groups_get_group( $item->group_id );
				$group_status = bp_get_group_status( $group_object );
				$filename     = md5( $item->id . $attachment_id . $group_status . $item->privacy );
			}
			break;
		case 'video':
			$key      = 'bb_video_symlink_type';
			$sym_path = bb_video_symlink_path();
			$filename = md5( $item->id . $attachment_id . $item->privacy . $size );
			if ( $item->group_id > 0 && bp_is_active( 'groups' ) ) {
				$group_object = groups_get_group( $item->group_id );
				$group_status = bp_get_group_status( $group_object );
				$filename     = md5( $item->id . $attachment_id . $group_status . $item->privacy . $size );
			}
			break;
		case 'video_thumb':
			$key      = 'bb_video_thumb_symlink_type';
			$sym_path = bb_video_symlink_path();
			$filename = md5( $item->id . $attachment_id . $item->privacy . $size );
			if ( $item->group_id > 0 && bp_is_active( 'groups' ) ) {
				$group_object = groups_get_group( $item->group_id );
				$group_status = bp_get_group_status( $group_object );
				$filename     = md5( $item->id . $attachment_id . $group_status . $item->privacy . $size );
			}
			break;
	}

	if ( file_exists( $output_file_src ) && is_file( $output_file_src ) && ! is_dir( $output_file_src ) && ! file_exists( $attachment_path ) ) {
		if ( ! is_link( $attachment_path ) ) {

			$sym_status = bp_get_option( $key );

			if ( 'default' === $sym_status ) {
				symlink( $output_file_src, $attachment_path );
			} elseif ( 'relative' === $sym_status ) {
				$tmp = getcwd();
				chdir( wp_normalize_path( ABSPATH ) );
				$sym_path   = explode( '/', $sym_path );
				$search_key = array_search( 'wp-content', $sym_path, true );
				if ( is_array( $sym_path ) && ! empty( $sym_path ) && false !== $search_key ) {
					$sym_path = array_slice( array_filter( $sym_path ), $search_key );
					$sym_path = implode( '/', $sym_path );
				}
				if ( is_dir( 'wp-content/' . $sym_path ) ) {
					chdir( 'wp-content/' . $sym_path );
					if ( empty( $file['path'] ) ) {
						$file['path'] = get_post_meta( $attachment_id, '_wp_attached_file', true );
						if ( 'document' === $type ) {
							$is_image         = wp_attachment_is_image( $attachment_id );
							$img_url          = get_attached_file( $attachment_id );
							$meta             = wp_get_attachment_metadata( $attachment_id );
							$img_url_basename = wp_basename( $img_url );
							$upl_dir          = wp_get_upload_dir();

							if ( ! $is_image ) {
								if ( ! empty( $meta['sizes'][$size] ) ) {
									$img_url = str_replace( $img_url_basename, $meta['sizes'][$size]['file'], $img_url );
								} else {
									$img_url = str_replace( $img_url_basename, $meta['sizes']['full']['file'], $img_url );
								}
							}
							$file['path'] = str_replace( trailingslashit( $upl_dir['basedir'] ), '', $img_url );
						}
					}
					$output_file_src = '../../' . $file['path'];
					if ( file_exists( $output_file_src ) ) {
						symlink( $output_file_src, $filename );
					}
				}
				chdir( $tmp );
			}
		}
	}

}

function bb_core_symlink_absolute_path( $preview_attachment_path, $upload_directory ) {

	$attachment_url = str_replace( $upload_directory['basedir'], $upload_directory['baseurl'], $preview_attachment_path );

	return str_replace( 'uploads/uploads', 'uploads', $attachment_url );
}

/**
 * Return absolute path of the document file.
 *
 * @param $path
 * @since BuddyBoss 1.7.0
 */
function bb_core_scaled_attachment_path( $attachment_id ) {
	$is_image         = wp_attachment_is_image( $attachment_id );
	$img_url          = get_attached_file( $attachment_id );
	$meta             = wp_get_attachment_metadata( $attachment_id );
	$img_url_basename = wp_basename( $img_url );
	if ( ! $is_image ) {
		if ( ! empty( $meta['sizes']['full'] ) ) {
			$img_url = str_replace( $img_url_basename, $meta['sizes']['full']['file'], $img_url );
		}
	}

	return $img_url;
}

/**
 * Check is device is IOS.
 *
 * @return bool
 *
 * @since BuddyBoss 1.7.0
 */
function bb_check_ios_device() {

	$is_ios = false;
	$ipod   = ( isset( $_SERVER['HTTP_USER_AGENT'] ) ? stripos( $_SERVER['HTTP_USER_AGENT'], 'iPod' ) : false ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized,WordPress.Security.ValidatedSanitizedInput.MissingUnslash
	$iphone = ( isset( $_SERVER['HTTP_USER_AGENT'] ) ? stripos( $_SERVER['HTTP_USER_AGENT'], 'iPhone' ) : false ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized,WordPress.Security.ValidatedSanitizedInput.MissingUnslash
	$ipad   = ( isset( $_SERVER['HTTP_USER_AGENT'] ) ? stripos( $_SERVER['HTTP_USER_AGENT'], 'iPad' ) : false ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized,WordPress.Security.ValidatedSanitizedInput.MissingUnslash

	if ( $ipod || $iphone || $ipad ) {
		$is_ios = true;
	}

	/**
	 * Filter for the check if it's ios devices or not.
	 *
	 * @since BuddyBoss 1.7.0.1
	 */
	return apply_filters( 'bb_check_ios_device', $is_ios );
}

/**
 * Enable symlink automatically.
 *
 * @since BuddyBoss 1.7.0
 */
function bb_core_enable_default_symlink_support() {

	if ( ! bp_is_active( 'media' ) ) {
		return;
	}

	if ( (bool) get_option( 'bp_media_symlink_support', false ) ) {
		return;
	}

	$upload_dir = wp_upload_dir();
	$upload_dir = $upload_dir['basedir'];

	$platform_previews_path = $upload_dir . '/bb-platform-previews';
	if ( ! is_dir( $platform_previews_path ) ) {
		wp_mkdir_p( $platform_previews_path );
		chmod( $platform_previews_path, 0755 );
	}

	$media_symlinks_path = $platform_previews_path . '/' . md5( 'bb-media' );
	if ( ! is_dir( $media_symlinks_path ) ) {
		wp_mkdir_p( $media_symlinks_path );
		chmod( $media_symlinks_path, 0755 );
	}

	$upload_dir      = wp_upload_dir();
	$upload_dir      = $upload_dir['basedir'];
	$output_file_src = '';
	$attachment_id   = bb_core_upload_dummy_attachment();

	if ( ! empty( $attachment_id ) ) {

		$attachment_url          = wp_get_attachment_image_src( $attachment_id );
		$attachment_file         = get_attached_file( $attachment_id );
		$symlinks_path           = $media_symlinks_path;
		$size                    = 'thumbnail';
		$symlink_name            = md5( 'testsymlink' . $attachment_id . $size );
		$attachment_path         = $symlinks_path . '/' . $symlink_name;
		$file                    = image_get_intermediate_size( $attachment_id, $size );
		$upload_directory        = wp_get_upload_dir();
		$preview_attachment_path = $symlinks_path . '/' . $symlink_name;
		$symlink_url             = bb_core_symlink_absolute_path( $preview_attachment_path, $upload_directory );

		if ( $file && ! empty( $file['path'] ) ) {
			$output_file_src = $upload_dir . '/' . $file['path'];
		} elseif ( $attachment_url ) {
			$output_file_src = $attachment_file;
		}

		if ( file_exists( $output_file_src ) && is_file( $output_file_src ) && ! is_dir( $output_file_src ) && ! file_exists( $attachment_path ) ) {

			if ( ! is_link( $attachment_path ) ) {

				symlink( $output_file_src, $attachment_path );

				if ( ! empty( $symlink_url ) ) {
					$fetch = wp_remote_get( $symlink_url );

					if ( ! is_wp_error( $fetch ) && isset( $fetch['response']['code'] ) && 200 === $fetch['response']['code'] ) {
						bp_update_option( 'bp_media_symlink_support', 1 );
					}
				}
				unlink( $attachment_path );

				if ( bb_enable_symlinks() ) {
					wp_delete_attachment( $attachment_id, true );
					return;
				}

				$tmp = getcwd();
				chdir( wp_normalize_path( ABSPATH ) );
				$sym_path   = explode( '/', $symlinks_path );
				$search_key = array_search( 'wp-content', $sym_path, true );
				if ( is_array( $sym_path ) && ! empty( $sym_path ) && false !== $search_key ) {
					$sym_path = array_slice( array_filter( $sym_path ), $search_key );
					$sym_path = implode( '/', $sym_path );
				}
				if ( is_dir( 'wp-content/' . $sym_path ) ) {
					chdir( 'wp-content/' . $sym_path );
					if ( empty( $file['path'] ) ) {
						$file['path'] = get_post_meta( $attachment_id, '_wp_attached_file', true );
					}
					$output_file_src = '../../' . $file['path'];
					if ( file_exists( $output_file_src ) ) {
						symlink( $output_file_src, $symlink_name );
					}
				}
				chdir( $tmp );

				if ( ! empty( $symlink_url ) ) {
					$fetch = wp_remote_get( $symlink_url );
					if ( ! is_wp_error( $fetch ) && isset( $fetch['response']['code'] ) && 200 === $fetch['response']['code'] ) {
						bp_update_option( 'bp_media_symlink_support', 1 );
					}
				}
			}
		}
		wp_delete_attachment( $attachment_id, true );
	}
}

/**
 * Create a dummy attachment and return the id.
 *
 * @return int|WP_Error
 *
 * @since BuddyBoss 1.7.0
 */
function bb_core_upload_dummy_attachment() {

	$file          = buddypress()->plugin_dir . 'bp-core/images/suspended-mystery-man.jpg';
	$filename      = basename( $file );
	$upload_file   = wp_upload_bits( $filename, null, file_get_contents( $file ) );
	$attachment_id = 0;
	if ( ! $upload_file['error'] ) {
		$wp_filetype   = wp_check_filetype( $filename, null );
		$attachment    = array(
			'post_mime_type' => $wp_filetype['type'],
			'post_title'     => preg_replace( '/\.[^.]+$/', '', $filename ),
			'post_content'   => '',
			'post_status'    => 'inherit',
		);
		$attachment_id = wp_insert_attachment( $attachment, $upload_file['file'] );
		if ( ! is_wp_error( $attachment_id ) ) {
			if ( ! function_exists( 'wp_generate_attachment_metadata' ) ) {
				require_once ABSPATH . 'wp-admin/includes/image.php';
			}
			$attachment_data = wp_generate_attachment_metadata( $attachment_id, $upload_file['file'] );
			wp_update_attachment_metadata( $attachment_id, $attachment_data );
		}
	}

	return $attachment_id;
}

/**
 * Function which will return the browser useragent, name, version, platform and pattern.
 *
 * @return array
 *
 * @since BuddyBoss 1.7.2
 */
function bb_core_get_browser() {

	$u_agent  = $_SERVER['HTTP_USER_AGENT'];
	$bname    = 'Unknown';
	$platform = 'Unknown';
	$version  = "";

	//First get the platform?
	if ( preg_match( '/linux/i', $u_agent ) ) {
		$platform = 'linux';
	} elseif ( preg_match( '/macintosh|mac os x/i', $u_agent ) ) {
		$platform = 'mac';
	} elseif ( preg_match( '/windows|win32/i', $u_agent ) ) {
		$platform = 'windows';
	}

	// Next get the name of the useragent yes seperately and for good reason
	if ( preg_match( '/MSIE/i', $u_agent ) && ! preg_match( '/Opera/i', $u_agent ) ) {
		$bname = 'Internet Explorer';
		$ub    = "MSIE";
	} elseif ( preg_match( '/Firefox/i', $u_agent ) ) {
		$bname = 'Mozilla Firefox';
		$ub    = "Firefox";
	} elseif ( preg_match( '/Chrome/i', $u_agent ) ) {
		$bname = 'Google Chrome';
		$ub    = "Chrome";
	} elseif ( preg_match( '/Safari/i', $u_agent ) ) {
		$bname = 'Apple Safari';
		$ub    = "Safari";
	} elseif ( preg_match( '/Opera/i', $u_agent ) ) {
		$bname = 'Opera';
		$ub    = "Opera";
	} elseif ( preg_match( '/Netscape/i', $u_agent ) ) {
		$bname = 'Netscape';
		$ub    = "Netscape";
	}

	// finally get the correct version number
	$known   = array( 'Version', $ub, 'other' );
	$pattern = '#(?<browser>' . join( '|', $known ) .
	           ')[/ ]+(?<version>[0-9.|a-zA-Z.]*)#';
	if ( ! preg_match_all( $pattern, $u_agent, $matches ) ) {
		// we have no matching number just continue
	}

	// see how many we have
	$i = count( $matches['browser'] );
	if ( $i != 1 ) {
		//we will have two since we are not using 'other' argument yet
		//see if version is before or after the name
		if ( strripos( $u_agent, "Version" ) < strripos( $u_agent, $ub ) ) {
			$version = $matches['version'][0];
		} else {
			$version = $matches['version'][1];
		}
	} else {
		$version = $matches['version'][0];
	}

	// check if we have a number
	if ( $version == null || $version == "" ) {
		$version = "?";
	}

	return array(
		'userAgent' => $u_agent,
		'name'      => $bname,
		'version'   => $version,
		'platform'  => $platform,
		'pattern'   => $pattern
	);
}

/**
 * Function to check if media record is exist.
 *
 * @param int    $id   media id
 * @param string $type media type
 *
 * @since BuddyBoss 1.7.5
 *
 * @return null|array|object|void
 */
function bb_moderation_get_media_record_by_id( $id, $type ) {
	global $wpdb;

	$record         = array();
	$media_table    = "{$wpdb->prefix}bp_media";
	$document_table = "{$wpdb->prefix}bp_document";

	if ( in_array( $type, array( 'media', 'video' ) ) ) {
		$media_sql = $wpdb->prepare( "SELECT activity_id FROM {$media_table} WHERE id=%d", $id );
		$record    = $wpdb->get_row( $media_sql );
	}

	if ( 'document' === $type ) {
		$document_sql = $wpdb->prepare( "SELECT activity_id FROM {$document_table} WHERE id=%d", $id );
		$record       = $wpdb->get_row( $document_sql );
	}

	return $record;
}

/**
 * Function to check if suspend record is exist.
 *
 * @param int $id id
 *
 * @since BuddyBoss 1.7.5
 *
 * @return null|array|object|void
 */
function bb_moderation_suspend_record_exist( $id ) {
	global $wpdb;

	$record = array();

	if ( ! $id ) {
		return $record;
	}

	$suspend_table = "{$wpdb->prefix}bp_suspend";

	$suspend_record_sql = $wpdb->prepare( "SELECT id,item_id,item_type,reported FROM {$suspend_table} WHERE item_id=%d", $id );
	$record             = $wpdb->get_row( $suspend_record_sql );

	return $record;
}

/**
 * Function to update suspend data.
 *
 * @param object $moderated_activities suspend records
 * @param int    $offset               pagination object
 *
 * @since BuddyBoss 1.7.5
 *
 * @return int|mixed
 */
function bb_moderation_update_suspend_data( $moderated_activities, $offset = 0 ) {
	global $wpdb;

	$suspend_table = "{$wpdb->prefix}bp_suspend";

	if ( ! empty( $moderated_activities ) ) {
		foreach ( $moderated_activities as $moderated_activity ) {
			if ( in_array( $moderated_activity->item_type, array( 'media', 'video' ) ) ) {
				$media_results = bb_moderation_get_media_record_by_id( $moderated_activity->item_id, $moderated_activity->item_type );
				if ( ! empty( $media_results ) ) {
					$suspend_record = bb_moderation_suspend_record_exist( $media_results->activity_id );
					if ( ! empty( $suspend_record ) && 1 === (int) $suspend_record->reported ) {
						$wpdb->update( $suspend_table, array(
							'item_id'   => $suspend_record->item_id,
							'item_type' => $suspend_record->item_type,
						), array( 'id' => $moderated_activity->id ) );

						$wpdb->update( $suspend_table, array(
							'item_id'   => $moderated_activity->item_id,
							'item_type' => $moderated_activity->item_type,
						), array( 'id' => $suspend_record->id ) );
					}
				}
			}

			if ( 'document' === $moderated_activity->item_type ) {
				$document_results = bb_moderation_get_media_record_by_id( $moderated_activity->item_id, 'document' );
				if ( ! empty( $document_results ) ) {
					$suspend_record = bb_moderation_suspend_record_exist( $document_results->activity_id );
					if ( ! empty( $suspend_record ) && 1 === (int) $suspend_record->reported ) {
						$wpdb->update( $suspend_table, array(
							'item_id'   => $suspend_record->item_id,
							'item_type' => $suspend_record->item_type,
						), array( 'id' => $moderated_activity->id ) );

						$wpdb->update( $suspend_table, array(
							'item_id'   => $moderated_activity->item_id,
							'item_type' => $moderated_activity->item_type,
						), array( 'id' => $suspend_record->id ) );
					}
				}
			}
			$offset ++;
		}
	}

	return $offset;
}

/**
 * Function to update moderation data on plugin update.
 *
 * @since BuddyBoss 1.7.5
 *
 * @return int|mixed|void
 */
function bb_moderation_bg_update_moderation_data() {
	global $wpdb;
	$suspend_table = "{$wpdb->prefix}bp_suspend";
	$table_exists  = (bool) $wpdb->get_results( "DESCRIBE {$suspend_table}" );

	if ( ! $table_exists ) {
		return;
	}

	$moderated_activities = $wpdb->get_results( "SELECT id,item_id,item_type FROM {$suspend_table} WHERE item_type IN ('media','video','document') GROUP BY id ORDER BY id DESC" );

	if ( ! empty( $moderated_activities ) ) {
		bb_moderation_update_suspend_data( $moderated_activities, 0 );
	}
}
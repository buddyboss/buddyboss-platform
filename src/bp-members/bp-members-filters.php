<?php
/**
 * BuddyPress Members Filters.
 *
 * Filters specific to the Members component.
 *
 * @package BuddyBoss\Members\Filters
 * @since BuddyPress 1.5.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Escape commonly used fullname output functions.
 */
add_filter( 'bp_displayed_user_fullname', 'esc_html' );
add_filter( 'bp_get_loggedin_user_fullname', 'esc_html' );

// Filter the user registration URL to point to BuddyPress's registration page.
add_filter( 'register_url', 'bp_get_signup_page' );

// Change the last active display format if users active within interval then shows 'Active now'.
add_filter( 'bp_get_last_activity', 'bb_get_member_last_active_within_minutes', 10, 2 );

// Repair member profile links.
add_filter( 'bp_repair_list', 'bb_repair_member_profile_links', 12 );

add_action( 'bb_assign_default_member_type_to_activate_user_on_admin', 'bb_set_default_member_type_to_activate_user_on_admin', 1, 2 );

/**
 * Load additional sign-up sanitization filters on bp_loaded.
 *
 * These are used to prevent XSS in the BuddyPress sign-up process. You can
 * unhook these to allow for customization of your registration fields;
 * however, it is highly recommended that you leave these in place for the
 * safety of your network.
 *
 * @since BuddyPress 1.5.0
 */
function bp_members_signup_sanitization() {

	// Filters on sign-up fields.
	$fields = array(
		'bp_get_signup_username_value',
		'bp_get_signup_email_value',
		'bp_get_signup_with_blog_value',
		'bp_get_signup_blog_url_value',
		'bp_get_signup_blog_title_value',
		'bp_get_signup_blog_privacy_value',
		'bp_get_signup_avatar_dir_value',
	);

	// Add the filters to each field.
	foreach ( $fields as $filter ) {
		add_filter( $filter, 'esc_html', 1 );
		add_filter( $filter, 'wp_filter_kses', 2 );
		add_filter( $filter, 'stripslashes', 3 );
	}

	// Sanitize email.
	add_filter( 'bp_get_signup_email_value', 'sanitize_email' );
}
add_action( 'bp_loaded', 'bp_members_signup_sanitization' );

/**
 * Make sure the username is not the blog slug in case of root profile & subdirectory blog.
 *
 * If BP_ENABLE_ROOT_PROFILES is defined & multisite config is set to subdirectories,
 * then there is a chance site.url/username == site.url/blogslug. If so, user's profile
 * is not reachable, instead the blog is displayed. This filter makes sure the signup username
 * is not the same than the blog slug for this particular config.
 *
 * @since BuddyPress 2.1.0
 *
 * @param array $illegal_names Array of illiegal names.
 * @return array $illegal_names
 */
function bp_members_signup_with_subdirectory_blog( $illegal_names = array() ) {
	if ( ! bp_core_enable_root_profiles() ) {
		return $illegal_names;
	}

	if ( is_network_admin() && isset( $_POST['blog'] ) ) {
		$blog   = $_POST['blog'];
		$domain = '';

		if ( preg_match( '|^([a-zA-Z0-9-])$|', $blog['domain'] ) ) {
			$domain = strtolower( $blog['domain'] );
		}

		if ( username_exists( $domain ) ) {
			$illegal_names[] = $domain;
		}
	} else {
		$illegal_names[] = buddypress()->signup->username;
	}

	return $illegal_names;
}
add_filter( 'subdirectory_reserved_names', 'bp_members_signup_with_subdirectory_blog', 10, 1 );

/**
 * Filter the user profile URL to point to BuddyPress profile edit.
 *
 * @since BuddyPress 1.6.0
 *
 * @param string $url     WP profile edit URL.
 * @param int    $user_id ID of the user.
 * @param string $scheme  Scheme to use.
 * @return string
 */
function bp_members_edit_profile_url( $url, $user_id, $scheme = 'admin' ) {

	// If xprofile is active, use profile domain link.
	if ( ! is_admin() && bp_is_active( 'xprofile' ) ) {
		$profile_link = trailingslashit( bp_core_get_user_domain( $user_id ) . bp_get_profile_slug() . '/edit' );

	} else {
		// Default to $url.
		$profile_link = $url;
	}

	/**
	 * Filters the user profile URL to point to BuddyPress profile edit.
	 *
	 * @since BuddyPress 1.5.2
	 *
	 * @param string $url WP profile edit URL.
	 * @param int    $user_id ID of the user.
	 * @param string $scheme Scheme to use.
	 */
	return apply_filters( 'bp_members_edit_profile_url', $profile_link, $url, $user_id, $scheme );
}
add_filter( 'edit_profile_url', 'bp_members_edit_profile_url', 10, 3 );

/**
 * Overwrites login form email field label.
 *
 * @since BuddyBoss 1.0.0
 */
function bp_overwrite_login_form_email_field_label( $defaults ) {
	$defaults['label_username'] = __( 'Email Address', 'buddyboss' );

	return $defaults;
}
add_filter( 'login_form_defaults', 'bp_overwrite_login_form_email_field_label' );

/**
 * Overwrites login email field label.
 *
 * @since BuddyBoss 1.0.0
 */
function bp_overwrite_login_email_field_label( $translated_text, $text, $domain ) {
	if ( 'Username or Email Address' == $text && 'default' == $domain ) {
		remove_filter( 'gettext', 'bp_overwrite_login_email_field_label' );
		return __( 'Email Address', 'buddyboss' );
	}

	return $translated_text;
}

/**
 * Overwrites login form email field label hook.
 *
 * @since BuddyBoss 1.0.0
 */
function bp_overwrite_login_email_field_label_hook() {
	add_filter( 'gettext', 'bp_overwrite_login_email_field_label', 10, 3 );
}
add_action( 'login_form_retrievepassword', 'bp_overwrite_login_email_field_label_hook' );
add_action( 'login_form_lostpassword', 'bp_overwrite_login_email_field_label_hook' );
add_action( 'login_form_login', 'bp_overwrite_login_email_field_label_hook' );

/**
 * Set up media arguments for use with the 'personal' scope.
 *
 * @since BuddyBoss 1.1.9
 *
 * @param array $retval Empty array by default.
 * @param array $filter Current activity arguments.
 * @return array
 */
function bp_members_filter_media_personal_scope( $retval = array(), $filter = array() ) {

	// Determine the user_id.
	if ( ! empty( $filter['user_id'] ) ) {
		$user_id = $filter['user_id'];
	} else {
		$user_id = bp_displayed_user_id()
			? bp_displayed_user_id()
			: bp_loggedin_user_id();
	}

	$privacy = array( 'public' );

	if ( bp_loggedin_user_id() ) {
		$privacy[] = 'loggedin';

		if ( (int) $user_id === (int) bp_loggedin_user_id() ) {
			$privacy[] = 'onlyme';
		}

		if ( bp_is_active( 'friends' ) && bp_is_profile_media_support_enabled() ) {
			if ( (int) $user_id === (int) bp_loggedin_user_id() ) {
				$privacy[] = 'friends';
			} else {
				$friends = friends_get_friend_user_ids( $user_id );
				if ( ! empty( $friends ) && in_array( (int) bp_loggedin_user_id(), $friends, true ) ) {
					$privacy[] = 'friends';
				}
			}
		}
	}

	$retval = array(
		'relation' => 'AND',
		array(
			'column' => 'user_id',
			'value'  => $user_id,
		),
		array(
			'column'  => 'privacy',
			'value'   => $privacy,
			'compare' => 'IN',
		),
	);

	if ( ! bp_is_profile_albums_support_enabled() ) {
		$retval[] = array(
			'column'  => 'album_id',
			'compare' => '=',
			'value'   => '0',
		);
	}

	if ( ! empty( $filter['search_terms'] ) ) {
		$retval[] = array(
			'column'  => 'title',
			'compare' => 'LIKE',
			'value'   => $filter['search_terms'],
		);
	}

	return $retval;
}
add_filter( 'bp_media_set_personal_scope_args', 'bp_members_filter_media_personal_scope', 10, 2 );

/**
 * Set up video arguments for use with the 'personal' scope.
 *
 * @since BuddyBoss 1.5.7
 *
 * @param array $retval Empty array by default.
 * @param array $filter Current activity arguments.
 * @return array
 */
function bp_members_filter_video_personal_scope( $retval = array(), $filter = array() ) {

	// Determine the user_id.
	if ( ! empty( $filter['user_id'] ) ) {
		$user_id = $filter['user_id'];
	} else {
		$user_id = bp_displayed_user_id()
			? bp_displayed_user_id()
			: bp_loggedin_user_id();
	}

	$privacy = array( 'public' );

	if ( bp_loggedin_user_id() ) {
		$privacy[] = 'loggedin';

		if ( bp_loggedin_user_id() === (int) $user_id ) {
			$privacy[] = 'onlyme';
		}

		if ( bp_is_active( 'friends' ) && bp_is_profile_video_support_enabled() ) {
			if ( bp_loggedin_user_id() === (int) $user_id ) {
				$privacy[] = 'friends';
			} else {
				$friends = friends_get_friend_user_ids( $user_id );
				if ( ! empty( $friends ) && in_array( (int) bp_loggedin_user_id(), $friends, true ) ) {
					$privacy[] = 'friends';
				}
			}
		}
	}

	$retval = array(
		'relation' => 'AND',
		array(
			'column' => 'user_id',
			'value'  => $user_id,
		),
		array(
			'column'  => 'privacy',
			'value'   => $privacy,
			'compare' => 'IN',
		),
	);

	if ( ! bp_is_profile_albums_support_enabled() ) {
		$retval[] = array(
			'column'  => 'album_id',
			'compare' => '=',
			'value'   => '0',
		);
	}

	if ( ! empty( $filter['search_terms'] ) ) {
		$retval[] = array(
			'column'  => 'title',
			'compare' => 'LIKE',
			'value'   => $filter['search_terms'],
		);
	}

	return $retval;
}
add_filter( 'bp_video_set_personal_scope_args', 'bp_members_filter_video_personal_scope', 10, 2 );

/**
 * Set up media arguments for use with the 'personal' scope.
 *
 * @since BuddyBoss 1.1.9
 *
 * @param array $retval Empty array by default.
 * @param array $filter Current activity arguments.
 * @return array
 */
function bp_members_filter_document_personal_scope( $retval = array(), $filter = array() ) {

	// Determine the user_id.
	if ( ! empty( $filter['user_id'] ) ) {
		$user_id = $filter['user_id'];
	} else {
		$user_id = bp_displayed_user_id()
			? bp_displayed_user_id()
			: bp_loggedin_user_id();
	}

	$folder_id = 0;
	$folders   = array();
	if ( ! empty( $filter['folder_id'] ) ) {
		$folder_id = (int) $filter['folder_id'];
	}

	$privacy = array( 'public' );

	if ( is_user_logged_in() ) {
		$privacy[] = 'loggedin';

		if ( bp_is_active( 'friends' ) ) {
			$friends = friends_get_friend_user_ids( $user_id );
			if ( ( ! empty( $friends ) && in_array( bp_loggedin_user_id(), $friends ) ) || $user_id === bp_loggedin_user_id() ) {
				$privacy[] = 'friends';
			}
		}

		if ( $user_id === bp_loggedin_user_id() ) {
			$privacy[] = 'onlyme';
		}
	}

	if ( ! bp_is_profile_document_support_enabled() ) {
		$user_id = '0';
	}

	if ( ! empty( $filter['search_terms'] ) ) {
		if ( ! empty( $folder_id ) ) {
			$user_root_folder_ids = bp_document_get_folder_children( (int) $folder_id );

			$folder_ids = array();
			if ( $user_root_folder_ids ) {
				foreach ( $user_root_folder_ids as $single_folder ) {
					$single_folder_ids = bp_document_get_folder_children( (int) $single_folder );
					if ( $single_folder_ids ) {
						array_merge( $folder_ids, $single_folder_ids );
					}
					array_push( $folder_ids, $single_folder );
				}
			}
			$folder_ids[] = $folder_id;
			$folders      = array(
				'column'  => 'folder_id',
				'compare' => 'IN',
				'value'   => $folder_ids,
			);
		}
	} else {
		if ( ! empty( $folder_id ) ) {
			$folders = array(
				'column'  => 'folder_id',
				'compare' => '=',
				'value'   => $folder_id,
			);
		} else {
			$folders = array(
				'column' => 'folder_id',
				'value'  => 0,
			);
		}
	}

	$args = array(
		'relation' => 'AND',
		array(
			'column'  => 'user_id',
			'compare' => '=',
			'value'   => $user_id,
		),
		array(
			'column'  => 'privacy',
			'compare' => 'IN',
			'value'   => $privacy,
		),
		array(
			'column'  => 'group_id',
			'compare' => '=',
			'value'   => '0',
		),
		$folders,
	);

	return $args;
}
add_filter( 'bp_document_set_document_personal_scope_args', 'bp_members_filter_document_personal_scope', 10, 2 );

/**
 * Set up media arguments for use with the 'personal' scope.
 *
 * @since BuddyBoss 1.1.9
 *
 * @param array $retval Empty array by default.
 * @param array $filter Current activity arguments.
 * @return array
 */
function bp_members_filter_folder_personal_scope( $retval = array(), $filter = array() ) {

	if ( ! bp_is_profile_document_support_enabled() ) {
		return $retval;
	}

	// Determine the user_id.
	if ( ! empty( $filter['user_id'] ) ) {
		$user_id = (int) $filter['user_id'];
	} else {
		$user_id = bp_displayed_user_id()
			? bp_displayed_user_id()
			: bp_loggedin_user_id();
	}

	$folder_id = 0;
	$folders   = array();

	if ( ! empty( $filter['folder_id'] ) ) {
		$folder_id = (int) $filter['folder_id'];
	}

	if ( ! empty( $filter['search_terms'] ) ) {
		if ( ! empty( $folder_id ) ) {
			$user_root_folder_ids = bp_document_get_folder_children( (int) $folder_id );

			$folder_ids = array();
			if ( $user_root_folder_ids ) {
				foreach ( $user_root_folder_ids as $single_folder ) {
					$single_folder_ids = bp_document_get_folder_children( (int) $single_folder );
					if ( $single_folder_ids ) {
						array_merge( $folder_ids, $single_folder_ids );
					}
					array_push( $folder_ids, $single_folder );
				}
			}
			$folder_ids[] = $folder_id;
			$folders      = array(
				'column'  => 'parent',
				'compare' => 'IN',
				'value'   => $folder_ids,
			);
		}
	} else {
		if ( ! empty( $folder_id ) ) {
			$folders = array(
				'column'  => 'parent',
				'compare' => '=',
				'value'   => $folder_id,
			);
		} else {
			$folders = array(
				'column' => 'parent',
				'value'  => 0,
			);
		}
	}

	$privacy = array( 'public' );

	if ( is_user_logged_in() ) {
		$privacy[] = 'loggedin';

		if ( bp_is_active( 'friends' ) ) {
			$friends = friends_get_friend_user_ids( $user_id );
			if ( ( ! empty( $friends ) && in_array( bp_loggedin_user_id(), $friends ) ) || $user_id === bp_loggedin_user_id() ) {
				$privacy[] = 'friends';
			}
		}

		if ( $user_id === bp_loggedin_user_id() ) {
			$privacy[] = 'onlyme';
		}
	}

	$args = array(
		'relation' => 'AND',
		array(
			'column'  => 'user_id',
			'compare' => '=',
			'value'   => $user_id,
		),
		array(
			'column'  => 'privacy',
			'compare' => 'IN',
			'value'   => $privacy,
		),
		$folders,
	);

	return $args;
}
add_filter( 'bp_document_set_folder_personal_scope_args', 'bp_members_filter_folder_personal_scope', 10, 2 );

/**
 * Used by the Activity component's @mentions to print a JSON list of the latest 10 users.
 *
 * This is intended to speed up @mentions lookups for a majority of use cases.
 *
 * @since buddyboss 1.8.6
 *
 * @see   bp_activity_mentions_script()
 */
function bb_core_prime_mentions_results() {

	// Stop here if user is not logged in.
	if ( ! is_user_logged_in() ) {
		return;
	}

	// Bail out if the site has a ton of users.
	if ( bp_is_large_install() ) {
		return;
	}

	// Bail if single group page.
	if ( bp_is_group() ) {
		return;
	}

	$members_query = array(
		'count_total'     => '', // Prevents total count.
		'populate_extras' => false,
		'per_page'        => 10,
		'page'            => 1,
		'type'            => 'active',
		'exclude'         => array( get_current_user_id() ),
	);

	$members_query = new BP_User_Query( $members_query );
	$members       = array();

	foreach ( $members_query->results as $user ) {
		$result        = new stdClass();
		$result->ID    = bp_activity_get_user_mentionname( $user->ID );
		$result->image = bp_core_fetch_avatar(
			array(
				'html'    => false,
				'item_id' => $user->ID,
			)
		);

		if ( ! empty( $user->display_name ) && ! bp_disable_profile_sync() ) {
			$result->name = $user->display_name;
		} else {
			$result->name = bp_core_get_user_displayname( $user->ID );
		}
		$result->user_id = $user->ID;

		$members[] = $result;
	}

	$friends = array();

	if ( bp_is_active( 'friends' ) ) {

		if ( friends_get_total_friend_count( get_current_user_id() ) > 30 ) {
			return;
		}

		$friends_query = array(
			'count_total'     => '',                    // Prevents total count.
			'populate_extras' => false,
			'type'            => 'alphabetical',
			'user_id'         => get_current_user_id(),
		);

		$friends_query = new BP_User_Query( $friends_query );

		foreach ( $friends_query->results as $user ) {
			$result        = new stdClass();
			$result->ID    = bp_activity_get_user_mentionname( $user->ID );
			$result->image = bp_core_fetch_avatar(
				array(
					'html'    => false,
					'item_id' => $user->ID,
				)
			);

			if ( ! empty( $user->display_name ) && ! bp_disable_profile_sync() ) {
				$result->name = bp_core_get_user_displayname( $user->ID );
			} else {
				$result->name = bp_core_get_user_displayname( $user->ID );
			}
			$result->user_id = $user->ID;

			$friends[] = $result;
		}
	}

	wp_localize_script(
		'bp-mentions',
		'BP_Suggestions',
		array(
			'members' => $members,
			'friends' => $friends,
		)
	);
}

add_action( 'bp_activity_mentions_prime_results', 'bb_core_prime_mentions_results' );
add_action( 'bbp_forums_mentions_prime_results', 'bb_core_prime_mentions_results' );

/**
 * Get member last active difference in minutes.
 *
 * @param string $last_activity Formatted 'active [x days ago]' string.
 * @param int    $user_id ID of the user. Default: displayed user ID.
 *
 * @since BuddyBoss 1.9.1
 *
 * @return string Return string if time difference within minutes otherwise $last_activity.
 */
function bb_get_member_last_active_within_minutes( $last_activity, $user_id ) {

	$last_active_date = bp_get_user_last_activity( $user_id );
	if ( empty( $last_active_date ) ) {
		return $last_activity;
	}

	// Get Unix timestamp from datetime.
	$time_chunks           = explode( ':', str_replace( ' ', ':', $last_active_date ) );
	$date_chunks           = explode( '-', str_replace( ' ', '-', $last_active_date ) );
	$last_active_timestamp = gmmktime( (int) $time_chunks[1], (int) $time_chunks[2], (int) $time_chunks[3], (int) $date_chunks[1], (int) $date_chunks[2], (int) $date_chunks[0] );

	// Difference in seconds.
	$since_diff = bp_core_current_time( true, 'timestamp' ) - $last_active_timestamp;
	if ( $since_diff < HOUR_IN_SECONDS && $since_diff >= 0 ) {

		$online_default_time = apply_filters( 'bb_default_online_presence_time', bb_presence_interval() + bb_presence_time_span() );

		if ( $online_default_time >= $since_diff ) {
			return esc_html__( 'Active now', 'buddyboss' );
		}
	}

	return $last_activity;
}

/**
 * Allow HTML for member xprofile data.
 *
 * @since BuddyBoss 1.9.1
 *
 * @param array $bbp_allow_tags The array allow custom tags and attributes. Default: null.
 *
 * @return array Associative array of allowed tags and attributes.
 */
function bb_members_allow_html_tags( $bbp_allow_tags = array() ) {
	// Allow tag attributes for xprofile datas.
	$bbp_allow_tags = array_merge( $bbp_allow_tags, wp_kses_allowed_html( 'post' ) );

	// Allow "svg" for social networks.
	$bbp_allow_tags['svg']  = array(
		'xmlns'       => array(),
		'fill'        => array(),
		'viewbox'     => array(),
		'role'        => array(),
		'aria-hidden' => array(),
		'focusable'   => array(),
		'fill-rule'   => array(),
		'clip-rule'   => array(),
	);
	$bbp_allow_tags['path'] = array(
		'd'    => array(),
		'fill' => array(),
	);
	$bbp_allow_tags['g']    = array(
		'transform' => array(),
		'fill'      => array(),
	);

	return apply_filters( 'bb_members_allow_html_tags', $bbp_allow_tags );
}

// Load Account Settings Notifications.
add_action( 'bp_members_includes', 'bb_load_members_account_settings_notifications' );

/**
 * Register the Account Settings notifications.
 *
 * @since BuddyBoss 1.9.3
 */
function bb_load_members_account_settings_notifications() {
	if ( class_exists( 'BP_Members_Mentions_Notification' ) ) {
		BP_Members_Mentions_Notification::instance();
	}

	if ( class_exists( 'BP_Members_Notification' ) ) {
		BP_Members_Notification::instance();
	}
}

/**
 * Function will add custom css for all member type's label. ( i.e - Background color, Text color)
 *
 * @since BuddyBoss 2.0.0
 */
function bb_load_member_type_label_custom_css() {
	if ( true === bp_member_type_enable_disable() ) {
		$registered_member_types = bp_get_member_types();
		$cache_key               = 'bb-member-type-label-css';
		$member_type_custom_css  = wp_cache_get( $cache_key, 'bp_member_member_type' );
		if ( false === $member_type_custom_css && ! empty( $registered_member_types ) ) {
			foreach ( $registered_member_types as $type ) {
				$label_color_data = function_exists( 'bb_get_member_type_label_colors' ) ? bb_get_member_type_label_colors( $type ) : '';
				if (
					isset( $label_color_data ) &&
					isset( $label_color_data['color_type'] ) &&
					'custom' === $label_color_data['color_type']
				) {
					$background_color        = isset( $label_color_data['background-color'] ) ? $label_color_data['background-color'] : '';
					$text_color              = isset( $label_color_data['color'] ) ? $label_color_data['color'] : '';
					$class_name              = 'body .bp-member-type.bb-current-member-' . $type;
					$member_type_custom_css .= $class_name . ' {' . "background-color:$background_color;" . '}';
					$member_type_custom_css .= $class_name . ' {' . "color:$text_color;" . '}';
				}
			}
			wp_cache_set( $cache_key, $member_type_custom_css, 'bp_member_member_type' );
		}
		wp_add_inline_style( 'bp-nouveau', $member_type_custom_css );
	}
}
add_action( 'bp_enqueue_scripts', 'bb_load_member_type_label_custom_css', 12 );

/**
 * Remove all subscription associations for a given user.
 *
 * @since BuddyBoss 2.2.6
 *
 * @param int $user_id ID whose subscription data should be removed.
 *
 * @return bool Returns false on failure.
 */
function bb_delete_user_subscriptions( $user_id ) {
	// Get the user subscriptions.
	$all_subscriptions = bb_get_subscriptions(
		array(
			'blog_id' => false,
			'user_id' => $user_id,
			'fields'  => 'id',
			'status'  => null,
			'cache'   => false,
		),
		true
	);
	$subscriptions     = ! empty( $all_subscriptions['subscriptions'] ) ? $all_subscriptions['subscriptions'] : array();

	if ( ! empty( $subscriptions ) ) {
		foreach ( $subscriptions as $subscription ) {
			bb_delete_subscription( $subscription );
		}
	}

	return true;
}
add_action( 'wpmu_delete_user', 'bb_delete_user_subscriptions' );
add_action( 'delete_user', 'bb_delete_user_subscriptions' );


/**
 * Add repair member profile links.
 *
 * @since BuddyBoss 2.3.1
 *
 * @param array $repair_list Repair list items.
 *
 * @return array Repair list items.
 */
function bb_repair_member_profile_links( $repair_list ) {
	$repair_list[] = array(
		'bb-member-repair-profile-links',
		__( 'Repair member profile links', 'buddyboss' ),
		'bb_repair_member_profile_links_callback',
	);

	return $repair_list;
}

/**
 * This function will work as migration process which will repair member profile links.
 *
 * @since BuddyBoss 2.3.1
 *
 * @return array|void
 */
function bb_repair_member_profile_links_callback() {
	if ( ! bp_is_active( 'members' ) ) {
		return;
	}

	global $wpdb;

	// phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPress.Security.ValidatedSanitizedInput.MissingUnslash
	$offset    = isset( $_POST['offset'] ) ? (int) ( $_POST['offset'] ) : 0;
	$per_page  = 20;
	$bp_prefix = bp_core_get_table_prefix();

	// Set limit while repair the member slug.
	$user_ids = $wpdb->get_col(
		$wpdb->prepare(
			"SELECT u.ID FROM `{$wpdb->users}` AS u LEFT JOIN `{$wpdb->usermeta}` AS um ON ( u.ID = um.user_id AND um.meta_key = %s ) WHERE ( um.user_id IS NULL OR LENGTH(meta_value) = %d ) ORDER BY u.ID ASC LIMIT %d, %d",
			'bb_profile_slug',
			40,
			0,
			$per_page
		)
	);

	if ( ! is_wp_error( $user_ids ) && ! empty( $user_ids ) ) {
		bb_set_bulk_user_profile_slug( $user_ids );

		$total           = $offset + count( $user_ids );
		$records_updated = sprintf(
		/* translators: total user */
			_n( '%d user unique identifier generated successfully', '%d users unique identifier generated successfully', $total, 'buddyboss' ),
			$total
		);

		return array(
			'status'  => 'running',
			'offset'  => $total,
			'records' => $records_updated,
		);
	} else {
		/* translators: Status of current action. */
		$statement = __( 'Profile unique identifier generated for all users; %s', 'buddyboss' );

		// All done!
		return array(
			'status'  => 1,
			'message' => sprintf( $statement, __( 'Complete!', 'buddyboss' ) ),
		);
	}
}

/**
 * Assign the default member type to user who register recently.
 *
 * @since BuddyBoss 2.3.2
 *
 * @param int    $user_id     ID of user.
 * @param string $member_type Default selected member type.
 */
function bb_set_default_member_type_to_activate_user_on_admin( $user_id, $member_type ) {

	if ( empty( $user_id ) || empty( $member_type ) ) {
		return false;
	}

	if ( is_admin() ) {

		// Assign the default member type to user.
		bp_set_member_type( $user_id, '' );
		bp_set_member_type( $user_id, $member_type );
	} else {
		// Assign the default member type to user.
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
	}
}

/**
 * This function will work as migration process which will repair member profile links.
 *
 * @since BuddyBoss 2.3.41
 *
 * @return array|void
 */
function bb_generate_member_profile_links_on_update() {
	if ( ! bp_is_active( 'members' ) ) {
		return;
	}

	global $wpdb, $bp_background_updater;
	$bp_prefix = bp_core_get_table_prefix();

	// Get all users who have not generate unique slug while it runs from background.
	$user_ids = $wpdb->get_col(
		$wpdb->prepare(
			"SELECT u.ID FROM `{$wpdb->users}` AS u LEFT JOIN `{$wpdb->usermeta}` AS um ON ( u.ID = um.user_id AND um.meta_key = %s ) WHERE um.user_id IS NULL ORDER BY u.ID ASC",
			'bb_profile_slug'
		)
	);

	if ( ! is_wp_error( $user_ids ) && ! empty( $user_ids ) ) {
		foreach ( array_chunk( $user_ids, 50 ) as $chunked_user_ids ) {
			$bp_background_updater->data(
				array(
					array(
						'callback' => 'bb_set_bulk_user_profile_slug',
						'args'     => array( $chunked_user_ids ),
					),
				)
			);
			$bp_background_updater->save()->dispatch();
		}
	}
}

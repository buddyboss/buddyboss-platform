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

add_action( 'update_option_bp-display-name-format', 'bb_member_remove_default_png_avatar_on_update_display_name', 10, 3 );
add_action( 'deleted_user', 'bb_member_remove_default_png_avatar_on_deleted_user' );

// Exclude account related notifications.
add_filter( 'bp_notifications_get_where_conditions', 'bb_members_hide_account_settings_notifications', 10, 2 );

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
		// Remove filter to support apostrophe in useremail.
		if ( 'bp_get_signup_email_value' !== $filter ) {
			add_filter( $filter, 'esc_html', 1 );
		}
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
			'relation' => 'OR',
			array(
				'column'  => 'title',
				'compare' => 'LIKE',
				'value'   => $filter['search_terms'],
			),
			array(
				'column'  => 'description',
				'compare' => 'LIKE',
				'value'   => $filter['search_terms'],
			)
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
			'relation' => 'OR',
			array(
				'column'  => 'title',
				'compare' => 'LIKE',
				'value'   => $filter['search_terms'],
			),
			array(
				'column'  => 'description',
				'compare' => 'LIKE',
				'value'   => $filter['search_terms'],
			)
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
 * @since BuddyBoss 1.8.6
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

		// Resolve the name for the current viewer so a last name hidden by profile-field
		// visibility is not localized into the page for every logged-in user. The raw
		// $user->display_name column is viewer-agnostic; the friends branch below already
		// resolves it, and bp_disable_profile_sync() is effectively always false, so the raw
		// branch previously ran on every request.
		$result->name    = bp_core_get_user_displayname( $user->ID );
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

			// Always resolved through the viewer-aware helper: the raw display_name column can
			// carry a name part this viewer is denied, and profile sync has no bearing on that.
			// Both arms of the branch that used to stand here had become identical, which read as
			// though sync still changed the answer.
			$result->name    = bp_core_get_user_displayname( $user->ID );
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
add_action( 'bp_loaded', 'bb_load_members_account_settings_notifications' );

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
					$member_type_custom_css .= $class_name . ' {' . "background-color:$background_color !important;" . '}';
					$member_type_custom_css .= $class_name . ' {' . "color:$text_color !important;" . '}';
				}
			}
			wp_cache_set( $cache_key, $member_type_custom_css, 'bp_member_member_type' );
		}
		wp_add_inline_style( 'bp-nouveau', $member_type_custom_css );
	}

	// load the member card template.
	bb_profile_card_template();
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
	$per_page  = apply_filters( 'bb_core_update_repair_member_slug_limit', 50 );

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
 * Generate profile slug registration and activation.
 *
 * @since BuddyBoss 2.4.90
 *
 * @param int $user_id ID of user.
 *
 * @return void
 */
function bb_generate_member_profile_slug_on_activate( $user_id ) {
	if ( empty( $user_id ) ) {
		return;
	}

	$username = bb_core_get_user_slug( $user_id );
	if ( empty( $username ) ) {
		bb_set_user_profile_slug( $user_id );
	}
}

add_action( 'bp_core_signup_user', 'bb_generate_member_profile_slug_on_activate', 10, 1 );
add_action( 'bp_core_activated_user', 'bb_generate_member_profile_slug_on_activate', 10, 1 );

/**
 * Function to exclude the account settings related notification.
 *
 * @since BuddyBoss 2.5.30
 *
 * @param array $where_conditions Where clause to get notifications.
 * @param array $args             Parsed arguments to get notifications.
 *
 * @return array
 */
function bb_members_hide_account_settings_notifications( $where_conditions, $args ) {

	if ( ! bp_is_active( 'settings' ) ) {
		$where_conditions['account_settings_exclude'] = "( component_action != 'bb_account_password' )";
	}

	return $where_conditions;
}

/**
 * Delete default PNG for members when update the display name format.
 *
 * @since BuddyBoss 2.5.50
 *
 * @param mixed  $old_value The old option value.
 * @param mixed  $value     The new option value.
 * @param string $option    Option name.
 *
 * @return void
 */
function bb_member_remove_default_png_avatar_on_update_display_name( $old_value, $value, $option ) {
	if (
		'bp-display-name-format' === $option &&
		$old_value !== $value
	) {
		// Delete default SVG for users.
		bb_delete_default_user_png_avatar( array(), false );
	}
}

/**
 * Delete default PNG for member when delete the user.
 *
 * @since BuddyBoss 2.5.50
 *
 * @param int $id ID of the user.
 *
 * @return void
 */
function bb_member_remove_default_png_avatar_on_deleted_user( $id ) {
	// Delete default PNG for users.
	bb_delete_default_user_png_avatar( array( $id ) );
}

/**
 * Add profile hover card template.
 *
 * @since BuddyBoss 2.8.20
 */
function bb_profile_card_template() {
	bp_get_template_part( 'members/profile-card' );
}

/**
 * Inject a custom ORDER BY for the Settings 2.0 admin Profile Types listing.
 *
 * Restores two design rules that `WP_Query::parse_orderby()` cannot express
 * with its built-in `orderby` keys:
 *   1. Private items first — toggling a type to private bubbles it to the
 *      top of the list on the next fetch.
 *   2. Within each visibility group, oldest → newest so newly added types
 *      fall to the END of their group instead of the top (the upstream
 *      `bp_get_active_member_types()` default of `menu_order ASC, post_date
 *      DESC` does the opposite).
 *
 * `FIELD( post_status, 'private' )` returns 1 for private rows and 0 for
 * everything else; `DESC` surfaces the 1s first. The secondary `post_date
 * ASC` orders within each group. Scoped to the member-type post type so it
 * is safe even if registered globally — any other WP_Query that runs while
 * this filter is registered returns its original `$orderby` unchanged. The
 * AJAX handler that needs this order still removes the filter immediately
 * after its single fetch as defense-in-depth.
 *
 * @since BuddyBoss 3.0.0
 *
 * @param string   $orderby The current ORDER BY clause produced by WP_Query.
 * @param WP_Query $query   The query object being filtered.
 *
 * @return string Filtered ORDER BY clause.
 */
function bb_admin_member_types_listing_orderby( $orderby, $query ) {
	global $wpdb;

	if ( bp_get_member_type_post_type() !== $query->get( 'post_type' ) ) {
		return $orderby;
	}

	return "FIELD({$wpdb->posts}.post_status, 'private') DESC, {$wpdb->posts}.post_date ASC";
}

/**
 * Name a member may be shown under on a WordPress-core author surface, when BuddyBoss redacts it.
 *
 * The name a member is shown under is resolved by bp_core_get_user_displayname(), which applies
 * profile-field visibility and the site-wide Display Name Format. WordPress core does not go
 * through it: several emitters read the raw `display_name` column straight off the user object, so
 * a name part the community hides is published on surfaces BuddyBoss never rendered - the author
 * archive title, its feed, the feed autodiscovery link and the `wp/v2/users` REST response
 * (PROD-9896).
 *
 * This returns a replacement ONLY when BuddyBoss actually redacts something. When the resolved name
 * equals the stored column - the normal case, and always the case for a moderator - it returns null
 * and the caller leaves core's own output untouched.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param int $user_id ID of the member whose name is about to be emitted.
 * @return string|null The name to show instead, or null to leave the value alone.
 */
function bb_core_get_redacted_core_author_name( $user_id ) {
	$user_id = (int) $user_id;

	// A resolution already in flight is reading the stored column as its own input; standing in
	// front of that read would feed it a redacted name resolved for the wrong viewer, and recurse.
	// See bb_core_is_resolving_user_displayname().
	if ( empty( $user_id ) || bb_core_is_resolving_user_displayname() ) {
		return null;
	}

	// wp-admin screens are left alone. They are an authenticated, capability-gated context that
	// renders names core's own way throughout, and user management needs the canonical column: a
	// site on the "First Name" format would otherwise list a dozen identical "Peter" rows with no
	// way to tell them apart. admin-ajax.php is NOT exempt - it serves front-end requests.
	if ( is_admin() && ! wp_doing_ajax() ) {
		return null;
	}

	/**
	 * Filters whether BuddyBoss name visibility is applied to WordPress core author output.
	 *
	 * Covers the author archive title, the author feed, the feed autodiscovery link, `the_author`
	 * and the `wp/v2/users` REST response. Return false to leave WordPress core emitting the raw
	 * `display_name` column on those surfaces.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param bool $redact  Whether to apply the redaction. Default true.
	 * @param int  $user_id ID of the member whose name is being emitted.
	 */
	if ( ! apply_filters( 'bb_core_redact_core_author_name', true, $user_id ) ) {
		return null;
	}

	if ( ! function_exists( 'bp_core_get_user_displayname' ) ) {
		return null;
	}

	$user_data = get_userdata( $user_id );

	if ( empty( $user_data ) ) {
		return null;
	}

	$raw_display_name = (string) $user_data->display_name;

	if ( '' === $raw_display_name ) {
		return null;
	}

	// bp_core_get_user_displayname() raises the marker itself for the branch that reads the stored
	// column; raise it here too so the branches that return before that read are covered as well.
	bb_core_is_resolving_user_displayname( true );
	$resolved = bp_core_get_user_displayname( $user_id );
	bb_core_is_resolving_user_displayname( false );

	if ( ! is_string( $resolved ) || '' === $resolved || $resolved === $raw_display_name ) {
		return null;
	}

	return $resolved;
}

/**
 * Apply member name visibility to `get_the_author_meta( 'display_name' )`.
 *
 * Reaches WordPress core's author feed autodiscovery link, comment author output and every theme
 * or plugin that asks core for an author's display name.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param string $display_name The stored display name.
 * @param int    $user_id      ID of the author.
 * @return string The name this viewer may see.
 */
function bb_core_filter_the_author_display_name( $display_name, $user_id ) {
	$redacted = bb_core_get_redacted_core_author_name( $user_id );

	return ( null === $redacted ) ? $display_name : $redacted;
}
add_filter( 'get_the_author_display_name', 'bb_core_filter_the_author_display_name', 10, 2 );

/**
 * Apply member name visibility to `get_the_author()`.
 *
 * Core reads $authordata->display_name directly here, so the value never passes through
 * get_the_author_meta() and needs its own callback. Feeds the archive title prefix
 * (get_the_archive_title()) and `the_author()` in themes.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param string|null $display_name The stored display name, or null when there is no author.
 * @return string|null The name this viewer may see.
 */
function bb_core_filter_the_author( $display_name ) {
	if ( ! is_string( $display_name ) || '' === $display_name || empty( $GLOBALS['authordata']->ID ) ) {
		return $display_name;
	}

	$redacted = bb_core_get_redacted_core_author_name( $GLOBALS['authordata']->ID );

	return ( null === $redacted ) ? $display_name : $redacted;
}
add_filter( 'the_author', 'bb_core_filter_the_author' );

/**
 * Apply member name visibility to the author archive's document title.
 *
 * wp_get_document_title() reads get_queried_object()->display_name directly, with no filter of its
 * own, so the raw column reaches both `<title>` on the author archive and `<title>` in that
 * author's feed (get_wp_title_rss() calls wp_get_document_title()).
 *
 * Only the author archive is touched, and only the author's own raw name inside it, so a title a
 * theme or SEO plugin has already rewritten keeps whatever else it contains.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param array $title_parts Parts of the document title.
 * @return array The same parts with the author's hidden name part removed.
 */
function bb_core_filter_author_document_title_parts( $title_parts ) {
	if ( empty( $title_parts['title'] ) || ! is_string( $title_parts['title'] ) || ! is_author() ) {
		return $title_parts;
	}

	$author = get_queried_object();

	if ( empty( $author->ID ) || empty( $author->display_name ) ) {
		return $title_parts;
	}

	$redacted = bb_core_get_redacted_core_author_name( $author->ID );

	if ( null === $redacted ) {
		return $title_parts;
	}

	$title_parts['title'] = str_replace( $author->display_name, $redacted, $title_parts['title'] );

	return $title_parts;
}
add_filter( 'document_title_parts', 'bb_core_filter_author_document_title_parts' );

/**
 * Apply member name visibility to the `wp/v2/users` REST response.
 *
 * WP_REST_Users_Controller copies the raw `display_name` column into the `name` field. The route is
 * readable by anonymous callers for any user who has published content, so without this the
 * community's own members endpoint redacts a name that WordPress' endpoint hands out in full.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param WP_REST_Response $response The response object.
 * @param WP_User          $user     The user object used to create the response.
 * @return WP_REST_Response The response, with `name` resolved for the requesting viewer.
 */
function bb_core_filter_rest_prepare_user( $response, $user ) {
	if ( ! ( $response instanceof WP_REST_Response ) || empty( $user->ID ) ) {
		return $response;
	}

	// REST is not is_admin(), so the wp-admin exemption in the resolver cannot see the user-picker
	// and user-list requests the block editor and wp-admin screens make over this route. Gate them
	// on the capability those screens require instead, for the same reason: user management needs
	// the canonical column to tell two members with the same first name apart. Anonymous callers
	// and ordinary members - the audience this ticket is about - never hold it.
	if ( current_user_can( 'list_users' ) ) {
		return $response;
	}

	$data = $response->get_data();

	if ( ! is_array( $data ) || ! isset( $data['name'] ) ) {
		return $response;
	}

	$redacted = bb_core_get_redacted_core_author_name( $user->ID );

	if ( null === $redacted ) {
		return $response;
	}

	$data['name'] = $redacted;
	$response->set_data( $data );

	return $response;
}
add_filter( 'rest_prepare_user', 'bb_core_filter_rest_prepare_user', 10, 2 );

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
			'compare' => 'IN'
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
			'value'   => $privacy
		),
		array(
			'column'  => 'group_id',
			'compare' => '=',
			'value'   => '0',
		),
		$folders
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

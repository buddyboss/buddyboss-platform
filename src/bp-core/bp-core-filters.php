<?php
/**
 * BuddyPress Filters.
 *
 * This file contains the filters that are used throughout BuddyPress. They are
 * consolidated here to make searching for them easier, and to help developers
 * understand at a glance the order in which things occur.
 *
 * There are a few common places that additional filters can currently be found.
 *
 *  - BuddyPress: In {@link BuddyPress::setup_actions()} in buddypress.php
 *  - Component: In {@link BP_Component::setup_actions()} in
 *                bp-core/bp-core-component.php
 *  - Admin: More in {@link BP_Admin::setup_actions()} in
 *            bp-core/bp-core-admin.php
 *
 * @package BuddyBoss\Core
 * @since BuddyPress 1.5.0
 *
 * @see bp-core-actions.php
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Attach BuddyPress to WordPress.
 *
 * BuddyPress uses its own internal actions to help aid in third-party plugin
 * development, and to limit the amount of potential future code changes when
 * updates to WordPress core occur.
 *
 * These actions exist to create the concept of 'plugin dependencies'. They
 * provide a safe way for plugins to execute code *only* when BuddyPress is
 * installed and activated, without needing to do complicated guesswork.
 *
 * For more information on how this works, see the 'Plugin Dependency' section
 * near the bottom of this file.
 *
 *           v--WordPress Actions       v--BuddyPress Sub-actions
 */
add_filter( 'request', 'bp_request', 10 );
add_filter( 'template_include', 'bp_template_include', 10 );
add_filter( 'login_redirect', 'bp_login_redirect', 10, 3 );
add_filter( 'map_meta_cap', 'bp_map_meta_caps', 10, 4 );

// Add some filters to feedback messages.
add_filter( 'bp_core_render_message_content', 'do_shortcode' );
add_filter( 'bp_core_render_message_content', 'wptexturize' );
add_filter( 'bp_core_render_message_content', 'convert_smilies' );
add_filter( 'bp_core_render_message_content', 'convert_chars' );
add_filter( 'bp_core_render_message_content', 'wpautop' );
add_filter( 'bp_core_render_message_content', 'shortcode_unautop' );
add_filter( 'bp_core_render_message_content', 'wp_kses_data', 5 );

// Emails.
add_filter( 'bp_email_set_content_html', 'wp_filter_post_kses', 6 );
add_filter( 'bp_email_set_content_html', 'stripslashes', 8 );
add_filter( 'bp_email_set_content_plaintext', 'wp_strip_all_tags', 6 );
add_filter( 'bp_email_set_subject', 'sanitize_text_field', 6 );

// Removed Document and Media from WordPress media endpoints.
add_filter( 'rest_attachment_query', 'bp_rest_restrict_wp_attachment_query', 999 );
add_filter( 'rest_prepare_attachment', 'bp_rest_restrict_wp_attachment_response', 999, 2 );
add_filter( 'oembed_request_post_id', 'bp_rest_restrict_oembed_request_post_id', 999 );

// Widget display name.
add_filter( 'bp_core_widget_user_display_name', 'wp_filter_kses' );
add_filter( 'bp_core_widget_user_display_name', 'stripslashes' );
add_filter( 'bp_core_widget_user_display_name', 'strip_tags' );
add_filter( 'bp_core_widget_user_display_name', 'esc_html' );

// Avatars.
/**
 * Disable gravatars fallback for member avatars.
 *
 * @since BuddyBoss 1.0.0
 */
add_action( 'init', 'bp_enable_gravatar_callback' );
// add_filter( 'bp_core_fetch_avatar_no_grav', '__return_true' );
function bp_enable_gravatar_callback() {
	$avatar = bp_enable_profile_gravatar();

	if ( false === $avatar ) {

		/**
		 * Disable gravatars fallback for member avatars.
		 *
		 * @since BuddyBoss 1.0.0
		 */
		add_filter( 'bp_core_fetch_avatar_no_grav', '__return_true' );
	}

}


/**
 * Template Compatibility.
 *
 * If you want to completely bypass this and manage your own custom BuddyPress
 * template hierarchy, start here by removing this filter, then look at how
 * bp_template_include() works and do something similar. :)
 */
add_filter( 'bp_template_include', 'bp_template_include_theme_supports', 2, 1 );
add_filter( 'bp_template_include', 'bp_template_include_theme_compat', 4, 2 );

// Filter BuddyPress template locations.
add_filter( 'bp_get_template_stack', 'bp_add_template_stack_locations' );

// Turn comments off for BuddyPress pages.
add_filter( 'comments_open', 'bp_comments_open', 10, 2 );

// Prevent DB query for WP's main loop.
add_filter( 'posts_pre_query', 'bp_core_filter_wp_query', 10, 2 );

// Remove deleted members link from mention for blog comment.
add_filter( 'comment_text', 'bb_mention_remove_deleted_users_link', 20, 1 );
/**
 * Prevent specific pages (eg 'Activate') from showing on page listings.
 *
 * @since BuddyPress 1.5.0
 *
 * @param array $pages List of excluded page IDs, as passed to the
 *                     'wp_list_pages_excludes' filter.
 * @return array The exclude list, with BP's pages added.
 */
function bp_core_exclude_pages( $pages = array() ) {

	// Bail if not the root blog.
	if ( ! bp_is_root_blog() ) {
		return $pages;
	}

	$bp = buddypress();

	if ( ! empty( $bp->pages->activate ) ) {
		$pages[] = $bp->pages->activate->id;
	}

	if ( ! empty( $bp->pages->register ) ) {
		$pages[] = $bp->pages->register->id;
	}

	/**
	 * Filters specific pages that shouldn't show up on page listings.
	 *
	 * @since BuddyPress 1.5.0
	 *
	 * @param array $pages Array of pages to exclude.
	 */
	return apply_filters( 'bp_core_exclude_pages', $pages );
}
add_filter( 'wp_list_pages_excludes', 'bp_core_exclude_pages' );

/**
 * Prevent specific pages (eg 'Activate') from showing in the Pages meta box of the Menu Administration screen.
 *
 * @since BuddyPress 2.0.0
 *
 * @param object|null $object The post type object used in the meta box.
 * @return object|null The $object, with a query argument to remove register and activate pages id.
 */
function bp_core_exclude_pages_from_nav_menu_admin( $object = null ) {

	// Bail if not the root blog.
	if ( ! bp_is_root_blog() ) {
		return $object;
	}

	if ( 'page' != $object->name ) {
		return $object;
	}

	$bp    = buddypress();
	$pages = array();

	if ( ! empty( $bp->pages->activate ) ) {
		$pages[] = $bp->pages->activate->id;
	}

	if ( ! empty( $bp->pages->register ) ) {
		$pages[] = $bp->pages->register->id;
	}

	if ( ! empty( $pages ) ) {
		$object->_default_query['post__not_in'] = $pages;
	}

	return $object;
}
add_filter( 'nav_menu_meta_box_object', 'bp_core_exclude_pages_from_nav_menu_admin', 11, 1 );

/**
 * Adds current page CSS classes to the parent BP page in a WP Page Menu.
 *
 * Because BuddyPress primarily uses virtual pages, we need a way to highlight
 * the BP parent page during WP menu generation.  This function checks the
 * current BP component against the current page in the WP menu to see if we
 * should highlight the WP page.
 *
 * @since BuddyPress 2.2.0
 *
 * @param array   $retval CSS classes for the current menu page in the menu.
 * @param WP_Post $page   The page properties for the current menu item.
 * @return array
 */
function bp_core_menu_highlight_parent_page( $retval, $page ) {
	if ( ! is_buddypress() ) {
		return $retval;
	}

	$page_id = false;

	// Loop against all BP component pages.
	foreach ( (array) buddypress()->pages as $component => $bp_page ) {
		// Handles the majority of components.
		if ( bp_is_current_component( $component ) ) {
			$page_id = (int) $bp_page->id;
		}

		// Stop if not on a user page.
		if ( ! bp_is_user() && ! empty( $page_id ) ) {
			break;
		}

		// Members component requires an explicit check due to overlapping components.
		if ( bp_is_user() && 'members' === $component ) {
			$page_id = (int) $bp_page->id;
			break;
		}
	}

	// Duplicate some logic from Walker_Page::start_el() to highlight menu items.
	if ( ! empty( $page_id ) ) {
		$_bp_page = get_post( $page_id );
		if ( isset( $page->ID ) && in_array( $page->ID, $_bp_page->ancestors, true ) ) {
			$retval[] = 'current_page_ancestor';
		}
		if ( isset( $page->ID ) && $page->ID === $page_id ) {
			$retval[] = 'current_page_item';
		} elseif ( isset( $page->ID ) && $_bp_page && $page->ID === $_bp_page->post_parent ) {
			$retval[] = 'current-menu-item';
			$retval[] = 'current_page_parent';
		}
	}

	$retval = array_unique( $retval );

	return $retval;
}
add_filter( 'page_css_class', 'bp_core_menu_highlight_parent_page', 10, 2 );

/**
 * Adds current page CSS classes to the parent BP page in a WP Nav Menu.
 *
 * When {@link wp_nav_menu()} is used, this function helps to highlight the
 * current BP parent page during nav menu generation.
 *
 * @since BuddyPress 2.2.0
 *
 * @param array   $retval CSS classes for the current nav menu item in the menu.
 * @param WP_Post $item   The properties for the current nav menu item.
 * @return array
 */
function bp_core_menu_highlight_nav_menu_item( $retval, $item ) {
	// If we're not on a BP page or if the current nav item is not a page, stop!
	if ( ! is_buddypress() || 'page' !== $item->object ) {
		return $retval;
	}

	// Get the WP page.
	$page = get_post( $item->object_id );

	// See if we should add our highlight CSS classes for the page.
	$retval = bp_core_menu_highlight_parent_page( $retval, $page );

	return $retval;
}
add_filter( 'nav_menu_css_class', 'bp_core_menu_highlight_nav_menu_item', 10, 2 );

/**
 * Filter the blog post comments array and insert BuddyPress URLs for users.
 *
 * @since BuddyPress 1.2.0
 *
 * @param array $comments The array of comments supplied to the comments template.
 * @param int   $post_id  The post ID.
 * @return array $comments The modified comment array.
 */
function bp_core_filter_comments( $comments, $post_id ) {
	global $wpdb;

	foreach ( (array) $comments as $comment ) {
		if ( $comment->user_id ) {
			$user_ids[] = $comment->user_id;
		}
	}

	if ( empty( $user_ids ) ) {
		return $comments;
	}

	$user_ids = implode( ',', wp_parse_id_list( $user_ids ) );

	if ( ! $userdata = $wpdb->get_results( "SELECT ID as user_id, user_login, user_nicename FROM {$wpdb->users} WHERE ID IN ({$user_ids})" ) ) {
		return $comments;
	}

	foreach ( (array) $userdata as $user ) {
		$users[ $user->user_id ] = bp_core_get_user_domain( $user->user_id, $user->user_nicename, $user->user_login );
	}

	foreach ( (array) $comments as $i => $comment ) {
		if ( ! empty( $comment->user_id ) ) {
			if ( ! empty( $users[ $comment->user_id ] ) ) {
				$comments[ $i ]->comment_author_url = $users[ $comment->user_id ];
			}
		}
	}

	return $comments;
}
add_filter( 'comments_array', 'bp_core_filter_comments', 10, 2 );

/**
 * When a user logs in, redirect him in a logical way.
 *
 * @since BuddyPress 1.2.0
 *
 *       are redirected to on login.
 *
 * @param string  $redirect_to     The URL to be redirected to, sanitized in wp-login.php.
 * @param string  $redirect_to_raw The unsanitized redirect_to URL ($_REQUEST['redirect_to']).
 * @param WP_User $user            The WP_User object corresponding to a successfully
 *                                 logged-in user. Otherwise a WP_Error object.
 * @return string The redirect URL.
 */
function bp_core_login_redirect( $redirect_to, $redirect_to_raw, $user ) {

	// Only modify the redirect if we're on the main BP blog.
	if ( ! bp_is_root_blog() ) {
		return $redirect_to;
	}

	// Only modify the redirect once the user is logged in.
	if ( ! is_a( $user, 'WP_User' ) ) {
		return $redirect_to;
	}

	/**
	 * Filters whether or not to redirect.
	 *
	 * Allows plugins to have finer grained control of redirect upon login.
	 *
	 * @since BuddyPress 1.6.0
	 *
	 * @param bool    $value           Whether or not to redirect.
	 * @param string  $redirect_to     Sanitized URL to be redirected to.
	 * @param string  $redirect_to_raw Unsanitized URL to be redirected to.
	 * @param WP_User $user            The WP_User object corresponding to a
	 *                                 successfully logged in user.
	 */
	$maybe_redirect = apply_filters( 'bp_core_login_redirect', false, $redirect_to, $redirect_to_raw, $user );
	if ( false !== $maybe_redirect ) {
		return $maybe_redirect;
	}

	// If a 'redirect_to' parameter has been passed that contains 'wp-admin', verify that the
	// logged-in user has any business to conduct in the Dashboard before allowing the
	// redirect to go through.
	if ( ! empty( $redirect_to ) && ( false === strpos( $redirect_to, 'wp-admin' ) || user_can( $user, 'edit_posts' ) ) ) {
		return $redirect_to;
	}

	if ( false === strpos( wp_get_referer(), 'wp-login.php' ) && false === strpos( wp_get_referer(), 'activate' ) && empty( $_REQUEST['nr'] ) ) {
		return wp_get_referer();
	}

	/**
	 * Filters the URL to redirect users to upon successful login.
	 *
	 * @since BuddyPress 1.9.0
	 *
	 * @param string $value URL to redirect to.
	 */
	return apply_filters( 'bp_core_login_redirect_to', bp_get_root_domain() );
}
add_filter( 'bp_login_redirect', 'bp_core_login_redirect', 10, 3 );

/**
 * Decode HTML entities for plain-text emails.
 *
 * @since BuddyPress 2.5.0
 *
 * @param string $retval    Current email content.
 * @param string $prop      Email property to check against.
 * @param string $transform Either 'raw' or 'replace-tokens'.
 * @return string|null $retval Modified email content.
 */
function bp_email_plaintext_entity_decode( $retval, $prop, $transform ) {
	switch ( $prop ) {
		case 'content_plaintext':
		case 'subject':
			// Only decode if 'replace-tokens' is the current type.
			if ( 'replace-tokens' === $transform ) {
				return html_entity_decode( $retval, ENT_QUOTES );
			} else {
				return $retval;
			}
			break;

		default:
			return $retval;
			break;
	}
}
add_filter( 'bp_email_get_property', 'bp_email_plaintext_entity_decode', 10, 3 );

/**
 * Replace the generated password in the welcome email with '[User Set]'.
 *
 * On a standard BP installation, users who register themselves also set their
 * own passwords. Therefore there is no need for the insecure practice of
 * emailing the plaintext password to the user in the welcome email.
 *
 * This filter will not fire when a user is registered by the site admin.
 *
 * @since BuddyPress 1.2.1
 *
 * @param string $welcome_email Complete email passed through WordPress.
 * @return string Filtered $welcome_email with the password replaced
 *                by '[User Set]'.
 * @todo should [User Set] be in the text domain?
 */
function bp_core_filter_user_welcome_email( $welcome_email ) {

	// Don't touch the email when a user is registered by the site admin.
	if ( ( is_admin() || is_network_admin() ) && buddypress()->members->admin->signups_page != get_current_screen()->id ) {
		return $welcome_email;
	}

	if ( strpos( bp_get_requested_url(), 'wp-activate.php' ) !== false ) {
		return $welcome_email;
	}

	// Don't touch the email if we don't have a custom registration template.
	if ( ! bp_has_custom_signup_page() ) {
		return $welcome_email;
	}

	// [User Set] Replaces 'PASSWORD' in welcome email; Represents value set by user
	return str_replace( 'PASSWORD', __( '[User Set]', 'buddyboss' ), $welcome_email );
}
add_filter( 'update_welcome_user_email', 'bp_core_filter_user_welcome_email' );

/**
 * Replace the generated password in the welcome email with '[User Set]'.
 *
 * On a standard BP installation, users who register themselves also set their
 * own passwords. Therefore there is no need for the insecure practice of
 * emailing the plaintext password to the user in the welcome email.
 *
 * This filter will not fire when a user is registered by the site admin.
 *
 * @since BuddyPress 1.2.1
 *
 * @param string $welcome_email Complete email passed through WordPress.
 * @param int    $blog_id       ID of the blog user is joining.
 * @param int    $user_id       ID of the user joining.
 * @param string $password      Password of user.
 * @return string Filtered $welcome_email with $password replaced by '[User Set]'.
 */
function bp_core_filter_blog_welcome_email( $welcome_email, $blog_id, $user_id, $password ) {

	// Don't touch the email when a user is registered by the site admin.
	if ( ( is_admin() || is_network_admin() ) && buddypress()->members->admin->signups_page != get_current_screen()->id ) {
		return $welcome_email;
	}

	// Don't touch the email if we don't have a custom registration template.
	if ( ! bp_has_custom_signup_page() ) {
		return $welcome_email;
	}

	// [User Set] Replaces $password in welcome email; Represents value set by user
	return str_replace( $password, __( '[User Set]', 'buddyboss' ), $welcome_email );
}
add_filter( 'update_welcome_email', 'bp_core_filter_blog_welcome_email', 10, 4 );

/**
 * Notify new users of a successful registration (with blog).
 *
 * This function filter's WP's 'wpmu_signup_blog_notification', and replaces
 * WP's default welcome email with a BuddyPress-specific message.
 *
 * @since BuddyPress 1.0.0
 *
 * @see wpmu_signup_blog_notification() for a description of parameters.
 *
 * @param string $domain     The new blog domain.
 * @param string $path       The new blog path.
 * @param string $title      The site title.
 * @param string $user       The user's login name.
 * @param string $user_email The user's email address.
 * @param string $key        The activation key created in wpmu_signup_blog().
 * @return bool              Returns false to stop original WPMU function from continuing.
 */
function bp_core_activation_signup_blog_notification( $domain, $path, $title, $user, $user_email, $key ) {
	$args = array(
		'tokens' => array(
			'activate-site.url' => esc_url( bp_get_activation_page() . '?key=' . urlencode( $key ) ),
			'domain'            => $domain,
			'key_blog'          => $key,
			'path'              => $path,
			'user-site.url'     => esc_url( set_url_scheme( "http://{$domain}{$path}" ) ),
			'title'             => $title,
			'user.email'        => $user_email,
		),
	);

	bp_send_email( 'core-user-registration-with-blog', array( array( $user_email => $user ) ), $args );

	// Return false to stop the original WPMU function from continuing.
	return false;
}
add_filter( 'wpmu_signup_blog_notification', 'bp_core_activation_signup_blog_notification', 1, 6 );

/**
 * Notify new users of a successful registration (without blog).
 *
 * @since BuddyPress 1.0.0
 *
 * @see wpmu_signup_user_notification() for a full description of params.
 *
 * @param string $user       The user's login name.
 * @param string $user_email The user's email address.
 * @param string $key        The activation key created in wpmu_signup_user().
 * @param array  $meta       By default, an empty array.
 * @return false|string Returns false to stop original WPMU function from continuing.
 */
function bp_core_activation_signup_user_notification( $user, $user_email, $key, $meta ) {
	if ( is_admin() ) {

		// If the user is created from the WordPress Add User screen, don't send BuddyPress signup notifications.
		if ( in_array( get_current_screen()->id, array( 'user', 'user-network' ) ) ) {
			// If the Super Admin want to skip confirmation email.
			if ( isset( $_POST['noconfirmation'] ) && is_super_admin() ) {
				return false;

				// WordPress will manage the signup process.
			} else {
				return $user;
			}

			/*
			 * There can be a case where the user was created without the skip confirmation
			 * And the super admin goes in pending accounts to resend it. In this case, as the
			 * meta['password'] is not set, the activation url must be WordPress one.
			 */
		} elseif ( buddypress()->members->admin->signups_page == get_current_screen()->id ) {
			$is_hashpass_in_meta = maybe_unserialize( $meta );

			if ( empty( $is_hashpass_in_meta['password'] ) ) {
				return $user;
			}
		}
	}

	$user_id     = 0;
	$user_object = get_user_by( 'login', $user );
	if ( $user_object ) {
		$user_id = $user_object->ID;
	}

	$args = array(
		'tokens' => array(
			'activate.url' => esc_url( trailingslashit( bp_get_activation_page() ) . "{$key}/" ),
			'key'          => $key,
			'user.email'   => $user_email,
			'user.id'      => $user_id,
		),
	);

	bp_send_email( 'core-user-registration', array( array( $user_email => $user ) ), $args );

	// Return false to stop the original WPMU function from continuing.
	return false;
}
add_filter( 'wpmu_signup_user_notification', 'bp_core_activation_signup_user_notification', 1, 4 );

/**
 * Filter the page title for BuddyPress pages.
 *
 * @since BuddyPress 1.5.0
 *
 * @see wp_title()
 * @global object $bp BuddyPress global settings.
 *
 * @param string $title       Original page title.
 * @param string $sep         How to separate the various items within the page title.
 * @param string $seplocation Direction to display title.
 * @return string              New page title.
 */
function bp_modify_page_title( $title = '', $sep = '&raquo;', $seplocation = 'right' ) {
	global $paged, $page, $_wp_theme_features;

	// Get the BuddyPress title parts.
	$bp_title_parts = bp_get_title_parts( $seplocation );

	// If not set, simply return the original title.
	if ( ! $bp_title_parts ) {
		return $title;
	}

	// Get the blog name, so we can check if the original $title included it.
	$blogname = get_bloginfo( 'name', 'display' );

	/**
	 * Are we going to fake 'title-tag' theme functionality?
	 *
	 * @link https://buddypress.trac.wordpress.org/ticket/6107
	 * @see wp_title()
	 */
	$title_tag_compatibility = (bool) ( ! empty( $_wp_theme_features['title-tag'] ) || ( $blogname && strstr( $title, $blogname ) ) );

	// Append the site title to title parts if theme supports title tag.
	if ( true === $title_tag_compatibility ) {
		$bp_title_parts['site'] = $blogname;

		if ( ( $paged >= 2 || $page >= 2 ) && ! is_404() && ! bp_is_single_activity() && ! bp_is_user_messages() ) {
			$bp_title_parts['page'] = sprintf( __( 'Page %s', 'buddyboss' ), max( $paged, $page ) );
		}
	}

	// Pad the separator with 1 space on each side.
	$prefix = str_pad( $sep, strlen( $sep ) + 2, ' ', STR_PAD_BOTH );

	// Join the parts together.
	$new_title = join( $prefix, array_filter( $bp_title_parts ) );

	// Append the prefix for pre `title-tag` compatibility.
	if ( false === $title_tag_compatibility ) {
		$new_title = $new_title . $prefix;
	}

	/**
	 * Filters the older 'wp_title' page title for BuddyPress pages.
	 *
	 * @since BuddyPress 1.5.0
	 *
	 * @param string $new_title   The BuddyPress page title.
	 * @param string $title       The original WordPress page title.
	 * @param string $sep         The title parts separator.
	 * @param string $seplocation Location of the separator (left or right).
	 */
	return apply_filters( 'bp_modify_page_title', $new_title, $title, $sep, $seplocation );
}
add_filter( 'wp_title', 'bp_modify_page_title', 20, 3 );
add_filter( 'bp_modify_page_title', 'wptexturize' );
add_filter( 'bp_modify_page_title', 'convert_chars' );
add_filter( 'bp_modify_page_title', 'esc_html' );

/**
 * Filter the document title for BuddyPress pages.
 *
 * @since BuddyPress 2.4.3
 *
 * @param array $title The WordPress document title parts.
 * @return array the unchanged title parts or the BuddyPress ones
 */
function bp_modify_document_title_parts( $title = array() ) {
	// Get the BuddyPress title parts.
	$bp_title_parts = bp_get_title_parts();

	// If not set, simply return the original title.
	if ( ! $bp_title_parts ) {
		return $title;
	}

	// Get the separator used by wp_get_document_title().
	$sep = apply_filters( 'document_title_separator', '-' );

	// Build the BuddyPress portion of the title.
	// We don't need to sanitize this as WordPress will take care of it.
	$bp_title = array(
		'title' => join( " $sep ", $bp_title_parts ),
	);

	// Add the pagination number if needed (not sure if this is necessary).
	if ( isset( $title['page'] ) && ! bp_is_single_activity() && ! bp_is_user_messages() ) {
		$bp_title['page'] = $title['page'];
	}

	// Add the sitename if needed.
	if ( isset( $title['site'] ) ) {
		$bp_title['site'] = $title['site'];
	}

	/**
	 * Filters BuddyPress title parts that will be used into the document title.
	 *
	 * @since BuddyPress 2.4.3
	 *
	 * @param array $bp_title The BuddyPress page title parts.
	 * @param array $title    The original WordPress title parts.
	 */
	return apply_filters( 'bp_modify_document_title_parts', $bp_title, $title );
}
add_filter( 'document_title_parts', 'bp_modify_document_title_parts', 20, 1 );

/**
 * Add BuddyPress-specific items to the wp_nav_menu.
 *
 * @since BuddyPress 1.9.0
 *
 * @param WP_Post $menu_item The menu item.
 * @return WP_Post The modified WP_Post object.
 */
function bp_setup_nav_menu_item( $menu_item ) {

	if ( isset( $menu_item->classes ) && is_array( $menu_item->classes ) && in_array( 'bp-menu', $menu_item->classes, true ) ) {
		$menu_item->type_label = __( 'BuddyBoss', 'buddyboss' );
		$menu_item->menu_type  = 'buddyboss';
	}

	if ( is_admin() ) {
		return $menu_item;
	}

	// Prevent a notice error when using the customizer.
	$menu_classes = $menu_item->classes;

	if ( is_array( $menu_classes ) ) {
		$menu_classes = implode( ' ', $menu_item->classes );
	}

	// We use information stored in the CSS class to determine what kind of
	// menu item this is, and how it should be treated.
	preg_match( '/\sbp-(.*)-nav/', $menu_classes, $matches );

	// If this isn't a BP menu item, we can stop here.
	if ( empty( $matches[1] ) ) {
		return $menu_item;
	}

	switch ( $matches[1] ) {
		case 'login':
			if ( is_user_logged_in() ) {
				$menu_item->_invalid = true;
			} else {
				$menu_item->url = wp_login_url( bp_get_requested_url() );
			}

			break;

		case 'logout':
			if ( ! is_user_logged_in() ) {
				$menu_item->_invalid = true;
			} else {
				$menu_item->url = wp_logout_url( bp_get_requested_url() );
			}

			break;

		// Don't show the Register link to logged-in users.
		case 'register':
			if ( is_user_logged_in() ) {
				$menu_item->_invalid = true;
			}

			break;

		// All other BP nav items are specific to the logged-in user,
		// and so are not relevant to logged-out users.
		default:
			if ( is_user_logged_in() ) {
				$menu_item->url = bp_nav_menu_get_item_url( $matches[1] );
			} else {
				$menu_item->_invalid = true;
			}

			break;
	}

	// If component is deactivated, make sure menu item doesn't render.
	if ( empty( $menu_item->url ) ) {
		$menu_item->_invalid = true;

		// Highlight the current page.
	} else {
		$current            = bp_get_requested_url();
		$url_parts          = explode( '/', untrailingslashit( $menu_item->url ) );
		$menu_item->classes = is_array( $menu_item->classes ) ? $menu_item->classes : array();

		if ( untrailingslashit( $current ) === untrailingslashit( $menu_item->url ) ) {
			$menu_item->classes[] = 'current_page_item';
			$menu_item->classes[] = 'current-menu-item';
		} else {

			if ( bp_loggedin_user_domain() && strpos( $current, bp_loggedin_user_domain() ) !== false ) {
				if (
					(
						! in_array( 'settings', $url_parts, true ) &&
						(
							(
								bp_is_user_profile() &&
								! bp_is_user_profile_edit() &&
								! bp_is_user_change_avatar() &&
								! bp_is_user_change_cover_image() &&
								'profile' === end( $url_parts )
							) ||
							(
								bp_is_user_profile_edit() &&
								'edit' === end( $url_parts )
							) ||
							(
								bp_is_user_change_avatar() &&
								'change-avatar' === end( $url_parts )
							) ||
							(
								bp_is_user_change_cover_image() &&
								'change-cover-image' === end( $url_parts )
							) ||
							(
								in_array( 'bp-profile-nav', $menu_item->classes, true ) &&
								(
									bp_is_user_profile_edit() ||
									bp_is_user_change_avatar() ||
									bp_is_user_change_cover_image()
								)
							)
						)
					) ||
					(
						in_array( 'settings', $url_parts, true ) &&
						(
							(
								bp_is_user_settings_general() &&
								'settings' === end( $url_parts )
							) ||
							(
								bp_is_user_settings_profile() &&
								'profile' === end( $url_parts )
							) ||
							(
								bp_is_user_settings_notifications() &&
								'subscriptions' === bp_action_variable() &&
								'notifications' === end( $url_parts ) &&
								in_array( 'bp-settings-notifications-sub-nav', $menu_item->classes, true )
							) ||
							(
								in_array( 'bp-settings-nav', $menu_item->classes, true ) &&
								(
									bp_is_user_settings_general() ||
									bp_is_user_settings_profile() ||
									bp_is_user_settings_notifications()
								)
							)
						)
					) ||
					(
						bp_is_user_invites() &&
						'sent-invites' === bp_current_action() &&
						in_array( 'bp-invites-nav', $menu_item->classes, true )
					) ||
					(
						bp_is_user_activity() &&
						in_array( 'bp-activity-nav', $menu_item->classes, true ) &&
						(
							'groups' === bp_current_action() ||
							'mentions' === bp_current_action() ||
							'following' === bp_current_action() ||
							'friends' === bp_current_action()
						)
					) ||
					(
						bp_is_user_notifications() &&
						'read' === bp_current_action() &&
						in_array( 'bp-notifications-nav', $menu_item->classes, true )
					) ||
					(
						bp_is_user_messages() &&
						in_array( 'bp-messages-nav', $menu_item->classes, true ) &&
						(
							'compose' === bp_current_action() ||
							'archived' === bp_current_action()
						)
					) ||
					(
						bp_is_friends_component() &&
						'requests' === bp_current_action() &&
						in_array( 'bp-friends-nav', $menu_item->classes, true )
					) ||
					(
						bp_is_groups_component() &&
						'invites' === bp_current_action() &&
						in_array( 'bp-groups-nav', $menu_item->classes, true )
					) ||
					(
						bp_is_user_albums() &&
						in_array( 'bp-photos-nav', $menu_item->classes, true )
					) ||
					(
						bp_is_forums_component() &&
						(
							'favorites' === bp_current_action() ||
							'replies' === bp_current_action()
						) &&
						in_array( 'bp-forums-nav', $menu_item->classes, true )
					)
				) {
					$menu_item->classes[] = 'current_page_item';
					$menu_item->classes[] = 'current-menu-item';
				}
			} elseif (
				bp_is_groups_component() &&
				bp_is_group_create() &&
				(
					'create' === end( $url_parts ) ||
					in_array( 'bp-groups-nav', $menu_item->classes, true )
				)
			) {
				$menu_item->classes[] = 'current_page_item';
				$menu_item->classes[] = 'current-menu-item';
			} elseif ( strpos( $current, $menu_item->url ) !== false ) {
				$menu_item->classes[] = 'current_page_item';
				$menu_item->classes[] = 'current-menu-item';
			}
		}
	}

	return $menu_item;
}
add_filter( 'wp_setup_nav_menu_item', 'bp_setup_nav_menu_item', 10, 1 );

/**
 * Populate BuddyPress user nav items for the customizer.
 *
 * @since BuddyPress 2.3.3
 *
 * @param array   $items  The array of menu items.
 * @param string  $type   The requested type.
 * @param string  $object The requested object name.
 * @param integer $page   The page num being requested.
 * @return array The paginated BuddyPress user nav items.
 */
function bp_customizer_nav_menus_get_items( $items = array(), $type = '', $object = '', $page = 0 ) {
	if ( 'bp_loggedin_nav' === $object ) {
		$bp_items = bp_nav_menu_get_loggedin_pages();
	} elseif ( 'bp_loggedout_nav' === $object ) {
		$bp_items = bp_nav_menu_get_loggedout_pages();
	} else {
		return $items;
	}

	foreach ( $bp_items as $bp_item ) {
		$items[] = array(
			'id'         => "bp-{$bp_item->post_excerpt}",
			'title'      => html_entity_decode( $bp_item->post_title, ENT_QUOTES, get_bloginfo( 'charset' ) ),
			'type'       => $type,
			'url'        => esc_url_raw( $bp_item->guid ),
			'classes'    => "bp-menu bp-{$bp_item->post_excerpt}-nav",
			'type_label' => __( 'BuddyBoss', 'buddyboss' ),
			'object'     => $object,
			'object_id'  => -1,
		);
	}

	return array_slice( $items, 10 * $page, 10 );
}
add_filter( 'customize_nav_menu_available_items', 'bp_customizer_nav_menus_get_items', 10, 4 );

/**
 * Set BuddyPress item navs for the customizer.
 *
 * @since BuddyPress 2.3.3
 *
 * @param array $item_types An associative array structured for the customizer.
 * @return array $item_types An associative array structured for the customizer.
 */
function bp_customizer_nav_menus_set_item_types( $item_types = array() ) {
	$item_types = array_merge(
		$item_types,
		array(
			'bp_loggedin_nav'  => array(
				'title'  => __( 'BuddyBoss (logged-in)', 'buddyboss' ),
				'type'   => 'bp_nav',
				'object' => 'bp_loggedin_nav',
			),
			'bp_loggedout_nav' => array(
				'title'  => __( 'BuddyBoss (logged-out)', 'buddyboss' ),
				'type'   => 'bp_nav',
				'object' => 'bp_loggedout_nav',
			),
		)
	);

	return $item_types;
}
add_filter( 'customize_nav_menu_available_item_types', 'bp_customizer_nav_menus_set_item_types', 10, 1 );

/**
 * Filter SQL query strings to swap out the 'meta_id' column.
 *
 * WordPress uses the meta_id column for commentmeta and postmeta, and so
 * hardcodes the column name into its *_metadata() functions. BuddyPress, on
 * the other hand, uses 'id' for the primary column. To make WP's functions
 * usable for BuddyPress, we use this just-in-time filter on 'query' to swap
 * 'meta_id' with 'id.
 *
 * @since BuddyPress 2.0.0
 *
 * @access private Do not use.
 *
 * @param string $q SQL query.
 * @return string
 */
function bp_filter_metaid_column_name( $q ) {
	/*
	 * Replace quoted content with __QUOTE__ to avoid false positives.
	 * This regular expression will match nested quotes.
	 */
	$quoted_regex = "/'[^'\\\\]*(?:\\\\.[^'\\\\]*)*'/s";
	preg_match_all( $quoted_regex, $q, $quoted_matches );
	$q = preg_replace( $quoted_regex, '__QUOTE__', $q );

	if ( strpos( $q, 'umeta_id' ) === false ) {
		$q = str_replace( 'meta_id', 'id', $q );
	}

	// Put quoted content back into the string.
	if ( ! empty( $quoted_matches[0] ) ) {
		for ( $i = 0; $i < count( $quoted_matches[0] ); $i++ ) {
			$quote_pos = strpos( $q, '__QUOTE__' );
			$q         = substr_replace( $q, $quoted_matches[0][ $i ], $quote_pos, 9 );
		}
	}

	return $q;
}

/**
 * Filter the edit post link to avoid its display in BuddyPress pages.
 *
 * @since BuddyPress 2.1.0
 *
 * @param string $edit_link The edit link.
 * @param int    $post_id   Post ID.
 * @return false|string Will be a boolean (false) if $post_id is 0. Will be a string (the unchanged edit link)
 *                      otherwise
 */
function bp_core_filter_edit_post_link( $edit_link = '', $post_id = 0 ) {
	if ( 0 === $post_id ) {
		$edit_link = false;
	}

	return $edit_link;
}

/**
 * Should BuddyPress load the mentions scripts and related assets, including results to prime the
 * mentions suggestions?
 *
 * @since BuddyPress 2.2.0
 *
 * @param bool $load_mentions    True to load mentions assets, false otherwise.
 * @param bool $mentions_enabled True if mentions are enabled.
 * @return bool True if mentions scripts should be loaded.
 */
function bp_maybe_load_mentions_scripts_for_blog_content( $load_mentions, $mentions_enabled ) {
	if ( ! $mentions_enabled ) {
		return $load_mentions;
	}

	if ( $load_mentions || ( bp_is_blog_page() && is_singular() && comments_open() ) ) {
		return true;
	}

	return $load_mentions;
}
add_filter( 'bp_activity_maybe_load_mentions_scripts', 'bp_maybe_load_mentions_scripts_for_blog_content', 10, 2 );

/**
 * Injects specific BuddyPress CSS classes into a widget sidebar.
 *
 * Helps to standardize styling of BuddyPress widgets within a theme that
 * does not use dynamic CSS classes in their widget sidebar's 'before_widget'
 * call.
 *
 * @since BuddyPress 2.4.0
 * @access private
 *
 * @global array $wp_registered_widgets Current registered widgets.
 *
 * @param array $params Current sidebar params.
 * @return array
 */
function _bp_core_inject_bp_widget_css_class( $params ) {
	global $wp_registered_widgets;

	$widget_id = $params[0]['widget_id'];

	// If callback isn't an array, bail.
	if ( false === is_array( $wp_registered_widgets[ $widget_id ]['callback'] ) ) {
		return $params;
	}

	// If the current widget isn't a BuddyPress one, stop!
	// We determine if a widget is a BuddyPress widget, if the widget class
	// begins with 'bp_'.
	if ( 0 !== strpos( $wp_registered_widgets[ $widget_id ]['callback'][0]->id_base, 'bp_' ) ) {
		return $params;
	}

	// Dynamically add our widget CSS classes for BP widgets if not already there.
	$classes = array();

	// Try to find 'widget' CSS class.
	if ( false === strpos( $params[0]['before_widget'], 'widget ' ) ) {
		$classes[] = 'widget';
	}

	// Try to find 'buddypress' CSS class.
	if ( false === strpos( $params[0]['before_widget'], ' buddypress' ) ) {
		$classes[] = 'buddypress';
	}

	// Stop if widget already has our CSS classes.
	if ( empty( $classes ) ) {
		return $params;
	}

	// CSS injection time!
	$params[0]['before_widget'] = str_replace( 'class="', 'class="' . implode( ' ', $classes ) . ' ', $params[0]['before_widget'] );

	return $params;
}
add_filter( 'dynamic_sidebar_params', '_bp_core_inject_bp_widget_css_class' );

/**
 * Add email link styles to rendered email template.
 *
 * This is only used when the email content has been merged into the email template.
 *
 * @since BuddyPress 2.5.0
 *
 * @param string $value         Property value.
 * @param string $property_name Email template property name.
 * @param string $transform     How the return value was transformed.
 * @return string Updated value.
 */
function bp_email_add_link_color_to_template( $value, $property_name, $transform ) {
	if ( $property_name !== 'template' || $transform !== 'add-content' ) {
		return $value;
	}

	$settings    = bp_email_get_appearance_settings();
	$replacement = 'style="color: ' . esc_attr( $settings['link_text_color'] ) . ';';

	// Find all links.
	preg_match_all( '#<a[^>]+>#i', $value, $links, PREG_SET_ORDER );
	foreach ( $links as $link ) {
		$new_link = $link = array_shift( $link );

		// Add/modify style property.
		if ( strpos( $link, 'style="' ) !== false ) {
			$new_link = str_replace( 'style="', $replacement, $link );
		} else {
			$new_link = str_replace( '<a ', "<a {$replacement}\" ", $link );
		}

		if ( $new_link !== $link ) {
			$value = str_replace( $link, $new_link, $value );
		}
	}

	return $value;
}
add_filter( 'bp_email_get_property', 'bp_email_add_link_color_to_template', 6, 3 );

/**
 * Add custom headers to outgoing emails.
 *
 * @since BuddyPress 2.5.0
 *
 * @param array    $headers   Array of email headers.
 * @param string   $property  Name of property. Unused.
 * @param string   $transform Return value transformation. Unused.
 * @param BP_Email $email     Email object reference.
 * @return array
 */
function bp_email_set_default_headers( $headers, $property, $transform, $email ) {
	$headers['X-BuddyPress']      = bp_get_version();
	$headers['X-BuddyPress-Type'] = $email->get( 'type' );

	$tokens = $email->get_tokens();

	// Add 'List-Unsubscribe' header if applicable.
	if ( ! empty( $tokens['unsubscribe'] ) && $tokens['unsubscribe'] !== wp_login_url() ) {
		$user = get_user_by( 'email', $tokens['recipient.email'] );

		$link = bp_email_get_unsubscribe_link(
			array(
				'user_id'           => $user->ID,
				'notification_type' => $email->get( 'type' ),
			)
		);

		if ( ! empty( $link ) ) {
			$headers['List-Unsubscribe'] = sprintf( '<%s>', esc_url_raw( $link ) );
		}
	}

	return $headers;
}
add_filter( 'bp_email_get_headers', 'bp_email_set_default_headers', 6, 4 );

/**
 * Add default email tokens.
 *
 * @since BuddyPress 2.5.0
 *
 * @param array    $tokens        Email tokens.
 * @param string   $property_name Unused.
 * @param string   $transform     Unused.
 * @param BP_Email $email         Email being sent.
 * @return array
 */
function bp_email_set_default_tokens( $tokens, $property_name, $transform, $email ) {
	$tokens['site.admin-email'] = bp_get_option( 'admin_email' );
	$tokens['site.url']         = home_url();
	$tokens['email.subject']    = $email->get_subject();

	// These options are escaped with esc_html on the way into the database in sanitize_option().
	$tokens['site.description'] = wp_specialchars_decode( bp_get_option( 'blogdescription' ), ENT_QUOTES );
	$tokens['site.name']        = wp_specialchars_decode( bp_get_option( 'blogname' ), ENT_QUOTES );
	$tokens['reset.url']        = esc_url( wp_lostpassword_url() );

	// Default values for tokens set conditionally below.
	$tokens['email.preheader']    = '';
	$tokens['recipient.email']    = '';
	$tokens['recipient.name']     = '';
	$tokens['recipient.avatar']   = '';
	$tokens['recipient.username'] = '';

	// Who is the email going to?
	$recipient = $email->get( 'to' );
	if ( $recipient ) {
		$recipient = array_shift( $recipient );
		$user_obj  = $recipient->get_user( 'search-email' );

		$tokens['recipient.email']  = $recipient->get_address();
		$tokens['recipient.name']   = $recipient->get_name();
		$tokens['recipient.avatar'] = $recipient->get_avatar();

		if ( ! $user_obj && $tokens['recipient.email'] ) {
			$user_obj = get_user_by( 'email', $tokens['recipient.email'] );
		}

		if ( $user_obj ) {
			$tokens['recipient.username'] = $user_obj->user_login;

			if ( bp_is_active( 'settings' ) && empty( $tokens['unsubscribe'] ) ) {
				$tokens['unsubscribe'] = esc_url(
					sprintf(
						'%s%s/notifications/',
						bp_core_get_user_domain( $user_obj->ID ),
						bp_get_settings_slug()
					)
				);
			}
		}
	}

	// Set default unsubscribe link if not passed.
	if ( empty( $tokens['unsubscribe'] ) ) {
		$tokens['unsubscribe'] = wp_login_url();
	}

	// Email pre header.
	$post = $email->get_post_object();
	if ( $post ) {
		$tokens['email.preheader'] = sanitize_text_field( get_post_meta( $post->ID, 'bp_email_preheader', true ) );
	}

	return $tokens;
}
add_filter( 'bp_email_get_tokens', 'bp_email_set_default_tokens', 6, 4 );

/**
 * Find and render the template for Email posts (the Customizer and admin previews).
 *
 * Misuses the `template_include` filter which expects a string, but as we need to replace
 * the `{{{content}}}` token with the post's content, we use object buffering to load the
 * template, replace the token, and render it.
 *
 * The function returns an empty string to prevent WordPress rendering another template.
 *
 * @since BuddyPress 2.5.0
 *
 * @param string $template Path to template (probably single.php).
 * @return string
 */
function bp_core_render_email_template( $template ) {
	if ( get_post_type() !== bp_get_email_post_type() || ! is_single() ) {
		return $template;
	}

	/**
	 * Filter template used to display Email posts.
	 *
	 * @since BuddyPress 2.5.0
	 *
	 * @param string $template Path to current template (probably single.php).
	 */
	$email_template = apply_filters(
		'bp_core_render_email_template',
		bp_locate_template( bp_email_get_template( get_queried_object() ), false ),
		$template
	);

	if ( ! $email_template ) {
		return $template;
	}

	ob_start();
	include $email_template;
	$template = ob_get_contents();
	ob_end_clean();

	// Make sure we add a <title> tag so WP Customizer picks it up.
	$template = str_replace( '<head>', '<head><title>' . esc_html__( 'BuddyBoss Emails', 'buddyboss' ) . '</title>', $template );
	echo str_replace( '{{{content}}}', wpautop( get_post()->post_content ), $template );

	/*
	 * Link colours are applied directly in the email template before sending, so we
	 * need to add an extra style here to set the colour for the Customizer or preview.
	 */
	$settings = bp_email_get_appearance_settings();
	printf(
		'<style>a { color: %s; }</style>',
		esc_attr( $settings['highlight_color'] )
	);

	return '';
}
add_action( 'bp_template_include', 'bp_core_render_email_template', 12 );


/**
 * Filter to update group cover images
 *
 * @since BuddyBoss 1.0.0
 *
 * @param array $attachment_data
 * @param array $param
 *
 * @return array
 */
function bp_dd_update_group_cover_images_url( $attachment_data, $param ) {

	$group_id = ! empty( $param['item_id'] ) ? absint( $param['item_id'] ) : 0;

	if ( ! empty( $group_id ) && isset( $param['type'] ) && 'cover-image' == $param['type'] ) {

		// check in group
		if ( isset( $param['object_dir'] ) && 'groups' == $param['object_dir'] ) {
			$cover_image = trim( groups_get_groupmeta( $group_id, 'cover-image' ) );
			if ( ! empty( $cover_image ) ) {
				$attachment_data = $cover_image;
			}
		}

		// check for user
		if ( isset( $param['object_dir'] ) && 'members' == $param['object_dir'] ) {
			$cover_image = trim( bp_get_user_meta( $group_id, 'cover-image', true ) );
			if ( ! empty( $cover_image ) ) {
				$attachment_data = $cover_image;
			}
		}
	}

	return $attachment_data;
}

add_filter( 'bp_attachments_pre_get_attachment', 'bp_dd_update_group_cover_images_url', 0, 2 );

/**
 * Delete the group cover photo attachment
 *
 * @since BuddyBoss 1.0.0
 */
function bp_dd_delete_group_cover_images_url( $group_id ) {
	if ( ! empty( $group_id ) ) {
		groups_delete_groupmeta( $group_id, 'cover-image' );
	}
}

add_action( 'groups_cover_image_deleted', 'bp_dd_delete_group_cover_images_url', 10, 1 );
add_action( 'groups_cover_image_uploaded', 'bp_dd_delete_group_cover_images_url', 10, 1 );

/**
 * Delete the user cover photo attachment
 *
 * @since BuddyBoss 1.0.0
 */
function bp_dd_delete_xprofile_cover_images_url( $user_id ) {
	if ( ! empty( $user_id ) ) {
		bp_delete_user_meta( $user_id, 'cover-image' );
	}
}

add_action( 'xprofile_cover_image_deleted', 'bp_dd_delete_xprofile_cover_images_url', 10, 1 );
add_action( 'xprofile_cover_image_uploaded', 'bp_dd_delete_xprofile_cover_images_url', 10, 1 );


/**
 * Create dummy path for Group and User
 *
 * @since BuddyBoss 1.0.0
 *
 * @param string $avatar_folder_dir
 * @param int    $group_id
 * @param array  $object
 * @param string $avatar_dir
 *
 * @return string $avatar_url
 */
function bp_dd_check_avatar_folder_dir( $avatar_folder_dir, $group_id, $object, $avatar_dir ) {

	if ( ! empty( $group_id ) && bp_is_active( 'groups' ) ) {
		if ( 'group-avatars' === $avatar_dir ) {
			$avatars = trim( groups_get_groupmeta( $group_id, 'avatars' ) );
			if ( ! empty( $avatars ) && ! file_exists( $avatar_folder_dir ) ) {
				wp_mkdir_p( $avatar_folder_dir );
			}
		}

		if ( 'avatars' == $avatar_dir ) {
			$avatars = trim( bp_get_user_meta( $group_id, 'avatars', true ) );
			if ( ! empty( $avatars ) && ! file_exists( $avatar_folder_dir ) ) {
				wp_mkdir_p( $avatar_folder_dir );
			}
		}
	}

	return $avatar_folder_dir;
}

add_filter( 'bp_core_avatar_folder_dir', 'bp_dd_check_avatar_folder_dir', 0, 4 );

/**
 * Get dummy URL from DB for Group and User
 *
 * @since BuddyBoss 1.0.0
 *
 * @param string $avatar_url
 * @param array  $params
 *
 * @return string $avatar_url
 */
function bp_dd_fetch_dummy_avatar_url( $avatar_url, $params ) {

	$item_id = ! empty( $params['item_id'] ) ? absint( $params['item_id'] ) : 0;
	if ( ! empty( $item_id ) && isset( $params['avatar_dir'] ) ) {

		// check for groups avatar
		if ( 'group-avatars' == $params['avatar_dir'] ) {
			$cover_image = trim( groups_get_groupmeta( $item_id, 'avatars' ) );
			if ( ! empty( $cover_image ) ) {
				$avatar_url = $cover_image;
			}
		}

		// check for user avatar
		if ( 'avatars' == $params['avatar_dir'] ) {
			$cover_image = trim( bp_get_user_meta( $item_id, 'avatars', true ) );
			if ( ! empty( $cover_image ) ) {
				$avatar_url = $cover_image;
			}
		}
	}

	return $avatar_url;
}

add_filter( 'bp_core_fetch_avatar_url_check', 'bp_dd_fetch_dummy_avatar_url', 1000, 2 );

/**
 * Delete avatar of group and user
 *
 * @since BuddyBoss 1.0.0
 *
 * @param $args
 */
function bp_dd_delete_avatar( $args ) {
	$item_id = ! empty( $args['item_id'] ) ? absint( $args['item_id'] ) : 0;
	if ( ! empty( $item_id ) ) {

		// check for user avatars getting deleted
		if ( isset( $args['object'] ) && 'user' == $args['object'] ) {
			bp_delete_user_meta( $item_id, 'avatars' );
		}

		// check for group avatars getting deleted
		if ( isset( $args['object'] ) && 'group' == $args['object'] ) {
			groups_delete_groupmeta( $item_id, 'avatars' );
		}
	}
}

add_action( 'bp_core_delete_existing_avatar', 'bp_dd_delete_avatar', 10, 1 );

/**
 * Removed the CKEditor Js on the activity page.
 *
 * @since BuddyBoss 1.0.5
 */
add_filter( 'script_loader_src', 'bp_remove_badgeos_conflict_ckeditor_dequeue_script', 9999, 2 );

function bp_remove_badgeos_conflict_ckeditor_dequeue_script( $src, $handle ) {

	if ( function_exists( 'is_plugin_active' ) && is_plugin_active( 'badgeos/badgeos.php' ) ) {

		if ( bp_is_user_activity() || bp_is_group_activity() || bp_is_activity_directory() || bp_is_messages_component() ) {

			if ( 'ck_editor_cdn' === $handle || 'custom_script' === $handle ) {
				$src = '';
			}
		}
	}

	return $src;
}

/**
 * Removed the non-component pages from $bp->pages from bp_core_set_uri_globals function.
 *
 * @since BuddyBoss 1.2.5
 */
function bp_pages_terms_and_privacy_exclude( $pages ) {

	if ( ! empty( $pages ) ) {

		// Removed terms page as non component page.
		if ( property_exists( $pages, 'terms' ) ) {
			unset( $pages->terms );
		}

		// Removed privacy policy page as non component page.
		if ( property_exists( $pages, 'privacy' ) ) {
			unset( $pages->privacy );
		}
	}

	return $pages;
}
add_filter( 'bp_pages', 'bp_pages_terms_and_privacy_exclude' );

/**
 * Filter to change cover image dimensions to original for group and profile.
 *
 * @param $wh        array
 * @param $settings  array
 * @param $component string
 *
 * @return array
 * @since BuddyBoss 1.5.1
 */
function bp_core_get_cover_image_dimensions( $wh, $settings, $component ) {
	if ( 'xprofile' === $component || 'groups' === $component ) {
		return array(
			'width'  => 1950,
			'height' => 450,
		);
	}

	return $wh;
}

add_filter( 'bp_attachments_get_cover_image_dimensions', 'bp_core_get_cover_image_dimensions', 10, 3 );

/**
 * Admin notice to update to BuddyBoss Theme 1.5.0 to fix fonts issues.
 */
if ( ! function_exists( 'buddyboss_platform_plugin_update_notice' ) ) {
	function buddyboss_platform_plugin_update_notice() {
		$buddyboss_theme = wp_get_theme( 'buddyboss-theme' );
		if ( $buddyboss_theme->exists() && $buddyboss_theme->get( 'Version' ) && function_exists( 'buddyboss_theme' ) && version_compare( $buddyboss_theme->get( 'Version' ), '1.5.0', '<' ) ) {
			$class   = 'notice notice-error';
			$message = __( 'Please update BuddyBoss Theme to v1.5.0 to maintain compatibility with BuddyBoss Platform. Some icons in your theme will look wrong until you update.', 'buddyboss' );
			printf( '<div class="%1$s"><p>%2$s</p></div>', esc_attr( $class ), esc_html( $message ) );
		}
	}
	add_action( 'admin_notices', 'buddyboss_platform_plugin_update_notice' );
}

/**
 * Update attachment rest query argument to hide media/document from media endpoints.
 * - Privacy security.
 *
 * @since BuddyBoss 1.5.5
 *
 * @param array $args WP_Query parsed arguments.
 *
 * @return array
 */
function bp_rest_restrict_wp_attachment_query( $args ) {
	$meta_query = ( array_key_exists( 'meta_query', $args ) ? $args['meta_query'] : array() );

	// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
	$args['meta_query'] = array(
		array(
			'key'     => 'bp_media_upload',
			'compare' => 'NOT EXISTS',
		),
		array(
			'key'     => 'bp_document_upload',
			'compare' => 'NOT EXISTS',
		),
	);

	if ( ! empty( $meta_query ) ) {
		$args['meta_query'][] = $meta_query;
	}

	if ( count( $args['meta_query'] ) > 1 ) {
		$args['meta_query']['relation'] = 'AND';
	}

	return $args;
}

/**
 * Empty response in single WordPress Media endpoint when fetch media/document.
 * - Privacy security.
 *
 * @since BuddyBoss 1.5.5
 *
 * @param WP_REST_Response $response The response object.
 * @param WP_Post          $post     The original attachment post.
 *
 * @return array
 */
function bp_rest_restrict_wp_attachment_response( $response, $post ) {
	$media_meta    = get_post_meta( $post->ID, 'bp_media_upload', true );
	$document_meta = get_post_meta( $post->ID, 'bp_document_upload', true );
	$data          = $response->get_data();
	if (
		array_key_exists( 'media_type', $data ) &&
		(
			! empty( $media_meta ) ||
			! empty( $document_meta )
		) &&
		(
			! is_user_logged_in()
			|| ! current_user_can( 'edit_post', $post->ID )
		)
	) {
		$response = array();
	}

	return $response;
}

/**
 * Restrict users to access media and documents from `/wp-json/oembed/1.0/embed`
 *
 * @param int $post_id Current post id.
 *
 * @return mixed
 */
function bp_rest_restrict_oembed_request_post_id( $post_id ) {
	$media_meta    = get_post_meta( $post_id, 'bp_media_upload', true );
	$document_meta = get_post_meta( $post_id, 'bp_document_upload', true );
	if (
		(
			! empty( $media_meta ) ||
			! empty( $document_meta )
		) &&
		(
			! is_user_logged_in()
			|| ! current_user_can( 'edit_post', $post_id )
		)
	) {
		$post_id = 0;
	}

	return $post_id;
}

/**
 * Add schedule to cron schedules.
 *
 * @param array $schedules Array of schedules for cron.
 *
 * @return array $schedules Array of schedules from cron.
 * @since Buddyboss 1.7.0
 */
function bp_core_cron_schedules( $schedules = array() ) {
	$bb_schedules = array(
		'bb_schedule_1min'    => array(
			'interval' => MINUTE_IN_SECONDS,
			'display'  => __( 'Every minute', 'buddyboss' ),
		),
		'bb_schedule_5min'    => array(
			'interval' => 5 * MINUTE_IN_SECONDS,
			'display'  => __( 'Once in 5 minutes', 'buddyboss' ),
		),
		'bb_schedule_10min'   => array(
			'interval' => 10 * MINUTE_IN_SECONDS,
			'display'  => __( 'Once in 10 minutes', 'buddyboss' ),
		),
		'bb_schedule_15min'   => array(
			'interval' => 15 * MINUTE_IN_SECONDS,
			'display'  => __( 'Once in 15 minutes', 'buddyboss' ),
		),
		'bb_schedule_30min'   => array(
			'interval' => 30 * MINUTE_IN_SECONDS,
			'display'  => __( 'Once in 30 minutes', 'buddyboss' ),
		),
		'bb_schedule_1hour'   => array(
			'interval' => 60 * MINUTE_IN_SECONDS,
			'display'  => __( 'Once Hourly', 'buddyboss' ),
		),
		'bb_schedule_3hours'  => array(
			'interval' => 180 * MINUTE_IN_SECONDS,
			'display'  => __( 'Once in 3 hours', 'buddyboss' ),
		),
		'bb_schedule_12hours' => array(
			'interval' => 720 * MINUTE_IN_SECONDS,
			'display'  => __( 'Once in 12 hours', 'buddyboss' ),
		),
		'bb_schedule_24hours' => array(
			'interval' => 1440 * MINUTE_IN_SECONDS,
			'display'  => __( 'Once in 24 hours', 'buddyboss' ),
		),
		'bb_schedule_15days'  => array(
			'interval' => 15 * DAY_IN_SECONDS,
			'display'  => __( 'Every 15 days', 'buddyboss' ),
		),
		'bb_schedule_30days'  => array(
			'interval' => 30 * DAY_IN_SECONDS,
			'display'  => __( 'Every 30 days', 'buddyboss' ),
		),
	);

	/**
	 * Filters the cron schedules.
	 *
	 * @param array $bb_schedules Schedules.
	 * @since BuddyBoss 1.7.0
	 */
	$bb_schedules = apply_filters( 'bp_core_cron_schedules', $bb_schedules );

	foreach ( $bb_schedules as $k => $bb_schedule ) {
		if ( ! isset( $schedules[ $k ] ) ) {
			$schedules[ $k ] = array(
				'interval' => $bb_schedule['interval'],
				'display'  => $bb_schedule['display'],
			);
		}
	}

	return $schedules;
}
add_filter( 'cron_schedules', 'bp_core_cron_schedules', 99, 1 ); // phpcs:ignore WordPress.WP.CronInterval.CronSchedulesInterval

/**
 * Filter to update the Avatar URL for the rest api.
 *
 * @since BuddyBoss 1.8.2
 *
 * @param string $gravatar Avatar Url.
 *
 * @return array|mixed|string|string[]
 */
function bb_rest_decode_default_avatar_url( $gravatar ) {
	if ( function_exists( 'bb_is_rest' ) && bb_is_rest() ) {
		$gravatar = str_replace( '&#038;', '&', $gravatar );
	}

	return $gravatar;
}

add_filter( 'bp_core_fetch_avatar_url', 'bb_rest_decode_default_avatar_url' );

/**
 * The custom profile and group avatar script data.
 *
 * @since BuddyBoss 1.8.6
 *
 * @param array  $script_data The avatar script data.
 * @param string $object      The object the avatar belongs to (eg: user or group).
 */
function bb_admin_setting_profile_group_add_script_data( $script_data, $object = '' ) {

	if ( 'bp-xprofile' === bp_core_get_admin_active_tab() ) {
		$script_data['bp_params'] = array(
			'object'     => 'user',
			'item_id'    => 0,
			'item_type'  => 'default',
			'has_avatar' => bb_has_default_custom_upload_profile_avatar(),
			'nonces'     => array(
				'set'    => wp_create_nonce( 'bp_avatar_cropstore' ),
				'remove' => wp_create_nonce( 'bp_delete_avatar_link' ),
			),
		);

		// Set feedback messages.
		$script_data['feedback_messages'] = array(
			1 => esc_html__( 'There was a problem cropping custom profile avatar.', 'buddyboss' ),
			2 => esc_html__( 'The custom profile avatar was uploaded successfully.', 'buddyboss' ),
			3 => esc_html__( 'There was a problem deleting custom profile avatar. Please try again.', 'buddyboss' ),
			4 => esc_html__( 'The custom profile avatar was deleted successfully!', 'buddyboss' ),
		);
	}

	if ( 'bp-groups' === bp_core_get_admin_active_tab() ) {
		$script_data['bp_params'] = array(
			'object'     => 'group',
			'item_id'    => 0,
			'item_type'  => 'default',
			'has_avatar' => bb_has_default_custom_upload_group_avatar(),
			'nonces'     => array(
				'set'    => wp_create_nonce( 'bp_avatar_cropstore' ),
				'remove' => wp_create_nonce( 'bp_group_avatar_delete' ),
			),
		);

		// Set feedback messages.
		$script_data['feedback_messages'] = array(
			1 => esc_html__( 'There was a problem cropping custom group avatar.', 'buddyboss' ),
			2 => esc_html__( 'The custom group avatar was uploaded successfully.', 'buddyboss' ),
			3 => esc_html__( 'There was a problem deleting custom group avatar. Please try again.', 'buddyboss' ),
			4 => esc_html__( 'The custom group avatar was deleted successfully!', 'buddyboss' ),
		);
	}

	return $script_data;
}

/**
 * Check ajax request is it for custom profile or group cover?
 *
 * @since BuddyBoss 1.8.6
 *
 * @return bool True if request from admin and it's for profile cover otherwise false.
 */
function bb_validate_custom_profile_group_avatar_ajax_reuqest() {
	$bp_params           = array();
	$profile_group_types = array( 'user', 'group' );
	$request_actions     = array( 'bp_cover_image_upload' );

	if ( ! isset( $_POST['action'] ) || ( isset( $_POST['action'] ) && ! in_array( sanitize_text_field( $_POST['action'] ), $request_actions, true ) ) ) {
		return false;
	}

	if ( ! isset( $_POST['bp_params'] ) || empty( $_POST['bp_params'] ) ) {
		return false;
	}

	$bp_params = $_POST['bp_params'];

	if ( ! is_admin() || ! isset( $bp_params['object'] ) || ! isset( $bp_params['item_id'] ) ) {
		return false;
	}

	$item_id = $bp_params['item_id'];
	$object  = $bp_params['object'];

	if ( ! is_admin() || 0 < $item_id || ! in_array( $object, $profile_group_types, true ) ) {
		return false;
	}

	return true;
}

/**
 * Setup upload directory for default custom profile or group cover.
 *
 * @since BuddyBoss 1.8.6
 *
 * @param array $upload_dir The original Uploads dir.
 * @return array Array containing the path, URL, and other helpful settings.
 */
function bb_default_custom_profile_group_cover_image_upload_dir( $upload_dir = array() ) {

	// Validate ajax request for upload custom profile and group cover.
	$is_validate = bb_validate_custom_profile_group_avatar_ajax_reuqest();

	if ( ! $is_validate ) {
		return $upload_dir;
	}

	$object = sanitize_text_field( $_POST['bp_params']['object'] );

	// Set the subdir.
	$subdir = '/members/0/cover-image';
	if ( 'group' === $object ) {
		$subdir = '/groups/0/cover-image';
	}

	$upload_dir = bp_attachments_uploads_dir_get();

	/**
	 * Filters set upload directory for default custom profile or group cover.
	 *
	 * @since BuddyBoss 1.8.6
	 *
	 * @param array $value Array containing the path, URL, and other helpful settings.
	 */
	return apply_filters(
		"bb_default_custom_{$object}_cover_image_upload_dir",
		array(
			'path'    => $upload_dir['basedir'] . $subdir,
			'url'     => set_url_scheme( $upload_dir['baseurl'] ) . $subdir,
			'subdir'  => $subdir,
			'basedir' => $upload_dir['basedir'],
			'baseurl' => set_url_scheme( $upload_dir['baseurl'] ),
			'error'   => false,
		),
		$upload_dir
	);

}

/**
 * The cover path for default custom cover upload.
 *
 * @since BuddyBoss 1.8.6
 *
 * @param string     $cover_dir  Path to the cover folder path.
 * @param string     $object_dir The object dir (eg: members/groups). Defaults to members.
 * @param int|string $item_id    The object id (eg: a user or a group id). Defaults to current user.
 * @param string     $type       The type of the attachment which is also the subdir where files are saved.
 *                               Defaults to 'cover-image'.
 * @return string Actual custom uploaded cover relative path.
 */
function bb_attachments_get_profile_group_attachment_dir( $cover_dir, $object_dir, $item_id, $type ) {
	// Validate ajax request for upload custom profile cover.
	$is_validate = bb_validate_custom_profile_group_avatar_ajax_reuqest();

	if ( ! $is_validate ) {
		return $cover_dir;
	}

	$object = sanitize_text_field( $_POST['bp_params']['object'] );

	$upload_dir = bp_attachments_uploads_dir_get();

	// Set the subdir.
	$subdir = '/members/0/cover-image';
	if ( 'group' === $object ) {
		$subdir = '/groups/0/cover-image';
	}

	return $upload_dir['basedir'] . $subdir;
}

/**
 * The cover sub path for default custom cover upload.
 *
 * @since BuddyBoss 1.8.6
 *
 * @param string     $cover_sub_dir Path to the cover folder path.
 * @param string     $object_dir    The object dir (eg: members/groups). Defaults to members.
 * @param int|string $item_id       The object id (eg: a user or a group id). Defaults to current user.
 * @param string     $type          The type of the attachment which is also the subdir where files are saved.
 *                                  Defaults to 'cover-image'.
 * @return string Actual custom uploaded cover relative sub path.
 */
function bb_attachments_get_profile_group_attachment_sub_dir( $cover_sub_dir, $object_dir, $item_id, $type ) {
	// Validate ajax request for upload custom profile cover.
	$is_validate = bb_validate_custom_profile_group_avatar_ajax_reuqest();

	if ( ! $is_validate ) {
		return $cover_sub_dir;
	}

	return $object_dir . '/0/' . $type;
}

/**
 * Save default profile and group avatar option on upload custom avatar.
 *
 * @since BuddyBoss 1.8.6
 *
 * @param string $item_id     Inform about the user id the avatar was set for.
 * @param string $type        Inform about the way the avatar was set ('camera').
 * @param array  $avatar_data Array of parameters passed to the avatar handler.
 */
function bb_save_profile_group_options_on_upload_custom_avatar( $item_id, $type, $avatar_data = array() ) {
	$item_id   = ! empty( $_POST['item_id'] ) ? (int) $_POST['item_id'] : '';
	$item_type = ! empty( $_POST['item_type'] ) ? sanitize_text_field( $_POST['item_type'] ) : null;

	if ( is_admin() && empty( $item_id ) && 'default' === $item_type ) {

		$avatar = isset( $avatar_data['avatar'] ) ? $avatar_data['avatar'] : '';

		if ( ! empty( $avatar ) ) {
			if ( 'user' === sanitize_text_field( $_POST['object'] ) ) {
				bp_update_option( 'bp-profile-avatar-type', 'BuddyBoss' );
				bp_update_option( 'bp-default-profile-avatar-type', 'custom' );
				bp_update_option( 'bp-default-custom-profile-avatar', $avatar );
			} elseif ( 'group' === sanitize_text_field( $_POST['object'] ) ) {
				bp_update_option( 'bp-disable-group-avatar-uploads', '' );
				bp_update_option( 'bp-default-group-avatar-type', 'custom' );
				bp_update_option( 'bp-default-custom-group-avatar', $avatar );
			}
		}
	}
}
add_action( 'xprofile_avatar_uploaded', 'bb_save_profile_group_options_on_upload_custom_avatar', 10, 3 );
add_action( 'groups_avatar_uploaded', 'bb_save_profile_group_options_on_upload_custom_avatar', 10, 3 );

/**
 * Save default profile and group avatar option on delete custom avatar.
 *
 * @since BuddyBoss 1.8.6
 *
 * @param array $args {
 *     Array of function parameters.
 *
 *     @type string|bool|int    $item_id    ID of the item whose avatar you're deleting.
 *                                          Defaults to the current item of type $object.
 *     @type string             $object     Object type of the item whose avatar you're
 *                                          deleting. 'user', 'group', 'blog', or custom.
 *                                          Default: 'user'.
 *     @type bool|string        $avatar_dir Subdirectory where avatar is located.
 *                                          Default: false, which falls back on the default location
 *                                          corresponding to the $object.
 * }
 */
function bb_delete_default_profile_group_avatar( $args ) {
	$item_id   = ! empty( $args['item_id'] ) ? (int) $args['item_id'] : '';
	$item_type = ! empty( $args['item_type'] ) ? sanitize_text_field( $args['item_type'] ) : null;

	if ( is_admin() && empty( $item_id ) && 'default' === $item_type ) {

		// check for user avatars getting deleted.
		if ( isset( $args['object'] ) && 'user' == $args['object'] ) {
			bp_update_option( 'bp-default-profile-avatar-type', 'buddyboss' );
			bp_update_option( 'bp-default-custom-profile-avatar', '' );
		}

		// check for group avatars getting deleted.
		if ( isset( $args['object'] ) && 'group' == $args['object'] ) {
			bp_update_option( 'bp-default-group-avatar-type', 'buddyboss' );
			bp_update_option( 'bp-default-custom-group-avatar', '' );
		}
	}
}
add_action( 'bp_core_delete_existing_avatar', 'bb_delete_default_profile_group_avatar', 10, 1 );

/**
 * Save default profile and group cover options on upload custom cover.
 *
 * @since BuddyBoss 1.8.6
 *
 * @param int $item_id Inform about the item id the cover photo was deleted for.
 */
function bb_save_profile_group_cover_options_on_upload_custom_cover( $item_id, $name, $cover_url, $feedback_code ) {

	$is_validate = bb_validate_custom_profile_group_avatar_ajax_reuqest();

	if ( $is_validate && ! empty( $cover_url ) ) {

		$object = sanitize_text_field( $_POST['bp_params']['object'] );

		if ( 'user' === $object ) {
			bp_update_option( 'bp-disable-cover-image-uploads', '' );
			bp_update_option( 'bp-default-profile-cover-type', 'custom' );
			bp_update_option( 'bp-default-custom-profile-cover', $cover_url );
		} elseif ( 'group' === $object ) {
			bp_update_option( 'bp-disable-group-cover-image-uploads', '' );
			bp_update_option( 'bp-default-group-cover-type', 'custom' );
			bp_update_option( 'bp-default-custom-group-cover', $cover_url );
		}
	}
}
add_action( 'xprofile_cover_image_uploaded', 'bb_save_profile_group_cover_options_on_upload_custom_cover', 10, 4 );
add_action( 'groups_cover_image_uploaded', 'bb_save_profile_group_cover_options_on_upload_custom_cover', 10, 4 );

/**
 * Save default profile and group cover options on delete custom cover.
 *
 * @since BuddyBoss 1.8.6
 *
 * @param int $item_id Inform about the item id the cover photo was deleted for.
 */
function bb_delete_profile_group_cover_images_url( $item_id ) {
	$item_id   = ! empty( $_POST['item_id'] ) ? (int) $_POST['item_id'] : '';
	$item_type = ! empty( $_POST['item_type'] ) ? sanitize_text_field( $_POST['item_type'] ) : null;

	if ( is_admin() && empty( $item_id ) && 'default' === $item_type ) {

		if ( 'user' === sanitize_text_field( $_POST['object'] ) ) {
			bp_update_option( 'bp-default-profile-cover-type', 'buddyboss' );
			bp_update_option( 'bp-default-custom-profile-cover', '' );
		} elseif ( 'group' === sanitize_text_field( $_POST['object'] ) ) {
			bp_update_option( 'bp-default-group-cover-type', 'buddyboss' );
			bp_update_option( 'bp-default-custom-group-cover', '' );
		}
	}
}
add_action( 'xprofile_cover_image_deleted', 'bb_delete_profile_group_cover_images_url', 10, 1 );
add_action( 'groups_cover_image_deleted', 'bb_delete_profile_group_cover_images_url', 10, 1 );

/**
 * Set gravatars when Gravatars is enabled from the Profile Images and Profile Avatars is BuddyBoss.
 *
 * @since BuddyBoss 1.8.6
 *
 * @param string $avatar_default The avatar default.
 * @param array  $params         The avatar's data.
 * @return string Set default 'mm' if upload avatars is 'BuddyBoss'.
 */
function bb_profile_set_gravatar_default( $avatar_default, $params ) {

	if ( 'BuddyBoss' === bb_get_profile_avatar_type() ) {
		$avatar_default = 'mm';
	}

	return $avatar_default;
}
add_filter( 'bp_core_avatar_default', 'bb_profile_set_gravatar_default', 10, 2 );

/**
 * Add inline css for profile and group cover background color when selected 'none' or 'BuddyBoss'.
 *
 * @since BuddyBoss 1.8.6
 */
function bb_add_default_cover_image_inline_css() {
	$css_rules = '.list-wrap .bs-group-cover a:before{ background:unset; }';

	// BuddyBoss theme is not activated.
	if ( ! function_exists( 'buddyboss_theme' ) ) {
		$css_rules .= '#buddypress #header-cover-image.has-default, #buddypress #header-cover-image.has-default .guillotine-window img, .bs-group-cover a img{ background-color: #e2e9ef; }';
	}

	// Buddyboss theme compatibility.
	$current_theme    = wp_get_theme();
	$bb_theme_version = $current_theme->get( 'Version' );

	if ( $current_theme->parent() ) {
		$bb_theme_version = $current_theme->parent()->get( 'Version' );
	}

	if ( function_exists( 'buddyboss_theme' ) && version_compare( $bb_theme_version, '1.8.4', '<' ) ) {

		$profile_cover_type = bb_get_default_profile_cover_type();
		$group_cover_type   = bb_get_default_group_cover_type();
		$background_color   = function_exists( 'buddyboss_theme_get_option' ) ? buddyboss_theme_get_option( 'buddyboss_theme_group_cover_bg' ) : '#e2e9ef';

		if ( empty( $background_color ) ) {
			// Set default color.
			$background_color = '#e2e9ef';
		}

		if ( ! bp_disable_cover_image_uploads() && 'custom' !== $profile_cover_type ) {
			$css_rules .= '.bp_members #buddypress #header-cover-image, .bp_members #buddypress #header-cover-image .guillotine-window img{ background-color: ' . $background_color . '; }';
		}

		if ( ! bp_disable_group_cover_image_uploads() && 'custom' !== $group_cover_type ) {
			$css_rules .= '.bp_group #buddypress #header-cover-image, .bp_group #buddypress #header-cover-image .guillotine-window img, .list-wrap .bs-group-cover a{ background-color: ' . $background_color . '; }';
		}
	}

	wp_add_inline_style( 'bp-nouveau', $css_rules );
}
add_action( 'bp_enqueue_scripts', 'bb_add_default_cover_image_inline_css', 12 );

/**
 * Enable gravatars for members when Profile Avatars is WordPress.
 *
 * @since BuddyBoss 1.8.6
 *
 * @param bool  $no_grav Whether or not to skip Gravatar.
 * @param array $params Array of parameters for the avatar request.
 * @return bool.
 */
function bb_member_enabled_gravatar( $no_grav, $params ) {

	if ( ! isset( $params['object'] ) ) {
		return $no_grav;
	}

	// By default, Gravatar is pinged for members when WordPress is enabled.
	$show_avatars = bp_get_option( 'show_avatars' );

	if ( 'user' === $params['object'] && $show_avatars && 'WordPress' === bb_get_profile_avatar_type() ) {
		$no_grav = false;
	}

	return $no_grav;
}
add_filter( 'bp_core_fetch_avatar_no_grav', 'bb_member_enabled_gravatar', 99, 2 );

/**
 * Filter the admin emails by notification preference.
 *
 * @since BuddyBoss 1.9.3
 *
 * @param WP_Query $query The WP_Query instance (passed by reference).
 */
function bb_filter_admin_emails( $query ) {
	if (
		is_admin() &&
		$query->get( 'post_type' ) === bp_get_email_post_type() &&
		! empty( $_GET['terms'] ) // phpcs:ignore
	) {
		$taxquery = array(
			array(
				'taxonomy' => bp_get_email_tax_type(),
				'field'    => 'slug',
				'terms'    => explode( ',', $_GET['terms'] ), // phpcs:ignore
			),
		);

		$query->set( 'tax_query', $taxquery );
	}
}
add_action( 'pre_get_posts', 'bb_filter_admin_emails' );

/**
 * Filter to change the display user URLs and current user URLs.
 *
 * @since BuddyBoss 2.0.6
 *
 * @param array    $atts {
 *        The HTML attributes applied to the menu item's `<a>` element, empty strings are ignored.
 *
 *     @type string $title  Title attribute.
 *     @type string $target Target attribute.
 *     @type string $rel    The rel attribute.
 *     @type string $href   The href attribute.
 * }
 * @param WP_Post  $item  The current menu item.
 * @param stdClass $args  An object of wp_nav_menu() arguments.
 * @param int      $depth Depth of menu item. Used for padding.
 */
function bb_change_nav_menu_links( $atts, $item, $args, $depth ) {

	if ( isset( $item->menu_type ) && 'buddyboss' === $item->menu_type && isset( $atts['href'] ) ) {
		if ( bp_loggedin_user_domain() !== bp_displayed_user_domain() ) {
			$atts['href'] = str_replace( bp_displayed_user_domain(), bp_loggedin_user_domain(), $atts['href'] );
		}
	}

	return $atts;
}
add_filter( 'nav_menu_link_attributes', 'bb_change_nav_menu_links', 10, 4 );

/**
 * Filters to update the active classes for display user URLs and current user URLs.
 *
 * @since BuddyBoss 2.0.6
 *
 * @param array    $classes The CSS classes that are applied to the menu item's `<li>` element.
 * @param WP_Post  $item    The current menu item.
 * @param stdClass $args    An object of wp_nav_menu() arguments.
 * @param int      $depth   Depth of menu item. Used for padding.
 */
function bb_change_nav_menu_class( $classes, $item, $args, $depth ) {

	if ( isset( $item->menu_type ) && 'buddyboss' === $item->menu_type && ! bp_is_groups_component() ) {
		if ( bp_loggedin_user_domain() !== bp_displayed_user_domain() ) {
			$classes = array_diff( $classes, array( 'current-menu-item', 'current_page_item' ) );
		}
	}

	return $classes;
}
add_filter( 'nav_menu_css_class', 'bb_change_nav_menu_class', 10, 4 );

/**
 * Update the digest schedule event on change messages component status.
 *
 * @since BuddyBoss 2.1.4
 *
 * @param array $active_components Components to install.
 */
function bb_update_digest_schedule_event_on_change_component_status( $active_components = array() ) {

	$active_components = array_keys( $active_components );
	$db_component      = array_keys( bp_get_option( 'bp-active-components', array() ) );

	// If 'messages' component is disabled.
	if ( in_array( 'messages', $db_component, true ) && ! in_array( 'messages', $active_components, true ) ) {
		$timestamp = wp_next_scheduled( 'bb_digest_email_notifications_hook' );
		if ( $timestamp ) {
			wp_unschedule_event( $timestamp, 'bb_digest_email_notifications_hook' );
		}
		// If 'messages' component is enabled.
	} elseif ( ! in_array( 'messages', $db_component, true ) && in_array( 'messages', $active_components, true ) ) {

		$time_delay_email_notification = (int) bp_get_option( 'time_delay_email_notification', 15 );
		$schedule_key                  = 'bb_schedule_15min';
		if ( 5 === $time_delay_email_notification ) {
			$schedule_key = 'bb_schedule_5min';
		} elseif ( 30 === $time_delay_email_notification ) {
			$schedule_key = 'bb_schedule_30min';
		} elseif ( 60 === $time_delay_email_notification ) {
			$schedule_key = 'bb_schedule_1hour';
		} elseif ( 180 === $time_delay_email_notification ) {
			$schedule_key = 'bb_schedule_3hours';
		} elseif ( 720 === $time_delay_email_notification ) {
			$schedule_key = 'bb_schedule_12hours';
		} elseif ( 1440 === $time_delay_email_notification ) {
			$schedule_key = 'bb_schedule_24hours';
		}

		// Schedule an action if it's not already scheduled.
		if ( ! wp_next_scheduled( 'bb_digest_email_notifications_hook' ) ) {
			wp_schedule_event( time(), $schedule_key, 'bb_digest_email_notifications_hook' );
		}
	}

}
add_action( 'bp_core_install', 'bb_update_digest_schedule_event_on_change_component_status', 10, 1 );

/**
 * Get member presence information.
 *
 * @since BuddyBoss 2.1.4
 *
 * @return array
 */
function bb_heartbeat_member_presence_info( $response = array(), $data = array() ) {
	if ( ! isset( $data['presence_users'] ) ) {
		return $response;
	}

	bp_core_record_activity();

	$presence_user_ids          = wp_parse_id_list( $data['presence_users'] );
	$response['users_presence'] = bb_get_users_presence( $presence_user_ids );

	return $response;
}
add_filter( 'heartbeat_received', 'bb_heartbeat_member_presence_info', 11, 2 );
add_filter( 'heartbeat_nopriv_received', 'bb_heartbeat_member_presence_info', 11, 2 );


/**
 * Update interval time option when someone change the heartbeat interval.
 *
 * @since BuddyBoss 2.1.4
 *
 * @param array $settings Array of heartbeat settings.
 *
 * @return mixed
 */
function bb_heartbeat_settings( $settings ) {
	$interval_time = bb_presence_interval();

	if ( isset( $settings['interval'] ) && $settings['interval'] !== $interval_time ) {
		bp_update_option( 'bb_presence_interval', absint( $settings['interval'] ) );
	} else {
		bp_delete_option( 'bb_presence_interval' );
	}

	return $settings;
}

add_filter( 'heartbeat_settings', 'bb_heartbeat_settings', PHP_INT_MAX, 1 );

/**
 * Function to update menu order for Theme Options and License Key in the BuddyBoss menu.
 *
 * @since BuddyBoss 2.2.2
 *
 * @param array $menu_order Menu order.
 *
 * @return array
 */
function buddyboss_menu_order( $menu_order ) {
	global $submenu;
	$buddyboss_theme_options_menu = array();
	$buddyboss_theme_font_menu    = array();
	$buddyboss_updater_menu       = array();
	$sep_position                 = 0;

	$after_sep = array();

	if ( ! empty( $submenu['buddyboss-platform'] ) ) {
		foreach ( $submenu['buddyboss-platform'] as $key => $val ) {
			if ( isset( $val[2] ) ) {

				if ( 'buddyboss_theme_options' === $val[2] ) {
					$buddyboss_theme_options_menu = $submenu['buddyboss-platform'][ $key ];
					unset( $submenu['buddyboss-platform'][ $key ] );
					continue;
				}

				if ( 'edit.php?post_type=buddyboss_fonts' === $val[2] ) {
					$buddyboss_theme_font_menu = $submenu['buddyboss-platform'][ $key ];
					unset( $submenu['buddyboss-platform'][ $key ] );
					continue;
				}

				if ( 'buddyboss-updater' === $val[2] ) {
					$buddyboss_updater_menu = $submenu['buddyboss-platform'][ $key ];
					unset( $submenu['buddyboss-platform'][ $key ] );
					continue;
				}

				if ( 0 !== $sep_position ) {
					$after_sep[] = $val;
					unset( $submenu['buddyboss-platform'][ $key ] );
				}

				if ( 'bp-plugin-seperator' === $val[2] && 0 === $sep_position ) {
					$sep_position = $key;
				}
			}
		}

		if ( ! empty( $buddyboss_theme_options_menu ) ) {
			$submenu['buddyboss-platform'][ ++ $sep_position ] = $buddyboss_theme_options_menu; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		}

		if ( ! empty( $buddyboss_theme_font_menu ) ) {
			$submenu['buddyboss-platform'][ ++ $sep_position ] = $buddyboss_theme_font_menu; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		}

		if ( ! empty( $after_sep ) ) {
			foreach ( $after_sep as $menu ) {
				$submenu['buddyboss-platform'][ ++ $sep_position ] = $menu; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
			}
		}

		if ( ! empty( $buddyboss_updater_menu ) ) {
			$submenu['buddyboss-platform'][ ++ $sep_position ] = $buddyboss_updater_menu; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		}
	}

	return $menu_order;
}

add_filter( 'menu_order', 'buddyboss_menu_order' );

/**
 * Function to remove html entity from number format.
 *
 * @since BuddyBoss 2.3.1
 *
 * @param string $formatted The number to be formatted.
 *
 * @return string
 */
function bb_core_number_format_callback( $formatted ) {
	return html_entity_decode( $formatted, ENT_NOQUOTES );
}

add_filter( 'bp_core_number_format', 'bb_core_number_format_callback', 10, 1 );

/**
 * Fix the issue with loom embed not working correctly.
 *
 * @since BuddyBoss 2.4.20
 *
 * @param string $return The returned oEmbed HTML.
 * @param object $data   A data object result from an oEmbed provider.
 * @param string $url    The URL of the content to be embedded.
 *
 * @return array|mixed|string|string[]|null
 */
function bb_oembed_dataparse( $return, $data, $url ) {
	if ( ! empty( $return ) && false !== strpos( $return, 'loom.com' ) ) {
		$return = preg_replace( '/\s*sandbox\s*=\s*(["\']).*?\1/', '', $return );
	}

	return $return;
}

add_filter( 'oembed_dataparse', 'bb_oembed_dataparse', 999, 3 );

/**
 * Make the loom video embed discoverable.
 *
 * @since BuddyBoss 2.4.20
 *
 * @param bool   $retval Return value to enabled discover support or not.
 * @param string $url    URL to parse for embed.
 *
 * @return bool
 */
function bb_loom_oembed_discover_support( $retval, $url ) {
	if ( ! empty( $url ) && false !== strpos( $url, 'loom.com' ) ) {
		$retval = true;
	}

	return $retval;
}

add_filter( 'bb_oembed_discover_support', 'bb_loom_oembed_discover_support', 10, 2 );

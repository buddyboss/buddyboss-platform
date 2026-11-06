<?php
/**
 * Deprecated Functions
 *
 * @package BuddyBoss\Core
 * @deprecated Since 1.5.0
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/** Loader ********************************************************************/

/**
 * bboss_setup_root_components()
 *
 * @deprecated BuddyPress 1.5.0
 */
function bboss_setup_root_components() {
	do_action( 'bp_setup_root_components' );
}
add_action( 'bp_init', 'bboss_setup_root_components', 6 );

/** WP Abstraction ************************************************************/

/**
 * bboss_core_is_multisite()
 *
 * This function originally served as a wrapper when WordPress and WordPress MU were separate entities.
 * Use is_multisite() instead.
 *
 * @deprecated BuddyPress 1.5.0
 * @deprecated Use is_multisite()
 *
 * @return bool
 */
function bboss_core_is_multisite() {
	_deprecated_function( __FUNCTION__, '1.5', 'is_multisite()' );
	return is_multisite();
}

/**
 * bp_core_is_main_site
 *
 * Checks if current blog is root blog of site. Deprecated in 1.5.
 *
 * @deprecated BuddyPress 1.5.0
 * @deprecated Use is_main_site()
 * @param int|string $blog_id optional blog id to test (default current blog)
 * @return bool True if not multisite or $blog_id is main site
 * @since BuddyPress 1.2.6
 */
function bboss_core_is_main_site( $blog_id = '' ) {
	_deprecated_function( __FUNCTION__, '1.5', 'is_main_site()' );
	return is_main_site( $blog_id );
}

if ( ! function_exists( 'is_site_admin' ) ) {
	/**
	 * WPMU version of is_super_admin()
	 *
	 * @deprecated BuddyPress 1.5.0
	 * @deprecated Use is_super_admin()
	 * @param int|bool $user_id Optional. Defaults to logged-in user
	 * @return bool True if is super admin
	 */
	function is_site_admin( $user_id = false ) {
		_deprecated_function( __FUNCTION__, '1.5', 'is_super_admin()' );
		return is_super_admin( $user_id );
	}
}

/** Admin ******************************************************************/

/**
 * In BuddyPress 1.1 - 1.2.x, this function provided a better version of add_menu_page()
 * that allowed positioning of menus. Deprecated in 1.5 in favour of a WP core function.
 *
 * @deprecated BuddyPress 1.5.0
 * @deprecated Use add_menu_page().
 * @since BuddyPress 1.1.0
 *
 * @return string
 */
function bboss_core_add_admin_menu_page( $args = '' ) {
	global $_registered_pages, $admin_page_hooks, $menu;

	_deprecated_function( __FUNCTION__, '1.5', 'Use add_menu_page()' );

	$defaults = array(
		'access_level' => 2,
		'file'         => false,
		'function'     => false,
		'icon_url'     => false,
		'menu_title'   => '',
		'page_title'   => '',
		'position'     => 100,
	);

	$r = bboss_parse_args( $args, $defaults );
	extract( $r, EXTR_SKIP );

	$file     = plugin_basename( $file );
	$hookname = get_plugin_page_hookname( $file, '' );

	$admin_page_hooks[ $file ] = sanitize_title( $menu_title );

	if ( ! empty( $function ) && ! empty( $hookname ) ) {
		add_action( $hookname, $function );
	}

	if ( empty( $icon_url ) ) {
		$icon_url = 'images/generic.png';
	} elseif ( is_ssl() && 0 === strpos( $icon_url, 'http://' ) ) {
		$icon_url = 'https://' . substr( $icon_url, 7 );
	}

	do {
		$position++;
	} while ( ! empty( $menu[ $position ] ) );

	$menu[ $position ]              = array( $menu_title, $access_level, $file, $page_title, 'menu-top ' . $hookname, $hookname, $icon_url );
	$_registered_pages[ $hookname ] = true;

	return $hookname;
}
/** Activity ******************************************************************/

/**
 * bboss_is_activity_permalink()
 *
 * @deprecated BuddyPress 1.5.0
 */
function bboss_is_activity_permalink() {
	_deprecated_function( __FUNCTION__, '1.5', 'bp_is_single_activity' );
	bboss_is_single_activity();
}

/** Core **********************************************************************/

/**
 * bboss_core_get_wp_profile()
 *
 * @deprecated BuddyPress 1.5.0
 */
function bboss_core_get_wp_profile() {
	_deprecated_function( __FUNCTION__, '1.5' );

	$ud = get_userdata( bboss_displayed_user_id() ); ?>

<div class="bp-widget wp-profile">
	<h4><?php esc_html_e( 'My Profile', 'buddyboss-platform' ); ?></h4>

	<table class="wp-profile-fields">

		<?php if ( $ud->display_name ) : ?>

			<tr id="wp_displayname">
				<td class="label"><?php esc_html_e( 'Name', 'buddyboss-platform' ); ?></td>
				<td class="data"><?php echo esc_html( $ud->display_name ); ?></td>
			</tr>

		<?php endif; ?>

		<?php if ( $ud->user_description ) : ?>

			<tr id="wp_desc">
				<td class="label"><?php esc_html_e( 'About Me', 'buddyboss-platform' ); ?></td>
				<td class="data"><?php echo esc_html( $ud->user_description ); ?></td>
			</tr>

		<?php endif; ?>

		<?php if ( $ud->user_url ) : ?>

			<tr id="wp_website">
				<td class="label"><?php esc_html_e( 'Website', 'buddyboss-platform' ); ?></td>
				<td class="data"><?php echo wp_kses_post( make_clickable( $ud->user_url ) ); ?></td>
			</tr>

		<?php endif; ?>

		<?php if ( $ud->jabber ) : ?>

			<tr id="wp_jabber">
				<td class="label"><?php esc_html_e( 'Jabber', 'buddyboss-platform' ); ?></td>
				<td class="data"><?php echo esc_html( $ud->jabber ); ?></td>
			</tr>

		<?php endif; ?>

		<?php if ( $ud->aim ) : ?>

			<tr id="wp_aim">
				<td class="label"><?php esc_html_e( 'AOL Messenger', 'buddyboss-platform' ); ?></td>
				<td class="data"><?php echo esc_html( $ud->aim ); ?></td>
			</tr>

		<?php endif; ?>

		<?php if ( $ud->yim ) : ?>

			<tr id="wp_yim">
				<td class="label"><?php esc_html_e( 'Yahoo Messenger', 'buddyboss-platform' ); ?></td>
				<td class="data"><?php echo esc_html( $ud->yim ); ?></td>
			</tr>

		<?php endif; ?>

	</table>
</div>

	<?php
}

/**
 * bboss_is_home()
 *
 * @deprecated BuddyPress 1.5.0
 * @deprecated Use bboss_is_my_profile()
 */
function bboss_is_home() {
	_deprecated_function( __FUNCTION__, '1.5', 'bp_is_my_profile' );
	return bboss_is_my_profile();
}

/**
 * Is the user on the front page of the site?
 *
 * @deprecated BuddyPress 1.5.0
 * @deprecated Use is_front_page()
 * @return bool
 */
function bboss_is_front_page() {
	_deprecated_function( __FUNCTION__, '1.5', 'is_front_page()' );
	return is_front_page();
}

/**
 * Is the front page of the site set to the Activity component?
 *
 * @deprecated BuddyPress 1.5.0
 * @deprecated Use bp_is_component_front_page( 'activity' )
 * @return bool
 */
function bboss_is_activity_front_page() {
	_deprecated_function( __FUNCTION__, '1.5', "bp_is_component_front_page( 'activity' )" );
	return bboss_is_component_front_page( 'activity' );
}

/**
 * bboss_is_member()
 *
 * @deprecated BuddyPress 1.5.0
 * @deprecated use bboss_is_user()
 */
function bboss_is_member() {
	_deprecated_function( __FUNCTION__, '1.5', 'bp_is_user' );
	return bboss_is_user();
}

/**
 * bboss_loggedinuser_link()
 *
 * @deprecated BuddyPress 1.5.0
 * @deprecated use bboss_loggedin_user_link()
 */
function bboss_loggedinuser_link() {
	_deprecated_function( __FUNCTION__, '1.5', 'bp_loggedin_user_link' );
	bboss_loggedin_user_link();
}

/**
 * Only show the search form if there are available objects to search for.
 * Deprecated in 1.5; not used anymore.
 *
 * @deprecated BuddyPress 1.5.0
 * @return bool
 */
function bboss_search_form_enabled() {
	_deprecated_function( __FUNCTION__, '1.5', 'No longer required.' );
	return apply_filters( 'bp_search_form_enabled', true );
}

/**
 * Template tag version of bboss_get_page_title()
 *
 * @deprecated BuddyPress 1.5.0
 * @deprecated Use wp_title()
 * @since BuddyPress 1.0.0
 */
function bboss_page_title() {
	echo esc_html( bboss_get_page_title() );
}
	/**
	 * Prior to BuddyPress 1.5, this was used to generate the page's <title> text.
	 * Now, just simply use wp_title().
	 *
	 * @deprecated BuddyPress 1.5.0
	 * @deprecated Use wp_title()
	 * @since BuddyPress 1.0.0
	 *
	 * @return string
	 */
function bboss_get_page_title() {
	_deprecated_function( __FUNCTION__, '1.5', 'wp_title()' );
	$title = wp_title( '|', false, 'right' ) . get_bloginfo( 'name', 'display' );

	// Backpat for BP 1.2 filter
	$title = apply_filters( 'bp_page_title', esc_attr( $title ), esc_attr( $title ) );

	return apply_filters( 'bp_get_page_title', $title );
}

/**
 * Generate a link to log out. Last used in BP 1.2-beta. You should be using wp_logout_url().
 *
 * @deprecated BuddyPress 1.5.0
 * @deprecated Use wp_logout_url()
 * @since BuddyPress 1.0.0
 */
function bboss_log_out_link() {
	_deprecated_function( __FUNCTION__, '1.5', 'wp_logout_url()' );

	$logout_link = '<a href="' . wp_logout_url( bboss_get_root_domain() ) . '">' . __( 'Log Out', 'buddyboss-platform' ) . '</a>';
	echo wp_kses_post( apply_filters( 'bp_logout_link', $logout_link ) );
}

/**
 * Send an email and a BP notification on receipt of an @-mention in a group
 *
 * @deprecated BuddyPress 1.5.0
 * @deprecated Deprecated in favor of the more general bboss_activity_at_message_notification()
 */
function bboss_groups_at_message_notification( $content, $poster_user_id, $group_id, $activity_id ) {
	_deprecated_function( __FUNCTION__, '1.5', 'bp_activity_at_message_notification()' );

	/* Scan for @username strings in an activity update. Notify each user. */
	$pattern = '/[@]+([A-Za-z0-9-_\.@]+)/';
	preg_match_all( $pattern, $content, $usernames );

	/* Make sure there's only one instance of each username */
	if ( ! $usernames = array_unique( $usernames[1] ) ) {
		return false;
	}

	$group = new BBoss_Groups_Group( $group_id );

	foreach ( (array) $usernames as $username ) {
		if ( ! $receiver_user_id = bboss_core_get_userid( $username ) ) {
			continue;
		}

		/* Check the user is a member of the group before sending the update. */
		if ( ! bboss_groups_is_user_member( $receiver_user_id, $group_id ) ) {
			continue;
		}

		// Now email the user with the contents of the message (if they have enabled email notifications)
		if ( 'no' != bboss_get_user_meta( $receiver_user_id, 'notification_activity_new_mention', true ) ) {
			$poster_name = bboss_core_get_user_displayname( $poster_user_id );

			$message_link  = bboss_activity_get_permalink( $activity_id );
			$settings_slug = function_exists( 'bboss_get_settings_slug' ) ? bboss_get_settings_slug() : 'settings';
			$settings_link = bboss_core_get_user_domain( $receiver_user_id ) . $settings_slug . '/notifications/';

			$poster_name = stripslashes( $poster_name );
			$content     = bboss_groups_filter_kses( stripslashes( $content ) );

			// Set up and send the message
			$ud      = bboss_core_get_core_userdata( $receiver_user_id );
			$to      = $ud->user_email;
			/* translators: 1: member display name, 2: group name. */
			$subject = bboss_get_email_subject( array( 'text' => sprintf( __( '%1$s mentioned you in the group "%2$s"', 'buddyboss-platform' ), $poster_name, $group->name ) ) );

			$message = sprintf(
				/* translators: 1: member display name, 2: group name, 3: message excerpt, 4: link to the activity, 5: link to the notification settings. */
				__(
					'%1$s mentioned you in the group "%2$s":

"%3$s"

To view and respond to the message, log in and visit: %4$s

---------------------
',
					'buddyboss-platform'
				),
				$poster_name,
				$group->name,
				$content,
				$message_link
			);

			/* Send the message */
			$to      = apply_filters( 'groups_at_message_notification_to', $to );
			$subject = apply_filters( 'groups_at_message_notification_subject', $subject, $group, $poster_name );
			$message = apply_filters( 'groups_at_message_notification_message', $message, $group, $poster_name, $content, $message_link, $settings_link );

			wp_mail( $to, $subject, $message );
		}
	}

	do_action( 'bp_groups_sent_mention_email', $usernames, $subject, $message, $content, $poster_user_id, $group_id, $activity_id );
}

/**
 * BP 1.5 simplified notification functions a bit
 *
 * @deprecated BuddyPress 1.5.0
 *
 * @return mixed
 */
function bboss_core_delete_notifications_for_user_by_type( $user_id, $component_name, $component_action ) {
	_deprecated_function( __FUNCTION__, '1.5', 'bp_core_delete_notifications_by_type()' );
	return BBoss_Core_Notification::delete_for_user_by_type( $user_id, $component_name, $component_action );
}

/**
 * bboss_core_delete_notifications_for_user_by_item_id()
 *
 * @return mixed
 */
function bboss_core_delete_notifications_for_user_by_item_id( $user_id, $item_id, $component_name, $component_action, $secondary_item_id = false ) {
	_deprecated_function( __FUNCTION__, '1.5', 'bp_core_delete_notifications_by_item_id()' );
	return BBoss_Core_Notification::delete_for_user_by_item_id( $user_id, $item_id, $component_name, $component_action, $secondary_item_id );
}

/**
 * Last used by core in BP 1.1. The markup was merged into DTheme's header.php template.
 *
 * @deprecated BuddyPress 1.5.0
 */
function bboss_search_form() {
	_deprecated_function( __FUNCTION__, '1.1', 'No longer required.' );

	$form = '
		<form action="' . bboss_search_form_action() . '" method="post" id="search-form">
			<input type="text" id="search-terms" name="search-terms" value="" />
			' . bboss_search_form_type_select() . '

			<input type="submit" name="search-submit" id="search-submit" value="' . __( 'Search', 'buddyboss-platform' ) . '" />
			' . wp_nonce_field( 'bp_search_form' ) . '
		</form>
	';

	echo apply_filters( 'bp_search_form', $form ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- buffered search-form template (contains <form>/<input>); wp_kses_post() would strip the form controls.
}

/**
 * Some _is_ function had their names normalized
 *
 * @deprecated BuddyPress 1.5.0
 */
function bboss_is_profile_edit() {
	_deprecated_function( __FUNCTION__, '1.5', 'bp_is_user_profile_edit()' );
	return bboss_is_user_profile_edit();
}

/**
 * bboss_is_change_avatar()
 *
 * @deprecated BuddyPress 1.5.0
 */
function bboss_is_change_avatar() {
	_deprecated_function( __FUNCTION__, '1.5', 'bp_is_user_change_avatar()' );
	return bboss_is_user_change_avatar();
}

/**
 * bboss_is_friend_requests()
 *
 * @deprecated BuddyPress 1.5.0
 */
function bboss_is_friend_requests() {
	_deprecated_function( __FUNCTION__, '1.5', 'bp_is_user_friend_requests()' );
	return bboss_is_user_friend_requests();
}

/**
 * Checks to see if a component's URL should be in the root, not under a member page:
 * eg: http://example.com/groups/the-group NOT http://example.com/members/andy/groups/the-group
 * You should be using bboss_is_root_component().
 *
 * @deprecated BuddyPress 1.5.0
 * @deprecated bboss_is_root_component()
 * @return bool True if root component, else false.
 */
function bboss_core_is_root_component( $component_name ) {
	_deprecated_function( __FUNCTION__, '1.5', 'bp_is_root_component()' );
	return bboss_is_root_component( $component_name );
}

/** Theme *********************************************************************/

/**
 * Contains functions which were moved out of BP-Default's functions.php
 * in BuddyPress 1.5.
 *
 * @deprecated BuddyPress 1.5.0
 * @since BuddyPress 1.5.0
 */
function bboss_dtheme_deprecated() {
	if ( ! function_exists( 'bboss_dtheme_wp_pages_filter' ) ) :
		/**
		 * In BuddyPress 1.2.x, this function filtered the dropdown on the
		 * Settings > Reading screen for selecting the page to show on front to
		 * include "Activity Feed." As of 1.5.x, it is no longer required.
		 *
		 * @deprecated BuddyPress 1.5.0
		 * @deprecated No longer required.
		 * @param string $page_html A list of pages as a dropdown (select list)
		 * @return string
		 * @see wp_dropdown_pages()
		 * @since BuddyPress 1.2.0
		 */
		function bboss_dtheme_wp_pages_filter( $page_html ) {
			_deprecated_function( __FUNCTION__, '1.5', 'No longer required.' );
			return $page_html;
		}
	endif;

	if ( ! function_exists( 'bboss_dtheme_page_on_front_update' ) ) :
		/**
		 * In BuddyPress 1.2.x, this function hijacked the saving of page on front setting to save the activity feed setting.
		 * As of 1.5.x, it is no longer required.
		 *
		 * @deprecated BuddyPress 1.5.0
		 * @deprecated No longer required.
		 * @param string $oldvalue Previous value of get_option( 'page_on_front' )
		 * @param string $oldvalue New value of get_option( 'page_on_front' )
		 * @return false|string
		 * @since BuddyPress 1.2.0
		 */
		function bboss_dtheme_page_on_front_update( $oldvalue, $newvalue ) {
			_deprecated_function( __FUNCTION__, '1.5', 'No longer required.' );
			if ( ! is_admin() || ! bboss_current_user_can( 'bp_moderate' ) ) {
				return false;
			}

			return $oldvalue;
		}
	endif;

	if ( ! function_exists( 'bboss_dtheme_page_on_front_template' ) ) :
		/**
		 * In BuddyPress 1.2.x, this function loaded the activity feed template if the front page display settings allow.
		 * As of 1.5.x, it is no longer required.
		 *
		 * @deprecated BuddyPress 1.5.0
		 * @deprecated No longer required.
		 * @param string $template Absolute path to the page template
		 * @return string
		 * @since BuddyPress 1.2.0
		 */
		function bboss_dtheme_page_on_front_template( $template ) {
			_deprecated_function( __FUNCTION__, '1.5', 'No longer required.' );
			return $template;
		}
	endif;

	if ( ! function_exists( 'bboss_dtheme_fix_get_posts_on_activity_front' ) ) :
		/**
		 * In BuddyPress 1.2.x, this forced the page ID as a string to stop the get_posts query from kicking up a fuss.
		 * As of 1.5.x, it is no longer required.
		 *
		 * @deprecated BuddyPress 1.5.0
		 * @deprecated No longer required.
		 * @since BuddyPress 1.2.0
		 */
		function bboss_dtheme_fix_get_posts_on_activity_front() {
			_deprecated_function( __FUNCTION__, '1.5', 'No longer required.' );
		}
	endif;

	if ( ! function_exists( 'bboss_dtheme_fix_the_posts_on_activity_front' ) ) :
		/**
		 * In BuddyPress 1.2.x, this was used as part of the code that set the activity feed to be on the front page.
		 * As of 1.5.x, it is no longer required.
		 *
		 * @deprecated BuddyPress 1.5.0
		 * @deprecated No longer required.
		 * @param array $posts Posts as retrieved by WP_Query
		 * @return array
		 * @since BuddyPress 1.2.5
		 */
		function bboss_dtheme_fix_the_posts_on_activity_front( $posts ) {
			_deprecated_function( __FUNCTION__, '1.5', 'No longer required.' );
			return $posts;
		}
	endif;

	if ( ! function_exists( 'bboss_dtheme_add_blog_comments_js' ) ) :
		/**
		 * In BuddyPress 1.2.x, this added the JavaScript needed for blog comment replies.
		 * As of 1.5.x, we recommend that you enqueue the comment-reply JavaScript in your theme's header.php.
		 *
		 * @deprecated BuddyPress 1.5.0
		 * @deprecated Enqueue the comment-reply script in your theme's header.php.
		 * @since BuddyPress 1.2.0
		 */
		function bboss_dtheme_add_blog_comments_js() {
			_deprecated_function( __FUNCTION__, '1.5', "Enqueue the comment-reply script in your theme's header.php." );
			if ( is_singular() && bboss_is_blog_page() && get_option( 'thread_comments' ) ) {
				wp_enqueue_script( 'comment-reply' );
			}
		}
	endif;
}
add_action( 'after_setup_theme', 'bboss_dtheme_deprecated', 15 );

/**
 * In BP 1.5, the Settings functions were moved out of the Core and Members
 * components, and moved into a new Settings component. This function is no
 * longer needed as the nav structure is set up by the {@link BBoss_Component} class.
 *
 * @deprecated BuddyPress 1.5.0
 * @since BuddyPress 1.6.0
 */
function bboss_core_add_settings_nav() {
	_deprecated_function( __FUNCTION__, '1.5' );
}

/**
 * In BP 1.5, the Settings functions were moved out of the Core and Members
 * components, and moved into a new Settings component. This function is no
 * longer needed as new template files for the Settings component were
 * introduced.
 *
 * @deprecated BuddyPress 1.5.0
 * @since BuddyPress 1.6.0
 */
function bboss_core_screen_general_settings() {
	_deprecated_function( __FUNCTION__, '1.5', 'Moved into theme template' );
}

/**
 * In BP 1.5, the Settings functions were moved out of the Core and Members
 * components, and moved into a new Settings component. This function is no
 * longer needed as new template files for the Settings component were
 * introduced.
 *
 * @deprecated BuddyPress 1.5.0
 * @since BuddyPress 1.6.0
 */
function bboss_core_screen_general_settings_title() {
	_deprecated_function( __FUNCTION__, '1.5', 'Moved into theme template' );
}

/**
 * In BP 1.5, the Settings functions were moved out of the Core and Members
 * components, and moved into a new Settings component. This function is no
 * longer needed as new template files for the Settings component were
 * introduced.
 *
 * @deprecated BuddyPress 1.5.0
 * @since BuddyPress 1.6.0
 */
function bboss_core_screen_general_settings_content() {
	_deprecated_function( __FUNCTION__, '1.5', 'Moved into theme template' );
}

/**
 * In BP 1.5, the Settings functions were moved out of the Core and Members
 * components, and moved into a new Settings component. This function is no
 * longer needed as new template files for the Settings component were
 * introduced.
 *
 * @deprecated BuddyPress 1.5.0
 * @since BuddyPress 1.6.0
 */
function bboss_core_screen_notification_settings() {
	_deprecated_function( __FUNCTION__, '1.5', 'Moved into theme template' );
}

/**
 * In BP 1.5, the Settings functions were moved out of the Core and Members
 * components, and moved into a new Settings component. This function is no
 * longer needed as new template files for the Settings component were
 * introduced.
 *
 * @deprecated BuddyPress 1.5.0
 * @since BuddyPress 1.6.0
 */
function bboss_core_screen_notification_settings_title() {
	_deprecated_function( __FUNCTION__, '1.5', 'Moved into theme template' );
}

/**
 * In BP 1.5, the Settings functions were moved out of the Core and Members
 * components, and moved into a new Settings component. This function is no
 * longer needed as new template files for the Settings component were
 * introduced.
 *
 * @deprecated BuddyPress 1.5.0
 * @since BuddyPress 1.6.0
 */
function bboss_core_screen_notification_settings_content() {
	_deprecated_function( __FUNCTION__, '1.5', 'Moved into theme template' );
}

/**
 * In BP 1.5, the Settings functions were moved out of the Core and Members
 * components, and moved into a new Settings component. This function is no
 * longer needed as new template files for the Settings component were
 * introduced.
 *
 * @deprecated BuddyPress 1.5.0
 * @since BuddyPress 1.6.0
 */
function bboss_core_screen_delete_account() {
	_deprecated_function( __FUNCTION__, '1.5', 'Moved into theme template' );
}

/**
 * In BP 1.5, the Settings functions were moved out of the Core and Members
 * components, and moved into a new Settings component. This function is no
 * longer needed as new template files for the Settings component were
 * introduced.
 *
 * @deprecated BuddyPress 1.5.0
 * @since BuddyPress 1.6.0
 */
function bboss_core_screen_delete_account_title() {
	_deprecated_function( __FUNCTION__, '1.5', 'Moved into theme template' );
}

/**
 * In BP 1.5, the Settings functions were moved out of the Core and Members
 * components, and moved into a new Settings component. This function is no
 * longer needed as new template files for the Settings component were
 * introduced.
 *
 * @deprecated BuddyPress 1.5.0
 * @since BuddyPress 1.6.0
 */
function bboss_core_screen_delete_account_content() {
	_deprecated_function( __FUNCTION__, '1.5', 'Moved into theme template' );
}

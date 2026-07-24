<?php
/**
 * Deprecated functions
 *
 * @package BuddyBoss\Core
 * @deprecated BuddyPress 2.1.0
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * Register (not enqueue) scripts that used to be used by BuddyPress.
 *
 * @since BuddyPress 2.1.0
 */
function bp_core_register_deprecated_scripts() {
	// Scripts undeprecated as of 2.5.0.
}

/**
 * Register (not enqueue) styles that used to be used by BuddyPress.
 *
 * @since BuddyPress 2.1.0
 */
function bp_core_register_deprecated_styles() {
	// Scripts undeprecated as of 2.5.0.
}

/** BuddyBar *****************************************************************/

/**
 * Add a Sites menu to the BuddyBar.
 *
 * @since BuddyPress 1.0.0
 * @deprecated BuddyPress 2.1.0
 *
 * @return false|null Returns false on failure. Otherwise echoes the menu item.
 */
function bp_adminbar_blogs_menu() {

	if ( ! is_user_logged_in() || ! bp_is_active( 'blogs' ) ) {
		return false;
	}

	if ( ! is_multisite() ) {
		return false;
	}

	$blogs = wp_cache_get( 'bp_blogs_of_user_' . bp_loggedin_user_id() . '_inc_hidden', 'bp' );
	if ( empty( $blogs ) ) {
		$blogs = bp_blogs_get_blogs_for_user( bp_loggedin_user_id(), true );
		wp_cache_set( 'bp_blogs_of_user_' . bp_loggedin_user_id() . '_inc_hidden', $blogs, 'bp' );
	}

	$counter = 0;
	if ( is_array( $blogs['blogs'] ) && (int) $blogs['count'] ) {

		echo '<li id="bp-adminbar-blogs-menu"><a href="' . esc_url( trailingslashit( bp_loggedin_user_domain() . bp_get_blogs_slug() ) ) . '">';

		esc_html_e( 'My Sites', 'buddyboss-platform' );

		echo '</a>';
		echo '<ul>';

		foreach ( (array) $blogs['blogs'] as $blog ) {
			$alt      = ( 0 == $counter % 2 ) ? ' class="alt"' : '';
			$site_url = esc_attr( $blog->siteurl );

			echo '<li' . $alt . '>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- $alt is a static class attribute literal.
			echo '<a href="' . $site_url . '">' . esc_html( $blog->name ) . '</a>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- $site_url escaped at assignment.
			echo '<ul>';
			echo '<li class="alt"><a href="' . $site_url . 'wp-admin/">' . esc_html__( 'Dashboard', 'buddyboss-platform' ) . '</a></li>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- $site_url escaped at assignment.
			echo '<li><a href="' . $site_url . 'wp-admin/post-new.php">' . esc_html__( 'New Post', 'buddyboss-platform' ) . '</a></li>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- $site_url escaped at assignment.
			echo '<li class="alt"><a href="' . $site_url . 'wp-admin/edit.php">' . esc_html__( 'Manage Posts', 'buddyboss-platform' ) . '</a></li>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- $site_url escaped at assignment.
			echo '<li><a href="' . $site_url . 'wp-admin/edit-comments.php">' . esc_html__( 'Manage Comments', 'buddyboss-platform' ) . '</a></li>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- $site_url escaped at assignment.
			echo '</ul>';

			do_action( 'bp_adminbar_blog_items', $blog );

			echo '</li>';
			$counter++;
		}

		$alt = ( 0 == $counter % 2 ) ? ' class="alt"' : '';

		if ( bp_blog_signup_enabled() ) {
			echo '<li' . $alt . '>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- $alt is a static class attribute literal.
			echo '<a href="' . esc_url( trailingslashit( bp_get_blogs_directory_permalink() . 'create' ) ) . '">' . esc_html__( 'Create a Site!', 'buddyboss-platform' ) . '</a>';
			echo '</li>';
		}

		echo '</ul>';
		echo '</li>';
	}
}

/**
 * If user has upgraded to 1.6 and chose to retain their BuddyBar, offer then a switch to change over
 * to the WP Toolbar.
 *
 * @since BuddyPress 1.6.0
 * @deprecated BuddyPress 2.1.0
 */
function bp_admin_setting_callback_force_buddybar() {
	?>

	<input id="_bp_force_buddybar" name="_bp_force_buddybar" type="checkbox" value="1" <?php checked( ! bp_force_buddybar( true ) ); ?> />
	<label for="_bp_force_buddybar"><?php esc_html_e( 'Switch to WordPress Toolbar', 'buddyboss-platform' ); ?></label>

	<?php
}


/**
 * Sanitization for _bp_force_buddybar
 *
 * If upgraded to 1.6 and you chose to keep the BuddyBar, a checkbox asks if you want to switch to
 * the WP Toolbar. The option we store is 1 if the BuddyBar is forced on, so we use this function
 * to flip the boolean before saving the intval.
 *
 * @since BuddyPress 1.6.0
 * @deprecated BuddyPress 2.1.0
 * @access Private
 */
function bp_admin_sanitize_callback_force_buddybar( $value = false ) {
	return $value ? 0 : 1;
}

/**
 * Wrapper function for rendering the BuddyBar.
 *
 * @return false|null Returns false if the BuddyBar is disabled.
 * @deprecated BuddyPress 2.1.0
 */
function bp_core_admin_bar() {
	$bp = buddypress();

	if ( defined( 'BP_DISABLE_ADMIN_BAR' ) && BP_DISABLE_ADMIN_BAR ) {
		return false;
	}

	if ( (int) bp_get_option( 'hide-loggedout-adminbar' ) && ! is_user_logged_in() ) {
		return false;
	}

	$bp->doing_admin_bar = true;

	echo '<div id="wp-admin-bar"><div class="padder">';

	// **** Do bp-adminbar-logo Actions ********
	do_action( 'bp_adminbar_logo' );

	echo '<ul class="main-nav">';

	// **** Do bp-adminbar-menus Actions ********
	do_action( 'bp_adminbar_menus' );

	echo '</ul>';
	echo "</div></div><!-- #wp-admin-bar -->\n\n";

	$bp->doing_admin_bar = false;
}

/**
 * Output the BuddyBar logo.
 *
 * @deprecated BuddyPress 2.1.0
 */
function bp_adminbar_logo() {
	echo '<a href="' . esc_url( bp_get_root_domain() ) . '" id="admin-bar-logo">' . esc_html( get_blog_option( bp_get_root_blog_id(), 'blogname' ) ) . '</a>';
}

/**
 * Output the "Log In" and "Sign Up" names to the BuddyBar.
 *
 * Visible only to visitors who are not logged in.
 *
 * @deprecated BuddyPress 2.1.0
 *
 * @return false|null Returns false if the current user is logged in.
 */
function bp_adminbar_login_menu() {

	if ( is_user_logged_in() ) {
		return false;
	}

	echo '<li class="bp-login no-arrow"><a href="' . esc_url( wp_login_url() ) . '">' . esc_html__( 'Log In', 'buddyboss-platform' ) . '</a></li>';

	// Show "Sign Up" link if user registrations are allowed
	if ( bp_get_signup_allowed() ) {
		echo '<li class="bp-signup no-arrow"><a href="' . esc_url( bp_get_signup_page() ) . '">' . esc_html__( 'Sign Up', 'buddyboss-platform' ) . '</a></li>';
	}
}

/**
 * Output the My Account BuddyBar menu.
 *
 * @deprecated BuddyPress 2.1.0
 *
 * @return false|null Returns false on failure.
 */
function bp_adminbar_account_menu() {
	$bp = buddypress();

	if ( empty( $bp->bp_nav ) || ! is_user_logged_in() ) {
		return false;
	}

	echo '<li id="bp-adminbar-account-menu"><a href="' . esc_url( bp_loggedin_user_domain() ) . '">';
	esc_html_e( 'My Account', 'buddyboss-platform' );
	echo '</a>';
	echo '<ul>';

	// Loop through each navigation item
	$counter = 0;
	foreach ( (array) $bp->bp_nav as $nav_item ) {
		$alt = ( 0 == $counter % 2 ) ? ' class="alt"' : '';

		if ( -1 == $nav_item['position'] ) {
			continue;
		}

		echo '<li' . $alt . '>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- $alt is a static class attribute literal.
		echo '<a id="bp-admin-' . esc_attr( $nav_item['css_id'] ) . '" href="' . esc_url( $nav_item['link'] ) . '">' . esc_html( $nav_item['name'] ) . '</a>';

		if ( isset( $bp->bp_options_nav[ $nav_item['slug'] ] ) && is_array( $bp->bp_options_nav[ $nav_item['slug'] ] ) ) {
			echo '<ul>';
			$sub_counter = 0;

			foreach ( (array) $bp->bp_options_nav[ $nav_item['slug'] ] as $subnav_item ) {
				$link = $subnav_item['link'];
				$name = $subnav_item['name'];

				if ( bp_displayed_user_domain() ) {
					$link = str_replace( bp_displayed_user_domain(), bp_loggedin_user_domain(), $subnav_item['link'] );
				}

				if ( isset( $bp->displayed_user->userdata->user_login ) ) {
					$name = str_replace( $bp->displayed_user->userdata->user_login, $bp->loggedin_user->userdata->user_login, $subnav_item['name'] );
				}

				$alt = ( 0 == $sub_counter % 2 ) ? ' class="alt"' : '';
				echo '<li' . $alt . '><a id="bp-admin-' . esc_attr( $subnav_item['css_id'] ) . '" href="' . esc_url( $link ) . '">' . esc_html( $name ) . '</a></li>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- $alt is a static class attribute literal.
				$sub_counter++;
			}
			echo '</ul>';
		}

		echo '</li>';

		$counter++;
	}

	$alt = ( 0 == $counter % 2 ) ? ' class="alt"' : '';

	echo '<li' . $alt . '><a id="bp-admin-logout" class="logout" href="' . esc_url( wp_logout_url( home_url() ) ) . '">' . esc_html__( 'Log Out', 'buddyboss-platform' ) . '</a></li>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- $alt is a static class attribute literal.
	echo '</ul>';
	echo '</li>';
}

/**
 * bp_adminbar_thisblog_menu()
 *
 * @deprecated BuddyPress 2.1.0
 */
function bp_adminbar_thisblog_menu() {
	if ( current_user_can( 'edit_posts' ) ) {
		echo '<li id="bp-adminbar-thisblog-menu"><a href="' . esc_url( admin_url() ) . '">';
		esc_html_e( 'Dashboard', 'buddyboss-platform' );
		echo '</a>';
		echo '<ul>';

		echo '<li class="alt"><a href="' . esc_url( admin_url( 'post-new.php' ) ) . '">' . esc_html__( 'New Post', 'buddyboss-platform' ) . '</a></li>';
		echo '<li><a href="' . esc_url( admin_url( 'edit.php' ) ) . '">' . esc_html__( 'Manage Posts', 'buddyboss-platform' ) . '</a></li>';
		echo '<li class="alt"><a href="' . esc_url( admin_url( 'edit-comments.php' ) ) . '">' . esc_html__( 'Manage Comments', 'buddyboss-platform' ) . '</a></li>';

		do_action( 'bp_adminbar_thisblog_items' );

		echo '</ul>';
		echo '</li>';
	}
}

/**
 * Output the Random BuddyBar menu.
 *
 * Not visible for logged-in users.
 *
 * @deprecated BuddyPress 2.1.0
 */
function bp_adminbar_random_menu() {
	?>

	<li class="align-right" id="bp-adminbar-visitrandom-menu">
		<a href="#"><?php esc_html_e( 'Visit', 'buddyboss-platform' ); ?></a>
		<ul class="random-list">
			<li><a href="<?php bp_members_directory_permalink(); ?>?random-member" rel="nofollow"><?php esc_html_e( 'Random Member', 'buddyboss-platform' ); ?></a></li>

			<?php if ( bp_is_active( 'groups' ) ) : ?>

				<li class="alt"><a href="<?php bp_groups_directory_permalink(); ?>?random-group"  rel="nofollow"><?php esc_html_e( 'Random Group', 'buddyboss-platform' ); ?></a></li>

			<?php endif; ?>

			<?php if ( is_multisite() && bp_is_active( 'blogs' ) ) : ?>

				<li><a href="<?php bp_blogs_directory_permalink(); ?>?random-blog"  rel="nofollow"><?php esc_html_e( 'Random Site', 'buddyboss-platform' ); ?></a></li>

			<?php endif; ?>

			<?php do_action( 'bp_adminbar_random_menu' ); ?>

		</ul>
	</li>

	<?php
}

/**
 * Enqueue the BuddyBar CSS.
 *
 * @deprecated BuddyPress 2.1.0
 */
function bp_core_load_buddybar_css() {

	if ( bp_use_wp_admin_bar() || ( (int) bp_get_option( 'hide-loggedout-adminbar' ) && ! is_user_logged_in() ) || ( defined( 'BP_DISABLE_ADMIN_BAR' ) && BP_DISABLE_ADMIN_BAR ) ) {
		return;
	}

	$min = bp_core_get_minified_asset_suffix();

	if ( file_exists( get_stylesheet_directory() . '/_inc/css/adminbar.css' ) ) { // Backwards compatibility
		$stylesheet = get_stylesheet_directory_uri() . '/_inc/css/adminbar.css';
	} else {
		$stylesheet = buddypress()->plugin_url . "bp-core/css/buddybar{$min}.css";
	}

	wp_enqueue_style( 'bp-admin-bar', apply_filters( 'bp_core_buddybar_rtl_css', $stylesheet ), array(), bp_get_version() );

	wp_style_add_data( 'bp-admin-bar', 'rtl', true );
	if ( $min ) {
		wp_style_add_data( 'bp-admin-bar', 'suffix', $min );
	}
}
add_action( 'bp_init', 'bp_core_load_buddybar_css' );

/**
 * Add menu items to the BuddyBar.
 *
 * @since BuddyPress 1.0.0
 *
 * @deprecated BuddyPress 2.1.0
 */
function bp_groups_adminbar_admin_menu() {
	$bp = buddypress();

	if ( empty( $bp->groups->current_group ) ) {
		return false;
	}

	// Only group admins and site admins can see this menu
	if ( ! current_user_can( 'edit_users' ) && ! bp_current_user_can( 'bp_moderate' ) && ! bp_is_item_admin() ) {
		return false;
	}
	?>

	<li id="bp-adminbar-adminoptions-menu">
		<a href="<?php bp_groups_action_link( 'admin' ); ?>"><?php esc_html_e( 'Admin Options', 'buddyboss-platform' ); ?></a>

		<ul>
			<li><a href="<?php bp_groups_action_link( 'admin/edit-details' ); ?>"><?php esc_html_e( 'Edit Details', 'buddyboss-platform' ); ?></a></li>

			<li><a href="<?php bp_groups_action_link( 'admin/group-settings' ); ?>"><?php esc_html_e( 'Group Settings', 'buddyboss-platform' ); ?></a></li>

			<?php if ( ! (int) bp_get_option( 'bp-disable-avatar-uploads' ) && $bp->avatar->show_avatars ) : ?>

				<li><a href="<?php bp_groups_action_link( 'admin/group-avatar' ); ?>"><?php esc_html_e( 'Group Profile Photo', 'buddyboss-platform' ); ?></a></li>

			<?php endif; ?>

			<?php if ( bp_is_active( 'friends' ) ) : ?>

				<li><a href="<?php bp_groups_action_link( 'send-invites' ); ?>"><?php esc_html_e( 'Manage Invitations', 'buddyboss-platform' ); ?></a></li>

			<?php endif; ?>

			<li><a href="<?php bp_groups_action_link( 'admin/manage-members' ); ?>"><?php esc_html_e( 'Manage Members', 'buddyboss-platform' ); ?></a></li>

			<?php if ( $bp->groups->current_group->status == 'private' ) : ?>

				<li><a href="<?php bp_groups_action_link( 'admin/membership-requests' ); ?>"><?php esc_html_e( 'Membership Requests', 'buddyboss-platform' ); ?></a></li>

			<?php endif; ?>

			<li><a class="confirm" href="<?php echo esc_url( wp_nonce_url( bp_get_group_permalink( $bp->groups->current_group ) . 'admin/delete-group/', 'groups_delete_group' ) ); ?>&amp;delete-group-button=1&amp;delete-group-understand=1"><?php esc_html_e( 'Delete Group', 'buddyboss-platform' ); ?></a></li>

			<?php do_action( 'bp_groups_adminbar_admin_menu' ); ?>

		</ul>
	</li>

	<?php
}
add_action( 'bp_adminbar_menus', 'bp_groups_adminbar_admin_menu', 20 );

/**
 * Add the Notifications menu to the BuddyBar.
 *
 * @deprecated BuddyPress 2.1.0
 */
function bp_adminbar_notifications_menu() {

	// Bail if notifications is not active
	if ( ! bp_is_active( 'notifications' ) ) {
		return false;
	}

	bp_notifications_buddybar_menu();
}
add_action( 'bp_adminbar_menus', 'bp_adminbar_notifications_menu', 8 );

/**
 * Add the Blog Authors menu to the BuddyBar (visible when not logged in).
 *
 * @deprecated BuddyPress 2.1.0
 */
function bp_adminbar_authors_menu() {
	global $wpdb;

	// Only for multisite
	if ( ! is_multisite() ) {
		return false;
	}

	// Hide on root blog
	if ( bp_is_root_blog( $wpdb->blogid ) || ! bp_is_active( 'blogs' ) ) {
		return false;
	}

	$blog_prefix = $wpdb->get_blog_prefix( $wpdb->blogid );
	$authors     = $wpdb->get_results( "SELECT user_id, user_login, user_nicename, display_name, user_email, meta_value as caps FROM $wpdb->users u, $wpdb->usermeta um WHERE u.ID = um.user_id AND meta_key = '{$blog_prefix}capabilities' ORDER BY um.user_id" );

	if ( ! empty( $authors ) ) {
		// This is a blog, render a menu with links to all authors
		echo '<li id="bp-adminbar-authors-menu"><a href="/">';
		esc_html_e( 'Blog Authors', 'buddyboss-platform' );
		echo '</a>';

		echo '<ul class="author-list">';
		foreach ( (array) $authors as $author ) {
			$caps = maybe_unserialize( $author->caps );
			if ( isset( $caps['subscriber'] ) || isset( $caps['contributor'] ) ) {
				continue;
			}

			echo '<li>';
			echo '<a href="' . esc_url( bp_core_get_user_domain( $author->user_id, $author->user_nicename, $author->user_login ) ) . '">';
			echo wp_kses_post(
				bp_core_fetch_avatar(
					array(
						'item_id' => $author->user_id,
						'email'   => $author->user_email,
						'width'   => 15,
						'height'  => 15,
						/* translators: %s: Author display name. */
						'alt'     => sprintf( __( 'Profile photo of %s', 'buddyboss-platform' ), $author->display_name ),
					)
				)
			);
			echo ' ' . esc_html( $author->display_name ) . '</a>';
			echo '<div class="admin-bar-clear"></div>';
			echo '</li>';
		}
		echo '</ul>';
		echo '</li>';
	}
}
add_action( 'bp_adminbar_menus', 'bp_adminbar_authors_menu', 12 );

/**
 * Add a member admin menu to the BuddyBar.
 *
 * Adds an Toolbar menu to any profile page providing site moderator actions
 * that allow capable users to clean up a users account.
 *
 * @deprecated BuddyPress 2.1.0
 */
function bp_members_adminbar_admin_menu() {

	// Only show if viewing a user
	if ( ! bp_displayed_user_id() ) {
		return false;
	}

	// Don't show this menu to non site admins or if you're viewing your own profile
	if ( ! current_user_can( 'edit_users' ) || bp_is_my_profile() ) {
		return false;
	}
	?>

	<li id="bp-adminbar-adminoptions-menu">

		<a href=""><?php esc_html_e( 'Admin Options', 'buddyboss-platform' ); ?></a>

		<ul>
			<?php if ( bp_is_active( 'xprofile' ) ) : ?>

				<?php /* translators: %s: Displayed user full name. */ ?>
				<li><a href="<?php bp_members_component_link( 'profile', 'edit' ); ?>"><?php echo esc_html( sprintf( __( "Edit %s's Profile", 'buddyboss-platform' ), bp_get_displayed_user_fullname() ) ); ?></a></li>

			<?php endif ?>

			<?php /* translators: %s: Displayed user full name. */ ?>
			<li><a href="<?php bp_members_component_link( 'profile', 'change-avatar' ); ?>"><?php echo esc_html( sprintf( __( "Edit %s's Profile Photo", 'buddyboss-platform' ), bp_get_displayed_user_fullname() ) ); ?></a></li>

			<li><a href="<?php bp_members_component_link( 'settings', 'capabilities' ); ?>"><?php esc_html_e( 'User Capabilities', 'buddyboss-platform' ); ?></a></li>

			<?php /* translators: %s: Displayed user full name. */ ?>
			<li><a href="<?php bp_members_component_link( 'settings', 'delete-account' ); ?>"><?php echo esc_html( sprintf( __( "Delete %s's Account", 'buddyboss-platform' ), bp_get_displayed_user_fullname() ) ); ?></a></li>

			<?php do_action( 'bp_members_adminbar_admin_menu' ); ?>

		</ul>
	</li>

	<?php
}
add_action( 'bp_adminbar_menus', 'bp_members_adminbar_admin_menu', 20 );

/**
 * Create the Notifications menu for the BuddyBar.
 *
 * @since BuddyPress 1.9.0
 * @deprecated BuddyPress 2.1.0
 */
function bp_notifications_buddybar_menu() {

	if ( ! is_user_logged_in() ) {
		return false;
	}

	echo '<li id="bp-adminbar-notifications-menu"><a href="' . esc_url( bp_loggedin_user_domain() ) . '">';
	esc_html_e( 'Notifications', 'buddyboss-platform' );

	$notification_count = bp_notifications_get_unread_notification_count( bp_loggedin_user_id() );
	$notifications      = bp_notifications_get_notifications_for_user( bp_loggedin_user_id() );

	if ( ! empty( $notification_count ) ) :
		?>
		<span><?php echo esc_html( bp_core_number_format( $notification_count ) ); ?></span>
		<?php
	endif;

	echo '</a>';
	echo '<ul>';

	if ( ! empty( $notifications ) ) {
		$counter = 0;
		for ( $i = 0, $count = count( $notifications ); $i < $count; ++$i ) {
			$alt = ( 0 == $counter % 2 ) ? ' class="alt"' : '';
			?>

			<li<?php echo $alt; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- $alt is a static class attribute literal. ?>><?php echo wp_kses_post( $notifications[ $i ] ); ?></li>

			<?php
			$counter++;
		}
	} else {
		?>

		<li><a href="<?php echo esc_url( bp_loggedin_user_domain() ); ?>"><?php esc_html_e( 'No new notifications.', 'buddyboss-platform' ); ?></a></li>

		<?php
	}

	echo '</ul>';
	echo '</li>';
}
add_action( 'bp_adminbar_menus', 'bp_adminbar_notifications_menu', 8 );

/**
 * Output the base URL for subdomain installations of WordPress Multisite.
 *
 * @since BuddyPress 1.6.0
 *
 * @deprecated BuddyPress 2.1.0
 */
function bp_blogs_subdomain_base() {
	_deprecated_function( __FUNCTION__, '2.1', 'bp_signup_subdomain_base()' );
	echo esc_html( bp_signup_get_subdomain_base() );
}

/**
 * Return the base URL for subdomain installations of WordPress Multisite.
 *
 * @since BuddyPress 1.6.0
 *
 * @return string The base URL - eg, 'example.com' for site_url() example.com or www.example.com.
 *
 * @deprecated BuddyPress 2.1.0
 */
function bp_blogs_get_subdomain_base() {
	_deprecated_function( __FUNCTION__, '2.1', 'bp_signup_get_subdomain_base()' );
	return bp_signup_get_subdomain_base();
}

/**
 * Allegedly output an avatar upload form, but it hasn't done that since 2009.
 *
 * @since BuddyPress 1.0.0
 * @deprecated BuddyPress 2.1.0
 */
function bp_avatar_upload_form() {
	_deprecated_function( __FUNCTION__, '2.1', 'No longer used' );
}


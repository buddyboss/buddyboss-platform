<?php
/**
 * BuddyBoss Admin Settings.
 *
 * @package BuddyBoss\Core\Administration
 * @since BuddyPress 2.3.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Admin bar for logged out users setting field.
 *
 * @since BuddyPress 1.6.0
 */
function bp_admin_setting_callback_admin_bar() {
	?>

	<input id="hide-loggedout-adminbar" name="hide-loggedout-adminbar" type="checkbox" value="1" <?php checked( ! bp_hide_loggedout_adminbar( false ) ); ?> />
	<label for="hide-loggedout-adminbar"><?php _e( 'Show the Toolbar for logged out users', 'buddyboss' ); ?></label>

	<?php
}

/**
 * Allow members to delete their accounts setting field.
 *
 * @since BuddyPress 1.6.0
 */
function bp_admin_setting_callback_account_deletion() {
	?>

	<input id="bp-disable-account-deletion" name="bp-disable-account-deletion" type="checkbox" value="1" <?php checked( ! bp_disable_account_deletion( false ) ); ?> />
	<label for="bp-disable-account-deletion"><?php _e( 'Allow members to delete their profiles', 'buddyboss' ); ?></label>

	<?php
}


/**
 * Admin bar for logged in users setting field.
 *
 * @since BuddyBoss 1.1.0
 */
function bp_admin_setting_callback_login_admin_bar() {
	?>

	<input id="show-login-adminbar" name="show-login-adminbar" type="checkbox" value="1" <?php checked( bp_show_login_adminbar( true ) ); ?> />
	<label for="show-login-adminbar"><?php _e( 'Show the Toolbar for logged-in members (non-admins)', 'buddyboss' ); ?></label>

	<?php
}

/**
 * Admin bar for admin users setting field.
 *
 * @since BuddyBoss 1.1.0
 */
function bp_admin_setting_callback_admin_admin_bar() {
	?>

	<input id="show-admin-adminbar" name="show-admin-adminbar" type="checkbox" value="1" <?php checked( bp_show_admin_adminbar( true ) ); ?> />
	<label for="show-admin-adminbar"><?php _e( 'Show the Toolbar for logged-in admins', 'buddyboss' ); ?></label>

	<?php
}

/**
 * Link to Admin Settings tutorial
 *
 * @since BuddyBoss 1.0.0
 */
function bp_admin_setting_tutorial() {
	?>

	<p>
		<a class="button" href="<?php echo bp_get_admin_url(
			add_query_arg(
				array(
					'page'    => 'bp-help',
					'article' => 62792,
				),
				'admin.php'
			)
		); ?>"><?php _e( 'View Tutorial', 'buddyboss' ); ?></a>
	</p>

	<?php
}

/**
 * Allow admin to make the site private network.
 *
 * @since BuddyBoss 1.0.0
 */
function bp_admin_setting_callback_private_network() {
	?>

	<input id="bp-enable-private-network" name="bp-enable-private-network" type="checkbox" value="1" <?php checked( ! bp_enable_private_network( false ) ); ?> />
	<label for="bp-enable-private-network"><?php _e( 'Restrict site access to only logged-in members', 'buddyboss' ); ?></label>
	<?php
	printf(
		'<p class="description">%s</p>',
		sprintf(
			__( 'Login and <a href="%s">Registration</a> content will remain publicly visible.', 'buddyboss' ),
			add_query_arg(
				array(
					'page' => 'bp-pages',
				),
				admin_url( 'admin.php' )
			)
		)
	);
}

/**
 * Allow admin to make the site private network.
 *
 * @since BuddyBoss 1.0.0
 */
function bp_admin_setting_callback_private_network_public_content() {
	?>

	<label for="bp-enable-private-network-public-content"><?php _e( 'Enter URLs or URI fragments (e.g. /groups/) to remain publicly visible always. Enter one URL or URI per line. ', 'buddyboss' ); ?></label>
	<textarea rows="10" cols="100" id="bp-enable-private-network-public-content" name="bp-enable-private-network-public-content" style="margin-top: 10px;"><?php echo esc_textarea( bp_enable_private_network_public_content() ); ?></textarea>
	<?php
}

/**
 * Link to Privacy tutorial
 *
 * @since BuddyBoss 1.0.0
 */
function bp_privacy_tutorial() {
	?>

	<p>
		<a class="button" href="<?php echo bp_get_admin_url(
			add_query_arg(
				array(
					'page'    => 'bp-help',
					'article' => 62793,
				),
				'admin.php'
			)
		); ?>"><?php _e( 'View Tutorial', 'buddyboss' ); ?></a>
	</p>

	<?php
}

/** Activity *******************************************************************/

/**
 * Allow Akismet setting field.
 *
 * @since BuddyPress 1.6.0
 */
function bp_admin_setting_callback_activity_akismet() {
	?>

	<input id="_bp_enable_akismet" name="_bp_enable_akismet" type="checkbox" value="1" <?php checked( bp_is_akismet_active( true ) ); ?> />
	<label for="_bp_enable_akismet"><?php _e( 'Enable Akismet spam protection for activity feed', 'buddyboss' ); ?></label>

	<?php
}

/**
 * Allow activity comments on posts and comments.
 *
 * @since BuddyPress 1.6.0
 */
function bp_admin_setting_callback_blogforum_comments() {
	?>

	<input id="bp-disable-blogforum-comments" name="bp-disable-blogforum-comments" type="checkbox" value="1" <?php checked( ! bp_disable_blogforum_comments( false ) ); ?> />
	<label for="bp-disable-blogforum-comments"><?php _e( 'Allow activity stream commenting on blog posts, forum discussions and replies', 'buddyboss' ); ?></label>

	<?php
}

/**
 * Link to Posts in Activity Feed tutorial
 *
 * @since BuddyBoss 1.0.0
 */
function bp_posts_in_activity_tutorial() {
	?>

	<p>
		<a class="button" href="<?php echo bp_get_admin_url(
			add_query_arg(
				array(
					'page'    => 'bp-help',
					'article' => 62823,
				),
				'admin.php'
			)
		); ?>"><?php _e( 'View Tutorial', 'buddyboss' ); ?></a>
	</p>

	<?php
}

/**
 * Allow Heartbeat to refresh activity stream.
 *
 * @since BuddyPress 2.0.0
 */
function bp_admin_setting_callback_heartbeat() {
	// NOTE: this request is made to check for Heartbeat API on front end if it enabled or not
	wp_remote_get( bp_core_get_user_domain( bp_loggedin_user_id() ) );
	$heartbeat_disabled = get_option( 'bp_wp_heartbeat_disabled' );
	?>

	<input id="_bp_enable_heartbeat_refresh" name="_bp_enable_heartbeat_refresh" type="checkbox" value="1"
	<?php
	if ( '1' != $heartbeat_disabled ) {
		checked( bp_is_activity_heartbeat_active( true ) );
	} else {
		echo 'disabled="disabled"'; }
	?>
	 />
	<label for="_bp_enable_heartbeat_refresh"><?php _e( 'Automatically check for new activity posts', 'buddyboss' ); ?></label>
	<?php if ( '1' == $heartbeat_disabled ) { ?>
		<p class="description"><?php _e( 'This feature requires the WordPress <a href="https://developer.wordpress.org/plugins/javascript/heartbeat-api/" target="_blank">Heartbeat API</a> to function, which is disabled on your server.', 'buddyboss' ); ?></p>
	<?php } ?>
	<?php
}

/**
 * Automatically load more activity posts when scrolling to the bottom of the page.
 *
 * @since BuddyPress 2.0.0
 */
function bp_admin_setting_callback_enable_activity_autoload() {
	?>

	<input id="_bp_enable_activity_autoload" name="_bp_enable_activity_autoload" type="checkbox" value="1" <?php checked( bp_is_activity_autoload_active( false ) ); ?> />
	<label for="_bp_enable_activity_autoload"><?php _e( 'Automatically load more activity posts when scrolling to the bottom of the page ', 'buddyboss' ); ?></label>

	<?php
}

/**
 * Enable activity scopes like groups, friends, mentions, following etc.
 *
 * @since BuddyBoss 1.1.6
 */
function bp_admin_setting_callback_enable_activity_tabs() {
	?>

	<input id="_bp_enable_activity_tabs" name="_bp_enable_activity_tabs" type="checkbox" value="1" <?php checked( bp_is_activity_tabs_active( false ) ); ?> />
	<label for="_bp_enable_activity_tabs"><?php _e( 'Display activity in separate tabs based on activity type', 'buddyboss' ); ?></label>

	<?php
}

/**
 * Allow following activity stream.
 *
 * @since BuddyBoss 1.0.0
 */
function bp_admin_setting_callback_enable_activity_follow() {
	?>

	<input id="_bp_enable_activity_follow" name="_bp_enable_activity_follow" type="checkbox" value="1" <?php checked( bp_is_activity_follow_active( false ) ); ?> />
	<label for="_bp_enable_activity_follow"><?php _e( 'Allow your users to follow the activity of each other on their timeline', 'buddyboss' ); ?></label>

	<?php
}

/**
 * Allow like activity stream.
 *
 * @since BuddyBoss 1.0.0
 */
function bp_admin_setting_callback_enable_activity_like() {
	?>

	<input id="_bp_enable_activity_like" name="_bp_enable_activity_like" type="checkbox" value="1" <?php checked( bp_is_activity_like_active( true ) ); ?> />
	<label for="_bp_enable_activity_like"><?php _e( 'Allow your users to "Like" each other\'s activity posts', 'buddyboss' ); ?></label>

	<?php
}


/**
 * Allow link previews in activity posts.
 *
 * @since BuddyBoss 1.0.0
 */
function bp_admin_setting_callback_enable_activity_link_preview() {
	?>

	<input id="_bp_enable_activity_link_preview" name="_bp_enable_activity_link_preview" type="checkbox" value="1" <?php checked( bp_is_activity_link_preview_active( false ) ); ?> />
	<label for="_bp_enable_activity_link_preview"><?php _e( 'When links are used in activity posts, display an image and excerpt from the site', 'buddyboss' ); ?></label>

	<?php
}

/**
 * Link to Activity Settings tutorial
 *
 * @since BuddyBoss 1.0.0
 */
function bp_activity_settings_tutorial() {
	?>

	<p>
		<a class="button" href="<?php echo bp_get_admin_url(
			add_query_arg(
				array(
					'page'    => 'bp-help',
					'article' => 62822,
				),
				'admin.php'
			)
		); ?>"><?php _e( 'View Tutorial', 'buddyboss' ); ?></a>
	</p>

	<?php
}


/**
 * Sanitization for bp-disable-blogforum-comments setting.
 *
 * In the UI, a checkbox asks whether you'd like to *enable* post/comment activity comments. For
 * legacy reasons, the option that we store is 1 if these comments are *disabled*. So we use this
 * function to flip the boolean before saving the intval.
 *
 * @since BuddyPress 1.6.0
 *
 * @param bool $value Whether or not to sanitize.
 * @return bool
 */
function bp_admin_sanitize_callback_blogforum_comments( $value = false ) {
	return $value ? 0 : 1;
}

/**
 * Allow members to upload avatars field.
 *
 * @since BuddyPress 1.6.0
 */
function bp_admin_setting_callback_avatar_uploads() {
	?>

	<input id="bp-disable-avatar-uploads" name="bp-disable-avatar-uploads" type="checkbox" value="1" <?php checked( ! bp_disable_avatar_uploads( false ) ); ?> />
	<label for="bp-disable-avatar-uploads"><?php _e( 'Allow members to upload photos for profile avatars', 'buddyboss' ); ?></label>

	<?php
}

/**
 * Allow members to upload cover photos field.
 *
 * @since BuddyPress 2.4.0
 */
function bp_admin_setting_callback_cover_image_uploads() {
	?>
	<input id="bp-disable-cover-image-uploads" name="bp-disable-cover-image-uploads" type="checkbox" value="1" <?php checked( ! bp_disable_cover_image_uploads() ); ?> />
	<label for="bp-disable-cover-image-uploads"><?php _e( 'Allow members to upload cover images', 'buddyboss' ); ?></label>
	<?php
}

/**
 * Link to Profile Photos tutorial
 *
 * @since BuddyBoss 1.1.1
 */
function bp_profile_photos_tutorial() {
	?>

	<p>
		<a class="button" href="<?php echo bp_get_admin_url(
			add_query_arg(
				array(
					'page'    => 'bp-help',
					'article' => 72341,
				),
				'admin.php'
			)
		); ?>"><?php _e( 'View Tutorial', 'buddyboss' ); ?></a>
	</p>

	<?php
}

/** Group Settings ************************************************************/

/**
 * Allow all users to create groups field.
 *
 * @since BuddyPress 1.6.0
 */
function bp_admin_setting_callback_group_creation() {
	?>

	<input id="bp_restrict_group_creation" name="bp_restrict_group_creation" type="checkbox" aria-describedby="bp_group_creation_description" value="1" <?php checked( ! bp_restrict_group_creation( false ) ); ?> />
	<label for="bp_restrict_group_creation"><?php _e( 'Enable social group creation by all users', 'buddyboss' ); ?></label>
	<p class="description" id="bp_group_creation_description"><?php _e( 'Administrators can always create groups, regardless of this setting.', 'buddyboss' ); ?></p>

	<?php
}

/**
 * 'Enable group avatars' field markup.
 *
 * @since BuddyPress 2.3.0
 */
function bp_admin_setting_callback_group_avatar_uploads() {
	?>
	<input id="bp-disable-group-avatar-uploads" name="bp-disable-group-avatar-uploads" type="checkbox" value="1" <?php checked( ! bp_disable_group_avatar_uploads() ); ?> />
	<label for="bp-disable-group-avatar-uploads"><?php _e( 'Allow group organizers to upload an avatar', 'buddyboss' ); ?></label>
	<?php
}

/**
 * 'Enable group cover photos' field markup.
 *
 * @since BuddyPress 2.4.0
 */
function bp_admin_setting_callback_group_cover_image_uploads() {
	?>
	<input id="bp-disable-group-cover-image-uploads" name="bp-disable-group-cover-image-uploads" type="checkbox" value="1" <?php checked( ! bp_disable_group_cover_image_uploads() ); ?> />
	<label for="bp-disable-group-cover-image-uploads"><?php _e( 'Allow group organizers to upload cover photos', 'buddyboss' ); ?></label>
	<?php
}

/**
 * Link to Group Settings tutorial
 *
 * @since BuddyBoss 1.0.0
 */
function bp_group_setting_tutorial() {
	?>

	<p>
		<a class="button" href="<?php echo bp_get_admin_url(
			add_query_arg(
				array(
					'page'    => 'bp-help',
					'article' => 62811,
				),
				'admin.php'
			)
		); ?>"><?php _e( 'View Tutorial', 'buddyboss' ); ?></a>
	</p>

	<?php
}

/** Group Types ************************************************************/

/**
 * 'Enable group types' field markup.
 *
 * @since BuddyBoss 1.0.0
 */
function bp_admin_setting_callback_group_type_creation() {
	?>
	<input id="bp-disable-group-type-creation" name="bp-disable-group-type-creation" type="checkbox" value="1" <?php checked( bp_disable_group_type_creation() ); ?> />
	<?php
	if ( true === bp_disable_group_type_creation() ) {
		printf(
			'<label for="bp-disable-group-type-creation">%s</label>',
			sprintf(
				__( 'Enable <a href="%s">group types</a> to better organize groups', 'buddyboss' ),
				add_query_arg(
					array(
						'post_type' => bp_groups_get_group_type_post_type(),
					),
					admin_url( 'edit.php' )
				)
			)
		);
	} else {
		?>
		<label for="bp-disable-group-type-creation"><?php _e( 'Enable group types to better organize groups', 'buddyboss' ); ?></label>
		<?php
	}

}

/**
 * Enable auto group membership approval field markup.
 *
 * @since BuddyBoss 1.0.0
 */
function bp_admin_setting_callback_group_auto_join() {
	?>
	<input id="bp-enable-group-auto-join" name="bp-enable-group-auto-join" type="checkbox" value="1" <?php checked( bp_enable_group_auto_join() ); ?> />
	<?php
	if ( true === bp_enable_group_auto_join() ) {
		printf(
			'<label for="bp-enable-group-auto-join">%s</label>',
			sprintf(
				__( 'Allow selected <a href="%s">profile types</a> to automatically join groups', 'buddyboss' ),
				add_query_arg(
					array(
						'post_type' => bp_get_member_type_post_type(),
					),
					admin_url( 'edit.php' )
				)
			)
		);
	} else {
		?>
		<label for="bp-enable-group-auto-join"><?php _e( 'Allow selected profile types to automatically join groups', 'buddyboss' ); ?></label>
		<?php
	}
	?>
	<p class="description"><?php _e( 'When a member requests to join a group their membership is automatically accepted', 'buddyboss' ); ?></p>
	<?php
}

/**
 * Link to Group Types tutorial
 *
 * @since BuddyBoss 1.0.0
 */
function bp_group_types_tutorial() {
	?>

	<p>
		<a class="button" href="<?php echo bp_get_admin_url(
			add_query_arg(
				array(
					'page'    => 'bp-help',
					'article' => 62816,
				),
				'admin.php'
			)
		); ?>"><?php _e( 'View Tutorial', 'buddyboss' ); ?></a>
	</p>

	<?php
}

/** Group Hierarchies ************************************************************/

/**
 * 'Enable group hierarchies' field markup.
 *
 * @since BuddyBoss 1.0.0
 */
function bp_admin_setting_callback_group_hierarchies() {
	?>
	<input id="bp-enable-group-hierarchies" name="bp-enable-group-hierarchies" type="checkbox" value="1" <?php checked( bp_enable_group_hierarchies() ); ?> />
	<label for="bp-enable-group-hierarchies"><?php _e( 'Allow groups to have subgroups', 'buddyboss' ); ?></label>
	<?php
}

/**
 * Enable group restrict invites field markup.
 *
 * @since BuddyBoss 1.0.0
 */
function bp_admin_setting_callback_group_restrict_invites() {
	?>
	<input id="bp-enable-group-restrict-invites" name="bp-enable-group-restrict-invites" type="checkbox" value="1" <?php checked( bp_enable_group_restrict_invites() ); ?> />
	<label for="bp-enable-group-restrict-invites"><?php _e( 'Restrict subgroup invites to members of the parent group', 'buddyboss' ); ?></label>
	<p class="description"><?php _e( 'Members must first be a member of the parent group prior to being invited to a subgroup', 'buddyboss' ); ?></p>
	<?php
}

/**
 * Link to Group Hierarchies tutorial
 *
 * @since BuddyBoss 1.0.0
 */
function bp_group_hierarchies_tutorial() {
	?>

	<p>
		<a class="button" href="<?php echo bp_get_admin_url(
			add_query_arg(
				array(
					'page'    => 'bp-help',
					'article' => 62817,
				),
				'admin.php'
			)
		); ?>"><?php _e( 'View Tutorial', 'buddyboss' ); ?></a>
	</p>

	<?php
}

/** Settings Page *************************************************************/

/**
 * The main settings page
 *
 * @since BuddyBoss 1.0.0
 */
function bp_core_admin_settings() {
	$active_tab  = bp_core_get_admin_active_tab();
	$form_action = bp_core_admin_setting_url( $active_tab );
	?>

	<div class="wrap">
		<h2 class="nav-tab-wrapper"><?php bp_core_admin_tabs( __( 'Settings', 'buddyboss' ) ); ?></h2>
		<div class="nav-settings-subsubsub">
			<ul class="subsubsub">
				<?php bp_core_settings_admin_tabs(); ?>
			</ul>
		</div>
		<form action="<?php echo esc_url( $form_action ); ?>" method="post">
			<?php bp_core_get_admin_active_tab_object()->form_html(); ?>
		</form>
	</div>

	<?php
}

/**
 * The main Integrations page
 *
 * @since BuddyBoss 1.0.0
 */
function bp_core_admin_integrations() {
	$active_tab  = bp_core_get_admin_integration_active_tab();
	$form_action = bp_core_admin_integrations_url( $active_tab );
	?>

	<div class="wrap">
		<h2 class="nav-tab-wrapper"><?php bp_core_admin_tabs( __( 'Integrations', 'buddyboss' ) ); ?></h2>
		<div class="nav-settings-subsubsub">
			<ul class="subsubsub">
				<?php bp_core_admin_integration_tabs(); ?>
			</ul>
		</div>
		<form action="<?php echo esc_url( $form_action ); ?>" method="post">
			<?php bp_core_get_admin_integration_active_tab_object()->form_html(); ?>
		</form>
	</div>

	<?php
}

/**
 * Load the AppBoss integration admin screen.
 *
 * @since BuddyBoss 1.0.0
 */
function bp_core_admin_appboss() {
	?>
		 <div class="wrap">
			<h2 class="nav-tab-wrapper"><?php bp_core_admin_tabs( __( 'AppBoss', 'buddyboss' ) ); ?></h2>
			<?php require buddypress()->plugin_dir . 'bp-core/admin/templates/about-appboss.php'; ?>
		</div>
		<?php
}

/**
 * Output settings API option.
 *
 * @since BuddyPress 1.6.0
 *
 * @param string $option  Form option to echo.
 * @param string $default Form option default.
 * @param bool   $slug    Form option slug.
 */
function bp_form_option( $option, $default = '', $slug = false ) {
	echo bp_get_form_option( $option, $default, $slug );
}

	/**
	 * Return settings API option
	 *
	 * @since BuddyPress 1.6.0
	 *
	 * @param string $option  Form option to return.
	 * @param string $default Form option default.
	 * @param bool   $slug    Form option slug.
	 * @return string
	 */
function bp_get_form_option( $option, $default = '', $slug = false ) {

	// Get the option and sanitize it.
	$value = bp_get_option( $option, $default );

	// Slug?
	if ( true === $slug ) {

		/**
		 * Filters the slug value in the form field.
		 *
		 * @since BuddyPress 1.6.0
		 *
		 * @param string $value Value being returned for the requested option.
		 */
		$value = esc_attr( apply_filters( 'editable_slug', $value ) );
	} else { // Not a slug.
		$value = esc_attr( $value );
	}

	// Fallback to default.
	if ( empty( $value ) ) {
		$value = $default;
	}

	/**
	 * Filters the settings API option.
	 *
	 * @since BuddyPress 1.6.0
	 *
	 * @param string $value  Value being returned for the requested option.
	 * @param string $option Option whose value is being requested.
	 */
	return apply_filters( 'bp_get_form_option', $value, $option );
}

/** Invites Section ************************************************************/

/**
 * Enable email subject field markup.
 *
 * @since BuddyBoss 1.0.0
 */
function bp_admin_setting_callback_member_invite_email_subject() {
	?>
	<input id="bp-disable-invite-member-email-subject" name="bp-disable-invite-member-email-subject" type="checkbox" value="1" <?php checked( bp_disable_invite_member_email_subject() ); ?> />
	<label for="bp-disable-invite-member-email-subject"><?php _e( 'Allow members to customize the email subject', 'buddyboss' ); ?></label>
	<?php
}

/**
 * Enable email content field markup.
 *
 * @since BuddyBoss 1.0.0
 */
function bp_admin_setting_callback_member_invite_email_content() {
	?>
	<input id="bp-disable-invite-member-email-content" name="bp-disable-invite-member-email-content" type="checkbox" value="1" <?php checked( bp_disable_invite_member_email_content() ); ?> />
	<label for="bp-disable-invite-member-email-content"><?php _e( 'Allow members to customize the email body content', 'buddyboss' ); ?></label>
	<?php
}

/**
 * Enable member invite field markup.
 *
 * @since BuddyBoss 1.0.0
 */
function bp_admin_setting_callback_member_invite_member_type() {
	?>
	<input id="bp-disable-invite-member-type" name="bp-disable-invite-member-type" type="checkbox" value="1" <?php checked( bp_disable_invite_member_type() ); ?> />
	<label for="bp-disable-invite-member-type"><?php _e( 'Allow members to select profile type of invitee', 'buddyboss' ); ?></label>
	<?php
		printf(
			'<p class="description">%s</p>',
			sprintf(
				__( 'Customize this setting while editing any of your <a href="%s">Profile Types</a>.', 'buddyboss' ),
				admin_url( 'edit.php?post_type=bp-member-type' )
			)
		);
}

/**
 * Allow Post Type feed setting field
 *
 * @since BuddyBoss 1.0.0
 *
 * @param $args array
 *
 * @uses checked() To display the checked attribute
 */
function bp_feed_settings_callback_post_type( $args ) {

	$post_type   = $args['post_type'];
	$option_name = 'bp-feed-custom-post-type-' . $post_type;

	$post_type_obj = get_post_type_object( $post_type );

	// Description for the last option of CPT
	if ( true === $args['description'] && 'post' !== $post_type ) {
		?>
		<p class="description" style="margin-bottom: 10px;"><?php _e( 'Select which Custom Post Types (coming from your plugins) should be shown in the activity feed. For example, if using WooCommerce it could post into the activity feed every time someone creates a new product.', 'buddyboss' ); ?></p>
		<?php
	}
	?>
	<input
		name="<?php echo $option_name; ?>"
		id="<?php echo $option_name; ?>"
		type="checkbox"
		value="1"
		<?php checked( bp_is_post_type_feed_enable( $post_type, false ) ); ?>
	/>
	<label for="<?php echo $option_name; ?>">
		<?php echo $post_type === 'post' ? esc_html__( 'Blog Posts', 'buddyboss' ) : $post_type_obj->labels->name; ?>
	</label>
	<?php

	// Description for the WordPress Blog Posts
	if ( 'post' === $post_type ) {
		?>
		<p class="description"><?php _e( 'When users publish new blog posts, show them in the activity feed.', 'buddyboss' ); ?></p>
		<?php
	}

}

/**
 * Allow Platform default activity feed setting field
 *
 * @since BuddyBoss 1.0.0
 *
 * @param $args array
 *
 * @uses checked() To display the checked attribute
 */
function bp_feed_settings_callback_platform( $args ) {

		$option_name = $args['activity_name'];
	?>
		<input name="<?php echo esc_attr( 'bp-feed-platform-' . $option_name ); ?>" id="<?php echo esc_attr( $option_name ); ?>" type="checkbox" value="1" <?php checked( bp_platform_is_feed_enable( 'bp-feed-platform-' . $option_name, true ) ); ?>/>
		<label for="<?php echo esc_attr( $option_name ); ?>"><?php echo esc_html( $args['activity_label'], 'buddyboss' ); ?></label>
		<?php

}

/**
 * Admin bar for logged out users setting field.
 *
 * @since BuddyPress 1.6.0
 */
function bp_admin_setting_callback_register() {
	?>

	<input id="bp-enable-site-registration" name="bp-enable-site-registration" type="checkbox" value="1" <?php checked( bp_enable_site_registration() ); ?> />
	<label for="bp-enable-site-registration"><?php _e( 'Allow non-members to register new accounts', 'buddyboss' ); ?></label>
	<?php
	if ( false === bp_enable_site_registration() && bp_is_active( 'invites' ) ) {
		printf(
			'<p class="description">%s</p>',
			sprintf(
				__(
					'Because <a href="%s">Email Invites</a> is enabled, invited users will still be allowed to register new accounts.',
					'buddyboss'
				),
				add_query_arg(
					array(
						'page' => 'bp-settings',
						'tab'  => 'bp-invites',
					),
					admin_url( 'admin.php' )
				)
			)
		);
	}
}

/**
 * Allow member type to send invites setting field
 *
 * @since BuddyBoss 1.0.0
 *
 * @param $args array
 *
 * @uses checked() To display the checked attribute
 */
function bp_admin_setting_callback_enable_send_invite_member_type( $args ) {

	$option_name = $args['name'];

	if ( true === $args['description'] ) {
		?>
		<p class="description"><?php _e( 'Only allow the selected profile types to send invites.', 'buddyboss' ); ?></p>
		<?php
	} ?>
	<input name="<?php echo esc_attr( 'bp-enable-send-invite-member-type-' . $option_name ); ?>" id="<?php echo esc_attr( $option_name ); ?>" type="checkbox" value="1" <?php checked( bp_enable_send_invite_member_type( 'bp-enable-send-invite-member-type-' . $option_name, false ) ); ?>/>
	<label for="<?php echo esc_attr( $option_name ); ?>"><?php echo esc_html( $args['member_type_name'] ); ?></label>
	<?php

}

/**
 * Allow members to enable gravatars.
 *
 * @since BuddyBoss 1.0.0
 */
function bp_admin_setting_callback_enable_profile_gravatar() {
	?>
	<input id="bp-enable-profile-gravatar" name="bp-enable-profile-gravatar" type="checkbox" value="1" <?php checked( bp_enable_profile_gravatar() ); ?> />
	<label for="bp-enable-profile-gravatar"><?php _e( 'Allow members to use <a href="https://gravatar.com/">gravatars</a> for profile avatars', 'buddyboss' ); ?></label>
	<?php
}

/**
 * Link to Email Invites tutorial
 *
 * @since BuddyBoss 1.0.0
 */
function bp_email_invites_tutorial() {
	?>

	<p>
		<a class="button" href="<?php echo bp_get_admin_url(
			add_query_arg(
				array(
					'page'    => 'bp-help',
					'article' => 62838,
				),
				'admin.php'
			)
		); ?>"><?php _e( 'View Tutorial', 'buddyboss' ); ?></a>
	</p>

	<?php
}

/**
 * If 'First Name' selected then add option to hide Last Name.
 *
 * @since BuddyBoss 1.1.1
 */
function bp_admin_setting_display_name_first_name() {
	?>

	<input id="bp-hide-first-name" type="checkbox" disabled="disabled" checked="checked" />
	<label for="bp-hide-first-name"><?php _e( 'First Name', 'buddyboss' ); ?></label>

	<br /><br />

	<input id="bp-hide-last-name" name="bp-hide-last-name" type="checkbox" value="1" <?php checked( bp_hide_last_name( true ) ); ?> />
	<label for="bp-hide-last-name"><?php _e( 'Last Name', 'buddyboss' ); ?> <span class="description"><?php _e( '(can be disabled)', 'buddyboss' ); ?></span></label>

	<br /><br />

	<input id="bp-hide-nickname" type="checkbox" disabled="disabled" checked="checked" />
	<label for="bp-hide-nickname"><?php _e( 'Nickname', 'buddyboss' ); ?></label>

	<br /><br />

	<p class="description"><?php _e( 'If you disable "Last Name" field, it will not appear anywhere in the network.', 'buddyboss' ); ?></p>

	<?php
}

/**
 * If 'First Name & Last Name' selected then add option to hide Last Name.
 *
 * @since BuddyBoss 1.1.1
 */
function bp_admin_setting_display_name_first_last_name() {
	?>

	<input id="bp-hide-first-name" type="checkbox" disabled="disabled" checked="checked" />
	<label for="bp-hide-first-name"><?php _e( 'First Name', 'buddyboss' ); ?></label>

	<br /><br />

	<input id="bp-hide-last-name" type="checkbox" disabled="disabled" checked="checked" />
	<label for="bp-hide-last-name"><?php _e( 'Last Name', 'buddyboss' ); ?></label>

	<br /><br />

	<input id="bp-hide-nickname" type="checkbox" disabled="disabled" checked="checked" />
	<label for="bp-hide-nickname"><?php _e( 'Nickname', 'buddyboss' ); ?></label>

	<br /><br />

	<p class="description"><?php _e( 'All name fields are required with this format. Best used for professional networks.', 'buddyboss' ); ?></p>

	<?php
}

/**
 * If 'Nickname' selected then add options to hide First Name.
 *
 * @since BuddyBoss 1.1.1
 */
function bp_admin_setting_callback_nickname_hide_first_name() {
	?>
	<div class="bb-nickname-hide-first-name">
		<input id="bp-hide-nickname-first-name" name="bp-hide-nickname-first-name" type="checkbox" value="1" <?php checked( bp_hide_nickname_first_name( true ) ); ?> />
		<label for="bp-hide-nickname-first-name"><?php _e( 'First Name', 'buddyboss' ); ?> <span class="description"><?php _e( '(can be disabled)', 'buddyboss' ); ?></label>
	</div>
	<?php
}

/**
 * If 'Nickname' selected then add options to hide Last Name.
 *
 * @since BuddyBoss 1.1.1
 */
function bp_admin_setting_callback_nickname_hide_last_name() {
	?>
	<div class="bb-nickname-hide-last-name">
		<input id="bp-hide-nickname-last-name" name="bp-hide-nickname-last-name" type="checkbox" value="1" <?php checked( bp_hide_nickname_last_name( true ) ); ?> />
		<label for="bp-hide-nickname-last-name"><?php _e( 'Last Name', 'buddyboss' ); ?> <span class="description"><?php _e( '(can be disabled)', 'buddyboss' ); ?></label>

		<br /><br />

		<input id="bp-hide-nickname" type="checkbox" disabled="disabled" checked="checked" />
		<label for="bp-hide-nickname"><?php _e( 'Nickname', 'buddyboss' ); ?></label>

		<br /><br />

		<p class="description"><?php _e( 'If you disable "First Name" and "Last Name" fields, they will not appear anywhere in the network. This allows your members to be fully anonymous (if they use a pseudonym for their nickname).', 'buddyboss' ); ?></p></p>
	</div>
	<?php
}

/**
 * Link to Profile Names tutorial
 *
 * @since BuddyBoss 1.1.1
 */
function bp_profile_names_tutorial() {
	?>

	<p>
		<a class="button" href="<?php echo bp_get_admin_url(
			add_query_arg(
				array(
					'page'    => 'bp-help',
					'article' => 72340,
				),
				'admin.php'
			)
		); ?>"><?php _e( 'View Tutorial', 'buddyboss' ); ?></a>
	</p>

	<?php
}

/**
 * Save our settings.
 *
 * @since 1.6.0
 */
function bp_core_admin_settings_save() {
	global $wp_settings_fields;

	if (
		isset( $_GET['page'] )
		&& 'bp-integrations' == $_GET['page']
		&& isset( $_GET['tab'] )
		&& 'bp-compatibility' == $_GET['tab']
		&& ! empty( $_POST['submit'] ) ) {

		check_admin_referer( 'buddypress-options' );

		// Because many settings are saved with checkboxes, and thus will have no values
		// in the $_POST array when unchecked, we loop through the registered settings.
		if ( isset( $wp_settings_fields['buddypress'] ) ) {
			foreach ( (array) $wp_settings_fields['buddypress'] as $section => $settings ) {
				foreach ( $settings as $setting_name => $setting ) {
					$value = isset( $_POST[ $setting_name ] ) ? $_POST[ $setting_name ] : '';

					bp_update_option( $setting_name, $value );
				}
			}
		}

		bp_core_redirect(
			add_query_arg(
				array(
					'page'    => 'bp-integrations',
					'tab'     => 'bp-compatibility',
					'updated' => 'true',
				),
				bp_get_admin_url( 'admin.php' )
			)
		);
	}
}

add_action( 'bp_admin_init', 'bp_core_admin_settings_save', 100 );

 /*
  Admin settings for showing the email confirmation field.
 *
 * @since BuddyBoss 1.1.6
 *
 */
function bp_admin_setting_callback_register_show_confirm_email() {
	?>

	<input id="register-confirm-email" name="register-confirm-email" type="checkbox" value="1" <?php checked( bp_register_confirm_email( false ) ); ?> />
	<label for="register-confirm-email"><?php _e( 'Add Email confirmation to register form', 'buddyboss' ); ?></label>

	<?php
}

/**
 * Admin settings for showing the password confirmation field.
 *
 * @since BuddyBoss 1.1.6
 */
function bp_admin_setting_callback_register_show_confirm_password() {
	?>

	<input id="register-confirm-password" name="register-confirm-password" type="checkbox" value="1" <?php checked( bp_register_confirm_password( false ) ); ?> />
	<label for="register-confirm-password"><?php _e( 'Add Password confirmation to register form', 'buddyboss' ); ?></label>

	<?php
}

/**
 * Admin Settings for Settings > Groups > Group Directories
 *
 * @since BuddyBoss 1.2.0
 */
function bp_admin_setting_callback_group_layout_type_format() {
	$options = [
		'list_grid' => __( 'Grid and List', 'buddyboss' ),
		'grid'      => __( 'Grid', 'buddyboss' ),
		'list'      => __( 'List', 'buddyboss' ),
	];

	$current_value = bp_get_option( 'bp-group-layout-format' );

	printf( '<select name="%1$s" for="%1$s">', 'bp-group-layout-format' );
	foreach ( $options as $key => $value ) {
		printf(
			'<option value="%s" %s>%s</option>',
			$key,
			$key == $current_value? 'selected' : '',
			$value
		);
	}
	printf( '</select>' );

	?>
	<p class="description"><?php _e( 'Display group directories in Grid View, List View, or allow toggling between both views.', 'buddyboss' ); ?></p>
	<?php
}

/**
 * Admin Settings for Settings > Groups > Group Directories > Default Format
 *
 * @since BuddyBoss 1.2.0
 */
function bp_admin_setting_group_layout_default_option() {
	$selected = bp_group_layout_default_format( 'grid' );

	$options = [
		'grid'      => __( 'Grid', 'buddyboss' ),
		'list'      => __( 'List', 'buddyboss' ),
	];

	printf( '<select name="%1$s" for="%1$s">', 'bp-group-layout-default-format' );
	foreach ( $options as $key => $value ) {
		printf(
			'<option value="%s" %s>%s</option>',
			$key,
			$key == $selected ? 'selected' : '',
			$value
		);
	}
	printf( '</select>' );

}

/**
 * Link to Group Directories tutorial
 *
 * @since BuddyBoss 1.2.0
 */
function bp_group_directories_tutorial() {
	?>

	<p>
		<a class="button" href="<?php echo bp_get_admin_url(
			add_query_arg(
				array(
					'page'    => 'bp-help',
					'article' => '',
				),
				'admin.php'
			)
		); ?>"><?php _e( 'View Tutorial', 'buddyboss' ); ?></a>
	</p>

	<?php
}

/*
 Admin settings for showing the allow custom registration checkbox.
*
* @since BuddyBoss 1.2.8
*
*/
function bp_admin_setting_callback_register_allow_custom_registration() {

	$allow_custom_registration = bp_allow_custom_registration();
	?>

    <select name="allow-custom-registration" id="allow-custom-registration">
	    <option value="0" <?php selected( 0, $allow_custom_registration ); ?>><?php _e( 'BuddyBoss Registration', 'buddyboss' ); ?></option>
	    <option value="1" <?php selected( 1, $allow_custom_registration ); ?>><?php _e( 'Custom URL', 'buddyboss' ); ?></option>
    </select>
	<?php
    if ( ! $allow_custom_registration ) {
	    printf( '<p class="description">%s</p>',
		    sprintf( __( 'Use the default BuddyBoss registration form. Make sure to configure the <a href="%s">registration pages</a>.',
			    'buddyboss' ),
			    add_query_arg( array(
				    'page' => 'bp-pages'
			    ),
				    admin_url( 'admin.php' ) ) ) );
    }
}

/*
 Admin settings for showing the allow custom registration checkbox.
*
* @since BuddyBoss 1.2.8
*
*/
function bp_admin_setting_callback_register_page_url() {
	?>
    <input style="width: 89%;" id="register-page-url" name="register-page-url" type="text" value="<?php echo esc_url( bp_custom_register_page_url() ); ?>" />
	<?php
	printf(
		'<p class="description">%s</p>', sprintf( __( 'Enter a custom URL to redirect users to register to your site. Useful for membership plugins.', 'buddyboss' ) )
	);
}

/**
 * Link to Registration tutorial
 *
 * @since BuddyBoss 1.2.8
 */
function bp_admin_registration_setting_tutorial() {
	?>

    <p>
        <a class="button" href="<?php echo bp_get_admin_url(
			add_query_arg(
				array(
					'page'    => 'bp-help',
					'article' => 86158,
				),
				'admin.php'
			)
		); ?>"><?php _e( 'View Tutorial', 'buddyboss' ); ?></a>
    </p>

	<?php
}

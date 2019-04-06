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
 * Main settings section description for the settings page.
 *
 * @since BuddyPress 1.6.0
 */
function bp_admin_setting_callback_main_section() { }

/**
 * Admin bar for logged out users setting field.
 *
 * @since BuddyPress 1.6.0
 *
 */
function bp_admin_setting_callback_admin_bar() {
?>

	<input id="hide-loggedout-adminbar" name="hide-loggedout-adminbar" type="checkbox" value="1" <?php checked( !bp_hide_loggedout_adminbar( false ) ); ?> />
	<label for="hide-loggedout-adminbar"><?php _e( 'Show the Toolbar for logged out users', 'buddyboss' ); ?></label>

<?php
}

/**
 * Allow members to delete their accounts setting field.
 *
 * @since BuddyPress 1.6.0
 *
 */
function bp_admin_setting_callback_account_deletion() {
?>

	<input id="bp-disable-account-deletion" name="bp-disable-account-deletion" type="checkbox" value="1" <?php checked( !bp_disable_account_deletion( false ) ); ?> />
	<label for="bp-disable-account-deletion"><?php _e( 'Allow members to delete their profiles', 'buddyboss' ); ?></label>

<?php
}

/**
 * Allow admin to make the site private network.
 *
 * @since BuddyBoss 1.0.0
 *
 */
function bp_admin_setting_callback_private_network() {
	?>

	<input id="bp-enable-private-network" name="bp-enable-private-network" type="checkbox" value="1" <?php checked( !bp_enable_private_network( false ) ); ?> />
	<label for="bp-enable-private-network"><?php _e( 'Restrict site access to only logged-in members', 'buddyboss' ); ?></label>
	<?php
	printf(
	'<p class="description">%s</p>',
			sprintf(
				__( 'Login and <a href="%s">Registration</a> pages will remain publicly visible.', 'buddyboss' ),
				add_query_arg([
					'page' => 'bp-pages',
				], admin_url( 'admin.php' ) )
			)
	);
}

/** Activity *******************************************************************/

/**
 * Groups settings section description for the settings page.
 *
 * @since BuddyPress 1.6.0
 */
function bp_admin_setting_callback_activity_section() { }

/**
 * Allow Akismet setting field.
 *
 * @since BuddyPress 1.6.0
 *
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

	<input id="bp-disable-blogforum-comments" name="bp-disable-blogforum-comments" type="checkbox" value="1" <?php checked( !bp_disable_blogforum_comments( false ) ); ?> />
	<label for="bp-disable-blogforum-comments"><?php _e( 'Allow activity stream commenting on blog posts, forum discussions and topics', 'buddyboss' ); ?></label>

<?php
}

/**
 * Allow Heartbeat to refresh activity stream.
 *
 * @since BuddyPress 2.0.0
 */
function bp_admin_setting_callback_heartbeat() {
?>

	<input id="_bp_enable_heartbeat_refresh" name="_bp_enable_heartbeat_refresh" type="checkbox" value="1" <?php checked( bp_is_activity_heartbeat_active( true ) ); ?> />
	<label for="_bp_enable_heartbeat_refresh"><?php _e( 'Automatically check for new activity posts', 'buddyboss' ); ?></label>

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
 * Allow emoji in activity posts.
 *
 * @since BuddyBoss 1.0.0
 */
function bp_admin_setting_callback_enable_activity_emoji() {
	?>

	<input id="_bp_enable_activity_emoji" name="_bp_enable_activity_emoji" type="checkbox" value="1" <?php checked( bp_is_activity_emoji_active( false ) ); ?> />
	<label for="_bp_enable_activity_emoji"><?php _e( 'Display emoji dropdown to choose from when creating activity posts', 'buddyboss' ); ?></label>

	<?php
}

/**
 * Allow GIFs in activity posts.
 *
 * @since BuddyBoss 1.0.0
 */
function bp_admin_setting_callback_enable_activity_gif() {
	?>

	<input id="_bp_enable_activity_gif" name="_bp_enable_activity_gif" type="checkbox" value="1" data-run-js-condition="_bp_enable_activity_gif" <?php checked( bp_is_activity_gif_active( false ) ); ?> />
	<label for="_bp_enable_activity_gif"><?php _e( 'Display a library of animated GIFs to choose from when creating activity posts', 'buddyboss' ); ?></label>
	<p class="description js-show-on-_bp_enable_activity_gif"><?php _e('This feature requires an account at <a href="https://developers.giphy.com/">GIPHY</a>. Create your account, and then click "Create an App". Once done, copy the API key and paste it here:', 'buddyboss') ?> <input type="text" name="_bp_activity_gif_api_key" id="_bp_activity_gif_api_key" value="<?php echo bp_get_activity_gif_api_key() ?>" placeholder="<?php _e( 'GIPHY API key', 'buddyboss' ); ?>" style="width: 300px;" /></p>
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

/** XProfile ******************************************************************/

/**
 * Profile settings section description for the settings page.
 *
 * @since BuddyPress 1.6.0
 */
function bp_admin_setting_callback_xprofile_section() { }

/**
 * Allow members to upload avatars field.
 *
 * @since BuddyPress 1.6.0
 *
 */
function bp_admin_setting_callback_avatar_uploads() {
?>

	<input id="bp-disable-avatar-uploads" name="bp-disable-avatar-uploads" type="checkbox" value="1" <?php checked( !bp_disable_avatar_uploads( false ) ); ?> />
	<label for="bp-disable-avatar-uploads"><?php _e( 'Allow members to upload avatars', 'buddyboss' ); ?></label>

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
	<label for="bp-disable-cover-image-uploads"><?php _e( 'Allow members to upload cover photos', 'buddyboss' ); ?></label>
<?php
}

/** Groups Section ************************************************************/

/**
 * Groups settings section description for the settings page.
 *
 * @since BuddyPress 1.6.0
 * @todo deprecate this function?
 */
function bp_admin_setting_callback_groups_section() { }

/**
 * Allow all users to create groups field.
 *
 * @since BuddyPress 1.6.0
 *
 */
function bp_admin_setting_callback_group_creation() {
?>

	<input id="bp_restrict_group_creation" name="bp_restrict_group_creation" type="checkbox" aria-describedby="bp_group_creation_description" value="1" <?php checked( !bp_restrict_group_creation( false ) ); ?> />
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
	<label for="bp-disable-group-avatar-uploads"><?php _e( 'Allow social group organizers to upload an avatar', 'buddyboss' ); ?></label>
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
	<label for="bp-disable-group-cover-image-uploads"><?php _e( 'Allow social group organizers to upload cover photos', 'buddyboss' ); ?></label>
<?php
}

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
				add_query_arg([
					'post_type' => bp_get_group_type_post_type(),
				], admin_url( 'edit.php' ) )
			)
		);
	} else {
		?>
		<label for="bp-disable-group-type-creation"><?php _e( 'Enable group types to better organize groups', 'buddyboss' ); ?></label>
		<?php
	}

}

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
				add_query_arg([
					'post_type' => bp_get_member_type_post_type(),
				], admin_url( 'edit.php' ) )
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

/** Settings Page *************************************************************/

/**
 * The main settings page
 *
 * @since BuddyBoss 1.0.0
 *
 */
function bp_core_admin_settings() {
    $active_tab = bp_core_get_admin_active_tab();
    $form_action = bp_core_admin_setting_url( $active_tab );
	?>

	<div class="wrap">
		<h2 class="nav-tab-wrapper"><?php bp_core_admin_tabs( __( 'Settings', 'buddyboss' ) ); ?></h2>
		<div class="nav-settings-subsubsub">
			<ul class="subsubsub">
				<?php bp_core_settings_admin_tabs(); ?>
			</ul>
		</div>
		<form action="<?php echo esc_url( $form_action ) ?>" method="post">
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
    $active_tab = bp_core_get_admin_integration_active_tab();
    $form_action = bp_core_admin_integrations_url( $active_tab );
    ?>

    <div class="wrap">
	    <h2 class="nav-tab-wrapper"><?php bp_core_admin_tabs( __( 'Integrations', 'buddyboss' ) ); ?></h2>
	    <div class="nav-settings-subsubsub">
	        <ul class="subsubsub">
		        <?php bp_core_admin_integration_tabs(); ?>
	        </ul>
	    </div>
        <form action="<?php echo esc_url( $form_action ) ?>" method="post">
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
	        <?php require buddypress()->plugin_dir . 'bp-core/admin/templates/appboss-screen.php'; ?>
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
function bp_form_option( $option, $default = '' , $slug = false ) {
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
		if ( empty( $value ) )
			$value = $default;

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
 * @todo link to Profile Types
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
	?>
	<input
		name="<?php echo $option_name ?>"
		id="<?php echo $option_name ?>"
		type="checkbox"
		value="1"
		<?php checked( bp_is_post_type_feed_enable( $post_type, false ) ) ?>
	/>
	<label for="<?php echo $option_name ?>">
		<?php echo $post_type === 'post' ? esc_html__( 'Blog Posts', 'buddyboss' ) : $post_type_obj->labels->name ?>
	</label>
	<?php

	// Description for the WordPress Blog Posts
	if ( 'post' === $post_type ) {
		?>
		<p class="description"><?php _e( 'When users publish new blog posts, show them in the activity feed.', 'buddyboss' ); ?></p>
		<?php
	}

	// Description for the last option of CPT
	if ( true === $args['description'] && 'post' !== $post_type ) {
		?>
		<p class="description"><?php _e( 'Select which Custom Post Types (coming from your plugins) should be shown in the activity feed. For example, if using WooCommerce it could post into the activity feed every time someone creates a new product.', 'buddyboss' ); ?></p>
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
		<input name="<?php echo esc_attr( 'bp-feed-platform-'.$option_name ); ?>" id="<?php echo esc_attr( $option_name ); ?>" type="checkbox" value="1" <?php checked( bp_platform_is_feed_enable( 'bp-feed-platform-'.$option_name, true ) ); ?>/>
		<label for="<?php echo esc_attr( $option_name ); ?>"><?php echo esc_html( $args['activity_label'], 'buddyboss' ); ?></label>
		<?php

}

/**
 * Admin bar for logged out users setting field.
 *
 * @since BuddyPress 1.6.0
 *
 */
function bp_admin_setting_callback_register() {
	?>

	<input id="bp-enable-site-registration" name="bp-enable-site-registration" type="checkbox" value="1" <?php checked( bp_enable_site_registration() ); ?> />
	<label for="bp-enable-site-registration"><?php _e( 'Allow non-members to register new accounts', 'buddyboss' ); ?></label>
	<?php
	if ( true === bp_enable_site_registration() && bp_is_active( 'invites' ) ) {
		printf( '<p class="description">%s</p>',
			sprintf( __( 'Because <a href="%s">Email Invites</a> is enabled, invited users will still be allowed to register new accounts.',
				'buddyboss' ),
				add_query_arg( [
					'page' => 'bp-settings',
					'tab'  => 'bp-invites',
				],
					admin_url( 'admin.php' ) ) ) );
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


	if ( true === $args['description'] ) { ?>
		<p class="description"><?php _e( 'Only allow the selected profile types to send invites.', 'buddyboss' ); ?></p>
		<?php
	} ?>
	<input name="<?php echo esc_attr( 'bp-enable-send-invite-member-type-'.$option_name ); ?>" id="<?php echo esc_attr( $option_name ); ?>" type="checkbox" value="1" <?php checked( bp_enable_send_invite_member_type( 'bp-enable-send-invite-member-type-'.$option_name, false ) ); ?>/>
	<label for="<?php echo esc_attr( $option_name ); ?>"><?php esc_html_e( $args['member_type_name'], 'buddyboss' ); ?></label>
	<?php

}

/**
 * Allow admin to make the site private network.
 *
 * @since BuddyBoss 1.0.0
 *
 */
function bp_admin_setting_callback_private_network_public_content() {
	?>

	<label for="bp-enable-private-network-public-content"><?php _e( 'Enter URLs or URI fragments (e.g. /groups/) to remain publicly visible always. Enter one URL or URI per line. ', 'buddyboss' ); ?></label>
	<textarea rows="10" cols="100" id="bp-enable-private-network-public-content" name="bp-enable-private-network-public-content"><?php echo esc_textarea( bp_enable_private_network_public_content() ); ?></textarea>
	<?php
}

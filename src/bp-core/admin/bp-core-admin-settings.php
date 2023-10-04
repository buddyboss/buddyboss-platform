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
	<label for="hide-loggedout-adminbar"><?php esc_html_e( 'Show the Toolbar for logged out users', 'buddyboss' ); ?></label>

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
	<label for="bp-disable-account-deletion"><?php esc_html_e( 'Allow members to delete their profiles', 'buddyboss' ); ?></label>

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
	<label for="show-login-adminbar"><?php esc_html_e( 'Show the Toolbar for logged-in members (non-admins)', 'buddyboss' ); ?></label>

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
	<label for="show-admin-adminbar"><?php esc_html_e( 'Show the Toolbar for logged-in admins', 'buddyboss' ); ?></label>

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
		<a class="button" href="
		<?php
		echo esc_url(
			bp_get_admin_url(
				add_query_arg(
					array(
						'page'    => 'bp-help',
						'article' => 62792,
					),
					'admin.php'
				)
			)
		);
		?>
		"><?php esc_html_e( 'View Tutorial', 'buddyboss' ); ?></a>
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
	<label for="bp-enable-private-network"><?php esc_html_e( 'Restrict site access to only logged-in members', 'buddyboss' ); ?></label>
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

	<label for="bp-enable-private-network-public-content"><?php esc_html_e( 'Enter URLs or URI fragments (e.g. /groups/) to remain publicly visible always. Enter one URL or URI per line. ', 'buddyboss' ); ?></label>
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
		<a class="button" href="
		<?php
		echo esc_url(
			bp_get_admin_url(
				add_query_arg(
					array(
						'page'    => 'bp-help',
						'article' => 62793,
					),
					'admin.php'
				)
			)
		);
		?>
		"><?php esc_html_e( 'View Tutorial', 'buddyboss' ); ?></a>
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
	<label for="_bp_enable_akismet"><?php esc_html_e( 'Enable Akismet spam protection for activity feed', 'buddyboss' ); ?></label>

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
	<label for="bp-disable-blogforum-comments"><?php esc_html_e( 'Allow activity feed commenting on blog posts, custom post types, and forum discussions', 'buddyboss' ); ?></label>

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
		<a class="button" href="
		<?php
		echo esc_url(
			bp_get_admin_url(
				add_query_arg(
					array(
						'page'    => 'bp-help',
						'article' => 62823,
					),
					'admin.php'
				)
			)
		);
		?>
		"><?php esc_html_e( 'View Tutorial', 'buddyboss' ); ?></a>
	</p>
	<?php
}

/**
 * Allow Heartbeat to refresh activity stream.
 *
 * @since BuddyPress 2.0.0
 */
function bp_admin_setting_callback_heartbeat() {
	// NOTE: this request is made to check for Heartbeat API on front end if it enabled or not.
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
	<label for="_bp_enable_heartbeat_refresh"><?php esc_html_e( 'Automatically check for new activity posts', 'buddyboss' ); ?></label>
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
	<label for="_bp_enable_activity_autoload"><?php esc_html_e( 'Automatically load more activity posts when scrolling to the bottom of the page ', 'buddyboss' ); ?></label>

	<?php
}

/**
 * Enable activity edit
 *
 * @since BuddyBoss 1.5.0
 */
function bp_admin_setting_callback_enable_activity_edit() {
	$edit_times = bp_activity_edit_times();
	$edit_time  = bp_get_activity_edit_time();
	?>

	<input id="_bp_enable_activity_edit" name="_bp_enable_activity_edit" type="checkbox" value="1" <?php checked( bp_is_activity_edit_enabled( false ) ); ?> />
	<label for="_bp_enable_activity_edit"><?php esc_html_e( 'Allow members to edit their activity posts for a duration of', 'buddyboss' ); ?></label>

	<select name="_bp_activity_edit_time">
		<option value="-1"><?php esc_html_e( 'Forever', 'buddyboss' ); ?></option>
		<?php
		foreach ( $edit_times as $time ) {
			$value      = isset( $time['value'] ) ? $time['value'] : 0;
			$time_level = isset( $time['label'] ) ? $time['label'] : 0;
			echo '<option value="' . esc_attr( $value ) . '" ' . selected( $edit_time, $value, false ) . '>' . esc_html( $time_level ) . '</option>';
		}
		?>
	</select>

	<?php
}

/**
 * Enable relevant activity.
 *
 * @since BuddyBoss 1.5.5
 */
function bp_admin_setting_callback_enable_relevant_feed() {
	?>
	<input id="_bp_enable_relevant_feed" name="_bp_enable_relevant_feed" type="checkbox" value="1" <?php checked( bp_is_relevant_feed_enabled( false ) ); ?> />
	<label for="_bp_enable_relevant_feed"><?php esc_html_e( 'Restrict the Activity Feed directory to only posts that are relevant to the logged-in member', 'buddyboss' ); ?></label>
	<p class="description"><?php esc_html_e( 'While logged in, members will only see activity posts from their own timeline, their connections, members they followed, groups they joined, forum discussions they subscribed to, and posts they are mentioned in.', 'buddyboss' ); ?></p>
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
	<label for="_bp_enable_activity_tabs"><?php esc_html_e( 'Display activity in separate tabs based on activity type', 'buddyboss' ); ?></label>

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
	<label for="_bp_enable_activity_follow"><?php esc_html_e( 'Allow your members to follow the activity of each other on their timeline', 'buddyboss' ); ?></label>

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
	<label for="_bp_enable_activity_like"><?php esc_html_e( 'Allow your members to "Like" each other\'s activity posts', 'buddyboss' ); ?></label>

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
	<label for="_bp_enable_activity_link_preview"><?php esc_html_e( 'When links are used in activity posts, display an image and excerpt from the site', 'buddyboss' ); ?></label>

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
		<a class="button" href="
		<?php
			echo esc_url(
				bp_get_admin_url(
					add_query_arg(
						array(
							'page'    => 'bp-help',
							'article' => 62822,
						),
						'admin.php'
					)
				)
			);
		?>
		"><?php esc_html_e( 'View Tutorial', 'buddyboss' ); ?></a>
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
	<label for="bp-disable-avatar-uploads"><?php esc_html_e( 'Allow members to upload a profile avatar', 'buddyboss' ); ?></label>

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
	<label for="bp-disable-cover-image-uploads"><?php esc_html_e( 'Enable cover images for member profiles', 'buddyboss' ); ?></label>

	<p class="description"><?php esc_html_e( 'When enabled, members will be able to upload cover images in their profile settings.', 'buddyboss' ); ?></p>
	<?php
}

/**
 * Which type of avatar needs to display.
 *
 * @since BuddyBoss 1.8.6
 */
function bp_admin_setting_callback_profile_avatar_type() {
	?>
	<div class="avatar-custom-input">
		<select name="bp-profile-avatar-type" id="bp-profile-avatar-type">
			<option value="BuddyBoss" <?php selected( bb_get_profile_avatar_type(), 'BuddyBoss' ); ?>><?php esc_html_e( 'BuddyBoss', 'buddyboss' ); ?></option>
			<option value="WordPress" <?php selected( bb_get_profile_avatar_type(), 'WordPress' ); ?>><?php esc_html_e( 'WordPress', 'buddyboss' ); ?></option>
		</select>
	</div>

	<p class="description">
		<?php
		$link = '<a href="' . esc_url( admin_url( 'options-discussion.php' ) ) . '">' . esc_html__( 'Discussion', 'buddyboss' ) . '</a>';
		echo sprintf(
			/* translators: %s: Admin discussion link */
			__( 'Select whether to use the BuddyBoss or WordPress avatar systems. You can manage WordPress avatars in the %s settings.', 'buddyboss' ),
			wp_kses_post( $link )
		);
		?>
	</p>

	<div class="bp-cover-image-status bb-wordpress-profile-gravatar-warning <?php echo ( ( ! get_option( 'show_avatars' ) && 'WordPress' === bb_get_profile_avatar_type() ) ? '' : 'bp-hide' ); ?>">
		<p id="bb-wordpress-profile-gravatar-feedback" class="updated warning">
			<?php
			$link = '<a href="' . esc_url( admin_url( 'options-discussion.php' ) ) . '">' . esc_html__( 'Discussion', 'buddyboss' ) . '</a>';
			echo sprintf(
				/* translators: %s: Admin discussion link */
				__( 'Please enable "Avatar display" in your WordPress %s settings.', 'buddyboss' ),
				wp_kses_post( $link )
			);
			?>
		</p>
	</div>
	<?php
}

/**
 * Allow admin to set default profile avatar.
 *
 * @since BuddyBoss 1.8.6
 */
function bp_admin_setting_callback_default_profile_avatar_type() {
	?>
	<div class="avatar-custom-input">
		<input id="bp-default-profile-avatar-buddyboss" name="bp-default-profile-avatar-type" type="radio" value="buddyboss" <?php checked( bb_get_default_profile_avatar_type(), 'buddyboss' ); ?> />
		<label for="bp-default-profile-avatar-buddyboss">
			<div class="img-block">
				<img class="buddyboss-profile-avatar" src="<?php echo esc_url( buddypress()->plugin_url . 'bp-core/images/bb-profile-avatar-buddyboss.jpg' ); ?>" />
			</div>
			<span><?php esc_html_e( 'BuddyBoss', 'buddyboss' ); ?></span>
		</label>
	</div>

	<div class="avatar-custom-input">
		<input id="bp-default-profile-avatar-legacy" name="bp-default-profile-avatar-type" type="radio" value="legacy" <?php checked( bb_get_default_profile_avatar_type(), 'legacy' ); ?> />
		<label for="bp-default-profile-avatar-legacy">
			<div class="img-block">
				<img class="legacy-profile-avatar" src="<?php echo esc_url( buddypress()->plugin_url . 'bp-core/images/bb-profile-avatar-legacy.jpg' ); ?>" />
			</div>
			<span><?php esc_html_e( 'Legacy', 'buddyboss' ); ?></span>
		</label>
	</div>

	<div class="avatar-custom-input">
		<input id="bp-default-profile-avatar-custom" name="bp-default-profile-avatar-type" type="radio" value="custom" <?php checked( bb_get_default_profile_avatar_type(), 'custom' ); ?> />
		<label for="bp-default-profile-avatar-custom">
			<div class="img-block">
				<img src="<?php echo esc_url( buddypress()->plugin_url . 'bp-core/images/bb-avatar-custom.jpg' ); ?>" />
			</div>
			<span><?php esc_html_e( 'Custom', 'buddyboss' ); ?></span>
		</label>
	</div>

	<p class="description"><?php esc_html_e( 'Select which image should be used for members who haven\'t uploaded a profile avatar.', 'buddyboss' ); ?></p>

	<div class="bp-cover-image-status bb-wordpress-profile-gravatar-warning" style="display:none;">
		<p id="bb-wordpress-profile-gravatar-feedback" class="updated warning"><?php _e( 'Please enable <strong>Profile Gravatars</strong> below in order to use one of WordPress\' generated default avatars.', 'buddyboss' ); ?></p>
	</div>
	<?php
}

/**
 * Allow admin to upload custom default profile avatar.
 *
 * @since BuddyBoss 1.8.6
 */
function bp_admin_setting_callback_default_profile_custom_avatar() {
	$hide_show_style        = 'bp-inline-block';
	$placeholder_avatar_url = buddypress()->plugin_url . 'bp-core/images/bb-avatar-placeholder.jpg';
	$custom_avatar_url      = bb_get_default_custom_upload_profile_avatar();

	if ( ! $custom_avatar_url || empty( $custom_avatar_url ) ) {
		$custom_avatar_url = $placeholder_avatar_url;
		$hide_show_style   = 'bp-hide';
	}
	?>

	<div class="bb-default-custom-upload-file custom-profile-avatar custom-profile-group-avatar">
		<div class="bb-upload-container">
			<img src="<?php echo esc_url( $custom_avatar_url ); ?>" class="bb-upload-preview user-custom-avatar <?php echo esc_attr( $hide_show_style ); ?>" data-default="<?php echo esc_url( $placeholder_avatar_url ); ?>">
			<input type="hidden" name="bp-default-custom-profile-avatar" class="bb-default-custom-avatar-field" id="bp-default-user-custom-avatar" value="<?php echo esc_url( bb_get_default_custom_upload_profile_avatar() ); ?>">
		</div>
		<div class="bb-img-button-wrap">
			<a href="#TB_inline?width=800px&height=400px&inlineId=bp-xprofile-avatar-editor" class="button button-large thickbox bp-xprofile-avatar-user-edit" data-uploading="<?php esc_html_e( 'Uploading...', 'buddyboss' ); ?>" data-upload="<?php esc_html_e( 'Upload', 'buddyboss' ); ?>"><?php esc_html_e( 'Upload', 'buddyboss' ); ?></a>
			<a href="#" class="delete button button-large bb-img-remove-button bp-delete-custom-avatar bp-delete-custom-profile-avatar <?php echo esc_attr( $hide_show_style ); ?>" data-removing="<?php esc_html_e( 'Removing...', 'buddyboss' ); ?>" data-remove="<?php esc_html_e( 'Remove', 'buddyboss' ); ?>"><?php esc_html_e( 'Remove', 'buddyboss' ); ?></a>
			<div id="bp-xprofile-avatar-editor" style="display:none;">
				<?php bp_attachments_get_template_part( 'avatars/index' ); ?>
			</div>
		</div>
	</div>
	<div class="bp-messages-feedback admin-notice bp-cover-image-status bb-custom-profile-group-avatar-feedback" style="display: none;">
		<div class="bp-feedback">
			<span class="bp-icon" aria-hidden="true"></span>
			<p id="bp-avatar-image-feedback"></p>
		</div>
	</div>
	<p class="description">
		<?php
		echo sprintf(
		/* translators: 1: Profile avatar width. 2: Profile avatar height */
			esc_html__( 'Upload an image to be used as the default profile avatar. Recommended size is %1$spx by %2$spx.', 'buddyboss' ),
			(int) bp_core_avatar_full_width(),
			(int) bp_core_avatar_full_height()
		);
		?>
	</p>
	<?php
}

/**
 * Allow admin to set default profile cover.
 *
 * @since BuddyBoss 1.8.6
 */
function bp_admin_setting_callback_default_profile_cover_type() {
	?>
	<div class="avatar-custom-input">
		<input id="bp-default-profile-cover-default" name="bp-default-profile-cover-type" type="radio" value="buddyboss" <?php checked( bb_get_default_profile_cover_type(), 'buddyboss' ); ?> />
		<label for="bp-default-profile-cover-default">
			<div class="img-block">
				<img src="<?php echo esc_url( buddypress()->plugin_url . 'bp-core/images/bb-cover-buddyboss.jpg' ); ?>" />
			</div>
			<span><?php esc_html_e( 'BuddyBoss', 'buddyboss' ); ?></span>
		</label>
	</div>

	<div class="avatar-custom-input">
		<input id="bp-default-profile-cover-none" name="bp-default-profile-cover-type" type="radio" value="none" <?php checked( bb_get_default_profile_cover_type(), 'none' ); ?> />
		<label for="bp-default-profile-cover-none">
			<div class="img-block">
				<img src="<?php echo esc_url( buddypress()->plugin_url . 'bp-core/images/bb-cover-none.jpg' ); ?>" />
			</div>
			<span><?php esc_html_e( 'None', 'buddyboss' ); ?></span>
		</label>
	</div>

	<div class="avatar-custom-input">
		<input id="bp-default-profile-cover-custom" name="bp-default-profile-cover-type" type="radio" value="custom" <?php checked( bb_get_default_profile_cover_type(), 'custom' ); ?> />
		<label for="bp-default-profile-cover-custom">
			<div class="img-block">
				<img src="<?php echo esc_url( buddypress()->plugin_url . 'bp-core/images/bb-cover-custom.jpg' ); ?>" />
			</div>
			<span><?php esc_html_e( 'Custom', 'buddyboss' ); ?></span>
		</label>
	</div>
	<p class="description"><?php esc_html_e( 'Select which image should be used for members who haven\'t uploaded a profile cover image.', 'buddyboss' ); ?></p>
	<?php
}

/**
 * Allow admin to upload custom default profile cover.
 *
 * @since BuddyBoss 1.8.6
 */
function bp_admin_setting_callback_default_profile_custom_cover() {
	$cover_dimensions = bb_attachments_get_default_custom_cover_image_dimensions();

	$hide_show_style       = '';
	$placeholder_cover_url = buddypress()->plugin_url . 'bp-core/images/bb-cover-placeholder.jpg';
	$profile_cover_image   = bb_get_default_custom_upload_profile_cover();

	if ( empty( $profile_cover_image ) ) {
		$profile_cover_image = $placeholder_cover_url;
		$hide_show_style     = 'bp-hide';
	}
	?>
	<div class="bb-default-custom-upload-file custom-profile-avatar cover-uploader custom-profile-group-cover">
		<div class="bb-upload-container">
			<img src="<?php echo esc_url( $profile_cover_image ); ?>" data-default="<?php echo esc_url( $placeholder_cover_url ); ?>" class="bb-upload-preview <?php echo esc_attr( $hide_show_style ); ?>">
			<input type="hidden" name="bp-default-custom-profile-cover" id="bp-default-custom-user-cover" value="<?php echo esc_url( bb_get_default_custom_upload_profile_cover() ); ?>">
		</div>
		<div class="bb-img-button-wrap">
			<label class="cover-uploader-label">
				<input type="file" name="default-profile-cover-file" id="default-profile-cover-file" class="bb-setting-profile button cover-uploader-hide" accept="image/*">
				<a href="#" class="button button-large bb-img-upload-button" data-uploading="<?php esc_html_e( 'Uploading...', 'buddyboss' ); ?>" data-upload="<?php esc_html_e( 'Upload', 'buddyboss' ); ?>"><?php esc_html_e( 'Upload', 'buddyboss' ); ?></a>
			</label>
			<a href="#" class="delete button button-large bb-img-remove-button <?php echo esc_attr( $hide_show_style ); ?>" data-removing="<?php esc_html_e( 'Removing...', 'buddyboss' ); ?>" data-remove="<?php esc_html_e( 'Remove', 'buddyboss' ); ?>"><?php esc_html_e( 'Remove', 'buddyboss' ); ?></a>
		</div>

		<div class="bp-messages-feedback admin-notice bp-cover-image-status bb-custom-profile-group-cover-feedback" style="display: none;">
			<div class="bp-feedback">
				<span class="bp-icon" aria-hidden="true"></span>
				<p id="bp-cover-image-feedback"></p>
			</div>
		</div>


	</div>
	<p class="description">
		<?php
		echo sprintf(
			/* translators: 1: Profile cover width. 2: Profile cover height */
			esc_html__( 'Upload an image to be used as the default profile cover image. Recommended size is %1$spx by %2$spx.', 'buddyboss' ),
			(int) $cover_dimensions['width'],
			(int) $cover_dimensions['height']
		);
		?>
	</p>
	<?php
}

/**
 * Preview based on profile images settings.
 *
 * @since BuddyBoss 1.8.6
 */
function bp_admin_setting_callback_preview_profile_avatar_cover() {
	$live_preview_settings = bb_get_settings_live_preview_default_profile_group_images();
	$avatar                = bb_attachments_get_default_profile_group_avatar_image(
		array(
			'object' => 'user',
		)
	);

	// If Profile Avatar is 'WordPress'.
	$wordpress_avatar_url = bb_get_blank_profile_avatar();
	if ( bp_get_option( 'show_avatars' ) && 'blank' !== bp_get_option( 'avatar_default', 'mystery' ) ) {
		$wordpress_avatar_url = get_avatar_url(
			'',
			array(
				'size'          => 64,
				'default'       => bp_get_option( 'avatar_default', 'mystery' ),
				'force_default' => true,
			)
		);
	}

	if ( 'WordPress' === bb_get_profile_avatar_type() ) {
		$avatar = $wordpress_avatar_url;
	}

	$web_cover_preview = buddypress()->plugin_url . 'bp-core/images/cover-image.png';
	$app_cover_preview = buddypress()->plugin_url . 'bp-core/images/cover-image.png';

	if ( 'buddyboss' !== bb_get_default_profile_cover_type() ) {
		$web_cover_preview = bb_attachments_get_default_profile_group_cover_image( 'members' );
		$app_cover_preview = $web_cover_preview;
	}
	?>
	<div class="preview_avatar_cover has-avatar has-cover">

		<div class="preview-switcher-main">

			<div class="button-group preview-switcher">
				<?php if ( $live_preview_settings['is_buddyboss_app_plugin_active'] ) : ?>
					<a href="#web-preview" class="button button-large button-primary"><?php esc_html_e( 'Browser', 'buddyboss' ); ?></a>
					<a href="#app-preview" class="button button-large"><?php esc_html_e( 'App', 'buddyboss' ); ?></a>
				<?php endif; ?>
			</div>
			<div class="web-preview-wrap preview-block active" id="web-preview">
				<div class="preview-item-cover <?php echo esc_attr( bb_get_profile_cover_image_height() . '-image' ); ?>" style="background-color: <?php echo esc_attr( $live_preview_settings['web_background_color'] ); ?>">
					<img src="<?php echo esc_url( $web_cover_preview ); ?>" alt="" data-buddyboss-cover="<?php echo esc_url( buddypress()->plugin_url . 'bp-core/images/cover-image.png' ); ?>">
				</div>
				<div class="preview-item-avatar <?php echo esc_attr( bb_get_profile_header_layout_style() . '-image' ); ?>">
					<img src="<?php echo esc_url( $avatar ); ?>" alt="" class="user-custom-avatar" data-wordpress-avatar="<?php echo esc_url( $wordpress_avatar_url ); ?>" data-blank-avatar="<?php echo esc_url( bb_get_blank_profile_avatar() ); ?>">
				</div>
			</div>
			<?php if ( $live_preview_settings['is_buddyboss_app_plugin_active'] ) : ?>
				<div class="app-preview-wrap preview-block" id="app-preview">
					<div class="preview-item-cover" style="background-color: <?php echo esc_attr( $live_preview_settings['app_background_color'] ); ?>">
						<img src="<?php echo esc_url( $app_cover_preview ); ?>" alt="" data-buddyboss-cover="<?php echo esc_url( buddypress()->plugin_url . 'bp-core/images/cover-image.png' ); ?>">
					</div>
					<div class="preview-item-avatar">
						<img src="<?php echo esc_url( $avatar ); ?>" alt="" class="user-custom-avatar" data-wordpress-avatar="<?php echo esc_url( $wordpress_avatar_url ); ?>" data-blank-avatar="<?php echo esc_url( bb_get_blank_profile_avatar() ); ?>">
					</div>
				</div>
			<?php endif; ?>
		</div>

	</div>
	<p class="description"><?php echo wp_kses_post( $live_preview_settings['info'] ); ?></p>
	<?php
}

/**
 * Default profile cover sizes settings.
 *
 * @since BuddyBoss 1.9.1
 */
function bb_admin_setting_callback_default_profile_cover_size() {

	?>
	<div class="image-width-height">
		<?php
			new BB_Admin_Setting_Fields(
				array(
					'type'        => 'select',
					'id'          => 'bb-cover-profile-width',
					'label'       => esc_html__( 'Width', 'buddyboss' ),
					'description' => esc_html__( 'Select the width of profile cover images in profile headers.', 'buddyboss' ),
					'disabled'    => true,
					'value'       => bb_get_profile_cover_image_width(),
					'options'     => array(
						'default' => esc_html__( 'Default', 'buddyboss' ),
						'full'    => esc_html__( 'Full Width', 'buddyboss' ),
					),
				)
			);
		?>
	</div>
	<div class="image-width-height">
		<?php
			new BB_Admin_Setting_Fields(
				array(
					'type'        => 'select',
					'id'          => 'bb-cover-profile-height',
					'label'       => esc_html__( 'Height', 'buddyboss' ),
					'description' => esc_html__( 'Select the height of profile cover images in profile headers.', 'buddyboss' ),
					'disabled'    => true,
					'value'       => bb_get_profile_cover_image_height(),
					'options'     => array(
						'small' => esc_html__( 'Small', 'buddyboss' ),
						'large' => esc_html__( 'Large', 'buddyboss' ),
					),
				)
			);
		?>
	</div>
	<p class="description"><?php esc_html_e( 'Changing your size of your cover images will reposition cover images already uploaded by your members', 'buddyboss' ); ?></p>
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
		<a class="button" href="
		<?php
			echo esc_url(
				bp_get_admin_url(
					add_query_arg(
						array(
							'page'    => 'bp-help',
							'article' => 125202,
						),
						'admin.php'
					)
				)
			);
		?>
		"><?php esc_html_e( 'View Tutorial', 'buddyboss' ); ?></a>
	</p>
	<?php
}

/** Group Headers ************************************************************/

/**
 * Link to Group Headers Settings tutorial
 *
 * @since BuddyBoss 1.9.1
 */
function bb_group_headers_tutorial() {
	?>
	<p>
		<a class="button" href="
		<?php
		echo esc_url(
			bp_get_admin_url(
				add_query_arg(
					array(
						'page'    => 'bp-help',
						'article' => '125305',
					),
					'admin.php'
				)
			)
		);
		?>
		"><?php esc_html_e( 'View Tutorial', 'buddyboss' ); ?></a>
	</p>
	<?php
}

/** Group Photos ************************************************************/

/**
 * Link to Group Photos Settings tutorial
 *
 * @since BuddyBoss 1.8.6
 */
function bp_group_avatar_tutorial() {
	?>
	<p>
		<a class="button" href="
		<?php
			echo esc_url(
				bp_get_admin_url(
					add_query_arg(
						array(
							'page'    => 'bp-help',
							'article' => 62811,
						),
						'admin.php'
					)
				)
			);
		?>
		"><?php esc_html_e( 'View Tutorial', 'buddyboss' ); ?></a>
	</p>
	<?php
}

/**
 * Allow admin to set default group avatar.
 *
 * @since BuddyBoss 1.8.6
 */
function bp_admin_setting_callback_default_group_avatar_type() {
	?>
	<div class="avatar-custom-input">
		<input id="bp-default-group-avatar-buddyboss" name="bp-default-group-avatar-type" type="radio" value="buddyboss" <?php checked( bb_get_default_group_avatar_type(), 'buddyboss' ); ?> />
		<label for="bp-default-group-avatar-buddyboss">
			<div class="img-block">
				<img class="buddyboss-group-avatar" src="<?php echo esc_url( buddypress()->plugin_url . 'bp-core/images/bb-group-avatar-buddyboss.jpg' ); ?>" />
			</div>
			<span><?php esc_html_e( 'BuddyBoss', 'buddyboss' ); ?></span>
		</label>
	</div>

	<div class="avatar-custom-input">
		<input id="bp-default-group-avatar-legacy" name="bp-default-group-avatar-type" type="radio" value="legacy" <?php checked( bb_get_default_group_avatar_type(), 'legacy' ); ?> />
		<label for="bp-default-group-avatar-legacy">
			<div class="img-block">
				<img class="legacy-group-avatar" src="<?php echo esc_url( buddypress()->plugin_url . 'bp-core/images/bb-group-avatar-legacy.jpg' ); ?>" />
			</div>
			<span><?php esc_html_e( 'Legacy', 'buddyboss' ); ?></span>
		</label>
	</div>

	<div class="avatar-custom-input">
		<input id="bp-default-group-avatar-custom" name="bp-default-group-avatar-type" type="radio" value="custom" <?php checked( bb_get_default_group_avatar_type(), 'custom' ); ?> />
		<label for="bp-default-group-avatar-custom">
			<div class="img-block">
				<img src="<?php echo esc_url( buddypress()->plugin_url . 'bp-core/images/bb-avatar-custom.jpg' ); ?>" />
			</div>
			<span><?php esc_html_e( 'Custom', 'buddyboss' ); ?></span>
		</label>
	</div>
	<?php
}

/**
 * Allow admin to upload custom default group avatar.
 *
 * @since BuddyBoss 1.8.6
 */
function bp_admin_setting_callback_default_group_custom_avatar() {
	$hide_show_style        = 'bp-inline-block';
	$placeholder_avatar_url = buddypress()->plugin_url . 'bp-core/images/bb-avatar-placeholder.jpg';
	$custom_avatar_url      = bb_get_default_custom_upload_group_avatar();

	if ( ! $custom_avatar_url || empty( $custom_avatar_url ) ) {
		$custom_avatar_url = $placeholder_avatar_url;
		$hide_show_style   = 'bp-hide';
	}
	?>

	<div class="bb-default-custom-upload-file custom-group-avatar custom-profile-group-avatar">
		<div class="bb-upload-container">
			<img src="<?php echo esc_url( $custom_avatar_url ); ?>" class="bb-upload-preview group-custom-avatar <?php echo esc_attr( $hide_show_style ); ?>" data-placeholder="<?php echo esc_url( $placeholder_avatar_url ); ?>">
			<input type="hidden" name="bp-default-custom-group-avatar" class="bb-default-custom-avatar-field" id="bp-default-group-custom-avatar" value="<?php echo esc_url( bb_get_default_custom_upload_group_avatar() ); ?>">
		</div>
		<div class="bb-img-button-wrap">
			<a href="#TB_inline?width=800px&height=400px&inlineId=bp-xprofile-avatar-editor" class="button button-large thickbox bp-xprofile-avatar-user-edit" data-uploading="<?php esc_html_e( 'Uploading...', 'buddyboss' ); ?>" data-upload="<?php esc_html_e( 'Upload', 'buddyboss' ); ?>"><?php esc_html_e( 'Upload', 'buddyboss' ); ?></a>
			<a href="#" class="delete button button-large bb-img-remove-button bp-delete-custom-avatar bp-delete-custom-group-avatar <?php echo esc_attr( $hide_show_style ); ?>" data-removing="<?php esc_html_e( 'Removing...', 'buddyboss' ); ?>" data-remove="<?php esc_html_e( 'Remove', 'buddyboss' ); ?>"><?php esc_html_e( 'Remove', 'buddyboss' ); ?></a>
			<div id="bp-xprofile-avatar-editor" style="display:none;">
				<?php bp_attachments_get_template_part( 'avatars/index' ); ?>
			</div>
		</div>
	</div>
	<div class="bp-messages-feedback admin-notice bp-cover-image-status bb-custom-profile-group-avatar-feedback" style="display: none;">
		<div class="bp-feedback">
			<span class="bp-icon" aria-hidden="true"></span>
			<p id="bp-avatar-image-feedback"></p>
		</div>
	</div>
	<p class="description">
		<?php
		echo sprintf(
			/* translators: 1: Full avatar width in pixels. 2: Full avatar height in pixels */
			esc_html__( 'Upload an image to be used as the default group avatar. Recommended size is %1$spx by %2$spx.', 'buddyboss' ),
			absint( bp_core_avatar_full_width() ),
			absint( bp_core_avatar_full_height() )
		);
		?>
	</p>
	<?php
}

/**
 * Allow admin to set default group cover.
 *
 * @since BuddyBoss 1.8.6
 */
function bp_admin_setting_callback_default_group_cover_type() {
	?>
	<div class="avatar-custom-input">
		<input id="bp-default-group-cover-default" name="bp-default-group-cover-type" type="radio" value="buddyboss" <?php checked( bb_get_default_group_cover_type(), 'buddyboss' ); ?> />
		<label for="bp-default-group-cover-default">
			<div class="img-block">
				<img class="buddyboss-group-cover" src="<?php echo esc_url( buddypress()->plugin_url . 'bp-core/images/bb-cover-buddyboss.jpg' ); ?>" />
			</div>
			<span><?php esc_html_e( 'BuddyBoss', 'buddyboss' ); ?></span>
		</label>
	</div>

	<div class="avatar-custom-input">
		<input id="bp-default-group-cover-none" name="bp-default-group-cover-type" type="radio" value="none" <?php checked( bb_get_default_group_cover_type(), 'none' ); ?> />
		<label for="bp-default-group-cover-none">
			<div class="img-block">
				<img src="<?php echo esc_url( buddypress()->plugin_url . 'bp-core/images/bb-cover-none.jpg' ); ?>" />
			</div>
			<span><?php esc_html_e( 'None', 'buddyboss' ); ?></span>
		</label>
	</div>

	<div class="avatar-custom-input">
		<input id="bp-default-group-cover-custom" name="bp-default-group-cover-type" type="radio" value="custom" <?php checked( bb_get_default_group_cover_type(), 'custom' ); ?> />
		<label for="bp-default-group-cover-custom">
			<div class="img-block">
				<img src="<?php echo esc_url( buddypress()->plugin_url . 'bp-core/images/bb-cover-custom.jpg' ); ?>" />
			</div>
			<span><?php esc_html_e( 'Custom', 'buddyboss' ); ?></span>
		</label>
	</div>
	<?php
}

/**
 * Allow admin to upload custom default group cover.
 *
 * @since BuddyBoss 1.8.6
 */
function bp_admin_setting_callback_default_group_custom_cover() {
	$cover_dimensions = bb_attachments_get_default_custom_cover_image_dimensions();

	$hide_show_style       = '';
	$placeholder_cover_url = buddypress()->plugin_url . 'bp-core/images/bb-cover-placeholder.jpg';
	$group_cover_image     = bb_get_default_custom_upload_group_cover();

	if ( empty( $group_cover_image ) ) {
		$group_cover_image = $placeholder_cover_url;
		$hide_show_style   = 'bp-hide';
	}
	?>
	<div class="bb-default-custom-upload-file custom-group-avatar cover-uploader custom-profile-group-cover">
		<div class="bb-upload-container">
			<img src="<?php echo esc_url( $group_cover_image ); ?>" data-default="<?php echo esc_url( $placeholder_cover_url ); ?>" class="bb-upload-preview <?php echo esc_attr( $hide_show_style ); ?>">
			<input type="hidden" name="bp-default-custom-group-cover" id="bp-default-custom-group-cover" value="<?php echo esc_url( bb_get_default_custom_upload_group_cover() ); ?>">
		</div>
		<div class="bb-img-button-wrap">
			<label class="cover-uploader-label">
				<input type="file" name="default-group-cover-file" id="default-group-cover-file" class="bb-setting-profile button cover-uploader-hide" accept="image/*">
				<a href="#" class="button button-large bb-img-upload-button" data-uploading="<?php esc_html_e( 'Uploading...', 'buddyboss' ); ?>" data-upload="<?php esc_html_e( 'Upload', 'buddyboss' ); ?>"><?php esc_html_e( 'Upload', 'buddyboss' ); ?></a>
			</label>
			<a href="#" class="delete button button-large bb-img-remove-button <?php echo esc_attr( $hide_show_style ); ?>" data-removing="<?php esc_html_e( 'Removing...', 'buddyboss' ); ?>" data-remove="<?php esc_html_e( 'Remove', 'buddyboss' ); ?>"><?php esc_html_e( 'Remove', 'buddyboss' ); ?></a>
		</div>
		<div class="bp-messages-feedback admin-notice bp-cover-image-status bb-custom-profile-group-cover-feedback" style="display: none;">
			<div class="bp-feedback">
				<span class="bp-icon" aria-hidden="true"></span>
				<p id="bp-cover-image-feedback"></p>
			</div>
		</div>
	</div>
	<p class="description">
		<?php
		echo sprintf(
			/* translators: 1: Cover image width in pixels. 2: Cover image height in pixels */
			esc_html__( 'Upload an image to be used as the default group cover image. Recommended size is %1$spx by %2$spx.', 'buddyboss' ),
			(int) $cover_dimensions['width'],
			(int) $cover_dimensions['height']
		);
		?>
	</p>
	<?php
}

/**
 * Preview based on profile images settings.
 *
 * @since BuddyBoss 1.8.6
 */
function bp_admin_setting_callback_preview_group_avatar_cover() {
	$live_preview_settings = bb_get_settings_live_preview_default_profile_group_images();
	$avatar                = bb_attachments_get_default_profile_group_avatar_image(
		array(
			'object' => 'group',
		)
	);

	$web_cover_preview = buddypress()->plugin_url . 'bp-core/images/cover-image.png';
	$app_cover_preview = buddypress()->plugin_url . 'bp-core/images/cover-image.png';

	if ( 'buddyboss' !== bb_get_default_group_cover_type() ) {
		$web_cover_preview = bb_attachments_get_default_profile_group_cover_image( 'groups' );
		$app_cover_preview = $web_cover_preview;
	}
	?>
	<div class="preview_avatar_cover has-avatar has-cover">

		<div class="preview-switcher-main">

			<div class="button-group preview-switcher">
				<?php if ( $live_preview_settings['is_buddyboss_app_plugin_active'] ) : ?>
					<a href="#web-preview" class="button button-large button-primary"><?php esc_html_e( 'Browser', 'buddyboss' ); ?></a>
					<a href="#app-preview" class="button button-large"><?php esc_html_e( 'App', 'buddyboss' ); ?></a>
				<?php endif; ?>
			</div>

			<div class="web-preview-wrap preview-block active" id="web-preview">
				<div class="preview-item-cover <?php echo esc_attr( bb_get_profile_cover_image_height() . '-image' ); ?>" style="background-color: <?php echo esc_attr( $live_preview_settings['web_background_color'] ); ?>">
					<img src="<?php echo esc_url( $web_cover_preview ); ?>" alt="" data-buddyboss-cover="<?php echo esc_url( buddypress()->plugin_url . 'bp-core/images/cover-image.png' ); ?>">
				</div>
				<div class="preview-item-avatar <?php echo esc_attr( bb_platform_group_header_style() . '-image' ); ?>">
					<img src="<?php echo esc_url( $avatar ); ?>" alt="" class="group-custom-avatar" data-blank-avatar="<?php echo esc_url( bb_get_blank_profile_avatar() ); ?>">
				</div>
			</div>

			<?php if ( $live_preview_settings['is_buddyboss_app_plugin_active'] ) : ?>
				<div class="app-preview-wrap preview-block" id="app-preview">
					<div class="preview-item-cover" style="background-color: <?php echo esc_attr( $live_preview_settings['app_background_color'] ); ?>">
						<img src="<?php echo esc_url( $app_cover_preview ); ?>" alt="" data-buddyboss-cover="<?php echo esc_url( buddypress()->plugin_url . 'bp-core/images/cover-image.png' ); ?>">
					</div>
					<div class="preview-item-avatar">
						<img src="<?php echo esc_url( $avatar ); ?>" alt="" class="group-custom-avatar" data-blank-avatar="<?php echo esc_url( bb_get_blank_profile_avatar() ); ?>">
					</div>
				</div>
			<?php endif; ?>

		</div>

	</div>
	<p class="description"><?php echo wp_kses_post( $live_preview_settings['info'] ); ?></p>
	<?php
}

/**
 * Default group cover sizes settings.
 *
 * @since BuddyBoss 1.9.1
 */
function bb_admin_setting_callback_default_group_cover_size() {

	?>
	<div class="image-width-height">
		<?php
			new BB_Admin_Setting_Fields(
				array(
					'type'        => 'select',
					'id'          => 'bb-cover-group-width',
					'label'       => esc_html__( 'Width', 'buddyboss' ),
					'description' => esc_html__( 'Select the width of group cover images in group headers.', 'buddyboss' ),
					'value'       => bb_get_group_cover_image_width(),
					'disabled'    => true,
					'options'     => array(
						'default' => esc_html__( 'Default', 'buddyboss' ),
						'full'    => esc_html__( 'Full Width', 'buddyboss' ),
					),
				)
			);
		?>
	</div>
	<div class="image-width-height">
		<?php
			new BB_Admin_Setting_Fields(
				array(
					'type'        => 'select',
					'id'          => 'bb-cover-group-height',
					'label'       => esc_html__( 'Height', 'buddyboss' ),
					'description' => esc_html__( 'Select the height of group cover images in group headers and directories.', 'buddyboss' ),
					'value'       => bb_get_group_cover_image_height(),
					'disabled'    => true,
					'options'     => array(
						'small' => esc_html__( 'Small', 'buddyboss' ),
						'large' => esc_html__( 'Large', 'buddyboss' ),
					),
				)
			);
		?>
	</div>
	<p class="description"><?php esc_html_e( 'Changing your size of your cover images will reposition cover images already uploaded by your members', 'buddyboss' ); ?></p>
	<?php
}

/**
 * Link to Profile Headers tutorial
 *
 * @since BuddyBoss 1.9.1
 */
function bb_profile_headers_tutorial() {
	?>
	<p>
		<a class="button" href="
			<?php
			echo esc_url(
				bp_get_admin_url(
					add_query_arg(
						array(
							'page'    => 'bp-help',
							'article' => 125303,
						),
						'admin.php'
					)
				)
			);
			?>
		"><?php esc_html_e( 'View Tutorial', 'buddyboss' ); ?></a>
	</p>
	<?php
}

/**
 * Profile headers style options.
 *
 * @since BuddyBoss 1.9.1
 */
function bb_admin_setting_profile_headers_style() {
	?>
	<div class="bb-grid-style-outer">
		<?php
		new BB_Admin_Setting_Fields(
			array(
				'type'        => 'radio',
				'id'          => 'bb-profile-headers-layout-style',
				'label'       => esc_html__( 'Header Style', 'buddyboss' ),
				'disabled'    => true,
				'opt_wrapper' => true,
				'value'       => bb_get_profile_header_layout_style(),
				'options'     => array(
					'left'     => array(
						'label' => is_rtl() ? 'Right' : 'Left',
						'class' => 'option opt-left',
					),
					'centered' => array(
						'label' => esc_html__( 'Centered', 'buddyboss' ),
						'class' => 'option opt-centered',
					),
				),
			)
		);
		?>
	</div>
	<p class="description"><?php esc_html_e( 'Select the style of your profile headers. Profile cover images will only be displayed if they are enabled.', 'buddyboss' ); ?></p>
	<?php
}

/**
 * Allow Platform default profile header element setting field
 *
 * @since BuddyBoss 1.9.1
 *
 * @param array $args Field options.
 */
function bb_admin_setting_profile_header_elements( $args ) {
	?>
	<div class='bb-profile-header-elements'>
		<?php
		if ( isset( $args['elements'] ) && ! empty( $args['elements'] ) ) {
			foreach ( $args['elements'] as $element ) {
				?>
				<div class="bb-profile-header-element bb-profile-header-element-<?php echo esc_attr( $element['element_name'] ); ?>">
					<?php
					new BB_Admin_Setting_Fields(
						array(
							'type'     => 'checkbox',
							'id'       => 'bb-profile-headers-layout-elements-' . $element['element_name'],
							'label'    => $element['element_label'],
							'disabled' => true,
							'selected' => bb_enabled_profile_header_layout_element( $element['element_name'] ) ? $element['element_name'] : '',
							'value'    => $element['element_name'],
						)
					);
					?>
				</div>
				<?php
			}
		}
		?>
	</div>
	<p class="description"><?php esc_html_e( 'Select which elements to show in your member directories.', 'buddyboss' ); ?></p>
	<?php
}

/**
 * Member directory elements options.
 *
 * @since BuddyBoss 1.9.1
 *
 * @param array $args The array contains extra information of field.
 */
function bb_admin_setting_member_directory_elements( $args ) {
	?>
	<div class='bb-member-directory-elements'>
		<?php
		if ( isset( $args['elements'] ) && ! empty( $args['elements'] ) ) {
			foreach ( $args['elements'] as $element ) {
				?>
				<div class="bb-member-directory-element bb-member-directory-element-<?php echo esc_attr( $element['element_name'] ); ?>">
					<?php
					new BB_Admin_Setting_Fields(
						array(
							'type'     => 'checkbox',
							'id'       => 'bb-member-directory-element-' . $element['element_name'],
							'label'    => $element['element_label'],
							'disabled' => true,
							'selected' => function_exists( 'bb_enabled_member_directory_element' ) ? bb_enabled_member_directory_element( $element['element_name'] ) ? $element['element_name'] : '' : $element['element_name'],
							'value'    => $element['element_name'],
						)
					);
					?>
				</div>
				<?php
			}
		}
		?>
	</div>
	<p class="description"><?php esc_html_e( 'Select which elements to show in your member directories.', 'buddyboss' ); ?></p>
	<?php
}

/**
 * Member directory profile actions options.
 *
 * @since BuddyBoss 1.9.1
 *
 * @param array $args The array contains extra information of field.
 */
function bb_admin_setting_member_profile_actions( $args ) {
	?>
	<div class='bb-member-directory-profile-actions'>
		<?php
		if ( isset( $args['elements'] ) && ! empty( $args['elements'] ) ) {
			foreach ( $args['elements'] as $profile_action ) {
				?>
				<div class="bb-member-directory-profile-action bb-member-directory-profile-action-<?php echo esc_attr( $profile_action['element_name'] ); ?>">
					<?php
					new BB_Admin_Setting_Fields(
						array(
							'type'     => 'checkbox',
							'id'       => 'bb-member-profile-action-' . $profile_action['element_name'],
							'label'    => $profile_action['element_label'],
							'disabled' => true,
							'selected' => function_exists( 'bb_enabled_member_directory_profile_action' ) ? bb_enabled_member_directory_profile_action( $profile_action['element_name'] ) ? $profile_action['element_name'] : '' : $profile_action['element_name'],
							'value'    => $profile_action['element_name'],
						)
					);
					?>
				</div>
				<?php
			}
		}
		?>
	</div>
	<p class="description"><?php esc_html_e( 'Select which profile actions to enable in your member directories.', 'buddyboss' ); ?></p>
	<?php
}

/**
 * Member directory profile primary action options.
 *
 * @since BuddyBoss 1.9.1
 *
 * @param array $args The array contains extra information of field.
 */
function bb_admin_setting_member_profile_primary_action( $args ) {
	?>
	<div class='bb-member-directory-profile-primary-action'>
		<div class="bb-member-directory-primary-action">
			<?php
			$options = array( '' => esc_html__( 'None', 'buddyboss' ) );

			if ( isset( $args['elements'], $args['selected_elements'] ) && ! empty( $args['elements'] ) && ! empty( $args['selected_elements'] ) ) {
				foreach ( $args['elements'] as $profile_primary_action ) {
					if ( in_array( $profile_primary_action['element_name'], $args['selected_elements'], true ) ) {
						$options[ $profile_primary_action['element_name'] ] = $profile_primary_action['element_label'];
					}
				}
			}

			new BB_Admin_Setting_Fields(
				array(
					'type'     => 'select',
					'id'       => 'bb-member-profile-primary-action',
					'value'    => bb_get_member_directory_primary_action(),
					'disabled' => true,
					'options'  => $options,
				)
			);
			?>
		</div>
	</div>
	<p class="description"><?php esc_html_e( 'Select which profile action to show as a primary button. The remaining enabled profile actions will be shown as secondary buttons underneath.', 'buddyboss' ); ?></p>
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
	<label for="bp_restrict_group_creation"><?php esc_html_e( 'Enable social group creation by all members', 'buddyboss' ); ?></label>
	<p class="description" id="bp_group_creation_description"><?php esc_html_e( 'Administrators can always create groups, regardless of this setting.', 'buddyboss' ); ?></p>

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
	<label for="bp-disable-group-avatar-uploads"><?php esc_html_e( 'Enable avatars for groups', 'buddyboss' ); ?></label>
	<p class="description"><?php esc_html_e( 'When enabled, group organizers will be able to upload avatars in the group\'s settings', 'buddyboss' ); ?></p>
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
	<label for="bp-disable-group-cover-image-uploads"><?php esc_html_e( 'Enable cover images for groups', 'buddyboss' ); ?></label>
	<p class="description" id="bp_group_creation_description"><?php esc_html_e( 'When enabled, group organizers will be able to upload cover images in the group\'s settings', 'buddyboss' ); ?></p>
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
		<a class="button" href="
		<?php
			echo esc_url(
				bp_get_admin_url(
					add_query_arg(
						array(
							'page'    => 'bp-help',
							'article' => 62811,
						),
						'admin.php'
					)
				)
			);
		?>
		"><?php esc_html_e( 'View Tutorial', 'buddyboss' ); ?></a>
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
		<label for="bp-disable-group-type-creation"><?php esc_html_e( 'Enable group types to better organize groups', 'buddyboss' ); ?></label>
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
		<label for="bp-enable-group-auto-join"><?php esc_html_e( 'Allow selected profile types to automatically join groups', 'buddyboss' ); ?></label>
		<?php
	}
	?>
	<p class="description"><?php esc_html_e( 'When a member requests to join a group their membership is automatically accepted', 'buddyboss' ); ?></p>
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
		<a class="button" href="
		<?php
			echo esc_url(
				bp_get_admin_url(
					add_query_arg(
						array(
							'page'    => 'bp-help',
							'article' => 62816,
						),
						'admin.php'
					)
				)
			);
		?>
		"><?php esc_html_e( 'View Tutorial', 'buddyboss' ); ?></a>
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
	<label for="bp-enable-group-hierarchies"><?php esc_html_e( 'Allow groups to have subgroups', 'buddyboss' ); ?></label>
	<?php
}

/**
 * 'Hide subgroups from Groups Directory & Group Type Shortcode field markup.
 *
 * @since BuddyBoss 1.5.1
 */
function bp_admin_setting_callback_group_hide_subgroups() {
	?>
	<input id="bp-enable-group-hide-subgroups" name="bp-enable-group-hide-subgroups" type="checkbox" value="1" <?php checked( bp_enable_group_hide_subgroups() ); ?> />
	<label for="bp-enable-group-hide-subgroups"><?php esc_html_e( 'Hide subgroups from Groups Directory & Group Type Shortcode', 'buddyboss' ); ?></label>
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
	<label for="bp-enable-group-restrict-invites"><?php esc_html_e( 'Restrict subgroup invites to members of the parent group', 'buddyboss' ); ?></label>
	<p class="description"><?php esc_html_e( 'Members must first be a member of the parent group prior to being invited to a subgroup', 'buddyboss' ); ?></p>
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
		<a class="button" href="
		<?php
			echo esc_url(
				bp_get_admin_url(
					add_query_arg(
						array(
							'page'    => 'bp-help',
							'article' => 62817,
						),
						'admin.php'
					)
				)
			);
		?>
		"><?php esc_html_e( 'View Tutorial', 'buddyboss' ); ?></a>
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
		<form action="<?php echo esc_url( $form_action ); ?>" method="post" enctype="multipart/form-data">
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
 * Load the BuddyBoss App integration admin screen.
 *
 * @since BuddyBoss 1.0.0
 */
function bp_core_admin_buddyboss_app() {
	?>
		 <div class="wrap">
			<h2 class="nav-tab-wrapper"><?php bp_core_admin_tabs( __( 'BuddyBoss App', 'buddyboss' ) ); ?></h2>
			<?php require buddypress()->plugin_dir . 'bp-core/admin/templates/about-buddyboss-app.php'; ?>
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
	<label for="bp-disable-invite-member-email-subject"><?php esc_html_e( 'Allow members to customize the email subject', 'buddyboss' ); ?></label>
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
	<label for="bp-disable-invite-member-email-content"><?php esc_html_e( 'Allow members to customize the email body content', 'buddyboss' ); ?></label>
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
	<label for="bp-disable-invite-member-type"><?php esc_html_e( 'Allow members to select profile type of invitee', 'buddyboss' ); ?></label>
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

	$post_type     = $args['post_type'];
	$option_name   = bb_post_type_feed_option_name( $post_type );
	$post_type_obj = get_post_type_object( $post_type );

	// Description for the last option of CPT.
	if ( true === $args['description'] && 'post' !== $post_type ) {
		?>
		<p class="description" style="margin-bottom: 10px;"><?php esc_html_e( 'Select which custom post types show in the activity feed when members publish them. For each custom post type, you can select whether or not to show comments in these activity posts (if comments are supported).', 'buddyboss' ); ?></p>
		<?php
	}
	?>
	<input
		class="bp-feed-post-type-checkbox <?php echo 'bp-feed-post-type-' . esc_attr( $post_type ); ?>"
		data-post_type="<?php echo esc_attr( $post_type ); ?>"
		name="<?php echo esc_attr( $option_name ); ?>"
		id="<?php echo esc_attr( $option_name ); ?>"
		type="checkbox"
		value="1"
		<?php checked( bp_is_post_type_feed_enable( $post_type, false ) ); ?>
	/>
	<label for="<?php echo esc_attr( $option_name ); ?>">
		<?php echo 'post' === $post_type ? esc_html__( 'WordPress Posts', 'buddyboss' ) : $post_type_obj->labels->name; ?>
	</label>
	<?php

	// Description for the WordPress Blog Posts
	if ( 'post' === $post_type ) {
		?>
		<p class="description"><?php esc_html_e( 'When members publish new blog posts, show them in the activity feed.', 'buddyboss' ); ?></p>
		<?php
	}
}

/**
 * Allow activity comments on posts and comments.
 *
 * @since BuddyBoss 1.7.2
 *
 * @param array $args Feed settings.
 *
 * @return void
 */
function bb_feed_settings_callback_post_type_comments( $args ) {
	$post_type     = $args['post_type'];
	$option_name   = bb_post_type_feed_comment_option_name( $post_type );
	$post_type_obj = get_post_type_object( $post_type );

	if ( in_array( $post_type, bb_feed_not_allowed_comment_post_types(), true ) ) {
		?>
			<p class="description <?php echo esc_attr( 'bp-feed-post-type-comment-' . $post_type ); ?>">
				<?php
				printf(
				/* translators: %s: comment post type */
					esc_html__( 'Comments are not supported for %s.', 'buddyboss' ),
					esc_html( $post_type_obj->labels->name )
				);
				?>
			</p>
		<?php
		return;
	}
	?>

	<input
		class="bp-feed-post-type-commenet-checkbox <?php echo 'bp-feed-post-type-comment-' . esc_attr( $post_type ); ?>"
		name="<?php echo esc_attr( $option_name ); ?>"
		id="<?php echo esc_attr( $option_name ); ?>"
		type="checkbox"
		value="1"
		<?php checked( bb_is_post_type_feed_comment_enable( $post_type, false ) ); ?>
	/>
	<label for="<?php echo esc_attr( $option_name ); ?>">
		<?php echo 'post' === $post_type ? esc_html__( 'Enable WordPress Post comments in the activity feed', 'buddyboss' ) : sprintf( esc_html__( 'Enable %s comments in the activity feed.', 'buddyboss' ), esc_html( $post_type_obj->labels->name ) ); ?>
	</label>
	<?php

	// Description for the WordPress Blog Posts.
	if ( 'post' === $post_type ) {
		?>
		<p class="description"><?php esc_html_e( 'Allow members to view and create comments to blog posts in the activity feed.', 'buddyboss' ); ?></p>
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
		<label for="<?php echo esc_attr( $option_name ); ?>"><?php echo esc_html( $args['activity_label'] ); ?></label>
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
	<label for="bp-enable-site-registration"><?php esc_html_e( 'Allow non-members to register new accounts', 'buddyboss' ); ?></label>
	<?php
	if ( false === bp_enable_site_registration() && bp_is_active( 'invites' ) ) {
		printf(
			'<p class="description">%s</p>',
			sprintf(
				__(
					'Because <a href="%s">Email Invites</a> is enabled, invited users will still be allowed to register new accounts.',
					'buddyboss'
				),
				esc_url(
					add_query_arg(
						array(
							'page' => 'bp-settings',
							'tab'  => 'bp-invites',
						),
						admin_url( 'admin.php' )
					)
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
		<p class="description" style="margin-bottom: 10px;"><?php esc_html_e( 'Only allow the selected profile types to send invites.', 'buddyboss' ); ?></p>
		<?php
	}
	?>
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
	<label for="bp-enable-profile-gravatar"><?php esc_html_e( 'Allow members to use gravatars for profile avatars', 'buddyboss' ); ?></label>
	<p class="description"><?php _e( 'When enabled, members will be able to use avatars from their <a href="https://gravatar.com/">Gravatar</a> account.', 'buddyboss' ); ?></p>
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
		<a class="button" href="
		<?php
			echo esc_url(
				bp_get_admin_url(
					add_query_arg(
						array(
							'page'    => 'bp-help',
							'article' => 62838,
						),
						'admin.php'
					)
				)
			);
		?>
		"><?php esc_html_e( 'View Tutorial', 'buddyboss' ); ?></a>
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
	<label for="bp-hide-first-name"><?php esc_html_e( 'First Name', 'buddyboss' ); ?></label>

	<br /><br />

	<input id="bp-hide-last-name" name="bp-hide-last-name" type="checkbox" value="1" <?php checked( bp_hide_last_name( true ) ); ?> />
	<label for="bp-hide-last-name"><?php esc_html_e( 'Last Name', 'buddyboss' ); ?> <span class="description"><?php esc_html_e( '(can be disabled)', 'buddyboss' ); ?></span></label>

	<br /><br />

	<input id="bp-hide-nickname" type="checkbox" disabled="disabled" checked="checked" />
	<label for="bp-hide-nickname"><?php esc_html_e( 'Nickname', 'buddyboss' ); ?></label>

	<br /><br />

	<p class="description"><?php esc_html_e( 'If you disable "Last Name" field, it will not appear anywhere in the network.', 'buddyboss' ); ?></p>

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
	<label for="bp-hide-first-name"><?php esc_html_e( 'First Name', 'buddyboss' ); ?></label>

	<br /><br />

	<input id="bp-hide-last-name" type="checkbox" disabled="disabled" checked="checked" />
	<label for="bp-hide-last-name"><?php esc_html_e( 'Last Name', 'buddyboss' ); ?></label>

	<br /><br />

	<input id="bp-hide-nickname" type="checkbox" disabled="disabled" checked="checked" />
	<label for="bp-hide-nickname"><?php esc_html_e( 'Nickname', 'buddyboss' ); ?></label>

	<br /><br />

	<p class="description"><?php esc_html_e( 'All name fields are required with this format. Best used for professional networks.', 'buddyboss' ); ?></p>

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
		<label for="bp-hide-nickname-first-name"><?php esc_html_e( 'First Name', 'buddyboss' ); ?> <span class="description"><?php esc_html_e( '(can be disabled)', 'buddyboss' ); ?></label>
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
		<label for="bp-hide-nickname-last-name"><?php esc_html_e( 'Last Name', 'buddyboss' ); ?> <span class="description"><?php esc_html_e( '(can be disabled)', 'buddyboss' ); ?></label>

		<br /><br />

		<input id="bp-hide-nickname" type="checkbox" disabled="disabled" checked="checked" />
		<label for="bp-hide-nickname"><?php esc_html_e( 'Nickname', 'buddyboss' ); ?></label>

		<br /><br />

		<p class="description"><?php esc_html_e( 'If you disable "First Name" and "Last Name" fields, they will not appear anywhere in the network. This allows your members to be fully anonymous (if they use a pseudonym for their nickname).', 'buddyboss' ); ?></p></p>
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
		<a class="button" href="
		<?php
			echo esc_url(
				bp_get_admin_url(
					add_query_arg(
						array(
							'page'    => 'bp-help',
							'article' => 72340,
						),
						'admin.php'
					)
				)
			);
		?>
		"><?php esc_html_e( 'View Tutorial', 'buddyboss' ); ?></a>
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

/**
 * Admin settings for showing the email confirmation field.
 *
 * @since BuddyBoss 1.1.6
 */
function bp_admin_setting_callback_register_show_confirm_email() {
	?>

	<input id="register-confirm-email" name="register-confirm-email" type="checkbox" value="1" <?php checked( bp_register_confirm_email( false ) ); ?> />
	<label for="register-confirm-email"><?php esc_html_e( 'Add Email confirmation to register form', 'buddyboss' ); ?></label>

	<?php
}

/**
 * Admin settings for showing the legal agreement confirmation field.
 *
 * @since BuddyBoss 1.5.8.3
 */
function bb_admin_setting_callback_register_show_legal_agreement() {
	?>

	<input id="register-legal-agreement" name="register-legal-agreement" type="checkbox" value="1" <?php checked( bb_register_legal_agreement( false ) ); ?> />
	<label for="register-legal-agreement"><?php esc_html_e( 'Add Legal Agreement checkbox to register form', 'buddyboss' ); ?></label>
	<?php
		printf(
			'<p class="description">%s</p>',
			esc_html__( 'Require non-members to explicitly agree to your Terms of Service and Privacy Policy before registering.', 'buddyboss' )
		);
}

/**
 * Admin settings for showing the password confirmation field.
 *
 * @since BuddyBoss 1.1.6
 */
function bp_admin_setting_callback_register_show_confirm_password() {
	?>

	<input id="register-confirm-password" name="register-confirm-password" type="checkbox" value="1" <?php checked( bp_register_confirm_password( false ) ); ?> />
	<label for="register-confirm-password"><?php esc_html_e( 'Add Password confirmation to register form', 'buddyboss' ); ?></label>

	<?php
}

/**
 * Admin Settings for Settings > Groups > Group Directories
 *
 * @since BuddyBoss 1.2.0
 */
function bp_admin_setting_callback_group_layout_type_format() {
	$options = array(
		'list_grid' => __( 'Grid and List', 'buddyboss' ),
		'grid'      => __( 'Grid', 'buddyboss' ),
		'list'      => __( 'List', 'buddyboss' ),
	);

	$current_value = bp_get_option( 'bp-group-layout-format' );

	printf( '<select name="%1$s" for="%1$s">', 'bp-group-layout-format' );
	foreach ( $options as $key => $value ) {
		printf(
			'<option value="%s" %s>%s</option>',
			$key,
			$key == $current_value ? 'selected' : '',
			$value
		);
	}
	printf( '</select>' );

	?>
	<p class="description"><?php esc_html_e( 'Display group directories in Grid View, List View, or allow toggling between both views.', 'buddyboss' ); ?></p>
	<?php
}

/**
 * Admin Settings for Settings > Groups > Group Directories > Default Format
 *
 * @since BuddyBoss 1.2.0
 */
function bp_admin_setting_group_layout_default_option() {
	$selected = bp_group_layout_default_format( 'grid' );

	$options = array(
		'grid' => __( 'Grid', 'buddyboss' ),
		'list' => __( 'List', 'buddyboss' ),
	);

	printf( '<select name="%1$s" for="%1$s">', 'bp-group-layout-default-format' );
	foreach ( $options as $key => $value ) {
		printf(
			'<option value="%s" %s>%s</option>',
			$key,
			$key == $selected ? 'selected' : '',
			esc_attr( $value )
		);
	}
	printf( '</select>' );

}

/**
 * Admin Settings for Settings > Groups > Group Headers > Header style
 *
 * @since BuddyBoss 1.9.1
 */
function bb_admin_setting_group_header_style() {
	?>
	<div class="bb-header-style-outer">
		<?php
		new BB_Admin_Setting_Fields(
			array(
				'type'        => 'radio',
				'id'          => 'bb-group-header-style-',
				'label'       => esc_html__( 'Header Style', 'buddyboss' ),
				'disabled'    => true,
				'opt_wrapper' => true,
				'value'       => 'left',
				'options'     => array(
					'left'     => array(
						'label' => is_rtl() ? esc_html__( 'Right', 'buddyboss' ) : esc_html__( 'Left', 'buddyboss' ),
						'class' => 'option opt-left',
					),
					'centered' => array(
						'label' => esc_html__( 'Centered', 'buddyboss' ),
						'class' => 'option opt-centered',
					),
				),
			)
		);
		?>
	</div>
	<p class="description"><?php echo esc_html__( 'Select the style of your group headers. Group avatars and cover images will only be displayed if they are enabled.', 'buddyboss' ); ?></p>
	<?php
}

/**
 * Allow Platform default group header elements setting field
 *
 * @since BuddyBoss 1.9.1
 *
 * @param array $args Field options.
 */
function bb_admin_setting_group_headers_elements( $args ) {

	echo "<div class='bb-group-headers-elements'>";
	if ( isset( $args['elements'] ) && ! empty( $args['elements'] ) ) {
		foreach ( $args['elements'] as $element ) {
			$element_name = $element['element_name'];
			?>
			<div class="bb-group-headers-element bb-group-headers-element-<?php echo esc_attr( $element_name ); ?>">
				<?php
				new BB_Admin_Setting_Fields(
					array(
						'type'     => 'checkbox',
						'id'       => 'bb-group-headers-element-' . $element_name,
						'label'    => $element['element_label'],
						'disabled' => true,
						'value'    => $element_name,
						'selected' => $element_name,
					)
				);
				?>
			</div>
			<?php
		}
	}
	echo '</div>' .
	'<p class="description">' .
		esc_html__( 'Select which elements to show in your group headers.', 'buddyboss' ) .
	'</p>';

}

/**
 * Admin Settings for Settings > Groups > Group Directories > Grid style
 *
 * @since BuddyBoss 1.9.1
 */
function bb_admin_setting_group_grid_style() {
	?>
	<div class="bb-grid-style-outer">
		<?php
		new BB_Admin_Setting_Fields(
			array(
				'type'        => 'radio',
				'id'          => 'bb-group-directory-layout-grid-style-',
				'label'       => esc_html__( 'Grid Style', 'buddyboss' ),
				'disabled'    => true,
				'opt_wrapper' => true,
				'value'       => 'left',
				'options'     => array(
					'left'     => array(
						'label' => is_rtl() ? esc_html__( 'Right', 'buddyboss' ) : esc_html__( 'Left', 'buddyboss' ),
						'class' => 'option opt-left',
					),
					'centered' => array(
						'label' => esc_html__( 'Centered', 'buddyboss' ),
						'class' => 'option opt-centered',
					),
				),
			)
		);
		?>
	</div>
	<p class="description"><?php echo esc_html__( 'Select the style of the of grid layouts. Group avatars and cover images will only be displayed if they are enabled.', 'buddyboss' ); ?></p>
	<?php
}

/**
 * Allow Platform default group element setting field
 *
 * @since BuddyBoss 1.9.1
 *
 * @param array $args Field options.
 */
function bb_admin_setting_group_elements( $args ) {

	echo "<div class='bb-group-elements'>";
	if ( isset( $args['elements'] ) && ! empty( $args['elements'] ) ) {
		foreach ( $args['elements'] as $element ) {
			$element_name = $element['element_name'];
			?>
			<div class="bb-group-element bb-group-element-<?php echo esc_attr( $element_name ); ?>">
				<?php
				new BB_Admin_Setting_Fields(
					array(
						'type'     => 'checkbox',
						'id'       => 'bb-group-directory-layout-element-' . $element_name,
						'label'    => $element['element_label'],
						'disabled' => true,
						'value'    => $element_name,
						'selected' => $element_name,
					)
				);
				?>
			</div>
			<?php
		}
	}
	echo '</div>' .
	'<p class="description">' .
		esc_html__( 'Select which elements show in your group directories. Cover images will only display in grid view and group descriptions will only display in list view.', 'buddyboss' ) .
	'</p>';
}

/**
 * Link to Group Directories tutorial
 *
 * @since BuddyBoss 1.2.0
 */
function bp_group_directories_tutorial() {
	?>
	<p>
		<a class="button" href="
		<?php
			echo esc_url(
				bp_get_admin_url(
					add_query_arg(
						array(
							'page'    => 'bp-help',
							'article' => '125311',
						),
						'admin.php'
					)
				)
			);
		?>
		"><?php esc_html_e( 'View Tutorial', 'buddyboss' ); ?></a>
	</p>
	<?php
}

/**
 * Admin settings for showing the allow custom registration checkbox.
 *
 * @since BuddyBoss 1.2.8
 */
function bp_admin_setting_callback_register_allow_custom_registration() {

	$allow_custom_registration = bp_allow_custom_registration();
	?>

	<select name="allow-custom-registration" id="allow-custom-registration">
		<option value="0" <?php selected( 0, $allow_custom_registration ); ?>><?php esc_html_e( 'BuddyBoss Registration', 'buddyboss' ); ?></option>
		<option value="1" <?php selected( 1, $allow_custom_registration ); ?>><?php esc_html_e( 'Custom URL', 'buddyboss' ); ?></option>
	</select>
	<?php
	if ( ! $allow_custom_registration ) {
		printf(
			'<p class="description">%s</p>',
			sprintf(
				__(
					'Use the default BuddyBoss registration form. Make sure to configure the <a href="%s">registration pages</a>.',
					'buddyboss'
				),
				esc_url(
					add_query_arg(
						array(
							'page' => 'bp-pages',
						),
						admin_url( 'admin.php' )
					)
				)
			)
		);
	}
}

/**
 * Admin settings for showing the allow custom registration checkbox.
 *
 * @since BuddyBoss 1.2.8
 */
function bp_admin_setting_callback_register_page_url() {
	?>
	<input style="width: 89%;" id="register-page-url" name="register-page-url" type="text" value="<?php echo esc_url( bp_custom_register_page_url() ); ?>" />
	<?php
	printf(
		'<p class="description">%s</p>',
		esc_html__( 'Enter a custom URL to redirect users to register to your site. Useful for membership plugins.', 'buddyboss' )
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
		<a class="button" href="
		<?php
			echo esc_url(
				bp_get_admin_url(
					add_query_arg(
						array(
							'page'    => 'bp-help',
							'article' => 86158,
						),
						'admin.php'
					)
				)
			);
		?>
		"><?php esc_html_e( 'View Tutorial', 'buddyboss' ); ?></a>
	</p>
	<?php
}

/**
 * Enable group messages field markup.
 *
 * @since BuddyBoss 1.2.9
 */
function bp_admin_setting_callback_group_messages() {
	?>
	<input id="bp-disable-group-messages" name="bp-disable-group-messages" type="checkbox" value="1" <?php checked( bp_disable_group_messages() ); ?> />
	<label for="bp-disable-group-messages"><?php esc_html_e( 'Allow for sending group messages to group members', 'buddyboss' ); ?></label>
	<?php
}

/**
 * Link to Moderation Block tutorial
 *
 * @since BuddyBoss 1.5.6
 */
function bp_admin_moderation_block_setting_tutorial() {
	?>
	<p>
		<a class="button" href="
		<?php
			echo esc_url(
				bp_get_admin_url(
					add_query_arg(
						array(
							'page'    => 'bp-help',
							'article' => 121711,
						),
						'admin.php'
					)
				)
			);
		?>
		"><?php esc_html_e( 'View Tutorial', 'buddyboss' ); ?></a>
	</p>
	<?php
}

/**
 * Link to Moderation Report tutorial
 *
 * @since BuddyBoss 1.5.6
 */
function bp_admin_moderation_report_setting_tutorial() {
	?>
	<p>
		<a class="button" href="
		<?php
			echo esc_url(
				bp_get_admin_url(
					add_query_arg(
						array(
							'page'    => 'bp-help',
							'article' => 121712,
						),
						'admin.php'
					)
				)
			);
		?>
		"><?php esc_html_e( 'View Tutorial', 'buddyboss' ); ?></a>
	</p>
	<?php
}

/**
 * Enable on-screen notification.
 *
 * @since BuddyBoss 1.7.0
 *
 * @return void
 */
function bb_admin_setting_callback_on_screen_notifications_enable() {
	?>
	<input id="_bp_on_screen_notifications_enable" name="_bp_on_screen_notifications_enable" type="checkbox" value="1" <?php checked( bp_get_option( '_bp_on_screen_notifications_enable', 0 ) ); ?> />
	<label for="_bp_on_screen_notifications_enable"><?php esc_html_e( 'Enable on-screen notifications', 'buddyboss' ); ?></label>
	<p class="description"><?php esc_html_e( 'Show members new notifications received while on a page on-screen.', 'buddyboss' ); ?></p>
	<?php
}

/**
 * Define on-screen notification position.
 *
 * @since BuddyBoss 1.7.0
 *
 * @return void
 */
function bb_admin_setting_callback_on_screen_notifications_position() {
	?>
	<div class="bb-screen-position-outer">
		<div class="bb-screen-position bb-bottom-left">
			<input id="bp_on_screen_notifications_position_left" name="_bp_on_screen_notifications_position" type="radio" value="left" <?php checked( 'left' === bp_get_option( '_bp_on_screen_notifications_position', '' ) ? true : false ); ?> />
			<label class="option opt-left" for="bp_on_screen_notifications_position_left">
				<span>
					<?php esc_html_e( 'Bottom Left', 'buddyboss' ); ?>
				</span>
			</label>
		</div>
		<div class="bb-screen-position bb-bottom-right">
			<input id="bp_on_screen_notifications_position_right" name="_bp_on_screen_notifications_position" type="radio" value="right" <?php checked( 'right' === bp_get_option( '_bp_on_screen_notifications_position', '' ) ? true : false ); ?> />
			<label class="option opt-right" for="bp_on_screen_notifications_position_right">
				<span>
					<?php esc_html_e( 'Bottom Right', 'buddyboss' ); ?>
				</span>
			</label>
		</div>
	</div>
	<?php
}

/**
 * Enable on-screen notification mobile support.
 *
 * @since BuddyBoss 1.7.0
 *
 * @return void
 */
function bb_admin_setting_callback_on_screen_notifications_mobile_support() {
	?>
	<input id="_bp_on_screen_notifications_mobile_support" name="_bp_on_screen_notifications_mobile_support" type="checkbox" value="1" <?php checked( bp_get_option( '_bp_on_screen_notifications_mobile_support', 0 ) ); ?> />
	<label for="_bp_on_screen_notifications_mobile_support"><?php esc_html_e( 'Show on-screen notifications on small screens', 'buddyboss' ); ?></label>
	<p class="description"><?php esc_html_e( 'Enable this option to show on-screen notifications at the bottom of the screen smaller than 500px wide.', 'buddyboss' ); ?></p>
	<?php
}

/**
 * Enable on-screen notification browser tab.
 *
 * @since BuddyBoss 1.7.0
 *
 * @return void
 */
function bb_admin_setting_callback_on_screen_notifications_browser_tab() {
	?>
	<input id="_bp_on_screen_notifications_browser_tab" name="_bp_on_screen_notifications_browser_tab" type="checkbox" value="1"  <?php checked( bp_get_option( '_bp_on_screen_notifications_browser_tab', 0 ) ); ?> />
	<label for="_bp_on_screen_notifications_browser_tab"><?php esc_html_e( 'Show new notifications in the title of the browser tab', 'buddyboss' ); ?></label>
	<p class="description"><?php esc_html_e( 'Update the page <title> tab when new notifications are received.', 'buddyboss' ); ?></p>
	<?php
}

/**
 * Option value for auto remove on-screen single notification.
 *
 * @since BuddyBoss 1.7.0
 *
 * @return void
 */
function bb_admin_setting_callback_on_screen_notifications_visibility() {

	// Options for remove single notification time.
	$options = array(
		'never' => __( 'Never Hide', 'buddyboss' ),
		'5'     => __( '5 Seconds', 'buddyboss' ),
		'10'    => __( '10 Seconds', 'buddyboss' ),
		'30'    => __( '30 Seconds', 'buddyboss' ),
		'60'    => __( '1 Minute', 'buddyboss' ),
		'120'   => __( '2 Minutes', 'buddyboss' ),
		'180'   => __( '3 Minutes', 'buddyboss' ),
		'240'   => __( '4 Minutes', 'buddyboss' ),
		'300'   => __( '5 Minutes', 'buddyboss' ),
	);
	?>

	<select name="_bp_on_screen_notifications_visibility" id="_bp_on_screen_notifications_visibility" >

		<?php foreach ( $options as $option_id => $label ) : ?>

			<option  value="<?php echo esc_attr( $option_id ); ?>" <?php selected( esc_attr( $option_id ) === bp_get_option( '_bp_on_screen_notifications_visibility', '' ) ? true : false ); ?>>
				<?php echo esc_html( $label ); ?>
			</option>

		<?php endforeach; ?>

	</select>

	<?php
}

/**
 * Link to Moderation Report tutorial
 *
 * @since BuddyBoss 1.5.6
 */
function bp_admin_on_screen_notification_setting_tutorial() {
	?>
	<p>
		<a class="button" href="
		<?php
			echo esc_url(
				bp_get_admin_url(
					add_query_arg(
						array(
							'page'    => 'bp-help',
							'article' => 124801,
						),
						'admin.php'
					)
				)
			);
		?>
		"><?php esc_html_e( 'View Tutorial', 'buddyboss' ); ?></a>
	</p>
	<?php
}

/**
 * After update activity setting
 *
 * @since BuddyBoss 1.7.2
 *
 * @param string $tab_name  Settings tab name.
 *
 * @uses bb_feed_post_types()                    Get all post type name.
 * @uses bb_post_type_feed_option_name()         Settings option name for post type.
 * @uses bb_post_type_feed_comment_option_name() Settings option name for post type comment.
 *
 * @return void
 */
function bb_after_update_activity_settings( $tab_name ) {
	if ( 'bp-activity' !== $tab_name ) {
		return;
	}

	foreach ( bb_feed_post_types() as $key => $post_type ) {
		// Post type option name.
		$pt_opt_name = bb_post_type_feed_option_name( $post_type );

		// Post type comment option name.
		$ptc_opt_name = bb_post_type_feed_comment_option_name( $post_type );

		// Get the post type activity status.
		$opt_value = bp_get_option( $pt_opt_name, '' );

		// If the post type activity disable then its comment also make disable.
		if ( empty( $opt_value ) ) {
			bp_update_option( $ptc_opt_name, 0 );
		}
	}
}
add_action( 'bp_admin_tab_setting_save', 'bb_after_update_activity_settings', 10, 1 );

/**
 * Allow admin to make the REST APIs private.
 *
 * @since BuddyBoss 1.8.6
 */
function bb_admin_setting_callback_private_rest_apis() {
	$disable_field    = false;
	$checked_checkbox = bp_enable_private_rest_apis();
	if ( function_exists( 'bbapp_is_private_app_enabled' ) ) {
		if ( true === bbapp_is_private_app_enabled() ) {
			$disable_field = false;
		} else {
			$disable_field    = true;
			$checked_checkbox = false;
		}
	}
	?>

	<input id="bb-enable-private-rest-apis" name="bb-enable-private-rest-apis" type="checkbox" value="1" <?php checked( $checked_checkbox ); disabled( $disable_field ); ?>/>
	<label for="bb-enable-private-rest-apis"><?php esc_html_e( 'Restrict REST API access to only logged-in members', 'buddyboss' ); ?></label>
	<p class="description">
		<?php
		printf(
			wp_kses_post(
			/* translators: Registration link. */
				__( 'Login and %s APIs will remain publicly visible.', 'buddyboss' )
			),
			sprintf(
				'<a href="%s">' . esc_html__( 'Registration', 'buddyboss' ) . '</a>',
				esc_url(
					add_query_arg(
						array( 'page' => 'bp-pages' ),
						admin_url( 'admin.php' )
					)
				)
			)
		);
		?>
	</p>
	<?php
	if ( function_exists( 'bbapp_is_private_app_enabled' ) && false === bbapp_is_private_app_enabled() ) {
		?>
		<div class="bp-feedback info">
			<span class="bp-icon" aria-hidden="true"></span>
			<p>
				<?php
				printf(
					wp_kses_post(
					/* translators: Settings link. */
						__( 'Your BuddyBoss App is currently public. To restrict access to REST APIs for logged-out members, please enable "Private App" in the %s.', 'buddyboss' )
					),
					sprintf(
						'<a href="%s">' . esc_html__( 'BuddyBoss App\'s settings', 'buddyboss' ) . '</a>',
						esc_url(
							add_query_arg(
								array( 'page' => 'bbapp-settings' ),
								admin_url( 'admin.php' )
							)
						)
					)
				);
				?>
			</p>
		</div>
		<?php
	}
}

/**
 * Allow admin to exclude REST APIs endpoint.
 *
 * @since BuddyBoss 1.8.6
 */
function bb_admin_setting_callback_private_rest_apis_public_content() {
	$disable_field = false;
	if ( function_exists( 'bbapp_is_private_app_enabled' ) && false === bbapp_is_private_app_enabled() ) {
		$disable_field = true;
	}
	?>

	<label for="bb-enable-private-rest-apis-public-content" style="display:block;"><?php esc_html_e( 'Enter REST API endpoint URLs or URI fragments (e.g. wp-json/wp/v2/pages/&lt;id&gt;) to remain publicly visible always. Enter one URL or URI per line.', 'buddyboss' ); ?></label>
	<textarea rows="10" cols="100" id="bb-enable-private-rest-apis-public-content" name="bb-enable-private-rest-apis-public-content" style="margin-top: 10px;" <?php disabled( $disable_field ); ?>><?php echo esc_textarea( bb_enable_private_rest_apis_public_content() ); ?></textarea>
	<?php
}

/**
 * Allow admin to make the RSS feeds private.
 *
 * @since BuddyBoss 1.8.6
 */
function bb_admin_setting_callback_private_rss_feeds() {
	?>

	<input id="bb-enable-private-rss-feeds" name="bb-enable-private-rss-feeds" type="checkbox" value="1" <?php checked( bp_enable_private_rss_feeds() ); ?>/>
	<label for="bb-enable-private-rss-feeds"><?php esc_html_e( 'Restrict RSS feed access to only logged-in members', 'buddyboss' ); ?></label>
	<?php
}

/**
 * Allow admin to exclude RSS feeds endpoint.
 *
 * @since BuddyBoss 1.8.6
 */
function bb_admin_setting_callback_private_rss_feeds_public_content() {
	?>

	<label for="bb-enable-private-rss-feeds-public-content" style="display:block;"><?php esc_html_e( 'Enter RSS feed URLs or URI fragments (e.g. /post-name/feed/) to remain publicly visible always. Enter one URL or URI per line.', 'buddyboss' ); ?></label>
	<textarea rows="10" cols="100" id="bb-enable-private-rss-feeds-public-content" name="bb-enable-private-rss-feeds-public-content" style="margin-top: 10px;"><?php echo esc_textarea( bb_enable_private_rss_feeds_public_content() ); ?></textarea>
	<?php
}

/**
 * Register the labs settings section.
 *
 * @since BuddyBoss 1.9.3
 *
 * @return array
 */
function bb_labs_get_settings_sections() {

	$settings = array(
		'bp_labs_settings' => array(
			'page'     => 'labs',
			'title'    => esc_html__( 'BuddyBoss Labs', 'buddyboss' ),
			'callback' => 'bb_labs_info_section_callback',
		),
	);

	return (array) apply_filters( 'bb_labs_get_settings_sections', $settings );

}

/**
 * Get settings fields by section.
 *
 * @since BuddyBoss 1.9.3
 *
 * @param string $section_id Section id.
 *
 * @return mixed False if section is invalid, array of fields otherwise.
 */
function bb_labs_get_settings_fields_for_section( $section_id = '' ) {

	// Bail if section is empty.
	if ( empty( $section_id ) ) {
		return false;
	}

	$fields = bb_labs_get_settings_fields();
	$retval = isset( $fields[ $section_id ] ) ? $fields[ $section_id ] : false;

	return (array) apply_filters( 'bb_labs_get_settings_fields_for_section', $retval, $section_id );
}

/**
 * Get all the settings fields.
 *
 * @since BuddyBoss 1.9.3
 *
 * @return array
 */
function bb_labs_get_settings_fields() {

	$fields = (array) apply_filters( 'bb_labs_get_settings_fields', array() );

	if ( empty( $fields ) ) {
		$fields['bp_labs_settings'] = array(
			'bb_labs_no_settings_callback' => array(
				'title'    => ' ',
				'callback' => 'bb_labs_no_settings_callback',
				'args'     => array( 'class' => 'notes-hidden-header' ),
			),
		);
	}

	return $fields;
}

/**
 * BuddyBoss Labs settings section callback.
 *
 * @since BuddyBoss 1.9.3
 */
function bb_labs_info_section_callback() {
	?>

	<p>
		<?php
		printf(
			'<p class="description">%s</p>',
			sprintf(
				wp_kses_post(
				/* translators: Support portal. */
					__(
						'BuddyBoss Labs provides early-access to upcoming BuddyBoss features. You can help us prepare these features for official release by reporting issues and providing feedback through the <a href="%s" target="_blank" >support portal</a>.',
						'buddyboss'
					)
				),
				'https://support.buddyboss.com'
			)
		);
		?>
	</p>

	<p>
		<?php
		printf(
			'<p class="description">%s</p>',
			wp_kses_post(
			/* translators: Support portal. */
				__(
					'Please note, customer support will not be able to provide support for these features until their official release.',
					'buddyboss'
				)
			)
		);
		?>
	</p>

	<?php
}

/**
 * Function to show the notice about the no labs features available.
 *
 * @since BuddyBoss 2.1.5.1
 *
 * @return void
 */
function bb_labs_no_settings_callback() {
	printf(
		'<p class="no-field-notice">%s</p><style>.submit{display:none;}</style>',
		wp_kses_post(
		/* translators: Support portal. */
			__(
				'There are currently no BuddyBoss Labs features available.',
				'buddyboss'
			)
		)
	);
}

/**
 * Allow all users to subscribe groups field.
 *
 * @since BuddyBoss 2.2.8
 */
function bb_admin_setting_callback_group_subscriptions() {
	?>
	<input id="bb_enable_group_subscriptions" name="bb_enable_group_subscriptions" type="checkbox" aria-describedby="bp_group_creation_description" value="1" <?php checked( bb_enable_group_subscriptions() ); ?> />
	<label for="bb_enable_group_subscriptions"><?php esc_html_e( 'Allow members to subscribe to groups', 'buddyboss' ); ?></label>
	<p class="description" id="bb_enable_group_subscriptions"><?php esc_html_e( 'When a member is subscribed to a group, they can receive notifications of new activity posts and discussions created in the group.', 'buddyboss' ); ?></p>
	<?php
}

/**
 * Link to profile slug tutorial
 *
 * @since BuddyBoss 2.3.1
 */
function bb_profile_slug_tutorial() {
	?>
	<p>
		<a class="button" href="
		<?php
		echo esc_url(
			bp_get_admin_url(
				add_query_arg(
					array(
						'page'    => 'bp-help',
						'article' => 126235,
					),
					'admin.php'
				)
			)
		);
		?>
		"><?php esc_html_e( 'View Tutorial', 'buddyboss' ); ?></a>
	</p>
	<?php
}

/**
 * Link to registration restrictions tutorial.
 *
 * @since BuddyBoss 2.4.11
 */
function bb_registration_restrictions_tutorial() {
	?>
	<p>
		<a class="button" href="
		<?php
		echo esc_url(
			bp_get_admin_url(
				add_query_arg(
					array(
						'page'    => 'bp-help',
						'article' => 126835,
					),
					'admin.php'
				)
			)
		);
		?>
		"><?php esc_html_e( 'View Tutorial', 'buddyboss' ); ?></a>
	</p>
	<?php
}

/**
 * Allow admin to add blacklist emails and domains.
 *
 * @since  BuddyBoss 2.4.11
 */
function bb_admin_setting_callback_domain_restrictions() {

	$domain_restrictions = bb_domain_restrictions_setting();
	$conditions          = array(
		''             => esc_html__( 'Select Condition', 'buddyboss' ),
		'always_allow' => esc_html__( 'Always Allow', 'buddyboss' ),
		'never_allow'  => esc_html__( 'Never Allow', 'buddyboss' ),
		'only_allow'   => esc_html__( 'Only Allow', 'buddyboss' ),
	);
	?>
	<label for="bb-domain-restrictions-setting">
		<?php
		esc_html_e( 'Add domain(s) to restrict new users from being able to register, you can use a wildcard (*) symbol to apply restrictions to an entire extension.
		When multiple restrictions are in place, a domain will always take priority over an extension.
		', 'buddyboss' );
		?>
	</label>

	<div id="bb-domain-restrictions-setting" class="bb-domain-restrictions-listing registration-restrictions-listing">
		<div class="restrictions-error"></div>
		<div class="registration-restrictions-rule-list bb-sortable">
		<?php
		// Count the occurrences used later to validate.
		$pre_saved_conditions = array(
			'always_allow' => 0,
			'only_allow'   => 0,
		);

		if ( ! empty( $domain_restrictions ) ) {
			foreach ( $domain_restrictions as $key_rule => $rule ) {
				if ( isset( $rule['condition'] ) && isset( $pre_saved_conditions[ $rule['condition'] ] ) ) {
					$pre_saved_conditions[ $rule['condition'] ] += 1;
				}
			}
			foreach ( $domain_restrictions as $key_rule => $rule ) {
				?>
				<div class="registration-restrictions-rule">
					<span class='registration-restrictions-priority' style='display:none;'><?php echo esc_html( $key_rule + 1 ); ?></span>
					<div class="registration-restrictions-input">
						<input type="text" name="bb-domain-restrictions[<?php echo esc_attr( $key_rule ); ?>][domain]" class="registration-restrictions-domain" placeholder="<?php esc_attr_e( 'Domain name', 'buddyboss' ); ?>" value="<?php echo esc_attr( $rule['domain'] ); ?>"/>
					</div>
					<div class="registration-restrictions-input registration-restrictions-input-tld">
						<input type="text" name="bb-domain-restrictions[<?php echo esc_attr( $key_rule ); ?>][tld]" class="registration-restrictions-tld" placeholder="<?php esc_attr_e( 'Extension', 'buddyboss' ); ?>" value="<?php echo esc_attr( $rule['tld'] ); ?>"/>
					</div>
					<div class="registration-restrictions-select">
						<select name="bb-domain-restrictions[<?php echo esc_attr( $key_rule ); ?>][condition]" class="registration-restrictions-input-select">
							<?php
							foreach ( $conditions as $key => $value ) {
								$disabled = false;
								if (
									(
										'always_allow' === $key && $pre_saved_conditions['only_allow'] > 0
									) ||
									(
										'only_allow' === $key && $pre_saved_conditions['always_allow'] > 0
									)
								) {
									$disabled = true;
								}
								?>
								<option value='<?php echo esc_attr( $key ); ?>'
									<?php
									selected( $key === $rule['condition'] );
									disabled( $disabled );
									?>
								>
									<?php echo esc_html( $value ); ?>
								</option>
								<?php
							}
							?>
						</select>
					</div>
					<div class="registration-restrictions-remove">
						<button class="registration-restrictions-rule-remove domain-rule-remove" aria-label="Remove Rule">
							<i class="bb-icon-f bb-icon-times"></i>
						</button>
					</div>
				</div>
				<?php
			}
		}
		?>

			<!-- This below HTML is for clone only - Starts -->
			<div class="custom registration-restrictions-rule" style="display: none;">
				<span class='registration-restrictions-priority' style='display:none;'><?php echo esc_html( empty( $domain_restrictions ) ? 0 : count( $domain_restrictions ) + 1 ); ?></span>
				<div class="registration-restrictions-input">
					<input type="text" name="bb-domain-restrictions[placeholder_priority_index][domain]" class="registration-restrictions-domain" placeholder="<?php esc_attr_e( 'Domain name', 'buddyboss' ); ?>" value="" />
				</div>
				<div class="registration-restrictions-input registration-restrictions-input-tld">
					<input type="text" name="bb-domain-restrictions[placeholder_priority_index][tld]" class="registration-restrictions-tld" placeholder="<?php esc_attr_e( 'Extension', 'buddyboss' ); ?>" value="" />
				</div>
				<div class="registration-restrictions-select">
					<select name="bb-domain-restrictions[placeholder_priority_index][condition]" class="registration-restrictions-input-select">
						<?php
						foreach ( $conditions as $key => $value ) {
							$disabled = false;
							if (
								(
									'always_allow' === $key && $pre_saved_conditions['only_allow'] > 0
								) ||
								(
									'only_allow' === $key && $pre_saved_conditions['always_allow'] > 0
								)
							) {
								$disabled = true;
							}
							?>
							<option value='<?php echo esc_attr( $key ); ?>'
								<?php echo disabled( $disabled ); ?>
							>
								<?php echo esc_html( $value ); ?>
							</option>
							<?php
						}
						?>
					</select>
				</div>
				<div class="registration-restrictions-remove">
					<button class="registration-restrictions-rule-remove domain-rule-remove" aria-label="<?php esc_attr_e( 'Remove Rule', 'buddyboss' ); ?>">
						<i class="bb-icon-f bb-icon-times"></i>
					</button>
				</div>
			</div>
			<!-- This below HTML is for clone only - Ends -->

		</div>
		<input type='hidden' class='registration-restrictions-lastindex' value='<?php echo empty( $domain_restrictions ) ? 0 : count( $domain_restrictions ); ?>' />
		<button class="button registration-restrictions-add-rule domain-rule-add"> <?php esc_html_e( 'Add Domain', 'buddyboss' ); ?></button>
	</div>
	<?php
}

/**
 * Allow admin to add whitelist emails and domains.
 *
 * @since BuddyBoss 2.4.11
 */
function bb_admin_setting_callback_email_restrictions() {

	$email_restrictions = bb_email_restrictions_setting();
	$conditions         = array(
		''             => esc_html__( 'Select Condition', 'buddyboss' ),
		'always_allow' => esc_html__( 'Always Allow', 'buddyboss' ),
		'never_allow'  => esc_html__( 'Never Allow', 'buddyboss' ),
	);
	?>
	<label for="bb-email-restrictions-setting"><?php esc_html_e( 'Enter specific email addresses which you want to allow for user registrations. Enter one address per line.', 'buddyboss' ); ?></label>
	<div id="bb-email-restrictions-setting" class="bb-email-restrictions-listing registration-restrictions-listing">
		<div class="restrictions-error"></div>
		<div class="registration-restrictions-rule-list">
		<?php
		if ( ! empty( $email_restrictions ) ) {
			foreach ( $email_restrictions as $key_rule => $rule ) {
				?>
				<div class="registration-restrictions-rule">
					<div class="registration-restrictions-input">
						<input type="email" name="bb-email-restrictions[<?php echo esc_attr( $key_rule ); ?>][address]" class="registration-restrictions-domain" placeholder="<?php esc_attr_e( 'Email address', 'buddyboss' ); ?>" value="<?php echo esc_attr( $rule['address'] ); ?>"/>
					</div>
					<div class="registration-restrictions-select">
						<select name="bb-email-restrictions[<?php echo esc_attr( $key_rule ); ?>][condition]" class="registration-restrictions-input-select">
							<?php
							foreach ( $conditions as $key => $value ) {
								?>
								<option value='<?php echo esc_attr( $key ); ?>'
									<?php selected( $key === $rule['condition'] ); ?>
								>
									<?php echo esc_html( $value ); ?>
								</option>
								<?php
							}
							?>
						</select>
					</div>
					<div class="registration-restrictions-remove">
						<button class="registration-restrictions-rule-remove email-rule-remove" aria-label="Remove Rule">
							<i class="bb-icon-f bb-icon-times"></i>
						</button>
					</div>
				</div>
				<?php
			}
		}
		?>
			<!-- This below HTML is for clone only - Starts -->
			<div class="custom registration-restrictions-rule" style="display: none;">
				<div class="registration-restrictions-input">
					<input type="email" name="bb-email-restrictions[placeholder_priority_index][address]" class="registration-restrictions-domain" placeholder="<?php esc_attr_e( 'Email address', 'buddyboss' ); ?>" value=""/>
				</div>
				<div class="registration-restrictions-select">
					<select name="bb-email-restrictions[placeholder_priority_index][condition]" class="registration-restrictions-input-select">
						<?php
						foreach ( $conditions as $key => $value ) {
							?>
							<option value='<?php echo esc_attr( $key ); ?>'><?php echo esc_html( $value ); ?></option>
							<?php
						}
						?>
					</select>
				</div>
				<div class="registration-restrictions-remove">
					<button class="registration-restrictions-rule-remove email-rule-remove" aria-label="<?php esc_attr_e( 'Remove Rule', 'buddyboss' ); ?>">
						<i class="bb-icon-f bb-icon-times"></i>
					</button>
				</div>
			</div>
			<!-- This below HTML is for clone only - Ends -->

		</div>
		<input type='hidden' class='registration-restrictions-lastindex' value='<?php echo empty( $email_restrictions ) ? 0 : count( $email_restrictions ); ?>' />
		<button class="button registration-restrictions-add-rule email-rule-add"> <?php esc_html_e( 'Add Email', 'buddyboss' ); ?></button>
	</div>
	<?php
}


/**
 * Callback function for registration restrictions section.
 *
 * @since BuddyBoss 2.4.11
 */
function bb_admin_setting_callback_registration_restrictions_instructions() {
	?>
	<p class='description'><?php esc_html_e( 'Domain restrictions can be configured to limit new user registrations to specific domains or extensions. This setting is only available when using the BuddyBoss Registration Form.', 'buddyboss' ); ?></p>
	<?php
}

/**
 * Get label with buddyboss registration notice if not active for the registration restrictions.
 *
 * @since BuddyBoss 2.4.11
 *
 * @return string $bb_registration_notice Notice content.
 */
function bb_get_buddyboss_registration_notice() {
	static $bb_registration_notice = '';

	if ( '' !== $bb_registration_notice ) {
		return $bb_registration_notice;
	}

	if ( bp_allow_custom_registration() ) {
		$bb_registration_notice = sprintf(
			'<br/><span class="bb-head-notice"> %1$s <a href="#bp_registration"><strong>%2$s</strong></a> %3$s</span>',
			esc_html__( 'Enable the', 'buddyboss' ),
			esc_html__( 'BuddyBoss Registration Form', 'buddyboss' ),
			esc_html__( 'to unlock', 'buddyboss' )
		);
	}

	return $bb_registration_notice;
}

/**
 * Enable activity comment edit.
 *
 * @since BuddyBoss 2.4.40
 */
function bb_admin_setting_callback_enable_activity_comment_edit() {
	$edit_times = bp_activity_edit_times();
	$edit_time  = bb_get_activity_comment_edit_time();
	?>

	<input id="_bb_enable_activity_comment_edit" name="_bb_enable_activity_comment_edit" type="checkbox" value="1" <?php checked( bb_is_activity_comment_edit_enabled( false ) ); ?> />
	<label for="_bb_enable_activity_comment_edit"><?php esc_html_e( 'Allow members to edit their comment for a duration of', 'buddyboss' ); ?></label>

	<select name="_bb_activity_comment_edit_time">
		<option value="-1"><?php esc_html_e( 'Forever', 'buddyboss' ); ?></option>
		<?php
		foreach ( $edit_times as $time ) {
			$value      = isset( $time['value'] ) ? $time['value'] : 0;
			$time_level = isset( $time['label'] ) ? $time['label'] : 0;
			echo '<option value="' . esc_attr( $value ) . '" ' . selected( $edit_time, $value, false ) . '>' . esc_html( $time_level ) . '</option>';
		}
		?>
	</select>

	<?php
}

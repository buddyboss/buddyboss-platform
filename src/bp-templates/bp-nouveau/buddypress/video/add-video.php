<?php
/**
 * BuddyBoss - Add Video
 *
 * This template is used to show the add video form.
 *
 * This template can be overridden by copying it to yourtheme/buddypress/video/add-video.php.
 *
 * @package BuddyBoss\Core
 *
 * @since   BuddyBoss 1.7.0
 * @version 1.7.0
 */

if ( ( ( bp_is_my_profile() && bb_user_can_create_video() ) || ( bp_is_group() && is_user_logged_in() && groups_can_user_manage_video( bp_loggedin_user_id(), bp_get_current_group_id() ) ) ) ) { ?>

	<div class="bb-video-actions-wrap bb-media-actions-wrap">
		<h2 class="bb-title"><?php esc_html_e( 'Videos', 'buddyboss' ); ?></h2>
		<div class="bb-video-actions">
			<a href="#" id="bp-add-video" class="bb-add-video button small outline"><?php esc_html_e( 'Add Videos', 'buddyboss' ); ?></a>
		</div>
	</div>

	<?php
	bp_get_template_part( 'video/uploader' );

} else {
	?>
	<div class="bb-video-actions-wrap bb-media-actions-wrap">
		<h2 class="bb-title"><?php esc_html_e( 'Videos', 'buddyboss' ); ?></h2>
	</div>
	<?php
}

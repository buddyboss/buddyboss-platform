<?php
/**
 * ReadyLaunch - Add Video template.
 *
 * Template for adding videos interface.
 *
 * @package BuddyBoss\Template
 * @subpackage BP_Nouveau\ReadyLaunch
 * @since BuddyBoss [BBVERSION]
 * @version 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

if ( ( ( bp_is_my_profile() && bb_user_can_create_video() ) || ( bp_is_group() && is_user_logged_in() && groups_can_user_manage_video( bp_loggedin_user_id(), bp_get_current_group_id() ) ) ) ) { ?>
	<div class="bb-video-actions-wrap bb-media-actions-wrap bb-rl-media-actions-wrap">
		<h2 class="bb-title"><?php esc_html_e( 'Videos', 'buddyboss' ); ?></h2>
		<div class="bb-video-actions">
			<a href="#" id="bp-add-video" class="bb-add-video button bb-rl-button bb-rl-button--brandFill bb-rl-button--small"><i class="bb-icons-rl-plus"></i><?php esc_html_e( 'Add Videos', 'buddyboss' ); ?></a>
		</div>
	</div>
	<?php
	bp_get_template_part( 'video/uploader' );
} else {
	?>
	<div class="bb-video-actions-wrap bb-media-actions-wrap bb-rl-media-actions-wrap">
		<h2 class="bb-title"><?php esc_html_e( 'Videos', 'buddyboss' ); ?></h2>
	</div>
	<?php
}

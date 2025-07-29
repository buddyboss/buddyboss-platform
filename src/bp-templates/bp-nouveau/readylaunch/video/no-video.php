<?php
/**
 * ReadyLaunch - No Video template.
 *
 * Template for displaying when no videos are available.
 *
 * @package BuddyBoss\Template
 * @subpackage BP_Nouveau\ReadyLaunch
 * @since BuddyBoss 2.9.00
 * @version 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

?>
<div class="bb-rl-media-none">
	<div class="bb-rl-media-none-figure"><i class="bb-icons-rl-file-video"></i></div>
	<aside class="bp-feedback bp-messages info">
		<span class="bp-icon" aria-hidden="true"></span>
		<p><?php esc_html_e( 'No videos found', 'buddyboss' ); ?></p>
	</aside>
	<p class="bb-rl-media-none-description"><?php esc_html_e( 'It looks like there aren\'t any videos in this directory.', 'buddyboss' ); ?></p>
	<?php
	if (
		! bp_is_video_directory() &&
		! bp_is_user_albums() &&
		! bp_is_group_albums() &&
		! bp_is_single_album()
	) {
		bp_get_template_part( 'video/add-video' );
	} elseif (
		(
			bp_is_group() &&
			bp_is_group_video_support_enabled()
		) ||
		(
			bp_is_my_profile() &&
			bp_is_profile_video_support_enabled()
		)
	) {
		bp_get_template_part( 'video/create-album' );
	}
	?>
</div>


<?php
/**
 * BuddyBoss - No Videos Found Template
 *
 * This template displays a message when no videos are found.
 * It includes an option to add new videos if the user has permission.
 *
 * @package BuddyBoss\Template
 * @subpackage BP_Nouveau\ReadyLaunch
 * @since BuddyBoss [BBVERSION]
 * @version 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

?>
<div class="bb-rl-media-none">
	<div class="bb-rl-media-none-figure"><i class="bb-icons-rl-file-video"></i></div>
	<aside class="bp-feedback bp-messages info">
		<span class="bp-icon" aria-hidden="true"></span>
		<p><?php esc_html_e( 'Sorry, no videos were found', 'buddyboss' ); ?></p>
	</aside>
	<div class="bb-video-actions-wrap bb-media-actions-wrap bb-rl-media-actions-wrap">
		<h2 class="bb-title"><?php esc_html_e( 'Videos', 'buddyboss' ); ?></h2>
		<div class="bb-video-actions">
			<a href="#" id="bp-add-video" class="bb-add-video button bb-rl-button bb-rl-button--brandFill bb-rl-button--small">
				<i class="bb-icons-rl-plus"></i><?php esc_html_e( 'Add Videos', 'buddyboss' ); ?>
			</a>
		</div>
	</div>
</div>
<?php
	bp_get_template_part( 'video/uploader' );
?>

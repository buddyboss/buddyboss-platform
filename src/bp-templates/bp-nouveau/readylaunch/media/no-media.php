<?php
/**
 * ReadyLaunch - Media No Content template.
 *
 * This template handles displaying the no media content message and actions.
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
	<div class="bb-rl-media-none-figure"><i class="bb-icons-rl-file-image"></i></div>
	<aside class="bp-feedback bp-messages info">
		<span class="bp-icon" aria-hidden="true"></span>
		<p><?php ( bp_is_active( 'video' ) && ( bp_is_profile_video_support_enabled() && bp_is_user_albums() ) || ( bp_is_group_video_support_enabled() && bp_is_group_albums() ) ) ? esc_html_e( 'Sorry, no photos or videos were found.', 'buddyboss' ) : esc_html_e( 'Sorry, no photos were found.', 'buddyboss' ); ?></p>
	</aside>
	<?php
	if ( ! bp_is_user_albums() && ! bp_is_group_albums() ) {
		?>
		<div class="bb-media-actions-wrap">
			<h2 class="bb-title"><?php esc_html_e( 'Photos', 'buddyboss' ); ?></h2>
			<div class="bb-media-actions">
				<a href="#" id="bp-add-media" class="bb-add-media button bb-rl-button bb-rl-button--brandFill bb-rl-button--small">
					<i class="bb-icons-rl-plus"></i><?php esc_html_e( 'Add Photos', 'buddyboss' ); ?>
				</a>
			</div>
		</div>
		<?php
	}
	?>
</div>
<?php
	bp_get_template_part( 'media/uploader' );
?>

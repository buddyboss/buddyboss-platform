<?php
/**
 * ReadyLaunch - Media No Content template.
 *
 * This template handles displaying the no media content message and actions.
 *
 * @package BuddyBoss\Template
 * @subpackage BP_Nouveau\ReadyLaunch
 * @since BuddyBoss 2.9.00
 * @version 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

$album_dir = false;
if (
	bp_is_active( 'video' ) &&
	(
		(
			bp_is_profile_video_support_enabled() &&
			bp_is_user_albums()
		) ||
		(
			bp_is_group_video_support_enabled() &&
			bp_is_group_albums()
		)
	)
) {
	$album_dir = true;
}
?>
<div class="bb-rl-media-none">
	<div class="bb-rl-media-none-figure"><i class="bb-icons-rl-file-image"></i></div>
	<aside class="bp-feedback bp-messages info">
		<span class="bp-icon" aria-hidden="true"></span>
		<p>
			<?php
			if ( $album_dir ) {
				esc_html_e( 'No albums found', 'buddyboss' );
			} else {
				esc_html_e( 'No photos found', 'buddyboss' );
			}
			?>
		</p>
	</aside>
	<p class="bb-rl-media-none-description">
		<?php
		if ( $album_dir ) {
			esc_html_e( 'It looks like there aren\'t any albums in this directory.', 'buddyboss' );
		} else {
			esc_html_e( 'It looks like there aren\'t any photos in this directory.', 'buddyboss' );
		}
		?>
	</p>
	<?php
	if (
		! bp_is_media_directory() &&
		! bp_is_user_albums() &&
		! bp_is_group_albums()
	) {
		bp_get_template_part( 'media/add-media' );
	} elseif (
		(
			bp_is_group() &&
			bp_is_group_albums_support_enabled()
		) ||
		(
			bp_is_my_profile() &&
			bp_is_profile_albums_support_enabled()
		)
	) {
		?>
		<div class="bb-media-actions bb-rl-media-none-actions">
			<a href="#" id="bb-create-album" class="bb-create-album button bb-rl-button bb-rl-button--brandFill bb-rl-button--small"><i class="bb-icons-rl-images"></i> <?php esc_html_e( 'Create Album', 'buddyboss' ); ?></a>
		</div>
		<?php
		bp_get_template_part( 'media/create-album' );
	}
	?>
</div>


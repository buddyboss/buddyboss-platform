<?php
/**
 * ReadyLaunch - Media No Content template.
 *
 * This template handles displaying the no media content message and actions.
 *
 * @package BuddyBoss\Template
 * @subpackage BBoss_Nouveau\ReadyLaunch
 * @since BuddyBoss 2.9.00
 * @version 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

$album_dir = false;
if (
	bboss_is_active( 'video' ) &&
	(
		(
			bboss_is_profile_video_support_enabled() &&
			bboss_is_user_albums()
		) ||
		(
			bboss_is_group_video_support_enabled() &&
			bboss_is_group_albums()
		)
	)
) {
	$album_dir = true;
}

// Check if we're viewing a single album page
$single_album = false;
if ( function_exists( 'bboss_is_single_album' ) && bboss_is_single_album() ) {
	$single_album = true;
} elseif ( function_exists( 'bboss_is_single_video_album' ) && bboss_is_single_video_album() ) {
	$single_album = true;
}

// Mention videos only when the album can hold them in this context.
$single_album_videos = $single_album && bboss_is_active( 'video' ) && (
	( bboss_is_group() && bboss_is_group_video_support_enabled() ) ||
	( ! bboss_is_group() && bboss_is_profile_video_support_enabled() )
);
?>
<div class="bb-rl-media-none">
	<div class="bb-rl-media-none-figure"><i class="bb-icons-rl-file-image"></i></div>
	<aside class="bp-feedback bp-messages info">
		<span class="bp-icon" aria-hidden="true"></span>
		<p>
			<?php
			if ( $single_album_videos ) {
				esc_html_e( 'No photos or videos found', 'buddyboss' );
			} elseif ( $single_album ) {
				esc_html_e( 'No photos found', 'buddyboss' );
			} elseif ( $album_dir ) {
				esc_html_e( 'No albums found', 'buddyboss' );
			} else {
				esc_html_e( 'No photos found', 'buddyboss' );
			}
			?>
		</p>
	</aside>
	<p class="bb-rl-media-none-description">
		<?php
		if ( $single_album_videos ) {
			esc_html_e( 'It looks like there aren\'t any photos or videos in this album.', 'buddyboss' );
		} elseif ( $single_album ) {
			esc_html_e( 'It looks like there aren\'t any photos in this album.', 'buddyboss' );
		} elseif ( $album_dir ) {
			esc_html_e( 'It looks like there aren\'t any albums in this directory.', 'buddyboss' );
		} else {
			esc_html_e( 'It looks like there aren\'t any photos in this directory.', 'buddyboss' );
		}
		?>
	</p>
	<?php
	if ( $single_album ) {
		// Show add media buttons for single empty album
		?>
		<div class="bb-media-actions-wrap">
			<div class="bb-media-actions bb-rl-media-none-actions">
				<?php if ( ( bboss_is_my_profile() && bboss_user_can_create_media() ) || ( bboss_is_group() && is_user_logged_in() && groups_can_user_manage_media( bboss_loggedin_user_id(), bboss_get_current_group_id() ) ) ) : ?>
					<a href="#" id="bp-add-media" class="bb-album-add-photos button bb-rl-button bb-rl-button--brandFill bb-rl-button--small"><i class="bb-icons-rl-images"></i> <?php esc_html_e( 'Add Photos', 'buddyboss' ); ?></a>
				<?php endif; ?>
				<?php if ( bboss_is_active( 'video' ) && ( ( bboss_is_profile_video_support_enabled() && bboss_is_user() ) || ( bboss_is_group_video_support_enabled() && bboss_is_group() ) ) && ( ( bboss_is_my_profile() && bboss_user_can_create_video() ) || ( bboss_is_group() && is_user_logged_in() && groups_can_user_manage_video( bboss_loggedin_user_id(), bboss_get_current_group_id() ) ) ) ) : ?>
					<a href="#" id="bp-add-video" class="bb-album-add-videos button bb-rl-button bb-rl-button--brandFill bb-rl-button--small"><i class="bb-icons-rl-video"></i> <?php esc_html_e( 'Add Videos', 'buddyboss' ); ?></a>
				<?php endif; ?>
			</div>
		</div>
		<?php
	} elseif (
		! bboss_is_media_directory() &&
		! bboss_is_user_albums() &&
		! bboss_is_group_albums()
	) {
		bboss_get_template_part( 'media/add-media' );
	} elseif (
		(
			bboss_is_group() &&
			bboss_is_group_albums_support_enabled()
		) ||
		(
			bboss_is_my_profile() &&
			bboss_is_profile_albums_support_enabled()
		)
	) {
		?>
		<div class="bb-media-actions bb-rl-media-none-actions">
			<a href="#" id="bb-create-album" class="bb-create-album button bb-rl-button bb-rl-button--brandFill bb-rl-button--small"><i class="bb-icons-rl-images"></i> <?php esc_html_e( 'Create Album', 'buddyboss' ); ?></a>
		</div>
		<?php
		bboss_get_template_part( 'media/create-album' );
	}
	?>
</div>


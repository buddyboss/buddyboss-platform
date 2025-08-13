<?php
/**
 * ReadyLaunch - Album Entry template.
 *
 * This template handles displaying individual album entries in the album listing.
 *
 * @package BuddyBoss\Template
 * @subpackage BP_Nouveau\ReadyLaunch
 * @since BuddyBoss 2.9.00
 * @version 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

global $media_album_template;

$privacy = bp_get_album_privacy();

$icons = array(
	'public'    => 'globe',
	'loggedin'  => 'users-four',
	'private'   => 'users',
	'grouponly' => 'lock',
);

?>

<li class="bb-album-list-item">
	<div class="bb-album-cover-wrap">
		<a class="bs-cover-wrap" href="<?php bp_album_link(); ?>">
			<?php if ( ! empty( $media_album_template->album->media['medias'] ) ) : ?>
				<img src="<?php echo esc_url( $media_album_template->album->media['medias'][0]->attachment_data->media_album_cover ); ?>" />
			<?php endif; ?>

			<div class="bb-album-content-wrap">
				<h4><?php bp_album_title(); ?></h4>
				<span class="bb-album_date"><?php echo bp_core_format_date( $media_album_template->album->date_created ); ?></span>
				<div class="bb-album_stats">
					<?php
					if ( 'grouponly' !== $privacy ) {
						?>
							<span class="bb-album-privacy <?php echo esc_attr( $privacy ); ?>">
								<?php if ( ! empty( $icons[ $privacy ] ) ) { ?>
									<i class="bb-icons-rl-<?php echo esc_attr( $icons[ $privacy ] ); ?>"></i>
								<?php } ?>
								<?php echo esc_html( ucfirst( $privacy ) ); ?>
							</span>
							<span class="bb-album_stats_spacer"></span>
						<?php
					}
					?>
					<span class="bb-album_stats_photos"> <i class="bb-icons-rl-images"></i> <?php echo bp_core_number_format( $media_album_template->album->media['total'] ); ?></span>
					<?php if ( ( bp_is_profile_albums_support_enabled() || bp_is_group_albums_support_enabled() ) && ( bp_is_active( 'video' ) && ( bp_is_profile_video_support_enabled() || bp_is_group_video_support_enabled() ) ) ) { ?>
						<span class="bb-album_stats_spacer"></span>
						<span class="bb-album_stats_videos"><i class="bb-icons-rl-video"></i> <?php echo bp_core_number_format( $media_album_template->album->media['total_video'] ); ?></span>
					<?php } ?>
				</div>
			</div>
		</a>
	</div>
</li>

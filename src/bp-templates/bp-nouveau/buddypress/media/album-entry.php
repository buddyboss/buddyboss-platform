<?php
/**
 * The template for album entry
 *
 * This template can be overridden by copying it to yourtheme/buddypress/media/album-entry.php.
 *
 * @since   BuddyBoss 1.0.0
 * @version 1.0.0
 */
?>

<?php global $media_album_template; ?>

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
					<span class="bb-album_stats_photos"> <i class="bb-icon-l bb-icon-image"></i> <?php echo bp_core_number_format( $media_album_template->album->media['total'] ); ?></span>
					<?php if ( ( bp_is_profile_albums_support_enabled() || bp_is_group_albums_support_enabled() ) && ( bp_is_active( 'video' ) && ( bp_is_profile_video_support_enabled() || bp_is_group_video_support_enabled() ) ) ) { ?>
						<span class="bb-album_stats_spacer">&middot;</span>
						<span class="bb-album_stats_videos"><i class="bb-icon-l bb-icon-video"></i> <?php echo bp_core_number_format( $media_album_template->album->media['total_video'] ); ?></span>
					<?php } ?>
				</div>
			</div>
		</a>
	</div>
</li>

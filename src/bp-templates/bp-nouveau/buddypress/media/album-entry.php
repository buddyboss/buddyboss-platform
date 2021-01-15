<?php
/**
 * BuddyBoss - Album Entry
 *
 * @since BuddyBoss 1.0.0
 */
?>

<?php global $media_album_template; ?>

<li class="bb-album-list-item">
    <div class="bb-album-cover-wrap">
        <a class="bs-cover-wrap" href="<?php bp_album_link(); ?>">
            <?php if ( ! empty( $media_album_template->album->media['medias'] ) ) : ?>
                <img src="<?php echo $media_album_template->album->media['medias'][0]->attachment_data->thumb; ?>" />

            <?php endif; ?>

			<div class="bb-album-content-wrap">
				<h4><?php bp_album_title(); ?></h4>
				<span class="bb-album_date"><?php echo bp_core_format_date( $media_album_template->album->date_created ); ?></span>
                <div class="bb-album_stats">
                    <span class="bb-album_stats_photos"> <i class="bb-icon bb-icon-video-album"></i> <?php echo number_format_i18n( $media_album_template->album->media['total'] ); ?></span>
                    <span class="bb-album_stats_spacer">&middot;</span>
                    <span class="bb-album_stats_videos"><i class="bb-icon bb-icon-video-alt"></i> <?php echo number_format_i18n( $media_album_template->album->video['total'] ); ?></span>
                </div>
			</div>
        </a>
    </div>
</li>

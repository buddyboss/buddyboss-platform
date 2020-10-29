<?php
/**
 * BuddyBoss - Album Entry
 *
 * @since BuddyBoss 1.0.0
 */
?>

<?php global $video_album_template; ?>

<li class="bb-album-list-item">
    <div class="bb-album-cover-wrap">
        <a class="bs-cover-wrap" href="<?php bp_video_album_link(); ?>">
            <?php if ( ! empty( $video_album_template->album->video['videos'] ) ) : ?>
                <img src="<?php echo $video_album_template->album->video['videos'][0]->attachment_data->thumb; ?>" />
            <?php endif; ?>

			<div class="bb-album-content-wrap">
				<h4><?php bp_video_album_title(); ?></h4>
				<span><?php echo bp_core_format_date( $video_album_template->album->date_created ); ?></span> <span>&middot;</span> <span><?php printf( _n( '%s video', '%s videos', $video_album_template->album->video['total'], 'buddyboss' ), number_format_i18n( $video_album_template->album->video['total'] ) ); ?></span>
			</div>
        </a>
    </div>
</li>

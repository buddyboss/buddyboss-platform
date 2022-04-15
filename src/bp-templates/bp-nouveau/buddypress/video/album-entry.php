<?php
/**
 * BuddyBoss - Album Entry
 *
 * This template can be overridden by copying it to yourtheme/buddypress/video/album-entry.php.
 *
 * @package BuddyBoss\Core
 *
 * @since   BuddyBoss 1.7.0
 * @version 1.7.0
 */

global $video_album_template; ?>

<li class="bb-album-list-item">
	<div class="bb-album-cover-wrap">
		<a class="bs-cover-wrap" href="<?php bp_video_album_link(); ?>">
			<?php if ( ! empty( $video_album_template->album->video['videos'] ) ) : ?>
				<img src="<?php echo esc_url( $video_album_template->album->video['videos'][0]->attachment_data->video_album_cover_thumb ); ?>" />
			<?php endif; ?>

			<div class="bb-album-content-wrap">
				<h4><?php bp_video_album_title(); ?></h4>
				<span><?php echo bp_core_format_date( $video_album_template->album->date_created ); // phpcs:ignore ?></span> <span>&middot;</span> <span><?php printf( _n( '%s video', '%s videos', $video_album_template->album->video['total'], 'buddyboss' ), bp_core_number_format( $video_album_template->album->video['total'] ) ); ?></span>
			</div>
		</a>
	</div>
</li>

<?php
/**
 * Template for displaying the search results of the videos ajax
 *
 * This template can be overridden by copying it to yourtheme/buddypress/search/loop/videos-ajax.php.
 *
 * @package BuddyBoss\Core
 * @since   BuddyBoss 1.7.0
 * @version 1.7.0
 */

$listing_class = '';
$attachment_id = bp_get_video_attachment_id();
$video_id      = bp_get_video_id();
$filename      = basename( get_attached_file( $attachment_id ) );
$video_title   = '';
$video_type    = '';
if ( $attachment_id ) {
	$listing_class = 'ac-video-list';
	$video_type    = 'videos';
	$video_title   = bp_get_video_title();
}

$class = ''; // used.
if ( $attachment_id && bp_get_video_activity_id() ) {
	$class = ''; // used.
}
$video_link    = bp_get_videos_link();
$video_created = bp_get_video_date_created();
?>

<div class="bp-search-ajax-item bboss_ajax_search_video search-video-list">
	<a href="">
		<div class="item">
			<div class="media-album_items ac-album-list">
				<div class="media-album_thumb">
					<a href="<?php echo esc_url( $video_link ); ?>">
						<img src="<?php bp_video_attachment_image_thumbnail(); ?>" alt="<?php echo esc_html( $video_title ); ?>" />
					</a>
				</div>

				<div class="media-album_details">
					<a class="media-album_name " href="<?php echo esc_url( $video_link ); ?>">
						<span><?php echo esc_html( $video_title ); ?></span>
					</a>
				</div>

				<div class="media-album_modified">
					<div class="media-album_details__bottom">
						<span class="media-album_author"><?php esc_html_e( 'By ', 'buddyboss' ); ?>
							<a href="<?php echo esc_url( $video_link ); ?>">
								<?php esc_html( bp_video_author() ); ?>
							</a>
						</span>
						<span class="middot">&middot;</span>
						<span class="media-album_date"><?php echo esc_html( bp_core_format_date( $video_created ) ); ?></span>
					</div>
				</div>

				<div class="media-album_visibility">
					<div class="media-album_details__bottom">
						<?php
						if ( bp_is_active( 'groups' ) ) {
							$group_id = bp_get_video_group_id();
							if ( $group_id > 0 ) {
								?>
								<span class="middot">&middot;</span>
								<span class="bp-tooltip" data-bp-tooltip-pos="left" data-bp-tooltip="<?php esc_attr_e( 'Based on group privacy', 'buddyboss' ); ?>">
									<?php bp_video_visibility(); ?>
								</span>
								<?php
							} else {
								?>
								<span class="middot">&middot;</span>
								<span id="privacy-<?php echo esc_attr( bp_get_video_id() ); ?>">
									<?php bp_video_visibility(); ?>
								</span>
								<?php
							}
						} else {
							?>
							<span class="middot">&middot;</span>
							<span>
								<?php bp_video_visibility(); ?>
							</span>
							<?php
						}
						?>
					</div>
				</div>
			</div>
		</div>
	</a>
</div>

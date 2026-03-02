<?php
/**
 * ReadyLaunch - Search Loop Videos template.
 *
 * The template for search results for videos.
 *
 * @package BuddyBoss\Template
 * @subpackage BP_Nouveau\ReadyLaunch
 * @since BuddyBoss 2.9.00
 * @version 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

$listing_class = '';
$attachment_id = bp_get_video_attachment_id();
$video_id      = bp_get_video_id();
$filename      = basename( get_attached_file( $attachment_id ) );
$video_title   = '';
$video_type    = '';
$download_link = '';
if ( $attachment_id ) {
	$download_link = bp_video_download_link( $attachment_id, $video_id );
	$listing_class = 'ac-video-list';
	$video_type    = 'videos';
	$video_title   = bp_get_video_title();
}

$class = ''; // used.
if ( $attachment_id && bp_get_video_activity_id() ) {
	$class = ''; // used.
}
$video_link       = bp_get_videos_link();
$video_created    = bp_get_video_date_created();
$video_visibility = bp_get_video_visibility();
?>

<li data-bp-item-id="<?php echo esc_attr( $video_id ); ?>" data-bp-item-component="video" class="bp-search-item bp-search-item_video search-video-list bb-rl-search-post-item">
	<div class="media-album_items ac-album-list list-wrap">
		<div class="item-avatar">
			<a href="<?php echo esc_url( $video_link ); ?>">
				<img src="<?php bp_video_attachment_image_thumbnail(); ?>" alt="<?php echo esc_html( $video_title ); ?>" class="avatar" />
			</a>
		</div>

		<div class="item">
			<div class="media-album_details">
				<h2 class="item-title">
					<a class="media-album_name" href="<?php echo esc_url( $video_link ); ?>">
						<span><?php echo esc_html( $video_title ); ?></span>
					</a>
				</h2>
			</div>

			<div class="entry-meta">
				<div class="media-album_modified">
					<div class="media-album_details__bottom">
						<?php
						if ( ! bp_is_user() ) {
							?>
							<span class="media-album_author"><?php esc_html_e( 'By ', 'buddyboss' ); ?>
								<a href="<?php echo esc_url( $video_link ); ?>" data-bb-hp-profile="<?php echo esc_attr( bp_get_video_user_id() ); ?>"><?php bp_video_author(); ?></a>
							</span>
							<?php
						}
						?>
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
								<span class="bp-tooltip" data-bp-tooltip-pos="down" data-bp-tooltip="<?php esc_attr_e( 'Based on group privacy', 'buddyboss' ); ?>">
									<?php echo esc_html( $video_visibility ); ?>
								</span>
								<?php
							} else {
								?>
								<span class="middot">&middot;</span>
								<span id="privacy-<?php echo esc_attr( $video_id ); ?>">
									<?php echo esc_html( $video_visibility ); ?>
								</span>
								<?php
							}
						} else {
							?>
							<span class="middot">&middot;</span>
							<span>
								<?php echo esc_html( $video_visibility ); ?>
							</span>
							<?php
						}
						?>
					</div>
				</div>
			</div>
		</div>
	</div>
</li>

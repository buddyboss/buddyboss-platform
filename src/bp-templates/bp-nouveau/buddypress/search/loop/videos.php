<?php
/**
 * Video search Template
 *
 * @package BuddyBoss\Core
 */

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
$video_link    = bp_get_videos_link();
$video_created = bp_get_video_date_created();
?>

<li data-bp-item-id="<?php bp_get_video_id(); ?>" data-bp-item-component="video" class="search-video-list">
	<div class="list-wrap">
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
						<span class="media-album_date"><?php echo esc_html( bp_core_format_date( $video_created ) ); ?></span>
						<?php
						if ( ! bp_is_user() ) {
							?>
							<span class="media-album_author"><?php esc_html_e( 'by ', 'buddyboss' ); ?>
							<a href="<?php echo esc_url( $video_link ); ?>"><?php bp_video_author(); ?></a></span>
							<?php
						}
						?>
					</div>
				</div>

				<div class="media-album_group">
					<div class="media-album_details__bottom">
						<?php
						if ( bp_is_active( 'groups' ) ) {
							$group_id = bp_get_video_group_id();
							if ( $group_id > 0 ) {
								// Get the group from the database.
								$group        = groups_get_group( $group_id );
								$group_name   = isset( $group->name ) ? bp_get_group_name( $group ) : '';
								$group_link   = sprintf( '<a href="%s" class="bp-group-home-link %s-home-link">%s</a>', esc_url( $video_link ), esc_attr( bp_get_group_slug( $group ) ), esc_html( bp_get_group_name( $group ) ) );
								$group_status = bp_get_group_status( $group );
								?>
								<span class="media-album_group_name"><?php echo wp_kses_post( $group_link ); ?></span>
								<span class="media-album_status"><?php echo esc_html( ucfirst( $group_status ) ); ?></span>
								<?php
							} else {
								?>
								<span class="media-album_group_name"> </span>
								<?php
							}
						}
						?>
					</div>
				</div>

				<div class="media-album_visibility">
					<div class="media-album_details__bottom">
						<?php
						if ( bp_is_active( 'groups' ) ) {
							$group_id = bp_get_video_group_id();
							if ( $group_id > 0 ) {
								?>
								<span class="bp-tooltip" data-bp-tooltip-pos="down" data-bp-tooltip="<?php esc_attr_e( 'Based on group privacy', 'buddyboss' ); ?>">
									<?php bp_video_visibility(); ?>
								</span>
								<?php
							} else {
								?>
								<span id="privacy-<?php echo esc_attr( bp_get_video_id() ); ?>">
									<?php bp_video_visibility(); ?>
								</span>
								<?php
							}
						} else {
							?>
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
	</div>
</li>

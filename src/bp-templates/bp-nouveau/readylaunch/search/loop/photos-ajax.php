<?php
/**
 * ReadyLaunch - Search Loop Photos AJAX template.
 *
 * The template for AJAX search results for photos.
 *
 * @package BuddyBoss\Template
 * @subpackage BP_Nouveau\ReadyLaunch
 * @since BuddyBoss 2.9.00
 * @version 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

$listing_class = '';
$attachment_id = bp_get_media_attachment_id();
$media_id      = bp_get_media_id();
$filename      = basename( get_attached_file( $attachment_id ) );
$photo_title   = '';
$media_type    = '';
if ( $attachment_id ) {
	$listing_class = 'ac-media-list';
	$media_type    = 'photos';
	$photo_title   = bp_get_media_title();
}

$class = ''; // used.
if ( $attachment_id && bp_get_media_activity_id() ) {
	$class = ''; // used.
}
$media_link       = bp_get_media_link();
$media_created    = bp_get_media_date_created();
$media_visibility = bp_get_media_visibility();
?>

<div class="bp-search-ajax-item bboss_ajax_search_media search-media-list bb-rl-search-post-item">
	<a href="<?php echo esc_url( $media_link ); ?>">
		<div class="item-avatar">
			<img src="<?php bp_media_attachment_image_thumbnail(); ?>" alt="<?php echo esc_html( $photo_title ); ?>" class="avatar" />
		</div>
	</a>
	<div class="item">
		<div class="media-album_items ac-album-list">
			<div class="media-album_details item-title">
				<a class="media-album_name " href="<?php echo esc_url( $media_link ); ?>">
					<span><?php echo esc_html( $photo_title ); ?></span>
				</a>
			</div>

			<div class="entry-meta">
				<div class="media-album_modified">
					<div class="media-album_details__bottom">
						<span class="media-album_author"><?php esc_html_e( 'By ', 'buddyboss' ); ?>
							<a href="<?php echo esc_url( $media_link ); ?>"><?php esc_html( bp_get_media_author() ); ?></a>
						</span>
						<span class="middot">&middot;</span>
						<span class="media-album_date"><?php echo esc_html( bp_core_format_date( $media_created ) ); ?></span>
					</div>
				</div>

				<div class="media-album_visibility">
					<div class="media-album_details__bottom">
						<?php
						if ( bp_is_active( 'groups' ) ) {
							$group_id = bp_get_media_group_id();
							if ( $group_id > 0 ) {
								?>
									<span class="middot">&middot;</span>
									<span class="bp-tooltip" data-bp-tooltip-pos="left" data-bp-tooltip="<?php esc_attr_e( 'Based on group privacy', 'buddyboss' ); ?>">
									<?php echo esc_html( $media_visibility ); ?>
									</span>
								<?php
							} else {
								?>
									<span class="middot">&middot;</span>
									<span id="privacy-<?php echo esc_attr( $media_id ); ?>">
									<?php echo esc_html( $media_visibility ); ?>
									</span>
								<?php
							}
						} else {
							?>
								<span class="middot">&middot;</span>
								<span>
								<?php echo esc_html( $media_visibility ); ?>
								</span>
							<?php
						}
						?>
					</div>
				</div>
			</div>

		</div>
	</div>
</div>

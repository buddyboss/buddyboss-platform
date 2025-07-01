<?php
/**
 * ReadyLaunch - Search Loop Albums AJAX template.
 *
 * The template for AJAX search results for albums.
 *
 * @package BuddyBoss\Template
 * @subpackage BP_Nouveau\ReadyLaunch
 * @since BuddyBoss 2.9.00
 * @version 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

global $media_album_template;
$albums_link = bp_get_album_link();

?>
<div class="bp-search-ajax-item bboss_ajax_search_media search-media-list bb-rl-search-post-item">
	<div class="item-avatar">
		<?php if ( ! empty( $media_album_template->album->media['medias'] ) ) : ?>
			<div class="media-album_thumb">
				<a href="<?php echo esc_url( $albums_link ); ?>">
					<img src="<?php echo esc_url( $media_album_template->album->media['medias'][0]->attachment_data->thumb ); ?>" alt="<?php echo wp_kses_post( bp_get_album_title() ); ?>" />
				</a>
			</div>
		<?php else : ?>
			<a href="<?php echo esc_url( $albums_link ); ?>" class="item-avatar">
				<i class="bb-icon-f bb-icon-image-video"></i>
			</a>
		<?php endif; ?>
	</div>
	<div class="item">

		<div class="media-album_items ac-album-list">
			<div class="media-album_details">
				<div class="item-title">
					<a class="media-album_name " href="<?php echo esc_url( $albums_link ); ?>">
						<span><?php echo wp_kses_post( bp_get_album_title() ); ?></span>
					</a>
				</div>
			</div>

			<div class="entry-meta">
				<div class="media-album_modified">
					<div class="media-album_details__bottom">
						<span class="media-album_author">
							<?php esc_html_e( 'By ', 'buddyboss' ); ?>
							<a href="<?php echo esc_url( $albums_link ); ?>"><?php bp_album_author(); ?></a>
						</span>
						<span class="middot">&middot;</span>
						<span class="media-album_date"><?php echo esc_html( bp_core_format_date( $media_album_template->album->date_created ) ); ?></span>
					</div>
				</div>

				<div class="media-album_visibility">
					<div class="media-album_details__bottom">
						<?php
						if ( bp_is_active( 'groups' ) ) {
							$group_id = bp_get_album_group_id();
							if ( $group_id > 0 ) {
								?>
								<span class="middot">&middot;</span>
								<span class="bp-tooltip" data-bp-tooltip-pos="left" data-bp-tooltip="<?php esc_attr_e( 'Based on group privacy', 'buddyboss' ); ?>">
									<?php bp_album_visibility(); ?>
								</span>
								<?php
							} else {
								?>
								<span class="middot">&middot;</span>
								<span id="privacy-<?php echo esc_attr( bp_get_album_id() ); ?>">
									<?php bp_album_visibility(); ?>
								</span>
								<?php
							}
						} else {
							?>
							<span class="middot">&middot;</span>
							<span>
								<?php bp_album_visibility(); ?>
							</span>
							<?php
						}
						?>
					</div>
				</div>
			</div>

		</div><!--.media-folder_items-->
	</div>
</div>

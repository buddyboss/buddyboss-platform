<?php
/**
 * Albums search ajax Template
 *
 * This template can be overridden by copying it to yourtheme/buddypress/search/loop/albums-ajax.php.
 *
 * @package BuddyBoss\Core
 * @version 1.0.0
 */

global $media_album_template;
$albums_link = bp_get_album_link();

?>
<div class="bp-search-ajax-item bboss_ajax_search_media search-media-list">
	<a href="<?php echo esc_url( $albums_link ); ?>">
		<div class="item-avatar">
			<?php if ( ! empty( $media_album_template->album->media['medias'] ) ) : ?>
				<img src="<?php echo esc_url( $media_album_template->album->media['medias'][0]->attachment_data->thumb ); ?>" alt="<?php echo wp_kses_post( bp_get_album_title() ); ?>" />
			<?php else : ?>
				<i class="bb-icon-f bb-icon-image-video"></i>
			<?php endif; ?>
		</div>
	</a>
	<div class="item">

		<div class="media-album_items ac-album-list">
			<div class="media-album_details">
				<a class="media-album_name " href="<?php echo esc_url( $albums_link ); ?>">
					<span><?php echo wp_kses_post( bp_get_album_title() ); ?></span>
				</a>
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

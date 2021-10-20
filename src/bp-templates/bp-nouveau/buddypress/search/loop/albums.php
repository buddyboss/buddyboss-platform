<?php
/**
 * Albums search Template
 *
 * @package BuddyBoss\Core
 */

global $media_album_template;
$albums_link = bp_get_album_link();
?>
<li data-bp-item-id="<?php bp_album_id(); ?>" data-bp-item-component="media" class="search-media-list">
	<div class="list-wrap">
		<div class="item">

			<div class="media-album_items ac-album-list">
				<div class="media-album_thumb">
					<?php if ( ! empty( $media_album_template->album->media['medias'] ) ) : ?>
						<a href="<?php echo esc_url( $albums_link ); ?>">
							<img src="<?php echo esc_url( $media_album_template->album->media['medias'][0]->attachment_data->thumb ); ?>" alt="<?php echo wp_kses_post( bp_get_album_title() ); ?>" />
						</a>
					<?php else : ?>
						<a href="<?php echo esc_url( $albums_link ); ?>">
							<img src="<?php echo esc_url( buddypress()->plugin_url ); ?>bp-templates/bp-nouveau/images/placeholder.png" alt="<?php echo wp_kses_post( bp_get_album_title() ); ?>" />
						</a>
					<?php endif; ?>
				</div>

				<div class="media-album_details">
					<a class="media-album_name " href="<?php echo esc_url( $albums_link ); ?>">
						<span><?php echo wp_kses_post( bp_get_album_title() ); ?></span>
					</a>
					<span class="media-photo_count">
						<?php
						printf(
						// translators: Photos count.
							esc_html( _n( '%s photo', '%s photos', $media_album_template->album->media['total'], 'buddyboss' ) ),
							esc_attr( bp_core_number_format( $media_album_template->album->media['total'] ) )
						);
						?>
					</span>
					<?php
					if ( bp_is_profile_video_support_enabled() || bp_is_group_video_support_enabled() ) {
						?>
						<span class="media-photo_count">
							<?php
							printf(
							// translators: Photos count.
								esc_html( _n( '%s video', '%s videos', $media_album_template->album->media['total_video'], 'buddyboss' ) ),
								esc_attr( bp_core_number_format( $media_album_template->album->media['total_video'] ) )
							);
							?>
						</span> <!-- Get the count of photos in that album -->
						<?php
					}
					?>
				</div>

				<div class="media-album_modified">
					<div class="media-album_details__bottom">
						<span class="media-album_date"><?php echo esc_html( bp_core_format_date( $media_album_template->album->date_created ) ); ?></span>
						<span class="media-album_author">
							<?php esc_html_e( 'by ', 'buddyboss' ); ?>
							<a href="<?php echo esc_url( $albums_link ); ?>"><?php bp_album_author(); ?></a>
						</span>
					</div>
				</div>

				<div class="media-album_group">
					<div class="media-album_details__bottom">
						<?php
						if ( bp_is_active( 'groups' ) ) {
							$group_id = bp_get_album_group_id();
							if ( $group_id > 0 ) {
								// Get the group from the database.
								$group        = groups_get_group( $group_id );
								$group_name   = isset( $group->name ) ? bp_get_group_name( $group ) : '';
								$group_link   = sprintf( '<a href="%s" class="bp-group-home-link %s-home-link">%s</a>', esc_url( $albums_link ), esc_attr( bp_get_group_slug( $group ) ), esc_html( bp_get_group_name( $group ) ) );
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
							$group_id = bp_get_album_group_id();
							if ( $group_id > 0 ) {
								?>
								<span class="bp-tooltip" data-bp-tooltip-pos="down" data-bp-tooltip="<?php esc_attr_e( 'Based on group privacy', 'buddyboss' ); ?>">
									<?php bp_album_visibility(); ?>
								</span>
								<?php
							} else {
								?>
								<span id="privacy-<?php echo esc_attr( bp_get_album_id() ); ?>">
									<?php bp_album_visibility(); ?>
								</span>
								<?php
							}
						} else {
							?>
							<span>
								<?php bp_album_visibility(); ?>
							</span>
							<?php
						}
						?>
					</div>
				</div>
			</div><!--.media-folder_items-->
		</div>
	</div>
</li>

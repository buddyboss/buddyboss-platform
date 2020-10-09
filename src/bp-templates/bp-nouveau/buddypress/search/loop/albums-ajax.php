<?php
$albums_link = bp_get_album_link();

?>
<div class="bp-search-ajax-item bboss_ajax_search_media search-media-list">
	<div class="item">

		<div class="media-album_items ac-album-list">
			<div class="media-album_thumb">
				<a href="<?php echo esc_url( $albums_link ); ?>">
					<img src="https://picsum.photos/400/270" alt="<?php echo bp_get_album_title(); ?>" />
				</a>
			</div>

			<div class="media-album_details">
				<a class="media-album_name " href="<?php echo esc_url( $albums_link ); ?>">
					<span><?php echo bp_get_album_title(); ?></span>
				</a>
					<span class="media-photo_count">2 photos</span> <!-- Get the count of photos in that album -->
			</div>

			<div class="media-album_modified">
				<div class="media-album_details__bottom">
					<span class="media-album_date">October 9, 2020</span>
					<span class="media-album_author">by <a href="http://localhost/buddyboss/members/john/documents/">John Smith</a></span>
				</div>
			</div>

			<div class="media-album_group">
				<div class="media-album_details__bottom">
					<span class="media-album_group_name"><a href="#">Group Name</a></span>
					<span class="media-album_status">Public</span>
				</div>
			</div>

			<div class="media-album_visibility">
				<div class="media-album_details__bottom">
					<?php
						if ( bp_is_active( 'groups' ) ) {
							$group_id = bp_get_media_group_id();
							if ( $group_id > 0 ) {
							?>
								<span class="bp-tooltip" data-bp-tooltip-pos="down" data-bp-tooltip="<?php esc_attr_e( 'Based on group privacy', 'buddyboss' ); ?>">
									<?php bp_media_privacy(); ?>
								</span>
								<?php
							} else {
							?>
								<span id="privacy-<?php echo esc_attr( bp_get_media_id() ); ?>">
									<?php bp_media_privacy(); ?>
								</span>
							<?php
							}
						} else {
						?>
							<span>
								<?php bp_media_privacy(); ?>
							</span>
						<?php
						}
					?>
				</div>
			</div>

		</div><!--.media-folder_items-->

	</div>
</div>

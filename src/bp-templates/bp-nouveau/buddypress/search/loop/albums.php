<?php
$albums_link = bp_get_album_link();

?>
<li data-bp-item-id="<?php echo bp_get_album_id(); ?>" data-bp-item-component="media" class="search-media-list">
	<div class="list-wrap">
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
					<span class="media-photo_count">2 photos</span>
				</div>

				<div class="media-album_modified">
					<div class="media-album_details__bottom">
						<span class="media-album_date">October 9, 2020</span>
						<span class="media-album_author">by <a href="http://localhost/buddyboss/members/john/documents/">John Smith</a></span>
					</div>
				</div>

				<div class="media-album_group">
					<div class="media-album_details__bottom">
						<span class="media-album_group_name">Group Name</span>
					</div>
				</div>

				<div class="media-album_visibility">
					<div class="media-album_details__bottom">
						<span>
							<?php echo bp_get_album_privacy(); ?>
						</span>
					</div>
				</div>

			</div><!--.media-folder_items-->

		</div>
	</div>
</li>

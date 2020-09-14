<?php
$albums_link = bp_get_album_link();

?>
<div class="bp-search-ajax-item bboss_ajax_search_media search-media-list">
	<div class="item">

		<div class="media-folder_items ac-folder-list">
			<div class="media-folder_icon">
				<a href="<?php echo esc_url( $albums_link ); ?>">
					<i class="bb-icon-folder-stacked"></i>
				</a>
			</div>

			<div class="media-folder_details">
				<a class="media-folder_name " href="<?php echo esc_url( $albums_link ); ?>">
					<span><?php echo bp_get_album_title(); ?></span>
				</a>
			</div>

			<div class="media-folder_visibility">
				<div class="media-folder_details__bottom">
					<span>
						<?php echo bp_get_album_privacy(); ?>
					</span>
				</div>
			</div>

		</div><!--.media-folder_items-->

	</div>
</div>

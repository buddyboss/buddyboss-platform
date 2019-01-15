<div class="bboss_ajax_search_item bboss_ajax_search_item_forum">
	<a href="<?php echo esc_url(add_query_arg( array( 'no_frame' => '1' ), bbp_get_forum_permalink( get_the_ID()) )); ?>">
		<div class="item">
			<div class="item-title"><?php bbp_forum_title(get_the_ID()); ?></div>
		</div>
	</a>
</div>
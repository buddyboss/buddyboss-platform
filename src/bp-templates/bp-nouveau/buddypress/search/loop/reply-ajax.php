<div class="bboss_ajax_search_item bboss_ajax_search_item_reply">
	<a href="<?php echo esc_url(add_query_arg( array( 'no_frame' => '1' ), bbp_get_reply_url(get_the_ID()) )); ?>">
		<div class="item-avatar">
			<img
				src="<?php echo bbp_get_forum_thumbnail_src( bbp_get_forum_id( get_the_ID() ) ) ?: buddypress()->plugin_url . "bp-core/images/mystery-forum.png"; ?>"
				class="avatar forum-avatar"
				height="150"
				width="150"
				alt=""
			/>
		</div>
		<div class="item">
            <div class="item-title">
                <?php echo stripslashes( wp_strip_all_tags( bbp_forum_title( get_the_ID() ) ) );?>
            </div>
            <div class="item-desc"><?php echo bp_search_reply_intro( 30 ); ?></div>
		</div>
	</a>
</div>

<div class="bboss_ajax_search_item bboss_ajax_search_item_reply">
	<a href="<?php echo esc_url(add_query_arg( array( 'no_frame' => '1' ), bbp_get_reply_url(get_the_ID()) )); ?>">
		<div class="item">
            <div class="item-title">
                <?php echo stripslashes( wp_strip_all_tags( bbp_forum_title( get_the_ID() ) ) );?>
            </div>
            <div class="item-desc"><?php echo buddyboss_global_search_reply_intro( 30 ); ?></div>
		</div>
	</a>
</div>
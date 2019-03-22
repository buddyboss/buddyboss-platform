<?php $total = bbp_get_topic_reply_count( get_the_ID() ) ?>
<div class="bp-search-ajax-item bp-search-ajax-item_topic">
	<a href="<?php echo esc_url(add_query_arg( array( 'no_frame' => '1' ), bbp_get_topic_permalink(get_the_ID()) )); ?>">
		<div class="item-avatar">
			<img
				src="<?php echo bbp_get_forum_thumbnail_src( bbp_get_forum_id( get_the_ID() ) ) ?: buddypress()->plugin_url . "bp-core/images/forum.svg"; ?>"
				class="avatar forum-avatar"
				height="150"
				width="150"
				alt=""
			/>
		</div>
		<div class="item">
            <div class="item-title">
                <?php echo stripslashes( wp_strip_all_tags( bbp_get_topic_title( get_the_ID() ) ) );?>
            </div>
			<div class="item-desc">
				<?php
            	//@todo remove %d?
				printf( _n( '%d reply', '%d replies', $total, 'buddyboss' ), $total ); ?>
			</div>
		</div>
	</a>
</div>

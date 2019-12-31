<?php
$total = bbp_get_forum_topic_count( get_the_ID() );
$result = bp_search_is_post_restricted( get_the_ID(), get_current_user_id(), 'forum' );
?>
<div class="bp-search-ajax-item bp-search-ajax-item_forum">
	<a href="<?php echo esc_url(add_query_arg( array( 'no_frame' => '1' ), bbp_get_forum_permalink( get_the_ID()) )); ?>">
		<div class="item-avatar">
			<img
				src="<?php echo $result['post_thumbnail']; ?>"
				class="avatar forum-avatar"
				height="150"
				width="150"
				alt=""
			/>
		</div>
		<div class="item">
			<div class="item-title"><?php bbp_forum_title(get_the_ID()); ?></div>
			<div class="item-desc"><?php
            //@todo take %d out of this?
			printf( _n( '%d topic', '%d topics', $total, 'buddyboss' ), $total ); ?></div>
		</div>
	</a>
</div>

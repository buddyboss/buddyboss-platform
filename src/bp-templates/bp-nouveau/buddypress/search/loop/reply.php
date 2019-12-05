<?php
$reply_id = get_the_ID();
$topic_id = bbp_get_reply_topic_id($reply_id);
?>
<li class="bp-search-item bp-search-item_reply">
	<div class="list-wrap">
		<div class="item-avatar">
			<a href="<?php bbp_reply_url( $reply_id ); ?>">
				<img
					src="<?php echo bbp_get_forum_thumbnail_src( bbp_get_forum_id( $reply_id ) ) ?: bp_search_get_post_thumbnail_default( get_post_type() ); ?>"
					class="avatar forum-avatar"
					height="150"
					width="150"
					alt=""
				/>
			</a>
		</div>

		<div class="item">
			<div class="entry-title item-title">
				<a href="<?php bbp_reply_url( $reply_id ); ?>"><?php bbp_topic_title( $topic_id ) ?></a>
			</div>
			<div class="entry-content entry-summary">
				<?php echo wp_trim_words( bbp_get_reply_content( $reply_id ), 30, '...' ) ?>
			</div>
			<div class="entry-meta">
				<span class="datetime">
					<?php bbp_reply_post_date( $reply_id ) ?>
				</span>
			</div>
		</div>
	</div>
</li>

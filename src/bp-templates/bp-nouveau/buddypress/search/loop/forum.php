<?php
$forum_id    = get_the_ID();
$total_topic = bbp_get_forum_topic_count( $forum_id );
$total_reply = bbp_get_forum_reply_count( $forum_id );
?>
<li class="bp-search-item bp-search-item_forum">
	<div class="list-wrap">
		<div class="item-avatar">
			<a href="<?php bbp_forum_permalink( get_the_ID() ); ?>">
				<img
					src="<?php echo bbp_get_forum_thumbnail_src( $forum_id ) ?: bp_search_get_post_thumbnail_default( get_post_type() ); ?>"
					class="avatar forum-avatar"
					height="150"
					width="150"
					alt=""
				/>
			</a>
		</div>

		<div class="item">
			<div class="entry-title item-title">
				<a href="<?php bbp_forum_permalink( $forum_id ); ?>"><?php bbp_forum_title( $forum_id ); ?></a>
			</div>
			<div class="entry-content entry-summary">
				<?php echo wp_trim_words( bbp_get_forum_content( $forum_id ), 30, '...' ); ?>
			</div>
			<div class="entry-meta">
				<span class="topic-count">
					<?php printf( _n( '%d topic', '%d topics', $total_topic, 'buddyboss' ), $total_topic ); ?>
				</span>
				<span class="middot">&middot;</span>
				<span class="reply-count">
				<?php printf( _n( '%d reply', '%d replies', $total_reply, 'buddyboss' ), $total_reply ); ?>
				</span>
				<span class="middot">&middot;</span>
				<span class="freshness">
					<?php bbp_forum_freshness_link( $forum_id ); ?>
				</span>
			</div>
		</div>
	</div>
</li>

<?php $total = bbp_get_topic_reply_count( get_the_ID() ) ?>
<li class="bp-search-item bp-search-item_topic">
	<div class="list-wrap">
		<div class="item-avatar">
			<a href="<?php bbp_topic_permalink( get_the_ID() ); ?>">
				<img
					src="<?php echo bbp_get_forum_thumbnail_src( bbp_get_forum_id( get_the_ID() ) ) ?: buddypress()->plugin_url . "bp-core/images/mystery-forum.png"; ?>"
					class="avatar forum-avatar"
					height="150"
					width="150"
					alt=""
				/>
			</a>
		</div>

		<div class="item">
			<h3 class="entry-title item-title">
				<a href="<?php bbp_topic_permalink(get_the_ID()); ?>"><?php bbp_topic_title(get_the_ID()); ?></a>
			</h3>
			<div class="entry-content entry-summary">
				<?php printf( _n( '%d reply', '%d replies', $total, 'buddyboss' ), $total ); ?>
			</div>
		</div>
	</div>
</li>

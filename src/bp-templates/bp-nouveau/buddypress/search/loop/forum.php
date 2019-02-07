<?php $total = bbp_get_forum_topic_count( get_the_ID() ) ?>
<li class="bp-search-item bp-search-item_forum">
	<div class="list-wrap">
		<div class="item-avatar">
			<a href="<?php bbp_forum_permalink( get_the_ID() ); ?>">
				<img
					src="<?php echo bbp_get_forum_thumbnail_src( get_the_ID() ) ?: buddypress()->plugin_url . "bp-core/images/mystery-forum.png"; ?>"
					class="avatar forum-avatar"
					height="150"
					width="150"
					alt=""
				/>
			</a>
		</div>

		<div class="item">
			<div class="item-title"><a href="<?php bbp_forum_permalink( get_the_ID()); ?>"><?php bbp_forum_title(get_the_ID()); ?></a></div>
			<div class="item-desc"><?php printf( _n( '%d topic', '%d topics', $total, 'buddyboss' ), $total ); ?></div>
		</div>
	</div>
</li>

<li class="bboss_search_item bboss_search_item_forum">
	<div class="item">
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
		<div class="item-title"><a href="<?php bbp_forum_permalink( get_the_ID()); ?>"><?php bbp_forum_title(get_the_ID()); ?></a></div>
		<div class="item-desc"><?php bbp_forum_content(get_the_ID());?></div>
	</div>
</li>

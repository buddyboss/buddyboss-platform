<?php global $current_comment; ?>
<div class="bboss_ajax_search_item bboss_ajax_search_item_posts_comments">
	<div class="item-avatar">
		<a href="<?php comment_author_link( $current_comment ); ?>">
			<?php echo get_avatar( $current_comment->user_id, 50 );?>
		</a>
	</div>

	<div class="item">
		<a href="<?php comment_link( $current_comment ); ?>">
			<div class="item-desc"><?php echo buddyboss_global_search_result_intro( $current_comment->comment_content, 100 );?></div>
		</a>
	</div>
</div>
<?php
/**
 * Comment search Template
 *
 * @package BuddyBoss\Core
 * @since   BuddyBoss [BBVERSION]
 * @version 1.0.0
 */

global $current_comment;
?>
<li class="bp-search-item bp-search-item_posts_comments">
	<div class="list-wrap">
		<div class="item-avatar">
			<a href="<?php comment_author_link( $current_comment ); ?>" data-bb-hp-profile="<?php echo esc_attr( $current_comment->user_id ); ?>">
				<?php echo get_avatar( $current_comment->user_id, 50 ); ?>
			</a>
		</div>

		<div class="item">
			<a href="<?php comment_link( $current_comment ); ?>">
				<div class="item-desc"><?php echo bp_search_result_intro( $current_comment->comment_content, 150 ); ?></div>
			</a>
		</div>
	</div>
</li>

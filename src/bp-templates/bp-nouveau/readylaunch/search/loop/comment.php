<?php
/**
 * ReadyLaunch - Search Loop Comment template.
 *
 * The template for search results for comments.
 *
 * @package BuddyBoss\Template
 * @subpackage BP_Nouveau\ReadyLaunch
 * @since BuddyBoss 2.9.00
 * @version 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

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

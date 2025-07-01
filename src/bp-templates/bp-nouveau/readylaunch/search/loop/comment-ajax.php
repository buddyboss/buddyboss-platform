<?php
/**
 * ReadyLaunch - Search Loop Comment AJAX template.
 *
 * The template for AJAX search results for comments.
 *
 * @package BuddyBoss\Template
 * @subpackage BP_Nouveau\ReadyLaunch
 * @since BuddyBoss 2.9.00
 * @version 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

global $current_comment; ?>
<div class="bp-search-ajax-item bp-search-ajax-item_posts_comments">
	<div class="item-avatar">
		<a href="<?php comment_author_link( $current_comment ); ?>">
			<?php echo get_avatar( $current_comment->user_id, 50 ); ?>
		</a>
	</div>

	<div class="item">
		<a href="<?php comment_link( $current_comment ); ?>">
			<div class="item-desc"><?php echo bp_search_result_intro( $current_comment->comment_content, 100 ); ?></div>
		</a>
	</div>
</div>

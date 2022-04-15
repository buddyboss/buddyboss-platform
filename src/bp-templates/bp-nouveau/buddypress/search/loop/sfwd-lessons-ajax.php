<?php
/**
 * Template for displaying the search results of the lessons ajax
 *
 * This template can be overridden by copying it to yourtheme/buddypress/search/loop/sfwd-lessons-ajax.php.
 *
 * @package BuddyBoss\Core
 * @since   BuddyBoss 1.0.0
 * @version 1.0.0
 */

$total = bp_search_get_total_topics_count( get_the_ID() ) ?>
<div class="bp-search-ajax-item bp-search-ajax-item_sfwd-lessons">
	<a href="<?php echo esc_url(add_query_arg( array( 'no_frame' => '1' ), get_permalink() ));?>">
		<div class="item-avatar">
			<img
				src="<?php echo get_the_post_thumbnail_url() ?: bp_search_get_post_thumbnail_default(get_post_type()) ?>"
				class="attachment-post-thumbnail size-post-thumbnail wp-post-image"
				alt="<?php the_title() ?>"
			/>
		</div>

		<div class="item">
			<div class="item-title"><?php the_title();?></div>
			<div class="item-desc"><?php
            //@todo remove %d?
			printf( _n( '%d topic', '%d topics', $total, 'buddyboss' ), $total ); ?></div>

		</div>
	</a>
</div>

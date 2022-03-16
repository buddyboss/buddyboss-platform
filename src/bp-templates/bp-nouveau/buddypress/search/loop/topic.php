<?php
/**
 * Template for displaying the search results of the topic
 *
 * This template can be overridden by copying it to yourtheme/buddypress/search/loop/topic.php.
 *
 * @package BuddyBoss\Core
 * @since   BuddyBoss 1.0.0
 * @version 1.0.0
 */

$topic_id = get_the_ID();
$total    = bbp_get_topic_reply_count( $topic_id ) ?>
<li class="bp-search-item bp-search-item_topic">
	<div class="list-wrap">
		<div class="item-avatar">
			<a href="<?php bbp_topic_permalink( $topic_id ); ?>">
				<img
					src="<?php echo esc_url( bbp_get_forum_thumbnail_src( bbp_get_forum_id( $topic_id ) ) ?: bp_search_get_post_thumbnail_default( get_post_type() ) ); ?>"
					class="avatar forum-avatar"
					height="150"
					width="150"
					alt=""
				/>
			</a>
		</div>

		<div class="item">
			<h3 class="entry-title item-title">
				<a href="<?php bbp_topic_permalink( $topic_id ); ?>"><?php bbp_topic_title( $topic_id ); ?></a>
			</h3>
			<div class="entry-content entry-summary">
				<?php echo wp_kses_post( wp_trim_words( bbp_get_topic_content( $topic_id ), 30, '...' ) ); ?>
			</div>
			<div class="entry-meta">
				<span class="reply-count">
					<?php printf( _n( '%d reply', '%d replies', $total, 'buddyboss' ), $total ); ?>
				</span>
				<span class="middot">&middot;</span>
				<span class="freshness">
					<?php bbp_topic_freshness_link( $topic_id ); ?>
				</span>
			</div>
		</div>
	</div>
</li>

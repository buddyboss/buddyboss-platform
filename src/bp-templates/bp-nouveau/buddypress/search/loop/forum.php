<?php
/**
 * Template for displaying the search results of the forum
 *
 * This template can be overridden by copying it to yourtheme/buddypress/search/loop/forum.php.
 *
 * @package BuddyBoss\Core
 * @since   BuddyBoss 1.0.0
 * @version 1.0.0
 */

$forum_id    = get_the_ID();
$total_topic = bbp_get_forum_topic_count( $forum_id );
$total_reply = bbp_get_forum_reply_count( $forum_id );
$result      = bp_search_is_post_restricted( $forum_id, get_current_user_id(), 'forum' );
?>
<li class="bp-search-item bp-search-item_forum">
	<div class="list-wrap">
		<div class="item-avatar">
			<a href="<?php bbp_forum_permalink( get_the_ID() ); ?>">
				<?php
				if ( $result['has_thumb'] ) {
					?>
					<img src="<?php echo esc_url( $result['post_thumbnail'] ); ?>" class="avatar forum-avatar" height="150" width="150" alt=""/>
					<?php
				} else {
					?>
					<i class="bb-icon-f <?php echo esc_attr( $result['post_thumbnail'] ); ?>"></i>
					<?php
				}
				?>
			</a>
		</div>

		<div class="item">
			<div class="entry-title item-title">
				<a href="<?php bbp_forum_permalink( $forum_id ); ?>"><?php bbp_forum_title( $forum_id ); ?></a>
			</div>
			<div class="entry-content entry-summary">
				<?php echo wp_kses_post( $result['post_content'] ); ?>
			</div>
			<div class="entry-meta">
				<span class="topic-count">
					<?php
					printf(
						/* translators: total topics */
						_n( '%d topic', '%d topics', $total_topic, 'buddyboss' ),
						absint( $total_topic )
					);
					?>
				</span>
				<span class="middot">&middot;</span>
				<span class="reply-count">
					<?php
					printf(
						/* translators: total replies */
						_n( '%d reply', '%d replies', $total_reply, 'buddyboss' ),
						absint( $total_reply )
					);
					?>
				</span>

				<?php
				$last_active = bbp_get_forum_last_active_time( $forum_id );
				if ( $last_active ) {
					?>
					<span class="middot">&middot;</span>
					<span class="freshness">
						<?php
						esc_html_e( 'Last active ', 'buddyboss' );
						echo wp_kses_post( $last_active );
						?>
					</span>
					<?php
				}
				?>
			</div>
		</div>
	</div>
</li>

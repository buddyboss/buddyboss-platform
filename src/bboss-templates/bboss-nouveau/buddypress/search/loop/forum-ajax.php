<?php
/**
 * Template for displaying the search results of the forum ajax
 *
 * This template can be overridden by copying it to yourtheme/buddypress/search/loop/forum-ajax.php.
 *
 * @package BuddyBoss\Core
 * @since   BuddyBoss 1.0.0
 * @version 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

$forum_id    = get_the_ID();
$total       = bboss_bbp_get_forum_topic_count( $forum_id );
$result      = bboss_search_is_post_restricted( $forum_id, get_current_user_id(), 'forum' );
$total_reply = bboss_bbp_get_forum_reply_count( $forum_id );

?>
<div class="bp-search-ajax-item bp-search-ajax-item_forum">
	<a href="<?php echo esc_url( add_query_arg( array( 'no_frame' => '1' ), bboss_bbp_get_forum_permalink( $forum_id ) ) ); ?>">
		<div class="item-avatar">
			<?php
			if ( $result['has_thumb'] ) {
				?>
				<img src="<?php echo esc_url( $result['post_thumbnail'] ); ?>" class="attachment-post-thumbnail size-post-thumbnail wp-post-image" alt="<?php echo esc_attr( get_the_title() ); ?>" />
				<?php
			} else {
				?>
				<i class="bb-icon-f <?php echo esc_attr( $result['post_thumbnail'] ); ?>"></i>
				<?php
			}
			?>
		</div>
		<div class="item">
			<div class="item-title"><?php bboss_bbp_forum_title( $forum_id ); ?></div>
			<div class="item-desc">
				<?php echo wp_kses_post( $result['post_content'] ); ?>
				<div class="entry-meta">
					<span class="topic-count">
						<?php
						echo esc_html(
							sprintf(
							/* translators: total topics */
								_n( '%d topic', '%d topics', $total, 'buddyboss-platform' ),
								$total
							)
						);
						?>
					</span>
					<span class="middot">&middot;</span>
					<span class="reply-count">
						<?php
						echo esc_html(
							sprintf(
							/* translators: total replies */
								_n( '%d reply', '%d replies', $total_reply, 'buddyboss-platform' ),
								$total_reply
							)
						);
						?>
					</span>
					<?php
					$last_active = bboss_bbp_get_forum_last_active_time( $forum_id );
					if ( $last_active ) {
						?>
						<span class="middot">&middot;</span>
						<span class="freshness">
							<?php
							esc_html_e( 'Last active ', 'buddyboss-platform' );
							echo wp_kses_post( $last_active );
							?>
						</span>
						<?php
					}
					?>
				</div>
			</div>
		</div>
	</a>
</div>

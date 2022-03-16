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

$total  = bbp_get_forum_topic_count( get_the_ID() );
$result = bp_search_is_post_restricted( get_the_ID(), get_current_user_id(), 'forum' );
?>
<div class="bp-search-ajax-item bp-search-ajax-item_forum">
	<a href="<?php echo esc_url( add_query_arg( array( 'no_frame' => '1' ), bbp_get_forum_permalink( get_the_ID() ) ) ); ?>">
		<div class="item-avatar">
			<?php
			if ( $result['has_thumb'] ) {
				?>
				<img src="<?php echo esc_url( $result['post_thumbnail'] ); ?>" class="attachment-post-thumbnail size-post-thumbnail wp-post-image" alt="<?php echo esc_attr( get_the_title() ); ?>" />
				<?php
			} else {
				?>
				<i class=" <?php echo esc_attr( $result['post_thumbnail'] ); ?>"></i>
				<?php
			}
			?>

		</div>
		<div class="item">
			<div class="item-title"><?php bbp_forum_title( get_the_ID() ); ?></div>
			<div class="item-desc">
			<?php
			// @todo take %d out of this?
			printf( _n( '%d topic', '%d topics', $total, 'buddyboss' ), $total );
			?>
			</div>
		</div>
	</a>
</div>

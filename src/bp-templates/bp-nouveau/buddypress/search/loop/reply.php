<?php
/**
 * Template for displaying the search results of the reply
 *
 * This template can be overridden by copying it to yourtheme/buddypress/search/loop/reply.php.
 *
 * @package BuddyBoss\Core
 * @since   BuddyBoss 1.0.0
 * @version 1.0.0
 */

$reply_id = get_the_ID();
$topic_id = bbp_get_reply_topic_id( $reply_id );
?>
<li class="bp-search-item bp-search-item_reply">
	<div class="list-wrap">
		<div class="item-avatar">
			<a href="<?php bbp_reply_url( $reply_id ); ?>" class="bp-search-item_reply_link">
				<?php
				$args   = array(
					'type'    => 'avatar',
					'post_id' => get_the_ID(),
				);
				$avatar = bbp_get_reply_author_link( $args );

				if ( $avatar ) {
					echo wp_kses_post( $avatar );
				} else {
					?>
					<i class="bb-icon-f <?php echo esc_attr( bp_search_get_post_thumbnail_default( get_post_type(), 'icon' ) ); ?>"></i>
					<?php
				}
				?>
			</a>
		</div>

		<div class="item">
			<div class="entry-title item-title">
				<?php
				$bbp_get_reply_author_url = bbp_get_reply_author_url( $reply_id );
				if ( ! empty( $bbp_get_reply_author_url ) ) {
					?>
					<a href="<?php echo $bbp_get_reply_author_url; ?>"><?php bbp_reply_author_display_name( $reply_id ); ?></a>
					<?php
				} else {
					?>
					<span><?php bbp_reply_author_display_name( $reply_id ); ?></span>
					<?php
				}
				?>

				<a href="<?php bbp_reply_url( $reply_id ); ?>"><?php esc_html_e( 'replied to a discussion', 'buddyboss' ); ?></a>
			</div>
			<div class="entry-content entry-summary">
				<?php echo wp_kses_post( wp_trim_words( bbp_get_reply_content( $reply_id ), 30, '...' ) ); ?>
			</div>
			<div class="entry-meta">
				<span class="datetime">
					<?php bbp_reply_post_date( $reply_id, true ); ?>
				</span>
			</div>
		</div>
	</div>
</li>

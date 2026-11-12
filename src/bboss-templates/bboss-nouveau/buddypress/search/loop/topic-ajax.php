<?php
/**
 * Template for displaying the search results of the topic ajax
 *
 * This template can be overridden by copying it to yourtheme/buddypress/search/loop/topic-ajax.php.
 *
 * @package BuddyBoss\Core
 * @since   BuddyBoss 1.0.0
 * @version 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

$topic_id = get_the_ID();
$total    = bboss_bbp_get_topic_reply_count( $topic_id );
?>
<div class="bp-search-ajax-item bp-search-ajax-item_topic">
	<a href="<?php echo esc_url( add_query_arg( array( 'no_frame' => '1' ), bboss_bbp_get_topic_permalink( $topic_id ) ) ); ?>">
		<div class="item-avatar">
			<?php
			$args   = array(
				'type'    => 'avatar',
				'post_id' => $topic_id,
			);
			$avatar = bboss_bbp_get_topic_author_link( $args );

			if ( $avatar ) {
				echo wp_kses_post( $avatar );
			} else {
				?>
				<i class="<?php echo esc_attr( bboss_search_get_post_thumbnail_default( get_post_type(), 'icon' ) ); ?>"></i>
				<?php
			}
			?>
		</div>
		<div class="item">
			<div class="item-title">
				<?php echo wp_kses_post( stripslashes( wp_strip_all_tags( bboss_bbp_get_topic_title( $topic_id ) ) ) ); ?>
			</div>
			<div class="item-desc">
				<span><?php echo wp_kses_post( wp_trim_words( bboss_bbp_get_topic_content( $topic_id ), 30, '...' ) ); ?></span><br>
			</div>
			<div class="entry-meta">
				<span><?php echo esc_html__( 'By ', 'buddyboss-platform' ) . esc_html( bboss_core_get_user_displayname( bboss_bbp_get_topic_author_id( $topic_id ) ) ); ?></span>
				<span class="middot">&middot;</span>
				<?php
				/* translators: %s: number. */
				printf( esc_html( _n( '%d reply', '%d replies', $total, 'buddyboss-platform' ) ), (int) $total );
				?>
				<span class="middot">&middot;</span>
				<span>
					<?php
					esc_html_e( 'Started ', 'buddyboss-platform' );
					echo wp_kses_post( bboss_bbp_get_topic_created_time( $topic_id ) );
					?>
				</span>
			</div>
		</div>
	</a>
</div>

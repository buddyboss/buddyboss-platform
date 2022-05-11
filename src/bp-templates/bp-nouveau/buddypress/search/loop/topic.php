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
				<?php
				$args   = array(
					'type'    => 'avatar',
					'post_id' => $topic_id,
				);
				$avatar = bbp_get_topic_author_link( $args );

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
			<h3 class="entry-title item-title">
				<a href="<?php bbp_topic_permalink( $topic_id ); ?>"><?php bbp_topic_title( $topic_id ); ?></a>
			</h3>
			<div class="entry-content entry-summary">
				<?php echo wp_kses_post( wp_trim_words( bbp_get_topic_content( $topic_id ), 30, '...' ) ); ?>
			</div>
			<div class="entry-meta">
				<span><?php echo esc_html__( 'By ', 'buddyboss' ) . esc_html( bp_core_get_user_displayname( bbp_get_topic_author_id( $topic_id ) ) ); ?></span>
				<span class="middot">&middot;</span>
				<span class="reply-count">
					<?php printf( _n( '%d reply', '%d replies', $total, 'buddyboss' ), $total ); ?>
				</span>
				<span class="middot">&middot;</span>
				<span>
					<?php esc_html_e( 'Started ', 'buddyboss' ); ?>
					<?php echo wp_kses_post( bbp_get_topic_created_time( $topic_id ) ); ?>
				</span>
			</div>
		</div>
	</div>
</li>

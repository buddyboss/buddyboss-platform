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

$total = bbp_get_topic_reply_count( get_the_ID() ) ?>
<div class="bp-search-ajax-item bp-search-ajax-item_topic">
	<a href="<?php echo esc_url( add_query_arg( array( 'no_frame' => '1' ), bbp_get_topic_permalink( get_the_ID() ) ) ); ?>">
		<div class="item-avatar">
			<?php
			$args   = array(
				'type'    => 'avatar',
				'post_id' => get_the_ID(),
			);
			$avatar = bbp_get_topic_author_link( $args );

			if ( $avatar ) {
				echo wp_kses_post( $avatar );
			} else {
				?>
				<i class="<?php echo esc_attr( bp_search_get_post_thumbnail_default( get_post_type(), 'icon' ) ); ?>"></i>
				<?php
			}
			?>
		</div>
		<div class="item">
			<div class="item-title">
				<?php echo wp_kses_post( stripslashes( wp_strip_all_tags( bbp_get_topic_title( get_the_ID() ) ) ) ); ?>
			</div>
			<div class="item-desc">
				<span><?php echo wp_kses_post( wp_trim_words( bbp_get_topic_content( get_the_ID() ), 30, '...' ) ); ?></span><br>
			</div>
			<div class="entry-meta">
				<span><?php echo esc_html__( 'By ', 'buddyboss' ) . esc_html( bp_core_get_user_displayname( bbp_get_topic_author_id( get_the_ID() ) ) ); ?></span>
				<span class="middot">&middot;</span>
				<?php
				// @todo remove %d?
				printf( _n( '%d reply', '%d replies', $total, 'buddyboss' ), $total );
				?>
				<span class="middot">&middot;</span>
				<span>
					<?php esc_html_e( 'Started ', 'buddyboss' ); ?>
					<?php echo wp_kses_post( bbp_get_topic_created_time( get_the_ID() ) ); ?>
				</span>
			</div>
		</div>
	</a>
</div>

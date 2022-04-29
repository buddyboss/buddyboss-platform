<?php
/**
 * Template for displaying the search results of the reply ajax
 *
 * This template can be overridden by copying it to yourtheme/buddypress/search/loop/reply-ajax.php.
 *
 * @package BuddyBoss\Core
 * @since   BuddyBoss 1.0.0
 * @version 1.0.0
 */
?>
<div class="bp-search-ajax-item bp-search-ajax-item_reply">
	<a href="<?php echo esc_url( add_query_arg( array( 'no_frame' => '1' ), bbp_get_reply_url( get_the_ID() ) ) ); ?>">
		<div class="item-avatar">
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
				<i class="<?php echo esc_attr( bp_search_get_post_thumbnail_default( get_post_type(), 'icon' ) ); ?>"></i>
				<?php
			}
			?>
		</div>
		<div class="item">
			<div class="entry-title item-title">
				<a href="<?php bbp_reply_url( get_the_ID() ); ?>"><?php bbp_reply_author_display_name( get_the_ID() ); ?></a>
				<?php esc_html_e( 'replied to a discussion', 'buddyboss' ); ?>
			</div>
			<div class="item-desc">				
				<?php echo wp_kses_post( wp_trim_words( bbp_get_reply_content( get_the_ID() ), 30, '...' ) ); ?>
			</div>

			<div class="entry-meta">
				<?php bbp_reply_post_date( get_the_ID(), true ); ?>
			</div>
		</div>
	</a>
</div>

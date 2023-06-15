<?php
/**
 * Template for displaying the search results of the post ajax
 *
 * This template can be overridden by copying it to yourtheme/buddypress/search/loop/post-ajax.php.
 *
 * @package BuddyBoss\Core
 * @since   BuddyBoss 1.0.0
 * @version 1.0.0
 */

$result = bp_search_is_post_restricted( get_the_ID(), get_current_user_id(), 'post' );
?>
<div class="bp-search-ajax-item bp-search-ajax-item_post">
	<a href="<?php echo esc_url( add_query_arg( array( 'no_frame' => '1' ), get_permalink() ) ); ?>">
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
			<div class="item-title"><?php the_title(); ?></div>
			<?php
				$content = wp_strip_all_tags( $result['post_content'] );
				preg_match_all( '^\[(.*?)\]^', $content, $matches, PREG_PATTERN_ORDER );  // strip all shortcodes in the ajax search content.
				$content         = str_replace( $matches[0], '', $content );
				$trimmed_content = wp_trim_words( $content, 20, '&hellip;' );
			?>
			<div class="item-desc"><?php echo wp_kses_post( $trimmed_content ); ?></div>
			<div class="entry-meta">
				<span class="author">
					<?php
					/* translators: %s author name */
					printf( esc_html__( 'By %s', 'buddyboss' ), get_the_author_link() );
					?>
				</span>
				<span class="middot">&middot;</span>
				<span class="published">
					<?php echo get_the_date(); ?>
				</span>
			</div>

		</div>
	</a>
</div>

<?php
/**
 * Template for displaying the search results of the topic
 *
 * This template can be overridden by copying it to yourtheme/buddypress/search/loop/sfwd-topic.php.
 *
 * @package BuddyBoss\Core
 * @since   BuddyBoss 1.0.0
 * @version 1.0.0
 */

$total = bp_search_get_total_quizzes_count( get_the_ID() ) ?>
<li class="bp-search-item bp-search-item_sfwd-topic">
	<div class="list-wrap">
		<div class="item-avatar">
			<a href="<?php the_permalink(); ?>">
				<img
					src="<?php echo get_the_post_thumbnail_url() ?: bp_search_get_post_thumbnail_default( get_post_type() ); ?>"
					class="attachment-post-thumbnail size-post-thumbnail wp-post-image"
					alt="<?php the_title(); ?>"
				/>
			</a>
		</div>

		<div class="item">
			<h3 class="entry-title item-title">
				<a href="<?php the_permalink(); ?>" title="<?php echo esc_attr( sprintf( __( 'Permalink to %s', 'buddyboss' ), the_title_attribute( 'echo=0' ) ) ); ?>" rel="bookmark"><?php the_title(); ?></a>
			</h3>

			<?php
			if ( get_the_excerpt( get_the_ID() ) ) {
				echo mb_strimwidth( get_the_excerpt( get_the_ID() ), 0, 100 ) . '...';
			} elseif ( get_the_content( get_the_ID() ) ) {
				echo mb_strimwidth( get_the_content( get_the_ID() ), 0, 100 ) . '...';
			}
			?>

			<div class="entry-content entry-summary">
				<?php printf( _n( '%d quiz', '%d quizzes', $total, 'buddyboss' ), $total ); ?>
			</div>
		</div>
	</div>
</li>

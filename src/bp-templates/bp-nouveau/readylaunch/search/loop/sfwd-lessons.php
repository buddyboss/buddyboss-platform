<?php
/**
 * ReadyLaunch - Search Loop SFWD Lessons template.
 *
 * The template for search results for LearnDash lessons.
 *
 * @package BuddyBoss\Template
 * @subpackage BP_Nouveau\ReadyLaunch
 * @since BuddyBoss 2.9.00
 * @version 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

$lessons_id         = get_the_ID();
$total              = bp_search_get_total_topics_count( $lessons_id );
$post_thumbnail_url = get_the_post_thumbnail_url();
?>
<li class="bp-search-item bp-search-item_sfwd-lessons">
	<div class="list-wrap">
		<div class="item-avatar">
			<a href="<?php the_permalink(); ?>">
				<?php
				if ( $post_thumbnail_url ) {
					?>
					<img src="<?php echo esc_url( $post_thumbnail_url ); ?>" class="attachment-post-thumbnail size-post-thumbnail wp-post-image" alt="<?php echo esc_attr( get_the_title() ); ?>"  />
					<?php
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
				<a href="<?php the_permalink(); ?>" title="<?php echo esc_attr( sprintf( __( 'Permalink to %s', 'buddyboss' ), the_title_attribute( 'echo=0' ) ) ); ?>" rel="bookmark"><?php the_title(); ?></a>
			</h3>

			<div class="entry-content">
				<?php
				if ( get_the_excerpt( $lessons_id ) ) {
					echo bp_create_excerpt(
						get_the_excerpt( $lessons_id ),
						100,
						array(
							'ending' => __( '&hellip;', 'buddyboss' ),
						)
					);
				} elseif ( get_the_content( $lessons_id ) ) {
					echo bp_create_excerpt(
						wp_strip_all_tags( get_the_content( $lessons_id ) ),
						100,
						array(
							'ending' => __( '&hellip;', 'buddyboss' ),
						)
					);
				}
				?>
			</div>

			<div class="entry-meta">
				<?php printf( _n( '%d topic', '%d topics', $total, 'buddyboss' ), $total ); ?>
			</div>
		</div>
	</div>
</li>

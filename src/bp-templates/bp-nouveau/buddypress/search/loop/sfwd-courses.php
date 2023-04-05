<?php
/**
 * Template for displaying the search results of the courses
 *
 * This template can be overridden by copying it to yourtheme/buddypress/search/loop/sfwd-courses.php.
 *
 * @package BuddyBoss\Core
 * @since   BuddyBoss 1.0.0
 * @version 1.0.0
 */

$course_id         = get_the_ID();
$total             = bp_search_get_total_lessons_count( $course_id );
$meta              = get_post_meta( $course_id, '_sfwd-courses', true );
$course_price_type = @$meta['sfwd-courses_course_price_type'];
$course_price      = @$meta['sfwd-courses_course_price'];
?>
<li class="bp-search-item bp-search-item_sfwd-courses">
	<div class="list-wrap">
		<div class="item-avatar">
			<a href="<?php the_permalink(); ?>">
				<?php
				if ( get_the_post_thumbnail_url() ) {
					?>
					<img src="<?php echo get_the_post_thumbnail_url() ?: bp_search_get_post_thumbnail_default( get_post_type() ); ?>" class="attachment-post-thumbnail size-post-thumbnail wp-post-image" alt="<?php the_title(); ?>" />
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

			<div class="entry-summary">
				<?php
				if ( get_the_excerpt( $course_id ) ) {
					echo bp_create_excerpt(
						wp_strip_all_tags( get_the_excerpt( $course_id ) ),
						100,
						array(
							'ending' => __( '&hellip;', 'buddyboss' ),
						)
					);
				}
				?>
			</div>
			

			<div class="entry-meta">
			
				<?php if ( ! empty( learndash_course_status( $course_id ) ) ) : ?>
					<span class="course-status">
						<?php echo learndash_course_status( $course_id, null, false ); ?>
					</span>
				<?php endif; ?>
				
				<span class="middot">&middot;</span>
				
				<span><?php printf( _n( '%d lesson', '%s lessons', $total, 'buddyboss' ), $total ); ?></span>
				
			</div>

			<?php
			// format the Course price to be proper XXX.YY no leading dollar signs or other values.
			if ( ( 'paynow' === $course_price_type ) || ( 'subscribe' === $course_price_type ) ) {
				if ( $course_price != '' ) {
					$course_price = preg_replace( '/[^0-9.]/', '', $course_price );
					?>
					<div class="item-extra entry-meta"><?php echo number_format( floatval( $course_price ), 2, '.', '' ); ?></div>
					<?php
				}
			}
			?>



		</div>
	</div>
</li>

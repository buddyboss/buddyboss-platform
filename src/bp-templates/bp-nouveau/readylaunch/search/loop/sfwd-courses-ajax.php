<?php
/**
 * ReadyLaunch - Search Loop SFWD Courses AJAX template.
 *
 * The template for AJAX search results for LearnDash courses.
 *
 * @package BuddyBoss\Template
 * @subpackage BP_Nouveau\ReadyLaunch
 * @since BuddyBoss 2.9.00
 * @version 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

$course_id          = get_the_ID();
$total              = bp_search_get_total_lessons_count( $course_id );
$post_thumbnail_url = get_the_post_thumbnail_url();
?>
<div class="bp-search-ajax-item bp-search-ajax-item_sfwd-courses">
	<a href="<?php echo esc_url( get_permalink() ); ?>">
		<div class="item-avatar">
			<?php
			if ( $post_thumbnail_url ) {
				?>
				<img src="<?php echo esc_url( $post_thumbnail_url ); ?>" class="attachment-post-thumbnail size-post-thumbnail wp-post-image" alt="<?php the_title(); ?>" />
				<?php
			} else {
				?>
				<i class="bb-icon-f <?php echo esc_attr( bp_search_get_post_thumbnail_default( get_post_type(), 'icon' ) ); ?>"></i>
				<?php
			}
			?>
		</div>
	</a>
	<div class="item">
		<div class="item-title">
			<a href="<?php echo esc_url( get_permalink() ); ?>"><?php the_title(); ?></a>
		</div>
		<div class="item-desc">
			<?php
			$excerpt = get_the_excerpt( $course_id );
			if ( $excerpt ) {
				echo bp_create_excerpt(
					wp_strip_all_tags( $excerpt ),
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
			<?php
			// @todo remove %d?
			printf( _n( '%d lesson', '%d lessons', $total, 'buddyboss' ), $total );
			?>
		</div>
	</div>
</div>

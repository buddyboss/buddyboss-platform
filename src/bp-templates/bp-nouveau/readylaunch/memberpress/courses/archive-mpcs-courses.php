<?php
/**
 * Template for course archive page for memberpress courses.
 *
 * This template can be overridden by copying it to yourtheme/memberpress/courses/archive-mpcs-courses.php.
 *
 * @since 2.9.00
 *
 * @package BuddyBoss\MemberpressLMS
 */

use memberpress\courses\models;
use memberpress\courses\helpers;
use memberpress\courses\lib;

global $wp, $post;
$filter_base_url = home_url( $wp->request );
$pos             = strpos( $filter_base_url, '/page' );
$courses_page    = get_home_url( null, helpers\Courses::get_permalink_base() );

if ( $pos > 0 ) {
	$filter_base_url = substr( $filter_base_url, 0, $pos );
}

$course           = new models\Course( $post->ID );
$progress         = $course->user_progress( get_current_user_id() );
$categories       = get_the_terms( $course->ID, 'mpcs-course-categories' );
$course_is_locked = false;

if ( MeprRule::is_locked( $post ) && helpers\Courses::is_course_archive() ) {
	$course_is_locked = true;
}
?>

<div class="bb-rl-course-card bb-rl-course-card--mbprlms">
	<div class="bb-rl-course-item">
		<div class="bb-rl-course-image">
			<?php if ( $course_is_locked ) { ?>
				<div class="locked-course-overlay">
					<i class="mpcs-icon mpcs-lock"></i>
				</div>
			<?php } ?>
			<a href="<?php the_permalink(); ?>" title="<?php the_title_attribute(); ?>">
				<?php
				if ( has_post_thumbnail() ) :
					the_post_thumbnail( apply_filters( 'mpcs_course_thumbnail_size', 'mpcs-course-thumbnail' ), array( 'class' => 'img-responsive' ) );
				else :
					?>
					<img src="<?php echo esc_url( buddypress()->plugin_url . 'bp-templates/bp-nouveau/readylaunch/images/group_cover_image.jpeg' ); ?>" alt="<?php esc_attr_e( 'Course placeholder image', 'buddyboss' ); ?>">
				<?php endif; ?>
			</a>
		</div>
		<div class="bb-rl-course-card-content">
			<div class="bb-rl-course-body">
				<h2 class="bb-rl-course-title"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>
				<div class="bb-rl-course-meta">
					<div class="bb-rl-course-category">
						<?php if ( ! empty( $categories ) ) : ?>
							<div class="card-categories">
								<?php foreach ( $categories as $category ) : ?>
									<a class="card-category-name" href="<?php echo esc_url( add_query_arg( 'category', $category->slug, $filter_base_url ) ); ?>">
										<?php echo esc_html( $category->name ); ?>
										<span class="card-category__separator">,</span></a>
								<?php endforeach; ?>
							</div>
						<?php endif; ?>
					</div>
					<div class="bb-rl-course-excerpt">
						<?php the_excerpt(); ?>
					</div>
					<div class="bb-rl-course-author">
						<?php
						$user_id    = get_the_author_meta( 'ID' );
						$author_url = bp_core_get_user_domain( $user_id );
						?>
						<a href="<?php echo esc_url( $author_url ); ?>" class="item-avatar bb-rl-author-avatar">
							<?php
							echo bp_core_fetch_avatar(
								array(
									'item_id' => $user_id,
									'html' => true,
								)
							);
							?>
						</a>
						<span class="bb-rl-author-name">
							<?php
							$author_name = bp_core_get_user_displayname( $user_id );
							// translators: %s is the author name.
							printf( esc_html__( 'By %s', 'buddyboss' ), '<a href="' . esc_url( $author_url ) . '">' . esc_html( $author_name ) . '</a>' );
							?>
						</span>
					</div>
				</div>
			</div>
			<div class="bb-rl-course-footer">
				<?php
				$is_enrolled = models\UserProgress::has_started_course( get_current_user_id(), $course->ID );

				if ( $is_enrolled ) :
					// Get lessons count.
					$total_lessons = $course->number_of_lessons();
					// If total lessons are n and progress is p% then get completed lesson count.
					$completed_lessons = ceil( $total_lessons * $progress / 100 );
					?>
					<div class="mpcs-progress-wrap">
						<div class="mpcs-progress-data">
							<strong class="mpcs-progress-lessons"><?php echo esc_html( $completed_lessons . '/' . $total_lessons ); ?></strong>
							<span class="mpcs-progress-per"><strong><?php echo esc_html( $progress . '%' ); ?></strong> <?php esc_html_e( ' Complete', 'buddyboss' ); ?></span>
						</div>
						<div class="mpcs-progress-bar">
							<div class="mpcs-progress-bar-inner" style="width: <?php echo esc_attr( $progress ); ?>%;"></div>
						</div>
					</div>
					<?php
					$next_lesson = models\UserProgress::next_lesson( get_current_user_id(), $course->ID );
					if ( false !== $next_lesson && is_object( $next_lesson ) ) {
						?>
						<a href="<?php echo esc_url( get_permalink( $next_lesson->ID ) ); ?>" class="bb-rl-course-link bb-rl-button bb-rl-button--secondaryFill bb-rl-button--small">
							<?php esc_html_e( 'Continue Course', 'buddyboss' ); ?><i class="bb-icons-rl-caret-right"></i>
						</a>
						<?php
					}
					?>
				<?php else : ?>
					<a href="<?php the_permalink(); ?>" class="bb-rl-course-link bb-rl-button bb-rl-button--secondaryFill bb-rl-button--small">
						<?php esc_html_e( 'View Course', 'buddyboss' ); ?>
						<i class="bb-icons-rl-caret-right"></i>
					</a>
				<?php endif; ?>
			</div>
		</div>
	</div>
	<div class="bb-rl-course-card-popup">
		<div class="bb-rl-course-timestamp">
			<?php
			$course_update_date = BB_Readylaunch_Memberpress_Courses_Helper::bb_rl_mpcs_get_course_update_date( $course->ID, get_option( 'date_format' ) );
			// translators: %s is the updated date.
			printf( esc_html__( 'Updated: %s', 'buddyboss' ), esc_html( $course_update_date ) );
			?>
		</div>
		<div class="bb-rl-course-popup-meta">
			<?php
			$total_lessons = $course->number_of_lessons();
			?>
			<span class="bb-rl-course-meta-tag"><?php echo esc_html( $total_lessons ); ?></span>
			<span class="bb-rl-course-meta-tag"><?php esc_html_e( 'Lessons', 'buddyboss' ); ?></span>
		</div>
		<div class="bb-rl-course-popup-caption">
			<?php the_excerpt(); ?>
		</div>
		<div class="bb-rl-course-author">
			<h4><?php esc_html_e( 'Instructor', 'buddyboss' ); ?></h4>
			<?php
			$author_id   = get_the_author_meta( 'ID' );
			$author_name = bp_core_get_user_displayname( $user_id );
			?>
			<span class="bb-rl-author-avatar">
				<?php
				echo bp_core_fetch_avatar(
					array(
						'item_id' => $user_id,
						'html' => true,
					)
				);
				?>
			</span>
			<span class="bb-rl-author-name"><?php echo esc_html( $author_name ); ?></span>
		</div>
		<div class="bb-rl-course-popup-actions">
			<?php
			if ( $is_enrolled ) :
				$next_lesson = models\UserProgress::next_lesson( get_current_user_id(), $course->ID );
				if ( false !== $next_lesson && is_object( $next_lesson ) ) {
					?>
					<a href="<?php echo esc_url( get_permalink( $next_lesson->ID ) ); ?>" class="bb-rl-course-link bb-rl-button bb-rl-button--brandFill bb-rl-button--small">
						<i class="bb-icons-rl-play"></i>
						<?php esc_html_e( 'Continue', 'buddyboss' ); ?>
					</a>
					<?php
				} else {
					?>
					<a href="<?php the_permalink(); ?>" class="bb-rl-course-link bb-rl-button bb-rl-button--brandFill bb-rl-button--small">
						<i class="bb-icons-rl-play"></i>
						<?php esc_html_e( 'Continue', 'buddyboss' ); ?>
					</a>
					<?php
				}
			else :
				?>
				<a href="<?php the_permalink(); ?>" class="bb-rl-course-link bb-rl-button bb-rl-button--brandFill bb-rl-button--small">
					<i class="bb-icons-rl-play"></i>
					<?php esc_html_e( 'View Course', 'buddyboss' ); ?>
				</a>
			<?php endif; ?>
		</div>
	</div>
</div>

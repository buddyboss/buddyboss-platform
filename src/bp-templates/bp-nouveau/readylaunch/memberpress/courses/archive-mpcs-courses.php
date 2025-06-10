<?php
/**
 * Template for course archive page for memberpress courses.
 *
 * This template can be overridden by copying it to yourtheme/memberpress/courses/archive-mpcs-courses.php.
 *
 * @since [BBVERSION]
 *
 * @package BuddyBoss\MemberpressLMS
 */

use memberpress\courses\helpers;
use memberpress\courses\models;
use memberpress\courses\lib;

global $wp;
$search          = isset( $_GET['s'] ) ? esc_attr( $_GET['s'] ) : '';  // phpcs:ignore
$category        = isset( $_GET['category'] ) ? esc_attr( $_GET['category'] ) : ''; // phpcs:ignore
$author          = isset( $_GET['author'] ) ? esc_attr( $_GET['author'] ) : ''; // phpcs:ignore
$filter_base_url = home_url( $wp->request );
$pos             = strpos( $filter_base_url, '/page' );
$courses_page    = get_home_url( null, helpers\Courses::get_permalink_base() );

if ( $pos > 0 ) {
	$filter_base_url = substr( $filter_base_url, 0, $pos );
}

?>
<div class="entry entry-content" style="padding: 2em 0">
	<div class="container grid-xl">

		<div class="mpcs-course-filter columns">
			<div class="column col-sm-12">
				<div class="dropdown">
					<a href="#" class="btn btn-link dropdown-toggle" tabindex="0">
						<?php esc_html_e( 'Category', 'buddyboss-pro' ); ?>: <span></span><i class="mpcs-down-dir"></i>
					</a>
					<ul class="menu">
						<?php
						$terms = get_terms( 'mpcs-course-categories' ); // Get all terms of a taxonomy.

						printf( '<li><input type="text" class="form-input mpcs-dropdown-search" placeholder="%s" id="mpmcSearchCategory"></li>', esc_html__( 'Search', 'buddyboss-pro' ) );

						printf( '<li class="%s"><a href="%s">%s</a></li>', esc_attr( '' === $category ? 'active' : 'noactive' ), esc_url( add_query_arg( 'category', '', $filter_base_url ) ), esc_html__( 'All', 'buddyboss-pro' ) );
						foreach ( $terms as $term ) {
							printf( '<li class="%s"><a href="%s">%s</a></li>', esc_attr( $category === $term->slug ? 'active' : 'noactive' ), esc_url( add_query_arg( 'category', $term->slug, $filter_base_url ) ), esc_html( $term->name ) );
						}
						?>
					</ul>
				</div>

				<div class="dropdown">
					<a href="#" class="btn btn-link dropdown-toggle" tabindex="0">
						<?php esc_html_e( 'Author', 'buddyboss-pro' ); ?>: <span></span><i class="mpcs-down-dir"></i>
					</a>
					<!-- menu component -->
					<ul class="menu">
						<?php
						$post_authors = models\Course::post_authors();

						printf( '<li><input type="text" class="form-input mpcs-dropdown-search" placeholder="%s" id="mpmcSearchCourses"></li>', esc_html__( 'Search', 'buddyboss-pro' ) );

						printf( '<li class="%s"><a href="%s">%s</a></li>', esc_attr( empty( $author ) ? 'active' : 'noactive' ), esc_url( add_query_arg( 'author', '', $filter_base_url ) ), esc_html__( 'All', 'buddyboss-pro' ) );

						foreach ( $post_authors as $post_author ) {
							printf( '<li class="%s"><a href="%s">%s</a></li>', esc_attr( $author === $post_author->user_login ? 'active' : 'noactive' ), esc_url( add_query_arg( 'author', $post_author->user_login, $filter_base_url ) ), esc_html( lib\Utils::get_full_name( $post_author->ID ) ) );
						}
						?>
					</ul>
				</div>

				<div class="archives-authors-section">
					<ul>

					</ul>
				</div>
			</div>

			<div class="column col-sm-12">
				<form method="GET" class="" action="<?php echo esc_url( $courses_page ); ?>">
					<div class="input-group">
						<input type="text" name="s" class="form-input"
								placeholder="<?php esc_html_e( 'Find a course', 'buddyboss-pro' ); ?>"
								value="<?php echo esc_attr( $search ); ?>">
						<button class="btn input-group-btn"><i class="bb-icon-l bb-icon-search"></i></button>
					</div>
				</form>

			</div>
		</div>

		<div class="columns mpcs-cards">

			<?php
			if ( have_posts() ) :
				while ( have_posts() ) :
					the_post(); // Standard WordPress loop.
					global $post;

					$course           = new models\Course( $post->ID );
					$progress         = $course->user_progress( get_current_user_id() );
					$categories       = get_the_terms( $course->ID, 'mpcs-course-categories' );
					$course_is_locked = false;

					if ( MeprRule::is_locked( $post ) && helpers\Courses::is_course_archive() ) {
						$course_is_locked = true;
					}
					?>

					<div class="column col-3 col-md-6 col-xs-12">
						<div class="card s-rounded">
							<div class="card-image">
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
										<img src="<?php echo esc_url( bb_meprlms_integration_url( '/assets/images/course-placeholder.jpg' ) ); ?>"
											class="img-responsive" alt="">
									<?php endif; ?>
								</a>
							</div>
							<div class="card-header">
								<div class="card-title">
									<h2 class="h5"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>
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
							</div>
							<div class="card-body">
								<?php the_excerpt(); ?>
							</div>
							<div class="card-footer">
								<?php
								if ( models\UserProgress::has_started_course( get_current_user_id(), $course->ID ) ) :
									// Get lessons count.
									$total_lessons = $course->number_of_lessons();
									// If total lessons are n and progress is p% then get completed lesson count.
									$completed_lessons = ceil( $total_lessons * $progress / 100 );
									?>
									<div class="mpcs-progress-wrap">
										<div class="mpcs-progress-data">
											<strong class="mpcs-progress-lessons"><?php echo esc_html( $completed_lessons . '/' . $total_lessons ); ?></strong>
											<span class="mpcs-progress-per"><strong><?php echo esc_html( $progress . '%' ); ?></strong> <?php esc_html_e( ' Complete', 'buddyboss-pro' ); ?></span>
										</div>
										<div class="mpcs-progress-bar">
											<div class="mpcs-progress-bar-inner" style="width: <?php echo esc_attr( $progress ); ?>%;"></div>
										</div>
									</div>
									<?php
									$next_lesson = models\UserProgress::next_lesson( get_current_user_id(), $course->ID );
									if ( false !== $next_lesson && is_object( $next_lesson ) ) {
										?>
										<a href="<?php echo esc_url( get_permalink( $next_lesson->ID ) ); ?>" class="mpcs-btn-secondary">
											<i class="bb-icon-l bb-icon-play"></i><?php esc_html_e( 'Continue Course', 'buddyboss-pro' ); ?>
										</a>
										<?php
									}
									?>
								<?php else : ?>
									<span class="course-author">
										<?php
										$user_id    = get_the_author_meta( 'ID' );
										$author_url = bp_core_get_user_domain( $user_id );
										?>
										<a href="<?php echo esc_url( $author_url ); ?>">
											<?php
											echo bp_core_fetch_avatar(
												array(
													'item_id' => $user_id,
													'html' => true,
												)
											) . bp_core_get_user_displayname( $user_id );
											?>
										</a>
								</span>
								<?php endif; ?>
							</div>
						</div>
					</div>

					<?php
				endwhile; // end of the loop.
				?>
			<?php else : ?>
				<p><?php esc_html_e( 'No Course found', 'buddyboss-pro' ); ?></p>
			<?php endif; // the end of end. ?>
		</div>

		<div class="pagination">
			<?php echo helpers\Courses::archive_navigation(); // phpcs:ignore ?>
		</div>

	</div>
</div>

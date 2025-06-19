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

// Get the global query object.
global $wp_query;

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
<div class="bb-rl-secondary-header flex items-center bb-rl-secondary-header--mbprlms">
	<div class="bb-rl-entry-heading">
		<h1 class="bb-rl-page-title bb-rl-base-heading">
			<?php
			if ( is_tax() ) {
				echo single_term_title( '', false );
			} else {
				esc_html_e( 'Courses', 'buddyboss' );
			}
			?>
			<span class="bb-rl-heading-count"><?php echo esc_html( $wp_query->found_posts ); ?></span>
		</h1>
	</div>

	<div class="bb-rl-course-filters bb-rl-sub-ctrls flex items-center">

		<div class="bb-rl-grid-filters flex items-center" data-view="ld-course">
			<a href="#" class="layout-view layout-view-course layout-grid-view bp-tooltip active" data-view="grid" data-bp-tooltip-pos="down" data-bp-tooltip="<?php esc_html_e( 'Grid View', 'buddyboss' ); ?>">
				<i class="bb-icons-rl-squares-four"></i>
			</a>
			<a href="#" class="layout-view layout-view-course layout-list-view bp-tooltip" data-view="list" data-bp-tooltip-pos="down" data-bp-tooltip="<?php esc_html_e( 'List View', 'buddyboss' ); ?>">
				<i class="bb-icons-rl-rows"></i>
			</a>
		</div>

		<div class="component-filters">
			<div class="mpcs-course-filter columns bb-rl-meprlms-course-filters">
				<div class="column col-sm-12">
					<div class="dropdown">
						<a href="#" class="btn btn-link dropdown-toggle" tabindex="0">
							<?php esc_html_e( 'Category', 'buddyboss-pro' ); ?> <span></span><i class="bb-icons-rl-caret-down"></i>
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
							<?php esc_html_e( 'Author', 'buddyboss-pro' ); ?> <span></span><i class="bb-icons-rl-caret-down"></i>
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
							<button class="btn input-group-btn"><i class="bb-icons-rl-magnifying-glass"></i></button>
						</div>
					</form>

				</div>
			</div>
		</div>
	</div>
</div>
<div class="bb-rl-container-inner bb-rl-meprlms-content-wrap">	

	<div class="bb-rl-courses-grid grid bb-rl-courses-grid--mbprlms">

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
									<img src="<?php echo esc_url( bb_meprlms_integration_url( '/assets/images/course-placeholder.jpg' ) ); ?>"
										class="img-responsive" alt="">
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
										<a href="<?php echo esc_url( get_permalink( $next_lesson->ID ) ); ?>" class="bb-rl-course-link bb-rl-button bb-rl-button--secondaryFill bb-rl-button--small">
											<?php esc_html_e( 'Continue Course', 'buddyboss-pro' ); ?><i class="bb-icons-rl-caret-right"></i>
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
							$updated_date = get_the_modified_date();
							// translators: %s is the updated date.
							printf( esc_html__( 'Updated: %s', 'buddyboss' ), esc_html( $updated_date ) );
							?>
						</div>
						<div class="bb-rl-course-popup-meta">
							<?php
							$total_lessons = 5;
							?>
							<span class="bb-rl-course-meta-tag"><?php echo esc_html( $total_lessons ); ?></span>
							<span class="bb-rl-course-meta-tag"><?php esc_html_e( 'Beginner', 'buddyboss' ); ?></span>
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
							<a href="<?php the_permalink(); ?>" class="bb-rl-course-link bb-rl-button bb-rl-button--brandFill bb-rl-button--small">
								<i class="bb-icons-rl-play"></i>
								<?php
								if ( $is_enrolled ) {
									esc_html_e( 'Continue', 'buddyboss' );
								} else {
									esc_html_e( 'View Course', 'buddyboss' );
								}
								?>
							</a>
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

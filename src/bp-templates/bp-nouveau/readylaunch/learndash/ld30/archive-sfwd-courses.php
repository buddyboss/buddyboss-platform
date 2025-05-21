<?php
/**
 * LearnDash Course Archive Template for ReadyLaunch
 *
 * @package BuddyBoss\Core
 * @since BuddyBoss [BBVERSION]
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Get the ReadyLaunch instance to check if sidebar is enabled
$readylaunch = BB_Readylaunch::instance();
?>

<div class="bb-learndash-content-wrap">
	<main class="bb-learndash-content-area">
		<div class="bb-rl-courses-list">
			<header class="bb-rl-page-header">
				<h1 class="bb-rl-page-title">
					<?php
					if ( is_tax() ) {
						echo single_term_title( '', false );
					} else {
						esc_html_e( 'Courses', 'buddyboss' );
					}
					?>
				</h1>
				
				<?php
				if ( is_tax() ) {
					$term_description = term_description();
					if ( ! empty( $term_description ) ) :
						?>
						<div class="bb-rl-taxonomy-description">
							<?php echo wp_kses_post( $term_description ); ?>
						</div>
						<?php
					endif;
				}
				?>
			</header>
			
			<div class="bb-rl-course-filters">
				<div class="bb-rl-course-search">
					<form role="search" method="get" class="bb-rl-search-form" action="<?php echo esc_url( home_url( '/' ) ); ?>">
						<input type="search" class="bb-rl-search-field" placeholder="<?php esc_attr_e( 'Search courses...', 'buddyboss' ); ?>" value="<?php echo get_search_query(); ?>" name="s" />
						<input type="hidden" name="post_type" value="sfwd-courses" />
						<button type="submit" class="bb-rl-search-submit"><i class="bb-icons-rl-search"></i></button>
					</form>
				</div>
				
				<?php
				// Display course category filter if available
				$course_cats = get_terms( array(
					'taxonomy' => 'ld_course_category',
					'hide_empty' => true,
				) );
				
				if ( ! empty( $course_cats ) && ! is_wp_error( $course_cats ) ) :
				?>
					<div class="bb-rl-course-categories">
						<label for="ld-course-cats"><?php esc_html_e( 'Categories:', 'buddyboss' ); ?></label>
						<select id="ld-course-cats" onchange="if (this.value) window.location.href=this.value">
							<option value="<?php echo esc_url( get_post_type_archive_link( 'sfwd-courses' ) ); ?>"><?php esc_html_e( 'All Categories', 'buddyboss' ); ?></option>
							<?php foreach ( $course_cats as $cat ) : ?>
								<option value="<?php echo esc_url( get_term_link( $cat ) ); ?>" <?php selected( is_tax( 'ld_course_category', $cat->term_id ) ); ?>>
									<?php echo esc_html( $cat->name ); ?>
								</option>
							<?php endforeach; ?>
						</select>
					</div>
				<?php endif; ?>
			</div>
			
			<?php if ( have_posts() ) : ?>
				<div class="bb-rl-courses-grid">
					<?php while ( have_posts() ) : the_post(); ?>
						<?php
						$course_id = get_the_ID();
						$user_id = get_current_user_id();
						$is_enrolled = sfwd_lms_has_access( $course_id, $user_id );
						?>
						<div class="bb-rl-course-card">
							<article id="post-<?php the_ID(); ?>" <?php post_class( 'bb-rl-course-item' ); ?>>
								<div class="bb-rl-course-image">
									<a href="<?php the_permalink(); ?>">
										<?php if ( has_post_thumbnail() ) : ?>
											<?php the_post_thumbnail( 'medium' ); ?>
										<?php else : ?>
											<div class="bb-rl-course-placeholder-image"></div>
										<?php endif; ?>
										
										<?php if ( $is_enrolled ) : ?>
											<span class="bb-rl-course-status bb-rl-enrolled"><?php esc_html_e( 'Enrolled', 'buddyboss' ); ?></span>
										<?php endif; ?>
									</a>
								</div>
								
								<div class="bb-rl-course-content">
									<h2 class="bb-rl-course-title">
										<a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
									</h2>
									
									<div class="bb-rl-course-excerpt">
										<?php the_excerpt(); ?>
									</div>
									
									<div class="bb-rl-course-footer">
										<div class="bb-rl-course-author">
											<?php
											$author_id = get_the_author_meta( 'ID' );
											$author_name = get_the_author();
											?>
											<span class="bb-rl-author-avatar">
												<?php echo get_avatar( $author_id, 32 ); ?>
											</span>
											<span class="bb-rl-author-name"><?php echo esc_html( $author_name ); ?></span>
										</div>
										
										<a href="<?php the_permalink(); ?>" class="bb-rl-course-link">
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
							</article>
						</div>
					<?php endwhile; ?>
				</div>
				
				<div class="bb-rl-course-pagination">
					<?php
					echo paginate_links( array(
						'prev_text' => __( '<i class="bb-icons-rl-arrow-left"></i> Previous', 'buddyboss' ),
						'next_text' => __( 'Next <i class="bb-icons-rl-arrow-right"></i>', 'buddyboss' ),
					) );
					?>
				</div>
			<?php else : ?>
				<div class="bb-rl-no-courses">
					<p><?php esc_html_e( 'No courses found.', 'buddyboss' ); ?></p>
				</div>
			<?php endif; ?>
		</div>
	</main>

	<?php if ( $readylaunch->bb_is_sidebar_enabled_for_courses() ) : ?>
		<aside class="bb-learndash-sidebar">
			<div class="bb-rl-sidebar-content">
				<?php do_action( 'bb_readylaunch_learndash_sidebar' ); ?>
			</div>
		</aside>
	<?php endif; ?>
</div> 
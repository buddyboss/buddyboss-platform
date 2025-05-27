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

<div class="bb-rl-secondary-header flex items-center">
	<div class="bb-rl-entry-heading">
		<h1 class="bb-rl-page-title bb-rl-base-heading">
			<?php
			if ( is_tax() ) {
				echo single_term_title( '', false );
			} else {
				esc_html_e( 'Courses', 'buddyboss' );
			}
			?>
			<span class="bb-rl-heading-count">9</span>
		</h1>
	</div>

	<div class="bb-rl-course-filters bb-rl-sub-ctrls flex items-center">

		<div class="bb-rl-grid-filters flex items-center" data-view="ld-course">
			<a href="" class="layout-view layout-view-course layout-grid-view bp-tooltip active" data-view="grid" data-bp-tooltip-pos="down" data-bp-tooltip="<?php _e( 'Grid View', 'buddyboss' ); ?>">
				<i class="bb-icons-rl-squares-four"></i>
			</a>
			<a href="" class="layout-view layout-view-course layout-list-view bp-tooltip" data-view="list" data-bp-tooltip-pos="down" data-bp-tooltip="<?php _e( 'List View', 'buddyboss' ); ?>">
				<i class="bb-icons-rl-rows"></i>
			</a>
		</div>
				
		<?php
		// Display course category filter if available
		$course_cats = get_terms( array(
			'taxonomy' => 'ld_course_category',
			'hide_empty' => true,
		) );
		
		//if ( ! empty( $course_cats ) && ! is_wp_error( $course_cats ) ) :
		?>
			<div class="component-filters">
				<div class="bb-rl-course-categories bb-rl-filter">
					<label for="ld-course-cats" class="bb-rl-filter-label"><span><?php esc_html_e( 'Category', 'buddyboss' ); ?></span></label>
					<div class="select-wrap">
						<select id="ld-course-cats" onchange="if (this.value) window.location.href=this.value">
							<option value="<?php echo esc_url( get_post_type_archive_link( 'sfwd-courses' ) ); ?>"><?php esc_html_e( 'All Categories', 'buddyboss' ); ?></option>
							<?php foreach ( $course_cats as $cat ) : ?>
								<option value="<?php echo esc_url( get_term_link( $cat ) ); ?>" <?php selected( is_tax( 'ld_course_category', $cat->term_id ) ); ?>>
									<?php echo esc_html( $cat->name ); ?>
								</option>
							<?php endforeach; ?>
						</select>
					</div>
				</div>
			</div>
		<?php //endif; ?>
	</div>
</div>
<div class="bb-rl-container-inner bb-rl-learndash-content-wrap">
	<main class="bb-learndash-content-area">
		<div class="bb-rl-courses-list">
			
			<?php if ( have_posts() ) : ?>
				<div class="bb-rl-courses-grid grid">
					<?php while ( have_posts() ) : the_post(); ?>
						<?php
						$course_id = get_the_ID();
						$user_id = get_current_user_id();
						$is_enrolled = sfwd_lms_has_access( $course_id, $user_id );

						// Get course progress
						$course_progress = learndash_course_progress(
							array(
								'user_id'   => $user_id,
								'course_id' => $course_id,
								'array'     => true,
							)
						);

						// Course data
						$course = get_post( $course_id );
						$course_settings = learndash_get_setting( $course_id );
						$course_price = learndash_get_course_price( $course_id );
						$is_enrolled = sfwd_lms_has_access( $course_id, $user_id );
						$course_status = learndash_course_status( $course_id, $user_id );

						// Get course steps
						$course_steps = learndash_get_course_steps( $course_id );
						$lessons = learndash_get_course_lessons_list( $course_id );
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
									</a>
								</div>
								
								<div class="bb-rl-course-content">
									<div class="bb-rl-course-body">
										<h2 class="bb-rl-course-title">
											<a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
										</h2>

										<div class="bb-rl-course-meta">
											<?php if ( $is_enrolled ) : ?>
												<div class="bb-rl-course-status">
													<?php if ( ! empty( $course_progress ) ) : ?>
														<div class="bb-rl-course-progress">
															<span class="bb-rl-percentage"><span class="bb-rl-percentage-figure"><?php echo (int) $course_progress['percentage']; ?>%</span> <?php esc_html_e( 'Completed', 'buddyboss' ); ?></span>
															<div class="bb-rl-progress-bar">
																<div class="bb-rl-progress" style="width: <?php echo (int) $course_progress['percentage']; ?>%"></div>
															</div>
														</div>
													<?php endif; ?>
												</div>
											<?php else : ?>
												<div class="bb-rl-course-price">
													<?php if ( ! empty( $course_price['type'] ) && 'open' === $course_price['type'] ) : ?>
														<span class="bb-rl-price bb-rl-free"><?php esc_html_e( 'Free', 'buddyboss' ); ?></span>
													<?php elseif ( ! empty( $course_price['type'] ) && 'paynow' === $course_price['type'] ) : ?>
														<span class="bb-rl-price"><?php echo sprintf( esc_html__( 'Price: %s', 'buddyboss' ), esc_html( $course_price['price'] ) ); ?></span>
													<?php elseif ( ! empty( $course_price['type'] ) && 'subscribe' === $course_price['type'] ) : ?>
														<span class="bb-rl-price"><?php echo sprintf( esc_html__( 'Subscription: %s', 'buddyboss' ), esc_html( $course_price['price'] ) ); ?></span>
													<?php endif; ?>
												</div>
											<?php endif; ?>
										</div>
									</div>
									<div class="bb-rl-course-footer">
										
										<a href="<?php the_permalink(); ?>" class="bb-rl-course-link bb-rl-button bb-rl-button--secondaryFill bb-rl-button--small">
											<?php
											if ( $is_enrolled ) {
												esc_html_e( 'Continue', 'buddyboss' );
											} else {
												esc_html_e( 'View Course', 'buddyboss' );
											}
											?>
											<i class="bb-icons-rl-caret-right"></i>
										</a>
									</div>
								</div>
							</article>
							<div class="bb-rl-course-card-popup">
								<div class="bb-rl-course-timestamp"><?php esc_html_e( 'Updated: 20 May 2025', 'buddyboss' ); ?></div>
								<div class="bb-rl-course-popup-meta">
									<span class="bb-rl-course-meta-tag"><?php esc_html_e( '5 lessons', 'buddyboss' ); ?></span>
									<span class="bb-rl-course-meta-tag"><?php esc_html_e( 'Beginner', 'buddyboss' ); ?></span>
								</div>
								<div class="bb-rl-course-popup-caption">
									<?php the_excerpt(); ?>
								</div>
								<div class="bb-rl-course-author">
									<h4><?php esc_html_e( 'Instructor', 'buddyboss' ); ?></h4>
									<?php
									$author_id = get_the_author_meta( 'ID' );
									$author_name = get_the_author();
									?>
									<span class="bb-rl-author-avatar">
										<?php echo get_avatar( $author_id, 32 ); ?>
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
</div> 
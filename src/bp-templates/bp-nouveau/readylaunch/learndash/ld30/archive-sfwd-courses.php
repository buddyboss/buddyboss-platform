<?php
/**
 * LearnDash Course Archive Template for ReadyLaunch
 *
 * @since   BuddyBoss [BBVERSION]
 * @package BuddyBoss\Core
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Get the ReadyLaunch instance to check if sidebar is enabled.
$readylaunch = BB_Readylaunch::instance();

// Get the global query object.
global $wp_query;

// Get filter values.
$current_orderby    = isset( $_GET['orderby'] ) ? sanitize_text_field( wp_unslash( $_GET['orderby'] ) ) : 'alphabetical';
$current_category   = isset( $_GET['categories'] ) ? sanitize_text_field( wp_unslash( $_GET['categories'] ) ) : '';
$current_instructor = isset( $_GET['instructors'] ) ? sanitize_text_field( wp_unslash( $_GET['instructors'] ) ) : '';
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
			<?php
			$order_by_options = array(
				'alphabetical' => __( 'Alphabetical', 'buddyboss' ),
				'recent'       => __( 'Newly Created', 'buddyboss' ),
			);

			if ( is_user_logged_in() ) {
				$order_by_options['my-progress'] = __( 'My Progress', 'buddyboss' );
			}
			if ( ! empty( $order_by_options ) ) {
				$order_by_current = isset( $order_by_options[ $current_orderby ] ) ? $current_orderby : 'alphabetical';
				?>
				<div class="bb-rl-course-categories bb-rl-filter">
					<label for="ld-course-orderby" class="bb-rl-filter-label">
						<span><?php esc_html_e( 'Sort by', 'buddyboss' ); ?></span>
					</label>
					<div class="select-wrap">
						<select id="ld-course-orderby" name="orderby">
							<?php
							foreach ( $order_by_options as $key => $value ) {
								?>
								<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $order_by_current, $key ); ?>>
									<?php echo esc_html( $value ); ?>
								</option>
								<?php
							}
							?>
						</select>
					</div>
				</div>
				<?php
			}
			// Display course category filter if available.
			$course_cats = get_terms(
				array(
					'taxonomy'   => 'ld_course_category',
					'hide_empty' => true,
				)
			);
			if ( ! empty( $course_cats ) && ! is_wp_error( $course_cats ) ) {
				?>
				<div class="bb-rl-course-categories bb-rl-filter">
					<label for="ld-course-cats" class="bb-rl-filter-label">
						<span><?php esc_html_e( 'Category', 'buddyboss' ); ?></span>
					</label>
					<div class="select-wrap">
						<select id="ld-course-cats" name="filter-categories">
							<option value="all">
								<?php esc_html_e( 'All Categories', 'buddyboss' ); ?>
							</option>
							<?php
							foreach ( $course_cats as $cat_data ) {
								?>
								<option value="<?php echo esc_attr( $cat_data->slug ); ?>" <?php selected( $current_category, $cat_data->slug ); ?>>
									<?php echo esc_html( $cat_data->name ); ?>
								</option>
								<?php
							}
							?>
						</select>
					</div>
				</div>
				<?php
			}
			// Display course instructor filter if available.
			global $wpdb;
			$author_ids = $wpdb->get_col( "SELECT DISTINCT post_author FROM {$wpdb->posts} WHERE post_type = 'sfwd-courses' AND post_status = 'publish' ORDER BY post_title ASC" );
			if ( ! empty( $author_ids ) ) {
				?>
				<div class="bb-rl-course-instructors bb-rl-filter">
					<label for="ld-course-instructors" class="bb-rl-filter-label">
						<span><?php esc_html_e( 'Instructor', 'buddyboss' ); ?></span>
					</label>
					<div class="select-wrap">
						<select id="ld-course-instructors" name="filter-instructors">
							<option value="all">
								<?php esc_html_e( 'All', 'buddyboss' ); ?>
							</option>
							<?php
							foreach ( $author_ids as $author_id ) {
								$author_name = get_the_author_meta( 'display_name', $author_id );
								?>
								<option value="<?php echo esc_attr( $author_id ); ?>" <?php selected( (int) $current_instructor, (int) $author_id ); ?>>
									<?php echo esc_html( $author_name ); ?>
								</option>
								<?php
							}
							?>
						</select>
					</div>
				</div>
				<?php
			}
			?>
		</div>
	</div>
</div>
<div class="bb-rl-container-inner bb-rl-learndash-content-wrap">
	<main class="bb-learndash-content-area">
		<div class="bb-rl-courses-list">

			<?php
			if ( have_posts() ) :
				?>
				<div class="bb-rl-courses-grid grid">
					<?php
					while ( have_posts() ) :
						the_post();

						$course_id   = get_the_ID();
						$user_id     = get_current_user_id();
						$is_enrolled = sfwd_lms_has_access( $course_id, $user_id );

						// Get course progress.
						$course_progress = learndash_course_progress(
							array(
								'user_id'   => $user_id,
								'course_id' => $course_id,
								'array'     => true,
							)
						);

						// Course data.
						$course          = get_post( $course_id );
						$course_settings = learndash_get_setting( $course_id );
						$course_price    = learndash_get_course_price( $course_id );
						$is_enrolled     = sfwd_lms_has_access( $course_id, $user_id );
						$course_status   = learndash_course_status( $course_id, $user_id );

						// Get course steps.
						$course_steps  = learndash_get_course_steps( $course_id );
						$lessons       = learndash_get_course_lessons_list( $course_id );
						$lesson_count  = array_column( $lessons, 'post' );
						$lessons_count = ! empty( $lesson_count ) ? count( $lesson_count ) : 0;
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
											<?php
											$course_category = get_the_terms( $course_id, 'ld_course_category' );
											if ( ! empty( $course_category ) ) {
												?>
												<div class="bb-rl-course-category">
													<?php
													foreach ( $course_category as $category ) {
														echo '<span class="bb-rl-course-category-tag">' . esc_html( $category->name ) . '</span>';
													}
													?>
												</div>
												<?php
											}
											if ( $is_enrolled ) {
												?>
												<div class="bb-rl-course-status">
													<?php
													if ( ! empty( $course_progress ) ) {
														?>
														<div class="bb-rl-course-progress">
															<span class="bb-rl-percentage">
																<?php
																echo wp_kses_post(
																	sprintf(
																	/* translators: 1: course progress percentage, 2: percentage symbol. */
																		__( '<span class="bb-rl-percentage-figure">%1$s%2$s</span> Completed', 'buddyboss' ),
																		(int) $course_progress['percentage'],
																		'%'
																	)
																);
																?>
															</span>
															<?php
															// Get completed steps.
															$completed_steps = ! empty( $course_progress['completed'] ) ? (int) $course_progress['completed'] : 0;

															// Output as "completed/total".
															if ( $course_progress['total'] > 0 ) {
																?>
																<span class="bb-rl-course-steps">
																	<?php echo esc_html( $completed_steps . '/' . $course_progress['total'] ); ?>
																</span>
																<?php
															}
															?>
															<div class="bb-rl-progress-bar">
																<div class="bb-rl-progress" style="width: <?php echo (int) $course_progress['percentage']; ?>%"></div>
															</div>
														</div>
														<?php
													}
													?>
												</div>
												<?php
											}
											if ( ! $is_enrolled ) {
												?>
												<div class="bb-rl-course-author">
													<?php
													$user_link = bp_core_get_user_domain( get_the_author_meta( 'ID' ) );
													if ( ! empty( $user_link ) ) {
														?>
														<a class="item-avatar bb-rl-author-avatar" href="<?php echo esc_url( $user_link ); ?>">
															<?php echo get_avatar( get_the_author_meta( 'email' ), 80, '', '', array() ); ?>
														</a>
														<?php
													}
													?>
													<span class="bb-rl-author-name">
														<?php
														$author_name = get_the_author_meta( 'display_name' );
														// translators: %s is the author name.
														printf( esc_html__( 'By %s', 'buddyboss' ), '<a href="' . esc_url( $user_link ) . '">' . esc_html( $author_name ) . '</a>' );
														?>
													</span>
												</div>
												<?php
											}
											?>
										</div>
									</div>
									<div class="bb-rl-course-footer">
										<?php
										if ( $is_enrolled ) {
											?>
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
											<?php
										} else {
											?>
											<div class="bb-rl-course-footer-meta">
												<?php
												$currency = function_exists( 'learndash_get_currency_symbol' ) ? learndash_get_currency_symbol() : learndash_30_get_currency_symbol();
												$price    = $course_price['price'];
												if ( ! $is_enrolled && ! empty( $price ) ) {
													?>
													<div class="bb-rl-course-price">
															<span class="bb-rl-price">
																<span class="ld-currency">
																	<?php echo wp_kses_post( $currency ); ?>
																</span> 
																<?php echo wp_kses_post( $price ); ?>
															</span>
													</div>
													<?php
												}
												?>
											</div>
											<?php
										}
										?>
									</div>
								</div>
							</article>
							<div class="bb-rl-course-card-popup">
								<div class="bb-rl-course-timestamp">
									<?php
									$updated_date = get_the_date( 'j M, Y' );
									// translators: %s is the updated date.
									printf( esc_html__( 'Updated %s', 'buddyboss' ), esc_html( $updated_date ) );
									?>
								</div>
								<div class="bb-rl-course-popup-meta">
									<?php
									$total_lessons = (
									$lessons_count > 1
										? sprintf(
									/* translators: 1: plugin name, 2: action number 3: total number of actions. */
											__( '%1$s %2$s', 'buddyboss' ),
											$lessons_count,
											LearnDash_Custom_Label::get_label( 'lessons' )
										)
										: sprintf(
									/* translators: 1: plugin name, 2: action number 3: total number of actions. */
											__( '%1$s %2$s', 'buddyboss' ),
											$lessons_count,
											LearnDash_Custom_Label::get_label( 'lesson' )
										)
									);
									?>
									<span class="bb-rl-course-meta-tag"><?php echo esc_html( $total_lessons ); ?></span>
									<span class="bb-rl-course-meta-tag"><?php esc_html_e( 'Beginner', 'buddyboss' ); ?></span>
								</div>
								<div class="bb-rl-course-popup-caption">
									<?php
									add_filter( 'learndash_template_content_on_listing_is_hidden', '__return_false' );
									echo wp_kses_post( get_the_excerpt( $course_id ) );
									remove_filter( 'learndash_template_content_on_listing_is_hidden', '__return_false' );
									?>
								</div>
								<div class="bb-rl-course-author">
									<h4><?php esc_html_e( 'Instructor', 'buddyboss' ); ?></h4>
									<?php
									$author_id   = get_the_author_meta( 'ID' );
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
					echo wp_kses_post(
						paginate_links(
							array(
								'prev_text' => sprintf(
								/* translators: %s is the previous text. */
									'<i class="bb-icons-rl-arrow-left"></i> %s',
									esc_html__( 'Previous', 'buddyboss' )
								),
								'next_text' => sprintf(
								/* translators: %s is the next text. */
									'%s <i class="bb-icons-rl-arrow-right"></i>',
									esc_html__( 'Next', 'buddyboss' )
								),
							)
						)
					);
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
<?php
/**
 * Archive course header template for ReadyLaunch.
 *
 * @since   BuddyBoss 2.9.00
 * @package BuddyBoss\ReadyLaunch
 * @version 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$orderby_data       = BB_Readylaunch_Learndash_Helper::instance()->bb_rl_get_orderby_data();
$current_orderby    = $orderby_data['current_orderby'];
$current_category   = $orderby_data['current_category'];
$current_instructor = $orderby_data['current_instructor'];

?>
<div class="bb-rl-secondary-header flex items-center bb-rl-secondary-header--ldlms">
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
						<select id="ld-course-instructors" name="filter-instructors" data-dropdown-align="true">
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

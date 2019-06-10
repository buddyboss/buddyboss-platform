<?php
$course_id = get_the_ID();
$total = bp_search_get_total_lessons_count( $course_id );
$meta = get_post_meta( $course_id, '_sfwd-courses', true );
$course_price_type = @$meta['sfwd-courses_course_price_type'];
$course_price = @$meta['sfwd-courses_course_price'];
?>
<li class="bp-search-item bp-search-item_sfwd-courses">
	<div class="list-wrap">
		<div class="item-avatar">
			<a href="<?php the_permalink(); ?>">
				<img
					src="<?php echo get_the_post_thumbnail_url() ?: bp_search_get_post_thumbnail_default(get_post_type()) ?>"
					class="attachment-post-thumbnail size-post-thumbnail wp-post-image"
					alt="<?php the_title() ?>"
				/>
			</a>
		</div>

		<div class="item">
			<div class="entry-meta">
				<span><?php printf( _n( '%d lesson', '%s lessons', $total, 'buddyboss' ), $total ); ?></span>
			</div>

			<h3 class="entry-title item-title">
				<a href="<?php the_permalink(); ?>" title="<?php echo esc_attr( sprintf( __( 'Permalink to %s', 'buddyboss' ), the_title_attribute( 'echo=0' ) ) ); ?>" rel="bookmark"><?php the_title(); ?></a>
			</h3>

			<?php if ( ! empty( learndash_course_status( $course_id ) ) ): ?>
				<?php echo do_shortcode( "[learndash_course_progress course_id=$course_id]" ) ?>
				<div class="entry-meta">
					<span class="course-status">
						<?php echo learndash_course_status( $course_id, null, false ) ?>
					</span>
				</div>
			<?php endif; ?>


		</div>

		<?php // format the Course price to be proper XXX.YY no leading dollar signs or other values.
		if ( ( $course_price_type == 'paynow' ) || ( $course_price_type == 'subscribe' ) ) {
			if ( $course_price != '' ) {
				$course_price = preg_replace( "/[^0-9.]/", '', $course_price ); ?>
				<div class="item-extra"><?php echo number_format( floatval( $course_price ), 2, '.', '' ); ?></div>
				<?php
			}
		} ?>

	</div>
</li>

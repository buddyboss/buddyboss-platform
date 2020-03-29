<?php
$total              = bp_search_get_total_lessons_count( get_the_ID() );
$post_thumbnail_url = get_the_post_thumbnail_url();
?>
<div class="bp-search-ajax-item bp-search-ajax-item_sfwd-courses">
	<a href="<?php echo esc_url( add_query_arg( array( 'no_frame' => '1' ), get_permalink() ) ); ?>">
		<div class="item-avatar">
			<img
				src="<?php echo ! empty( $post_thumbnail_url ) ? esc_url( $post_thumbnail_url ) : esc_url( bp_search_get_post_thumbnail_default( get_post_type() ) ); ?>"
				class="attachment-post-thumbnail size-post-thumbnail wp-post-image"
				alt="<?php the_title(); ?>"
			/>
		</div>

		<div class="item">
			<div class="item-title"><?php the_title(); ?></div>
			<div class="item-desc">
			<?php
			// @todo remove %d?
			printf( _n( '%d lesson', '%d lessons', $total, 'buddyboss' ), $total );
			?>
			</div>

		</div>
	</a>
</div>

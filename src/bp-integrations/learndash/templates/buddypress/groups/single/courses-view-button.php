

<div class="course-link">
	<a class="ld-set-cookie" data-course-id="<?php echo esc_attr( get_the_ID() ); ?>" data-group-id="<?php echo esc_attr( ( bp_is_group_single() ? bp_get_current_group_id() : '' ) ); ?>" href="<?php the_permalink(); ?>" class="button"><?php echo $label; ?></a>
</div>

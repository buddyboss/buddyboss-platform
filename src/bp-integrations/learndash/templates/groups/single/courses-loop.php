<li class="item-entry">
    <div class="list-wrap">
        <div class="item-avatar">
            <a href="<?php the_permalink(); ?>">
                <?php if (has_post_thumbnail()): ?>
                	<?php the_post_thumbnail(); ?>
                <?php else: ?>
					<img src="<?php echo bp_learndash_url('/assets/images/mystery-course.png'); ?>" class="photo" />
                <?php endif; ?>
            </a>
        </div>

        <div class="item">
            <div class="item-block">
	            <h3 class="course-name">
	                <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
	            </h3>

	            <?php do_action('bp_ld_sync/courses_loop/after_title'); ?>
	        </div>
        </div>
    </div>
</li>

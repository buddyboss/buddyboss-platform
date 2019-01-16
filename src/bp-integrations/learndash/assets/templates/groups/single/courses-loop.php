<li class="item-entry">
    <div class="list-wrap">
        <div class="item-avatar">
            <a href="<?php the_permalink(); ?>">
                <?php if (has_post_thumbnail()): ?>
                	<?php the_post_thumbnail(); ?>
                <?php else: ?>
					<img src="<?php echo bp_ld_sync()->url('/assets/images/mystery-course.png'); ?>" />
                <?php endif; ?>
            </a>
        </div>

        <div class="item">
            <h3 class="course-name">
                <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
            </h3>

            <?php do_action('bp_ld_sync/courses_loop/after_title'); ?>
        </div>
    </div>
</li>

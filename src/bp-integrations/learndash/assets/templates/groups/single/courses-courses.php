<ul id="courses-list" class="item-list courses-group-list bp-list">
	<?php foreach (bp_ld_sync('buddypress')->courses->getGroupCourses() as $post): setup_postdata($post); ?>
		<?php bp_locate_template('groups/single/courses-loop.php', true, false); ?>
	<?php endforeach; wp_reset_postdata(); ?>
</ul>

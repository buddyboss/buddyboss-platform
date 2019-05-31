<?php
$count   = count( bp_ld_sync('buddypress')->courses->getGroupCourses() );
$courses_new = bp_ld_sync('buddypress')->courses->getGroupCourses();
?>

<div id="courses-group-list" class="group_courses dir-list" data-bp-list="group_courses">
    <ul id="courses-list" class="item-list courses-group-list bp-list">
		<?php
		foreach (bp_ld_sync('buddypress')->courses->getGroupCourses() as $post): setup_postdata($post);
			bp_locate_template('groups/single/courses-loop.php', true, false);


		endforeach;

		wp_reset_postdata(); ?>
	</ul>
</div>
<?php
if ( 1 === $count ) {
	?>
	<div class="single-course-content">
		<?php
		echo apply_filters( 'the_content', $courses_new[0]->post_content );
		echo do_shortcode( '[course_content course_id="'.$courses_new[0]->ID.'"]' );
		?>
	</div>
	<?php
}

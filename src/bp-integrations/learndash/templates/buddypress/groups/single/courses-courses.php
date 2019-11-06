<?php

global $courses_new;
$count = count( bp_ld_sync( 'buddypress' )->courses->getGroupCourses() );

$courses_new = bp_ld_sync( 'buddypress' )->courses->getGroupCourses();

if ( $count > 1 ) {
	?>

	<div id="courses-group-list" class="group_courses dir-list" data-bp-list="group_courses">
		<ul id="courses-list" class="item-list courses-group-list bp-list">
			<?php
			foreach ( bp_ld_sync( 'buddypress' )->courses->getGroupCourses() as $post ) :
				setup_postdata( $post );
				bp_locate_template( 'groups/single/courses-loop.php', true, false );
			endforeach;

			wp_reset_postdata();
			?>
		</ul>
	</div>

	<?php
}
if ( 1 === $count ) {
	bp_locate_template( 'groups/single/courses-content-display.php', true, false );
}

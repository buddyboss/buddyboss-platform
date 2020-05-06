<?php
/**
 * BuddyBoss - Groups Zoom Single Meeting
 *
 * @since BuddyBoss 1.2.10
 */

if ( bp_has_zoom_meetings( array( 'include' => bp_zoom_get_current_meeting_id() ) ) ) :

	bp_zoom_web_sdk_scripts();

	while ( bp_zoom_meeting() ) : bp_the_zoom_meeting();
		bp_get_template_part( 'single-meeting-item' );
	endwhile;
endif;


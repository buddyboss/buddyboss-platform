<?php
/**
 * BuddyBoss - Groups Zoom Meetings
 *
 * @since BuddyBoss 1.2.10
 */

$tab = false;
if ( bp_is_current_action( 'zoom' ) ) {
	if ( ! empty( bp_action_variable( 0 ) ) ) {
		$tab = bp_action_variable( 0 );
	} else {
		$tab = 'zoom';
	}
}

// subnav.
bp_get_template_part( 'groups/single/parts/zoom-subnav' );

switch ( $tab ) :

	// meetings.
	case 'zoom':
	case 'meetings':
	case 'past-meetings':
		if ( bp_zoom_is_single_meeting() ) {
			bp_get_template_part( 'groups/single/zoom/single-meeting' );
		} else if ( bp_zoom_is_edit_meeting() ) {
			bp_get_template_part( 'groups/single/zoom/edit-meeting' );
		} else {
			bp_get_template_part( 'groups/single/zoom/meetings' );
		}
		break;

	// create meeting.
	case 'create-meeting':
		bp_get_template_part( 'groups/single/zoom/create-meeting' );
		break;

	// Any other
	default:
		bp_get_template_part( 'groups/single/plugins' );
		break;
endswitch;

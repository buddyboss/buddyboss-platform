<?php
/**
 * BuddyBoss - Users Groups
 *
 * @since BuddyPress 3.0.0
 * @version 3.0.0
 */

switch ( bp_get_group_current_invite_tab() ) :

	case 'invite':
		bp_get_template_part( 'groups/single/invite/send-invites' );
		break;

	// Home/My Groups
	case 'send-invites':
		bp_get_template_part( 'groups/single/invite/send-invites' );
		break;

	// Group Invitations
	case 'pending-invites':
		bp_get_template_part( 'groups/single/invite/pending-invites' );
		break;

	// Any other
	default:
		bp_get_template_part( 'groups/single/plugins' );
		break;
endswitch;

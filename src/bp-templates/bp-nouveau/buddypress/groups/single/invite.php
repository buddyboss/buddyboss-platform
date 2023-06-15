<?php
/**
 * BuddyBoss - Groups Invites
 *
 * This template can be overridden by copying it to yourtheme/buddypress/groups/single/invite.php.
 *
 * @since   BuddyBoss 1.2.3
 * @version  1.2.3
 */

switch ( bp_get_group_current_invite_tab() ) :

	case 'invite':
		bp_get_template_part( 'groups/single/invite/send-invites' );
		break;

	// Send Invites.
	case 'send-invites':
		bp_get_template_part( 'groups/single/invite/send-invites' );
		break;

	// Group Invitations.
	case 'pending-invites':
		bp_get_template_part( 'groups/single/invite/pending-invites' );
		break;

	// Any other.
	default:
		bp_get_template_part( 'groups/single/plugins' );
		break;
endswitch;

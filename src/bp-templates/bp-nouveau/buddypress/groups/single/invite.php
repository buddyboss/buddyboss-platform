<?php
/**
 * BuddyBoss - Groups Invites
 *
 * This template can be overridden by copying it to yourtheme/buddypress/groups/single/invite.php.
 *
 * @since   BuddyBoss 1.2.3
 * @version  1.2.3
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

switch ( bboss_get_group_current_invite_tab() ) :

	case 'invite':
		bboss_get_template_part( 'groups/single/invite/send-invites' );
		break;

	// Send Invites.
	case 'send-invites':
		bboss_get_template_part( 'groups/single/invite/send-invites' );
		break;

	// Group Invitations.
	case 'pending-invites':
		bboss_get_template_part( 'groups/single/invite/pending-invites' );
		break;

	// Any other.
	default:
		bboss_get_template_part( 'groups/single/plugins' );
		break;
endswitch;

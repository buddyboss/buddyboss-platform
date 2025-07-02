<?php
/**
 * ReadyLaunch - Groups Invites template.
 *
 * This template handles the different invite-related pages for groups
 * including sending invites and viewing pending invitations.
 *
 * @package BuddyBoss\Template
 * @subpackage BP_Nouveau\ReadyLaunch
 * @since BuddyBoss 2.9.00
 * @version 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

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

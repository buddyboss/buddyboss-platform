<?php
/**
 * The template for invites
 *
 * This template can be overridden by copying it to yourtheme/buddypress/members/single/invites.php.
 *
 * @since   BuddyPress 3.0.0
 * @version 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

if ( bboss_is_my_profile() ) {
	bboss_get_template_part( 'members/single/parts/item-subnav' );
}

switch ( bboss_current_action() ) :

	// Home/My Groups
	case 'send-invites':
		bboss_get_template_part( 'members/single/invites/send-invites' );
		break;

	// Group Invitations
	case 'sent-invites':
		bboss_get_template_part( 'members/single/invites/sent-invites' );
		break;

	// Any other
	default:
		bboss_get_template_part( 'members/single/plugins' );
		break;
endswitch;

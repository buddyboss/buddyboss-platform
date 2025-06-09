<?php
/**
 * The template for invites
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @version 1.0.0
 */

if ( bp_is_my_profile() ) {
	echo '<div class="bb-rl-sub-ctrls flex items-center justify-between">';
	bp_get_template_part( 'members/single/parts/item-subnav' );
	echo '</div>';
}

echo '<div class="bb-rl-wrap-invites">';
switch ( bp_current_action() ) :

	// Home/My Groups.
	case 'send-invites':
		bp_get_template_part( 'members/single/invites/send-invites' );
		break;

	// Group Invitations.
	case 'sent-invites':
		bp_get_template_part( 'members/single/invites/sent-invites' );
		break;

	// Any other.
	default:
		bp_get_template_part( 'members/single/plugins' );
		break;
endswitch;
echo '</div>';

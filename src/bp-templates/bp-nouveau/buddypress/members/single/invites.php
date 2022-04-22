<?php
/**
 * The template for invites
 *
 * This template can be overridden by copying it to yourtheme/buddypress/members/single/invites.php.
 *
 * @since   BuddyPress 3.0.0
 * @version 1.0.0
 */
?>

<?php if ( bp_is_my_profile() ) : ?>
	<?php bp_get_template_part( 'members/single/parts/item-subnav' ); ?>
<?php endif; ?>

<?php

switch ( bp_current_action() ) :

	// Home/My Groups
	case 'send-invites':
		bp_get_template_part( 'members/single/invites/send-invites' );
		break;

	// Group Invitations
	case 'sent-invites':
		bp_get_template_part( 'members/single/invites/sent-invites' );
		break;

	// Any other
	default:
		bp_get_template_part( 'members/single/plugins' );
		break;
endswitch;

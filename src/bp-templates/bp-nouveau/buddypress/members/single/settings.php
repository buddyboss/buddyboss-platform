<?php
/**
 * BuddyBoss - Users Settings
 *
 * @version 3.0.0
 */

?>

<?php if ( bp_core_can_edit_settings() ) : ?>

	<?php bp_get_template_part( 'members/single/parts/item-subnav' ); ?>

<?php
endif;

switch ( bp_current_action() ) :
	case 'notifications':
		bp_get_template_part( 'members/single/settings/notifications' );
		break;
	case 'capabilities':
		bp_get_template_part( 'members/single/settings/capabilities' );
		break;
	case 'delete-account':
		bp_get_template_part( 'members/single/settings/delete-account' );
		break;
	case 'general':
		bp_get_template_part( 'members/single/settings/general' );
		break;
	case 'profile':
		bp_get_template_part( 'members/single/settings/profile' );
		break;
	case 'invites':
		bp_get_template_part( 'members/single/settings/group-invites' );
		break;
	case 'export':
		bp_get_template_part( 'members/single/settings/export-data' );
		break;
	case 'blocked-members':
		bp_get_template_part( 'members/single/settings/moderation' );
		break;
	default:
		bp_get_template_part( 'members/single/plugins' );
		break;
endswitch;

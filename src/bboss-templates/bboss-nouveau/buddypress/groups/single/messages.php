<?php
/**
 * BuddyBoss - Groups Messages
 *
 * This template can be overridden by copying it to yourtheme/buddypress/groups/single/messages.php.
 *
 * @since   BuddyPress 3.0.0
 * @version 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

bboss_get_template_part( 'groups/single/parts/messages-subnav' );
?>
	<div id="group-messages-container">
		<?php
		switch ( bboss_get_group_current_messages_tab() ) :
			case 'messages':
			case 'public-message': // Group Message.
				bboss_get_template_part( 'groups/single/messages/public-message' );
				break;
			// Group Invitations.
			case 'private-message':
				bboss_get_template_part( 'groups/single/messages/private-message' );
				break;
			// Any other.
			default:
				bboss_get_template_part( 'groups/single/plugins' );
				break;
		endswitch;
		?>
	</div>
<?php

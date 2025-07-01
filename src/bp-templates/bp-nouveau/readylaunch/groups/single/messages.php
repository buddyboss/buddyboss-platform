<?php
/**
 * ReadyLaunch - Groups Messages template.
 *
 * This template handles group messaging functionality including
 * public and private message interfaces with navigation.
 *
 * @package BuddyBoss\Template
 * @subpackage BP_Nouveau\ReadyLaunch
 * @since BuddyBoss 2.9.00
 * @version 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

bp_get_template_part( 'groups/single/parts/messages-subnav' );
?>
	<div id="group-messages-container">
		<?php
		switch ( bp_get_group_current_messages_tab() ) :
			case 'messages':
			case 'public-message': // Group Message.
				bp_get_template_part( 'groups/single/messages/public-message' );
				break;
			// Group Invitations.
			case 'private-message':
				bp_get_template_part( 'groups/single/messages/private-message' );
				break;
			// Any other.
			default:
				bp_get_template_part( 'groups/single/plugins' );
				break;
		endswitch;
		?>
	</div>
<?php

<?php
/**
 * BuddyBoss - Groups Messages
 *
 * This template can be overridden by copying it to yourtheme/buddypress/groups/single/messages.php.
 *
 * @since   BuddyPress 3.0.0
 * @version 1.0.0
 */
?>

	<?php bp_get_template_part( 'groups/single/parts/messages-subnav' ); ?>
	
	<div id="group-messages-container">

		<?php

		switch ( bp_get_group_current_messages_tab() ) :

			case 'messages':
				bp_get_template_part( 'groups/single/messages/public-message' );
				break;

			// Group Message.
			case 'public-message':
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

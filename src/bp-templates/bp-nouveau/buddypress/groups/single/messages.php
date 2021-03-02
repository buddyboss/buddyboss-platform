<?php
/**
 * BuddyBoss - Groups Messages
 *
 * @since BuddyPress 3.0.0
 * @version 3.0.0
 */
?>

	<div id="group-messages-container">

		<?php
		bp_get_template_part( 'groups/single/parts/messages-subnav' );

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

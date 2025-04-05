<?php
/**
 * ReadyLaunch - The template for users messages.
 *
 * @since   BuddyBoss [BBVERSION]
 * @version 1.0.0
 */

?>

<div class="messages-wrapper">
	<div class="messages-screen">
		<?php
		if ( ! in_array( bp_current_action(), array( 'inbox', 'starred', 'view', 'compose', 'notices', 'archived' ), true ) ) :
			bp_get_template_part( 'members/single/plugins' );
		else :
			bp_nouveau_messages_member_interface();
		endif;
		?>
	</div>
</div>

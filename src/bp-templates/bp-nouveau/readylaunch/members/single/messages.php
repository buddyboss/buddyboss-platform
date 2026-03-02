<?php
/**
 * ReadyLaunch - Member Messages template.
 *
 * This template handles displaying member messages interface.
 *
 * @package BuddyBoss\Template
 * @subpackage BP_Nouveau\ReadyLaunch
 * @since BuddyBoss 2.9.00
 * @version 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

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

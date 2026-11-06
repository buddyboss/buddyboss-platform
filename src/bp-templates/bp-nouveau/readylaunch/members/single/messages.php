<?php
/**
 * ReadyLaunch - Member Messages template.
 *
 * This template handles displaying member messages interface.
 *
 * @package BuddyBoss\Template
 * @subpackage BBoss_Nouveau\ReadyLaunch
 * @since BuddyBoss 2.9.00
 * @version 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

?>

<div class="messages-wrapper">
	<div class="messages-screen">
		<?php
		if ( ! in_array( bboss_current_action(), array( 'inbox', 'starred', 'view', 'compose', 'notices', 'archived' ), true ) ) :
			bboss_get_template_part( 'members/single/plugins' );
		else :
			bboss_nouveau_messages_member_interface();
		endif;
		?>
	</div>
</div>
